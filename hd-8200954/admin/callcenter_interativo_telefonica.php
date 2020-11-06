<?
//as tabs definem a categoria do chamado
/* OBSERVACAO HBTECH
	* O produto Hibeats possui uma garantia estendida, ou seja, 1 ano de garantia normal e se ele entrar no site do hibeats ou solicitar via SAC a extensão o cliente ganha mais 6 meses de garantia ficando com 18 meses.
	* Para verificar os produtos que tem garantia estendida acessamos o bd do hibeats (conexao_hbflex.php) e verificamos o número de série. 
		* Todos numeros de series vendidos estao no bd do hibeats, caso nao esteja lá não foi vendido ou a AKabuki não deu carga no bd.
		* AKabuki é a agencia que toma conta do site da hbflex, responsavel pelo bd e atualizacao do bd. Contato:
			Allan Rodrigues
			Programador
			AGÊNCIA KABUKI
			* allan@akabuki.com.br
			* www.akabuki.com.br
			( 55 11 3871-9976 
	** Acompanhar os lancamentos destas garantias, liberado no ultimo dia do ano e ainda estamos acompanhando

*/
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
				$sql .= " AND UPPER(tbl_revenda.nome) like UPPER('%$q%') ";
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
			$sql = "SELECT tbl_posto.posto, tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
			
			if ($tipo_busca == "codigo"){
				$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
			}else{
				$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
			}
			
			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$posto        = trim(pg_result($res,$i,posto));
					$cnpj         = trim(pg_result($res,$i,cnpj));
					$nome         = trim(pg_result($res,$i,nome));
					$codigo_posto = trim(pg_result($res,$i,codigo_posto));
					echo "$posto|$cnpj|$codigo_posto|$nome";
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
	$date = explode("-", str_replace('/', '-', $date));
	$date2 = ''.$date[2].'-'.$date[1].'-'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}


$btn_acao = $_POST['btn_acao'];

