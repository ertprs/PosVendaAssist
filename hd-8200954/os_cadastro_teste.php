<?php
/*  11/12/2009
	Esta versão tem bastantes alteraçoes... Se for o caso, voltar à versão anterior e me avisar!
*/
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include '_traducao_erro.php';

$os = $_GET['os'];
if ($login_fabrica==1 and strlen($os)>0){
	$sql = "SELECT consumidor_revenda FROM tbl_os WHERE os=$os";
	$res = pg_query($con,$sql);
	$verifica = pg_fetch_result($res,0,0);
	if($verifica=="R"){
		header ("Location: os_revenda_alterar.php?os=$os");
		exit;
	}
	#HD 11906
	$sql = "SELECT os FROM tbl_os_troca WHERE os=$os";
	$res = pg_query($con,$sql);
	if (pg_num_rows($res) > 0) {
		header ("Location: os_cadastro_troca.php?os=$os");
		exit;
	}
}

/* HD 35521 */
$pre_os = trim($_GET['pre_os']);
$os_offline = $_POST['os_offline'];

$bd_locacao = array(36,82,83,84,90);    // Tipo Posto locação para Black & Decker

if ($login_fabrica == 1 and (in_array($login_tipo_posto, $bd_locacao))) {
	header ("Location: os_cadastro_locacao.php");
	exit;
}

//takashi 28-02 15:00 liberado conforme contato da fabiola HD 1367
if($login_fabrica==1) {
	include 'os_cadastro_black.php';
	exit;
}

# HD 17218 liberado para todos os Postos - HD 33776 Maxcom
if(($login_fabrica==14 or $login_fabrica==66) and $os_offline <> '1'){
	include 'os_cadastro_intelbras_ajax.php';
	exit;
}

/*	Britânia	LIBERADO TAKASHI 06-02-07 10:11 HD 1141
	Mondial		Liberado para todos os postos da mondial em 11/01/2008 - HD 9975
	Filizola
	LENOXX		LIBERADO POR WELLINGTON 20/12/2006 - 14:05:00
*/
$usam_os_cad_tudo_com_offline = array(3, 11);

/*  Não precisa que $os_offline != 1:
	Dynacom     liberado 03/05 HD 1454, 12660
	TecToy      liberado segunda 06-11-2006 conforme email enviado por angelica, andre ricardo e leandro - Takashi
	Latinatec	Liberado dia 14/12/2006 HD 2300 - Posto de testes MHM Abrantes, Geni Peres
	Lorenzetti
	Suggar
	HBTech
	Metalight
	Cafe Automatic
*/
$usam_os_cadastro_tudo = array(2, 5, 6, 7, 15, 19, 24, 25, 26);
// echo $login_fabrica;
if (($os_offline != '1' and in_array($login_fabrica,$usam_os_cad_tudo_com_offline))
	or
	(in_array($login_fabrica,$usam_os_cadastro_tudo))
	or
	($login_fabrica >= 28)
	or
	($login_fabrica==3 and ($login_posto=='5037' or $login_posto=='595'))) {
// 	echo "OS cadastro tudo";

	include 'os_cadastro_tudo.php';
	exit;
}

include 'funcoes.php';

function anti_injection($string) {
	$a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
	return strtr(strip_tags(trim($string)), $a_limpa);
}

function checaCPF($cpf,$return_str = true) {
	global $cook_idioma, $login_pais, $con;    // Para conectar com o banco...
	$cpf = preg_replace("/\D/","",$cpf);   // Limpa o CPF
	if (strlen($login_pais)>0 and $login_pais != "BR") return $cpf;
	if (!$cpf or $cpf == '' or (strlen($cpf) != 11 and strlen($cpf) != 14)) return false;

	$res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
	if ($res_cpf === false) {
		return ($return_str) ? pg_last_error($con) : false;
	}
	return $cpf;
}

// Neste programa o 'default' é 'null', e não 'false'
function check_post_field($fieldname, $returns = 'null') {
	if (!isset($_POST[$fieldname])) return $returns;
	$data = anti_injection($_POST[$fieldname]);
// 	echo "<p><b>$fieldname</b>: $data</p>\n";
	return (strlen($data)==0) ? $returns : $data;
}

function pg_quote($str) {
    if (is_bool($str)) return ($str) ? 'true' : 'false';
    if (is_null($str)) return 'null';
	if (in_array($str,array('null','true','false','t','f'))) return $str;
	return "'".pg_escape_string($str)."'";
}

//if ($login_fabrica == 1) {
//	echo "<H2>Sistema em manutenção. Estará disponível em alguns instantes.</H2>";
//	exit;
//}

#-------- Libera digitação de OS pelo distribuidor --------------
$posto = $login_posto ;
if ($login_fabrica == 3) {
	$sql = "SELECT tbl_tipo_posto.distribuidor FROM tbl_tipo_posto JOIN tbl_posto_fabrica USING (tipo_Posto) WHERE tbl_posto_fabrica.posto = $login_posto AND tbl_posto_fabrica.fabrica = $login_fabrica";
	$res = @pg_query($con,$sql);
	$distribuidor_digita = pg_fetch_result ($res,0,0);
	if (strlen ($posto) == 0) $posto = $login_posto;
}
#----------------------------------------------------------------

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = @pg_query($con,$sql);
$pedir_sua_os = pg_fetch_result ($res,0,pedir_sua_os);
$pedir_defeito_reclamado_descricao = pg_fetch_result ($res,0,pedir_defeito_reclamado_descricao);

/*======= <PHP> FUNÇOES DOS BOTÕES DE AÇÃO =========*/


$btn_acao = strtolower ($_POST['btn_acao']);

$msg_erro = "";

