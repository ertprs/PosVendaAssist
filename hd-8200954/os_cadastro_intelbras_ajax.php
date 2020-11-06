<?php
include	'dbconfig.php';
include	'includes/dbconnect-inc.php';
include	'autentica_usuario.php';

include	'funcoes.php';

/*  MLG 26/10/2010 - Toda a rotina de anexo de imagem da NF, inclusive o array com os parâmetros por fabricante, está num include.
	Para saber se a fábrica pede imagem da NF, conferir a variável (bool) '$anexaNotaFiscal'
	Para anexar uma imagem, chamar a função anexaNF($os, $_FILES['foto_nf'])
	Para mostrar a imagem: echo temNF($os); // Devolve um link: <a href='imagem' blank><img src='imagem[thumb'></a>
	Para saber se tem anexo: temNF($os, 'bool');
*/
include_once('anexaNF_inc.php');

if (strlen($os)==0){
	$sql = "SELECT digita_os FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
	$res = @pg_query($con,$sql);
	$digita_os = pg_fetch_result ($res,0,0);
	
	if ($digita_os == 'f' and strlen($hd_chamado)==0) {
		echo "<H4>Sem permissão de acesso.</H4>";
		exit;
	}
}

if(strlen($_GET['os'])>0){
	$os=$_GET['os'];
	$sql = "SELECT fn_valida_os_reincidente($os,$login_fabrica)";
	$res1 = pg_query ($con,$sql);
	$sql = "SELECT obs_reincidencia,os_reincidente FROM tbl_os WHERE os = $os AND fabrica = $login_fabrica";
	$res = pg_query($con,$sql);
	$obs_reincidencia = pg_fetch_result($res,0,obs_reincidencia);
	$os_reincidente = pg_fetch_result($res,0,os_reincidente);
	if($os_reincidente=='t' AND strlen($obs_reincidencia )==0) {
		$sql = "SELECT os from tbl_os_status where status_os = 67 and os = $os";
		$res = pg_exec($con,$sql);
		if (pg_num_rows($res)>0) {
			header ("Location: os_motivo_atraso.php?os=$os&justificativa=ok");
		}
	}
}
if ($_POST['ajax'] == 'excluir_nf') {
	$img_nf = $_POST['excluir_nf'];
	if ($img_nf) $excluiu = (excluirNF($img_nf)) ? 'ok' : 'ko';
	exit($excluiu);
}//	FIM	Excluir	imagem

$sql = "SELECT * FROM tbl_fabrica WHERE	fabrica	= $login_fabrica";
$res = pg_query	($con,$sql);
$pedir_sua_os					  =	pg_fetch_result	($res,0,pedir_sua_os);
$pedir_causa_defeito_os_item	  =	pg_fetch_result	($res,0,pedir_causa_defeito_os_item);
$pedir_defeito_constatado_os_item =	pg_fetch_result	($res,0,pedir_defeito_constatado_os_item);
$ip_fabricante					  =	trim (pg_fetch_result($res,0,ip_fabricante));
$ip_acesso	   = $_SERVER['REMOTE_ADDR'];
$os_item_admin = "null";
$msg_erro="";

if (strlen($_POST['os']) > 0)	 $os	 = trim($_POST['os'])	 ;
if (strlen($_GET['os'])	> 0)	 $os	 = trim($_GET['os'])	 ;
if (strlen($_POST['sua_os']) > 0)$sua_os = trim($_POST['sua_os']);
if (strlen($_GET['sua_os'])	> 0) $sua_os = trim($_GET['sua_os']) ;
if (strlen($_GET['reabrir']) > 0)$reabrir= $_GET['reabrir'];

if (strlen($_GET['referencia'])	> 0)	$referencia	= trim($_GET['referencia'])	;

$posto = $login_posto ;

