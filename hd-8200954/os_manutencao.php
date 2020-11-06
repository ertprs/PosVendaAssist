<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

include 'autentica_usuario.php';
include 'funcoes.php';

$msg_erro  = "";
$qtde_item = 150;
$qtde_item_visiveis = 30;

$btn_acao = trim (strtolower ($_POST['btn_acao']));

if($_GET["ajax"]=="true" AND $_GET["buscaValores"]=="true"){
	$referencia   = trim($_GET["produto_referencia"]);
	$cliente_cnpj = trim($_GET["cliente_cnpj"]);

	$listaReferencias = implode("','",explode(",",$referencia));
	$retorno = 0;
	$desconto_peca = "0";

	if (strlen($cliente_cnpj) > 0) {
		$sql = "SELECT  tbl_posto_consumidor.contrato,
						tbl_posto_consumidor.desconto_peca
				FROM   tbl_posto_consumidor
				JOIN   tbl_posto ON tbl_posto.posto = tbl_posto_consumidor.posto AND tbl_posto_consumidor.fabrica = $login_fabrica
				WHERE  tbl_posto.cnpj = '$cliente_cnpj' ";
		$res2 = pg_exec ($con,$sql);
		if (pg_numrows ($res2) > 0 ) {
			$contrato      = trim(pg_result($res2,0,contrato));
			$desconto_peca = trim(pg_result($res2,0,desconto_peca));

			if ($contrato != 't'){
				$desconto_peca = "0";
			}
		}
	}

	if(strlen($listaReferencias)>0){
		$sql = "SELECT  tbl_familia_valores.taxa_visita,
						tbl_familia_valores.hora_tecnica,
						tbl_familia_valores.valor_diaria,
						tbl_familia_valores.valor_por_km_caminhao,
						tbl_familia_valores.valor_por_km_carro,
						tbl_familia_valores.regulagem_peso_padrao,
						tbl_familia_valores.certificado_conformidade
				FROM    tbl_familia
				JOIN    tbl_familia_valores USING(familia)
				JOIN    tbl_produto         USING(familia)
				WHERE   tbl_familia.fabrica = $login_fabrica
				AND     tbl_produto.referencia IN ('$listaReferencias')
				ORDER BY tbl_familia_valores.hora_tecnica DESC
				LIMIT 1";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) {
			$taxa_visita              = number_format(trim(pg_result($res,0,taxa_visita)),2,',','.');
			$hora_tecnica             = number_format(trim(pg_result($res,0,hora_tecnica)),2,',','.');
			$valor_diaria             = number_format(trim(pg_result($res,0,valor_diaria)),2,',','.');
			$valor_por_km_caminhao    = number_format(trim(pg_result($res,0,valor_por_km_caminhao)),2,',','.');
			$valor_por_km_carro       = number_format(trim(pg_result($res,0,valor_por_km_carro)),2,',','.');
			$regulagem_peso_padrao    = number_format(trim(pg_result($res,0,regulagem_peso_padrao)),2,',','.');
			$certificado_conformidade = number_format(trim(pg_result($res,0,certificado_conformidade)),2,',','.');
			$retorno++;
		}
		$sql = "SELECT  tbl_familia_valores.taxa_visita
				FROM    tbl_familia
				JOIN    tbl_familia_valores USING(familia)
				JOIN    tbl_produto         USING(familia)
				WHERE   tbl_familia.fabrica = $login_fabrica
				AND     tbl_produto.referencia IN ('$listaReferencias')
				AND     tbl_familia_valores.taxa_visita IS NOT NULL
				ORDER BY tbl_familia_valores.taxa_visita DESC
				LIMIT 1";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) {
			$taxa_visita              = number_format(trim(pg_result($res,0,taxa_visita)),2,',','.');
			$retorno++;
		}
		$sql = "SELECT  tbl_familia_valores.regulagem_peso_padrao
				FROM    tbl_familia
				JOIN    tbl_familia_valores USING(familia)
				JOIN    tbl_produto         USING(familia)
				WHERE   tbl_familia.fabrica = $login_fabrica
				AND     tbl_produto.referencia IN ('$listaReferencias')
				AND     tbl_familia_valores.regulagem_peso_padrao IS NOT NULL
				ORDER BY tbl_familia_valores.regulagem_peso_padrao DESC
				LIMIT 1";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) {
			$regulagem_peso_padrao = number_format(trim(pg_result($res,0,regulagem_peso_padrao)),2,',','.');
			$retorno++;
		}
		$sql = "SELECT  tbl_familia_valores.valor_diaria
				FROM    tbl_familia
				JOIN    tbl_familia_valores USING(familia)
				JOIN    tbl_produto         USING(familia)
				WHERE   tbl_familia.fabrica = $login_fabrica
				AND     tbl_produto.referencia IN ('$listaReferencias')
				AND     tbl_familia_valores.valor_diaria IS NOT NULL
				ORDER BY tbl_familia_valores.valor_diaria DESC
				LIMIT 1";
		$res = pg_exec ($con,$sql);
		if (pg_numrows($res) > 0) {
			$valor_diaria = number_format(trim(pg_result($res,0,valor_diaria)),2,',','.');
			$retorno++;
		}

		if ($retorno>0){
			echo "ok|$taxa_visita|$hora_tecnica|$valor_diaria|$valor_por_km_carro|$valor_por_km_caminhao|$regulagem_peso_padrao|$certificado_conformidade|$desconto_peca";
			exit;
		}
	}
	echo "nao|nao";
	exit;
}

if (strlen($_GET['os_manutencao'])  > 0) $os_manutencao = trim($_GET['os_manutencao']);
if (strlen($_POST['os_manutencao']) > 0) $os_manutencao = trim($_POST['os_manutencao']);