if ($btn_acao == "continuar") {
	$os = $_POST['os'];

//MLG 06/12/2010 - HD 326935 - Limitar por HTML e PHP o comprimento das strings para campos varchar(x).
	$_POST['consumidor_bairro']			= substr($_POST['consumidor_bairro']		, 0, 80);
	$_POST['consumidor_celular']		= substr($_POST['consumidor_celular']		, 0, 20);
	$_POST['consumidor_cep']			= substr(preg_replace('/\D/', '', $_POST['consumidor_cep'])	, 0, 8);
	$_POST['consumidor_cpf']			= substr(preg_replace('/\D/', '', $_POST['consumidor_cpf'])	, 0, 14);
	$_POST['consumidor_cidade']			= substr($_POST['consumidor_cidade']		, 0, 70);
	$_POST['consumidor_complemento']	= substr($_POST['consumidor_complemento']	, 0, 20);
	$_POST['consumidor_email']			= substr($_POST['consumidor_email']			, 0, 50);
	$_POST['consumidor_estado']			= substr($_POST['consumidor_estado']		, 0, 2);
	$_POST['consumidor_fone']			= substr($_POST['consumidor_fone']			, 0, 20);
	$_POST['consumidor_fone_comercial']	= substr($_POST['consumidor_fone_comercial'], 0, 20);
	$_POST['consumidor_fone_recado']	= substr($_POST['consumidor_fone_recado']	, 0, 20);
	$_POST['consumidor_nome']			= substr($_POST['consumidor_nome']			, 0, 50);
	$_POST['consumidor_nome_assinatura']= substr($_POST['consumidor_nome_assinatura'],0, 50);
	$_POST['consumidor_numero']			= substr($_POST['consumidor_numero']		, 0, 20);
	$_POST['consumidor_revenda']		= substr($_POST['consumidor_revenda']		, 0, 1);
	$_POST['revenda_bairro']      		= substr($_POST['revenda_bairro']     		, 0, 80);
	$_POST['revenda_cep']         		= substr($_POST['revenda_cep']        		, 0, 8);
	$_POST['revenda_cnpj']        		= substr(preg_replace('/\D/', '', $_POST['revenda_cnpj']) , 0, 14);
	$_POST['revenda_complemento'] 		= substr($_POST['revenda_complemento']		, 0, 30);
	$_POST['revenda_email']       		= substr($_POST['revenda_email']      		, 0, 50);
	$_POST['revenda_endereco']    		= substr($_POST['revenda_endereco']   		, 0, 60);
	$_POST['revenda_fone']        		= substr($_POST['revenda_fone']       		, 0, 20);
	$_POST['revenda_nome']        		= substr($_POST['revenda_nome']       		, 0, 50);
	$_POST['revenda_numero']      		= substr($_POST['revenda_numero']     		, 0, 20);
	$_POST['natureza_servico']			= substr($_POST['natureza_servico']			, 0, 20);
	$_POST['nota_fiscal']				= substr($_POST['nota_fiscal']				, 0, 20);
	$_POST['nota_fiscal_saida']			= substr($_POST['nota_fiscal_saida']		, 0, 20);
	$_POST['prateleira_box']			= substr($_POST['prateleira_box']			, 0, 10);
	$_POST['produto_voltagem']			= substr($_POST['produto_voltagem']			, 0, 20);
	$_POST['produto_serie']				= substr($_POST['produto_serie']			, 0, 20);
	$_POST['tipo_os_cortesia']			= substr($_POST['tipo_os_cortesia']			, 0, 20);
	$_POST['type']						= substr($_POST['type']						, 0, 10);
	$_POST['veiculo']					= substr($_POST['veiculo']					, 0, 20);
	$_POST['versao']					= substr($_POST['versao']					, 0, 20);
	$_POST['produto_voltagem']			= substr($_POST['produto_voltagem']			, 0, 20);
	$_POST['produto_serie']				= substr($_POST['produto_serie']			, 0, 20);
	$_POST['codigo_posto']				= substr($_POST['codigo_posto']				, 0, 20);

// if($btn_acao and $login_posto = 6359) die(nl2br(print_r($_POST, true)));
	$sua_os_offline = pg_quote(check_post_field('sua_os_offline'));

	$sua_os = check_post_field('sua_os');
	if ($sua_os == 'null') {
		if ($pedir_sua_os == 't') {
			$msg_erro .= " Digite o número da OS Fabricante.";
		}
	} else {
		//WELLINGTON 04/01/2007
		 if ($login_fabrica <> 1 and $login_fabrica <> 11 and $login_fabrica <> 3) {
		//if ($login_fabrica <> 1) {
			if ($login_fabrica <> 3 and $login_fabrica <> 5 and strlen($sua_os) < 7) {
				$sua_os = str_pad($sua_os, 7, "0", STR_PAD_LEFT);
			}

			# inserido pelo Ricardo - 04/07/2006
			if ($login_fabrica == 5) {
				if (is_numeric($sua_os)) {
					// retira os ZEROS a esquerda
					$sua_os = intval(trim($sua_os));
			  	}
			}

#			if (strlen($sua_os) > 6) {
#				$sua_os = substr ($sua_os, strlen ($sua_os) - 6 , 6) ;
#			}
#  CUIDADO para OS de Revenda que já vem com = "-" e a sequencia.
#  fazer rotina para contar 6 caracteres antes do "-"
		}
		$sua_os = pg_quote($sua_os);
	}

	##### INÍCIO DA VALIDAÇÃO DOS CAMPOS #####

	$x_locacao = (strlen($locacao = trim($_POST["locacao"])) > 0) ? "7" : "null";

	$tipo_atendimento = check_post_field('tipo_atendimento');
	//HD 15511
	if($login_fabrica==20 and $tipo_atendimento == 'null') {
		if($sistema_lingua == "ES") $msg_erro="Por favor, informar el tipo de atención.";
		else $msg_erro="Por favor, informar o tipo de atendimento.";
	}

	$segmento_atuacao = check_post_field('segmento_atuacao');

	/*HD: 87459*/
	/* RETIRADO: AND ($login_pais=='BR' or $login_pais=='CO')*/
	if(($tipo_atendimento=='15' or $tipo_atendimento=='16')){
		if (strlen($promotor_treinamento) > 0) {
			if($login_pais=='BR') $x_promotor_treinamento = "$promotor_treinamento";
			else $x_promotor_treinamento  = "null";
		}else{
			if($login_pais=='BR') {
				$msg_erro = "Selecione o promotor que autorizou a cortesia";
			}else{
				if($tipo_atendimento=='16') {
					$msg_erro = "Elija al responsable que autorizó a cortesía";
				}
			}
		}
	}else{
		if (strlen($promotor_treinamento) > 0) {
			$x_promotor_treinamento = "$promotor_treinamento";
		}elseif (strlen($promotor_treinamento2) > 0) {
			// HD 32908
			$x_promotor_treinamento = "$promotor_treinamento2";
		}else{
			$x_promotor_treinamento = "null";
		}
		/* HD 41291 - Se tipo de atendimento for Troca de Produto (13) */
		if(($tipo_atendimento=='13' OR $tipo_atendimento=='66') AND $sistema_lingua <>'ES'){
			if ($x_promotor_treinamento == 'null') {
				$msg_erro = "Selecione o promotor que autorizou a cortesia";
			}
		}
	}

	$produto_referencia = check_post_field('produto_referencia',false);
	$produto_referencia = strtoupper(trim($_POST['produto_referencia']));

	//BOSCH - REGRAS DE VALICAÇÃO
	//regra: caso ele escolho um dois tipos de atendimento abaixo o produto vai ser  sempre os designados
	if($login_fabrica ==20 and $produto_referencia !== false) {
		if($tipo_atendimento==11){    //garantia de peças
			$produto_referencia='0000002';
			$xproduto_serie = '999';
		}
		if($tipo_atendimento==12){    //garantia de acessórios
			$produto_referencia='0000001';

			$xproduto_serie = '999';
		}
	}
	//BOSCH - REGRAS DE VALICAÇÃO

	if ($produto_referencia === false) {
		$produto_referencia = 'null';
		$msg_erro .= " Digite o produto.";
	} else {
		$produto_referencia = pg_quote(strtoupper(preg_replace("/\W/","",$produto_referencia)));
	}

	if($login_fabrica==20) $xdata_abertura = fnc_formata_data_hora_pg(trim($_POST['data_abertura']));
	else                   $xdata_abertura = fnc_formata_data_pg(trim($_POST['data_abertura']));

	if (substr($xdata_abertura,1,4) < 2000){
	if($sistema_lingua == "ES")  $msg_erro .= " Fecha de abertura inválida";
	else $msg_erro .= " Data de abertura inválida.";
	}

	if ($xdata_abertura == 'null') {
		$msg_erro .= ($sistema_lingua == "ES") ? " Escriba la fecha de abertura de la OS" : " Digite a data de abertura da OS.";
	}
	$cdata_abertura = str_replace("'","",$xdata_abertura);

#   Dados do consumidor
	$xconsumidor_nome	= pg_quote(check_post_field('consumidor_nome',	'null'));
	if ($login_fabrica == 15 and $xconsumidor_nome == 'null') {
		$msg_erro = "Digite o nome do consumidor.";
	}

//  11/12/2009 - MLG - HD 175044 - Adiciona validação de CPF se for digitado. Se não for digitado, não confere.
    $cpf_valido = false;
	if (strlen(trim($consumidor_cpf)) != 0) {
	    if (!is_bool(checaCPF($consumidor_cpf, false))) {
			$consumidor_cpf = checaCPF($consumidor_cpf);
			$cpf_valido = true;
		} else {
			$msg_erro .= "CPF/CNPJ do cliente inválido<br>";
		}
	}
	$xconsumidor_cpf	= ($cpf_valido) ? "'$consumidor_cpf'" : 'null';
	$xconsumidor_cidade	= pg_quote(check_post_field('consumidor_cidade'));
	$xconsumidor_estado	= pg_quote(check_post_field('consumidor_estado'));
	$xconsumidor_fone	= pg_quote(check_post_field('consumidor_fone'));
	$xconsumidor_nome	= pg_quote(check_post_field('consumidor_nome'));
	$xconsumidor_nome	= pg_quote(check_post_field('consumidor_nome'));

	if ($login_fabrica == 20 and $sistema_lingua <> "ES"){ #HD 157034
		$sql = "SELECT	tbl_cliente.cliente
				FROM tbl_cliente
				LEFT JOIN tbl_cidade
				USING (cidade)
				WHERE tbl_cliente.cpf = $xconsumidor_cpf";
		$res = pg_query ($con,$sql);

		if (pg_num_rows ($res) == 0){

			$sql = "SELECT fnc_qual_cidade (contato_cidade,contato_estado) FROM tbl_posto_fabrica WHERE posto = $login_posto and fabrica= $login_fabrica";
			$res = pg_query ($con,$sql);
			$xconsumidor_cidade2 = pg_fetch_result($res,0,0);

			$sql = "INSERT INTO tbl_cliente
						(nome,cpf,fone,cidade)
					VALUES
						($xconsumidor_nome, $xconsumidor_cpf, $xconsumidor_fone, $xconsumidor_cidade2) ";
			$res = pg_query ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
	}

#   Dados da revenda
	$xrevenda_nome	= pg_quote(check_post_field('revenda_nome'));
	$revenda_cnpj	= checaCPF(check_post_field('revenda_cnpj','null'),false);
    if (!is_bool($revenda_cnpj)) {
		$xrevenda_cnpj = "'$revenda_cnpj'";
		$cnpj_valido = true;
	} else {
		$xrevenda_cnpj = 'null';
	    $cnpj_valido = false;
		if ($_POST['revenda_cnpj'] != '') $msg_erro .= "CPF/CNPJ da revenda inválido<br>";  // Só se digitou
	}
// if ($login_posto==6359) echo "<pre>$login_pais - $revenda_cnpj.</pre>";
	$xrevenda_fone	= pg_quote(check_post_field('revenda_fone'));
	$xnota_fiscal	= pg_quote(check_post_field('nota_fiscal'));
	if ($login_fabrica == 8) {
		if ($xnota_fiscal == 'null' ) { // hD 64397
			$msg_erro .= "Digite o número da nota fiscal.";
		}
	}

	$qtde_produtos	= check_post_field('qtde_produtos',"1");
	$xtroca_faturada= check_post_field('troca_faturada');

//pedido por Leandro Tectoy, feito por takashi 02/08 Alterado por Raphael para a bosch tb
	if(($login_fabrica==20 AND $tipo_atendimento <> 66) or $login_fabrica == 8){ // hD 64397
		if (strlen ($_POST['data_nf']) == 0) {
			if($sistema_lingua == "ES") $msg_erro = " Informe la fecha de compra";
			else                        $msg_erro = " Digite a data de compra.";
		}
	}
//pedido por Leandro tectoy, feito por takashi 04/08
	$xdata_nf = fnc_formata_data_pg(trim($_POST['data_nf']));
	if ($xdata_nf == null AND $xtroca_faturada <> 't') $msg_erro .= " Digite a data de compra.";

#   Dados do produto
	$xproduto_serie					= pg_quote(check_post_field('produto_serie'));
	$xcodigo_fabricacao				= pg_quote(check_post_field('codigo_fabricacao'));
	$xaparencia_produto				= pg_quote(check_post_field('aparencia_produto'));
	$xacessorios					= pg_quote(check_post_field('acessorios'));
	$xdefeito_reclamado_descricao	= pg_quote(check_post_field('defeito_reclamado_descricao'));
	$xdefeito_reclamado				= pg_quote(check_post_field('defeito_reclamado'));
	$xobs							= pg_quote(check_post_field('obs'));
	$xquem_abriu_chamado			= pg_quote(check_post_field('quem_abriu_chamado'));

	if (check_post_field('consumidor_revenda',false) === false){
		 if($sistema_lingua == "ES") $msg_erro .= "Elija consumidor o distribuidor";
			else $msg_erro .= " Selecione consumidor ou revenda.";
	}else{
	      $xconsumidor_revenda = pg_quote(check_post_field('consumidor_revenda'));
	}

	//if (strlen($_POST['type']) == 0) $xtype = 'null';
	//else             $xtype = "'".$_POST['type']."'";

	$xsatisfacao    = pg_quote(check_post_field('satisfacao',false));
	$xlaudo_tecnico = pg_quote(check_post_field('laudo_tecnico'));

//BOSCH - REGRAS DE VALICAÇÃO - RAPHAEL GIOVANINI
	if($login_fabrica==20) {
		if ($tipo_atendimento==14) {
			$aux_data      = explode('/',$data_nf);
			// HD 214509 foi solicitado alteração de 100 dias para 360 dias (ESTE VALOR É FIXO) EDUARDO 25-03-2010
			// HD 242952 foi solicitado para desfazer a alteração do HD 214509
			$xsoma = 100;
			$xdia = $aux_data[0];
			$xmes = $aux_data[1];
			$xano = $aux_data[2];

			$bosch_nf_data = date("Ymd", mktime(0,0,0,$xmes,$xdia+$xsoma,$xano));
			$bosch_data_abertura=explode('/',$data_abertura );
			$bosch_data_abertura=$bosch_data_abertura[2].$bosch_data_abertura[1].$bosch_data_abertura[0];

			if($bosch_data_abertura >$bosch_nf_data)
				if($sistema_lingua == "ES") $msg_erro =  "Plazo de garantía de reparación caducado";
				else $msg_erro =  "Prazo de Garantia de Conserto expirado" ;
		}
		if ($tipo_atendimento==15) {
			$sql = "SELECT garantia FROM tbl_produto WHERE tbl_produto.referencia = $produto_referencia";
			$res = @pg_query ($con,$sql);
			if (@pg_num_rows($res) == 0) {
				if($sistema_lingua == "ES") $msg_erro =  "Producto $produto_referencia sin garantía";
				else $msg_erro = " Produto $produto_referencia sem garantia";
			}

			if (strlen($msg_erro) == 0) {
				$garantia = trim(@pg_fetch_result($res,0,garantia));
				$sql = "SELECT ($xdata_nf::date + (($garantia || ' months')::interval))::date;";
				$res = @pg_query ($con,$sql);
				$data_final_garantia = trim(pg_fetch_result($res,0,0));

//echo "$data_final_garantia > $cdata_abertura";
				if($data_final_garantia >$cdata_abertura){
					if($sistema_lingua == "ES") $msg_erro =  "El producto aún está en garantía y no puede ser GENTILEZA";
					else $msg_erro="O produto ainda está na garantia, e não pode ser do tipo CORTESIA";
				}
			}
		}
	}
//BOSCH - REGRAS DE VALICAÇÃO

##### FIM DA VALIDAÇÃO DOS CAMPOS #####

	##### Verificação se o nº de série é reincidente para a Tectoy #####
	$os_reincidente = "'f'";
	if ($login_fabrica == 6) {
	    $data_inicial = date('Y-m-d',strtotime('-30 day'));
		$data_final = date('Y-m-d',strtotime('+1 day'));

		if (strlen($produto_serie) > 0) {
			$sql = "SELECT  tbl_os.os            ,
							tbl_os.sua_os        ,
							tbl_os.data_digitacao,
							tbl_os_extra.extrato
					FROM    tbl_os
					JOIN    tbl_os_extra ON tbl_os_extra.os = tbl_os.os
					WHERE   UPPER(tbl_os.serie)   = UPPER('$produto_serie')
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os.posto   = $posto
					AND     tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'
					ORDER BY tbl_os.data_digitacao DESC
					LIMIT 1";
			$res = @pg_query($con,$sql);

			if (pg_num_rows($res) > 0) {
				$xxxos      = trim(pg_fetch_result($res,0,os));
				$xxxsua_os  = trim(pg_fetch_result($res,0,sua_os));
				$xxxextrato = trim(pg_fetch_result($res,0,extrato));

				if (strlen($xxxextrato) == 0) {
					$msg_erro .= "Nº de Série $produto_serie digitado é reincidente.<br>
					Favor reabrir a ordem de serviço $xxxsua_os e acrescentar itens.";
				}else{
					$os_reincidente = "'t'";
				}
			}
		}
	}

	##### Verificação se o nº de série é reincidente para a Britânia #####
/*	if ($login_fabrica == 3) {
		$sqlX = "SELECT to_char (current_date - INTERVAL '30 days', 'YYYY-MM-DD')";
		$resX = @pg_query($con,$sqlX);
		$data_inicial = pg_fetch_result($resX,0,0);

		$sqlX = "SELECT to_char (current_date, 'YYYY-MM-DD')";
		$resX = @pg_query($con,$sqlX);
		$data_final = pg_fetch_result($resX,0,0);

		if (strlen($produto_serie) > 0) {
			$sql = "SELECT  tbl_os.os            ,
							tbl_os.sua_os        ,
							tbl_os.data_digitacao
					FROM    tbl_os
					JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
					WHERE   tbl_os.serie   = '$produto_serie'
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_produto.numero_serie_obrigatorio IS TRUE
					AND     tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'
					ORDER BY tbl_os.data_digitacao DESC
					LIMIT 1";
			$res = @pg_query($con,$sql);

			if (pg_num_rows($res) > 0) {
				$msg_erro .= "Nº de Série $produto_serie digitado é reincidente. Favor verificar.<br>Em caso de dúvida, entre em contato com a Fábrica.";
			}
		}
	}
 VER PARA LIBERAR */

/*	if ($login_fabrica == 3) {
		if (strlen($produto_serie) > 0) {
			$sql = "SELECT  tbl_os.os            ,
							tbl_os.sua_os        ,
							tbl_os.data_digitacao
					FROM    tbl_os
					JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
					WHERE   tbl_os.serie   = '$produto_serie'
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_produto.numero_serie_obrigatorio IS TRUE
					ORDER BY tbl_os.data_digitacao DESC
					LIMIT 1";
			$res = @pg_query($con,$sql);

			if (pg_num_rows($res) > 0) {
				$os_reincidente = "'t'";
			}
		}
	}
VER PARA LIBERAR */

/*TAKASHI 18-12 HD-854
	if ($login_fabrica == 3 and $login_posto==6359) {
		$sqlX = "SELECT to_char ($xdata_abertura::date - INTERVAL '90 days', 'YYYY-MM-DD')";
		$resX = @pg_query($con,$sqlX);
		$data_inicial = pg_fetch_result($resX,0,0);
//echo $sqlX;
		$sqlX = "SELECT to_char (current_date, 'YYYY-MM-DD')";
		$resX = @pg_query($con,$sqlX);
		$data_final = pg_fetch_result($resX,0,0);

		if (strlen($produto_serie) > 0) {
			$sql = "SELECT  tbl_os.os            ,
							tbl_os.sua_os        ,
							tbl_os.data_digitacao,
							tbl_os.finalizada,
							tbl_os.data_fechamento
					FROM    tbl_os
					JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
					WHERE   tbl_os.serie   = '$produto_serie'
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_produto.numero_serie_obrigatorio IS TRUE
					AND     tbl_produto.linha=3
					ORDER BY tbl_os.data_abertura DESC
					LIMIT 1";
			$res = @pg_query($con,$sql);
//if ($ip=="201.42.46.223"){ echo "$sql"; }
					//AND     tbl_os.data_fechamento::date BETWEEN '$data_inicial' AND '$data_final'
//linha 3, pois é a linha audio e video
			if (pg_num_rows($res) > 0) {
				$xxxos      = trim(pg_fetch_result($res,0,os));
				$xxfinalizada   = trim(pg_fetch_result($res,0,finalizada));
				$xx_sua_os   = trim(pg_fetch_result($res,0,sua_os));
				$xxdata_fechamento =   trim(pg_fetch_result($res,0,data_fechamento));

				if(strlen($xxfinalizada)==0){ //aberta
					$os_reincidente = "'t'";
					$msg_erro .= "OS $xx_sua_os com este número de série ainda está aberta, por favor consulta-la.";
				}else{//fechada
					if($data_inicio<$xxdata_fechamento and $data_final>=$xxdata_fechamento){
					//if(($xxdata_fechamento > $data_inicial) and ($xxdata_fechamento < $data_final)){
						$os_reincidente = "'t'";
					}
				}
			}
		}
	}
TAKASHI 18-12 HD-854*/

#	if (strlen ($consumidor_cpf) <> 0 and strlen ($consumidor_cpf) <> 11 and strlen ($consumidor_cpf) <> 14) $msg_erro .= 'Tamanho do CPF/CNPJ do cliente inválido';

#	if ($login_fabrica == 1 AND strlen($consumidor_cpf) == 0) $msg_erro .= 'Tamanho do CPF/CNPJ do cliente inválido';

	$produto = 0;

	$voltagem = pg_quote(check_post_field('produto_voltagem',"null"));

	$sql = "SELECT tbl_produto.produto, tbl_produto.linha
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  UPPER(trim(tbl_produto.referencia_pesquisa)) = UPPER($produto_referencia)
			AND    tbl_linha.fabrica      = $login_fabrica
			AND    tbl_produto.ativo IS TRUE";

	$res = @pg_query ($con,$sql);

	if (@pg_num_rows ($res) == 0) {
	if($sistema_lingua == "ES") $msg_erro =  "Producto ($produto_referencia) no está dado de alta";
	else		$msg_erro = " Produto $produto_referencia não cadastrado";
	}else{
		$produto = @pg_fetch_result ($res,0,produto);
		$linha   = @pg_fetch_result ($res,0,linha);
	}

	if ($xtroca_faturada <> "'t'") { // verifica troca faturada para a Black
		if (1 == 2) {
			if (strlen($msg_erro) == 0) {

				$sql = "SELECT garantia FROM tbl_produto WHERE tbl_produto.produto = $produto";
				$res = @pg_query ($con,$sql);

				if (@pg_num_rows($res) == 0) {
					if($sistema_lingua == "ES") $msg_erro =  "Producto sin garantía";
					else						$msg_erro = " Produto $produto_referencia sem garantia";
				}

				if (strlen($msg_erro) == 0) {
					$garantia = trim(@pg_fetch_result($res,0,garantia));

					$sql = "SELECT ($xdata_nf::date + (($garantia || ' months')::interval))::date;";
					$res = @pg_query ($con,$sql);
					if (strlen (pg_errormessage($con)) > 0) {
						$msg_erro = pg_errormessage($con);
					}

					if (strlen($msg_erro) > 0) $msg_erro =  "Data da NF inválida.";

					if (strlen($msg_erro) == 0) {
						if (pg_num_rows ($res) > 0) {
							$data_final_garantia = trim(pg_fetch_result($res,0,0));
						}

						if ($data_final_garantia < $cdata_abertura) {
							if($login_fabrica==20 AND ($tipo_atendimento==15 OR $tipo_atendimento==16)){
							}else{
							//$msg_erro = " Produto $produto_referencia fora da garantia, vencida em ". substr($data_final_garantia,8,2) ."/". substr($data_final_garantia,5,2) ."/". substr($data_final_garantia,0,4);
							}
						}
					}
				}
			}
		}

	}

	$xtipo_os_cortesia = 'null';

	#----------- OS digitada pelo Distribuidor -----------------
	$digitacao_distribuidor = "null";
	if ($distribuidor_digita == 't'){
		$codigo_posto = strtoupper(preg_replace("/\W/","",trim ($_POST['codigo_posto'])));

		if (strlen ($codigo_posto) > 0) {
			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto' AND credenciamento = 'CREDENCIADO'";
			$res = @pg_query($con,$sql);
			if (pg_num_rows ($res) <> 1) {
				$msg_erro = "Posto $codigo_posto não cadastrado";
				$posto = $login_posto;
			}else{
				$posto = pg_fetch_result ($res,0,0);
				if ($posto <> $login_poso) {
					$sql = "SELECT posto FROM tbl_posto_linha WHERE posto = $posto AND distribuidor = $login_posto AND linha = $linha";
					$res = @pg_query($con,$sql);
					if (pg_num_rows ($res) <> 1) {
						$msg_erro = "Posto $codigo_posto não pertence a sua região";
						$posto = $login_posto;
					}else{
						$posto = pg_fetch_result ($res,0,0);
						$digitacao_distribuidor = $login_posto;
					}
				}
			}
		}
	}
	#------------------------------------------------------

//CARTÃO CLUBE - LATINATEC
	$cartao_clube = trim($_POST['cartao_clube']);
	$cc = 0;
	if($login_fabrica == 15 AND strlen($cartao_clube) > 0 AND strlen($msg_erro) == 0){
		$sql_5 = "SELECT cartao_clube      ,
						dt_nota_fiscal   ,
						dt_garantia
					FROM tbl_cartao_clube
					WHERE cartao_clube = '$cartao_clube'
					AND produto = '$produto' ; ";
		$res_5 = pg_query($con,$sql_5);
		if(pg_num_rows($res_5) > 0){
			$cc = "OK";
		}else{
			$msg_erro = "Verifique o produto do Cartão Clube com o da OS.";
		}
	}

	//HD 187792
	if($login_fabrica==20){
		if(strlen($xdata_abertura)>0){
			$xdata_hora_abertura = $xdata_abertura;

			$xdata_abertura = explode(" ",$xdata_abertura);
			$xdata_abertura = $xdata_abertura[0];
			$xdata_abertura = str_replace("'","",$xdata_abertura);
			$xdata_abertura = "'" . $xdata_abertura . "'";
		}else{
			$xdata_hora_abertura = "null";
		}
	}else{
		$xdata_hora_abertura = "null";
	}

	$res = @pg_query($con,"BEGIN TRANSACTION");

	$os_offline = $_POST['os_offline'];
	if (strlen ($os_offline) == 0) $os_offline = "null";

	if (strlen($msg_erro) == 0){

/*if($login_fabrica ==20 and $login_posto==6359 and $ip=="200.228.76.102"){
	echo "post: ";
	print_r($_POST);
	exit;
}*/
		/*================ INSERE NOVA OS =========================*/

		if (strlen($os) == 0) {
			$sql =	"INSERT INTO tbl_os (
						tipo_atendimento                                               ,
						segmento_atuacao                                               ,
						posto                                                          ,
						fabrica                                                        ,
						sua_os                                                         ,
						sua_os_offline                                                 ,
						data_abertura                                                  ,
						cliente                                                        ,
						revenda                                                        ,
						consumidor_nome                                                ,
						consumidor_cpf                                                 ,
						consumidor_fone                                                ,
						consumidor_cidade	                                           ,
						consumidor_estado                                              ,
						revenda_cnpj                                                   ,
						revenda_nome                                                   ,
						revenda_fone                                                   ,
						nota_fiscal                                                    ,
						data_nf                                                        ,
						produto                                                        ,
						serie                                                          ,
						qtde_produtos                                                  ,
						codigo_fabricacao                                              ,
						aparencia_produto                                              ,
						acessorios                                                     ,
						defeito_reclamado_descricao                                    ,
						defeito_reclamado                                              ,
						obs                                                            ,
						quem_abriu_chamado                                             ,
						consumidor_revenda                                             ,
						satisfacao                                                     ,
						laudo_tecnico                                                  ,
						tipo_os_cortesia                                               ,
						troca_faturada                                                 ,
						os_offline                                                     ,
						os_reincidente                                                 ,
						digitacao_distribuidor                                         ,
						tipo_os                                                        ,
						promotor_treinamento                                           ,
						data_hora_abertura
					) VALUES (
						$tipo_atendimento                                              ,
						$segmento_atuacao                                              ,
						$posto                                                         ,
						$login_fabrica                                                 ,
						$sua_os                                                        ,
						$sua_os_offline                                                ,
						$xdata_abertura                                                ,
						null                                                           ,
						(SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj limit 1)  ,
						$xconsumidor_nome                                              ,
						$xconsumidor_cpf                                               ,
						$xconsumidor_fone                                              ,
						$xconsumidor_cidade                                            ,
						$xconsumidor_estado                                            ,
						$xrevenda_cnpj                                                 ,
						$xrevenda_nome                                                 ,
						$xrevenda_fone                                                 ,
						$xnota_fiscal                                                  ,
						$xdata_nf                                                      ,
						$produto                                                       ,
						$xproduto_serie                                                ,
						$qtde_produtos                                                 ,
						$xcodigo_fabricacao                                            ,
						$xaparencia_produto                                            ,
						$xacessorios                                                   ,
						$xdefeito_reclamado_descricao                                  ,
						$xdefeito_reclamado                                            ,
						$xobs                                                          ,
						$xquem_abriu_chamado                                           ,
						$xconsumidor_revenda                                           ,
						$xsatisfacao                                                   ,
						$xlaudo_tecnico                                                ,
						$xtipo_os_cortesia                                             ,
						$xtroca_faturada                                               ,
						$os_offline                                                    ,
						$os_reincidente                                                ,
						$digitacao_distribuidor                                        ,
						$x_locacao                                                     ,
						$x_promotor_treinamento                                        ,
						$xdata_hora_abertura
					);";
		}else{
			$sql =	"UPDATE tbl_os SET
						tipo_atendimento            = $tipo_atendimento                 ,
						segmento_atuacao            = $segmento_atuacao                 ,
						data_abertura               = $xdata_abertura                   ,
						revenda                     = (SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj)  ,
						consumidor_nome             = $xconsumidor_nome                 ,
						consumidor_cpf              = $xconsumidor_cpf                  ,
						consumidor_fone             = $xconsumidor_fone                 ,
						revenda_cnpj                = $xrevenda_cnpj                    ,
						revenda_nome                = $xrevenda_nome                    ,
						revenda_fone                = $xrevenda_fone                    ,
						nota_fiscal                 = $xnota_fiscal                     ,
						data_nf                     = $xdata_nf                         ,
						serie                       = $xproduto_serie                   ,
						qtde_produtos               = $qtde_produtos                    ,
						codigo_fabricacao           = $xcodigo_fabricacao               ,
						aparencia_produto           = $xaparencia_produto               ,
						defeito_reclamado_descricao = $xdefeito_reclamado_descricao     ,
						defeito_reclamado           = $xdefeito_reclamado               ,
						consumidor_revenda          = $xconsumidor_revenda              ,
						satisfacao                  = $xsatisfacao                      ,
						laudo_tecnico               = $xlaudo_tecnico                   ,
						troca_faturada              = $xtroca_faturada                  ,
						tipo_os_cortesia            = $xtipo_os_cortesia                ,
						tipo_os                     = $x_locacao                        ,
						promotor_treinamento        = $x_promotor_treinamento           ,
						data_hora_abertura          = $xdata_hora_abertura
					WHERE os      = $os
					AND   fabrica = $login_fabrica
					AND   posto   = $posto;";
		}
		#if ($login_posto==6359) echo "<pre>$sql.</pre>";
		$sql_OS = $sql;
		$res = pg_query ($con,$sql);
		if (strlen (pg_errormessage($con)) > 0 ) {
			$msg_erro = pg_errormessage($con);
			$msg_erro = substr($msg_erro,6);
		}

		if (strlen ($msg_erro) == 0) {
			if (strlen($os) == 0) {
				$res = @pg_query ($con,"SELECT CURRVAL ('seq_os')");
				$os  = pg_fetch_result ($res,0,0);
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		if (strlen($os) == 0) {
			$res = @pg_query ($con,"SELECT CURRVAL ('seq_os')");
			$os  = pg_fetch_result ($res,0,0);
		}

//CARTAO CLUBE - LATINATEC
		if($login_fabrica == 15 AND $cc == "OK"){
			$sql_cc = "UPDATE tbl_cartao_clube SET os = $os WHERE cartao_clube = '$cartao_clube' ";
			$res = pg_query($con,$sql_cc);
		}

		$res      = @pg_query ($con,"SELECT fn_valida_os($os, $login_fabrica)");
		if (strlen (pg_errormessage($con)) > 0 ) {
			$msg_erro = pg_errormessage($con);
if ($ip == '201.43.247.108') echo $msg_erro;
		}
		#--------- grava OS_EXTRA ------------------
		if (strlen ($msg_erro) == 0) {
			$taxa_visita				= str_replace (",",".",trim ($_POST['taxa_visita']));
			$visita_por_km				= trim ($_POST['visita_por_km']);
			$hora_tecnica				= str_replace (",",".",trim ($_POST['hora_tecnica']));
			$regulagem_peso_padrao		= str_replace (",",".",trim ($_POST['regulagem_peso_padrao']));
			$certificado_conformidade	= str_replace (",",".",trim ($_POST['certificado_conformidade']));
			$valor_diaria				= str_replace (",",".",trim ($_POST['valor_diaria']));

			if (strlen ($taxa_visita)				== 0) $taxa_visita					= '0';
			if (strlen ($visita_por_km)				== 0) $visita_por_km				= 'f';
			if (strlen ($hora_tecnica)				== 0) $hora_tecnica					= '0';
			if (strlen ($regulagem_peso_padrao)		== 0) $regulagem_peso_padrao		= '0';
			if (strlen ($certificado_conformidade)	== 0) $certificado_conformidade		= '0';
			if (strlen ($valor_diaria)				== 0) $valor_diaria					= '0';

			$sql = "UPDATE tbl_os_extra SET
						taxa_visita              = $taxa_visita             ,
						visita_por_km            = '$visita_por_km'         ,
						hora_tecnica             = $hora_tecnica            ,
						regulagem_peso_padrao    = $regulagem_peso_padrao   ,
						certificado_conformidade = $certificado_conformidade,
						valor_diaria             = $valor_diaria ";

			if ($os_reincidente == "'t'") $sql .= ", os_reincidente = $xxxos ";

			$sql .= "WHERE tbl_os_extra.os = $os";
#if ( $ip == '201.0.9.216' OR $login_posto == 14068) echo nl2br($sql)."<br><br>";
#if ( $ip == '201.0.9.216' OR $login_posto == 14068) flush();
			$res = @pg_query ($con,$sql);
			if (strlen (pg_errormessage($con)) > 0 ) {
				$msg_erro = pg_errormessage($con);
			}

			if (strlen($x_promotor_treinamento)>0 AND $x_promotor_treinamento <> "null"){
				$sql = "SELECT status_os
						FROM tbl_os_status
						WHERE os = $os
						AND status_os IN (92,93,94)
						ORDER BY data DESC
						LIMIT 1";
				$res = @pg_query ($con,$sql);
				if(pg_num_rows($res) > 0){
					$status_os  = pg_fetch_result ($res,0,status_os);
				}
				if(pg_num_rows($res) == 0 OR $status_os <> "92"){
					if(($tipo_atendimento <> 66 and $tipo_atendimento<>13) OR $login_posto<>6359){
						if($login_pais == 'BR') {
							$sql = "INSERT INTO tbl_os_status (os,status_os,observacao) VALUES ($os,92,'OS Aguardando aprovação do promotor.')";
						}else{
							$sql = "INSERT INTO tbl_os_status (os,status_os,observacao) VALUES ($os,92,'OS esperando aprobación del promotor.')";
						}

						$res = @pg_query ($con,$sql);
					}else{
						if($login_pais == 'BR') {
							$sql = "INSERT INTO tbl_os_status (os,status_os,observacao) VALUES ($os,92,'OS Aguardando aprovação do promotor.')";
						}else{
							$sql = "INSERT INTO tbl_os_status (os,status_os,observacao) VALUES ($os,92,'OS esperando aprobación del promotor.')";
						}
						$res = @pg_query ($con,$sql);
					}
				}
			}

			if (strlen ($msg_erro) == 0) {
				$res = @pg_query ($con,"COMMIT TRANSACTION");

//BOSCH - ENVIAR EMAIL
				if($login_fabrica == 20){
					if($login_pais == 'BR') {

						$sql = "SELECT  tbl_posto.nome,
								tbl_posto_fabrica.codigo_posto,
								tbl_posto.email,
								tbl_os.consumidor_nome,
								tbl_produto.referencia,
								tbl_produto.descricao
							FROM  tbl_os
							JOIN  tbl_posto         USING(posto)
							JOIN  tbl_produto       USING(produto)
							JOIN  tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
							WHERE os = $os and tipo_atendimento IN (15,16)";
						$res = pg_query($con,$sql);
						if (pg_num_rows($res) > 0) {
							$posto_nome      = trim(pg_fetch_result($res,0,nome));
							$codigo_posto    = trim(pg_fetch_result($res,0,codigo_posto));
							$consumidor_nome = trim(pg_fetch_result($res,0,consumidor_nome));
							$produto_ref     = trim(pg_fetch_result($res,0,referencia));
							$produto_nome    = trim(pg_fetch_result($res,0,descricao));
							$email           = trim(pg_fetch_result($res,0,email));
							//ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO

							$email_origem  = "helpdesk@telecontrol.com.br";
							$email_destino = "pt.garantia@br.bosch.com";
							$assunto       = "Nova OS de Cortesia";

							$corpo.="<br>Foi inserido uma nova OS n°$os no sistema TELECONTROL ASSIST.\n\n";
							$corpo.="<br>Codigo do Posto: $codigo_posto<br>Posto: $posto_nome <br>Email: $email\n\n";
							$corpo.="<br><br>Telecontrol\n";
							$corpo.="<br>www.telecontrol.com.br\n";
							$corpo.="<br>_______________________________________________\n";
							$corpo.="<br>OBS: POR FAVOR NÃO RESPONDA ESTE EMAIL.";

							$body_top = "--Message-Boundary\n";
							$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
							$body_top .= "Content-transfer-encoding: 7BIT\n";
							$body_top .= "Content-description: Mail message body\n\n";

							if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){

								if( strlen($x_promotor_treinamento) > 0  and $x_promotor_treinamento <>'null'){
									$sql = "SELECT email,nome FROM tbl_promotor_treinamento WHERE promotor_treinamento = $x_promotor_treinamento;";
									$res2 = pg_query($con,$sql);
									$promotor_nome  = trim(pg_fetch_result($res2,0,nome));
									$promotor_email = trim(pg_fetch_result($res2,0,email));

									if(strlen($promotor_email) > 0 ){
										$email_origem  = "pt.garantia@br.bosch.com";
										$email_destino = $promotor_email ;
										$assunto       = "Novo OS de Cortesia";

										#Liberado: HD 18323
										if ($tipo_atendimento==13){
												$assunto       = "Solicitação de Troca de Produto";
												$corpo ="<br>Caro promotor $promotor_nome,<br>\n\n";
												$corpo.="<br>O posto autorizado <b>$posto_nome</b>, código $codigo_posto, acaba cadastrar uma troca de produto e necessita de sua autorização.\n\n";
												$corpo.="<br>Troca de produto para o consumidor <b>$consumidor_nome</b> referente a máquina: <b>$produto_ref - $produto_nome</b>\n";
												$corpo.="<br><br>Para aprovar / recusar a OS, acesse o sistema ASSIST , MENU CallCenter / Aprovação de Troca. O número da OS é <b>$os</b>\n";
										}else{
											if ($login_posto=='6359' OR 1==1){

												#if ($x_promotor_treinamento<>96){
												#	$email_destino = "fabio@telecontrol.com.br";
												#}

												$corpo ="<br>Caro promotor $promotor_nome,<br>\n\n";
												$corpo.="<br>O posto autorizado <b>$posto_nome</b>, código $codigo_posto, acaba de cadastrar uma cortesia e necessita de sua autorização.\n\n";
												$corpo.="<br>Cortesia para o consumidor <b>$consumidor_nome</b> referente a máquina: <b>$produto_ref - $produto_nome</ib>\n";
												$corpo.="<br><br>Para aprovar / recusar a OS, acesse o sistema ASSIST , MENU CallCenter / Aprovação das OS de Cortesia. O número da OS é <b>$os</b>\n";
											}else{
												$corpo ="<br>Caro promotor $promotor_nome,<br>\n\n";
												$corpo.="<br>Você acaba de autorizar uma cortesia para o posto autorizado <b>$posto_nome</b>, código do posto: $codigo_posto\n\n";
												$corpo.="<br>Cortesia concedida para o consumidor <b>$consumidor_nome</b> referente a máquina: <b>$produto_ref - $produto_nome</ib>\n";
												$corpo.="<br>Verificar a OS <b>$os</b>\n";
											}
										}
										$body_top = "--Message-Boundary\n";
										$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
										$body_top .= "Content-transfer-encoding: 7BIT\n";
										$body_top .= "Content-description: Mail message body\n\n";

										if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
											$enviou = 'ok';
										}
									}

								}
								header ("Location: os_cadastro_adicional.php?os=$os");
								exit;
							}
						}
					}
					if($login_pais=='CO'){
						$sql = "SELECT  tbl_posto.nome,
							tbl_posto_fabrica.codigo_posto,
							tbl_posto.email,
							tbl_os.consumidor_nome,
							tbl_produto.referencia,
							tbl_produto.descricao
						FROM  tbl_os
						JOIN  tbl_posto         USING(posto)
						JOIN  tbl_produto       USING(produto)
						JOIN  tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						WHERE os = $os AND tipo_atendimento = 16 ";
						$res = pg_query($con,$sql);
						if (pg_num_rows($res) > 0) {
							$posto_nome      = trim(pg_fetch_result($res,0,nome));
							$codigo_posto    = trim(pg_fetch_result($res,0,codigo_posto));
							$consumidor_nome = trim(pg_fetch_result($res,0,consumidor_nome));
							$produto_ref     = trim(pg_fetch_result($res,0,referencia));
							$produto_nome    = trim(pg_fetch_result($res,0,descricao));
							$email           = trim(pg_fetch_result($res,0,email));
							//ENVIA EMAIL PARA POSTO PRA CONFIRMAÇÃO

							$email_origem  = "pt.garantia@br.bosch.com";
							$email_destino = "edwing.diaz@co.bosch.com";
							$assunto       = "Nueva OS de Cortesía";

							$corpo ="<br>Estimado Edwing Diaz,<br>\n\n";
							$corpo.="<br>El servicio autorizado <b>$codigo_posto - $posto_nome</b>, ha catastrado una cortesía comercial y necesita de su autorización.\n\n";
							$corpo.="<br>Cortesía para el cliente <b>$consumidor_nome</b> referenta a la herramienta: <b>$produto_ref - $produto_nome.</b>\n";
							$corpo.="El número de OS es <b>$os</b>\n";

							$body_top = "--Message-Boundary\n";
							$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
							$body_top .= "Content-transfer-encoding: 7BIT\n";
							$body_top .= "Content-description: Mail message body\n\n";

							if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
										$enviou = 'ok';
							}
						}
					}
				}

				// se o produto tiver TROCA OBRIGATORIA, bloqueia a OS para intervencao da fabrica // fabio 17/01/2007
				if ($login_fabrica == 3 AND 1==1){
					$sql = "SELECT  troca_obrigatoria
							FROM    tbl_produto
							WHERE   upper(tbl_produto.referencia) = upper($produto_referencia)";
					$res = @pg_query($con,$sql);
					if (pg_num_rows($res) > 0) {
						$troca_obrigatoria = trim(pg_fetch_result($res,0,troca_obrigatoria));
						if ($troca_obrigatoria == 't') {
							$sql_intervencao = "SELECT * FROM  tbl_os_status WHERE os=$os AND status_os=62";
							$res_intervencao = pg_query($con, $sql_intervencao);
							if (pg_num_rows ($res_intervencao) == 0){
								$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,62,current_timestamp,'O Produto desta O.S. necessita de troca.')";
								$res = @pg_query ($con,$sql);
								$msg_intervencao .= "<br>A produto $produto_referencia precisa de Intervenção da Assistência Técnica da Fábrica. Aguarde o contato da fábrica";
							}
							// envia email teste para avisar
							$email_origem  = "fabio@telecontrol.com.br";
							$email_destino = "fabio@telecontrol.com.br";
							$assunto       = "TROCA OBRIGATORIA - OS cadastrada";
							$corpo.="<br>OS: $os \n";
							$body_top = "--Message-Boundary\n";
							$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
							$body_top .= "Content-transfer-encoding: 7BIT\n";
							$body_top .= "Content-description: Mail message body\n\n";
							@mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem);
							// fim
						}
					}
				}// fim TROCA OBRIGATORIA


				if($login_fabrica == 20 and $reabrir='ok'){
					header ("Location: os_cadastro_adicional.php?os=$os&reabrir=ok");exit;
				}
				header ("Location: os_cadastro_adicional.php?os=$os");
				exit;
			}else{
				$res = @pg_query ($con,"ROLLBACK TRANSACTION");
			}
		}else{
			$res = @pg_query ($con,"ROLLBACK TRANSACTION");
		}

	}else{
		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf\"") > 0)
		$msg_erro = " Data da compra maior que a data da abertura da Ordem de Serviço.";

		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_abertura_futura\"") > 0)
		$msg_erro = " Data da abertura deve ser inferior ou igual a data de digitação da OS no sistema (data de hoje).";

		if (strpos ($msg_erro,"tbl_os_unico") > 0)
			$msg_erro = " O Número da Ordem de Serviço do fabricante já esta cadastrado.";

		$res = @pg_query ($con,"ROLLBACK TRANSACTION");
	}

}

