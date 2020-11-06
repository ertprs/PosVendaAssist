<?php
// as tabs definem a categoria do chamado
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios = "call_center";
include 'autentica_admin.php';

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);

if( isset($_GET["q"]) )
{
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];

	if( strlen($q) > 2 )
	{
		if( $tipo_busca == "revenda" )
		{
			$sql = "SELECT tbl_revenda.revenda, tbl_revenda.cnpj, tbl_revenda.nome
					FROM tbl_revenda
					JOIN tbl_revenda_compra USING(revenda)
					WHERE tbl_revenda_compra.fabrica = $login_fabrica ";

			if( $busca == "codigo" )
			{
				$sql .= " AND tbl_revenda.cnpj like '%$q%' ";
			}
			else
			{
				$sql .= " AND UPPER(tbl_revenda.nome) ilike UPPER('%$q%') ";
			}

			$res = pg_exec($con, $sql);

			if( pg_numrows ($res) > 0 )
			{
				for( $i=0; $i<pg_numrows ($res); $i++ )
				{
					$revenda = trim(pg_result($res, $i, revenda));
					$cnpj    = trim(pg_result($res, $i, cnpj));
					$nome    = trim(pg_result($res, $i, nome));
					echo "$revenda|$cnpj|$nome";
					echo "\n";
				}
			}
		}

		if( $tipo_busca == "posto" )
		{
			$sql = "SELECT tbl_posto.posto, tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto, tbl_posto_fabrica.nome_fantasia
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

			if( $tipo_busca == "codigo" )
			{
				$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
			}
			else
			{
				$sql .= "  AND (
								UPPER(tbl_posto.nome) like UPPER('%$q%')
							OR  UPPER(tbl_posto_fabrica.nome_fantasia) like UPPER('%$q%')
								)";
			}

			$res = pg_exec($con, $sql);

			if( pg_numrows ($res) > 0 )
			{
				for( $i=0; $i<pg_numrows ($res); $i++ )
				{
					$posto         = trim(pg_result($res, $i, posto));
					$cnpj          = trim(pg_result($res, $i, cnpj));
					$nome          = trim(pg_result($res, $i, nome));
					$codigo_posto  = trim(pg_result($res, $i, codigo_posto));
					$nome_fantasia = trim(pg_result($res, $i, nome_fantasia));
					echo "$posto|$cnpj|$codigo_posto|$nome|$nome_fantasia";
					echo "\n";
				}
			}
		}

		if( $tipo_busca == "mapa_cidade" )
		{
			$sql = "SELECT      DISTINCT tbl_posto.cidade
					FROM        tbl_posto_fabrica
					JOIN tbl_posto using(posto)
					WHERE       tbl_posto_fabrica.fabrica = $login_fabrica
					AND         tbl_posto.cidade LIKE UPPER('%$q%')
					ORDER BY    tbl_posto.cidade";

			$res = pg_exec($con, $sql);

			if( pg_numrows ($res) > 0 )
			{
				for( $i=0; $i<pg_numrows ($res); $i++ )
				{
					$mapa_cidade = trim(pg_result($res, $i, cidade));
					echo "$mapa_cidade";
					echo "\n";
				}
			}
		}
	}
	exit;
}

$title       = "Atendimento Call-Center";
$layout_menu = 'callcenter';

include 'funcoes.php';

function converte_data($date)
{
	$date  = explode('/', $date);
	$date2 = $date[2].'-'.$date[1].'-'.$date[0];
	if( sizeof($date) == 3 ) return $date2;
	else return false;
}

function acentos1($texto)
{
	$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","ñ","Ñ" );
	$array2 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" ,"á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç","ñ","ñ");
	return str_replace( $array1, $array2, $texto );
}

function acentos3($texto)
{
	$array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","ñ","Ñ" );
	$array2 = array("A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","N","N" );
	return str_replace( $array1, $array2, $texto );
}

$btn_acao = $_POST['btn_acao'];
$msg_erro = "";

