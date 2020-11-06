<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$arr_host = explode('.', $_SERVER['HTTP_HOST']);

/**
 * @since HD 898307 - redireciona pro ww2 como solução [temporária] para os problemas de envio de email
 */
if ($arr_host[0] != "ww2") {

	$cookie_admin = $_COOKIE['cook_admin'];
	$cookie_fabrica = $_COOKIE['cook_fabrica'];
	
	if (isset($_COOKIE['cook_master'])) {
		$t = md5($_COOKIE['cook_master']);
		$a = md5($cookie_admin);
		$token = md5($_COOKIE['cook_master'] . $cook_fabrica);
	} else {
		$t = '0';
		$a = '0';
		$token = md5($cookie_admin . $cook_fabrica);
	}

	$params = '?t=' . $t  . '&a=' . $a . '&token=' . $token;

	echo '<meta http-equiv="Refresh" content="0 ; url=http://ww2.telecontrol.com.br/assist/admin/os_cadastro_troca_black_teste.php' . $params . '" />';
	exit;
} else {

	if (isset($_GET['t']) and isset($_GET['a']) and isset($_GET['token'])) {
		$t = $_GET['t'];
		$a = $_GET['a'];
		$token = $_GET['token'];

		if ($t <> '0') {
			$sql_master = "SELECT admin FROM tbl_admin WHERE md5(admin::text) = '$a' AND ativo = 't'";
			$qry_master = pg_query($con, $sql_master);

			if (pg_num_rows($qry_master) == 0) {
				echo '<meta http-equiv="Refresh" content="0 ; url=http://www.telecontrol.com.br" />';
				exit;
			}

			setcookie('cook_admin', pg_fetch_result($qry_master, 0, 'admin'));

			$sql_fab = "SELECT nome, fabrica FROM tbl_fabrica WHERE  md5(nome || fabrica) = '$token'";
			$qry_fab = pg_query($con, $sql_fab);

			if (pg_num_rows($qry_master) == 0) {
				echo '<meta http-equiv="Refresh" content="0 ; url=http://www.telecontrol.com.br" />';
				exit;
			}

			setcookie('cook_master', pg_fetch_result($qry_fab, 0, 'nome'));
			setcookie('cook_fabrica', pg_fetch_result($qry_fab, 0, 'fabrica'));

		} else {

			$sql_chk = "SELECT tbl_admin.admin, tbl_fabrica.fabrica FROM tbl_admin
						JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_admin.fabrica
						WHERE md5(tbl_admin.admin::text || tbl_fabrica.fabrica) = '$token'";
			$qry_chk = pg_query($con, $sql_chk);

			if (pg_num_rows($qry_chk) == 0) {
				echo '<meta http-equiv="Refresh" content="0 ; url=http://www.telecontrol.com.br" />';
				exit;
			}

			setcookie('cook_admin', pg_fetch_result($qry_chk, 0, 'admin'));
			setcookie('cook_fabrica', pg_fetch_result($qry_chk, 0, 'fabrica'));

		}
	}

}

include 'autentica_admin.php';
include 'funcoes.php';

$admin_privilegios = 'call_center,gerencia';

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_query($con,$sql);
$pedir_sua_os = pg_fetch_result($res, 0, 'pedir_sua_os');

/*  MLG 26/10/2010 - Toda a rotina de anexo de imagem da NF, inclusive o array com os parâmetros por fabricante, está num include.
	Para saber se a fábrica pede imagem da NF, conferir a variável (bool) '$anexaNotaFiscal'
	Para anexar uma imagem, chamar a função anexaNF($os, $_FILES['foto_nf'])
	Para saber se tem anexo:temNF($os, 'bool');
	Para saber se 2º anexo: temNF($os, 'bool', 2);
	Para mostrar a imagem:  echo temNF($os); // Devolve um link: <a href='imagem' blank><img src='imagem[thumb]'></a>
							echo temNF($os, , 'url'); // Devolve a imagem (<img src='imagem'>)
							echo temNF($os, , 'link', 2); // Devolve um link da 2ª imagem
*/
include_once('../anexaNF_inc.php');

if (strlen($_POST['os']) > 0) {
	$os = trim($_POST['os']);
}

if (strlen($_GET['os']) > 0) {
	$os = trim($_GET['os']);
}

##AJAX##
if ($_REQUEST['ajax'] == 'true'){

	if ($_REQUEST['action'] == 'mostra_cidades'){
		
		$uf = $_REQUEST['uf'];

		$sql = "SELECT cidade from tbl_ibge where estado = '$uf' order by cidade";
		$res = pg_query($con,$sql);

		for ($i=0; $i < pg_num_rows($res); $i++) { 
			$cidade_ibge = pg_fetch_result($res, $i, 'cidade');
			echo "<option value='$cidade_ibge'> $cidade_ibge </option>";
		}

	}
	exit;

}

// HD 145639 - Quantos campos de produtos irão aparecer para selecionar os produtos de troca
if ($os) {

	$sql = "SELECT os FROM tbl_os WHERE os = " . $os;
	$res = pg_query($con, $sql);

	if (pg_num_rows($res)) {

		$sql = "SELECT COUNT(os_item)
				  FROM tbl_os
				  JOIN tbl_os_produto ON tbl_os.os=tbl_os_produto.os
				  JOIN tbl_os_item ON tbl_os_produto.os_produto=tbl_os_item.os_produto
				 WHERE tbl_os.os=" . $os;

		$res = pg_query($con, $sql);

		$numero_produtos_troca = pg_fetch_result($res, 0, 0);

	} else {
		$numero_produtos_troca = 1;
	}

} else {
	$numero_produtos_troca = 1;
}

if (strlen($_POST['sua_os']) > 0) {
	$sua_os = trim($_POST['sua_os']);
}

if (strlen($_GET['sua_os']) > 0) {
	$sua_os = trim($_GET['sua_os']);
}

$btn_acao = strtolower ($_POST['btn_acao']);