/*================ LE OS DA BASE DE DADOS =========================*/
if (strlen($_GET['os'] ) > 0) $os = $_GET['os'];
if (strlen($_POST['os']) > 0) $os = $_POST['os'];

if (strlen ($os) > 0) {
	$sql =	"SELECT tbl_os.sua_os                                                    ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura      ,
					to_char(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao    ,
					tbl_os.consumidor_nome                                           ,
					tbl_os.consumidor_cpf                                            ,
					tbl_os.consumidor_cidade                                         ,
					tbl_os.consumidor_fone                                           ,
					tbl_os.consumidor_estado                                         ,
					tbl_os.revenda_cnpj                                              ,
					tbl_os.revenda_nome                                              ,
					tbl_os.nota_fiscal                                               ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')       AS data_nf            ,
					tbl_os.consumidor_revenda                                        ,
					tbl_os.aparencia_produto                                         ,
					tbl_os.codigo_fabricacao                                         ,
					tbl_os.type                                                      ,
					tbl_os.satisfacao                                                ,
					tbl_os.laudo_tecnico                                             ,
					tbl_os.tipo_os_cortesia                                          ,
					tbl_os.serie                                                     ,
					tbl_os.qtde_produtos                                             ,
					tbl_os.troca_faturada                                            ,
					tbl_os.tipo_os                                                   ,
					tbl_os.tipo_atendimento                                          ,
					tbl_os.segmento_atuacao                                          ,
					tbl_os.promotor_treinamento                                      ,
					tbl_produto.produto                                              ,
					tbl_produto.referencia                     AS produto_referencia ,
					tbl_produto.descricao                      AS produto_descricao  ,
					tbl_produto.voltagem                       AS produto_voltagem   ,
					tbl_posto_fabrica.codigo_posto
			FROM tbl_os
			JOIN      tbl_produto  ON tbl_produto.produto       = tbl_os.produto
			JOIN      tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.posto = $posto
			LEFT JOIN tbl_os_extra ON tbl_os.os                 = tbl_os_extra.os
			WHERE tbl_os.os = $os
			AND   tbl_os.posto = $posto
			AND   tbl_os.fabrica = $login_fabrica";
	$res = @pg_query ($con,$sql);

	if (pg_num_rows ($res) == 1) {
		$sua_os			    = pg_fetch_result ($res,0,sua_os);
		$data_abertura		= pg_fetch_result ($res,0,data_abertura);
		$data_digitacao		= pg_fetch_result ($res,0,data_digitacao);
		$consumidor_nome	= pg_fetch_result ($res,0,consumidor_nome);
		$consumidor_cpf 	= pg_fetch_result ($res,0,consumidor_cpf);
		$consumidor_cidade	= pg_fetch_result ($res,0,consumidor_cidade);
		$consumidor_fone	= pg_fetch_result ($res,0,consumidor_fone);
		$consumidor_estado	= pg_fetch_result ($res,0,consumidor_estado);
		$revenda_cnpj		= pg_fetch_result ($res,0,revenda_cnpj);
		$revenda_nome		= pg_fetch_result ($res,0,revenda_nome);
		$nota_fiscal		= pg_fetch_result ($res,0,nota_fiscal);
		$data_nf		    = pg_fetch_result ($res,0,data_nf);
		$consumidor_revenda	= pg_fetch_result ($res,0,consumidor_revenda);
		$aparencia_produto	= pg_fetch_result ($res,0,aparencia_produto);
		$codigo_fabricacao	= pg_fetch_result ($res,0,codigo_fabricacao);
		$type			    = pg_fetch_result ($res,0,type);
		$satisfacao		    = pg_fetch_result ($res,0,satisfacao);
		$laudo_tecnico		= pg_fetch_result ($res,0,laudo_tecnico);
		$tipo_os_cortesia	= pg_fetch_result ($res,0,tipo_os_cortesia);
		$produto_serie		= pg_fetch_result ($res,0,serie);
		$qtde_produtos		= pg_fetch_result ($res,0,qtde_produtos);
		$produto            = pg_fetch_result ($res,0,produto);
		$produto_referencia	= pg_fetch_result ($res,0,produto_referencia);
		$produto_descricao	= pg_fetch_result ($res,0,produto_descricao);
		$produto_voltagem	= pg_fetch_result ($res,0,produto_voltagem);
		$troca_faturada		= pg_fetch_result ($res,0,troca_faturada);
		$codigo_posto		= pg_fetch_result ($res,0,codigo_posto);
		$tipo_os                = pg_fetch_result ($res,0,tipo_os);
		$tipo_atendimento       = pg_fetch_result ($res,0,tipo_atendimento);
		$segmento_atuacao       = pg_fetch_result ($res,0,segmento_atuacao);
		$promotor_treinamento   = pg_fetch_result ($res,0,promotor_treinamento);


		//--=== Tradução para outras linguas ============================= Raphael HD:1212

		$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

		$res_idioma = @pg_query($con,$sql_idioma);
		if (@pg_num_rows($res_idioma) >0) {
			$produto_descricao  = trim(@pg_fetch_result($res_idioma,0,descricao));
		}

		//--=== Tradução para outras linguas ================================================

	}
}

/*============= RECARREGA FORM EM CASO DE ERRO ==================*/

if (strlen ($msg_erro) > 0) {
	$os                       = $_POST['os'];
	$sua_os                   = $_POST['sua_os'];
	$data_abertura            = $_POST['data_abertura'];
	$data_digitacao           = $_POST['data_digitacao'];
	$consumidor_nome          = $_POST['consumidor_nome'];
	$consumidor_cpf           = $_POST['consumidor_cpf'];
	$consumidor_cidade        = $_POST['consumidor_cidade'];
	$consumidor_fone          = $_POST['consumidor_fone'];
	$consumidor_estado        = $_POST['consumidor_estado'];
	$revenda_cnpj             = $_POST['revenda_cnpj'];
	$revenda_nome             = $_POST['revenda_nome'];
	$nota_fiscal              = $_POST['nota_fiscal'];
	$data_nf                  = $_POST['data_nf'];
	$produto_referencia       = $_POST['produto_referencia'];
	$produto_descricao        = $_POST['produto_descricao'];
	$produto_voltagem         = $_POST['produto_voltagem'];
	$produto_serie			= trim($_POST['produto_serie']);
	$qtde_produtos			= $_POST['qtde_produtos'];
	$cor				    = $_POST['cor'];
	$consumidor_revenda		= $_POST['consumidor_revenda'];

	$type			    	= $_POST['type'];
	$satisfacao		    	= $_POST['satisfacao'];
	$laudo_tecnico			= $_POST['laudo_tecnico'];

	$obs				      = $_POST['obs'];
//	$chamado			      = $_POST['chamado'];
	$quem_abriu_chamado 	  = $_POST['quem_abriu_chamado'];
	$taxa_visita			  = $_POST['taxa_visita'];
	$visita_por_km			  = $_POST['visita_por_km'];
	$hora_tecnica			  = $_POST['hora_tecnica'];
	$regulagem_peso_padrao	  = $_POST['regulagem_peso_padrao'];
	$certificado_conformidade = $_POST['certificado_conformidade'];
	$valor_diaria			  = $_POST['valor_diaria'];
	$codigo_posto			  = $_POST['codigo_posto'];
	$tipo_atendimento		  = $_POST['tipo_atendimento'];
	$segmento_atuacao         = $_POST['segmento_atuacao'];

	$locacao                  = $_POST['locacao'];
	$promotor_treinamento       = $_POST['promotor_treinamento'];
	$promotor_treinamento2       = $_POST['promotor_treinamento2'];
}

if($login_fabrica == 20 AND strlen($os)>0) $desabilita = "SIM";

$body_onload = "javascript: document.frm_os.sua_os.focus()";

/* PASSA PARÂMETRO PARA O CABEÇALHO (não esquecer ===========*/

/* $title = Aparece no sub-menu e no título do Browser ===== */
$title = "Cadastro de Ordem de Serviço";
if($sistema_lingua == "ES") $title = "Alta de Orden de Servicio";
/* $layout_menu = Determina a aba em destaque do MENU ===== */
$layout_menu = 'os';

include "cabecalho.php";

$sql = "SELECT digita_os FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = @pg_query($con,$sql);
$digita_os = pg_fetch_result ($res,0,0);
if ($digita_os == 'f') {
if($sistema_lingua == "ES") echo "<h4>Acceso denegado</h4>";
else	echo "<H4>Sem permissão de acesso.</H4>";
	exit;
}

?>

<!--=============== <FUNÇÕES> ================================!-->

<? include "javascript_pesquisas.php" ?>
<script language='javascript' src='ajax.js'></script>
<script language='javascript'>
/* ============= Função PESQUISA DE CONSUMIDOR POR NOME ====================
Nome da Função : fnc_pesquisa_consumidor_nome (nome, cpf)
=================================================================*/
function fnc_pesquisa_consumidor (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_consumidor.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor.php?cpf=" + campo.value + "&tipo=cpf";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
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
	janela.focus();
}

function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=650,height=400,top=18,left=0");
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

/* ============= Função FORMATA CNPJ =============================
Nome da Função : formata_cnpj (cnpj, form)
		Formata o Campo de CNPJ a medida que ocorre a digitação
		Parâm.: cnpj (numero), form (nome do form)
=================================================================*/
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


/* ========== Função AJUSTA CAMPO DE DATAS =========================
Nome da Função : ajustar_data (input, evento)
		Ajusta a formatação da Máscara de DATAS a medida que ocorre
		a digitação do texto.
=================================================================*/
function ajustar_data(input , evento)
{
	var BACKSPACE=  8;
	var DEL=  46;
	var FRENTE=  39;
	var TRAS=  37;
	var key;
	var tecla;
	var strValidos = "0123456789" ;
	var temp;
	tecla= (evento.keyCode ? evento.keyCode: evento.which ? evento.which : evento.charCode)

	if (( tecla == BACKSPACE )||(tecla == DEL)||(tecla == FRENTE)||(tecla == TRAS)) {
		return true;
			}
		if ( tecla == 13) return false;
		if ((tecla<48)||(tecla>57)){
			return false;
			}
		key = String.fromCharCode(tecla);
		input.value = input.value+key;
		temp="";
		for (var i = 0; i<input.value.length;i++ )
			{
				if (temp.length==2) temp=temp+"/";
				if (temp.length==5) temp=temp+"/";
				if ( strValidos.indexOf( input.value.substr(i,1) ) != -1 ) {
					temp=temp+input.value.substr(i,1);
			}
			}
					input.value = temp.substr(0,10);
				return false;
}



/* ============= <PHP> VERIFICA SE HÁ COMUNICADOS =============
		VERIFICA SE TEM COMUNICADOS PARA ESTE PRODUTO E SE TIVER, RETORNA UM
		LINK PARA VISUALIZAR-LO
		Fábio 07/12/2006
=============================================================== */

function retornaComunicado (http) {
	var imagem = document.getElementById('img_comunicado');
	if (http.readyState == 4) {
alert("estado 4");
		if (http.status == 200) {
alert("estado 200");
			results = http.responseText;
			alert("Resultado="+results);
			if (typeof (results) != 'undefined') {
				if (results!="sem"){
					imagem.style.visibility = "visible";
					document.frm_os.link_comunicado.value=results;
					alert("OK="+results);
				}
				else {
					imagem.style.visibility = "hidden";
					document.frm_os.link_comunicado.value="";
					alert("SEM="+results);
				}
			}else{

				imagem.style.visibility = "hidden";
				document.frm_os.link_comunicado.value="";
					alert("SEM_1="+results);
			}
		}
	}
}

function checarComunicado2 (fabrica) {
	var ref = document.frm_os.produto_referencia.value;
	var imagem = document.getElementById('img_comunicado');

	imagem.style.visibility = "hidden";
	document.frm_os.link_comunicado.value="";

	if (ref.length>0){
		url = "ajax_os_cadastro_comunicado.php?fabrica="+fabrica+"&produto="+escape(ref);
		alert(url);
		http.open("GET", url , true);
		http.onreadystatechange = function () { retornaComunicado (http) ; } ;
		http.send(null);
	}else{
	}
}
function trim(str)
{  while(str.charAt(0) == (" ") )
  {  str = str.substring(1);
  }
  while(str.charAt(str.length-1) == " " )
  {  str = str.substring(0,str.length-1);
  }
  return str;
}

function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}

