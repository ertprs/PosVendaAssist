<?
//as tabs definem a categoria do chamado

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';


# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];

	if (strlen($q)>2){

		if ($tipo_busca=="revenda"){
			$sql = "SELECT tbl_revenda.revenda, tbl_revenda.cnpj, tbl_revenda.nome
					FROM tbl_revenda
					JOIN tbl_revenda_compra USING(revenda)
					WHERE tbl_revenda_compra.fabrica = $login_fabrica ";

			if ($busca == "codigo"){
				$sql .= " AND tbl_revenda.cnpj like '%$q%' ";
			}else{
				$sql .= " AND UPPER(tbl_revenda.nome) ilike UPPER('%$q%') ";
			}

			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$revenda = trim(pg_result($res,$i,revenda));
					$cnpj    = trim(pg_result($res,$i,cnpj));
					$nome    = trim(pg_result($res,$i,nome));
					echo "$revenda|$cnpj|$nome";
					echo "\n";
				}
			}
		}

		if ($tipo_busca=="posto"){
			$sql = "SELECT tbl_posto.posto, tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto, tbl_posto_fabrica.nome_fantasia
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

			if ($tipo_busca == "codigo"){
				$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
			}else{
				$sql .= "  AND (
								UPPER(tbl_posto.nome) like UPPER('%$q%')
							OR  UPPER(tbl_posto_fabrica.nome_fantasia) like UPPER('%$q%')
								)";
			}

			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$posto         = trim(pg_result($res,$i,posto));
					$cnpj          = trim(pg_result($res,$i,cnpj));
					$nome          = trim(pg_result($res,$i,nome));
					$codigo_posto  = trim(pg_result($res,$i,codigo_posto));
					$nome_fantasia = trim(pg_result($res,$i,nome_fantasia));
					echo "$posto|$cnpj|$codigo_posto|$nome|$nome_fantasia";
					echo "\n";
				}
			}
		}
			if ($tipo_busca=="mapa_cidade"){

			$sql = "SELECT      DISTINCT tbl_posto.cidade
					FROM        tbl_posto_fabrica
					JOIN tbl_posto using(posto)
					WHERE       tbl_posto_fabrica.fabrica = $login_fabrica
					AND         tbl_posto.cidade LIKE UPPER('%$q%')
					ORDER BY    tbl_posto.cidade";


			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$mapa_cidade        = trim(pg_result($res,$i,cidade));
					echo "$mapa_cidade";
					echo "\n";
				}
			}
		}
	}
	exit;
}


$title = "Atendimento Call-Center";
$layout_menu = 'callcenter';

include 'funcoes.php';
function converte_data($date)
{
	//$date = explode("-", ereg_replace('/', '-', $date));
	$date = explode("-", preg_replace('/\//', '-', $date));
	$date2 = ''.$date[2].'-'.$date[1].'-'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}
function acentos1( $texto ){
	 $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","ñ","Ñ" );
	$array2 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" ,"á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç","ñ","ñ");
	return str_replace( $array1, $array2, $texto );
}
function acentos3( $texto ){
 $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","ñ","Ñ" );
 $array2 = array("A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","N","N" );
 return str_replace( $array1, $array2, $texto );
}