if( strlen($btn_acao) > 0 )
{
	$callcenter        = $_POST['callcenter'];
	$hd_chamado        = $callcenter;
	$tab_atual         = $_POST['tab_atual'];
	$categoria         = $_POST['categoria'];
	$tab_atual         = $categoria;
	// $status_interacao   = $_POST['status_interacao'];
	// campo será buscado através da tabela tbl_hd_situacao
	$hd_situacao       = $_POST['hd_situacao'];
	$hd_motivo_ligacao = $_POST['hd_motivo_ligacao'];

	if( strlen($hd_situacao) == 0 )
	{
		$msg_erro .= "Por favor selecionar a situação do chamado.<br>";
	}
	else
	{
		$sql = "SELECT descricao, resolvido
				FROM tbl_hd_situacao
				WHERE fabrica     = $login_fabrica
				AND   hd_situacao = $hd_situacao
				ORDER BY descricao ";

		$res = pg_exec($con,$sql);

		if( pg_numrows($res) > 0 )
		{
			$status_resolvido = pg_result($res, 0, resolvido);

			if( $status_resolvido == 't' )
			{
				$status_interacao = 'Resolvido';
			}else{
				$status_interacao = 'Aberto';
			}
		}
		else
		{
			$msg_erro .= "Por favor selecionar a situação do chamado.<br>";
		}
	}

	if( strlen($hd_motivo_ligacao) == 0 )
	{
		$msg_erro .= "Por favor selecionar o motivo da ligação.<br>";
	}

	$transferir      = $_POST['transferir'];
	$chamado_interno = $_POST['chamado_interno'];
	$envia_email     = $_POST['envia_email'];

	if( strlen($envia_email) == 0 ){
		$xenvia_email = "'f'";
	}else{
		$xenvia_email = "'t'";
	}

	if( strlen($chamado_interno) > 0   ){ $xchamado_interno   = "'t'"; }else{ $xchamado_interno = "'f'"; }
	if( strlen($transferir) == 0       ){ $xtransferir        = $login_admin; }else{ $xtransferir = $transferir; }
	if( strlen($status_interacao) > 0  ){ $xstatus_interacao  = "'".$status_interacao."'"; }
	if( strlen($hd_situacao) > 0       ){ $xhd_situacao       = $hd_situacao; }
	if( strlen($hd_motivo_ligacao) > 0 ){ $xhd_motivo_ligacao = $hd_motivo_ligacao; }
	if( strlen($tab_atual) == 0 and $login_fabrica == 25 ){ $tab_atual = "extensao"; }
	if( strlen($tab_atual) == 0 and $login_fabrica <> 25 ){ $tab_atual = "reclamacao_produto"; }

	if( strlen(trim($_POST['consumidor_revenda'])) > 0 ){
		$xconsumidor_revenda = "'".trim($_POST['consumidor_revenda'])."'";
	}else{
		$xconsumidor_revenda = "'C'";
	}

	$xorigem             = "'".trim($_POST['origem'])."'";
	$receber_informacoes = $_POST['receber_informacoes'];
	$hora_ligacao        = $_POST['hora_ligacao'];
	
	if( strlen($hora_ligacao) == 0 ){ $xhora_ligacao = "null"; }else{ $xhora_ligacao = "'$hora_ligacao".":00'"; }
	
	$defeito_reclamado = $_POST['defeito_reclamado'];

	if( ($login_fabrica == 11 and strlen($callcenter) == 0) or $login_fabrica <> 11 )
	{
		$consumidor_nome           = trim($_POST['consumidor_nome']);
		$cliente                   = trim($_POST['cliente']);
		$consumidor_cpf            = trim($_POST['consumidor_cpf']);
		$consumidor_cpf            = str_replace("/", "", $consumidor_cpf);
		$consumidor_cpf            = str_replace("-", "", $consumidor_cpf);
		$consumidor_cpf            = str_replace(".", "", $consumidor_cpf);
		$consumidor_cpf            = str_replace(",", "", $consumidor_cpf);
		if( strlen($consumidor_cpf) == 11 ){
			$consumidor_cpf = substr($consumidor_cpf, 0, 3) .".". substr($consumidor_cpf, 3, 3) .".". substr($consumidor_cpf, 6, 3) ."-". substr($consumidor_cpf, 9, 2);
		}
		$consumidor_rg             = trim($_POST['consumidor_rg']);
		$consumidor_rg             = str_replace("/", "",$consumidor_rg);
		$consumidor_rg             = str_replace("-", "",$consumidor_rg);
		$consumidor_rg             = str_replace(".", "",$consumidor_rg);
		$consumidor_rg             = str_replace(",", "",$consumidor_rg);
		$consumidor_email          = trim($_POST['consumidor_email']);
		$consumidor_fone           = trim($_POST['consumidor_fone']);
		$consumidor_fone           = str_replace("'", "",$consumidor_fone);
		$consumidor_fone2          = trim($_POST['consumidor_fone2']);
		$consumidor_fone2          = str_replace("'", "",$consumidor_fone2);
		$consumidor_cep            = trim($_POST['consumidor_cep']);
		$consumidor_cep            = str_replace("-", "",$consumidor_cep);
		$consumidor_cep            = str_replace(".", "",$consumidor_cep);
		$consumidor_endereco       = trim($_POST['consumidor_endereco']);
		$consumidor_numero         = trim($_POST['consumidor_numero']);
		$consumidor_numero         = str_replace("'", "",$consumidor_numero);
		$consumidor_complemento    = trim($_POST['consumidor_complemento']);
		$consumidor_bairro         = trim($_POST['consumidor_bairro']);
		$consumidor_cidade         = trim(strtoupper($_POST['consumidor_cidade']));
		$consumidor_estado         = trim(strtoupper($_POST['consumidor_estado']));
		$origem                    = $_POST['origem'];
		$consumidor_revenda        = $_POST['consumidor_revenda'];

		if( strlen($consumidor_nome) == 0 ){ $xconsumidor_nome  = "null";  }else{ $xconsumidor_nome = "'".$consumidor_nome."'";}
		if( strlen($consumidor_cpf) == 0 ){ $xconsumidor_cpf   = "null";  }else{ $xconsumidor_cpf   = "'".$consumidor_cpf."'";}
		if( strlen($consumidor_rg) == 0 ){ $xconsumidor_rg    = "null";  }else{ $xconsumidor_rg    = "'".$consumidor_rg."'";}
		if( strlen($consumidor_email) == 0 ){ $xconsumidor_email = "null";  }else{ $xconsumidor_email = "'".$consumidor_email."'";}
		if( strlen($consumidor_fone) == 0 ){ $xconsumidor_fone = "null";  }else{ $xconsumidor_fone  = "'".$consumidor_fone."'";}
		if( strlen($consumidor_fone2) == 0 ){ $xconsumidor_fone2 = "null";  }else{ $xconsumidor_fone2  = "'".$consumidor_fone2."'";}
		if( strlen($consumidor_cep) == 0 ){ $xconsumidor_cep ="null"; }else{ $xconsumidor_cep      = "'".$consumidor_cep."'";}
		if( strlen($consumidor_endereco) == 0 ){ $xconsumidor_endereco ="null"; }else{ $xconsumidor_endereco = "'".$consumidor_endereco."'";}
		if( strlen($consumidor_numero) == 0 ){ $xconsumidor_numero ="null"; }else{ $xconsumidor_numero   = "'".$consumidor_numero."'";}
		if( strlen($consumidor_complemento) == 0 ){ $xconsumidor_complemento ="null"; }else{ $xconsumidor_complemento = "'".$consumidor_complemento."'"; }
		if( strlen($consumidor_bairro) == 0 ){ $xconsumidor_bairro  = "null"; }else{ $xconsumidor_bairro = "'".$consumidor_bairro."'";}
		if( strlen($consumidor_cidade) == 0 ){ $xconsumidor_cidade  = "null"; }else{ $xconsumidor_cidade = "'".$consumidor_cidade."'";}
		if( strlen($consumidor_estado) == 0 ){ $xconsumidor_estado  = "null"; }else{ $xconsumidor_estado = "'".$consumidor_estado."'";}

		if( $login_fabrica == 3 ) // HD 48900
		{
			if(strlen($consumidor_nome)==0){
				$msg_erro .= "Por favor inserir o nome do consumidor.<br>";
			}

			if(strlen($consumidor_fone)==0){
				$msg_erro .= "Por favor inserir o telefone do consumidor.<br>";
			}

			if(strlen($consumidor_estado)==0){
				$msg_erro .= "Por favor selecione o estado.<br>";
			}

			if(strlen($consumidor_cidade)==0){
				$msg_erro .= "Por favor inserir a cidade.<br>";
			}

			if(strlen(trim($_POST['consumidor_revenda'])) ==0) {
				$msg_erro .= "Por favor selecione o tipo (Consumidor ou Revenda).<br>";
			}

			if(strlen(trim($_POST['origem'])) ==0) {
				$msg_erro .= "Por favor selecione a origem.<br>";
			}
		}
		else
		{
			if(strlen($consumidor_nome)>0 and strlen($consumidor_estado)==0){
				$msg_erro .= "Por favor selecione o estado.<br>";
			}

			if(strlen($consumidor_nome)>0 and strlen($consumidor_cidade)==0){
				$msg_erro .= "Por favor inserir a cidade.<br>";
			}
		}
	}

	$abre_os            = trim($_POST['abre_os']);
	$imprimir_os        = trim($_POST['imprimir_os']);
	$resposta           = trim($_POST['resposta']);
	$posto_tab          = trim(strtoupper($_POST['posto_tab']));
	$codigo_posto_tab   = trim(strtoupper($_POST['codigo_posto_tab']));
	$posto_nome_tab     = trim(strtoupper($_POST['posto_nome_tab']));
	$posto_endereco_tab = trim(strtoupper($_POST['posto_endereco_tab']));
	$posto_cidade_tab   = trim(strtoupper($_POST['posto_cidade_tab']));
	$posto_estado_tab   = trim(strtoupper($_POST['posto_estado_tab']));
	$revenda_nome       = trim($_POST['revenda_nome']);
	$revenda_endereco   = trim($_POST['revenda_endereco']);
	$revenda_nro        = trim($_POST['revenda_nro']);
	$revenda_cmpto      = trim($_POST['revenda_cmpto']);
	$revenda_bairro     = trim($_POST['revenda_bairro']);
	$revenda_city       = trim($_POST['revenda_city']);
	$revenda_uf         = trim($_POST['revenda_uf']);
	$revenda_fone       = trim($_POST['revenda_fone']);
	$hd_extra_defeito   = trim($_POST['hd_extra_defeito']);
	$faq_situacao       = trim($_POST['faq_situacao']);
	$reclama_posto      = trim($_POST['tipo_reclamacao']);
	$tipo_registro      = trim($_POST['tipo_registro']);

	if( strlen($resposta) == 0 ){ $xresposta  = "null"; }else{ $xresposta = "'".$resposta."'";}

	if( strlen($receber_informacoes) > 0 ){
		$xreceber_informacoes = "'$receber_informacoes'";
	}else{
		$xreceber_informacoes = "'f'";
	}

	if( strlen($tipo_registro) == 0 ){
		$msg_erro .="Por favor selecione o tipo de registro";
	}

	if( $tab_atual == "extensao" )
	{
		$produto_referencia = $_POST['produto_referencia_es'];
		$produto_nome       = $_POST['produto_nome_es'];
		$reclamado          = trim($_POST['reclamado_es']);

		if( strlen($reclamado) == 0 ){
			$xreclamado = "null";
		}else{
			$xreclamado = "'".$reclamado."'";
		}

		# $xserie = $_POST['serie_es'];
		# if(strlen($_POST["serie"])>0) $xserie = $_POST['serie'];

		$xserie = $_POST['serie'];

		if( strlen($_POST["serie_es"]) > 0 ) $xserie = $_POST['serie_es'];

		// HD 12749
		if( strlen($produto_referencia) == 0 ){
			$msg_erro.=" Insira a referência do produto\n ";
		}

		if(strlen($produto_nome) == 0){
			$msg_erro.=" Insira nome do produto\n ";
		}

		if(strlen($xserie) == 0){
			$msg_erro.=" Insira o número de série do produto\n ";
		}

		$es_id_numeroserie = $_POST['es_id_numeroserie'];
		$es_revenda_cnpj   = $_POST['es_revenda_cnpj'];
		$es_revenda        = $_POST['es_revenda'];

		if( strlen($es_revenda) == 0 ){
			$xes_revenda = "NULL";
		}else{
			$xes_revenda = "'".$es_revenda."'";
		}

		$es_nota_fiscal    = $_POST['es_nota_fiscal'];

		if( strlen($es_nota_fiscal) == 0 ){
			$xes_nota_fiscal = "NULL";
		}else{
			$xes_nota_fiscal = "'".$es_nota_fiscal."'";
		}

		$es_data_compra     = $_POST['es_data_compra'];

		if( strlen($es_data_compra) == 0 ){
			$xes_data_compra = "NULL";
		}else{
			$xes_data_compra = "'".converte_data($es_data_compra)."'";
		}

		$es_municipiocompra  = $_POST['es_municipiocompra'];

		if( strlen($es_municipiocompra) == 0 ){
			$xes_municipiocompra = "NULL";
		}else{
			$xes_municipiocompra = "'".$es_municipiocompra."'";
		}

		$es_estadocompra     = $_POST['es_estadocompra'];

		if( strlen($es_estadocompra) == 0 ){
			$xes_estadocompra = "NULL";
		}else{
			$xes_estadocompra = "'".$es_estadocompra."'";
		}

		$es_data_nascimento  = $_POST['es_data_nascimento'];

		if( strlen($es_data_nascimento) == 0 ){
			$xes_data_nascimento = "NULL";
		}else{
			$xes_data_nascimento = "'".converte_data($es_data_nascimento)."'";
		}

		$es_estadocivil      = $_POST['es_estadocivil'];

		if( strlen($es_estadocivil) == 0 ){
			$xes_estadocivil = "NULL";
		}else{
			$xes_estadocivil = "'".$es_estadocivil."'";
		}

		$es_sexo             = $_POST['es_sexo'];

		if( strlen($es_sexo) == 0 ){
			$xes_sexo = "NULL";
		}else{
			$xes_sexo = "'".$es_sexo."'";
		}

		$es_filhos           = $_POST['es_filhos'];

		if( strlen($es_filhos) == 0 ){
			$xes_filhos = "NULL";
		}else{
			$xes_filhos = "'".$es_filhos."'";
		}

		$es_fonecomercial    = $_POST['es_fonecomercial'];

		if( strlen($es_fonecomercial) == 0 ){
			$xes_dddcomercial  = " NULL ";
			$xes_fonecomercial = "NULL";
		}else{
			$xes_dddcomercial  = "'".substr($es_fonecomercial, 1, 2)."'";
			$xes_fonecomercial = "'".substr($es_fonecomercial, 5, 9)."'";
		}

		$es_celular          = $_POST['es_celular'];

		if( strlen($es_celular) == 0 ){
			$xes_dddcelular = " NULL ";
			$xes_celular    = "NULL";
		}else{
			$xes_dddcelular = "'".substr($es_celular, 1, 2)."'";
			$xes_celular    = "'".substr($es_celular, 5, 9)."'";
		}

		$es_preferenciamusical = $_POST['es_preferenciamusical'];

		if( strlen($es_preferenciamusical) == 0 ){
			$xes_preferenciamusical = "NULL";
		}else{
			$xes_preferenciamusical = "'".$es_preferenciamusical."'";
		}
	}

	// if($tab_atual == "reclamacao_produto"){
		$produto_referencia = $_POST['produto_referencia'];
		$produto_nome       = $_POST['produto_nome'];
		$reclamado          = trim($_POST['reclamado_produto']);
		$xserie             = $_POST['serie'];

		if( strlen($reclamado) == 0 ){
			$xreclamado = "null"; //$msg_erro = "Insira a reclamação";
		}else{
			$xreclamado = "'".$reclamado."'";
		}
	// }

	if( $tab_atual == "reclamacao_at" ){
		$reclamado   = trim($_POST['reclamado_at']);
		$xserie      = $_POST['serie'];

		if( strlen($reclamado) == 0 ){
			$msg_erro = "Insira a reclamação";
		}else{
			$xreclamado = "'".$reclamado."'";
		}
	}

	$posto_nome       = $_POST['posto_nome'];
	$codigo_posto     = $_POST['codigo_posto'];

	if( $login_fabrica == 2 AND $reclama_posto <> 'reclamacao_at' ){
		$codigo_posto = "";
	}

	/*
	if ($login_fabrica == 2 AND $reclama_posto == 'reclamacao_at'){
		if(strlen($codigo_posto) == 0){
			$msg_erro .= "Ao selecionar Reclamação da Assitência Técnica é <br/>
			    obrigatório informar qual foi a assistência que gerou a reclamação.";
		}
	}*/

	if( strlen($codigo_posto_tab) > 0 )
	{
		$sql = "SELECT posto
				from tbl_posto_fabrica
				where codigo_posto='$codigo_posto_tab'
				and fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);

		if( pg_numrows($res) > 0 )
		{
			$mr_codigo_posto = pg_result($res, 0, 0);

			$sqlMr = "SELECT endereco, numero, cidade, estado
					FROM tbl_posto
					WHERE posto = $mr_codigo_posto";
			$resMr = pg_exec($con,$sqlMr);

			if( pg_numrows($resMr) > 0 )
			{
				$endereco_posto_tab = pg_result($resMr, 0, endereco);
				$numero_posto_tab   = pg_result($resMr, 0, numero);
				$posto_endereco_tab = "$endereco_posto_tab, $numero_posto_tab";
				$posto_cidade_tab   = pg_result($resMr, 0, cidade);
				$posto_estado_tab   = pg_result($resMr, 0, estado);
			}
		}
	}

	if( strlen($codigo_posto) == 0 )
	{
		$xcodigo_posto = "null";
	}
	else
	{
		$sql = "SELECT posto
				from tbl_posto_fabrica
				where codigo_posto='$codigo_posto'
				and fabrica = $login_fabrica";
		$res = pg_exec($con, $sql);

		if( pg_numrows($res) > 0 ){
			$xcodigo_posto = pg_result($res, 0, 0);
		}else{
			$xcodigo_posto = "null";
		}
	}

	$os = trim($_POST['os']);

	if( strlen($os) == 0 ){
		$xos = "null";
	}else{
		$sql = "SELECT os from tbl_os where sua_os='$os' and fabrica=$login_fabrica"; //echo $sql;
		$res = pg_exec($con, $sql);

		if( pg_numrows($res) > 0 ){
			$xos = pg_result($res, 0, 0);
		}else{
			$msg_erro .= "OS informada não encontrada no sistema";
		}
	}

	if( $tab_atual == "reclamacao_empresa" ){
		$reclamado = trim($_POST['reclamado_empresa']);
		if( strlen($reclamado) == 0 ){
			$msg_erro = "Insira a reclamação";
		}else{
			$xreclamado = "'".$reclamado."'";
		}
	}

	if( $tab_atual == "reclamacoes" )
	{
		$reclamado      = trim($_POST['reclamado']);
		$tipo_reclamado = trim($_POST['tipo_reclamacao']);

		if( strlen($reclamado) == 0 ){
			$msg_erro = "Insira a reclamação";
		}else{
			$xreclamado = "'".$reclamado."'";
		}
	}

	if( $tab_atual == "duvida_produto" ){
		$produto_referencia = $_POST['produto_referencia_duvida'];
		$produto_nome       = $_POST['produto_nome_duvida'];
		$xserie             = $_POST['troca_serie_duvida'];
	}

	if( $tab_atual == "sugestao" )
	{
		$reclamado          = trim($_POST['reclamado_sugestao']);

		if( strlen($reclamado) == 0 ){
			$msg_erro .= "Insira a sugestão";
		}else{
			$xreclamado = "'".$reclamado."'";
		}
	}

	if( $tab_atual == "assistencia" )
	{
		$produto_referencia = $_POST['produto_referencia_pa'];
		$produto_nome       = $_POST['produto_nome_pa'];
		$xserie             = $_POST['serie_pa'];
		$reclamado          = trim($_POST['reclamado_pa']);

		if( strlen($reclamado) == 0 ){
			$xreclamado = "null";
		}else{
			$xreclamado = "'".$reclamado."'";
		}
	}

	if( $tab_atual == "procon" ){
		$reclamado = trim($_POST['reclamado_procon']);

		if( strlen($reclamado) == 0 ){
			$xreclamado = "null";
		}else{
			$xreclamado = "'".$reclamado."'";
		}
	}

	if( $tab_atual == "garantia" ){
		$produto_referencia = $_POST['produto_referencia_garantia'];
		$produto_nome       = $_POST['produto_nome_garantia'];
		$xserie             = $_POST['serie_garantia'];
		$reclamado          = trim($_POST['reclamado_produto_garantia']);

		if( strlen($reclamado) == 0 ){
			$xreclamado = "null";
		}else{
			$xreclamado = "'".$reclamado."'";
		}
	}

	if( $tab_atual == "troca_produto" ){
		$produto_referencia = $_POST['troca_produto_referencia'];
		$produto_nome       = $_POST['troca_produto_nome'];
		$reclamado          = trim($_POST['troca_produto_descricao']);
		$xserie             = $_POST['troca_serie'];

		if( strlen($reclamado) == 0 ){
			$xreclamado = "null"; //$msg_erro = "Insira a reclamação";
		}else{
			$xreclamado = "'".$reclamado."'";
		}

		if( strlen($produto_referencia) == 0 AND strlen($produto_nome) == 0 ){
			$msg_erro = "Por favor escolha o produto.";
		}
	}

	$xrevenda      = "null";
	$xrevenda_nome = "''";

	if( $tab_atual == "onde_comprar" )
	{
		$revenda          = $_POST['revenda'];
		$revenda_cnpj     = $_POST['revenda_cnpj'];
		$revenda_nome     = trim($_POST['revenda_nome']);
		$revenda_endereco = trim($_POST['revenda_endereco']);
		$revenda_nro      = trim($_POST['revenda_nro']);
		$revenda_cmpto    = trim($_POST['revenda_cmpto']);
		$revenda_bairro   = trim($_POST['revenda_bairro']);
		$revenda_city     = trim($_POST['revenda_city']);
		$revenda_uf       = trim($_POST['revenda_uf']);
		$revenda_fone     = trim($_POST['revenda_fone']);
		$xrevenda         = "$revenda";
		$xrevenda_nome    = "'$xrevenda_nome'";
	}

	if( $tab_atual == "ressarcimento" )
	{
		$banco             = trim($_POST['banco']);
		$agencia           = trim($_POST['agencia']);
		$contay            = trim($_POST['contay']);
		$nomebanco         = trim($_POST['nomebanco']);
		$tipo_conta        = trim($_POST['tipo_conta']);
		$favorecido_conta  = trim($_POST['favorecido_conta']);
		$cpf_conta         = trim($_POST['cpf_conta']);
		$reclamado         = trim($_POST['obs_ressarcimento']);
		$valor_produto     = trim($_POST['valor_produto']);
		$valor_inpc        = trim($_POST['valor_inpc']);
		$valor_corrigido   = trim($_POST['valor_corrigido']);
		$reclamado         = trim($_POST['troca_produto_descricao']);
		$data_pagamento    = trim($_POST['data_pagamento']);
		$procon            = trim($_POST['procon']);
		$numero_processo   = trim($_POST['numero_processo']);
		$valor_produto     = str_replace(",", ".", $valor_produto);
		$valor_inpc        = str_replace(",", ".", $valor_inpc);
		$valor_corrigido   = str_replace(",", ".", $valor_corrigido);

		if(strlen($banco)==0){
			$xbanco = "null";
		}else{
			$xbanco = "'".$banco."'";
		}

		if(strlen($agencia)==0){
			$xagencia = "null";
		}else{
			$xagencia = "'".$agencia."'";
		}

		if(strlen($contay)==0){
			$xcontay = "null";
		}else{
			$xcontay = "'".$contay."'";
		}

		if(strlen($nomebanco)==0){
			$xnomebanco = "null";
		}else{
			$xnomebanco = "'".$nomebanco."'";
		}

		if(strlen($tipo_conta)==0){
			$xtipo_conta = "null";
		}else{
			$xtipo_conta = "'".$tipo_conta."'";
		}

		if(strlen($favorecido_conta)==0){
			$xfavorecido_conta = "null";
		}else{
			$xfavorecido_conta = "'".$favorecido_conta."'";
		}

		if(strlen($cpf_conta)==0){
			$xcpf_conta = "null";
		}else{
			$xcpf_conta = "'".$cpf_conta."'";
		}

		if(strlen($obs_conta)==0){
			$xobs_conta = "null";
		}else{
			$xobs_conta = "'".$obs_conta."'";
		}

		if(strlen($data_pagamento)==0){
			$xdata_pagamento = "null";
		}else{
			$xdata_pagamento = "'".$data_pagamento."'";
		}
	}

	if( $tab_atual == "sedex_reverso" )
	{
		$troca_produto_referencia = trim($_POST['troca_produto_referencia']);
		$troca_produto_nome       = trim($_POST['troca_produto_nome']);
		$reclamado                = trim($_POST['troca_observacao']);
		$numero_objeto            = trim($_POST['numero_objeto']);
		$nota_fiscal_saida        = trim($_POST['nota_fiscal_saida']);
		$data_nf_saida            = trim($_POST['data_nf_saida']);
		$data_retorno_produto     = trim($_POST['data_retorno_produto']);
		$procon                   = trim($_POST['procon2']);
		$numero_processo          = trim($_POST['numero_processo2']);

		if( strlen($nota_fiscal_saida) == 0 ){
			$xnota_fiscal_saida = "null";
		}else{
			$xnota_fiscal_saida = "'".$nota_fiscal_saida."'";
		}

		if(strlen($data_nf_saida)==0){
			$xdata_nf_saida = "null";
		}else{
			$xdata_nf_saida = "'".converte_data($data_nf_saida)."'";
		}

		if(strlen($data_retorno_produto)==0){
			$xdata_retorno_produto = "null";
		}else{
			$xdata_retorno_produto = "'".converte_data($data_retorno_produto)."'";
		}

		if(strlen($numero_objeto)==0){
			$xnumero_objeto = "null";
		}else{
			$xnumero_objeto = "'".$numero_objeto."'";
		}

		if(strlen($produto_referencia) == 0 AND strlen($produto_nome) == 0){
			$msg_erro = "Por favor escolha o produto.";
		}
	}

	if(strlen($valor_produto)==0){
		$xvalor_produto = "null";
	}else{
		$xvalor_produto = $valor_produto;
	}

	if(strlen($valor_inpc)==0){
		$xvalor_inpc = "null";
	}else{
		$xvalor_inpc = $valor_inpc;
	}

	if(strlen($valor_corrigido)==0){
		$xvalor_corrigido = "null";
	}else{
		$xvalor_corrigido = $valor_corrigido;
	}

	if(strlen($numero_processo)==0){
		$xnumero_processo = "null";
	}else{
		$xnumero_processo = "'".$numero_processo."'";
	}

	if (strlen($cliente)==0){
		$cliente = "null";
	}

	if(strlen($faq_situacao) > 0){
		$produto_referencia = $_POST['produto_referencia'];
	}

	if( strlen($defeito_reclamado) == 0 ){ $xdefeito_reclamado  = "null"; }else{ $xdefeito_reclamado = $defeito_reclamado;}
	if( strlen($reclamado) ==0          ){ $xreclamado          = "null"; }else{ $xreclamado         = "'".$reclamado."'";}

	if( strlen($produto_referencia) > 0 )
	{
		$sql = "SELECT tbl_produto.produto
					FROM  tbl_produto
					join  tbl_linha on tbl_produto.linha = tbl_linha.linha
					WHERE tbl_produto.referencia = '$produto_referencia'
					and tbl_linha.fabrica = $login_fabrica
					limit 1";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con); //echo nl2br($sql)."<BR>";

		if( pg_numrows($res) > 0 ){
			$xproduto = pg_result($res, 0, 0);
		}else{
			$xproduto = "null";
		}
	}
	else
	{
		$xproduto = "null";
	}

	if( strlen($troca_produto_referencia) > 0)
	{
		$sql = "SELECT tbl_produto.produto
					FROM  tbl_produto
					JOIN  tbl_linha ON tbl_produto.linha = tbl_linha.linha
					WHERE tbl_produto.referencia = '$troca_produto_referencia'
					AND tbl_linha.fabrica = $login_fabrica
					LIMIT 1";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con); // echo nl2br($sql)."<BR>";
		
		if( pg_numrows($res) > 0 ){
			$xproduto_troca = pg_result($res, 0, 0);
		}else{
			$xproduto_troca = "null";
		}
	}
	else
	{
		$xproduto_troca = "null";
	}

	if( strlen($faq_situacao) > 0 ) // HD 45991
	{
		$sql = "INSERT INTO tbl_faq (
					situacao,
					produto
				) VALUES (
					'$faq_situacao',
					$xproduto
				);";

		$res = @pg_exec($con, $sql);
		$msg_erro = pg_errormessage($con);

		if( strlen($msg_erro) == 0 )
		{
			$sql = "SELECT email_cadastros FROM tbl_fabrica WHERE fabrica = $login_fabrica";
			$res = pg_exec($con, $sql);

			if( pg_numrows($res) > 0 )
			{
				$email_cadastros = pg_result($res, 0, email_cadastros);
				$admin_email     = "helpdesk@telecontrol.com.br";
				$remetente       = $admin_email;
				$destinatario    = $email_cadastros ;
				$assunto         = "Nova dúvida cadastrada";
				$mensagem        = "Prezado, <br> Foi cadastrada uma nova dúvida no sistema para o produto $produto_referencia:<br>  - $faq_situacao <br><br>Por favor, entre na aba <b>Cadastro - Perguntas Frequentes</b> para cadastrar causa e solução da mesma. <br>Att <br>Equipe Telecontrol";
				$headers         = "Return-Path: <$admin_email>\nFrom:".$remetente."\nContent-type: text/html\n";
				mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
			}
		}
	}

	# HD Chamado 13106 Bloqueia
	# HD Chamado 21419 DESBloqueia
	if( $login_fabrica == 25 AND strlen($xserie) > 0 AND 1 == 2 )
	{
		$sql = "SELECT tbl_hd_chamado_extra.hd_chamado
				FROM tbl_hd_chamado_extra
				JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado=tbl_hd_chamado_extra.hd_chamado
				WHERE tbl_hd_chamado.fabrica        = $login_fabrica
				AND   tbl_hd_chamado_extra.serie    = '$xserie' ";
				// AND   tbl_hd_chamado_extra.produto  = $xproduto

		if( strlen($callcenter) > 0 )
		{
			$sql .= " AND tbl_hd_chamado_extra.hd_chamado <> $callcenter ";
		}

		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);

		if( pg_numrows($res) > 0 )
		{
			$hd_chamado_serie = pg_result($res, 0, 0);
			$msg_erro        .= "Número de série $xserie já cadastrado anteriormente. Número do chamado: <a href='callcenter_interativo.php?callcenter=$hd_chamado_serie' target='_blank'>$hd_chamado_serie</a> ";
		}
	}

	if( strlen($xserie) == 0 ){ $xserie = "null"; }else{ $xserie = "'".$xserie."'"; }

	if( $login_fabrica == 11 ) // HD 45078
	{
		$xconsumidor_nome        = acentos1($xconsumidor_nome);
		$xconsumidor_nome        = acentos3($xconsumidor_nome);
		$xconsumidor_endereco    = acentos1($xconsumidor_endereco);
		$xconsumidor_endereco    = acentos3($xconsumidor_endereco);
		$xconsumidor_numero      = acentos1($xconsumidor_numero);
		$xconsumidor_numero      = acentos3($xconsumidor_numero);
		$xconsumidor_complemento = acentos1($xconsumidor_complemento);
		$xconsumidor_complemento = acentos3($xconsumidor_complemento);
		$xconsumidor_bairro      = acentos1($xconsumidor_bairro);
		$xconsumidor_bairro      = acentos3($xconsumidor_bairro);
		$xconsumidor_cidade      = acentos1($xconsumidor_cidade);
		$xconsumidor_cidade      = acentos3($xconsumidor_cidade);
		$xconsumidor_email       = acentos1($xconsumidor_email);
		$xconsumidor_email       = acentos3($xconsumidor_email);
	}

	/* INSERINDO */
	if( strlen($callcenter) == 0 )
	{
		if( strlen($msg_erro) == 0 )
		{
			$res = pg_exec($con,"BEGIN TRANSACTION");

			if( strlen($consumidor_nome) > 0 and strlen($consumidor_estado) > 0 and strlen($consumidor_cidade) > 0 )
			{
				$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$consumidor_cidade}')) AND UPPER(estado) = UPPER('{$consumidor_estado}')";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$cidade = pg_fetch_result($res, 0, "cidade");
				} else {
					$sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$consumidor_cidade}')) AND UPPER(estado) = UPPER('{$consumidor_estado}')";
					$res = pg_query($con, $sql);

					if (pg_num_rows($res) > 0) {
						$cidade_ibge        = pg_fetch_result($res, 0, "cidade");
						$cidade_estado_ibge = pg_fetch_result($res, 0, "estado");

						$sql = "INSERT INTO tbl_cidade (
									nome, estado
								) VALUES (
									'{$cidade_ibge}', '{$cidade_estado_ibge}'
								) RETURNING cidade";
						$res = pg_query($con, $sql);

						$cidade = pg_fetch_result($res, 0, "cidade");
					} else {
						$msg_erro .= "Cidade do consumidor não encontrada";
					}
				}
			}else{
				$msg_erro .= "Informe a cidade do consumidor";
			}
		}

		if( $tab_atual == 'reclamacoes' ){ $tab_atual = $tipo_reclamado; }

			if( strlen($msg_erro) == 0 and strlen($callcenter) == 0 )
			{
				$sql = "INSERT INTO tbl_hd_chamado (
							admin                 ,
							data                  ,
							status                ,
							atendente             ,
							fabrica_responsavel   ,
							titulo                ,
							categoria             ,
							fabrica
						)VALUES(
							$login_admin            ,
							current_timestamp       ,
							$xstatus_interacao      ,
							$login_admin            ,
							$login_fabrica          ,
							'Atendimento interativo',
							'$tab_atual'            ,
							$login_fabrica )";
				
				$res = pg_exec($con,$sql); //echo nl2br($sql)."<BR>";
				$msg_erro .= pg_errormessage($con);
				$res    = pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado')");
				$hd_chamado = pg_result ($res,0,0);
			}

			if( strlen($msg_erro)==0 and strlen($callcenter)==0 and $abre_os=='t' )
			{
				if( ($login_fabrica == 2) and (strlen($mr_codigo_posto) == 0) ){
					$msg_erro = "Para que a OS seja aberta é necessário escolher um posto";
				}

				if( ($login_fabrica == 2) and (strlen($mr_codigo_posto) > 0) ){
					$rat_codigo_posto = $xcodigo_posto;
					$xcodigo_posto = $mr_codigo_posto;
				}

				if( strlen($xcodigo_posto)==0 OR $xcodigo_posto=='null' ){
					$msg_erro = "Para que a OS seja aberta é necessário escolher um posto";
				}

				$xnota_fiscal = "'".$_POST["nota_fiscal"]."'";
				
				if( strlen($msg_erro) == 0 )
				{
					if( strlen($data_nf) == 0 ) $xdata_nf = "NULL";
					else $xdata_nf = "'".converte_data($data_nf)."'";

					/* A Britania nao quer abrir a OS pelo call-center quer somente pre-os.
					Então estaremos marcando na tbl_hd_chamado_extra o abre_os, e consultar no posto
					se existe um chamado call-center não resolvido (em aberto) com pedido de abertura de OS,
					isto será considerado pre-os */

					if( $login_fabrica != 3 )
					{
						$sql = "INSERT INTO tbl_os (
									posto              ,
									admin              ,
									fabrica            ,
									data_abertura      ,
									cliente            ,
									consumidor_nome    ,
									consumidor_cpf     ,
									consumidor_cidade  ,
									consumidor_estado  ,
									consumidor_fone    ,
									consumidor_celular ,
									consumidor_fone_comercial,
									consumidor_email   ,
									produto            ,
									serie              ,
									nota_fiscal        ,
									data_nf
								) VALUES (
									$xcodigo_posto                    ,
									$login_admin                      ,
									$login_fabrica                    ,
									CURRENT_DATE                      ,
									$cliente                          ,
									trim('$consumidor_nome')          ,
									trim('$consumidor_cpf')           ,
									trim('$consumidor_cidade')        ,
									trim('$consumidor_estado')        ,
									trim('$consumidor_fone')          ,
									trim('$consumidor_celular')       ,
									trim('$consumidor_fone_comercial'),
									trim('$consumidor_email')         ,
									$xproduto                         ,
									$xserie                           ,
									$xnota_fiscal                     ,
									$xdata_nf );";

						$res = @pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);

						$sql = "SELECT CURRVAL ('seq_os')";
						$res = pg_exec($con, $sql);
						$msg_erro .= pg_errormessage($con);
						$xos = pg_result($res, 0, 0);

						$os  = $xos;
						$sql = "SELECT fn_valida_os($xos, $login_fabrica)";
						$res = @pg_exec($con, $sql);
						$msg_erro .= @pg_errormessage($con);

						if( strpos($msg_erro, "CONTEXT:") ){
							$x = explode('CONTEXT:', $msg_erro);
							$msg_erro = $x[0];
						}

						if( strpos($msg_erro,"ERROR: ") !== false ){
							$x = explode('ERROR: ', $msg_erro);
							$msg_erro = $x[1];
						}
					}
				}
			}

			$data_nf = $_POST["data_nf"] ;

			if( strlen($data_nf) == 0 ) $xdata_nf = "NULL";
			else $xdata_nf = "'".converte_data($data_nf)."'";

			if( strlen($xdata_nf) > 0 ){
				$sql_nf = "SELECT $xdata_nf > '2005-01-01' AS valida_data";
				$res    = pg_exec($con,$sql_nf);
				$valida_data = pg_result($res, 0, valida_data);
				if( $valida_data == 'f' ) $msg_erro =" Data da nota muito antiga.";
			}

			if( strlen($msg_erro)==0 and strlen($callcenter) == 0 )
			{
				if( isset($rat_codigo_posto) ){ $xcodigo_posto = $rat_codigo_posto; }

				$xnota_fiscal = "'".$_POST["nota_fiscal"]."'";

				if( strlen($abre_os) == 0 ){ $abre_os = 'f'; }

				$xabre_os = "'".$abre_os."'";

				$sql = "INSERT INTO tbl_hd_chamado_extra(
							hd_chamado           ,
							reclamado            ,
							defeito_reclamado    ,
							serie                ,
							hora_ligacao         ,
							produto              ,
							posto                ,
							os                   ,
							receber_info_fabrica ,
							consumidor_revenda   ,
							origem               ,
							revenda              ,
							revenda_nome         ,
							data_nf              ,
							nota_fiscal          ,
							nome                 ,
							endereco             ,
							numero               ,
							complemento          ,
							bairro               ,
							cep                  ,
							fone                 ,
							fone2                ,
							email                ,
							cpf                  ,
							rg                   ,
							cidade               ,
							abre_os              ,
							defeito_reclamado_descricao,
							numero_processo      ,
							tipo_registro        ,
							hd_situacao          ,
							hd_motivo_ligacao
						)VALUES(
							$hd_chamado                    ,
							$xreclamado                    ,
							$xdefeito_reclamado            ,
							$xserie                        ,
							$xhora_ligacao                 ,
							$xproduto                      ,
							$xcodigo_posto                 ,
							$xos                           ,
							$xreceber_informacoes          ,
							$xconsumidor_revenda           ,
							$xorigem                       ,
							$xrevenda                      ,
							$xrevenda_nome                 ,
							$xdata_nf                      ,
							$xnota_fiscal                  ,
							upper($xconsumidor_nome)       ,
							upper($xconsumidor_endereco)   ,
							upper($xconsumidor_numero)     ,
							upper($xconsumidor_complemento),
							upper($xconsumidor_bairro)     ,
							upper($xconsumidor_cep)        ,
							upper($xconsumidor_fone)       ,
							upper($xconsumidor_fone2)      ,
							upper($xconsumidor_email)      ,
							upper($xconsumidor_cpf)        ,
							upper($xconsumidor_rg)         ,
							$cidade                        ,
							$xabre_os                      ,
							'$hd_extra_defeito'            ,
							$xnumero_processo              ,
							'$tipo_registro'               ,
							$xhd_situacao                  ,
							$xhd_motivo_ligacao );";

					$res = pg_exec($con, $sql); # echo nl2br($sql)."<BR>";
					$msg_erro .= pg_errormessage($con); // $msg_erro = "aaa<br>"; // $msg_erro .= $xrevenda;

					if( $xstatus_interacao == "'Resolvido'" AND $login_fabrica <> 6 )
					{
						$sql = "INSERT INTO tbl_hd_chamado_item(
									hd_chamado   ,
									data         ,
									comentario   ,
									admin        ,
									interno      ,
									status_item  ,
									enviar_email
								)VALUES(
									$hd_chamado       ,
									current_timestamp ,
									'Resolvido'       ,
									$login_admin      ,
									$xchamado_interno ,
									$xstatus_interacao,
									$xenvia_email )";
								
						$res = pg_exec($con, $sql); //echo $sql;
						$msg_erro .= pg_errormessage($con);

						if( strlen($msg_erro) == 0 )
						{
							$sql = "SELECT fn_callcenter_dias_interacao($hd_chamado,$login_fabrica)";
							$res = pg_exec($con,$sql);
							$msg_erro .= pg_errormessage($con);

							$sql = "SELECT fn_callcenter_dias_aberto($hd_chamado,$login_fabrica);";
							$res = pg_exec($con,$sql);
							$msg_erro .= pg_errormessage($con);

							$sql = "SELECT fn_callcenter_intervalo_interacao($hd_chamado,$login_fabrica);";
							$res = pg_exec($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}
					}

					// IGOR - HD: 10441 QUANDO FOR INDICADO UM POSTO AUTORIZADO, DEVE-SE INSERIR UMA INTERAÇÃO NO CHAMADO
					if( strlen($posto_tab) > 0 )
					{
						$comentario = "Indicação do posto mais próximo do consumidor: <br>
										Código: $codigo_posto_tab <br>
										Nome: $posto_nome_tab<br>
										Endereço: $posto_endereco_tab <br>
										Cidade: $posto_cidade_tab <br>
										Estado: $posto_estado_tab";

						if( strlen($xos)>0 AND $abre_os=='t' )
						{
							$sql = "SELECT sua_os FROM tbl_os WHERE os = $xos AND fabrica = $login_fabrica";

							$res = @pg_exec($con, $sql);
							$msg_erro .= pg_errormessage($con);

							if( @pg_numrows($res)>0 ){
								$xsua_os = pg_result($res,0,0);
							}

							if( $login_fabrica == 3 ){
								$comentario .= "<Br><br> Foi disponibilizado para o posto a Pré-Ordem de Serviço.";
							}else{
								$comentario .= "<Br><br> Foi aberta a Ordem de Serviço nº $xsua_os";
							}
						}

						$sql = "INSERT INTO tbl_hd_chamado_item(
								hd_chamado   ,
								data         ,
								comentario   ,
								admin        ,
								interno      ,
								status_item
								)values(
								$hd_chamado      ,
								current_timestamp,
								'$comentario'    ,
								$login_admin     ,
								'f',
								$xstatus_interacao )";

						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}
				}

				/* HD 37805 */
				if( $tab_atual == "ressarcimento" and strlen($msg_erro) == 0 )
				{
					if( strlen($xdata_nf) == 0 OR $xdata_nf == 'NULL' )
					{
						$msg_erro .= "Informe a data da Nota fiscal.";
					}

					$sql = "SELECT hd_chamado
							FROM tbl_hd_chamado_extra_banco
							WHERE hd_chamado = $hd_chamado ";
					$resx = @pg_exec($con,$sql);

					if( @pg_numrows($resx) == 0 )
					{
						$sql = "INSERT INTO tbl_hd_chamado_extra_banco ( hd_chamado ) values ( $hd_chamado )";
						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}

					$sql = "UPDATE tbl_hd_chamado_extra_banco SET
								banco            = $xbanco,
								agencia          = $xagencia,
								contay           = $xcontay,
								nomebanco        = $xnomebanco,
								favorecido_conta = $xfavorecido_conta,
								cpf_conta        = $xcpf_conta,
								tipo_conta       = $xtipo_conta
							WHERE hd_chamado = $hd_chamado";

					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);

					$sql = "SELECT hd_chamado
							FROM tbl_hd_chamado_troca
							WHERE hd_chamado = $hd_chamado ";

					$resx = @pg_exec($con, $sql);

					if( @pg_numrows($resx) == 0 ){
						$sql = "INSERT INTO tbl_hd_chamado_troca ( hd_chamado ) values ( $hd_chamado )";
						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}

					$sql = "UPDATE tbl_hd_chamado_troca SET
								data_pagamento    = $xdata_pagamento,
								ressarcimento     = 't',
								numero_objeto     = NULL,
								nota_fiscal_saida = NULL,
								data_nf_saida     = NULL,
								produto           = NULL,
								valor_produto     = $xvalor_produto,
								valor_inpc        = $xvalor_inpc,
								valor_corrigido   = $xvalor_corrigido
							WHERE hd_chamado = $hd_chamado";

					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);

					if( strlen($valor_produto)>0 AND strlen($valor_inpc)>0 AND strlen($msg_erro)==0 )
					{
						$sql = "SELECT CURRENT_DATE - data_nf AS qtde_dias
								FROM tbl_hd_chamado_extra
								WHERE hd_chamado = $hd_chamado ";
						$resx = @pg_exec($con, $sql);

						if( @pg_numrows($resx) > 0 )
						{
							$qtde_dias = pg_result($resx, 0, qtde_dias); # echo "<hr>";

							if( $qtde_dias > 0 )
							{
								$valor_corrigido = $valor_produto + ($valor_produto * $qtde_dias / 100);
								$sql = "UPDATE tbl_hd_chamado_troca SET valor_corrigido = $valor_corrigido WHERE hd_chamado = $hd_chamado";
								$res = pg_exec($con, $sql);
								$msg_erro .= pg_errormessage($con);
							}
						}
					}
				}

				/* HD 37805 */
				if( $tab_atual == "sedex_reverso" and strlen($msg_erro) == 0 )
				{
					$sql = "SELECT hd_chamado
							FROM tbl_hd_chamado_troca
							WHERE hd_chamado = $hd_chamado ";

					$resx = @pg_exec($con, $sql);

					if( @pg_numrows($resx) == 0 )
					{
						$sql = "INSERT INTO tbl_hd_chamado_troca ( hd_chamado ) values ( $hd_chamado )";
						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}

					$sql = "UPDATE tbl_hd_chamado_troca SET
									data_pagamento       =  NULL,
									ressarcimento        = 'f',
									numero_objeto        = $xnumero_objeto,
									nota_fiscal_saida    = $xnota_fiscal_saida,
									data_nf_saida        = $xdata_nf_saida,
									produto              = $xproduto_troca,
									data_retorno_produto = $xdata_retorno_produto
							WHERE hd_chamado = $hd_chamado";

					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

				if( $tab_atual == "extensao" and strlen($msg_erro) == 0 and $login_fabrica == 25 )
				{
					if( strlen($es_data_compra)==0 ){
						$msg_erro .= "Informe a data da Compra do produto.";
					}
				}

				/* ########################################################################### */
				/* ##################  grava no banco de dados da hbtech ##################### */
				/* ########################################################################### */
				if( $tab_atual == "extensao" and strlen($msg_erro) == 0 and $login_fabrica == 25 )
				{
					if( strlen($consumidor_fone) == 15 )
					{
						$xddd_consumidor  = "'".substr($consumidor_fone, 2, 2)."'";
						$xfone_consumidor = "'".substr($consumidor_fone, 6, 9)."'";
					}
					elseif( strlen($consumidor_fone) == 9 or strlen($consumidor_fone) == 8 )
					{
						$xddd_consumidor  = "null";
						$xfone_consumidor = "'".$consumidor_fone."'";
					}
					elseif( strlen($consumidor_fone) == 11 or strlen($consumidor_fone) == 10 )
					{
						$xddd_consumidor  = "'".substr($consumidor_fone, 0, 2)."'";
						$xfone_consumidor = "'".substr($consumidor_fone, 2, 9)."'";
					}
					elseif( strlen($consumidor_fone) == 0 )
					{
						$xddd_consumidor  = "NULL";
						$xfone_consumidor = "NULL";
					}
					else
					{
						$xddd_consumidor       = "NULL";
						$xfone_consumidor      = "'".$consumidor_fone."'";
					}

					 $xxes_data_compra = converte_data($es_data_compra);
	/* voltar aqui */
					 $sql = "SELECT garantia from tbl_produto where produto = $xproduto";
					 $res = pg_exec($con, $sql);
					 $garantia = pg_result($res, 0, 0);

					 $sql = "SELECT to_char(('$xxes_data_compra'::date + interval '$garantia month') + interval '6 month','YYYY-MM-DD') ";
	  			     $res = pg_exec($con, $sql);
					 $es_garantia = "'".pg_result($res, 0, 0)."'";

					if( strlen($es_id_numeroserie) > 0 )
					{
						include "conexao_hbtech.php";

						/* INSERINDO NO SITE DO HIBEATS, VERIFICAMOS ANTES SE EXISTE ESSE NUMERO DE SÉRIE E INSERIMOS OS DADOS DO CLIENTE */
						$sql = "INSERT INTO garantia(
									produto           ,
									numeroSerie       ,
									nome              ,
									endereco          ,
									numero            ,
									complemento       ,
									cep               ,
									bairro            ,
									cidade            ,
									estado            ,
									sexo              ,
									dataNascimento    ,
									cpf               ,
									dddComercial      ,
									foneComercial     ,
									dddResidencial    ,
									foneResidencial   ,
									dddCelular        ,
									foneCelular       ,
									email             ,
									estadoCivil       ,
									filhos            ,
									prefMusical       ,
									dataCompra        ,
									nf                ,
									lojaAdquirida     ,
									estadoCompra      ,
									municipioCompra   ,
									dataGarantia
								)VALUES(
									'$produto_referencia||$produto_nome',
									$xserie  ,
									$xconsumidor_nome       ,
									$xconsumidor_endereco   ,
									$xconsumidor_numero     ,
									$xconsumidor_complemento,
									$xconsumidor_cep        ,
									$xconsumidor_bairro     ,
									$xconsumidor_cidade     ,
									$xconsumidor_estado     ,
									$xes_sexo               ,
									$xes_data_nascimento    ,
									$xconsumidor_cpf        ,
									$xes_dddcomercial       ,
									$xes_fonecomercial      ,
									$xddd_consumidor        ,
									$xfone_consumidor       ,
									$xes_dddcelular         ,
									$xes_celular            ,
									$xconsumidor_email      ,
									$xes_estadocivil        ,
									$xes_filhos             ,
									$xes_preferenciamusical ,
									$xes_data_compra        ,
									$xes_nota_fiscal        ,
									$xes_revenda            ,
									$xes_estadocompra       ,
									$xes_municipiocompra    ,
									$es_garantia );"; // echo "$sql;<BR>";

						$res = mysql_query($sql) or die("Erro no Sql1: ".mysql_error());

						if( strlen(mysql_error()) > 0 )
						{
							/* Dispara um email para o PA */
							$mensagem   = $enviar_erro."<br><br><br> $sql <br><br>".mysql_error();
							$cabecalho .= "MIME-Version: 1.0\n";
							$cabecalho .= "Content-type: text/html; charset=iso-8859-1\n";
							$cabecalho .= "From: Telecontrol <helpdesk@telecontrol.com.br>\n";
							$cabecalho .= 'To: Fabio<fabio@telecontrol.com.br>'."\n";
							$cabecalho .= "Subject: LOG HBTECH GARANTIA\n";
							$cabecalho .= "Return-Path: Suporte <helpdesk@telecontrol.com.br>\n";
							$cabecalho .= "X-Priority: 1\n";
							$cabecalho .= "X-MSMail-Priority: High\n";
							$cabecalho .= "X-Mailer: PHP/" . phpversion();
							if( !mail("", $assunto, $mensagem, $cabecalho) ){}
						}

						if( $xconsumidor_cpf == 'null' or strlen($xconsumidor_cpf) == 0 ){
							$pesquisa_xconsumidor_cpf = " AND cpf  IS NULL ";
						}else{
							$pesquisa_xconsumidor_cpf = " AND cpf  = $xconsumidor_cpf";
						}

						$sql = "SELECT idGarantia FROM garantia WHERE numeroSerie = $xserie $pesquisa_xconsumidor_cpf";
						$res = mysql_query($sql) or die("Erro no Sql2:".mysql_error());

						if( mysql_num_rows($res)>0 )
						{
							$idGarantia = mysql_result($res, 0, idGarantia);
							$sql = "UPDATE numero_serie SET idGarantia = $idGarantia WHERE numero = $xserie";
							$res = mysql_query($sql) or die("Erro no Sql:".mysql_error());
						}
					}
				}

				if( strlen($msg_erro) == 0 )
				{
					$sql = "SELECT fn_callcenter_dias_interacao($hd_chamado,$login_fabrica)";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);

					$sql = "SELECT fn_callcenter_dias_aberto($hd_chamado,$login_fabrica);";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);

					$sql = "SELECT fn_callcenter_intervalo_interacao($hd_chamado,$login_fabrica);";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

				if( $abre_os == 't' AND $imprimir_os == 't' ){
					$imprimir_os = "&imprimir_os=t";
				}else{
					$imprimir_os = "";
				}

			// HD 26968
			if( strlen($xtransferir) >0 AND strlen($hd_chamado) >0 AND ($login_admin <> $xtransferir) )
			{
				$sql = "UPDATE tbl_hd_chamado SET atendente = $xtransferir
						WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
						AND tbl_hd_chamado.hd_chamado = $hd_chamado	";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "SELECT login from tbl_admin where admin = $login_admin";
				$res = pg_exec($con,$sql);
				$nome_ultimo_atendente = pg_result($res,0,login);

				$sql = "SELECT login from tbl_admin where admin = $xtransferir";
				$res = pg_exec($con,$sql);
				$nome_atendente = pg_result($res,0,login);

				$sql = "INSERT INTO tbl_hd_chamado_item(
							hd_chamado   ,
							data         ,
							comentario   ,
							admin        ,
							interno      ,
							status_item
						)VALUES(
							$hd_chamado      ,
							current_timestamp,
							'Atendimento transferido de <b>$nome_ultimo_atendente</b> para <b>$nome_atendente</b>',
							$login_admin,
							't'  ,
							$xstatus_interacao )";

				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}

			if( strlen($msg_erro) == 0 )
			{
				$res = pg_exec($con,"COMMIT TRANSACTION");
				header ("Location: callcenter_interativo_new_britania.php?callcenter=$hd_chamado&$imprimir_os#$tab_atual");
				exit;
			}else{
				$res = pg_exec($con,"ROLLBACK TRANSACTION");
			}
	} /* FIM - INSERINDO */

	/* Atualizando */
	if( strlen($callcenter) > 0 )
	{
		if( $xresposta == "null" ){ $msg_erro = "Por favor insira a resposta"; }

		$sql = "SELECT atendente,login
				from tbl_hd_chamado
				JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente
				WHERE fabrica_responsavel= $login_fabrica
				AND hd_chamado = $callcenter";

		$res = pg_exec($con, $sql);

		if( pg_numrows($res) > 0 ){
			$ultimo_atendente       = pg_result($res,0,atendente);
			$ultimo_atendente_login = pg_result($res,0,login);
		}

		/* echo $xresposta."<BR>"; echo $xstatus_interacao."<BR>"; echo $xtransferir."<BR>"; echo $xchamado_interno; */
		
		# HD 45756
		if( $login_fabrica == 3 ){
			if( $ultimo_atendente <> $login_admin ){
				$msg_erro = "Sem permissão de alteração. Admin responsável: $ultimo_atendente_login";
			}
		}

		$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$consumidor_cidade}')) AND UPPER(estado) = UPPER('{$consumidor_estado}')";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$cidade = pg_fetch_result($res, 0, "cidade");
		} else {
			$sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$consumidor_cidade}')) AND UPPER(estado) = UPPER('{$consumidor_estado}')";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$cidade_ibge        = pg_fetch_result($res, 0, "cidade");
				$cidade_estado_ibge = pg_fetch_result($res, 0, "estado");

				$sql = "INSERT INTO tbl_cidade (
							nome, estado
						) VALUES (
							'{$cidade_ibge}', '{$cidade_estado_ibge}'
						) RETURNING cidade";
				$res = pg_query($con, $sql);

				$cidade = pg_fetch_result($res, 0, "cidade");
			} else {
				$msg_erro .= "Cidade do consumidor não encontrada";
			}
		}

		if( strlen($msg_erro) == 0 )
		{
			$res = pg_exec ($con,"BEGIN TRANSACTION");
			$sql = "INSERT INTO tbl_hd_chamado_item(
							hd_chamado   ,
							data         ,
							comentario   ,
							admin        ,
							interno      ,
							status_item  ,
							enviar_email
							)values(
							$callcenter       ,
							current_timestamp ,
							$xresposta        ,
							$login_admin      ,
							$xchamado_interno  ,
							$xstatus_interacao ,
							$xenvia_email )"; // echo $sql;

			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

		if( strlen($posto_tab) > 0 )
		{
			$comentario = "Indicação do posto mais próximo do consumidor: <br>
							Código: $codigo_posto_tab <br>
							Nome: $posto_nome_tab<br>
							Endereço: $posto_endereco_tab <br>
							Cidade: $posto_cidade_tab <br>
							Estado: $posto_estado_tab";

			$sql = "INSERT INTO tbl_hd_chamado_item(
					hd_chamado   ,
					data         ,
					comentario   ,
					admin        ,
					interno      ,
					status_item
					)values(
					$hd_chamado      ,
					current_timestamp,
					'$comentario'    ,
					$login_admin     ,
					'f',
					$xstatus_interacao )"; // echo $sql;

			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

		if( strlen($msg_erro)==0 and $xenvia_email == "'t'" ) // se é para enviar email para consumidor
		{
			$sql = "SELECT email
					FROM tbl_hd_chamado_extra
					WHERE tbl_hd_chamado_extra.hd_chamado = $callcenter";

			$res = pg_exec($con, $sql);
			$msg_erro .= pg_errormessage($con);

			if( pg_numrows($res) > 0 )
			{
				$cliente_email = pg_result($res, 0, email);

				if( strlen($cliente_email) > 0 )
				{
					$sql = "SELECT email FROM tbl_admin WHERE admin = $login_admin AND fabrica = $login_fabrica";

					$res = pg_exec($con, $sql);
					$msg_erro .= pg_errormessage($con);

					if( pg_numrows($res) > 0 ){
						$admin_email = pg_result($res, 0, email);
					}else{
						$admin_email = "telecontrol@telecontrol.com.br";
					}

					$xxresposta   = str_replace("'", "", $xresposta);
					$remetente    = $admin_email;
					$destinatario = $cliente_email;
					$assunto      = "Resposta atendimento Call Center";
					$mensagem     = nl2br($xxresposta);
					$headers      = "Return-Path: <$admin_email>\nFrom:".$remetente."\nContent-type: text/html\n";
					mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
				}
			}
		}

		if( strlen($msg_erro) == 0 )
		{
			$sql = "UPDATE tbl_hd_chamado SET status = $xstatus_interacao
					WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
					AND tbl_hd_chamado.hd_chamado = $callcenter	";

			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);

			// HD 69915
			$xdata_nf     = "'".$_POST['data_nf']."'" ;

			if( strlen($data_nf) == 0 ) $xdata_nf = "NULL";
			else $xdata_nf = "'".converte_data($data_nf)."'";

			$xnota_fiscal = "'".$_POST['nota_fiscal']."'" ;

			// HD 69918
			$sql = "UPDATE tbl_hd_chamado_extra set 
							hd_situacao       = $xhd_situacao      ,
							hd_motivo_ligacao = $xhd_motivo_ligacao,
							tipo_registro     = '$tipo_registro'   ,
							produto           = $xproduto          ,
							serie             = $xserie            ,
							data_nf           = $xdata_nf          ,
							nota_fiscal       = $xnota_fiscal      ,
							posto             = $xcodigo_posto
					WHERE  tbl_hd_chamado_extra.hd_chamado = $callcenter";

			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if( $ultimo_atendente <> $xtransferir )
			{
				$sql = "UPDATE tbl_hd_chamado set atendente = $xtransferir
						WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
						and tbl_hd_chamado.hd_chamado = $callcenter	";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

				# HD 35488
				# Marca HD como pendente
				if ($login_fabrica == 51){
					$sql = "UPDATE tbl_hd_chamado_extra set leitura_pendente = 't'
							WHERE hd_chamado = $callcenter	";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

				$sql = "SELECT login,email from tbl_admin where admin = $ultimo_atendente";
				$res = pg_exec($con,$sql);
				$nome_ultimo_atendente  = pg_result($res,0,login);
				$email_ultimo_atendente = pg_result($res,0,email);

				$sql = "SELECT login,email from tbl_admin where admin = $xtransferir";
				$res = pg_exec($con,$sql);
				$nome_atendente  = pg_result($res,0,login);
				$email_atendente = pg_result($res,0,email);

				$sql = "INSERT INTO tbl_hd_chamado_item(
								hd_chamado   ,
								data         ,
								comentario   ,
								admin        ,
								interno      ,
								status_item
								)values(
								$callcenter       ,
								current_timestamp ,
								'Atendimento transferido de <b>$nome_ultimo_atendente</b> para <b>$nome_atendente</b>'       ,
								$login_admin      ,
								't'  ,
								$xstatus_interacao )"; // echo $sql;

				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

				if( strlen($email_ultimo_atendente) >0 AND strlen($email_atendente) > 0 )
				{
					$assunto   = "O atendimento $callcenter foi transferido para você";
					$corpo     = "<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
								 <P align=left>$nome_atendente,</P>
								 <P align=justify>O atendimento $callcenter foi transferido de <b>$nome_ultimo_atendente</b> para você</P>";
					$body_top  = "--Message-Boundary\n";
					$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
					$body_top .= "Content-transfer-encoding: 7BIT\n";
					$body_top .= "Content-description: Mail message body\n\n";

					if ( @mail($email_atendente, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_ultimo_atendente." \n $body_top " ) ){
						$msg = "<br>Foi enviado um email para: ".$email_atendente."<br>";
					}else{
						$msg_erro = "Não foi possível enviar o email. ";
					}
				}
			}
		}

		// hd 14231 22/2/2008
		if( strlen($msg_erro) == 0 )
		{
			if( strlen($consumidor_nome)>0 and strlen($xconsumidor_estado)>0 and strlen($xconsumidor_cidade)>0 )
			{
				$sql = "SELECT tbl_cidade.cidade
							FROM tbl_cidade
							where tbl_cidade.nome = $xconsumidor_cidade
							AND tbl_cidade.estado = $xconsumidor_estado
							limit 1";

					$res = pg_exec($con, $sql); //echo nl2br($sql)."<BR>";

					if( pg_numrows($res)>0 ){ $cidade = pg_result($res, 0, 0); }
			}

			if( strlen($hd_chamado) > 0 and $login_fabrica <> 11 ) // *ja tem cadastro no telecontrol/
			{
				$sql = "SELECT hd_chamado
						from tbl_hd_chamado_extra
						where hd_chamado=$hd_chamado";
				$res = pg_exec($con, $sql);

				if( pg_numrows($res) > 0 )
				{
					$xhd_chamado = pg_result($res, 0, 0);

					$sql = "UPDATE tbl_hd_chamado_extra SET 
								nome        = upper($xconsumidor_nome)       ,
								endereco    = upper($xconsumidor_endereco)   ,
								numero      = upper($xconsumidor_numero)     ,
								complemento = upper($xconsumidor_complemento),
								bairro      = upper($xconsumidor_bairro)     ,
								cep         = upper($xconsumidor_cep)        ,
								fone        = upper($xconsumidor_fone)       ,
								fone2       = upper($xconsumidor_fone2)      ,
								email       = upper($xconsumidor_email)      ,
								cpf         = upper($xconsumidor_cpf)        ,
								rg          = upper($xconsumidor_rg)         ,
								cidade      = $cidade                        ,
								defeito_reclamado_descricao = '$hd_extra_defeito'
							WHERE tbl_hd_chamado_extra.hd_chamado = $xhd_chamado"; //echo nl2br($sql)."<BR>";
					
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}
		}

		/* HD 37805 */
		if( $tab_atual == "ressarcimento" and strlen($msg_erro)==0 )
		{
			$sql = "SELECT hd_chamado
					FROM tbl_hd_chamado_extra_banco
					WHERE hd_chamado = $hd_chamado ";
			$resx = @pg_exec($con, $sql);

			if( @pg_numrows($resx) == 0 ){
				$sql = "INSERT INTO tbl_hd_chamado_extra_banco ( hd_chamado ) values ( $hd_chamado )";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}

			$sql = "UPDATE tbl_hd_chamado_extra_banco SET
									banco            = $xbanco,
									agencia          = $xagencia,
									contay           = $xcontay,
									nomebanco        = $xnomebanco,
									favorecido_conta = $xfavorecido_conta,
									cpf_conta        = $xcpf_conta,
									tipo_conta       = $xtipo_conta
					WHERE hd_chamado = $hd_chamado";

			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);

			$sql = "SELECT hd_chamado
					FROM tbl_hd_chamado_troca
					WHERE hd_chamado = $hd_chamado ";

			$resx = @pg_exec($con,$sql);

			if( @pg_numrows($resx) == 0 ){
				$sql = "INSERT INTO tbl_hd_chamado_troca ( hd_chamado ) values ( $hd_chamado )";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}

			$sql = "UPDATE tbl_hd_chamado_troca SET
							data_pagamento    = $xdata_pagamento,
							ressarcimento     = 't',
							numero_objeto     = NULL,
							nota_fiscal_saida = NULL,
							data_nf_saida     = NULL,
							produto           = NULL,
							valor_produto     = $xvalor_produto,
							valor_inpc        = $xvalor_inpc,
							valor_corrigido   = $xvalor_corrigido
					WHERE hd_chamado = $hd_chamado";

			$res = pg_exec($con, $sql);
			$msg_erro .= pg_errormessage($con);

			if( strlen($valor_produto)>0 AND strlen($valor_inpc)>0 AND strlen($msg_erro)==0 )
			{
				$sql = "SELECT CURRENT_DATE - data_nf AS qtde_dias
						FROM tbl_hd_chamado_extra
						WHERE hd_chamado = $hd_chamado ";
				$resx = @pg_exec($con, $sql);

				if( @pg_numrows($resx) > 0 )
				{
					$qtde_dias = pg_result($resx, 0, qtde_dias); # echo "<hr>";
					if( $qtde_dias > 0 )
					{
						$valor_corrigido = $valor_produto + ($valor_produto * $qtde_dias / 100);
						$sql = "UPDATE tbl_hd_chamado_troca SET valor_corrigido = $valor_corrigido WHERE hd_chamado = $hd_chamado";
						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}
				}
			}
		}

		/* HD 37805 */
		if( $tab_atual == "sedex_reverso" and strlen($msg_erro)==0 )
		{
			$sql = "SELECT hd_chamado
					FROM tbl_hd_chamado_troca
					WHERE hd_chamado = $hd_chamado ";
			$resx = @pg_exec($con, $sql);

			if( @pg_numrows($resx) == 0 ){
				$sql = "INSERT INTO tbl_hd_chamado_troca ( hd_chamado ) values ( $hd_chamado )";
				$res = pg_exec($con, $sql);
				$msg_erro .= pg_errormessage($con);
			}

			$sql = "UPDATE tbl_hd_chamado_troca SET
							data_pagamento       =  NULL
							ressarcimento        = 'f',
							numero_objeto        = $xnumero_objeto,
							nota_fiscal_saida    = $xnota_fiscal_saida,
							data_nf_saida        = $xdata_nf_saida,
							produto              = $xproduto_troca,
							data_retorno_produto = $xdata_retorno_produto
					WHERE hd_chamado = $hd_chamado";

			$res = pg_exec($con, $sql);
			$msg_erro .= pg_errormessage($con);
		}

		if( $tab_atual == "extensao" and strlen($msg_erro) == 0 and $login_fabrica==25 ){
			if( strlen($es_data_compra) == 0 ){
				$msg_erro .= "Informe a data da Compra do produto.";
			}
		}

		$sql = "SELECT fn_callcenter_dias_interacao($hd_chamado,$login_fabrica)";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$sql = "SELECT fn_callcenter_dias_aberto($hd_chamado,$login_fabrica);";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$sql = "SELECT fn_callcenter_intervalo_interacao($hd_chamado,$login_fabrica);";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);

		if( strlen($msg_erro) == 0 )
		{
			// $res = pg_exec($con,"ROLLBACK TRANSACTION");
			$res = pg_exec($con,"COMMIT TRANSACTION");
			header("Location: callcenter_interativo_new.php?callcenter=$hd_chamado");
			exit;
		}else{
			// echo $msg_erro;
			$res = pg_exec($con,"ROLLBACK TRANSACTION");
		}
	}/* FIM - Atualizando */
}