var http = new Array();
function checarComunicado(fabrica){
	var imagem = document.getElementById('img_comunicado');
	var ref = document.frm_os.produto_referencia.value;

	//imagem.style.visibility = "hidden";
	document.frm_os.link_comunicado.value="";
	imagem.title = "<? if($sistema_lingua == "ES"){
echo "NO HAY COMUNICADO PARA ESTE PRODUCTO";
}else{
echo "NÃO HÁ COMUNICADO PARA ESTE PRODUTO";
 } ?>
";

	ref = trim(ref);

	if (ref.length>0){
		var curDateTime = new Date();
		http[curDateTime] = createRequestObject();
		url = "ajax_os_cadastro_comunicado.php?fabrica="+fabrica+"&produto="+escape(ref);
		http[curDateTime].open('get',url);
		http[curDateTime].onreadystatechange = function(){
			if (http[curDateTime].readyState == 4)
			{
				if (http[curDateTime].status == 200 || http[curDateTime].status == 304)
				{
					var response = http[curDateTime].responseText;
					if (response=="ok"){
					    var txt = "<? if($sistema_lingua == "ES"){

echo "EXISTEN COMUNICADOS PARA ESTE PRODUCTO. HAGA CLICK AQUÍ PARA LEER ";
}else{
echo "HÁ COMUNICADO PARA ESTE PRODUTO. CLIQUE AQUI PARA LER";
}?>";
						document.frm_os.link_comunicado.value = txt;
						imagem.title = txt;
					}
					else {
						document.frm_os.link_comunicado.value="";
						imagem.title = "<? if($sistema_lingua == "ES"){
echo "NO HAY COMUNICADOS PARA ESTE PRODUCTO";
}else{
echo "NÃO HÁ COMUNICADO PARA ESTE PRODUTO";
 } ?>";
					}
				}
			}
		}
		http[curDateTime].send(null);
	}
}