if(strlen($btn_acao)>0){
	
		$callcenter         = $_POST['callcenter'];
		$hd_chamado         = $callcenter;
		$tab_atual          = $_POST['tab_atual'];
		$status_interacao   = $_POST['status_interacao'];
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
		if(($login_fabrica ==11 and strlen($callcenter) ==0) or $login_fabrica <>11){
		$consumidor_nome           = trim($_POST['consumidor_nome']);
		$cliente                   = trim($_POST['cliente']);
		$consumidor_cpf            = trim($_POST['consumidor_cpf']);
		$consumidor_cpf            = str_replace("/","",$consumidor_cpf);
		$consumidor_cpf            = str_replace("-","",$consumidor_cpf);
		$consumidor_cpf            = str_replace(".","",$consumidor_cpf);
		$consumidor_cpf            = str_replace(",","",$consumidor_cpf);
		if (strlen($consumidor_cpf) == 11) {
		$consumidor_cpf = substr($consumidor_cpf,0,3) .".". substr($consumidor_cpf,3,3) .".". substr($consumidor_cpf,6,3) ."-". substr($consumidor_cpf,9,2);
		} 
		$consumidor_rg             = trim($_POST['consumidor_rg']);
		$consumidor_rg             = str_replace("/","",$consumidor_rg);
		$consumidor_rg             = str_replace("-","",$consumidor_rg);
		$consumidor_rg             = str_replace(".","",$consumidor_rg);
		$consumidor_rg             = str_replace(",","",$consumidor_rg);
		$consumidor_email          = trim($_POST['consumidor_email']);
		$consumidor_fone           = trim($_POST['consumidor_fone']);
		$consumidor_fone           = str_replace("'","",$consumidor_fone);
		$consumidor_fone2          = trim($_POST['consumidor_fone2']);
		$consumidor_fone2          = str_replace("'","",$consumidor_fone2);
		$consumidor_cep            = trim($_POST['consumidor_cep']);
		$consumidor_cep            = str_replace("-","",$consumidor_cep);
		$consumidor_cep            = str_replace("/","",$consumidor_cep);
		$consumidor_endereco       = trim($_POST['consumidor_endereco']);
		$consumidor_numero         = trim($_POST['consumidor_numero']);
		$consumidor_numero         = str_replace("'","",$consumidor_numero);
		$consumidor_complemento    = trim($_POST['consumidor_complemento']);
		$consumidor_bairro         = trim($_POST['consumidor_bairro']);
		$consumidor_cidade         = trim(strtoupper($_POST['consumidor_cidade']));
		$consumidor_estado         = trim(strtoupper($_POST['consumidor_estado']));


		if(strlen($consumidor_nome)==0)   { $xconsumidor_nome  = "null";  }else{ $xconsumidor_nome = "'".$consumidor_nome."'";}
		if(strlen($consumidor_cpf)==0)    { $xconsumidor_cpf   = "null";  }else{ $xconsumidor_cpf   = "'".$consumidor_cpf."'";}
		if(strlen($consumidor_rg)==0)     {	$xconsumidor_rg    = "null";  }else{ $xconsumidor_rg    = "'".$consumidor_rg."'";}
		if(strlen($consumidor_email)==0)  { $xconsumidor_email = "null";  }else{ $xconsumidor_email = "'".$consumidor_email."'";}
		if(strlen($consumidor_fone)==0)   { $xconsumidor_fone  = "null";  }else{ $xconsumidor_fone  = "'".$consumidor_fone."'";}
		if(strlen($consumidor_fone2)==0)   { $xconsumidor_fone2  = "null";  }else{ $xconsumidor_fone2  = "'".$consumidor_fone2."'";}
		if(strlen($consumidor_cep)==0)        { $xconsumidor_cep        ="null"; }else{ $xconsumidor_cep      = "'".$consumidor_cep."'";}
		if(strlen($consumidor_endereco)==0)   { $xconsumidor_endereco   ="null"; }else{ $xconsumidor_endereco = "'".$consumidor_endereco."'";}
		if(strlen($consumidor_numero)==0)     { $xconsumidor_numero     ="null"; }else{ $xconsumidor_numero   = "'".$consumidor_numero."'";}
		if(strlen($consumidor_complemento)==0){$xconsumidor_complemento ="null"; }else{ $xconsumidor_complemento      = "'".$consumidor_complemento."'";}
		if(strlen($consumidor_bairro)==0) { $xconsumidor_bairro  = "null"; }else{ $xconsumidor_bairro = "'".$consumidor_bairro."'";}
		if(strlen($consumidor_cidade)==0) { $xconsumidor_cidade  = "null"; }else{ $xconsumidor_cidade = "'".$consumidor_cidade."'";}
		if(strlen($consumidor_estado)==0) { $xconsumidor_estado  = "null"; }else{ $xconsumidor_estado = "'".$consumidor_estado."'";}

		if(strlen($consumidor_nome)>0 and strlen($consumidor_estado)==0){
			$msg_erro .= "Por favor inserir o estado";
		}
		if(strlen($consumidor_nome)>0 and strlen($consumidor_cidade)==0){
			$msg_erro .= "Por favor inserir a cidade";
		}
		}
		
		$abre_os                   = trim($_POST['abre_os']);
		$imprimir_os               = trim($_POST['imprimir_os']);
		$resposta                  = trim($_POST['resposta']);
		if(strlen($defeito_reclamado)==0) { $xdefeito_reclamado  = "null"; }else{ $xdefeito_reclamado = $defeito_reclamado;}
		if(strlen($reclamado)==0)         { $xreclamado          = "null"; }else{ $xreclamado = "'".$reclamado."'";}
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

			$xserie              = $_POST['serie_es'];
			if(strlen($_POST["serie"])>0) $xserie = $_POST['serie'];
			//HD 12749
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
					$xes_celular = "NULL";
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
		//insert into numero_serie(numero, revenda, cnpj)values('XXXX0039AAA84B1F','Revenda Teste','11111111OOO111');
		}

		if($tab_atual == "reclamacao_produto"){
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

		if(strlen($codigo_posto_tab)>0) $codigo_posto = $codigo_posto_tab;
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

		if (strlen($cliente)==0){
			$cliente = "null";
		}

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


/*INSERINDO*/
if(strlen($callcenter)==0){
		if(strlen($msg_erro)==0){
			$res = pg_exec ($con,"BEGIN TRANSACTION");
			if(strlen($consumidor_nome)>0 and strlen($consumidor_estado)>0 and strlen($consumidor_cidade)>0){
				$sql = "SELECT tbl_cidade.cidade
							FROM tbl_cidade
							where tbl_cidade.nome = $xconsumidor_cidade
							AND tbl_cidade.estado = $xconsumidor_estado
							limit 1";
					$res = pg_exec($con,$sql);
				//	echo nl2br($sql)."<BR>";
					if(pg_numrows($res)>0){
						$cidade = pg_result($res,0,0);
					}else{
						$sql = "INSERT INTO tbl_cidade(nome, estado)values(upper($xconsumidor_cidade),upper($xconsumidor_estado))";		
					//	echo nl2br($sql)."<BR>";
						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);
						$res    = pg_exec ($con,"SELECT CURRVAL ('seq_cidade')");
						$cidade = pg_result ($res,0,0);
					}
			}
		}

		if(strlen($msg_erro)==0 and strlen($callcenter)==0) {
			$sql = "INSERT INTO tbl_hd_chamado (
						admin                 ,
						data                  ,
						status                ,
						atendente             ,
						fabrica_responsavel   ,
						titulo                ,
						categoria             ,
						fabrica
					)values(
						$login_admin            ,
						current_timestamp       ,
						$xstatus_interacao      ,
						$login_admin            ,
						$login_fabrica          ,
						'Atendimento interativo',
						'$tab_atual'            ,
						$login_fabrica
				)";
		//echo nl2br($sql)."<BR>";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			$res    = pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado')");
			$hd_chamado = pg_result ($res,0,0);
		}

		if(strlen($msg_erro)==0 and strlen($callcenter)==0 and $abre_os=='t'){
			if(strlen($xcodigo_posto)==0 OR $xcodigo_posto=='null') $msg_erro = "Para que a OS seja aberta é necessário escolher um posto";
			$xserie = "'".$_POST["serie"]."'";
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
			if(strlen($msg_erro)==0 and strlen($callcenter)==0) {

			$xserie = "'".$_POST["serie"]."'";
			$xnota_fiscal = "'".$_POST["nota_fiscal"]."'";

			if(strlen($abre_os)==0){ $abre_os = 'f';}
			$xabre_os = "'".$abre_os."'";
			
			$data_nf = $_POST["data_nf"] ;
			if(strlen($data_nf)==0) $xdata_nf = "NULL";
			else                    $xdata_nf = "'".converte_data($data_nf)."'";

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
								abre_os
						)values(
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
							$xabre_os
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

			if(strlen($xrevenda)>0){
				$sql = "SELECT 
								endereco,
								numero  ,
								bairro  ,
								tbl_cidade.nome AS cidade,
								tbl_cidade.estado  
						FROM tbl_revenda
						JOIN tbl_revenda_compra USING(revenda)
						JOIn tbl_cidade USING(cidade)
						WHERE tbl_revenda.revenda        = $xrevenda
						AND   tbl_revenda_compra.fabrica = $login_fabrica";
					$resx = @pg_exec($con,$sql);
					if(@pg_numrows($resx)>0){
						$r_endereco = pg_result($resx,0,endereco);
						$r_numero   = pg_result($resx,0,numero);
						$r_bairro   = pg_result($resx,0,bairro);
						$r_cidade   = pg_result($resx,0,cidade);
						$r_estado   = pg_result($resx,0,estado);
						$r_endereco = pg_result($resx,0,endereco);

						$comentario = "Indicação de onde comprar o produto: <br>
									CNPJ: $revenda_cnpj <br>
									Nome: $revenda_nome<br>
									Endereço: $r_endereco, $r_numero - $r_bairro <br>
									Cidade: $r_cidade <br>
									Estado: $r_estado";

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
				}
				 $xxes_data_compra = converte_data($es_data_compra);
/*voltar aqui*/
				 $sql = "SELECT garantia from tbl_produto where produto = $xproduto";
				 $res = pg_exec($con,$sql);
				 $garantia = pg_result($res,0,0);
					
				 $sql = "SELECT to_char(('$xxes_data_compra'::date + interval '$garantia month') + interval '6 month','YYYY-MM-DD') ";
  			     $res = pg_exec($con,$sql);
			//	 echo $sql;
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
									$es_garantia
								);";
							//		echo "$sql;<BR>";
						$res = mysql_query($sql) or die("Erro no Sql1:".mysql_error());
					
						$sql = "SELECT idGarantia from garantia where numeroSerie = $xserie and cpf = $xconsumidor_cpf";
//echo "$sql;<BR>";
						$res = mysql_query($sql) or die("Erro no Sql2:".mysql_error());
						if(mysql_num_rows($res)>0){
							$idGarantia = mysql_result($res,0,0);
							if(strlen($es_id_numeroserie)>0){
								$sql = "UPDATE numero_serie set idGarantia = $idGarantia where idNumeroSerie = $es_id_numeroserie";
//echo $sql;
								$res = mysql_query($sql) or die("Erro no Sql:".mysql_error());
							}
						
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
			
			if($login_fabrica == 59) { // HD 26968
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
			}

			if (strlen($msg_erro) == 0) {
				$res = pg_exec($con,"COMMIT TRANSACTION");
				header ("Location: callcenter_interativo_new.php?callcenter=$hd_chamado&$imprimir_os#$tab_atual");
				exit;

			}else{
				$res = pg_exec($con,"ROLLBACK TRANSACTION");
			}
}/*INSERINDO*/

/*atualizando*/
if(strlen($callcenter)>0){
	if($xresposta=="null"){ $msg_erro = "Por favor insira a resposta";}
	$sql = "SELECT atendente 
			from tbl_hd_chamado 
			where fabrica_responsavel= $login_fabrica 
			and hd_chamado = $callcenter";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		$ultimo_atendente = pg_result($res,0,atendente);
	}
	/*echo $xresposta."<BR>";
	echo $xstatus_interacao."<BR>";
	echo $xtransferir."<BR>";
	echo $xchamado_interno;*/
	if(strlen($msg_erro)==0){
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
						$xenvia_email
						)";
				//		echo $sql;

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
		$sql = "UPDATE tbl_hd_chamado set status = $xstatus_interacao
				WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
				and tbl_hd_chamado.hd_chamado = $callcenter	";
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);

		if($ultimo_atendente <> $xtransferir){
			$sql = "UPDATE tbl_hd_chamado set atendente = $xtransferir
					WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
					and tbl_hd_chamado.hd_chamado = $callcenter	";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			
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
							nome        = upper($xconsumidor_nome)       ,
							endereco    = upper($xconsumidor_endereco)   ,
							numero      = upper($xconsumidor_numero)     ,
							complemento = upper($xconsumidor_complemento),
							bairro      = upper($xconsumidor_bairro)     ,
							cep         = upper($xconsumidor_cep)        ,
							fone        = upper($xconsumidor_fone)       ,
							fone2        = upper($xconsumidor_fone2)     ,
							email       = upper($xconsumidor_email)      ,
							cpf         = upper($xconsumidor_cpf)        ,
							rg          = upper($xconsumidor_rg)         ,
							cidade      = $cidade
						WHERE tbl_hd_chamado_extra.hd_chamado = $xhd_chamado";
				//echo nl2br($sql)."<BR>";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
				
			}
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
		header ("Location: callcenter_interativo_new.php?callcenter=$hd_chamado");
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
					tbl_cidade.nome as cidade_nome,
					tbl_cidade.estado,
					tbl_hd_chamado_extra.origem,
					tbl_admin.login as atendente,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY') as data,
					tbl_hd_chamado.status,
					tbl_hd_chamado.categoria as natureza_operacao,
					tbl_posto.posto,
					tbl_hd_chamado.titulo as assunto,
					tbl_produto.produto,
					tbl_produto.linha,
					tbl_produto.referencia as produto_referencia,
					tbl_produto.descricao as produto_nome,
					tbl_defeito_reclamado.defeito_reclamado,
					tbl_defeito_reclamado.descricao as defeito_reclamado_descricao,
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
					tbl_hd_chamado_extra.abre_os
		FROM tbl_hd_chamado
		JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
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
			$natureza_chamado          = pg_result($res,0,natureza_operacao);
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
			$atendente                = pg_result($res,0,atendente);
			$receber_informacoes	  = pg_result($res,0,receber_info_fabrica);	
			$codigo_posto	          = pg_result($res,0,codigo_posto);	
			$linha         	          = pg_result($res,0,linha);	
			$abre_os                  = pg_result($res,0,abre_os);	

			$tipo_atendimento = array(1 => 'extensao', 2 => 'reclamacao_produto', 3 => 'reclamacao_empresa',
				4 => 'reclamacao_at',5 => 'duvida_produto', 6 => 'sugestao', 7 => 'assistencia', 8 => 'garantia', 9 => 'troca_produto', 10 => 'procon' ,11 => 'onde_comprar');

			$posicao = array_search($natureza_chamado, $tipo_atendimento); // $key = 2;
			//echo "$natureza_chamado ==>$posicao";

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
					tbl_hd_chamado_extra.revenda ,
					tbl_cidade.nome as cidade_nome,
					tbl_cidade.estado,
					tbl_produto.produto,
					tbl_produto.linha,
					tbl_produto.referencia as produto_referencia,
					tbl_produto.descricao as produto_nome,
					tbl_hd_chamado_extra.serie,
					to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') as data_nf,
					tbl_hd_chamado_extra.nota_fiscal,
					tbl_hd_chamado_extra.abre_os
		FROM tbl_hd_chamado
		JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
		LEFT JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
		LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto
		WHERE tbl_hd_chamado.hd_chamado = $Id";
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){
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
			$produto                  = pg_result($res,0,produto);
			$produto_referencia       = pg_result($res,0,produto_referencia);
			$produto_nome             = pg_result($res,0,produto_nome);
			$serie                    = pg_result($res,0,serie);
			$data_nf                  = pg_result($res,0,data_nf);
			$nota_fiscal              = pg_result($res,0,nota_fiscal);
			$revenda                  = pg_result($res,0,revenda);
			$abre_os                  = pg_result($res,0,abre_os);
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
if($login_fabrica==25){
	$w=1;
}else if($login_fabrica == 45){
	$w=1;
	$posicao = $posicao-1;
}else if($login_fabrica == 46 OR $login_fabrica == 11){
	$w=1;
	$posicao = $posicao-1;
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
		
	});
