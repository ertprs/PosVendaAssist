<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

include 'autentica_usuario.php';
include 'funcoes.php';

$msg_erro  = "";
$qtde_item = 150;
$qtde_item_visiveis = 10;

$btn_acao = trim (strtolower ($_POST['btn_acao']));

if($_GET["ajax"]=="true" AND $_GET["buscaValores"]=="true"){
	$referencia = trim($_GET["produto_referencia"]);

	$listaReferencias = implode("','",explode(",",$referencia));
	$retorno = 0;
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
			echo "ok|$taxa_visita|$hora_tecnica|$valor_diaria|$valor_por_km_carro|$valor_por_km_caminhao|$regulagem_peso_padrao|$certificado_conformidade";
			exit;
		}
	}
	echo "nao|nao";
	exit;
}

if (strlen($_GET['os_metal'])  > 0) $os_metal = trim($_GET['os_metal']);
if (strlen($_POST['os_metal']) > 0) $os_metal = trim($_POST['os_metal']);
/*
echo "post:";
print_r ($_POST);
*/
if ($btn_acao == "gravar") {

	$consumidor_revenda = $_POST['consumidor_revenda'];
	if($login_fabrica ==1 ){
		$consumidor_revenda= "C";
	}
	if (strlen($consumidor_revenda)==0 ){
		$msg_erro = "Selecione Consumidor ou Revenda.:";
	}

	$xconsumidor_revenda = "'".$consumidor_revenda."'";

	if (strlen($_POST['consumidor_cnpj']) > 0) {
		$consumidor_cnpj  = trim($_POST['consumidor_cnpj']);
		$consumidor_cnpj  = str_replace (".","",$consumidor_cnpj);
		$consumidor_cnpj  = str_replace ("-","",$consumidor_cnpj);
		$consumidor_cnpj  = str_replace ("/","",$consumidor_cnpj);
		$consumidor_cnpj  = str_replace (" ","",$consumidor_cnpj);
		$consumidor_cnpj  = substr($consumidor_cnpj,0,14);
		$consumidor_cpf= $consumidor_cnpj ;
	}else{
		$consumidor_cnpj = "";
	}

	$consumidor_nome  = $_POST['consumidor_nome'];
	if (strlen($consumidor_nome) > 0) {
		$consumidor_nome = $consumidor_nome ;
	}else{
		$msg_erro.="Informe o nome do consumidor";
	}

	$consumidor_fone = $_POST['consumidor_fone'];
	if (strlen($consumidor_fone) > 0) {
		$consumidor_fone = $consumidor_fone ;
	}else{
		$msg_erro.="Informe o telefone do consumidor";
	}

	$consumidor_endereco  = $_POST['consumidor_endereco'];
	if (strlen($consumidor_endereco) > 0) {
		$consumidor_endereco = $consumidor_endereco ;
	}else{
		$msg_erro.="Informe o endereço do consumidor";
	}

	$consumidor_numero  = $_POST['consumidor_numero'];
	if (strlen($consumidor_numero) > 0) {
		$consumidor_numero = $consumidor_numero ;
	}else{
		$msg_erro.="Informe o número do consumidor";
	}

	$consumidor_complemento  = $_POST['consumidor_complemento'];
	if (strlen($consumidor_complemento) > 0) {
		$consumidor_complemento = $consumidor_complemento ;
	}else{
		$consumidor_complemento = "";
	}
	
	$consumidor_cep  = $_POST['consumidor_cep'];
	if (strlen($consumidor_cep) > 0) {
		$xconsumidor_cep  = str_replace (".","",$consumidor_cep);
		$xconsumidor_cep  = str_replace ("-","",$xconsumidor_cep);
		$xconsumidor_cep  = str_replace ("/","",$xconsumidor_cep);
		$xconsumidor_cep  = str_replace (" ","",$xconsumidor_cep);
		$consumidor_cep = $xconsumidor_cep ;
	}else{
		$consumidor_cep = "";
	}

	$consumidor_bairro  = $_POST['consumidor_bairro'];
	if (strlen($consumidor_bairro) > 0) {
		$consumidor_bairro = $consumidor_bairro ;
	}else{
		$consumidor_bairro = "";
	}

	$consumidor_cidade  = $_POST['consumidor_cidade'];
	if (strlen($consumidor_cidade) > 0) {
		$consumidor_cidade = $consumidor_cidade ;
	}else{
		$consumidor_cidade = "";
	}

	$consumidor_estado  = $_POST['consumidor_estado'];
	if (strlen($consumidor_estado) > 0) {
		$consumidor_estado = $consumidor_estado ;
	}else{
		$consumidor_estado = "";
	}


	$consumidor_email  = $_POST['consumidor_email'];
	if (strlen($consumidor_email) > 0) {
		$consumidor_email =  $consumidor_email;
	}else{
		$consumidor_email = "";
	}

	if ($consumidor_revenda == 'R'){
		$xconsumidor_cnpj = "null";
		$xcliente = "null";
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

	if (strlen($_POST['data_abertura']) > 0) {
		$xdata_abertura = fnc_formata_data_pg(trim($_POST['data_abertura']));
		
		$sql = " SELECT $xdata_abertura > current_date - interval '6 months'";
		$res = pg_exec($con,$sql);
		if(pg_result($res,0,0) == 'f'){
			$msg_erro .= "A Data de abertura não pode ser anterior a 6 meses";
		}
	}else{
		$msg_erro .= "Digite a data de abertura";
	}


	if (strlen($_POST['tipo_atendimento']) > 0) {
		$tipo_atendimento = $_POST['tipo_atendimento'];
	}else{
		$msg_erro .= "Digite o tipo de atendimento.";
	}

	if (strlen(trim($_POST['fisica_juridica'])) == 0 AND $login_fabrica == 1) {
		$msg_erro .="Escolha o Tipo Consumidor<BR>";
	}else{
		$xfisica_juridica = "'".$_POST['fisica_juridica']."'";
	}

	if (strlen($_POST['obs']) > 0) {
		$xobs = "'". $_POST['obs'] ."'";
	}else{
		$xobs = "null";
	}
	
/*	if (strlen($_POST['relatorio_tecnico']) > 0) {
		$xrelatorio_tecnico = "'". $_POST['relatorio_tecnico'] ."'";
	}else{
		$xrelatorio_tecnico = "null";
	}
*/
	$qtde_km = $_POST['qtde_km'];

	if (strlen($qtde_km) > 0) {
		$xqtde_km = $_POST['qtde_km'] ;
	}else{
		$xqtde_km = "null";
	}
	$xqtde_km	= str_replace (".","", $xqtde_km);
	$xqtde_km	= str_replace (",",".",$xqtde_km);

	if (strlen($_POST['valor_adicional']) > 0) {
		$xvalor_adicional = $_POST['valor_adicional'] ;
	}else{
		$xvalor_adicional = "null";
	}
	$xvalor_adicional = str_replace (".","", $xvalor_adicional);
	$xvalor_adicional = str_replace (",",".",$xvalor_adicional);

	if (strlen($_POST['valor_adicional_justificativa']) > 0) {
		$xvalor_adicional_justificativa = "'". $_POST['valor_adicional_justificativa'] ."'";
	}else{
		$xvalor_adicional_justificativa = "null";
	}

	$retorno_visita = $_POST['retorno_visita'];
	if (strlen($retorno_visita ) > 0) {
		$retorno_visita = $_POST['retorno_visita'] ;
	}else{
		$retorno_visita = "f";
	}

	if (strlen($_POST['contrato']) > 0) {
		$xcontrato = "'". $_POST['contrato'] ."'";
	}else{
		$xcontrato = "'f'";
	}
	

	if (strlen($_POST['quem_abriu_chamado']) > 0) {
		$xquem_abriu_chamado = "'".$_POST['quem_abriu_chamado']."'" ;
	}else{
		$xquem_abriu_chamado = 'null'; 
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




	if (strlen ($msg_erro) == 0) {

		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen ($os_metal) == 0) {
			$sql = "INSERT INTO tbl_os_revenda (
						data_abertura      ,
						fabrica            ,
						consumidor_revenda ,
						consumidor_nome    ,
						consumidor_cnpj    ,
						consumidor_email    ,
						consumidor_cidade  ,
						consumidor_estado  ,
						consumidor_fone    ,
						consumidor_endereco,
						consumidor_numero  ,
						consumidor_cep     ,
						consumidor_complemento,
						consumidor_bairro  ,
						nota_fiscal        ,
						tipo_atendimento   ,
						data_nf            ,
						qtde_km            ,
						valor_adicional ,
						valor_adicional_justificativa,
						valor_por_km       ,
						quem_abriu_chamado ,
						obs                ,
						posto              ,
						contrato           ,
						condicao           ,
						os_geo             ,
						fisica_juridica
					) VALUES (
						$xdata_abertura                   ,
						$login_fabrica                    ,
						$xconsumidor_revenda              ,
						'$consumidor_nome'                ,
						'$consumidor_cnpj'                ,
						'$consumidor_email'               ,
						'$consumidor_cidade'              ,
						'$consumidor_estado'              ,
						'$consumidor_fone'                ,
						'$consumidor_endereco'            ,
						'$consumidor_numero'              ,
						'$consumidor_cep'                 ,
						'$consumidor_complemento'         ,
						'$consumidor_bairro'              ,
						$xnota_fiscal                     ,
						$tipo_atendimento                 ,
						$xdata_nf                         ,
						$xqtde_km                         ,
						$xvalor_adicional                 ,
						$xvalor_adicional_justificativa   ,
						0.65                              ,
						$xquem_abriu_chamado              ,
						$xobs                             ,
						$login_posto                      ,
						$xcontrato                        ,
						$xcondicao                        ,
						't'                               ,
						$xfisica_juridica
					)";
		}else{

			$sql = "UPDATE tbl_os_revenda SET
						consumidor_nome       = '$consumidor_nome'        ,
						consumidor_cnpj       = '$consumidor_cnpj'         ,
						consumidor_email      = '$consumidor_email'         ,
						consumidor_cidade     = '$consumidor_cidade'        ,
						consumidor_estado     = '$consumidor_estado'        ,
						consumidor_fone       = '$consumidor_fone'          ,
						consumidor_endereco   = '$consumidor_endereco'      ,
						consumidor_numero     = '$consumidor_numero'        ,
						consumidor_cep        = '$consumidor_cep'           ,
						consumidor_complemento= '$consumidor_complemento'   ,
						consumidor_bairro     = '$consumidor_bairro'        ,
						nota_fiscal        = $xnota_fiscal               ,
						data_nf            = $xdata_nf                   ,
						tipo_atendimento   = $tipo_atendimento           ,
						qtde_km            = $xqtde_km                   ,
						valor_adicional    = $xvalor_adicional        ,
						valor_adicional_justificativa= $xvalor_adicional_justificativa,
						valor_por_km       = 0.65                        ,
						quem_abriu_chamado = $xquem_abriu_chamado        ,
						obs                = $xobs                       ,
						contrato           = $xcontrato                  ,
						condicao           = $xcondicao                  ,
						os_geo             = 't'                         ,
						retorno_visita     = '$retorno_visita'           ,
						fisica_juridica             = $xfisica_juridica
					WHERE os_revenda = $os_metal 
					AND   fabrica    = $login_fabrica
					AND   posto      = $login_posto ";
		}
		$res = @pg_exec ($con,$sql);

		$msg_erro .= pg_errormessage($con);
		
		if (strlen($msg_erro) == 0 and strlen($os_metal) == 0) {
			$res           = pg_exec ($con,"SELECT CURRVAL ('seq_os_revenda')");
			$os_metal      = pg_result ($res,0,0);
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
					desconto_diaria          = $desconto_diaria         
				WHERE fabrica    = $login_fabrica
				AND   posto      = $login_posto 
				AND   os_revenda = $os_metal  ";
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

				if (strlen($defeito_reclamado) == 0 and strlen($referencia)> 0){
					$msg_erro .= "Selecione o defeito reclamado.";
				}

				if ($defeito_reclamado == '0' and strlen($referencia)> 0) {
					$msg_erro .= "Selecione o defeito reclamado . <BR>";
				}

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
							WHERE  os_revenda      = $os_metal
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
					
					if (strlen($defeito_reclamado) == 0 and strlen($referencia)> 0) {
						$msg_erro .= "Defeito reclamado do produto é obrigatório.";
					}else{
						$xdefeito_reclamado = $defeito_reclamado;
					}

					if (strlen ($msg_erro) > 0) {
						break;
					}
					if ($novo == 't'){
						$sql = "INSERT INTO tbl_os_revenda_item (
									os_revenda                ,
									produto                   ,
									serie                     ,
									defeito_reclamado         ,
									capacidade                ,
									regulagem_peso_padrao     ,
									certificado_conformidade  ,
									nota_fiscal               ,
									data_nf                   
								) VALUES (
									$os_metal            ,
									$produto                  ,
									'$serie'                  ,
									$defeito_reclamado        ,
									$xcapacidade              ,
									$regulagem_peso_padrao    ,
									$certificado_conformidade ,
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
									defeito_reclamado           = $defeito_reclamado        ,
									nota_fiscal                 = $xnota_fiscal             ,
									data_nf                     = $xdata_nf                  
								WHERE  os_revenda      = $os_metal 
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

			$sql = "UPDATE tbl_os_revenda SET revenda = $revenda WHERE os_revenda = $os_metal AND fabrica = $login_fabrica";
			$res = @pg_exec ($con,$sql);
			$monta_sql .= "13: $sql<br>$msg_erro<br><br>";
		}

		if(strlen($msg_erro) ==0){
			$sql = "UPDATE tbl_os_revenda SET
							sua_os = '$os_metal'
					WHERE tbl_os_revenda.os_revenda  = $os_metal
					AND   tbl_os_revenda.posto       = $login_posto
					AND   tbl_os_revenda.fabrica     = $login_fabrica ";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if(strlen($os_metal) > 0 AND strlen($msg_erro) ==0){
			$sql = "SELECT fn_valida_os_revenda ($os_metal,$login_posto,$login_fabrica)";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");

			header ("Location: os_metal_finalizada.php?os_metal=$os_metal");
			exit;
		}else{
			$os_metal = "";
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}


/* ====================  APAGAR  =================== */
if ($btn_acao == "apagar") {
	if(strlen($os_metal) > 0){
		$sql = "DELETE FROM tbl_os_revenda 
				WHERE tbl_os_revenda.os_revenda = $os_metal 
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


$title			= "Cadastro de Ordem de Serviço - Metais Sanitários"; 
$layout_menu	= "callcenter";

include "cabecalho.php";
include "javascript_pesquisas.php";
#include "admin/javascript_calendario.php";
 
?>
<script language='javascript' src='ajax.js'></script>

<script type="text/javascript" src="js/jquery-1.2.1.pack.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>

<script language="JavaScript">
	$(function() {
        $("#cpf_consumidor").numeric();
        $("#cnpj_revenda").numeric();

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
    });

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

        valor = valor.replace(".","");
        valor = valor.replace(".","");
        valor = valor.replace("-","");

        if (tipo == 2) {
            valor = valor.replace("/","");
        }

        if (valor.length == 11 && tipo == 1) {
            campo.value = valor.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/,"$1.$2.$3-$4");//CPF
        } else if (valor.length == 14 && tipo == 2) {
            campo.value = valor.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/,'$1.$2.$3/$4-$5');//CNPJ
        }

    }
	//ajax defeito_reclamado
	function listaDefeitos(defeito, valor) {
	//verifica se o browser tem suporte a ajax
		try {ajax = new ActiveXObject("Microsoft.XMLHTTP");}
		catch(e) { try {ajax = new ActiveXObject("Msxml2.XMLHTTP");}
			catch(ex) { try {ajax = new XMLHttpRequest();}
					catch(exc) {alert("Esse browser não tem recursos para uso do Ajax"); ajax = null;}
			}
		}
	//se tiver suporte ajax
		if(ajax) {
		//deixa apenas o elemento 1 no option, os outros são excluídos
		defeito_rec = document.getElementById('defeito_reclamado_'+defeito);
		defeito_rec.options.length = 1;
		//opcoes é o nome do campo combo
		idOpcao  = document.getElementById("opcoes");
		//	 ajax.open("POST", "ajax_produto.php", true);
		ajax.open("GET", "ajax_produto_antigo.php?produto_referencia="+valor, true);
		ajax.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

		ajax.onreadystatechange = function() {
			if(ajax.readyState == 1) {idOpcao.innerHTML = "Carregando...!";}//enquanto estiver processando...emite a msg
			if(ajax.readyState == 4 ) {if(ajax.responseXML) { montaCombo(defeito_rec, ajax.responseXML);//após ser processado-chama fun
				} else {idOpcao.innerHTML = "Selecione o produto";//caso não seja um arquivo XML emite a mensagem abaixo
						}
			}
		}
		//passa o código do produto escolhido
		var params = "produto_referencia="+valor;
		ajax.send(null);
		}
	}
	function montaCombo(defeito, obj){
		var dataArray   = obj.getElementsByTagName("produto");//pega a tag produto
		if(dataArray.length > 0) {//total de elementos contidos na tag cidade
		for(var i = 0 ; i < dataArray.length ; i++) {     //percorre o arquivo XML paara extrair os dados
			 var item = dataArray[i];
			//contéudo dos campos no arquivo XML
			var codigo    =  item.getElementsByTagName("codigo")[0].firstChild.nodeValue;
			var nome =  item.getElementsByTagName("nome")[0].firstChild.nodeValue;
			idOpcao.innerHTML = "Selecione o defeito";
			//cria um novo option dinamicamente
			var novo = document.createElement("option");
			novo.setAttribute("id", "opcoes");//atribui um ID a esse elemento
			novo.value = codigo;		//atribui um valor
			novo.text  = nome;//atribui um texto
			defeito_rec.options.add(novo);

			//document.forms[0].defeito_reclamado.options.add(novo);//adiciona o novo elemento
			}
		} else { idOpcao.innerHTML = "Selecione o defeito";//caso o XML volte vazio, printa a mensagem abaixo
		}
	}


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
			url = "produto_pesquisa_os_metais.php?campo=" + xcampo.value + "&tipo=" + tipo ;
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
		<?php if ($login_fabrica == 1) { ?>
		$('#alerta').val(0);
		<?php } ?>
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

		$("input[@rel='produtos']").each( function (){
			if (this.value.length > 0){
				arrayReferencias.push( this.value );
			}
		});

		listaReferencias = arrayReferencias.join(",");

		if (listaReferencias.length > 0) {
			var curDateTime = new Date();
			http5[curDateTime] = createRequestObject();
			url = "<?=$PHP_SELF?>?ajax=true&buscaValores=true&produto_referencia="+listaReferencias+'&data='+curDateTime;

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

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.titulo_coluna{
	background-color: #596D9B;
	color: white;
	font: normal normal bold 11px/normal Arial;
	text-align: center;
}
input[type=button]{
	cursor:pointer;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}
.littleFont{
    font:bold 11px Arial;
}
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}


</style>

<? 

if (strlen($os_metal) > 0) {
	$sql = "SELECT  
					tbl_os_revenda.os_revenda,
					TO_CHAR(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura,
					tbl_os_revenda.posto,
					tbl_os_revenda.qtde_km,
					tbl_os_revenda.quem_abriu_chamado,
					tbl_os_revenda.valor_adicional,
					tbl_os_revenda.valor_adicional_justificativa,
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
					tbl_os_revenda.condicao,
					tbl_os_revenda.obs,
					tbl_os_revenda.contrato,
					tbl_os_revenda.nota_fiscal,
					tbl_os_revenda.retorno_visita,
					tbl_os_revenda.fisica_juridica ,
					TO_CHAR(tbl_os_revenda.data_nf,'DD/MM/YYYY') AS data_nf,
					tbl_os_revenda.consumidor_revenda,
					tbl_os_revenda.consumidor_nome,
					tbl_os_revenda.consumidor_cnpj,
					tbl_os_revenda.consumidor_cidade    ,
					tbl_os_revenda.consumidor_estado    ,
					tbl_os_revenda.consumidor_fone      ,
					tbl_os_revenda.consumidor_email     ,
					tbl_os_revenda.consumidor_endereco  ,
					tbl_os_revenda.consumidor_numero    ,
					tbl_os_revenda.consumidor_cep       ,
					tbl_os_revenda.consumidor_complemento,
					tbl_os_revenda.consumidor_bairro    ,
					tbl_revenda.revenda              AS revenda,
					tbl_revenda.nome                 AS revenda_nome,
					tbl_revenda.cnpj                 AS revenda_cnpj,
					tbl_revenda.fone                 AS revenda_fone,
					tbl_revenda.endereco             AS revenda_endereco,
					tbl_revenda.numero               AS revenda_numero,
					tbl_revenda.complemento          AS revenda_complemento,
					tbl_revenda.bairro               AS revenda_bairro,
					tbl_revenda.cep                  AS revenda_cep,
					tbl_os_revenda.tipo_atendimento                ,
					CR.nome                          AS revenda_cidade,
					CR.estado                        AS revenda_estado,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.posto,
					tbl_posto.nome,
					tbl_posto.cnpj,
					tbl_posto_fabrica.contato_cidade AS posto_cidade,
					tbl_posto_fabrica.contato_estado AS posto_estado
			FROM	tbl_os_revenda
			JOIN	tbl_posto USING(posto)
			JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_revenda   ON tbl_revenda.revenda = tbl_os_revenda.revenda
			LEFT JOIN tbl_cidade CR ON CR.cidade           = tbl_revenda.cidade
			WHERE	tbl_os_revenda.fabrica    = $login_fabrica
			AND		tbl_os_revenda.os_revenda = $os_metal ";
	$res = pg_exec($con, $sql);
	//echo "sql: $sql";
	if (pg_numrows($res) > 0) {
		$os_metal       = trim(pg_result($res,0,os_revenda));
		$consumidor_revenda  = trim(pg_result($res,0,consumidor_revenda));
		$data_abertura       = trim(pg_result($res,0,data_abertura));
		$consumidor_nome     = trim(pg_result($res,0,consumidor_nome));
		$consumidor_cnpj     = trim(pg_result($res,0,consumidor_cnpj));
		$consumidor_cidade   = trim(pg_result($res,0,consumidor_cidade));
		$consumidor_estado   = trim(pg_result($res,0,consumidor_estado));

		$consumidor_fone       = trim(pg_result($res,0,consumidor_fone));
		$consumidor_email      = trim(pg_result($res,0,consumidor_email));
		$consumidor_endereco   = trim(pg_result($res,0,consumidor_endereco));
		$consumidor_numero     = trim(pg_result($res,0,consumidor_numero));
		$consumidor_cep        = trim(pg_result($res,0,consumidor_cep));
		$consumidor_complemento= trim(pg_result($res,0,consumidor_complemento));
		$consumidor_bairro     = trim(pg_result($res,0,consumidor_bairro));

		$qtde_km             = trim(pg_result($res,0,qtde_km));
		$valor_adicional     = trim(pg_result($res,0,valor_adicional));
		$valor_adicional_justificativa= trim(pg_result($res,0,valor_adicional_justificativa));
		
		$quem_abriu_chamado  = trim(pg_result($res,0,quem_abriu_chamado));
		$obs                 = trim(pg_result($res,0,obs));
		$contrato            = trim(pg_result($res,0,contrato));
		$nota_fiscal         = trim(pg_result($res,0,nota_fiscal));
		$data_nf             = trim(pg_result($res,0,data_nf));
		$retorno_visita      = trim(pg_result($res,0,retorno_visita));
		$fisica_juridica	 = trim(pg_result($res,0,fisica_juridica));
		$revenda             = trim(pg_result($res,0,revenda));
		$revenda_nome        = trim(pg_result($res,0,revenda_nome));
		$revenda_cnpj        = trim(pg_result($res,0,revenda_cnpj));
		$revenda_fone        = trim(pg_result($res,0,revenda_fone));
		$revenda_endereco    = trim(pg_result($res,0,revenda_endereco));
		$revenda_numero      = trim(pg_result($res,0,revenda_numero));
		$revenda_complemento = trim(pg_result($res,0,revenda_complemento));
		$revenda_bairro      = trim(pg_result($res,0,revenda_bairro));
		$revenda_cep         = trim(pg_result($res,0,revenda_cep));
		$tipo_atendimento    = trim(pg_result($res,0,tipo_atendimento));
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
		$condicao				= trim(pg_result ($res,0,condicao));


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

	$os_metal        = $_POST['os_metal'];
	$consumidor_nome         = $_POST['consumidor_nome'];
	$consumidor_cnpj         = $_POST['consumidor_cnpj'];

	//$qtde_km              = $_POST['qtde_km'];
	$contrato             = $_POST['contrato'];
	$quem_abriu_chamado   = $_POST['quem_abriu_chamado'];
	$taxa_visita          = $_POST['taxa_visita'];
	$hora_tecnica         = $_POST['hora_tecnica'];
	$cobrar_percurso      = $_POST['cobrar_percurso'];
	$visita_por_km        = $_POST['visita_por_km'];
	$diaria               = $_POST['diaria'];
	$obs                  = $_POST['obs'];
	$fisica_juridica      = $_POST['fisica_juridica'];
	$retorno_visita       = $_POST['retorno_visita'];
	
?>
<table align="center" width="700" >
<tr>
	<td valign="middle" align="center" class='msg_erro'>
		<? echo $msg_erro ?>
	</td>
</tr>
</table>
<?
}
?>

<br>

<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
<input type='hidden' name='os_metal' value='<?=$os_metal?>'>


<table align="center" width="700" cellspacing="1" class="formulario">
	<tr>
		<td valign="top" align="left">
			<?
			if($login_fabrica <> 1){
			?>
			<table align="center" width="700" cellspacing="1" class="formulario">
				<tr>
					<td>
						Consumidor
						<input type='radio' name='consumidor_revenda' value='C' <?=($consumidor_revenda!='R')?"checked":"";?> onClick='verificaConsumidorRevenda(this)'>
					</td>
					<td>
						Revenda
						<input type='radio' name='consumidor_revenda' value='R' <?=($consumidor_revenda=='R')?"checked":"";?> onClick='verificaConsumidorRevenda(this)'>
					</td>

				</tr>
			</table>
			<?
			}
			?>

<!-- CLIENTE -->
	
		<?
			if ($consumidor_revenda == 'R'){
				$esconde_cliente = " style='display:none'; ";
			}
		?>
		<?php if ($login_fabrica == 1) { ?>
		<input type="hidden" name="alerta" id="alerta" value="0">
		<?php } ?>
		<div id='tbl_cliente' <?=$esconde_cliente?>>
			<input type='hidden' name='cliente_cliente'>
			<input type='hidden' name='consumidor_complemento'>

			<table align="center" width="700" cellspacing="1" class="formulario">
				<tr class="titulo_tabela">
					<td colspan='6' align='left'>Cliente</td>
				</tr>
				<tr class='table_line3'>
					<td >Nome<br>
						<input class="frm" type="text" name="consumidor_nome" size="25" maxlength="50" value="<? echo $consumidor_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)">&nbsp;
					</td>
					<td >
						CPF/CNPJ <br>
						<input class="frm" type="text" name="consumidor_cnpj" size="20" maxlength="18" value="<? echo $consumidor_cnpj ?>">&nbsp;
					</td>
					<td >
						Tipo Consumidor
						<br>
						<SELECT NAME="fisica_juridica" class="frm">
							<OPTION></OPTION>
							<OPTION VALUE="F" <?if($fisica_juridica =='F') echo " SELECTED ";?>>Pessoa Física</OPTION>
							<OPTION VALUE="J" <?if($fisica_juridica =='J') echo " SELECTED ";?>>Pessoa Jurídica</OPTION>
						</SELECT>
					</td >
					<td >
						Telefone<br>
						<input class="frm" type="text" name="consumidor_fone" rel='fone' size="18" maxlength="16" value="<? echo $consumidor_fone ?>">
					</td>
					<td >
						Email<br>
						<input class="frm" type="text" name="consumidor_email" size="18" maxlength="50" value="<? echo $consumidor_email ?>">
					</td>
				</tr>
			</table>
			
			<table align="center" width="700" cellspacing="1" class="formulario multiCep">
				<tr>
					<td >CEP<br>
						<input class="frm addressZip" type="text" name="consumidor_cep" rel='cep' size="10" maxlength="10" value="<? echo $consumidor_cep ?>" >
					</td>
					<td >Estado<br>
						<select name="consumidor_estado" id='consumidor_estado' size="1" class="frm addressState">
							<option value=""   <? if (strlen($consumidor_estado) == 0)    echo " selected "; ?>></option>
							<option value="AC" <? if ($consumidor_estado == "AC") echo " selected "; ?>>AC</option>
							<option value="AL" <? if ($consumidor_estado == "AL") echo " selected "; ?>>AL</option>
							<option value="AM" <? if ($consumidor_estado == "AM") echo " selected "; ?>>AM</option>
							<option value="AP" <? if ($consumidor_estado == "AP") echo " selected "; ?>>AP</option>
							<option value="BA" <? if ($consumidor_estado == "BA") echo " selected "; ?>>BA</option>
							<option value="CE" <? if ($consumidor_estado == "CE") echo " selected "; ?>>CE</option>
							<option value="DF" <? if ($consumidor_estado == "DF") echo " selected "; ?>>DF</option>
							<option value="ES" <? if ($consumidor_estado == "ES") echo " selected "; ?>>ES</option>
							<option value="GO" <? if ($consumidor_estado == "GO") echo " selected "; ?>>GO</option>
							<option value="MA" <? if ($consumidor_estado == "MA") echo " selected "; ?>>MA</option>
							<option value="MG" <? if ($consumidor_estado == "MG") echo " selected "; ?>>MG</option>
							<option value="MS" <? if ($consumidor_estado == "MS") echo " selected "; ?>>MS</option>
							<option value="MT" <? if ($consumidor_estado == "MT") echo " selected "; ?>>MT</option>
							<option value="PA" <? if ($consumidor_estado == "PA") echo " selected "; ?>>PA</option>
							<option value="PB" <? if ($consumidor_estado == "PB") echo " selected "; ?>>PB</option>
							<option value="PE" <? if ($consumidor_estado == "PE") echo " selected "; ?>>PE</option>
							<option value="PI" <? if ($consumidor_estado == "PI") echo " selected "; ?>>PI</option>
							<option value="PR" <? if ($consumidor_estado == "PR") echo " selected "; ?>>PR</option>
							<option value="RJ" <? if ($consumidor_estado == "RJ") echo " selected "; ?>>RJ</option>
							<option value="RN" <? if ($consumidor_estado == "RN") echo " selected "; ?>>RN</option>
							<option value="RO" <? if ($consumidor_estado == "RO") echo " selected "; ?>>RO</option>
							<option value="RR" <? if ($consumidor_estado == "RR") echo " selected "; ?>>RR</option>
							<option value="RS" <? if ($consumidor_estado == "RS") echo " selected "; ?>>RS</option>
							<option value="SC" <? if ($consumidor_estado == "SC") echo " selected "; ?>>SC</option>
							<option value="SE" <? if ($consumidor_estado == "SE") echo " selected "; ?>>SE</option>
							<option value="SP" <? if ($consumidor_estado == "SP") echo " selected "; ?>>SP</option>
							<option value="TO" <? if ($consumidor_estado == "TO") echo " selected "; ?>>TO</option>
						</select>
					</td>
					<td >Cidade<br>
						<select id="consumidor_cidade" name="consumidor_cidade" class="frm addressCity" style="width:100px">
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
						<!-- <input class="frm addressCity" type="text" name="consumidor_cidade" size="20" maxlength="18" value="<? echo $consumidor_cidade ?>"> -->
					</td>
					<td >Bairro<br>
						<input class="frm addressDistrict" type="text" name="consumidor_bairro" size="20" maxlength="30" value="<? echo $consumidor_bairro ?>">
					</td>
					<td >Endereço<br>
						<input class="frm address" type="text" name="consumidor_endereco" size="28" maxlength="50" value="<? echo $consumidor_endereco ?>">
					</td>
					<td >Número<br>
						<input class="frm" type="text" name="consumidor_numero" size="4" maxlength="5" value="<? echo $consumidor_numero ?>">
					</td>
				</tr>
			</table>
		</div>
			
<!-- Revenda -->
		
			<input type='hidden' name='revenda'>

			<table align="center" width="700" cellspacing="1" class="formulario">
				<tr class="titulo_tabela">
					<td colspan='4' align='left'>Revenda</td>
				</tr>
				<tr>
					<td>
						Nome<br>
						<input class="frm" type="text" name="revenda_nome" id="revenda_nome" size="28" maxlength="50" value="<? echo $revenda_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)">&nbsp;
						<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' style='cursor:pointer;'>
					</td>
					<td>
						CNPJ<br>
						<input class="frm" type="text" name="revenda_cnpj" id="revenda_cnpj" size="20" maxlength="18" value="<? echo $revenda_cnpj ?>">&nbsp;
						<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor:pointer;'>
					</td>
					<td>
						Fone<br>
						<input class="frm" type="text" name="revenda_fone" id="revenda_fone" rel='fone' size="18" maxlength="18" value="<? echo $revenda_fone ?>">
					</td>
					<td>
						E-Mail<br>
						<input class="frm" type="text" name="revenda_email" id="revenda_email" size="21" maxlength="50" value="<? echo $revenda_email ?>" tabindex="0">
					</td>
				</tr>
			</table>
			
			<table align="center" width="700" cellspacing="1" class="formulario multiCep">			
				<tr>
					<td>CEP<br>
						<input class="frm addressZip" type="text" name="revenda_cep" id="revenda_cep" rel='cep'size="10" maxlength="10" value="<? echo $revenda_cep ?>">
					</td>
					<td>Estado<br>
						<select name="revenda_estado" id="revenda_estado" size="1" class="frm addressState">
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
					<td>Cidade<br>
						<select id="revenda_cidade" name="revenda_cidade" class="frm addressCity" style="width:100px">
                            <option value="" >Selecione</option>
                            <?php
                                if (strlen($revenda_estado) > 0) {
                                    $sql = "SELECT DISTINCT * FROM (
                                            SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".$revenda_estado."')
                                                UNION (
                                                    SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".$revenda_estado."')
                                                )
                                            ) AS cidade
                                            ORDER BY cidade ASC";
                                    $res = pg_query($con, $sql);

                                    if (pg_num_rows($res) > 0) {
                                        while ($result = pg_fetch_object($res)) {
                                            $selected  = (trim($result->cidade) == $revenda_cidade) ? "SELECTED" : "";

                                            echo "<option value='{$result->cidade}' {$selected} >{$result->cidade} </option>";
                                        }
                                    }
                                }
                            ?>
                        </select>
						<!-- <input class="frm addressCity" type="text" name="revenda_cidade" size="20" maxlength="18" value="<? echo $revenda_cidade ?>"> -->
					</td>
					<td>Bairro<br>
						<input class="frm addressDistrict" type="text" name="revenda_bairro" id="revenda_bairro" size="20" maxlength="50" value="<? echo $revenda_bairro ?>">
					</td>
					<td>Endereço<br>
						<input class="frm address" type="text" name="revenda_endereco" id="revenda_endereco" size="28" maxlength="50" value="<? echo $revenda_endereco ?>">
					</td>
					<td>Número<br>
						<input class="frm" type="text" name="revenda_numero" id="revenda_numero" size="4" maxlength="5" value="<? echo $revenda_numero ?>">
					</td>
					<td>Complemento<br>
						<input class="frm" type="text" name="revenda_complemento" size="10" maxlength="10" value="<? echo $revenda_complemento ?>">
					</td>
				</tr>
			</table>

			<table align="center" width="700" cellspacing="1" class="formulario">
				<tr class="titulo_tabela">
					<td>Data Abertura</td>
					<td>Nota Fiscal</td>
					<td>Data da Compra</td> 
					<td>Tipo de Atendimento</td>
				</tr>
				<tr>
					<td align='center'>
						<input class="frm" type="text" name="data_abertura" rel='data' size="12" maxlength='10' value="<? echo $data_abertura ?>">
					</td>
					<td align='center'>
						<input class="frm" type="text" name="nota_fiscal" size="10" maxlength='20' value="<? echo $nota_fiscal ?>">
					</td>
					<td align='center'>
						<input class="frm" type="text" name="data_nf" rel='data' size="12" maxlength='10' value="<? echo $data_nf ?>">
					</td>
					<td>
						<select name="tipo_atendimento" id='tipo_atendimento' style='width:230px;' class="frm">
						<?
						$cond_tipo_atendimento = " 1=1 ";
						if($login_fabrica ==1){
							$cond_tipo_atendimento = " tipo_atendimento in(64,69) ";
						}

						$sql = "SELECT * 
								FROM tbl_tipo_atendimento 
								WHERE fabrica = $login_fabrica
								AND   ativo IS TRUE 
								AND   $cond_tipo_atendimento 
								ORDER BY tipo_atendimento ";
						$res = pg_exec ($con,$sql) ;

						for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
							echo "<option ";
							if ($tipo_atendimento == pg_result ($res,$i,tipo_atendimento) ) {
								echo " selected ";
							}
							$j=$i+1;
							echo " value='" . pg_result ($res,$i,tipo_atendimento) . "'>" ;
							echo $j . " - " . pg_result ($res,$i,descricao) ;
							echo "</option>";
						}
						?>
						</select>

					</td>
				</tr>
			</table>

			<table align="center" width="700" cellspacing="1" class="formulario">
				<tr class="titulo_tabela">
					<td>Observações</td>
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

echo '<table align="center" width="700" cellspacing="1" class="formulario">'."\n";

echo "<tr class='titulo_tabela'>\n";
echo "<td colspan='3'></td>\n";
echo "<td>
		Qtde Item
		<select onChange='qtdeItens(this)' class='frm'>
		<option value='10'>10 Itens</option>
		<option value='20'>20 Itens</option>
		<option value='40'>40 Itens</option>
		<option value='60'>60 Itens</option>
		<option value='80'>80 Itens</option>
		<option value='100'>100 Itens</option>
		<option value='150'>150 Itens</option>
		</select>
</td>\n";
echo "</tr>\n";


echo "<tr class='subtitulo'>\n";
echo "<td align='center'>#</td>\n";
echo "<td align='center'>Produto</td>\n";
echo "<td align='center'>Descrição do produto</td>\n";
//echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Nº Série</font></td>\n";
//echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Capacidade</font></td>\n";
//echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Peso<br>Padrão</font><!-- <br> <img src='imagens/selecione_todas.gif' border=0 onclick=\"javascript:TodosRegulagem()\" ALT='Coloca o valor de Regulagem Peso Padrão do primeiro produto para todos.' style='cursor:pointer;'> --> </td>\n";
//echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Cert.<br>Conf.</font><!-- <br> <img src='imagens/selecione_todas.gif' border=0 onclick=\"javascript:TodosCertificado()\" ALT='Coloca o valor de Certificado de Conformidade do primeiro produto para todos.' style='cursor:pointer;'>--></td>\n";
echo "<td align='center'>Defeito<br>Reclamado</td>\n";
echo "</tr>\n";

// monta <SELECT> de defeito reclamado
$sql = "SELECT defeito_reclamado,
				descricao
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

if (strlen($os_metal) > 0) {
	$sql = "SELECT  tbl_os_revenda_item.os_revenda_item            ,
					tbl_os_revenda_item.produto                    ,
					tbl_os_revenda_item.serie                      ,
					tbl_os_revenda_item.capacidade                 ,
					tbl_os_revenda_item.regulagem_peso_padrao      ,
					tbl_os_revenda_item.certificado_conformidade   ,
					tbl_os_revenda_item.defeito_reclamado          ,
					tbl_produto.referencia                         ,
					tbl_produto.descricao
			FROM	tbl_os_revenda
			JOIN	tbl_os_revenda_item USING(os_revenda)
			JOIN	tbl_produto         USING (produto)
			WHERE	tbl_os_revenda.fabrica         = $login_fabrica
			AND		tbl_os_revenda_item.os_revenda = $os_metal 
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

	if (strlen($os_metal) > 0 AND $i < $qtde_item_os AND strlen($msg_erro)==0){
		$novo                     = 'f';
		$os_revenda_item          = pg_result($res,$i,os_revenda_item);
		$produto_referencia       = pg_result($res,$i,referencia);
		$produto_descricao        = pg_result($res,$i,descricao);
		$produto_serie            = pg_result($res,$i,serie);
		$produto_capacidade       = pg_result($res,$i,capacidade);
		#$regulagem_peso_padrao    = pg_result($res,$i,regulagem_peso_padrao);
		$certificado_conformidade = pg_result($res,$i,certificado_conformidade);
		$defeito_reclamado        = pg_result($res,$i,defeito_reclamado);
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
	
	$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

	echo "<tr ".$ocultar_item." bgcolor='".$cor."' rel='$i'>\n";

	echo "<td align='center'>\n";
	echo "<input type='hidden' name='novo_$i' value='$novo'>\n";
	echo "<input type='hidden' name='os_revenda_item_$i' value='$os_revenda_item'>\n";
	echo "<input type='hidden' name='produto_voltagem_$i' value=''>\n";
	echo $i+1;
	echo "</td>\n";
	echo "<td align='center'><input class='frm' type='text' name='produto_referencia_$i' onBlur='busca_valores_produto($i); ' rel='produtos' size='8' maxlength='50' value='$produto_referencia'><img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,\"referencia\",document.frm_os.produto_voltagem_$i,document.frm_os.produto_capacidade_$i)' style='cursor:pointer;'></td>\n";
	echo "<td align='center'><input class='frm' type='text' name='produto_descricao_$i' onBlur='busca_valores_produto($i);' size='30' maxlength='18' value='$produto_descricao'><img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,\"descricao\",document.frm_os.produto_voltagem_$i,document.frm_os.produto_capacidade_$i)' style='cursor:pointer;'></td>\n";
#	echo "<td align='center'><input class='frm' type='text' name='produto_serie_$i'  size='7'  maxlength='20'  value='$produto_serie' onFocus='busca_valores_produto($i);'></td>\n";
#	echo "<td align='center'><input class='frm' type='text' name='produto_capacidade_$i'  size='5'  maxlength='20'  value='$produto_capacidade' onFocus='busca_valores_produto($i);'></td>\n";
	#echo "<td align='left'><input type='checkbox' name='cobrar_regulagem_$i' value='t' $cobrar_regulagem_check > <span id='regulagem_peso_padrao_$i' class='valor'>".$regulagem_peso_padrao."</span><input type='hidden' name='regulagem_peso_padrao_$i' value='$regulagem_peso_padrao'></td>\n";
#	echo "<td align='left'><span id='certificado_conformidade_$i'  class='valor'>".$certificado_conformidade."</span><input type='hidden' name='certificado_conformidade_$i' value='$certificado_conformidade'></td>\n";
	//<input type='checkbox' name='cobrar_certificado_$i' value='t' $cobrar_certificado_check >

	echo "<td align='center'><select name='defeito_reclamado_$i'  id='defeito_reclamado_$i' style='width: 300px;' onfocus='listaDefeitos($i, document.frm_os.produto_referencia_$i.value);' >";
	if(strlen($defeito_reclamado) > 0 ) {
		$sql = " SELECT defeito_reclamado,descricao
				FROM tbl_defeito_reclamado
				WHERE defeito_reclamado = $defeito_reclamado";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$descricao = pg_fetch_result($res,0,descricao);
			echo "<option id='opcoes' value='$defeito_reclamado'>$descricao</option>";
		}
	}else{
		echo "<option id='opcoes' value='0'></option>";
	}
	echo "</select>";
	echo "</td>";


	//echo "<td align='center'><input class='frm' type='text' name='defeito_reclamado_$i' onFocus='' size='30' maxlength='50' value='$defeito_reclamado'></td>\n";
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

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr>
		<td colspan='8' align='center'>
			<br>
			<input type='hidden' name='btn_acao' value=''>
			<input type="button" value="Gravar" onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT='Gravar' border='0'>
		</td>
	</tr>
</table>
</form>

<br>
<script language='javascript' src='admin/address_components.js'></script>
<? include 'rodape.php'; ?>