function abreComunicado(){
	var ref = document.frm_os.produto_referencia.value;
	var desc = document.frm_os.produto_descricao.value;
	if (document.frm_os.link_comunicado.value!=""){
		url = "pesquisa_comunicado.php?produto=" + ref +"&descricao="+desc;
		window.open(url,"comm","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=650,height=400,top=18,left=0");
	}
}

function MostraEsconde(dados)
{

    if (document.getElementById)
    {
        var style2 = document.getElementById(dados);
        if (style2==false) return;
        if (style2.style.display=="block"){
            style2.style.display = "none";
            }
        else{
            style2.style.display = "block";
        }
     }
}
function checarNumero(campo){
	var num = campo.value;
	campo.value = parseInt(num);
	if (campo.value=='NaN') {
		campo.value='';
		return false;
	}
}

function txtBoxFormat(objeto, sMask, evtKeyPress) {
	var i, nCount, sValue, fldLen, mskLen,bolMask, sCod, nTecla;

	if(document.all) { // Internet Explorer
		nTecla = evtKeyPress.keyCode;
	} else if(document.layers) { // Nestcape
		nTecla = evtKeyPress.which;
	} else {
		nTecla = evtKeyPress.which;
		if (nTecla == 8) {
			return true;
		}
	}

	sValue = objeto.value;

	// Limpa todos os caracteres de formatação que
	// já estiverem no campo.
	sValue = sValue.toString().replace( "-", "" );
	sValue = sValue.toString().replace( "-", "" );
	sValue = sValue.toString().replace( ".", "" );
	sValue = sValue.toString().replace( ".", "" );
	sValue = sValue.toString().replace( "/", "" );
	sValue = sValue.toString().replace( "/", "" );
	sValue = sValue.toString().replace( ":", "" );
	sValue = sValue.toString().replace( ":", "" );
	sValue = sValue.toString().replace( "(", "" );
	sValue = sValue.toString().replace( "(", "" );
	sValue = sValue.toString().replace( ")", "" );
	sValue = sValue.toString().replace( ")", "" );
	sValue = sValue.toString().replace( " ", "" );
	sValue = sValue.toString().replace( " ", "" );
	fldLen = sValue.length;
	mskLen = sMask.length;

	i = 0;
	nCount = 0;
	sCod = "";
	mskLen = fldLen;

	while (i <= mskLen) {
		bolMask = ((sMask.charAt(i) == "-") || (sMask.charAt(i) == ".") || (sMask.charAt(i) == "/") || (sMask.charAt(i) == ":"))
		bolMask = bolMask || ((sMask.charAt(i) == "(") || (sMask.charAt(i) == ")") || (sMask.charAt(i) == " "))

	if (bolMask) {
		sCod += sMask.charAt(i);
		mskLen++; }
	else {
		sCod += sValue.charAt(nCount);
		nCount++;
	}

	  i++;
	}

	objeto.value = sCod;

	if (nTecla != 8) { // backspace
		if (sMask.charAt(i-1) == "9") { // apenas números...
			return ((nTecla > 47) && (nTecla < 58)); }
		else { // qualquer caracter...
			return true;
	}
	}
	else {
		return true;
	}
}
</script>