if ($btn_acao == "continuar") {

	$msg_erro = "";
	$os = $_POST['os'];

	$produto_referencia = strtoupper(trim($_POST['produto_referencia']));
	$produto_referencia = str_replace("-","",$produto_referencia);
	$produto_referencia = str_replace(" ","",$produto_referencia);
	$produto_referencia = str_replace("/","",$produto_referencia);
	$produto_referencia = str_replace(".","",$produto_referencia);

	// HD 145639: TROCAR UM PRODUTO POR UM OU MAIS
	$numero_produtos_troca_digitados = 0;
	for($p = 0; $p < $numero_produtos_troca; $p++)
	{
		if ($_POST["produto_troca$p"])
		{
			$voltagem = "'". $_POST['produto_voltagem'] ."'";

			$sql = "SELECT tbl_produto.produto, tbl_produto.linha
					FROM   tbl_produto
					JOIN   tbl_linha USING (linha)
					WHERE  UPPER(tbl_produto.referencia_pesquisa) = UPPER('$produto_referencia') ";
			if ($login_fabrica == 1) {
				$voltagem_pesquisa = str_replace("'","",$voltagem);
				$sql .= " AND tbl_produto.voltagem ILIKE '%$voltagem_pesquisa%'";
			}

			$sql .= " AND    tbl_linha.fabrica      = $login_fabrica
					AND    tbl_produto.ativo IS TRUE";
					
			$res = @pg_query($con,$sql);

			if (@pg_num_rows($res) == 0) {
				$msg_erro = " Produto $produto_referencia não cadastrado";
			} else {
				$produto = @pg_fetch_result($res,0,produto);
			}

			if ($_POST["produto_referencia_troca$p"] == "KIT") {

				$sql = "SELECT
							tbl_produto_troca_opcao.produto_opcao,
							tbl_produto.referencia,
							tbl_produto.descricao,
							tbl_produto.voltagem
						FROM tbl_produto_troca_opcao
						JOIN tbl_produto ON tbl_produto_troca_opcao.produto_opcao = tbl_produto.produto
						WHERE tbl_produto_troca_opcao.produto = " . $produto . "
							AND tbl_produto_troca_opcao.kit = " . $_POST["produto_troca$p"];

				$res = pg_query($con, $sql);

				for ($k = 0; $k < pg_num_rows($res); $k++) {

					$produto_troca				[$numero_produtos_troca_digitados] = pg_fetch_result($res, $k, produto_opcao);
					$produto_referencia_troca	[$numero_produtos_troca_digitados] = pg_fetch_result($res, $k, referencia);
					$produto_descricao_troca	[$numero_produtos_troca_digitados] = pg_fetch_result($res, $k, descricao);
					$produto_voltagem_troca		[$numero_produtos_troca_digitados] = pg_fetch_result($res, $k, voltagem);
					$produto_observacao_troca	[$numero_produtos_troca_digitados] = trim($_POST["produto_observacao_troca$p"]);

					$numero_produtos_troca_digitados++;

				}

			} else {

				$produto_troca				[$numero_produtos_troca_digitados] = trim($_POST["produto_troca$p"]);
				$produto_os_item			[$numero_produtos_troca_digitados] = trim($_POST["produto_os_troca$p"]);
				$produto_referencia_troca	[$numero_produtos_troca_digitados] = trim($_POST["produto_referencia_troca$p"]);
				$produto_descricao_troca	[$numero_produtos_troca_digitados] = trim($_POST["produto_descricao_troca$p"]);
				$produto_voltagem_troca		[$numero_produtos_troca_digitados] = trim($_POST["produto_voltagem_troca$p"]);
				$produto_observacao_troca	[$numero_produtos_troca_digitados] = trim($_POST["produto_observacao_troca$p"]);

				$numero_produtos_troca_digitados++;

			}

		}

	}

	if (strlen(trim($sua_os)) == 0) {
		$sua_os = 'null';
		if ($pedir_sua_os == 't') {
			$msg_erro .= " Digite o número da OS Fabricante.";
		}
	} else {
		$sua_os = "'" . $sua_os . "'" ;
	}

	// explode a sua_os
	$fOsRevenda = 0;
	$expSua_os = explode("-",$sua_os);
	$sql = "SELECT sua_os
			FROM   tbl_os_revenda
			WHERE  sua_os = $expSua_os[0]
			AND    fabrica      = $login_fabrica";

	$res = @pg_query($con,$sql);

	if (@pg_num_rows($res) != 0) {
		$fOsRevenda = 1;
	}
		$data_nf =trim($_POST['data_nf']);

	$tipo_atendimento = $_POST['tipo_atendimento'];
	if (strlen(trim($tipo_atendimento)) == 0) $msg_erro .= " Escolha o Tipo de Atendimento<br />";

	$consumidor_revenda="C";

	if (strlen($msg_erro) == 0){
		#------------ Atualiza Dados do Consumidor ----------
		$cidade = strtoupper(trim($_POST['consumidor_cidade']));
		$estado = strtoupper(trim($_POST['consumidor_estado']));

		if (strlen($estado) == 0) $msg_erro .= " Digite o estado do consumidor. <br />";
		if (strlen($cidade) == 0) $msg_erro .= " Digite a cidade do consumidor. <br />";

		if ($tipo_atendimento <> 18) {
			$xdata_nf = fnc_formata_data_pg(trim($_POST['data_nf']));
			if ($xdata_nf == null AND $xtroca_faturada <> 't') $msg_erro .= " Digite a data de compra.";
		}

		$nome = trim($_POST['consumidor_nome']) ;

		if (strlen(trim($_POST['fisica_juridica'])) == 0) $msg_erro = "Escolha o Tipo Consumidor.<br /> ";
		else $xfisica_juridica = "'".($_POST['fisica_juridica'])."'";

		$cpf = trim($_POST['consumidor_cpf']) ;
		$cpf = str_replace(".","",$cpf);
		$cpf = str_replace("-","",$cpf);
		$cpf = str_replace("/","",$cpf);
		$cpf = str_replace(",","",$cpf);
		$cpf = str_replace(" ","",$cpf);

		if (strlen($cpf) == 0) $xcpf = "null";
		else                   $xcpf = $cpf;

		if (strlen($xcpf) > 0 and $xcpf <> "null") $xcpf = "'" . $xcpf . "'";

		$rg     = trim($_POST['consumidor_rg']) ;

		if (strlen($rg) == 0) $rg = "null";
		else                  $rg = "'" . $rg . "'";

		$fone		= trim($_POST['consumidor_fone']) ;
		$endereco	= trim($_POST['consumidor_endereco']) ;
		if ($login_fabrica == 2 || $login_fabrica == 1) {
			if (strlen($endereco) == 0) $msg_erro .= " Digite o endereço do consumidor. <br />";
		}
		$numero        = trim($_POST['consumidor_numero']);
		$complemento   = trim($_POST['consumidor_complemento']) ;
		$bairro        = trim($_POST['consumidor_bairro']) ;
		$cep           = trim($_POST['consumidor_cep']) ;
		$admin_autoriza= trim($_POST['admin_autoriza']) ;
		$causa_troca   = trim($_POST['causa_troca']) ;
		$multi_peca    = $_POST['multi_peca'];
		$obs_causa     = trim($_POST['obs_causa']) ;
		$numero_processo= trim($_POST['numero_processo']) ;
		$v_os1         = trim($_POST['v_os1']) ;
		$v_os2         = trim($_POST['v_os2']) ;
		// $v_os3         = trim($_POST['v_os3']) ;
		
		if ($login_fabrica == 1) {
			if (strlen($numero) == 0) $msg_erro .= " Digite o número do endereço do consumidor. <br />";
			if (strlen($bairro) == 0) $msg_erro .= " Digite o bairro do consumidor. <br />";
		}

		if(empty($admin_autoriza)) {
			$msg_erro .= "Por favor, selecione o admin que autoriza";
		}

		if(empty($causa_troca)) {
			$msg_erro .= "Por favor, selecione o motivo da troca";
		}

		if (strlen($complemento) == 0) $complemento = "null";
		else                           $complemento = "'" . $complemento . "'";

		if($_POST['consumidor_contrato'] == 't' ) $contrato	= 't';
		else                                      $contrato	= 'f';

		$cep = str_replace(".","",$cep);
		$cep = str_replace("-","",$cep);
		$cep = str_replace("/","",$cep);
		$cep = str_replace(",","",$cep);
		$cep = str_replace(" ","",$cep);
		$cep = substr($cep,0,8);

		if (strlen($cep) == 0) $cep = "null";
		else                   $cep = "'" . $cep . "'";

		if ($login_fabrica == 1 AND strlen($cpf) == 0) {
			$cpf = 'null';
		}

	}

	$tipo_atendimento = $_POST['tipo_atendimento'];
	if (strlen(trim($tipo_atendimento)) == 0) $tipo_atendimento = 'null';

	$segmento_atuacao = $_POST['segmento_atuacao'];
	if (strlen(trim($segmento_atuacao)) == 0) $segmento_atuacao = 'null';

	if ($tipo_atendimento == '15' or $tipo_atendimento == '16') {
		if (strlen(trim($_POST['autorizacao_cortesia'])) == 0) $msg_erro = 'Digite autorização cortesia.';
		else           $autorizacao_cortesia = "'".trim($_POST['autorizacao_cortesia'])."'";
	} else {
		if (strlen(trim($_POST['autorizacao_cortesia'])) == 0) $autorizacao_cortesia = 'null';
		else           $autorizacao_cortesia = "'".trim($_POST['autorizacao_cortesia'])."'";
	}

	$posto_codigo = trim($_POST['posto_codigo']);
	$posto_codigo = str_replace("-","",$posto_codigo);
	$posto_codigo = str_replace(".","",$posto_codigo);
	$posto_codigo = str_replace("/","",$posto_codigo);
	$posto_codigo = substr($posto_codigo,0,14);

	if (!strlen($posto_codigo)) $msg_erro = "Selecione o posto para a abertura da OS";

	$res = pg_query($con,"SELECT * FROM tbl_posto JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica WHERE tbl_posto_fabrica.codigo_posto = '$posto_codigo'");
	$posto = @pg_fetch_result($res,0,0);

	if ($causa_troca == 130){

		$estado_causa_troca = trim($_REQUEST['estado_causa_troca']);
		$cidade_causa_troca = trim($_REQUEST['cidade_causa_troca']);

		if (empty($estado_causa_troca)){
			$msg_erro .= "Informe o ESTADO do posto para o motivo selecionado";
		}

		if (empty($cidade_causa_troca)){
			$msg_erro .= "Informe a CIDADE do posto para o motivo selecionado";
		}

	}

	if (!empty($posto) and $causa_troca == 126) {
		if (!empty($v_os1)) {

			$xv_os1 = explode($posto_codigo,$v_os1);
			$sql = " SELECT sua_os,finalizada
				FROM tbl_os
				WHERE fabrica = $login_fabrica
				AND   posto = $posto
				AND   excluida IS NOT true
				AND   sua_os like '%".$xv_os1[1]."';";echo "<br>";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				if(strlen(pg_fetch_result($res,0,1)) == 0) {
					$msg_erro .= "$v_os1 não finalizada, não é permitido o cadastro de OS<br/>";
				}
			} else {
				$msg_erro .="A OS $v_os1 não é do posto informado nessa OS<br/>";
			}
		}

		if (!empty($v_os2)) {
			$xv_os2 = explode($posto_codigo,$v_os2);
			$sql = " SELECT sua_os,finalizada
					FROM tbl_os
					WHERE fabrica = $login_fabrica
					AND   posto = $posto
					AND   excluida IS NOT TRUE
					AND   sua_os like '%$xv_os2[1]';";
			$res = pg_query($con,$sql);
			if (pg_num_rows($res) > 0) {
				if(strlen(pg_fetch_result($res,0,1)) == 0) {
					$msg_erro .= "$v_os2 não finalizada, não é permitido o cadastro de OS<br/>";
				}
			} else {
				$msg_erro .="A OS $v_os2 não é do posto informado nessa OS<br/>";
			}
		}

		/*if (!empty($v_os3)) {
			$xv_os3 = explode($posto_codigo,$v_os3);
			$sql = " SELECT sua_os,finalizada
					FROM tbl_os
					WHERE fabrica = $login_fabrica
					AND   posto = $posto
					AND   excluida IS NOT TRUE
					AND   sua_os like '%$xv_os3[1]';";
			$res = pg_query($con,$sql);
			if (pg_num_rows($res) > 0) {
				if (strlen(pg_fetch_result($res,0,1)) == 0) {
					$msg_erro .= "$v_os3 não finalizada, não é permitido o cadastro de OS<br/>";
				}
			} else {
				$msg_erro .="A OS $v_os3 não é do posto informado nessa OS<br/>";
			}
		}*/
	}

	#HD 231110
	$locacao_serie = $_POST['locacao_serie'];
	$data_abertura = trim($_POST['data_abertura']);
	$data_abertura = fnc_formata_data_pg($data_abertura);

	$consumidor_nome   = str_replace("'","",$_POST['consumidor_nome']);
	$consumidor_cidade = str_replace("'","",$_POST['consumidor_cidade']);
	$consumidor_estado = $_POST['consumidor_estado'];
	$consumidor_fone   = $_POST['consumidor_fone'];

	$consumidor_cpf = trim($_POST['consumidor_cpf']);
	$consumidor_cpf = str_replace("-","",$consumidor_cpf);
	$consumidor_cpf = str_replace(".","",$consumidor_cpf);
	$consumidor_cpf = str_replace("/","",$consumidor_cpf);
	$consumidor_cpf = trim(substr($consumidor_cpf,0,14));

	if (strlen($consumidor_cpf) == 0) $xconsumidor_cpf = 'null';
	else                              $xconsumidor_cpf = "'".$consumidor_cpf."'";

	$consumidor_fone = strtoupper(trim($_POST['consumidor_fone']));

	$consumidor_email = trim($_POST['consumidor_email']) ;
	// HD 18051
	if(strlen($consumidor_email) ==0 ){
		$msg_erro .="Digite o email de contato. <br />";
	} else {
		$consumidor_email = trim($_POST['consumidor_email']);
	}

	$revenda_cnpj = trim($_POST['revenda_cnpj']);
	$revenda_cnpj = str_replace("-","",$revenda_cnpj);
	$revenda_cnpj = str_replace(".","",$revenda_cnpj);
	$revenda_cnpj = str_replace("/","",$revenda_cnpj);
	$revenda_cnpj = substr($revenda_cnpj,0,14);

	// HD  22391
		if (strlen($revenda_cnpj) == 0){
			if($tipo_atendimento ==17){
				$msg_erro .= " Digite CNPJ da revenda. <br />";
			} else {
				$xrevenda_cnpj = 'null';
			}
		} else {

			if ($login_fabrica == 1) {

				// HD 37000
				function Valida_CNPJ($cnpj) {

					$cnpj = preg_replace( "@[./-]@", "", $cnpj );

					if (strlen($cnpj) <> 14 or !is_numeric($cnpj)) {
						return "errado";
					}

					$k = 6;
					$soma1 = "";
					$soma2 = "";

					for ($i = 0; $i < 13; $i++) {
						$k = $k == 1 ? 9 : $k;
						$soma2 += ( $cnpj{$i} * $k );
						$k--;
						if($i < 12){
							if($k == 1){
								$k = 9;
								$soma1 += ( $cnpj{$i} * $k );
								$k = 1;
							} else {
							$soma1 += ( $cnpj{$i} * $k );
							}
						}
					}

					$digito1 = $soma1 % 11 < 2 ? 0 : 11 - $soma1 % 11;
					$digito2 = $soma2 % 11 < 2 ? 0 : 11 - $soma2 % 11;

					return ( $cnpj{12} == $digito1 and $cnpj{13} == $digito2 ) ? "certo" : "errado" ;

				}

			}

			if ($login_fabrica == 1) {

				$valida_cnpj = Valida_CNPJ("$revenda_cnpj");

				if ($valida_cnpj == 'errado') {
					$msg_erro.="CNPJ da revenda inválida";
				}

			}

			$xrevenda_cnpj = "'".$revenda_cnpj."'";

		}

		if (strlen(trim($_POST['revenda_nome'])) == 0) {

			if ($tipo_atendimento == 17) {
				$msg_erro .= " Digite o Nome da revenda. <br />";
			} else {
				$xrevenda_nome = 'null';
			}

		} else {
			$xrevenda_nome = "'".str_replace("'","",trim($_POST['revenda_nome']))."'";
		}

		if (strlen($xrevenda_cnpj) > 0 AND strlen($msg_erro) == 0) {
			$sql  = "SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj";
			$res1 = pg_query($con,$sql);

			if (pg_num_rows($res1) > 0) {
				$revenda = pg_fetch_result($res1,0,revenda);
				$sql = "UPDATE tbl_revenda SET
							nome		= $xrevenda_nome     ,
							cnpj		= $xrevenda_cnpj
						WHERE tbl_revenda.revenda = $revenda";
				$res3 = @pg_query($con,$sql);
				$msg_erro .= pg_errormessage ($con);
			} else {
				$sql = "INSERT INTO tbl_revenda (
						nome,
						cnpj
					) VALUES (
						$xrevenda_nome ,
						$xrevenda_cnpj
					)";

				$res3 = @pg_query($con,$sql);
				$msg_erro .= pg_errormessage ($con);

				$sql = "SELECT currval ('seq_revenda')";
				$res3 = @pg_query($con,$sql);
				$revenda = @pg_fetch_result($res3,0,0);
			}
		}

	$nota_fiscal  = $_POST['nota_fiscal'];

	$xtroca_faturada = " NULL ";
	$xtroca_garantia = " NULL ";

	if (strlen($_POST['troca_faturada']) == 0) $xtroca_faturada = 'null';
	else        $xtroca_faturada = "'".trim($_POST['troca_faturada'])."'";

	# Alterado por Fabio - HD 10513, só para organizar melhor
	if($tipo_atendimento == 18){
		$xtroca_faturada = " 't' ";
		$xtroca_garantia = " NULL ";
	} else {
		$xtroca_faturada = " NULL ";
		$xtroca_garantia = " 't' ";
	}

	$data_nf = trim($_POST['data_nf']);
	$data_nf = fnc_formata_data_pg($data_nf);

	if (strlen(trim($_POST['obs_reincidencia'])) == 0) $xobs_reincidencia = "''";
	else                                               $xobs_reincidencia = "'".trim($_POST['obs_reincidencia'])."'";

	$voltagem               = strtoupper(trim($_POST['produto_voltagem']));
	$produto_serie          = strtoupper(trim($_POST['produto_serie']));
	$admin_paga_mao_de_obra = $_POST['admin_paga_mao_de_obra'];

	if ($admin_paga_mao_de_obra == 'admin_paga_mao_de_obra')
		$admin_paga_mao_de_obra = 't';
	else
		$admin_paga_mao_de_obra = 'f';

	$qtde_produtos     = strtoupper(trim($_POST['qtde_produtos']));
	$aparencia_produto = strtoupper(trim($_POST['aparencia_produto']));
	$acessorios        = strtoupper(trim($_POST['acessorios']));
	$orientacao_sac    = trim($_POST['orientacao_sac']);
	$orientacao_sac    = htmlentities($orientacao_sac,ENT_QUOTES);
	$orientacao_sac    = nl2br ($orientacao_sac);

	if (strlen($posto) > 0) {

		$sql  = "select pais from tbl_posto where posto = $posto";
		$res  = pg_query($con, $sql);
		$pais = pg_fetch_result($res, 0, pais);

	}

	/*IGOR HD 2935 - Quando pais for diferente de Brasil não tem CNPJ (bosch)*/
	if ($pais == "BR") {
		if (strlen($revenda_cnpj) <> 0 and strlen($revenda_cnpj) <> 14) $msg_erro .= "Tamanho do CNPJ da revenda inválido.";
	}

	if (strlen($produto_referencia) == 0) $msg_erro .= " Digite o produto.";

	$xquem_abriu_chamado = trim($_POST['quem_abriu_chamado']);

	if (strlen($xquem_abriu_chamado) == 0) $xquem_abriu_chamado = 'null';
	else $xquem_abriu_chamado = "'".$xquem_abriu_chamado."'";

	$xobs = trim($_POST['obs']);
	if (strlen($xobs) == 0) $xobs = 'null';
	else                    $xobs = "'".$xobs.".";

	// Campos da Black & Decker
	if ($login_fabrica == 1) {

		if (strlen(trim($_POST['codigo_fabricacao'])) == 0) $codigo_fabricacao = 'null';
		else $codigo_fabricacao = "'".trim($_POST['codigo_fabricacao'])."'";

		if (strlen($_POST['satisfacao']) == 0) $satisfacao = "f";
		else                                   $satisfacao = "t";

		if (strlen($_POST['laudo_tecnico']) == 0) $laudo_tecnico = 'null';
		else                                      $laudo_tecnico = "'".trim($_POST['laudo_tecnico'])."'";

		if ($satisfacao == 't' AND strlen($_POST['laudo_tecnico']) == 0) {
			$msg_erro .= " Digite o Laudo Técnico.";
		}

	}

	if (strlen(trim($data_nf)) <> 12 and $login_fabrica==1) {
		$data_nf = "null";
	}

	if (strlen($data_abertura) <> 12) {
		$msg_erro .= " Digite a data de abertura da OS.";
	} else {
		$cdata_abertura = str_replace("'","",$data_abertura);
	}

	if (strlen($qtde_produtos) == 0) $qtde_produtos = "1";

	// se ? uma OS de revenda
	if ($fOsRevenda == 1){

		if (strlen($nota_fiscal) == 0){
			$nota_fiscal = "null";
			//$msg_erro = "Entre com o n?mero da Nota Fiscal";
		} else
			$nota_fiscal = "'" . $nota_fiscal . "'" ;

		if (strlen($aparencia_produto) == 0)
			$aparencia_produto  = "null";
		else
			$aparencia_produto  = "'" . $aparencia_produto . "'" ;

		if (strlen($acessorios) == 0)
			$acessorios = "null";
		else
			$acessorios = "'" . $acessorios . "'" ;


		if (strlen($orientacao_sac) == 0)
			$orientacao_sac  = "null";
		else
			$orientacao_sac  = "'" . $orientacao_sac . "'" ;

	} else {

		if (strlen($nota_fiscal) == 0 and $login_fabrica==1){
			$nota_fiscal = "null";
//			$msg_erro = "Entre com o número da Nota Fiscal";
		}
		else
			$nota_fiscal = "'" . $nota_fiscal . "'" ;

		if (strlen($aparencia_produto) == 0)
			$aparencia_produto  = "null";
		else
			$aparencia_produto  = "'" . $aparencia_produto . "'" ;

		if (strlen($acessorios) == 0)
			$acessorios = "null";
		else
			$acessorios = "'" . $acessorios . "'" ;

		if (strlen($orientacao_sac) == 0)
			$orientacao_sac  = "null";
		else
			$orientacao_sac  = "'" . $orientacao_sac . "'" ;

	}

	if ($tipo_atendimento == 18) {

		if ($data_nf <> 'null') {
			//$msg_erro = "Para troca faturada não é necessário digitar a Nota Fiscal.";
		} else {
			$data_nf = 'null';
		}
		if(strlen($_POST['data_nf']) > 0 ){
			//$msg_erro = "Para troca faturada não é necessário digitar a Data da Nota Fiscal.";
		} else {
			$data_nf = 'null';
		}

	}

	$res = pg_query($con,"BEGIN");

	$produto = 0;
	$sql = "SELECT tbl_produto.produto
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  UPPER (tbl_produto.referencia_pesquisa) = UPPER ('$produto_referencia')
			AND    tbl_linha.fabrica      = $login_fabrica ";
	if ($login_fabrica == 1) {
		$voltagem_pesquisa = str_replace("'","",$voltagem);
		$sql .= " AND ( tbl_produto.voltagem ILIKE '%$voltagem_pesquisa%' OR tbl_produto.voltagem IS NULL )";
	}

	$sql .= "AND    tbl_produto.ativo IS TRUE";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) == 0) {
		if ($login_fabrica == 3 and strlen($os) > 0) {
		} else {
			$msg_erro = "Produto $produto_referencia não cadastrado";
		}
	}

	$produto = @pg_fetch_result($res,0,'produto');

	if ($xtroca_faturada <> "'t'") { // verifica troca faturada para a Black
		// se não é uma OS de revenda, entra
		if ($fOsRevenda == 0) {
			$sql = "SELECT garantia FROM tbl_produto WHERE tbl_produto.produto = $produto";

			$res = @pg_query($con,$sql);

			if (@pg_num_rows($res) == 0) {
				//HD 3576 - Validar o produto somente na abertura da OS
				//HD 16457 - 27/03/2008 EM OUTRO CHAMADO A BLACK SOLICITOU PARA TIRAR TODAS AS VALIDAÇÕES, MAS AINDA HAVIA FICADO ESSA.
				if (($login_fabrica == 3 or $login_fabrica == 1 )and strlen($os)> 0) {
					//$msg_erro = "";
				} else {
					$msg_erro = "Produto $produto_referencia sem garantia";
				}
			}

			$garantia = trim(@pg_fetch_result($res,0,garantia));

			$sql = "SELECT ($data_nf::date + (($garantia || ' months')::interval))::date;";
			$res = @pg_query($con,$sql);

			if (@pg_num_rows($res) > 0) {
				$data_final_garantia = trim(pg_fetch_result($res,0,0));
			}
		}

	}
	
	# HD 221627
	if ($causa_troca == 124 and !empty($produto)) {
		if (count($multi_peca) > 0) {
			$sql="SELECT tbl_peca.referencia,
								tbl_peca.descricao
						FROM tbl_lista_basica
						JOIN tbl_peca USING(peca)
						WHERE produto = $produto
						AND   tbl_lista_basica.fabrica = $login_fabrica";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				for($i =0;$i<count($multi_peca);$i++) {
					$sql = "SELECT tbl_peca.referencia,
									tbl_peca.descricao
							FROM tbl_lista_basica
							JOIN tbl_peca USING(peca)
							WHERE produto = $produto
							AND   tbl_lista_basica.fabrica = $login_fabrica
							AND   referencia  = '".$multi_peca[$i]."'";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res) > 0){
						$pecas .="<br />".pg_fetch_result($res,0,referencia)." - ". pg_fetch_result($res,0,descricao);
					} else {
						$msg_erro .= "A peça informada ".$multi_peca[$i]." não pertence a lista básica do produto $produto_referencia<br />";
					}
				}
			} else {
				$msg_erro = "Produto $produto_referencia não possui lista básica, não sendo possível a continuidade da OS";
			}
		} else {
			$msg_erro .= "É necessário informar a peça faltante";
		}
	}

	if ($causa_troca == 124) {
		$xobs_causa = "'".$pecas."'";
	} else if ($causa_troca == 126) {

		$xobs_causa = "'".$v_os1."<br/>".$v_os2 . "'";

		if(empty($v_os1) or empty($v_os2) ) {
			$msg_erro .="Por favor, informe as OSs anteriores<br/>";
		}

	} else if ($causa_troca == 127) {

		$xobs_causa="'".$numero_processo."'";
		
		if(empty($numero_processo)) {
			$msg_erro .="Por favor, informe o número de processo<br/>";
		} else {
			$msg_erro .=(substr($numero_processo,-4) <> date('Y')) ? "O $numero_processo não confere com o ano atual": "";
		}

	} else if (in_array($causa_troca, array(125,128,131))) {

		$xobs_causa = "'".$obs_causa."'";

		if (empty($obs_causa)) {
			$msg_erro .="Por favor, informe a justificativa para esse motivo de troca<br/>";
		}

	}else if ($causa_troca == 130){
		$xobs_causa = " 'Estado do Posto: " . $estado_causa_troca . " - Cidade do Posto: " . $cidade_causa_troca . "'" ;
	}
	else if ($causa_troca == 237 and $login_fabrica == 1)
	{
		$produto_origem_id         = $_POST["produto_origem_id"];
		$produto_origem_referencia = $_POST["produto_origem_referencia"];
		$produto_origem_descricao  = $_POST["produto_origem_descricao"];

		if (strlen($produto_origem_id) > 0 and strlen($produto) > 0)
		{
			if ($produto_origem_id == $produto)
			{
				$msg_erro .= "Produto de origem informado é o mesmo que consta nessa OS para troca. Como trata-se de uma OS com o motivo \" Reverter Troca \" o produto obrigatoriamente deve ser diferente, favor corrigir.";
			}
			else
			{
				$xobs_causa .= "'Produto de Origem: $produto_origem_referencia - $produto_origem_descricao'";
			}
		}
	}
	//hd 21461
	// HD 145639: TROCAR UM PRODUTO POR UM OU MAIS
	if ($login_fabrica == 1) {

		if ($numero_produtos_troca_digitados == 0) {
			$msg_erro = 'Informe o produto para troca.';
		} else {

			for ($p = 0; $p < $numero_produtos_troca_digitados; $p++) {

				if (strlen($produto_voltagem_troca[$p]) == 0) {
					$msg_erro = 'Informe a voltagem do produto para troca. Caso esteja em branco clique na lupa para pesquisar o produto a ser trocado.';
				}

				if (strlen($msg_erro) == 0) {
					//HD 217003: Quando a OS já está gravada, a referencia vem da tbl_peca, que grava referencia_fabrica
					// no caso da Black
					if (strlen($os)) {
						$referencia_pesquisa = "referencia_fabrica";
					} else {
						$referencia_pesquisa = "referencia";
					}

					$sql = "
					SELECT
					tbl_produto.produto,
					tbl_produto.linha

					FROM
					tbl_produto
					JOIN tbl_linha USING (linha)

					WHERE
					UPPER(tbl_produto.$referencia_pesquisa) = UPPER('" . $produto_referencia_troca[$p] . "')
					AND tbl_produto.voltagem ILIKE '%" . $produto_voltagem_troca[$p] . "%'
					AND tbl_linha.fabrica = $login_fabrica
					AND tbl_produto.ativo IS TRUE
					";
					$res = pg_query($con, $sql);
					
					if (pg_num_rows($res) == 0) {
						$msg_erro = "Produto " . $produto_referencia_troca[$p] . " não cadastrado.";
					} else if(strlen($os)) {
						//HD 217003: Quando já tiver OS gravada, no array produto_troca vem o ID do produto acabado de tbl_peca
						$produto_troca[$p] = pg_fetch_result($res, 0, produto);
					}

				}

				if (strlen($msg_erro) == 0) {

					$sql = "SELECT produto_opcao as produto
							FROM tbl_produto_troca_opcao
							WHERE produto = $produto
							AND produto_opcao = " . $produto_troca[$p];

					$res = pg_query($con, $sql);

					if (pg_num_rows($res) == 0) {

						$sql = "SELECT COUNT(produto_troca_opcao)
									FROM tbl_produto_troca_opcao
								WHERE produto = $produto
									AND $produto = " . $produto_troca[$p] . "
								HAVING COUNT(produto_troca_opcao) = 0 ";

						$res = pg_query($con,$sql);

						if (pg_num_rows($res) == 0) {
							$msg_erro = " Produto " . $produto_referencia_troca[$p] . " não encontrado como opção de troca para o produto $produto_referencia";
						}

					}

				}

				if (strlen($msg_erro) == 0) {

					if ($tipo_atendimento == 18) { //troca faturada
						//pega o valor da troca
						//HD 202025 - Modifiquei a verificação para verificar valor_troca e ipi direto na SQL
						$sql = "
						SELECT
						valor_troca,
						ipi

						FROM
						tbl_produto
						JOIN tbl_linha USING(linha)

						WHERE
						fabrica = $login_fabrica
						AND produto = " . $produto_troca[$p] . "
						AND valor_troca<>0
						AND ipi IS NOT NULL
						";
						$resvalor = pg_query($con,$sql);

						if (pg_num_rows($resvalor) > 0) {
							$produto_valor_troca[$p] = floatval(pg_fetch_result($resvalor, 0, valor_troca));
							$produto_ipi = floatval(pg_fetch_result($resvalor, 0, ipi));
							$produto_valor_troca[$p] = $produto_valor_troca[$p] * (1 + ($produto_ipi /100));
						} else {
							$msg_erro = "Há incorreções no cadastro do produto escolhido para troca ([" . $produto_referencia_troca[$p] . "] " . $produto_descricao_troca[$p] . ") que impossibilitam a troca (valor de troca e/ou IPI). Favor verificar o cadastro do produto.";
						}

					} else { //troca garantia qualquer uma diferente de troca
						$produto_valor_troca[$p] = "0";
					}

				}

			}

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

		$res = @pg_query($con,$sql);

		if (@pg_num_rows($res) > 0) {
			$xtipo_os_compressor = "10";
		} else {
			$xtipo_os_compressor = 'null';
		}

	} else {
		$xtipo_os_compressor = 'null';
	}

	$os_reincidente = "'f'";

	if (strlen($msg_erro) == 0) {

		if (strlen($os) == 0) {
			/*================ INSERE NOVA OS =========================*/
			$sql = "INSERT INTO tbl_os (
						tipo_atendimento   ,
						segmento_atuacao   ,
						posto              ,
						admin              ,
						fabrica            ,
						sua_os             ,
						data_abertura      ,
						cliente            ,
						revenda            ,
						consumidor_nome    ,
						consumidor_cpf     ,
						consumidor_cidade  ,
						consumidor_estado  ,
						consumidor_fone    ,
						consumidor_email   ,
						revenda_cnpj       ,
						revenda_nome       ,
						nota_fiscal        ,
						data_nf            ,
						produto            ,
						serie              ,
						qtde_produtos      ,
						aparencia_produto  ,
						acessorios         ,
						obs                ,
						quem_abriu_chamado ,
						consumidor_revenda ,
						troca_faturada     ,
						troca_garantia     ,
						os_reincidente     ,
						obs_reincidencia   ";

			if ($login_fabrica == 1) {
				$sql .=	",codigo_fabricacao ,
						satisfacao          ,
						tipo_os             ,
						laudo_tecnico       ,
						fisica_juridica";
			}

			$sql .= ") VALUES (
						$tipo_atendimento                                                       ,
						$segmento_atuacao                                                       ,
						$posto                                                                  ,
						$login_admin                                                            ,
						$login_fabrica                                                          ,
						trim($sua_os)                                                           ,
						$data_abertura                                                          ,
						(SELECT cliente FROM tbl_cliente WHERE cpf  = $xconsumidor_cpf LIMIT 1) ,
						(SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj LIMIT 1)   ,
						trim('$consumidor_nome')                                                ,
						trim('$consumidor_cpf')                                                 ,
						trim('$consumidor_cidade')                                              ,
						trim('$consumidor_estado')                                              ,
						trim('$consumidor_fone')                                                ,
						trim('$consumidor_email')                                               ,
						trim('$revenda_cnpj')                                                   ,
						trim('$revenda_nome')                                                   ,
						trim($nota_fiscal)                                                      ,
						$data_nf                                                                ,
						$produto                                                                ,
						'$produto_serie'                                                        ,
						$qtde_produtos                                                          ,
						trim($aparencia_produto)                                                ,
						trim($acessorios)                                                       ,
						$xobs                                                                   ,
						$xquem_abriu_chamado                                                    ,
						'$consumidor_revenda'                                                   ,
						$xtroca_faturada                                                        ,
						$xtroca_garantia                                                        ,
						$os_reincidente                                                         ,
						$xobs_reincidencia                                                     ";

			if ($login_fabrica == 1) {
				$sql .= ", $codigo_fabricacao ,
						'$satisfacao'         ,
						$xtipo_os_compressor  ,
						$laudo_tecnico        ,
						$xfisica_juridica";
			}

			$sql .= ");";
		} else {
			/*================ ALTERA OS =========================*/
			$sql = "UPDATE tbl_os SET
						segmento_atuacao   = $segmento_atuacao           ,
						posto              = $posto                      ,";
				$sql .=" admin_altera      = $login_admin                ,";
				$sql .=" fabrica            = $login_fabrica              ,
						sua_os             = trim($sua_os)               ,
						data_abertura      = $data_abertura              ,
						consumidor_nome    = trim('$consumidor_nome')    ,
						consumidor_cpf     = trim('$consumidor_cpf')     ,
						consumidor_fone    = trim('$consumidor_fone')    ,
						consumidor_estado  = trim('$consumidor_estado')  ,
						consumidor_cidade  = trim('$consumidor_cidade')   ,
						revenda_cnpj       = trim('$revenda_cnpj')       ,
						revenda_nome       = trim('$revenda_nome')       ,
						nota_fiscal        = trim($nota_fiscal)          ,
						data_nf            = $data_nf                    ,
						produto            = $produto                    ,
						serie              = '$produto_serie'            ,
						qtde_produtos      = $qtde_produtos              ,
						aparencia_produto  = trim($aparencia_produto)    ,
						acessorios         = trim($acessorios)           ,
						quem_abriu_chamado = $xquem_abriu_chamado        ,
						obs                = $xobs                       ,
						consumidor_revenda = '$consumidor_revenda'       ,
						troca_faturada     = $xtroca_faturada            ,
						troca_garantia     = $xtroca_garantia            ,
						os_reincidente     = $os_reincidente             ,
						consumidor_email   = '$consumidor_email'          ,
						obs_reincidencia   =  $xobs_reincidencia         ";
			if ($login_fabrica == 1) {
				$sql .=	", codigo_fabricacao = $codigo_fabricacao ,
						satisfacao           = '$satisfacao'      ,
						tipo_os              = $xtipo_os_compressor,
						laudo_tecnico        = $laudo_tecnico     ";
			}

			$sql .= "WHERE os      = $os
					AND   fabrica = $login_fabrica";
		}

		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);

		$msg_erro = substr($msg_erro,6);

		if (strlen($msg_erro) == 0) {
			if (strlen($os) == 0) {
				$res = @pg_query($con,"SELECT CURRVAL ('seq_os')");
				$os  = pg_fetch_result($res,0,0);
			}
		}

	$valor_observacao = $_POST['produto_obs_troca'];//HD 303195
	$valor_troca      = str_replace(",",".",$valor_troca);

	//CONTROLE DA TROCA DO PRODUTO
	if (strlen($os) > 0) {

		$sql = "SELECT os_troca FROM tbl_os_troca WHERE os = $os";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) == 0) {
			// HD 145639: TROCAR UM PRODUTO POR UM OU MAIS
			// CONFORME INTERACAO 21 DO CHAMADO, GRAVANDO TROCA EM tbl_os_produto
			$sql = "INSERT INTO tbl_os_produto (
						os,
						produto
					) VALUES (
						$os,
						$produto
					)";

			$res = pg_query($con, $sql);
			$res = pg_query($con, "SELECT CURRVAL('seq_os_produto')");
			$os_produto = pg_fetch_result($res, 0, 0);

			// HD 145639: TROCAR UM PRODUTO POR UM OU MAIS
			// CONFORME INTERACAO 21 DO CHAMADO, DEVE SER GRAVADO APENAS O PRIMEIRO PRODUTO EM tbl_os_troca
			// ESTE MESMO PRODUTO E DEMAIS DEVERÃO SER GRAVADOS EM tbl_os_item, COMO UMA PEÇA

			//HD 303195 - COMENTADO POIS FOI SUBSTITUIDO POR UM TEXTAREA
			//if ($produto_observacao_troca[0] == "") $valor_observacao = "null";
			//else $valor_observacao = "'" . $produto_observacao_troca[0] . "'";
			
			//HD 249064: O total da troca deve ser preenchido apenas em troca faturada
			if ($tipo_atendimento == 18) {

				$mostra_valor_faturada = "sim";

				//HD 224193: O total da troca deve ser o valor de troca do produto original
				$sql = "SELECT valor_troca*(1+(ipi/100)) AS valor_troca
						FROM tbl_produto
						WHERE produto = $produto ";

				$res_valor_troca = pg_query($con, $sql);
				$total_troca     = pg_fetch_result($res_valor_troca, 0, valor_troca);

			} else {
				$total_troca = 0;
			}

			$sql = "INSERT INTO tbl_os_troca (
						os,
						situacao_atendimento,
						total_troca,
						observacao,
						fabric,
						produto,
						causa_troca,
						admin_autoriza,
						obs_causa
					) VALUES (
						$os,
						$tipo_atendimento,
						round(" . $total_troca . "::numeric,2),
						'$valor_observacao',
						$login_fabrica,
						" . $produto_troca[0] . ",
						$causa_troca,
						$admin_autoriza,
						$xobs_causa
					) ";

			$res = pg_query($con, $sql);

			$pg_err = pg_last_error();
			for ($p = 0; $p < $numero_produtos_troca_digitados; $p++) {
				$values = array();

				//HD 303195 - COMENTADO POIS FOI SUBSTITUIDO POR UM TEXTAREA
				//if ($produto_observacao_troca[$p] == "") $valor_observacao = "null";
				//else $valor_observacao = "'" . $produto_observacao_troca[$p] . "'";

				$sql = "SELECT servico_realizado
						  FROM tbl_servico_realizado
						 WHERE troca_produto
						   AND fabrica = $login_fabrica ";

				$res = pg_query($con,$sql);

				$msg_erro .= pg_errormessage($con);
				if (pg_num_rows($res) > 0) $servico_realizado = pg_fetch_result($res,0,0);
				if (strlen($servico_realizado) == 0) $msg_erro .= "Não existe Serviço Realizado de Troca de Produto, favor cadastrar!";

				//HD 202440 - Estava buscando refernecia no lugar de referencia_fabrica para ver se a peça existe
				// correções efetuadas a partir deste ponto

				$sql = "SELECT referencia_fabrica,
								ipi
							FROM tbl_produto
						WHERE produto = {$produto_troca[$p]}";

				$res = pg_query($con, $sql);

				$referencia_fabrica = pg_result($res, 0, "referencia_fabrica");
				$ipi                = pg_result($res, 0, "ipi");

				//HD 202025 - Adicionei esta verificação caso a verificação anterior falhe
				
				if ($ipi == "") {
					$msg_erro = "$pg_err Há incorreções no cadastro do produto escolhido para troca ([" . $produto_referencia_troca[$p] . "] " . $produto_descricao_troca[$p] . ") que impossibilitam a troca (valor de troca e/ou IPI). Favor entrar em contato com o fabricante.";
				} else {

					$sql = "SELECT peca
							  FROM tbl_peca
							 WHERE fabrica = $login_fabrica
							   AND referencia = '" . $referencia_fabrica . "'
							   AND voltagem = '" . $produto_voltagem_troca[$p] . "'
							 LIMIT 1 ";

					$res = pg_query($con, $sql);
					if (pg_num_rows($res) > 0) {

						$peca = pg_fetch_result($res,0,0);

						$sql = "UPDATE tbl_peca
									SET ipi = $ipi
								WHERE fabrica = $login_fabrica
									AND peca = $peca ";

						$res = pg_query($con, $sql);

					} else {

						$sql = "INSERT INTO tbl_peca (
									fabrica,
									referencia,
									descricao,
									ipi,
									origem,
									produto_acabado,
									voltagem
								)
								SELECT
									$login_fabrica,
									referencia_fabrica,
									descricao,
									CASE WHEN ipi IS NULL THEN 0 ELSE ipi END,
									CASE WHEN origem IS NULL THEN 'Nac' ELSE origem END,
									't',
									voltagem
								FROM tbl_produto
								WHERE produto = " . $produto_troca[$p];

						$res = pg_query($con,$sql);

						$sql  = "SELECT CURRVAL('seq_peca')";
						$res  = pg_query($con, $sql);
						$peca = pg_fetch_result($res, 0, 0);

						$sql = "INSERT INTO tbl_lista_basica (
									fabrica,
									produto,
									peca,
									qtde
								) VALUES (
									$login_fabrica,
									" . $produto_troca[$p] . ",
									$peca,
									1
								) ";

						$res = pg_query($con, $sql);

					}

					if (($produto_valor_troca[$p] == "") || ($produto_valor_troca[$p] == "null")) $produto_valor_troca[$p] = 0;

					$values = " (
						$os_produto,
						$peca,
						1,
						$servico_realizado,
						" . $produto_valor_troca[$p] . ",
						'$valor_observacao'
					)";

					$sql = "INSERT INTO tbl_os_item (
								os_produto,
								peca,
								qtde,
								servico_realizado,
								custo_peca,
								obs
							) VALUES $values ";

					$res = pg_query($con, $sql);

					if (strlen(pg_errormessage($con)) > 0 ) {
						$msg_erro = pg_errormessage($con);
					}

				}

			}

		} else {

			// HD 145639: TROCAR UM PRODUTO POR UM OU MAIS
			for ($p = 0; $p < $numero_produtos_troca_digitados; $p++) {
				//HD 303195 - COMENTADO POIS FOI TROCADO POR UM TEXTAREA
				//if ($produto_observacao_troca[$p] == "") $valor_observacao = "null";
				//else $valor_observacao = "'" . $produto_observacao_troca[$p] . "'";
				
				$sql = "UPDATE tbl_os_item
						   SET obs = '$valor_observacao'
						 WHERE os_item = " . $produto_os_item[$p];

				$res = pg_query($con, $sql);

				if (strlen(pg_errormessage($con)) > 0 ) {//HD 303195
					$msg_erro = pg_errormessage($con);
				}

			}

			if (strlen($msg_erro) == 0) {//HD 303195

				$sql = "UPDATE tbl_os_troca
						   SET observacao = '$valor_observacao',
                                                        causa_troca = $causa_troca,
                                                        obs_causa = $xobs_causa
						 WHERE os = $os";

				$res = @pg_query($con, $sql);

				if (strlen(pg_errormessage($con)) > 0 ) {
					$msg_erro = pg_errormessage($con);
				}

			}

		}

	}

	if (strlen($msg_erro) == 0) {

		
		$sql = "UPDATE tbl_os
					SET consumidor_nome = tbl_cliente.nome
				FROM tbl_cliente 
				where tbl_os.os = $os
					AND tbl_os.cliente IS NOT NULL
					AND tbl_os.cliente = tbl_cliente.cliente";

		$res = @pg_query($con,$sql);
						

		$sql = "UPDATE tbl_os
				   SET consumidor_cidade = tbl_cidade.nome,
						consumidor_estado = tbl_cidade.estado
				from tbl_cliente 
				join tbl_cidade on tbl_cliente.cidade = tbl_cidade.cidade
				WHERE tbl_os.os = $os
					AND tbl_os.cliente IS NOT NULL
					AND tbl_os.consumidor_cidade IS NULL
					AND tbl_os.cliente = tbl_cliente.cliente";

		$res = pg_query($con,$sql);
		if (strlen($consumidor_endereco)    == 0) { $consumidor_endereco    = "null" ; } else { $consumidor_endereco    = "'" . $consumidor_endereco    . "'" ; };
		if (strlen($consumidor_numero)      == 0) { $consumidor_numero      = "null" ; } else { $consumidor_numero      = "'" . $consumidor_numero      . "'" ; };
		if (strlen($consumidor_complemento) == 0) { $consumidor_complemento = "null" ; } else { $consumidor_complemento = "'" . $consumidor_complemento . "'" ; };
		if (strlen($consumidor_bairro)      == 0) { $consumidor_bairro      = "null" ; } else { $consumidor_bairro      = "'" . $consumidor_bairro      . "'" ; };
		if (strlen($consumidor_cep)         == 0) { $consumidor_cep         = "null" ; } else { $consumidor_cep         = "'" . $consumidor_cep         . "'" ; };
		if (strlen($consumidor_cidade)      == 0) { $consumidor_cidade      = "null" ; } else { $consumidor_cidade      = "'" . $consumidor_cidade      . "'" ; };
		if (strlen($consumidor_estado)      == 0) { $consumidor_estado      = "null" ; } else { $consumidor_estado      = "'" . $consumidor_estado      . "'" ; };


		$sql = "UPDATE tbl_os SET
					consumidor_endereco    = $consumidor_endereco       ,
					consumidor_numero      = $consumidor_numero         ,
					consumidor_complemento = $consumidor_complemento    ,
					consumidor_bairro      = $consumidor_bairro         ,
					consumidor_cep         = $consumidor_cep            ,
					consumidor_cidade      = $consumidor_cidade         ,
					consumidor_estado      = $consumidor_estado
				WHERE tbl_os.os = $os ";

		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0) {
			$sql      = "SELECT fn_valida_os($os, $login_fabrica)";
			$res      = @pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen($msg_erro) == 0) { # 170785
			$res = @pg_query($con,"SELECT os_reincidente, obs_reincidencia FROM tbl_os WHERE fabrica = $login_fabrica AND os = $os ");
			if (pg_num_rows($res) > 0) {
				$xos_reincidente   = pg_fetch_result($res,0,os_reincidente);
				$xobs_reincidencia = pg_fetch_result($res,0,obs_reincidencia);
				if ($login_fabrica == 1 AND $xos_reincidente == 't' AND strlen($xobs_reincidencia) == 0) {
					$msg_erro .= "OS reincidente. Informar a justificativa";
					$os_reincidente = 't';
				}
			}
		}

			#--------- grava OS_EXTRA ------------------
			if (strlen($msg_erro) == 0) {

				$taxa_visita				= str_replace(",",".",trim($_POST['taxa_visita']));
				$visita_por_km				= trim($_POST['visita_por_km']);
				$hora_tecnica				= str_replace(",",".",trim($_POST['hora_tecnica']));
				$regulagem_peso_padrao		= str_replace(",",".",trim($_POST['regulagem_peso_padrao']));
				$certificado_conformidade	= str_replace(",",".",trim($_POST['certificado_conformidade']));
				$valor_diaria				= str_replace(",",".",trim($_POST['valor_diaria']));

				if (strlen($taxa_visita)				== 0) $taxa_visita					= '0';
				if (strlen($visita_por_km)				== 0) $visita_por_km				= 'f';
				if (strlen($hora_tecnica)				== 0) $hora_tecnica					= '0';
				if (strlen($regulagem_peso_padrao)		== 0) $regulagem_peso_padrao		= '0';
				if (strlen($certificado_conformidade)	== 0) $certificado_conformidade		= '0';
				if (strlen($valor_diaria)				== 0) $valor_diaria					= '0';

				$sql = "UPDATE  tbl_os_extra SET
								orientacao_sac          = trim($orientacao_sac)      ,
								taxa_visita              = $taxa_visita              ,
								visita_por_km            = '$visita_por_km'          ,
								hora_tecnica             = $hora_tecnica             ,
								regulagem_peso_padrao    = $regulagem_peso_padrao    ,
								certificado_conformidade = $certificado_conformidade ,
								valor_diaria             = $valor_diaria             ,
								admin_paga_mao_de_obra   = '$admin_paga_mao_de_obra' ";

				if ($os_reincidente == "'t'") {
					$sql .= ", os_reincidente = $xxxos ";
				}

				$sql .= "WHERE tbl_os_extra.os = $os";
				$res = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);

				$sqls = "SELECT sua_os,descricao FROM tbl_os join tbl_tipo_atendimento using(tipo_atendimento) WHERE os = $os";
				$ress = pg_query($con, $sqls);

				if (pg_num_rows($ress)) {//HD 235182 - Transferi de baixo para cima
					$sua_os    = pg_fetch_result($ress, 0, 'sua_os');
					$descricao = pg_fetch_result($ress, 0, 'descricao');
				} else {
					$msg_erro = 'Erro ao selecionar a descrição do tipo de atendimento.';
				}

				if (!empty($os) and empty($msg_erro)) {
					if (is_array($_FILES['foto_nf']) and $_FILES['foto_nf']['name'] != '') {
						$anexou = anexaNF($os, $_FILES['foto_nf']);
						if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' é que executou OK
					}
				}

				//HD 235182 - AQUI COMEÇA A INSERÇÃO DO CERTIFICADO DE GARANTIA (TIPO_ATENDIMENTO = TROCA FATURADA)
				if (strlen($msg_erro) == 0 && $tipo_atendimento == 18 && $gerar_certificado_garantia == 1) {

					$sql = "SELECT * FROM tbl_certificado WHERE os = $os AND fabrica = $login_fabrica";
					$res_certificado = pg_query($con, $sql);
					$tot_certificado = pg_num_rows($res_certificado);

					if ($tot_certificado == 0) {

						$certificado        = 'CBW' . $posto_codigo . str_replace("'",'',$sua_os);
						$motivo_certificado = strlen($motivo_certificado) > 250 ? substr($motivo_certificado, 0, 250) : $motivo_certificado;

						if (strlen($msg_erro) == 0) {

							$sql = "INSERT INTO tbl_certificado(
										os,
										fabrica,
										motivo,
										codigo,
										admin
									) VALUES (
										$os,
										$login_fabrica,
										'$motivo_certificado',
										'$certificado',
										$login_admin
									)";

							$res = @pg_query($con, $sql);
							$msg_erro = pg_errormessage($con);

						}

					}

				}
				if (strlen($msg_erro) == 0) {

					$res = pg_query($con,"COMMIT TRANSACTION");

					if ($causa_troca == 125) {
						$sql = "SELECT email,codigo_posto from tbl_posto_fabrica join tbl_admin ON tbl_posto_fabrica.admin_sap = tbl_admin.admin where tbl_admin.fabrica = $login_fabrica and posto =$posto";
						$res = pg_query($con,$sql);

						if (pg_num_rows($res) > 0) {
							
							$email        = pg_fetch_result($res, 0, 'email');
							$codigo_posto = pg_fetch_result($res, 0, 'codigo_posto');

							if (!empty($email)) {

								$message = "OS $codigo_posto"."$sua_os de troca de produto ($descricao) lançada com o motivo Falha do posto.\n<br/>Falha informada: $obs_causa";
								
								$assunto = "Troca de produto por falha do posto ($codigo_posto).";
								$headers  = "From: Telecontrol <telecontrol@telecontrol.com.br>\n";
								$headers .= "MIME-Version: 1.0\n";
								$headers .= "Content-type: text/html; charset=iso-8859-1\n";

								mail("$email", $assunto, $message,$headers);

							}
						}
					}

					if ($causa_troca == 130){

						$sql = "SELECT email from tbl_admin where tbl_admin.fabrica = $login_fabrica and responsavel_postos and ativo";
						$res = pg_query($con,$sql);
						$numrows = pg_last_error($res);
						if (pg_num_rows($res) > 0) {
							
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

								$message = "OS $codigo_posto"."$sua_os de troca de produto ($descricao) foi cadastrada com o motivo Falta de Posto de serviço.\n<br> Cidade: $cidade_causa_troca\n<br/>Estado: $estado_causa_troca \n<br><br><b>Suporte Telecontrol</b>";
								
								$assunto = "OS $codigo_posto"."$sua_os Falta de Posto de serviço.";
								$headers  = "From: Telecontrol <helpdesk@telecontrol.com.br>\n";
								$headers .= "MIME-Version: 1.0\n";
								$headers .= "Content-type: text/html; charset=iso-8859-1\n";

								mail("$email", $assunto, $message,$headers);

							}

						}
					
					}

					header ("Location: os_press.php?os=$os&mostra_valor_faturada=$mostra_valor_faturada");
					exit;
				}
			}
		}
	}

	if (strlen($msg_erro) > 0) {

		if (strpos($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf\"") > 0){
			$msg_erro = "Data da compra maior que a data da abertura da Ordem de Serviço.";
		}

		if (strpos($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_abertura\"") > 0){
			$msg_erro = " Data da abertura deve ser inferior ou igual a data de digitação da OS no sistema (data de hoje).";
		}

		if (strpos($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf_superior_data_abertura\"") > 0){//HD 235182
			$msg_erro = " Data da Nota Fiscal deve ser inferior ou igual a data de digitação da OS no sistema (data de hoje).";
		}

		$res = pg_query($con,"ROLLBACK");

	}

}