$().ready(function() {

	function formatItem(row) {
		return row[1] + " - " + row[2];
	}

	function formatItemPosto(row) {
		return row[2] + " - " + row[3];
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
		url = "pesquisa_consumidor_callcenter_new.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor_callcenter_new.php?cpf=" + campo.value + "&tipo=cpf";
	}
	if (campo.value != "") {
		janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
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

		janela.focus();
	}
}


function fnc_pesquisa_revenda (campo, tipo) {
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

var http1 = new Array();
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


function mapa_rede(linha,estado,cidade){
	url = "mapa_rede.php?callcenter=true&pais=BR&estado="+estado.value+"&linha="+linha.value+"&cidade="+cidade.value;
	janela = window.open(url,"janela","width=700,height=300,scrollbars=yes,resizable=yes");
	janela.posto_tab        = document.frm_callcenter.posto_tab;
	janela.codigo_posto_tab = document.frm_callcenter.codigo_posto_tab;
	janela.posto_nome_tab   = document.frm_callcenter.posto_nome_tab;

}

function fnc_pesquisa_os (campo, tipo) {
	var url = "";
	if (tipo == "os") {
		url = "pesquisa_os_callcenter_teste.php?consumidor_cpf=" + campo.value + "&tipo=os";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor_callcenter.php?consumidor_cpf=" + campo.value + "&tipo=cpf";
	}
	if (campo.value != "") {
		janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");

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

//teste

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

<? if($login_fabrica ==11) { // HD 14549 ?>
function char(sem_acento){
		try{var element = sem_acento.which	}catch(er){};
		try{var element = event.keyCode	}catch(er){};
		if (String.fromCharCode(element).search(/[A-Z]|[ ]|[0-9]/g) == -1)
		return false
}
window.onload = function(){
	document.getElementById('consumidor_nome').onkeypress = char;
	document.getElementById('consumidor_endereco').onkeypress = char;
	document.getElementById('consumidor_numero').onkeypress = char;
	document.getElementById('consumidor_complemento').onkeypress = char;
	document.getElementById('consumidor_cidade').onkeypress = char;
	document.getElementById('consumidor_bairro').onkeypress = char;
}


<? } ?>
</script>

<br><br>
<? if(strlen($msg_erro)>0){ ?>

<? //recarrega informacoes
	$callcenter                = trim($_POST['callcenter']);
	$data_abertura_callcenter  = trim($_POST['data_abertura_callcenter']);
	$natureza_chamado          = trim($_POST['natureza_chamado']);
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
	$defeito_reclamado         = trim($_POST['defeito_reclamado']);
//	$reclamado                 = trim($_POST['reclamado']);
	$status                    = trim($_POST['status']);

	$transferir                = trim($_POST['transferir']);
	$chamado_interno           = trim($_POST['chamado_interno']);
	$status_interacao          = trim($_POST['status_interacao']);
	$resposta                  = trim($_POST['resposta']);
	$abre_os                   = trim($_POST['abre_os']);
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
				<td align='right'><strong><? if(strlen($callcenter)>0){echo "nº <font color='#CC0033'>$callcenter</font>";}?></strong></td>
			</tr>
		</table>

		<?  if(strlen($callcenter)==0){ ?>

		<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr> 
				<td align='left' width='68'><strong>Localizar:</strong></td>
				<td align='left'>
					<input name="localizar" id='localizar' value='<?echo $localizar ;?>' class="input" type="text" size="30" maxlength="500">  <a href='#' onclick="javascript:localizarConsumidor('localizar','cpf')">Por CPF</a> | <a href='#' onclick='javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.localizar, "nome")'>Por Nome</a> | <a href='#' onclick="javascript:localizarConsumidor('localizar','atendimento')">Por atendimento</a> | <a href='#' onclick="javascript:localizarConsumidor('localizar','cep')">Por CEP</a> | <a href='#' onclick="javascript:localizarConsumidor('localizar','telefone')">Por Telefone</a> 
					| <a href='#' onclick="javascript:localizarConsumidor('localizar','novo')">Novo consumidor</a>
					| <a href='#' onclick="javascript:localizarConsumidor('localizar','novo')">Nº Série</a>
					| <a href='#' onclick="javascript:localizarConsumidor('localizar','novo')">Ordem Serviço</a>
					| <a href='#' onclick="javascript:localizarConsumidor('localizar','novo')">Nº do CAT</a>
					</td>
			</tr>
		</table>
	<?  } ?>

	</td>
</tr>

<tr>
	<td>

	<div id='div_consumidor' style='display:inline; Position:relative;background-color: #e6eef7;width:100%'>
		<table width='100%' border='0' align='center' cellpadding='2' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
		<tr>
			<td align='left'><strong>Nome:</strong></td>
			<td align='left'>
				<input name="consumidor_nome" id='consumidor_nome' value='<?echo $consumidor_nome ;?>' <? if($login_fabrica==11 and (strlen($callcenter) >0 or strlen($Id) >0) ){?>  readonly <?}?> class="input" type="text" size="35" maxlength="500"
				 <? if($login_fabrica==11){?> onChange="javascript: this.value=this.value.toUpperCase();"<?}?>> <img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, "nome")' style='cursor: pointer' >
			</td>
			<td align='left'><strong>Cpf:</strong></td>
			<td align='left'>
				<input name="consumidor_cpf" id="cpf" value='<?echo $consumidor_cpf ;?>' class="input" type="text" size="18" maxlength="14" onkeypress="return txtBoxFormat(this.form, this.name, '999.999.999-99', event);" <? if($login_fabrica==11 and (strlen($callcenter) >0 or strlen($Id) >0) ){?>  readonly <?}?> >
				<img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer' onclick='javascript: fnc_pesquisa_consumidor_callcenter 	(document.frm_callcenter.consumidor_cpf, "cpf")'>
				<input name="cliente"  class="input" value='<?echo $cliente ;?>' type="hidden"> 
			</td>
			<td align='left'><strong>Rg:</strong></td>
			<td align='left'>
				<input name="consumidor_rg"  value='<?echo $consumidor_rg ;?>'  class="input" type="text" size="14" maxlength="14" <? if($login_fabrica==11 and (strlen($callcenter) >0 or strlen($Id) >0) ){?>  readonly <?}?> > 
			</td>
		</tr>
		<tr> 
			<td align='left'><strong>E-mail:</strong></td>
			<td align='left'>
				<input name="consumidor_email"   value='<?echo $consumidor_email ;?>' class="input" type="text" size="40" maxlength="500" <? if($login_fabrica==11 and (strlen($callcenter) >0 or strlen($Id) >0) ){?>  readonly <?}?> >
			</td>
			<td align='left'><strong>Telefone:</strong></td>
			<td align='left'>
				<input name="consumidor_fone" id="telefone" value='<?echo $consumidor_fone ;?>'  class="input"  type="text" size="18" maxlength="15" onkeypress="return txtBoxFormat(this.form, this.name, '(99) 9999-9999', event);" <? if($login_fabrica==11 and (strlen($callcenter) >0 or strlen($Id) >0) ){?>  readonly <?}?> >
			</td>
			<td align='left'><strong>Cep:</strong></td>
			<td align='left'>
				<input name="consumidor_cep" id='cep' value='<?echo $consumidor_cep ;?>' class="input" type="text" size="14" maxlength="9" onblur="buscaCEP(this.value, document.frm_callcenter.consumidor_endereco, document.frm_callcenter.consumidor_bairro, document.frm_callcenter.consumidor_cidade, document.frm_callcenter.consumidor_estado) ;" onkeypress="return txtBoxFormat(this.form, this.name, '99999-999', event);" <? if($login_fabrica==11 and (strlen($callcenter) >0 or strlen($Id) >0) ){?>  readonly <?}?> >
			</td>
		</tr>
		<tr>
			<td align='left'><strong>Endereço:</strong></td>
			<td align='left'>
				<input name="consumidor_endereco" id='consumidor_endereco' value='<?echo $consumidor_endereco ;?>' class="input" type="text" size="40" maxlength="500" <? if($login_fabrica==11 and (strlen($callcenter) >0 or strlen($Id) >0) ){?>  readonly <?}?> > 
			</td>
			<td align='left'><strong>Número:</strong></td>
			<td align='left'>
				<input name="consumidor_numero" id='consumidor_numero' value='<?echo $consumidor_numero ;?>' class="input" type="text" size="18" maxlength="16" <? if($login_fabrica==11 and (strlen($callcenter) >0 or strlen($Id) >0) ){?>  readonly <?}?> > 
			</td>
			<td align='left'><strong>Complem.</strong></td>
			<td align='left'>
				<input name="consumidor_complemento" id='consumidor_complemento' value='<?echo $consumidor_complemento ;?>' class="input" type="text" size="14" maxlength="14" <? if($login_fabrica==11 and (strlen($callcenter) >0 or strlen($Id) >0) ){?>  readonly <?}?> >
			</td>
		</tr>
		<tr>
			<td align='left'><strong>Bairro:</strong></td>
			<td align='left'>
				<input name="consumidor_bairro" id='consumidor_bairro' value='<?echo $consumidor_bairro ;?>' class="input" type="text" size="40" maxlength="30" <? if($login_fabrica==11 and (strlen($callcenter) >0 or strlen($Id) >0) ){?>  readonly <?}?> >
			</td>
			<td align='left'><strong>Cidade:</strong></td>
			<td align='left'>
				<input name="consumidor_cidade" id='consumidor_cidade' value='<?echo $consumidor_cidade ;?>'   class="input" type="text" size="18" maxlength="16" <? if($login_fabrica==11 and (strlen($callcenter) >0 or strlen($Id) >0) ){?>  readonly <?}?> >
				<input name="cidade"  class="input" value='<?echo $cidade ;?>' type="hidden"> 
			</td>
			<td align='left'><strong>Estado:</strong></td>
			<td align='left'>
				<select name="consumidor_estado" id='consumidor_estado' style='width:81px; font-size:9px'>
					<? $ArrayEstados = array('AC','AL','AM','AP',
												'BA','CE','DF','ES',
												'GO','MA','MG','MS',
												'MT','PA','PB','PE',
												'PI','PR','RJ','RN',
												'RO','RR','RS','SC',
												'SE','SP','TO'
											);
					for ($i=0; $i<=26; $i++){
						echo"<option value='".$ArrayEstados[$i]."'";
						if ($consumidor_estado == $ArrayEstados[$i]) echo " selected";
						echo ">".$ArrayEstados[$i]."</option>\n";
					}?>
				</select>
			</td>
		</tr>
		<tr>
			<td colspan='2' align='left'>
				<strong>Melhor horário p/ contato: </strong>
				<input name="hora_ligacao" id='hora_ligacao' class="input" value='<?echo $hora_ligacao ;?>' type="text" maxlength='5' size='7'> 
			</td>
			<td align='left'><strong>Origem:</strong></td>
			<td align='left'>
				<select name='origem' id='origem' style='width:102px;font-size:9px'>
				<option value='Telefone' <?PHP if ($origem == 'Telefone') { echo "Selected";}?>>Telefone</option>
				<option value='Email' <?PHP if ($origem == 'Email') { echo "Selected";}?>>E-mail</option>
				</select>
			</td>
			<td align='left'><strong>Tipo:</strong></td>
			<td align='left'>
				<select name="consumidor_revenda" id='consumidor_revenda' style='width:81px; font-size:9px'>
				<option value='C'>Consumidor</option>
				<option value='R'>Revenda</option>
				</select>
			</td>
		</tr>
		<tr>
			<td colspan='2' align='left'>
				<INPUT TYPE="checkbox" NAME="receber_informacoes" <? if($receber_informacoes=="t") echo "checked";?> value='t'>
				<strong>Aceita receber informações sobre nossos produtos? </strong> <br>
				<a  href="javascript:fnc_pesquisa_os (document.frm_callcenter.consumidor_cpf, 'os')">Clique aqui para ver OS</a>
			</td>
			<? if($login_fabrica ==51) { ?>
			<td align='left' colspan='1'><strong>Telefone 2:</strong></td>
			<td align='left' colspan='3'>
				<input name="consumidor_fone2" id="telefone2" value='<?echo $consumidor_fone2 ;?>'  class="input"  type="text" size="18" maxlength="14" onkeypress="return txtBoxFormat(this.form, this.name, '(99) 9999-9999', event);">
			</td>
			<? } ?>
			<?if ($login_fabrica == 11) { // HD 14549?>
			<td align='left'><strong>OS:</strong></td>
			<td align='left'> 
			<input name="os"  class="input"  value='<?echo $sua_os ;?>'>
			</td>
			<? } ?>
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
				<?
				$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='2' AND fabrica = $login_fabrica";
				$pe = pg_exec($con,$sql);
				if(pg_numrows($pe)>0) {
					echo pg_result($pe,0,0);
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
				<input name="produto_referencia"  class="input"  value='<?echo $produto_referencia ;?>' 
				onblur="javascript: fnc_pesquisa_produto2 (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'referencia',document.frm_callcenter.mapa_linha);mostraDefeitos('Reclamação',document.frm_callcenter.produto_referencia.value);atualizaQuadroMapas();" type="text" size="15" maxlength="15"> 
				<img src='imagens/lupa.png' border='0' align='absmiddle'
				style='cursor: pointer' 
				onclick="javascript: fnc_pesquisa_produto2 (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao',document.frm_callcenter.mapa_linha)"> 
			</td>
			<td align='left'><strong>Descrição:</strong></td>
			<td align='left'> 
				<input type='hidden' name='produto' value="<? echo $produto; ?>">
				<input name="produto_nome"  class="input" value='<?echo $produto_nome ;?>' 
				onblur="javascript: fnc_pesquisa_produto2 (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao',document.frm_callcenter.mapa_linha); mostraDefeitos('Reclamação',document.frm_callcenter.produto_referencia.value); atualizaQuadroMapas();" type="text" size="35" maxlength="500"> 
				<img src='imagens/lupa.png' border='0' align='absmiddle'
				style='cursor: pointer' 
				onclick="javascript: fnc_pesquisa_produto2 (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao',document.frm_callcenter.mapa_linha)">
			</td>
			<td align='left'><strong>Série:</strong></td>
			<td align='left'> 
				<input name="serie"  class="input"  value='<?echo $serie;?>'>
			</td>
		</tr>
		<tr>
			<td align='left'><strong>NF compra:</strong></td>
			<td align='left'> 
				<input name="nota_fiscal" id='nota_fiscal' class="input" value='<?echo $nota_fiscal;?>' > 
			</td>
			<td align='left'><strong>Data NF:</strong></td>
			<td align='left'> 
				<input name="data_nf" id='data_nf' class="input" rel='data' value='<?echo $data_nf ;?>'>
			</td>
		</tr>
	</table>
	<table width="100%" border='0'>
		<tr>
			<td align='left'><strong>Mapa da Rede</strong></td>
		</tr>
	</table>
	<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
		<tr>
			<td align='left' width='50'><strong>Linha:</strong></td>
			<td align='left'> 
			<?
			$sql = "SELECT  *
					FROM    tbl_linha
					WHERE   tbl_linha.fabrica = $login_fabrica 
					ORDER BY tbl_linha.nome;";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) {
				echo "<select name='mapa_linha' class='frm'>\n";
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
			
				<input type='button' name='btn_mapa' value='mapa' onclick='javascript:mapa_rede(mapa_linha,mapa_estado,mapa_cidade)'>
				</font>
			</td>
		</tr>
			<tr>
				<td align='left'><strong>Código:</strong></td>
				<td align='left'> 
					<input name="codigo_posto_tab" id="codigo_posto_tab"  class="input" value='<?echo $codigo_posto_tab;?>'  type="text" size="15" maxlength="15"> 
				</td>
				<td align='left'><strong>Nome:</strong></td>
				<td align='left'> 
					<input type='hidden' name='posto_tab' value="<? echo $posto_tab; ?>">
					<input name="posto_nome_tab" id="posto_nome_tab"  class="input" value='<?echo $posto_nome_tab ;?>'  type="text" size="35" maxlength="500"> 
					
				</td>
			</tr>

		<tr>
			<td colspan='6'>
			<?
			if(strlen($callcenter)==0){
				echo "<tr><td align='left' colspan='6'>";
				echo "<strong><input type='checkbox' name='abre_os' id='abre_os' value='t' onClick='verificarImpressao(this)'> Abrir OS para o esta Autorizada</strong>";
				echo "<div id='imprimir_os' style='display:none'><strong>&nbsp;&nbsp;&nbsp;&nbsp;<input type='checkbox' name='imprimir_os' value='t'> Imprimir OS</strong></div>";
				echo "</td></tr>";
			}
			?>
			</td>
		</tr>
		</table>

	<table width="100%" border='0'>
		<tr>
			<td align='left'><strong>Dados para Coleta</strong></td>
		</tr>
	</table>
	<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
		<tr>
			<td align='left' width='20'><strong>Contato:</strong></td>
			<td align='left'><INPUT TYPE="text" NAME="coleta_contato" size='40'></td>
			<td align='left'><strong>Endereço:</strong></td>
			<td align='left'><INPUT TYPE="text" NAME="coleta_endereco" size='40'></td>
			<td align='left'><strong>Número:</strong></td>
			<td align='left'><INPUT TYPE="text" NAME="coleta_numero" size='5'></td>
		</tr>
		<tr>
			<td align='left'><strong>Bairro:</strong></td>
			<td align='left'><INPUT TYPE="text" NAME="coleta_bairro"></td>
			<td align='left'><strong>Cidade:</strong></td>
			<td align='left'><INPUT TYPE="text" NAME="coleta_cidade"></td>
			<td align='left'><strong>Estado:</strong></td>
			<td align='left'><INPUT TYPE="text" NAME="coleta_estado" size='5'></td>
		</tr>
		<tr>
			<td align='left'><strong>Telefone:</strong></td>
			<td align='left'><INPUT TYPE="text" NAME="coleta_telefone"></td>
			<td align='left'><strong>Horário:</strong></td>
			<td align='left'><INPUT TYPE="text" NAME="coleta_horario" size='5'></td>
		</tr>
		<tr>
			<td align='left'><strong>N.Série Ap. Novo</strong></td>
			<td align='left'><INPUT TYPE="text" NAME="coleta_numero_serie_deixado"></td>
		</tr>
	</table>

	<br>

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
    <td align='left'>


	<input type='hidden' name='tab_atual' id='tab_atual' value='<? if(strlen($callcenter)>0){echo $natureza_chamado;}?>' >

	<div id="container-Principal">


	<ul>
		<?if($login_fabrica==25){ ?>
		<li>
			<a href="#extensao" onclick="javascript:$('#tab_atual').val('extensao')">
			<span><img src='imagens/garantia_estendida.png' width='10' align="absmiddle">Garantia</span>
			</a>
		</li>
		<?}?>
		<li>
			<a href="#reclamacao_produto" onclick="javascript:$('#tab_atual').val('reclamacao_produto');">
			<span>
			<!--<img src='imagens/rec_produto.png' width='10' align="absmiddle" alt='Reclamação Produto/Defeito'>-->LG</span>
			</a>
		</li>
		<li>
			<a href="#reclamacao_empresa" onclick="javascript:$('#tab_atual').val('reclamacao_empresa')">
			<span><!--<img src='imagens/rec_empresa.png' width='10' align="absmiddle" alt='Reclamação Empresa'>-->Samsung</span>
			</a>
		</li>
		<li>
			<a href="#reclamacao_at" onclick="javascript:$('#tab_atual').val('reclamacao_at')">
			<span><!--<img src='imagens/rec_empresa.png' width='10' align="absmiddle" alt='Reclamação Assistência Técnica'>-->Motorola</span>
			</a>
		</li>
		<li>
			<a href="#duvida_produto" onclick="javascript:$('#tab_atual').val('duvida_produto')">
			<span><!--<img src='imagens/duv_produto.png' width='10' align=absmiddle>-->Nokia</span>
			</a>
		</li>
		<li>
			<a href="#sugestao" onclick="javascript:$('#tab_atual').val('sugestao')">
			<span><!--<img src='imagens/sugestao_call.png' width='10' align=absmiddle>-->Sony Ericsson</span>
			</a>
		</li>
		<!--<li>
			<a href="#assistencia" onclick="javascript:$('#tab_atual').val('assistencia');">
			<span>Busca A.T.</span>
			</a>
		</li>
		
		<li>
			<a href="#at_proximo" onclick="javascript:$('#tab_atual').val('at_proximo');">
			<span><img src='imagens/lupa.png' width='10' align=absmiddle>A.T. Próx.</span>
			</a>
		</li>
		-->

<?/*
		<li>
			<a href="#procon" onclick="javascript:$('#tab_atual').val('procon');">
			<span><!--<img src='imagens/lupa.png' width='10' align=absmiddle>-->Procon/Jec.</span>
			</a>
		</li>
		<li>
			<a href="#onde_comprar" onclick="javascript:$('#tab_atual').val('onde_comprar');">
			<span><!--<img src='imagens/lupa.png' width='10' align=absmiddle>-->Onde Comprar</span>
			</a>
		</li>
*/?>
<?/*
		<?if($login_fabrica==45 OR $login_fabrica ==46 OR $login_fabrica ==11){?>
<?/*
		<br>
		<li>
			<a href="#garantia" onclick="javascript:$('#tab_atual').val('garantia')">
			<span><!--<img src='imagens/garantia_estendida.png' width='10' align="absmiddle">-->Garantia</span>
			</a>
		</li>
		<?}?>
*/?>
<?/*
		<?if($login_fabrica==46 OR $login_fabrica == 11){?>
<?/*
		<li>
			<a href="#troca_produto" onclick="javascript:$('#tab_atual').val('troca_produto')">
			<span><!--<img src='imagens/rec_empresa.png' width='10' align="absmiddle">-->Troca Prod.</span>
			</a>
		</li>
		<?}?>
*/?>
	</ul>


	<?if($login_fabrica==25){?>
		<div id="extensao" class='tab_content'>

			<div rel='div_ajuda' style='display:inline; Position:relative;'>
			<? if(strlen($callcenter)==0){ ?>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>Oferecer Garantia Estendida.</STRONG><BR>
						O Sr.(a) gostaria de estar cadastrando a garantia estendida do seu produto?
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>
				<?} ?>
			Informações do Produto

			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left'><strong>Referência:</strong></td>
				<td align='left'> 
					<input name="produto_referencia_es" id="produto_referencia_es"  class="input"  value='<?echo $produto_referencia ;?>' 
					onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_es,document.frm_callcenter.produto_nome_es,'referencia')" type="text" size="10" maxlength="15"> 
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer' 
					onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao')"> 
				</td> 
				<td align='left'><strong>Descrição:</strong></td>
				<td align='left'>  
					<input type='hidden' name='produto' value="<? echo $produto; ?>">
					<input name="produto_nome_es"  id="produto_nome_es"  class="input" value='<?echo $produto_nome ;?>' 
					onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_es,document.frm_callcenter.produto_nome_es,'descricao')" type="text" size="30" maxlength="500"> 
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer' 
					onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao')">
				</td>
				<td align='left'><strong>Série:</strong></td>
				<td align='left'> 
					<input name="serie_es" id='serie_es'  class="input"  value='<?echo $serie ;?>'>
				</td>
				<td align='left'> <?if(strlen($callcenter)==0){?>
				<INPUT TYPE="button" onClick='javascritp:fn_verifica_garantia();' name='Verificar' value='Verificar'>
				<?}?>
					
				</td>
			</tr>

			<tr>
				<td colspan='7'>
					<div id='div_estendida'>
					<? if(strlen($callcenter)>0){
							if(strlen($serie)>0){
								include "conexao_hbtech.php";

								$sql = "SELECT	idNumeroSerie  ,
												idGarantia     ,
												revenda        ,
												cnpj
										FROM numero_serie 
										WHERE numero = '$serie'";
								$res = mysql_query($sql) or die("Erro no Sql:".mysql_error());
							
								if(mysql_num_rows($res)>0){
									$idNumeroSerie = mysql_result($res,0,idNumeroSerie);
									$idGarantia    = mysql_result($res,0,idGarantia);
									$es_revenda       = mysql_result($res,0,revenda);
									$es_cnpj          = mysql_result($res,0,cnpj);

									if(strlen($idGarantia)>0){
										$sql = "SELECT	nf                ,
														dataCompra        ,
														municipioCompra   ,
														estadoCompra      ,
														dataNascimento    ,
														estadoCivil       ,
														filhos            ,
														sexo              ,
														dddComercial      ,
														foneComercial     ,
														dddCelular        ,
														foneCelular       ,
														prefMusical
												FROM garantia
												WHERE idGarantia = $idGarantia;
											";
										$res = mysql_query($sql) or die("Erro no Sql:".mysql_error());
																	
										if(mysql_num_rows($res)>0){
											$es_nf                    = mysql_result($res,0,nf);
											$es_dataCompra            = mysql_result($res,0,dataCompra);
											$es_municipioCompra       = mysql_result($res,0,municipioCompra);
											$es_estadoCompra          = mysql_result($res,0,estadoCompra);
											$es_dataNascimento        = mysql_result($res,0,dataNascimento);
											$es_estadoCivil           = mysql_result($res,0,estadoCivil);
											$es_filhos                = mysql_result($res,0,filhos);
											$es_sexo                  = mysql_result($res,0,sexo);
											$es_dddComercial          = mysql_result($res,0,dddComercial);
											$es_foneComercial         = mysql_result($res,0,foneComercial);
											
											$es_telComercial  = "($es_dddComercial) $es_foneComercial";

											$es_dddCelular            = mysql_result($res,0,dddCelular);
											$es_foneCelular           = mysql_result($res,0,foneCelular);
											$es_prefMusical           = mysql_result($res,0,prefMusical);
											$es_telCelular  = "($es_dddCelular) $es_foneCelular";

											$es_dataCompra = converte_data($es_dataCompra);
											$es_dataCompra = str_replace("-","/",$es_dataCompra);

											$es_dataNascimento = converte_data($es_dataNascimento);
											$es_dataNascimento = str_replace("-","/",$es_dataNascimento);

										}

										echo "<input name='es_id_numeroserie' id='es_id_numeroserie' value='$idNumeroSerie' type='hidden'>";
										echo "<table width='98%' border='0' align='center' cellpadding='2' cellspacing='2' style=' font-size:10px'>";
										echo "<tr>";
											echo "<td><B>Cnpj Revenda:</B></td>";
											echo "<td><input name='es_revenda_cnpj' id='es_revenda_cnpj' class='input' value='$es_cnpj' type='text' maxlength='14' size='15' readonly></td>";
											echo "<td><B>Nome Revenda:</B></td>";
											echo "<td><input name='es_revenda' id='es_revenda' class='input' value='$es_revenda' type='text' maxlength='50' size='25' readonly></td>";
											echo "<td><B>Nota Fiscal:</B></td>";
											echo "<td><input name='es_nota_fiscal' id='es_nota_fiscal' class='input' value='$es_nf' type='text' maxlength='8' size='8'> </td>";
										echo "</tr>";
					 
										echo "<tr>";
											echo "<td><B>Data Compra:</B></td>";
											echo "<td><input name='es_data_compra' id='es_data_compra' class='input' value='$es_dataCompra' type='text' maxlength='10' size='12'></td>";
											echo "<td><B>Municipio Compra:</B></td>";
											echo "<td><input name='es_municipiocompra' id='es_municipiocompra' class='input' value='$es_municipioCompra' type='text' maxlength='255' size='25'></td>";
											echo "<td><B>Estado Compra:</B></td>";
											echo "<td>";
											echo "<select name='es_estadocompra' id='es_estadocompra' style='width:52px; font-size:9px' >";
											 $ArrayEstados = array('AC','AL','AM','AP',
																		'BA','CE','DF','ES',
																		'GO','MA','MG','MS',
																		'MT','PA','PB','PE',
																		'PI','PR','RJ','RN',
																		'RO','RR','RS','SC',
																		'SE','SP','TO'
																	);
											for ($i=0; $i<=26; $i++){
												echo"<option value='".$ArrayEstados[$i]."'";
												if ($es_estadoCompra == $ArrayEstados[$i]) echo " selected";
												echo ">".$ArrayEstados[$i]."</option>\n";
											}
											echo "</select>";
											echo "</td>";
										echo "</tr>";


										echo "<tr>";
											echo "<td><B>Data Nascimento:</B></td>";
											echo "<td><input name='es_data_nascimento' id='es_data_nascimento' class='input' value='$es_dataNascimento' type='text' maxlength='10' size='12'></td>";
											echo "<td><B>Estado Civil:</B></td>";
											echo "<td>";
											echo "<select name='es_estadocivil' id='es_estadocivil' style='width:100px; font-size:9px' >";
											echo "<option value=''></option>";
											echo "<option value='0' ";
											if($es_estadoCivil=="0")echo "SELECTED";
											echo ">Solteiro(a)</option>";
											echo "<option value='1' ";
											if($es_estadoCivil=="1")echo "SELECTED";
											echo ">Casado(a)</option>";
											echo "<option value='2' ";
											if($es_estadoCivil=="2")echo "SELECTED";
											echo ">Divorciado(a)</option>";
											echo "<option value='3' ";
											if($es_estadoCivil=="3")echo "SELECTED";
											echo ">Viuvo(a)</option>";
											echo "</select>";
											echo "</td>";
											echo "<td><B>Sexo:</B></td>";
											echo "<td>";
											echo "<INPUT TYPE='radio' NAME='es_sexo' ";
											if($es_sexo == "0") echo "CHECKED ";
											echo "value='0'>M. ";
											echo "<INPUT TYPE='radio' NAME='es_sexo' ";
											if($es_sexo == "1") echo "CHECKED ";
											echo " value='1'>F. ";
											echo "</td>";
										echo "</tr>";

										echo "<tr>";
											echo "<td><B>Filhos:</B></td>";
											echo "<td>";
											echo "<INPUT TYPE='radio' NAME='es_filhos' ";
											if($es_filhos == "0") echo "CHECKED ";
											echo "value='0'>Sim ";
											echo "<INPUT TYPE='radio' NAME='es_filhos' ";
											if($es_filhos == "1") echo "CHECKED ";
											echo "value='1'>Não ";
											echo "</td>";
											echo "<td><B>Fone Comercial:</B></td>";
											echo "<td><input name='es_fonecomercial' id='es_fonecomercial' class='input' value='$es_telComercial' type='text' maxlength='14' size='16'></td>";

											echo "<td><B>Celular:</B></td>";
											echo "<td>";
											echo "<input name='es_celular' id='es_celular' class='input' value='$es_telCelular' type='text' maxlength='14' size='16'>";
											echo "</td>";
										echo "</tr>";

										echo "<tr>";
											echo "<td colspan='6'><B>Preferência Musical:</B> ";
											echo "<input name='es_preferenciamusical' id='es_preferenciamusical' class='input' value='$es_prefMusical' type='text' maxlength='255' size='100'>";
											echo "</td>";
										echo "</tr>";


										echo "</table>";
									
									}
								}else{
									echo "Número de série não encontrado nas vendas";
								
								}
 
							}
					
						}
					?>
					
					</div>
				</td>
			</tr>
			<tr>
				<td align='left'><strong>Descrição:</strong></td>
				<td colspan='6'>
				<TEXTAREA NAME="reclamado_es" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
				</div>
				</td>
			</tr>
			</table>
			</div>



		</div>
	<? } ?>

		<div id="reclamacao_produto" class='tab_content'>

			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>		
			<tr> 
				<td align='left' valign='top'><strong>OBSERVAÇÃO:</strong></td>
				<td align='left' colspan='5'>
					<TEXTAREA NAME="reclamado_sugestao" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
				</td>
			</tr>
			</table>

			<BR>

			<div rel='div_ajuda' style='display:inline; Position:relative;'>

				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>
						<?
						$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='8' AND fabrica = $login_fabrica";
						$pe = pg_exec($con,$sql);
						if(pg_numrows($pe)>0) {
							echo pg_result($pe,0,0);
						}else{
							echo "Sr.(a) estou encaminhando a sua reclamação ao Depto. responsável, que responderá em 12 h.";
						}
						?>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>

			</div>
		</div>


		<div id="reclamacao_empresa" class='tab_content'>
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>		
			<tr> 
				<td align='left' valign='top'><strong>OBSERVAÇÃO:</strong></td>
				<td align='left' colspan='5'>
					<TEXTAREA NAME="reclamado_sugestao" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
				</td>
			</tr>
			</table>

			<BR>

			<div rel='div_ajuda' style='display:inline; Position:relative;'>

				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>
						<?
						$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='8' AND fabrica = $login_fabrica";
						$pe = pg_exec($con,$sql);
						if(pg_numrows($pe)>0) {
							echo pg_result($pe,0,0);
						}else{
							echo "Sr.(a) estou encaminhando a sua reclamação ao Depto. responsável, que responderá em 12 h.";
						}
						?>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>

			</div>


		</div>

		<div id="reclamacao_at" class='tab_content'>


			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>		
			<tr> 
				<td align='left' valign='top'><strong>OBSERVAÇÃO:</strong></td>
				<td align='left' colspan='5'>
					<TEXTAREA NAME="reclamado_sugestao" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
				</td>
			</tr>
			</table>

			<BR>

			<div rel='div_ajuda' style='display:inline; Position:relative;'>

				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>
						<?
						$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='8' AND fabrica = $login_fabrica";
						$pe = pg_exec($con,$sql);
						if(pg_numrows($pe)>0) {
							echo pg_result($pe,0,0);
						}else{
							echo "Sr.(a) estou encaminhando a sua reclamação ao Depto. responsável, que responderá em 12 h.";
						}
						?>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>

			</div>


		</div>

		<div id="duvida_produto" class='tab_content'>

			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>		
			<tr> 
				<td align='left' valign='top'><strong>OBSERVAÇÃO:</strong></td>
				<td align='left' colspan='5'>
					<TEXTAREA NAME="reclamado_sugestao" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
				</td>
			</tr>
			</table>

			<BR>

			<div rel='div_ajuda' style='display:inline; Position:relative;'>

				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>
						<?
						$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='8' AND fabrica = $login_fabrica";
						$pe = pg_exec($con,$sql);
						if(pg_numrows($pe)>0) {
							echo pg_result($pe,0,0);
						}else{
							echo "Sr.(a) estou encaminhando a sua reclamação ao Depto. responsável, que responderá em 12 h.";
						}
						?>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>

			</div>

		</div>


		<div id="sugestao" class='tab_content'>
		
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>		
			<tr> 
				<td align='left' valign='top'><strong>OBSERVAÇÃO:</strong></td>
				<td align='left' colspan='5'>
					<TEXTAREA NAME="reclamado_sugestao" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
				</td>
			</tr>
			</table>

			<BR>

			<div rel='div_ajuda' style='display:inline; Position:relative;'>

				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>
						<?
						$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='8' AND fabrica = $login_fabrica";
						$pe = pg_exec($con,$sql);
						if(pg_numrows($pe)>0) {
							echo pg_result($pe,0,0);
						}else{
							echo "Sr.(a) estou encaminhando a sua reclamação ao Depto. responsável, que responderá em 12 h.";
						}
						?>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>

			</div>
		
		</div>

		<!--
		<div id="assistencia" class='tab_content'>

			<div rel='div_ajuda' style='display:inline; Position:relative;'>
			
				<table width='680' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
					<td align='center'><STRONG>Qual o problema com o produto?</strong></td>
					<td align='right' width='150'></td>
				</tr>
				</table>
		
			</div>

			Informações do Produto
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left'><strong>Referência:</strong></td>
				<td align='left'> 
					<input name="produto_referencia_pa"  class="input" value='<?echo $produto_referencia ;?>' 
					onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_pa,document.frm_callcenter.produto_nome_pa,'referencia');" type="text" size="15" maxlength="15"> 
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer' 
					onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_pa,document.frm_callcenter.produto_nome_pa,'descricao')"> 
				</td>
				<td align='left'><strong>Descrição:</strong></td>
				<td align='left'> 
					<input type='hidden' name='produto' value="<? echo $produto; ?>">
					<input name="produto_nome_pa"  class="input" value='<?echo $produto_nome ;?>' 
					onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_pa,document.frm_callcenter.produto_nome_pa,'descricao');" type="text" size="35" maxlength="500"> 
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer' 
					onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_pa,document.frm_callcenter.produto_nome_pa,'descricao')">
				</td>
				<td align='left'><strong>Série:</strong></td>
				<td align='left'> 
					<input name="serie_pa"  class="input" value='<?echo $serie ;?>'>
				</td>
			</tr>
			<tr>
				<td align='left' valign='top'><strong>Problema:</strong></td>
				<td align='left' colspan='6'>
					<TEXTAREA NAME="reclamado_pa" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
				</td>
			</tr>
			<tr>
				<td><strong>Dúvida:</strong></td>
				<td align='left' colspan='5'> 
					<input name="faq_duvida_pa"  id='faq_duvida_pa' class="input"  size='74' value='<?echo $faq_duvida ;?>'>
					<input  class="input"  type="button" name="bt_localizar"        value='Localizar' onclick="javascript:localizarFaq(document.frm_callcenter.produto_referencia_pa.value,'faq_duvida_pa')">
				</td>
			</tr>
			<tr>
				<td colspan='6'>
					<div id='div_faq_duvida_pa' style='display:inline; Position:relative;background-color: #e6eef7;width:100%'>
					</div>
				</td>
			</tr>
			</table>

			Consultar Posto Autorizado
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left' colspan='6'><strong><a href="javascript: fnc_pesquisa_at_proximo('<?echo $login_fabrica?>')" title="Localize o Posto Autorizado" >Clique aqui para consultar o posto autorizado mais próximo do consumidor</a></strong></td>
			</tr>
			</table>

		</div>
		
		<div id="at_proximo" class='tab_content'>

			<div rel='div_ajuda' style='display:inline; Position:relative;'>
			
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
					<td align='center'><STRONG><?
					$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='9' AND fabrica = $login_fabrica";
					$pe = pg_exec($con,$sql);
					if(pg_numrows($pe)>0) {
						echo pg_result($pe,0,0);
					}else{
						echo "Qual o Posto mais próximo do consumidor?";
					}?>
					</strong></td>
					<td align='right' width='150'></td>
				</tr>
				</table>
		
			</div>

			Informações do Posto Autorizado mais próximo
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left'><strong>Código:</strong></td>
				<td align='left'> 
					<input name="codigo_posto_tab"  class="input" value='<?echo $codigo_posto_tab;?>' 
					onblur="javascript: fnc_pesquisa_at_proximo('<? echo $login_fabrica?>');" type="text" size="15" maxlength="15"> 
				</td>
				<td align='left'><strong>Nome:</strong></td>
				<td align='left'> 
					<input type='hidden' name='posto_tab' value="<? echo $posto_tab; ?>">
					<input name="posto_nome_tab"  class="input" value='<?echo $posto_nome_tab ;?>' 
					onblur="javascript: fnc_pesquisa_at_proximo('<?echo $login_fabrica?>');" type="text" size="35" maxlength="500"> 
					
				</td>
			</tr>
			<tr>
				<td align='left'><strong>Cidade:</strong></td>
				<td align='left'> 
					<input name="posto_cidade_tab"  class="input" value='<?echo $posto_cidade_tab;?>'>
				</td>
				<td align='left' size="35" valign='top'><strong>Endereço:</strong></td>
				<td align='left'> 
					<input name="posto_endereco_tab"  class="input" value='<?echo $posto_endereco_tab;?>'>
				</td>
				<td align='left' valign='top'><strong>Estado:</strong></td>
				<td align='left'> 
					<input name="posto_estado_tab"  class="input" size = '8' value='<?echo $posto_estado_tab;?>'>
				</td>
			</tr>
			</table>
			Consultar Posto Autorizado
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left' colspan='6'><strong><a href="javascript: fnc_pesquisa_at_proximo('<?echo $login_fabrica?>')" title="Localize o Posto Autorizado" >Clique aqui para consultar o posto autorizado mais próximo do consumidor</a></strong></td>
			</tr>
			<?
			if(strlen($callcenter)==0){
				echo "<tr><td align='left' colspan='6'><strong><input type='checkbox' name='abre_os' id='abre_os' value='t'> Abrir OS para o esta Autorizada</strong></td></tr>";
			}
			?>
			</table>
		</div>
		-->
		<div id="procon" class='tab_content'>
			<div rel='div_ajuda' style='display:inline; Position:relative;'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>Confirmar ou perguntar a reclamação.</STRONG><BR>
						<?
						$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='11' AND fabrica = $login_fabrica";
						$pe = pg_exec($con,$sql);
						if(pg_numrows($pe)>0) {
							echo pg_result($pe,0,0);
						}else{
							echo "Qual é a reclamação feita no Procon pelo SR.(a)?<BR>	ou<BR> O Sr.(a), correto?";
						}
						?>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>

			</div>

			Informações da Reclamação
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left' valign='top'><strong>Reclamação:</strong></td>
			    <td align='left' colspan='5'>
				  <TEXTAREA NAME="reclamado_procon" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
				</td>
			</tr>
			</table>
			
			<BR>


		</div>

		<div id="onde_comprar" class='tab_content'>
			<div rel='div_ajuda' style='display:inline; Position:relative;'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>Informar dados da Revenda.</STRONG><BR>
						<?
						$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='11' AND fabrica = $login_fabrica";
						$pe = pg_exec($con,$sql);
						if(pg_numrows($pe)>0) {
							echo pg_result($pe,0,0);
						}else{
							echo "Quais são os dados da Revenda?";
						}
				?>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>

			</div>

			<?
			# HD 31204 - Francisco Ambrozio
			#   Alterado campo onde comprar para a Dynacom
			if ($login_fabrica == 2){ 
				if (strlen($revenda) > 0){
				$sql = "SELECT tbl_revenda.nome, 
							tbl_revenda.endereco, 
							tbl_revenda.numero, 
							tbl_revenda.complemento, 
							tbl_revenda.bairro,
							tbl_revenda.fone,
							tbl_cidade.nome AS revenda_city,
							tbl_cidade.estado AS revenda_uf
							FROM tbl_revenda
							JOIN tbl_cidade USING (cidade)
							WHERE revenda = $revenda";
				$res = pg_exec($con,$sql);
				
				if(pg_numrows($res)>0){
					$revenda_nome             = pg_result($res,0,nome);
					$revenda_endereco         = pg_result($res,0,endereco);
					$revenda_nro              = pg_result($res,0,numero);
					$revenda_cmpto            = pg_result($res,0,complemento);
					$revenda_bairro           = pg_result($res,0,bairro);
					$revenda_city             = pg_result($res,0,revenda_city);
					$revenda_uf               = pg_result($res,0,revenda_uf);
					$revenda_fone             = pg_result($res,0,fone);
				}
			}
			?>
				Informações da Revenda
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
				<tr> 
					<td align='left' width='68'><strong>Localizar:</strong></td>
					<td align='left'>
						<input name="localizarrevenda" id='localizarrevenda' value='<?echo $localizarrevenda ;?>' class="input" type="text" size="40" maxlength="500"> <a href='#' onclick='javascript: fnc_pesquisa_revenda (document.frm_callcenter.localizarrevenda, "nome")'>Por Nome</a> | <a href='#' onclick='javascript:fnc_pesquisa_revenda (document.frm_callcenter.localizarrevenda, "cidade")'>Por Cidade</a> | <a href='#' onclick='javascript:fnc_pesquisa_revenda (document.frm_callcenter.localizarrevenda, "cnpj")'>Por CNPJ</a>
					</td>
				</tr>
				<tr>
					<td align='left'><strong>Nome:</strong></td>
					<td align='left'><input type='hidden' name='revenda' id='revenda' value='<?=$revenda?>'><input type='text' name='revenda_nome' id='revenda_nome' value='<?=$revenda_nome?>'  size="40" maxlength="500">
					</td>
				</tr>
				<tr>
					<td align='left'><strong>Endereço:</strong></td>
					<td align='left'><input type='text' name='revenda_endereco' id='revenda_endereco' value='<?=$revenda_endereco?>'  size="40" maxlength="500">
					</td>
					<td align='left'><strong>Nro.:</strong></td>
					<td align='left'><input type='text' name='revenda_nro' id='revenda_nro' value='<?=$revenda_nro?>'>
					</td>
					<td align='left'><strong>Complemento:</strong></td>
					<td align='left'><input type='text' name='revenda_cmpto' id='revenda_cmpto' value='<?=$revenda_cmpto?>'>
					</td>
				</tr>
					<tr>
					<td align='left'><strong>Bairro:</strong></td>
					<td align='left'><input type='text' name='revenda_bairro' id='revenda_bairro' value='<?=$revenda_bairro?>'>
					</td>
					<td align='left' valign='top'><strong>Cidade:</strong></td>
					<td align='left'><input type='text' name='revenda_city' id='revenda_city' value='<?=$revenda_city?>'>
					</td>
					<td align='left'><strong>UF:</strong></td>
					<td align='left'><input type='text' name='revenda_uf' id='revenda_uf' value='<?=$revenda_uf?>'>
					</td>
				</tr>
				<tr>
					<td align='left'><strong>Telefone:</strong></td>
					<td align='left'><input type='text' name='revenda_fone' id='revenda_fone' value='<?=$revenda_fone?>'>
					</td>
				</tr><tr><td colspan='4'>Para cadastrar <a href='revenda_cadastro.php' target='_blank'>clique aqui</a></td>
				</table>
					

			<? }else{ ?>

			Informações da Reclamação
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left' valign='top'><strong>CNPJ:</strong></td>
				<td align='left' colspan='5'><input type='hidden' name='revenda' id='revenda' value='<?=$revenda?>'><input type='text' name='revenda_cnpj' id='revenda_cnpj' value='<?=$revenda_cnpj?>'>
				</td>
				<td align='left' valign='top'><strong>Nome:</strong></td>
				<td align='left' colspan='5'><input type='text' name='revenda_nome' id='revenda_nome' value='<?=$revenda_nome?>'>
				</td>
			</tr>
			<tr><td colspan='4'>Para cadastrar <a href='revenda_cadastro.php' target='_blank'>clique aqui</a></td>
			</table>

			<? } ?>
			
			<BR>

		</div>

		<?if($login_fabrica==45 OR $login_fabrica == 46 OR $login_fabrica == 11){?>
		<div id="garantia" class='tab_content'>
			<p style='font-size: 14px'><b>Garantia</b></p>
			Informações do Produto
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left'><strong>Referência:</strong></td>
				<td align='left'> 
					<input name="produto_referencia_garantia"  class="input"  value='<?echo $produto_referencia ;?>' 
					onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_garantia,document.frm_callcenter.produto_nome_garantia,'referencia');mostraDefeitos('Reclamação',document.frm_callcenter.produto_referencia_garantia.value)" type="text" size="15" maxlength="15"> 
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer' 
					onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_garantia,document.frm_callcenter.produto_nome_garantia,'descricao')"> 
				</td>
				<td align='left'><strong>Descrição:</strong></td>
				<td align='left'> 
					<input type='hidden' name='produto_garantia' value="<? echo $produto; ?>">
					<input name="produto_nome_garantia"  class="input" value='<?echo $produto_nome ;?>' 
					onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_garantia,document.frm_callcenter.produto_nome_garantia,'descricao');mostraDefeitos('Reclamação',document.frm_callcenter.produto_referencia_garantia.value)" type="text" size="35" maxlength="500"> 
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer' 
					onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_garantia,document.frm_callcenter.produto_nome_garantia,'descricao')">
				</td>
				<td align='left'><strong>Série:</strong></td>
				<td align='left'> 
					<input name="serie_garantia"  class="input"  value='<?echo $serie ;?>'>
				</td>
			</tr>

			<tr> 
				<td align='left' valign='top'><strong>Descrição:</strong></td>
				<td align='left' colspan='5'>
					<TEXTAREA NAME="reclamado_produto_garantia" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
				</td>
			</tr>
			</table>

		</div>
		<? } ?>

		<?if($login_fabrica==46 OR $login_fabrica==11){?>
		<div id="troca_produto" class='tab_content'>
			<p style='font-size: 14px'><b>Troca de Produto</b></p>
			Informações do Produto
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left'><strong>Referência:</strong></td>
				<td align='left'> 
					<input name="troca_produto_referencia"  class="input"  value='<?echo $produto_referencia ;?>' 
					onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.troca_produto_referencia,document.frm_callcenter.troca_produto_nome,'referencia');mostraDefeitos('Reclamação',document.frm_callcenter.troca_produto_referencia.value)" type="text" size="15" maxlength="15"> 
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer' 
					onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.troca_produto_referencia,document.frm_callcenter.troca_produto_nome,'descricao')"> 
				</td>
				<td align='left'><strong>Descrição:</strong></td>
				<td align='left'> 
					<input type='hidden' name='produto' value="<? echo $produto; ?>">
					<input name="troca_produto_nome"  class="input" value='<?echo $produto_nome ;?>' 
					onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.troca_produto_referencia,document.frm_callcenter.troca_produto_nome,'descricao');mostraDefeitos('Reclamação',document.frm_callcenter.troca_produto_referencia.value)" type="text" size="35" maxlength="500"> 
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer' 
					onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.troca_produto_referencia,document.frm_callcenter.troca_produto_nome,'descricao')">
				</td>
				<td align='left'><strong>Série:</strong></td>
				<td align='left'> 
					<input name="troca_serie"  class="input"  value='<?echo $serie ;?>'>
				</td>
			</tr>