<!-- JQuery -->
<script type="text/javascript" src="js/jquery-latest.pack.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<!-- Formatar DATA -->
<script type="text/javascript" charset="utf-8">
	$(function(){
		displayText('&nbsp;');
		$("input[@rel='data']").maskedinput("99/99/9999");
		$("input[@rel='data_hora']").maskedinput("99/99/9999 99:99");
	});
</script>

<script type="text/javascript">

/* Função mostra o campo quando muda o select(combo)*/
<? /* HD 28140 - Francisco Ambrozio - diferenciação entre troca e cortesia.
		Os promotores só podem aprovar troca se estiverem habilitados para isto */
	if($login_pais =='BR'){ // HD 53926
		$promotor_mostra="campo.value== '15' || campo.value== '16'";
	}elseif($login_pais=='CO'){
		$promotor_mostra="campo.value== '16'";
	}else{
		$promotor_mostra="campo.value== '15' || campo.value== '16'";
	}

?>
	function MudaCampo(campo){
		if (<?echo $promotor_mostra;?>) {
			document.getElementById('autorizacao_cortesia').style.display='inline';
		}else{
			document.getElementById('autorizacao_cortesia').style.display='none';
			//hd 47203
			document.getElementById('promotor_treinamento').selectedIndex=0;
		}

		if (campo.value=='13'  || campo.value== '66' ) {
			document.getElementById('autorizacao_troca').style.display='inline';
		}else{
			document.getElementById('autorizacao_troca').style.display='none';
			//hd 47203
			document.getElementById('promotor_treinamento2').selectedIndex=0;
		}
	}

</script>
<!-- ============= <PHP> VERIFICA DUPLICIDADE DE OS  =============
		Verifica a existência de uma OS com o mesmo número e em
		caso positivo passa a mensagem para o usuário.
=============================================================== -->
<?
//if ($ip == '201.0.9.216') echo $msg_erro;

if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";
?>

<!-- ============= <HTML> COMEÇA FORMATAÇÃO ===================== -->

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
<?
	if($sistema_lingua) $msg_erro = traducao_erro($msg_erro,$sistema_lingua);
/*
	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		if($sistema_lingua == "ES"){
			$erro = "Fue detectado el seguiente error:<BR>";
		}else{ $erro = "Foi detectado o seguinte erro:<br>";}
		$msg_erro = substr($msg_erro, 6);
	}
*/
	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo "<!-- ERRO INICIO -->";
	//echo $erro . $msg_erro . "<br><!-- " . $sql . "<br>" . $sql_OS . " -->";
	echo $erro . $msg_erro;
	echo "<!-- ERRO FINAL -->";
?>
	</td>
</tr>
</table>

<? } ?>

<?
if($ip=='200.208.222.183') echo 'passo';

$sql = "SELECT TO_CHAR (current_timestamp , 'DD/MM/YYYY' )";
$res  = @pg_query ($con,$sql);
$hoje = @pg_fetch_result ($res,0,0);

$hora = date("H:i");
$hoje = $hoje . " " . $hora;

//Chamado 1982
if ($login_fabrica == 15) { ?>
	<div style='font-size: 12px;display: inline; text-align: justify; padding: 2px; width: 650px; background-color: #8CBCE3' align='justify'>
		<p align='center'><b>AVISO IMPORTANTE!</b></p>
		&nbsp;&nbsp;&nbsp;&nbsp;Em continuação da implantação do sistema Telecontrol, a partir do dia 02 de maio de 2007
		estaremos gerando numeração automática na abertura de todas as ordens de serviço, não sendo mais necessário o envio do bloco.<br>
		&nbsp;&nbsp;&nbsp;&nbsp;As ordens de serviço que já estão preenchidas, devem ser digitadas o número no campo observação. Exemplo.: ref OS 12345<br>
		<br>
		Latinatec<br>
		0800-7711575<br>
		(16) 3363-4400
	</div>
<? } ?>

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>

	<td valign="top" align="left">

		<!-- ------------- Formulário ----------------- -->

		<form style="margin: 0px; word-spacing: 0px" name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input class="frm" type="hidden" name="os" value="<? echo $os; ?>">
<?
		if ($login_fabrica == 3) {
			echo "<table width='600' border='0' cellspacing='5' cellpadding='0' align='center'>";
			echo "<tr>";
			echo "<td align='center' bgcolor='#66FF99' style='font-color:#ffffff ; font-size:12px'>";
			echo "Não é permitido abrir Ordens de Serviço com data de abertura superior a 90 dias.";
			echo "</td>";
			echo "</tr>";
			echo "</table>";

			//HD 7662
			/*echo "<table width='600' border='0' cellspacing='5' cellpadding='0' align='center'>";
			echo "<tr>";
			echo "<td align='center' bgcolor='#6699FF' style='font-color:#ffffff ; font-size:12px'>";
			echo "Conforme comunicado, é obrigatório o envio de cópia da <br>Nota de Compra juntamente com a Ordem de Serviço.<br>";
			echo "<a href='comunicado_mostra.php?comunicado=735' target='_blank'>Clique para visualizar o Comunicado</a>";
			echo "</td>";
			echo "</tr>";
			echo "</table>";*/
		}
?>

		<p>
		<? if ($distribuidor_digita == 't') { ?>
			<table width="100%" border="0" cellspacing="5" cellpadding="0">
			<tr valign='top' style='font-size:12px'>
				<td nowrap>
				Distribuidor pode digitar OS para seus postos.
				<br>
				Digite o código do posto
				<input type='text' name='codigo_posto' size='5' maxlength='10' value='<? echo $codigo_posto ?>'>
				ou deixe em branco para suas próprias OS.
				</td>
			</tr>
			</table>
		<? } ?>

		<br>

		<? if ($login_fabrica == 20) { ?>
		<div style='border: #D3BE96 1px solid;
				background-color: #FCF0D8;
				font-family: Arial;
				font-size:   9pt;
				color:#333333;' class='CaixaMensagem' width='400'>
		<center>
		<font size="2" face="Geneva, Arial, Helvetica, san-serif">
		<? /*3192 Em "Tipo de Atendimento" devería ser "Tipo de Atención"*/
		if($sistema_lingua) echo "Tipo de Atención";else echo "Tipo de Atendimento";
		?>

		<select name="tipo_atendimento" size="1" class="frm" <? if ($login_fabrica==20) { echo "onChange='MudaCampo(this)'"; };?>>
			<option selected></option>
			<?

			//IGOR  - HD 2909  | Garantía de repuesto - Não tem | Garantía de accesorios - Não tem | Garantía de reparación - Não tem
			$wr = "";
			if($login_fabrica == 20 and $login_pais == "PE"){
				$wr = " AND tbl_tipo_atendimento.tipo_atendimento NOT IN(11, 12, 14) ";
			}
			if($login_fabrica == 20 and $login_pais <> "BR"){
				$tipo_at = " AND tbl_tipo_atendimento.tipo_atendimento NOT IN(66) ";
			}

			$sql = "SELECT *
					FROM tbl_tipo_atendimento
					WHERE fabrica = $login_fabrica
					AND   ativo IS TRUE
					$wr
					$tipo_at
					ORDER BY tipo_atendimento";
			$res = pg_query ($con,$sql) ;

			for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
				$codigo                = pg_fetch_result ($res,$i,codigo);
				$descricao_atendimento = pg_fetch_result ($res,$i,descricao);
				$x_tipo_atendimento    = pg_fetch_result ($res,$i,tipo_atendimento);

				//--=== Tradução para outras linguas ============================= Raphael HD:1356

				$sql_idioma = "SELECT * FROM tbl_tipo_atendimento_idioma WHERE tipo_atendimento = $x_tipo_atendimento AND upper(idioma) = '$sistema_lingua'";

				$res_idioma = pg_query($con,$sql_idioma);

				if (pg_num_rows($res_idioma) >0) {
					$descricao_atendimento  = trim(pg_fetch_result($res_idioma,0,descricao));
				}

				//--=== Tradução para outras linguas ================================================

				echo "<option ";
				if ($tipo_atendimento == $x_tipo_atendimento ) echo " selected ";
				echo " value='$x_tipo_atendimento' >" ;
				echo $codigo . " - " .$descricao_atendimento  ;
				echo "</option>\n";

			}

			// HD 43180 - HD 53545
			if($login_fabrica == 20 and $login_posto == "6359" AND 1==2){
				echo "<option ";
				if ($tipo_atendimento == 66 ) echo " selected ";
				echo " value='66'>8 - OS Troca Fora de garantia</option>\n";
			}
			?>
		</select>
		<?