function saudacao(){
	$hora = date("H");
	if($hora >= 7 and $hora <= 11){
		echo "bom dia";
	}
	if($hora>=12 and $hora <= 17){
		echo "boa tarde";
	}
	if($hora>=18){
		echo "boa noite";
	}
}

$callcenter  = $_GET['callcenter'];
$imprimir_os = trim($_GET['imprimir_os']);

if( strlen($callcenter) > 0 )
{
	$sql = "SELECT	tbl_hd_chamado_extra.hd_chamado AS callcenter,
					to_char(tbl_hd_chamado_extra.data_abertura,'DD/MM/YYYY') AS abertura_callcenter,
					tbl_hd_chamado_extra.nome,
					tbl_hd_chamado_extra.endereco ,
					tbl_hd_chamado_extra.numero ,
					tbl_hd_chamado_extra.complemento ,
					tbl_hd_chamado_extra.bairro ,
					tbl_hd_chamado_extra.cep ,
					tbl_hd_chamado_extra.fone ,
					tbl_hd_chamado_extra.fone2 ,
					tbl_hd_chamado_extra.email ,
					tbl_hd_chamado_extra.cpf ,
					tbl_hd_chamado_extra.rg ,
					tbl_hd_chamado_extra.cliente ,
					tbl_hd_chamado_extra.consumidor_revenda,
					tbl_hd_chamado_extra.hd_situacao,
					tbl_hd_situacao.descricao AS hd_situacao_descricao,
					tbl_hd_chamado_extra.hd_motivo_ligacao,
					tbl_cidade.nome AS cidade_nome,
					tbl_cidade.estado,
					tbl_hd_chamado_extra.origem,
					tbl_admin.login AS atendente,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data,
					tbl_hd_chamado.status,
					tbl_hd_chamado.categoria AS natureza_operacao,
					tbl_posto.posto,
					tbl_hd_chamado.titulo AS assunto,
					tbl_hd_chamado.categoria,
					tbl_produto.produto,
					tbl_produto.linha,
					tbl_produto.referencia AS produto_referencia,
					tbl_produto.descricao AS produto_nome,
					tbl_defeito_reclamado.defeito_reclamado,
					tbl_defeito_reclamado.descricao AS defeito_reclamado_descricao,
					tbl_hd_chamado_extra.reclamado,
					tbl_hd_chamado_extra.os,
					tbl_hd_chamado_extra.serie,
					to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') AS data_nf,
					tbl_hd_chamado_extra.nota_fiscal,
					tbl_hd_chamado_extra.revenda,
					tbl_hd_chamado_extra.revenda_nome,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome AS posto_nome,
					to_char(tbl_hd_chamado_extra.data_abertura_os,'DD/MM/YYYY') AS data_abertura,
					tbl_hd_chamado_extra.receber_info_fabrica,
					tbl_os.sua_os AS sua_os,
					tbl_hd_chamado_extra.abre_os,
					tbl_hd_chamado_extra.leitura_pendente,
					tbl_hd_chamado.atendente AS atendente_pendente,
					tbl_hd_chamado_extra.defeito_reclamado_descricao AS hd_extra_defeito,
					tbl_hd_chamado_extra.numero_processo,
					tbl_hd_chamado_extra.tipo_registro
		FROM tbl_hd_chamado
		JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
		LEFT JOIN tbl_hd_situacao ON tbl_hd_situacao.hd_situacao = tbl_hd_chamado_extra.hd_situacao
		LEFT JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
		JOIN tbl_admin  ON tbl_hd_chamado.atendente = tbl_admin.admin
		LEFT JOIN tbl_posto ON tbl_hd_chamado_extra.posto = tbl_posto.posto
		LEFT JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto  AND tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_extra.produto
		LEFT JOIN tbl_revenda ON tbl_revenda.revenda = tbl_hd_chamado_extra.revenda
		LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
		LEFT JOIN tbl_os ON tbl_os.os = tbl_hd_chamado_extra.os
		WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
		AND tbl_hd_chamado.hd_chamado = $callcenter";

	$res = pg_exec($con,$sql);

	if( pg_numrows($res) > 0 )
	{
		$callcenter               = pg_result($res,0,callcenter);
		$abertura_callcenter      = pg_result($res,0,abertura_callcenter);
		$data_abertura_callcenter = pg_result($res,0,data);
		$categoria                = pg_result($res,0,categoria);
		$consumidor_nome          = pg_result($res,0,nome);
		$cliente                  = pg_result($res,0,cliente);
		$consumidor_cpf           = pg_result($res,0,cpf);
		$consumidor_rg            = pg_result($res,0,rg);
		$consumidor_email         = pg_result($res,0,email);
		$consumidor_fone          = pg_result($res,0,fone);
		$consumidor_fone2         = pg_result($res,0,fone2);
		$consumidor_cep           = pg_result($res,0,cep);
		$consumidor_endereco      = pg_result($res,0,endereco);
		$consumidor_numero        = pg_result($res,0,numero);
		$consumidor_complemento   = pg_result($res,0,complemento);
		$consumidor_bairro        = pg_result($res,0,bairro);
		$consumidor_cidade        = pg_result($res,0,cidade_nome);
		$consumidor_estado        = pg_result($res,0,estado);
		$consumidor_revenda       = pg_result($res,0,consumidor_revenda);
		$origem                   = pg_result($res,0,origem);
		$assunto                  = pg_result($res,0,assunto);
		$sua_os                   = pg_result($res,0,sua_os);
		$os                       = pg_result($res,0,os);
		$data_abertura            = pg_result($res,0,data_abertura);
		$produto                  = pg_result($res,0,produto);
		$produto_referencia       = pg_result($res,0,produto_referencia);
		$produto_nome             = pg_result($res,0,produto_nome);
		$serie                    = pg_result($res,0,serie);
		$data_nf                  = pg_result($res,0,data_nf);
		$nota_fiscal              = pg_result($res,0,nota_fiscal);
		$revenda                  = pg_result($res,0,revenda);
		$revenda_nome             = pg_result($res,0,revenda_nome);
		$posto                    = pg_result($res,0,posto);
		$posto_nome               = pg_result($res,0,posto_nome);
		$defeito_reclamado        = pg_result($res,0,defeito_reclamado);
		$reclamado                = pg_result($res,0,reclamado);
		$status_interacao         = pg_result($res,0,status);
		$hd_situacao              = pg_result($res,0,hd_situacao);
		$hd_situacao_descricao    = pg_result($res,0,hd_situacao_descricao);
		$hd_motivo_ligacao        = pg_result($res,0,hd_motivo_ligacao);
		$atendente                = pg_result($res,0,atendente);
		$receber_informacoes	  = pg_result($res,0,receber_info_fabrica);
		$codigo_posto	          = pg_result($res,0,codigo_posto);
		$linha         	          = pg_result($res,0,linha);
		$abre_os                  = pg_result($res,0,abre_os);
		$leitura_pendente         = pg_result($res,0,leitura_pendente);
		$atendente_pendente       = pg_result($res,0,atendente_pendente);
		$categoria                = pg_result($res,0,categoria);
		$hd_extra_defeito         = pg_result($res,0,hd_extra_defeito);
		$numero_processo          = pg_result($res,0,numero_processo);
		$tipo_registro            = pg_result($res,0,tipo_registro);

		if( $login_fabrica == 51 and $leitura_pendente == "t" )
		{
			if( $atendente_pendente == $login_admin )
			{
				$sql = "UPDATE tbl_hd_chamado_extra SET leitura_pendente = NULL
						WHERE hd_chamado = $callcenter";
				$res = pg_exec($con, $sql);
				$msg_erro .= pg_errormessage($con);
			}
		}

		$sql ="SELECT	tbl_hd_chamado_troca.valor_corrigido   ,
						tbl_hd_chamado_troca.hd_chamado        ,
						to_char(tbl_hd_chamado_troca.data_pagamento,'DD/MM/YYYY') AS data_pagamento,
						tbl_hd_chamado_troca.ressarcimento     ,
						tbl_hd_chamado_troca.numero_objeto     ,
						tbl_hd_chamado_troca.nota_fiscal_saida ,
						TO_CHAR(tbl_hd_chamado_troca.data_nf_saida,'DD/MM/YYYY')        AS data_nf_saida,
						TO_CHAR(tbl_hd_chamado_troca.data_retorno_produto,'DD/MM/YYYY') AS data_retorno_produto,
						tbl_hd_chamado_troca.valor_produto     ,
						tbl_hd_chamado_troca.valor_inpc        ,
						tbl_hd_chamado_troca.valor_corrigido   ,
						tbl_produto.referencia                 AS troca_produto_referencia,
						tbl_produto.referencia                 AS troca_produto_descricao
				FROM tbl_hd_chamado_troca
				LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_troca.produto
				WHERE tbl_hd_chamado_troca.hd_chamado = $callcenter";

		$res = pg_exec($con, $sql);

		if( pg_numrows($res)>0 )
		{
			$valor_corrigido          = pg_result($res,0,valor_corrigido);
			$hd_chamado               = pg_result($res,0,hd_chamado);
			$data_pagamento           = pg_result($res,0,data_pagamento);
			$ressarcimento            = pg_result($res,0,ressarcimento);
			$numero_objeto            = pg_result($res,0,numero_objeto);
			$nota_fiscal_saida        = pg_result($res,0,nota_fiscal_saida);
			$nota_fiscal_saida        = pg_result($res,0,nota_fiscal_saida);
			$data_nf_saida            = pg_result($res,0,data_nf_saida);
			$data_retorno_produto     = pg_result($res,0,data_retorno_produto);
			$valor_produto            = pg_result($res,0,valor_produto);
			$valor_inpc               = pg_result($res,0,valor_inpc);
			$valor_corrigido          = pg_result($res,0,valor_corrigido);
			$troca_produto_referencia = pg_result($res,0,troca_produto_referencia);
			$troca_produto_descricao  = pg_result($res,0,troca_produto_descricao);
		}

		/* HD 37805 - Adicionei 59 - Arrumei esta parte de baixo*/
		if( $login_fabrica == 59 )
		{
			$tipo_atendimento = array(1 => 'reclamacao_produto',
									  2 => 'reclamacao_empresa',
									  3 => 'reclamacao_at',
									  4 => 'duvida_produto',
									  5 => 'sugestao',
									  6 => 'onde_comprar',
									  7 => 'ressarcimento',
									  8 => 'sedex_reverso');
		}
		elseif( $login_fabrica == 2 )
		{
			if( $natureza_chamado == 'reclamacao_revenda' or $natureza_chamado == 'reclamacao_at' or $natureza_chamado == 'reclamacao_enderecos' )
			{
				$natureza_chamado2 = $natureza_chamado;
				$natureza_chamado  = "reclamacoes";
			}

			$tipo_atendimento = array(1 => 'reclamacao_produto',
									  2 => 'reclamacoes',
									  3 => 'duvida_produto',
									  4 => 'sugestao',
									  5 => 'procon' ,
									  6 => 'onde_comprar');
		}
		else
		{
			$tipo_atendimento = array(1 => 'extensao',
									  2 => 'reclamacao_produto',
									  3 => 'reclamacao_empresa',
									  4 => 'reclamacao_at',
									  5 => 'duvida_produto',
									  6 => 'sugestao',
									  7 => 'assistencia',
									  8 => 'garantia',
									  9 => 'troca_produto',
									 10 => 'procon' ,
									 11 => 'onde_comprar');
		}

		$posicao = array_search($natureza_chamado, $tipo_atendimento); // $key = 2;

		if( $imprimir_os == 't' AND strlen ($os) > 0 )
		{
			echo "<script language='javascript'>";
			echo "window.open ('os_print.php?os=$os&qtde_etiquetas=$qtde_etiquetas','os_print','resizable=yes,resize=yes,toolbar=no,location=yes,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0')";
			echo "</script>";
		}
	}
}