/* ====================  APAGAR  =================== */
if ($btn_acao == "apagar") {

	if (strlen($os) > 0) {

		if ($login_fabrica == 1) {

			$sql =	"SELECT sua_os
					FROM tbl_os
					WHERE os = $os;";

			$res = @pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);

			if (@pg_num_rows($res) == 1) {
				$sua_os = @pg_fetch_result($res,0,0);
				$sua_os_explode = explode("-", $sua_os);
				$xsua_os = $sua_os_explode[0];
			}

		}

		if ($login_fabrica == 3) {
			$sql = "UPDATE tbl_os SET excluida = 't' , admin_excluida = $login_admin WHERE os = $os AND fabrica = $login_fabrica";
			$res = @pg_query($con,$sql);
		} else {
			$sql = "SELECT fn_os_excluida($os,$login_fabrica,$login_admin);";
			$res = @pg_query($con,$sql);
		}

		$msg_erro = pg_errormessage($con);

		if (strlen($msg_erro) == 0 AND $login_fabrica == 1) {
			$sqlPosto =	"SELECT tbl_posto.posto
						FROM tbl_posto
						JOIN tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
											   AND tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE tbl_posto_fabrica.codigo_posto = '".trim($_POST['posto_codigo'])."'
						AND   tbl_posto_fabrica.fabrica      = $login_fabrica;";
			$resPosto = @pg_query($con,$sqlPosto);
			if (@pg_num_rows($res) == 1) {
				$xposto = pg_fetch_result($resPosto,0,0);
			}

			$sql = "SELECT tbl_os.sua_os
					FROM tbl_os
					WHERE sua_os ILIKE '$xsua_os-%'
					AND   posto   = $xposto
					AND   fabrica = $login_fabrica;";
			$res = @pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);

			if (@pg_num_rows($res) == 0) {
				$sql = "DELETE FROM tbl_os_revenda
						WHERE  tbl_os_revenda.sua_os  = '$xsua_os'
						AND    tbl_os_revenda.fabrica = $login_fabrica
						AND    tbl_os_revenda.posto   = $xposto";
				$res = @pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
			}

		}

		if (strlen($msg_erro) == 0) {
			header("Location: os_parametros.php");
			exit;
		}

	}

}