//BOSCH
		if($login_fabrica == 20){
			//hd 3329
			if($sistema_lingua == "ES") {
				echo "&nbsp;&nbsp;&nbsp;&nbsp;Segmento de actuación ";
			}else {
				echo "&nbsp;&nbsp;&nbsp;&nbsp;Segmento de atuação ";
			}
			echo "<select name='segmento_atuacao' size='1' class='frm'>";
			echo "<option selected></option>";

			$sql = "SELECT *
				FROM tbl_segmento_atuacao
				WHERE fabrica = $login_fabrica
				ORDER BY descricao";
			$res = pg_query ($con,$sql) ;

			for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
				$descricao_segmento = pg_fetch_result ($res,$i,descricao);
				$x_segmento_atuacao = pg_fetch_result ($res,$i,segmento_atuacao);

				//--=== Tradução para outras linguas ============================= Raphael HD:1356

				$sql_idioma = "SELECT * FROM tbl_segmento_atuacao_idioma WHERE segmento_atuacao = $x_segmento_atuacao AND upper(idioma) = '$sistema_lingua'";

				$res_idioma = @pg_query($con,$sql_idioma);

				if (@pg_num_rows($res_idioma) >0) $descricao_segmento  = trim(@pg_fetch_result($res_idioma,0,descricao));

				//--=== Tradução para outras linguas ================================================

				echo "<option ";
				if ($segmento_atuacao == $x_segmento_atuacao ) echo " selected ";
				echo " value='$x_segmento_atuacao'>" ;
				echo $descricao_segmento  ;
				echo "</option>\n";
			}
			echo "</select>";

/* HD 3192 - Em caso de garantía de piezas o accesorios no es necesario inserir el producto en la OS"  deveria ser "En caso de garantía de piezas o accesorios no es necesario insertar el producto en la OS" */
			echo "<br><b><FONT SIZE='' COLOR='#FF9900'>";
			if($sistema_lingua)
				 echo "En caso de garantía de repuestos o accesorios no es necesario especificar el producto en la OS";
			else echo "Nos casos de Garantia de Peças ou  Acessórios não é necessário lançar o Produto na OS.";
			echo "</FONT></b><br>";
		}
		echo "</div>";
		?>
		<? } ?>
		<?
		if($login_fabrica == 20){
		//alterado gustavo HD 5909
		/*#####################################*/
		$mostrar		= ($tipo_atendimento==15 or $tipo_atendimento==16) ? "display:inline;":"display:none;";
		$troca_mostrar	= ($tipo_atendimento==13 or $tipo_atendimento==66) ? "display:inline;":"display:none;";

		#####################################################################################
		# HD - 28140 - Francisco Ambrozio - acrescentei a div abaixo para fazer             #
		#   diferenciação entre troca e cortesia, uma vez que o promotor só pode autorizar  #
		#   troca se estiver habilitado para isto                                           #
		#####################################################################################

		echo "<div id='autorizacao_troca'
				style='$troca_mostrar
				border: #D3BE96 1px solid;
				background-color: #FCF0D8;
				font-family: Arial;
				font-size:   9pt;
				text-align: left;
				color:#333333;width:700px'>";
				echo "<TABLE  width='710'>";
					echo "<TR>";
					echo "<TD colspan='2'>";echo "<b><FONT COLOR='#FF9900'>";
					if($sistema_lingua)
						 echo "En el caso de cambio de producto, gentileza comercial o técnica, es obligatorio informar del nombre de la persona que lo aprobó y la fecha de aprobación.";
					else echo "Nos casos de Troca de Produto, Cortesia comercial ou técnica é obrigatório informar o nome da pessoa para aprovação.";
					echo "</FONT></b><br>";
					echo "</TD>";
					echo "</TR>";
					echo "<TR>";

					//if($sistema_lingua <> 'ES'){

						echo "<TD>";
						echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif'>";
						if($login_pais<>'BR') {
							echo "RESPONSABLE: ";
						}else{
							echo "Cortesia concedida pelo Promotor: ";
						}
						//echo "Cortesia concedida pelo Promotor: </font><br>";
						echo "<select name='promotor_treinamento2' id='promotor_treinamento2' size='1' class='frm'>";
						echo "<option></option>";
						$sql = "SELECT tbl_promotor_treinamento.promotor_treinamento,
										tbl_promotor_treinamento.nome,
										tbl_promotor_treinamento.email,
										tbl_promotor_treinamento.ativo,
										tbl_escritorio_regional.descricao
							FROM tbl_promotor_treinamento
							JOIN tbl_escritorio_regional USING(escritorio_regional)
							WHERE tbl_promotor_treinamento.fabrica = $login_fabrica
							AND   tbl_promotor_treinamento.ativo ='t'
							AND   tbl_promotor_treinamento.aprova_troca ='t'
							AND   tbl_promotor_treinamento.pais = '$login_pais'
							ORDER BY tbl_promotor_treinamento.nome";
						$res = pg_query ($con,$sql) ;
						for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
							$xx_promotor_treinamento = pg_fetch_result ($res,$i,promotor_treinamento);
							$xx_nome                 = pg_fetch_result ($res,$i,nome);

							echo "<option ";
							if ($promotor_treinamento2 == $xx_promotor_treinamento ) echo " selected ";
							echo " value='$xx_promotor_treinamento' >" ;
							echo $xx_nome;
							echo "</option>\n";
						}
						echo "</select>";
						echo "</TD>";
					//}
					echo "</TR>";
				echo "</TABLE>";
		echo "</div>";

		#### --- Fim da div autorizacao_troca, acrescentada no HD 28140--- ##################

		echo "<div id='autorizacao_cortesia'
				style='$mostrar
				border: #D3BE96 1px solid;
				background-color: #FCF0D8;
				font-family: Arial;
				font-size:   9pt;
				text-align: left;
				color:#333333;width:700px'>";
				echo "<TABLE  width='710'>";
					echo "<TR>";
					echo "<TD colspan='2'>";echo "<b><FONT SIZE='' COLOR='#FF9900'>";
					if($sistema_lingua) echo "En el caso de cambio de producto,  gentileza comercial o técnica,  es obligatorio informar del nombre de la persona que lo aprobó y la fecha de aprobación.";else echo "Nos casos de Troca de Produto, Cortesia comercial ou técnica é obrigatório informar o nome da pessoa para aprovação.";
					echo "</FONT></b><br>";
					echo "</TD>";
					echo "</TR>";
					echo "<TR>";

					if($login_pais=='BR' or $login_pais=='CO' or 1==1){

						echo "<TD>";
// 						echo "$login_pais - pais";
						echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif'>";
						if($login_pais<>'BR') {
							echo "RESPONSABLE: ";
						}else{
							echo "Cortesia concedida pelo Promotor: ";
						}
						echo "</font><br>";
						echo "<select name='promotor_treinamento' id='promotor_treinamento' size='1' class='frm'>";
						echo "<option></option>";
						#if($login_pais<>'CO') { HD 221033
							$sql = "SELECT tbl_promotor_treinamento.promotor_treinamento,
											tbl_promotor_treinamento.nome,
											tbl_promotor_treinamento.email,
											tbl_promotor_treinamento.ativo,
											tbl_escritorio_regional.descricao
								FROM tbl_promotor_treinamento
								JOIN tbl_escritorio_regional USING(escritorio_regional)
								WHERE tbl_promotor_treinamento.fabrica = $login_fabrica
								AND   tbl_promotor_treinamento.ativo ='t'
								AND   tbl_promotor_treinamento.pais = '$login_pais'
								ORDER BY tbl_promotor_treinamento.nome";
							$res = pg_query ($con,$sql) ;
							for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
								$x_promotor_treinamento = pg_fetch_result ($res,$i,promotor_treinamento);
								$x_nome                 = pg_fetch_result ($res,$i,nome);

								echo "<option ";
								if ($promotor_treinamento == $x_promotor_treinamento ) echo " selected ";
								echo " value='$x_promotor_treinamento' >" ;
								echo $x_nome;
								echo "</option>\n";
							}
						#}elseif($login_pais=='CO'){
						#	echo "<option value='606'>Edwing Diaz</option>";
						#}
						echo "</select>";
						echo "</TD>";

					}
					echo "</TR>";
				echo "</TABLE>";
		echo "</div>";
		/*#####################################*/
		}
		?>
		<table width="100%" border="0" cellspacing="10" cellpadding="0">
		<tr valign='top'>
			<td nowrap>
				<? if ($pedir_sua_os == 't') { ?>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS Fabricante</font>
				<br>
				<input  name ="sua_os" class ="frm" type ="text" size ="10" maxlength="20" value ="<? echo $sua_os ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número da OS do Fabricante.');">
				<?
				} else {
					echo "&nbsp;";
					echo "<input type='hidden' name='sua_os'>";
				}
				/*HD 8431 - Os Off-line para Argentina - Raphael */
				if ( $login_fabrica == 20 AND $login_pais == 'AR'){
				?>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Orden Interna</font>
					<br>
					<input  name ="sua_os_offline" class ="frm" type ="text" size ="10" maxlength="10" value ="<? echo $sua_os_offline ?>" >
				<?
				}
				?>
			</td>

			<?
			if (strlen(trim($data_abertura)) == 0 AND $login_fabrica == 20) {
				$data_abertura = $hoje;
			}
			?>

			<? if ($login_fabrica == 6){ ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">N. Série</font>
				<br>
				<input class="frm" type="text" name="produto_serie" size="12" maxlength="20" value="<? echo $produto_serie ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o número de série do aparelho.'); ">
				&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto_serie (document.frm_os.produto_serie,'frm_os')"  style='cursor: pointer'></A>
				<script>
				<!--
				function fnc_pesquisa_produto_serie (campo,form) {
					if (campo.value != "") {
						var url = "";
						url = "produto_serie_pesquisa.php?campo=" + campo.value + "&form=" + form ;
						janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
						janela.focus();
					}
				}
				-->
				</script>
			</td>
			<? } ?>

			<td nowrap>
				<?
				if ($login_fabrica == 3) {
 					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Código do Produto</font>";
				}else{
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>";
					if($sistema_lingua) echo "Referencia del producto";else echo "Referência do Produto";
					echo "</font>";
				}

				// verifica se tem comunicado para este produto (só entra aqui se for abrir a OS) - FN 07/12/2006
				$arquivo_comunicado="";
				if (strlen ($produto_referencia) >0) {
					$sql ="SELECT tbl_comunicado.comunicado, tbl_comunicado.extensao
						FROM  tbl_comunicado JOIN tbl_produto USING(produto)
						WHERE tbl_produto.referencia = '$produto_referencia'
						AND tbl_comunicado.fabrica = $login_fabrica
						AND tbl_comunicado.ativo IS TRUE";
					$res = pg_query($con,$sql);
					if (pg_num_rows($res) > 0)
						$arquivo_comunicado= "HÁ ".pg_num_rows($res)." COMUNICADO(S) PARA ESTE PRODUTO";
				}