$Id = $_GET['Id'];

if( strlen($Id) > 0 )
{
	$sql = "SELECT	tbl_hd_chamado_extra.hd_chamado AS callcenter,
					to_char(tbl_hd_chamado_extra.data_abertura,'DD/MM/YYYY') AS abertura_callcenter,
					tbl_hd_chamado_extra.nome,
					tbl_hd_chamado_extra.endereco ,
					tbl_hd_chamado_extra.numero ,
					tbl_hd_chamado_extra.complemento ,
					tbl_hd_chamado_extra.bairro ,
					tbl_hd_chamado_extra.cep ,
					tbl_hd_chamado_extra.fone ,
					tbl_hd_chamado_extra.fone2 ,
					tbl_hd_chamado_extra.email ,
					tbl_hd_chamado_extra.cpf ,
					tbl_hd_chamado_extra.rg ,
					tbl_hd_chamado_extra.cliente ,
					tbl_hd_chamado_extra.consumidor_revenda ,
					tbl_cidade.nome AS cidade_nome,
					tbl_cidade.estado,
					tbl_produto.produto,
					tbl_produto.linha,
					tbl_produto.referencia AS produto_referencia,
					tbl_produto.descricao AS produto_nome,
					tbl_hd_chamado_extra.serie,
					to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') AS data_nf,
					tbl_hd_chamado_extra.nota_fiscal,
					tbl_hd_chamado_extra.abre_os,
					tbl_hd_chamado_extra.leitura_pendente,
					tbl_hd_chamado.atendente AS atendente_pendente,
					tbl_hd_chamado_extra.defeito_reclamado_descricao AS hd_extra_defeito,
					tbl_hd_chamado_extra.tipo_registro
		FROM tbl_hd_chamado
		JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
		LEFT JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
		LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_extra.produto
		WHERE tbl_hd_chamado.hd_chamado = $Id";

	$res = pg_exec($con,$sql);

	if( pg_numrows($res) > 0 )
	{
		$consumidor_nome        = pg_result($res,0,nome);
		$cliente                = pg_result($res,0,cliente);
		$consumidor_cpf         = pg_result($res,0,cpf);
		$consumidor_rg          = pg_result($res,0,rg);
		$consumidor_email       = pg_result($res,0,email);
		$consumidor_fone        = pg_result($res,0,fone);
		$consumidor_fone2       = pg_result($res,0,fone2);
		$consumidor_cep         = pg_result($res,0,cep);
		$consumidor_endereco    = pg_result($res,0,endereco);
		$consumidor_numero      = pg_result($res,0,numero);
		$consumidor_complemento = pg_result($res,0,complemento);
		$consumidor_bairro      = pg_result($res,0,bairro);
		$consumidor_cidade      = pg_result($res,0,cidade_nome);
		$consumidor_estado      = pg_result($res,0,estado);
		$produto                = pg_result($res,0,produto);
		$produto_referencia     = pg_result($res,0,produto_referencia);
		$produto_nome           = pg_result($res,0,produto_nome);
		$serie                  = pg_result($res,0,serie);
		$data_nf                = pg_result($res,0,data_nf);
		$nota_fiscal            = pg_result($res,0,nota_fiscal);
		$revenda                = pg_result($res,0,consumidor_revenda);
		$abre_os                = pg_result($res,0,abre_os);
		$leitura_pendente       = pg_result($res,0,leitura_pendente);
		$atendente_pendente     = pg_result($res,0,atendente_pendente);
		$hd_extra_defeito       = pg_result($res,0,hd_extra_defeito);
		$tipo_registro          = pg_result($res,0,tipo_registro);

		if( $login_fabrica == 51 and $leitura_pendente == "t" )
		{
			if( $atendente_pendente == $login_admin ){
				$sql = "UPDATE tbl_hd_chamado_extra set leitura_pendente = null
						WHERE hd_chamado = $Id";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
		}
	}
}

include "cabecalho.php";
?>

<style type="text/css">
.input {
	font-size: 10px;
	font-family: verdana;
	border-right: #666666 1px double;
	border-top: #666666 1px double;
	border-left: #666666 1px double;
	border-bottom: #666666 1px double;
	background-color: #ffffff}
.respondido {
	font-size: 10px;
	color: #4D4D4D;
	font-family: verdana;
	border-right: #666666 1px double;
	border-top: #666666 1px double;
	border-left: #666666 1px double;
	border-bottom: #666666 1px double;
	background-color: #ffffff;
}
.inicio{
	border:#485989 1px solid;
	background-color: #e6eef7;
	font-size:10px;
	font-family:verdana;
	text-align:center;
	margin: 0 auto;
	width:200px;
	padding-left: 2px;
	padding-right: 2px;
	padding-top: 2px;
	padding-bottom: 2px;
}
.tab_content{
	border:#485989 1px solid;
	font-size:10px;
	font-family:verdana;
	margin: 0 auto;
	float:center;
    /* width:680px; */
	padding-left: 2px;
	padding-right: 2px;
	padding-top: 2px;
	padding-bottom: 2px;
}
.padding { padding-left: 150px; }
</style>

<!-- ================================ <FUNES> ================================ !-->
<?php
	include "javascript_pesquisas.php";
	//include 'javascript_calendario.php';
	include '../js/js_css.php';
?>

<script language='javascript' src='ajax_cep.js'></script>
<script type="text/javascript">

	var produtosTrocaDireta = [];

<?php
	if( $login_fabrica==25 OR $login_fabrica==59 ){
		$w=1;
	}else if( $login_fabrica == 45 ){
		$w=1;
		$posicao = $posicao-1;
	}else if( $login_fabrica == 46 OR $login_fabrica == 11 ){
		$w=1;
		$posicao = $posicao-1;
	}else if( $login_fabrica == 2 ){
		$w=1;
		$posicao = $posicao;
	}else{
		$w=1;
		if( $posicao>=10 ) $posicao = $posicao-4;
		else $posicao = $posicao-1;
	}
?>
	$(function()
	{
		$('#container-Principal').tabs(<?php if( strlen($callcenter) > 0 ){ echo "$posicao,"; } ?>{ fxSpeed: 'fast' } );
		<?php 
			if( strlen($callcenter) > 0 )
			{
				for( $x=$w; $x<12; $x++ )
				{
					if( $x <> $posicao ){
		?>
						$('#container-Principal').disableTab(<?php echo $x; ?>);
		<?php 
					} 
				}
			} 
		?>
		// $('#container').disableTab(3);
		// fxAutoHeight: true,
		$("#consumidor_cpf").mask("999.999.999-99");
		$("#consumidor_cep, #posto_cep, #cep").mask("99.999-999");
		$("#hora_ligacao").mask("99:99");
		$("input[rel='data']").mask("99/99/9999");
	});

	$().ready(function()
	{
		function formatItem(row) {
			return row[1] + " - " + row[2];
		}

		function formatItemPosto(row) {
			return row[2] + " - " + row[3] + " (Fantasia:" + row[4] + ")";
		}

		/* Busca pelo Código */
		$("#revenda_cnpj").autocomplete("<?php echo $PHP_SELF.'?tipo_busca=revenda&busca=codigo'; ?>", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {
				return row[1];
			}
		});

		$("#revenda_cnpj").result(function(event, data, formatted) {
			$("#revenda").val(data[0]) ;
			$("#revenda_cnpj").val(data[1]) ;
			$("#revenda_nome").val(data[2]) ;
		});

		/* Busca pelo Nome */
		$("#revenda_nome").autocomplete("<?php echo $PHP_SELF.'?tipo_busca=revenda&busca=nome'; ?>", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {
				return row[2];
			}
		});

		$("#revenda_nome").result(function(event, data, formatted) {
			$("#revenda").val(data[0]);
			$("#revenda_cnpj").val(data[1]);
			$("#revenda_nome").val(data[2]); // alert(data[2]);
		});

		$("#mapa_cidade").autocomplete("<?php echo $PHP_SELF.'?tipo_busca=mapa_cidade&busca=mapa_cidade'; ?>", {
			minChars: 1,
			delay: 150,
			width: 205,
			matchContains: true,
			formatItem: function(row) {
				return row[0];
			},
			formatResult: function(row) {
				return row[0];
			}
		});

		/* Busca pelo Código */
		$("#codigo_posto_tab").autocomplete("<?php echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItemPosto,
			formatResult: function(row) {
				return row[2];
			}
		});

		$("#codigo_posto_tab").result(function(event, data, formatted){
			$("#posto_tab").val(data[0]);
			$("#codigo_posto_tab").val(data[2]);
			$("#posto_nome_tab").val(data[3]);
		});

		/* Busca pelo Nome */
		$("#posto_nome_tab").autocomplete("<?php echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItemPosto,
			formatResult: function(row) {
				return row[3];
			}
		});

		$("#posto_nome_tab").result(function(event, data, formatted){
			$("#posto_tab").val(data[0]);
			$("#codigo_posto_tab").val(data[2]);
			$("#posto_nome_tab").val(data[3]); // alert(data[2]);
		});

		var extraParamEstado = {
			estado: function () {
				return $("#consumidor_estado").val()
			}
		};

		$("#consumidor_cidade").autocomplete("autocomplete_cidade_new.php", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			extraParams: extraParamEstado,
			formatItem: function (row) { return row[0]; },
			formatResult: function (row) { return row[0]; }
		});

		$("#consumidor_cidade").result(function(event, data, formatted) {
			$("#consumidor_cidade").val(data[0].toUpperCase());
		});
	});

	function verificarImpressao(check){
		if( check.checked ){
			$('#imprimir_os').show();
		}else{
			$('#imprimir_os').hide();
		}
	}

	function fnc_pesquisa_produto2 (descricao, referencia)
	{
		if( descricao.length > 2 || referencia.length > 2 ){
			Shadowbox.open({
				content: "produto_pesquisa_2_nv.php?descricao=" + descricao + "&referencia=" + referencia,
				player:	"iframe",
				title:		"Pesquisa Produto",
				width:	800,
				height:	500
			});
		}
	}

	function retorna_dados_produto(produto,linha,nome_comercial,voltagem,referencia,descricao,referencia_fabrica,garantia,ativo,valor_troca,troca_garantia,troca_faturada,mobra,off_line,capacidade,ipi,troca_obrigatoria,posicao)
	{
		gravaDados("produto",produto);
		gravaDados("produto_referencia",referencia);
		gravaDados("produto_nome",descricao);
		gravaDados("mapa_linha",linha);
	}

	function MudaCampo(campo)
	{
		if (campo.value == 'reclamacao_at') {
			document.getElementById('info_posto').style.display='inline';
		}else{
			document.getElementById('info_posto').style.display='none';
		}
	}

	function fnc_pesquisa_posto_call(campo, campo2, campo3, tipo)
	{
		if (tipo == "codigo" ) {
			var xcampo = campo;
		}

		if (tipo == "nome" ) {
			var xcampo = campo2;
		}

		if (xcampo.value.length > 0) {
			Shadowbox.open({
				content:	"posto_pesquisa_call2_nv.php?campo=" + xcampo.value + "&tipo=" + tipo,
				player:	"iframe",
				title:		"Pesquisa Posto",
				width:	800,
				height:	500
			});
		}
	}

	function retorna_posto_call(codigo_posto, nome, fone)
	{
		gravaDados("codigo_posto",codigo_posto);
		gravaDados("posto_nome",nome);
		gravaDados("posto_fone",fone);
	}

	function tipoRegistro(tipo)
	{
		$.ajax({
			type: "GET",
			url: "callcenter_tipo_registro_ajax.php",
			data: "tipo_registro=" + tipo,
			cache: false,
			beforeSend: function(){
				// enquanto a função esta sendo processada, você pode exibir na tela uma msg de carregando
			},
			success: function(txt) {
				// pego o id da div que envolve o select com name="id_modelo" e a substituiu
				// com o texto enviado pelo php, que é um novo select com dados da marca x
				$('#categoria').html(txt);
			},
			error: function(txt){ alert(txt); }
		});

		$.ajax({
			type: "GET",
			url: "callcenter_situacao_ajax.php",
			data: "tipo_registro=" + tipo,
			cache: false,
			beforeSend: function() {},
			success: function(txt) {
				$('#hd_situacao').html(txt);
			},
			error: function(txt) {
				alert(txt);
			}
		});
	}

	function condicaoPagamento(categoria)
	{
		tipo_registro = document.getElementById('tipo_registro').value;

		$.ajax({
			type: "GET",
			url: "callcenter_motivo_ligacao_ajax.php",
			data: "categoria=" + categoria +"&tipo_registro=" + tipo_registro,
			cache: false,
			beforeSend: function() {
				// enquanto a função esta sendo processada, você pode exibir na tela uma msg de carregando
			},
			success: function(txt) {
				// pego o id da div que envolve o select com  name="id_modelo" e a substituiu
				// com o texto enviado pelo php, que é um novo select com dados da marca x
				$('#hd_motivo_ligacao').html(txt);
			},
			error: function(txt){ alert(txt); }
		});
	}

	function defeitoReclamado(produto)
	{
		$.ajax({
			type: "GET",
			url: "callcenter_defeitos_combo_ajax.php",
			data: "produto=" + produto,
			cache: false,
			beforeSend: function(){
				// enquanto a função esta sendo processada, você pode exibir na tela uma msg de carregando 
			},
			success: function(txt){
				// pego o id da div que envolve o select com  name="id_modelo" e a substituiu
				// com o texto enviado pelo php, que é um novo select com dados da marca x
				$('#defeito_reclamado').html(txt);
			},
			error: function(txt){ alert(txt); }
		});
	}

	function fnc_pesquisa_serie (campo) 
	{
		if( campo.value != "" )
		{
			Shadowbox.open({
				content : "serie_pesquisa_nv.php?produto_serie=" + campo.value,
				player  : "iframe",
				title   : "Pesquisa Série",
				width   : 800,
				height  : 500
			});
		}
		else
		{
			alert("Digite toda ou uma parte da informação para pesquisar !");
		}
	}

	function retorna_serie(produto, descricao, referencia, serie)
	{
		gravaDados("produto",produto);
		gravaDados("produto_nome",descricao);
		gravaDados("produto_referencia",referencia);
		gravaDados("serie",serie);
	}