if ($btn_acao == "gravar") {

	$consumidor_revenda = $_POST['consumidor_revenda'];

	if (strlen($consumidor_revenda)==0){
		$msg_erro = "Selecione Consumidor ou Revenda.";
	}

	$xconsumidor_revenda = "'".$consumidor_revenda."'";


	$xdata_abertura = fnc_formata_data_pg(trim($_POST['data_abertura']));
	if ($xdata_abertura == 'null'){
		$msg_erro .= " Digite a data de abertura da OS.";
	}

	$hora_abertura = trim($_POST['hora_abertura']);
	if (strlen($hora_abertura)==0){
		$msg_erro .= " Digite a hora de abertura da OS.";
	}

	if (strlen($msg_erro)==0){
		if (strlen($hora_abertura) > 0){
			$xhora_abertura = "'".$hora_abertura."'";
		}else{
			$xhora_abertura = " NULL ";
		}
	}

	if (strlen($_POST['cliente_cnpj']) > 0) {
		$cliente_cnpj  = trim($_POST['cliente_cnpj']);
		$cliente_cnpj  = str_replace (".","",$cliente_cnpj);
		$cliente_cnpj  = str_replace ("-","",$cliente_cnpj);
		$cliente_cnpj  = str_replace ("/","",$cliente_cnpj);
		$cliente_cnpj  = str_replace (" ","",$cliente_cnpj);
		$cliente_cnpj  = substr($cliente_cnpj,0,14);
		$xcliente_cnpj = "'". $cliente_cnpj ."'";
		 // HD 46309

		$valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$cliente_cnpj));

		if(empty($valida_cpf_cnpj)){
			$sql = "SELECT fn_valida_cnpj_cpf('$cliente_cnpj')";
			$res = @pg_exec($con,$sql);
			$cpf_erro= pg_errormessage($con);
			if(strlen($cpf_erro) > 0){
				$msg_erro="CPF/CNPJ do cliente inválido";
			}
		}else{
			$msg_erro = $valida_cpf_cnpj;
		}

	}else{
		$xcliente_cnpj = "null";
	}

	$cliente_nome  = $_POST['cliente_nome'];
	if (strlen($cliente_nome) > 0) {
		$xcliente_nome = "'". $cliente_nome ."'";
	}else{
		$xcliente_nome = "null";
	}

	$cliente_rg  = $_POST['cliente_rg'];
	if (strlen($cliente_rg) > 0) {
		$xcliente_rg = "'". $cliente_rg ."'";
	}else{
		$xcliente_rg = "null";
	}

	$cliente_fone  = $_POST['cliente_fone'];
	if (strlen($cliente_fone) > 0) {
		$xcliente_fone = "'". $cliente_fone ."'";
	}else{
		$xcliente_fone = "null";
	}

	$cliente_endereco  = $_POST['cliente_endereco'];
	if (strlen($cliente_endereco) > 0) {
		$xcliente_endereco = "'". $cliente_endereco ."'";
	}else{
		$xcliente_endereco = "null";
	}

	$cliente_numero  = $_POST['cliente_numero'];
	if (strlen($cliente_numero) > 0) {
		$xcliente_numero = "'". $cliente_numero ."'";
	}else{
		$xcliente_numero = "null";
	}

	$cliente_complemento  = $_POST['cliente_complemento'];
	if (strlen($cliente_complemento) > 0) {
		$xcliente_complemento = "'". $cliente_complemento ."'";
	}else{
		$xcliente_complemento = "null";
	}

	$cliente_cep  = $_POST['cliente_cep'];
	if (strlen($cliente_cep) > 0) {
		$xcliente_cep  = str_replace (".","",$cliente_cep);
		$xcliente_cep  = str_replace ("-","",$xcliente_cep);
		$xcliente_cep  = str_replace ("/","",$xcliente_cep);
		$xcliente_cep  = str_replace (" ","",$xcliente_cep);
		$xcliente_cep = "'". $xcliente_cep ."'";
	}else{
		$xcliente_cep = "null";
	}

	$cliente_bairro  = $_POST['cliente_bairro'];
	if (strlen($cliente_bairro) > 0) {
		$xcliente_bairro = "'". $cliente_bairro ."'";
	}else{
		$xcliente_bairro = "null";
	}

	$cliente_cidade  = $_POST['cliente_cidade'];
	if (strlen($cliente_cidade) > 0) {
		$xcliente_cidade = "'". $cliente_cidade ."'";
	}else{
		$xcliente_cidade = "null";
	}

	$cliente_estado  = $_POST['cliente_estado'];
	if (strlen($cliente_estado) > 0) {
		$xcliente_estado = "'". $cliente_estado ."'";
	}else{
		$xcliente_estado = "null";
	}

	if ($consumidor_revenda == 'R'){
		$xcliente_cnpj = "null";
		$xcliente = "null";
	}

	if ($xcliente_cnpj <> "null" AND $consumidor_revenda == 'C') {

		$sql = "SELECT	tbl_cliente.cliente,
						tbl_cliente.nome,
						tbl_cliente.fone,
						tbl_cliente.cpf,
						tbl_cidade.nome AS cidade,
						tbl_cidade.estado
				FROM tbl_cliente
				LEFT JOIN tbl_cidade
				USING (cidade)
				WHERE tbl_cliente.cpf = $xcliente_cnpj";
		$res = pg_exec ($con,$sql);

		if (pg_numrows ($res) == 0){

			$sql = "SELECT fnc_qual_cidade ($xcliente_cidade,$xcliente_estado)";
			$res = pg_exec ($con,$sql);
			$xcliente_cidade = pg_result($res,0,0) ;

			$sql = "INSERT INTO tbl_cliente
						(nome,cpf,endereco,numero,complemento,bairro,cep,cidade,fone,rg)
					VALUES
						($xcliente_nome, $xcliente_cnpj, $xcliente_endereco, $xcliente_numero, $xcliente_complemento, $xcliente_bairro, $xcliente_cep, $xcliente_cidade, $xcliente_fone, $xcliente_rg) ";
			$res = pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if (strlen($msg_erro) == 0) {
				$res           = pg_exec ($con,"SELECT CURRVAL ('seq_cliente')");
				$xcliente      = pg_result ($res,0,0);
				$msg_erro      = pg_errormessage($con);
			}
		}else{
			$cliente		   = trim(pg_result($res,0,cliente));
			$consumidor_nome   = trim(pg_result($res,0,nome));
			$consumidor_cidade = trim(pg_result($res,0,cidade));
			$consumidor_estado = trim(pg_result($res,0,estado));
			$consumidor_fone   = trim(pg_result($res,0,fone));
			$consumidor_cpf    = trim(pg_result($res,0,cpf));

			if (strlen($cliente) > 0) {
				$xcliente = $cliente ;
			}else{
				$xcliente = "null";
			}
		}
	}else{
		if ($consumidor_revenda == 'C'){
			$msg_erro = "CNPJ do cliente não informado";
		}
	}

	$revenda_cnpj = trim($_POST['revenda_cnpj']);
	$revenda_cnpj = str_replace("-","",$revenda_cnpj);
	$revenda_cnpj = str_replace(".","",$revenda_cnpj);
	$revenda_cnpj = str_replace(" ","",$revenda_cnpj);
	$revenda_cnpj = str_replace("/","",$revenda_cnpj);
	$revenda_cnpj = substr($revenda_cnpj,0,14);

	if (strlen($revenda_cnpj) <> 0 AND strlen($revenda_cnpj) <> 14) {
		$msg_erro .= " Tamanho do CNPJ da revenda inválido.<BR>";
	}

	if (strlen($revenda_cnpj) == 0) {
		$xrevenda_cnpj = 'null';
	}else{
		$xrevenda_cnpj = "'".$revenda_cnpj."'";
		// HD 46309

		$valida_cpf_cnpj = verificaCpfCnpj(preg_replace("/\D/","",$revenda_cnpj));

		if(empty($valida_cpf_cnpj)){
			$sql = "SELECT fn_valida_cnpj_cpf('$revenda_cnpj')";
			$res = @pg_exec($con,$sql);
			$cnpj_erro = pg_errormessage($con);
			if(strlen($cnpj_erro) > 0){
				$msg_erro = "CNPJ da Revenda inválido";
			}
		}else{
			$msg_erro = $valida_cpf_cnpj;
		}
	}

	if (strlen(trim($_POST['revenda_nome'])) == 0){
		$xrevenda_nome = "NULL";
	}else{
		$xrevenda_nome = "'".str_replace("'","",trim($_POST['revenda_nome']))."'";
	}

	if (strlen(trim($_POST['revenda_fone'])) == 0) {
		$xrevenda_fone = 'null';
	}else {
		$xrevenda_fone = "'".str_replace("'","",trim($_POST['revenda_fone']))."'";
	}

	$xrevenda_cep = trim ($_POST['revenda_cep']) ;
	$xrevenda_cep = str_replace (".","",$xrevenda_cep);
	$xrevenda_cep = str_replace ("-","",$xrevenda_cep);
	$xrevenda_cep = str_replace ("/","",$xrevenda_cep);
	$xrevenda_cep = str_replace (",","",$xrevenda_cep);
	$xrevenda_cep = str_replace (" ","",$xrevenda_cep);
	$xrevenda_cep = substr ($xrevenda_cep,0,8);

	if (strlen($xrevenda_cep) == 0) {
		$xrevenda_cep = "null";
	}else {
		$xrevenda_cep = "'" . $xrevenda_cep . "'";
	}

	if (strlen(trim($_POST['revenda_email'])) == 0) {
		$xrevenda_email = 'null';
	}else {
		$xrevenda_email = "'".str_replace("'","",trim($_POST['revenda_email']))."'";
	}

	if (strlen(trim($_POST['revenda_endereco'])) == 0) {
		$xrevenda_endereco = 'null';
	}else {
		$xrevenda_endereco = "'".str_replace("'","",trim($_POST['revenda_endereco']))."'";
	}

	if (strlen(trim($_POST['revenda_numero'])) == 0) {
		$xrevenda_numero = 'null';
	}else {
		$xrevenda_numero = "'".str_replace("'","",trim($_POST['revenda_numero']))."'";
	}

	if (strlen(trim($_POST['revenda_complemento'])) == 0) {
		$xrevenda_complemento = 'null';
	}else {
		$xrevenda_complemento = "'".str_replace("'","",trim($_POST['revenda_complemento']))."'";
	}

	if (strlen(trim($_POST['revenda_bairro'])) == 0) {
		$xrevenda_bairro = 'null';
	}else {
		$xrevenda_bairro = "'".str_replace("'","",trim($_POST['revenda_bairro']))."'";
	}

	if (strlen(trim($_POST['revenda_cidade'])) == 0) {
		$xrevenda_cidade='null';
	}else{
		$xrevenda_cidade = "'".str_replace("'","",trim($_POST['revenda_cidade']))."'";
	}

	if (strlen(trim($_POST['revenda_estado'])) == 0){
		$xrevenda_estado='null';
	}else{
		$xrevenda_estado = "'".str_replace("'","",trim($_POST['revenda_estado']))."'";
	}

	if (strlen($_POST['nota_fiscal']) > 0) {
		$xnota_fiscal = "'". $_POST['nota_fiscal'] ."'";
	}else{
		$xnota_fiscal = "null";
	}

	if (strlen($_POST['data_nf']) > 0) {
		$xdata_nf = fnc_formata_data_pg(trim($_POST['data_nf']));
	}else{
		$xdata_nf = "null";
	}

	if ($login_fabrica==7) { // HD 75762 para Filizola
		$classificacao_os = trim($_POST['classificacao_os']);
		if(strlen($classificacao_os) == 0){
			$msg_erro .= " Escolha a classificação da OS. ";
		}
	}else{
		$classificacao_os = 'null';
	}

	if (strlen($_POST['tipo_atendimento']) > 0) {
		$tipo_atendimento = $_POST['tipo_atendimento'];
	}else{
		$msg_erro .= "Digite a natureza da OS.";
	}

	if (strlen($_POST['obs']) > 0) {
		$xobs = "'". $_POST['obs'] ."'";
	}else{
		$xobs = "null";
	}

	if (strlen($_POST['qtde_km']) > 0) {
		$xqtde_km = $_POST['qtde_km'] ;
	}else{
		$xqtde_km = "null";
	}

	$xdeslocamento_km = $xqtde_km;

	if (strlen($_POST['contrato']) > 0) {
		$xcontrato = "'". $_POST['contrato'] ."'";
	}else{
		$xcontrato = "'f'";
	}

	if (strlen($_POST['quem_abriu_chamado']) > 0) {
		$xquem_abriu_chamado = "'".$_POST['quem_abriu_chamado']."'" ;
	}else{
		//$xquem_abriu_chamado = 'null'; HD 26106
		$msg_erro .= "Digite quem abriu o Chamado.";
	}

	if (strlen($_POST['condicao']) > 0) {
		$xcondicao = $_POST['condicao'];
	}else{
		$xcondicao = " NULL ";
	}

	/* VALORES DA OS */
	$taxa_visita				= str_replace (",",".",trim ($_POST['taxa_visita']));
	$visita_por_km				= trim($_POST['visita_por_km']);
	$valor_por_km				= str_replace (",",".",trim ($_POST['valor_por_km']));
	$veiculo					= trim ($_POST['veiculo']);

	if ($veiculo == "caminhao"){
		$valor_por_km = str_replace (",",".",trim ($_POST['valor_por_km_caminhao']));
	}

	if ($veiculo == "carro"){
		$valor_por_km = str_replace (",",".",trim ($_POST['valor_por_km_carro']));
	}

	$hora_tecnica				= str_replace (".","",trim ($_POST['hora_tecnica']));
	$hora_tecnica				= str_replace (",",".",$hora_tecnica);

	$regulagem_peso_padrao		= str_replace (".","",trim ($_POST['regulagem_peso_padrao']));
	$regulagem_peso_padrao		= str_replace (",",".",$regulagem_peso_padrao);

	$valor_diaria				= str_replace (".","",trim ($_POST['valor_diaria']));
	$valor_diaria				= str_replace (",",".",$valor_diaria);

	$cobrar_deslocamento		= trim ($_POST['cobrar_deslocamento']);
	$cobrar_hora_diaria			= trim ($_POST['cobrar_hora_diaria']);
	$cobrar_regulagem			= trim ($_POST['cobrar_regulagem']);


	$desconto_deslocamento		= str_replace (",",".",trim ($_POST['desconto_deslocamento']));
	$desconto_hora_tecnica		= str_replace (",",".",trim ($_POST['desconto_hora_tecnica']));
	$desconto_diaria			= str_replace (",",".",trim ($_POST['desconto_diaria']));
	$desconto_peca				= str_replace (",",".",trim ($_POST['desconto_peca']));

	if (strlen($desconto_peca)>0 AND $desconto_peca>100){
		$desconto_peca = 100;
	}

	if ($login_tipo_posto == 214 or $login_tipo_posto == 215) {
		if ($desconto_deslocamento>7){
			$msg_erro .= "O desconto máximo permitido para deslocamento é 7%.<br>";
		}
		if ($desconto_hora_tecnica>7){
			$msg_erro .= "O desconto máximo permitido para hora técnica é 7%.<br>";
		}
		if ($desconto_diaria>7){
			$msg_erro .= "O desconto máximo permitido para diára é 7%.<br>";
		}
		if ($desconto_regulagem>7){
			$msg_erro .= "O desconto máximo permitido para regulagem é 7%.<br>";
		}
		if ($desconto_certificado>7){
			$msg_erro .= "O desconto máximo permitido para o certificado é 7%.<br>";
		}
	}

	if (strlen($valor_por_km)>0){
		$xvalor_por_km = $valor_por_km;
		$xvisita_por_km = "'t'";
	}else{
		$xvalor_por_km = "0";
		$xvisita_por_km = "'f'";
	}

	if (strlen($taxa_visita)>0){
		$xtaxa_visita = $taxa_visita;
	}else{
		$xtaxa_visita = '0';
	}

	if (strlen($veiculo)==0){
		$xveiculo = "NULL";
	}else{
		$xveiculo = "'$veiculo'";
	}

	if ($tipo_atendimento == 63){
		$cobrar_deslocamento = 'isento';
	}

	if ($cobrar_deslocamento == 'isento'){
		$xvisita_por_km = "'f'";
		$xvalor_por_km = "0";
		$xtaxa_visita = '0';
		$xveiculo = "NULL";
	}elseif ($cobrar_deslocamento == 'valor_por_km'){
		$xvisita_por_km = "'t'";
		$xtaxa_visita = '0';
	}elseif ($cobrar_deslocamento == 'taxa_visita'){
		$xvisita_por_km = "'f'";
		$xvalor_por_km = "0";
	}

	if(strlen($valor_diaria) > 0){
		$xvalor_diaria = $valor_diaria;
	}else{
		$xvalor_diaria = '0';
	}

	if(strlen($hora_tecnica) > 0){
		$xhora_tecnica = $hora_tecnica;
	}else{
		$xhora_tecnica = '0';
	}

	if ($cobrar_hora_diaria == 'isento'){
		$xhora_tecnica = '0';
		$xvalor_diaria = '0';
	}elseif ($cobrar_hora_diaria == 'diaria'){
		$xhora_tecnica = '0';
	}elseif ($cobrar_hora_diaria == 'hora'){
		$xvalor_diaria = '0';
	}

	if (strlen($regulagem_peso_padrao)==0){
		$xregulagem_peso_padrao = '0';
	}else{
		$xregulagem_peso_padrao = $regulagem_peso_padrao;
	}

	if ($cobrar_regulagem != 't'){
		$xregulagem_peso_padrao = "0";
	}

	/* Descontos */
	if(strlen($desconto_deslocamento) > 0){
		$desconto_deslocamento = $desconto_deslocamento;
	}else{
		$desconto_deslocamento = '0';
	}

	if(strlen($desconto_hora_tecnica) > 0){
		$desconto_hora_tecnica = $desconto_hora_tecnica;
	}else{
		$desconto_hora_tecnica = '0';
	}

	if(strlen($desconto_diaria) > 0){
		$desconto_diaria = $desconto_diaria;
	}else{
		$desconto_diaria = '0';
	}

	if(strlen($desconto_peca) > 0){
		$desconto_peca = $desconto_peca;
	}else{
		$desconto_peca = '0';
	}

	if (strlen ($msg_erro) == 0) {

		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen ($os_manutencao) == 0) {
			$sql = "INSERT INTO tbl_os_revenda (
						data_abertura      ,
						hora_abertura      ,
						fabrica            ,
						consumidor_revenda ,
						cliente            ,
						consumidor_nome    ,
						consumidor_cnpj    ,
						nota_fiscal        ,
						tipo_atendimento   ,
						data_nf            ,
						qtde_km            ,
						deslocamento_km    ,
						quem_abriu_chamado ,
						obs                ,
						posto              ,
						contrato           ,
						condicao           ,
						os_manutencao      ,
						classificacao_os   ,
						consumidor_cidade     ,
						consumidor_estado     ,
						consumidor_fone       ,
						consumidor_endereco   ,
						consumidor_numero     ,
						consumidor_cep        ,
						consumidor_complemento,
						consumidor_bairro      
					) VALUES (
						$xdata_abertura                   ,
						$xhora_abertura                   ,
						$login_fabrica                    ,
						$xconsumidor_revenda              ,
						$xcliente                         ,
						$xcliente_nome                    ,
						$xcliente_cnpj                    ,
						$xnota_fiscal                     ,
						$tipo_atendimento                 ,
						$xdata_nf                         ,
						$xqtde_km                         ,
						$xdeslocamento_km                 ,
						$xquem_abriu_chamado              ,
						$xobs                             ,
						$login_posto                      ,
						$xcontrato                        ,
						$xcondicao                        ,
						't'                               ,
						$classificacao_os                 ,
						'$cliente_cidade'                 ,
						'$cliente_estado'                 ,
						$xcliente_fone                    ,
						$xcliente_endereco                ,
						$xcliente_numero                  ,
						$xcliente_cep                     ,
						$xcliente_complemento             ,
						$xcliente_bairro
					)";
		}else{
			$sql = "UPDATE tbl_os_revenda SET
						data_abertura      = $xdata_abertura             ,
						hora_abertura      = $xhora_abertura             ,
						cliente            = $xcliente                   ,
						consumidor_nome    = $xcliente_nome              ,
						consumidor_cpf     = $xcliente_cnpj              ,
						nota_fiscal        = $xnota_fiscal               ,
						data_nf            = $xdata_nf                   ,
						tipo_atendimento   = $tipo_atendimento           ,
						qtde_km            = $xqtde_km                   ,
						deslocamento_km    = $xdeslocamento_km           ,
						quem_abriu_chamado = $xquem_abriu_chamado        ,
						obs                = $xobs                       ,
						contrato           = $xcontrato                  ,
						condicao           = $xcondicao                  ,
						os_manutencao      = 't'                         ,
						classificacao_os   = $classificacao_os           ,
						consumidor_cidade     ='$cliente_cidade'         ,
						consumidor_estado     ='$cliente_estado'         ,
						consumidor_fone       =$xcliente_fone            ,
						consumidor_endereco   =$xcliente_endereco        ,
						consumidor_numero     =$xcliente_numero          ,
						consumidor_cep        =$xcliente_cep             ,
						consumidor_complemento=$xcliente_complemento     ,
						consumidor_bairro     =$xcliente_bairro           
					WHERE os_revenda = $os_manutencao
					AND   fabrica    = $login_fabrica
					AND   posto      = $login_posto ";
		}

		$res = @pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);

		if (strlen($msg_erro) == 0 and strlen($os_manutencao) == 0) {
			$res           = pg_exec ($con,"SELECT CURRVAL ('seq_os_revenda')");
			$os_manutencao = pg_result ($res,0,0);
			$msg_erro      .= pg_errormessage($con);
		}

		$sql = "UPDATE tbl_os_revenda SET
					taxa_visita              = $xtaxa_visita            ,
					visita_por_km            = $xvisita_por_km          ,
					valor_por_km             = $xvalor_por_km           ,
					hora_tecnica             = $xhora_tecnica           ,
					valor_diaria             = $xvalor_diaria           ,
					regulagem_peso_padrao    = $xregulagem_peso_padrao,
					veiculo                  = $xveiculo                ,
					desconto_deslocamento    = $desconto_deslocamento   ,
					desconto_hora_tecnica    = $desconto_hora_tecnica   ,
					desconto_diaria          = $desconto_diaria         ,
					desconto_peca            = $desconto_peca
				WHERE fabrica    = $login_fabrica
				AND   posto      = $login_posto
				AND   os_revenda = $os_manutencao  ";
		$res = @pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);

		if (strlen($msg_erro) == 0) {
			$qtde_item = $_POST['qtde_item'];

			for ($i = 0 ; $i < $qtde_item ; $i++) {
				$novo                     = $_POST["novo_".$i];
				$os_revenda_item          = $_POST["os_revenda_item_".$i];
				$referencia               = $_POST["produto_referencia_".$i];
				$serie                    = $_POST["produto_serie_".$i];
				$capacidade               = $_POST["produto_capacidade_".$i];
				$cobrar_regulagem         = $_POST["cobrar_regulagem_".$i];
				$regulagem_peso_padrao    = $_POST["regulagem_peso_padrao_".$i];
				$cobrar_certificado       = $_POST["cobrar_certificado_".$i];
				$certificado_conformidade = $_POST["certificado_conformidade_".$i];
				$defeito_reclamado        = $_POST["defeito_reclamado_".$i];

				$certificado_conformidade	= str_replace(".","",trim ($certificado_conformidade));
				$certificado_conformidade	= str_replace(",",".",$certificado_conformidade);

				$regulagem_peso_padrao		= str_replace(".","",trim ($regulagem_peso_padrao));
				$regulagem_peso_padrao		= str_replace(",",".",$regulagem_peso_padrao);

				if ($cobrar_regulagem != 't'){
					$regulagem_peso_padrao = "0";
				}

				if ($cobrar_certificado != 't'){
					$certificado_conformidade = "0";
				}

				if (strlen($os_revenda_item) > 0 AND strlen ($referencia)== 0 AND $novo == 'f') {
					$sql = "DELETE FROM tbl_os_revenda_item
							WHERE  os_revenda      = $os_manutencao
							AND    os_revenda_item = $os_revenda_item";
					$res = @pg_exec($con,$sql);
					$msg_erro.= pg_errormessage($con);
				}

				if (strlen($msg_erro) > 0) {
					break;
				}

				if (strlen ($referencia) > 0) {
					$referencia = strtoupper ($referencia);
					$referencia = str_replace ("-","",$referencia);
					$referencia = str_replace (".","",$referencia);
					$referencia = str_replace ("/","",$referencia);
					$referencia = str_replace (" ","",$referencia);
					$referencia = "'". $referencia ."'";

					$sql = "SELECT tbl_produto.produto
							FROM tbl_produto
							JOIN tbl_linha USING(linha)
							WHERE tbl_linha.fabrica        = $login_fabrica
							AND UPPER(referencia_pesquisa) = $referencia";
					$res = pg_exec ($con,$sql);
					if (pg_numrows ($res) == 0) {
						$msg_erro = "Produto $referencia não cadastrado";
						$linha_erro = $i;
					}else{
						$produto   = pg_result ($res,0,produto);
					}

					if (strlen($regulagem_peso_padrao) == 0) {
						$regulagem_peso_padrao = '0';
					}

					if (strlen($certificado_conformidade) == 0) {
						$certificado_conformidade = '0';
					}

					if (strlen($capacidade) == 0) {
						$xcapacidade = ' NULL ';
					}else{
						$xcapacidade = "'".$capacidade."'";
					}

					if (strlen($defeito_reclamado) == 0) {
						$msg_erro .= "Defeito reclamado do produto é obrigatório.";
					}else{
						$xdefeito_reclamado = "'".$defeito_reclamado."'";
					}

					if (strlen ($msg_erro) > 0) {
						break;
					}
					if ($novo == 't'){
						$sql = "INSERT INTO tbl_os_revenda_item (
									os_revenda                ,
									produto                   ,
									serie                     ,
									capacidade                ,
									regulagem_peso_padrao     ,
									certificado_conformidade  ,
									defeito_reclamado_descricao,
									nota_fiscal               ,
									data_nf
								) VALUES (
									$os_manutencao            ,
									$produto                  ,
									'$serie'                  ,
									$xcapacidade              ,
									$regulagem_peso_padrao    ,
									$certificado_conformidade ,
									$xdefeito_reclamado       ,
									$xnota_fiscal             ,
									$xdata_nf
								)";
					}else{
						$sql = "UPDATE tbl_os_revenda_item SET
									produto                     = $produto                  ,
									serie                       = '$serie'                  ,
									capacidade                  = $xcapacidade              ,
									regulagem_peso_padrao       = $regulagem_peso_padrao    ,
									certificado_conformidade    = $certificado_conformidade ,
									defeito_reclamado_descricao = $xdefeito_reclamado       ,
									nota_fiscal                 = $xnota_fiscal             ,
									data_nf                     = $xdata_nf
								WHERE  os_revenda      = $os_manutencao
								AND    os_revenda_item = $os_revenda_item";
					}
					$res = @pg_exec ($con,$sql);
					$msg_erro .= pg_errormessage($con);
					if (strlen ($msg_erro) > 0) {
						break ;
					}
				}
			}
		}

		if (strlen($msg_erro) == 0 AND strlen ($revenda_cnpj) > 0 and strlen ($xrevenda_cidade) > 0 AND $xrevenda_cidade<>'null' and strlen ($xrevenda_estado) > 0 AND $xrevenda_estado<>'null') {
			$sql = "SELECT fnc_qual_cidade ($xrevenda_cidade,$xrevenda_estado)";
			$res = pg_exec ($con,$sql);
			$xrevenda_cidade = pg_result($res,0,0) ;

			$sql  = "SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj";
			$res1 = pg_exec ($con,$sql);

			if (pg_numrows($res1) > 0) {
				$revenda = pg_result ($res1,0,revenda);
				$sql = "UPDATE tbl_revenda SET
							nome		= $xrevenda_nome          ,
							cnpj		= $xrevenda_cnpj          ,
							fone		= $xrevenda_fone          ,
							endereco	= $xrevenda_endereco      ,
							numero		= $xrevenda_numero        ,
							complemento	= $xrevenda_complemento   ,
							bairro		= $xrevenda_bairro        ,
							cep			= $xrevenda_cep           ,
							cidade		= $xrevenda_cidade        ,
							email		= $xrevenda_email
						WHERE tbl_revenda.revenda = $revenda";
				$res3 = @pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage ($con);
			}else{
				$sql = "INSERT INTO tbl_revenda (
							nome,
							cnpj,
							fone,
							endereco,
							numero,
							complemento,
							bairro,
							cep,
							cidade,
							email
						) VALUES (
							$xrevenda_nome ,
							$xrevenda_cnpj ,
							$xrevenda_fone ,
							$xrevenda_endereco ,
							$xrevenda_numero ,
							$xrevenda_complemento ,
							$xrevenda_bairro ,
							$xrevenda_cep ,
							$xrevenda_cidade,
							$xrevenda_email
						)";
				$res3 = @pg_exec ($con,$sql);
				$msg_erro .= pg_errormessage ($con);
				$monta_sql .= "12: $sql<br>$msg_erro<br><br>";

				$sql = "SELECT currval ('seq_revenda')";
				$res3 = @pg_exec ($con,$sql);
				$revenda = @pg_result ($res3,0,0);
			}

			$sql = "UPDATE tbl_os_revenda SET revenda = $revenda WHERE os_revenda = $os_manutencao AND fabrica = $login_fabrica";
			$res = @pg_exec ($con,$sql);
			$monta_sql .= "13: $sql<br>$msg_erro<br><br>";
		}

		if(strlen($msg_erro) ==0){
			$sql = "UPDATE tbl_os_revenda SET
							sua_os = '$os_manutencao'
					WHERE tbl_os_revenda.os_revenda  = $os_manutencao
					AND   tbl_os_revenda.posto       = $login_posto
					AND   tbl_os_revenda.fabrica     = $login_fabrica ";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if(strlen($os_manutencao) > 0 AND strlen($msg_erro) ==0){
			$sql = "SELECT fn_valida_os_revenda ($os_manutencao,$login_posto,$login_fabrica)";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			header ("Location: os_manutencao_finalizada.php?os_manutencao=$os_manutencao");
			exit;
		}else{
			$os_manutencao = "";
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}