/*================ LE OS DA BASE DE DADOS =========================*/

if (strlen($os) > 0) {

	$sql = "SELECT	tbl_os.os                                           ,
			tbl_os.tipo_atendimento                                     ,
			tbl_os.segmento_atuacao                                     ,
			tbl_os.posto                                                ,
			tbl_posto.nome                             AS posto_nome    ,
			tbl_os.sua_os                                               ,
			to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura ,
			tbl_os.produto                                              ,
			tbl_produto.referencia                                      ,
			tbl_produto.descricao                                       ,
			tbl_produto.voltagem                                        ,
			tbl_os.serie                                                ,
			tbl_os.qtde_produtos                                        ,
			tbl_os.cliente                                              ,
			tbl_os.consumidor_nome                                      ,
			tbl_os.consumidor_cpf                                       ,
			tbl_os.consumidor_fone                                      ,
			tbl_os.consumidor_cidade                                    ,
			tbl_os.consumidor_estado                                    ,
			tbl_os.consumidor_cep                                       ,
			tbl_os.consumidor_endereco                                  ,
			tbl_os.consumidor_numero                                    ,
			tbl_os.consumidor_complemento                               ,
			tbl_os.consumidor_bairro                                    ,
			tbl_os.revenda                                              ,
			tbl_os.revenda_cnpj                                         ,
			tbl_os.revenda_nome                                         ,
			tbl_os.nota_fiscal                                          ,
			to_char(tbl_os.data_nf,'DD/MM/YYYY')       AS data_nf       ,
			tbl_os.aparencia_produto                                    ,
			tbl_os_extra.orientacao_sac                                 ,
			tbl_os_extra.admin_paga_mao_de_obra                        ,
			tbl_os.acessorios                                           ,
			tbl_os.fabrica                                              ,
			tbl_os.quem_abriu_chamado                                   ,
			tbl_os.obs                                                  ,
			tbl_os.consumidor_revenda                                   ,
			tbl_os_extra.extrato                                        ,
			tbl_posto_fabrica.codigo_posto             AS posto_codigo  ,
			tbl_os.codigo_fabricacao                                    ,
			tbl_os.satisfacao                                           ,
			tbl_os.laudo_tecnico                                        ,
			tbl_os.troca_faturada                                       ,
			tbl_os.admin                                                ,
			tbl_os.troca_garantia                                       ,
			tbl_os.autorizacao_cortesia                                 ,
			tbl_os.consumidor_email                                     ,
			tbl_os.fisica_juridica                                      ,
			tbl_os_troca.causa_troca                                    ,
			tbl_os_troca.obs_causa                                      ,
			tbl_os_troca.admin_autoriza
			FROM	tbl_os
			JOIN    tbl_os_troca         ON tbl_os.os = tbl_os_troca.os
			JOIN	tbl_produto          ON tbl_produto.produto       = tbl_os.produto
			JOIN	tbl_posto            ON tbl_posto.posto           = tbl_os.posto
			JOIN	tbl_fabrica          ON tbl_fabrica.fabrica       = tbl_os.fabrica
			JOIN	tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
										AND tbl_fabrica.fabrica       = $login_fabrica
			LEFT JOIN	tbl_os_extra     ON tbl_os.os                 = tbl_os_extra.os
			WHERE	tbl_os.os      = $os
			AND		tbl_os.fabrica = $login_fabrica";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {

		$os                     = pg_fetch_result($res, 0, 'os');
		$tipo_atendimento       = pg_fetch_result($res, 0, 'tipo_atendimento');
		$segmento_atuacao       = pg_fetch_result($res, 0, 'segmento_atuacao');
		$posto                  = pg_fetch_result($res, 0, 'posto');
		$posto_nome             = pg_fetch_result($res, 0, 'posto_nome');
		$sua_os                 = pg_fetch_result($res, 0, 'sua_os');
		$data_abertura          = pg_fetch_result($res, 0, 'data_abertura');
		$produto_referencia     = pg_fetch_result($res, 0, 'referencia');
		$produto_descricao      = pg_fetch_result($res, 0, 'descricao');
		$produto_voltagem       = pg_fetch_result($res, 0, 'voltagem');
		$produto_serie          = pg_fetch_result($res, 0, 'serie');
		$qtde_produtos          = pg_fetch_result($res, 0, 'qtde_produtos');
		$cliente                = pg_fetch_result($res, 0, 'cliente');
		$consumidor_nome        = pg_fetch_result($res, 0, 'consumidor_nome');
		$consumidor_cpf         = pg_fetch_result($res, 0, 'consumidor_cpf');
		$consumidor_fone        = pg_fetch_result($res, 0, 'consumidor_fone');
		$consumidor_cep         = trim(pg_fetch_result($res, 0, 'consumidor_cep'));
		$consumidor_endereco    = trim(pg_fetch_result($res, 0, 'consumidor_endereco'));
		$consumidor_numero      = trim(pg_fetch_result($res, 0, 'consumidor_numero'));
		$consumidor_complemento = trim(pg_fetch_result($res, 0, 'consumidor_complemento'));
		$consumidor_bairro      = trim(pg_fetch_result($res, 0, 'consumidor_bairro'));
		$consumidor_cidade      = pg_fetch_result($res, 0, 'consumidor_cidade');
		$consumidor_estado      = pg_fetch_result($res, 0, 'consumidor_estado');
		$consumidor_email       = pg_fetch_result($res, 0, 'consumidor_email');
		$fisica_juridica        = pg_fetch_result($res, 0, 'fisica_juridica');
		$revenda                = pg_fetch_result($res, 0, 'revenda');
		$revenda_cnpj           = pg_fetch_result($res, 0, 'revenda_cnpj');
		$revenda_nome           = pg_fetch_result($res, 0, 'revenda_nome');
		$nota_fiscal            = pg_fetch_result($res, 0, 'nota_fiscal');
		$data_nf                = pg_fetch_result($res, 0, 'data_nf');
		$aparencia_produto      = pg_fetch_result($res, 0, 'aparencia_produto');
		$acessorios             = pg_fetch_result($res, 0, 'acessorios');
		$fabrica                = pg_fetch_result($res, 0, 'fabrica');
		$posto_codigo           = pg_fetch_result($res, 0, 'posto_codigo');
		$extrato                = pg_fetch_result($res, 0, 'extrato');
		$quem_abriu_chamado     = pg_fetch_result($res, 0, 'quem_abriu_chamado');
		$obs                    = pg_fetch_result($res, 0, 'obs');
		$consumidor_revenda     = pg_fetch_result($res, 0, 'consumidor_revenda');
		$codigo_fabricacao      = pg_fetch_result($res, 0, 'codigo_fabricacao');
		$satisfacao             = pg_fetch_result($res, 0, 'satisfacao');
		$laudo_tecnico          = pg_fetch_result($res, 0, 'laudo_tecnico');
		$troca_faturada         = pg_fetch_result($res, 0, 'troca_faturada');
		$troca_garantia         = pg_fetch_result($res, 0, 'troca_garantia');
		$admin_os               = trim(pg_fetch_result($res, 0, 'admin'));
		$autorizacao_cortesia   = pg_fetch_result($res, 0, 'autorizacao_cortesia');
		$orientacao_sac         = pg_fetch_result($res, 0, 'orientacao_sac');
		$orientacao_sac         = html_entity_decode ($orientacao_sac,ENT_QUOTES);
		$orientacao_sac         = str_replace("<br />","",$orientacao_sac);
		$admin_paga_mao_de_obra = pg_fetch_result($res, 0, 'admin_paga_mao_de_obra');
		$causa_troca            = pg_fetch_result($res, 0, 'causa_troca');
		$obs_causa              = pg_fetch_result($res, 0, 'obs_causa');
		$admin_autoriza         = pg_fetch_result($res, 0, 'admin_autoriza');

		// HD 145639: TROCAR UM PRODUTO POR UM OU MAIS
		$sql = "SELECT os_item,
					   peca,
					   obs
				  FROM tbl_os_item
				  JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
				 WHERE tbl_os_produto.os = $os ";

		$res_produtos_troca = pg_query($con, $sql);

		$numero_produtos_troca_digitados = pg_num_rows($res_produtos_troca);
		
		for ($p = 0; $p < $numero_produtos_troca_digitados; $p++) {

			$produto_os_item[$p]          = pg_fetch_result($res_produtos_troca, $p, 'os_item');
			$produto_troca[$p]            = pg_fetch_result($res_produtos_troca, $p, 'peca');
			$produto_observacao_troca[$p] = pg_fetch_result($res_produtos_troca, $p, 'obs');

			$sql = "SELECT tbl_peca.referencia,
						   tbl_peca.descricao,
						   tbl_peca.voltagem
					  FROM tbl_os_item
					  JOIN tbl_peca ON tbl_os_item.peca=tbl_peca.peca
					 WHERE tbl_os_item.os_item = " . $produto_os_item[$p];

			$res = pg_query($con, $sql);

			if (pg_num_rows($res) == 1) {

				$produto_referencia_troca[$p] = pg_fetch_result($res, 0, 'referencia');
				$produto_descricao_troca[$p]  = pg_fetch_result($res, 0, 'descricao');
				$produto_voltagem_troca[$p]   = pg_fetch_result($res, 0, 'voltagem');

				if ($numero_produtos_troca_digitados == 1 && !$produto_voltagem_troca[$p]) {

					$sql = "SELECT tbl_produto.voltagem
							  FROM tbl_os_troca
							  JOIN tbl_produto ON tbl_os_troca.produto = tbl_produto.produto
							 WHERE tbl_os_troca.os = $os ";

					$res = pg_query($con, $sql);

					$produto_voltagem_troca[$p] = pg_fetch_result($res, 0, 'voltagem');

				}

			}

		}

		$sql = "SELECT tbl_os_produto.produto ,
						tbl_os_item.pedido
				FROM    tbl_os
				JOIN    tbl_produto using (produto)
				JOIN    tbl_posto using (posto)
				JOIN    tbl_fabrica using (fabrica)
				JOIN    tbl_posto_fabrica ON  tbl_posto_fabrica.posto   = tbl_posto.posto
										  AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
				JOIN    tbl_os_produto USING (os)
				JOIN    tbl_os_item
				ON      tbl_os_item.os_produto = tbl_os_produto.os_produto
				WHERE   tbl_os.os = $os
				AND     tbl_os.fabrica = $login_fabrica";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			$produto = pg_fetch_result($res,0,produto);
			$pedido  = pg_fetch_result($res,0,pedido);
		}

		$sql = "SELECT * FROM tbl_os_extra WHERE os = $os";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) == 1) {
			$taxa_visita              = pg_fetch_result($res,0,taxa_visita);
			$visita_por_km            = pg_fetch_result($res,0,visita_por_km);
			$hora_tecnica             = pg_fetch_result($res,0,hora_tecnica);
			$regulagem_peso_padrao    = pg_fetch_result($res,0,regulagem_peso_padrao);
			$certificado_conformidade = pg_fetch_result($res,0,certificado_conformidade);
			$valor_diaria             = pg_fetch_result($res,0,valor_diaria);
		}

		//SELECIONA OS DADOS DO CLIENTE PRA JOGAR NA OS
		if (strlen($consumidor_cidade) == 0) {

			if (strlen($cpf) > 0 OR strlen($cliente) > 0 ) {

				$sql = "SELECT
						tbl_cliente.cliente,
						tbl_cliente.nome,
						tbl_cliente.endereco,
						tbl_cliente.numero,
						tbl_cliente.complemento,
						tbl_cliente.bairro,
						tbl_cliente.cep,
						tbl_cliente.rg,
						tbl_cliente.fone,
						tbl_cliente.contrato,
						tbl_cidade.nome AS cidade,
						tbl_cidade.estado
						FROM tbl_cliente
						LEFT JOIN tbl_cidade USING (cidade)
						WHERE 1 = 1";

				if (strlen($cpf) > 0) $sql .= " AND tbl_cliente.cpf = '$cpf'";
				if (strlen($cliente) > 0) $sql .= " AND tbl_cliente.cliente = '$cliente'";

				$res = pg_query($con,$sql);

				if (pg_num_rows($res) == 1) {
					$consumidor_cliente     = trim(pg_fetch_result($res, 0, 'cliente'));
					$consumidor_fone        = trim(pg_fetch_result($res, 0, 'fone'));
					$consumidor_nome        = trim(pg_fetch_result($res, 0, 'nome'));
					$consumidor_endereco    = trim(pg_fetch_result($res, 0, 'endereco'));
					$consumidor_numero      = trim(pg_fetch_result($res, 0, 'numero'));
					$consumidor_complemento = trim(pg_fetch_result($res, 0, 'complemento'));
					$consumidor_bairro      = trim(pg_fetch_result($res, 0, 'bairro'));
					$consumidor_cep         = trim(pg_fetch_result($res, 0, 'cep'));
					$consumidor_rg          = trim(pg_fetch_result($res, 0, 'rg'));
					$consumidor_cidade      = trim(pg_fetch_result($res, 0, 'cidade'));
					$consumidor_estado      = trim(pg_fetch_result($res, 0, 'estado'));
					$consumidor_contrato    = trim(pg_fetch_result($res, 0, 'contrato'));
				}

			}

		}

	}

}