$btn_acao = $_POST['btn_acao'];
$msg_erro = "";
if(strlen($btn_acao)>0){

		$callcenter         = $_POST['callcenter'];
		$hd_chamado         = $callcenter;
		$tab_atual          = $_POST['tab_atual'];

		$categoria          = $_POST['categoria'];
		$tab_atual          = $categoria;
		//$status_interacao   = $_POST['status_interacao'];
		//campo será buscado através da tabela tbl_hd_situacao

		$hd_situacao        = $_POST['hd_situacao'];

		$hd_motivo_ligacao  = $_POST['hd_motivo_ligacao'];

		$transferir         = $_POST['transferir'];
		$chamado_interno    = $_POST['chamado_interno'];
		$envia_email        = $_POST['envia_email'];
		if(strlen($envia_email)==0){
			$xenvia_email = "'f'";
		}else{
			$xenvia_email = "'t'";
		}
		if(strlen($chamado_interno)>0){$xchamado_interno = "'t'";}else{$xchamado_interno="'f'";}
		if(strlen($transferir)==0){$xtransferir = $login_admin;}else{$xtransferir = $transferir;}
		if(strlen($status_interacao)>0){ $xstatus_interacao = "'".$status_interacao."'";}

		if(strlen($hd_situacao)>0){ $xhd_situacao = $hd_situacao;}
		if(strlen($hd_motivo_ligacao)>0){ $xhd_motivo_ligacao= $hd_motivo_ligacao;}


		if(strlen($tab_atual)==0 and $login_fabrica==25)      { $tab_atual = "extensao"; }
		if(strlen($tab_atual)==0 and $login_fabrica<>25)      { $tab_atual = "reclamacao_produto"; }
		if(strlen(trim($_POST['consumidor_revenda']))>0) {
			$xconsumidor_revenda        = "'".trim($_POST['consumidor_revenda'])."'";
		}else{
			$xconsumidor_revenda        = "'C'";
		}
		$xorigem                    = "'".trim($_POST['origem'])."'";

		$receber_informacoes       = $_POST['receber_informacoes'];
		$hora_ligacao              = $_POST['hora_ligacao'];
		if(strlen($hora_ligacao)==0){$xhora_ligacao = "null";}else{$xhora_ligacao = "'$hora_ligacao".":00'";}
		$defeito_reclamado         = $_POST['defeito_reclamado'];

		$abre_os                   = trim($_POST['abre_os']);
		$imprimir_os               = trim($_POST['imprimir_os']);
		$resposta                  = trim($_POST['resposta']);
		$posto_tab                 = trim(strtoupper($_POST['posto_tab']));
		$codigo_posto_tab          = trim(strtoupper($_POST['codigo_posto_tab']));
		$posto_nome_tab            = trim(strtoupper($_POST['posto_nome_tab']));
		$posto_endereco_tab        = trim(strtoupper($_POST['posto_endereco_tab']));
		$posto_cidade_tab          = trim(strtoupper($_POST['posto_cidade_tab']));
		$posto_estado_tab          = trim(strtoupper($_POST['posto_estado_tab']));
		$revenda_nome              = trim($_POST['revenda_nome']);
		$revenda_endereco          = trim($_POST['revenda_endereco']);
		$revenda_nro               = trim($_POST['revenda_nro']);
		$revenda_cmpto             = trim($_POST['revenda_cmpto']);
		$revenda_bairro            = trim($_POST['revenda_bairro']);
		$revenda_city              = trim($_POST['revenda_city']);
		$revenda_uf                = trim($_POST['revenda_uf']);
		$revenda_fone              = trim($_POST['revenda_fone']);
		$defeito_reclamado		   = trim($_POST['defeito_reclamado']);
		$faq_situacao              = trim($_POST['faq_situacao']);
		$reclama_posto             = trim($_POST['tipo_reclamacao']);
		$tipo_registro             = trim($_POST['tipo_registro']);
		$protocolo_atendimento	   = trim($_POST['protocolo_atendimento']);
		$consumidor_nome		   = trim($_POST['consumidor_nome']);

		if(strlen($consumidor_nome) == 0){
			$msg_erro.=" Insira o nome do consumidor\n<br>";
		}

		if(strlen($defeito_reclamado)==0){
			$msg_erro .= "Informe o Defeitos\n<br>";
		}

		if(strlen($protocolo_atendimento)==0){
			$msg_erro .= "Informe o número do Protocolo\n<br>";
		}

		if(strlen($resposta)==0)          { $xresposta  = "null";  }else{ $xresposta = "'".$resposta."'";}

		if(strlen($receber_informacoes)>0){
			$xreceber_informacoes = "'$receber_informacoes'";
		}else{
			$xreceber_informacoes = "'f'";
		}

		if($tab_atual == "extensao"){

			$produto_referencia = $_POST['produto_referencia_es'];
			$produto_nome       = $_POST['produto_nome_es'];
			$reclamado          = trim($_POST['reclamado_es']);
			if(strlen($reclamado)==0) {
				$xreclamado = "null";
			}else{
				$xreclamado = "'".$reclamado."'";
			}

			#$xserie              = $_POST['serie_es'];
			#if(strlen($_POST["serie"])>0) $xserie = $_POST['serie'];

			$xserie = $_POST['serie'];
			if(strlen($_POST["serie_es"])>0) $xserie = $_POST['serie_es'];

			if(strlen($produto_referencia) == 0){
				$msg_erro.=" Insira a referência do produto\n ";
			}
			if(strlen($produto_nome) == 0){
				$msg_erro.=" Insira nome do produto\n ";
			}
			if(strlen($xserie) == 0){
				$msg_erro.=" Insira o número de série do produto\n ";
			}


			$es_id_numeroserie        = $_POST['es_id_numeroserie'];
			$es_revenda_cnpj          = $_POST['es_revenda_cnpj'];

			$es_revenda               = $_POST['es_revenda'];
				if(strlen($es_revenda)==0){
					$xes_revenda = "NULL";
				}else{
					$xes_revenda = "'".$es_revenda."'";
				}

			$es_nota_fiscal           = $_POST['es_nota_fiscal'];
				if(strlen($es_nota_fiscal)==0){
					$xes_nota_fiscal = "NULL";
				}else{
					$xes_nota_fiscal = "'".$es_nota_fiscal."'";
				}

			$es_data_compra           = $_POST['es_data_compra'];
				if(strlen($es_data_compra)==0){
					$xes_data_compra = "NULL";
				}else{
					$xes_data_compra = "'".converte_data($es_data_compra)."'";
				}

			$es_municipiocompra       = $_POST['es_municipiocompra'];
				if(strlen($es_municipiocompra)==0){
					$xes_municipiocompra = "NULL";
				}else{
					$xes_municipiocompra = "'".$es_municipiocompra."'";
				}

			$es_estadocompra          = $_POST['es_estadocompra'];
				if(strlen($es_estadocompra)==0){
					$xes_estadocompra = "NULL";
				}else{
					$xes_estadocompra = "'".$es_estadocompra."'";
				}

			$es_data_nascimento       = $_POST['es_data_nascimento'];
				if(strlen($es_data_nascimento)==0){
					$xes_data_nascimento = "NULL";
				}else{
					$xes_data_nascimento = "'".converte_data($es_data_nascimento)."'";
				}

			$es_estadocivil           = $_POST['es_estadocivil'];
				if(strlen($es_estadocivil)==0){
					$xes_estadocivil = "NULL";
				}else{
					$xes_estadocivil = "'".$es_estadocivil."'";
				}

			$es_sexo                  = $_POST['es_sexo'];
				if(strlen($es_sexo)==0){
					$xes_sexo = "NULL";
				}else{
					$xes_sexo = "'".$es_sexo."'";
				}

			$es_filhos                = $_POST['es_filhos'];
				if(strlen($es_filhos)==0){
					$xes_filhos = "NULL";
				}else{
					$xes_filhos = "'".$es_filhos."'";
				}

			$es_fonecomercial         = $_POST['es_fonecomercial'];
				if(strlen($es_fonecomercial)==0){
					$xes_dddcomercial = " NULL ";
					$xes_fonecomercial = "NULL";
				}else{
					$xes_dddcomercial = "'".substr($es_fonecomercial,1,2)."'";
					$xes_fonecomercial = "'".substr($es_fonecomercial,5,9)."'";
				}


			$es_celular               = $_POST['es_celular'];
				if(strlen($es_celular)==0){
					$xes_dddcelular = " NULL ";
					$xes_celular    = "NULL";
				}else{
					$xes_dddcelular = "'".substr($es_celular,1,2)."'";
					$xes_celular = "'".substr($es_celular,5,9)."'";
				}

			$es_preferenciamusical    = $_POST['es_preferenciamusical'];
				if(strlen($es_preferenciamusical)==0){
					$xes_preferenciamusical = "NULL";
				}else{
					$xes_preferenciamusical = "'".$es_preferenciamusical."'";
				}

		}


			$produto_referencia = $_POST['produto_referencia'];
			$produto_nome       = $_POST['produto_nome'];
			$reclamado          = trim($_POST['reclamado_produto']);
			$xserie             = $_POST['serie'];
			if(strlen($reclamado)==0){
				$xreclamado = "null";
				//$msg_erro = "Insira a reclamação";
			}else{
				$xreclamado = "'".$reclamado."'";
			}


		if($tab_atual == "reclamacao_at"){
			$reclamado          = trim($_POST['reclamado_at']);
			$xserie             = $_POST['serie'];
			if(strlen($reclamado)==0){
				$msg_erro = "Insira a reclamação";
			}else{
				$xreclamado = "'".$reclamado."'";
			}
		}
		$posto_nome       = $_POST['posto_nome'];
		$codigo_posto     = $_POST['codigo_posto'];

		if(strlen($posto_nome) == 0 and strlen($codigo_posto) == 0){
			$msg_erro.="Para que a OS seja aberta é necessário escolher um posto<br>";
		}

		if ($login_fabrica == 2 AND $reclama_posto <> 'reclamacao_at'){
			$codigo_posto = "";
		}

		/*
		if ($login_fabrica == 2 AND $reclama_posto == 'reclamacao_at'){
			if(strlen($codigo_posto) == 0){
				$msg_erro .= "Ao selecionar Reclamação da Assitência Técnica é <br/>
				    obrigatório informar qual foi a assistência que gerou a reclamação.";
			}
		}*/

		if(strlen($codigo_posto_tab)>0){
			$sql = "SELECT posto
					from tbl_posto_fabrica
					where codigo_posto='$codigo_posto_tab'
					and fabrica = $login_fabrica";
			$res = pg_exec($con,$sql);

			if(pg_numrows($res)>0){
				$mr_codigo_posto = pg_result($res,0,0);
				$sqlMr = "SELECT endereco, numero, cidade, estado
						FROM tbl_posto
						WHERE posto = $mr_codigo_posto";
				$resMr = pg_exec($con,$sqlMr);

				if (pg_numrows($resMr)>0){
					$endereco_posto_tab = pg_result($resMr,0,endereco);
					$numero_posto_tab = pg_result($resMr,0,numero);
					$posto_endereco_tab = "$endereco_posto_tab, $numero_posto_tab";
					$posto_cidade_tab = pg_result($resMr,0,cidade);
					$posto_estado_tab = pg_result($resMr,0,estado);
				}
			}
		}

		if(strlen($codigo_posto)==0){
				$xcodigo_posto = "null";
		}else{
			$sql = "SELECT posto
					from tbl_posto_fabrica
					where codigo_posto='$codigo_posto'
					and fabrica = $login_fabrica";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				$xcodigo_posto = pg_result($res,0,0);
			}else{
				$xcodigo_posto = "null";
			}

		}

		$os               = trim($_POST['os']);
		if(strlen($os)==0){
			$xos = "null";
		}else{
			$sql = "SELECT os from tbl_os where sua_os='$os' and fabrica=$login_fabrica";
			//echo $sql;
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				$xos = pg_result($res,0,0);
			}else{
				$msg_erro .= "OS informada não encontrada no sistema";
			}
		}


		if($tab_atual == "reclamacao_empresa"){
			$reclamado                 = trim($_POST['reclamado_empresa']);
			if(strlen($reclamado)==0){
				$msg_erro = "Insira a reclamação";
			}else{
				$xreclamado = "'".$reclamado."'";
			}
		}

		if($tab_atual == "reclamacoes"){
			$reclamado                 = trim($_POST['reclamado']);
			$tipo_reclamado            = trim($_POST['tipo_reclamacao']);
			if(strlen($reclamado)==0){
				$msg_erro = "Insira a reclamação";
			}else{
				$xreclamado = "'".$reclamado."'";
			}
		}

		if($tab_atual == "duvida_produto"){
			$produto_referencia = $_POST['produto_referencia_duvida'];
			$produto_nome       = $_POST['produto_nome_duvida'];
			$xserie             = $_POST['troca_serie_duvida'];
		}

		if($tab_atual == "sugestao"){
			$reclamado                 = trim($_POST['reclamado_sugestao']);
			if(strlen($reclamado)==0){
				$msg_erro .= "Insira a sugestão";
			}else{
				$xreclamado = "'".$reclamado."'";
			}
		}
		if($tab_atual == "assistencia"){
			$produto_referencia = $_POST['produto_referencia_pa'];
			$produto_nome       = $_POST['produto_nome_pa'];
			$xserie             = $_POST['serie_pa'];
			$reclamado     = trim($_POST['reclamado_pa']);
			if(strlen($reclamado)==0){
				$xreclamado = "null";
			}else{
				$xreclamado = "'".$reclamado."'";
			}
		}

		if($tab_atual == "procon"){
			$reclamado     = trim($_POST['reclamado_procon']);
			if(strlen($reclamado)==0){
				$xreclamado = "null";
			}else{
				$xreclamado = "'".$reclamado."'";
			}
		}

		if($tab_atual == "garantia"){
			$produto_referencia = $_POST['produto_referencia_garantia'];
			$produto_nome       = $_POST['produto_nome_garantia'];
			$xserie             = $_POST['serie_garantia'];
			$reclamado     = trim($_POST['reclamado_produto_garantia']);
			if(strlen($reclamado)==0){
				$xreclamado = "null";
			}else{
				$xreclamado = "'".$reclamado."'";
			}
		}

		if($tab_atual == "troca_produto"){
			$produto_referencia = $_POST['troca_produto_referencia'];
			$produto_nome       = $_POST['troca_produto_nome'];
			$reclamado          = trim($_POST['troca_produto_descricao']);
			$xserie             = $_POST['troca_serie'];
			if(strlen($reclamado)==0){
				$xreclamado = "null";
				//$msg_erro = "Insira a reclamação";
			}else{
				$xreclamado = "'".$reclamado."'";
			}
			if(strlen($produto_referencia) == 0 AND strlen($produto_nome) == 0){
				$msg_erro = "Por favor escolha o produto.";
			}
		}
		$xrevenda      = "null";
		$xrevenda_nome = "''";

		if($tab_atual == "onde_comprar"){
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

			$xrevenda      = "$revenda";
			$xrevenda_nome = "'$xrevenda_nome'";
		}

		if($tab_atual == "ressarcimento"){
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

			$reclamado          = trim($_POST['troca_produto_descricao']);

			$data_pagamento    = trim($_POST['data_pagamento']);
			$procon            = trim($_POST['procon']);
			$numero_processo   = trim($_POST['numero_processo']);

			$valor_produto     = str_replace(",",".",$valor_produto);
			$valor_inpc        = str_replace(",",".",$valor_inpc);
			$valor_corrigido   = str_replace(",",".",$valor_corrigido);

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

		if($tab_atual == "sedex_reverso"){
			$troca_produto_referencia = trim($_POST['troca_produto_referencia']);
			$troca_produto_nome       = trim($_POST['troca_produto_nome']);
			$reclamado                = trim($_POST['troca_observacao']);

			$numero_objeto        = trim($_POST['numero_objeto']);
			$nota_fiscal_saida    = trim($_POST['nota_fiscal_saida']);
			$data_nf_saida        = trim($_POST['data_nf_saida']);
			$data_retorno_produto = trim($_POST['data_retorno_produto']);

			$procon            = trim($_POST['procon2']);
			$numero_processo   = trim($_POST['numero_processo2']);

			if(strlen($nota_fiscal_saida)==0){
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

		if(strlen($defeito_reclamado)==0) { $xdefeito_reclamado  = "null"; }else{ $xdefeito_reclamado = $defeito_reclamado;}
		if(strlen($reclamado)==0)         { $xreclamado          = "null"; }else{ $xreclamado = "'".$reclamado."'";}

		if(strlen($produto_referencia)>0){
			$sql = "SELECT tbl_produto.produto
						FROM  tbl_produto
						join  tbl_linha on tbl_produto.linha = tbl_linha.linha
						WHERE tbl_produto.referencia = '$produto_referencia'
						and tbl_linha.fabrica = $login_fabrica
						limit 1";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
	//		echo nl2br($sql)."<BR>";
			if(pg_numrows($res)>0){
				$xproduto = pg_result($res,0,0);
			}else{
				$xproduto = "null";
			}
		}else{
				$xproduto = "null";
		}

		if(strlen($troca_produto_referencia)>0){
			$sql = "SELECT tbl_produto.produto
						FROM  tbl_produto
						join  tbl_linha on tbl_produto.linha = tbl_linha.linha
						WHERE tbl_produto.referencia = '$troca_produto_referencia'
						and tbl_linha.fabrica = $login_fabrica
						limit 1";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
	//		echo nl2br($sql)."<BR>";
			if(pg_numrows($res)>0){
				$xproduto_troca = pg_result($res,0,0);
			}else{
				$xproduto_troca = "null";
			}
		}else{
				$xproduto_troca = "null";
		}

		if(strlen($faq_situacao) > 0){ // HD 45991
			$sql = "INSERT INTO tbl_faq (
				situacao,
				produto
			) VALUES (
				'$faq_situacao',
				$xproduto
			);";
			$res = @pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
			if(strlen($msg_erro) ==0 ){
				$sql = "SELECT email_cadastros FROM tbl_fabrica WHERE fabrica = $login_fabrica";
				$res=pg_exec($con,$sql);
				if(pg_numrows($res) > 0){
					$email_cadastros = pg_result($res,0,email_cadastros);
					$admin_email = "helpdesk@telecontrol.com.br";
					$remetente    = $admin_email;
					$destinatario = $email_cadastros ;
					$assunto      = "Nova dúvida cadastrada";
					$mensagem     = "Prezado, <br> Foi cadastrada uma nova dúvida no sistema para o produto $produto_referencia:<br>  - $faq_situacao <br><br>Por favor, entre na aba <b>Cadastro - Perguntas Frequentes</b> para cadastrar causa e solução da mesma. <br>Att <br>Equipe Telecontrol";
					$headers="Return-Path: <$admin_email>\nFrom:".$remetente."\nContent-type: text/html\n";
					mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);
				}
			}
		}

		#HD Chamado 13106 Bloqueia
		#HD Chamado 21419 DESBloqueia
		if ( $login_fabrica==25 AND strlen($xserie)>0 AND 1==2){
			$sql = "SELECT tbl_hd_chamado_extra.hd_chamado
					FROM tbl_hd_chamado_extra
					JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado=tbl_hd_chamado_extra.hd_chamado
					WHERE tbl_hd_chamado.fabrica        = $login_fabrica
					AND   tbl_hd_chamado_extra.serie    = '$xserie' ";
					//AND   tbl_hd_chamado_extra.produto  = $xproduto
			if (strlen($callcenter)>0){
				$sql .= " AND tbl_hd_chamado_extra.hd_chamado <> $callcenter ";
			}
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			if(pg_numrows($res)>0){
				$hd_chamado_serie = pg_result($res,0,0);
				$msg_erro .= "Número de série $xserie já cadastrado anteriormente. Número do chamado: <a href='callcenter_interativo.php?callcenter=$hd_chamado_serie' target='_blank'>$hd_chamado_serie</a> ";
			}
		}

		if(strlen($xserie)==0){$xserie="null";}else{$xserie = "'".$xserie."'";}

		if($login_fabrica ==11) { // HD 45078
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

/*INSERINDO*/
if(strlen($callcenter)==0){
		if(strlen($msg_erro)==0){
			$res = pg_exec ($con,"BEGIN TRANSACTION");
		}

		if ($tab_atual == 'reclamacoes') {
			$tab_atual = $tipo_reclamado;
		}

		if(strlen($msg_erro)==0 and strlen($callcenter)==0) {
			$sql = "INSERT INTO tbl_hd_chamado (
						admin                 ,
						data                  ,
						atendente             ,
						fabrica_responsavel   ,
						titulo                ,
						categoria             ,
						fabrica				  ,
						status
					)values(
						$login_admin            ,
						current_timestamp       ,
						$login_admin            ,
						$login_fabrica          ,
						'Atendimento interativo',
						'$tab_atual'            ,
						$login_fabrica			,
						'Aberto'
				)";
			//echo nl2br($sql)."<BR>";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			$res    = pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado')");
			$hd_chamado = pg_result ($res,0,0);
		}

		if(strlen($msg_erro)==0 and strlen($callcenter)==0 and $abre_os=='t'){
			if (($login_fabrica == 2) and (strlen($mr_codigo_posto) == 0)){
				$msg_erro = "Para que a OS seja aberta é necessário escolher um posto";
			}

			if (($login_fabrica == 2) and (strlen($mr_codigo_posto) > 0)){
				$rat_codigo_posto = $xcodigo_posto;
				$xcodigo_posto = $mr_codigo_posto;
			}

			if(strlen($xcodigo_posto)==0 OR $xcodigo_posto=='null') {
				$msg_erro = "Para que a OS seja aberta é necessário escolher um posto";
			}

			$xnota_fiscal = "'".$_POST["nota_fiscal"]."'";

			if(strlen($msg_erro)==0){
				if(strlen($data_nf)==0) $xdata_nf = "NULL";
				else                    $xdata_nf = "'".converte_data($data_nf)."'";
				/* A Britania nao quer abrir a OS pelo call-center quer somente pre-os.
				Então estaremos marcando na tbl_hd_chamado_extra o abre_os, e consultar no posto
				se existe um chamado call-center não resolvido (em aberto) com pedido de abertura de OS,
				isto será considerado pre-os */
				if($login_fabrica != 3){
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
								consumidor_fone_comercial ,
								consumidor_email   ,
								produto            ,
								serie              ,
								nota_fiscal        ,
								data_nf
							) VALUES (
								$xcodigo_posto                                                  ,
								$login_admin                                                    ,
								$login_fabrica                                                  ,
								CURRENT_DATE                                                    ,
								$cliente                                                        ,
								trim ('$consumidor_nome')                                       ,
								trim ('$consumidor_cpf')                                        ,
								trim ('$consumidor_cidade')                                     ,
								trim ('$consumidor_estado')                                     ,
								trim ('$consumidor_fone')                                       ,
								trim ('$consumidor_celular')                                    ,
								trim ('$consumidor_fone_comercial')                             ,
								trim ('$consumidor_email')                                      ,
								$xproduto                                                       ,
								$xserie                                                         ,
								$xnota_fiscal                                                   ,
								$xdata_nf
							);";

					$res = @pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);

					$sql = "SELECT CURRVAL ('seq_os')";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
					$xos = pg_result($res,0,0);
					$os = $xos;
					$sql = "SELECT fn_valida_os($xos,$login_fabrica)";
					$res = @pg_exec($con,$sql);
					$msg_erro .= @pg_errormessage($con);
					if (strpos($msg_erro,"CONTEXT:")) {
						$x = explode('CONTEXT:',$msg_erro);
						$msg_erro = $x[0];
					}
					if (strpos($msg_erro,"ERROR: ") !== false) {
						$x = explode('ERROR: ',$msg_erro);
						$msg_erro = $x[1];
					}
				}
			}
		}
			$data_nf = $_POST["data_nf"] ;
			if(strlen($data_nf)==0) $xdata_nf = "NULL";
			else                    $xdata_nf = "'".converte_data($data_nf)."'";

			if(strlen($xdata_nf) > 0){
				$sql_nf= "SELECT $xdata_nf > '2005-01-01' as valida_data";
				$res=pg_exec($con,$sql_nf);
				$valida_data = pg_result($res,0,valida_data);
				if($valida_data=='f') $msg_erro =" Data da nota muito antiga.";
			}

			if(strlen($msg_erro)==0 and strlen($callcenter)==0) {

				if (isset($rat_codigo_posto)){
					$xcodigo_posto = $rat_codigo_posto;
				}

			$xnota_fiscal = "'".$_POST["nota_fiscal"]."'";
			if(strlen($abre_os)==0){ $abre_os = 'f';}
			$xabre_os = "'".$abre_os."'";


				$sql = "INSERT INTO tbl_hd_chamado_extra(
							hd_chamado           ,
							hd_situacao			 ,
							reclamado            ,
							defeito_reclamado    ,
							posto                ,
							consumidor_revenda   ,
							nome                 ,
							abre_os				 ,
							ordem_montagem		 ,
							data_abertura_os
						)values(
							$hd_chamado                    ,
							$hd_situacao				   ,
							'$reclamado'                   ,
							$defeito_reclamado           ,
							$xcodigo_posto                 ,
							$xconsumidor_revenda           ,
							'$consumidor_nome'			   ,
							true						   ,
							$protocolo_atendimento         ,
							NOW()
						);";
				//echo nl2br($sql)."<BR>";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
				//$msg_erro = "aaa<br>";
				//$msg_erro .= $xrevenda;

				if($xstatus_interacao == "'Resolvido'" AND $login_fabrica <> 6){
					$sql = "INSERT INTO tbl_hd_chamado_item(
							hd_chamado   ,
							data         ,
							comentario   ,
							admin        ,
							interno      ,
							status_item  ,
							enviar_email
							)values(
							$hd_chamado       ,
							current_timestamp ,
							'Resolvido'       ,
							$login_admin      ,
							$xchamado_interno ,
							$xstatus_interacao,
							$xenvia_email
							)";
							//echo $sql;
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);

					if (strlen($msg_erro) == 0) {
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


				//IGOR - HD: 10441 QUANDO FOR INDICADO UM POSTO AUTORIZADO, DEVE-SE INSERIR UMA INTERAÇÃO NO CHAMADO

				if(strlen($posto_tab)>0){

					$comentario = "Indicação do posto mais próximo do consumidor: <br>
								Código: $codigo_posto_tab <br>
								Nome: $posto_nome_tab<br>
								Endereço: $posto_endereco_tab <br>
								Cidade: $posto_cidade_tab <br>
								Estado: $posto_estado_tab";
					if(strlen($xos)>0 AND $abre_os=='t'){
						$sql = "SELECT sua_os FROM tbl_os WHERE os = $xos AND fabrica = $login_fabrica";

						$res = @pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);
						if(@pg_numrows($res)>0){
							$xsua_os = pg_result($res,0,0);
						}
						if($login_fabrica == 3){
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
							$hd_chamado       ,
							current_timestamp ,
							'$comentario'       ,
							$login_admin      ,
							'f',
							$xstatus_interacao
							)";

					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}

			/* HD 37805 */
			if ($tab_atual == "ressarcimento" and strlen($msg_erro)==0){

				if (strlen($xdata_nf)== 0 OR $xdata_nf == 'NULL'){
					$msg_erro .= "Informe a data da Nota fiscal.";
				}

				$sql = "SELECT hd_chamado
						FROM tbl_hd_chamado_extra_banco
						WHERE hd_chamado = $hd_chamado ";
				$resx = @pg_exec($con,$sql);
				if(@pg_numrows($resx) == 0){
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
				if(@pg_numrows($resx) == 0){
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

				if (strlen($valor_produto)>0 AND strlen($valor_inpc)>0 AND strlen($msg_erro)==0){
					$sql = "SELECT CURRENT_DATE - data_nf AS qtde_dias
							FROM tbl_hd_chamado_extra
							WHERE hd_chamado = $hd_chamado ";
					$resx = @pg_exec($con,$sql);
					if(@pg_numrows($resx) > 0){
						#echo "<hr>";
						$qtde_dias = pg_result($resx,0,qtde_dias);
						if ($qtde_dias>0){
							$valor_corrigido = $valor_produto + ($valor_produto * $qtde_dias / 100);
							 $sql = "UPDATE tbl_hd_chamado_troca SET valor_corrigido = $valor_corrigido WHERE hd_chamado = $hd_chamado";
							$res = pg_exec($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}
					}
				}
			}


			/* HD 37805 */
			if ($tab_atual == "sedex_reverso" and strlen($msg_erro)==0){
				$sql = "SELECT hd_chamado
						FROM tbl_hd_chamado_troca
						WHERE hd_chamado = $hd_chamado ";
				$resx = @pg_exec($con,$sql);
				if(@pg_numrows($resx) == 0){
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

			if($tab_atual == "extensao" and strlen($msg_erro) == 0 and $login_fabrica==25){
				if (strlen($es_data_compra)==0){
					$msg_erro .= "Informe a data da Compra do produto.";
				}
			}

			/* ########################################################################### */
			/* ##################  grava no banco de dados da hbtech ##################### */
			/* ########################################################################### */
			if($tab_atual == "extensao" and strlen($msg_erro) == 0 and $login_fabrica==25){
				if(strlen($consumidor_fone)==15){
					 $xddd_consumidor       = "'".substr($consumidor_fone,2,2)."'";
					 $xfone_consumidor      = "'".substr($consumidor_fone,6,9)."'";
				}elseif(strlen($consumidor_fone)==9 or strlen($consumidor_fone)==8){
					 $xddd_consumidor       = "null";
					 $xfone_consumidor      = "'".$consumidor_fone."'";
				}elseif(strlen($consumidor_fone)==11 or strlen($consumidor_fone)==10){
					 $xddd_consumidor       = "'".substr($consumidor_fone,0,2)."'";
					 $xfone_consumidor      = "'".substr($consumidor_fone,2,9)."'";
				}elseif(strlen($consumidor_fone)==0){
					 $xddd_consumidor       = "NULL";
					 $xfone_consumidor      = "NULL";
				}else{
					 $xddd_consumidor       = "NULL";
					 $xfone_consumidor      = "'".$consumidor_fone."'";
				}

				 $xxes_data_compra = converte_data($es_data_compra);
/*voltar aqui*/
				 $sql = "SELECT garantia from tbl_produto where produto = $xproduto";
				 $res = pg_exec($con,$sql);
				 $garantia = pg_result($res,0,0);

				 $sql = "SELECT to_char(('$xxes_data_compra'::date + interval '$garantia month') + interval '6 month','YYYY-MM-DD') ";
  			     $res = pg_exec($con,$sql);
				 $es_garantia = "'".pg_result($res,0,0)."'";



				if(strlen($es_id_numeroserie)>0){
					include "conexao_hbtech.php";

					/*INSERINDO NO SITE DO HIBEATS, VERIFICAMOS ANTES SE EXISTE ESSE NUMERO DE SÉRIE E INSERIMOS OS DADOS DO CLIENTE*/
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
							)values(
								'$produto_referencia||$produto_nome',
								$xserie  ,
								'$consumidor_nome'      ,
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
								$es_garantia
							);";
						//		echo "$sql;<BR>";
					$res = mysql_query($sql) or die("Erro no Sql1: ".mysql_error());

					if (strlen(mysql_error())>0){
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
						if ( !mail("", $assunto, $mensagem, $cabecalho) ) {
						}
					}

					if ($xconsumidor_cpf == 'null' or strlen($xconsumidor_cpf)==0 ){
						$pesquisa_xconsumidor_cpf = " AND cpf  IS NULL ";
					}else{
						$pesquisa_xconsumidor_cpf = " AND cpf  = $xconsumidor_cpf";
					}
					$sql = "SELECT idGarantia FROM garantia WHERE numeroSerie = $xserie $pesquisa_xconsumidor_cpf";

					$res = mysql_query($sql) or die("Erro no Sql2:".mysql_error());

					if(mysql_num_rows($res)>0){
						$idGarantia = mysql_result($res,0,idGarantia);
						$sql = "UPDATE numero_serie SET idGarantia = $idGarantia WHERE numero = $xserie";
						$res = mysql_query($sql) or die("Erro no Sql:".mysql_error());
					}
				}
			}

			if (strlen($msg_erro) == 0) {
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

			if ($abre_os == 't' AND $imprimir_os == 't'){
				$imprimir_os = "&imprimir_os=t";
			}else{
				$imprimir_os = "";
			}

			// HD 26968
			if(strlen($xtransferir) >0 AND strlen($hd_chamado) >0 AND ($login_admin <> $xtransferir)){
				$sql = "UPDATE tbl_hd_chamado set atendente = $xtransferir
				WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
				and tbl_hd_chamado.hd_chamado = $hd_chamado	";
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
						)values(
						$hd_chamado       ,
						current_timestamp ,
						'Atendimento transferido de <b>$nome_ultimo_atendente</b> para <b>$nome_atendente</b>'       ,
						$login_admin      ,
						't'  ,
						$xstatus_interacao
						)";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}


			if (strlen($msg_erro) == 0) {
				$res = pg_exec($con,"COMMIT TRANSACTION");
				header ("Location: pre_os_britania_simplificada.php?callcenter=$hd_chamado&$imprimir_os#$tab_atual");
				exit;

			}else{
				$res = pg_exec($con,"ROLLBACK TRANSACTION");
			}
}/*INSERINDO*/

/*atualizando*/
if(strlen($callcenter)>0){
	if($xresposta=="null"){ $msg_erro = "Por favor insira a resposta";}
	$sql = "SELECT atendente,login
			from tbl_hd_chamado
			JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente
			where fabrica_responsavel= $login_fabrica
			and hd_chamado = $callcenter";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		$ultimo_atendente       = pg_result($res,0,atendente);
		$ultimo_atendente_login = pg_result($res,0,login);
	}
	/*echo $xresposta."<BR>";
	echo $xstatus_interacao."<BR>";
	echo $xtransferir."<BR>";
	echo $xchamado_interno;*/
	# HD 45756
	if($login_fabrica == 3) {
		if($ultimo_atendente <> $login_admin) {
			$msg_erro = "Sem permissão de alteração. Admin responsável: $ultimo_atendente_login";
		}
	}
	if(strlen($msg_erro)==0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		$sql = "INSERT INTO tbl_hd_chamado_item(
						hd_chamado   ,
						data         ,
						comentario   ,
						admin        ,
						interno      ,
						enviar_email
						)values(
						$callcenter       ,
						current_timestamp ,
						$xresposta        ,
						$login_admin      ,
						$xchamado_interno  ,
						$xenvia_email
						)";
						//echo $sql;

			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
	}

	if(strlen($posto_tab)>0){

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
				$hd_chamado       ,
				current_timestamp ,
				'$comentario'       ,
				$login_admin      ,
				'f',
				$xstatus_interacao
				)";
				//echo $sql;
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	if(strlen($msg_erro)==0 and $xenvia_email == "'t'"){//se é para enviar email para consumidor
		$sql = "select email
				from tbl_hd_chamado_extra
				where tbl_hd_chamado_extra.hd_chamado = $callcenter";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);

		if(pg_numrows($res)>0){
			$cliente_email = pg_result($res,0,email);
			if(strlen($cliente_email)>0){
				$sql = "Select email from tbl_admin where admin = $login_admin and fabrica = $login_fabrica";

				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
				if(pg_numrows($res)>0){
					$admin_email = pg_result($res,0,email);
				}else{
					$admin_email = "telecontrol@telecontrol.com.br";
				}
				$xxresposta = str_replace("'","",$xresposta);
				$remetente    = $admin_email;
				$destinatario = $cliente_email;
				$assunto      = "Resposta atendimento Call Center";
				$mensagem     = nl2br($xxresposta);
				$headers="Return-Path: <$admin_email>\nFrom:".$remetente."\nContent-type: text/html\n";
				mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), $headers);

			}
		}
	}

	if(strlen($msg_erro)==0){
		// HD 69915
		$xdata_nf = "'".$_POST['data_nf']."'"         ;
		if(strlen($data_nf)==0) $xdata_nf = "NULL";
		else                    $xdata_nf = "'".converte_data($data_nf)."'";
		$xnota_fiscal = "'".$_POST['nota_fiscal']."'" ;


		if($ultimo_atendente <> $xtransferir){
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
							$xstatus_interacao
							)";
					//		echo $sql;
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if(strlen($email_ultimo_atendente) >0 AND strlen($email_atendente) >0){

				$assunto       = "O atendimento $callcenter foi transferido para você";

				$corpo = "<P align=left><STRONG>Nota: Este e-mail é gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
				<P align=left>$nome_atendente,</P>
				<P align=justify>
				O atendimento $callcenter foi transferido de <b>$nome_ultimo_atendente</b> para você
				</P>";

				$body_top = "--Message-Boundary\n";
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

	//hd 14231 22/2/2008
	if(strlen($msg_erro)==0){
		if(strlen($consumidor_nome)>0 and strlen($xconsumidor_estado)>0 and strlen($xconsumidor_cidade)>0){
			$sql = "SELECT tbl_cidade.cidade
						FROM tbl_cidade
						where tbl_cidade.nome = $xconsumidor_cidade
						AND tbl_cidade.estado = $xconsumidor_estado
						limit 1";
				$res = pg_exec($con,$sql);
				//echo nl2br($sql)."<BR>";
				if(pg_numrows($res)>0){
					$cidade = pg_result($res,0,0);
				}
		}

		if(strlen($hd_chamado)>0 and $login_fabrica <>11){//*ja tem cadastro no telecontrol/
			$sql = "SELECT hd_chamado
					from tbl_hd_chamado_extra
					where hd_chamado=$hd_chamado";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				$xhd_chamado = pg_result($res,0,0);

				$sql = "UPDATE tbl_hd_chamado_extra set
							nome        = '$consumidor_nome'       ,
							reclamado   = '$reclamado'					 ,
							defeito_reclamado = $defeito_reclamados,
							ordem_montagem  = $protocolo_atendimento,
							hd_situacao		= $hd_situacao
						WHERE tbl_hd_chamado_extra.hd_chamado = $xhd_chamado";
				//echo nl2br($sql)."<BR>";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

			}
		}
	}


	/* HD 37805 */
	if ($tab_atual == "ressarcimento" and strlen($msg_erro)==0){

		$sql = "SELECT hd_chamado
				FROM tbl_hd_chamado_extra_banco
				WHERE hd_chamado = $hd_chamado ";
		$resx = @pg_exec($con,$sql);
		if(@pg_numrows($resx) == 0){
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
		if(@pg_numrows($resx) == 0){
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

		if (strlen($valor_produto)>0 AND strlen($valor_inpc)>0 AND strlen($msg_erro)==0){
			$sql = "SELECT CURRENT_DATE - data_nf AS qtde_dias
					FROM tbl_hd_chamado_extra
					WHERE hd_chamado = $hd_chamado ";
			$resx = @pg_exec($con,$sql);
			if(@pg_numrows($resx) > 0){
				#echo "<hr>";
				$qtde_dias = pg_result($resx,0,qtde_dias);
				if ($qtde_dias>0){
					$valor_corrigido = $valor_produto + ($valor_produto * $qtde_dias / 100);
					 $sql = "UPDATE tbl_hd_chamado_troca SET valor_corrigido = $valor_corrigido WHERE hd_chamado = $hd_chamado";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}
		}
	}


	/* HD 37805 */
	if ($tab_atual == "sedex_reverso" and strlen($msg_erro)==0){
		$sql = "SELECT hd_chamado
				FROM tbl_hd_chamado_troca
				WHERE hd_chamado = $hd_chamado ";
		$resx = @pg_exec($con,$sql);
		if(@pg_numrows($resx) == 0){
			$sql = "INSERT INTO tbl_hd_chamado_troca ( hd_chamado ) values ( $hd_chamado )";
			$res = pg_exec($con,$sql);
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
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	if($tab_atual == "extensao" and strlen($msg_erro) == 0 and $login_fabrica==25){
		if (strlen($es_data_compra)==0){
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

	if (strlen($msg_erro) == 0) {
	//	$res = pg_exec($con,"ROLLBACK TRANSACTION");
		$res = pg_exec($con,"COMMIT TRANSACTION");
		header ("Location: pre_os_britania_simplificada.php?callcenter=$hd_chamado");
		exit;
	}else{
		//echo $msg_erro;
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}



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


if(strlen($callcenter)>0){

	$sql = "SELECT	tbl_hd_chamado_extra.hd_chamado as callcenter,
					to_char(tbl_hd_chamado_extra.data_abertura,'DD/MM/YYYY') as abertura_callcenter,
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
					tbl_hd_situacao.descricao                   AS hd_situacao_descricao,
					tbl_hd_chamado_extra.hd_motivo_ligacao,
					tbl_cidade.nome as cidade_nome,
					tbl_cidade.estado,
					tbl_hd_chamado_extra.origem,
					tbl_admin.login as atendente,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY') as data,
					tbl_hd_chamado.status,
					tbl_hd_chamado.categoria as natureza_operacao,
					tbl_posto.posto,
					tbl_hd_chamado.titulo as assunto,
					tbl_hd_chamado.categoria,
					tbl_produto.produto,
					tbl_produto.linha,
					tbl_produto.referencia as produto_referencia,
					tbl_produto.descricao as produto_nome,
					tbl_defeito_reclamado.defeito_reclamado,
					tbl_defeito_reclamado.descricao as defeito_reclamado,
					tbl_hd_chamado_extra.reclamado,
					tbl_hd_chamado_extra.os,
					tbl_hd_chamado_extra.serie,
					to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') as data_nf,
					tbl_hd_chamado_extra.nota_fiscal,
					tbl_hd_chamado_extra.revenda,
					tbl_hd_chamado_extra.revenda_nome,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome as posto_nome,
					to_char(tbl_hd_chamado_extra.data_abertura_os,'DD/MM/YYYY') as data_abertura,
					tbl_hd_chamado_extra.receber_info_fabrica,
					tbl_os.sua_os as sua_os,
					tbl_hd_chamado_extra.abre_os,
					tbl_hd_chamado_extra.leitura_pendente,
					tbl_hd_chamado.atendente as atendente_pendente,
					tbl_hd_chamado_extra.defeito_reclamado as defeito_reclamado,
					tbl_hd_chamado_extra.defeito_reclamado as defeito_reclamado,
					tbl_hd_chamado_extra.ordem_montagem,
					tbl_hd_chamado_extra.numero_processo,
					tbl_hd_chamado_extra.tipo_registro
		FROM tbl_hd_chamado
		JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
		LEFT JOIN tbl_hd_situacao ON tbl_hd_situacao.hd_situacao = tbl_hd_chamado_extra.hd_situacao
		LEFT JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
		JOIN tbl_admin  on tbl_hd_chamado.atendente = tbl_admin.admin
		LEFT JOIN tbl_posto on tbl_hd_chamado_extra.posto = tbl_posto.posto
		LEFT JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto  and tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto
		LEFT JOIN tbl_revenda on tbl_revenda.revenda = tbl_hd_chamado_extra.revenda
		LEFT JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
		LEFT JOIN tbl_os on tbl_os.os = tbl_hd_chamado_extra.os
		WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
		AND tbl_hd_chamado.hd_chamado = $callcenter";
		$res = pg_exec($con,$sql);

		if(pg_numrows($res)>0){
			$callcenter                = pg_result($res,0,callcenter);
			$abertura_callcenter       = pg_result($res,0,abertura_callcenter);
			$data_abertura_callcenter  = pg_result($res,0,data);
			$categoria                 = pg_result($res,0,categoria);
			$consumidor_nome           = pg_result($res,0,nome);
			$cliente                   = pg_result($res,0,cliente);
			$consumidor_cpf            = pg_result($res,0,cpf);
			$consumidor_rg             = pg_result($res,0,rg);
			$consumidor_email          = pg_result($res,0,email);
			$consumidor_fone           = pg_result($res,0,fone);
			$consumidor_fone2          = pg_result($res,0,fone2);
			$consumidor_cep            = pg_result($res,0,cep);
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
			//$defeito_reclamado        = pg_result($res,0,defeito_reclamado);
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
			$defeito_reclamado        = pg_result($res,0,defeito_reclamado);
			$numero_processo          = pg_result($res,0,numero_processo);
			$tipo_registro            = pg_result($res,0,tipo_registro);
			$ordem_montagem           = pg_result($res,0,ordem_montagem);

			$sql ="SELECT	tbl_hd_chamado_troca.valor_corrigido   ,
							tbl_hd_chamado_troca.hd_chamado        ,
							to_char(tbl_hd_chamado_troca.data_pagamento,'DD/MM/YYYY') as data_pagamento,
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
			$res = pg_exec($con,$sql);

			if(pg_numrows($res)>0){
				$valor_corrigido           = pg_result($res,0,valor_corrigido);
				$hd_chamado                = pg_result($res,0,hd_chamado);
				$data_pagamento            = pg_result($res,0,data_pagamento);
				$ressarcimento             = pg_result($res,0,ressarcimento);
				$numero_objeto             = pg_result($res,0,numero_objeto);
				$nota_fiscal_saida         = pg_result($res,0,nota_fiscal_saida);
				$nota_fiscal_saida         = pg_result($res,0,nota_fiscal_saida);
				$data_nf_saida             = pg_result($res,0,data_nf_saida);
				$data_retorno_produto      = pg_result($res,0,data_retorno_produto);
				$valor_produto             = pg_result($res,0,valor_produto);
				$valor_inpc                = pg_result($res,0,valor_inpc);
				$valor_corrigido           = pg_result($res,0,valor_corrigido);
				$troca_produto_referencia  = pg_result($res,0,troca_produto_referencia);
				$troca_produto_descricao   = pg_result($res,0,troca_produto_descricao);
			}

			/* HD 37805 - Adicionei 59 - Arrumei esta parte de baixo*/
			if ($login_fabrica==59){
				$tipo_atendimento = array(	1 => 'reclamacao_produto',
											2 => 'reclamacao_empresa',
											3 => 'reclamacao_at',
											4 => 'duvida_produto',
											5 => 'sugestao',
											6 => 'onde_comprar',
											7 => 'ressarcimento',
											8 => 'sedex_reverso');
			}elseif ($login_fabrica == 2) {
				if ( $natureza_chamado == 'reclamacao_revenda' or $natureza_chamado == 'reclamacao_at' or $natureza_chamado == 'reclamacao_enderecos') {
					$natureza_chamado2 = $natureza_chamado;
					$natureza_chamado = "reclamacoes";
				}
				$tipo_atendimento = array(	1 => 'reclamacao_produto',
											2 => 'reclamacoes',
											3 => 'duvida_produto',
											4 => 'sugestao',
											5 => 'procon' ,
											6 => 'onde_comprar');
			} else {
				$tipo_atendimento = array(	1 => 'extensao',
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

			if ($imprimir_os == 't' AND strlen ($os) > 0 ) {
				echo "<script language='javascript'>";
				echo "window.open ('os_print.php?os=$os&qtde_etiquetas=$qtde_etiquetas','os_print','resizable=yes,resize=yes,toolbar=no,location=yes,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0')";
				echo "</script>";
			}
		}
}

$Id = $_GET['Id'];
if(strlen($Id)>0){
	$sql = "SELECT	tbl_hd_chamado_extra.hd_chamado as callcenter,
					to_char(tbl_hd_chamado_extra.data_abertura,'DD/MM/YYYY') as abertura_callcenter,
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
					tbl_cidade.nome as cidade_nome,
					tbl_cidade.estado,
					tbl_produto.produto,
					tbl_produto.linha,
					tbl_produto.referencia as produto_referencia,
					tbl_produto.descricao as produto_nome,
					tbl_hd_chamado_extra.serie,
					to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') as data_nf,
					tbl_hd_chamado_extra.nota_fiscal,
					tbl_hd_chamado_extra.abre_os,
					tbl_hd_chamado_extra.leitura_pendente,
					tbl_hd_chamado.atendente as atendente_pendente,
					tbl_hd_chamado_extra.defeito_reclamado as defeito_reclamado,
					tbl_hd_chamado_extra.tipo_registro
		FROM tbl_hd_chamado
		JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
		LEFT JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
		LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto
		WHERE tbl_hd_chamado.hd_chamado = $Id";
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){
		$consumidor_nome           = pg_result($res,0,nome);
		$defeito_reclamado		   = pg_result($res,0,defeito_reclamado);
		$cliente                   = pg_result($res,0,cliente);
		$consumidor_cpf            = pg_result($res,0,cpf);
		$consumidor_rg             = pg_result($res,0,rg);
		$consumidor_email          = pg_result($res,0,email);
		$consumidor_fone           = pg_result($res,0,fone);
		$consumidor_fone2          = pg_result($res,0,fone2);
		$consumidor_cep            = pg_result($res,0,cep);
		$consumidor_endereco      = pg_result($res,0,endereco);
		$consumidor_numero        = pg_result($res,0,numero);
		$consumidor_complemento   = pg_result($res,0,complemento);
		$consumidor_bairro        = pg_result($res,0,bairro);
		$consumidor_cidade        = pg_result($res,0,cidade_nome);
		$consumidor_estado        = pg_result($res,0,estado);
		$produto                  = pg_result($res,0,produto);
		$produto_referencia       = pg_result($res,0,produto_referencia);
		$produto_nome             = pg_result($res,0,produto_nome);
		$serie                    = pg_result($res,0,serie);
		$data_nf                  = pg_result($res,0,data_nf);
		$nota_fiscal              = pg_result($res,0,nota_fiscal);
		$revenda                  = pg_result($res,0,consumidor_revenda);
		$abre_os                  = pg_result($res,0,abre_os);
		$leitura_pendente         = pg_result($res,0,leitura_pendente);
		$atendente_pendente       = pg_result($res,0,atendente_pendente);
		$tipo_registro            = pg_result($res,0,tipo_registro);

	}
}

include "cabecalho.php";

?>
<style>

.input {font-size: 10px;
		  font-family: verdana;
		  BORDER-RIGHT: #666666 1px double;
		  BORDER-TOP: #666666 1px double;
		  BORDER-LEFT: #666666 1px double;
		  BORDER-BOTTOM: #666666 1px double;
		  BACKGROUND-COLOR: #ffffff}

.respondido {font-size: 10px;
				color: #4D4D4D;
			  font-family: verdana;
			   BORDER-RIGHT: #666666 1px double;
		  BORDER-TOP: #666666 1px double;
		  BORDER-LEFT: #666666 1px double;
		  BORDER-BOTTOM: #666666 1px double;
			  BACKGROUND-COLOR: #ffffff;
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
/*	width:680px;*/

	padding-left: 2px;
	padding-right: 2px;
	padding-top: 2px;
	padding-bottom: 2px;

}

.padding {
	padding-left: 150px;
}
</style>

<!--=============== <FUNÇÕES> ================================!-->
<? include "javascript_pesquisas.php" ?>
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>

<script type="text/javascript" src="js/firebug.js"></script>
<?include 'javascript_calendario.php'?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script type="text/javascript" src="js/jquery.maskedinput.js"></script>

<script src="js/jquery.tabs.pack.js" type="text/javascript"></script>
<link rel="stylesheet" href="js/jquery.tabs.css" type="text/css" media="print, projection, screen">
<!-- Additional IE/Win specific style sheet (Conditional Comments) -->
<!--[if lte IE 7]>
<link rel="stylesheet" href="js/jquery.tabs-ie.css" type="text/css" media="projection, screen">
<![endif]-->

<script type="text/javascript">
<?
if($login_fabrica==25 OR $login_fabrica==59){
	$w=1;
}else if($login_fabrica == 45){
	$w=1;
	$posicao = $posicao-1;
}else if($login_fabrica == 46 OR $login_fabrica == 11){
	$w=1;
	$posicao = $posicao-1;
}else if ($login_fabrica == 2){
	$w=1;
	$posicao = $posicao;
}else{
	$w=1;
	if($posicao>=10) $posicao = $posicao-4;
	else             $posicao = $posicao-1;
}



?>
	$(function() {
		$('#container-Principal').tabs( <? if(strlen($callcenter)>0){ echo "$posicao,"; }?>{fxSpeed: 'fast'} );
	<? if(strlen($callcenter)>0){for($x=$w;$x<12;$x++){
		if($x<>$posicao) {?>
		$('#container-Principal').disableTab(<?echo $x;?>);
	<? } }}?>

//		$('#container').disableTab(3);
		//fxAutoHeight: true,
		$("#consumidor_cpf").maskedinput("999.999.999-99");
		$("#consumidor_fone").maskedinput("(999) 9999-9999");
		$("#consumidor_cep").maskedinput("99999-999");
		$("#hora_ligacao").maskedinput("99:99");
		$("input[@rel='data']").maskedinput("99/99/9999");
		$("input[@rel='fone']").maskedinput("(99) 9999-9999")

	});
$().ready(function() {

	function formatItem(row) {
		return row[1] + " - " + row[2];
	}

	function formatItemPosto(row) {
		return row[2] + " - " + row[3] + " (Fantasia:" + row[4] + ")";
	}


	/* Busca pelo Código */
	$("#revenda_cnpj").autocomplete("<?echo $PHP_SELF.'?tipo_busca=revenda&busca=codigo'; ?>", {
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
	$("#revenda_nome").autocomplete("<?echo $PHP_SELF.'?tipo_busca=revenda&busca=nome'; ?>", {
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
		$("#revenda").val(data[0]) ;
		$("#revenda_cnpj").val(data[1]) ;
		$("#revenda_nome").val(data[2]) ;
		//alert(data[2]);
	});

	$("#mapa_cidade").autocomplete("<?echo $PHP_SELF.'?tipo_busca=mapa_cidade&busca=mapa_cidade'; ?>", {
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
	$("#codigo_posto_tab").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItemPosto,
		formatResult: function(row) {
			return row[2];
		}
	});

	$("#codigo_posto_tab").result(function(event, data, formatted) {
		$("#posto_tab").val(data[0]) ;
		$("#codigo_posto_tab").val(data[2]) ;
		$("#posto_nome_tab").val(data[3]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome_tab").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItemPosto,
		formatResult: function(row) {
			return row[3];
		}
	});

	$("#posto_nome_tab").result(function(event, data, formatted) {
		$("#posto_tab").val(data[0]) ;
		$("#codigo_posto_tab").val(data[2]) ;
		$("#posto_nome_tab").val(data[3]) ;
		//alert(data[2]);
	});


});


function verificarImpressao(check){
	if (check.checked){
		$('#imprimir_os').show();
	}else{
		$('#imprimir_os').hide();
	}
}

function fnc_pesquisa_produto2 (campo, campo2, tipo, mapa_linha) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}


	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&mapa_linha=t";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;
		janela.mapa_linha   = mapa_linha;
		janela.focus();
	}
}

function MudaCampo(campo){
	if (campo.value == 'reclamacao_at') {
		document.getElementById('info_posto').style.display='inline';
	}else{
		document.getElementById('info_posto').style.display='none';
	}
}

function fnc_pesquisa_posto_call(campo, campo2, campo3, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_call2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.fone    = campo3;
		janela.focus();
	}
}

function tipoRegistro(tipo) {

	$.ajax({
		type: "GET",
		url: "callcenter_tipo_registro_ajax.php",
		data: "tipo_registro=" + tipo,
		cache: false,
		beforeSend: function() {
			// enquanto a função esta sendo processada, você
			// pode exibir na tela uma
			// msg de carregando
		},
		success: function(txt) {
			// pego o id da div que envolve o select com
			// name="id_modelo" e a substituiu
			// com o texto enviado pelo php, que é um novo
			//select com dados da marca x
			$('#categoria').html(txt);
		},
		error: function(txt) {
			alert(txt);
		}
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

function condicaoPagamento(categoria) {
	tipo_registro = document.getElementById('tipo_registro').value;

	$.ajax({
		type: "GET",
		url: "callcenter_motivo_ligacao_ajax.php",

		data: "categoria=" + categoria +"&tipo_registro=" + tipo_registro,
		cache: false,
		beforeSend: function() {
			// enquanto a função esta sendo processada, você
			// pode exibir na tela uma
			// msg de carregando
		},
		success: function(txt) {
			// pego o id da div que envolve o select com
			// name="id_modelo" e a substituiu
			// com o texto enviado pelo php, que é um novo
			//select com dados da marca x
			$('#hd_motivo_ligacao').html(txt);
		},
		error: function(txt) {
			alert(txt);
		}
	});
}


function fnc_pesquisa_serie (campo) {
	if (campo.value != "") {
		var url = "";
		url = "serie_pesquisa.php?produto_serie=" + campo.value;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = document.frm_callcenter.produto_referencia;
		janela.descricao    = document.frm_callcenter.produto_nome;
		janela.serie        = document.frm_callcenter.serie;
		janela.focus();
	}
}


function verificaNumero(e) {
	if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
		return false;
	}
}

$(document).ready(function() {
	$("#protocolo_atendimento").keypress(verificaNumero);
});

</script>

<script type="text/javascript" src="js/thickbox.js"></script>

<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script language="JavaScript">
function atualizaMapa(){
	var cidade = $('#consumidor_cidade').val();
	var estado = $('#consumidor_estado').val();
	$('#link').attr('href','callcenter_interativo_posto.php?fabrica=12<?echo $login_fabrica;?>&cidade='+cidade+'&estado='+estado+'&keepThis=trueTB_iframe=true&height=400&width=700')
	$('#link2').attr('href','callcenter_interativo_posto.php?fabrica=12<?echo $login_fabrica;?>&cidade='+cidade+'&estado='+estado+'&keepThis=trueTB_iframe=true&height=400&width=700')
}
function minimizar(arquivo){
	if (document.getElementById(arquivo)){
		var style2 = document.getElementById(arquivo);
		if (style2==false) return;
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
		}
	}
}

function formata_data(valor_campo, form, campo){
	var mydata = '';
	mydata = mydata + valor_campo;
	myrecord = campo;
	myform = form;

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

function fnc_pesquisa_consumidor_callcenter (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pre_os_britania_simplificada.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cpf") {
		url = "pre_os_britania_simplificada.php?cpf=" + campo.value + "&tipo=cpf";
	}
	if (tipo == "telefone") {
		url = "pre_os_britania_simplificada.php?telefone=" + campo.value + "&tipo=telefone";
	}
	if (tipo == "cep") {
		url = "pre_os_britania_simplificada.php?cep=" + campo.value + "&tipo=cep";
	}
	if (tipo == "atendimento") {
		url = "pesquisa_consumidor_callcenter_new_britania.php?atendimento=" + campo.value + "&tipo=atendimento";
	}

	if (tipo == "os") {
		url = "pesquisa_consumidor_callcenter_new_britania.php?os=" + campo.value + "&tipo=os";
	}
	if (campo.value != "") {
		janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=550,height=450,top=18,left=0");
		janela.cliente      = document.frm_callcenter.cliente;
		janela.nome         = document.frm_callcenter.consumidor_nome;
		janela.cpf          = document.frm_callcenter.consumidor_cpf;
		janela.rg           = document.frm_callcenter.consumidor_rg;
		janela.cidade       = document.frm_callcenter.consumidor_cidade;
		janela.estado       = document.frm_callcenter.consumidor_estado;
		janela.fone         = document.frm_callcenter.consumidor_fone;
		janela.endereco     = document.frm_callcenter.consumidor_endereco;
		janela.numero       = document.frm_callcenter.consumidor_numero;
		janela.complemento  = document.frm_callcenter.consumidor_complemento;
		janela.bairro       = document.frm_callcenter.consumidor_bairro;
		janela.cep          = document.frm_callcenter.consumidor_cep;
		janela.tipo         = document.frm_callcenter.consumidor_revenda;
		janela.email        = document.frm_callcenter.consumidor_email;
		janela.referencia   = document.frm_callcenter.produto_referencia;
		janela.descricao    = document.frm_callcenter.produto_nome;
		janela.serie        = document.frm_callcenter.serie;
		janela.nota_fiscal  = document.frm_callcenter.nota_fiscal;
		janela.data_nf      = document.frm_callcenter.data_nf;
		janela.focus();
	}
}


function fnc_pesquisa_revenda (campo, tipo,cidade) {
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

function zxxx (campo) {

	url = "pesquisa_os_callcenter.php?sua_os=" + campo;
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.sua_os			= document.frm_callcenter.sua_os;
	janela.data_abertura	= document.frm_callcenter.data_abertura;
	janela.data_nf	        = document.frm_callcenter.data_nf;
	janela.serie	        = document.frm_callcenter.serie;
	janela.nota_fiscal	    = document.frm_callcenter.nota_fiscal;
	janela.produto	        = document.frm_callcenter.produto;
	janela.produto_nome	    = document.frm_callcenter.produto_nome;
	janela.revenda_nome	    = document.frm_callcenter.revenda_nome;
	janela.revenda	        = document.frm_callcenter.revenda;
	//janela.posto        	= document.frm_callcenter.posto;
	janela.posto_nome     	= document.frm_callcenter.posto_nome;

	janela.focus();

}

/* ============= Função PESQUISA DE POSTO POR MAPA ====================
Nome da Função : fnc_pesquisa_at_proximo()
=================================================================*/
function fnc_pesquisa_at_proximo(fabrica) {
	url = "callcenter_interativo_posto.php?fabrica=12"+fabrica;
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=750,height=500,top=18,left=0");
	janela.posto_tab = document.frm_callcenter.posto_tab;
	janela.codigo_posto_tab = document.frm_callcenter.codigo_posto_tab;
	janela.posto_nome_tab = document.frm_callcenter.posto_nome_tab;
	janela.posto_cidade_tab = document.frm_callcenter.posto_cidade_tab;
	janela.posto_estado_tab= document.frm_callcenter.posto_estado_tab;
	janela.posto_endereco_tab = document.frm_callcenter.posto_endereco_tab;
	janela.abas = $('#container-Principal');
	janela.focus();
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

var http2 = new Array();
function localizarFaq(produto,local){
	var faq_duvida = document.getElementById(local).value;
	var campo = document.getElementById('div_'+local);
	if(produto.length==0){
		alert('Por favor selecione o produto');
		return 0;
	}

	if(faq_duvida.length==0){
		alert('Por favor inserir a dúvida');
		return 0;
	}

	var curDateTime = new Date();
	http2[curDateTime] = createRequestObject();

	url = "callcenter_interativo_ajax.php?ajax=true&faq_duvida=true&produto=" + produto+"&duvida="+faq_duvida;
	http2[curDateTime].open('get',url);

	http2[curDateTime].onreadystatechange = function(){
		if(http2[curDateTime].readyState == 1) {
			campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http2[curDateTime].readyState == 4){
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
function localizarConsumidor(busca,tipo){
	if (tipo=='novo'){
		$('#tabela_consumidor input').each( function(){
			$(this).val('');
		});
		$('#consumidor_nome').focus();
		return false;
	}
	var campo = document.getElementById('div_consumidor');
	var busca = document.getElementById(busca).value;
	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();

	url = "callcenter_interativo_ajax.php?ajax=true&busca_cliente=tue&busca=" + busca + "&tipo=" + tipo;
	http3[curDateTime].open('get',url);

	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
				var results = http3[curDateTime].responseText;
				campo.innerHTML   = results;
			}else {
				campo.innerHTML = "Erro";

			}
		}

		$("#consumidor_fone").maskedinput("(999) 9999-9999");
		$("#consumidor_cep").maskedinput("99999-999");
		$("#hora_ligacao").maskedinput("99:99");


	}
	http3[curDateTime].send(null);



}

function mostraEsconde(){
	$("div[@rel=div_ajuda]").toggle();
}
var http4 = new Array();
function fn_verifica_garantia(){
	var produto_nome       = document.getElementById('produto_nome_es').value;
	var produto_referencia = document.getElementById('produto_referencia_es').value;
	var serie              = document.getElementById('serie_es').value;
	 var campo = document.getElementById('div_estendida');
	var curDateTime = new Date();
	http4[curDateTime] = createRequestObject();

	url = "callcenter_interativo_ajax.php?ajax=true&garantia=tue&produto_nome=" + produto_nome + "&produto_referencia=" + produto_referencia+"&serie="+serie+"&data="+curDateTime;
	http4[curDateTime].open('get',url);

	http4[curDateTime].onreadystatechange = function(){
		if(http4[curDateTime].readyState == 1) {
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
		$("#es_data_compra").maskedinput("99/99/9999");
		$("#es_data_nascimento").maskedinput("99/99/9999");
		$("#es_fonecomercial").maskedinput("(99) 9999-9999");
		$("#es_celular").maskedinput("(99) 9999-9999");

	}
	http4[curDateTime].send(null);

}


function mapa_rede(linha,estado,cidade,cep,endereco,numero,bairro, consumidor_cidade,consumidor_estado){
	url = "mapa_rede.php?callcenter=true&pais=BR&estado="+estado.value+"&linha="+linha.value+"&cidade="+cidade.value+"&cep="+cep.value+"&consumidor="+endereco.value+","+numero.value+" "+bairro.value+" "+consumidor_cidade.value+" "+consumidor_estado.value;
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

}
function fnc_pesquisa_os (campo, tipo) {
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
	if (campo.value != "") {
		janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0,resizable=yes");

		janela.produto_referencia      = document.frm_callcenter.produto_referencia;
		janela.produto_nome            = document.frm_callcenter.produto_nome;
		janela.produto_serie           = document.frm_callcenter.serie;
		janela.produto_nf              = document.frm_callcenter.nota_fiscal;
		janela.produto_nf_data         = document.frm_callcenter.data_nf;
		janela.sua_os                  = document.frm_callcenter.os;
		janela.posto_nome              = document.frm_callcenter.posto_nome;
		janela.posto_codigo            = document.frm_callcenter.codigo_posto;
		<? if($login_fabrica==11) { //HD 14549 ?>
			janela.consumidor_nome         = document.frm_callcenter.consumidor_nome;
			janela.consumidor_cpf          = document.frm_callcenter.consumidor_cpf;
			janela.consumidor_cep          = document.frm_callcenter.consumidor_cep;
			janela.consumidor_fone         = document.frm_callcenter.consumidor_fone;
			janela.consumidor_endereco     = document.frm_callcenter.consumidor_endereco;
			janela.consumidor_numero       = document.frm_callcenter.consumidor_numero;
			janela.consumidor_complemento  = document.frm_callcenter.consumidor_complemento;
			janela.consumidor_bairro       = document.frm_callcenter.consumidor_bairro;
			janela.consumidor_cidade       = document.frm_callcenter.consumidor_cidade;
			janela.consumidor_estado       = document.frm_callcenter.consumidor_estado;
			janela.abas = $('#container-Principal');
		<? } ?>
		janela.focus();
	}
}

function atualizaQuadroMapas(){

	/* Atualiza os dados do posto conforme cidade e estado do Consumidor */

	var estado_selecionado = $('#consumidor_estado').val();

	/* Centro Oeste */
	if (estado_selecionado == 'GO' || estado_selecionado == 'MT' || estado_selecionado == 'MS' || estado_selecionado == 'DF'){
		estado_selecionado = 'BR-CO';
	}

	/* Nordeste */
	if (estado_selecionado == 'AL' || estado_selecionado == 'BA' || estado_selecionado == 'CE' || estado_selecionado == 'MA' || estado_selecionado == 'PB' || estado_selecionado == 'PE' || estado_selecionado == 'PI' || estado_selecionado == 'RN' || estado_selecionado == 'SE'){
		estado_selecionado = 'BR-NE';
	}

	/* Note */
	if (estado_selecionado == 'AC' || estado_selecionado == 'AP' || estado_selecionado == 'AM' || estado_selecionado == 'PA' || estado_selecionado == 'RR' || estado_selecionado == 'RO' || estado_selecionado == 'TO'){
		estado_selecionado = 'BR-N';
	}

	$('#mapa_cidade').val( $('#consumidor_cidade').val() );
	$('#mapa_estado').val( estado_selecionado );
}


function txtBoxFormat(objForm, strField, sMask, evtKeyPress) {
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

	i = 0;
	nCount = 0;
	sCod = "";
	mskLen = fldLen;

	while (i <= mskLen) {
	bolMask = ((sMask.charAt(i) == "-") || (sMask.charAt(i) == ":") || (sMask.charAt(i) == ".") || (sMask.charAt(i) == "/"))
	bolMask = bolMask || ((sMask.charAt(i) == "(") || (sMask.charAt(i) == ")") || (sMask.charAt(i) == " ") || (sMask.charAt(i) == "."))


	if (bolMask) {
		sCod += sMask.charAt(i);
		mskLen++;

	} else {
		sCod += sValue.charAt(nCount);
		nCount++;
	}
	i++;
	}

	objForm[strField].value = sCod;
	if (nTecla != 8) { // backspace
		if (sMask.charAt(i-1) == "9") { // apenas números...
		return ((nTecla > 47) && (nTecla < 58)); } // números de 0 a 9
	else { // qualquer caracter...
		return true;
	}
	} else {
		return true;
	}
}


<?PHP if ($login_fabrica == 3) { ?>
	window.onload = function foco(){
		  var campo = document.getElementById("consumidor_nome");
		  campo.focus();
	}
<? } ?>


var http5 = new Array()
function listaFaq(produto){
	var campo = document.getElementById('div_faq_duvida_duvida');
	if(produto.length==0){
		alert('Por favor selecione o produto');
	}else{

		var curDateTime = new Date();
		http5[curDateTime] = createRequestObject();

		url = "callcenter_interativo_ajax.php?ajax=true&listar=sim&produto=" + produto;
		http5[curDateTime].open('get',url);

		http5[curDateTime].onreadystatechange = function(){
			if(http5[curDateTime].readyState == 1) {
				campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
			}
			if (http5[curDateTime].readyState == 4){
				if (http5[curDateTime].status == 200 || http5[curDateTime].status == 304){
					var results = http5[curDateTime].responseText;
					campo.innerHTML   = results;
				}else {
					campo.innerHTML = "Erro";

				}
			}
		}
		http5[curDateTime].send(null);
	}

}
</script>

<br><br>
<? if(strlen($msg_erro)>0){ ?>

<? //recarrega informacoes
	$callcenter                = trim($_POST['callcenter']);
	$data_abertura_callcenter  = trim($_POST['data_abertura_callcenter']);
	$categoria                 = trim($_POST['categoria']);
	$consumidor_nome           = trim($_POST['consumidor_nome']);
	$cliente                   = trim($_POST['cliente']);
	$consumidor_cpf            = trim($_POST['consumidor_cpf']);
	$consumidor_cpf            = str_replace("/","",$consumidor_cpf);
	$consumidor_cpf            = str_replace("-","",$consumidor_cpf);
	$consumidor_cpf            = str_replace(".","",$consumidor_cpf);
	$consumidor_cpf            = str_replace(",","",$consumidor_cpf);
	$consumidor_rg             = trim($_POST['consumidor_rg']);
	$consumidor_rg             = str_replace("/","",$consumidor_rg);
	$consumidor_rg             = str_replace("-","",$consumidor_rg);
	$consumidor_rg             = str_replace(".","",$consumidor_rg);
	$consumidor_rg             = str_replace(",","",$consumidor_rg);
	$consumidor_email          = trim($_POST['consumidor_email']);
	$consumidor_fone           = trim($_POST['consumidor_fone']);
	$consumidor_fone2          = trim($_POST['consumidor_fone2']);
	$consumidor_cep            = trim($_POST['consumidor_cep']);
	$consumidor_cep            = str_replace("-","",$consumidor_cep);
	$consumidor_cep            = str_replace("/","",$consumidor_cep);
	$consumidor_endereco       = trim($_POST['consumidor_endereco']);
	$consumidor_numero         = trim($_POST['consumidor_numero']);
	$consumidor_complemento    = trim($_POST['consumidor_complemento']);
	$consumidor_bairro         = trim($_POST['consumidor_bairro']);
	$consumidor_cidade         = trim(strtoupper($_POST['consumidor_cidade']));
	$consumidor_estado         = trim(strtoupper($_POST['consumidor_estado']));
	$assunto                   = trim($_POST['assunto']);
	$sua_os                    = trim($_POST['sua_os']);
	$data_abertura             = trim($_POST['data_abertura']);
	$produto                   = trim($_POST['produto']);
	$produto_referencia        = trim($_POST['produto_referencia']);
	$produto_nome              = trim($_POST['produto_nome']);
	$serie                     = trim($_POST['serie']);
	$data_nf                   = trim($_POST['data_nf']);
	$mapa_linha                = trim($_POST['mapa_linha']);
	$nota_fiscal               = trim($_POST['nota_fiscal']);
	$revenda                   = trim($_POST['revenda']);
	$revenda_nome              = trim($_POST['revenda_nome']);
	$revenda_endereco          = trim($_POST['revenda_endereco']);
	$revenda_nro               = trim($_POST['revenda_nro']);
	$revenda_cmpto             = trim($_POST['revenda_cmpto']);
	$revenda_bairro            = trim($_POST['revenda_bairro']);
	$revenda_city              = trim($_POST['revenda_city']);
	$revenda_uf                = trim($_POST['revenda_uf']);
	$revenda_fone              = trim($_POST['revenda_fone']);
	$posto                     = trim($_POST['posto']);
	$posto_nome                = trim($_POST['posto_nome']);
//	$reclamado                 = trim($_POST['reclamado']);
	$status                    = trim($_POST['status']);
	$hd_situacao               = trim($_POST['hd_situacao']);
	$hd_motivo_ligacao         = trim($_POST['hd_motivo_ligacao']);
	$transferir                = trim($_POST['transferir']);
	$chamado_interno           = trim($_POST['chamado_interno']);
	$status_interacao          = trim($_POST['status_interacao']);
	$resposta                  = trim($_POST['resposta']);
	$defeito_reclamado		   = trim($_POST['defeito_reclamado']);
?>
	<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F7503E;font-size:10px'>
		<tr>
			<td align='center'><? echo "<font color='#FFFFFF'>$msg_erro</font>"; ?></td>
		</tr>
	</table>
<?}
$sql = "SELECT nome from tbl_fabrica where fabrica = $login_fabrica";
$res = pg_exec($con,$sql);
$nome_da_fabrica = pg_result($res,0,0);
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
			<?
			$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='1' AND fabrica = $login_fabrica";
			$pe = pg_exec($con,$sql);
			if(pg_numrows($pe)>0){
				echo pg_result($pe,0,0);
			}else{
				if ($login_fabrica==25) echo "Hbflex"; else echo "$nome_da_fabrica";?>, <?echo ucfirst($login_login);?>, <?echo saudacao();?>.<BR> O Sr.(a) já fez algum contato com a <? if ($login_fabrica==25) echo "Hbflex"; else echo "$nome_da_fabrica ";?> <?if($login_fabrica==25){ ?> por telefone ou pelo Site<?}?> ?
			<?}?>
		</td>
		<td align='right' width='150'></td>
	</tr>
</table>

<BR />
<form name="frm_callcenter" method="post" action="<?$PHP_SELF?>">
<input name="callcenter" class="input" type="hidden" value='<?echo $callcenter;?>'>
<table width="98%" border="0" align="center" cellpadding="2" cellspacing="2" style='font-size:12px'>
<tr>
	<td align='left'>

		<table width="100%" border='0'>
			<tr>
				<td align='left'><strong>Cadastro de Atendimento</strong></td>
				<? if(strlen($callcenter)>0 AND $login_fabrica == 3) { ?>
				<td  nowrap>Tipo de registro: <strong><? echo $tipo_registro; ?></strong></td>
				<td  nowrap>Situação Atual: <strong><? echo $hd_situacao_descricao; ?></strong></td>
				<td  nowrap>Data de Abertura: <strong><? echo $data_abertura_callcenter; ?></strong></td>
				<? } ?>
				<td align='right'><strong><? if(strlen($callcenter)>0){echo "nº <font color='#CC0033'>$callcenter</font>";}?></strong></td>
			</tr>
		</table>

		<?  if(strlen($callcenter)==0){ ?>

		<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left' width='68'><strong>Localizar:</strong></td>
				<? if($login_fabrica==3){ ?>
					<td align='left'>
						<input name="localizar" id='localizar' value='<?echo $localizar ;?>' class="input" type="text" size="30" maxlength="500">  <a href='#' onclick="javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.localizar, 'os')">Por OS</a> | <a href='#' onclick='javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.localizar, "nome")'>Por Nome</a> | <a href='#' onclick="javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.localizar, 'atendimento')">Por atendimento</a> | <a href='#' onclick="javascript:localizarConsumidor('localizar','novo')">Novo consumidor</a>
					</td>
				<? } ?>
			</tr>
		</table>
	<?  } ?>

	</td>
</tr>

<tr>
	<td>

	<div id='div_consumidor' style='display:inline; Position:relative;background-color: #e6eef7;width:100%'>
		<table width='100%' border='0' align='center' cellpadding='2' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px' id='tabela_consumidor'>
		<tr>
			<td align='left'><strong>Nome:</strong></td>
			<td align='left'>
				<input name="consumidor_nome" id="consumidor_nome"  value='<?echo $consumidor_nome ;?>' class="input" type="text" size="35" maxlength="500"
				 >
			</td>
		</table>
	</div>
	<br>
	<table width="100%" border='0'>
		<tr>
			<td align='left'><strong>Informações do Atendimento</strong></td>
		</tr>
	</table>

	<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
		<tr>
			<td align='left' valign='top'><strong>Defeitos:</strong></td>
			<td align='left' colspan='5'>
			<?php //echo $defeito_reclamado;?>
				<select name="defeito_reclamado" id="defeito_reclamado"  class="input">
					<option value="">Selecione um Defeito</option>
					<?php
						$sql ="SELECT DISTINCT(tbl_defeito_reclamado.defeito_reclamado) AS cod_reclamado,
								tbl_defeito_reclamado.descricao AS desc_reclamado
								FROM tbl_diagnostico
								JOIN tbl_familia ON tbl_diagnostico.familia = tbl_familia.familia
								AND tbl_diagnostico.fabrica = tbl_familia.fabrica
								JOIN tbl_defeito_reclamado
								ON tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
								AND tbl_diagnostico.fabrica = tbl_defeito_reclamado.fabrica
								JOIN tbl_linha
								ON tbl_diagnostico.linha = tbl_linha.linha
								WHERE tbl_diagnostico.linha = 528
								AND tbl_diagnostico.fabrica = 3
								AND tbl_diagnostico.ativo = 't'
								ORDER BY tbl_defeito_reclamado.descricao";
						$res = pg_exec($con,$sql);
						if(pg_numrows($res)>0){
							for($x=0;pg_numrows($res)>$x;$x++){
								$cod_reclamado = pg_result($res,$x,cod_reclamado);
								$cdesc_reclamado = pg_result($res,$x,desc_reclamado);

								$selectd_reclamado = '';
								if($defeito_reclamado == $cod_reclamado){
									$selectd_reclamado = " SELECTED ";
								}
							?>
								<option value="<?php echo $cod_reclamado;?>" <?php echo $selectd_reclamado;?> title="<?php echo $cdesc_reclamado;?>"><?php echo $cdesc_reclamado;?></option>
							<?php
							}
						}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td align='left' valign='top'><strong>Protocolo:</strong></td>
			<td align='left' colspan='5'>
				<input name="protocolo_atendimento" id="protocolo_atendimento" class="input" value='<?echo $ordem_montagem ;?>'  type="text" size="40" maxlength="8">
			</td>
		</tr>
		<tr>
			<td align='left' valign='top'><strong>Descrição:</strong></td>
			<td align='left' colspan='5'>
				<textarea name="reclamado_produto" rows="6" cols="110" class="input" style="font-size:10px"><?php echo $reclamado;?></textarea>
			</td>
		</tr>
		</table>


	<BR>

		<table width="100%" border='0'>
			<tr>
				<td align='left'><strong>Posto Autorizado</strong></td>
			</tr>
		</table>

		<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left'>
					<strong>Codigo do Posto:&nbsp;</strong>
					<input name="codigo_posto" class="input" value='<?echo $codigo_posto ;?>'
						onblur="javascript: fnc_pesquisa_posto_call (document.frm_callcenter.codigo_posto,document.frm_callcenter.posto_nome,document.frm_callcenter.posto_fone,'codigo');" type="text" size="15" maxlength="15">
						<img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer' onclick="javascript: fnc_pesquisa_posto_call (document.frm_callcenter.codigo_posto,document.frm_callcenter.produto_nome,
						document.frm_callcenter.posto_fone,'codigo');">
				</td>
				<td align='left'>
					<strong>Nome do Posto:&nbsp;</strong>
					<input name="posto_nome" class="input" value='<?echo $posto_nome ;?>'
						onblur="javascript: fnc_pesquisa_posto_call (document.frm_callcenter.codigo_posto,document.frm_callcenter.posto_nome,document.frm_callcenter.posto_fone,'nome');" type="text" size="35" maxlength="500">
						<img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer' onclick="javascript: fnc_pesquisa_posto_call (document.frm_callcenter.codigo_posto,document.frm_callcenter.posto_nome,
						document.frm_callcenter.posto_fone,'nome');">
				</td>
			</tr>
		</table>
		<BR>
	<div rel='div_ajuda' style='display:inline; Position:relative;'>
		<table width='98%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
		<tr>
			<td align='right' width='150'></td>
			<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
			<td align='center'><STRONG><?echo$consumidor_nome;?></STRONG><BR>
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

		<? if(strlen($callcenter)>0){
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
								order by tbl_hd_chamado_item.data ";
					//	echo $sql;
				$res = pg_exec($con,$sql);
				if(pg_numrows($res)>0){
					for($x=0;pg_numrows($res)>$x;$x++){
						$data               = pg_result($res,$x,data);
						$comentario         = pg_result($res,$x,comentario);
						$atendente_resposta = pg_result($res,$x,login);
						$status_item        = pg_result($res,$x,status_item);
						$interno            = pg_result($res,$x,interno);
						$enviar_email       = pg_result($res,$x,enviar_email);
						$xx = $xx + 1;
						?>
						<table width='100%' border='0' align='center' cellpadding="2" cellspacing="1" style=' border:#485989 1px solid; background-color: #A0BFE0;font-size:10px'>
						<tr>
						<td align='left' valign='top'>
							<table style='font-size: 10px' border='0' width='100%'>
							<tr>
							<td align='left' width='70%'>Resposta: <strong><?echo $xx;?></strong> Por: <strong><?echo nl2br($atendente_resposta);?></strong> </td>
							<td align='right' nowrap><?echo "$data";?></td>
							</tr>
							</table>
						</td>
						</tr>
						<? if($interno == "t"){?>
							<tr>
							<td align='center' valign='top' bgcolor='#EFEBCF'>
							<?echo "<font size='2'>Chamado Interno</font>";?>
							</td>
							</tr>
						<?}?>
						<? if($status_item == "Cancelado" or $status_item == "Resolvido"){?>
							<tr>
							<td align='center' valign='top' bgcolor='#EFEBCF'><?echo "<font size='2'>$status_item</font>";?>
							</td>
							</tr>
						<?}?>
						<? if($enviar_email == "t"){?>
							<tr>
							<td align='center' valign='top' bgcolor='#EFEBCF'><?echo "<font size='2'>Conteúdo enviado por e-mail para o consumidor</font>";?>
							</td>
							</tr>
						<?}?>
						<tr>
						<td align='left' valign='top' bgcolor='#FFFFFF'><?echo nl2br($comentario);?></td>
						</tr>
						</table><br>
	<?				}
				}
		}

	?>
	</td>
</tr>

<tr>
     <td align='center' colspan='5'>

     <table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#5AA962 1px solid; background-color:#D1E7D3;font-size:10px'>
		<? if(strlen($callcenter)>0){ ?>
			 <tr>
			 <td align='left' valign='top'> <strong>Resposta:</strong></td>
			 <td colspan='6' align='left'><TEXTAREA NAME="resposta" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $resposta ;?></TEXTAREA></td>
			 </tr>
		<?}?>
	 <tr>
		<td align='left' width='80'><strong>Transferir p/:</strong></td>
		<td align='left' width='90'>
			<select name="transferir" style='width:80px; font-size:9px' class="input" >
			 <option value=''></option>
			<?	$sql = "SELECT admin, login
						from tbl_admin
						where fabrica = $login_fabrica
						and ativo is true
						and (privilegios like '%call_center%' or privilegios like '*') order by login";
				$res = pg_exec($con,$sql);
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
			<?
				#HD 52767 - Só mostra se estiver com tipo de registro. Este combo é carregado por AJAX, de acordo com o tipo de resgistro.
					//echo "<option value=''></option>";
					$sql = "SELECT
								hd_situacao,
								descricao
							FROM tbl_hd_situacao
							WHERE fabrica = $login_fabrica
							AND tipo_registro ='Contato'
							ORDER BY descricao";
					$res = pg_exec($con,$sql);
					if(pg_numrows($res)>0){
						for($i=0;pg_numrows($res)>$i;$i++){
							$xhd_situacao = pg_result($res,$i,hd_situacao);
							$descricao    = pg_result($res,$i,descricao);
							$selected     = " ";
							if($xhd_situacao == $hd_situacao){
								$selected = " selected ";
							}
							echo "<option value='$xhd_situacao' $selected>$descricao</option>";
						}
					}else{
						echo "<option value='' selected>Selecione o tipo de Registro</option>";
					}
			?>
			</select>
		</td>
		<td align='left' nowrap>
			<INPUT TYPE="checkbox" NAME="chamado_interno" class="input" ><strong>Chamado Interno</strong>
		</td>

		<? if($login_fabrica==25){?>

			<td align='center' nowrap><a href='sedex_cadastro.php' target='blank'><strong>Abrir OS Sedex</strong></a></td>

		<? } ?>

		<? if($login_fabrica==35 and strlen($callcenter)>0){?>

			<td align='center' nowrap><INPUT TYPE="checkbox" NAME="envia_email" class="input" > <strong>Envia e-mail</strong></td>

		<? } ?>

		<td align='center'>
			<input class="botao" type="hidden" name="btn_acao"  value=''>
			<input  class="input"  type="button" name="bt" value='<? if(strlen($callcenter)==0) echo "Gravar Atendimento"; else echo "Gravar Alterações"; ?>' style='width:150px;font-size:12px;font-weight:bold;' onclick="javascript:if (document.frm_callcenter.btn_acao.value!='') alert('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.'); else{
			<?if($login_fabrica ==3) { // HD 48680
			  echo "if(confirm('Deseja confirmar o atendimento?') == true){ document.frm_callcenter.btn_acao.value='final';document.frm_callcenter.submit();}else{ return; }";
			} else {
				echo "document.frm_callcenter.btn_acao.value='final';document.frm_callcenter.submit();";
			 } ?>
			}
			">
		</td>
	</tr>
	</table>
</td>
</tr>

<? if(strlen($callcenter)>0){ ?>
<tr>
	<td align='center' colspan='5'>
		<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
		<tr>
			<td align='right' width='150'></td>
			<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
			<td align='center'><STRONG>Por favor, queira anotar o n° do protocolo de atendimento</STRONG><BR>
			Número <font color='#D1130E'><?echo $callcenter;?></font>
			</td>
			<td align='right' width='150'></td>
		</tr>
		</table><BR>
	</td>
</tr>
<tr>
	<td><a href='callcenter_interativo_print.php?callcenter=<?echo $callcenter;?>' target='_blank' style='font-size:10px;font-family:Verdana;'><img src='imagens/img_impressora.gif'>Imprimir</a></td>
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
				<input  class="input"  type="button" name="bt" value='Sim' onclick="javascript:window.location='pre_os_britania_simplificada.php?Id=<?echo $callcenter;?>';">
				<input  class="input"  type="button" name="bt" value='Não' onclick="javascript:window.location='pre_os_britania_simplificada.php';">
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
				A <?echo "$nome_da_fabrica";?> agradece a sua ligação, tenha um(a) <?echo saudacao();?>.
				</td>
				<td align='right' width='150'></td>
			</tr>
			</table>
	  </td>
     </tr>
	 <? } ?>
</table>
</form>
<? include "rodape.php";?>