/* ====================  APAGAR  =================== */
if ($btn_acao == "apagar") {
	if(strlen($os_manutencao) > 0){
		$sql = "DELETE FROM tbl_os_revenda
				WHERE tbl_os_revenda.os_revenda = $os_manutencao
				AND   tbl_os_revenda.fabrica    = $login_fabrica";
		$res = pg_exec ($con,$sql);

		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);

		if (strlen ($msg_erro) == 0) {
			header("Location: $PHP_SELF");
			exit;
		}
	}
}


$title			= "Cadastro de Ordem de Serviço - Manutenção";
$layout_menu	= "callcenter";

include "cabecalho.php";
include "javascript_pesquisas.php";
#include "admin/javascript_calendario.php";

?>
<script language='javascript' src='../ajax.js'></script>
<script language='javascript' src='../ajax_cep.js'></script>
<script type="text/javascript" src="js/jquery-1.2.1.pack.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>


<script language="JavaScript">

	$(function(){
		$("input[@rel='data']").maskedinput("99/99/9999");
		$("input[@rel='fone']").maskedinput("(99) 9999-9999");
		$("input[@rel='cep']").maskedinput("99999-999");
		$("input[@rel='cnpj']").maskedinput("99.999.999/9999-99");
	});

	function verificaConsumidorRevenda(campo){
		if (campo.value == 'R'){
			$('#tbl_cliente').css('display','none');
		}else{
			$('#tbl_cliente').css('display','inline');
		}
	}

	function verificarContrato(campo){
		if (campo.checked){
			$('#numero_contrato').css('display','inline');
		}else{
			$('#numero_contrato').css('display','none');
		}
	}

	/* ============= Função PESQUISA DE CLIENTE POR NOME ====================
	Nome da Função : fnc_pesquisa_cliente_nome (nome, cpf)
	=================================================================*/
	function fnc_pesquisa_cliente (campo, tipo) {
		var url = "";
		if (tipo == "nome") {
			url = "pesquisa_consumidor.php?nome=" + campo.value + "&tipo=nome";
		}
		if (tipo == "cpf") {
			url = "pesquisa_consumidor.php?cpf=" + campo.value + "&tipo=cpf";
		}
		janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.cliente		= document.frm_os.cliente_cliente;
		janela.nome			= document.frm_os.cliente_nome;
		janela.cpf			= document.frm_os.cliente_cnpj;
		janela.rg			= document.frm_os.cliente_rg;
		janela.cidade		= document.frm_os.cliente_cidade;
		janela.estado		= document.frm_os.cliente_estado;
		janela.fone			= document.frm_os.cliente_fone;
		janela.endereco		= document.frm_os.cliente_endereco;
		janela.numero		= document.frm_os.cliente_numero;
		janela.complemento	= document.frm_os.cliente_complemento;
		janela.bairro		= document.frm_os.cliente_bairro;
		janela.cep			= document.frm_os.cliente_cep;
		janela.focus();
	}

	/* ============= Função PESQUISA DE PRODUTOS ====================
	Nome da Função : fnc_pesquisa_produto (codigo,descricao)
			Abre janela com resultado da pesquisa de Produtos pela
			referência (código) ou descrição (mesmo parcial).
	=================================================================*/

	function fnc_pesquisa_produto (campo, campo2, tipo, campo3, campo4) {
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
			janela.capacidade	= campo4;
			janela.focus();
		}
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

	//INICIO DA FUNCAO DATA
	function date_onkeydown() {
		if (window.event.srcElement.readOnly) return;
		var key_code = window.event.keyCode;
		var oElement = window.event.srcElement;
		if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
			var d = new Date();
			oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" +
							 String(d.getDate()).padL(2, "0") + "/" +
							 d.getFullYear();
			window.event.returnValue = 0;
		}
		if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
			if ((key_code > 47 && key_code < 58) ||
			  (key_code > 95 && key_code < 106)) {
				if (key_code > 95) key_code -= (95-47);
				oElement.value =
					oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
			}
			if (key_code == 8) {
				if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
					oElement.value = "dd/mm/aaaa";
				oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
					function ($0, $1, $2) {
						var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
						if (idx >= 5) {
							return $1 + "a" + $2;
						} else if (idx >= 2) {
							return $1 + "m" + $2;
						} else {
							return $1 + "d" + $2;
						}
					} );
				window.event.returnValue = 0;
			}
		}
		if (key_code != 9) {
			event.returnValue = false;
		}
	}

	// Preenche todas as regulagens
	var ok = false;
	function TodosRegulagem() {
		f = document.frm_os;
		if (!ok) {
			for (i=0; i<<?echo $qtde_item?>; i++){
				myREF = "produto_referencia_" + i;
				myNF  = "regulagem_peso_padrao_0";
				myNFF = "regulagem_peso_padrao_" + i;
				if ((f.elements[myREF].type == "text") && (f.elements[myREF].value != "")){
					f.elements[myNFF].value = f.elements[myNF].value;
					//alert(i);
				}
				ok = true;
			}
		}else{
			for (i=1; i<<?echo $qtde_item?>; i++){
				myNFF = "regulagem_peso_padrao_" + i;
				f.elements[myNFF].value = "";
			}
			ok = false;
		}

	}

	// Preenche todos os certificados
	var ok2 = false;
	function TodosCertificado() {
		f = document.frm_os;
		if (!ok2) {
			for (i=0; i<<?echo $qtde_item?>; i++){
				myREF = "produto_referencia_" + i;
				myNF  = "certificado_conformidade_0";
				myNFF = "certificado_conformidade_" + i;
				if ((f.elements[myREF].type == "text") && (f.elements[myREF].value != "")){
					f.elements[myNFF].value = f.elements[myNF].value;
					//alert(i);
				}
				ok2 = true;
			}
		}else{
			for (i=1; i<<?echo $qtde_item?>; i++){
				myNFF = "certificado_conformidade_" + i;
				f.elements[myNFF].value = "";
			}
			ok2 = false;
		}
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


	function atualizaValorKM(campo){
		if (campo.value == 'carro'){
			$('input[name=valor_por_km]').val( $('input[name=valor_por_km_carro]').val() );
		}
		if (campo.value == 'caminhao'){
			$('input[name=valor_por_km]').val( $('input[name=valor_por_km_caminhao]').val() );
		}
	}

	function atualizaCobraHoraDiaria(campo){
		if (campo.value == 'isento'){
			$('div[name=div_hora]').css('display','none');
			$('div[name=div_diaria]').css('display','none');
			$('div[name=div_desconto_hora_diaria]').css('display','none');
			$('input[name=hora_tecnica]').attr('disabled','disabled');
			$('input[name=valor_diaria]').attr('disabled','disabled');
		}
		if (campo.value == 'hora'){
			$('div[name=div_hora]').css('display','');
			$('div[name=div_diaria]').css('display','none');
			$('div[name=div_desconto_hora_diaria]').css('display','');
			$('#hora_tecnica').removeAttr("disabled")
			$('#valor_diaria').attr('disabled','disabled');
		}
		if (campo.value == 'diaria'){
			$('div[name=div_hora]').css('display','none');
			$('div[name=div_diaria]').css('display','');
			$('div[name=div_desconto_hora_diaria]').css('display','');
			$('#hora_tecnica').attr('disabled','disabled');
			$('#valor_diaria').removeAttr("disabled")
		}
	}

	function atualizaCobraDeslocamento(campo){
		if (campo.value == 'isento'){
			$('div[name=div_valor_por_km]').css('display','none');
			$('div[name=div_taxa_visita]').css('display','none');
			$('div[name=div_desconto_deslocamento]').css('display','none');
			$('input[name=valor_por_km]').attr('disabled','disabled');
			$('input[name=taxa_visita]').attr('disabled','disabled');
		}
		if (campo.value == 'valor_por_km'){
			$('div[name=div_valor_por_km]').css('display','');
			$('div[name=div_taxa_visita]').css('display','none');
			$('div[name=div_desconto_deslocamento]').css('display','');
			$('input[name=valor_por_km]').removeAttr("disabled")
			$('input[name=taxa_visita]').attr('disabled','disabled');

			$('input[name=veiculo]').each(function (){
				if (this.checked){
					atualizaValorKM(this);
				}
			});
		}
		if (campo.value == 'taxa_visita'){
			$('div[name=div_valor_por_km]').css('display','none');
			$('div[name=div_taxa_visita]').css('display','');
			$('div[name=div_desconto_deslocamento]').css('display','');
			$('input[name=valor_por_km]').attr('disabled','disabled');
			$('input[name=taxa_visita]').removeAttr("disabled")
		}
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

	var http5 = new Array();
	var http6 = new Array();

	function busca_valores(){

		var arrayReferencias = new Array();
		var listaReferencias = "";
		var cliente_cnpj     = $("input[@name='cliente_cnpj']").val();

		$("input[@rel='produtos']").each( function (){
			if (this.value.length > 0){
				arrayReferencias.push( this.value );
			}
		});

		listaReferencias = arrayReferencias.join(",");

		if (listaReferencias.length > 0) {
			var curDateTime = new Date();
			http5[curDateTime] = createRequestObject();
			url = "<?=$PHP_SELF?>?ajax=true&buscaValores=true&produto_referencia="+listaReferencias+'&cliente_cnpj='+cliente_cnpj+'&data='+curDateTime;

			http5[curDateTime].open('get',url);

			http5[curDateTime].onreadystatechange = function(){
				if (http5[curDateTime].readyState == 4){
					if (http5[curDateTime].status == 200 || http5[curDateTime].status == 304){
						var results = http5[curDateTime].responseText.split("|");
						if (results[0] == 'ok') {
							$('input[name=taxa_visita]').val(results[1]);
							$('#taxa_visita').html(results[1]);
							$('input[name=hora_tecnica]').val(results[2]);
							$('#hora_tecnica').html(results[2]);
							$('input[name=valor_diaria]').val(results[3]);
							$('#valor_diaria').html(results[3]);
							$('input[name=valor_por_km_carro]').val(results[4]);
							$('#valor_por_km_carro').html('R$ '+results[4]);
							$('input[name=valor_por_km_caminhao]').val(results[5]);
							$('#valor_por_km_caminhao').html('R$ '+results[5]);
							$('input[name=regulagem_peso_padrao]').val(results[6]);
							$('#regulagem_peso_padrao').html(results[6]);
							//$('input[name=certificado_conformidade]').val(results[7]);
							//$('#certificado_conformidade').html(results[7]);
							$('input[name=desconto_peca]').val(results[8]);
							//$('#desconto_peca').html(results[8]);

							$('input[name=veiculo]').each(function (){
								if (this.checked){
									atualizaValorKM(this);
								}
							});
						}
					}
				}
			}
			http5[curDateTime].send(null);
		}
	}

	function busca_valores_produto(linha){
		var referencia = $("input[@name=produto_referencia_"+linha+"]").val();

		if (referencia.length > 0) {
			var curDateTime = new Date();
			http6[curDateTime] = createRequestObject();
			url = "<?=$PHP_SELF?>?ajax=true&buscaValores=true&produto_referencia="+referencia+'&data='+curDateTime;

			http6[curDateTime].open('get',url);

			http6[curDateTime].onreadystatechange = function(){
				if (http6[curDateTime].readyState == 4){
					if (http6[curDateTime].status == 200 || http6[curDateTime].status == 304){
						var results = http6[curDateTime].responseText.split("|");
						if (results[0] == 'ok') {
							//$('input[name=regulagem_peso_padrao_'+linha+']').val(results[6]);
							//$('#regulagem_peso_padrao_'+linha).html(results[6]);
							$('input[name=certificado_conformidade_'+linha+']').val(results[7]);
							$('#certificado_conformidade_'+linha).html(results[7]);
						}
					}
				}
			}
			http6[curDateTime].send(null);
		}
	}

	function verificaValorPorKm(campo){
		if (campo.checked){
			$('div[name=div_valor_por_km]').css('display','');
			$('div[name=div_taxa_visita]').css('display','none');
			$('input[name=taxa_visita]').attr("disabled", true);
		}else{
			$('div[name=div_valor_por_km]').css('display','none');
			$('div[name=div_taxa_visita]').css('display','');
			$('input[name=taxa_visita]').removeAttr("disabled");
		}
		$("input[@name='veiculo']").each( function (){
			if (this.checked){
				atualizaValorKM( this );
			}
		});
	}

	function qtdeItens(campo){
		var linha = 0;
		if (campo.value > 0){
			$(".tabela_item tr").each( function (){
				linha = parseInt( $(this).attr("rel") );
				linha++;
				if (linha  > campo.value) {
					$(this).css('display','none');
				}else{
					$(this).css('display','');
				}
			});
		}
	}

</script>


<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	text-align: center;
	border: 1px solid #d9e2ef;
}

.table_line {
	text-align: right;
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

.table_line3 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

fieldset.valores{
	border:none;
}

fieldset.valores , fieldset.valores div{
	padding: 0.2em;
	font-size:10px;
	width:225px;
}

fieldset.valores label {
	float:left;
	width:43%;
	margin-right:0.2em;
	padding-top:0.2em;
	text-align:right;
}

fieldset.valores span {
	font-size:11px;
	font-weight:bold;
}

span.valor {
	font-size:10px;
	font-weight:bold;
}


</style>

<?

if (strlen($os_manutencao) > 0) {
	$sql = "SELECT  tbl_os_revenda.os_revenda,
					tbl_os_revenda.consumidor_revenda,
					TO_CHAR(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura,
					tbl_os_revenda.hora_abertura,
					tbl_os_revenda.posto,
					tbl_os_revenda.consumidor_nome,
					tbl_os_revenda.qtde_km,
					tbl_os_revenda.quem_abriu_chamado,

					tbl_os_revenda.taxa_visita,
					tbl_os_revenda.visita_por_km,
					tbl_os_revenda.valor_por_km,
					tbl_os_revenda.hora_tecnica,
					tbl_os_revenda.valor_diaria,
					tbl_os_revenda.veiculo,
					tbl_os_revenda.regulagem_peso_padrao,
					tbl_os_revenda.desconto_deslocamento,
					tbl_os_revenda.desconto_hora_tecnica,
					tbl_os_revenda.desconto_diaria,
					tbl_os_revenda.desconto_peca,
					tbl_os_revenda.condicao,
					tbl_os_revenda.classificacao_os,

					tbl_os_revenda.obs,
					tbl_os_revenda.contrato,
					tbl_os_revenda.nota_fiscal,
					TO_CHAR(tbl_os_revenda.data_nf,'DD/MM/YYYY') AS data_nf,
					tbl_cliente.cliente              AS cliente,
					tbl_cliente.nome                 AS cliente_nome,
					tbl_cliente.cpf                  AS cliente_cpf,
					tbl_cliente.fone                 AS cliente_fone,
					tbl_cliente.endereco             AS cliente_endereco,
					tbl_cliente.numero               AS cliente_numero,
					tbl_cliente.complemento          AS cliente_complemento,
					tbl_cliente.bairro               AS cliente_bairro,
					tbl_cliente.cep                  AS cliente_cep,
					tbl_cidade.nome                  AS cliente_cidade,
					tbl_cidade.estado                AS cliente_estado,
					tbl_revenda.revenda              AS revenda,
					tbl_revenda.nome                 AS revenda_nome,
					tbl_revenda.cnpj                 AS revenda_cnpj,
					tbl_revenda.fone                 AS revenda_fone,
					tbl_revenda.endereco             AS revenda_endereco,
					tbl_revenda.numero               AS revenda_numero,
					tbl_revenda.complemento          AS revenda_complemento,
					tbl_revenda.bairro               AS revenda_bairro,
					tbl_revenda.cep                  AS revenda_cep,
					CR.nome                          AS revenda_cidade,
					CR.estado                        AS revenda_estado,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.posto,
					tbl_posto.nome,
					tbl_posto.cnpj,
					tbl_posto_fabrica.contato_cidade AS posto_cidade,
					tbl_posto_fabrica.contato_estado AS posto_estado,
					tbl_os_revenda.consumidor_fone				,
					tbl_os_revenda.consumidor_endereco          ,
					tbl_os_revenda.consumidor_numero            ,
					tbl_os_revenda.consumidor_complemento       ,
					tbl_os_revenda.consumidor_bairro            ,
					tbl_os_revenda.consumidor_cep               ,
					tbl_os_revenda.consumidor_cidade            ,
					tbl_os_revenda.consumidor_estado            
			FROM	tbl_os_revenda
			JOIN	tbl_posto USING(posto)
			JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_cliente   ON tbl_cliente.cliente       = tbl_os_revenda.cliente
			LEFT JOIN tbl_cidade    ON tbl_cidade.cidade         = tbl_cliente.cidade
			LEFT JOIN tbl_revenda   ON tbl_revenda.revenda       = tbl_os_revenda.revenda
			LEFT JOIN tbl_cidade CR ON CR.cidade                 = tbl_revenda.cidade
			WHERE	tbl_os_revenda.fabrica    = $login_fabrica
			AND		tbl_os_revenda.os_revenda = $os_manutencao ";
	$res = pg_exec($con, $sql);
	if (pg_numrows($res) > 0) {
		$os_manutencao       = trim(pg_result($res,0,os_revenda));
		$consumidor_revenda  = trim(pg_result($res,0,consumidor_revenda));
		$data_abertura       = trim(pg_result($res,0,data_abertura));
		$hora_abertura       = trim(pg_result($res,0,hora_abertura));
		$consumidor_nome     = trim(pg_result($res,0,consumidor_nome));
		$qtde_km             = trim(pg_result($res,0,qtde_km));
		$quem_abriu_chamado  = trim(pg_result($res,0,quem_abriu_chamado));
		$obs                 = trim(pg_result($res,0,obs));
		$contrato            = trim(pg_result($res,0,contrato));
		$nota_fiscal         = trim(pg_result($res,0,nota_fiscal));
		$data_nf             = trim(pg_result($res,0,data_nf));
		$cliente             = trim(pg_result($res,0,cliente));
		$cliente_nome        = trim(pg_result($res,0,cliente_nome));
		$cliente_cnpj        = trim(pg_result($res,0,cliente_cpf));
		$cliente_fone        = trim(pg_result($res,0,cliente_fone));
		$cliente_endereco    = trim(pg_result($res,0,cliente_endereco));
		$cliente_numero      = trim(pg_result($res,0,cliente_numero));
		$cliente_complemento = trim(pg_result($res,0,cliente_complemento));
		$cliente_bairro      = trim(pg_result($res,0,cliente_bairro));
		$cliente_cep         = trim(pg_result($res,0,cliente_cep));
		$cliente_cidade      = trim(pg_result($res,0,cliente_cidade));
		$cliente_estado      = trim(pg_result($res,0,cliente_estado));
		$revenda             = trim(pg_result($res,0,revenda));
		$revenda_nome        = trim(pg_result($res,0,revenda_nome));
		$revenda_cnpj        = trim(pg_result($res,0,revenda_cnpj));
		$revenda_fone        = trim(pg_result($res,0,revenda_fone));
		$revenda_endereco    = trim(pg_result($res,0,revenda_endereco));
		$revenda_numero      = trim(pg_result($res,0,revenda_numero));
		$revenda_complemento = trim(pg_result($res,0,revenda_complemento));
		$revenda_bairro      = trim(pg_result($res,0,revenda_bairro));
		$revenda_cep         = trim(pg_result($res,0,revenda_cep));
		$revenda_cidade      = trim(pg_result($res,0,revenda_cidade));
		$revenda_estado      = trim(pg_result($res,0,revenda_estado));
		$posto_codigo        = trim(pg_result($res,0,codigo_posto));
		$posto_nome          = trim(pg_result($res,0,nome));
		$posto_cidade        = trim(pg_result($res,0,posto_cidade));
		$posto_estado        = trim(pg_result($res,0,posto_estado));

		$taxa_visita         = trim(pg_result($res,0,taxa_visita));
		$hora_tecnica        = trim(pg_result($res,0,hora_tecnica));
		$visita_por_km       = trim(pg_result($res,0,visita_por_km));

		$taxa_visita			= trim(pg_result ($res,0,taxa_visita));
		$visita_por_km			= trim(pg_result ($res,0,visita_por_km));
		$valor_por_km			= trim(pg_result ($res,0,valor_por_km));
		$hora_tecnica			= trim(pg_result ($res,0,hora_tecnica));
		$valor_diaria			= trim(pg_result ($res,0,valor_diaria));
		$veiculo				= trim(pg_result ($res,0,veiculo));
		$regulagem_peso_padrao	=trim(pg_result ($res,0,regulagem_peso_padrao));

		$desconto_deslocamento	= trim(pg_result ($res,0,desconto_deslocamento));
		$desconto_hora_tecnica	= trim(pg_result ($res,0,desconto_hora_tecnica));
		$desconto_diaria		= trim(pg_result ($res,0,desconto_diaria));
		$desconto_peca			= trim(pg_result ($res,0,desconto_peca));
		$condicao				= trim(pg_result ($res,0,condicao));
		$classificacao_os		= trim(pg_result ($res,0,classificacao_os));
		$consumidor_fone		= trim(pg_result($res,0,consumidor_fone));			
		$consumidor_endereco    = trim(pg_result($res,0,consumidor_endereco));
		$consumidor_numero      = trim(pg_result($res,0,consumidor_numero));
		$consumidor_complemento = trim(pg_result($res,0,consumidor_complemento));
		$consumidor_bairro      = trim(pg_result($res,0,consumidor_bairro));
		$consumidor_cep         = trim(pg_result($res,0,consumidor_cep));
		$consumidor_cidade      = trim(pg_result($res,0,consumidor_cidade));
		$consumidor_estado      = trim(pg_result($res,0,consumidor_estado));

		// HD  92046
		if(strlen($consumidor_nome) > 0)      $cliente_nome        = $consumidor_nome       ;
		if(strlen($consumidor_fone)>0)        $cliente_fone        = $consumidor_fone       ;
		if(strlen($consumidor_endereco)>0)    $cliente_endereco    = $consumidor_endereco    ;
		if(strlen($consumidor_numero)>0)      $cliente_numero      = $consumidor_numero      ;
		if(strlen($consumidor_complemento)>0) $cliente_complemento = $consumidor_complemento ;
		if(strlen($consumidor_bairro)>0)      $cliente_bairro      = $consumidor_bairro      ;
		if(strlen($consumidor_cep)>0)         $cliente_cep         = $consumidor_cep         ;
		if(strlen($consumidor_cidade)>0)      $cliente_cidade      = $consumidor_cidade      ;
		if(strlen($consumidor_estado)>0)      $cliente_estado      = $consumidor_estado      ;

		if ($veiculo == "carro"){
			$valor_por_km_carro = $valor_por_km;
		}

		if ($veiculo == "caminhao"){
			$valor_por_km_caminhao = $valor_por_km;
		}

		if ($valor_diaria == 0 AND $hora_tecnica == 0){
			$cobrar_hora_diaria = "isento";
		}

		if ($valor_diaria > 0 AND $hora_tecnica == 0){
			$cobrar_hora_diaria = "diaria";
		}

		if ($valor_diaria == 0 AND $hora_tecnica > 0){
			$cobrar_hora_diaria = "hora";
		}

		if ($valor_por_km == 0 AND $taxa_visita == 0){
			$cobrar_deslocamento = "isento";
		}
		if ($valor_por_km > 0 AND $taxa_visita == 0){
			$cobrar_deslocamento = "valor_por_km";
		}
		if ($valor_por_km == 0 AND $taxa_visita > 0){
			$cobrar_deslocamento = "taxa_visita";
		}

		if ($regulagem_peso_padrao > 0){
			$cobrar_regulagem = 't';
		}
	}
}


if (strlen ($msg_erro) > 0) {
	$os_manutencao        = $_POST['os_manutencao'];
	$cliente_nome         = $_POST['cliente_nome'];
	$cliente_cnpj         = $_POST['cliente_cnpj'];

	$data_abertura        = $_POST['data_abertura'];
	$hora_abertura        = $_POST['hora_abertura'];

	$qtde_km              = $_POST['qtde_km'];
	$contrato             = $_POST['contrato'];
	$quem_abriu_chamado   = $_POST['quem_abriu_chamado'];
	$taxa_visita          = $_POST['taxa_visita'];
	$hora_tecnica         = $_POST['hora_tecnica'];
	$cobrar_percurso      = $_POST['cobrar_percurso'];
	$visita_por_km        = $_POST['visita_por_km'];
	$diaria               = $_POST['diaria'];
	$obs                  = $_POST['obs'];
?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
		<? echo $msg_erro ?>
	</td>
</tr>
</table>
<?
}
?>

<br>

<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
<input type='hidden' name='os_manutencao' value='<?=$os_manutencao?>'>


<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr>
		<td valign="top" align="left">

			<table width="100%" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Consumidor</font>
						<input type='radio' name='consumidor_revenda' value='C' <?=($consumidor_revenda!='R')?"checked":"";?> onClick='verificaConsumidorRevenda(this)'>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Revenda</font>
						<input type='radio' name='consumidor_revenda' value='R' <?=($consumidor_revenda=='R')?"checked":"";?> onClick='verificaConsumidorRevenda(this)'>
					</td>
					<td align = 'right'>
						<?
						if ( strlen( trim($data_abertura)) == 0 ) {
							$data_abertura = date("d/m/Y");
						}
						?>
					&nbsp;
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Abertura</font>
						<input name="data_abertura" id="data_abertura" rel='data' size="12" maxlength="10" value="<? echo $data_abertura; ?>" type="text" class="frm"  onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a Data da Abertura da OS.');" tabindex="0"  ><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Hora Abertura</font>
							<?
							if (strlen($hora_abertura)==0){
								#$hora_abertura = date("H:i"); //Vazio para forçar o preenchimento
							}else{
								$hora_abertura = substr($hora_abertura,0,5);
							}
							?>
							<input name="hora_abertura" id="hora_abertura" rel='hora' size="7" maxlength="5" value="<? echo $hora_abertura; ?>" type="text" class="frm"  onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a Hora da Abertura da OS.');">

					</td>

				</tr>
			</table>
<!-- CLIENTE -->

		<?
			if ($consumidor_revenda == 'R'){
				$esconde_cliente = " style='display:none'; ";
			}
		?>
		<div id='tbl_cliente' <?=$esconde_cliente?>>
			<input type='hidden' name='cliente_cliente'>
			<input type='hidden' name='cliente_complemento'>

			<hr>
			<table width="100%" border="0" cellspacing="2" cellpadding="2">
				<tr class="menu_top">
					<td colspan='5' align='left'><font size="1" face="Geneva, Arial, Helvetica, san-serif">Cliente</font></td>
				</tr>
				<tr class='table_line3'>
					<td >Nome<br>
						<input class="frm" type="text" name="cliente_nome" size="28" maxlength="50" value="<? echo $cliente_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)">&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_cliente (document.frm_os.cliente_nome, "nome")' style='cursor:pointer;'>
					</td>
					<td >
						CPF/CNPJ Cliente<br>
						<input class="frm" type="text" name="cliente_cnpj" size="20" maxlength="18" value="<? echo $cliente_cnpj ?>">&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_cliente (document.frm_os.cliente_cnpj, "cpf")' style='cursor:pointer;'>
					</td>
					<td >
						IE/RG<br>
						<input class="frm" type="text" name="cliente_rg" size="18" maxlength="18" value="<? echo $cliente_rg ?>">
					</td>
					<td >
						Telefone<br>
						<input class="frm" type="text" name="cliente_fone" rel='fone' size="18" maxlength="18" value="<? echo $cliente_fone ?>">
					</td>
					<td>Distância (KM)<br>
						<INPUT TYPE="text" NAME="qtde_km" id='qtde_km' VALUE="<?=$qtde_km?>" SIZE='9' MAXLENGTH='9' class="frm">
					</td>
				</tr>
			</table>

			<table width="100%" border="0" cellspacing="2" cellpadding="2">
				<tr class='table_line3'>
					<td >Endereço<br>
						<input class="frm" type="text" name="cliente_endereco" size="28" maxlength="50" value="<? echo $cliente_endereco ?>">
					</td>
					<td >Número<br>
						<input class="frm" type="text" name="cliente_numero" size="4" maxlength="5" value="<? echo $cliente_numero ?>">
					</td>
					<td >CEP<br>
						<input class="frm" type="text" name="cliente_cep" rel='cep' size="10" maxlength="10" value="<? echo $cliente_cep ?>" onBlur="buscaCEP(this.value, document.frm_os.cliente_endereco, document.frm_os.cliente_bairro, document.frm_os.cliente_cidade, document.frm_os.cliente_estado)">
					</td>
					<td >Bairro<br>
						<input class="frm" type="text" name="cliente_bairro" size="20" maxlength="50" value="<? echo $cliente_bairro ?>">
					</td>
					<td >Cidade<br>
						<input class="frm" type="text" name="cliente_cidade" size="20" maxlength="18" value="<? echo $cliente_cidade ?>">
					</td>
					<td >Estado<br>
						<select name="cliente_estado" id='cliente_estado' size="1" class="frm">
							<option value=""   <? if (strlen($cliente_estado) == 0)    echo " selected "; ?>></option>
							<option value="AC" <? if ($cliente_estado == "AC") echo " selected "; ?>>AC</option>
							<option value="AL" <? if ($cliente_estado == "AL") echo " selected "; ?>>AL</option>
							<option value="AM" <? if ($cliente_estado == "AM") echo " selected "; ?>>AM</option>
							<option value="AP" <? if ($cliente_estado == "AP") echo " selected "; ?>>AP</option>
							<option value="BA" <? if ($cliente_estado == "BA") echo " selected "; ?>>BA</option>
							<option value="CE" <? if ($cliente_estado == "CE") echo " selected "; ?>>CE</option>
							<option value="DF" <? if ($cliente_estado == "DF") echo " selected "; ?>>DF</option>
							<option value="ES" <? if ($cliente_estado == "ES") echo " selected "; ?>>ES</option>
							<option value="GO" <? if ($cliente_estado == "GO") echo " selected "; ?>>GO</option>
							<option value="MA" <? if ($cliente_estado == "MA") echo " selected "; ?>>MA</option>
							<option value="MG" <? if ($cliente_estado == "MG") echo " selected "; ?>>MG</option>
							<option value="MS" <? if ($cliente_estado == "MS") echo " selected "; ?>>MS</option>
							<option value="MT" <? if ($cliente_estado == "MT") echo " selected "; ?>>MT</option>
							<option value="PA" <? if ($cliente_estado == "PA") echo " selected "; ?>>PA</option>
							<option value="PB" <? if ($cliente_estado == "PB") echo " selected "; ?>>PB</option>
							<option value="PE" <? if ($cliente_estado == "PE") echo " selected "; ?>>PE</option>
							<option value="PI" <? if ($cliente_estado == "PI") echo " selected "; ?>>PI</option>
							<option value="PR" <? if ($cliente_estado == "PR") echo " selected "; ?>>PR</option>
							<option value="RJ" <? if ($cliente_estado == "RJ") echo " selected "; ?>>RJ</option>
							<option value="RN" <? if ($cliente_estado == "RN") echo " selected "; ?>>RN</option>
							<option value="RO" <? if ($cliente_estado == "RO") echo " selected "; ?>>RO</option>
							<option value="RR" <? if ($cliente_estado == "RR") echo " selected "; ?>>RR</option>
							<option value="RS" <? if ($cliente_estado == "RS") echo " selected "; ?>>RS</option>
							<option value="SC" <? if ($cliente_estado == "SC") echo " selected "; ?>>SC</option>
							<option value="SE" <? if ($cliente_estado == "SE") echo " selected "; ?>>SE</option>
							<option value="SP" <? if ($cliente_estado == "SP") echo " selected "; ?>>SP</option>
							<option value="TO" <? if ($cliente_estado == "TO") echo " selected "; ?>>TO</option>
						</select>
					</td>
				</tr>
			</table>
		</div>

<!-- Revenda -->
			<hr>
			<input type='hidden' name='revenda'>

			<table width="100%" border="0" cellspacing="2" cellpadding="2">
				<tr class="menu_top">
					<td colspan='4' align='left'><font size="1" face="Geneva, Arial, Helvetica, san-serif">Revenda</font></td>
				</tr>
				<tr class='table_line3'>
					<td>
						Nome<br>
						<input class="frm" type="text" name="revenda_nome" size="28" maxlength="50" value="<? echo $revenda_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)">&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' style='cursor:pointer;'>
					</td>
					<td>
						CNPJ<br>
						<input class="frm" type="text" name="revenda_cnpj" size="20" maxlength="18" value="<? echo $revenda_cnpj ?>">&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor:pointer;'>
					</td>
					<td>
						Fone<br>
						<input class="frm" type="text" name="revenda_fone" rel='fone' size="18" maxlength="18" value="<? echo $revenda_fone ?>">
					</td>
					<td>
						E-Mail<br>
						<input readonly class="frm" type="text" name="revenda_email" size="21" maxlength="50" value="<? echo $revenda_email ?>" tabindex="0">
					</td>
				</tr>
			</table>

			<table width="100%" border="0" cellspacing="2" cellpadding="2">
				<tr class='table_line3'>
					<td>Endereço<br>
						<input class="frm" type="text" name="revenda_endereco" size="28" maxlength="50" value="<? echo $revenda_endereco ?>">
					</td>
					<td>Número<br>
						<input class="frm" type="text" name="revenda_numero" size="4" maxlength="5" value="<? echo $revenda_numero ?>">
					</td>
					<td>Complemento<br>
						<input class="frm" type="text" name="revenda_complemento" size="10" maxlength="10" value="<? echo $revenda_complemento ?>">
					</td>
					<td>CEP<br>
						<input class="frm" type="text" name="revenda_cep" rel='cep'size="10" maxlength="10" value="<? echo $revenda_cep ?>" onBlur="buscaCEP(this.value, document.frm_os.revenda_endereco, document.frm_os.revenda_bairro, document.frm_os.revenda_cidade, document.frm_os.revenda_estado)">
					</td>
					<td>Bairro<br>
						<input class="frm" type="text" name="revenda_bairro" size="20" maxlength="50" value="<? echo $revenda_bairro ?>">
					</td>
					<td>Cidade<br>
						<input class="frm" type="text" name="revenda_cidade" size="20" maxlength="18" value="<? echo $revenda_cidade ?>">
					</td>
					<td>Estado<br>
						<select name="revenda_estado" id="revenda_estado" size="1" class="frm">
						<option value=""   <? if (strlen($revenda_estado) == 0)    echo " selected "; ?>></option>
							<option value="AC" <? if ($revenda_estado == "AC") echo " selected "; ?>>AC</option>
							<option value="AL" <? if ($revenda_estado == "AL") echo " selected "; ?>>AL</option>
							<option value="AM" <? if ($revenda_estado == "AM") echo " selected "; ?>>AM</option>
							<option value="AP" <? if ($revenda_estado == "AP") echo " selected "; ?>>AP</option>
							<option value="BA" <? if ($revenda_estado == "BA") echo " selected "; ?>>BA</option>
							<option value="CE" <? if ($revenda_estado == "CE") echo " selected "; ?>>CE</option>
							<option value="DF" <? if ($revenda_estado == "DF") echo " selected "; ?>>DF</option>
							<option value="ES" <? if ($revenda_estado == "ES") echo " selected "; ?>>ES</option>
							<option value="GO" <? if ($revenda_estado == "GO") echo " selected "; ?>>GO</option>
							<option value="MA" <? if ($revenda_estado == "MA") echo " selected "; ?>>MA</option>
							<option value="MG" <? if ($revenda_estado == "MG") echo " selected "; ?>>MG</option>
							<option value="MS" <? if ($revenda_estado == "MS") echo " selected "; ?>>MS</option>
							<option value="MT" <? if ($revenda_estado == "MT") echo " selected "; ?>>MT</option>
							<option value="PA" <? if ($revenda_estado == "PA") echo " selected "; ?>>PA</option>
							<option value="PB" <? if ($revenda_estado == "PB") echo " selected "; ?>>PB</option>
							<option value="PE" <? if ($revenda_estado == "PE") echo " selected "; ?>>PE</option>
							<option value="PI" <? if ($revenda_estado == "PI") echo " selected "; ?>>PI</option>
							<option value="PR" <? if ($revenda_estado == "PR") echo " selected "; ?>>PR</option>
							<option value="RJ" <? if ($revenda_estado == "RJ") echo " selected "; ?>>RJ</option>
							<option value="RN" <? if ($revenda_estado == "RN") echo " selected "; ?>>RN</option>
							<option value="RO" <? if ($revenda_estado == "RO") echo " selected "; ?>>RO</option>
							<option value="RR" <? if ($revenda_estado == "RR") echo " selected "; ?>>RR</option>
							<option value="RS" <? if ($revenda_estado == "RS") echo " selected "; ?>>RS</option>
							<option value="SC" <? if ($revenda_estado == "SC") echo " selected "; ?>>SC</option>
							<option value="SE" <? if ($revenda_estado == "SE") echo " selected "; ?>>SE</option>
							<option value="SP" <? if ($revenda_estado == "SP") echo " selected "; ?>>SP</option>
							<option value="TO" <? if ($revenda_estado == "TO") echo " selected "; ?>>TO</option>
						</select>
					</td>
				</tr>
			</table>

<!--
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Código do posto</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome do posto</font>
					</td>
				</tr>
				<tr>
					<td align='center'>
						<input class="frm" type="text" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>">&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_os.posto_codigo,document.frm_os.posto_nome,'codigo')" style="cursor:pointer;"></A>
					</td>
					<td align='center'>
						<input class="frm" type="text" name="posto_nome" size="50" value="<? echo $posto_nome ?>" >&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto (document.frm_os.posto_codigo,document.frm_os.posto_nome,'nome')" style="cursor:pointer;"></A>
					</td>
				</tr>
			</table>
-->
			<hr>
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
<!--					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nota Fiscal</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data da Compra</font>
					</td> -->
					<? if ($login_fabrica==7) { // HD 75762 para Filizola ?>
						<td>
							<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Classificação da OS</font>
						</td>
					<? } ?>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Natureza</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Solicitante</font>
					</td>
				</tr>
				<tr>
<!--					<td align='center'>
						<input class="frm" type="text" name="nota_fiscal" size="10" maxlength='10' value="<? echo $nota_fiscal ?>">
					</td>
					<td align='center'>
						<input class="frm" type="text" name="data_nf" rel='data' size="12" maxlength='10' value="<? echo $data_nf ?>">
					</td>-->
					<? if ($login_fabrica==7) { // HD 75762 para Filizola ?>
						<td align='center'>
							<select name='classificacao_os' id='classificacao_os' style='width:180px;'>
								<option <? if (strlen($classificacao_os)==0) {echo "selected";} ?>></option>
								<?

									$sql = "SELECT	*
											FROM	tbl_classificacao_os
											WHERE	fabrica = $login_fabrica
											AND		ativo IS TRUE
											ORDER BY descricao";
									$res = @pg_exec ($con,$sql);
									if(pg_numrows($res) > 0){
										for($i=0; $i < pg_numrows($res); $i++){
											$xclassificacao_os=pg_result($res,$i,classificacao_os);
											if($xclassificacao_os==5 and $classificacao_os!=5){
												continue;
											}
											echo "<option value='$xclassificacao_os'";
											if ($classificacao_os == $xclassificacao_os) echo " selected";
											echo ">".pg_result($res,$i,descricao)."</option>\n";
										}
									}
								?>
							</select>
						</td>
					<? } ?>
					<td align='center'>
						<select name="tipo_atendimento" id='tipo_atendimento' style='width:180px;';>
						<?
						$sql = "SELECT *
								FROM tbl_tipo_atendimento
								WHERE fabrica = $login_fabrica
								AND   ativo IS TRUE
								ORDER BY tipo_atendimento ";
						$res = pg_exec ($con,$sql) ;
						for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
							echo "<option ";
							if ($tipo_atendimento == pg_result ($res,$i,tipo_atendimento) ) {
								echo " selected ";
							}
							echo " value='" . pg_result ($res,$i,tipo_atendimento) . "'>" ;
							echo pg_result ($res,$i,codigo) . " - " . pg_result ($res,$i,descricao) ;
							echo "</option>";
						}
						?>
						</select>
					</td>
					<td align='center'>
						<input class="frm" type="text" name="quem_abriu_chamado" size="16" value="<? echo $quem_abriu_chamado ?>">
					</td>
				</tr>
			</table>

			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Observações</font>
					</td>
				</tr>
				<tr>
					<td align='center'>
						<textarea name="obs" class="frm" cols="100" rows="2" style='width:100%'><? echo $obs ?></textarea>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>




<?
// monta o FOR
echo "<input class='frm' type='hidden' name='qtde_item' value='$qtde_item'>\n";

echo "<br>";

echo "<table border='0' cellpadding='1' cellspacing='2' align='center' bgcolor='#ffffff' class='tabela_item'>\n";

echo "<tr>\n";
echo "<td colspan='6'></td>\n";
echo "<td class=table_line>
		Qtde Item
		<select onChange='qtdeItens(this)'>
		<option value='30'>30 Itens</option>
		<option value='40'>40 Itens</option>
		<option value='50'>50 Itens</option>
		<option value='60'>60 Itens</option>
		<option value='80'>80 Itens</option>
		<option value='100'>100 Itens</option>
		<option value='150'>150 Itens</option>
		</select>
</td>\n";
echo "</tr>\n";


echo "<tr class='menu_top'>\n";
echo "<td align='center'>#</td>\n";
echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Produto</font></td>\n";
echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Descrição do produto</font></td>\n";
echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Nº Série</font></td>\n";
echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Capacidade</font></td>\n";
#echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Peso<br>Padrão</font><!-- <br> <img src='imagens/selecione_todas.gif' border=0 onclick=\"javascript:TodosRegulagem()\" ALT='Coloca o valor de Regulagem Peso Padrão do primeiro produto para todos.' style='cursor:pointer;'> --> </td>\n";
echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Cert.<br>Conf.</font><!-- <br> <img src='imagens/selecione_todas.gif' border=0 onclick=\"javascript:TodosCertificado()\" ALT='Coloca o valor de Certificado de Conformidade do primeiro produto para todos.' style='cursor:pointer;'>--></td>\n";
echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Defeito<br>Reclamado</font></td>\n";
echo "</tr>\n";

// monta <SELECT> de defeito reclamado
$sql = "SELECT defeito_reclamado,descricao
		FROM   tbl_defeito_reclamado
		JOIN   tbl_linha USING (linha)
		WHERE  tbl_linha.fabrica = $login_fabrica
		ORDER  BY tbl_defeito_reclamado.descricao;";
$resD = pg_exec($con,$sql);

function defeito_reclamado($selecionado){
	global $resD;
	$select_defeito_reclamado = "";

	for ($i=0; $i<pg_numrows($resD); $i++){
		$defeito_reclamado = pg_result($resD,$i,defeito_reclamado);
		$descricao         = FormataTexto(pg_result($resD,$i,descricao),'lower');

		$select_defeito_reclamado .= "<option value='$defeito_reclamado'";
		if ($selecionado == $defeito_reclamado) $select_defeito_reclamado .= " selected ";
		$select_defeito_reclamado .= ">".ucwords($descricao)."</option>\n";
	}

	echo $select_defeito_reclamado;

}

/*-----------------------------------------------------------------------------
	FormataTexto($text, $return)
	$text   = texto a ser alterado
	$return = 'lower' (minuscula) / 'upper' (maiuscula)
	Pega uma string e retorna em letras minusculas ou maiusculas
	Uso: $texto_upper = FormataTexto($text, 'upper');
-----------------------------------------------------------------------------*/
function FormataTexto($text,$return){
	if (!empty($text)) {

		$arrayLower=array('ç','â','ã','á','à','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','õ','ô','ö','ú','ù','û','ü');
		$arrayUpper=array('Ç','Â','Ã','Á','À','Ä','É','È','Ê','Ë','Í','Ì','Î','Ï','Ó','Ò','Õ','Ô','Ö','Ú','Ù','Û','Ü');

		if ($return == 'lower') {
			$text=strtolower($text);
			for($i=0; $i<count($arrayLower); $i++) {
				$text=str_replace($arrayUpper[$i], $arrayLower[$i], $text);
			}
		} elseif ($return == 'upper') {
			$text=strtoupper($text);
			for($i=0; $i<count($arrayLower); $i++) {
				$text=str_replace($arrayLower[$i], $arrayUpper[$i], $text);
			}
		}
		return($text);
	}
}

if (strlen($os_manutencao) > 0) {
	$sql = "SELECT  tbl_os_revenda_item.os_revenda_item            ,
					tbl_os_revenda_item.produto                    ,
					tbl_os_revenda_item.serie                      ,
					tbl_os_revenda_item.capacidade                 ,
					tbl_os_revenda_item.regulagem_peso_padrao      ,
					tbl_os_revenda_item.certificado_conformidade   ,
					tbl_os_revenda_item.defeito_reclamado_descricao,
					tbl_produto.referencia                         ,
					tbl_produto.descricao
			FROM	tbl_os_revenda
			JOIN	tbl_os_revenda_item USING(os_revenda)
			JOIN	tbl_produto         USING (produto)
			WHERE	tbl_os_revenda.fabrica         = $login_fabrica
			AND		tbl_os_revenda_item.os_revenda = $os_manutencao
			ORDER BY tbl_os_revenda_item.os_revenda_item ASC ";
	$res = pg_exec($con, $sql);
	$qtde_item_os = pg_numrows($res);
}

if ($qtde_item < $qtde_item_os){
	$qtde_item = $qtde_item_os;
}

if ($qtde_item_visiveis < $qtde_item_os){
	$qtde_item_visiveis = $qtde_item_os;
}

for ($i=0; $i<$qtde_item; $i++) {

	$novo                     = "t";
	$os_revenda_item          = "";
	$produto_referencia       = "";
	$produto_descricao        = "";
	$produto_serie            = "";
	$produto_capacidade       = "";
	#$regulagem_peso_padrao    = "";
	$certificado_conformidade = "";
	$defeito_reclamado        = "";
	$cobrar_regulagem_check   = "";
	$cobrar_certificado_check = "";
	#$cobrar_regulagem         = "";
	$cobrar_certificado       = "";

	if (strlen($os_manutencao) > 0 AND $i < $qtde_item_os AND strlen($msg_erro)==0){
		$novo                     = 'f';
		$os_revenda_item          = pg_result($res,$i,os_revenda_item);
		$produto_referencia       = pg_result($res,$i,referencia);
		$produto_descricao        = pg_result($res,$i,descricao);
		$produto_serie            = pg_result($res,$i,serie);
		$produto_capacidade       = pg_result($res,$i,capacidade);
		#$regulagem_peso_padrao    = pg_result($res,$i,regulagem_peso_padrao);
		$certificado_conformidade = pg_result($res,$i,certificado_conformidade);
		$defeito_reclamado        = pg_result($res,$i,defeito_reclamado_descricao);

		if ($regulagem_peso_padrao>0){
			#$cobrar_regulagem = 't';
		}

		if ($certificado_conformidade>0){
			$cobrar_certificado = 't';
		}
	}

	if( strlen($msg_erro) >0 ){
		$novo                     = $_POST["novo_".$i];
		$os_revenda_item          = $_POST["os_revenda_item_".$i];
		$produto_referencia       = $_POST["produto_referencia_".$i];
		$produto_serie            = $_POST["produto_serie_".$i];
		$produto_descricao        = $_POST["produto_descricao_".$i];
		$produto_capacidade       = $_POST["produto_capacidade_".$i];
		#$cobrar_regulagem         = $_POST["cobrar_regulagem_".$i];
		#$regulagem_peso_padrao    = $_POST["regulagem_peso_padrao_".$i];
		$cobrar_certificado       = $_POST["cobrar_certificado_".$i];
		$certificado_conformidade = $_POST["certificado_conformidade_".$i];

		$defeito_reclamado        = $_POST["defeito_reclamado_".$i];
	}

	#$regulagem_peso_padrao    = number_format($regulagem_peso_padrao,2,",",".");
	$certificado_conformidade = number_format($certificado_conformidade,2,",",".");

	if (strlen($produto_referencia)==0){
		#$regulagem_peso_padrao    = "";
		$certificado_conformidade = "";
	}

	if ($cobrar_regulagem=='t'){
		$cobrar_regulagem_check = ' checked ';
	}

	if ($cobrar_certificado=='t'){
		$cobrar_certificado_check = ' checked ';
	}

	$ocultar_item = "";
	if ($i+1 > $qtde_item_visiveis){
		$ocultar_item = " style='display:none' ";
	}

	$cor_item = "";
	if ($linha_erro == $i AND strlen ($msg_erro) > 0){
		$cor_item = " bgcolor='#ffcccc' ";
	}

	echo "<tr ".$ocultar_item." ".$cor_item." rel='$i'>\n";

	echo "<td align='center' class=table_line>\n";
	echo "<input type='hidden' name='novo_$i' value='$novo'>\n";
	echo "<input type='hidden' name='os_revenda_item_$i' value='$os_revenda_item'>\n";
	echo "<input type='hidden' name='produto_voltagem_$i' value=''>\n";
	echo $i+1;
	echo "</td>\n";

	echo "<td align='center'><input class='frm' type='text' name='produto_referencia_$i' onBlur='busca_valores_produto($i); ' rel='produtos' size='8' maxlength='50' value='$produto_referencia'><img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,\"referencia\",document.frm_os.produto_voltagem_$i,document.frm_os.produto_capacidade_$i)' style='cursor:pointer;'></td>\n";
	echo "<td align='center'><input class='frm' type='text' name='produto_descricao_$i' onBlur='busca_valores_produto($i);' size='30' maxlength='18' value='$produto_descricao'><img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,\"descricao\",document.frm_os.produto_voltagem_$i,document.frm_os.produto_capacidade_$i)' style='cursor:pointer;'></td>\n";
	echo "<td align='center'><input class='frm' type='text' name='produto_serie_$i'  size='7'  maxlength='20'  value='$produto_serie' onFocus='busca_valores_produto($i);'></td>\n";
	echo "<td align='center'><input class='frm' type='text' name='produto_capacidade_$i'  size='5'  maxlength='20'  value='$produto_capacidade' onFocus='busca_valores_produto($i);'></td>\n";
	#echo "<td align='left'><input type='checkbox' name='cobrar_regulagem_$i' value='t' $cobrar_regulagem_check > <span id='regulagem_peso_padrao_$i' class='valor'>".$regulagem_peso_padrao."</span><input type='hidden' name='regulagem_peso_padrao_$i' value='$regulagem_peso_padrao'></td>\n";
	echo "<td align='left'><span id='certificado_conformidade_$i'  class='valor'>".$certificado_conformidade."</span><input type='hidden' name='certificado_conformidade_$i' value='$certificado_conformidade'></td>\n";
	//<input type='checkbox' name='cobrar_certificado_$i' value='t' $cobrar_certificado_check >
	echo "<td align='center'><input class='frm' type='text' name='defeito_reclamado_$i' onFocus='' size='30' maxlength='50' value='$defeito_reclamado'></td>\n";
#	echo "<td align='center'>\n";
#	echo "	<select name='defeito_reclamado_$i' class='frm'>\n";
#	echo "		<option selected></option>\n";
#	defeito_reclamado($defeito_reclamado);
#	echo "	</select>\n";
#	echo "</td>\n";
	echo "</tr>\n";
}

?>
</table>

<?PHP
	if ($login_tipo_posto == 214 OR $login_tipo_posto == 215) {
?>

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr>
		<td valign="top" align="center">
			<br>
			<br>
			<input class='frm' type='button' onClick='busca_valores();' value='Atualizar Valores'>
			<br>
		</td>
	<tr>
		<td valign="top" align="left">
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">

					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Deslocamento</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Mão de Obra</font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Regulagem Peso Padrão</font>
					</td>
				</tr>
				<tr>
					<td valign='top'>
				<fieldset class='valores' style='height:140px;'>
					<div>
					<label for="cobrar_deslocamento">Isento:</label>
					<input type='radio' name='cobrar_deslocamento' value='isento' onClick='atualizaCobraDeslocamento(this)' <? if (strtolower($cobrar_deslocamento) == 'isento') echo "checked";?>>
					<br>
					<label for="cobrar_deslocamento">Por Km:</label>
					<input type='radio' name='cobrar_deslocamento' value='valor_por_km' <? if ($cobrar_deslocamento == 'valor_por_km') echo " checked " ?> onClick='atualizaCobraDeslocamento(this)'>
					<br />
					<label for="cobrar_deslocamento">Taxa de Visita:</label>
					<input type='radio' name='cobrar_deslocamento' value='taxa_visita' <? if ($cobrar_deslocamento == 'taxa_visita') echo " checked " ?> onClick='atualizaCobraDeslocamento(this)'>
					<br />
					</div>

					<div name='div_taxa_visita' <? if ($cobrar_deslocamento != 'taxa_visita') echo " style='display:none' "?>>
						<label for="taxa_visita">Valor:</label>
						<input type='text' name='taxa_visita' value='<? echo number_format($taxa_visita ,2,',','.') ?>' class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8'>
						<br />
					</div>

					<div <? if ($cobrar_deslocamento != 'valor_por_km' or strlen($cobrar_deslocamento)==0) echo " style='display:none' " ?> name='div_valor_por_km'>
						<label for="veiculo">Carro:</label>
						<input type='radio' name='veiculo' value='carro' onClick='atualizaValorKM(this)' <? if (strtolower($veiculo) != 'caminhao') echo "checked";?>>
						<input type='text' name='valor_por_km_carro' value='<? echo number_format($valor_por_km_carro,2,',','.') ?>' class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8' >
						<br>
						<label for="veiculo">Caminhão:</label>
						<input type='radio' name='veiculo' value='caminhao' onClick='atualizaValorKM(this)' <? if (strtolower($veiculo) == 'caminhao') echo "checked";?> >
						<input type='text' name='valor_por_km_caminhao' class='frm' value='<? echo number_format($valor_por_km_caminhao,2,',','.') ?>' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8'>
						<input type='hidden' name='valor_por_km' value='<? echo $valor_por_km ?>'>
					</div>

<?if  (1==2){ #HD 32483 ?>
					<div <? if ($cobrar_deslocamento == 'isento' OR strlen($cobrar_deslocamento)==0) echo " style='display:none' " ?> name='div_desconto_deslocamento'>
						<label>Desconto:</label>
						<input type='text' name='desconto_deslocamento' value="<? echo $desconto_deslocamento ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %
					</div>
<?}?>
				</fieldset>
					</td>
					<td valign='top'>
				<fieldset class='valores' style='height:140px;'>
					<div>
					<label for="cobrar_hora_diaria">Diária:</label>
					<input type='radio' name='cobrar_hora_diaria' value='diaria' onClick='atualizaCobraHoraDiaria(this)' <? if (strtolower($cobrar_hora_diaria) == 'diaria') echo "checked";?>>
					<br>
					<label for="cobrar_hora_diaria">Hora Técnica:</label>
					<input type='radio' name='cobrar_hora_diaria' value='hora' onClick='atualizaCobraHoraDiaria(this)' <? if (strtolower($cobrar_hora_diaria) == 'hora') echo "checked";?>>
					<br>
					</div>
					<div <? if ($cobrar_hora_diaria != 'hora') echo " style='display:none' " ?> name='div_hora'>
						<label>Valor:</label>
						<input type='text' name='hora_tecnica' value='<? echo number_format($hora_tecnica,2,',','.') ?>' class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8'>
						<br>
<?/*						<!--<br>
						<label>Desconto:</label>
						<input type='text' name='desconto_hora_tecnica' value="<? echo $desconto_hora_tecnica ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %-->
*/?>
					</div>
					<div <? if ($cobrar_hora_diaria != 'diaria') echo " style='display:none' " ?> name='div_diaria'>
						<label>Valor:</label>
						<input type='text' name='valor_diaria' value="<? echo number_format($valor_diaria,2,',','.') ?>" class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8'>
						<br>
<?/*						<!--						<br>
						<label>Desconto:</label>
						<input type='text' name='desconto_diaria' value="<? echo $desconto_diaria ?>" class='frm' onblur="this.className='frm';" onfocus="this.className='frm-on';" size='6' maxlength='6'> %
-->
*/?>
					</div>
				</fieldset>
					</td>
					<td valign='top'>
						<fieldset class='valores'>
							<div>

							<label>Regulagem:</label>
							<input type="checkbox" name="cobrar_regulagem" value="t" <? if ($cobrar_regulagem=='t') echo "checked" ?>>
							<br />
							<label>Valor:</label>
							<input type="text" name="regulagem_peso_padrao" value="<? echo number_format($regulagem_peso_padrao,2,',','.') ?>"  class='frm' onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on';" size='8' maxlength='8'>
							<br>
						</fieldset>
					</td>
				</tr>
				<tr class="menu_top">
					<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">% Desconto de Peças</font></td>
					<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Condição de Pagamento</font></td>
				</tr>
				<tr>
					<TD valign='top'>
						<input type='text' name='desconto_peca' class='frm' value='<?=$desconto_peca?>' size='12' maxlength='5'>
					</TD>
					<TD valign='top'>
						<fieldset class='valores'>
							<div>
							<SELECT NAME='condicao' class='frm'>"
								<OPTION VALUE=''></OPTION>
							<?
							$sql = " SELECT condicao,
											codigo_condicao,
											descricao
									FROM tbl_condicao
									WHERE fabrica = $login_fabrica
										AND visivel is true";
							$res = pg_exec ($con,$sql);
							for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
								echo "<option ";
								if ($condicao== pg_result ($res,$i,condicao) ) echo " selected ";
								echo " value='" . pg_result ($res,$i,condicao) . "'>" ;
								echo pg_result ($res,$i,codigo_condicao) . " - " . pg_result ($res,$i,descricao) ;
								echo "</option>";
							}
							?>
							</SELECT>
						</div>
					</TD>
				</tr>
			</table>
		</td>
	</tr>
</table>
<? } ?>

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr>
		<td colspan='8' align='center'>
			<br>
			<input type='hidden' name='btn_acao' value=''>
			<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT='Gravar' border='0' style='cursor:pointer;'>
		</td>
	</tr>
</table>


</form>

<br>

<? include 'rodape.php'; ?>