/*============= RECARREGA FORM EM CASO DE ERRO ==================*/

if (strlen($msg_erro) > 0 and $btn_troca <> "trocar") {
	$os                   = $_POST['os'];
	$tipo_atendimento     = $_POST['tipo_atendimento'];
	$segmento_atuacao     = $_POST['segmento_atuacao'];
	$sua_os               = $_POST['sua_os'];
	$data_abertura        = $_POST['data_abertura'];
	$cliente              = $_POST['cliente'];
	$consumidor_nome      = $_POST['consumidor_nome'];
	$consumidor_cpf       = $_POST['consumidor_cpf'];
	$consumidor_fone      = $_POST['consumidor_fone'];
	$consumidor_email     = $_POST['consumidor_email'];
	$fisica_juridica      = $_POST['fisica_juridica'];
	$revenda              = $_POST['revenda'];
	$revenda_cnpj         = $_POST['revenda_cnpj'];
	$revenda_nome         = $_POST['revenda_nome'];
	$nota_fiscal          = $_POST['nota_fiscal'];
	$data_nf              = $_POST['data_nf'];
	$produto_referencia   = $_POST['produto_referencia'];
	$cor                  = $_POST['cor'];
	$acessorios           = $_POST['acessorios'];
	$aparencia_produto    = $_POST['aparencia_produto'];
	$obs                  = $_POST['obs'];
	$orientacao_sac       = $_POST['orientacao_sac'];
	$consumidor_revenda   = $_POST['consumidor_revenda'];
	$qtde_produtos        = $_POST['qtde_produtos'];
	$produto_serie        = $_POST['produto_serie'];
	$autorizacao_cortesia = $_POST['autorizacao_cortesia'];

	$codigo_fabricacao    = $_POST['codigo_fabricacao'];
	$satisfacao           = $_POST['satisfacao'];
	$laudo_tecnico        = $_POST['laudo_tecnico'];
	$troca_faturada       = $_POST['troca_faturada'];

	$quem_abriu_chamado       = $_POST['quem_abriu_chamado'];
	$taxa_visita              = $_POST['taxa_visita'];
	$visita_por_km            = $_POST['visita_por_km'];
	$hora_tecnica             = $_POST['hora_tecnica'];
	$regulagem_peso_padrao    = $_POST['regulagem_peso_padrao'];
	$certificado_conformidade = $_POST['certificado_conformidade'];
	$valor_diaria             = $_POST['valor_diaria'];

	$sql = "SELECT descricao
			FROM    tbl_produto
			JOIN    tbl_linha USING (linha)
			WHERE   tbl_produto.referencia = UPPER ('$produto_referencia')
			AND     tbl_linha.fabrica      = $login_fabrica
			AND     tbl_produto.ativo IS TRUE";
	$res = pg_query($con,$sql);
	$produto_descricao = @pg_fetch_result($res,0,0);
}

$body_onload = "javascript: document.frm_os.sua_os.focus()";

/* $title = Aparece no sub-menu e no titulo do Browser ===== */
$title = "CADASTRO DE OS DE TROCA - ADMIN";

/* $layout_menu = Determina a aba em destaque do MENU ===== */
$layout_menu = 'callcenter';

include "cabecalho.php";
include "javascript_pesquisas.php"; ?>

<script language='javascript' src='js/jquery-1.4.2.js'></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>

<script type="text/javascript" src="js/jquery.blockUI_2.39.js"></script>
<script type="text/javascript" src="js/plugin_verifica_servidor.js"></script>
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">


<? // HD 31122?>
<script language="JavaScript">
var self = window.location.pathname;

$(document).ready(function(){


	$('#causa_troca').change(function(){
		if ($(this).val() == '130'){
			$('#tr_cidade_estado').show();
		}else{
			$('#tr_cidade_estado').hide();
		}
	});

	$('#estado_causa_troca').change(function(){
		
		var estado = $(this).val();
		
		if (estado.length > 0){

			$.get(self, {'ajax':'true', 'action': 'mostra_cidades','uf': estado},
			  function(data){
			  	$("#cidade_causa_troca").html();
				$("#cidade_causa_troca").html("<option></option>"+data);

			});

		}else{
			$("#cidade_causa_troca").html("<option></option>");
		}
	});

	$("#nota_fiscal").keypress(function(e) {//HD 235182

		tecla = (e.keyCode ? e.keyCode : e.which ? e.which : e.charCode);
		var c = String.fromCharCode(tecla);<?php

		if ($login_fabrica == 1) {?>
			var allowed = '1234567890cbwCBW';<?php
		} else {?>
			var allowed = '1234567890-';<?php
		}?>

		if (tecla != 8 && tecla != 9 && tecla != 35 && tecla != 36 && tecla != 37 && tecla != 39 && tecla != 46 && allowed.indexOf(c) < 0 ) return false;

	});

	$("input[rel='fone']").maskedinput("(99) 9999-9999");
	$("input[rel='data']").maskedinput("99/99/9999");
	$("#numero_processo").maskedinput("9999/9999");
	$("input[name='consumidor_cidade']").alpha();
	$("input[name='consumidor_estado']").alpha();
	$("#consumidor_cpf").numeric();
	$("#revenda_cnpj").numeric();

	$('#frm_os').submit(function(){
		$('#multi_peca option').attr('selected','selected');
	})

});