</script>

<script type="text/javascript">
	$(function(){
		Shadowbox.init();

		$("#consumidor_estado").change(function () {
			if ($(this).val().length > 0) {
				$("#consumidor_cidade").removeAttr("readonly");
			} else {
				$("#consumidor_cidade").attr({"readonly": "readonly"});
			}
		});	
	});

	function atualizaMapa(){
		var cidade = $('#consumidor_cidade').val();
		var estado = $('#consumidor_estado').val();
		$('#link').attr('href','callcenter_interativo_posto.php?fabrica=12<?php echo $login_fabrica;?>&cidade='+cidade+'&estado='+estado+'&keepThis=trueTB_iframe=true&height=400&width=700')
		$('#link2').attr('href','callcenter_interativo_posto.php?fabrica=12<?php echo $login_fabrica;?>&cidade='+cidade+'&estado='+estado+'&keepThis=trueTB_iframe=true&height=400&width=700')
	}

	function minimizar(arquivo){
		if (document.getElementById(arquivo)){
			var style2 = document.getElementById(arquivo);
			if( style2 == false) return;
			if( style2.style.display == "block" ){
				style2.style.display = "none";
			}else{
				style2.style.display = "block";
			}
		}
	}

	function formata_data(valor_campo, form, campo){
		var mydata = '';
		mydata     = mydata + valor_campo;
		myrecord   = campo;
		myform     = form;

		if (mydata.length == 2){
			mydata = mydata + '/';
			window.document.forms["" + myform + ""].elements[myrecord].value = mydata;
		}
		if (mydata.length == 5){
			mydata = mydata + '/';
			window.document.forms["" + myform + ""].elements[myrecord].value = mydata;
		}
	}

	/* ============= Função PESQUISA DE CONSUMIDOR POR NOME ====================
	Nome da Função : fnc_pesquisa_consumidor_nome (nome, cpf)
	=================================================================*/
	function fnc_pesquisa_consumidor_callcenter(campo, tipo)
	{
		var url = "";
		if (tipo == "nome") {
			url = "pesquisa_consumidor_callcenter_new_britania_nv.php?nome=" + campo.value + "&tipo=nome";
		}

		if (tipo == "cpf") {
			url = "pesquisa_consumidor_callcenter_new_britania_nv.php?cpf=" + campo.value + "&tipo=cpf";
		}

		if (tipo == "telefone") {
			url = "pesquisa_consumidor_callcenter_new_britania_nv.php?telefone=" + campo.value + "&tipo=telefone";
		}

		if (tipo == "cep") {
			url = "pesquisa_consumidor_callcenter_new_britania_nv.php?cep=" + campo.value + "&tipo=cep";
		}

		if (tipo == "atendimento") {
			url = "pesquisa_consumidor_callcenter_new_britania_nv.php?atendimento=" + campo.value + "&tipo=atendimento";
		}

		if (tipo == "os") {
			url = "pesquisa_consumidor_callcenter_new_britania_nv.php?os=" + campo.value + "&tipo=os";
		}

		if( campo.value != "" )
		{
			Shadowbox.open({
				content : url,
				player  : "iframe",
				title   : "Pesquisa Consumidor",
				width   : 800,
				height  : 500
			});
		}
		else
		{
			alert("Digite toda ou parte da informação para pesquisar !");
		}
	}

	function retorna_consumidor_hdchamado(hd_chamado)
	{
		window.location = "callcenter_interativo_new_britania_nv.php?callcenter="+hd_chamado;
	}

	function retorna_consumidor_reload(retorno, cliente)
	{
		window.location = retorno+"?cliente="+cliente;
	}

	function retorna_consumidor(cliente, nome, cpf, rg, nome_cidade, fone, endereco, numero, complemento, bairro, cep, estado, tipo, email)
	{
		gravaDados("cliente", cliente);
		gravaDados("consumidor_nome", nome);
		gravaDados("consumidor_cpf", cpf);
		gravaDados("consumidor_rg", rg);
		gravaDados("consumidor_cidade", nome_cidade);
		gravaDados("consumidor_fone", fone);
		gravaDados("consumidor_endereco", endereco);
		gravaDados("consumidor_numero", numero);
		gravaDados("consumidor_complemento", complemento);
		gravaDados("consumidor_bairro", bairro);
		gravaDados("consumidor_cep", cep);
		gravaDados("consumidor_estado", estado);
		gravaDados("consumidor_revenda", tipo);
		gravaDados("consumidor_email", email);
	}

	function retorna_consumidor_suaos(cliente, nome, cpf, rg, nome_cidade, fone, endereco, numero, complemento, bairro, cep, estado, tipo, email, referencia, descricao, serie, nota_fiscal, data_nf)
	{
		gravaDados("cliente", cliente);
		gravaDados("consumidor_nome", nome);
		gravaDados("consumidor_cpf", cpf);
		gravaDados("consumidor_rg", rg);
		gravaDados("consumidor_cidade", nome_cidade);
		gravaDados("consumidor_fone", fone);
		gravaDados("consumidor_endereco", endereco);
		gravaDados("consumidor_numero", numero);
		gravaDados("consumidor_complemento", complemento);
		gravaDados("consumidor_bairro", bairro);
		gravaDados("consumidor_cep", cep);
		gravaDados("consumidor_estado", estado);
		gravaDados("consumidor_revenda", tipo);
		gravaDados("consumidor_email", email);
		gravaDados("produto_referencia", referencia);
		gravaDados("produto_nome", descricao);
		gravaDados("serie", serie);
		gravaDados("nota_fiscal", nota_fiscal);
		gravaDados("data_nf", data_nf);
	}

	function fnc_pesquisa_revenda(campo, tipo, cidade)
	{
		var url = "";
		if (tipo == "nome") {
			url = "pesquisa_revenda_callcenter.php?nome=" + campo.value + "&tipo=nome";
		}

		if (tipo == "cnpj") {
			url = "pesquisa_revenda_callcenter.php?cnpj=" + campo.value + "&tipo=cnpj";
		}

		if (tipo == "cidade") {
			url = "pesquisa_revenda_callcenter.php?cidade=" + campo.value + "&tipo=cidade";
		}

		if (tipo == "familia") {
			url = "pesquisa_revenda_callcenter.php?familia=" + campo.value + "&tipo=familia&consumidor_cidade=" + cidade.value;
		}

		janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.nome         = document.frm_callcenter.revenda_nome;
		janela.endereco     = document.frm_callcenter.revenda_endereco;
		janela.numero       = document.frm_callcenter.revenda_nro;
		janela.complemento  = document.frm_callcenter.revenda_cmpto;
		janela.bairro       = document.frm_callcenter.revenda_bairro;
		janela.cidade       = document.frm_callcenter.revenda_city;
		janela.estado       = document.frm_callcenter.revenda_uf;
		janela.fone         = document.frm_callcenter.revenda_fone;
		janela.revenda      = document.frm_callcenter.revenda;
		janela.focus();
	}

	function zxxx(campo)
	{
		url    = "pesquisa_os_callcenter.php?sua_os=" + campo;
		janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.sua_os		 = document.frm_callcenter.sua_os;
		janela.data_abertura = document.frm_callcenter.data_abertura;
		janela.data_nf	     = document.frm_callcenter.data_nf;
		janela.serie	     = document.frm_callcenter.serie;
		janela.nota_fiscal	 = document.frm_callcenter.nota_fiscal;
		janela.produto	     = document.frm_callcenter.produto;
		janela.produto_nome	 = document.frm_callcenter.produto_nome;
		janela.revenda_nome	 = document.frm_callcenter.revenda_nome;
		janela.revenda	     = document.frm_callcenter.revenda;
		//janela.posto       = document.frm_callcenter.posto;
		janela.posto_nome    = document.frm_callcenter.posto_nome;
		janela.focus();
	}

	/* ============= Função PESQUISA DE POSTO POR MAPA ====================
	Nome da Função : fnc_pesquisa_at_proximo()
	=================================================================*/
	function fnc_pesquisa_at_proximo(fabrica)
	{
		url    = "callcenter_interativo_posto.php?fabrica=12"+fabrica;
		janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=750,height=500,top=18,left=0");
		janela.posto_tab          = document.frm_callcenter.posto_tab;
		janela.codigo_posto_tab   = document.frm_callcenter.codigo_posto_tab;
		janela.posto_nome_tab     = document.frm_callcenter.posto_nome_tab;
		janela.posto_cidade_tab   = document.frm_callcenter.posto_cidade_tab;
		janela.posto_estado_tab   = document.frm_callcenter.posto_estado_tab;
		janela.posto_endereco_tab = document.frm_callcenter.posto_endereco_tab;
		janela.abas               = $('#container-Principal');
		janela.focus();
	}

	/* ========== Função AJUSTA CAMPO DE DATAS =========================
	Nome da Função : ajustar_data (input, evento)
			Ajusta a formatação da Máscara de DATAS a medida que ocorre
			a digitação do texto.
	=================================================================*/
	function ajustar_data(input , evento)
	{
		var BACKSPACE  = 8;
		var DEL        = 46;
		var FRENTE     = 39;
		var TRAS       = 37;
		var key;
		var tecla;
		var strValidos = "0123456789" ;
		var temp;
		tecla          = (evento.keyCode ? evento.keyCode: evento.which ? evento.which : evento.charCode)

		if( ( tecla == BACKSPACE )||(tecla == DEL)||(tecla == FRENTE)||(tecla == TRAS) )
		{
			return true;
		}

		if( tecla == 13 ) return false;
		if( (tecla<48) || (tecla>57) ){ return false; }
		key         = String.fromCharCode(tecla);
		input.value = input.value+key;
		temp        = "";

		for( var i=0; i<input.value.length; i++ )
		{
			if( temp.length==2 ) temp = temp+"/";
			if( temp.length==5 ) temp = temp+"/";
			if( strValidos.indexOf(input.value.substr(i, 1)) != -1 ){
				temp=temp+input.value.substr(i, 1);
			}
		}

		input.value = temp.substr(0,10);
		return false;
	}

	function createRequestObject()
	{
		var request_;
		var browser = navigator.appName;
		if( browser == "Microsoft Internet Explorer" ){
			 request_ = new ActiveXObject("Microsoft.XMLHTTP");
		}else{
			 request_ = new XMLHttpRequest();
		}
		return request_;
	}

