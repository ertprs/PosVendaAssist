<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include_once '_traducao_erro.php';

require('class/email/mailer/class.phpmailer.php');
include_once __DIR__ . DIRECTORY_SEPARATOR . 'class/tdocs.class.php';

$os = $_GET['os'];

if(isset($novaTelaOs)){
	$cond_os = empty($os) ? '' : '?os=' . $os;
	header('Location:cadastro_os.php' . $cond_os);
}

if ( $login_fabrica == 20 ) {
	//LIBERANDO PARA TODOS OS POSTOS DO BRASIL = hd_chamado=2806621
	$sql = "SELECT tbl_posto_fabrica.posto
			FROM tbl_posto_fabrica
			JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			WHERE tbl_posto_fabrica.posto = $login_posto
			AND tbl_posto_fabrica.fabrica = $login_fabrica
			AND atendimento = 'n'";
	#$res = pg_query($con,$sql);

	//if (pg_num_rows($res)) {

		$cond_os = empty($os) ? '' : '?os=' . $os;
		header('Location:os_cadastro_unico.php' . $cond_os);
		exit;

	//}
}

/**
 * Retorna TRUE ou FALSE.
 *
 * Acrescentar aqui as regras para o anexo obrigatório da foto
 * da série do produto.
 * A "função" recebe um array como parâmetro, assim fica totalmente
 * flexível.
 * As variáveis que estão no use() são lidas conforme ao valor que têm
 * nesta linha, e não quando for usar a "função".
 */
$anexaFotoSerie = function(array $filtros)
	use ($con, $pa_foto_serie_produto, $login_fabrica)
{
	if (!$pa_foto_serie_produto)
		return false;

	if ($login_fabrica == 20) {
		if ($filtros['produto_serie'] == '999' and
			!in_array($filtros['tipo_atendimento'], array(11, 12, 172))) {
			return true;
		}
	}
	return false;
};