/*visibility:<? if ($arquivo_comunicado) echo "visible;";else echo "hidden;"; */?>
				<br>
				<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20"
					value="<? echo $produto_referencia ?>"
					onblur="this.className='frm'; displayText('&nbsp;');checarComunicado(<? echo $login_fabrica ?>);<? if($login_fabrica==15)echo "fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia',document.frm_os.produto_voltagem);"; ?>"
					onfocus="this.className='frm-on'; displayText('&nbsp;<? if($sistema_lingua == "ES") echo "Informe la referencia del producto y haga clic en la lupa para buscar";else echo "Entre com a referência do produto e clique na lupa para efetuar a pesquisa.";?>');" <? if ((strlen($locacao) > 0) or $desabilita) echo "readonly"; ?>>&nbsp;
				<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle'
					onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia',document.frm_os.produto_voltagem) " height='22px' style='cursor: pointer'>
				<img src='imagens/botoes/vista.jpg' height='22px' id="img_comunicado" target="_blank" name='img_comunicado' border='0'
					align='absmiddle'  title="COMUNICADOS"
					onclick="javascript:abreComunicado()"
					style='cursor: pointer;'>
				<input type="hidden" name="link_comunicado" value="<? echo $arquivo_comunicado; ?>">
			</td>
			<td nowrap>
				<?
				if ($login_fabrica == 3) {
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Modelo do Produto</font>";
				}else{
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>";
					if($sistema_lingua) echo "Descripción del producto";else echo "Descrição do Produto";
					echo "</font>";
				}
				?>

				<br>
				<input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>"  onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<?if($sistema_lingua == "ES") echo "Informe del modelo del producto y haga clic en la lupa para buscar";else echo "Digite aqui o modelo do produto e clique na lupa para efetuar a pesquisa.";?>');checarComunicado(<? echo $login_fabrica ?>);" <? if (strlen($locacao) > 0) echo "readonly"; ?><?if($desabilita)echo " disabled";?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao',document.frm_os.produto_voltagem)"  style='cursor: pointer' <?if($desabilita)echo "disabled";?>></A>
			</td>

			<td nowrap>
			<?php
				if ($login_fabrica != 59) {//HD 188632?>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua) echo "Voltaje";else echo "Voltagem";?></font>
					<br />
					<input class="frm" type="text" name="produto_voltagem" size="5" value="<? echo $produto_voltagem ?>" <? if ($login_fabrica != 1 || strlen($tipo_os) > 0) echo "readonly"; ?> />
			<?php
				}
			?>
			</td>
			<td nowrap>
<?		if ($login_fabrica == 6){ ?>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif" color='#cc0000'>Data de entrada </font>
<?		}else{?>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">
				<?if($sistema_lingua) echo "Fecha de abertura";else echo "Data Abertura";?>
			</font>
<?		}?>
				<br>
				<? if($login_fabrica==20){ ?>
				<input  name="data_abertura" rel='data_hora' size="18" maxlength="18" value="<?=$data_abertura?>" type="text" class="frm" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<?if($sistema_lingua == "ES") echo "Informe de la fecha de abertura de la OS";else echo "Entre a Data da Abertura da OS.";?>');" tabindex="0">
				<? }else{ ?>
					<input  name="data_abertura" rel='data' size="12" maxlength="10" value="<?=$data_abertura?>" type="text" class="frm" onblur="this.className='frm'; displayText('&nbsp;');"
					 onfocus="this.className='frm-on'; displayText('&nbsp;<?if($sistema_lingua == "ES") echo "Informe de la fecha de abertura de la OS";else echo "Entre a Data da Abertura da OS.";?>'); "
					tabindex="0">
				<? } ?>
				 <br>
				 <font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo ($sistema_lingua) ? "N. Serie" : "N. Série";?></font>
				<br>
				<input  class="frm" type="text" name="produto_serie" size="8" maxlength="20"
						value="<? echo $produto_serie ?>"
					   onblur="this.className='frm'; displayText('&nbsp;');"
					  onfocus="this.className='frm-on'; displayText('&nbsp;<?
				echo ($sistema_lingua == "ES") ? "Escriba el número de serie del producto." :  "Digite aqui o número de série do aparelho.";
				?>
				');">
				<br/>
				<div id='dados_1' style='position:absolute; display:none; border: 1px solid #949494;background-color: #f4f4f4;'>
				Máscara: LLNNNNNNLNNL<br>
				L: Letra<BR>
				N: Número
				</div>
			</td>
		</tr>
		</table>

		<hr>
		<input type="hidden" name="consumidor_cliente">
		<input type="hidden" name="consumidor_endereco">
		<input type="hidden" name="consumidor_numero">
		<input type="hidden" name="consumidor_complemento">
		<input type="hidden" name="consumidor_bairro">
		<input type="hidden" name="consumidor_cep">
		<input type="hidden" name="consumidor_cidade">
		<input type="hidden" name="consumidor_estado">
		<input type="hidden" name="consumidor_rg">

		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo ($sistema_lingua) ? "Nombre consumidor " : "Nome Consumidor";?></font>
				<br>
				<input class="frm" type="text" name="consumidor_nome"   size="30" maxlength="50" value="<? echo $consumidor_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<? echo ($sistema_lingua == "ES") ?  "Ingrese el nobre sel cliente" : "Insira aqui o nome do Cliente.";?>');">&nbsp;<? echo ($sistema_lingua <> "ES" and $login_fabrica == 20) ?"<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, \"nome\")' style='cursor: pointer'>" : "";?>
			</td>
			<td>
				<? if ($login_fabrica <> 19) { ?>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo ($sistema_lingua=="ES") ? "Identificación del consumidor" : "CPF/CNPJ do Consumidor";?></font>
				<br>
				<input class="frm" type="text" name="consumidor_cpf"   size="17" maxlength="18" value="<? echo $consumidor_cpf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<?echo ($sistema_lingua == "ES") ? "Digite la Identificación del cliente. Puede ser digitada directamente o separado com pontos y trazos" : "Digite o CPF do consumidor. Pode ser digitado diretamente, ou separado com pontos e traços.";?>');">&nbsp;<? echo ($sistema_lingua <> "ES" and $login_fabrica == 20) ?"<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_cpf, \"cpf\")' style='cursor: pointer'>" : "";?>
			<? }else{
					echo "<input type='hidden' name='consumidor_cpf'>";
				} ?>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo ($sistema_lingua=="ES") ? "Teléfono" : "Fone";?></font>
				<br>
				<input class="frm" type="text" name="consumidor_fone" size="16" value="<? echo $consumidor_fone ?>"
				maxlength="20" onkeypress="return txtBoxFormat(this, '999 9999-9999', event);"
				onblur="this.className='frm'; displayText('&nbsp;');"
				onfocus="this.className='frm-on'; displayText('&nbsp;<? echo ($sistema_lingua == "ES") ? "Escriba el nº de teléfono con código de area/prefijo " : "Insira o telefone com o DDD. ex.: 014 3434-5656.";?>');">
				<span style='font-size:10px;color:#8F8F8F'> Ex.: 011 3456-1357</span>
			</td>
		</tr>
		</table>
		<hr>
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr valign='top'>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, sans-serif"><? echo ($sistema_lingua) ? "Nombre distribuidor" : "Nome Revenda";?></font>
				<br>
				<input class="frm" type="text" name="revenda_nome" size="30" maxlength="50" value="<? echo $revenda_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<? echo ($sistema_lingua == "ES") ? "Informe del nombre de la tienda donde el produto fue comprado" : "Digite o nome da REVENDA onde foi adquirido o produto.";?>');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' style='cursor: pointer'>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, sans-serif"><? echo ($sistema_lingua) ? "ID distribuidor" : "CNPJ Revenda";?></font>
				<br>
				<input class="frm" type="text" name="revenda_cnpj" size="20" maxlength="18" value="<? echo $revenda_cnpj ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<? echo ($sistema_lingua == "ES") ? "Escriba el nº de ID fiscal de la tienda o distribuidor" : "Insira o número no Cadastro Nacional de Pessoa Jurídica.";?>'); " <? echo ($sistema_lingua <> "ES") ? "onKeyUp=\"formata_cnpj(this.value, 'frm_os')\"" : "";?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor: pointer'>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, sans-serif"><? echo ($sistema_lingua) ? "Factura comercial" : "Nota Fiscal";?></font>
				<br>
				<input class="frm" type="text" name="nota_fiscal"  size="8"  maxlength="8"  value="<? echo $nota_fiscal ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<? echo ($sistema_lingua == "ES") ? "Escriba el número de factura" : "Entre com o número da Nota Fiscal.";?>');" <? if (strlen($locacao) > 0) echo "readonly"; ?>>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, sans-serif"><? echo ($sistema_lingua) ? "Fecha compra" : "Data Compra/NF";?> </font>
				<br>
				<input class="frm" type="text" name="data_nf" rel='data' size="12" maxlength="10" value="<? echo $data_nf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<? echo ($sistema_lingua == "ES") ? "Informe de la fecha de compra. Verifique si el producto está aún en garantía" : "Entre com a data da compra. Verifique se o produto está dentro do PRAZO DE GARANTIA.";?>');" tabindex="0" <? if (strlen($locacao) > 0) echo "readonly"; ?>><br><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
			</td>
		</tr>
		</table>
		<hr>
		<table width="100%" border="0" cellspacing="5" cellpadding="2">
		<tr>
			<td>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>
			<?
				echo "Consumidor";
				echo "</font>&nbsp;";
				echo "<input type='radio' name='consumidor_revenda' value='C' " ;
				if (strlen($consumidor_revenda) == 0 OR $consumidor_revenda == 'C') echo "checked";
				echo "></td>";
				echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>";
				echo ($sistema_lingua=="ES")  ? "o" : "ou";
				echo "</font></td>";
				echo "<td><font size='1' face='Geneva, Arial, Helvetica, san-serif'>";
				echo ($sistema_lingua=="ES") ? "Distribuidor" : "Revenda";
				echo "</font>&nbsp;";
				echo "<input type='radio' name='consumidor_revenda' value='R' ";
				if ($consumidor_revenda == 'R') echo " checked";
				echo ">&nbsp;&nbsp;</td>";

			if($login_fabrica==11){
				echo "<td width='440px'>&nbsp;";
			}else{
				echo "<td>";
				echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>";
				echo ($sistema_lingua=="ES") ? "Apariencia del producto" : "Aparência do Produto";
				echo "</font>";
			}
			echo "<br>";

			if ($login_fabrica == 20) {
				$a_aparencia = array(
				    "NEW" => array("pt-br"	=> "Bom estado",
								   "ES"     => "Buena aparencia"),
				    "USL" => array("pt-br"	=> "Uso intenso",
								   "ES"     => "Uso continuo"),
				    "USN" => array("pt-br"	=> "Uso Normal",
								   "ES"     => "Uso Normal"),
				    "USH" => array("pt-br"	=> "Uso Pesado",
								   "ES"     => "Uso Pesado"),
				    "ABU" => array("pt-br"	=> "Uso Abusivo",
								   "ES"     => "Uso Abusivo"),
				    "ORI" => array("pt-br"	=> "Original, sem uso",
								   "ES"     => "Original, sin uso"),
				    "PCK" => array("pt-br"	=> "Embalagem",
								   "ES"     => "Embalaje")
				);
				echo "<select name='aparencia_produto' size='1' class='frm'>";
				echo "<option value=''></option>";
                foreach ($a_aparencia as $valor => $a_desc) {
                	$desc = ($sistema_lingua == 'ES') ? $a_desc['ES'] : $a_desc['pt-br'];
                	$item_sel= ($aparencia_produto == $valor) ? " selected":"";
                	echo "<option value='$valor'$item_sel>$desc</option>\n";
                }
				echo "</select>";
			}else{
				if($login_fabrica==11){
					echo "<input type='hidden' type='text' name='aparencia_produto' value='$aparencia_produto'>";
				}else{
					echo "<input class='frm' type='text' name='aparencia_produto' size='30' value='$aparencia_produto' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a aparência externa do aparelho deixado no balcão.');\">";
				}
			}

			echo "</td>";
			if ($login_fabrica <> 1) {
				if($login_fabrica == 11){
					//nao mostra acessórios
				}else{ ?>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua) echo "Accesorios";else echo "Acessórios";?></font>
				<br>
				<input class="frm" type="text" name="acessorios" size="30" value="<? echo $acessorios ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<?if($sistema_lingua == "ES") echo "Informe de la fecha de compra. Verifique si el producto está aún en garantía";else echo "Texto livre com os acessórios deixados junto ao produto.";?>');">
			</td>
		<? }
		}?>
		</tr>
	</table>
		<? if ($pedir_defeito_reclamado_descricao == 't') { ?>
	<hr>
	<center>
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">
		Descrição do Defeito Reclamado pelo Consumidor
		</font>
		<br>
		<textarea class='frm' name='defeito_reclamado_descricao' cols='70' rows='5'><? echo $defeito_reclamado_descricao ?></textarea>

		<? }  # Final do IF do Defeito_Reclamado_Descricao ?>
	</td>

	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
</table>

<hr width='700'>

<table width="100%" border="0" cellspacing="5" cellpadding="0">
	<tr>
		<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
			<input type="hidden" name="btn_acao" value="">
			<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('Aguarde ') }" ALT="<?if($sistema_lingua == 'ES') echo "Continuar con la orden de servicio";else echo "Continuar com Ordem de Serviço";?>" border='0' style='cursor: pointer'>
		</td>
	</tr>
</table>

<input type='hidden' name = 'revenda_fone'>
<input type='hidden' name = 'revenda_cidade'>
<input type='hidden' name = 'revenda_estado'>
<input type='hidden' name = 'revenda_endereco'>
<input type='hidden' name = 'revenda_numero'>
<input type='hidden' name = 'revenda_complemento'>
<input type='hidden' name = 'revenda_bairro'>
<input type='hidden' name = 'revenda_cep'>
<input type='hidden' name = 'revenda_email'>

</form>
<p>&nbsp;</p>
<? include "rodape.php";?>