/*var http1 = new Array();
function mostraDefeitos(natureza,produto){

	var curDateTime = new Date();
	http1[curDateTime] = createRequestObject();

	url = "callcenter_interativo_defeitos.php?ajax=true&natureza="+ natureza +"&produto=" + produto;
	http1[curDateTime].open('get',url);

	var campo = document.getElementById('div_defeitos');
//alert(natureza);
	http1[curDateTime].onreadystatechange = function(){
		if(http1[curDateTime].readyState == 1) {
			campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http1[curDateTime].readyState == 4){
			if (http1[curDateTime].status == 200 || http1[curDateTime].status == 304){
				var results = http1[curDateTime].responseText;
				campo.innerHTML   = results;
			}else {
				campo.innerHTML = "Erro";

			}
		}
	}
	http1[curDateTime].send(null);
}
*/

	var http2 = new Array();

	function localizarFaq(produto,local)
	{
		var faq_duvida = document.getElementById(local).value;
		var campo      = document.getElementById('div_'+local);
		if( produto.length == 0 ){
			alert('Por favor selecione o produto');
			return 0;
		}

		if( faq_duvida.length == 0 ){
			alert('Por favor inserir a dúvida');
			return 0;
		}

		var curDateTime    = new Date();
		http2[curDateTime] = createRequestObject();

		url = "callcenter_interativo_ajax.php?ajax=true&faq_duvida=true&produto=" + produto+"&duvida="+faq_duvida;
		http2[curDateTime].open('get',url);
		http2[curDateTime].onreadystatechange = function()
		{
			if( http2[curDateTime].readyState == 1 ){
				campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
			}

			if( http2[curDateTime].readyState == 4 ){
				if (http2[curDateTime].status == 200 || http2[curDateTime].status == 304){
					var results = http2[curDateTime].responseText;
					campo.innerHTML   = results;
				}else {
					campo.innerHTML = "Erro";
				}
			}
		}
		http2[curDateTime].send(null);
	}

	var http3 = new Array();

	function localizarConsumidor(busca, tipo)
	{
		if( tipo == 'novo' )
		{
			$('#tabela_consumidor input').each( function(){ $(this).val(''); });
			$('#consumidor_nome').focus();
			return false;
		}

		var campo          = document.getElementById('div_consumidor');
		var busca          = document.getElementById(busca).value;
		var curDateTime    = new Date();
		http3[curDateTime] = createRequestObject();
		url                = "callcenter_interativo_ajax.php?ajax=true&busca_cliente=tue&busca=" + busca + "&tipo=" + tipo;
		http3[curDateTime].open('get',url);

		http3[curDateTime].onreadystatechange = function()
		{
			if( http3[curDateTime].readyState == 1 ){
				campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
			}

			if( http3[curDateTime].readyState == 4 ){
				if( http3[curDateTime].status == 200 || http3[curDateTime].status == 304 ){
					var results = http3[curDateTime].responseText;
					campo.innerHTML   = results;
				}else {
					campo.innerHTML = "Erro";
				}
			}

			$("#consumidor_cep").mask("99.999-999");
			$("#hora_ligacao").mask("99:99");
		}
		http3[curDateTime].send(null);
	}

	function mostraEsconde(){ $("div[rel=div_ajuda]").toggle(); }

	var http4 = new Array();

	function fn_verifica_garantia()
	{
		var produto_nome       = document.getElementById('produto_nome_es').value;
		var produto_referencia = document.getElementById('produto_referencia_es').value;
		var serie              = document.getElementById('serie_es').value;
		var campo              = document.getElementById('div_estendida');
		var curDateTime        = new Date();
		http4[curDateTime]     = createRequestObject();
		url                    = "callcenter_interativo_ajax.php?ajax=true&garantia=tue&produto_nome=" + produto_nome + "&produto_referencia=" + produto_referencia+"&serie="+serie+"&data="+curDateTime;
		http4[curDateTime].open('get',url);

		http4[curDateTime].onreadystatechange = function()
		{
			if( http4[curDateTime].readyState == 1 ) {
				campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
			}
			if (http4[curDateTime].readyState == 4){
				if (http4[curDateTime].status == 200 || http4[curDateTime].status == 304){
					var results = http4[curDateTime].responseText;
					campo.innerHTML   = results;
				}else {
					campo.innerHTML = "Erro";
				}
			}

			$("#es_data_compra").mask("99/99/9999");
			$("#es_data_nascimento").mask("99/99/9999");
		}
		http4[curDateTime].send(null);
	}

	/* function mapa_rede(linha, estado, cidade, cep, endereco, numero, bairro, consumidor_cidade, consumidor_estado){
		url    = "mapa_rede_new.php?callcenter=true&pais=BR&estado="+estado.value+"&linha="+linha.value+"&cidade="+cidade.value+"&cep="+cep.value+"&consumidor="+endereco.value+","+numero.value+" "+bairro.value+" "+consumidor_cidade.value+" "+consumidor_estado.value;
		janela = window.open(url,"janela","width=700,height=300,scrollbars=yes,resizable=yes");
		janela.posto_tab           = document.frm_callcenter.posto_tab;
		janela.codigo_posto_tab    = document.frm_callcenter.codigo_posto_tab;
		janela.posto_nome_tab      = document.frm_callcenter.posto_nome_tab;
		janela.posto_nome_fantasia = document.frm_callcenter.posto_nome_fantasia;
		janela.posto_endereco      = document.frm_callcenter.posto_endereco;
		janela.fone_posto          = document.frm_callcenter.fone_posto;
		janela.posto_cidade        = document.frm_callcenter.posto_cidade;
		janela.posto_estado        = document.frm_callcenter.posto_estado;
		janela.posto_cep           = document.frm_callcenter.posto_cep;
	} */

	function mapa_rede(linha,estado,cidade,cep,endereco,numero,bairro,consumidor_cidade,consumidor_estado){

		var endereco_completo = "";
		var endereco_rota = "";

		if(endereco.value != ""){ endereco_completo += endereco.value+","; }
		if(numero.value != ""){ endereco_completo += numero.value+","; }
		if(bairro.value != ""){ endereco_completo += bairro.value+","; }
		if(consumidor_cidade.value != ""){ endereco_completo += consumidor_cidade.value+","; }
		if(consumidor_estado.value != ""){ endereco_completo += consumidor_estado.value; }
		endereco_completo += ", Brasil";

		if(endereco.value != ""){ endereco_rota += endereco.value+","; }
		if(numero.value != ""){ endereco_rota += numero.value+","; }
		if(consumidor_cidade.value != ""){ endereco_rota += consumidor_cidade.value+","; }
		if(consumidor_estado.value != ""){ endereco_rota += consumidor_estado.value; }
		endereco_rota += ", Brasil";

		var nome_cliente = $("#consumidor_nome").val();
		url = "mapa_rede_new.php?callcenter=true&pais=BR&estado="+estado.value+"&linha="+linha.value+"&cidade="+cidade.value+"&cep="+cep.value+"&consumidor_cidade="+consumidor_cidade.value+"&consumidor_estado="+consumidor_estado.value+"&consumidor="+endereco_completo+"&nome="+nome_cliente+"&endereco_rota="+endereco_rota;
		//janela = window.open(url,"janela","width=960,height=600,scrollbars=yes,resizable=yes");

		Shadowbox.open({
			content: url,
			player:	"iframe",
			title:		"Mapa da Rede",
			width:	800,
			height:	600
		});

		/*		
			janela.posto_tab        = document.frm_callcenter.posto_tab;
			janela.codigo_posto_tab = document.frm_callcenter.codigo_posto_tab;
			janela.posto_nome_tab   = document.frm_callcenter.posto_nome_tab;
			janela.posto_email_tab  = document.frm_callcenter.posto_email_tab;
			janela.posto_fone_tab   = document.frm_callcenter.posto_fone_tab;
			janela.posto_km_tab     = document.frm_callcenter.posto_km_tab;

		*/
	}

	function informacoesPosto(id, cidade){
		var dados = new Array();
		$.ajax({
			url: 'informacoes_posto.php',
			type: 'post',
			data: 'cod='+id,
			success: function(data){
				dados = data.split("|");
				$('#codigo_posto_tab').attr('value', dados[4]);
				$('#posto_tab').attr('value', id);
				$('#posto_nome_tab').attr('value', dados[0]);
				$('#posto_fone_tab').attr('value', dados[1]);
				$('#posto_email_tab').attr('value', dados[2]);
				$('#mapa_cidade').attr('value', cidade);
			}
		});
	}

	function fnc_pesquisa_os(campo, tipo)
	{
		var url = "";
		if (tipo == "os") {
			url = "pesquisa_os_callcenter.php?consumidor_cpf=" + campo.value + "&tipo=os";
		}

		if (tipo == "cpf") {
			url = "pesquisa_consumidor_callcenter.php?consumidor_cpf=" + campo.value + "&tipo=cpf";
		}

		if (tipo == "nota_fiscal") {
			url = "pesquisa_os_callcenter.php?nota_fiscal=" + campo.value + "&tipo=nota_fiscal";
		}

		if( campo.value != "" )
		{
			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0,resizable=yes");
			janela.produto_referencia = document.frm_callcenter.produto_referencia;
			janela.produto_nome       = document.frm_callcenter.produto_nome;
			janela.produto_serie      = document.frm_callcenter.serie;
			janela.produto_nf         = document.frm_callcenter.nota_fiscal;
			janela.produto_nf_data    = document.frm_callcenter.data_nf;
			janela.sua_os             = document.frm_callcenter.os;
			janela.posto_nome         = document.frm_callcenter.posto_nome;
			janela.posto_codigo       = document.frm_callcenter.codigo_posto;
			<?php if( $login_fabrica == 11 ){ // HD 14549 ?>
				janela.consumidor_nome        = document.frm_callcenter.consumidor_nome;
				janela.consumidor_cpf         = document.frm_callcenter.consumidor_cpf;
				janela.consumidor_cep         = document.frm_callcenter.consumidor_cep;
				janela.consumidor_fone        = document.frm_callcenter.consumidor_fone;
				janela.consumidor_endereco    = document.frm_callcenter.consumidor_endereco;
				janela.consumidor_numero      = document.frm_callcenter.consumidor_numero;
				janela.consumidor_complemento = document.frm_callcenter.consumidor_complemento;
				janela.consumidor_bairro      = document.frm_callcenter.consumidor_bairro;
				janela.consumidor_cidade      = document.frm_callcenter.consumidor_cidade;
				janela.consumidor_estado      = document.frm_callcenter.consumidor_estado;
				janela.abas                   = $('#container-Principal');
			<?php } ?>
			janela.focus();
		}
	}

	function atualizaQuadroMapas()
	{
		/* Atualiza os dados do posto conforme cidade e estado do Consumidor */
		var estado_selecionado = $('#consumidor_estado').val();

		/* Centro Oeste */
		if( estado_selecionado == 'GO' || estado_selecionado == 'MT' || estado_selecionado == 'MS' || estado_selecionado == 'DF' )
		{
			estado_selecionado = 'BR-CO';
		}

		/* Nordeste */
		if( estado_selecionado == 'AL' || estado_selecionado == 'BA' || estado_selecionado == 'CE' || estado_selecionado == 'MA' || estado_selecionado == 'PB' || estado_selecionado == 'PE' || estado_selecionado == 'PI' || estado_selecionado == 'RN' || estado_selecionado == 'SE' )
		{
			estado_selecionado = 'BR-NE';
		}

		/* Note */
		if( estado_selecionado == 'AC' || estado_selecionado == 'AP' || estado_selecionado == 'AM' || estado_selecionado == 'PA' || estado_selecionado == 'RR' || estado_selecionado == 'RO' || estado_selecionado == 'TO' )
		{
			estado_selecionado = 'BR-N';
		}

		$('#mapa_cidade').val( $('#consumidor_cidade').val() );
		$('#mapa_estado').val( estado_selecionado );
	}

	function txtBoxFormat(objForm, strField, sMask, evtKeyPress)
	{
		var i, nCount, sValue, fldLen, mskLen,bolMask, sCod, nTecla;

		if( document.all ){ // Internet Explorer
			nTecla = evtKeyPress.keyCode;
		} else if(document.layers) { // Nestcape
			nTecla = evtKeyPress.which;
		} else {
			nTecla = evtKeyPress.which;
			if( nTecla == 8 ){
				return true;
			}
		}

		sValue = objForm[strField].value;
		sValue = sValue.toString().replace( "-", "" );
		sValue = sValue.toString().replace( "-", "" );
		sValue = sValue.toString().replace( ".", "" );
		sValue = sValue.toString().replace( ".", "" );
		sValue = sValue.toString().replace( "/", "" );
		sValue = sValue.toString().replace( "/", "" );
		sValue = sValue.toString().replace( "/", "" );
		sValue = sValue.toString().replace( "(", "" );
		sValue = sValue.toString().replace( "(", "" );
		sValue = sValue.toString().replace( ")", "" );
		sValue = sValue.toString().replace( ")", "" );
		sValue = sValue.toString().replace( " ", "" );
		sValue = sValue.toString().replace( " ", "" );
		fldLen = sValue.length;
		mskLen = sMask.length;
		i      = 0;
		nCount = 0;
		sCod   = "";
		mskLen = fldLen;

		while( i <= mskLen )
		{
			bolMask = ((sMask.charAt(i) == "-") || (sMask.charAt(i) == ":") || (sMask.charAt(i) == ".") || (sMask.charAt(i) == "/"))
			bolMask = bolMask || ((sMask.charAt(i) == "(") || (sMask.charAt(i) == ")") || (sMask.charAt(i) == " ") || (sMask.charAt(i) == "."))

			if( bolMask ){
				sCod += sMask.charAt(i);
				mskLen++;
			} else {
				sCod += sValue.charAt(nCount);
				nCount++;
			}
			i++;
		}

		objForm[strField].value = sCod;

		if( nTecla != 8 ) // backspace
		{
			if( sMask.charAt(i-1) == "9" ) // apenas números...
			{
				return ((nTecla > 47) && (nTecla < 58)); // números de 0 a 9
			}
			else
			{
				return true; // qualquer caracter...
			}
		} else {
			return true;
		}
	}

<?php if( $login_fabrica == 3 ){ ?>
	window.onload = function foco()
	{
		var campo = document.getElementById("consumidor_nome");
		campo.focus();
	}
<?php } ?>

	var http5 = new Array();

	function listaFaq(produto)
	{
		var campo = document.getElementById('div_faq_duvida_duvida');
		if( produto.length==0 ){
			alert('Por favor selecione o produto');
		}else{

			var curDateTime    = new Date();
			http5[curDateTime] = createRequestObject();
			url                = "callcenter_interativo_ajax.php?ajax=true&listar=sim&produto=" + produto;
			http5[curDateTime].open('get',url);

			http5[curDateTime].onreadystatechange = function()
			{
				if( http5[curDateTime].readyState == 1 ){
					campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
				}

				if( http5[curDateTime].readyState == 4 ){
					if( http5[curDateTime].status == 200 || http5[curDateTime].status == 304 ){
						var results = http5[curDateTime].responseText;
						campo.innerHTML = results;
					}else {
						campo.innerHTML = "Erro";
					}
				}
			}
			http5[curDateTime].send(null);
		}
	}

	function gravaDados(name, valor)
	{
	    try { $("input[name="+name+"]").val(valor); $("#"+name).val(valor);} 
	    catch(err){ return false; }
	}
</script>

<br><br>

<?php if( strlen($msg_erro) > 0 ){ ?>

<?php // Recarrega informacoes
	$callcenter               = trim($_POST['callcenter']);
	$data_abertura_callcenter = trim($_POST['data_abertura_callcenter']);
	$categoria                = trim($_POST['categoria']);
	$consumidor_nome          = trim($_POST['consumidor_nome']);
	$cliente                  = trim($_POST['cliente']);
	$consumidor_cpf           = trim($_POST['consumidor_cpf']);
	$consumidor_cpf           = str_replace("/", "",$consumidor_cpf);
	$consumidor_cpf           = str_replace("-", "",$consumidor_cpf);
	$consumidor_cpf           = str_replace(".", "",$consumidor_cpf);
	$consumidor_cpf           = str_replace(",", "",$consumidor_cpf);
	$consumidor_rg            = trim($_POST['consumidor_rg']);
	$consumidor_rg            = str_replace("/", "",$consumidor_rg);
	$consumidor_rg            = str_replace("-", "",$consumidor_rg);
	$consumidor_rg            = str_replace(".", "",$consumidor_rg);
	$consumidor_rg            = str_replace(",", "",$consumidor_rg);
	$consumidor_email         = trim($_POST['consumidor_email']);
	$consumidor_fone          = trim($_POST['consumidor_fone']);
	$consumidor_fone2         = trim($_POST['consumidor_fone2']);
	$consumidor_cep           = trim($_POST['consumidor_cep']);
	$consumidor_cep           = str_replace("-", "",$consumidor_cep);
	$consumidor_cep           = str_replace("/", "",$consumidor_cep);
	$consumidor_endereco      = trim($_POST['consumidor_endereco']);
	$consumidor_numero        = trim($_POST['consumidor_numero']);
	$consumidor_complemento   = trim($_POST['consumidor_complemento']);
	$consumidor_bairro        = trim($_POST['consumidor_bairro']);
	$consumidor_cidade        = trim(strtoupper($_POST['consumidor_cidade']));
	$consumidor_estado        = trim(strtoupper($_POST['consumidor_estado']));
	$assunto                  = trim($_POST['assunto']);
	$sua_os                   = trim($_POST['sua_os']);
	$data_abertura            = trim($_POST['data_abertura']);
	$produto                  = trim($_POST['produto']);
	$produto_referencia       = trim($_POST['produto_referencia']);
	$produto_nome             = trim($_POST['produto_nome']);
	$serie                    = trim($_POST['serie']);
	$data_nf                  = trim($_POST['data_nf']);
	$mapa_linha               = trim($_POST['mapa_linha']);
	$nota_fiscal              = trim($_POST['nota_fiscal']);
	$revenda                  = trim($_POST['revenda']);
	$revenda_nome             = trim($_POST['revenda_nome']);
	$revenda_endereco         = trim($_POST['revenda_endereco']);
	$revenda_nro              = trim($_POST['revenda_nro']);
	$revenda_cmpto            = trim($_POST['revenda_cmpto']);
	$revenda_bairro           = trim($_POST['revenda_bairro']);
	$revenda_city             = trim($_POST['revenda_city']);
	$revenda_uf               = trim($_POST['revenda_uf']);
	$revenda_fone             = trim($_POST['revenda_fone']);
	$posto                    = trim($_POST['posto']);
	$posto_nome               = trim($_POST['posto_nome']);
	$defeito_reclamado        = trim($_POST['defeito_reclamado']);
	//	$reclamado                = trim($_POST['reclamado']);
	$status                   = trim($_POST['status']);
	$hd_situacao              = trim($_POST['hd_situacao']);
	$hd_motivo_ligacao        = trim($_POST['hd_motivo_ligacao']);
	$transferir               = trim($_POST['transferir']);
	$chamado_interno          = trim($_POST['chamado_interno']);
	$status_interacao         = trim($_POST['status_interacao']);
	$resposta                 = trim($_POST['resposta']);
	$abre_os                  = trim($_POST['abre_os']);
	$hd_extra_defeito         = trim($_POST['hd_extra_defeito']);
?>
	<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F7503E;font-size:10px'>
		<tr>
			<td align='center'><?php echo "<font color='#FFFFFF'>$msg_erro</font>"; ?></td>
		</tr>
	</table>
<?php
	}

	$sql = "SELECT nome from tbl_fabrica where fabrica = $login_fabrica";
	$res = pg_exec($con, $sql);

	$nome_da_fabrica = pg_result($res, 0, 0);
?>

<br>
<table width='98%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
	<tr>
		<td align='right' width='150'></td>
		<td align='right' width='55'>
			<img src='imagens/ajuda_call.png' align='absmiddle' onClick='javascript:mostraEsconde();'>
		</td>
		<td align='center'>
			<STRONG>APRESENTAÇÃO</STRONG><BR>
			<?php
				$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='1' AND fabrica = $login_fabrica";
				$pe = pg_exec($con, $sql);

				if( pg_numrows($pe) > 0 ){
					echo pg_result($pe, 0, 0);
				}else{
					if( $login_fabrica==25 ) echo "Hbflex"; else echo "$nome_da_fabrica"; ?>, <?php echo ucfirst($login_login); ?>, <?php echo saudacao(); ?>.<BR> O Sr.(a) já fez algum contato com a <?php if( $login_fabrica == 25 ) echo "Hbflex"; else echo "$nome_da_fabrica "; ?> <?php if( $login_fabrica == 25 ){ ?> por telefone ou pelo Site<?php } ?> ?
			<?php } ?>
		</td>
		<td align='right' width='150'></td>
	</tr>
</table>

<br />