if ($login_fabrica == 1 and strlen($os) > 0) {

	$sql = "SELECT consumidor_revenda FROM tbl_os WHERE os=$os";
	$res = pg_query($con,$sql);
	$verifica = pg_fetch_result($res,0,0);

	if ($verifica == "R") {
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

#HD 311414 - 24/03/11 - Gabriel Silveira - VERIFICA SE A OS TEM REGISTRO NA TBL_OS_TROCA, SE TIVER IRÁ REDIRECIONAR PARA os_finalizada.php
if ($login_fabrica == 6 and strlen($os) > 0) {

	$sql = "Select os from tbl_os_troca where os=$os and fabric=$login_fabrica";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		header ("Location: os_finalizada.php?os=$os");
		exit;
	}

}#HD 311414 FIM

/* HD 35521 */
$pre_os     = trim($_GET['pre_os']);
$os_offline = $_POST['os_offline'];

$bd_locacao = array(36,82,83,84,90);// Tipo Posto locação para Black & Decker

if ($login_fabrica == 1 and (in_array($login_tipo_posto, $bd_locacao))) {
	header ("Location: os_cadastro_locacao.php");
	exit;
}

//takashi 28-02 15:00 liberado conforme contato da fabiola HD 1367
if ($login_fabrica == 1) {
	include 'os_cadastro_black.php';
	exit;
}

# HD 17218 liberado para todos os Postos - HD 33776 Maxcom
if (($login_fabrica == 14 or $login_fabrica==66) and $os_offline <> '1') {
	include 'os_cadastro_intelbras_ajax.php';
	exit;
}



/*	Britânia	LIBERADO TAKASHI 06-02-07 10:11 HD 1141
	Mondial		Liberado para todos os postos da mondial em 11/01/2008 - HD 9975
	Filizola
	LENOXX		LIBERADO POR WELLINGTON 20/12/2006 - 14:05:00
*/
$usam_os_cad_tudo_com_offline = array(3, 11, 172);

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

// A Fabrica 20 está em versão BETA
$usam_os_cadastro_unico = array(85,87);
if(in_array($login_fabrica, $usam_os_cadastro_unico)) {
	include_once("os_cadastro_unico.php");
	exit;
}

$usam_os_cadastro_tudo = array(2, 5, 6, 7, 15, 19, 24, 25, 26);
// echo $login_fabrica;
if (($os_offline != '1' and in_array($login_fabrica,$usam_os_cad_tudo_com_offline))
	or
	(in_array($login_fabrica,$usam_os_cadastro_tudo))
	or
	($login_fabrica >= 28)
	or
	($login_fabrica==3 and ($login_posto=='5037' or $login_posto=='595'))) {
 	//echo "OS cadastro tudo";

	include 'os_cadastro_tudo.php';
	exit;
}


function checaCPF($cpf,$return_str = true) {
	global $cook_idioma, $login_pais, $con;    // Para conectar com o banco...
	$cpf = preg_replace("/\D/","",$cpf);   // Limpa o CPF
	if (strlen($login_pais)>0 and $login_pais != "BR") return $cpf;
	if (!$cpf or $cpf == '' or (strlen($cpf) != 11 and strlen($cpf) != 14)) return false;

	if(strlen($cpf) > 0){
		$res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
		if ($res_cpf === false) {
			return ($return_str) ? pg_last_error($con) : false;
		}
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
	$tipo_atendimento = $_POST["tipo_atendimento"];

      if($login_fabrica == 20 and ($tipo_atendimento == 13 or $tipo_atendimento == 66)){

		$dados = array();
		$motivo_ordem     = utf8_encode(trim($_POST["motivo_ordem"]));

        if(empty($motivo_ordem) AND $sistema_lingua <>'ES') {
          $msg_erro .= "O campo Motivo Ordem é obrigatório \n";
        }

		$dados['motivo_ordem'] = $motivo_ordem;

		if($motivo_ordem == 'Pecas nao disponiveis em estoque (XSS)'){

			$mostrar['peca_nao_disponivel'] = 'block';

			// $dados['codigo_peca_1']  = retira_acentos($_POST["codigo_peca_1"]);
			// $dados['codigo_peca_2']  = retira_acentos($_POST["codigo_peca_2"]);
			// $dados['codigo_peca_3']  = retira_acentos($_POST["codigo_peca_3"]);
			// $dados['numero_pedido_1']  = retira_acentos($_POST["numero_pedido_1"]);
			// $dados['numero_pedido_2']  = retira_acentos($_POST["numero_pedido_2"]);
			// $dados['numero_pedido_3']  = retira_acentos($_POST["numero_pedido_3"]);
			$dados['codigo_peca_1']  = utf8_encode($_POST["codigo_peca_1"]);
			$dados['codigo_peca_2']  = utf8_encode($_POST["codigo_peca_2"]);
			$dados['codigo_peca_3']  = utf8_encode($_POST["codigo_peca_3"]);
			$dados['numero_pedido_1']  = utf8_encode($_POST["numero_pedido_1"]);
			$dados['numero_pedido_2']  = utf8_encode($_POST["numero_pedido_2"]);
			$dados['numero_pedido_3']  = utf8_encode($_POST["numero_pedido_3"]);

			if(strlen(trim($dados['codigo_peca_1']))==0 and strlen(trim($dados['codigo_peca_2']))==0 and strlen(trim($dados['codigo_peca_3']))==0){
				$msg_erro .= "Por favor informar o código da peça. <Br>";
			}
		}
		if($motivo_ordem == 'Nao existem pecas de reposicao (nao definidas) (XSD)'){
			// $dados['descricao_peca_1'] = retira_acentos($_POST["descricao_peca_1"]);
			// $dados['descricao_peca_2'] = retira_acentos($_POST["descricao_peca_2"]);
			// $dados['descricao_peca_3'] = retira_acentos($_POST["descricao_peca_3"]);
			$dados['descricao_peca_1'] = utf8_encode($_POST["descricao_peca_1"]);
			$dados['descricao_peca_2'] = utf8_encode($_POST["descricao_peca_2"]);
			$dados['descricao_peca_3'] = utf8_encode($_POST["descricao_peca_3"]);

			if(strlen(trim($dados['descricao_peca_1']))==0 and strlen(trim($dados['descricao_peca_2']))==0 and strlen(trim($dados['descricao_peca_3']))==0){
				$msg_erro .= "Por favor informar a descrição da peça. <Br>";
			}
		}
		if($motivo_ordem == 'PROCON (XLR)'){
			#$dados['protocolo'] = retira_acentos($_POST["protocolo"]);
			$dados['protocolo'] = utf8_encode($_POST["protocolo"]);
		}
		if($motivo_ordem == 'Solicitacao de Fabrica (XQR)'){
			#$dados['ci_solicitante'] = retira_acentos($_POST["ci_solicitante"]);
			$dados['ci_solicitante'] = utf8_encode($_POST["ci_solicitante"]);
		}
		if($motivo_ordem == "Linha de Medicao (XSD)"){
			#$dados['linha_medicao'] = retira_acentos($_POST["linha_medicao"]);
			$dados['linha_medicao'] = utf8_encode($_POST["linha_medicao"]);
		}
		if($motivo_ordem == 'Pedido nao fornecido - Valor Minimo (XSS)'){
			#$dados['pedido_nao_fornecido'] = retira_acentos($_POST["pedido_nao_fornecido"]);
			$dados['pedido_nao_fornecido'] = utf8_encode($_POST["pedido_nao_fornecido"]);
		}

		if($motivo_ordem == 'Contato SAC (XLR)'){ //HD-3200578
			if(strlen(trim($_POST['contato_sac'])) == 0){
				$msg_erro .= "Campo N° do Chamado é obrigatório";
			}else{
				$dados['contato_sac'] = utf8_encode($_POST['contato_sac']);
			}
		}

		if($motivo_ordem == 'Bloqueio financeiro (XSS)' OR $motivo_ordem == 'Ameaca de Procon (XLR)' OR $motivo_ordem == 'Defeito reincidente (XQR)'){//HD-3200578

			if(strlen(trim($_POST['detalhe'])) == 0){
				$msg_erro .= "Campo Detalhe é obrigatório";
			}else{
				$dados['detalhe'] = utf8_encode($_POST['detalhe']);
			}
		}
		extract($dados);
	}

//MLG 06/12/2010 - HD 326935 - Limitar por HTML e PHP o comprimento das strings para campos varchar(x).
	$_POST['consumidor_bairro']          = substr($_POST['consumidor_bairro'],          0,  80);
	$_POST['consumidor_celular']         = substr($_POST['consumidor_celular'],         0,  20);
	$_POST['consumidor_cep']             = substr(preg_replace('/\D/', '', $_POST['consumidor_cep']),0, 8);
	$_POST['consumidor_cpf']             = substr(preg_replace('/\D/', '', $_POST['consumidor_cpf']),0, 14);
	$_POST['consumidor_cidade']          = substr($_POST['consumidor_cidade'],          0,  70);
	$_POST['consumidor_complemento']     = substr($_POST['consumidor_complemento'],     0,  20);
	$_POST['consumidor_email']           = substr($_POST['consumidor_email'],           0,  50);
	$_POST['consumidor_estado']          = substr($_POST['consumidor_estado'],          0,  2);
	$_POST['consumidor_fone']            = substr($_POST['consumidor_fone'],            0,  20);
	$_POST['consumidor_fone']            = str_replace(" ", "", $_POST['consumidor_fone']);
	$_POST['consumidor_fone']            = str_replace("-", "", $_POST['consumidor_fone']);
	$_POST['consumidor_fone_comercial']  = substr($_POST['consumidor_fone_comercial'],  0,  20);
	$_POST['consumidor_fone_recado']     = substr($_POST['consumidor_fone_recado'],     0,  20);
	$_POST['consumidor_nome']            = substr($_POST['consumidor_nome'],            0,  50);
	$_POST['consumidor_nome_assinatura'] = substr($_POST['consumidor_nome_assinatura'], 0,  50);
	$_POST['consumidor_numero']          = substr($_POST['consumidor_numero'],          0,  20);
	$_POST['consumidor_revenda']         = substr($_POST['consumidor_revenda'],         0,  1);
	$_POST['revenda_bairro']             = substr($_POST['revenda_bairro'],             0,  80);
	$_POST['revenda_cep']                = substr($_POST['revenda_cep'],                0,  8);
	$_POST['revenda_cnpj']               = substr(preg_replace('/\D/', '', $_POST['revenda_cnpj']), 0, 14);
	$_POST['revenda_complemento']        = substr($_POST['revenda_complemento'],        0,  30);
	$_POST['revenda_email']              = substr($_POST['revenda_email'],              0,  50);
	$_POST['revenda_endereco']           = substr($_POST['revenda_endereco'],           0,  60);
	$_POST['revenda_fone']               = substr($_POST['revenda_fone'],               0,  20);
	$_POST['revenda_nome']               = substr($_POST['revenda_nome'],               0,  50);
	$_POST['revenda_numero']             = substr($_POST['revenda_numero'],             0,  20);
	$_POST['natureza_servico']           = substr($_POST['natureza_servico'],           0,  20);
	$_POST['nota_fiscal']                = substr($_POST['nota_fiscal'],                0,  20);
	$_POST['nota_fiscal_saida']          = substr($_POST['nota_fiscal_saida'],          0,  20);
	$_POST['prateleira_box']             = substr($_POST['prateleira_box'],             0,  10);
	$_POST['produto_voltagem']           = substr($_POST['produto_voltagem'],           0,  20);
	$_POST['produto_serie']              = substr($_POST['produto_serie'],              0,  20);
	$_POST['tipo_os_cortesia']           = substr($_POST['tipo_os_cortesia'],           0,  20);
	$_POST['type']                       = substr($_POST['type'],                       0,  10);
	$_POST['veiculo']                    = substr($_POST['veiculo'],                    0,  20);
	$_POST['versao']                     = substr($_POST['versao'],                     0,  20);
	$_POST['produto_voltagem']           = substr($_POST['produto_voltagem'],           0,  20);
	$_POST['produto_serie']              = substr($_POST['produto_serie'],              0,  20);
	$_POST['codigo_posto']               = substr($_POST['codigo_posto'],               0,  20);



// if($btn_acao and $login_posto = 6359) die(nl2br(print_r($_POST, true)));
	$sua_os_offline = pg_quote(check_post_field('sua_os_offline'));

	$sua_os = check_post_field('sua_os');
	if ($sua_os == 'null') {
		if ($pedir_sua_os == 't') {
			$msg_erro .= traduz('digite.o.numero.da.os.fabricante', $con);
		}
	} else {
		//WELLINGTON 04/01/2007
		 if ( in_array($login_fabrica, array(1,3,11,172)) ) {
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
		$msg_erro = traduz(array('por.favor','selecione','o','tipo.de.atendimento'), $con);
	}

	$segmento_atuacao = check_post_field('segmento_atuacao');

	/*HD: 87459*/
	/* RETIRADO: AND ($login_pais=='BR' or $login_pais=='CO')*/
	if(($tipo_atendimento=='15' or $tipo_atendimento=='16')){
		if (strlen($promotor_treinamento) > 0) {
			if($login_pais=='BR') $x_promotor_treinamento = "$promotor_treinamento";
			else $x_promotor_treinamento  = "null";
		}else{
			$msg_erro = traduz('selecione.o.promotor.que.autorizou.a.cortesia', $con);
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
				$msg_erro = traduz('selecione.o.promotor.que.autorizou.a.cortesia', $con);
			}
		}
	}

	$produto_referencia = check_post_field('produto_referencia',false);
	$produto_referencia = strtoupper(trim($_POST['produto_referencia']));

	//BOSCH - REGRAS DE VALICAÇÃO
	//regra: caso ele escolha um dois tipos de atendimento abaixo o produto vai ser sempre o designado
	if($login_fabrica ==20 and $produto_referencia !== false) {
		if($tipo_atendimento==11){    //garantia de peças
			$produto_referencia = '0000002';
			$xproduto_serie     = '999000000'; // HD 1389784
		}
		if($tipo_atendimento==12){    //garantia de acessórios
			$produto_referencia = '0000001';

			//Comentado no chamado 3120742 a pedido da Bosch
			//$xproduto_serie     = '999';
		}
	}
	//BOSCH - REGRAS DE VALICAÇÃO

	if ($produto_referencia === false) {
		$produto_referencia = 'null';
		$msg_erro .= traduz('digite.o.produto', $con);
	}

	if($login_fabrica==20) $xdata_abertura = fnc_formata_data_hora_pg(trim($_POST['data_abertura']));
	else                   $xdata_abertura = fnc_formata_data_pg(trim($_POST['data_abertura']));

	if (substr($xdata_abertura,1,4) < 2000){
	if($sistema_lingua == "ES")  $msg_erro .= " Fecha de abertura inválida";
	else $msg_erro .= " Data de abertura inválida.";
	}

	if ($xdata_abertura == 'null') {
		$msg_erro .= traduz('digite.a.data.de.abertura.da.os', $con);
	}
	$cdata_abertura = str_replace("'","",$xdata_abertura);

#   Dados do consumidor
	$xconsumidor_nome	= pg_quote(check_post_field('consumidor_nome',	'null'));
	if ($login_fabrica == 15 and $xconsumidor_nome == 'null') {
		$msg_erro = traduz('digite.o.nome.do.consumidor', $con);
	}

//  11/12/2009 - MLG - HD 175044 - Adiciona validação de CPF se for digitado. Se não for digitado, não confere.
    $cpf_valido = false;
	$valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$consumidor_cpf));
	if(empty($valida_cpf_cnpj)){
		if (strlen(trim($consumidor_cpf)) != 0) {
			if (!is_bool(checaCPF($consumidor_cpf, false))) {
				$consumidor_cpf = checaCPF($consumidor_cpf);
				$cpf_valido = true;
			} else {
				$msg_erro .= "CPF/CNPJ do cliente inválido<br>";
			}
		}
	}else{
		$msg_erro .= $valida_cpf_cnpj."<br />";
	}
	$xconsumidor_cpf	= ($cpf_valido) ? "'$consumidor_cpf'" : 'null';
	$xconsumidor_cidade	= pg_quote(check_post_field('consumidor_cidade'));
	$xconsumidor_estado	= pg_quote(check_post_field('consumidor_estado'));
	$xconsumidor_fone	= pg_quote(check_post_field('consumidor_fone'));
	$xconsumidor_nome	= pg_quote(check_post_field('consumidor_nome'));
	$xconsumidor_nome	= pg_quote(check_post_field('consumidor_nome'));

	if ($login_fabrica == 20 and $login_pais == 'BR'){ #HD 157034
		$sql = "SELECT	tbl_cliente.cliente
				FROM tbl_cliente
				LEFT JOIN tbl_cidade
				USING (cidade)
				WHERE tbl_cliente.cpf = $xconsumidor_cpf";
		$res = pg_query ($con,$sql);

		if (pg_num_rows ($res) == 0){

			$sql = "SELECT fnc_qual_cidade (contato_cidade,contato_estado) FROM tbl_posto_fabrica WHERE posto = $login_posto and fabrica= $login_fabrica";
			$res = pg_query ($con,$sql);

			if(pg_num_rows($res) > 0) {
				$xconsumidor_cidade2 = pg_fetch_result($res,0,0);

				$sql = "INSERT INTO tbl_cliente
							(nome,cpf,fone,cidade)
						VALUES
							($xconsumidor_nome, $xconsumidor_cpf, $xconsumidor_fone, $xconsumidor_cidade2) ";
				$res = pg_query ($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
		}
	}

#   Dados da revenda
	$xrevenda_nome	= pg_quote(check_post_field('revenda_nome'));
	$valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$_POST['revenda_cnpj']));
	if(empty($valida_cpf_cnpj)){
		$revenda_cnpj	= checaCPF(check_post_field('revenda_cnpj','null'),false);
		if (!is_bool($revenda_cnpj)) {
			$xrevenda_cnpj = "'$revenda_cnpj'";
			$cnpj_valido = true;
		} else {
			$xrevenda_cnpj = 'null';
			$cnpj_valido = false;
			if ($_POST['revenda_cnpj'] != '') $msg_erro .= "CPF/CNPJ da revenda inválido<br>";  // Só se digitou
		}
	}else{

		$msg_erro .= $valida_cpf_cnpj."<br />";
	}
// if ($login_posto==6359) echo "<pre>$login_pais - $revenda_cnpj.</pre>";
	$xrevenda_fone	= pg_quote(check_post_field('revenda_fone'));
	$xnota_fiscal	= pg_quote(check_post_field('nota_fiscal'));
	if ($login_fabrica == 8) {
		if ($xnota_fiscal == 'null' ) { // hD 64397
			$msg_erro .= traduz('digite.o.numero.da.nota.fiscal', $con);
		}
	}

	$qtde_produtos	= check_post_field('qtde_produtos',"1");
	$xtroca_faturada= check_post_field('troca_faturada');

	//pedido por Leandro Tectoy, feito por takashi 02/08 Alterado por Raphael para a bosch tb
	if(($login_fabrica==20 AND $tipo_atendimento <> 66) or $login_fabrica == 8){ // hD 64397
		if (strlen ($_POST['data_nf']) == 0) {
			$msg_erro = traduz('digite.a.data.de.compra', $con);
		}
	}

	//pedido por Leandro tectoy, feito por takashi 04/08
	if ($login_fabrica == 20 and $tipo_atendimento == 66) {
		$xdata_nf = $_POST['data_nf'];
		if (strlen($xdata_nf == 0)) {
			$xdata_nf = "null";
		} else {
			$xdata_nf = fnc_formata_data_pg(trim($xdata_nf));
			list($yi, $mi, $di) = explode("-", str_replace("'","",$xdata_nf));
			if(!checkdate($mi,$di,$yi))
				$msg_erro .= traduz('data.invalida', $con);
		}
	} else {
		$xdata_nf = fnc_formata_data_pg(trim($_POST['data_nf']));
		if ($xdata_nf == null AND $xtroca_faturada <> 't') $msg_erro .= traduz('digite.a.data.de.compra', $con);

		if(strlen($msg_erro)==0){
			list($yi, $mi, $di) = explode("-", str_replace("'","",$xdata_nf));
			if(!checkdate($mi,$di,$yi))
				$msg_erro .= traduz('data.invalida', $con);
		}
	}

#   Dados do produto
	$xcodigo_fabricacao				= pg_quote(check_post_field('codigo_fabricacao'));
	$xaparencia_produto				= pg_quote(check_post_field('aparencia_produto'));
	$xacessorios					= pg_quote(check_post_field('acessorios'));
	$xdefeito_reclamado_descricao	= pg_quote(check_post_field('defeito_reclamado_descricao'));
	$xdefeito_reclamado				= pg_quote(check_post_field('defeito_reclamado'));
	$xobs							= pg_quote(check_post_field('obs'));
	$xquem_abriu_chamado			= pg_quote(check_post_field('quem_abriu_chamado'));

	if (empty($xproduto_serie)) {
		$xproduto_serie = pg_quote(
			check_post_field('produto_serie'),
			($login_fabrica != 52) // String para todas, menos para a Fricon...??
		);
	}

	if (check_post_field('consumidor_revenda',false) === false){
		 $msg_erro .= traduz('selecione.consumidor.ou.revenda', $con);
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
			// HD 214509 foi solicitado alteração de 100 dias para 360 dias (ESTE VALOR É FIXO) EDUARDO 25-03-2010
			// HD 242952 foi solicitado para desfazer a alteração do HD 214509
			$bosch_data_ab = is_date($data_nf);
			$bosch_nf_data = is_date($bosch_data_ab . ' + 100 dias');

			if($bosch_data_abertura >$bosch_nf_data)
				$msg_erro = traduz('prazo.de.garantia.de.conserto.expirado', $con);
		}
		if ($tipo_atendimento==15) {
			$sql = "SELECT garantia FROM tbl_produto WHERE tbl_produto.referencia = '$produto_referencia'";
			$res = @pg_query ($con,$sql);
			if (@pg_num_rows($res) == 0) {
				$msg_erro = traduz('produto.%.sem.garantia', $con, $cook_idioma, $produto_referencia);
			}

			if (strlen($msg_erro) == 0) {
				$garantia = trim(@pg_fetch_result($res,0,garantia));
				//$sql = "SELECT ($xdata_nf::date + (($garantia || ' months')::interval))::date;";
				$sql = "SELECT $xdata_nf::DATE + INTERVAL '$garantia MONTHS'";
				$res = @pg_query ($con,$sql);
				$data_final_garantia = trim(pg_fetch_result($res,0,0));

				//echo "$data_final_garantia > $cdata_abertura";
				if($data_final_garantia >$cdata_abertura){
					$msg_erro= traduz(array('o.produto.ainda.esta.na.garantia','nao.pode.ser.do.tipo.cortesia'), $con);
				}
			}
		}

		// HD 3032759 - Anexo de foto de NS produto para auditoria
		if ($anexaFotoSerie(compact('produto_serie', 'tipo_atendimento'))) {
			// anexo obrigatório
			if (!$_FILES['anexo_serie']['tmp_name'] and !$_POST['anexo_serie']) {
				$msg_erro .= '<br/>'.traduz('foto.do.num.de.serie.ausente/ilegivel.e.obrigatoria', $con);
			} else {
				if ($_POST['anexo_serie']) {
					$_POST['anexo_serie'] = $anexo_serie = stripslashes($_POST['anexo_serie']);
					$fileData = json_decode($anexo_serie, true);
					$anexoID  = $fileData['tdocs_id'];
				}

				// Se enviou um outro arquivo, este substitui o anterior
				if ($_FILES['anexo_serie']['tmp_name']) {

					// HD 3032759 - Salvar dados do anexo
					$tDocs   = new TDocs($con, $login_fabrica);

					// Exclui o anterior, pois não será usado
					if ($anexoID) {
						$idExcluir = $anexoID;
					}

					$anexoID = $tDocs->sendFile($_FILES['anexo_serie']);

					if (!$anexoID) {
						$msg_erro .= 'Erro ao salvar o arquivo!';
					} else {
						// Se ocorrer algum erro, o anexo está salvo:
						$_POST['anexo_serie'] = json_encode($tDocs->sentData);

						if (isset($idExcluir))
							$tDocs->deleteFileById($idExcluir);
					}
				}
				$anexoURL = '//api2.telecontrol.com.br/tdocs/document/id/'.$anexoID;
				// pre_echo($fileData, $anexoURL, true);
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
					$msg_erro .= traduz('n.de.serie.%.digitado.e.reincidente.%.favor.consultar.a.ordem.de.servico.%.e.acrescentar.itens.%.em.caso.de.duvida.entre.em.contato.com.a.fabrica.', $con, array($produto_serie, '<br />', $xxxsua_os, '<br />'));
				}else{
					$os_reincidente = "'t'";
				}
			}
		}
	}

	$produto = 0;

	$voltagem = pg_quote(check_post_field('produto_voltagem',"null"));

	$sql = "SELECT tbl_produto.produto, tbl_produto.linha
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  UPPER(trim(tbl_produto.referencia_pesquisa)) = UPPER('$produto_referencia')
			AND    tbl_linha.fabrica      = $login_fabrica
			AND    tbl_produto.ativo IS TRUE";

	$res = @pg_query ($con,$sql);

	if (@pg_num_rows ($res) == 0) {
		$msg_erro = traduz('produto.%.nao.cadastrado', $con, $cook_idioma, array($produto_referencia));
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
					$msg_erro = traduz('produto.%.sem.garantia', $con, $cook_idioma, array($produto_referencia));
				}

				if (strlen($msg_erro) == 0) {
					$garantia = trim(@pg_fetch_result($res,0,garantia));

					$sql = "SELECT ($xdata_nf::date + (($garantia || ' months')::interval))::date;";
					$res = @pg_query ($con,$sql);
					if (strlen (pg_errormessage($con)) > 0) {
						$msg_erro = pg_errormessage($con);
					}

					if (strlen($msg_erro) > 0) $msg_erro = traduz('data.nf.invalida', $con);

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

	$produto_referencia = pg_quote(strtoupper(preg_replace("/\W/","",$produto_referencia)));

	$xtipo_os_cortesia = 'null';

	#----------- OS digitada pelo Distribuidor -----------------
	$digitacao_distribuidor = "null";
	if ($distribuidor_digita == 't'){
		$codigo_posto = strtoupper(preg_replace("/\W/","",trim ($_POST['codigo_posto'])));

		if (strlen ($codigo_posto) > 0) {
			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto' AND credenciamento = 'CREDENCIADO'";
			$res = @pg_query($con,$sql);
			if (pg_num_rows ($res) <> 1) {
				$msg_erro = traduz('posto.%.nao.cadastrado', $con, $cook_idioma, array($codigo_posto));
				$posto = $login_posto;
			}else{
				$posto = pg_fetch_result ($res,0,0);
				if ($posto <> $login_poso) {
					$sql = "SELECT posto FROM tbl_posto_linha WHERE posto = $posto AND distribuidor = $login_posto AND linha = $linha";
					$res = @pg_query($con,$sql);
					if (pg_num_rows ($res) <> 1) {
						$msg_erro = traduz('posto.%.nao.pertence.a.sua.regiao', $con, $cook_idioma, array($codigo_posto));
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

		/*================ INSERE NOVA OS =========================*/

		if (strlen($os) == 0) {
			$inserir = true;
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
			$inserir = false;
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
		#echo nl2br($sql);exit;
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

		if($login_fabrica == 20){
			if (strlen($os) > 0){
				$sql = "SELECT tbl_os_campo_extra.campos_adicionais
			                FROM tbl_os_campo_extra
		        	        WHERE os = $os
			                AND fabrica = $login_fabrica";
	        		$res = pg_query($con,$sql);
			        $msg_erro .= pg_errormessage($con);
			        if(pg_num_rows($res) > 0){
			          $res_adicionais = pg_result($res,0,campos_adicionais);
                                  $adicionais = pg_result($res,0,campos_adicionais);
			          $adicionais = json_decode($adicionais, true);

			          $campos_adicionais = array_merge($adicionais, $dados);

	        		}else{
	        		 	$campos_adicionais = $dados;
	        		}
	        	$campos_adicionais = str_replace("\\", "\\\\", json_encode($campos_adicionais));
	        	#$campos_adicionais = json_encode($campos_adicionais);

	        	if(pg_num_rows($res) == 0){
					$sql_campo_extra = "INSERT INTO tbl_os_campo_extra (fabrica, os, campos_adicionais) values ($login_fabrica, $os, '$campos_adicionais')";
				}else{
					$sql_campo_extra = "UPDATE tbl_os_campo_extra SET campos_adicionais = '$campos_adicionais'
					                    WHERE os = $os
					                    AND fabrica = $login_fabrica";
					}
				$res_campo_extra = pg_query($con, $sql_campo_extra);
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

			if (strlen($x_promotor_treinamento)>0 AND $x_promotor_treinamento <> "null") {
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
				if(pg_num_rows($res) == 0 OR $status_os == "94"){

					$msg_status = traduz('os.aguardando.aprovacao.do.promotor', $con);

					$sql = "INSERT INTO tbl_os_status (os,status_os,observacao) VALUES ($os, 92, '$msg_status')";
					$res = @pg_query ($con,$sql);

				}
			}

			if (strlen ($msg_erro) == 0) {
				$res = @pg_query ($con,"COMMIT TRANSACTION");
				include_once 'class/email/mailer/class.phpmailer.php';
				$mailer = new PHPMailer();


//BOSCH - ENVIAR EMAIL
				if($login_fabrica == 20){

					if($login_pais == 'BR') {

						// HD 3032759 - Anexo de foto de NS produto para auditoria
						// aqui associa o anexo com a OS
						if (is_object($tDocs) and $anexoID) {
							$fileData['name'] = $os.'_foto_serie.' . pathinfo($fileData['name'], PATHINFO_EXTENSION);
							$tDocs->setDocumentReference($tDocs->sentData, $os, 'anexar', true, 'osserie');
						} elseif (isset($_POST['anexo_serie'])) {
							$tDocs = new TDocs($con, $login_fabrica);

							$fileData = json_decode($_POST['anexo_serie'], true);
							$fileData['name'] = $os.'_foto_serie.' . pathinfo($fileData['name'], PATHINFO_EXTENSION);

							$tDocs->setContext('osserie');
							$tDocs->setDocumentReference($fileData, $os);
						}

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
							WHERE os = $os and tipo_atendimento IN (15,16,13,66)";
						$res = pg_query($con,$sql);
						if (pg_num_rows($res) > 0) {
							$posto_nome      = trim(pg_fetch_result($res,0,nome));
							$codigo_posto    = trim(pg_fetch_result($res,0,codigo_posto));
							$consumidor_nome = trim(pg_fetch_result($res,0,consumidor_nome));
							$produto_ref     = trim(pg_fetch_result($res,0,referencia));
							$produto_nome    = trim(pg_fetch_result($res,0,descricao));

							$mailer = new PHPMailer();

							if( strlen($x_promotor_treinamento) > 0  and $x_promotor_treinamento <>'null'){
								$sql = "SELECT email,nome FROM tbl_promotor_treinamento WHERE promotor_treinamento = $x_promotor_treinamento;";
								$res2 = pg_query($con,$sql);
								$promotor_nome  = trim(pg_fetch_result($res2,0,nome));
								$promotor_email = trim(pg_fetch_result($res2,0,email));

								if(strlen($promotor_email) > 0 ){
									$email_origem  = "helpdesk@telecontrol.com.br";
									$new_email[]   = "helpdesk@telecontrol.com.br";
									$email_destino = $promotor_email;
									$new_email[]   = $promotor_email;
									$assunto       = "Nova OS de Cortesia";

									#Liberado: HD 18323
									if ($tipo_atendimento == 13)
									{
											$assunto = "Solicitação de Troca de Produto";
											$corpo ="<br>Caro promotor $promotor_nome,<br>\n\n";
											$corpo.="<br>O posto autorizado <b>$posto_nome</b>, código $codigo_posto, acaba cadastrar uma troca de produto e necessita de sua autorização.\n\n";
											$corpo.="<br>Troca de produto para o consumidor <b>$consumidor_nome</b> referente a máquina: <b>$produto_ref - $produto_nome</b>\n";
											$corpo.="<br><br>Para aprovar / recusar a OS, acesse o sistema ASSIST , MENU CallCenter / Aprovação de Troca. O número da OS é <b>$os</b>\n";
									}
									else if ($tipo_atendimento == 66)
									{
										$assunto = "Solicitação de Troca de Produto Fora da Garantia";
										$corpo   = "<p>
														Caro promotor $promotor_nome, <br />
														O posto autorizado $posto_nome, código $codigo_posto, acaba de cadastrar uma Troca de Produto Fora da Garantia e necessita de sua autorização.
													</p>
													<p>
														Troca de Produto Fora da Garantia para o consumidor $consumidor_nome referente ao produto: $produto_ref - $produto_nome
													</p>
													<p>
														Para aprovar / recusar a OS, acesse o sistema ASSIST , MENU CallCenter / Aprovação de Troca. O número da OS é $os.
													</p>";
									}
									else
									{
										if ($login_posto == '6359' OR 1 == 1){

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

									// if ( @mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
									// 	$enviou = 'ok';
									// }
									$mailer->IsHTML();
									$mailer->AddAddress($email_destino);
									$mailer->Subject 	= $assunto;
									$mailer->Body 		= $corpo;
									$mailer->From     	= $email_origem;
									$mailer->FromName 	= "Bosch";

									if(!$mailer->Send()){
										$cabecalho  = "MIME-Version: 1.0 \r";
										$cabecalho .= "Content-type: text/html; charset=iso-8859-1 \r";
										$cabecalho .= "From: helpdesk@telecontrol.com.br";

										$xemail_destino  = implode(",", $new_email);

										if( in_array($login_fabrica,array(24,35,81,86))){

											switch ($login_fabrica) {
												case 24:
													$username = 'tc.sac.suggar@gmail.com';
													$senha = 'tcsuggar';
													break;
												case 35:
													$username = 'tc.sac.cadence@gmail.com';
													$senha = 'tccadence';
													break;
												case 81:
													$username = 'tc.sac.bestway@gmail.com';
													$senha = 'tcbestway';
													break;
												case 86:
													$username = 'tc.sac.famastil@gmail.com';
													$senha = 'tcfamastil';
													break;
											}


										    $mailer = new PhpMailer(true);

										    $mailer->IsSMTP();
										    $mailer->Mailer = "smtp";

										    $mailer->Host = 'ssl://smtp.gmail.com';
										    $mailer->Port = '465';
										    $mailer->SMTPAuth = true;

										    $mailer->Username = $username;
										    $mailer->Password = $senha;
										    $mailer->SetFrom($username, $username);
										    $mailer->AddAddress($xemail_destino,$xemail_destino );
										    $mailer->Subject = utf8_encode($assunto);
										    $mailer->Body = utf8_encode($corpo);

										    try{
												$mailer->Send();
										    }catch(Exception $e){

										    }

										}else{
											mail($xemail_destino, utf8_encode($assunto), utf8_encode($corpo), $cabecalho);
										}

									}
									else
									{
										$mailer->ClearAddresses();
										$enviou = "ok";
									}
								}
							}

							if ($enviou == "ok")
							{
								header ("Location: os_cadastro_adicional.php?os=$os");
								exit;
							}
						}
					}else {
						if(!empty($x_promotor_treinamento)){
						$sql = "SELECT email,nome FROM tbl_promotor_treinamento WHERE promotor_treinamento = $x_promotor_treinamento;";
						$res2 = pg_query($con,$sql);
						$promotor_nome  = trim(pg_fetch_result($res2,0,nome));
						$promotor_email = trim(pg_fetch_result($res2,0,email));
						}

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
							$email_destino = $promotor_email;
							$assunto       = "Nueva OS de Cortesía";

							$corpo ="<br>Estimado $promotor_nome,<br>\n\n";
							$corpo.="<br>El servicio autorizado <b>$codigo_posto - $posto_nome</b>, ha catastrado una cortesía comercial y necesita de su autorización.\n\n";
							$corpo.="<br>Cortesía para el cliente <b>$consumidor_nome</b> referenta a la herramienta: <b>$produto_ref - $produto_nome.</b>\n";
							$corpo.="El número de OS es <b>$os</b>\n";

							$cabecalho  = "MIME-Version: 1.0 \r";
							$cabecalho .= "Content-type: text/html; charset=iso-8859-1 \r";
							$cabecalho .= "From: helpdesk@telecontrol.com.br";


							if( in_array($login_fabrica,array(24,35,81,86))){

								switch ($login_fabrica) {
									case 24:
										$username = 'tc.sac.suggar@gmail.com';
										$senha = 'tcsuggar';
										break;
									case 35:
										$username = 'tc.sac.cadence@gmail.com';
										$senha = 'tccadence';
										break;
									case 81:
										$username = 'tc.sac.bestway@gmail.com';
										$senha = 'tcbestway';
										break;
									case 86:
										$username = 'tc.sac.famastil@gmail.com';
										$senha = 'tcfamastil';
										break;
								}


							    $mailer = new PhpMailer(true);

							    $mailer->IsSMTP();
							    $mailer->Mailer = "smtp";

							    $mailer->Host = 'ssl://smtp.gmail.com';
							    $mailer->Port = '465';
							    $mailer->SMTPAuth = true;

							    $mailer->Username = $username;
							    $mailer->Password = $senha;
							    $mailer->SetFrom($username, $username);
							    $mailer->AddAddress($email_destino,$email_destino );
							    $mailer->Subject = utf8_encode($assunto);
							    $mailer->Body = utf8_encode($corpo);

							    try{
									$mailer->Send();
							    }catch(Exception $e){

							    }

							}else{
								mail($email_destino, utf8_encode($assunto), utf8_encode($corpo), $cabecalho);
							}
						}
					}
				}

				// se o produto tiver TROCA OBRIGATORIA, bloqueia a OS para intervencao da fabrica // fabio 17/01/2007
				if ($login_fabrica == 3){
					$sql = "SELECT  troca_obrigatoria
							FROM    tbl_produto
							WHERE   upper(tbl_produto.referencia) = upper($produto_referencia)";
					$res = @pg_query($con,$sql);
					if (pg_num_rows($res) > 0) {
						$troca_obrigatoria = trim(pg_fetch_result($res,0,'troca_obrigatoria'));
						if ($troca_obrigatoria == 't') {
							$sql_intervencao = "SELECT * FROM  tbl_os_status WHERE os=$os AND status_os=62";
							$res_intervencao = pg_query($con, $sql_intervencao);
							if (pg_num_rows ($res_intervencao) == 0){
								$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,62,current_timestamp,'O Produto desta O.S. necessita de troca.')";
								$res = @pg_query ($con,$sql);
								$msg_intervencao .= "<br>O produto $produto_referencia precisa de Intervenção da Assistência Técnica da Fábrica. Aguarde o contato da fábrica";
							}
							// envia email teste para avisar
							$email_origem  = "helpdesk@telecontrol.com.br";
							$email_destino = "helpdesk@telecontrol.com.br";//"fabio@telecontrol.com.br";
							$assunto       = "TROCA OBRIGATORIA - OS cadastrada";
							$corpo.="<br>OS: $os \n";
							$body_top = "--Message-Boundary\n";
							$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
							$body_top .= "Content-transfer-encoding: 7BIT\n";
							$body_top .= "Content-description: Mail message body\n\n";

							if( in_array($login_fabrica,array(24,35,81,86))){

								switch ($login_fabrica) {
									case 24:
										$username = 'tc.sac.suggar@gmail.com';
										$senha = 'tcsuggar';
										break;
									case 35:
										$username = 'tc.sac.cadence@gmail.com';
										$senha = 'tccadence';
										break;
									case 81:
										$username = 'tc.sac.bestway@gmail.com';
										$senha = 'tcbestway';
										break;
									case 86:
										$username = 'tc.sac.famastil@gmail.com';
										$senha = 'tcfamastil';
										break;
								}


							    $mailer = new PhpMailer(true);

							    $mailer->IsSMTP();
							    $mailer->Mailer = "smtp";

							    $mailer->Host = 'ssl://smtp.gmail.com';
							    $mailer->Port = '465';
							    $mailer->SMTPAuth = true;

							    $mailer->Username = $username;
							    $mailer->Password = $senha;
							    $mailer->SetFrom($email_origem, $email_origem);
							    $mailer->AddAddress($email_destino,$email_destino );
							    $mailer->Subject = utf8_encode($assunto);
							    $mailer->Body = utf8_encode($corpo);

							    try{
									$mailer->Send();
							    }catch(Exception $e){

							    }

							}else{
								@mail($email_destino, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem);
							}
							// fim
						}
					}
				}// fim TROCA OBRIGATORIA


				if($login_fabrica == 20 and $reabrir='ok'){
					header ("Location: os_cadastro_adicional.php?os=$os&reabrir=ok");exit;
				}
				header ("Location: os_cadastro_adicional.php?os=$os");
				exit('Aqui');
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
$os = getPost('os');

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
					tbl_os.acessorios                                                ,
					tbl_os.laudo_tecnico                                             ,
					tbl_os.tipo_os_cortesia                                          ,
					tbl_os.serie                                                     ,
					tbl_os.qtde_produtos                                             ,
					tbl_os.troca_faturada                                            ,
					tbl_os.tipo_os                                                   ,
					tbl_os.tipo_atendimento                                          ,
					tbl_os.segmento_atuacao                                          ,
					tbl_os.promotor_treinamento                                      ,
					tbl_os.excluida 												 ,
					tbl_produto.produto                                              ,
					tbl_produto.referencia                     AS produto_referencia ,
					tbl_produto.descricao                      AS produto_descricao  ,
					tbl_produto_idioma.descricao               AS produto_traducao   ,
					tbl_produto.voltagem                       AS produto_voltagem   ,
					tbl_posto_fabrica.codigo_posto,
					tbl_os_campo_extra.campos_adicionais AS adicionais
			FROM tbl_os
			JOIN      tbl_produto       USING(produto)
			JOIN      tbl_posto_fabrica USING(fabrica, posto)
			LEFT JOIN tbl_os_extra      USING(os)
			LEFT JOIN tbl_os_campo_extra USING(os)
			LEFT JOIN tbl_produto_idioma ON tbl_produto_idioma.produto = tbl_produto.produto
                                        AND tbl_produto_idioma.idioma  = UPPER(SUBSTR('ES', 1, 2))
			WHERE tbl_os.os = $os
			AND   tbl_os.posto = $posto
			AND   tbl_os.fabrica = $login_fabrica";
			//
	$res = @pg_query ($con,$sql);

	if (pg_num_rows ($res) == 1) {

		$sua_os               = pg_fetch_result($res,0, 'sua_os');
		$data_abertura        = pg_fetch_result($res,0, 'data_abertura');
		$data_digitacao       = pg_fetch_result($res,0, 'data_digitacao');
		$consumidor_nome      = pg_fetch_result($res,0, 'consumidor_nome');
		$consumidor_cpf       = pg_fetch_result($res,0, 'consumidor_cpf');
		$consumidor_cidade    = pg_fetch_result($res,0, 'consumidor_cidade');
		$consumidor_fone      = pg_fetch_result($res,0, 'consumidor_fone');
		$consumidor_estado    = pg_fetch_result($res,0, 'consumidor_estado');
		$revenda_cnpj         = pg_fetch_result($res,0, 'revenda_cnpj');
		$revenda_nome         = pg_fetch_result($res,0, 'revenda_nome');
		$nota_fiscal          = pg_fetch_result($res,0, 'nota_fiscal');
		$data_nf              = pg_fetch_result($res,0, 'data_nf');
		$consumidor_revenda   = pg_fetch_result($res,0, 'consumidor_revenda');
		$aparencia_produto    = pg_fetch_result($res,0, 'aparencia_produto');
		$codigo_fabricacao    = pg_fetch_result($res,0, 'codigo_fabricacao');
		$type                 = pg_fetch_result($res,0, 'type');
		$satisfacao           = pg_fetch_result($res,0, 'satisfacao');
		$acessorios           = pg_fetch_result($res,0, 'acessorios');
		$laudo_tecnico        = pg_fetch_result($res,0, 'laudo_tecnico');
		$tipo_os_cortesia     = pg_fetch_result($res,0, 'tipo_os_cortesia');
		$produto_serie        = pg_fetch_result($res,0, 'serie');
		$qtde_produtos        = pg_fetch_result($res,0, 'qtde_produtos');
		$produto              = pg_fetch_result($res,0, 'produto');
		$produto_referencia   = pg_fetch_result($res,0, 'produto_referencia');
		$produto_descricao    = pg_fetch_result($res,0, 'produto_descricao');
		$produto_traducao     = pg_fetch_result($res,0, 'produto_traducao');
		$produto_voltagem     = pg_fetch_result($res,0, 'produto_voltagem');
		$troca_faturada       = pg_fetch_result($res,0, 'troca_faturada');
		$codigo_posto         = pg_fetch_result($res,0, 'codigo_posto');
		$tipo_os              = pg_fetch_result($res,0, 'tipo_os');
		$tipo_atendimento     = pg_fetch_result($res,0, 'tipo_atendimento');
		$segmento_atuacao     = pg_fetch_result($res,0, 'segmento_atuacao');
		$promotor_treinamento = pg_fetch_result($res,0, 'promotor_treinamento');
		$excluida 			  = pg_fetch_result($res,0, 'excluida');

		if($login_fabrica == 20){ //HD-3200578
			if ($excluida == 't') {
				header('Location:os_press.php?os=' . $os);
				exit;
			}

			$adicionais =	pg_fetch_result($res, 0, 'adicionais');

			$adicionais = json_decode($adicionais, true);
			$motivo_ordem = $adicionais['motivo_ordem'];

			if($motivo_ordem == 'PROCON (XLR)'){
				$protocolo = utf8_decode($adicionais['protocolo']);
			}
			if($motivo_ordem == 'Solicitacao de Fabrica (XQR)'){
				$ci_solicitante = utf8_decode($adicionais['ci_solicitante']);
			}

			if($motivo_ordem == 'Nao existem pecas de reposicao (nao definidas) (XSD)'){
				$descricao_peca_1 = utf8_decode($adicionais['descricao_peca_1']);
				$descricao_peca_2 = utf8_decode($adicionais['descricao_peca_2']);
				$descricao_peca_3 = utf8_decode($adicionais['descricao_peca_3']);
			}

			if($motivo_ordem == 'Pecas nao disponiveis em estoque (XSS)'){
				$codigo_peca_1 = utf8_decode($adicionais['codigo_peca_1']);
				$codigo_peca_2 = utf8_decode($adicionais['codigo_peca_2']);
				$codigo_peca_3 = utf8_decode($adicionais['codigo_peca_3']);
				$numero_pedido_1 = utf8_decode($adicionais['numero_pedido_1']);
				$numero_pedido_2 = utf8_decode($adicionais['numero_pedido_2']);
				$numero_pedido_3 = utf8_decode($adicionais['numero_pedido_3']);
			}

			if($motivo_ordem == "Linha de Medicao (XSD)"){
				$linha_medicao = utf8_decode($adicionais['linha_medicao']);
			}
			if($motivo_ordem == 'Pedido nao fornecido - Valor Minimo (XSS)'){
				$pedido_nao_fornecido = utf8_decode($adicionais['pedido_nao_fornecido']);
			}

			if($motivo_ordem == 'Contato SAC (XLR)'){
				$contato_sac = utf8_decode($adicionais['contato_sac']);
			}

			if($motivo_ordem == 'Bloqueio financeiro (XSS)' OR $motivo_ordem == 'Ameaca de Procon (XLR)' OR $motivo_ordem == 'Defeito reincidente (XQR)'){
				$detalhe = utf8_decode($adicionais['detalhe']);
			}
		}


		/*--=== Tradução para outras linguas ============================= Raphael HD:1212

		$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = '$sistema_lingua'";

		$res_idioma = @pg_query($con,$sql_idioma);
		if (@pg_num_rows($res_idioma) >0) {
			$produto_descricao  = trim(@pg_fetch_result($res_idioma,0,descricao));
		}

		//--=== Tradução para outras linguas ================================================*/
		if ($fabrica_multinacional and !is_null($produto_traducao))
			$produto_descricao = $produto_traducao;

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
	$produto_serie            = trim($_POST['produto_serie']);
	$qtde_produtos            = $_POST['qtde_produtos'];
	$cor                      = $_POST['cor'];
	$consumidor_revenda       = $_POST['consumidor_revenda'];

	$type                     = $_POST['type'];
	$satisfacao               = $_POST['satisfacao'];
	$laudo_tecnico            = $_POST['laudo_tecnico'];

	$obs                      = $_POST['obs'];
	// $chamado                  = $_POST['chamado'];
	$quem_abriu_chamado       = $_POST['quem_abriu_chamado'];
	$taxa_visita              = $_POST['taxa_visita'];
	$visita_por_km            = $_POST['visita_por_km'];
	$hora_tecnica             = $_POST['hora_tecnica'];
	$regulagem_peso_padrao    = $_POST['regulagem_peso_padrao'];
	$certificado_conformidade = $_POST['certificado_conformidade'];
	$valor_diaria             = $_POST['valor_diaria'];
	$codigo_posto             = $_POST['codigo_posto'];
	$tipo_atendimento         = $_POST['tipo_atendimento'];
	$segmento_atuacao         = $_POST['segmento_atuacao'];

	$locacao                  = $_POST['locacao'];
	$promotor_treinamento     = $_POST['promotor_treinamento'];
	$promotor_treinamento2    = $_POST['promotor_treinamento2'];
	$motivo_ordem = $_POST['motivo_ordem'];
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
	fecho('sem.permissao.de.acesso', $con);
	include 'rodape.php';
	exit;
}

?>

<!-- <iframe src="http://fast.wistia.com/embed/iframe/f0c0e95e86?videoWidth=640&videoHeight=373&controlsVisibleOnLoad=true" allowtransparency="true" frameborder="0" scrolling="no" class="wistia_embed" name="wistia_embed" width="640" height="373"></iframe> -->

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
Nome da Função : formata_cnpj ((obj) campo)
        Formata o Campo de CNPJ a medida que ocorre a digitação
        Parâm.: cnpj (DOM object)
=================================================================*/
function formata_cnpj(campo) {
	var cnpj = campo.value.length;
	if (cnpj ==  2 || cnpj == 6) campo.value += '.';
	if (cnpj == 10) campo.value += '/';
	if (cnpj == 15) campo.value += '-';
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
		if (http.status == 200) {
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
<style type="text/css">
    @import "plugins/jquery/datepick/telecontrol.datepick.css";

	div.anexo {
		background: #fee;
		color: #400;
	}
</style>
<? // include "javascript_pesquisas.php"; ?>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3/jquery.min.js" type="text/javascript"></script>
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>
<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<? // include "javascript_calendario_new.php"; ?>
<script src="js/jquery-ui.min.js" type="text/javascript"></script>
<link rel="stylesheet" href="js/jquery.tooltip.css" />
<script type="text/javascript" src="js/jquery.tooltip.min.js"></script>
<script type="text/javascript" src="js/jquery.base64.js"></script>
<script src="plugins/shadowbox/shadowbox.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<script type="text/javascript" src="js/jquery.maskedinput2.js"></script>
<?/*    MLG 23/03/2010 - HD 205816 - Refiz o 'prompt' para evitar (novidade...) problemas com usuários do MSIE... */?>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick.js"></script>
<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<!-- Formatar DATA -->

<script type="text/javascript">
	var f = <?=$login_fabrica?>;
	$(function(){
		<?php // MLG - HD 3032759
		if ($pa_foto_serie_produto): ?>
		$("input[name=produto_serie]").blur(function() {
			var serie   = $(this).val();
			var tipo_at = parseInt($("[name=tipo_atendimento]").val());
			var show    = false;

			if (f === 20) {
				show = (serie == '999' && !(tipo_at == 11 || tipo_at == 12));
			}

			show ? $("#td_anexo_serie").show() : $("#td_anexo_serie").hide();
		});
		<?php endif; ?>

		displayText('&nbsp;');
		$("input[rel='data']").datepick({startdate:'01/01/2000'});
		$("input[rel='data']").maskedinput("99/99/9999");
		$("input[rel='data_hora']").maskedinput("99/99/9999 99:99");

/*
		$("#peca_nao_disponivel").hide();
		$("#nao_existe_pecas").hide();
		$("#procon").hide();
		$("#solicitacao_fabrica").hide();
		$("#pedido_nao_fornecido").hide();
		$("#linha_medicao").hide();
*/
		function EscondeCampos(){
			$("#peca_nao_disponivel").hide();
			$("#nao_existe_pecas").hide();
			$("#procon").hide();
			$("#solicitacao_fabrica").hide();
			$("#pedido_nao_fornecido").hide();
			$("#linha_medicao").hide();
			$("#contato_sac").hide(); //HD-3200578
			$("#detalhe").hide(); //HD-3200578
		}

		$("#motivo_ordem").change(function(){
			var motivo_ordem = $("#motivo_ordem").val();
			EscondeCampos();
			if(motivo_ordem == 'Pecas nao disponiveis em estoque (XSS)'){
				$("#peca_nao_disponivel").show();
			}
			if(motivo_ordem == 'Nao existem pecas de reposicao (nao definidas) (XSD)'){
				$("#nao_existe_pecas").show();
			}
			if(motivo_ordem == 'PROCON (XLR)'){
				$("#procon").show();
			}
			if(motivo_ordem == 'Solicitacao de Fabrica (XQR)'){
				$("#solicitacao_fabrica").show();

			}
			if(motivo_ordem == "Linha de Medicao (XSD)"){
				$("#linha_medicao").show();
			}
			if(motivo_ordem == 'Pedido nao fornecido - Valor Minimo (XSS)'){
				$("#pedido_nao_fornecido").show();
			}
			if(motivo_ordem == 'Contato SAC (XLR)'){ //HD-3200578
				$("#contato_sac").show();
			}
			if(motivo_ordem == 'Bloqueio financeiro (XSS)' || motivo_ordem == 'Ameaca de Procon (XLR)' || motivo_ordem == 'Defeito reincidente (XQR)'){ //HD-3200578
				$("#detalhe").show();
			}
		});

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

	if($login_fabrica == 20){ ?>
	function MudaCampo(campo){

		if (<?echo $promotor_mostra;?>) {
			document.getElementById('autorizacao_cortesia').style.display='inline';
		}else{
			document.getElementById('autorizacao_cortesia').style.display='none';
			//hd 47203
			document.getElementById('promotor_treinamento').selectedIndex=0;
		}

        if (campo.value=='12' ) {
            document.frm_os.produto_referencia.value = '0000001';
            document.frm_os.produto_descricao.value = 'Garantia de Acessórios';
            document.frm_os.produto_serie.value = '999';
			document.frm_os.produto_serie.blur();
			$('input[name=produto_serie]').maskedinput("?9999999999",{placeholder: " "});
            //$('input[name=produto_serie]').attr("readonly",true);
            document.getElementById('autorizacao_troca').style.display='none';

            document.getElementById('peca_nao_disponivel').style.display='none';
            document.getElementById('nao_existe_pecas').style.display='none';
            document.getElementById('procon').style.display='none';
            document.getElementById('solicitacao_fabrica').style.display='none';
            document.getElementById('pedido_nao_fornecido').style.display='none';
            document.getElementById('linha_medicao').style.display='none';

        }else if(campo.value=='11' ) {
            document.frm_os.produto_serie.value = '999';
            $('input[name=produto_serie]').attr("readonly",true);
            document.getElementById('autorizacao_troca').style.display='none';

            document.getElementById('peca_nao_disponivel').style.display='none';
            document.getElementById('nao_existe_pecas').style.display='none';
            document.getElementById('procon').style.display='none';
            document.getElementById('solicitacao_fabrica').style.display='none';
            document.getElementById('pedido_nao_fornecido').style.display='none';
            document.getElementById('linha_medicao').style.display='none';


        }else{
			document.getElementById('autorizacao_troca').style.display='none';
			//hd 47203
			document.getElementById('promotor_treinamento2').selectedIndex=0;
			if (campo.value=='13'  || campo.value== '66' ) {
				document.getElementById('autorizacao_troca').style.display='inline';
			}else{
	            document.getElementById('peca_nao_disponivel').style.display='none';
	            document.getElementById('nao_existe_pecas').style.display='none';
	            document.getElementById('procon').style.display='none';
	            document.getElementById('solicitacao_fabrica').style.display='none';
	            document.getElementById('pedido_nao_fornecido').style.display='none';
	            document.getElementById('linha_medicao').style.display='none';
			}
			document.frm_os.produto_serie.value = '';
			$('input[name=produto_serie]').attr("readonly",false);
			document.frm_os.produto_referencia.value = '';
			  document.frm_os.produto_descricao.value = '';
		}
	}

<? } ?>

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
	echo "
		<div class='alerts'>
			<div class='alert danger margin-top'>
				<br>
				$erro $msg_erro
			</div>
		</div>
	";
	//echo $erro . $msg_erro . "<br><!-- " . $sql . "<br>" . $sql_OS . " -->";
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

		<form style="margin: 0px; word-spacing: 0px" name="frm_os" id="frm_os" method="post" action="<? echo $PHP_SELF ; if ($login_fabrica==20 and $login_pais == 'BR') echo '" enctype="multipart/form-data';?>">
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
				<?=traduz('distribuidor.pode.digitar.pedidos.para.seus.postos', $con)?>
				<br>
				<?=traduz('digite.o.codigo.do.posto', $con)?>
				<input type='text' name='codigo_posto' size='5' maxlength='10' value='<? echo $codigo_posto ?>'>
				<?=traduz('ou.deixe.em.branco.para.suas.proprias.os', $con)?>
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
		<font size="2" color="red" face="Geneva, Arial, Helvetica, san-serif">
		<? /*3192 Em "Tipo de Atendimento" devería ser "Tipo de Atención"*/
		fecho('tipo.de.atendimento', $con);
?>
		</font>
		<select name="tipo_atendimento" size="1" class="frm" <? if ($login_fabrica==20) { echo "onChange='MudaCampo(this)'"; };?>>
			<option selected></option>
<?

			//IGOR  - HD 2909  | Garantía de repuesto - Não tem | Garantía de accesorios - Não tem | Garantía de reparación - Não tem
			$wr = "";

			if ($login_fabrica == 20 and $login_pais <> "BR") {

				$tipo_at = " AND tbl_tipo_atendimento.tipo_atendimento NOT IN";

				switch ($login_pais) {
				case 'AR':
				case 'MX':
					$tipo_at .= "(66) ";
					break;

				case 'PE':
					$tipo_at .= "(14) ";
					break;

				case 'CO':
					$tipo_at .= "(66)";
					break;
				default:
					$tipo_at .= "(66) ";
					break;
				}
			}

			$sql = "SELECT *
					FROM tbl_tipo_atendimento
					WHERE fabrica = $login_fabrica
					AND   ativo IS TRUE
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
			echo "&nbsp;&nbsp;&nbsp;&nbsp;" . traduz('segmento.de.atuacao', $con) . '&nbsp;';

			$sql = "SELECT tbl_segmento_atuacao.segmento_atuacao,
				           COALESCE(tbl_segmento_atuacao_idioma.descricao, tbl_segmento_atuacao.descricao) AS descricao
				      FROM tbl_segmento_atuacao
				 LEFT JOIN tbl_segmento_atuacao_idioma
				        ON tbl_segmento_atuacao_idioma.segmento_atuacao = tbl_segmento_atuacao.segmento_atuacao
				       AND idioma                                       = UPPER(SUBSTR('$cook_idioma',1,2))
				     WHERE fabrica = $login_fabrica
				  ORDER BY descricao";
			$res = pg_query ($con,$sql) ;

			$a_sa = pg_fetch_all($res);

			foreach ($a_sa as $i_sa) {
				$a_seg_at[$i_sa['segmento_atuacao']] = $i_sa['descricao'];
			}
			echo array2select('segmento_atuacao', 'sa', $a_seg_at, $segmento_atuacao, " size='1' class='frm'", ' ', true);

			/**
			 * HD 3192 - Em caso de garantía de piezas o accesorios no es necesario inserir el producto en la OS"
			 * deveria ser "En caso de garantía de piezas o accesorios no es necesario insertar el producto en la OS"
				/* Retirado, agora faz direto na consulta principal
			echo "<select name='segmento_atuacao' size='1' class='frm'>";
			echo "<option selected></option>";

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
				 */

			/* HD-3311645
			echo "<br><b><FONT SIZE='' COLOR='#FF9900'>";
			if($sistema_lingua)
				 echo "En caso de garantía de repuestos o accesorios no es necesario especificar el producto en la OS";
			else echo "Nos casos de Garantia de Peças ou  Acessórios não é necessário lançar o Produto na OS.";
			echo "</FONT></b><br>";
			 */

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
					echo "<TD colspan='4'>";echo "<b><FONT COLOR='#FF9900'>";
					if($sistema_lingua)
						 echo "En el caso de cambio de producto, gentileza comercial o técnica, es obligatorio informar del nombre de la persona que lo aprobó y la fecha de aprobación.";
					else echo "Nos casos de Troca de Produto, Cortesia comercial ou técnica é obrigatório informar o nome da pessoa para aprovação.";
					echo "</FONT></b><br>";
					echo "</TD>";
					echo "</TR>";
					echo "<TR>";

					//if($sistema_lingua <> 'ES'){

						echo "<TD>";
						echo "<font size='2' ";
						echo ($login_pais == 'BR') ? " color='red' ": "";
						echo "face='Geneva, Arial, Helvetica, san-serif'>";
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
							if ($promotor_treinamento == $xx_promotor_treinamento ) echo " selected ";
							echo " value='$xx_promotor_treinamento' >" ;
							echo $xx_nome;
							echo "</option>\n";
						}
						echo "</select>";
						echo "</TD>";
					if($login_fabrica ==20 AND $login_pais == "BR"){

						echo "<td><font color='red'>";
							echo "Motivo Ordem";
						echo "</font></td>";
						echo "<td>";
							echo "<select name='motivo_ordem' id='motivo_ordem' class='frm' style='width:180px'>";
							$array_motivo_ordem = array(
								"Ameaca de Procon (XLR)" => "Ameaça de Procon (XLR)",
								"Bloqueio financeiro (XSS)" => "Bloqueio financeiro (XSS)",
								"Contato SAC (XLR)" => "Contato SAC (XLR)",
								"Defeito reincidente (XQR)" => "Defeito reincidente (XQR)",
								"Linha de Medicao (XSD)" => "Linha de Medição (XSD)",
								"Nao existem pecas de reposicao (nao definidas) (XSD)" => "Não existem peças de reposição (não definidas) (XSD)",
								"Pecas nao disponiveis em estoque (XSS)" => "Peças não disponíveis em estoque (XSS)",
								"Pedido nao fornecido - Valor Minimo (XSS)" => "Pedido não fornecido - Valor Mínimo (XSS)",
								"PROCON (XLR)" => "PROCON (XLR)",
								"Solicitacao de Fabrica (XQR)" => "Solicitação de Fábrica (XQR)"
							);
            				echo"<option value=''></option>\n";
            				foreach ($array_motivo_ordem as $descricao => $descricao_acento) {
								echo"<option value='$descricao'";
								if (strtolower($motivo_ordem) == strtolower($descricao) OR $_POST['motivo_ordem'] == $descricao) echo " selected ";
									echo ">$descricao_acento</option>\n";
            				}
            				echo "</select>";
						echo "</td>";
					}
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

		if($motivo_ordem == 'Pecas nao disponiveis em estoque (XSS)'){
			$mostrar_peca_nao_disponivel = "block";
		}
		if($motivo_ordem == 'Nao existem pecas de reposicao (nao definidas) (XSD)'){
			$mostrar_nao_existe_pecas = 'block';
		}
		if($motivo_ordem == 'PROCON (XLR)'){
			$mostrar_procon = 'block';
		}

		if($motivo_ordem == 'Contato SAC (XLR)'){ //HD-3200578
			$mostra_sac = 'block';
		}else{
			$mostra_sac = 'none';
		}

		if($motivo_ordem == 'Bloqueio financeiro (XSS)' OR $motivo_ordem == 'Ameaca de Procon (XLR)' OR $motivo_ordem ==  'Defeito reincidente (XQR)'){ //HD-3200578
			$mostra_detalhe = 'block';
		}else{
			$mostra_detalhe = 'none';
		}

		if($motivo_ordem == 'Solicitacao de Fabrica (XQR)'){
			$mostrar_solicitacao_fabrica = 'block';
		}
		if($motivo_ordem == "Linha de Medicao (XSD)"){
			$mostrar_linha_medicao = 'block';
		}
		if($motivo_ordem == 'Pedido nao fornecido - Valor Minimo (XSS)'){
			$mostrar_pedido_nao_fornecido = 'block';
		}

		?>
		<div id='peca_nao_disponivel' style='display: <?php echo ($mostrar_peca_nao_disponivel == 'block') ? 'block': 'none'?> '>
 		<?php
			echo "<TABLE  width='710' border='0'>";
					echo "<TR>";
					echo "<TD rowspan='3' style='width:100px; text-align:right' > <font size='2' face='Geneva, Arial, Helvetica, san-serif' color='red'> Código Peças</font> </td>";
					echo "<td> <input type='text' maxlength='15' name='codigo_peca_1' value='$codigo_peca_1' class='frm'> </td>";
					echo "<TD rowspan='3' style='width:150px; text-align:right'> <font size='2' face='Geneva, Arial, Helvetica, san-serif'> Número de Pedido </font> </td>";
					echo "<td><input type='text' maxlength='15' name='numero_pedido_1' value='$numero_pedido_1' class='frm'></td>";
					echo "</tr>";
					echo "<tr>";
						echo "<td><input type='text' maxlength='15' name='codigo_peca_2' value='$codigo_peca_2' class='frm'></td>";
						echo "<td><input type='text' maxlength='15' name='numero_pedido_2' value='$numero_pedido_2' class='frm'></td>";
					echo "</tr>";
					echo "<tr>";
						echo "<td><input type='text' maxlength='15' name='codigo_peca_3' value='$codigo_peca_3' class='frm'></td>";
						echo "<td><input type='text' maxlength='15' name='numero_pedido_3' value='$numero_pedido_3' class='frm'></td>";
					echo "</tr>";
			echo "</table>";
		echo "</div>";

		?>
		<div id='nao_existe_pecas' style='display: <?php echo ($mostrar_nao_existe_pecas == 'block') ? 'block': 'none'?> '>
 		<?php
			echo "<TABLE  width='710' border='0'>";
					echo "<TR>";
					echo "<TD rowspan='3' style='width:200px; text-align:right' > <font size='2' face='Geneva, Arial, Helvetica, san-serif' color='red'> Descrição da Peças</font> </td>";
					echo "<td> <input type='text' maxlength='15' name='descricao_peca_1' value='$descricao_peca_1' class='frm'> </td>";
					echo "</tr>";
					echo "<tr>";
						echo "<td><input type='text' maxlength='15' name='descricao_peca_2' value='$descricao_peca_2' class='frm'></td>";
					echo "</tr>";
					echo "<tr>";
						echo "<td><input type='text' maxlength='15' name='descricao_peca_3' value='$descricao_peca_3' class='frm'></td>";
					echo "</tr>";
			echo "</table>";
		echo "</div>";

		?>
		<div id='procon' style='display: <?php echo ($mostrar_procon == 'block') ? 'block': 'none'?> '>
 		<?php
			echo "<TABLE  width='710' border='0'>";
					echo "<TR>";
						echo "<TD rowspan='3' style='width:200px; text-align:right' > <font size='2' face='Geneva, Arial, Helvetica, san-serif'>Protocolo</font> </td>";
						echo "<td> <input type='text' maxlength='15' name='protocolo' value='$protocolo' class='frm'> </td>";
					echo "</tr>";
			echo "</table>";
		echo "</div>";

		?>

		<div id='contato_sac' style='display: <?=$mostra_sac?>'> <!-- //HD-3200578 -->
			<table width="710" border="0">
				<tr>
					<td rowspan="3" style='width: 200px; text-align: right; font-size: 12px; font-family: Geneva, Arial, Helvetica, san-serif'>N° do Chamado: </td>
					<td> <input type="text" name="contato_sac" style='width: 250px;' maxlength="50" value="<?=$contato_sac?>" class='frm' "></td>
				</tr>
			</table>
		</div>

		<div id='detalhe' style='display: <?=$mostra_detalhe?>'> <!-- //HD-3200578 -->
			<table width="710" border="0">
				<tr>
					<td rowspan="3" style='width: 200px; text-align: right; font-size: 12px; font-family: Geneva, Arial, Helvetica, san-serif'>Detalhe: </td>
					<td> <input type="text" name="detalhe" style='width: 250px;' maxlength="100" value="<?=$detalhe?>" class='frm' "></td>
				</tr>
			</table>
		</div>

		<div id='solicitacao_fabrica' style='display: <?php echo ($mostrar_solicitacao_fabrica == 'block') ? 'block': 'none'?> '>
 		<?php
			echo "<TABLE  width='710' border='0'>";
					echo "<TR>";
						echo "<TD style='width:200px; text-align:right' > <font size='2' face='Geneva, Arial, Helvetica, san-serif'>Informe CI ou Solicitante</font> </td>";
						echo "<td> <input type='text' maxlength='15' name='ci_solicitante' value='$ci_solicitante' class='frm'> </td>";
					echo "</tr>";
			echo "</table>";
		echo "</div>";
		?>
		<div id='linha_medicao' style='display: <?php echo ($mostrar_linha_medicao == 'block') ? 'block': 'none'?> '>
 		<?php
			echo "<TABLE  width='760' border='0'>";
					echo "<tr>";
						echo "<TD style='width:200px; text-align:center' > <font size='2' face='Geneva, Arial, Helvetica, san-serif'>Linha de Medição(XSD)</font> </td>";
					echo "</tr>";
					echo "<tr>";
						echo "<td style='width:200px; text-align:center'> <input type='text' maxlength='250' name='linha_medicao' value='$linha_medicao' class='frm' style='width:650px;'> </td>";
					echo "</tr>";
			echo "</table>";
		echo "</div>";
		?>
		<div id='pedido_nao_fornecido' style='display: <?php echo ($mostrar_pedido_nao_fornecido == 'block') ? 'block': 'none'?> '>
 		<?php
			echo "<TABLE  width='760' border='0'>";
					echo "<tr>";
						echo "<TD style='width:200px; text-align:center' > <font size='2' face='Geneva, Arial, Helvetica, san-serif'>Pedido não fornecido - Valor Mínimo(XSS)</font> </td>";
					echo "</tr>";
					echo "<tr>";
						echo "<td style='width:200px; text-align:center'> <input type='text' maxlength='250' name='pedido_nao_fornecido' value='$pedido_nao_fornecido' class='frm' style='width:650px;'> </td>";
					echo "</tr>";
			echo "</table>";
		echo "</div>";

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
			}elseif($login_fabrica == 20){
				$data_abertura = $data_abertura.' '.$hora;
			}
			?>

			<? if ($login_fabrica == 6){ ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">N. Série</font>
				<br>
				<input class="frm" type="text" name="produto_serie" size="12" maxlength="20"
                       value="<? echo $produto_serie ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o número de série do aparelho.'); ">
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
					echo "<font size='1' color='red' face='Geneva, Arial, Helvetica, san-serif'>";
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
					echo "<font size='1' color='red' face='Geneva, Arial, Helvetica, san-serif'>";
					if($sistema_lingua) echo "Descripción del producto";else echo "Descrição do Produto";
					echo "</font>";
				}
				?>

				<br>
				<input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>"  onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<?if($sistema_lingua == "ES") echo "Informe del modelo del producto y haga clic en la lupa para buscar";else echo "Digite aqui o modelo do produto e clique na lupa para efetuar a pesquisa.";?>');checarComunicado(<? echo $login_fabrica ?>);" <? if (strlen($locacao) > 0) echo "readonly"; ?><?if($desabilita)echo " readonly";?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao',document.frm_os.produto_voltagem)"  style='cursor: pointer' <?if($desabilita)echo "readonly";?>></A>
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
					tabindex="0" />
				<? } ?>
				 <br>
				 <font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
			</td>
			<td nowrap>
				<?php
					$maxlength = ($login_fabrica == 20) ? 9 : 20;
				?>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? echo ($sistema_lingua) ? "N. Serie" : "N. Série";?></font>
				<br>
				 <input  class="frm" type="text" name="produto_serie" size="8" maxlength="<?=$maxlength?>"
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
	<?php if ($pa_foto_serie_produto == 't'):
	if ($login_fabrica == 20)
		if($login_pais <> "BR"){
          	$labelText  = "El código 999 es especifico solamente para casos en que no se pueda identificar el numero correcto (etiqueta faltante o ilegible). <br/> Por favor anexar una foto que comprobé eso.";
		}else{
			$labelText = "O número de série 999 é especifico somente para casos de etiqueta ilegível ou ausente.<br />
						Por favor, anexar foto comprovando um desses casos.";
		}
		$filtro = compact('produto_serie', 'tipo_atendimento'); ?>
	<table id="td_anexo_serie" width="100%" style="display:<?=$anexaFotoSerie($filtro) ? 'table' : 'none'?>">
        <tr>
            <td align="right">
            <?php if (isset($_POST['anexo_serie'])): ?>
                <div style="width:30%;float:left;height:4em;text-align:left">
                	Arquivo anexado:
                	    <a href="<?=$anexoURL?>" target="new">
                	    	<img id="img_anexo" src="<?=$anexoURL?>" alt="Anexo de Núm. Série" style="max-height:4em;vertical-align:top" />
                	    </a>
                	    <input type="hidden" name="anexo_serie" value='<?=$_POST['anexo_serie']?>'>
                </div>
			<?php
			echo $include_imgZoom;
			 endif; ?>
                <div class="anexo">
					<label for="input_anexo_serie">
						<?=$labelText?>
					</label>
                    <input id="input_anexo_serie" type="file" name="anexo_serie" />
                </div>
            </td>
        </tr>
    </table>
    <?php endif; ?>

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
            <input class="frm" type="text" name="revenda_nome" id="revenda_nome" size="30" maxlength="50" value="<? echo $revenda_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<? echo ($sistema_lingua == "ES") ? "Informe del nombre de la tienda donde el produto fue comprado" : "Digite o nome da REVENDA onde foi adquirido o produto.";?>');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' style='cursor: pointer'>
        </td>
        <td>
            <font size="1" face="Geneva, Arial, Helvetica, sans-serif"><? echo ($sistema_lingua) ? "ID distribuidor" : "CNPJ Revenda";?></font>
            <br>
            <input class="frm" type="text" name="revenda_cnpj" id="revenda_cnpj" size="20" maxlength="18" value="<? echo $revenda_cnpj ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<? echo ($sistema_lingua == "ES") ? "Escriba el nº de ID fiscal de la tienda o distribuidor" : "Insira o número no Cadastro Nacional de Pessoa Jurídica.";?>'); " <? echo ($sistema_lingua <> "ES") ? "onKeyUp=\"formata_cnpj(this)\"" : "";?>>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor: pointer'>
        </td>
        <td>
            <font size="1" face="Geneva, Arial, Helvetica, sans-serif"><? echo ($sistema_lingua) ? "Factura comercial" : "Nota Fiscal";?></font>
            <br>
            <input class="frm" type="text" name="nota_fiscal"  size="8"  maxlength="8"  value="<? echo $nota_fiscal ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<? echo ($sistema_lingua == "ES") ? "Escriba el número de factura" : "Entre com o número da Nota Fiscal.";?>');" <? if (strlen($locacao) > 0) echo "readonly"; ?>>
        </td>
        <td>
            <font size="1" color="red" face="Geneva, Arial, Helvetica, sans-serif"><? echo ($sistema_lingua) ? "Fecha compra" : "Data Compra/NF";?> </font>
            <br>
            <input class="frm" type="text" name="data_nf" rel='data' size="12" maxlength="10" value="<? echo $data_nf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<? echo ($sistema_lingua == "ES") ? "Informe de la fecha de compra. Verifique si el producto está aún en garantía" : "Entre com a data da compra. Verifique se o produto está dentro do PRAZO DE GARANTIA.";?>');" tabindex="0" <? if (strlen($locacao) > 0) echo "readonly"; ?>><br><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
        </td>
    </tr>
    </table>
    <hr>
    <table width="100%" border="0" cellspacing="5" cellpadding="2">
		<tr>
			<td>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'> <?php
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

			if( in_array($login_fabrica, array(11,172)) ){
				echo "<td width='440px'>&nbsp;";
			}else{
				echo "<td>";
				echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>";
				echo ($sistema_lingua=="ES") ? "Apariencia del producto" : "Aparência do Produto";
				echo "</font>";
			}
			echo "<br>";

			if (in_array($login_fabrica, array(20,114))) {
				if ($login_fabrica == 20) {

					$a_aparencia = array(
						'pt-br' => array(
							'NEW' => 'Bom estado',
							'USL' => 'Uso intenso',
							'USN' => 'Uso Normal',
							'USH' => 'Uso Pesado',
							'ABU' => 'Uso Abusivo',
							'ORI' => 'Original, sem uso',
							'PCK' => 'Embalagem'
						),
						'es'    => array(
							'NEW' => 'Buena aparencia',
							'USL' => 'Uso continuo',
							'USN' => 'Uso Normal',
							'USH' => 'Uso Pesado',
							'ABU' => 'Uso Abusivo',
							'ORI' => 'Original, sin uso',
							'PCK' => 'Embalaje'
						),
						'en-US' => array(
							'NEW' => 'New',
							'USL' => 'Intense Use',
							'USN' => 'Normal Use',
							'USH' => 'Heavy Use',
							'ABU' => 'Abusive Use',
							'ORI' => 'Original, no use',
							'PCK' => 'Packed'
						)
					);
				}

				if ($login_fabrica == 114) {
					$a_aparencia = array('pt-br' => explode(',', 'NOVA SEM USO,USO NORMAL,USO INADEQUADO'));
				}

				echo array2select('aparencia_produto', 'aparencia_produto', $a_aparencia[$cook_idioma], $aparencia_produto, ' class="frm"', 'ESCOLHA', $login_fabrica==20);

				/*echo "<select name='aparencia_produto' size='1' class='frm'>";
				echo "<option value=''></option>";
                foreach ($a_aparencia as $valor => $a_desc) {
                	$desc = ($sistema_lingua == 'ES') ? $a_desc['ES'] : $a_desc['pt-br'];
                	$item_sel= ($aparencia_produto == $valor) ? " selected":"";
                	echo "<option value='$valor'$item_sel>$desc</option>\n";
                }
				echo "</select>";*/

			} else {

				if ( in_array($login_fabrica, array(11,172)) ) {
					echo "<input type='hidden' type='text' name='aparencia_produto' value='$aparencia_produto'>";
				} else {
					echo "<input class='frm' type='text' name='aparencia_produto' size='30' value='$aparencia_produto' onblur=\"this.className='frm'; displayText('&nbsp;');\" onfocus=\"this.className='frm-on'; displayText('&nbsp;Texto livre com a aparência externa do aparelho deixado no balcão.');\">";
				}

			}

			echo "</td>";
			if ( !in_array($login_fabrica, array(1,11,172)) ) { ?>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? if($sistema_lingua) echo "Accesorios";else echo "Acessórios";?></font>
				<br>
				<input class="frm" type="text" name="acessorios" size="30" value="<? echo $acessorios ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<?if($sistema_lingua == "ES") echo "Informe de la fecha de compra. Verifique si el producto está aún en garantía";else echo "Texto livre com os acessórios deixados junto ao produto.";?>');">
			</td>
			<? } ?>
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
			<img src='imagens/btn_continuar.gif' name='nome_frm_os' class='verifica_servidor' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar'; document.frm_os.submit();}" ALT="<?if($sistema_lingua == 'ES') echo "Continuar con la orden de servicio";else echo "Continuar com Ordem de Serviço";?>" border='0' style='cursor: pointer'>
		</td>
	</tr>
</table>

 <input type='hidden' name='revenda_fone'        id='revenda_fone'>
 <input type='hidden' name='revenda_cidade'      id='revenda_cidade'>
 <input type='hidden' name='revenda_estado'      id='revenda_estado'>
 <input type='hidden' name='revenda_endereco'    id='revenda_endereco'>
 <input type='hidden' name='revenda_numero'      id='revenda_numero'>
 <input type='hidden' name='revenda_complemento' id='revenda_complemento'>
 <input type='hidden' name='revenda_bairro'      id='revenda_bairro'>
 <input type='hidden' name='revenda_cep'         id='revenda_cep'>
 <input type='hidden' name='revenda_email'       id='revenda_email'>

</form>
<p>&nbsp;</p>

<?/*<div>
	Video<br />
	<iframe src="http://fast.wistia.com/embed/iframe/f0c0e95e86?videoWidth=640&videoHeight=373&controlsVisibleOnLoad=true" allowtransparency="true" frameborder="0" scrolling="no" class="wistia_embed" name="wistia_embed" width="640" height="373"></iframe>
</div>
 */
?>

<? include "rodape.php";?>