function mascara_cpf(campo, event) {

	var cpf   = campo.value.length;
	var tecla = event.keyCode ? event.keyCode : event.which ? event.which : event.charCode;

	if (tecla != 8 && tecla != 46) {

		if (cpf == 3 || cpf == 7) campo.value += '.';
		if (cpf == 11) campo.value += '-';

	}

}

 function mascara_cnpj(campo, event) {

	var cnpj  = campo.value.length;
	var tecla = event.keyCode ? event.keyCode : event.which ? event.which : event.charCode;

	if (tecla != 8 && tecla != 46) {

		if (cnpj == 2 || cnpj == 6) campo.value += '.';
		if (cnpj == 10) campo.value += '/';
		if (cnpj == 15) campo.value += '-';

	}

}

function formata_cpf_cnpj(campo, tipo) {

	var valor = campo.value;

	valor = valor.replace('.','');
	valor = valor.replace('.','');
	valor = valor.replace('-','');

	if (tipo == 2) {
		valor = valor.replace('/','');
	}

	if (valor.length == 11 && tipo == 1) {

		campo.value = valor.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/,"$1.$2.$3-$4");//CPF

	} else if (valor.length == 14 && tipo == 2) {

		campo.value = valor.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/,'$1.$2.$3/$4-$5');//CNPJ

	}

}

function VerificaSuaOS (sua_os) {

	if (sua_os.value != "") {
		janela = window.open("pesquisa_sua_os.php?sua_os=" + sua_os.value,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=250,top=50,left=10");
		janela.focus();
	}

}

// ========= Função PESQUISA DE POSTO POR CÓDIGO OU NOME ========= //

function fnc_pesquisa_posto2 (campo, campo2, tipo) {

	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {

		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=t";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;

		if ("<? echo $pedir_sua_os; ?>" == "t") {
			janela.proximo = document.frm_os.sua_os;
		} else {
			janela.proximo = document.frm_os.data_abertura;
		}

		janela.focus();

	} else {
        alert("Informar toda ou parte da informação para realizar a pesquisa!");
    }

}

// ========= Função PESQUISA DE PRODUTO POR REFER?NCIA OU DESCRIÇÃO ========= //

function fnc_pesquisa_produto2 (campo, campo2, tipo, voltagem,valor_troca) {

	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=t" + "&exibe=<? echo $_SERVER['REQUEST_URI']; ?>";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;
		janela.proximo      = document.frm_os.produto_serie;

		if (voltagem != "") {
			janela.voltagem = voltagem;
		}

		janela.valor_troca    = valor_troca;
		janela.focus();

	} else {
        alert("Informar toda ou parte da informação para realizar a pesquisa!");
    }

}

// ========= Função PESQUISA DE CONSUMIDOR POR NOME OU CPF ========= //

function fnc_pesquisa_consumidor (campo, tipo) {

	var url = "";

	if (tipo == "nome") {
		url = "pesquisa_consumidor.php?nome=" + campo.value + "&tipo=nome&proximo=t";
	}

	if (tipo == "cpf") {
		url = "pesquisa_consumidor.php?cpf=" + campo.value + "&tipo=cpf&proximo=t";
	}

	if (campo.value != "") {

		if (campo.value.length >= 3) {

			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
			janela.cliente		= document.frm_os.consumidor_cliente;
			janela.nome			= document.frm_os.consumidor_nome;
			janela.cpf			= document.frm_os.consumidor_cpf;
			janela.rg			= document.frm_os.consumidor_rg;
			janela.cidade		= document.frm_os.consumidor_cidade;
			janela.estado		= document.frm_os.consumidor_estado;
			janela.fone			= document.frm_os.consumidor_fone;
			janela.endereco		= document.frm_os.consumidor_endereco;
			janela.numero		= document.frm_os.consumidor_numero;
			janela.complemento	= document.frm_os.consumidor_complemento;
			janela.bairro		= document.frm_os.consumidor_bairro;
			janela.cep			= document.frm_os.consumidor_cep;
			janela.proximo		= document.frm_os.revenda_nome;
			janela.focus();

		} else {
			alert("Digite pelo menos 3 caracteres para efetuar a pesquisa");
		}

	} else {
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}

}

// ========= Função PESQUISA DE REVENDA POR NOME OU CNPJ ========= //

function fnc_pesquisa_revenda (campo, tipo) {

	var url = "";

	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome&proximo=t";
	}

	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj&proximo=t";
	}

	if (campo.value != "") {

		if (campo.value.length >= 3) {

			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
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
			janela.proximo		= document.frm_os.nota_fiscal;
			janela.focus();

		} else {
			alert("Digite pelo menos 3 caracteres para efetuar a pesquisa");
		}

	} else {
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}

}

function verificaSerie() {
	var tipo_atend = $('#tipo_atendimento').val();
	if ($('#produto_referencia').length == 0 && $('#produto_serie').length == 0) {
		document.frm_os.btn_acao.value='continuar'; $('#multi_peca option').attr('selected','selected');
		document.frm_os.submit();
	} else {
		
		$.ajax({
			url:'ajax_verifica_serie.php',
			data:'produto_referencia='+$('#produto_referencia').val()+'&produto_serie='+$('#produto_serie').val(),
			complete: function(respostas){
				if (respostas.responseText == 'erro' && tipo_atend == 35){
					
					if (confirm('Esse número de série e produto foi identificado em nosso arquivo de vendas para locadoras. As locadoras têm acesso à pedido em garantia através da Telecontrol. Esse atendimento poderá ser gravado, e irá para um relatório gerencial. Deseja prosseguir?') == true){
						$('#locacao_serie').val('sim');
						document.frm_os.btn_acao.value='continuar'; $('#multi_peca option').attr('selected','selected');
						document.frm_os.submit();
					}else{
						return;	
					}
				}else{
					document.frm_os.btn_acao.value='continuar'; $('#multi_peca option').attr('selected','selected');
					document.frm_os.submit();
				}
			}
		})
	}

}

$(document).ready(function() {
	Shadowbox.init();

	var causa_troca = "<?=$causa_troca?>";

	if (causa_troca == "237")
	{
		$("#produto_origem").show();
	}

	$("select[name=causa_troca]").change(function() {
		var valor = $(this).val();

		if (valor == "237")
		{
			$("#produto_origem").show();
		}
		else
		{
			$("#produto_origem").hide();
			$("#produto_origem > tbody > tr > td > input[name^=produto_origem]").each(function() {
				$(this).val("");
			});
		}
	});
});

function fnc_pesquisa_produto_origem (referencia, descricao, tipo) 
{
	if (tipo == "referencia")
	{
		var campo = referencia;
	}
	else if (tipo == "descricao")
	{
		var campo = descricao;
	}

	if (campo.length > 0) 
	{
		Shadowbox.open({ 
			content :   "produto_pesquisa_2_nv.php?"+tipo+"="+campo,
			player  :   "iframe",
			title   :   "Pesquisa Produto de Origem",
			width   :   800,
			height  :   500 
		}); 
	} 
	else 
	{
        alert("Informar toda ou parte da informação para realizar a pesquisa!");
    }
}

function retorna_dados_produto(produto,linha,nome_comercial,voltagem,referencia,descricao,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada,mobra,off_line,capacidade,ipi,troca_obrigatoria,posicao)
{
	gravaDados("produto_origem_id", produto);
	gravaDados("produto_origem_referencia", referencia);
	gravaDados("produto_origem_descricao", descricao);
}

function gravaDados(name, valor)
{
	try 
	{
		$("input[name="+name+"]").val(valor);
	} 
	catch(err)
	{
		return false;
	}
}

</script>