<?php
if (strlen($callcenter) > 0) {
	include_once "class/aws/s3_config.php";

	include_once S3CLASS;

	$s3 = new AmazonTC('callcenter', (int) $login_fabrica);
?>
	<link rel="stylesheet" type="text/css" href="fancybox/jquery.fancybox-1.3.4.css" />

	<script src='js/FancyZoom.js'></script>
	<script src='js/FancyZoomHTML.js'></script>
	<script src="../js/jquery.form.js"></script>

	<style>
		.img_anexada {
			display: inline-block;
			width: 100px;
			height: 110px;
			border: 1px solid #000000;
			margin-left: 10px;
		}

		.img_anexada img {
			width: 100px;
			height: 90px;
		}

		.img_anexada .img_check_delete {
			display: block;
			width: 100%;
			border-top: 1px solid #000000;
		}
	</style>

	<script>
		$(function () {
			setupZoom();

			var anexando = false;

			var img_contador = {
				1: "div.img_anexada[rel=1]",
				2: "div.img_anexada[rel=2]",
				3: "div.img_anexada[rel=3]",
				4: "div.img_anexada[rel=4]",
				5: "div.img_anexada[rel=5]"
			};

			$("#anexarImagens").click(function () {
				if ($(".img_anexada").length == 1) {
					alert("O maximo de anexos por atendimento é de um arquivo");

					return false;
				}

				if (anexando === false) {
					$.each(img_contador, function (key, div) {
						if ($(div).length == 0) {
							$("#file_form").find("input[name=file_i]").val(key);
							return false;
						}
					});

					anexando = true;

					$("#anexando").show();
					$("#anexarImagens").hide();
					$("#file_form").submit();
				} else {	
					alert("Espere o upload atual finalizar!");
				}
			});

			var callcenter = $("input[name=callcenter]").val();

			$("#file_form").ajaxForm({
				data:{callcenter:callcenter},
                complete: function(data) {
                	data = $.parseJSON(data.responseText);

                    if (data.erro != undefined) {
                        alert(data.erro);
                    } else {
                        var file_div = $(".img_anexada_model").clone();

                        $(file_div).find("a").attr({ "href": data.file });
                        if (data.type != "pdf") {
                        	$(file_div).find("img").attr({ "src": data.file_mini });
                        } else {
                        	$(file_div).find("img").attr({ "src": "imagens/icone_pdf.jpg" });
                        }
                        $(file_div).find("input[name=img_anexada_nome]").val(data.file_name);
                        $(file_div).find("input[name=img_i]").val(data.i);
                        $(file_div).addClass("img_anexada").removeClass("img_anexada_model").css({ "display": "inline-block" }).attr({ "rel": data.i });

                        $(".td_img_anexadas").append(file_div);

                        setupZoom();

                        $("#file_form").find("input[name=file]").val("");

                        if (!$("#deleta_img_checked").is(":visible")) {
                        	$("#deleta_img_checked").show();
                        }
                    }

                    anexando = false;

                    $("#anexando").hide();
                    $("#anexarImagens").show();
                }
            });

			$("#deleta_img_checked").click(function () {
				if ($("input[name=img_anexada_nome]:checked").length > 0) {
					if (anexando === false) {
						var files = [];

						$("input[name=img_anexada_nome]:checked").each(function () {
							files.push($(this).val());
						});

						anexando = true;

						$("#deletando").show();
						$("#deleta_img_checked").hide();
						var callcenter = $("input[name=callcenter]").val();
						console.log(callcenter);
						$.ajax({
							url: "callcenter_upload_imagens.php",
							type: "POST",
							data: { files: files, deleta_imagens: true, callcenter:callcenter },
							complete: function (data) {

								data = $.parseJSON(data.responseText);

			                    if (data.erro != undefined) {
			                        alert(data.erro);
			                    } else {
			                    	$.each(files, function (key, value) {
			                    		$("input[name=img_anexada_nome][value='"+value+"']").parents("div.img_anexada").remove();
			                    	});
			                    }

								anexando = false;

                   				$("#deletando").hide();

                   				if ($(".img_anexada").length > 0) {
									$("#deleta_img_checked").show();
								}
							}
						});
					} else {	
						alert("Espere o processo atual finalizar!");
					}
				}
			});
		});
	</script>

	<br />

	<table style="margin: 0 auto;" >
		<tr>
			<td style="color: #FF0000; font-size: 14px;">
				* quantidade máxima de um anexo por atendimento
			</td>
		</tr>
		<tr>
			<td>
				<div style="text-align: center; height: 32px;">
					<form id="file_form" name="file_form" action="callcenter_upload_imagens.php" method="post" enctype="multipart/form-data" >
						<input type="file" name="file" value="" />
						<input type="hidden" name="file_hd_chamado" value="<?=$callcenter?>" />
						<input type="hidden" name="anexar_imagem" value="true" />
						<input type="hidden" name="file_i" value="" />
						<button type="button" id="anexarImagens" style="cursor: pointer;" >Anexar arquivo selecionado</button>
						<img class="loadImg" id="anexando" style="vertical-align: -14px; display: none;" src="imagens/loading_indicator_big.gif" />
					</form>
				</div>
			</td>
		</tr>
		<tr>
			<td style="text-align: center;" class="td_img_anexadas">
				<br />

				<div class="img_anexada_model" style="display: none;">
					<a href="" ><img src="" /></a>

					<br />

					<span class="img_check_delete" >
						<input type="checkbox" name="img_anexada_nome" value="" />
						<input type="hidden" name="img_i" value="" />
					</span>
				</div>

				<?php
				$s3->getObjectList("{$callcenter}-", false);

				if (count($s3->files) > 0) {
					$file_links = $s3->getLinkList($s3->files);

					foreach ($s3->files as $key => $file) {
						$img_i = preg_replace("/.*.\//", "", $file);
						$img_i = preg_replace("/\..*./", "", $img_i);
						$img_i = explode("-", $img_i);
						$img_i = $img_i[1];

						$file_name = preg_replace("/.*.\//", "", $file);

						$type  = trim(strtolower(preg_replace("/.+\./", "", $file_name)));

						if ($type != "pdf") {
							$file_thumb = $s3->getLink("thumb_".$file_name);

							if (!strlen($file_thumb)) {
								$file_thumb = $file_name;
							}
						} else {
							$file_thumb = "imagens/icone_pdf.jpg";
						}

						?>
						<div class="img_anexada" rel="<?=$img_i?>">
							<a href="<?=$file_links[$key]?>" ><img src="<?=$file_thumb?>" /></a>

							<br />

							<span class="img_check_delete" >
								<input type="checkbox" name="img_anexada_nome" value="<?=$file_name?>" />
								<input type="hidden" name="img_i" value="<?=$img_i?>" />
							</span>
						</div>
					<?php
					}
				}
				?>
			</td>
		</tr>
		<tr>
			<td style="text-align: center;">
				<br />
				<img id="deletando" class="loadImg" src="imagens/loading_indicator_big.gif" style="display: none;" />
				<button type="button" id="deleta_img_checked" style="display: <?=(count($s3->files) > 0) ? 'inline' : 'none'?>;" >Deletar os arquivos selecionados</button>
			</td>
		</tr>
	</table>

	<br />
<?php
}
?>

<form name="frm_callcenter" id="frm_callcenter" method="post" action="<?php $PHP_SELF; ?>">
	<input name="callcenter" class="input" type="hidden" value='<?php echo $callcenter; ?>'>
<table width="98%" border="0" align="center" cellpadding="2" cellspacing="2" style='font-size:12px'>
<tr>
	<td align='left'>

		<table width="100%" border='0'>
			<tr>
				<td align='left'><strong>Cadastro de Atendimento</strong></td>
				<?php if( strlen($callcenter)>0 AND $login_fabrica == 3 ){ ?>
					<td nowrap>Tipo de registro: <strong><?php echo $tipo_registro; ?></strong></td>
					<td nowrap>Situação Atual: <strong><?php echo $hd_situacao_descricao; ?></strong></td>
				<?php } ?>
				<td align='right'><strong><?php if(strlen($callcenter)>0){echo "nº <font color='#CC0033'>$callcenter</font>";}?></strong></td>
			</tr>
		</table>

	<?php if(strlen($callcenter) == 0 ){ ?>

		<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left' width='68'><strong>Localizar:</strong></td>
				<?php if( $login_fabrica == 3 ){ ?>
					<td align='left'>
						<input name="localizar" id='localizar' value='<?php echo $localizar ;?>' class="input" type="text" size="30" maxlength="500">  <a href='#' onclick="javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.localizar, 'os')">Por OS</a> | <a href='#' onclick='javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.localizar, "nome")'>Por Nome</a> | <a href='#' onclick="javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.localizar, 'atendimento')">Por atendimento</a> | <a href='#' onclick="javascript:localizarConsumidor('localizar','novo')">Novo consumidor</a>
					</td>
				<?php }else{ ?>
					<td align='left'>
						<input name="localizar" id='localizar' value='<?php echo $localizar ;?>' class="input" type="text" size="30" maxlength="500">  <a href='#' onclick="javascript:localizarConsumidor('localizar','cpf')">Por CPF</a> | <a href='#' onclick='javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.localizar, "nome")'>Por Nome</a> | <a href='#' onclick="javascript:localizarConsumidor('localizar','atendimento')">Por atendimento</a> | <a href='#' onclick="javascript:localizarConsumidor('localizar','cep')">Por CEP</a> | <a href='#' onclick="javascript:localizarConsumidor('localizar','telefone')">Por Telefone</a>
						| <a href='#' onclick="javascript:localizarConsumidor('localizar','novo')">Novo consumidor</a>
					</td>
				<?php } ?>
			</tr>
		</table>
	<?php } ?>

	</td>
</tr>
<tr>
	<td>

	<div id='div_consumidor' style='display:inline; Position:relative;background-color: #e6eef7;width:100%'>
		<table width='100%' border='0' align='center' cellpadding='2' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px' id='tabela_consumidor'>
		<!-- HD36903 -->
		<?php
			if( $login_fabrica == 2 ){
		?>
		<tr>
			<td colspan='6'  align='left'>
				<table border='0' cellpadding='3' cellspacing='0' width="50%">
					<tr>
						<td align='left'>
							<b>Tipo de atendimento:</b>
						</td>
						<td align='left'>
							Consumidor
							<input type='radio' name='consumidor_revenda' value='C' <?PHP if ($consumidor_revenda == 'C' or $consumidor_revenda == '') { echo "CHECKED";}?> onclick="fnc_tipo_atendimento(this)">
						</td>
						<td align='left'>
							Revenda
							<input type='radio' name='consumidor_revenda' value='R' <?PHP if ($consumidor_revenda == 'R') { echo "CHECKED";}?> onclick="fnc_tipo_atendimento(this)">
						</td>
						<td align='left'>
							Assistência Técnica
							<input type='radio' name='consumidor_revenda' value='A' <?PHP if ($consumidor_revenda == A) { echo "CHECKED";}?> onclick="fnc_tipo_atendimento(this)">
						</td>
					<tr>
				</table>
			</td>
		</tr>
		<?php
			}
		?>
		<tr>
			<td align='left'><strong>Nome:</strong></td>
			<td align='left'>
				<input name="consumidor_nome" id="consumidor_nome"  value='<?php echo $consumidor_nome ;?>' class="input" type="text" size="35" maxlength="500"
				 > <img src='imagens/lupa.png' id='label_nome' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, "nome")' style='cursor: pointer' >
			</td>
			<td align='left'><strong><span id='label_cpf'>CPF:</span></strong></td>
			<td align='left'>

				<input name="consumidor_cpf" id="cpf" value='<?php echo $consumidor_cpf ;?>' class="input" type="text" size="18" maxlength="14" onkeypress="return txtBoxFormat(this.form, this.name, '999.999.999-99', event);" >
				<img src='imagens/lupa.png' border='0' id='label_cnpj' align='absmiddle' style='cursor: pointer' onclick='javascript: fnc_pesquisa_consumidor_callcenter 	(document.frm_callcenter.consumidor_cpf, "cpf")'>
				<input name="cliente" id="cliente" value='<?php echo $cliente ;?>' type="hidden">
			</td>
			<td align='left'><strong>RG:</strong></td>
			<td align='left'>
				<input name="consumidor_rg" value='<?php echo $consumidor_rg ;?>'  class="input" type="text" size="14" maxlength="14" <? if($login_fabrica==11 and (strlen($callcenter) >0 or strlen($Id) >0) ){?>  readonly <?}?> >
			</td>
		</tr>
		<tr>
			<td align='left'><strong>E-mail:</strong></td>
			<td align='left'>
				<input name="consumidor_email" value='<?php echo $consumidor_email ;?>' class="input" type="text" size="40" maxlength="500" <? if($login_fabrica==11 and (strlen($callcenter) >0 or strlen($Id) >0) ){?>  readonly <?}?> >
			</td>
			<td align='left'><strong>Telefone:</strong></td>
			<td align='left'>
				<input name="consumidor_fone" id="telefone" value='<?php echo $consumidor_fone; ?>' class="input telefone" type="text" size="18" maxlength="15" <?php if( $login_fabrica == 11 and (strlen($callcenter) > 0 or strlen($Id) > 0 ) ){ ?> readonly <?php } ?> />
			</td>
			<td align='left'><strong>Cep:</strong></td>
			<td align='left'>
				<input name="consumidor_cep" id='cep' value='<?php echo $consumidor_cep ;?>' class="input" type="text" size="14" maxlength="9" onblur="buscaCEP(this.value, document.frm_callcenter.consumidor_endereco, document.frm_callcenter.consumidor_bairro, document.frm_callcenter.consumidor_cidade, document.frm_callcenter.consumidor_estado) ;" onkeypress="return txtBoxFormat(this.form, this.name, '99999-999', event);" <? if($login_fabrica==11 and (strlen($callcenter) >0 or strlen($Id) >0) ){?>  readonly <?}?> >
			</td>
		</tr>
		<tr>
			<td align='left'><strong>Endereço:</strong></td>
			<td align='left'>
				<input name="consumidor_endereco" id='consumidor_endereco' value='<?php echo $consumidor_endereco ;?>' class="input" type="text" size="40" maxlength="500" <? if($login_fabrica==11 and (strlen($callcenter) >0 or strlen($Id) >0) ){?>  readonly <?}?> >
			</td>
			<td align='left'><strong>Número:</strong></td>
			<td align='left'>
				<input name="consumidor_numero" id='consumidor_numero' value='<?php echo $consumidor_numero ;?>' class="input" type="text" size="18" maxlength="16" <? if($login_fabrica==11 and (strlen($callcenter) >0 or strlen($Id) >0) ){?>  readonly <?}?> >
			</td>
			<td align='left'><strong>Complem.</strong></td>
			<td align='left'>
				<input name="consumidor_complemento" id='consumidor_complemento' value='<?php echo $consumidor_complemento ;?>' class="input" type="text" size="14" maxlength="14" <? if($login_fabrica==11 and (strlen($callcenter) >0 or strlen($Id) >0) ){?>  readonly <?}?> >
			</td>
		</tr>
		<tr>
			<td align='left'><strong>Bairro:</strong></td>
			<td align='left'>
				<input name="consumidor_bairro" id='consumidor_bairro' value='<?php echo $consumidor_bairro ;?>' class="input" type="text" size="40" maxlength="30" <? if($login_fabrica==11 and (strlen($callcenter) >0 or strlen($Id) >0) ){?>  readonly <?}?> >
			</td>
			<td align='left'><strong>Estado:</strong></td>
			<td align='left'>
				<select name="consumidor_estado" id='consumidor_estado' style='width:81px; font-size:9px'>
				<?php
					$ArrayEstados = array('','AC','AL','AM','AP',
										  'BA','CE','DF','ES',
										  'GO','MA','MG','MS',
										  'MT','PA','PB','PE',
										  'PI','PR','RJ','RN',
										  'RO','RR','RS','SC',
										  'SE','SP','TO');
					for( $i=0; $i<=27; $i++ )
					{
						echo"<option value='".$ArrayEstados[$i]."'";
						if( $consumidor_estado == $ArrayEstados[$i] ) echo " selected";
						echo ">".$ArrayEstados[$i]."</option>\n";
					}
				?>
				</select>
			</td>
			<td align='left'><strong>Cidade:</strong></td>
			<td align='left'>
				<input name="consumidor_cidade" id='consumidor_cidade' <?=(!strlen($consumidor_estado)) ? "readonly" : ""?> value='<?php echo $consumidor_cidade ;?>'   class="input" type="text" size="18" maxlength="16" <? if($login_fabrica==11 and (strlen($callcenter) >0 or strlen($Id) >0) ){?>  readonly <?}?> >
				<input name="cidade"  class="input"  value='<?php echo $cidade ;?>' type="hidden">
			</td>
		</tr>
		<tr>
			<?php if( $login_fabrica <> 3 ){ // HD 48900 ?>
				<td colspan='2' align='left'>
					<strong>Melhor horário p/ contato: </strong>
					<input name="hora_ligacao" id='hora_ligacao' class="input" value='<?php echo $hora_ligacao ;?>' type="text" maxlength='5' size='7'>
				</td>
			<?php } ?>
			<td align='left'><strong>Origem:</strong></td>
			<td align='left'>
				<select name='origem' id='origem' style='width:102px;font-size:9px'>
					<?php if( $login_fabrica == 3 ){ // HD 48900 ?>
						<option value=''></option>
					<?php } ?>
					<option value='Telefone' <?php if ($origem == 'Telefone') { echo "Selected"; } ?>>Telefone</option>
					<option value='Email' <?php if ($origem == 'Email') { echo "Selected"; } ?> >E-mail</option>
				</select>
			</td>
			<!-- HD36903 -->
			<?php if( $login_fabrica != 2 ){ ?>
				<td align='left'><strong>Tipo:</strong></td>
				<td align='left'>
					<select name="consumidor_revenda" id='consumidor_revenda' style='width:81px; font-size:9px'>
						<?php if( $login_fabrica == 3 ){ // HD 48900 ?>
							<option value=''></option>
						<?php } ?>
						<option value='C' <? if($consumidor_revenda == "C") echo "Selected" ;?>>Consumidor</option>
						<option value='R' <? if($consumidor_revenda == "R") echo "Selected" ;?>>Revenda</option>
					</select>
				</td>
			<?php } ?>
		</tr>
		<tr>
		<?php // HD 51117 ?>
			<td align='left'><strong>Tipo de Registro:</strong></td>
			<td colspan='2' align='left'>
				<select name="tipo_registro" id='tipo_registro' style='width:81px; font-size:9px' onblur='tipoRegistro(this.value)'>
					<option value=' '></option>
					<option value='Contato'  <?php if($tipo_registro == "Contato")  echo "Selected" ;?>>Contato</option>
					<option value='Processo' <?php if($tipo_registro == "Processo") echo "Selected" ;?>>Processo</option>
				</select>
			</td>
			<?php if( $login_fabrica == 51 ){ ?>
			<td align='left' colspan='1'><strong>Telefone 2:</strong></td>
			<td align='left' colspan='3'>
				<input name="consumidor_fone2" id="telefone2" value='<?php echo $consumidor_fone2; ?>' class="input telefone" type="text" size="18" maxlength="15" />
			</td>
			<?php } ?>
			<?php if( $login_fabrica == 11 ){ // HD 14549 ?>
				<td align='left'><strong>OS:</strong></td>
				<td align='left'>
					<input name="os" class="input" value='<?php echo $sua_os; ?>' />
				</td>
			<?php } ?>
		</tr>
		</table>
	</div>
	<br>
	<table width='98%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
		<tr>
			<td align='right' width='150'></td>
			<td align='right' width='55'>
				<img src='imagens/ajuda_call.png' align='absmiddle' >
			</td>
			<td align='center'>
			<?php
				$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='2' AND fabrica = $login_fabrica";
				$pe  = pg_exec($con, $sql);

				if( pg_numrows($pe)>0 ){
					echo pg_result($pe, 0, 0);
				}else{
					echo "Qual o produto comprado?";
				}
			?>
			</td>
			<td align='right' width='150'></td>
		</tr>
	</table>

	<table width="100%" border='0'>
		<tr>
			<td align='left'><strong>Informações do produto</strong></td>
		</tr>
	</table>

	<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
		<tr>
			<td align='left'><strong>Referência:</strong></td>
			<td align='left'>
				<input name="produto_referencia" class="input" value='<?php echo $produto_referencia; ?>'
				onblur="javascript: fnc_pesquisa_produto2('', $('input[name=produto_referencia]').val());
				<?php if( $login_fabrica <> 51 ){ # HD 41923 ?>
					defeitoReclamado(document.frm_callcenter.produto_referencia.value);
				<?php } ?>
					atualizaQuadroMapas();" type="text" size="15" maxlength="15">
				<img src='imagens/lupa.png' border='0' align='absmiddle'
				style='cursor: pointer'
				onclick="javascript: fnc_pesquisa_produto2 ('', $('input[name=produto_referencia]').val())">
			</td>
			<td align='left'><strong>Descrição:</strong></td>
			<td align='left'>
				<input type='hidden' name='produto' value="<? echo $produto; ?>">
				<input name="produto_nome"  class="input" value='<?php echo $produto_nome ;?>'
				onblur="javascript: fnc_pesquisa_produto2 ($('input[name=produto_nome]').val(), '');
				<?php if ($login_fabrica <> 51){ ?>
					defeitoReclamado(document.frm_callcenter.produto_referencia.value);
				<?php } ?>
					atualizaQuadroMapas();" type="text" size="35" maxlength="500">
				<img src='imagens/lupa.png' border='0' align='absmiddle'
				style='cursor: pointer'
				onclick="javascript: fnc_pesquisa_produto2 ($('input[name=produto_nome]').val(), '');">
			</td>
			<td align='left'><strong>Série:</strong></td>
			<td align='left'>
				<input name="serie"  class="input"  value='<?php echo $serie;?>'>
					<img src='imagens/lupa.png' border='0' align='absmiddle'
				style='cursor: pointer'
				onclick="javascript: fnc_pesquisa_serie (document.frm_callcenter.serie)">

			</td>
		</tr>
		<tr>
			<td align='left'><strong>NF compra:</strong></td>
			<td align='left'>
				<input name="nota_fiscal" id='nota_fiscal' class="input" value='<?php echo $nota_fiscal;?>' >
			</td>
			<td align='left'><strong>Data NF:</strong></td>
			<td align='left'>
				<input name="data_nf" id='data_nf' class="input" rel='data' value='<?php echo $data_nf ;?>'>
			</td>
		</tr>
		<?php if( $login_fabrica == 3 ){ ?>
		<tr>
		<tr>
			<td colspan='2' align='left'>
				<a  href="javascript:fnc_pesquisa_os (document.frm_callcenter.nota_fiscal, 'nota_fiscal')">Clique aqui para ver todas as OSs cadastradas com esta nota fiscal</a>
			</td>
		</tr>
		<?php } ?>
	</table>

	<table width="100%" border='0'>
		<tr>
			<td align='left'><strong>Informações do Atendimento</strong></td>
		</tr>
	</table>

	<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
		<tr>
			<td align='left'><strong>Tipo:</strong></td>
			<td align='left'>
		<select name='categoria' id='categoria' class='frm' onblur="condicaoPagamento(this.value);">
			<?php
				if( $categoria =='informacao' )
				{
					echo "<option value='informacao'>Informação</option>";
				}
				elseif( $categoria =='reclamacao' )
				{
					echo "<option value='reclamacao'>Reclamação</option>";
				}
				elseif( $categoria =='sugestao' )
				{
					echo "<option value='sugestao'>Sugestão/Elogio</option>";
				}
				elseif( $categoria =='solicitacao' )
				{
					echo "<option value='solicitacao'>Solicitação</option>";
				}
				else
				{
					echo "<option value=''>Selecione o tipo de registro</option>";
				}
			?>
		</select>

		</td>
	</tr>
	<tr>
		<td align ='left'>
			<strong>Defeitos:</strong>
			<!--
			<a href="javascript:mostraDefeitos('Reclamação',document.frm_callcenter.produto_referencia.value)">Defeitos</a>
			-->
		</td>
		<td align='left' colspan='5' width='630' valign='top'>
			<select name="defeito_reclamado" id="defeito_reclamado" style='width:300px; font-size:9px' class="input" onFocus="defeitoReclamado(document.frm_callcenter.produto_referencia.value);">
			<option value=''></option>
			<?php if( strlen($defeito_reclamado) > 0 ){
				$sql = "SELECT defeito_reclamado, descricao 
						FROM tbl_defeito_reclamado
						WHERE defeito_reclamado = $defeito_reclamado";
				$res = pg_exec($con, $sql);

				if( pg_numrows($res)>0 ){
					for( $i=0; pg_numrows($res)>$i; $i++ )
					{
						$xdefeito_reclamado = pg_result($res,$i,defeito_reclamado);
						$descricao          = pg_result($res,$i,descricao);
						$selected           = " ";

						if( $xdefeito_reclamado == $defeito_reclamado ){
							$selected = " selected ";
						}
						echo "<option value='$xdefeito_reclamado' $selected>$descricao</option>";
					}
				}
                            }
			?>
			</select>