if (strlen($reabrir) > 0) {
	$sql = "SELECT count(*)
			FROM tbl_os
			JOIN tbl_os_produto	USING(os)
			JOIN tbl_os_item USING(os_produto)
			WHERE os=$os
			AND	fabrica=$login_fabrica
			AND	((tbl_os_item.servico_realizado	IN (SELECT servico_realizado FROM tbl_servico_realizado	WHERE fabrica=$login_fabrica AND troca_produto)) AND (SELECT servico_realizado FROM	tbl_servico_realizado WHERE	fabrica=$login_fabrica AND troca_produto) IS NOT NULL)";
	$res = pg_query	($con,$sql)	;
	if (pg_fetch_result	($res,0,0) == 0) {
		$sql = "UPDATE tbl_os SET data_fechamento =	null, finalizada = null
				WHERE  tbl_os.os	  =	$os
				AND	   tbl_os.fabrica =	$login_fabrica
				AND	   tbl_os.posto	  =	$login_posto;";
		$res = pg_query	($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}
	else{
		$msg_erro .= traduz("esta.os.nao.pode.ser.reaberta.pois.a.solucao.foi.a.troca.do.produto",$con,$cook_idioma);
		echo "<script language='javascript'>alert('".
				traduz("esta.os.nao.pode.ser.reaberta.pois.a.solucao.foi.a.troca.do.produto",$con,$cook_idioma).
			"'); history.go(-1);</script>";
		exit();
	}
}
## AJAX	para pegar a descrição do produto
if ($_POST['getProduto']=="sim"){
	$produto_referencia	= $_POST['produto_referencia'];
	if (strlen($produto_referencia)>0){

		$sql="SELECT tbl_produto.referencia,tbl_produto.descricao
				FROM tbl_produto
				JOIN tbl_linha USING(linha)
				WHERE upper(referencia)=upper('$produto_referencia')
				AND	tbl_linha.fabrica =	$login_fabrica LIMIT 1";
		$res = pg_query	($con,$sql);

		if (pg_num_rows	($res)>0){
			$produto_referencia	   = pg_fetch_result($res,0,'referencia') ;
			$produto_descricao	   = pg_fetch_result($res,0,'descricao') ;
			echo "$produto_referencia -	$produto_descricao";
		}else{
			fecho("selecione.o.produto",$con,$cook_idioma);
		}
	}else{
		fecho("selecione.o.produto",$con,$cook_idioma);
	}
	exit;
}
if ($_POST['getLinha']=="sim"){
	$produto_referencia	= $_POST['produto_referencia'];
	if (strlen($produto_referencia)>0){
		$sql="SELECT tbl_produto.linha
				FROM tbl_produto
				JOIN tbl_linha USING(linha)
				WHERE upper(referencia)=upper('$produto_referencia')
				AND	tbl_linha.fabrica =	$login_fabrica LIMIT 1";
		$res = pg_query	($con,$sql);

		if (pg_num_rows	($res)>0){
			$linha	  =	pg_fetch_result	($res,0,linha) ;
			echo "$linha";
		}
	}
	exit;
}

$ajax         = $_GET['ajax'];
$excluir      = $_GET['excluir'];
$os_produto   = $_GET['os_produto'];

if($ajax=='sim' AND $excluir=='item' AND strlen($os_produto)>0 AND $login_fabrica==14){
	list($os,$os_produto)= explode('|',$os_produto);

	#HD 15489 HD 311925
	$sql = "UPDATE tbl_os_produto SET
			os = 4836000
			FROM tbl_os_item
			WHERE  tbl_os_produto.os            = $os
			AND    tbl_os_produto.os_produto    = tbl_os_item.os_produto
			AND    tbl_os_produto.os_produto    = $os_produto
			AND    tbl_os_item.pedido           IS NULL";
			//echo nl2br($sql);
	$res = @pg_query ($con,$sql);
	$msg_erro .= pg_errormessage($con);

	exit('ok');
}

$btn_acao =	$_POST['btn_acao'];

####################		INICIO DA GRAVAÇÃO			 ############################

if ($btn_acao == "gravar") {

$_POST['certificado_garantia'] 		= substr($_POST['certificado_garantia']		, 0, 30);
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
$_POST['divisao']					= substr($_POST['divisao']					, 0, 20);
$_POST['natureza_servico']			= substr($_POST['natureza_servico']			, 0, 20);
$_POST['nota_fiscal']				= substr($_POST['nota_fiscal']				, 0, 20);
$_POST['nota_fiscal_saida']			= substr($_POST['nota_fiscal_saida']		, 0, 20);
$_POST['os_posto']					= substr($_POST['os_posto']					, 0, 20);
$_POST['pac']						= substr($_POST['pac']						, 0, 13);
$_POST['prateleira_box']			= substr($_POST['prateleira_box']			, 0, 10);
$_POST['quem_abriu_chamado']		= substr($_POST['quem_abriu_chamado']		, 0, 30);
$_POST['rg_produto']				= substr($_POST['rg_produto']				, 0, 50);
$_POST['serie']						= substr($_POST['serie']					, 0, 20);
$_POST['serie_reoperado']			= substr($_POST['serie_reoperado']			, 0, 20);
$_POST['sua_os']					= substr($_POST['sua_os']					, 0, 20);
$_POST['sua_os_offline']			= substr($_POST['sua_os_offline']			, 0, 20);
$_POST['tecnico_nome']				= substr($_POST['tecnico_nome']				, 0, 20);
$_POST['tipo_os_cortesia']			= substr($_POST['tipo_os_cortesia']			, 0, 20);
$_POST['type']						= substr($_POST['type']						, 0, 10);
$_POST['veiculo']					= substr($_POST['veiculo']					, 0, 20);
$_POST['versao']					= substr($_POST['versao']					, 0, 20);
$_POST['produto_voltagem']			= substr($_POST['produto_voltagem']			, 0, 20);
$_POST['produto_serie']				= substr($_POST['produto_serie']			, 0, 20);
$_POST['revenda_bairro']      		= substr($_POST['revenda_bairro']     		, 0, 80);
$_POST['revenda_cep']         		= substr($_POST['revenda_cep']        		, 0, 8);
$_POST['revenda_cnpj']        		= substr(preg_replace('/\D/', '', $_POST['revenda_cnpj']) , 0, 14);
$_POST['revenda_complemento'] 		= substr($_POST['revenda_complemento']		, 0, 30);
$_POST['revenda_email']       		= substr($_POST['revenda_email']      		, 0, 50);
$_POST['revenda_endereco']    		= substr($_POST['revenda_endereco']   		, 0, 60);
$_POST['revenda_fone']        		= substr($_POST['revenda_fone']       		, 0, 20);
$_POST['revenda_nome']        		= substr($_POST['revenda_nome']       		, 0, 50);
$_POST['revenda_numero']      		= substr($_POST['revenda_numero']     		, 0, 20);
$_POST['solucao_os2']      			= substr($_POST['solucao_os2']     			, 0, 20);

//	if($btn_acao and $login_posto = 6359) die(nl2br(print_r($_POST, true)));

	$os						= trim($_POST['os']);
	$sua_os					= trim($_POST['sua_os']);
	$imprimir_os			= trim($_POST["imprimir_os"]);
	$sua_os_offline			= trim($_POST['sua_os_offline']);
	$os_offline				= trim($_POST['os_offline']);
	$locacao				= trim($_POST["locacao"]);
	$tipo_atendimento		= trim($_POST['tipo_atendimento']);
	$xdata_abertura			= fnc_formata_data_pg(trim($_POST['data_abertura']));

	$xconsumidor_nome		= trim($_POST['consumidor_nome']);
	$xconsumidor_cpf		= trim($_POST['consumidor_cpf']);
	$xconsumidor_cidade		= trim($_POST['consumidor_cidade']);
	$xconsumidor_estado		= trim($_POST['consumidor_estado']);
	$xconsumidor_fone		= trim($_POST['consumidor_fone']);
	$xconsumidor_endereco	= trim($_POST['consumidor_endereco']);
	$xconsumidor_numero		= trim($_POST['consumidor_numero']);
	$xconsumidor_complemento= trim($_POST['consumidor_complemento']) ;
	$xconsumidor_bairro		= trim($_POST['consumidor_bairro'])	;
	$xconsumidor_cep		= trim($_POST['consumidor_cep']) ;
	$xconsumidor_email		= trim($_POST['consumidor_email']) ;
	$xcontrato				= trim($_POST['consumidor_contrato']);

	$revenda_cnpj			= trim($_POST['revenda_cnpj']);
	$xrevenda_nome			= trim($_POST['revenda_nome']);
	$xrevenda_fone			= trim($_POST['revenda_fone']);
	$xrevenda_cep			= trim($_POST['revenda_cep']);
	$xrevenda_bairro		= trim($_POST['revenda_bairro']);
	$xrevenda_complemento	= trim($_POST['revenda_complemento']);
	$xrevenda_numero		= trim($_POST['revenda_numero']);
	$xrevenda_endereco		= trim($_POST['revenda_endereco']);
	$xrevenda_cidade		= trim($_POST['revenda_cidade']);
	$xrevenda_estado		= trim($_POST['revenda_estado']);

	$xnota_fiscal			= trim($_POST['nota_fiscal']);
	$xdata_nf				= fnc_formata_data_pg(trim($_POST['data_nf']));

	$qtde_produtos			= trim($_POST['qtde_produtos']);
	$xtroca_faturada		= trim($_POST['troca_faturada']);
	$xproduto_serie			= trim($_POST['produto_serie']);
	$xcodigo_fabricacao		= trim($_POST['codigo_fabricacao']);
	$voltagem				= trim($_POST['produto_voltagem']);
	$xaparencia_produto		= trim($_POST['aparencia_produto']);
	$xacessorios			= trim($_POST['acessorios']);

	$xdefeito_reclamado_descricao =	trim($_POST['defeito_reclamado_descricao']);
	$xobs					= trim($_POST['obs']);
	$xquem_abriu_chamado	= trim($_POST['quem_abriu_chamado']);
	$xconsumidor_revenda	= trim($_POST['consumidor_revenda']);
	$xsatisfacao			= trim($_POST['satisfacao']);
	$xlaudo_tecnico			= trim($_POST['laudo_tecnico']);
	$defeito_reclamado		= trim($_POST['defeito_reclamado']);
	$defeito_constatado		= trim($_POST['defeito_constatado']);
	$data_fechamento		= trim($_POST['data_fechamento']);
	$data_conserto			= trim($_POST['data_conserto']);
	$obs					= trim($_POST['obs']);

	$numero_controle		= trim($_POST['numero_controle']);
	$xdefeito_reclamado		= trim($_POST['defeito_reclamado']);

	if (strlen($sua_os_offline)	== 0) {
		$sua_os_offline	= 'null';
	}else{
		$sua_os_offline	= "'" .	trim ($sua_os_offline) . "'";
	}

	if (strlen($sua_os)	== 0) {
		$sua_os	= 'null';
		if ($pedir_sua_os == 't') {
			$msg_erro .= traduz("digite.o.numero.da.os.fabricante",$con,$cook_idioma);
		}
	}else{
		if (strlen($sua_os)	< 7) {
			$sua_os	= str_pad($sua_os, 7, '0', STR_PAD_LEFT);
		}
		$sua_os	= "'$sua_os'" ;
	}

	##### INÍCIO DA	VALIDAÇÃO DOS CAMPOS #####

	$x_locacao = (strlen($locacao) > 0)	? "7" :	"null";

	if (strlen (trim ($tipo_atendimento)) == 0){
		$tipo_atendimento =	'null';
	}

	$produto_referencia	= strtoupper(trim($_POST['produto_referencia']));
	$produto_referencia	= str_replace("-","",$produto_referencia);
	$produto_referencia	= str_replace("	","",$produto_referencia);
	$produto_referencia	= str_replace("/","",$produto_referencia);
	$produto_referencia	= str_replace(".","",$produto_referencia);
	if(strlen($os)==0){
		if (strlen($produto_referencia)	== 0) {
			$produto_referencia	= 'null';
			$msg_erro .= traduz("digite.o.produto",$con,$cook_idioma)."<br />";
		}else{
			$produto_referencia	= "'".$produto_referencia."'" ;
		}
	}
	if ($produto_referencia != '' and $produto_referencia != 'null' and $produto_referencia[0] != "'") $produto_referencia = "'$produto_referencia'";

	//HD 209229: Número	de Série Obrigatório
	if ($xproduto_serie	== null	|| $xproduto_serie == "null" ||	strlen($xproduto_serie)	== 0) {
		if ($linha == 549) {
			$msg_erro .= traduz("digite.o.imei",$con,$cook_idioma)."<br />";
		}
		else {
		//HD 219190: Retirar obrigatoriedade de	número de série
//			$msg_erro .= traduz("digite.o.numero.de.serie",$con,$cook_idioma)."<br />";
		}
	}

	if ($xdata_abertura	== 'null'){
		$msg_erro .= traduz("digite.a.data.de.abertura.da.os",$con,$cook_idioma);
	}

	$cdata_abertura	= str_replace("'","",$xdata_abertura);

######################## CONSUMIDOR	##########################
	if (strlen($consumidor_nome) ==	0){
		$xconsumidor_nome =	'null';
		if($linha==549){
			$msg_erro .= traduz("digite.o.nome.do.consumidor",$con,$cook_idioma)."<br />";
		}
	}else{
		$xconsumidor_nome =	"'".str_replace("'","",$consumidor_nome)."'";
	}

	$xconsumidor_cpf = preg_replace("/\D/","",$xconsumidor_cpf);
	$xconsumidor_cpf = trim(substr($xconsumidor_cpf,0,14));
	if (strlen($xconsumidor_cpf) ==	0){
		if($linha==549){
			$msg_erro .= traduz("digite.o.cpf.do.consumidor",$con,$cook_idioma)."<br />";
		}else{
			$xconsumidor_cpf = 'null';
		}
	}else{
		$xconsumidor_cpf = "'".$xconsumidor_cpf."'";
	}

	$xconsumidor_email = (strlen($xconsumidor_email) ==	0) ? 'null'	: "'".$xconsumidor_email."'";

	if($login_posto<>7214){
		if (strlen($xconsumidor_fone)==0 AND $xconsumidor_revenda=="C"){//HD 56051
			//HD 209229: Telefone do consumidor	obrigatório
			$msg_erro .= traduz("digite.o.telefone.do.consumidor",$con,$cook_idioma)."<br />";
		}

		if (strlen($xconsumidor_cidade)==0 AND $xconsumidor_revenda=="C"){
			$msg_erro .= traduz("digite.a.cidade.do.consumidor",$con,$cook_idioma)."<br />";
		}

		if (strlen($xconsumidor_estado)==0 and strlen($os) ==0){
			$msg_erro .= traduz("digite.o.estado.do.consumidor",$con,$cook_idioma)."<br />";
		}
	}

	if (strlen(trim($xconsumidor_bairro)) == 0){
		if($linha==549){
			$msg_erro .= traduz("digite.o.bairro.do.consumidor",$con,$cook_idioma)."<br />";
		}else{
			$xconsumidor_bairro	= 'null';
		}
	}

	if (strlen(trim($xconsumidor_endereco))	== 0){
		if($linha==549){
			$msg_erro .= traduz("digite.o.endereco.do.consumidor",$con,$cook_idioma)."<br />";
		}else{
			$xconsumidor_endereco =	'null';
		}
	}

	if (strlen(trim($xconsumidor_numero)) == 0){
		if($linha==549){
			$msg_erro .= traduz("digite.o.numero.do.endereco.do.consumidor",$con,$cook_idioma)."<br />";
		}else{
			$xconsumidor_numero	= 'null';
		}
	}

	$xconsumidor_complemento = (strlen($xconsumidor_complemento) ==	0)	? 'null' : "'" . $xconsumidor_complemento .	"'";
	$contrato =	($xcontrato	== 't')	? 't' :	'f';

	$xconsumidor_cep = preg_replace ("/\D/","",$xconsumidor_cep);
	$xconsumidor_cep = substr ($xconsumidor_cep,0,8);

	if (strlen(trim($xconsumidor_cep)) == 0){
		if ($linha == 549) {
			$msg_erro .= traduz("digite.o.cep.do.consumidor",$con,$cook_idioma)."<br />";
		} else {
			$xconsumidor_cep = 'null';
			$xconsumidor_consumidor_cep	= "'" .	$cep . "'";
		}
	}

#################### REVENDA ############################

	//HD 321132
	$revenda_cnpj = preg_replace("/\D/","",$revenda_cnpj);
	$revenda_cnpj = substr($revenda_cnpj,0,14);
	if($login_fabrica!=14 and $login_fabrica!=66){
		if (strlen($revenda_cnpj) == 0 or strlen($revenda_cnpj) < 14) {
			$msg_erro .= traduz("tamanho.do.cnpj.da.revenda.invalido",$con,$cook_idioma)."<br />";
		}
	}

	if($login_fabrica==14 and $login_fabrica==66){
		if (strlen($revenda_cnpj) < 14) {
			$msg_erro .= traduz("tamanho.do.cnpj.da.revenda.invalido",$con,$cook_idioma)."<br />";
		}
	}

	$xrevenda_cnpj = (strlen($revenda_cnpj)  == 0)  ? 'null' : "'".$revenda_cnpj."'";
	$xrevenda_nome = (strlen($xrevenda_nome) == 0) ? 'null' : "'".str_replace("'","",$xrevenda_nome)."'";
	$xrevenda_fone = (strlen($xrevenda_fone) == 0) ? 'null' : "'".str_replace("'","",$xrevenda_fone)."'";

	$xrevenda_cep = preg_replace("/\D/","",$xrevenda_cep);
	$xrevenda_cep = substr($xrevenda_cep,0,8);

	$xrevenda_cep = (strlen($xrevenda_cep) == 0) ? "null" : "'" . $xrevenda_cep . "'";

	$obs = trim($_POST["obs"]);
	$obs = (strlen($obs) > 0) ? "'".$obs."'" : "null";

	$xrevenda_endereco    = (strlen($xrevenda_endereco) == 0)    ? 'null' : "'".str_replace("'","",trim($_POST['revenda_endereco']))."'";
	$xrevenda_numero      = (strlen($xrevenda_numero) == 0)      ? 'null' : "'".str_replace("'","",$xrevenda_numero)."'";
	$xrevenda_complemento = (strlen($xrevenda_complemento) == 0) ? 'null' : "'".str_replace("'","",$xrevenda_complemento)."'";
	$xrevenda_bairro	  = (strlen($xrevenda_bairro) == 0)      ? 'null' : "'".str_replace("'","",$xrevenda_bairro)."'";
	$xrevenda_cidade	  = (strlen($xrevenda_cidade) == 0)      ? 'null' : "'".str_replace("'","",$xrevenda_cidade)."'";
	$xrevenda_estado	  = (strlen($xrevenda_estado) == 0)      ? 'null' : "'".str_replace("'","",$xrevenda_estado)."'";

	$sql = "SELECT tbl_produto.produto,	tbl_produto.linha, tbl_produto.familia, tbl_produto.garantia
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  UPPER(tbl_produto.referencia_pesquisa) = UPPER($produto_referencia::text)
			AND    tbl_linha.fabrica = $login_fabrica
			AND    tbl_produto.ativo IS TRUE";

	$res = @pg_query ($con,$sql);

	if (@pg_num_rows($res) == 0) {
		$msg_erro .= traduz("produto.%.nao.cadastrado",$con,$cook_idioma,$produto_referencia)."<br />";
	} else {
		$produto   = pg_fetch_result($res,0,produto);
		$linha	   = pg_fetch_result($res,0,linha);
		$familia   = pg_fetch_result($res,0,familia);
		$garantia  = pg_fetch_result($res,0,garantia);
	}

	if ($login_posto<>7214 and $linha != 549 and $familia != 921) { //HD 55014 9/12/2008, HD 240348 31/05/2010
		if (strlen($xnota_fiscal) == 0){
			$msg_erro .= traduz("digite.o.numero.da.nota.fiscal",$con,$cook_idioma)."<br />";
		} else {
			$xnota_fiscal =	"'".$xnota_fiscal."'";
		}
	} else {
		//HD 209229: Nota Fiscal Obrigatória
		if (strlen($xnota_fiscal) == 0) {
			if ($linha == 549) {
				$xnota_fiscal = 999999;
			} else if ($familia == 921) {
				$xnota_fiscal = 'null';
			} else {
				$msg_erro .= traduz("digite.o.numero.da.nota.fiscal",$con,$cook_idioma)."<br />";
			}
		} else {
			$xnota_fiscal =	"'".$xnota_fiscal."'";
		}
	}

	############################## // FIM DA REVENDA

	if (strlen ($qtde_produtos)	== 0){
		$qtde_produtos = "1";
	}

	$xtroca_faturada = (strlen ($xtroca_faturada) == 0)	? 'null' : "'".$xtroca_faturada."'";

	if ($xdata_nf == null and $xtroca_faturada <> 't' and $linha <>	549) {
		$msg_erro .= traduz("digite.a.data.de.compra",$con,$cook_idioma);
	} else {
		if ($linha == 549 and ($xdata_nf ==	'null' OR $xdata_nf	== ''))	{
			$xdata_nf =	strtotime(date('Y-m-d'));
			$end = mktime(0,0,0,date('m',$xdata_nf)-$garantia, date('d', $xdata_nf), date('Y', $xdata_nf));
			$xdata_nf =	"'".date('Y-m-d',$end)."'";
		}
	}

//  HD 246273 - 01/06/2010 MLG - Permitir alterar a data de compra SE NÃO FOI GRAVADA NA HORA DO CADASTRO. É obrigatória desde HD 209229.
	if (strlen(trim($xdata_nf)) > 0 and is_numeric($os)) {
		$atualiza_data_compra = (strlen(@pg_fetch_result(@pg_query($con, "SELECT  data_nf FROM tbl_os WHERE os = $os"), 0, 0)) < 10);
	}

	//HD 209229: Data da Nota Fiscal (Data Compra) Obrigatória
	if ($xdata_nf == "null"	|| $xdata_nf ==	null ||	strlen($xdata_nf) == 0)	{
		$msg_erro .= traduz("digite.a.data.de.compra",$con,$cook_idioma) . "<br />";
	}

	$xproduto_serie		= (strlen($xproduto_serie) == 0) ? 'null' :	"'". strtoupper($xproduto_serie) ."'";
	$xcodigo_fabricacao	= (strlen($xcodigo_fabricacao) == 0) ? 'null' :	"'".$xcodigo_fabricacao."'";

	$sql_ap	= "	SELECT peca,descricao
			 FROM tbl_peca
			 WHERE fabrica = $login_fabrica
			 AND   peca	in ( 866587, 866585, 866584, 866583, 866582, 866581, 866580, 866579, 866578, 866577, 866576, 866575)
			 ORDER BY peca,descricao";
	$res_ap	= pg_query($con,$sql_ap);
	if(pg_num_rows($res_ap)	> 0) {
		for($i =0 ;$i< pg_num_rows($res_ap);$i++){
			$peca_ap		 = pg_fetch_result($res_ap,$i,peca);
			$descricao_ap =	pg_fetch_result($res_ap,$i,descricao);
			$sqlx= "SELECT produto_aparencia,descricao
					FROM tbl_produto_aparencia
					WHERE fabrica =	$login_fabrica
					ORDER BY produto_aparencia";
			$resx =	pg_query($con,$sqlx);
			for($j = 0;$j<pg_num_rows($resx);$j++){
				$aparencia_descricao = "";
				$produto_aparencia = pg_fetch_result($resx,$j,produto_aparencia);
				$descricao_aparencia = pg_fetch_result($resx,$j,descricao);
				$aparencia_descricao = $_POST[$peca_ap."".$produto_aparencia];
				if(strlen($aparencia_descricao)	> 0){
					$xaparencia_produto	.="	$descricao_ap($descricao_aparencia)	- ";
				}
			}
		}
	}

	$xaparencia_produto	= (strlen($xaparencia_produto) == 0) ? 'null' :	"'".$xaparencia_produto."'";
	$voltagem			= (strlen($voltagem) ==	0) ? "null"	: "'".$voltagem."'";
	$xacessorios		= (strlen($xacessorios)	== 0) ?	'null' : "'".$xacessorios."'";

	//HD 209229: Obrigatoriedades de defeito reclamado
	//O	sistema	trata para que na linha	549	- CELULAR não seja necessário selecionar
	//defeito constatado e solução,	portanto foi excluído aqui também
	if ($linha == 549) {
		$xdefeito_reclamado_descricao =	(strlen($xdefeito_reclamado_descricao) == 0) ? 'null' :	"'".$xdefeito_reclamado_descricao."'";

		if (strlen($xdefeito_reclamado)	== 0){
			$xdefeito_reclamado	= 'null';
		}
	}
	else {
		if (strlen($xdefeito_reclamado)	== 0){
			$msg_erro .= traduz("selecione.o.defeito.reclamado",$con,$cook_idioma) . "<br />";
		}
		else {
			$xdefeito_reclamado_descricao =	"'".$xdefeito_reclamado_descricao."'";
		}
	}

	$xobs =	(strlen($xobs) == 0) ? 'null' :	"'".$xobs."'";
	$xquem_abriu_chamado = (strlen($xquem_abriu_chamado) ==	0) ? 'null'	: "'".$xquem_abriu_chamado."'";

	######### CONSUMIDOR REVENDA
	if (strlen($xconsumidor_revenda) ==	0){
		if(strlen($os)==0){
			$msg_erro .= traduz("selecione.consumidor.ou.revenda",$con,$cook_idioma);
		}
	}else{
		$xconsumidor_revenda = "'".$xconsumidor_revenda."'";
	}

	$xsatisfacao	= (strlen($xsatisfacao)	== 0) ?	"'f'" :	"'".$xsatisfacao."'";
	$xlaudo_tecnico	= (strlen ($xlaudo_tecnico)	== 0) ?	'null' : "'".$xlaudo_tecnico."'";

	if ($login_posto <>7214	and	$login_posto <>	13562) { //	HD 76270
		if (strlen($msg_erro)==0 AND strlen($produto_referencia) > 0 AND (strlen($xproduto_serie) == 0 OR $xproduto_serie == 'null')) {
			$sql = "SELECT	tbl_produto.numero_serie_obrigatorio
					FROM	tbl_produto
					JOIN	tbl_linha on tbl_linha.linha = tbl_produto.linha
					WHERE	upper(tbl_produto.referencia) =	upper($produto_referencia)
					AND		tbl_linha.fabrica =	$login_fabrica";
			$res = pg_query($con,$sql);
			if (@pg_num_rows($res) > 0)	{
				$numero_serie_obrigatorio =	trim(pg_fetch_result($res,0,numero_serie_obrigatorio));

				if ($numero_serie_obrigatorio == 't') {
					$msg_erro .= "<br />".traduz("n.de.serie.%.e.obrigatorio",$con,$cook_idioma,$produto_referencia);
				}
			}
		}
	}
	##### FIM DA VALIDAÇÃO DOS CAMPOS #####

	$os_reincidente	= "'f'";

	$produto = 0;
	if(strlen($msg_erro)==0	AND	strlen($produto_referencia)>0){
		$sql = "SELECT tbl_produto.produto,	tbl_produto.linha
				FROM   tbl_produto
				JOIN   tbl_linha USING (linha)
				WHERE  UPPER(tbl_produto.referencia_pesquisa) =	UPPER($produto_referencia::text)
				AND	   tbl_linha.fabrica	  =	$login_fabrica
				AND	   tbl_produto.ativo IS	TRUE";
		$res = @pg_query ($con,$sql);
		if (@pg_num_rows ($res)	== 0) {
			$msg_erro .= traduz("produto.%.nao.cadastrado",$con,$cook_idioma,$produto_referencia);
		}else{
			$produto = pg_fetch_result($res,0,produto);
			$linha	 = pg_fetch_result($res,0,linha);
		}
	}
	$xtipo_os_cortesia = 'null';

	if ($login_fabrica == 14 and $login_posto == '0') {

		$produto_n_valida =	array(24324,23700,24537,23699,23583,23659,23750,24323,23582,23717,23696,23694,23697,23698,34588,23695,35518,37886,37933,37940,37941,37942,37943,24330);
		if (!in_array($produto,$produto_n_valida)) {
			$sql = "SELECT tbl_numero_serie.*, tbl_produto.descricao, tbl_produto.linha, tbl_produto.familia
				FROM tbl_numero_serie
				JOIN tbl_produto
				USING (produto)
				WHERE serie	= $xproduto_serie
				AND	fabrica	= $login_fabrica
				/* produto O e M intelbras*/
				AND	tbl_produto.produto	not	in (24324,23700,24537,23699,23583,23659,23750,24323,23582,23717,23696,23694,23697,23698,34588,23695,35518,37886,37933,37940,37941,37942,37943,24330)";
			$res = pg_exec ($con,$sql);
			if (pg_numrows ($res) == 0)	{
				$msg_erro =	"Número	de Série não encontrado";
			}
			else {
				$key_cod = pg_result($res,0,'key');

				if (strlen($key_cod)>0)	{
					if ($key_cod !=	$_POST['keycode']) {
						$msg_erro =	'Por favir digite um Key-Code válido';
					}
				}
			}
		}
	}


	$xnumero_controle =	(strlen($numero_controle)>0) ? "'" . $numero_controle .	"'"	: "null";

	###############	OS DIGITADA	PELO DISTRIBUIDOR
	$digitacao_distribuidor	= "null";

	if ($distribuidor_digita ==	't'){
		$codigo_posto =	strtoupper (trim ($_POST['codigo_posto']));
		$codigo_posto =	str_replace	(" ","",$codigo_posto);
		$codigo_posto =	str_replace	(".","",$codigo_posto);
		$codigo_posto =	str_replace	("/","",$codigo_posto);
		$codigo_posto =	str_replace	("-","",$codigo_posto);

		if (strlen ($codigo_posto) > 0)	{
			$sql = "SELECT posto FROM tbl_posto_fabrica	WHERE fabrica =	$login_fabrica AND codigo_posto	= '$codigo_posto' AND credenciamento = 'CREDENCIADO'";
			$res = @pg_query($con,$sql);
			if (pg_num_rows	($res) <> 1) {
				$msg_erro =	traduz("posto.%.nao.cadastrado",$con,$cook_idioma,$codigo_posto);
				$posto = $login_posto;
			}else{
				$posto = pg_fetch_result($res,0,0);
				if ($posto <> $login_posto) {
					$sql = "SELECT posto FROM tbl_posto_linha WHERE	posto =	$posto AND distribuidor	= $login_posto AND linha = $linha";
					$res = @pg_query($con,$sql);
					if (pg_num_rows	($res) <> 1) {
						$msg_erro =	traduz("posto.%.nao.pertence.a.sua.regiao",$con,$cook_idioma,$codigo_posto);
						$posto = $login_posto;
					}else{
						$posto = pg_fetch_result($res,0,0);
						$digitacao_distribuidor	= $login_posto;
					}
				}
			}
		}
	}

	$res = @pg_query($con,"BEGIN TRANSACTION");

	if (strlen ($os_offline) ==	0) {
		$os_offline	= "null";
	}

	if (strlen($msg_erro) == 0){

		/*================ INSERE NOVA OS =========================*/
		if (strlen($os)	== 0) {
			$sql =	"INSERT	INTO tbl_os	(
						tipo_atendimento											   ,
						posto														   ,
						fabrica														   ,
						sua_os														   ,
						sua_os_offline												   ,
						data_abertura												   ,
						cliente														   ,
						revenda														   ,
						consumidor_nome												   ,
						consumidor_cpf												   ,
						consumidor_fone												   ,
						consumidor_endereco											   ,
						consumidor_numero											   ,
						consumidor_complemento										   ,
						consumidor_bairro											   ,
						consumidor_cep												   ,
						consumidor_cidade											   ,
						consumidor_estado											   ,
						consumidor_email											   ,
						revenda_cnpj												   ,
						revenda_nome												   ,
						revenda_fone												   ,
						nota_fiscal													   ,
						data_nf														   ,
						produto														   ,
						serie														   ,
						qtde_produtos												   ,
						codigo_fabricacao											   ,
						aparencia_produto											   ,
						acessorios													   ,
						defeito_reclamado_descricao									   ,
						defeito_reclamado											   ,
						obs															   ,
						quem_abriu_chamado											   ,
						consumidor_revenda											   ,
						satisfacao													   ,
						laudo_tecnico												   ,
						tipo_os_cortesia											   ,
						troca_faturada												   ,
						os_offline													   ,
						os_reincidente												   ,
						digitacao_distribuidor										   ,
						tipo_os														   ,
						serie_reoperado
					) VALUES (
						$tipo_atendimento											   ,
						$posto														   ,
						$login_fabrica												   ,
						$sua_os														   ,
						$sua_os_offline												   ,
						$xdata_abertura												   ,
						null														   ,
						(SELECT	revenda	FROM tbl_revenda WHERE cnpj	= $xrevenda_cnpj limit 1)  ,
						$xconsumidor_nome											   ,
						$xconsumidor_cpf											   ,
						'$xconsumidor_fone'											   ,
						'$xconsumidor_endereco'										   ,
						'$xconsumidor_numero'										   ,
						$xconsumidor_complemento									   ,
						'$xconsumidor_bairro'										   ,
						'$xconsumidor_cep'											   ,
						'$xconsumidor_cidade'										   ,
						'$xconsumidor_estado'										   ,
						$xconsumidor_email											   ,
						$xrevenda_cnpj												   ,
						$xrevenda_nome												   ,
						$xrevenda_fone												   ,
						$xnota_fiscal												   ,
						$xdata_nf													 ,
						$produto													   ,
						$xproduto_serie												   ,
						$qtde_produtos												   ,
						$xcodigo_fabricacao											   ,
						$xaparencia_produto											   ,
						$xacessorios												   ,
						$xdefeito_reclamado_descricao								   ,
						$xdefeito_reclamado											   ,
						$obs														   ,
						$xquem_abriu_chamado										   ,
						$xconsumidor_revenda										   ,
						$xsatisfacao												   ,
						$xlaudo_tecnico												   ,
						$xtipo_os_cortesia											   ,
						$xtroca_faturada											   ,
						$os_offline													   ,
						$os_reincidente												   ,
						$digitacao_distribuidor										   ,
						$x_locacao													   ,
						$xnumero_controle
					);";
		}else{
			//HD 214236: Auditoria Prévia de OS: se a OS estava em auditoria e foi recusada,
			//deve atualizar o campo tbl_os_auditar.alterada_data com a data/hora atual
			$sql = "
			SELECT
			os_auditar,
			cancelada,
			liberado

			FROM
			tbl_os_auditar

			WHERE
			fabrica=$login_fabrica
			AND os=$os
			AND os_auditar=(
				SELECT
				MAX(ultima_auditoria.os_auditar)
				
				FROM
				tbl_os_auditar AS ultima_auditoria
				
				WHERE
				ultima_auditoria.fabrica=$login_fabrica
				AND ultima_auditoria.os=$os
			)
			AND (tbl_os_auditar.cancelada IS TRUE OR tbl_os_auditar.liberado IS TRUE)
			";
			$res = pg_query($con, $sql);
			if (pg_errormessage($con)) {
				$msg_erro = "Falha no processo de auditoria prévia, entre em contato com o fabricante";
			}

			if (pg_num_rows($res)) {
				$os_auditar = pg_result($res, 0, os_auditar);
				$liberado = pg_result($res, 0, liberado);
				$cancelada = pg_result($res, 0, cancelada);
				
				if ($cancelada == 't') {
					$sql =	"
					UPDATE tbl_os
					SET
					tipo_atendimento = $tipo_atendimento,
					posto = $posto,
					fabrica = $login_fabrica,
					sua_os = $sua_os,
					sua_os_offline = $sua_os_offline,
					data_abertura = $xdata_abertura,
					cliente = null,
					revenda = (SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj limit 1),
					consumidor_nome = $xconsumidor_nome,
					consumidor_cpf = $xconsumidor_cpf,
					consumidor_fone = '$xconsumidor_fone',
					consumidor_endereco = '$xconsumidor_endereco',
					consumidor_numero = '$xconsumidor_numero',
					consumidor_complemento = $xconsumidor_complemento,
					consumidor_bairro = '$xconsumidor_bairro',
					consumidor_cep = '$xconsumidor_cep',
					consumidor_cidade = '$xconsumidor_cidade',
					consumidor_estado = '$xconsumidor_estado',
					consumidor_email = $xconsumidor_email,
					revenda_cnpj = $xrevenda_cnpj,
					revenda_nome = $xrevenda_nome,
					revenda_fone = $xrevenda_fone,
					nota_fiscal = $xnota_fiscal,
					data_nf = $xdata_nf,
					produto = $produto,
					serie = $xproduto_serie,
					qtde_produtos = $qtde_produtos,
					codigo_fabricacao = $xcodigo_fabricacao,
					aparencia_produto = $xaparencia_produto,
					acessorios = $xacessorios,
					defeito_reclamado_descricao = $xdefeito_reclamado_descricao,
					defeito_reclamado = $xdefeito_reclamado,
					obs = $obs,
					quem_abriu_chamado = $xquem_abriu_chamado,
					consumidor_revenda = $xconsumidor_revenda,
					satisfacao = $xsatisfacao,
					laudo_tecnico = $xlaudo_tecnico,
					tipo_os_cortesia = $xtipo_os_cortesia,
					troca_faturada = $xtroca_faturada,
					os_offline = $os_offline,
					os_reincidente = $os_reincidente,
					digitacao_distribuidor = $digitacao_distribuidor,
					tipo_os = $x_locacao,
					serie_reoperado = $xnumero_controle

					WHERE
					tbl_os.os=$os
					AND tbl_os.posto=$login_posto
					AND tbl_os.fabrica=$login_fabrica
					";
					@$res = pg_query($con, $sql);
					if (pg_errormessage($con)) {
						$msg_erro = "Falha na atualização dos dados da OS, entre em contato com o fabricante";
					}
				}
			}

//			HD 246273 - 01/06/2010 MLG - Permitir alterar a data de compra SE NÃO FOI GRAVADA NA HORA DO CADASTRO. É obrigatória desde HD 209229.
			$atualiza_data_nf = ($atualiza_data_compra) ? "data_nf             = $xdata_nf			 ," : '';
			$sql =	"UPDATE	tbl_os SET
						$atualiza_data_nf
						aparencia_produto	= $xaparencia_produto,
						obs					= $obs				 ,
						defeito_reclamado	= $xdefeito_reclamado
					WHERE os	  =	$os
					AND	  fabrica =	$login_fabrica
					AND	  posto	  =	$posto;";
		}

		$res = @pg_query ($con,$sql);
		if (strlen (pg_errormessage($con)) > 0 ) {
			$msg_erro =	pg_errormessage($con);
		}
	}

	if (strlen ($msg_erro) == 0) {
		if (strlen($os)	== 0) {
			$res = @pg_query ($con,"SELECT CURRVAL ('seq_os')");
			$os	 = pg_fetch_result($res,0,0);
		}

		if (strlen ($msg_erro) == 0) {
			if (strlen($defeito_constatado)	== 0) $defeito_constatado =	'null';

			if (strlen ($defeito_constatado) > 0) {
				$sql = "UPDATE tbl_os SET defeito_constatado = $defeito_constatado
						WHERE  tbl_os.os	= $os
						AND	   tbl_os.posto	= $login_posto;";
				$res = @pg_query ($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
		}

		if (strlen ($msg_erro) == 0) {
			$xcausa_defeito	= $_POST ['causa_defeito'];
			if (strlen ($xcausa_defeito) ==	0) $xcausa_defeito = "null";
			if (strlen ($xcausa_defeito) > 0) {
				$sql = "UPDATE tbl_os SET causa_defeito	= $xcausa_defeito
						WHERE  tbl_os.os	= $os
						AND	   tbl_os.posto	= $login_posto;";
				$res = @pg_query ($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
		}

		if (strlen ($msg_erro) == 0) {
			$x_solucao_os =	$_POST['solucao_os'];
			if (strlen($x_solucao_os) == 0)	$x_solucao_os =	'null';
			else							$x_solucao_os =	"'".$x_solucao_os."'";
			$x_solucao_os2 = trim($_POST['solucao_os2']);
			if(strlen($x_solucao_os2) >	0) {
				$sql = "INSERT INTO	tbl_servico_realizado(fabrica,descricao,ativo)values($login_fabrica,'$x_solucao_os2','f')";
				$res = pg_query($con,$sql);
				$sql = "SELECT currval ('seq_servico_realizado')";
				$res = pg_query($con,$sql);
				$x_solucao_os =	pg_fetch_result($res,0,0);
			}
			$sql = "UPDATE tbl_os SET solucao_os = $x_solucao_os
					WHERE  tbl_os.os	= $os
					AND	   tbl_os.posto	= $login_posto;";
			$res = @pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

		$res = @pg_query ($con,"SELECT fn_valida_os($os, $login_fabrica)");
		if (strlen (pg_errormessage($con)) > 0 ) {
			$msg_erro .= pg_errormessage($con);
		}


		if (strlen ($msg_erro) == 0) {
	//HD 198693	- MLG 09/02/2010 - Anexo de	imagem da NF
	//HD 240348 - MLG 26/05/2010 - Não obrigatório para a familia de 'Segurança Eletrônica'
	$sql = "SELECT familia, CASE WHEN tbl_produto.familia = 921 THEN false ELSE true END AS nf_obrigatoria
			FROM tbl_produto
			JOIN tbl_familia USING(familia)
			WHERE tbl_produto.referencia	= $produto_referencia
			AND	  tbl_familia.fabrica	= $login_fabrica";
	$res = @pg_query($con,$sql);

	if(is_resource($res)) {
		$nf_obrig = (@pg_fetch_result($res,0,nf_obrigatoria)=='t') ? true : false;
	}
// exit ("Familia: ".pg_fetch_result($res,0,familia).", NF Obrigatória: ".$nf_obrig."<br />");
	$veio_img_nf = ($_FILES['foto']['name']	!= '');
	if ($veio_img_nf and $os != '') {
		$img_erro = anexaNF($os, $_FILES['foto']);
		$img_msg_erro = (is_numeric($img_erro)) ? '' : $img_erro;
		unset ($img_erro);
	}
// 	FIM	Anexa imagem NF

// 	if (!file_exists("./nf_digitalizada/$os.jpg") and $nf_obrig) {
// 		$img_msg_erro =	' Não foi anexada a	imagem da Nota Fiscal!';
// 	} else
		}
//	if ($os and !temNF($os, 'bool') and $nf_obrig and !$img_msg_erro) $msg_erro .= 'Por favor, anexe a Nota Fiscal';

		#--------- grava OS_EXTRA ------------------
		if (strlen ($msg_erro) == 0) {
			if (strlen($msg_erro) == 0 AND strlen ($revenda_cnpj) >	0 and strlen ($xrevenda_cidade)	> 0	and	strlen ($xrevenda_estado) >	0 and $xrevenda_estado <>'null'	and	$xrevenda_cidade <>	'null' AND strlen($os) >0) {

				$sql = "SELECT fnc_qual_cidade ($xrevenda_cidade,$xrevenda_estado)";

				$res = pg_query	($con,$sql);
				$xrevenda_cidade = pg_fetch_result($res,0,0);

				$sql  =	"SELECT	revenda	FROM tbl_revenda WHERE cnpj	= $xrevenda_cnpj";
				$res1 =	pg_query ($con,$sql);

				if (pg_num_rows($res1) > 0)	{
					$revenda = pg_fetch_result($res1,0,revenda);
					$sql = "UPDATE tbl_revenda SET
								nome		= $xrevenda_nome		  ,
								cnpj		= $xrevenda_cnpj		  ,
								fone		= $xrevenda_fone		  ,
								endereco	= $xrevenda_endereco	  ,
								numero		= $xrevenda_numero		  ,
								complemento	= $xrevenda_complemento	  ,
								bairro		= $xrevenda_bairro		  ,
								cep			= $xrevenda_cep			  ,
								cidade		= $xrevenda_cidade
							WHERE tbl_revenda.revenda =	$revenda";
					$res3 =	@pg_query ($con,$sql);

					if (strlen (pg_errormessage($con)) > 0){
						$msg_erro =	pg_errormessage	($con);
					}
				}else{
					$sql = "INSERT INTO	tbl_revenda	(
								nome,
								cnpj,
								fone,
								endereco,
								numero,
								complemento,
								bairro,
								cep,
								cidade
							) VALUES (
								$xrevenda_nome ,
								$xrevenda_cnpj ,
								$xrevenda_fone ,
								$xrevenda_endereco ,
								$xrevenda_numero ,
								$xrevenda_complemento ,
								$xrevenda_bairro ,
								$xrevenda_cep ,
								$xrevenda_cidade
							)";
					$res3 =	@pg_query ($con,$sql);

					if (strlen (pg_errormessage($con)) > 0){
						$msg_erro =	pg_errormessage	($con);
					}
					$sql = "SELECT currval ('seq_revenda')";
					$res3 =	@pg_query ($con,$sql);
					$revenda = @pg_fetch_result	($res3,0,0);
				}

				$sql = "UPDATE tbl_os SET revenda =	$revenda WHERE os =	$os	AND	fabrica	= $login_fabrica";
				$res = @pg_query ($con,$sql);
			}

			$taxa_visita				= str_replace (",",".",trim($_POST['taxa_visita']));
			$visita_por_km				=					   trim($_POST['visita_por_km']);
			$hora_tecnica				= str_replace (",",".",trim($_POST['hora_tecnica']));
			$regulagem_peso_padrao		= str_replace (",",".",trim($_POST['regulagem_peso_padrao']));
			$certificado_conformidade	= str_replace (",",".",trim($_POST['certificado_conformidade']));
			$valor_diaria				= str_replace (",",".",trim($_POST['valor_diaria']));

			if (strlen($taxa_visita)				== 0) $taxa_visita					= '0';
			if (strlen($visita_por_km)				== 0) $visita_por_km				= 'f';
			if (strlen($hora_tecnica)				== 0) $hora_tecnica					= '0';
			if (strlen($regulagem_peso_padrao)		== 0) $regulagem_peso_padrao		= '0';
			if (strlen($certificado_conformidade)	== 0) $certificado_conformidade		= '0';
			if (strlen($valor_diaria)				== 0) $valor_diaria					= '0';

			$sql = "UPDATE tbl_os_extra	SET
						taxa_visita				 = $taxa_visita				,
						visita_por_km			 = '$visita_por_km'			,
						hora_tecnica			 = $hora_tecnica			,
						regulagem_peso_padrao	 = $regulagem_peso_padrao	,
						certificado_conformidade = $certificado_conformidade,
						valor_diaria			 = $valor_diaria ";

			if ($os_reincidente	== "'t'") {
				$sql .=	", os_reincidente =	$xxxos ";
			}

			$sql .=	"WHERE tbl_os_extra.os = $os";
			$res = @pg_query ($con,$sql);
			if (strlen (pg_errormessage($con)) > 0 ) {
				$msg_erro =	pg_errormessage($con);
			}

			if ($login_fabrica == 14) {
				$sql = "SELECT	intervencao_tecnica,linha
							FROM	tbl_produto
							WHERE	produto	= $produto";

				$res = @pg_query($con,$sql);
				if (pg_num_rows($res) >	0) {

					$intervencao_tecnica = trim(pg_fetch_result($res,0,intervencao_tecnica));
					$linha = trim(pg_fetch_result($res,0,linha));

					if ($intervencao_tecnica=='t') {
						$sql_intervencao = "SELECT status_os
											FROM  tbl_os_status
											WHERE os = $os
											AND	status_os IN (62,64,65)
											ORDER BY data DESC
											LIMIT 1";
						$res_intervencao = pg_query($con, $sql_intervencao);
						$status_os = "";
						if (pg_num_rows	($res_intervencao) > 0){
							$status_os = trim(pg_fetch_result($res_intervencao,0,status_os));
						}

						if (pg_num_rows	($res_intervencao) == 0	or $status_os == "64"){
							/* HD 144313 - Não inserir para	o posto	interno	da Intelbras */
							if ($intervencao_tecnica ==	't'	and	$login_posto !=	7214) {	# HD 13826
								$sql = "INSERT INTO	tbl_os_status (os,status_os,data,observacao) values	($os,62,current_timestamp,'OS com intervenção técnica')";
									$res = @pg_query ($con,$sql);

									if (strlen (pg_errormessage($con)) > 0 ) {
									$msg_erro =	pg_errormessage($con);
									}
							}



							/* HD 144313 - Não inserir para	o posto	interno	da Intelbras */
							if ($linha == 549 and $login_posto != 7214)	{
								$sql = "INSERT INTO	tbl_os_status
										(os,status_os,data,observacao)
										VALUES ($os,65,current_timestamp,'Reparo do	produto	deve ser feito pela	fábrica')";

								$res = pg_query($con,$sql);
								$msg_erro =	pg_errormessage($con);

								$sql = "INSERT INTO	tbl_os_retorno (os)	VALUES ($os)";
								$res = pg_query($con,$sql);
								$msg_erro =	pg_errormessage($con);
							}
						}
					}
				}
			}

			if (strlen ($msg_erro) > 0)	{
				$os	= "";
			}

			if (strlen ($msg_erro) > 0)	{
				$res = @pg_query ($con,"ROLLBACK TRANSACTION");
				$os	= "";
			}
		}else{
			$res = @pg_query ($con,"ROLLBACK TRANSACTION");
			$os	= "";
		}
	}else{

		if (strpos ($msg_erro,"new row for relation	\"tbl_os\" violates	check constraint \"data_nf\"") > 0)
		$msg_erro =	traduz("data.da.compra.maior.que.a.data.da.abertura.da.ordem.de.servico",$con,$cook_idioma);

		if (strpos ($msg_erro,"new row for relation	\"tbl_os\" violates	check constraint \"data_abertura_futura\"")	> 0)
		$msg_erro =	traduz("data.da.abertura.deve.ser.inferior.ou.igual.a.data.de.digitacao.da.os.no.sistema.(data.de.hoje)",$con,$cook_idioma);

		if (strpos ($msg_erro,"tbl_os_unico") >	0)
			$msg_erro =	traduz("o.numero.da.ordem.de.servico.do.fabricante.ja.esta.cadastrado",$con,$cook_idioma);

		$res = @pg_query ($con,"ROLLBACK TRANSACTION");
		$os	= "";
	}

	######### FIM DA VALIDAÇÃO DA OS ########
	##########################################
	########## VALIDAÇÂO DOS ITENS ##########

		$defeito_constatado		= trim($_POST['defeito_constatado']);
		$data_fechamento		= trim($_POST['data_fechamento']);
		$data_conserto		= trim($_POST['data_conserto']);
	if (strlen($msg_erro)==0){
		$sql = "SELECT * FROM tbl_fabrica WHERE	fabrica	= $login_fabrica";
		$res = pg_query	($con,$sql);
		$pedir_causa_defeito_os_item		= trim(pg_fetch_result($res,0,pedir_causa_defeito_os_item));
		$pedir_defeito_constatado_os_item	= trim(pg_fetch_result($res,0,pedir_defeito_constatado_os_item));
		$ip_fabricante						= trim(pg_fetch_result($res,0,ip_fabricante));
		$ip_acesso							= $_SERVER['REMOTE_ADDR'];
		$os_item_admin						= "null";
		$sql = "SELECT fn_valida_os_reincidente($os,$login_fabrica)";
		$res1 =	pg_query ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	if (strlen($data_fechamento) > 0){
		$xdata_fechamento =	fnc_formata_data_pg	($data_fechamento);
		if($xdata_fechamento > "'".date("Y-m-d")."'") {
			$msg_erro =	traduz("data.de.fechamento.maior.que.a.data.de.hoje",$con,$cook_idioma);
		}
	}

	if (strlen($data_conserto) > 0){
		$xdata_conserto	= fnc_formata_data_pg ($data_conserto);
		if($xdata_conserto > "'".date("Y-m-d")."'")	{
			$msg_erro =	"Data de conserto maior	que	data Atual";
		}
	}


		$qtde_item = $_POST['qtde_item'];

		for	($i	= 0	; $i < $qtde_item ;	$i++) {
			$xos_item		 = $_POST['os_item_'		. $i];
			$xos_produto	 = $_POST['os_produto_'		. $i];
			$xproduto		 = $_POST['produto_'		. $i];
			$xserie			 = $_POST['serie_'			. $i];
			$xposicao		 = $_POST['posicao_'		. $i];
			$xpeca			 = $_POST['peca_'			. $i];
			$xqtde			 = $_POST['qtde_'			. $i];
			$xdefeito		 = $_POST['defeito_'		. $i];
			$xservico		 = $_POST['servico_'		. $i];
			$xpcausa_defeito = $_POST['pcausa_defeito_'	. $i];

			$xproduto =	str_replace	("." , "" ,	$xproduto);
			$xproduto =	str_replace	("-" , "" ,	$xproduto);
			$xproduto =	str_replace	("/" , "" ,	$xproduto);
			$xproduto =	str_replace	(" " , "" ,	$xproduto);

			$xpeca	  =	str_replace	("." , "" ,	$xpeca);
			$xpeca	  =	str_replace	("-" , "" ,	$xpeca);
			$xpeca	  =	str_replace	("/" , "" ,	$xpeca);
			$xpeca	  =	str_replace	(" " , "" ,	$xpeca);

			$xserie	  =	(strlen($xserie) ==	0) ? 'null'	: "'" .	$xserie	. "'";
			$xposicao =	(strlen($xposicao) == 0) ? 'null' :	"'"	. $xposicao	. "'";

			$xadmin_peca	  =	$_POST["admin_peca_"	 . $i];
			if(strlen($xadmin_peca)==0 or $xadmin_peca=="P"){
				$xadmin_peca ="null";
			}

			if (strlen ($xos_produto) >	0 AND strlen($xpeca) ==	0) {
				$sql = "DELETE FROM	tbl_os_produto
						WHERE  tbl_os_produto.os_produto = $xos_produto";
				$res = @pg_query ($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}else{
				if (strlen($xpeca) > 0 and strlen($msg_erro) ==	0) {
					$xpeca	  =	strtoupper ($xpeca);

					if (strlen ($xqtde)	== 0) $xqtde = "1";

					if (strlen ($xproduto) == 0) {
						$sql = "SELECT tbl_os.produto
								FROM   tbl_os
								WHERE  tbl_os.os	  =	$os
								AND	   tbl_os.fabrica =	$login_fabrica;";
						$res = pg_query	($con,$sql);

						if (pg_num_rows($res) >	0) {
							$xproduto =	pg_fetch_result	($res,0,0);
						}
					}else{
						$sql = "SELECT tbl_produto.produto
								FROM   tbl_produto
								JOIN   tbl_linha USING (linha)
								WHERE  tbl_produto.referencia_pesquisa = '$xproduto'
								AND	   tbl_linha.fabrica = $login_fabrica";
						$res = pg_query	($con,$sql);

						if (pg_num_rows	($res) == 0) {
							$msg_erro .= traduz("produto.%.nao.cadastrado",$con,$cook_idioma,$xproduto);
							$linha_erro	= $i;
						}else{
							$xproduto =	pg_fetch_result	($res,0,produto);
						}
					}

					if (strlen ($msg_erro) == 0) {
						if (strlen($xos_produto) ==	0){
							$sql = "INSERT INTO	tbl_os_produto (
										os	   ,
										produto,
										serie
									)VALUES(
										$os		,
										$xproduto,
										$xserie
								);";
							$res = @pg_query ($con,$sql);
							$msg_erro .= pg_errormessage($con);

							$res = pg_query	($con,"SELECT CURRVAL ('seq_os_produto')");
							$xos_produto  =	pg_fetch_result	($res,0,0);
						}else{
							$sql = "UPDATE tbl_os_produto SET
										os		= $os	   ,
										produto	= $xproduto,
										serie	= $xserie
									WHERE os_produto = $xos_produto;";
							$res = @pg_query ($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}
						if (strlen ($msg_erro) > 0)	{
							break ;
						}else{

							$xpeca = strtoupper	($xpeca);

							if (strlen($xpeca) > 0)	{
								$sql = "SELECT tbl_peca.*
										FROM   tbl_peca
										WHERE  UPPER(tbl_peca.referencia_pesquisa) = UPPER('$xpeca')
										AND	   tbl_peca.fabrica	= $login_fabrica;";
								$res = pg_query	($con,$sql);

								if (pg_num_rows	($res) == 0) {
									$msg_erro .= traduz("peca.%.nao.cadastrada",$con,$cook_idioma);
									$linha_erro	= $i;
								}else{
									$xpeca					  =	pg_fetch_result	($res,0,peca);
									$intervencao_fabrica_peca =	pg_fetch_result	($res,0,retorna_conserto);
									$troca_obrigatoria_peca	  =	pg_fetch_result	($res,0,troca_obrigatoria);
									$bloqueada_garantia_peca  =	pg_fetch_result	($res,0,bloqueada_garantia);
									$bloqueada_peca_critica	  =	pg_fetch_result	($res,0,peca_critica);
									$previsao_entrega_peca	  =	pg_fetch_result	($res,0,previsao_entrega);
								}

								if (strlen($xdefeito) == 0){
									$msg_erro .= traduz("favor.informar.o.defeito.da.peca",$con,$cook_idioma);
								}
								if (strlen($xservico) == 0){
									$msg_erro .= "Favor	informar o serviço realizado"; #$servico = "null";
								}

								if(strlen($xpcausa_defeito)	== 0){
									$xpcausa_defeito = 'null';
								}
								$sqlp =	"SELECT	tbl_peca.*
										FROM   tbl_peca
										WHERE  (UPPER(tbl_peca.referencia_pesquisa)	= UPPER('$xpeca') OR tbl_peca.peca = '$xpeca')
										AND	   tbl_peca.fabrica	= $login_fabrica
										AND	   UPPER(tbl_peca.descricao) like '%PLACA%';";
								$resp =	pg_query($con,$sqlp);

								$encontrou = (pg_num_rows($resp) > 0) ?	't'	: 'f';

								if($encontrou == "t" AND $xservico == 82){
									$msg_erro =	traduz("para.a.peca.escolhida.nao.pode.haver.troca.de.componente",$con,$cook_idioma);
								}

								if($encontrou == "f" AND $xservico == 83){
									//$msg_erro	= traduz("para.a.peca.escolhida.nao.pode.haver.troca.de.placa",$con,$cook_idioma);
								}

								if (strlen ($msg_erro) == 0) {

									if (strlen($xos_item) == 0){
										$sql = "INSERT INTO	tbl_os_item	(
													os_produto		  ,
													posicao			  ,
													peca			  ,
													qtde			  ,
													defeito			  ,
													causa_defeito	  ,
													servico_realizado ,
													admin
												)VALUES(
													$xos_produto	,
													$xposicao		,
													$xpeca			,
													$xqtde			,
													$xdefeito		,
													$xpcausa_defeito,
													$xservico		,
													$xadmin_peca
											);";
										$res = @pg_query ($con,$sql);
										$msg_erro .= pg_errormessage($con);

										$res = @pg_query ($con,"SELECT CURRVAL ('seq_os_item')");
										$os_item  =	pg_fetch_result	($res,0,0);

									}else{
										$sql = "UPDATE tbl_os_item SET
													os_produto		  =	$xos_produto	,
													posicao			  =	$xposicao		,
													peca			  =	$xpeca			,
													qtde			  =	$xqtde			,
													defeito			  =	$xdefeito		,
													causa_defeito	  =	$xpcausa_defeito,
													servico_realizado =	$xservico		,
													admin			  =	$xadmin_peca
												WHERE os_item =	$xos_item";
										$res = @pg_query ($con,$sql);
										$msg_erro .= pg_errormessage($con);

										$os_item = $xos_item;
									}

									if (strlen ($msg_erro) > 0)	{
										break ;
									}

									# se for peça critica, entra em	OS com intervenção de suprimentos
									if ($login_fabrica==14 AND $bloqueada_peca_critica=='t'){
										$os_com_intervencao	= 't';
										$gravou_peca="sim";
									}
								}
							}
						}
					}
				}
			}

			if (strlen ($msg_erro) == 0	and	strlen($os_item) > 0) {
				$sql = "SELECT fn_valida_os_item($os, $login_fabrica)";
				$res	  =	@pg_query ($con,$sql);
				$msg_erro =	pg_errormessage($con);
			}
		}

		if (strlen($xdata_conserto)	> 0){
			if (strlen ($msg_erro) == 0) {
				$sql = "UPDATE tbl_os SET
						data_conserto	= $xdata_conserto
						WHERE  tbl_os.os	= $os
						AND	   tbl_os.posto	= $login_posto;";
				$res = @pg_query ($con,$sql);
				$msg_erro .= pg_errormessage($con);

				if (strlen($msg_erro)==0) {
					$novo_status_os	= 'CONSERTADO';
				}
			}
		}

		if (strlen($xdata_fechamento) >	0){
			if (strlen ($msg_erro) == 0) {
				$sql = "UPDATE tbl_os SET
						data_fechamento	  =	$xdata_fechamento
						WHERE  tbl_os.os	= $os
						AND	   tbl_os.posto	= $login_posto;";
				$res = @pg_query ($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
				$res = @pg_query ($con,$sql);
				$msg_erro .= pg_errormessage($con);

				#HD	213365
				if (strpos($msg_erro,"data_fechamento_anterior_abertura")){
					$msg_erro =	traduz("data.de.fechamento.nao.pode.ser.anterior.a.data.de.abertura",$con,$cook_idioma);
				}

				if (strlen($msg_erro)==0) {
					$novo_status_os	= 'CONSERTADO';
				}
			}
		}

	if (strlen ($msg_erro) == 0	and	$login_fabrica== 14	AND	$gravou_peca=="sim") {
		/* hd 144313 - Não fazer intervenção para o	posto interno intelbras	*/
		if ($os_com_intervencao=='t' and $login_posto!=7214){
			$sql_intervencao = "SELECT sua_os,to_char(data_digitacao,'DD/MM/YYYY') AS data_digitacao, nome
							FROM  tbl_os
							JOIN tbl_posto USING(posto)
							WHERE tbl_os.os=$os";
			$res_Y = @pg_query ($con,$sql_intervencao);
			$y_sua_os =	pg_fetch_result	($res_Y,0,sua_os);
			$y_data	= pg_fetch_result($res_Y,0,data_digitacao);
			$y_nome	= pg_fetch_result($res_Y,0,nome);

			$sql_intervencao = "SELECT *
							FROM  tbl_os_status
							WHERE os=$os
							ORDER BY data DESC LIMIT 1";

			$res_intervencao = pg_query($con, $sql_intervencao);


			$sql = "INSERT INTO	tbl_os_status (os,status_os,data,observacao) values	($os,62,current_timestamp,'Peça	da O.S.	com	intervenção	da fábrica.')";
			if (pg_num_rows	($res_intervencao) == 0){
				$res = @pg_query ($con,$sql);
			}else{
				$status_os = pg_fetch_result($res_intervencao,0,status_os);
				if ($status_os!=62){
					$res = @pg_query ($con,$sql);
				}
			}

			$sql_intervencao_automatica	=  "UPDATE tbl_os_retorno
											SET	nota_fiscal_envio			= '1000',
												data_nf_envio				= current_date,
												numero_rastreamento_envio	= '1000'
												envio_chegada				= ' current_date,
												admin_recebeu				= 1516
											WHERE os=$os";
			$res = @pg_query ($con,$sql_intervencao_automatica);
		}
	}

	if (strlen ($msg_erro) == 0) {
		if ($intervencao_tecnica=='t' or $os_com_intervencao ==	't') {

   			$sql_intervencao_automatica	=  "UPDATE tbl_os_retorno
											SET	nota_fiscal_envio =	'1000',
												data_nf_envio	  =	current_date,
												numero_rastreamento_envio =	'1000'
											WHERE os=$os";
			$res = @pg_query ($con,$sql_intervencao_automatica);
			$msg_erro .= pg_errormessage($con);

			echo "<script>alert('Sua Os	foi	gravada, mas esta em intervenção pela fabrica!!')</script>";
		}
		
		$sqlB = "UPDATE tbl_os SET status_checkpoint=fn_os_status_checkpoint_os(os)
				WHERE os = $os
				AND	status_checkpoint<>fn_os_status_checkpoint_os(os)";
				$res = pg_query($con,$sqlB);
				$msg_erro .= pg_errormessage($con);	
		

		if (strlen ($msg_erro) == 0) {
			$res = pg_query	($con,"COMMIT TRANSACTION");
		}else {
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
		}

	//Envia	e-mail para	o consumidor, avisando da abertura da OS
	//HD 150972
	//22/02/2010 MLG - Não enviar e-mail quando	há erro	de anexo de	NF...
		$redirect = true;
	}else{
		$res = pg_query	($con,"ROLLBACK	TRANSACTION");
	}

	if ($redirect) {
		if ((($login_fabrica ==	14)	|| ($login_fabrica == 43) || ($login_fabrica ==	66)) &&	$img_msg_erro=='') {
			if (strlen($novo_status_os)==0)	{
				$novo_status_os	= "ABERTA";
			}
			include('os_email_consumidor.php');
		}
		if ($img_msg_erro == '') {
			echo "<script>window.location =	'os_finalizada.php?os=$os' </script>";
			exit;
		}
	}
}
//  FIM Gravar

if(strlen($serie)>0	or strlen($hd_chamado) > 0){
	$sql = "SELECT	tbl_hd_chamado_extra.hd_chamado,
					tbl_hd_chamado_extra.nome,
					tbl_hd_chamado_extra.endereco ,
					tbl_hd_chamado_extra.numero	,
					tbl_hd_chamado_extra.complemento ,
					tbl_hd_chamado_extra.bairro	,
					tbl_hd_chamado_extra.cep ,
					tbl_hd_chamado_extra.fone ,
					tbl_hd_chamado_extra.fone2 ,
					tbl_hd_chamado_extra.email ,
					tbl_hd_chamado_extra.cpf ,
					tbl_hd_chamado_extra.rg	,
					tbl_cidade.nome									   AS cidade_nome,
					tbl_cidade.estado								   AS estado,
					tbl_produto.referencia							   AS produto_referencia,
					tbl_produto.descricao							   AS produto_nome,
					tbl_hd_chamado_extra.serie,
					tbl_defeito_reclamado.defeito_reclamado			   AS defeito_reclamado,
					tbl_defeito_reclamado.descricao					   AS defeito_reclamado_descricao,
					to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') AS data_nf,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY')		   AS data_abertura,
					tbl_hd_chamado_extra.nota_fiscal				   AS nota_fiscal,
					tbl_hd_chamado_extra.os							   AS os,
					tbl_os.sua_os									   AS sua_os,
					tbl_os.data_fechamento							   AS data_fechamento
			FROM tbl_hd_chamado
			JOIN tbl_hd_chamado_extra	ON tbl_hd_chamado.hd_chamado  =	tbl_hd_chamado_extra.hd_chamado
			LEFT JOIN tbl_produto		ON tbl_produto.produto		  =	tbl_hd_chamado_extra.produto
			LEFT JOIN tbl_cidade		ON tbl_cidade.cidade		  =	tbl_hd_chamado_extra.cidade
			LEFT JOIN tbl_posto_fabrica	ON tbl_hd_chamado_extra.posto =	tbl_posto_fabrica.posto
				AND	tbl_posto_fabrica.fabrica =	$login_fabrica
			LEFT JOIN tbl_defeito_reclamado	ON tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
			LEFT JOIN tbl_os			ON tbl_os.os = tbl_hd_chamado_extra.os
			WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
			AND	  tbl_hd_chamado_extra.posto		 = $login_posto
			AND	  (tbl_hd_chamado_extra.serie		  =	'$serie' or	tbl_hd_chamado_extra.hd_chamado	= $hd_chamado)
			ORDER BY tbl_hd_chamado.data DESC ";
	$res = @pg_query($con,$sql);


	if (pg_num_rows($res)>0){
		$hd_chamado				= trim(pg_fetch_result($res,0,hd_chamado));
		$consumidor_nome		= trim(pg_fetch_result($res,0,nome));
		$consumidor_endereco	= trim(pg_fetch_result($res,0,endereco));
		$consumidor_numero		= trim(pg_fetch_result($res,0,numero));
		$consumidor_complemento	= trim(pg_fetch_result($res,0,complemento));
		$consumidor_bairro		= trim(pg_fetch_result($res,0,bairro));
		$consumidor_cep			= trim(pg_fetch_result($res,0,cep));
		$consumidor_fone		= trim(pg_fetch_result($res,0,fone));
		$consumidor_celular		= trim(pg_fetch_result($res,0,fone2));
		$consumidor_email		= trim(pg_fetch_result($res,0,email));
		$consumidor_cpf			= trim(pg_fetch_result($res,0,cpf));
		$consumidor_cidade		= trim(pg_fetch_result($res,0,cidade_nome));
		$consumidor_estado		= trim(pg_fetch_result($res,0,estado));
		$produto_referencia		= trim(pg_fetch_result($res,0,produto_referencia));
		$produto_descricao		= trim(pg_fetch_result($res,0,produto_nome));
		$produto_serie			= trim(pg_fetch_result($res,0,serie));
		$defeito_reclamado		= trim(pg_fetch_result($res,0,defeito_reclamado));
		$defeito_reclamado_descricao	= trim(pg_fetch_result($res,0,defeito_reclamado_descricao));
		$data_abertura			= trim(pg_fetch_result($res,0,data_abertura));
		$data_nf				= trim(pg_fetch_result($res,0,data_nf));
		$nota_fiscal			= trim(pg_fetch_result($res,0,nota_fiscal));

	}
}





if (strlen ($os) > 0) {
	$sql = "SELECT	tbl_os.os											,
			tbl_os.tipo_atendimento										,
			tbl_os.posto												,
			tbl_posto.nome							   AS posto_nome	,
			tbl_os.sua_os												,
			to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura	,
			tbl_os.produto												,
			tbl_produto.referencia										,
			tbl_produto.descricao										,
			tbl_os.serie												,
			tbl_os.qtde_produtos										,
			tbl_os.cliente												,
			tbl_os.consumidor_nome										,
			tbl_os.consumidor_cpf										,
			tbl_os.consumidor_fone										,
			tbl_os.consumidor_cidade									,
			tbl_os.consumidor_estado									,
			tbl_os.consumidor_cep										,
			tbl_os.consumidor_endereco									,
			tbl_os.consumidor_numero									,
			tbl_os.consumidor_complemento								,
			tbl_os.consumidor_bairro									,
			tbl_os.consumidor_email 									,
			tbl_os.revenda												,
			tbl_os.revenda_cnpj											,
			tbl_os.revenda_nome											,
			tbl_os.nota_fiscal											,
			to_char(tbl_os.data_nf,'DD/MM/YYYY')	   AS data_nf		,
			tbl_os.aparencia_produto									,
			tbl_os_extra.orientacao_sac									,
			tbl_os_extra.admin_paga_mao_de_obra						   ,
			tbl_os.acessorios											,
			tbl_os.fabrica												,
			tbl_os.quem_abriu_chamado									,
			tbl_os.obs													,
			tbl_os.consumidor_revenda									,
			tbl_os_extra.extrato										,
			tbl_posto_fabrica.codigo_posto			   AS posto_codigo	,
			tbl_os.codigo_fabricacao									,
			tbl_os.satisfacao											,
			tbl_os.laudo_tecnico										,
			tbl_os.troca_faturada										,
			tbl_os.admin												,
			tbl_os.troca_garantia										,
			tbl_os.solucao_os
			FROM	tbl_os
			JOIN	tbl_produto			 ON	tbl_produto.produto		  =	tbl_os.produto
			JOIN	tbl_posto			 ON	tbl_posto.posto			  =	tbl_os.posto
			JOIN	tbl_fabrica			 ON	tbl_fabrica.fabrica		  =	tbl_os.fabrica
			JOIN	tbl_posto_fabrica	 ON	tbl_posto_fabrica.posto	  =	tbl_posto.posto
										AND	tbl_posto_fabrica.fabrica =	tbl_fabrica.fabrica
										AND	tbl_fabrica.fabrica		  =	$login_fabrica
			LEFT JOIN	tbl_os_extra	 ON	tbl_os.os				  =	tbl_os_extra.os
			WHERE	tbl_os.os	   = $os
			AND		tbl_os.fabrica = $login_fabrica";
	$res = pg_query	($con,$sql);

	if (pg_num_rows	($res) == 1) {
		$os						= pg_fetch_result($res,0,os);
		$tipo_atendimento		= pg_fetch_result($res,0,tipo_atendimento);
		$posto					= pg_fetch_result($res,0,posto);
		$posto_nome				= pg_fetch_result($res,0,posto_nome);
		$sua_os					= pg_fetch_result($res,0,sua_os);
		$data_abertura			= pg_fetch_result($res,0,data_abertura);
		$produto_referencia		= pg_fetch_result($res,0,referencia);
		$produto_descricao		= pg_fetch_result($res,0,descricao);
		$produto_serie			= pg_fetch_result($res,0,serie);
		$qtde_produtos			= pg_fetch_result($res,0,qtde_produtos);
		$cliente				= pg_fetch_result($res,0,cliente);
		$consumidor_nome		= pg_fetch_result($res,0,consumidor_nome);
		$consumidor_cpf			= pg_fetch_result($res,0,consumidor_cpf);
		$consumidor_fone		= pg_fetch_result($res,0,consumidor_fone);
		$consumidor_cep			= trim (pg_fetch_result	($res,0,consumidor_cep));
		$consumidor_endereco	= trim (pg_fetch_result	($res,0,consumidor_endereco));
		$consumidor_numero		= trim (pg_fetch_result	($res,0,consumidor_numero));
		$consumidor_complemento	= trim (pg_fetch_result	($res,0,consumidor_complemento));
		$consumidor_bairro		= trim (pg_fetch_result	($res,0,consumidor_bairro));
		$consumidor_cidade		= pg_fetch_result($res,0,consumidor_cidade);
		$consumidor_estado		= pg_fetch_result($res,0,consumidor_estado);
		$consumidor_email		= pg_fetch_result($res,0,consumidor_email);

		$revenda				= pg_fetch_result($res,0,revenda);
		$revenda_cnpj			= pg_fetch_result($res,0,revenda_cnpj);
		$revenda_nome			= pg_fetch_result($res,0,revenda_nome);
		$nota_fiscal			= pg_fetch_result($res,0,nota_fiscal);
		$data_nf				= pg_fetch_result($res,0,data_nf);
		$aparencia_produto		= pg_fetch_result($res,0,aparencia_produto);
		$acessorios				= pg_fetch_result($res,0,acessorios);
		$fabrica				= pg_fetch_result($res,0,fabrica);
		$posto_codigo			= pg_fetch_result($res,0,posto_codigo);
		$extrato				= pg_fetch_result($res,0,extrato);
		$quem_abriu_chamado		= pg_fetch_result($res,0,quem_abriu_chamado);
		$obs					= pg_fetch_result($res,0,obs);
		$consumidor_revenda		= pg_fetch_result($res,0,consumidor_revenda);
		$codigo_fabricacao		= pg_fetch_result($res,0,codigo_fabricacao);
		$satisfacao				= pg_fetch_result($res,0,satisfacao);
		$laudo_tecnico			= pg_fetch_result($res,0,laudo_tecnico);
		$troca_faturada			= pg_fetch_result($res,0,troca_faturada);
		$troca_garantia			= pg_fetch_result($res,0,troca_garantia);
		$admin_os				= trim(pg_fetch_result($res,0,admin));
		$solucao_os				= trim(pg_fetch_result($res,0,solucao_os));

		$orientacao_sac	= pg_fetch_result($res,0,orientacao_sac);
		$orientacao_sac	= html_entity_decode ($orientacao_sac,ENT_QUOTES);
		$orientacao_sac	= str_replace ("<br	/>","",$orientacao_sac);

		if (temNF($os, 'bool'))
			$imagem_nf	= current(temNF($os, 'url'));

		$admin_paga_mao_de_obra	= pg_fetch_result($res,0,admin_paga_mao_de_obra);

		$sql =	"SELECT	tbl_os_produto.produto ,
						tbl_os_item.pedido
				FROM	tbl_os
				JOIN	tbl_produto	using (produto)
				JOIN	tbl_posto using	(posto)
				JOIN	tbl_fabrica	using (fabrica)
				JOIN	tbl_posto_fabrica ON  tbl_posto_fabrica.posto	= tbl_posto.posto
										  AND tbl_posto_fabrica.fabrica	= tbl_fabrica.fabrica
				JOIN	tbl_os_produto USING (os)
				JOIN	tbl_os_item
				ON		tbl_os_item.os_produto = tbl_os_produto.os_produto
				WHERE	tbl_os.os =	$os
				AND		tbl_os.fabrica = $login_fabrica";
		$res = pg_query	($con,$sql);

		if(pg_num_rows($res) > 0){
			$produto = pg_fetch_result($res,0,produto);
			$pedido	 = pg_fetch_result($res,0,pedido);
		}

		$sql = "SELECT * FROM tbl_os_extra WHERE os	= $os";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) == 1)	{
			$taxa_visita			  =	pg_fetch_result	($res,0,taxa_visita);
			$visita_por_km			  =	pg_fetch_result	($res,0,visita_por_km);
			$hora_tecnica			  =	pg_fetch_result	($res,0,hora_tecnica);
			$regulagem_peso_padrao	  =	pg_fetch_result	($res,0,regulagem_peso_padrao);
			$certificado_conformidade =	pg_fetch_result	($res,0,certificado_conformidade);
			$valor_diaria			  =	pg_fetch_result	($res,0,valor_diaria);
		}

		//SELECIONA	OS DADOS DO	CLIENTE	PRA	JOGAR NA OS
		if (strlen($consumidor_cidade)==0){
			if (strlen($cpf) > 0 OR	strlen($cliente) > 0 ) {
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
						tbl_cidade.nome	AS cidade,
						tbl_cidade.estado
						FROM tbl_cliente
						LEFT JOIN tbl_cidade USING (cidade)
						WHERE 1	= 1";
				if (strlen($cpf) > 0) $sql .= "	AND	tbl_cliente.cpf	= '$cpf'";
				if (strlen($cliente) > 0) $sql .= "	AND	tbl_cliente.cliente	= '$cliente'";

				$res = pg_query	($con,$sql);
				if (pg_num_rows	($res) == 1) {
					$consumidor_cliente		= trim (pg_fetch_result	($res,0,cliente));
					$consumidor_fone		= trim (pg_fetch_result	($res,0,fone));
					$consumidor_nome		= trim (pg_fetch_result	($res,0,nome));
					$consumidor_endereco	= trim (pg_fetch_result	($res,0,endereco));
					$consumidor_numero		= trim (pg_fetch_result	($res,0,numero));
					$consumidor_complemento	= trim (pg_fetch_result	($res,0,complemento));
					$consumidor_bairro		= trim (pg_fetch_result	($res,0,bairro));
					$consumidor_cep			= trim (pg_fetch_result	($res,0,cep));
					$consumidor_rg			= trim (pg_fetch_result	($res,0,rg));
					$consumidor_cidade		= trim (pg_fetch_result	($res,0,cidade));
					$consumidor_estado		= trim (pg_fetch_result	($res,0,estado));
					$consumidor_contrato	= trim (pg_fetch_result	($res,0,contrato));
				}
			}
		}
	}
	if (strlen($revenda)>0){
		$xsql  = "SELECT tbl_revenda.revenda,
						tbl_revenda.nome,
						tbl_revenda.cnpj,
						tbl_revenda.fone,
						tbl_revenda.endereco,
						tbl_revenda.numero,
						tbl_revenda.complemento,
						tbl_revenda.bairro,
						tbl_revenda.cep,
						tbl_cidade.nome	AS cidade,
						tbl_cidade.estado
						FROM tbl_revenda
						LEFT JOIN tbl_cidade USING (cidade)
						WHERE tbl_revenda.revenda =	$revenda";
		$res1 =	pg_query ($con,$xsql);

		if (pg_num_rows($res1) > 0)	{
			$revenda_nome =	pg_fetch_result	($res1,0,nome);
			$revenda_cnpj =	pg_fetch_result	($res1,0,cnpj);
			$revenda_fone =	pg_fetch_result	($res1,0,fone);
			$revenda_endereco =	pg_fetch_result	($res1,0,endereco);
			$revenda_numero	= pg_fetch_result($res1,0,numero);
			$revenda_complemento = pg_fetch_result($res1,0,complemento);
			$revenda_bairro	= pg_fetch_result($res1,0,bairro);
			$revenda_cep = pg_fetch_result($res1,0,cep);
			$revenda_cidade	= pg_fetch_result($res1,0,cidade);
			$revenda_estado	= pg_fetch_result($res1,0,estado);
		}
	}
}

/*=============	RECARREGA FORM EM CASO DE ERRO ==================*/

if (strlen($msg_erro) >	0 )	{
	$os					= $_POST['os'];
	$tipo_atendimento	= $_POST['tipo_atendimento'];
	$sua_os				= $_POST['sua_os'];
	$data_abertura		= $_POST['data_abertura'];
	$cliente			= $_POST['cliente'];
	$consumidor_nome	= $_POST['consumidor_nome'];
	$consumidor_cpf		= $_POST['consumidor_cpf'];
	$consumidor_fone	= $_POST['consumidor_fone'];
	$revenda			= $_POST['revenda'];
	$revenda_cnpj		= $_POST['revenda_cnpj'];
	$revenda_nome		= $_POST['revenda_nome'];
	$nota_fiscal		= $_POST['nota_fiscal'];
	$data_nf			= $_POST['data_nf'];
	$produto_referencia	= $_POST['produto_referencia'];
	$cor				= $_POST['cor'];
	$acessorios			= $_POST['acessorios'];
	$aparencia_produto	= $_POST['aparencia_produto'];
	$obs				= $_POST['obs'];
	$orientacao_sac		= $_POST['orientacao_sac'];
	$consumidor_revenda	= $_POST['consumidor_revenda'];
	$qtde_produtos		= $_POST['qtde_produtos'];
	$produto_serie		= $_POST['produto_serie'];

	$codigo_fabricacao	= $_POST['codigo_fabricacao'];
	$satisfacao			= $_POST['satisfacao'];
	$laudo_tecnico		= $_POST['laudo_tecnico'];
	$troca_faturada		= $_POST['troca_faturada'];

	$quem_abriu_chamado		  =	$_POST['quem_abriu_chamado'];
	$taxa_visita			  =	$_POST['taxa_visita'];
	$visita_por_km			  =	$_POST['visita_por_km'];
	$hora_tecnica			  =	$_POST['hora_tecnica'];
	$regulagem_peso_padrao	  =	$_POST['regulagem_peso_padrao'];
	$certificado_conformidade =	$_POST['certificado_conformidade'];
	$valor_diaria			  =	$_POST['valor_diaria'];
	$solucao_os2			  =	$_POST['solucao_os2'];

	$sql =	"SELECT	descricao
			FROM	tbl_produto
			JOIN	tbl_linha USING	(linha)
			WHERE	tbl_produto.referencia = UPPER ('$produto_referencia')
			AND		tbl_linha.fabrica	   = $login_fabrica
			AND		tbl_produto.ativo IS TRUE";
	$res = pg_query	($con,$sql);
	$produto_descricao = @pg_fetch_result($res,0,0);
}

$title = traduz	("cadastro.ordem.de.servico",$con,$cook_idioma);

include	"cabecalho.php";

//HD 214236: Se a OS estiver em auditoria prévia, travar a tela
if (strlen($os) && ($login_fabrica == 14 || $login_fabrica == 43)) {
	$sql = "
	SELECT
	liberado,
	cancelada

	FROM
	tbl_os_auditar

	WHERE
	os_auditar IN (
		SELECT
		MAX(os_auditar)

		FROM
		tbl_os_auditar

		WHERE
		os=$os
	)
	";
	$res_auditoria = pg_query($con, $sql);
	$auditoria_travar_opcoes = false;

	if (strlen(pg_errormessage($con)) == 0 && pg_num_rows($res_auditoria)) {
		$liberado = pg_result($res_auditoria, 0, liberado);
		$cancelada = pg_result($res_auditoria, 0, cancelada);

		if ($liberado == 'f') {
			if ($cancelada == 'f') {
				$legenda_status = "em análise";
				$cor_status = "#FFFF44";
				$auditoria_travar_opcoes = true;
			}
			elseif ($cancelada == 't') {
				$legenda_status = "reprovada";
				$cor_status = "#FF7744";
			}
			else {
				$legenda_status = "";
				$cor_status = "";
			}
		}
		elseif ($liberado == 't') {
			$legenda_status = "aprovada";
			$cor_status = "#44FF44";
		}
		else {
			$legenda_status = "";
			$cor_status = "";
		}
	}
	else {
		$legenda_status = "";
		$cor_status = "";
	}

	if ($auditoria_travar_opcoes) {
		$sql = "SELECT sua_os FROM tbl_os WHERE os=$os";
		$res = pg_query($con, $sql);
		$sua_os = pg_result($res, 0, sua_os);

		echo "
		<table width='730' cellpadding='10' style='background:$cor_status' >
			<tr>
				<td align='center'>A OS $sua_os está em auditoria pela fábrica,<br />e o seu status ($legenda_status) não permite que a mesma seja alterada.<br />Aguarde a liberação pela fábrica.</td>
			</tr>
		</table>";
		die;
	}
}

?>

<!--===============	<FUNÇÕES> ================================!-->
<? include "javascript_pesquisas_novo.php" ?>

<script	language='javascript' src='ajax.js'></script>
<script	language='javascript' src='ajax_cep.js'></script>
<script	language='javascript' src='ajax_os_cadastro.js'></script>
<script	type="text/javascript" src="js/jquery-1.3.2.js"></script>
<script	type="text/javascript" src="js/jquery.corner.js"></script>
<script	language='javascript' src='js/jquery.modal.js'></script>
<script	type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script	type="text/javascript" src="admin/js/jquery.blockUI.js"></script>

<!-- Funcoes Javascript	para AJAX da Pre-Validacao do Numero de	Serie ---- -->
<script	type="text/javascript" src="js/bibliotecaAJAX.js"></script>
<script src="plugins/shadowbox/shadowbox.js"	type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">

<script type="text/javascript">

	$(document).ready(function(){
		Shadowbox.init();
	})

function fnc_prevalida_serie (serie) {
	if (serie.length>0)	{
		url	= 'intelbras_prevalida_serie.php?serie=' + serie + '&fabrica=<?=$login_fabrica?>' +	'&cache_bypass=<?= $cache_bypass ?>';
		requisicaoHTTP ('GET', url , true ,	'fnc_resultado_prevalida_serie');
	}
}

function fnc_resultado_prevalida_serie (campos)	{
	var	erro   = campos.indexOf	('<erro>');
	if (erro > 0){
		alert ('Número de Série	não	existe na base de dados');
		$('#pre-serie').val('');
		$('#pre-serie').focus();
	}
	else {
		campos = campos.substring (campos.indexOf('<ok>')+4,campos.length);
		campos = campos.substring (0,campos.indexOf('</ok>'));
		campos_array = campos.split("|");

		produto			   = campos_array[0] ;
		produto_referencia = campos_array[1] ;
		data_fabricacao	   = campos_array[2] ;
		produto_descricao  = campos_array[3] ;
		keycode_base	   = campos_array[4] ;
		produto_serie	   = campos_array[5] ;

		window.document.forms["frm_os"].elements["produto_referencia"].value = produto_referencia;
		$('#produto_referencia').attr('readonly',true);
		window.document.forms["frm_os"].elements["produto"].value =	produto;
		window.document.forms["frm_os"].elements["produto_descricao"].value	= produto_descricao;
		$('#produto_descricao').attr('readonly',true);
		window.document.forms["frm_os"].elements["produto_serie"].value	= produto_serie;
		$('#produto_serie').attr('readonly',true);
		window.document.forms["frm_os"].elements["keycode_base"].value = keycode_base;
	}
}

function fnc_prevalida_serie_keycode (keycode) {
	if (keycode.length>0){
		keycode_base = window.document.forms["frm_os"].elements["keycode_base"].value ;
		if (keycode_base.length	> 0){
			if (keycode_base !=	keycode){
				alert ("Key-Code não confere");
				document.getElementById('keycode').value='';
				t =	window.setTimeout(function() {
					document.getElementById('keycode').focus();
					clearTimeout(t);
				},100);
			}
		}
	}
}

</script>

<script	language="JavaScript">

$(document).ready(function(){
	displayText('&nbsp;');
	$("input[rel='data_fechamento']").maskedinput("99/99/9999");
	$("input[rel='data_conserto']").maskedinput("99/99/9999");
	$(".content").corner("dog 10px");
	verificaLinha();

	$('#excluir_os_item').click(function () {
		var os = $(this).prev().val();
		$.get(location.pathname,'ajax=sim&excluir=item&os_produto='+os, function(data){
			if(data=='ok'){
				$('#excluir_os_item').parent().parent().remove();
			}
		});
	});

/*	Exclui a imagem	da NF se já	existir, e devolve o INPUT file	para poder anexar outra	*/
	$('#excluir_nota').click(function () {
		var	nf = $(this).attr('file').replace(/^http:\/\/\D*/, '/');
		var img_nf = $('#nf_table_item').html();
		var	excluir_str	= '<?=fecho('confimar.excluir.imagem.nf',$con,$cook_idioma)?>';
		if (confirm(excluir_str) ==	false) return false;
		$('#nf_table_item').html('<p>Excluindo...</p>');
		$.post('<?=$PHP_SELF?>',
			   'excluir_nf='+nf+'&ajax=excluir_nf',
			   function(data) {
				if (data ==	'ok') {
					var	input_html = '<input type="file" accept="image/jpg"	';
						input_html+= 'name="foto" class="frm" ';
						input_html+= 'title="Selecione a imagem	em formato JPG da Nota Fiscal para anexar à	OS">';
					$('#str_nf').css('background-color','#FFFFC0').
								 css('font-weight','bold').
								 text('Anexar Nota Fiscal:');
					$('#nf_table_item').css('background-color','#FFFFC0').
										html(input_html);
				} else {
					alert('Não foi possível	excluir a imagem da NF. Contate com a Telecontrol.');
					$('#nf_table_item').html(img_nf);
				}
		});
	});
	$('#nf_table_item img').css('cursor','pointer');
});

/* ============= Função	FORMATA	CNPJ =============================
Nome da	Função : formata_cnpj (cnpj, form)
		Formata	o Campo	de CNPJ	a medida que ocorre	a digitação
		Parâm.:	cnpj (numero), form	(nome do form)
=================================================================*/
function formata_cnpj(cnpj,	form){
	var	mycnpj = '';
	mycnpj = mycnpj	+ cnpj;
	myrecord = "revenda_cnpj";
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj	+ '.';
		window.document.forms["" + myform +	""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 6){
		mycnpj = mycnpj	+ '.';
		window.document.forms["" + myform +	""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 10){
		mycnpj = mycnpj	+ '/';
		window.document.forms["" + myform +	""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 15){
		mycnpj = mycnpj	+ '-';
		window.document.forms["" + myform +	""].elements[myrecord].value = mycnpj;
	}
}

function formata_data(campo_data, form,	campo){
	var	mycnpj = '';
	mycnpj = mycnpj	+ campo_data;
	myrecord = campo;
	myform = form;

	if (mycnpj.length == 2){
		mycnpj = mycnpj	+ '/';
		window.document.forms["" + myform +	""].elements[myrecord].value = mycnpj;
	}
	if (mycnpj.length == 5){
		mycnpj = mycnpj	+ '/';
		window.document.forms["" + myform +	""].elements[myrecord].value = mycnpj;
	}
}

function char(cnpj){
	try{var	element	= cnpj.which	}catch(er){};
	try{var	element	= event.keyCode	}catch(er){};
	if (String.fromCharCode(element).search(/[0-9]/gi) == -1)
	return false
}
window.onload =	function(){
	document.getElementById('revenda_cnpj').onkeypress = char;
}

function listaConstatado(referencia) {
//verifica se o	browser	tem	suporte	a ajax
	try	{ajax =	new	ActiveXObject("Microsoft.XMLHTTP");}
	catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
		catch(ex) {	try	{ajax =	new	XMLHttpRequest();}
				catch(exc) {alert("Esse	browser	não	tem	recursos para uso do Ajax"); ajax =	null;}
		}
	}
//se tiver suporte ajax
	if(ajax) {
	//deixa	apenas o elemento 1	no option, os outros são excluídos
	document.forms[0].defeito_constatado.options.length	= 1;
	//opcoes é o nome do campo combo
	idOpcao2  =	document.getElementById("opcoes2");
	//	 ajax.open("POST", "ajax_produto.php", true);
	ajax.open("GET", "ajax_os_cadastro_intelbras.php?produto_referencia="+referencia, true);
	ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

	ajax.onreadystatechange	= function() {
		if(ajax.readyState == 1) {idOpcao2.innerHTML = "Carregando...!";}//enquanto	estiver	processando...emite	a msg
		if(ajax.readyState == 4	) {
			if(ajax.responseXML) {
				montaCombo2(ajax.responseXML);//após ser processado-chama fun
			} else {
				idOpcao2.innerHTML = "Selecione	o produto";//caso não seja um arquivo XML emite	a mensagem abaixo
			}
		}
	}
	//passa	o código do	produto	escolhido
	var	params = "produto_referencia="+referencia;
	ajax.send(null);
	}
}

function montaCombo2(obj){
	var	dataArray2	 = obj.getElementsByTagName("produto2");//pega a tag produto
	if(dataArray2.length > 0) {//total de elementos	contidos na	tag	cidade
		for(var	i =	0 ;	i <	dataArray2.length ;	i++) { //percorre o	arquivo	XML	paara extrair os dados
			 var item =	dataArray2[i];
			//contéudo dos campos no arquivo XML
			var	codigo2	   =  item.getElementsByTagName("codigo2")[0].firstChild.nodeValue;
			var	nome2 =	 item.getElementsByTagName("nome2")[0].firstChild.nodeValue;
			idOpcao2.innerHTML = "Selecione	o defeito";
			//cria um novo option dinamicamente
			var	novo2 =	document.createElement("option");
			novo2.setAttribute("id", "opcoes2");//atribui um ID	a esse elemento
			novo2.value	= codigo2;		//atribui um valor
			novo2.text	= nome2;//atribui um texto
			document.forms[0].defeito_constatado.options.add(novo2);//adiciona o novo elemento
		}
	} else {
		idOpcao2.innerHTML = "Selecione	o defeito";//caso o	XML	volte vazio, printa	a mensagem abaixo
	}
}

function listaSubconjunto(campo,produto) {
//verifica se o	browser	tem	suporte	a ajax
	try	{ajax =	new	ActiveXObject("Microsoft.XMLHTTP");}
	catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
		catch(ex) {	try	{ajax =	new	XMLHttpRequest();}
				catch(exc) {alert("Esse	browser	não	tem	recursos para uso do Ajax"); ajax =	null;}
		}
	}
//se tiver suporte ajax
	if(ajax) {
	//deixa	apenas o elemento 1	no option, os outros são excluídos
	campo.options.length = 1;
	//	 ajax.open("POST", "ajax_produto.php", true);
	ajax.open("GET", "ajax_defeito_constatado_intelbras.php?subconjunto="+produto, true);
	ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

	ajax.onreadystatechange	= function() {
		if(ajax.readyState == 1) {
			campo.innerHTML	= "Carregando...!";
		}
		if(ajax.readyState == 4	) {
			if(ajax.responseXML) {
				montaCombo3(campo,ajax.responseXML);
			} else {
				campo.innerHTML	= "Selecione o produto";
			}
		}
	}
	//passa	o código do	produto	escolhido
	var	params = "subconjunto="+produto;
	ajax.send(null);
	}
}

function montaCombo3(campo,result) {

	var	dataArray3	= result.getElementsByTagName("produto3");//pega a	tag	produto

	campo.innerHTML = '<option id="opcoes3"></option>';//HD 382584

	if (dataArray3.length > 0) {
	
		for (var i = 0; i < dataArray3.length; i++) {	   //percorre o	arquivo	XML	paara extrair os dados

			var item3 = dataArray3[i];
			//contéudo dos campos no arquivo XML
			var	codigo3	   =  item3.getElementsByTagName("codigo3")[0].firstChild.nodeValue;
			var	nome3 =	 item3.getElementsByTagName("nome3")[0].firstChild.nodeValue;
			//campo.innerHTML =	"Selecione o subconjunto";
			//cria um novo option dinamicamente
			var	novo3 =	document.createElement("option");
			novo3.setAttribute("id", "opcoes3");//atribui um ID	a esse elemento
			novo3.value	= codigo3;		//atribui um valor
			novo3.text	= nome3;//atribui um texto
			campo.options.add(novo3);//adiciona	o novo elemento

		}

	} else {
		campo.innerHTML = "Selecione o	subconjunto";//caso	o XML volte	vazio, printa a	mensagem abaixo
	}

}

function getInformacoesProduto(formulatio){
	$.ajax({
		type: "POST",
		url: "<? echo $PHP_SELF	?>",
		data: "getProduto=sim&produto_referencia="+$('#produto_referencia').val(),
		success: function(msg){
			document.getElementById('dados').innerHTML=msg;
		}
	});
}

function verificaLinha(formulatio){
	$.ajax({
		type: "POST",
		url: "os_cadastro_intelbras_ajax.php",
		data: "getLinha=sim&produto_referencia="+$('#produto_referencia').val(),
		success: function(msg){
			if(msg == 549){
				$('#label_produto_serie').html('IMEI');
				$("input[rel='data_fechamento']").css({'visibility':'hidden','disable':'true'});
				$("#aparencia").css({'font-size':'8px','visibility':'visible','display':'block'});
				$('#solucao_os').hide().attr('disabled', true);
				$('#solucao_os2').attr('readonly', true);
				$('#defeito_constatado').attr('disabled', true);
				$('#linha').val(msg);
			}else{
				$('#label_produto_serie').html('N. Série.');
				$("input[rel='data_fechamento']").css({'visibility':'visible','disable':'false'});
				$("#aparencia").css({'visibility':'hidden','display':'none'});
				$('#solucao_os').show();
				$('#defeito_constatado').removeAttr('disabled',	true);
				$('#solucao_os2').hide().attr('disabled', true);
			}
		}
	});
}

function verificaPedido	(servico,linha) {
	var	servico	= servico;
	if (servico	!= 82 && servico !=	83)	{
		$("input[name=nao_pedido_"+linha+"]").attr("checked","checked");
	}
	else {
		$("input[name=nao_pedido_"+linha+"]").removeAttr("checked");
	}
}

function verificaPedido2 (check,linha) {
	if (check.checked){
		$("select[name=servico_"+linha+"]").val("76");
	}
}

function abreComunicado(){
	
	var ref  = document.frm_os.produto_referencia.value;
	var desc = document.frm_os.produto_descricao.value;
	
	if (document.frm_os.link_comunicado.value!="") {
		url = "pesquisa_comunicado.php?produto=" + ref +"&descricao="+desc;
		window.open(url,"comm","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=650,height=400,top=18,left=0");
	}

}

function trim(str) {
	
	while (str.charAt(0) == (" ")) {
		str = str.substring(1);
	}

	while (str.charAt(str.length-1) == " ") {
		str = str.substring(0,str.length-1);
	}

	return str;

}

function createRequestObject() {
	
	var request_;
	var browser = navigator.appName;
	
	if (browser == "Microsoft Internet Explorer") {
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	} else {
		 request_ = new XMLHttpRequest();
	}
	
	return request_;

}

var http_forn = new Array();
var http7     = new Array();

function checarComunicado(fabrica){
	
	var imagem = document.getElementById('img_comunicado');
	var ref    = document.frm_os.produto_referencia.value;

	//imagem.style.visibility = "hidden";
	document.frm_os.link_comunicado.value = "";
	imagem.title = "NÃO HÁ COMUNICADO PARA ESTE PRODUTO";
	ref = trim(ref);

	if (ref.length > 0) {
		var curDateTime = new Date();
		http7[curDateTime] = createRequestObject();
		url = "ajax_os_cadastro_comunicado.php?fabrica="+fabrica+"&produto="+escape(ref);
		http7[curDateTime].open('get',url);
		http7[curDateTime].onreadystatechange = function(){
			if (http7[curDateTime].readyState == 4) {
				if (http7[curDateTime].status == 200 || http7[curDateTime].status == 304) {	
					var response = http7[curDateTime].responseText;
					if (response=="ok") {
						document.frm_os.link_comunicado.value = "HÁ COMUNICADO PARA ESTE PRODUTO. CLIQUE AQUI PARA LER";
						imagem.title = "HÁ COMUNICADO PARA ESTE PRODUTO. CLIQUE AQUI PARA LER";
					} else {
						document.frm_os.link_comunicado.value = "";
						imagem.title = "NÃO HÁ COMUNICADO PARA ESTE PRODUTO";
					}
				}
			}
		}

		http7[curDateTime].send(null);

	}

}

function retorna_dados_produto(produto,linha,descricao,nome_comercial,voltagem,referencia,referencia_fabrica,garantia,mobra,ativo,off_line,capacidade,valor_troca,troca_garantia,troca_faturada,referencia_antiga,troca_obrigatoria,posicao){
		gravaDados("produto_referencia",referencia);
		gravaDados("produto_descricao",descricao);
		gravaDados("produto_voltagem",voltagem);
		$('.classeItens').val('');//HD 382584
		$('.classeItens').removeAttr('checked');//HD 382584
}

function retorna_peca(nome,cnpj,nome_cidade,fone,endereco,numero,complemento,bairro,cep,estado,email)
{
	gravaDados("revenda_nome",nome);
	gravaDados("revenda_cnpj",cnpj);
	gravaDados("revenda_fone",fone);
	gravaDados("revenda_cep",cep);
	gravaDados("revenda_endereco",endereco);
	gravaDados("revenda_numero",numero);
	gravaDados("revenda_complemento",complemento);
	gravaDados("revenda_bairro",bairro);
	gravaDados("revenda_cidade",nome_cidade);
	gravaDados("revenda_estado",estado);
	gravaDados("revenda_email",email);
}

function retorna_lista_subconjunto(peca_referencia,peca_descricao,posicao,input_posicao)
{
	gravaDados("posicao_" + input_posicao,posicao);
	gravaDados("peca_" + input_posicao,peca_referencia);
	gravaDados("descricao_" + input_posicao,peca_descricao);
}
function retorna_lista_peca(OCreferencia_antiga,OCposicao,OCcodigo_linha,OCpeca_referencia,OCpeca_descricao,OCpreco,OCpeca,OCtype,input_posicao)
{
	gravaDados("posicao_" + input_posicao,OCposicao);
	gravaDados("peca_" + input_posicao,OCpeca_referencia);
	gravaDados("descricao_" + input_posicao,OCpeca_descricao);
}

</script>

<?
if ($login_fabrica == 14) {
?>
<script	language='javascript'>
	$().ready(function() {
		$('#pre-serie').focus();
		
		// HD 359682 - INICIO - Restrição feita a pedido da Ramona neste chamado
		var valorEscolhidoSolucao;
		var produtoReferencia;
		var valorEscolhidoServico;
			$("#solucao_os").change(
				function(){
					// obtendo o valor selecionado
					valorEscolhidoSolucao = $("#solucao_os option:selected").val();
					produtoReferencia = $("#produto_referencia").val();
					// alert(produtoReferencia);	
					
					if (produtoReferencia != "4007500" && produtoReferencia != "4005036" && produtoReferencia != "4005037" && produtoReferencia != "4005038"){
						if ( valorEscolhidoSolucao == '10373' ){
							document.getElementById('solucao_os').selectedIndex = 0;
							alert("Opção não disponível para este produto");
						}
					}
				}
			);
			
			$("select[id^=servico_]").change(
				function(){
					// obtendo o valor selecionado
					valorEscolhidoServico = $(this).val();
					produtoReferencia = $("#produto_referencia").val();
					// alert("Produto: "+produtoReferencia+" Valor Selecionado: "+valorEscolhidoServico);	
					
					if (produtoReferencia != "4007500" && produtoReferencia != "4005036" && produtoReferencia != "4005037" && produtoReferencia != "4005038"){
						if ( valorEscolhidoServico == '10373' ){
							$(this).attr("selectedIndex",0);
							alert("Opção não disponível para este produto");
						}
					}
				}
			);
		   
		// HD 359682 - FIM -

	})
</script>
<?
}
?>
<style>
a.lnk:link{
	font-size: 10px;
	font-weight: bold;
	text-decoration: underline;
	color:#FFFF33;
}
a.lnk:visited{
	font-size: 10px;
	font-weight: bold;
	text-decoration: underline;
	color:#FFFF33;
}
.Label{
	font-family: Verdana;
	font-size: 10px;
}
.Titulo{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
}
.Erro{
	font-family: Verdana;
	font-size: 11px!important;
	color:#FFF;
	border:#485989 1px solid;
	background-color: #990000;
}
.msg .x-box-mc {
	font-size:14px;
}
#msg-div {
	position:absolute;
	left:70%;
	top:10px;
	width:250px;
	z-index:20000;
}
.aparencia_produto{
	font-size: 10px;
	visibility:	hidden;
	display: none;
}

</style>
<?
if(strlen($os) == 0){
	$sql = "SELECT TO_CHAR (current_timestamp ,	'DD/MM/YYYY' )";
	$res = @pg_query ($con,$sql);
	$hoje =	@pg_fetch_result($res,0,0);
	$data_abertura = $hoje;
}

if (strlen ($msg_erro) > 0 or ($img_msg_erro!='')) {
	if (strpos ($msg_erro,"tbl_os_sua_os") > 0){
		$msg_erro =	traduz("esta.ordem.de.servico.ja.foi.cadastrada",$con,$cook_idioma);
	}
?>

<!-- ============= <HTML> COMEÇA FORMATAÇÃO	===================== -->

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td	valign="middle"	align="center" class='error'>
<?
	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ")	!= false) {
		$erro =	traduz("foi.detectado.o.seguinte.erro",$con,$cook_idioma).":<br />";
		$msg_erro =	substr($msg_erro, 6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro =	$x[0];
	}
	echo "<!-- ERRO	INICIO -->";
	echo $erro . $msg_erro . $img_msg_erro;
	echo "<!-- ERRO	FINAL -->";
?>
	</td>
</tr>
</table>

<? } ?>
<form style="margin: 0px; word-spacing:	0px" name="frm_os" action="<? echo $PHP_SELF ?>"
	 method="post" enctype='multipart/form-data'>
<input type="hidden" name="os"		  value="<?echo	$os?>">
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td>
		<img height="2"	width="16" src="imagens/spacer.gif">
	</td>
</tr>
<?
//HD 214236: Auditoria Prévia de OS, mostrando status
if (strlen($os) && ($login_fabrica == 14 || $login_fabrica == 43)) {
	$bloqueia_edicao = true;

	$sql = "
	SELECT
	tbl_os_auditar.os_auditar,
	tbl_os_auditar.cancelada,
	tbl_os_auditar.liberado,
	TO_CHAR(tbl_os_auditar.data, 'DD/MM/YYYY HH24:MI') AS data ,
	TO_CHAR(CASE
		WHEN tbl_os_auditar.liberado_data IS NOT NULL THEN tbl_os_auditar.liberado_data
		WHEN tbl_os_auditar.cancelada_data IS NOT NULL THEN tbl_os_auditar.cancelada_data
		ELSE null
	END, 'DD/MM/YYYY HH24:MI') AS data_saida,
	tbl_os_auditar.justificativa

	FROM
	tbl_os_auditar

	WHERE
	tbl_os_auditar.os=$os
	";
	$res = pg_query($con, $sql);
	$n = pg_numrows($res);

	if ($n > 0) {
		echo "
<tr>
	<td	align='center' valign='middle' class='Label'>
		<TABLE style='border:#d3be96	1px	solid; background-color: #fcf0d8;text-align:center;width:750px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Label'>
			<TR>
				<TD class='inicio' style='text-align:center; background-color:#b39e76;' colspan='4' width='750'>
				AUDITORIA PRÉVIA
				</TD>
			</TR>
			<TR align='center'>
				<TD style='text-align:center; background-color:#d3be96;' align='center' width='70'>Status</TD>
				<TD style='text-align:center; background-color:#d3be96;' align='center' width='70'>Data Entrada</TD>
				<TD style='text-align:center; background-color:#d3be96;' align='center' width='70'>Data Saída</TD>
				<TD style='text-align:center; background-color:#d3be96;' align='center' width='540'>Justificativa</TD>
			</TR>";
		
		for ($i = 0; $i < $n; $i++) {
			//Recupera os valores do resultado da consulta
			$valores_linha = pg_fetch_array($res, $i);

			//Transforma os resultados recuperados de array para variáveis
			extract($valores_linha);

			if ($liberado == 'f') {
				if ($cancelada == 'f') {
					$legenda_status = "em análise";
					$cor_status = "#FFFF44";
				}
				elseif ($cancelada == 't') {
					$legenda_status = "reprovada";
					$cor_status = "#FF7744";
					$bloqueia_edicao = false;
				}
				else {
					$legenda_status = "";
					$cor_status = "";
				}
			}
			elseif ($liberado == 't') {
				$legenda_status = "aprovada";
				$cor_status = "#44FF44";
			}
			else {
				$legenda_status = "";
				$cor_status = "";
			}

			echo "
			<TR align='center' style='background-color: $cor_status;'>
				<TD class='conteudo' style='background-color: $cor_status; text-align:center;'>$legenda_status</TD>
				<TD class='conteudo' style='background-color: $cor_status; text-align:center;'>$data</TD>
				<TD class='conteudo' style='background-color: $cor_status; text-align:center;'>$data_saida</TD>
				<TD class='conteudo' style='background-color: $cor_status; text-align:center;'>$justificativa</TD>
			</TR>";
		}
		
		echo "
		</TABLE>
	</td>
</tr>
<tr>
	<td>
		<img height='2'	width='16' src='imagens/spacer.gif'>
	</td>
</tr>
";
	}
}
else {
	$bloqueia_edicao = false;
}
?>
<? if ($login_posto	== 6359) { ?>
<tr>
	<td	align='center' valign='middle'>
	<div style=' border:#d3be96	1px	solid; background-color: #fcf0d8;text-align:center;width:750px'>
		<br />
		<label class="label">Pré-validação do Número de	Série</label>
		<input type='text' name='pre-serie'	id='pre-serie' class='frm' onblur='javascript: fnc_prevalida_serie(this.value)'>

		<input type='hidden' name='keycode_base'>
		<label class="label">&nbsp;&nbsp;Key-Code</label>
		<input type='text' name='keycode' id='keycode' class='frm' onblur='javascript: fnc_prevalida_serie_keycode(this.value)'	size='5'>
		<br />&nbsp;
	</td>
	</div>
</tr>
<tr>
	<td>
		<img height="2"	width="16" src="imagens/spacer.gif">
	</td>
</tr>
<? } ?>
<tr>
	<td	valign="top" align="left">
		<!-- Informações da	OS	-->
		<table style='border:#485989 1px solid;	background-color: #e6eef7;width:750px' align='center' border='0'>
		<tr>
			<td colspan="4" align="center"><b class="Label" style="color:#FF0000">ATENÇÃO: Após selecionar um produto verifique se ele não possuí nenhum comunicado, passando o mouse sobre a LUPA</b></td>
		</tr>
		<tr>
			<td	nowrap class='Label'><?	fecho("referencia.do.produto",$con,$cook_idioma) ?></td>
			<td>
				<input class="frm" type="text" name="produto_referencia" id="produto_referencia" size="15" maxlength="20" value="<?	echo $produto_referencia ?>"  <?if(strlen($os)>0 && $bloqueia_edicao) echo "readonly='readonly'" ;?> onfocus="this.className='frm-on'; displayText('&nbsp;Entre	com	a referência do	produto	e clique na	lupa para efetuar a	pesquisa.');" onblur="this.className='frm';	displayText('&nbsp;'); checarComunicado(<?=$login_fabrica?>);" />
				&nbsp;
				<img src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle'	style="cursor:pointer" onclick="javascript:	fnc_pesquisa_produto ('',document.frm_os.produto_referencia,'')"	/>
				&nbsp;<?php

				// LUPA GRANDE - verifica se tem comunicado para este produto (só entra aqui se for abrir a OS) - FN 07/12/2006
				$arquivo_comunicado = "";
				
				if (strlen ($produto_referencia) > 0) {
					
					$sql = "SELECT tbl_comunicado.comunicado, tbl_comunicado.extensao
						      FROM tbl_comunicado JOIN tbl_produto USING(produto)
						     WHERE tbl_produto.referencia = '$produto_referencia'
						       AND tbl_comunicado.fabrica = $login_fabrica
						       AND tbl_comunicado.ativo IS TRUE";
					
					$res = pg_query($con,$sql);
					if (pg_num_rows($res) > 0) {
						$arquivo_comunicado = "HÁ ".pg_num_rows($res)." COMUNICADO(S) PARA ESTE PRODUTO";
					}

				}?>
				<input type="hidden" name="link_comunicado" value="<? echo $arquivo_comunicado; ?>" />
				<img src='imagens/botoes/vista.gif' height='22px' id="img_comunicado" target="_blank" name='img_comunicado' border='0' align='absmiddle'  title="NÃO HÁ COMUNICADOS PARA ESTE PRODUTO"
				onclick="javascript:abreComunicado()"
				style='cursor: pointer;' />
			</td>
			<td	nowrap class='Label'><?	fecho("descricao.do.produto",$con,$cook_idioma)	?></td>
			<td><input class="frm" type="text" id="produto_descricao" name="produto_descricao"	size="30" value="<?	echo $produto_descricao?>" <?if (strlen($os) > 0 && $bloqueia_edicao) echo "readonly='readonly'" ;?> onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o modelo do produto e clique na	lupa para efetuar a	pesquisa.');" onblur="this.className='frm'; displayText('&nbsp;'); verificaLinha(this.form); checarComunicado(<?=$login_fabrica?>);">&nbsp;<img src='imagens/btn_lupa_novo.gif'  style="cursor:pointer" border='0'	align='absmiddle' onclick="javascript: fnc_pesquisa_produto(document.frm_os.produto_descricao,'','')">
			</td>
		</tr>
		<tr	valign="top">
			<td	nowrap	class='Label'><? fecho("voltagem",$con,$cook_idioma) ?></td>
			<td><input class="frm" type="text" name="produto_voltagem" id="produto_voltagem" size="5" value="<?	echo $produto_voltagem ?>" readonly='readonly' ></td>
			<td	nowrap class='Label'><?	fecho("data.abertura",$con,$cook_idioma) ?></td>
			<td><input onKeyUp="formata_data(this.value,'frm_os', 'data_abertura')"	name="data_abertura" size="12" maxlength="10" value="<?	echo $data_abertura	?>"	type="text"	class="frm"	tabindex="0"  onfocus="this.className='frm-on';	displayText('&nbsp;Entre com a Data	da Abertura	da OS.');" onblur="this.className='frm'; displayText('&nbsp;');"><font size='-3' COLOR='#000099'> Ex.: <?=date('d/m/Y');?></td>
		</tr>
		<tr>
			<td	nowrap class='Label' id='label_produto_serie'><? fecho("n.serie",$con,$cook_idioma)	?>.</td>
			<td><input class="frm" type="text" id="produto_serie" name="produto_serie" size="15" maxlength="20"	value="<? echo $produto_serie ?>"  <?if(strlen($os)>0 && $bloqueia_edicao) echo	"readonly='readonly'" ;?> onfocus="this.className='frm-on';	displayText('&nbsp;Digite aqui o número	de série do	aparelho.');" onblur="this.className='frm';	displayText('&nbsp;');">
			</td>
			<td	class='Label'><? fecho("nota.fiscal",$con,$cook_idioma)	?>:</td>
			<td><input class="frm" type="text" name="nota_fiscal"  size="8"	 maxlength="8"	value="<? echo $nota_fiscal	?>"	<?if(strlen($os)>0 && $bloqueia_edicao)	echo "readonly='readonly'" ;?> onfocus="this.className='frm-on'; displayText('&nbsp;Entre com o	número da Nota Fiscal.');" onblur="this.className='frm'; displayText('&nbsp;');">
			</td>
		</tr>
		<tr>
			<td	class='Label'><? fecho("data.compra/nf",$con,$cook_idioma) ?>:</td>
			<td	nowrap>
				<input class="frm" type="text" name="data_nf" onKeyUp="formata_data(this.value,'frm_os',	'data_nf')"
						size="12" maxlength="10" value="<?=$data_nf?>" <?if (strlen($data_nf)>0) echo "readonly='readonly' ";?>
					  onblur="this.className='frm'; displayText('&nbsp;'); getInformacoesProduto(this.form);verificaLinha(this.form);"
					 onfocus="this.className='frm-on';displayText('&nbsp;Entre com a data da compra. Verifique se o produto está dentro do PRAZO DE GARANTIA.');">
				<font size='-3' color='#000099'>Ex.:&nbsp;11/02/2007</font>
			</td>
<?			if ($anexaNotaFiscal) {
				if (!$imagem_nf) {?>
				<td	class='label' id='str_nf' title='Imagem	da NF em formato JPG' style='font-weight:bold;background-color:#FFFFC0'>
					Anexar Nota	Fiscal:<br /><span style='font-aize: 10px;font-weight:normal;color: #999' title='Largura x Altura da imagem deve ser menor de 3.000.000'>JPG, máx. 3Mpx</span>
				</td>
				<td	style='background-color:#FFFFC0' id='nf_table_item'>
					<input type="file" accept="image/jpg" name="foto" class="frm" title="Selecione a imagem	em formato JPG da Nota Fiscal para anexar à	OS">
				</td>
<?			} else {?>
				<td	class='label' id='str_nf'>NF Anexada:</td>
				<td	id='nf_table_item'>
					<a href='<?=$imagem_nf; ?>'>
						<img src='imagens/btn_notafiscal.gif' title='Visualizar NF'>
					</a>
					<?=$include_imgZoom; ?>
					<img src='imagens/delete_2.gif'	id='excluir_nota' style='cursor:pointer'
						file='<?=$imagem_nf?>'	title='Excluir imagem anexada'>
				</td>
<?				}
			}?>
			</td>
		</tr>
		<tr>
		<? if($login_posto==7214 OR	$login_posto==6359){ ?>
			<td	class='Label'><? fecho("numero.controle",$con,$cook_idioma)	?></td>
			<td	nowrap><INPUT TYPE="text" NAME="numero_controle" VALUE="<? echo	$numero_controle; ?>" size="12"	maxlength="10"></td>
		<? }else { ?>
			<td	colspan='2'>&nbsp;</td>
		<? } ?>
			<td	nowrap class='Label'>
			<? fecho("consumidor",$con,$cook_idioma) ?><input type='radio' name='consumidor_revenda'  value='C'	<?
			if (strlen($consumidor_revenda)	== 0 OR	$consumidor_revenda	== 'C')	echo "checked";?> <?if(strlen($os)>0 && $bloqueia_edicao) echo "disabled" ;?>
			onclick="javascript:TipoOs('C')" >
			</td>
			<td	nowrap class='Label'>
			<? fecho("revenda",$con,$cook_idioma) ?><input type='radio'	name='consumidor_revenda'	value='R' <?
			if ($consumidor_revenda	== 'R')	echo " checked"; ?>
			<?if(strlen($os)>0 && $bloqueia_edicao)	echo "disabled"	;?>
			onclick="javascript:TipoOs('R')"></td>
		</tr>
		</table>
		<!-- Informações da	OS - FIM  -->
	</td>
</tr>
<tr><td><img height="2"	width="16" src="imagens/spacer.gif"></td></tr>
<tr>
	<td	valign="top" align="left">

		<!-- Informações do	Consumidor	-->
		<input type="hidden" name="consumidor_cliente">
		<input type="hidden" name="consumidor_rg">

		<table style=' border:#485989 1px solid; background-color: #e6eef7'	align='center' width='750' border='0'>
		<tr>
			<td	class='Label'><? fecho("nome.consumidor",$con,$cook_idioma)	?>:</td>
			<td><input class="frm" type="text" name="consumidor_nome" size="30"	maxlength="50" value="<? echo $consumidor_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)" onfocus="this.className='frm-on'; displayText('&nbsp;Insira aqui	o nome do Cliente.');" <?if(strlen($os)>0 && $bloqueia_edicao) echo	"readonly='readonly'" ;?> onblur="this.className='frm';	displayText('&nbsp;');"></td>
			<td	class='Label'><? fecho("telefone",$con,$cook_idioma) ?>:</td>
			<td><input class="frm" type="text" name="consumidor_fone"	size="15" maxlength="15" value="<? echo	$consumidor_fone ?>" <?if(strlen($os)>0 && $bloqueia_edicao) echo "readonly='readonly'"	;?>	onfocus="this.className='frm-on'; displayText('&nbsp;Insira	o telefone com o DDD. ex.: 14/4455-6677.');" onblur="this.className='frm'; displayText('&nbsp;');"></td>
			<td	class='Label'><? fecho("cep",$con,$cook_idioma)	?>:</td>
			<td><input class="frm" type="text" name="consumidor_cep"   size="10" maxlength="8" value="<? echo $consumidor_cep ?>" <?if(strlen($os)>0 && $bloqueia_edicao) echo "readonly='readonly'" ;?> onblur="this.className='frm'; displayText('&nbsp;'); buscaCEP(this.value, document.frm_os.consumidor_endereco,	document.frm_os.consumidor_bairro, document.frm_os.consumidor_cidade, document.frm_os.consumidor_estado) ;"	onfocus="this.className='frm-on'; displayText('&nbsp;Digite	o CEP do consumidor.');"></td>
		</tr>
		<tr>
			<td	class='Label'><? fecho("endereco",$con,$cook_idioma) ?>:</td>
			<td><input class="frm" type="text" name="consumidor_endereco"	size="30" maxlength="50" value="<? echo	$consumidor_endereco ?>" <?if(strlen($os)>0 && $bloqueia_edicao) echo "readonly='readonly'"	;?>	onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';	displayText('&nbsp;Digite o	endereço do	consumidor.');"></td>
			<td	class='Label'><? fecho("numero",$con,$cook_idioma) ?>:</td>
			<td><input class="frm" type="text" name="consumidor_numero"	  size="5" maxlength="10" value="<?	echo $consumidor_numero	?>"	<?if(strlen($os)>0 && $bloqueia_edicao)	echo "readonly='readonly'" ;?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o número do endereço do consumidor.');"></td>
			<td	class='Label'><? fecho("complemento",$con,$cook_idioma)	?>:</td>
			<td>	<input class="frm" type="text" name="consumidor_complemento"   size="10" maxlength="20"	value="<? echo $consumidor_complemento ?>" <?if(strlen($os)>0 && $bloqueia_edicao) echo	"readonly='readonly'" ;?> onblur="this.className='frm';	displayText('&nbsp;');"	onfocus="this.className='frm-on'; displayText('&nbsp;Digite	o complemento do endereço do consumidor.');"></td>
		</tr>
		<tr>
			<td	class='Label'><? fecho("bairro",$con,$cook_idioma) ?>:</td>
			<td><input class="frm" type="text" name="consumidor_bairro"	  size="15"	maxlength="30" value="<? echo $consumidor_bairro ?>" <?if(strlen($os)>0 && $bloqueia_edicao) echo "readonly='readonly'"	;?>	onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';	displayText('&nbsp;Digite o	bairro do consumidor.');"></td>
			<td	class='Label'><? fecho("cidade",$con,$cook_idioma) ?>:</td>
			<td><input class="frm" type="text" name="consumidor_cidade"	size="12" maxlength="30" value="<? echo	$consumidor_cidade;	?>"	<?if(strlen($os)>0 && $bloqueia_edicao)	echo "readonly='readonly'" ;?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite a cidade do consumidor.');"></td>
			<td	class='Label'><? fecho("estado",$con,$cook_idioma) ?>:</td>
			<td>
				<center>
				<? if($cook_idioma=='pt-br') {?>
				<select	name="consumidor_estado" size="1" class="frm" <?if(strlen($os)>0 && $bloqueia_edicao) echo "disabled" ;?>>
				<option	value=""   <? if (strlen($consumidor_estado) ==	0)	  echo " selected "; ?>></option>
				<option	value="AC" <? if ($consumidor_estado ==	"AC") echo " selected "; ?>>AC</option>
				<option	value="AL" <? if ($consumidor_estado ==	"AL") echo " selected "; ?>>AL</option>
				<option	value="AM" <? if ($consumidor_estado ==	"AM") echo " selected "; ?>>AM</option>
				<option	value="AP" <? if ($consumidor_estado ==	"AP") echo " selected "; ?>>AP</option>
				<option	value="BA" <? if ($consumidor_estado ==	"BA") echo " selected "; ?>>BA</option>
				<option	value="CE" <? if ($consumidor_estado ==	"CE") echo " selected "; ?>>CE</option>
				<option	value="DF" <? if ($consumidor_estado ==	"DF") echo " selected "; ?>>DF</option>
				<option	value="ES" <? if ($consumidor_estado ==	"ES") echo " selected "; ?>>ES</option>
				<option	value="GO" <? if ($consumidor_estado ==	"GO") echo " selected "; ?>>GO</option>
				<option	value="MA" <? if ($consumidor_estado ==	"MA") echo " selected "; ?>>MA</option>
				<option	value="MG" <? if ($consumidor_estado ==	"MG") echo " selected "; ?>>MG</option>
				<option	value="MS" <? if ($consumidor_estado ==	"MS") echo " selected "; ?>>MS</option>
				<option	value="MT" <? if ($consumidor_estado ==	"MT") echo " selected "; ?>>MT</option>
				<option	value="PA" <? if ($consumidor_estado ==	"PA") echo " selected "; ?>>PA</option>
				<option	value="PB" <? if ($consumidor_estado ==	"PB") echo " selected "; ?>>PB</option>
				<option	value="PE" <? if ($consumidor_estado ==	"PE") echo " selected "; ?>>PE</option>
				<option	value="PI" <? if ($consumidor_estado ==	"PI") echo " selected "; ?>>PI</option>
				<option	value="PR" <? if ($consumidor_estado ==	"PR") echo " selected "; ?>>PR</option>
				<option	value="RJ" <? if ($consumidor_estado ==	"RJ") echo " selected "; ?>>RJ</option>
				<option	value="RN" <? if ($consumidor_estado ==	"RN") echo " selected "; ?>>RN</option>
				<option	value="RO" <? if ($consumidor_estado ==	"RO") echo " selected "; ?>>RO</option>
				<option	value="RR" <? if ($consumidor_estado ==	"RR") echo " selected "; ?>>RR</option>
				<option	value="RS" <? if ($consumidor_estado ==	"RS") echo " selected "; ?>>RS</option>
				<option	value="SC" <? if ($consumidor_estado ==	"SC") echo " selected "; ?>>SC</option>
				<option	value="SE" <? if ($consumidor_estado ==	"SE") echo " selected "; ?>>SE</option>
				<option	value="SP" <? if ($consumidor_estado ==	"SP") echo " selected "; ?>>SP</option>
				<option	value="TO" <? if ($consumidor_estado ==	"TO") echo " selected "; ?>>TO</option>
			</select>
			<?}else{?>
			<input type='text' name='consumidor_estado'	maxlength='2' size='3'>
			<?}?>
			</center>
		</td>
		</tr>
		<tr>
		<td	class='Label'>Email</td>
		<td><INPUT TYPE='text' name='consumidor_email' class='frm' value='<? echo $consumidor_email	?>'	<?if(strlen($os) && $bloqueia_edicao>0)	echo "readonly='readonly'" ;?> size='30' maxlength='40'>
		</td>
		<td	class='Label'>CPF</td>
		<td><input class="frm" type="text" name="consumidor_cpf"   size="17" maxlength="18"	value="<? echo $consumidor_cpf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;<? echo "Digite o	CPF	do consumidor. Pode	ser	digitado diretamente, ou separado com pontos e traços.";?>');">
		</td>
		</tr>
		</table>
		<!-- Informações do	Consumidor - FIM -->
	</td>
</tr>
<tr><td><img height="2"	width="16" src="imagens/spacer.gif"></td></tr>
<tr>
	<td	valign="top" align="left">

		<!-- Informações da	Revenda	 -->
		<table style=' border:#485989 1px solid; background-color: #e6eef7'	align='center' width='750' border='0'>
		<tr>
		<tr>
			<td	class='Label'><? fecho("nome.revenda",$con,$cook_idioma) ?>:</td>
			<td	nowrap><input class="frm" type="text" name="revenda_nome" size="25"	maxlength="30" value="<? echo $revenda_nome	?>"	<?if(strlen($os)>0 && $bloqueia_edicao)	echo "readonly='readonly'" ;?> onfocus="this.className='frm-on'; displayText('&nbsp;Digite o nome da REVENDA onde foi adquirido	o produto.');" onblur="this.className='frm'; displayText('&nbsp;');">
			<img src='imagens/btn_lupa_novo.gif'  border='0' align='absmiddle' onclick='javascript:	fnc_pesquisa_revenda (document.frm_os.revenda_nome,	"nome")' style='cursor:	pointer' >
			</td>
			<td	class='Label'><? fecho("cnpj.revenda",$con,$cook_idioma) ?></td>
			<td><input class="frm" type="text" name="revenda_cnpj" size="20" maxlength="18"	id="revenda_cnpj" value="<?	echo $revenda_cnpj ?>" <?if(strlen($os)>0 && $bloqueia_edicao) echo	"readonly='readonly'" ;?> onblur="this.className='frm';	displayText('&nbsp;');"	onfocus="this.className='frm-on'; displayText('&nbsp;Insira	o número no	Cadastro Nacional de Pessoa	Jurídica.'); " onKeyUp="formata_cnpj(this.value, 'frm_os')">
			<img src='imagens/btn_lupa_novo.gif'  border='0' align='absmiddle' onclick='javascript:	fnc_pesquisa_revenda (document.frm_os.revenda_cnpj,	"cnpj")' style='cursor:	pointer'>
			</td>
			<td	class='Label'><? fecho("telefone",$con,$cook_idioma) ?></td>
			<td><input class="frm" type="text" name="revenda_fone" id="revenda_fone"  size="15"	maxlength="20" value="<? echo $revenda_fone	?>"	<?if(strlen($os)>0 && $bloqueia_edicao)	echo "readonly='readonly'" ;?> onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o	DDD. ex.: 14/3344-7788.');"	onblur="this.className='frm'; displayText('&nbsp;');"></td>
			</td>
		</tr>
		<tr>
			<td	class='Label'><? fecho("cep",$con,$cook_idioma)	?></td>
			<td><input class="frm" type="text" name="revenda_cep"  size="10" maxlength="9" value="<? echo $revenda_cep ?>" <?if(strlen($os)>0 && $bloqueia_edicao) echo	"readonly='readonly'" ;?> onblur="this.className='frm';	displayText('&nbsp;'); buscaCEP(this.value,	document.frm_os.revenda_endereco, document.frm_os.revenda_bairro, document.frm_os.revenda_cidade, document.frm_os.revenda_estado) ;" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CEP da revenda.');"></td>
			<td	class='Label'><? fecho("endereco",$con,$cook_idioma) ?></td>
			<td><input class="frm" type="text" name="revenda_endereco"	size="30" maxlength="50" value="<? echo	$revenda_endereco ?>" <?if(strlen($os)>0 && $bloqueia_edicao) echo "readonly='readonly'" ;?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o endereço da Revenda.');"></td>
			<td	class='Label'><? fecho("numero",$con,$cook_idioma) ?></td>
			<td><input class="frm" type="text" name="revenda_numero"  size="8" maxlength="40" value="<?	echo $revenda_numero ?>" <?if(strlen($os)>0 && $bloqueia_edicao) echo "readonly='readonly'"	;?>	onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';	displayText('&nbsp;Digite o	número do endereço da revenda.');"></td>
		</tr>
		<tr>
			<td	class='Label'><? fecho("complemento",$con,$cook_idioma)	?>:</td>
			<td><input class="frm" type="text" name="revenda_complemento"  size="10" maxlength="20"	value="<? echo $revenda_complemento	?>"	<?if(strlen($os)>0 && $bloqueia_edicao)	echo "readonly='readonly'" ;?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o complemento do endereço da revenda.');">
			<input type='hidden' name='revenda_email' value=''>
			</td>
			<td	class='Label'><? fecho("bairro",$con,$cook_idioma) ?>:</td>
			<td><input class="frm" type="text" name="revenda_bairro"  size="8" maxlength="40" value="<?	echo $revenda_bairro ?>" <?if(strlen($os)>0 && $bloqueia_edicao) echo "readonly='readonly'"	;?>	onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';	displayText('&nbsp;Digite o	bairro da revenda.');"></td>
			<td	class='Label'><? fecho("cidade",$con,$cook_idioma) ?>:</td>
			<td><input class="frm" type="text" name="revenda_cidade"  size="15"	maxlength="40" value="<? echo $revenda_cidade ?>" <?if(strlen($os)>0 && $bloqueia_edicao) echo "readonly='readonly'" ;?> onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite a cidade	da revenda.');">
			</td>
		</tr>
		<tr>
			<?if(strlen($os)>0 && $bloqueia_edicao)	echo "<input type='hidden' name='revenda_estado' value='$revenda_estado'>" ;?>
			<td	class='Label'><? fecho("estado",$con,$cook_idioma) ?></td>
			<td>
			<? if($cook_idioma=='pt-br') {?>
			<select	name="revenda_estado" size="1" class="frm" <?if(strlen($os)>0 && $bloqueia_edicao) echo	"disabled" ;?>>
			<option	value=""   <? if (strlen($revenda_estado) == 0)	   echo	" selected "; ?>></option>
				<option	value="AC" <? if ($revenda_estado == "AC") echo	" selected "; ?>>AC</option>
				<option	value="AL" <? if ($revenda_estado == "AL") echo	" selected "; ?>>AL</option>
				<option	value="AM" <? if ($revenda_estado == "AM") echo	" selected "; ?>>AM</option>
				<option	value="AP" <? if ($revenda_estado == "AP") echo	" selected "; ?>>AP</option>
				<option	value="BA" <? if ($revenda_estado == "BA") echo	" selected "; ?>>BA</option>
				<option	value="CE" <? if ($revenda_estado == "CE") echo	" selected "; ?>>CE</option>
				<option	value="DF" <? if ($revenda_estado == "DF") echo	" selected "; ?>>DF</option>
				<option	value="ES" <? if ($revenda_estado == "ES") echo	" selected "; ?>>ES</option>
				<option	value="GO" <? if ($revenda_estado == "GO") echo	" selected "; ?>>GO</option>
				<option	value="MA" <? if ($revenda_estado == "MA") echo	" selected "; ?>>MA</option>
				<option	value="MG" <? if ($revenda_estado == "MG") echo	" selected "; ?>>MG</option>
				<option	value="MS" <? if ($revenda_estado == "MS") echo	" selected "; ?>>MS</option>
				<option	value="MT" <? if ($revenda_estado == "MT") echo	" selected "; ?>>MT</option>
				<option	value="PA" <? if ($revenda_estado == "PA") echo	" selected "; ?>>PA</option>
				<option	value="PB" <? if ($revenda_estado == "PB") echo	" selected "; ?>>PB</option>
				<option	value="PE" <? if ($revenda_estado == "PE") echo	" selected "; ?>>PE</option>
				<option	value="PI" <? if ($revenda_estado == "PI") echo	" selected "; ?>>PI</option>
				<option	value="PR" <? if ($revenda_estado == "PR") echo	" selected "; ?>>PR</option>
				<option	value="RJ" <? if ($revenda_estado == "RJ") echo	" selected "; ?>>RJ</option>
				<option	value="RN" <? if ($revenda_estado == "RN") echo	" selected "; ?>>RN</option>
				<option	value="RO" <? if ($revenda_estado == "RO") echo	" selected "; ?>>RO</option>
				<option	value="RR" <? if ($revenda_estado == "RR") echo	" selected "; ?>>RR</option>
				<option	value="RS" <? if ($revenda_estado == "RS") echo	" selected "; ?>>RS</option>
				<option	value="SC" <? if ($revenda_estado == "SC") echo	" selected "; ?>>SC</option>
				<option	value="SE" <? if ($revenda_estado == "SE") echo	" selected "; ?>>SE</option>
				<option	value="SP" <? if ($revenda_estado == "SP") echo	" selected "; ?>>SP</option>
				<option	value="TO" <? if ($revenda_estado == "TO") echo	" selected "; ?>>TO</option>
			</select>
			<?}else{?>
			<input type='text' name='revenda_estado' maxlength='2' size='3'>
			<?}?>
			</td>
		</tr>
		</table>
		<!-- Informações da	Revenda	 FIM -->
	</td>
</tr>
<tr><td><img height="2"	width="16" src="imagens/spacer.gif"></td></tr>
<tr>
	<td	valign="top" align="left">
<?if(strlen($os)>0 && $bloqueia_edicao){
	$sql = "SELECT tbl_os.produto,tbl_linha.linha,tbl_familia.familia
		FROM tbl_os
		JOIN tbl_produto USING(produto)
		JOIN tbl_linha	 ON	tbl_linha.linha		= tbl_produto.linha
		JOIN tbl_familia ON	tbl_familia.familia	= tbl_produto.familia
		WHERE tbl_os.os	= $os
		AND	  tbl_os.fabrica = $login_fabrica";
	$res = pg_query($con,$sql) ;
	if(pg_num_rows($res)>0){
		$produto = pg_fetch_result($res,0,produto);
		$familia = pg_fetch_result($res,0,familia);
		$linha	 = pg_fetch_result($res,0,linha);
	}
}

?>
	<input type='hidden' name='produto'	id='produto' value='<?=$produto?>'>
	<input type='hidden' name='linha'	id='linha'	 value='<?=$linha?>'>
	<input type='hidden' name='familia'	id='familia' value='<?=$familia?>'>
<?

if (strlen($os)	> 0) {
	$sql = "SELECT	tbl_os.*									  ,
			tbl_produto.referencia						  ,
			tbl_produto.descricao						  ,
			tbl_produto.voltagem						  ,
			tbl_produto.linha							  ,
			tbl_produto.familia							  ,
			tbl_os_extra.os_reincidente	AS reincidente_os ,
			tbl_posto_fabrica.codigo_posto				  ,
			tbl_posto_fabrica.reembolso_peca_estoque
		FROM	tbl_os
		JOIN	tbl_os_extra USING (os)
		JOIN	tbl_produto	 USING (produto)
		JOIN	tbl_posto		  USING	(posto)
		JOIN	tbl_posto_fabrica ON  tbl_posto.posto			= tbl_posto_fabrica.posto
			  AND tbl_posto_fabrica.fabrica	= $login_fabrica
		WHERE	tbl_os.os =	$os";
	$res = @pg_query ($con,$sql) ;

	if (@pg_num_rows($res) > 0)	{
		$login_posto				 = pg_fetch_result($res,0,posto);
		$linha						 = pg_fetch_result($res,0,linha);
		$familia					 = pg_fetch_result($res,0,familia);
		$consumidor_nome			 = pg_fetch_result($res,0,consumidor_nome);
		$sua_os						 = pg_fetch_result($res,0,sua_os);
		$produto_os					 = pg_fetch_result($res,0,produto);
		$produto_referencia			 = pg_fetch_result($res,0,referencia);
		$produto_descricao			 = pg_fetch_result($res,0,descricao);
		$produto_voltagem			 = pg_fetch_result($res,0,voltagem);
		$produto_serie				 = pg_fetch_result($res,0,serie);
		$qtde_produtos				 = pg_fetch_result($res,0,qtde_produtos);
		$produto_type				 = pg_fetch_result($res,0,type);
		$defeito_reclamado			 = pg_fetch_result($res,0,defeito_reclamado);
		$defeito_constatado			 = pg_fetch_result($res,0,defeito_constatado);
		$causa_defeito				 = pg_fetch_result($res,0,causa_defeito);
		$posto						 = pg_fetch_result($res,0,posto);
		$obs						 = pg_fetch_result($res,0,obs);
		$os_reincidente				 = pg_fetch_result($res,0,reincidente_os);
		$codigo_posto				 = pg_fetch_result($res,0,codigo_posto);
		$reembolso_peca_estoque		 = pg_fetch_result($res,0,reembolso_peca_estoque);
		$consumidor_revenda			 = pg_fetch_result($res,0,consumidor_revenda);
		$troca_faturada				 = pg_fetch_result($res,0,troca_faturada);
		$motivo_troca				 = pg_fetch_result($res,0,motivo_troca);
		$defeito_reclamado_descricao = pg_fetch_result($res,0,defeito_reclamado_descricao);
	}
}
//--==== Defeito Reclamado ===============================================================================
	echo "<table style=' border: #D3BE96 1px solid;	background-color: #FCF0D8 '	align='center' width='750' border='0'>";
	echo "<tr>";
	echo "<td class='Titulo' align='left' colspan='2'>";
	fecho("analise.de.produto",$con,$cook_idioma);
	echo ":	<div id='dados'	style='display:inline;'><i><u> ";
	fecho("nao.informado",$con,$cook_idioma);
	echo "</i></u></div>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td nowrap class='Label'>";
	fecho("aparencia.do.produto",$con,$cook_idioma);
	echo ":</td>
	<td	nowrap><input class='frm' type='text' name='aparencia_produto' size='33' maxlength='50'	value='$aparencia_produto'>";
	echo "<div id='aparencia' class='aparencia_produto'	>";
	$sql_ap	= "	SELECT peca,descricao
			 FROM tbl_peca
			 WHERE fabrica = $login_fabrica
			 AND   peca	in ( 866587, 866585, 866584, 866583, 866582, 866581, 866580, 866579, 866578, 866577, 866576, 866575)
			 ORDER BY peca,descricao";
	$res_ap	= pg_query($con,$sql_ap);
	if(pg_num_rows($res_ap)	> 0) {
		echo "<table >";
		for($i =0 ;$i< pg_num_rows($res_ap);$i++){
			$peca_ap		 = pg_fetch_result($res_ap,$i,peca);
			$descricao_ap =	pg_fetch_result($res_ap,$i,descricao);
			echo "<tr>";
			echo "<td nowrap>$descricao_ap</td>";
			$sqlx= "SELECT produto_aparencia,descricao
					FROM tbl_produto_aparencia
					WHERE fabrica =	$login_fabrica
					ORDER BY produto_aparencia";
			$resx =	pg_query($con,$sqlx);
			echo "<td nowrap>";
			for($j = 0;$j<pg_num_rows($resx);$j++){
				$produto_aparencia = pg_fetch_result($resx,$j,produto_aparencia);
				$descricao_aparencia = pg_fetch_result($resx,$j,descricao);
				$check_ap		   = $_POST[$peca_ap."".$produto_aparencia];
				echo "<input type='checkbox' name='".$peca_ap."".$produto_aparencia."'";
				if(strlen($check_ap) > 0 ) echo	" CHECKED ";
				echo " value='1'>$descricao_aparencia&nbsp;";
			}
			echo "</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
	echo "</div>";

	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td nowrap class='Label'>";
	fecho("acessorios",$con,$cook_idioma);
	echo ":</td>
	<td><input class='frm' type='text' name='acessorios' size='33' maxlength='50' value='$acessorios'></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='Label'	align='left' >";
	fecho("defeito.reclamado",$con,$cook_idioma);
	echo ":</td>";
	echo "<td>";
	if((strlen($os)>0 and strlen($defeito_reclamado)>0) && $bloqueia_edicao){
		$sql = "SELECT defeito_reclamado, descricao	FROM tbl_defeito_reclamado WHERE defeito_reclamado = $defeito_reclamado";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$defeito_reclamado			 = pg_fetch_result($res,0,defeito_reclamado);
			$defeito_reclamado_descricao = pg_fetch_result($res,0,descricao);
		}
		echo '<font	size="1" face="Geneva, Arial, Helvetica, san-serif">';
		echo "</font>";
		echo "
		<select	class='frm'	disabled>
		<option	value=''>$defeito_reclamado_descricao</option>
		</select>";
		echo "<INPUT TYPE='hidden' NAME='defeito_reclamado'	VALUE='$defeito_reclamado'>"; //HD 53618
	}else{
		echo "<select name='defeito_reclamado' id='defeito_reclamado' class='frm' style='width:220px;' onfocus='listaDefeitos(document.frm_os.produto_referencia.value);' >";
		echo "<option id='opcoes' value=''></option>";
		if(strlen($defeito_reclamado) >0){
			$sql = "SELECT descricao FROM tbl_defeito_reclamado	WHERE defeito_reclamado=$defeito_reclamado";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				$defeito_reclamado_descricao = pg_fetch_result($res,0,0);
				echo "<option value='$defeito_reclamado' selected>$defeito_reclamado_descricao</option>";
			}
		}
		echo "</select>";
	}
	#echo "<input class='frm' type='text' name='defeito_reclamado_descricao' size='33' maxlength='50' value='$defeito_reclamado_descricao'>";
	echo "</td>";
	echo "</tr>";

//--==== Defeito Constatado	==============================================================================
if ($pedir_defeito_constatado_os_item != "f") {
	echo "<tr>";
	echo "<td class='Label'	align='left'>";
	fecho("defeito.constatado",$con,$cook_idioma);
	echo"<div id='integrigade' style='position:	absolute;visibility:hidden;	opacity:.90;filter:	Alpha(Opacity=90);width:401px; border: #555555 1px solid; background-color:	#EFEFEF'></div>";
	echo "</td>";
	echo "<td>";
	if(strlen($os)==0){
		echo "<select name='defeito_constatado'	id=defeito_constatado class='frm' style='width:220px;' onfocus='listaConstatado(document.frm_os.produto_referencia.value);'	>";
		echo "<option id='opcoes2' value=''></option>";
		if(strlen($defeito_constatado) >0){
		$sql = "SELECT descricao FROM tbl_defeito_constatado WHERE defeito_constatado=$defeito_constatado";
			$res = pg_query($con,$sql);
			if(pg_num_rows($res) > 0){
				$defeito_constatado_descricao =	pg_fetch_result($res,0,0);
				echo "<option value='$defeito_constatado' selected>$defeito_constatado_descricao</option>";
			}
		}
		echo "</select>";
	}else{
		echo "<select name='defeito_constatado'	 class='frm' style='width: 220px;' >";
		echo "<option selected></option>";
		$sql = "SELECT defeito_constatado_por_familia, defeito_constatado_por_linha	FROM tbl_fabrica WHERE fabrica = $login_fabrica";

		$res = pg_query	($con,$sql);
		$defeito_constatado_por_familia	= pg_fetch_result($res,0,0) ;
		$defeito_constatado_por_linha	= pg_fetch_result($res,0,1) ;

		if ($defeito_constatado_por_familia	== 't')	{
			$sql = "SELECT familia FROM	tbl_produto	WHERE produto =	$produto_os";
			$res = pg_query	($con,$sql);
			$familia = pg_fetch_result($res,0,0) ;

			$sql = "SELECT tbl_defeito_constatado.*
					FROM   tbl_familia
					JOIN   tbl_familia_defeito_constatado USING(familia)
					JOIN   tbl_defeito_constatado		  USING(defeito_constatado)
					WHERE  tbl_defeito_constatado.fabrica		  =	$login_fabrica	  AND tbl_defeito_constatado.ativo = 't'
					AND	   tbl_familia_defeito_constatado.familia =	$familia
					ORDER BY tbl_defeito_constatado.descricao";
		}else{
			if ($defeito_constatado_por_linha == 't') {
				$sql   = "SELECT linha FROM	tbl_produto	WHERE produto =	$produto_os";
				$res   = pg_query ($con,$sql);
				$linha = pg_fetch_result($res,0,0)	;

				$sql = "SELECT tbl_defeito_constatado.*
						FROM   tbl_defeito_constatado
						JOIN   tbl_linha USING(linha)
						WHERE  tbl_defeito_constatado.fabrica		  =	$login_fabrica
						AND	   ativo = 't'
						AND	   tbl_linha.linha = $linha
						ORDER BY tbl_defeito_constatado.descricao";
			}else{
				$sql = "SELECT tbl_defeito_constatado.*
					FROM   tbl_defeito_constatado
					WHERE  tbl_defeito_constatado.fabrica =	$login_fabrica
					AND	   ativo = 't'
					ORDER BY tbl_defeito_constatado.descricao";
			}
		}
		$res = pg_query	($con,$sql)	;

		for	($i	= 0	; $i < pg_num_rows ($res) ;	$i++ ) {

			$descricao_d = pg_fetch_result($res,$i,descricao);

			echo "<option ";
			if ($defeito_constatado	== pg_fetch_result($res,$i,defeito_constatado)	) echo " selected ";
			echo " value='"	. pg_fetch_result($res,$i,defeito_constatado) . "'>" ;
			echo pg_fetch_result($res,$i,codigo) ." - ". $descricao_d ;
			echo "</option>";
		}

		echo "</select>";
	}
	echo "</td>";
	echo "</tr>";
}

if ($pedir_solucao_os_item <> 'f') {
	echo "<tr>";
	echo "<td class='Label'align='left'	>";
	fecho("solucao",$con,$cook_idioma);
	echo "</td>";
	echo "<td>";
	echo "<select name='solucao_os'	size='1' class='frm' id='solucao_os'>";
	echo "<option value=''></option>";

	$sql = "SELECT *
			FROM   tbl_servico_realizado
			WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";
	if ($login_pede_peca_garantia == 't') {
		$sql .=	"AND tbl_servico_realizado.descricao NOT ILIKE 'troca%'	";
	}

	$sql .=	" AND tbl_servico_realizado.ativo IS TRUE ORDER	BY descricao ";
	$res = pg_query	($con,$sql)	;

	if (pg_num_rows($res) == 0)	{
		$sql = "SELECT *
				FROM   tbl_servico_realizado
				WHERE  tbl_servico_realizado.fabrica = $login_fabrica ";
		if ($login_pede_peca_garantia == 't' ) {
			$sql .=	"AND tbl_servico_realizado.descricao NOT ILIKE 'troca%'	";
		}
		$sql .=	" AND tbl_servico_realizado.linha IS NULL
				  AND tbl_servico_realizado.ativo IS TRUE ORDER	BY descricao ";
		$res = pg_query	($con,$sql)	;
	}

	for	($x	= 0	; $x < pg_num_rows($res) ; $x++	) {

		$descricao_d = pg_fetch_result($res,$x,descricao);

		echo "<option ";
		if ($solucao_os	== pg_fetch_result($res,$x,servico_realizado))	echo " selected	";
		echo " value='"	. pg_fetch_result($res,$x,servico_realizado) .	"'>" ;
		echo $descricao_d ;
		echo "</option>";
	}
	echo "</select>";
	echo "<div id='solucao_os2'	style='display:none; visibility:hidden;'>";
	echo "<input type='text' name='solucao_os2'	size='33' maxlength='50' value='$solucao_os2'>";
	echo "<div>";
	echo "</td>";
	echo "</tr>";
}
echo "</table>";

?>

	</td>
</tr>
<tr><td><img height="4"	width="16" src="imagens/spacer.gif"></td></tr>
<tr>
	<td	valign="top" align="left">

<?
#---------------- Carrega campos de	configuração da	Fabrica	-------------
$sql = "SELECT	tbl_fabrica.os_item_subconjunto	  ,
				tbl_fabrica.pergunta_qtde_os_item ,
				tbl_fabrica.os_item_serie		  ,
				tbl_fabrica.os_item_aparencia	  ,
				tbl_fabrica.qtde_item_os
		FROM	tbl_fabrica
		WHERE	tbl_fabrica.fabrica	= $login_fabrica;";
$resX =	pg_query ($con,$sql);

if (pg_num_rows($resX) > 0)	{
	$os_item_subconjunto = pg_fetch_result($resX,0,os_item_subconjunto);
	if (strlen ($os_item_subconjunto) == 0)	$os_item_subconjunto = 't';

	$pergunta_qtde_os_item = pg_fetch_result($resX,0,pergunta_qtde_os_item);
	if (strlen ($pergunta_qtde_os_item)	== 0) $pergunta_qtde_os_item = 'f';

	$os_item_serie = pg_fetch_result($resX,0,os_item_serie);
	if (strlen ($os_item_serie)	== 0) $os_item_serie = 'f';

	$os_item_aparencia = pg_fetch_result($resX,0,os_item_aparencia);
	if (strlen ($os_item_aparencia)	== 0) $os_item_aparencia = 'f';

	$qtde_item = pg_fetch_result($resX,0,qtde_item_os);
	if (strlen ($qtde_item)	== 0) $qtde_item = 5;
}

###	LISTA ITENS	DA OS QUE POSSUEM PEDIDOS
if(strlen($os) > 0){
	$sql = "SELECT	tbl_os_item.pedido									,
					case when tbl_pedido.pedido_blackedecker > 99999 then
						lpad((tbl_pedido.pedido_blackedecker - 100000)::text,5,'0')
					else
						lpad(tbl_pedido.pedido_blackedecker::text,5,'0')
					end							  AS pedido_blackedecker,
					tbl_os_item.posicao									,
					tbl_os_item.qtde									,
					tbl_os_item.causa_defeito							,
					tbl_os_item.admin  as admin_peca					,
					tbl_peca.referencia									,
					tbl_peca.descricao									,
					tbl_defeito.defeito									,
					tbl_defeito.descricao AS defeito_descricao			,
					tbl_causa_defeito.descricao	AS causa_defeito_descricao,
					tbl_produto.referencia AS subconjunto				,
					tbl_os_produto.produto								,
					tbl_os_produto.serie								,
					tbl_servico_realizado.servico_realizado				,
					tbl_servico_realizado.descricao	AS servico_descricao
			FROM	tbl_os
			JOIN   (SELECT os FROM tbl_os WHERE	os = $os AND fabrica = $login_fabrica) oss ON tbl_os.os	= oss.os
			JOIN	tbl_os_produto			   ON tbl_os.os	= tbl_os_produto.os
			JOIN	tbl_os_item				   ON tbl_os_produto.os_produto	= tbl_os_item.os_produto
			JOIN	tbl_produto				   ON tbl_os_produto.produto = tbl_produto.produto
			JOIN	tbl_peca				   ON tbl_os_item.peca = tbl_peca.peca
			JOIN	tbl_pedido				   ON tbl_os_item.pedido	   = tbl_pedido.pedido
			LEFT JOIN	 tbl_defeito		   USING (defeito)
			LEFT JOIN	 tbl_causa_defeito	   ON tbl_os_item.causa_defeito	= tbl_causa_defeito.causa_defeito
			LEFT JOIN	 tbl_servico_realizado USING (servico_realizado)
			WHERE	tbl_os.os	   = $os
			AND		tbl_os.fabrica = $login_fabrica
			AND		tbl_os_item.pedido NOTNULL
			ORDER BY tbl_os_item.os_item ASC;";
	$res = pg_query	($con,$sql)	;

	if(pg_num_rows($res) > 0) {

		echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>";
		echo "<tr height='20' bgcolor='#666666'>";

		echo "<td align='center' colspan='4'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#ffffff'><b>Pedidos já enviados ao fabricante</b></font></td>";

		echo "</tr>";
		echo "<tr height='20' bgcolor='#666666'>";

		echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#ffffff'><b>Pedido</b></font></td>";
		echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#ffffff'><b>Posição</b></font></td>";

		echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#ffffff'><b>Referência</b></font></td>";
		echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#ffffff'><b>Descrição</b></font></td>";
		echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#ffffff'><b>Qtde</b></font></td>";

		echo "</tr>";

		$numero_pecas_faturadas=pg_num_rows($res);

		for	($i	= 0	; $i < pg_num_rows($res) ; $i++) {
				$faturado	   = pg_num_rows($res);
				$fat_pedido	   = pg_fetch_result($res,$i,pedido);
				$fat_pedido_blackedecker = pg_fetch_result($res,$i,pedido_blackedecker);
				$posicao	   = pg_fetch_result($res,$i,posicao);
				$fat_peca	   = pg_fetch_result($res,$i,referencia);
				$fat_descricao = pg_fetch_result($res,$i,descricao);
				$fat_qtde	   = pg_fetch_result($res,$i,qtde);

				echo "<tr height='20' bgcolor='#FFFFFF'>";

				echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#000000'>$fat_pedido";
				echo "</font></td>";
				echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#000000'>$fat_peca</font></td>";
				echo "<td align='left'><font size='1' face='Geneva,	Arial, Helvetica, san-serif' color='#000000'>$fat_descricao</font></td>";
				echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#000000'>$fat_qtde</font></td>";

				echo "</tr>";
		}
		echo "</table>";
	}
}

###	LISTA ITENS	DA OS QUE ESTÃO	COMO NÃO LIBERADAS PARA	PEDIDO EM GARANTIA
if(strlen($os) > 0){
	$sql = "SELECT	tbl_os_item.os_item									,
					tbl_os_item.obs										,
					tbl_os_item.qtde									,
					tbl_os_item.posicao									,
					tbl_os_item.admin  as admin_peca					,
					tbl_peca.referencia									,
					tbl_peca.descricao									,
					tbl_defeito.defeito									,
					tbl_defeito.descricao AS defeito_descricao			,
					tbl_produto.referencia AS subconjunto				,
					tbl_os_produto.produto								,
					tbl_os_produto.serie								,
					tbl_servico_realizado.servico_realizado				,
					tbl_servico_realizado.descricao	AS servico_descricao
			FROM	tbl_os
			JOIN   (SELECT os FROM tbl_os WHERE	os = $os AND fabrica = $login_fabrica) oss ON tbl_os.os	= oss.os
			JOIN	tbl_os_produto			   ON tbl_os.os	= tbl_os_produto.os
			JOIN	tbl_os_item				   ON tbl_os_produto.os_produto	= tbl_os_item.os_produto
			JOIN	tbl_produto				   ON tbl_os_produto.produto = tbl_produto.produto
			JOIN	tbl_peca				   ON tbl_os_item.peca = tbl_peca.peca
			LEFT JOIN	 tbl_pedido			   ON tbl_os_item.pedido	   = tbl_pedido.pedido
			LEFT JOIN	 tbl_defeito		   USING (defeito)
			LEFT JOIN	 tbl_servico_realizado USING (servico_realizado)
			WHERE	tbl_os.os	   = $os
			AND		tbl_os.fabrica = $login_fabrica
			AND		tbl_os_item.liberacao_pedido		   IS FALSE
			AND		tbl_os_item.liberacao_pedido_analisado IS TRUE
			ORDER BY tbl_os_item.os_item ASC;";
	$res = pg_query	($con,$sql)	;

	if(pg_num_rows($res) > 0) {
		$col = 6;
		echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>\n";
		echo "<tr height='20' bgcolor='#666666'>\n";
		echo "<td align='center' colspan='$col'><font size='1' face='Geneva, Arial,	Helvetica, san-serif' color='#ffffff'><b>Peças que não irão	gerar pedido em	garantia</b></font></td>\n";
		echo "</tr>\n";
		echo "<tr height='20' bgcolor='#666666'>\n";
		echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#ffffff'><b>Pedido</b></font></td>\n";
		echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#ffffff'><b>Posição</b></font></td>\n";
		echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#ffffff'><b>Referência</b></font></td>\n";
		echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#ffffff'><b>Descrição</b></font></td>\n";
		echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#ffffff'><b>Qtde</b></font></td>\n";
		echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#ffffff'><b>Excluir</b></font></td>\n";
		echo "</tr>\n";

		for	($i	= 0	; $i < pg_num_rows($res) ; $i++) {
				$recusado	   = pg_num_rows($res);
				$rec_item	   = pg_fetch_result($res,$i,os_item);
				$rec_obs	   = pg_fetch_result($res,$i,obs);
				$posicao	   = pg_fetch_result($res,$i,posicao);
				$rec_peca	   = pg_fetch_result($res,$i,referencia);
				$rec_descricao = pg_fetch_result($res,$i,descricao);
				$rec_qtde	   = pg_fetch_result($res,$i,qtde);

				echo "<tr height='20' bgcolor='#FFFFFF'>";
				echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#000000'>$rec_obs</font></td>\n";
				echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#000000'>$posicao</font></td>";
				echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#000000'>$rec_peca</font></td>\n";
				echo "<td align='left'><font size='1' face='Geneva,	Arial, Helvetica, san-serif' color='#000000'>$rec_descricao</font></td>\n";
				echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#000000'>$rec_qtde</font></td>\n";
				echo "<td align='center'>
						<INPUT TYPE='hidden' NAME='rec_os' VALUE='$os|$rec_os_produto'>
						<IMG SRC=\"imagens/btn_excluir.gif\" id='excluir_os_item' style='cursor: pointer;' ALT=\"Excluir\">
					</td>";
				echo "</tr>\n";
		}
		echo "</table>\n";
	}
}

###	LISTA ITENS	DA OS FORAM	LIBERADAS E	AINDA NÃO POSSEM PEDIDO
if(strlen($os) > 0){
	$sql = "SELECT	tbl_os_item.os_item									,
					tbl_os_item.os_produto								,
					tbl_os_item.obs										,
					tbl_os_item.qtde									,
					tbl_os_item.admin  as admin_peca					,
					tbl_peca.referencia									,
					tbl_peca.descricao									,
					tbl_defeito.defeito									,
					tbl_defeito.descricao AS defeito_descricao			,
					tbl_produto.referencia AS subconjunto				,
					tbl_os_produto.produto								,
					tbl_os_produto.serie								,
					tbl_servico_realizado.servico_realizado				,
					tbl_servico_realizado.descricao	AS servico_descricao
			FROM	tbl_os
			JOIN   (SELECT os FROM tbl_os WHERE	os = $os AND fabrica = $login_fabrica) oss ON tbl_os.os	= oss.os
			JOIN	tbl_os_produto			   ON tbl_os.os	= tbl_os_produto.os
			JOIN	tbl_os_item				   ON tbl_os_produto.os_produto	= tbl_os_item.os_produto
			JOIN	tbl_produto				   ON tbl_os_produto.produto = tbl_produto.produto
			JOIN	tbl_peca				   ON tbl_os_item.peca = tbl_peca.peca
			LEFT JOIN	 tbl_pedido			   ON tbl_os_item.pedido	   = tbl_pedido.pedido
			LEFT JOIN	 tbl_defeito		   USING (defeito)
			LEFT JOIN	 tbl_servico_realizado USING (servico_realizado)
			WHERE	tbl_os.os	   = $os
			AND		tbl_os.fabrica = $login_fabrica
			AND		tbl_os_item.pedido			 ISNULL
			AND		tbl_os_item.liberacao_pedido IS	TRUE
			ORDER BY tbl_os_item.os_item ASC;";
	$res = pg_query	($con,$sql)	;

	if(pg_num_rows($res) > 0) {
		#HD 311925
		if(strlen($os)>0){
			$sqlA = "select cancelada AS cancelada_auditoria
					 from tbl_os_auditar
					 where os = $os
					 and os_auditar = (select max(os_auditar) from tbl_os_auditar where os = $os);";
					#echo nl2br($sqlA);
			$resA = pg_exec($con,$sqlA);

			if(pg_numrows($resA)>0){
				$cancelada_auditoria = pg_result($resA,0,cancelada_auditoria);
			}
		}

		echo "<table width='100%' border='0' cellspacing='2' cellpadding='0'>\n";
		echo "<tr height='20' bgcolor='#666666'>\n";
		echo "<td align='center' colspan='5'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#ffffff'><b>Peças	aprovadas aguardando pedido</b></font></td>\n";
		echo "</tr>\n";
		echo "<tr height='20' bgcolor='#666666'>\n";
		echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#ffffff'><b>Pedido</b></font></td>\n";
		echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#ffffff'><b>Referência</b></font></td>\n";
		echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#ffffff'><b>Descrição</b></font></td>\n";
		echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#ffffff'><b>Qtde</b></font></td>\n";

		if($cancelada_auditoria=='t'){
			echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#ffffff'><b>Excluir</b></font></td>\n";
		}
		echo "</tr>\n";

		for	($i	= 0	; $i < pg_num_rows($res) ; $i++) {
			$recusado	    = pg_num_rows($res);
			$rec_item	    = pg_fetch_result($res,$i,os_item);
			$rec_os_produto = pg_fetch_result($res,$i,os_produto);
			$rec_obs	    = pg_fetch_result($res,$i,obs);
			$rec_peca	    = pg_fetch_result($res,$i,referencia);
			$rec_descricao  = pg_fetch_result($res,$i,descricao);
			$rec_qtde	    = pg_fetch_result($res,$i,qtde);

			echo "<tr height='20' bgcolor='#FFFFFF'>";
			echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#000000'>$rec_obs</font></td>\n";
			echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#000000'>$rec_peca</font></td>\n";
			echo "<td align='left'><font size='1' face='Geneva,	Arial, Helvetica, san-serif' color='#000000'>$rec_descricao</font></td>\n";
			echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#000000'>$rec_qtde</font></td>\n";

			if($cancelada_auditoria=='t'){
				echo "<td align='center'>
				<INPUT TYPE='hidden' NAME='rec_os' VALUE='$os|$rec_os_produto'>
				<IMG SRC=\"imagens/btn_excluir.gif\" id='excluir_os_item' ALT=\"Excluir\">
				</td>";
			}
			echo "</tr>\n";
		}
		echo "</table>\n";
	}
}
$os_item = array();
if(strlen($os) > 0 AND strlen ($msg_erro) == 0){
	if ($os_item_aparencia == 't' AND $posto_item_aparencia	== 't' and $os_item_subconjunto	== 'f')	{
		$sql = "SELECT	tbl_peca.peca
				FROM	tbl_peca
				JOIN	tbl_lista_basica USING (peca)
				JOIN	tbl_produto		 USING (produto)
				WHERE	tbl_produto.produto		= $produto_os
				AND		tbl_peca.fabrica		= $login_fabrica
				AND		tbl_peca.item_aparencia	= 't'
				ORDER BY tbl_peca.referencia;";
		$resX =	@pg_query($con,$sql);
		$inicio_itens =	@pg_num_rows($resX);
	}else{
		$inicio_itens =	0;
	}
	$sql = "SELECT	tbl_os_item.os_item												   ,
					tbl_os_item.pedido												   ,
					tbl_os_item.qtde												   ,
					tbl_os_item.causa_defeito										   ,
					tbl_os_item.posicao												   ,
					tbl_os_item.admin  as admin_peca								   ,
					tbl_os_item.adicional_peca_estoque								   ,
					tbl_peca.referencia												   ,
					tbl_peca.descricao												   ,
					tbl_defeito.defeito												   ,
					tbl_defeito.descricao					AS defeito_descricao	   ,
					tbl_causa_defeito.descricao				AS causa_defeito_descricao ,
					tbl_produto.referencia					AS subconjunto			   ,
					tbl_os_produto.os_produto										   ,
					tbl_os_produto.produto											   ,
					tbl_os_produto.serie											   ,
					tbl_servico_realizado.servico_realizado							   ,
					tbl_servico_realizado.descricao			AS servico_descricao
		FROM	tbl_os_item
		JOIN	tbl_os_produto			   USING (os_produto)
		JOIN	tbl_produto				   USING (produto)
		JOIN	tbl_os					   USING (os)
		JOIN	tbl_peca				   USING (peca)
		LEFT JOIN tbl_defeito			   USING (defeito)
		LEFT JOIN tbl_servico_realizado	   USING (servico_realizado)
		LEFT JOIN tbl_causa_defeito	ON tbl_os_item.causa_defeito = tbl_causa_defeito.causa_defeito
		WHERE	tbl_os.os	   = $os
		AND		tbl_os.fabrica = $login_fabrica
		AND		tbl_os_item.pedido					   IS NULL
		AND		tbl_os_item.liberacao_pedido IS	FALSE
		ORDER BY tbl_os_item.os_item;";

	$res = pg_query	($con,$sql)	;

	if (pg_num_rows($res) >	0) {
		$fim_itens = $inicio_itens + pg_num_rows($res);
		$i = 0;
		for	($k	= $inicio_itens	; $k < $fim_itens ;	$k++) {
			$os_item[$k]				 = pg_fetch_result($res,$i,os_item);
			$os_produto[$k]				 = pg_fetch_result($res,$i,os_produto);
			$pedido[$k]					 = pg_fetch_result($res,$i,pedido);
			$peca[$k]					 = pg_fetch_result($res,$i,referencia);
			$qtde[$k]					 = pg_fetch_result($res,$i,qtde);
			$produto[$k]				 = pg_fetch_result($res,$i,subconjunto);
			$produto2[$k]				 = pg_fetch_result($res,$i,subconjunto);
			$serie[$k]					 = pg_fetch_result($res,$i,serie);
			$posicao[$k]				 = pg_fetch_result($res,$i,posicao);
			$descricao[$k]				 = pg_fetch_result($res,$i,descricao);
			$defeito[$k]				 = pg_fetch_result($res,$i,defeito);
			$pcausa_defeito[$k]			 = pg_fetch_result($res,$i,causa_defeito);
			$causa_defeito_descricao[$k] = pg_fetch_result($res,$i,causa_defeito_descricao);
			$defeito_descricao[$k]		 = pg_fetch_result($res,$i,defeito_descricao);
			$servico[$k]				 = pg_fetch_result($res,$i,servico_realizado);
			$servico_descricao[$k]		 = pg_fetch_result($res,$i,servico_descricao);
			$adicional[$k]				 = pg_fetch_result($res,$i,adicional_peca_estoque);
			$admin_peca[$k]				 = pg_fetch_result($res,$i,admin_peca);
			$produto_os[$k]				 = pg_fetch_result($res,$i,produto);
			if(strlen($admin_peca[$k])==0) { $admin_peca[$k]="P"; }
			$i++;
		}
	}else{
		for	($i	= 0	; $i < $qtde_item ;	$i++) {
			$os_item[$i]		= ($_POST["os_item_". $i]) ? $_POST["os_item_".$i] : '';
			$os_produto[$i]		= $_POST["os_produto_"	   . $i];
			$produto[$i]		= $_POST["produto_"		   . $i];
			$produto2[$i]		 = $_POST["produto_"		. $i];
			$serie[$i]			= $_POST["serie_"		   . $i];
			$posicao[$i]		= $_POST["posicao_"		   . $i];
			$peca[$i]			= $_POST["peca_"		   . $i];
			$descricao[$i]		= $_POST["descricao_"	   . $i];
			$qtde[$i]			= $_POST["qtde_"		   . $i];
			$defeito[$i]		= $_POST["defeito_"		   . $i];
			$pcausa_defeito[$i]	= $_POST["pcausa_defeito_" . $i];
			$servico[$i]		= $_POST["servico_"		   . $i];
			$adicional[$i]		= $_POST["adicional_peca_estoque_"		  .	$i];
			$admin_peca[$i]		= $_POST["admin_peca_"	   . $i];
		}
	}
}elseif(strlen($msg_erro)==0){//ok
	for	($i	= 0	; $i < $qtde_item ;	$i++) {
		$os_item[$i]		= $_POST["os_item_"		   . $i];
		$os_produto[$i]		= $_POST["os_produto_"	   . $i];
		$produto[$i]		= $_POST["produto_"		   . $i];
		$produto2[$i]		 = $_POST["produto_"		. $i];
		$serie[$i]			= $_POST["serie_"		   . $i];
		$posicao[$i]		= $_POST["posicao_"		   . $i];
		$peca[$i]			= $_POST["peca_"		   . $i];
		$descricao[$i]		= $_POST["descricao_"	   . $i];
		$qtde[$i]			= $_POST["qtde_"		   . $i];
		$defeito[$i]		= $_POST["defeito_"		   . $i];
		$pcausa_defeito[$i]	= $_POST["pcausa_defeito_" . $i];
		$servico[$i]		= $_POST["servico_"		   . $i];
		$adicional[$i]		= $_POST["adicional_peca_estoque_"		  .	$i];
		$admin_peca[$i]		= $_POST["admin_peca_"	   . $i];
	}
}else{
	if(strlen($msg_erro)>0){//HD 33776 26/8/2008
		for	($i	= 0	; $i < $qtde_item ;	$i++) {
			$produto2[$i]		 = $_POST["produto_"		. $i];
			$posicao[$i]		= $_POST["posicao_"		   . $i];
			$peca[$i]			= $_POST["peca_"		   . $i];
			$descricao[$i]		= $_POST["descricao_"	   . $i];
			$defeito[$i]		= $_POST["defeito_"		   . $i];
			$servico[$i]		= $_POST["servico_"		   . $i];
			$os_item[$i]		= ($_POST["os_item_".$i]) ? $_POST["os_item_".$i] : '';
			$os_produto[$i]		= $_POST["os_produto_"	   . $i];
			@$produto[$i]		 = $_POST["produto_"		. $i];
			$serie[$i]			= $_POST["serie_"		   . $i];
			$qtde[$i]			= $_POST["qtde_"		   . $i];
			$pcausa_defeito[$i]	= $_POST["pcausa_defeito_" . $i];
			$adicional[$i]		= $_POST["adicional_peca_estoque_"		  .	$i];
			$admin_peca[$i]		= $_POST["admin_peca_"	   . $i];
		}
	}
}

if (temNF($os, 'bool')) { ?>
	<br>
	<div style='text-align: center; margin: 10px auto; width: 800px;'>
		<?=temNF($os) . $include_imgZoom?>
	</div>
<?}

//--===== Lançamento das Peças da OS ====================================================================
echo "<input type='hidden' name='qtde_item'	value='$qtde_item'>";
echo "<input type='hidden' name='os'		value='$os'>";
echo "<input type='hidden' name='voltagem'	value='$produto_voltagem'>";
echo "<table style=' border:#76D176	1px	solid; background-color: #EFFAEF' align='center' width='750' border='0'>";
echo "<tr height='20' bgcolor='#76D176'>";
if ($os_item_subconjunto ==	't') {
	echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#ffffff'><b>";
	fecho("subconjunto",$con,$cook_idioma);
	echo "</b></font></td>";
}
if ($os_item_serie == 't' AND $os_item_subconjunto == 't') {
	echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#ffffff'><b>";
	fecho("n.serie",$con,$cook_idioma);
	echo "</b></font></td>";
}
echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#ffffff'><b>";
fecho("posicao",$con,$cook_idioma);
echo "</b></font></td>";
echo "<td align='center' class='Titulo'><b>";
fecho("codigo",$con,$cook_idioma);
echo "</b>&nbsp;&nbsp;&nbsp;</td>";
echo "<td align='center' class='Titulo'><b>";
fecho("descricao",$con,$cook_idioma);
echo "<td align='center' class='Titulo'><div id='lista_basica' style='display:inline;'>";
echo (strlen($produto_os)>0) ? "<acronym title=\"Clique	para abrir a lista básica do produto.\"><a class='lnk' href='peca_consulta_por_produto_subconjunto.php?produto=$produto_os'	target='_blank'>Lista Básica</a></acronym>"	: "";
echo "</div></td>";
if ($pergunta_qtde_os_item == 't') {
	echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#ffffff'><b>Qtde</b></font></td>";
}
if ($pedir_causa_defeito_os_item ==	't') {
	echo "<td align='center'><font size='1'	face='Geneva, Arial, Helvetica,	san-serif' color='#ffffff'><b>Causa</b></font></td>";
}
echo "<td align='center' class='Titulo'><b>";
fecho("defeito",$con,$cook_idioma);
echo "</b></td>";
echo "<td align='center' class='Titulo'><b>";
fecho("servico",$con,$cook_idioma);
echo "</b></td>";
echo "<td align='center' class='Titulo'><b>Não Receber Peça</td>";
echo "</tr>";
echo "<input type='hidden' name='preco'>";
echo "<input type='hidden' name='voltagem'>";

$loop =	10;

$offset	= 0;
for	($i	= 0	; $i < $loop ; $i++) {
	$xproduto = $produto[$i];

	echo "<tr>";
	echo "<input type='hidden' name='admin_peca_$i'	value='$admin_peca[$i]'>";
	echo "<input type='hidden' name='os_produto_$i'	value='$os_produto[$i]'>\n";
	echo "<input type='hidden' name='os_item_$i'	value='$os_item[$i]'>\n";
	echo "<input type='hidden' name='preco_$i'>";
	echo "<td align='center' nowrap>";
	if(strlen($os)==0){
		echo "<select name='produto_$i'	id='produto_$i'	class='frm classeItens'	style='width:220px;' onFocus='listaSubconjunto(this,document.frm_os.produto_referencia.value);'	onChange='javascript: document.frm_os.posicao_$i.focus(); fnc_pesquisa_peca_lista_sub (document.frm_os.produto_$i.value, $i, document.frm_os.produto_referencia.value)'>";
		echo "<option  selected></option>";
		if(strlen($produto2[$i])>0){
			$sql = "SELECT	tbl_produto.produto	  ,
							tbl_produto.referencia,
							tbl_produto.descricao
					FROM	tbl_produto
					WHERE	tbl_produto.referencia = '$produto2[$i]'";
			$resX =	pg_query ($con,$sql) ;
			if(pg_num_rows($resX) >0){
				$sub_referencia	= trim (pg_fetch_result	($resX,0,referencia));
				$sub_descricao	= trim (pg_fetch_result	($resX,0,descricao));
				echo "<option value='$produto2[$i]'	selected>";
				echo $sub_referencia . " - " . substr($sub_descricao,0,25) ;
				echo "</option>";
			}
		}
		echo "</select>";
	}else{
		echo "<select class='frm classeItens' size='1' name='produto_$i' onChange='javascript: document.frm_os.posicao_$i.focus(); fnc_pesquisa_peca_lista_sub (document.frm_os.produto_$i.value, $i, document.frm_os.produto_referencia.value)'>";
		echo "<option selected></option>";
		$sql = "SELECT	tbl_produto.produto	  ,
						tbl_produto.referencia,
						tbl_produto.descricao
				FROM	tbl_subproduto
				JOIN	tbl_produto	ON tbl_subproduto.produto_filho	= tbl_produto.produto
				WHERE	tbl_subproduto.produto_pai = $produto_os
				ORDER BY tbl_produto.referencia;";
		$resX =	pg_query ($con,$sql) ;

		echo "<option value='$produto_referencia' ";
		if ($produto[$i] ==	$produto_referencia) echo "	selected ";
		echo " >$produto_descricao</option>";

		for	($x	= 0	; $x < pg_num_rows ($resX) ; $x++ )	{
			$sub_produto	= trim (pg_fetch_result	($resX,$x,produto));
			$sub_referencia	= trim (pg_fetch_result	($resX,$x,referencia));
			$sub_descricao	= trim (pg_fetch_result	($resX,$x,descricao));
			if (substr ($sub_referencia,0,3) ==	"499" ){
				$sql = "SELECT	tbl_produto.produto	  ,
								tbl_produto.referencia,
								tbl_produto.descricao
						FROM	tbl_subproduto
						JOIN	tbl_produto	ON tbl_subproduto.produto_filho	= tbl_produto.produto
						WHERE	tbl_subproduto.produto_pai = $sub_produto
						ORDER BY tbl_produto.referencia;";
				$resY =	pg_query ($con,$sql) ;
				echo "<optgroup	label='" . $sub_referencia . " - " . substr($sub_descricao,0,25) . "'>"	;
				for	($y	= 0	; $y < pg_num_rows ($resY) ; $y++ )	{
					$sub_produto	= trim (pg_fetch_result	($resY,$y,produto));
					$sub_referencia	= trim (pg_fetch_result	($resY,$y,referencia));
					$sub_descricao	= trim (pg_fetch_result	($resY,$y,descricao));

					echo "<option ";
					if (trim ($produto2[$i]) ==	$sub_referencia) echo "	selected ";
					echo " value='"	. $sub_referencia .	"'>" ;
					echo $sub_referencia . " - " . substr($sub_descricao,0,25) ;
					echo "</option>";
				}
				echo "</optgroup>";
			}else{
				echo "<option ";
				if (trim ($produto2[$i]) ==	$sub_referencia) echo "	selected ";
				echo " value='"	. $sub_referencia .	"'>" ;
				echo $sub_referencia . " - " . substr($sub_descricao,0,25) ;
				echo "</option>";
			}
		}
		echo "</select>";
	}
		echo " <img	src='imagens/btn_lupa.gif' border='0' align='absmiddle'	onclick='javascript: fnc_pesquisa_peca_lista_sub (document.frm_os.produto_$i.value, $i, document.frm_os.produto_referencia.value)' alt='Clique	para abrir a lista básica do produto selecionado' style='cursor:pointer;'>";
		echo "</td>\n";

	if ($os_item_subconjunto ==	'f') {
		$xproduto =	$produto[$i];
		echo "<input type='hidden' name='serie_$i'>\n";
	}else{
		if ($os_item_serie == 't') {
			echo "<td align='center'><input	class='frm classeItens'	type='text'	name='serie_$' size='9'	value='$serie[$i]' maxlength='20'></td>\n";
		}
	}
	echo "<td align='center' nowrap><input class='classeItens' type='text' name='posicao_$i' size='6' maxlength='6'	value='$posicao[$i]'>";
	echo "<img src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle'	onclick='javascript: fnc_pesquisa_peca_lista_intel (document.frm_os.produto_$i.value , \"\" , \"\" ,	document.frm_os.posicao_$i.value , \"posicao\", $i)' alt='Clique para	efetuar	a pesquisa'	style='cursor:pointer;'>";
	echo "</td>\n";
	echo "<td align='center' nowrap><input class='frm classeItens' type='text' name='peca_$i' size='15'	value='$peca[$i]'>&nbsp;";
	echo "<img src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle'	onclick='javascript: fnc_pesquisa_peca_lista_intel (document.frm_os.produto_$i.value , document.frm_os.peca_$i.value , \"\" ,	\"\" , \"referencia\", $i)' alt='Clique para efetuar a pesquisa' style='cursor:pointer;'>";
	echo "</td>";

	echo "<td align='center' nowrap><input class='frm classeItens' type='text' name='descricao_$i' size='25' value='$descricao[$i]'>&nbsp;";
	echo "<img src='imagens/btn_lupa_novo.gif' border='0' align='absmiddle'	onclick='javascript: fnc_pesquisa_peca_lista_intel (document.frm_os.produto_$i.value , \"\" , document.frm_os.descricao_$i.value ,	\"\" , \"descricao\", $i)' alt='Clique para efetuar a	pesquisa' style='cursor:pointer;'>";
	echo "</td>";

	
	echo "<td width='60' align='center'><img src='imagens/btn_lista.gif' border='0' align='absmiddle' onclick=\"fnc_pesquisa_lista_basica ($('#produto_referencia').val(),'lista_basica',$i)\" alt='LISTA BÁSICA' style='cursor:pointer;'></TD>";

	//--===== Defeito do Item ========================================================================
	echo "<td align='center'>";
	echo "<select class='frm classeItens' size='1' name='defeito_$i'>";
	echo "<option selected></option>";

	$sql = "SELECT *
			FROM   tbl_defeito
			WHERE  tbl_defeito.fabrica = $login_fabrica
			AND	   tbl_defeito.ativo IS	TRUE
			ORDER BY descricao;";
	$res = pg_query	($con,$sql)	;

	for	($x	= 0	; $x < pg_num_rows ($res) ;	$x++ ) {
		echo "<option ";
		if ($defeito[$i] ==	pg_fetch_result	($res,$x,defeito)) echo	" selected ";
		echo " value='"	. pg_fetch_result($res,$x,defeito)	. "'>" ;
		if (strlen(trim(pg_fetch_result($res,$x,codigo_defeito))) >	0) {
			echo pg_fetch_result($res,$x,codigo_defeito);
			echo " - " ;
		}
		echo pg_fetch_result($res,$x,descricao);
		echo "</option>";
	}

	echo "</select>";
	echo "</td>";
	//--===== FIM -	Defeito	da Peça	===================================================================


	//--===== Serviço Realizado	=======================================================================
	echo "<td align='center'>";
	echo "<select class='frm classeItens' size='1' name='servico_$i' id='servico_$i' style='width:150px' onchange='verificaPedido($(this).val(),$i);'>";
	echo "<option selected></option>";

	$sql = "SELECT *
		FROM   tbl_servico_realizado
		WHERE  tbl_servico_realizado.fabrica = $login_fabrica
		AND	tbl_servico_realizado.linha	IS NULL
		AND	tbl_servico_realizado.ativo	  IS TRUE
		ORDER BY  descricao	ASC;";

	$res = pg_query($con,$sql) ;

	if (pg_num_rows($res) == 0)	{
		$sql = "SELECT *
			FROM   tbl_servico_realizado
			WHERE  tbl_servico_realizado.fabrica = $login_fabrica
			AND	tbl_servico_realizado.linha	IS NULL

			AND	tbl_servico_realizado.ativo	IS TRUE
			ORDER BY descricao ASC;";
		$res = pg_query($con,$sql) ;
	}

	for	($x	= 0	; $x < pg_num_rows($res) ; $x++	) {
		$gera_pedido = pg_fetch_result($res,$x,gera_pedido);
		echo "<option ";
		if ($servico[$i] ==	pg_fetch_result	($res,$x,servico_realizado)) echo "	selected ";
		echo " id='" . pg_fetch_result($res,$x,servico_realizado).	"' ";
		echo " value='"	. pg_fetch_result($res,$x,servico_realizado) .	"'>" ;
		echo pg_fetch_result($res,$x,descricao) ;
		echo "</option>";
	}

	echo "</select>";
	echo "</td>";
	//--===== FIM -	Serviço	Realizado ===================================================================
	echo "<td align='center'> <input type='checkbox' value='t' name='nao_pedido_$i' id='nao_pedido_$i' class='frm classeItens' onchange='verificaPedido2(this,$i);'>";
	echo "</td>";


	echo "</tr>";

	$offset	= $offset +	1;
}
echo "</table>";
//--===== FIM -	Lançamento de Peças	=====================================================================

?>
	</td>
</tr>
<tr>
	<td	height="27"	valign="middle"	align="center" colspan="3" bgcolor="#FFFFFF">
		<br />
		<FONT SIZE="1"><? fecho("observacao",$con,$cook_idioma); ?>:</FONT>	<INPUT TYPE="text" NAME="obs" value="<?	echo $obs; ?>" size="70" maxlength="255" class="frm">
		<br /><br />
		<FONT SIZE="1" COLOR="#ff0000"><? fecho	("o.campo.observacao.e.somente.para.o.controle.do.posto.autorizado", $con, $cook_idioma);
		echo "<br/>";
		fecho ("o.fabricante.nao.se.responsabilizara.pelos.dados.aqui.digitados",$con,$cook_idioma);?></FONT>
		<br /><br />
	</td>
</tr>
<tr><td><img height="2"	width="16" src="imagens/spacer.gif"></td></tr>
<tr>
	<td	valign="top" align="left">
<?

//--===== Data Fechamento da OS	=========================================================================
echo "<table style=' border:#B63434	1px	solid; background-color: #cfc0c0' align='center' width='400' border='0'height='40'>";
echo "<tr>";

echo "<td valign='middle' align='left' class='Label'>";
echo "Data Conserto";
echo ":";
echo "<INPUT TYPE='text' NAME='data_conserto' value='$data_conserto' size='12' maxlength='10' class='frm' rel='data_conserto'> </td>";
echo "<td valign='middle' align='center'>";
// HD 35365
echo "<td valign='middle' align='left' class='Label'>";
fecho("data.fechamento",$con,$cook_idioma);
echo ":";
echo "<INPUT TYPE='text' NAME='data_fechamento'	value='$data_fechamento' size='12' maxlength='10' class='frm' rel='data_fechamento'> </td>";
echo "<td valign='middle' align='center'>";
echo "<input type='hidden' name='btn_acao' value=''>";
echo "<input type='button' name='btn' value='";
fecho("gravar",$con,$cook_idioma);
echo "'	id='fechar'	onClick=\"
	if (document.frm_os.btn_acao.value != ''){
		alert('Aguarde');
	}else{";
echo "document.frm_os.btn_acao.value='gravar';
		document.frm_os.submit();";
echo "}\">";
echo "</td>";
echo "</tr>";
echo "</table>";
//--=====================================================================================================

?>
	</td>
</tr>
</table>
</form>
<div id='erro' style='visibility:hidden;opacity:.85;' class='Erro'></div>

<p>
<p>
</table></table>
<? include "rodape.php";?>