<!--========================= AJAX==================================.-->
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<script language='javascript' >
	

	function listaProduto(valor) {
	//verifica se o browser tem suporte a ajax
		try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
		catch(e) {
			try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
			catch(ex) {
				try {ajax = new XMLHttpRequest();}
				catch(exc) {alert("Esse browser nao tem recursos para uso do Ajax"); ajax = null;}
			}
		}
		if(ajax) {
			//deixa apenas o elemento 1 no option, os outros são excluídos
			window.document.frm_troca.troca_garantia_produto.options.length = 1;

			//opcoes é o nome do campo combo
			idOpcao  = document.getElementById("opcoes");

			ajax.open("GET", "ajax_produto_familia.php?familia="+valor, true);
//			alert("ajax_produto_familia.php?familia="+valor);
			ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			ajax.onreadystatechange = function() {
				if(ajax.readyState == 1) {
					idOpcao.innerHTML = "Carregando...!";
				}//enquanto estiver processando...emite a msg
				if(ajax.readyState == 4 ) {
					if(ajax.responseXML) {
						montaCombo(ajax.responseXML);//após ser processado-chama função
					}else {
						idOpcao.innerHTML = "Selecione a familia";//caso não seja um arquivo XML emite a mensagem abaixo
					}
				}
			}
		//passa o código do produto escolhido
		var params = "linha="+valor;
		ajax.send(null);
		}
	}

	function montaCombo(obj){

		var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
		if(dataArray.length > 0) {//total de elementos contidos na tag cidade
			for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
				var item = dataArray[i];
				//conteudo dos campos no arquivo XML
				var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
				var nome      =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
				idOpcao.innerHTML = "Selecione o produto";
				//cria um novo option dinamicamente
				var novo = document.createElement("option");
				//			echo "<option value='-1' >RESSARCIMENTO FINANCEIRO</option>";

				novo.setAttribute("id", "opcoes"); //atribui um ID a esse elemento
				novo.value = codigo;               //atribui um valor
				novo.text  = nome;                 //atribui um texto
				window.document.frm_troca.troca_garantia_produto.options.add(novo);//adiciona o novo elemento
			}

		} else {
			idOpcao.innerHTML = "Selecione a família";//caso o XML volte vazio, printa a mensagem abaixo
		}
	}

	function checkCertificado() {

		if ($('#gerar_certificado_garantia').attr('checked')) {
			$('#motivo_certificado').attr('disabled', '');
		} else {
			$('#motivo_certificado').attr('disabled', 'disabled');
		}

	}

	function verificaCertificado(tipo) {//HD 235182

		if ($('#tipo_atendimento').val() == 18 && $('#causa_troca').val() == 125) {

			$('#div_obs_certificado').css('display','block');

			if (tipo == 1) {

				if (confirm("Gerar Certificado de Garantia?")) {

					$('#gerar_certificado_garantia').attr('checked', 'checked');
					checkCertificado();

				} else {

					$('#gerar_certificado_garantia').attr('checked', '');
					checkCertificado();

				}

			} else {

				$('#gerar_certificado_garantia').attr('checked', 'checked');
				checkCertificado();

			}

		} else {

			$('#div_obs_certificado').css('display','none');
			$('#gerar_certificado_garantia').attr('checked', 'checked');
			checkCertificado();

		}

	}

	function mostraObs(campo){
		if (campo.value == '124' ) {
			$('#id_peca_multi').css('display','block');
		} else {
			$('#id_peca_multi').css('display','none');
		}
		
		if (campo.value =='125' || campo.value=='128' || campo.value=='131') {
			$('#div_obs_causa').css('display','block');
		} else {
			$('#div_obs_causa').css('display','none');
		}
		
		if (campo.value =='127') {
			$('#div_procon').css('display','block');
		} else {
			$('#div_procon').css('display','none');
		}

		if (campo.value =='126') {
			$('#div_vicio_os').css('display','block');
		} else {
			$('#div_vicio_os').css('display','none');
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

	function fnc_pesquisa_produto_troca (produto, referencia, descricao, voltagem, referencia_produto, voltagem_produto, tipo) {
		var url = "";

		url = "pesquisa_produto_troca.php?referencia=" + referencia.value + "&descricao=" + descricao.value + "&voltagem=" + voltagem.value + "&referencia_produto=" + referencia_produto.value + "&voltagem_produto=" + voltagem_produto.value + "&tipo=" + tipo;
		if (referencia_produto.value.length > 0) {
			janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
			janela.produto      = produto;
			janela.descricao    = descricao;
			janela.referencia   = referencia;
			janela.voltagem     = voltagem;
		} else {
			alert("Antes de escolher o produto para troca, informe o produto a ser trocado.");
		}
	}

</script>

<!-- ============= <PHP> VERIFICA DUPLICIDADE DE OS  =============
		Verifica a exist?ncia de uma OS com o mesmo n?mero e em
		caso positivo passa a mensagem para o usu?rio.
=============================================================== --><?php

if (strlen($msg_erro) > 0) {

	if (strpos($msg_erro,"tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";?>

	<table border="0" cellpadding="0" cellspacing="0" align="center" width='700'>
		<tr>
			<td valign="middle" align="center" class='error'><?php
				if (strpos($msg_erro,"ERROR: ") !== false) {
					$erro = "Foi detectado o seguinte erro:<br />";
					$msg_erro = substr($msg_erro, 6, strlen($msg_erro)-6);
				}
				if (strpos($msg_erro,"CONTEXT:")) {// retira CONTEXT:
					$x = explode('CONTEXT:',$msg_erro);
					$msg_erro = $x[0];
				}
				echo $erro . $msg_erro;?>
			</td>
		</tr>
	</table><?php

}

$sql  = "SELECT TO_CHAR (current_timestamp , 'DD/MM/YYYY' )";
$res  = pg_query($con,$sql);
$hoje = pg_fetch_result($res,0,0);?>

<style>

	.Conteudo{
		font-family: Arial;
		font-size: 10px;
		color: #333333;
	}
	.Caixa{
		FONT: 8pt Arial ;
		BORDER-RIGHT:     #6699CC 1px solid;
		BORDER-TOP:       #6699CC 1px solid;
		BORDER-LEFT:      #6699CC 1px solid;
		BORDER-BOTTOM:    #6699CC 1px solid;
		BACKGROUND-COLOR: #FFFFFF;
	}
	.select {
		width: 600px;
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
		font:bold 12px Arial;
		color: #FFFFFF;
		text-align:center;
	}

	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}

	.espaco{
		padding:0 0 0 40px;
	}
	
	label {
		cursor: pointer;
	}

</style><?php

if ($causa_troca=='124') {
	$display_multi_pecas = "display:inline";
} else {
	$display_multi_pecas = "display:none";
}

if ($causa_troca == '125' or $causa_troca == '128' or $causa_troca == '131') {
	$display_obs_causa = "display:inline";
} else {
	$display_obs_causa= "display:none";
}

if ($causa_troca == 125 AND $tipo_atendimento == 18) {//HD 235182
	$display_obs_certificado     = "display:inline";
	$disabled_motivo_certificado = '';
} else {
	$display_obs_certificado = "display:none";
	$disabled_motivo_certificado = 'disabled';
}

if ($causa_troca == '127') {
	$display_procon = "display:inline";
} else {
	$display_procon = "display:none";
}

if ($causa_troca == '126') {
	$display_os = "display:inline";
} else {
	$display_os = "display:none";
}?>

<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_os" method="post" action="<? echo $PHP_SELF ?>" enctype="multipart/form-data" id='frm_os'>
<table border="0" cellpadding="0" cellspacing="0" align="center" class="formulario" width="700">
	<tr class="titulo_tabela">
		<td colspan="2">OS de Troca</td>
	</tr>
	<tr>
		<td valign="top" align="left"><?php
			if (strlen($msg_erro) > 0) {
				$consumidor_cidade		= $_POST['consumidor_cidade'];
				$consumidor_estado		= $_POST['consumidor_estado'];
				$consumidor_nome		= trim($_POST['consumidor_nome']) ;
				$consumidor_fone		= trim($_POST['consumidor_fone']) ;
				$consumidor_endereco	= trim($_POST['consumidor_endereco']) ;
				$consumidor_numero		= trim($_POST['consumidor_numero']) ;
				$consumidor_complemento	= trim($_POST['consumidor_complemento']) ;
				$consumidor_bairro		= trim($_POST['consumidor_bairro']) ;
				$consumidor_cep			= trim($_POST['consumidor_cep']) ;
				$consumidor_rg			= trim($_POST['consumidor_rg']) ;
			}?>

			<input class="frm" type="hidden" name="os" value="<? echo $os ?>" /><?php

			if (strlen($pedido) > 0) { ?>
				<input class="frm" type="hidden" name="produto_referencia" id="produto_referencia" value="<? echo $produto_referencia ?>">
				<input class="frm" type="hidden" name="produto_descricao" id="produto_descricao" value="<? echo $produto_descricao ?>"><?php
			}?>

			<table width="100%" border="0" cellspacing="2" cellpadding="0">
				<tr class='subtitulo'>
					<td colspan="4">Dados do Posto</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td nowrap>
						Código do Posto
						<br />
						<input type="text" name="posto_codigo" size="15" value="<?=$posto_codigo?>" class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';" />&nbsp;
						<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="fnc_pesquisa_posto2(document.frm_os.posto_codigo, document.frm_os.posto_nome,'codigo')" />
					</td>
					<td nowrap>
						Nome do Posto
						<br />
						<input type="text" name="posto_nome" size="50" value="<?=$posto_nome?>" class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';" />&nbsp;
						<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="fnc_pesquisa_posto2(document.frm_os.posto_codigo, document.frm_os.posto_nome, 'nome')" style="cursor:pointer;" />
					</td>
					<td valign='top'>
						Tipo de Atendimento
						<br />
						<select name="tipo_atendimento" id="tipo_atendimento" size="1" style='width:200px; height=18px;' onchange="verificaCertificado(1)" class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';">
							<option selected></option><?php
							$sql = "SELECT tipo_atendimento,descricao
									FROM tbl_tipo_atendimento
									WHERE fabrica = $login_fabrica
										AND tipo_atendimento in(17, 18 , 35)
									ORDER BY tipo_atendimento";
							//		$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = 19 ORDER BY tipo_atendimento";
							$res = pg_query($con,$sql) ;
							for ($i = 0 ; $i < pg_num_rows($res) ; $i++ ) {
								echo "<option ";
								if ($tipo_atendimento == pg_fetch_result($res,$i,tipo_atendimento) ) echo " selected ";
								echo " value='" . pg_fetch_result($res,$i,tipo_atendimento) . "'>" ;
								echo pg_fetch_result($res,$i,descricao) ;
								echo "</option>";
							}?>
						</select>
					</td>
				</tr>
			</table>

			<table width="100%" border="0" cellspacing="2" cellpadding="0">
				<tr class='subtitulo'><?php
					if ($login_fabrica == 19 || $login_fabrica == 1) {
						$colspan = 5;
					} else {
						$colspan = 4;
					}?>
					<td colspan="<?=$colspan?>">Dados do Produto</td>
				</tr>
				<tr valign="top">
					<td nowrap><?php
						if ($pedir_sua_os == 't') { ?>
							OS Fabricante
							<br />
							<input name="sua_os" class="frm" type="text" size="20" maxlength="20" value="<?=$sua_os?>" onblur="VerificaSuaOS(this); this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';displayText('&nbsp;Digite aqui o número da OS do Fabricante.');" /><?php
						} else {
							echo "&nbsp;";
							if (strlen($sua_os) > 0) {
								echo "<input type='hidden' name='sua_os' value='$sua_os'>";
							} else {
								echo "<input type='hidden' name='sua_os'>";
							}
						}?>
					</td><?php
					if (trim(strlen($data_abertura)) == 0 AND $login_fabrica == 7) {
						$data_abertura = $hoje;
					}?>
					<td nowrap>
						Data Abertura
						<br />
						<input name="data_abertura" id="data_abertura" size="12" maxlength="10" value="<?=$data_abertura?>" rel='data' type="text" class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';" tabindex="0" />
					</td><?php
					if ($login_fabrica == 19) { ?>
						<td nowrap>
							Qtde.Produtos
							<br />
							<input name="qtde_produtos" size="2" maxlength="3" value="<?=$qtde_produtos?>" type="text" tabindex="0" class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';" />
						</td><?php
					}?>
					<td nowrap><?php
						if ($login_fabrica == 3) {
							echo "Código do Produto";
						} else {
							echo "Referência do Produto";
						}?>
						<br /><?php
						if (strlen($pedido) > 0) { ?>
							<b><? echo $produto_referencia ?></b><?php
						} else {?>
							<input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<?=$produto_referencia?>" onblur="<?php if ($login_fabrica == 5) { ?>fnc_pesquisa_produto2(document.frm_os.produto_referencia, document.frm_os.produto_descricao, 'referencia');<?php }?>this.className='frm';" onfocus="this.className='frm-on';" />&nbsp;
							<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="fnc_pesquisa_produto2(document.frm_os.produto_referencia, document.frm_os.produto_descricao, 'referencia', document.frm_os.produto_voltagem, document.frm_os.valor_troca)" /><?php
						}?>
					</td>
					<td nowrap><?php
						if ($login_fabrica == 3) {
							echo "Modelo do Produto";
						} else {
							echo "Descrição do Produto";
						}?>
						<br /><?php
						if (strlen($pedido) > 0) { ?>
							<b><? echo $produto_descricao ?></b><?php
						} else {?>
							<input class="frm" type="text" name="produto_descricao" size="30" value="<?=$produto_descricao?>"
							 onblur="<?php if ($login_fabrica == 5 or $login_fabrica == 15) { ?>fnc_pesquisa_produto2(document.frm_os.produto_referencia, document.frm_os.produto_descricao, 'descricao');<?php }?>this.className='frm';" onfocus="this.className='frm-on';" />&nbsp;
							<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="fnc_pesquisa_produto2(document.frm_os.produto_referencia, document.frm_os.produto_descricao, 'descricao', document.frm_os.produto_voltagem, document.frm_os.valor_troca)" /><?php
						}?>
					</td><?php
					if ($login_fabrica == 1) { ?>
						<td nowrap>
							Voltagem
							<br />
							<input class="frm" type="text" name="produto_voltagem" size="5" maxlength='10' class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';" readonly value="<? echo $produto_voltagem ?>" >
						</td><?php
					}?>
				</tr>
			</table>

			<table width="100%" border="0" cellspacing="2" cellpadding="0">
				<tr>
					<td>&nbsp;</td>
					<td nowrap>
						N. Série
						<br />
						<input type="text" name="produto_serie" id="produto_serie" size="15" maxlength="20" value="<? echo $produto_serie ?>" class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';" />
						<input class="frm" type="hidden" name="locacao_serie" value="" id="locacao_serie" />
						<input name ="valor_troca" id="valor_troca" type="hidden" value="<? echo $valor_troca ?>" />
					</td>
					<td nowrap>
						Código Fabricação
						<br />
						<input name="codigo_fabricacao" class="frm" type="text" size="13" maxlength="20" value="<? echo $codigo_fabricacao ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';displayText('&nbsp;Digite aqui o número do Código de Fabricação.');">
					</td>
					<td nowrap>
						Autorização
						<br /><?php
						//HD 303195 - não estava buscando os valores do POST a variavel era sobrescrita
						$admin_autoriza = !empty($_POST['admin_autoriza']) ? $_POST['admin_autoriza'] : $admin_autoriza;?>
						<select name="admin_autoriza" size="1" style='width:200px; height=18px;' class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';">
							<option selected></option><?php
							$sql = "SELECT admin,nome_completo
									FROM tbl_admin
									WHERE fabrica = $login_fabrica
									AND admin in(112,257,626)
									ORDER BY nome_completo";

							$res = pg_query($con,$sql) ;
							$tot = pg_num_rows($res);
							for ($i = 0; $i < $tot; $i++) {
								echo "<option ";
								if ($admin_autoriza == pg_fetch_result($res,$i,'admin')) echo " selected ";
								echo " value='" . pg_fetch_result($res,$i,'admin') . "'>" ;
								echo pg_fetch_result($res,$i,'nome_completo') ;
								echo "</option>";
							}?>
						</select>
					</td>
					<td nowrap>
						Motivo da Troca
						<br /><?php
						//HD 303195 - não estava buscando os valores do POST a variavel era sobrescrita
						$causa_troca = !empty($_POST['causa_troca']) ? $_POST['causa_troca'] : $causa_troca;?>
						<select name="causa_troca" id="causa_troca" size="1" style='width:200px; height=18px;' onchange='mostraObs(this);verificaCertificado(1)' class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';"> 
							<option value=""></option><?php
							$sql = "SELECT causa_troca,descricao
									FROM tbl_causa_troca
									WHERE fabrica = $login_fabrica
									AND causa_troca in(124,125,126,127,128,130,131)
									ORDER BY descricao";
							$res = pg_query($con,$sql) ;
							$tot = pg_num_rows($res);
							for ($i = 0; $i < $tot; $i++) {
								$xcausa_troca = pg_fetch_result($res,$i,'causa_troca');
								$desc_troca   = pg_fetch_result($res,$i,'descricao');
								echo "<option ".($causa_troca == $xcausa_troca ? ' selected="selected" ' : '')."value='".$xcausa_troca."'>".$desc_troca."</option>";
							}?>
							<?if ($login_fabrica == 1) {?>
								<option value="237" <?if($causa_troca == 237) echo "selected='selected'";?>>Reverter Produto</option>
							<?}?>
						</select>
					</td>
				</tr>

				<?php if ($causa_troca == 130){
					$display = "";
				}else{
					$display = "display:none";
				}?>

				<tr style="<?php echo $display ?>" id="tr_cidade_estado">
					<td>&nbsp;</td>
					<td>
						Estado <br>
						<select name="estado_causa_troca" id="estado_causa_troca">
							<option value=""></option>
							<?php
							$sql = "SELECT distinct estado 
							FROM tbl_ibge order by estado";
							$res = pg_query($con,$sql);

							for ($i=0; $i < pg_num_rows($res); $i++) { 
								$xestado_causa_troca = pg_fetch_result($res, $i, 'estado');

								$selected = ($estado_causa_troca == $xestado_causa_troca) ? "SELECTED" : "" ; ?>

								<option value="<?php echo $xestado_causa_troca ?>" <?php echo "$selected" ?>>
									<?php echo $xestado_causa_troca ?>
								</option>

								<?
							}

							?>
							
						</select>
					</td>
					<td colspan="2">
						Cidade <br>
						<select name="cidade_causa_troca" id="cidade_causa_troca">
							
							<?php  
							if (!empty($cidade_causa_troca) or !empty($estado_causa_troca)){ 
								$sql = "SELECT cidade from tbl_ibge where estado = '$estado_causa_troca' order by cidade ";
								$res = pg_query($con,$sql);
								?>
								<option value=""></option>

								<?
								for ($i=0; $i < pg_num_rows($res); $i++) { 
									
									$xcidade = pg_fetch_result($res, $i, 'cidade');
									$selected = ($cidade_causa_troca == $xcidade) ? 'SELECTED' : '' ;
								?>

								<option value="<?php echo $xcidade ?>" <?php echo $selected ?> > <?php echo $xcidade ?></option>
									
								<?php
								}
							
							}else{?>

								<option value=""></option>

							<?
							}
							?>
						</select>
					</td>

					
					<td>&nbsp;</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td colspan="5">
						<div id='id_peca_multi' style='<?echo $display_multi_pecas;?>'>
							Ref:&nbsp;<input class='frm' type="text" name="peca_referencia_multi" id="peca_referencia_multi" value="" size="15" maxlength="20">&nbsp;<IMG src='imagens/lupa.png' height='18' onClick="fnc_pesquisa_peca(document.frm_os.peca_referencia_multi,document.frm_os.peca_descricao_multi,'referencia')" style='cursor:pointer;' />
							&nbsp;&nbsp;&nbsp;
							Descrição:&nbsp;<input class='frm' type="text" name="peca_descricao_multi" id="peca_descricao_multi" value="" size="30" maxlength="50" onfocus="this.className='frm-on';" onblur="this.className='frm';" />&nbsp;
							<img src='imagens/lupa.png' height='18' onclick="fnc_pesquisa_peca(document.frm_os.peca_referencia_multi, document.frm_os.peca_descricao_multi, 'descricao')" style='cursor:pointer;' align='absmiddle' />
							<input type='button' name='adicionar_peca' id='adicionar_peca' value='Adicionar' class='frm' onClick='addItPeca();' />
							<br />
							<strong style='font-weight:normal;color:gray;font-size:10px'>(Selecione a peça e clique em 'Adicionar')</strong>
							<br />
							<select multiple="multiple" SIZE='6' id='multi_peca' class='select ' name="multi_peca[]" class='frm' onfocus="this.className='frm-on';" onblur="this.className='frm';"><?php
								if (count($multi_peca) > 0) {
									for ($i = 0; $i < count($multi_peca); $i++) {
										$sql = " SELECT tbl_peca.referencia,
														tbl_peca.descricao
													FROM tbl_peca
													WHERE fabrica = $login_fabrica
													AND   referencia  = '".$multi_peca[$i]."'";
										$res = pg_query($con,$sql);
										if (pg_num_rows($res) > 0) {
											echo "<option value='".pg_fetch_result($res,0,'referencia')."' >".pg_fetch_result($res,0,'referencia') . " - " . pg_fetch_result($res,0,'descricao') ."</option>";
										}
									}
								}?>
							</select>
							<input TYPE="BUTTON" VALUE="Remover" onClick="delItPeca();" class='frm'></input>
						</div>
						<div id='div_obs_causa' style='<?echo $display_obs_causa;?>'>
							<p>Justificativa: </p>
							<p><textarea name="obs_causa" id="obs_causa" class="frm" rows="4" cols="102" onfocus="this.className='frm-on';" onblur="this.className='frm';"><?=$obs_causa?></textarea></p>
						</div>
						<div id='div_obs_certificado' style='<?=$display_obs_certificado;?>'><?php //HD 235182?>
							<p>
								<p>Motivo da Geração do Certificado</p>
								<textarea name="motivo_certificado" id="motivo_certificado" rows="4" cols="102" class="frm" disabled="<?=$disabled_motivo_certificado?>" onfocus="this.className='frm-on';" onblur="this.className='frm';"><?=$motivo_certificado?></textarea>
								<br />
								<label for="gerar_certificado_garantia" onclick="checkCertificado()"><b>Gerar Certificado de Garantia?</b></label>
								<input type="checkbox" name="gerar_certificado_garantia" id="gerar_certificado_garantia" value="1" onclick="checkCertificado()" checked="checked" />
								<br />
								<br />
							</p>
						</div>
						<div id='div_procon' style='<?echo $display_procon;?>'>
							<p>Número do processo: </p><?php
							if ($causa_troca == 127 && !isset($_POST['numero_processo'])) $numero_processo = $obs_causa;?>
							<p><input type='text' name='numero_processo' value='<?=$numero_processo?>' id='numero_processo' class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';"></p>
						</div>
						<div id='div_vicio_os' style='<?echo $display_os;?>'>
							<p>Informe as OSs: </p><?php
							if ($causa_troca == 126 && (!isset($_POST['v_os1']) || !isset($_POST['v_os2']) )) {
								list($v_os1, $v_os2) = @explode('<br/>',$obs_causa);
							}?>
							<p>
								1. <input type='text' name='v_os1' value='<?=$v_os1?>' id='v_os1' class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';" />
								2. <input type='text' name='v_os2' value='<?=$v_os2?>' id='v_os2' class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';" />
								<!-- 3. <input type='text' name='v_os3' value='<?=$v_os3?>' id='v_os3' class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';" /> -->
							</p>
						</div>
					</td>
				</tr>
			</table>

			<table id="produto_origem" style="width: 100%; border: 0; display: none;" cellspacing="2" cellpadding="0">
				<tr class='subtitulo'>
					<td colspan="4">
						<input type="hidden" name="produto_origem_id" id="produto_origem_id" value="<?=$produto_origem_id?>" />
						Produto de Origem
					</td>
				</tr>
				<tr>
					<td style="width: 25%;">
						&nbsp;
					</td>
					<td>
						Refêrencia
						<br />
						<input type="text" name="produto_origem_referencia" id="produto_origem_referencia" value="<?=$produto_origem_referencia?>" class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';" style="width: 100px;" />
						<img src="imagens/lupa.png" style="cursor: pointer; border: 0;" onclick="fnc_pesquisa_produto_origem($('#produto_origem_referencia').val(), '', 'referencia')" align="absmiddle" />
					</td>
					<td>
						Descrição
						<br />
						<input type="text" name="produto_origem_descricao" id="produto_origem_descricao" value="<?=$produto_origem_descricao?>" class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';" style="width: 200px;" />
						<img src="imagens/lupa.png" style="cursor: pointer; border: 0;" onclick="fnc_pesquisa_produto_origem('', $('#produto_origem_descricao').val(), 'descricao')" align="absmiddle" />
					</td>
					<td style="width: 15%;">
						&nbsp;
					</td>
				</tr>
			</table>

			<input type="hidden" name="consumidor_cliente" />
			<input type="hidden" name="consumidor_rg" />

			<table width='100%' align='center' border='0' cellspacing='2' cellpadding='0'>
				<tr class="subtitulo">
					<td colspan="100%">Dados do Consumidor</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
						Nome Consumidor
						<br />
						<input class="frm" type="text" name="consumidor_nome" size="40" maxlength="50" value="<? echo $consumidor_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)" <? if ($login_fabrica == 5) { ?> onblur=" fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, 'nome'); displayText('&nbsp;');" <? } ?> onblur = "this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';  displayText('&nbsp;Insira aqui o nome do Cliente.');">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, "nome")'  style='cursor: pointer'>
					</td><?php
					if ($login_fabrica == 1) {?>
						<td>
							Tipo Consumidor
							<br /><?php
								if ($fisica_juridica == "F") $selectPF = " SELECTED";
								else if ($fisica_juridica == "J") $selectPJ = " SELECTED";?>
							<select name="fisica_juridica" id="fisica_juridica" class="frm" onfocus="this.className='frm-on';" onblur="this.className='frm';">
								<option></option>
								<option value="F" <?php echo $selectPF; ?>>Pessoa Física</option>
								<option value="J" <?php echo $selectPJ; ?>>Pessoa Jurídica</option>
							</select>
						</td><?php
					}?>
					<td>
						C.P.F. Consumidor
						<br />
						<input class="frm" type="text" name="consumidor_cpf" id="consumidor_cpf" size="17" maxlength="14" value="<? echo $consumidor_cpf ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_consumidor (document.frm_os.consumidor_cpf,'cpf'); this.className='frm'; displayText('&nbsp;');" <? } ?> onblur="this.className = 'frm'; displayText('&nbsp;');" onfocus="formata_cpf_cnpj(this,1); this.className='frm-on'; displayText('&nbsp;Digite o CPF do consumidor. Pode ser digitado diretamente, ou separado com pontos e traços.');" onkeypress="mascara_cpf(this, event);" />&nbsp;
						<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='fnc_pesquisa_consumidor(document.frm_os.consumidor_cpf,"cpf")' style='cursor: pointer' />
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td valign='top' align='left'>
						Email de Contato
						<br />
						<input type='text' name='consumidor_email' class='frm' value="<?=$consumidor_email;?>" size='30' maxlength='50' onfocus="this.className='frm-on';" onblur="this.className='frm';" />
					</td>
					<td>
						Fone
						<br />
						<input class="frm" type="text" name="consumidor_fone" rel='fone' size="15" maxlength="20" value="<? echo $consumidor_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');" />
					</td>
					<td>
						CEP
						<br />
						<input class="frm" type="text" name="consumidor_cep"   size="8" maxlength="8" value="<? echo $consumidor_cep ?>" onblur="this.className='frm'; displayText('&nbsp;'); buscaCEP(this.value, document.frm_os.consumidor_endereco, document.frm_os.consumidor_bairro, document.frm_os.consumidor_cidade, document.frm_os.consumidor_estado) ;" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CEP do consumidor.');" />
					</td>
				</tr>
				<tr class="top">
					<td>&nbsp;</td>
					<td>Endereço</td>
					<td>Número</td>
					<td>Compl.</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
						<input class="frm" type="text" name="consumidor_endereco" size="30" maxlength="60" value="<? echo $consumidor_endereco ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o endere?o do consumidor.');">
					</td>
					<td>
						<input class="frm" type="text" name="consumidor_numero" size="10" maxlength="20" value="<? echo $consumidor_numero ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o número do endere?o do consumidor.');">
					</td>
					<td>
						<input class="frm" type="text" name="consumidor_complemento" size="15" maxlength="20" value="<? echo $consumidor_complemento ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endere?o do consumidor.');">
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>Bairro</td>
					<td>Cidade</td>
					<td>Estado</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
						<input class="frm" type="text" name="consumidor_bairro" size="15" maxlength="30" value="<? echo $consumidor_bairro ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o bairro do consumidor.');">
					</td>
					<td>
						<input class="frm" type="text" name="consumidor_cidade" size="15" maxlength="50" value="<? echo $consumidor_cidade ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite a cidade do consumidor.');">
					</td>
					<td>
						<input class="frm" type="text" name="consumidor_estado" size="2" maxlength="2" value="<? echo $consumidor_estado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o estado do consumidor.');">
					</td>
				</tr>
			</table>

			<table width='100%' align='center' border='0' cellspacing='2' cellpadding='0'>
				<tr>
					<td class="subtitulo" colspan="4">Dados da Revenda</td>
				</tr>
				<tr valign="top">
					<td>&nbsp;</td>
					<td>
						Nome Revenda
						<br />
						<input class="frm" type="text" name="revenda_nome" size="30" maxlength="50" value="<? echo $revenda_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)" onblur="<? if ($login_fabrica == 5) {?>fnc_pesquisa_revenda(document.frm_os.revenda_nome, 'nome');<? } ?> this.className='frm';" onfocus="this.className='frm-on';" />&nbsp;
						<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='fnc_pesquisa_revenda(document.frm_os.revenda_nome, "nome")' style='cursor: pointer' />
					</td>
					<td>
						CNPJ Revenda
						<br />
						<input class="frm" type="text" name="revenda_cnpj" id="revenda_cnpj" size="20" maxlength="18" value="<? echo $revenda_cnpj ?>" onblur="<? if ($login_fabrica == 5) { ?>fnc_pesquisa_revenda(document.frm_os.revenda_cnpj, 'cnpj');<? } ?>this.className='frm';" onfocus="formata_cpf_cnpj(this,2);this.className='frm-on';" onkeypress="mascara_cnpj(this, event);" />&nbsp;
						<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='fnc_pesquisa_revenda(document.frm_os.revenda_cnpj, "cnpj")' style='cursor: pointer' />
					</td>
					<td>
						Nota Fiscal
						<br />
						<input class="frm" type="text" name="nota_fiscal" id="nota_fiscal" size="10" maxlength="20" value="<? echo $nota_fiscal ?>" onfocus="this.className = 'frm-on';" onblur="this.className = 'frm';" />
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
						<input type="hidden" name="consumidor_revenda" value=C>
						Aparência do Produto
						<br /><?php
							echo "<input class='frm' type='text' name='aparencia_produto' size='30' value='$aparencia_produto' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a apar?ncia externa do aparelho deixado no balc?o.');\">";?>
					</td>
					<td>
						Acessórios
						<br />
						<input class="frm" type="text" name="acessorios" size="30" value="<? echo $acessorios ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Texto livre com os acess?rios deixados junto ao produto.');" />
					</td>
					<td>
						Data Compra
						<br />
						<input class="frm" type="text" name="data_nf" size="12" maxlength="10" value="<?=$data_nf ?>" rel='data' tabindex="0" onfocus="this.className='frm-on';" onblur="this.className='frm';" />
						<!--<br /><font face='arial' size='1'>Ex.: 11/02/2009</font>-->
					</td>
				</tr>
			</table><?php

			if ($login_fabrica == 1) {//hd 21461

				echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>";
					echo "<tr class='subtitulo'>";
						echo "<td colspan='100%'>Dados dos Produtos para Troca</td>";
					echo "</tr>";
					echo "<tr align='center'>";
						echo "<td colspan='100%'>";
							if (strlen($os) == 0) {
								echo "<b>Informe um ou mais produtos para troca</b><br />(Clique na lupa para visualizar os produtos disponíveis para troca)<br /><br />";
							} else {
								echo '&nbsp;';
							}
						echo "</td>";
					echo "</tr>";
					echo "<tr>";
						echo "<td>&nbsp;</td>";
						echo "<td>Trocar por</td>";
						echo "<td>Descrição do produto</td>";
						echo "<td>Voltagem</td>";//HD 303195
						if (strlen($os) == 0) {
							echo "<td></td>";
						}
					echo "</tr>";

					if ((strlen($_GET["os"]) == 0) && ($_POST["produto_referencia_troca0"] == "KIT")) {
						$produto_troca				[0] = trim($_POST["produto_troca0"]);
						$produto_os_item			[0] = trim($_POST["produto_os_troca0"]);
						$produto_referencia_troca	[0] = trim($_POST["produto_referencia_troca0"]);
						$produto_descricao_troca	[0] = trim($_POST["produto_descricao_troca0"]);
						$produto_voltagem_troca		[0] = trim($_POST["produto_voltagem_troca0"]);
						$produto_observacao_troca	[0] = trim($_POST["produto_observacao_troca0"]);
					}

					for ($p = 0; $p < $numero_produtos_troca; $p++) {

						echo "<tr align='left' valign=middle>";
							echo "<td>&nbsp;</td>";
							echo "<td nowrap>";
								echo "<input class='frm' type='hidden' name='produto_troca$p' value='" . $produto_troca[$p] . "'>";
								echo "<input class='frm' type='hidden' name='produto_os_troca$p' value='" . $produto_os_item[$p] . "'>";
								if (strlen($os) > 0) {
									echo "<input class='frm' type='text' name='produto_referencia_troca$p' size='10' maxlength='30' value='" . $produto_referencia_troca[$p] . "' readonly>";
								} else {
									echo "<input class='frm' type='text' name='produto_referencia_troca$p' size='10' maxlength='30' value='" . $produto_referencia_troca[$p] . "' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Entre com a referência do produto e clique na lupa para efetuar a pesquisa.');\">
									<img src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_produto_troca (document.frm_os.produto_troca$p, document.frm_os.produto_referencia_troca$p, document.frm_os.produto_descricao_troca$p, document.frm_os.produto_voltagem_troca$p, document.frm_os.produto_referencia, document.frm_os.produto_voltagem, 'referencia')\" style='cursor: pointer' />";
								}
							echo "</td>";
							echo "<td nowrap>";
							if (strlen($os) > 0) {
								echo "<input class='frm' type='text' name='produto_descricao_troca$p' size='40' value='" . $produto_descricao_troca[$p] . "' readonly>";
							} else {
								echo "<input class='frm' type='text' name='produto_descricao_troca$p' size='30' value='" . $produto_descricao_troca[$p] . "' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Digite aqui o modelo do produto e clique na lupa para efetuar a pesquisa.');\">&nbsp;
								<img src='imagens/lupa.png' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_produto_troca (document.frm_os.produto_troca$p, document.frm_os.produto_referencia_troca$p, document.frm_os.produto_descricao_troca$p, document.frm_os.produto_voltagem_troca$p, document.frm_os.produto_referencia, document.frm_os.produto_voltagem, 'descricao')\"  style='cursor: pointer'>";
							}
							echo "</td>";
							echo "<td nowrap>";
								echo "<input class='frm' type='text' name='produto_voltagem_troca$p' size='5' value='" . $produto_voltagem_troca[$p] . "' readonly onfocus=\"this.className='frm-on';\" onblur=\"this.className='frm';\" />";
							echo "</td>";
							//HD 303195
							/*echo "<td>
								<input class='frm' type='text' name='produto_observacao_troca$p' size=35 value='" . $produto_observacao_troca[$p] . "'>
							</td>";*/

							if (strlen($os) == 0) {
								echo "<td>";
									echo "<img src='imagens/btn_limpar.gif' onclick=\"document.frm_os.produto_troca$p.value=''; document.frm_os.produto_os_troca$p.value=''; document.frm_os.produto_referencia_troca$p.value=''; document.frm_os.produto_descricao_troca$p.value=''; document.frm_os.produto_voltagem_troca$p.value=''; document.frm_os.produto_observacao_troca$p.value='';\">";
								echo "</td>";
							}

						echo "</tr>";

					}

					//HD 303195 - INI
					$produto_obs_troca = (empty($produto_obs_troca) && is_string($produto_observacao_troca)) ? $produto_observacao_troca : (!empty($produto_obs_troca) ? $produto_obs_troca : $produto_observacao_troca[0]);

					echo "<tr>";
						echo "<td>&nbsp;</td>";
						echo "<td colspan='100%'>Observações da Troca</td>";
					echo "</tr>";
					echo "<tr>";
						echo "<td>&nbsp;</td>";
						echo "<td colspan='100%'>";
							echo "<textarea class='frm' name='produto_obs_troca' id='produto_obs_troca' class='frm' cols='102' rows='5' onfocus=\"this.className='frm-on';\" onblur=\"this.className='frm';\">$produto_obs_troca</textarea>";
						echo "</td>";
					echo "</tr>";
					//HD 303195 - FIM
				echo "</table>";

			}?>
			<center>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Orientações do SAC ao Posto Autorizado</font>
				<br />
				<textarea name='orientacao_sac' rows='4' cols='50' class='frm' onfocus="this.className='frm-on';" onblur="this.className='frm';"><?=$orientacao_sac?></textarea>
				<br>
				<br>
			</center>
		</td>
	</tr>
</table>

<?php
if ($anexaNotaFiscal) { ?>
<table width="100%" border="0" cellspacing="5" cellpadding="0">
	<tr>
		<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">

<?php
			if (!temNF($os, 'bool') and !temNF($os, 'bool', 2)) {
				echo $inputNotaFiscal;
			} else {
				echo '<p style="text-align:center;font-weight:bold">Imagem em anexo</p>' . temNF($os) . temNF($os, 'link', 2) . $include_imgZoom;
			} ?>
		</td>
	</tr>
</table>
<?php
}
if ($os_reincidente == 't') {?>
	<hr />
	<center>
		<table style=' border: #D3BE96 1px solid; background-color: #FCF0D8 ' width="700px">
			<tr>
				<td align='center'>
					<b>OS REINCIDENTE</b>
					<br /><font size='2'>Gentileza justificar abaixo se esse atendimento tem procedência, pois foi localizado num período menor ou igual a 90 dias outra(s) OS(s) concluída(s) pelo seu posto com os mesmos dados de nota fiscal e produto. Se o lançamento estiver incorreto, solicitamos não proceder com a gravação da OS.</font>
					<br />
					<br />
					<textarea name="obs_reincidencia" cols='66' rows='5' class='frm'><? echo $obs_reincidencia ?></textarea>
				</td>
			</tr>
		</table>
	</center><?php
}?>

<table width="100%" border="0" cellspacing="5" cellpadding="0">
	<tr>
		<td height="27" valign="middle" align="center" bgcolor="#FFFFFF"><?php
		if (strlen($os) > 0) {?>
			<input type="hidden" name="btn_acao" value="" />
			<input type='button' value='Alterar' style="cursor:pointer" rel='sem_submit' class='verifica_servidor'  onclick=" if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value = 'continuar' ;  document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Alterar os itens da Ordem de Serviço" border='0'>
			<input type='button' value='Apagar' style="cursor:pointer" rel='sem_submit' class='verifica_servidor' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente apagar esta OS?') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); } else { return; }; } else { alert ('Aguarde submissão') }" ALT="Apagar a Ordem de Servi?o" border='0' /><?php
		} else {?>
			<input type="hidden" name="btn_acao" value="" />
			<input type='button' value='Continuar' style="cursor:pointer" rel='sem_submit' class='verifica_servidor' onclick="if (document.frm_os.btn_acao.value == '') { if (confirm('Deseja realmente gravar esta OS?') == true) { verificaSerie(); } else { return; } } else { alert('Aguarde submissão'); }" ALT="Continuar com Ordem de Serviço" border='0' />
			<?php
		}?>
		</td>
	</tr>
</table>

<input type='hidden' name='revenda_fone' />
<input type='hidden' name='revenda_cidade' />
<input type='hidden' name='revenda_estado' />
<input type='hidden' name='revenda_endereco' />
<input type='hidden' name='revenda_numero' />
<input type='hidden' name='revenda_complemento' />
<input type='hidden' name='revenda_bairro' />
<input type='hidden' name='revenda_cep' />
<input type='hidden' name='revenda_email' />

</form>
<script type="text/javascript">
	mostraObs(document.getElementById('causa_troca'));
	<?
	if (!empty($motivo_certificado)) {?>
		verificaCertificado(2);<?php //HD 235182
	}?>
</script>
<? include "rodape.php";?>