<!--
			<div id='div_defeitos' style='display:inline; Position:relative;background-color: #e6eef7;width:100%'>
			<?php if( strlen($defeito_reclamado) > 0 ){
					$sql = "SELECT defeito_reclamado,
									descricao
							FROM tbl_defeito_reclamado
							WHERE defeito_reclamado = $defeito_reclamado";
					$res = pg_exec($con,$sql);
					if(pg_numrows($res)>0){
						$defeito_reclamado_descricao = pg_result($res,0,descricao);
						echo "<input type='radio' checked value='$defeito_reclamado'><font size='1'>$defeito_reclamado_descricao</font>";
					}
				}
			?>
			</div>
-->
		</td>
	</tr>
	<tr>
		<td align='left' width='80'><strong>Motivo:</strong></td>
		<td align='left' width='90'>
			<select name="hd_motivo_ligacao" id="hd_motivo_ligacao" style='width:300px; font-size:9px' class="input" >
			 <option value=''></option>
			<?php
				$sql = "SELECT
							hd_motivo_ligacao,
							descricao
						FROM tbl_hd_motivo_ligacao
						WHERE fabrica = $login_fabrica
						ORDER BY descricao";
				$res = pg_exec($con,$sql);

				if( pg_numrows($res)>0 )
				{
					for( $i=0; pg_numrows($res)>$i; $i++ )
					{
						$xhd_motivo_ligacao = pg_result($res,$i,hd_motivo_ligacao);
						$descricao          = pg_result($res,$i,descricao);
						$selected           = " ";

						if( $xhd_motivo_ligacao == $hd_motivo_ligacao ){
							$selected=" selected ";
						}
						echo "<option value='$xhd_motivo_ligacao' $selected>$descricao</option>";
					}
				}
			?>
			</select>
		</td>
	</tr>
	<tr>
		<td align='left' valign='top'><strong>Descrição:</strong></td>
		<td align='left' colspan='5'>
			<TEXTAREA NAME="reclamado_produto" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?php echo $reclamado ;?></TEXTAREA>
		</td>
	</tr>
</table>

	<br>

	<table width="100%" border='0'>
		<tr>
			<td align='left'><strong>Posto Autorizado</strong></td>
		</tr>
	</table>

		<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left'>
					<strong>Codigo do Posto:&nbsp;</strong>
					<input name="codigo_posto" class="input" value='<?php echo $codigo_posto; ?>'
						onblur="javascript: fnc_pesquisa_posto_call (document.frm_callcenter.codigo_posto,document.frm_callcenter.posto_nome,document.frm_callcenter.posto_fone,'codigo');" type="text" size="15" maxlength="15">
						<img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer' onclick="javascript: fnc_pesquisa_posto_call (document.frm_callcenter.codigo_posto,document.frm_callcenter.produto_nome,
						document.frm_callcenter.posto_fone,'codigo');">
				</td>
				<td align='left'>
					<strong>Nome do Posto:&nbsp;</strong>
					<input name="posto_nome" class="input" value='<?php echo $posto_nome ;?>'
						onblur="javascript: fnc_pesquisa_posto_call (document.frm_callcenter.codigo_posto,document.frm_callcenter.posto_nome,document.frm_callcenter.posto_fone,'nome');" type="text" size="35" maxlength="500">
						<img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer' onclick="javascript: fnc_pesquisa_posto_call (document.frm_callcenter.codigo_posto,document.frm_callcenter.posto_nome,
						document.frm_callcenter.posto_fone,'nome');">
				</td>
				<td align='left'>
					<strong>Telefone:&nbsp;</strong>
					<input name="posto_fone" class="input telefone" value="<?php echo $posto_fone; ?>"
					<?php
						if (strlen($posto_fone)>0){
							echo " disabled";
					} ?>
					type="text" size="18" rel="fone">
				</td>
			</tr>
				<?php
				if(strlen($callcenter)==0){
					echo "<tr>";
					echo "<td align='left' colspan='3'>";
					echo "<strong><input type='checkbox' name='abre_os' id='abre_os' value='t' onClick='verificarImpressao(this)'> Abrir Pré-OS para o esta Autorizada</strong>";
					echo "<div id='imprimir_os' style='display:none'><strong>&nbsp;&nbsp;&nbsp;&nbsp;<input type='checkbox' name='imprimir_os' value='t'> Imprimir OS</strong></div>";
					echo "</td>";
					echo "</tr>";
				}
				?>
		</table>
		<br>

	<div rel='div_ajuda' style='display:inline; Position:relative;'>
		<table width='98%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
		<tr>
			<td align='right' width='150'></td>
			<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
			<td align='center'><STRONG><?php echo$consumidor_nome;?></STRONG><br>
			em que posso ajudá-lo?
			</td>
			<td align='right' width='150'></td>
		</tr>
		</table>
	</div>
	</td>
</tr>
<tr>
    <td align='center' colspan='5'>
		<?php
			if( strlen($callcenter) > 0 )
			{
				$sql = "SELECT
							tbl_hd_chamado_item.hd_chamado_item    ,
							to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY HH24:MI:SS') as data,
							tbl_hd_chamado_item.comentario         ,
							tbl_admin.login    ,
							tbl_hd_chamado_item.interno            ,
							tbl_hd_chamado_item.status_item        ,
							tbl_hd_chamado_item.interno            ,
							tbl_hd_chamado_item.enviar_email
						FROM tbl_hd_chamado_item
						JOIN tbl_admin on tbl_hd_chamado_item.admin = tbl_admin.admin
						JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado
						WHERE tbl_hd_chamado_item.hd_chamado = $callcenter
						AND   tbl_hd_chamado.fabrica_responsavel = $login_fabrica
								order by tbl_hd_chamado_item.data "; //	echo $sql;

				$res = pg_exec($con, $sql);

				if( pg_numrows($res) > 0 )
				{
					for( $x=0; pg_numrows($res)>$x; $x++ )
					{
						$data               = pg_result($res, $x, data);
						$comentario         = pg_result($res, $x, comentario);
						$atendente_resposta = pg_result($res, $x, login);
						$status_item        = pg_result($res, $x, status_item);
						$interno            = pg_result($res, $x, interno);
						$enviar_email       = pg_result($res, $x, enviar_email);
						$xx = $xx + 1;
		?>
					<table width='100%' border='0' align='center' cellpadding="2" cellspacing="1" style=' border:#485989 1px solid; background-color: #A0BFE0;font-size:10px'>
						<tr>
						<td align='left' valign='top'>
							<table style='font-size: 10px' border='0' width='100%'>
							<tr>
							<td align='left' width='70%'>Resposta: <strong><?php echo $xx; ?></strong> Por: <strong><?php echo nl2br($atendente_resposta); ?></strong> </td>
							<td align='right' nowrap><?php echo "$data"; ?></td>
							</tr>
							</table>
						</td>
						</tr>
						<?php if( $interno == "t" ){ ?>
							<tr>
								<td align='center' valign='top' bgcolor='#EFEBCF'>
									<?php echo "<font size='2'>Chamado Interno</font>";?>
								</td>
							</tr>
						<?php } ?>
						<?php if( $status_item == "Cancelado" or $status_item == "Resolvido" ){ ?>
							<tr>
								<td align='center' valign='top' bgcolor='#EFEBCF'><?php echo "<font size='2'>$status_item</font>"; ?></td>
							</tr>
						<?php } ?>
						<?php if( $enviar_email == "t" ){ ?>
							<tr>
								<td align='center' valign='top' bgcolor='#EFEBCF'><?php echo "<font size='2'>Conteúdo enviado por e-mail para o consumidor</font>";?></td>
							</tr>
						<?php } ?>
						<tr>
						<td align='left' valign='top' bgcolor='#FFFFFF'><?php echo nl2br($comentario);?></td>
						</tr>
						</table><br>
		<?php
					}
				}
			}

		?>
		</td>
	</tr>
	<tr>
 	<td align='center' colspan='5'>
	 <?php if( $login_fabrica == 3 ){ ?>
		<table width="100%" border='0'>
			<tr>
				<td align='left'><strong>Mapa da Rede</strong></td>
			</tr>
		</table>
		<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left' width='50'><strong>Linha:</strong></td>
				<td align='left'>
				<?php
				$sql = "SELECT  *
						FROM    tbl_linha
						WHERE   tbl_linha.fabrica = $login_fabrica
						ORDER BY tbl_linha.nome;";
				$res = pg_exec ($con,$sql);

				if (pg_numrows($res) > 0) {
					echo "<select id='mapa_linha'  name='mapa_linha' class='frm'>\n";
					echo "<option value=''>ESCOLHA</option>\n";
					for ($x = 0 ; $x < pg_numrows($res) ; $x++){
						$aux_linha = trim(pg_result($res,$x,linha));
						$aux_nome  = trim(pg_result($res,$x,nome));

						echo "<option value='$aux_linha'";
						if ($linha == $aux_linha){
							echo " SELECTED ";
							$mostraMsgLinha = "<br> da LINHA $aux_nome";
						}
						echo ">$aux_nome</option>\n";
					}
					echo "</select>\n&nbsp;";
				}
				?>
				</td>
				<td align='left' width='50'><strong>Estado:</strong></td>
				<td align='left'>
					<select name='mapa_estado' id='mapa_estado'>
						<option value='00' selected>Todos</option>
						<option value='SP'         >São Paulo</option>
						<option value='RJ'         >Rio de Janeiro</option>
						<option value='PR'         >Paraná</option>
						<option value='SC'         >Santa Catarina</option>
						<option value='RS'         >Rio Grande do Sul</option>
						<option value='MG'         >Minas Gerais</option>
						<option value='ES'         >Espírito Santo</option>
						<option value='BR-CO'      >Centro-Oeste</option>
						<option value='BR-NE'      >Nordeste</option>
						<option value='BR-N'       >Norte</option>
					</select>
				<td align='left' width='50'><strong>Cidade:</strong></td>
				<td align='left'><input type='text' id='mapa_cidade' name='mapa_cidade' value='<?=$mapa_cidade?>'>

					<input type='button' name='btn_mapa' value='mapa' onclick='javascript:mapa_rede(mapa_linha,mapa_estado,mapa_cidade,cep,consumidor_endereco,consumidor_numero,consumidor_bairro,consumidor_cidade,consumidor_estado)'>
					</font>
				</td>
			</tr>
				<tr>
					<td align='left'><strong>Código:</strong></td>
					<td align='left'>
						<input name="codigo_posto_tab" id="codigo_posto_tab"  class="input" value='<?php echo $codigo_posto_tab;?>'  type="text" size="15" maxlength="15">
					</td>
					<td align='left'><strong>Nome:</strong></td>
					<td align='left'>
						<input type='hidden' name='posto_tab' value="<? echo $posto_tab; ?>">
						<input name="posto_nome_tab" id="posto_nome_tab"  class="input" value='<?php echo $posto_nome_tab ;?>'  type="text" size="35" maxlength="500">
					</td>
					<td align='left'><strong>Nome Fantasia:</strong></td>
					<td align='left'>
						<input name="posto_nome_fantasia" id="posto_nome_fantasia"  class="input" value='<?php echo $posto_nome_fantasia ;?>'  type="text" size="35" maxlength="50">
					</td>
				</tr>
				<tr>
					<td align='left'><strong>Endereço:</strong></td>
					<td align='left'>
						<input name="posto_endereco" id="posto_endereco"  class="input" value='<?php echo $posto_endereco;?>'  type="text" size="35" maxlength="90">
					</td>
					<td align='left'><strong>Telefone:</strong></td>
					<td align='left'>
						<input name="fone_posto" id="fone_posto" class="input telefone" value='<?php echo $fone_posto ;?>'  type="text" size="20" >
					</td>
					<td align='left'><strong>Cidade:</strong></td>
					<td align='left'>
						<input name="posto_cidade" id="posto_cidade"  class="input" value='<?php echo $posto_cidade ;?>'  type="text" size="35" >
					</td>
				</tr>
				<tr>
					<td align='left'><strong>Estado:</strong></td>
					<td align='left'>
						<input name="posto_estado" id="posto_estado"  class="input" value='<?php echo $posto_estado;?>'  type="text" size="2" maxlength="2">
					</td>
					<td align='left'><strong>Cep:</strong></td>
					<td align='left'>
						<input name="posto_cep" id="posto_cep"  class="input" value='<?php echo $posto_cep ;?>'  type="text" size="15" >
					</td>
				</tr>
			</table>
			<BR>
		<?php } ?>

     <table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#5AA962 1px solid; background-color:#D1E7D3;font-size:10px'>
		<?php if(strlen($callcenter)>0){ ?>
			 <tr>
			 <td align='left' valign='top'> <strong>Resposta:</strong></td>
			 <td colspan='6' align='left'><TEXTAREA NAME="resposta" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?php echo $resposta ;?></TEXTAREA></td>
			 </tr>
		<?php } ?>
	 <tr>
		<td align='left' width='80'><strong>Transferir p/:</strong></td>
		<td align='left' width='90'>
			<select name="transferir" style='width:80px; font-size:9px' class="input" >
			 <option value=''></option>
			<?php $sql = "SELECT admin, login
						from tbl_admin
						where fabrica = $login_fabrica
						and ativo is true
						and (privilegios like '%call_center%' or privilegios like '*') order by login";
				$res = pg_exec($con, $sql);

				if(pg_numrows($res)>0){
					for($i=0;pg_numrows($res)>$i;$i++){
						$tranferir = pg_result($res,$i,admin);
						$tranferir_nome = pg_result($res,$i,login);
						echo "<option value='$tranferir'>$tranferir_nome</option>";
					}
				}
			?>
			</select>
		</td>
		<td align='left' width='80'><strong>Situação:</strong></td>
		<td align='left' width='90'>
			<select name="hd_situacao" id="hd_situacao" style='width:300px; font-size:9px' class="input" >
			<?php # HD 52767 - Só mostra se estiver com tipo de registro. Este combo é carregado por AJAX, de acordo com o tipo de resgistro.

				if(strlen($tipo_registro)>0) {
					echo "<option value=''></option>";
					$sql = "SELECT
								hd_situacao,
								descricao
							FROM tbl_hd_situacao
							WHERE fabrica = $login_fabrica
							AND tipo_registro ='$tipo_registro'
							AND '$hd_situacao' = '$hd_situacao'
							ORDER BY descricao";

					$res = pg_exec($con, $sql);

					if(pg_numrows($res)>0){
						for($i=0;pg_numrows($res)>$i;$i++){
							$xhd_situacao = pg_result($res,$i,hd_situacao);
							$descricao    = pg_result($res,$i,descricao);
							$selected     = " ";
							if($xhd_situacao == $hd_situacao ){
								$selected = " selected ";
							}
							echo "<option value='$xhd_situacao' $selected>$descricao</option>";
						}
					}
				}else{
					echo "<option value='' selected>Selecione o tipo de Registro</option>";
				}
			?>
			</select>
		</td>

<!--		<td align='left' width='50'><strong>Situação:</strong></td>
		<td align='left' width='85'>
			<select name="status_interacao" style='width:80px; font-size:9px' class="input" >
			<option value='Aberto'   <? if ($status_interacao=="Aberto") echo "SELECTED";?> >Aberto</option>
			<option value='Resolvido'  <? if ($status_interacao=="Resolvido") echo "SELECTED";?> >Resolvido</option>
			<option value='Cancelado' <? if ($status_interacao=="Cancelado") echo "SELECTED";?> >Cancelado</option>
			</select>
		</td>
-->
		<td align='left' nowrap>
			<INPUT TYPE="checkbox" NAME="chamado_interno" class="input" ><strong>Chamado Interno</strong>
		</td>

		<?php if( $login_fabrica==25 ){ ?>

			<td align='center' nowrap><a href='sedex_cadastro.php' target='blank'><strong>Abrir OS Sedex</strong></a></td>

		<?php } ?>

		<?php if($login_fabrica==35 and strlen($callcenter)>0){ ?>

			<td align='center' nowrap><INPUT TYPE="checkbox" NAME="envia_email" class="input" > <strong>Envia e-mail</strong></td>

		<?php } ?>

		<td align='center'>
			<input class="botao" type="hidden" name="btn_acao"  value=''>
			<input  class="input verifica_servidor" rel="frm_callcenter" type="button" name="bt" value='<? if(strlen($callcenter)==0) echo "Gravar Atendimento"; else echo "Gravar Alterações"; ?>' style='width:150px;font-size:12px;font-weight:bold;' onclick="javascript:if (document.frm_callcenter.btn_acao.value!='') alert('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.'); else{
			<?if($login_fabrica ==3) { // HD 48680
			  echo "if(confirm('Deseja confirmar o atendimento?') == true){ document.frm_callcenter.btn_acao.value='final';}else{ return; }";
			} else {
				echo "document.frm_callcenter.btn_acao.value='final';";
			 } ?>
			}
			">
		</td>
	</tr>
	</table>
</td>
</tr>

<?php if( strlen($callcenter)>0 ){ ?>

		<tr>
			<td align='center' colspan='5'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
					<td align='center'><STRONG>Por favor, queira anotar o n° do protocolo de atendimento</STRONG><BR>
					Número <font color='#D1130E'><?php echo $callcenter;?></font>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table><BR>
			</td>
		</tr>
		<tr>
			<td><a href='callcenter_interativo_print.php?callcenter=<?php echo $callcenter;?>' target='_blank' style='font-size:10px;font-family:Verdana;'><img src='imagens/img_impressora.gif'>Imprimir</a></td>
		</tr>
		<tr>
			<td align='center' colspan='5'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
					<td align='center'><STRONG>Posso ajudá-lo(a) em algo mais Sr.(a)?</STRONG><BR>
					</td>
					<td align='right' width='150'></td>
				</tr>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'></td>
					<td align='center'>
						<input  class="input"  type="button" name="bt" value='Sim' onclick="javascript:window.location='callcenter_interativo_new.php?Id=<?php echo $callcenter;?>';">
						<input  class="input"  type="button" name="bt" value='Não' onclick="javascript:window.location='callcenter_interativo_new.php';">
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>
				<bR>
			</td>
		</tr>
		<tr>
		<td align='center' colspan='5'>
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
			<tr>
				<td align='right' width='150'></td>
				<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
				<td align='center'><STRONG>FINALIZAR LIGAÇÃO</STRONG><BR>
				A <?php echo "$nome_da_fabrica";?> agradece a sua ligação, tenha um(a) <?php echo saudacao();?>.
				</td>
				<td align='right' width='150'></td>
			</tr>
			</table>
		</td>
		</tr>

	 <?php } ?>

</table>
</form>

<?php include "rodape.php"; ?>