<?/*		<tr>
			<td>
				<a href="javascript:mostraDefeitos('Reclamação',document.frm_callcenter.produto_referencia.value)">Defeitos</a>
				</td>
				<td align='left' colspan='5' width='630' valign='top'>
					<div id='div_defeitos' style='display:inline; Position:relative;background-color: #e6eef7;width:100%'>
					<?   if(strlen($defeito_reclamado)>0){
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
*/					?>
<?/*					</div>
				</td>
			</tr>
*/?>
			<tr> 
				<td align='left' valign='top'><strong>Descrição:</strong></td>
				<td align='left' colspan='5'>
					<TEXTAREA NAME="troca_produto_descricao" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
				</td>
			</tr>
			</table>
		</div>
		<? } ?>

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
		<td align='left' width='50'><strong>Situação:</strong></td>
		<td align='left' width='85'>
			<select name="status_interacao" style='width:80px; font-size:9px' class="input" >
				<option value=""></option>
				
				<?php
					$sqlS = " SELECT status FROM tbl_hd_status where fabrica=$login_fabrica ";
					$resS = pg_query($con,$sqlS);
				
					for ($i = 0; $i < pg_num_rows($resS);$i++){
					
						$status_hd = pg_result($resS,$i,0);
					
						$selected_status = ($status_hd == $status_interacao) ? "SELECTED" : null;
				?>
						<option value="<?=$status_hd?>" <?echo $selected_status?> ><?echo $status_hd?></option>				
				<?				
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
			<input  class="input"  type="button" name="bt" value='Gravar Atendimento' style='width:120px' onclick="javascript:if (document.frm_callcenter.btn_acao.value!='') alert('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.'); else{document.frm_callcenter.btn_acao.value='final';document.frm_callcenter.submit();}">
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
				<input  class="input"  type="button" name="bt" value='Sim' onclick="javascript:window.location='callcenter_interativo_new.php?Id=<?echo $callcenter;?>';">
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
