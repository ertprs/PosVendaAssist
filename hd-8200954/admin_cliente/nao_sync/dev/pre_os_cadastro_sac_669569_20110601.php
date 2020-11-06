 <?
//as tabs definem a categoria do chamado
/* OBSERVACAO HBTECH
	* O produto Hibeats possui uma garantia estendida, ou seja, 1 ano de garantia normal e se ele entrar no site do hibeats ou solicitar via SAC a extenso o cliente ganha mais 6 meses de garantia ficando com 18 meses.
	* Para verificar os produtos que tem garantia estendida acessamos o bd do hibeats (conexao_hbflex.php) e verificamos o nmero de srie.
		* Todos numeros de series vendidos estao no bd do hibeats, caso nao esteja l no foi vendido ou a AKabuki no deu carga no bd.
		* AKabuki  a agencia que toma conta do site da hbflex, responsavel pelo bd e atualizacao do bd. Contato:
			Allan Rodrigues
			Programador
			AGNCIA KABUKI
			* allan@akabuki.com.br
			* www.akabuki.com.br
			( 55 11 3871-9976
	** Acompanhar os lancamentos destas garantias, liberado no ultimo dia do ano e ainda estamos acompanhando

*/
# socinter = 59
include 'dbconfig.php';
include 'dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';


// !129655
// HD 129655 - Gravar faq para dúvida de produtos
/**
 * Insere as dúvidas do produto pesquisadas.
 *
 * @return boolean Se true a função gravou as Dúvidas 
 * @author Augusto Pascutti <augusto.pascutti@telecontrol.com.br>
 */
 if($login_fabrica == 30){
	if(strlen($hd_chamado)>0) header ("Location:pre_os_cadastro_sac_esmaltec.php?hd_chamado=$hd_chamado");
	else        header ('Location:pre_os_cadastro_sac_esmaltec.php');
	exit;
 }
function gravarFaq() {
	global $con,$hd_chamado,$msg_erro;
	
	if ( empty($hd_chamado) || $hd_chamado <= 0 ) {
		$msg_erro .= "<p>Não foi possível gravar dúvidas do produto, número do chamado não informado.</p>";
		return false;
	}
	
	if ( isset($_POST['faq']) && count($_POST['faq']) > 0 && is_array($_POST['faq']) ) {
		$aFaqs = array();
		foreach ( $_POST['faq'] as $xfaq ) {
			$xfaq = (int) $xfaq;
			$aFaqs[] = "({$hd_chamado},{$xfaq})";
		}
		@pg_exec($con,"DELETE FROM tbl_hd_chamado_faq WHERE hd_chamado = {$hd_chamado}");
		$sql = "INSERT INTO tbl_hd_chamado_faq (hd_chamado,faq) VALUES " . implode(',',$aFaqs);
		$res = @pg_exec($con,$sql);
		if ( is_resource($res) && pg_affected_rows($res) > 0 ) {
			return true;
		}
		$msg_erro .= "<p>Erro ao inserir as dúvidas.</p>";
		return false;
	}
}
// fim HD 129655

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


$title = "ATENDIMENTO SAC - ABERTURA DE PRÉ OS";
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
function acentos1( $texto ){
	 $array1 = array("", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "" , "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "","","" );
	$array2 = array("", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "" ,"", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "","","");
	return str_replace( $array1, $array2, $texto );
}
function acentos3( $texto ){
 $array1 = array("", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "" , "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "","","" );
 $array2 = array("A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","N","N" );
 return str_replace( $array1, $array2, $texto );
}

$indicacao_posto=$_GET['indicacao_posto'];
if(strlen($indicacao_posto)==0) {
	$indicacao_posto=$_POST['indicacao_posto'];
}
if(strlen($indicacao_posto)==0) {
	$indicacao_posto='f';
}

$btn_acao = $_POST['btn_acao'];

if(strlen($btn_acao)>0 AND $login_fabrica != 96){

		$callcenter         = $_POST['callcenter'];
		$hd_chamado         = $callcenter;
		$tab_atual          = $_POST['tab_atual'];
		$status_interacao   = 'Aberto';	
		$transferir         = $_POST['transferir'];
		$chamado_interno    = $_POST['chamado_interno'];
		$envia_email        = $_POST['envia_email'];
		if(strlen($envia_email)==0){
			$xenvia_email = "'f'";
		}else{
			$xenvia_email = "'t'";
		}

		if($login_fabrica==11){//HD 53881 27/11/2008
			$tipo_reclamacao = $_POST['tipo_reclamacao'];
			if($tab_atual=="reclamacao_at" AND strlen($tipo_reclamacao)==0){
				$msg_erro = "Escolha o Tipo da Reclamação";
			}

			$sub_tipo_reclamacao = array("mau_atendimento","posto_nao_contribui","demonstra_desorg","possui_bom_atend","demonstra_org","reclamacao_at_info");
			if(in_array($tipo_reclamacao, $sub_tipo_reclamacao)){
				$tab_atual       = $tipo_reclamacao;
			}

			$reclamado       = $_POST['reclamado_at'];
			if(strlen($reclamado)>0){
				$xreclamado = "'" . $reclamado . "'";
			}else{
				$xreclamado = "null";
			}
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
		$consumidor_nome           = trim($_POST['consumidor_nome']);
		$cliente                   = trim($_POST['cliente']);
		$consumidor_cpf            = trim($_POST['consumidor_cpf']);
		$consumidor_cpf            = str_replace("/","",$consumidor_cpf);
		$consumidor_cpf            = str_replace("-","",$consumidor_cpf);
		$consumidor_cpf            = str_replace(".","",$consumidor_cpf);
		$consumidor_cpf            = str_replace(",","",$consumidor_cpf);
		if (strlen($consumidor_cpf) == 14) {
			$consumidor_cpf = substr($consumidor_cpf,0,2) .".". substr($consumidor_cpf,2,3) .".". substr($consumidor_cpf,5,3) ."/". substr($consumidor_cpf,8,4)."-".substr($consumidor_cpf,12,2);
		}
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
		$consumidor_fone3          = trim($_POST['consumidor_fone3']);
		$consumidor_fone3          = str_replace("'","",$consumidor_fone3);
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
		$origem                    = $_POST['origem'];
		$consumidor_revenda        = $_POST['consumidor_revenda'];
		$cnpj_revenda        = $_POST['cnpj_revenda'];

		if($indicacao_posto=='t' and $login_fabrica <> 24){
			$consumidor_nome='Indicação de Posto';
			$consumidor_fone='00000000000';
			$consumidor_estado='00';
			$consumidor_cidade='Indicação de Posto';
			$consumidor_revenda='Indicação de Posto';
			$origem='Indicação de Posto';
			$consumidor_cpf='00000000000';
			$consumidor_cep='00000000';
			$produto_referencia='Indicação de Posto';
			$hora_ligacao='00:00';
		}elseif($indicacao_posto=='t' and $login_fabrica == 24){
			if(strlen($_POST['produto_referencia']) == 0 or strlen($_POST['produto_nome']) == 0) {
				$msg_erro .= "Por favor, insere a referência e a descrição do Produto ";
			}
		}

		$xconsumidor_nome        = (strlen($consumidor_nome)==0)  ? "null" : "'".$consumidor_nome."'";
		$xconsumidor_cpf         = (strlen($consumidor_cpf)==0)   ? "null" : "'".$consumidor_cpf."'";
		$xconsumidor_rg          = (strlen($consumidor_rg)==0)    ? "null" : "'".$consumidor_rg."'";
		$xconsumidor_email       = (strlen($consumidor_email)==0) ? "null" : "'".$consumidor_email."'";
		$xconsumidor_fone        = (strlen($consumidor_fone)==0)  ? "null" : "'".$consumidor_fone."'";
		$xconsumidor_fone2       = (strlen($consumidor_fone2)==0) ? "null" : "'".$consumidor_fone2."'";
		$xconsumidor_fone3       = (strlen($consumidor_fone3)==0) ? "null" : "'".$consumidor_fone3."'";
		$xconsumidor_cep         = (strlen($consumidor_cep)==0)   ? "null" : "'".$consumidor_cep."'";
		$xconsumidor_endereco    = (strlen($consumidor_endereco)== 0) ? "null" : "'".$consumidor_endereco."'";
		$xconsumidor_numero      = (strlen($consumidor_numero)==0) ? "null" : "'".$consumidor_numero."'";
		$xconsumidor_complemento = (strlen($consumidor_complemento)==0) ? "null" :"'".$consumidor_complemento."'";
		$xconsumidor_bairro      = (strlen($consumidor_bairro)==0) ? "null" :"'".$consumidor_bairro."'";
		$xconsumidor_cidade      = (strlen($consumidor_cidade)==0) ? "null" :"'".$consumidor_cidade."'";
		$xconsumidor_estado      = (strlen($consumidor_estado)==0) ? "null" : "'".$consumidor_estado."'";

		if($login_fabrica== 3 or $login_fabrica == 24 or ($login_fabrica==5 and $indicacao_posto=='f')){ // HD 48900 58796
			if(strlen($consumidor_nome)==0){
				$msg_erro .= "Por favor inserir o nome do consumidor ";
			}
			if(strlen($consumidor_cep)==0){
				$msg_erro .= "Por favor inserir o cep do consumidor ";
			}
			if(strlen($consumidor_bairro)==0){
				$msg_erro .= "Por favor inserir o bairro do consumidor ";
			}
			if(strlen($consumidor_endereco)==0){
				$msg_erro .= "Por favor inserir o endereco do consumidor ";
			}
			if(strlen($consumidor_fone)==0){
				$msg_erro .= "Por favor inserir o telefone do consumidor ";
			}
			if(strlen($consumidor_estado)==0){
				$msg_erro .= "Por favor selecione o estado ";
			}
			if(strlen($consumidor_cidade)==0){
				$msg_erro .= "Por favor inserir a cidade ";
			}
			if ($login_fabrica == 3) {
				if(strlen(trim($_POST['consumidor_revenda'])) ==0) {
					$msg_erro .= "Por favor selecione o tipo (Consumidor ou Revenda) ";
				}
				if(strlen(trim($_POST['origem'])) ==0) {
					$msg_erro .= "Por favor selecione a origem ";
				}
			}

			if ($login_fabrica == 5) { // HD 59786
				if(strlen(trim($_POST['consumidor_cpf'])) ==0) {
					$msg_erro .= "Por favor inserir o CPF do consumidor ";
				}
				if(strlen(trim($_POST['consumidor_cep'])) ==0) {
					$msg_erro .= "Por favor inserir cep do consumidor ";
				}

				if (strlen($_POST["produto_referencia"]) == 0) {
					$msg_erro .= "Por favor, insira a referência do produto ";
				}
			}
		}elseif($indicacao_posto=='f') {
			if(strlen($consumidor_nome)>0 and strlen($consumidor_estado)==0){
				$msg_erro .= "Por favor selecione o estado";
			}
			if(strlen($consumidor_nome)>0 and strlen($consumidor_cidade)==0){
				$msg_erro .= "Por favor inserir a cidade";
			}
		}

		$abre_os                   = trim($_POST['abre_os']);
		$imprimir_os               = trim($_POST['imprimir_os']);
		$resposta                  = trim($_POST['resposta']);
		$posto_tab                 = trim(strtoupper($_POST['posto_tab']));
		$codigo_posto_tab          = trim(strtoupper($_POST['codigo_posto_tab']));
		$posto_nome_tab            = trim(strtoupper($_POST['posto_nome_tab']));
		$posto_endereco_tab        = trim(strtoupper($_POST['posto_endereco_tab']));
		$posto_cidade_tab          = trim(strtoupper($_POST['posto_cidade_tab']));
		$posto_estado_tab          = trim(strtoupper($_POST['posto_estado_tab']));
		$posto_km_tab              = trim($_POST['posto_km_tab']);
		$revenda_nome              = trim($_POST['revenda_nome']);
		$revenda_endereco          = trim($_POST['revenda_endereco']);
		$revenda_nro               = trim($_POST['revenda_nro']);
		$revenda_cmpto             = trim($_POST['revenda_cmpto']);
		$revenda_bairro            = trim($_POST['revenda_bairro']);
		$revenda_city              = trim($_POST['revenda_city']);
		$revenda_uf                = trim($_POST['revenda_uf']);
		$revenda_fone              = trim($_POST['revenda_fone']);

		$hd_extra_defeito          = trim($_POST['hd_extra_defeito']);
		$faq_situacao              = trim($_POST['faq_situacao']);

		$reclama_posto             = trim($_POST['tipo_reclamacao']);

		$xresposta = (strlen($resposta)==0) ? "null" : "'".$resposta."'";
		$xreceber_informacoes = (strlen($receber_informacoes)>0) ? "'$receber_informacoes'" : "'f'";

		if($tab_atual == "extensao"){
			$produto_referencia = $_POST['produto_referencia_es'];
			$produto_nome       = $_POST['produto_nome_es'];
			$reclamado          = trim($_POST['reclamado_es']);
			if(strlen($reclamado)==0) {
				$xreclamado = "null";
			}else{
				$xreclamado = "'".$reclamado."'";
			}


			$xserie = $_POST['serie'];
			if(strlen($_POST["serie_es"])>0) $xserie = $_POST['serie_es'];

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

		if($tab_atual == "reclamacao_produto"){
			$produto_referencia = $_POST['produto_referencia'];
			$produto_nome       = $_POST['produto_nome'];
			$voltagem           = $_POST['voltagem'];
			$reclamado          = trim($_POST['reclamado_produto']);
			$xserie             = $_POST['serie'];
			if(strlen($reclamado)==0){
				$xreclamado = "null";
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

		$posto_nome           = $_POST['posto_nome'];
		$codigo_posto         = $_POST['codigo_posto'];
		$procon_posto_nome    = $_POST['procon_posto_nome'];
		$procon_codigo_posto  = $_POST['procon_codigo_posto'];
		$reclamacao_procon    = $_POST['reclamacao_procon'];

		if ($login_fabrica == 2 AND $reclama_posto <> 'reclamacao_at'){
			$codigo_posto = "";
		}

		/*
		if ($login_fabrica == 2 AND $reclama_posto == 'reclamacao_at'){
			if(strlen($codigo_posto) == 0){
				$msg_erro .= "Ao selecionar Reclamação da Assitência Técnica  <br/>
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
		
		if (strlen($mr_codigo_posto) > 0) {
					$xcodigo_posto=$mr_codigo_posto;
		}
		if($login_fabrica ==11) {
			if(strlen($procon_codigo_posto)==0){ // HD 55995
					$xcodigo_posto = "null";
			}else{
				$sql = "SELECT posto
						from tbl_posto_fabrica
						where codigo_posto='$procon_codigo_posto'
						and fabrica = $login_fabrica";
				$res = pg_exec($con,$sql);
				if(pg_numrows($res)>0){
					$xcodigo_posto = pg_result($res,0,0);
				}else{
					$xcodigo_posto = "null";
				}
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
			if(strlen($reclamacao_procon) > 0) {
				$sub_reclamacao_procon = array("pr_reclamacao_at","pr_info_at","pr_mau_atend","pr_posto_n_contrib","pr_demonstra_desorg","pr_bom_atend","pr_demonstra_org");
				if(in_array($reclamacao_procon, $sub_reclamacao_procon)){
					$tab_atual       = $reclamacao_procon;
				}
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
				//$msg_erro = "Insira a reclamao";
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
			//echo nl2br($sql)."<BR>";
			if(pg_numrows($res)>0){
				$xproduto = pg_result($res,0,0);
			}else{
				$msg_erro .= "Produto não encontrado<br>";
			}
		}
		else {
			$xproduto = 'null';
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
			if(pg_numrows($res)>0){
				$xproduto_troca = pg_result($res,0,0);
			}else{
				$xproduto_troca = "null";
			}
		}else {
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
					$admin_email = "suporte@telecontrol.com.br";
					$remetente    = $admin_email;
					$destinatario = $email_cadastros ;
					$assunto      = "Nova dúvida cadastrada";
					$mensagem     = "Prezado, <br> Foi cadastrada uma nova dúvida no sistema para o produto $produto_referencia:<br>  - $faq_situacao <br><br>Por favor, entre na aba <b>Cadastro - Perguntas Frequentes</b> para cadastrar causa e solução da mesma. <br>Att <br>Equipe Telecontrol";
					$headers="Return-Path: <$admin_email>\nFrom:".$remetente."\nContent-type: text/html\n";

					mail($destinatario,$assunto,$mensagem,$headers);
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
			}elseif($indicacao_posto=='f') {
				$msg_erro .= "Informe a cidade do consumidor<br>";
			}
		}
		
		$xnota_fiscal = "'".$_POST["nota_fiscal"]."'";

		$data_nf = $_POST["data_nf"] ;

		if ($tab_atual == 'reclamacoes') {
			$tab_atual = $tipo_reclamado;
		}
		
		if (strlen($mr_codigo_posto) > 0) {
			$xcodigo_posto=$mr_codigo_posto;
		}
		else {
			$msg_erro .= "Informe o Posto Autorizado<br>";
		}
		
		if (strlen($posto_km_tab)==0) {
			$msg_erro .= "É necessario digitar a Qtde de Km, clique em Mapa da Rede<br>";
		}


		$sql_admin = "SELECT cliente_admin from tbl_admin where admin = $login_admin";
		$res = pg_exec($con,$sql_admin);

		$cliente_admin = pg_result($res,0,cliente_admin);
		
		if(strlen($msg_erro)==0 and strlen($callcenter)==0) {
			$titulo = 'Atendimento interativo';
			if($indicacao_posto=='t') $titulo = 'Indicação de Posto';
			$sql = "INSERT INTO tbl_hd_chamado (
						admin                 ,
						cliente_admin         ,
						data                  ,
						status                ,
						atendente             ,
						fabrica_responsavel   ,
						titulo                ,
						categoria             ,
						fabrica
					)values(
						$login_admin            ,
						$cliente_admin          ,
						current_timestamp       ,
						$xstatus_interacao      ,
						$login_admin            ,
						$login_fabrica          ,
						'$titulo'               ,
						'$tab_atual'            ,
						$login_fabrica
				)";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			//echo nl2br($sql);
			$res    = pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado')");
			$hd_chamado = pg_result ($res,0,0);
		}

			if(strlen($msg_erro)==0 and strlen($callcenter)==0) {

				if (isset($rat_codigo_posto)){
					$xcodigo_posto = $rat_codigo_posto;
				}

				if(strlen($abre_os)==0){ $abre_os = 'f';}
				$xabre_os = "'".$abre_os."'";

				if(strlen($data_nf)==0) $xdata_nf = "NULL";
				else                    $xdata_nf = "'".converte_data($data_nf)."'";

				if($login_fabrica==3){
					if($status_interacao=='Resolvido' OR $status_interacao=='Cancelado') {
						$tipo_registro ="Contato";
					}elseif($status_interacao=='Aberto'){
						$tipo_registro ="Processo";
					}
				}else{
					$tipo_registro="";
				}
				

			if (strlen($posto_km_tab)==0) {
				$posto_km_tab = 'null';
			}
			else {
				$posto_km_tab = str_replace(',','.',$posto_km_tab);
			}

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
								qtde_km              ,
								tipo_registro";

				if($login_fabrica==59){
					$sql .=" ,celular";
				}

					$sql .="	)values(
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
							$posto_km_tab                  ,
							'$tipo_registro'
							";

				if($login_fabrica==59){
					$sql .="   ,upper($xconsumidor_fone3)";
				}

				$sql .=");";

				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

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

				//IGOR - HD: 10441 QUANDO FOR INDICADO UM POSTO AUTORIZADO, DEVE-SE INSERIR UMA INTERAO NO CHAMADO
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
				//94971
				$herdar_x = $_GET['herdar'];
				$hd_chamado_herdar_x = $_GET['Id'];
				if($login_fabrica==59 AND strlen($herdar_x)>0 AND strlen($hd_chamado_herdar_x)>0 AND strlen($callcenter)<=0)	{
					$interacao = $_POST['reclamado_produto_x'];
					$reclamado_x = "Histórico do HD $hd_chamado_herdar_x: $interacao ";
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
							'$reclamado_x'       ,
							$login_admin      ,
							'f',
							$xstatus_interacao
							)";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);

					$sql = "SELECT hd_chamado, data,comentario,admin,interno,status_item FROM tbl_hd_chamado_item WHERE hd_chamado = $hd_chamado_herdar_x";

					$res = @pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
					$linhas = pg_numrows($res);
					if(strlen($linhas)>0){
						for($y = 0;$y < $linhas;$y++){
							$data_hd_hist = pg_result($res,$y,data);
							$comentario_hd_hist = pg_result($res,$y,comentario);
							$admin_hd_hist = pg_result($res,$y,admin);
							$interno_hd_hist = pg_result($res,$y,interno);
							$status_item_hd_hist = pg_result($res,$y,status_item);
							$hd_chamado_hd_hist = pg_result($res,$y,hd_chamado);

							$sql = "INSERT INTO tbl_hd_chamado_item(
									hd_chamado   ,
									data         ,
									comentario   ,
									admin        ,
									interno      ,
									status_item
									)values(
									'$hd_chamado'       ,
									'$data_hd_hist' ,
									'Histórico do HD $hd_chamado_herdar_x: $comentario_hd_hist'       ,
									'$admin_hd_hist'      ,
									'$interno_hd_hist',
									'$status_item_hd_hist'
									)";

							$res2 = pg_exec($con,$sql);
							$msg_erro .= pg_errormessage($con);

							$finaliza = $y + 1;
						}
						if(strlen($finaliza)<=0){
							$finaliza = 0;
						}
					}
				}

					if ($login_fabrica == 52 or $login_fabrica == 30 or $login_fabrica == 85) {
				$qtde_produto = $_POST['qtde_produto'];

				if ($qtde_produto>0	) {
					for ($w=1;$w<=$qtde_produto;$w++) {
						$produto_referencia = $_POST['produto_referencia_'.$w]; 
						$serie              = $_POST['serie_'.$w]; echo "<br>";
						$defeito_reclamado  = $_POST['defeito_reclamado_'.$w]; echo "<br>";

						if (strlen($produto_referencia)>0) {
							$sql_ref = "SELECT tbl_produto.produto
							FROM  tbl_produto
							join  tbl_linha on tbl_produto.linha = tbl_linha.linha
							WHERE tbl_produto.referencia = '$produto_referencia'
							and tbl_linha.fabrica = $login_fabrica
							limit 1";
							$res_ref = pg_exec($con,$sql_ref);
							$msg_erro .= pg_errormessage($con);
							//echo nl2br($sql)."<BR>";
							if(pg_numrows($res_ref)>0){
							$xproduto = pg_result($res_ref,0,0);
							}else{
							$xproduto = "null";
							}
							}else{
									$xproduto = "null";
							}
						
							if (($login_fabrica == 52 or $login_fabrica == 30 or $login_fabrica == 85) and strlen($defeito_reclamado)==0) {
								$msg_erro = "Favor escolha um defeito reclamado para o produto";
							}

							if (strlen($msg_erro)==0) {
								$sql = "INSERT INTO tbl_hd_chamado_item(
								hd_chamado   ,
								data         ,
								comentario   ,
								admin        ,
								interno      ,
								produto      ,
								serie        ,";
								if ($login_fabrica == 52) {
									$sql .= "defeito_reclamado,";
								} else {
									$sql .= "defeito_reclamado_descricao,";
								}

								$sql .= "status_item
								)values(
								$hd_chamado                       ,
								current_timestamp                 ,
								'Insercao de Produto para pré-os' ,
								$login_admin                      ,
								't'                               ,
								'$xproduto'                       ,
								'$serie'                          ,
								'$defeito_reclamado'                ,
								'Aberto'
								)";
								$res = pg_exec($con,$sql);
							}
					}
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
				$sql = "SELECT garantia from tbl_produto where produto = $xproduto";
				$res = pg_exec($con,$sql);
				$garantia = pg_result($res,0,0);

				$sql = "SELECT to_char(('$xxes_data_compra'::date + interval '$garantia month') + interval '6 month','YYYY-MM-DD') ";
				$res = pg_exec($con,$sql);
				$es_garantia = "'".pg_result($res,0,0)."'";

				if(strlen($es_id_numeroserie)>0){
					include "conexao_hbtech.php";

					/*INSERINDO NO SITE DO HIBEATS, VERIFICAMOS ANTES SE EXISTE ESSE NUMERO DE SRIE E INSERIMOS OS DADOS DO CLIENTE*/
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
					$res = mysql_query($sql) or die("Erro no Sql1: ".mysql_error());

					if (strlen(mysql_error())>0){
						$mensagem   = $enviar_erro."<br><br><br> $sql <br><br>".mysql_error();
						$cabecalho .= "MIME-Version: 1.0\n";
						$cabecalho .= "Content-type: text/html; charset=iso-8859-1\n";
						$cabecalho .= "From: Telecontrol <suporte@telecontrol.com.br>\n";
						$cabecalho .= 'To: Fabio<fabio@telecontrol.com.br>'."\n";
						$cabecalho .= "Subject: LOG HBTECH GARANTIA\n";
						$cabecalho .= "Return-Path: Suporte <suporte@telecontrol.com.br>\n";
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

			// HD 129655 - Gravar dúvidas selecionadas [augusto]
			gravarFaq();
// HD 120306 - envia e-mail para o posto informando pre-OS cadastrada
				if(($login_fabrica==14 or $login_fabrica==43 or $login_fabrica==66) and $abre_os=='t'){

					$sql = "Select email from tbl_admin where admin = $login_admin and fabrica = $login_fabrica";
						$res = pg_exec($con,$sql);
						$msg_erro .= pg_errormessage($con);
					if(pg_numrows($res)>0){
						$admin_email = pg_result($res,0,email);
					}else{
						$admin_email = "suporte@telecontrol.com.br";
					}
					$sql = "SELECT contato_email from tbl_posto_fabrica where posto = $xcodigo_posto and fabrica=$login_fabrica";
					$res = pg_exec($con,$sql);
					if(@pg_numrows($res) == 0){
					$email_posto = pg_result($res,0,contato_email);
					}else{
					$sql = "SELECT email from tbl_posto where posto = $xcodigo_posto";
					$res = pg_exec($con,$sql);
					$email_posto = pg_result($res,0,email);
					}
					
					$subject  = "Nova Pré-OS cadastrada pelo SAC da fabrica";

					$message="<b>Nova Pré-OS cadastrada</b><br><br>";
					$message .= "<b> Favor dar continuidade no atendimento dessa Pré-OS.<br><br>";
					$message .= "<b> Atenciosamente<br><br>";
					$message .= "<b> SAC <br>";

					$headers = "From: Call-center <suporte@telecontrol.com.br>\n";
					$headers .= "MIME-Version: 1.0\n";
					$headers .= "Content-type: text/html; charset=iso-8859-1\n";	

					//	mail("$admin_email",$subject,$message,$headers);
					mail("$email_posto",$subject,$message,$headers);

					$peca = "null";		
					$produto = "null";
					$aux_familia = "null";
					$aux_linha = "null";
					$aux_extensao = "null";
					$aux_descricao = $subject;
					$aux_mensagem  = $message;
					$aux_tipo      = "Comunicado";
					$posto	       = $xcodigo_posto;
					$aux_obrigatorio_os_produto = "'f'";
					$aux_obrigatorio_site = "'t'";
					$aux_tipo_posto = "null";
					$aux_ativo = "'t'";
					$aux_estado = "null";
					$aux_pais = "'BR'";
					$remetente_email = "$admin_email";
					$pedido_faturado="'f'";
					$pedido_em_garantia="'f'";
					$digita_os="'f'";
					$reembolso_peca_estoque="'f'";


					$sql = "INSERT INTO tbl_comunicado (
						peca                   ,
						produto                ,
						familia                ,
						linha                  ,
						extensao               ,
						descricao              ,
						mensagem               ,
						tipo                   ,
						fabrica                ,
						obrigatorio_os_produto ,
						obrigatorio_site       ,
						posto                  ,
						tipo_posto             ,
						ativo                  ,
						estado                 ,
						pais                   ,
						remetente_email        ,
						pedido_faturado        ,
						pedido_em_garantia     ,
						digita_os              ,
						reembolso_peca_estoque 
						) VALUES (
						$peca                       ,
						$produto                    ,
						$aux_familia                ,
						$aux_linha                  ,
						$aux_extensao               ,
						'$aux_descricao'            ,
						'$aux_mensagem'             ,
						'$aux_tipo'                 ,
						$login_fabrica              ,
						$aux_obrigatorio_os_produto ,
						$aux_obrigatorio_site       ,
						$posto                      ,
						$aux_tipo_posto             ,
						$aux_ativo                  ,
						$aux_estado                 ,
						$aux_pais                   ,
						'$remetente_email'          ,
						$pedido_faturado            ,
						$pedido_em_garantia         ,
						$digita_os                  ,
						$reembolso_peca_estoque     
					);";
					$res = @pg_exec ($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			if (strlen($msg_erro) == 0){
				$res = pg_exec($con,"ROLLBACK TRANSACTION");
				//$res = pg_exec($con,"COMMIT TRANSACTION");
				echo "<script language='javascript'>
					alert('Pré-os Cadastrada com Sucesso');
					window.location = '$PHP_SELF?callcenter=$hd_chamado';
				</script>";
			}else{
				$res = pg_exec($con,"ROLLBACK TRANSACTION");
			}
}/*INSERINDO*/

/*atualizando*/
if(strlen($callcenter)>0){
	
	$sql = "SELECT atendente,login
			from tbl_hd_chamado
			JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente
			where fabrica_responsavel= $login_fabrica
			and hd_chamado = $callcenter";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		$ultimo_atendente       = pg_result($res,0,'atendente');
		$ultimo_atendente_login = pg_result($res,0,'login');
	}
	// ! Gravar alterações de dados
	// HD 122446 (augusto) - Criar interação quando o endereço do cliente for modificado (Lenoxx)
	// HD 124579 (augusto) - Implementar isso em outros campos que podem ser modificados para todas as fábricas
	$msg_interacao = '';
	if ( ( strlen($msg_erro) <= 0 && $login_fabrica == 11 && $hd_chamado > 0 && $xstatus_interacao == "'Aberto'" ) || 
		 ( strlen($msg_erro) <= 0 && $login_fabrica != 11 && $hd_chamado > 0 )                                       ) {
		$array_campos_consumidor_verificar 					= array('consumidor_nome','consumidor_cpf','consumidor_rg','consumidor_email','consumidor_fone',
																	'consumidor_fone2','consumidor_fone3','consumidor_cep','consumidor_endereco','consumidor_numero',
												   					'consumidor_complemento','consumidor_bairro','consumidor_cidade','consumidor_estado',
												   					'produto_referencia','produto_nome','voltagem','serie','nota_fiscal','data_nf');
		$array_consumidor_label            					= array_flip($array_campos_consumidor_verificar);
		$array_consumidor_label['consumidor_nome']	 		= 'Nome';
		$array_consumidor_label['consumidor_cpf']	 		= 'CPF';
		$array_consumidor_label['consumidor_rg']	 		= 'RG';
		$array_consumidor_label['consumidor_email'] 		= 'E-mail';
		$array_consumidor_label['consumidor_fone'] 			= 'Telefone';
		$array_consumidor_label['consumidor_fone2']			= 'Telefone Comercial';
		$array_consumidor_label['consumidor_fone3'] 		= 'Telefone Celular';
		$array_consumidor_label['consumidor_cep'] 			= 'CEP';
		$array_consumidor_label['consumidor_endereco'] 		= 'Endereço';
		$array_consumidor_label['consumidor_numero'] 		= 'Número';
		$array_consumidor_label['consumidor_complemento'] 	= 'Complem.';
		$array_consumidor_label['consumidor_bairro'] 		= 'Bairro';
		$array_consumidor_label['consumidor_cidade']		= 'Cidade';
		$array_consumidor_label['consumidor_estado'] 		= 'Estado';
		$array_consumidor_label['produto_referencia'] 		= 'Referência (do Produto)';
		$array_consumidor_label['produto_nome'] 			= 'Descrição (do Produto)';
		$array_consumidor_label['voltagem'] 				= 'Voltagem';
		$array_consumidor_label['serie'] 					= 'Série';
		$array_consumidor_label['nota_fiscal'] 				= 'NF Compra';
		$array_consumidor_label['data_nf'] 					= 'Data NF';
		$interacao_campos_consumidor_msgs  					= array();
		foreach ($array_campos_consumidor_verificar as $campo_consumidor) {
			$valor_anterior = $campo_consumidor.'_anterior';
			if ( ! isset($_POST[$campo_consumidor]) ) { continue; }
			if ( $_POST[$valor_anterior] != $$campo_consumidor ) {
				$msg_valor_anterior = ( empty($_POST[$valor_anterior]) ) ? 'Em branco' : $_POST[$valor_anterior] ;
				$msg_alteracao      = "<li>Campo <strong>{$array_consumidor_label[$campo_consumidor]}</strong> alterado de '<em>{$msg_valor_anterior}</em>' para '<em>{$$campo_consumidor}</em>'</li>";
				$interacao_campos_consumidor_msgs[] = $msg_alteracao;
			}
		}
		if ( count($interacao_campos_consumidor_msgs) > 0 ) {
			$msg_interacao  = "<p>As seguintes informações do chamado foram alteradas nesta interação:</p><p>&nbsp;</p>";
			$msg_interacao .= "<ul>".implode('',$interacao_campos_consumidor_msgs)."</ul>";
			//$msg_interacao  = pg_escape_string($msg_interacao);
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
								'$msg_interacao'       ,
								$login_admin      ,
								't',
								$xstatus_interacao
								)";
/*
			// A inserçnao agora é feita na SQL de inserção de resposta inserida pelo usuário, e não numa resposta nova !
			$res = pg_exec($con,$sql);
			if ( ! is_resource($res) ) {
				$msg_erro .= "<p> Erro ao inserir interação informando modificação das informações do chamado: ".pg_errormessage($con)."</p>";
			}
*/
		}
		unset($array_campos_consumidor_verificar,$interacao_campos_consumidor_msgs,$msg_alteracao,$valor_anterior,$campo_consumidor,$sql,$res);
	}
	// fim HD 122446
	
	# HD 45756
	if($login_fabrica == 3) {
		if($ultimo_atendente <> $login_admin) {
			$msg_erro = "Sem permissão de alteração. Admin responsável: $ultimo_atendente_login";
		}
	}
	if(strlen($msg_erro)==0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		$_xresposta = pg_escape_string("{$resposta}<p>&nbsp;</p> {$msg_interacao}");
		//if ( ! empty($msg_interacao) ) {
			
			//$xresposta .= "<p>&nbsp;</p> {$msg_interacao}";
		//}
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
						'$_xresposta'        ,
						$login_admin      ,
						$xchamado_interno  ,
						$xstatus_interacao ,
						$xenvia_email
						)";
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
		$res = pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	if(strlen($msg_erro)==0 and $xenvia_email == "'t'"){//se  para enviar email para consumidor
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
				mail($destinatario,$assunto,$mensagem,$headers);
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
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if(strlen($email_ultimo_atendente) >0 AND strlen($email_atendente) >0){

				$assunto       = "O atendimento $callcenter foi transferido para você";

				if($login_fabrica == 24) {
					$sql = " SELECT  tbl_hd_chamado_extra.nome       ,
									 endereco   ,
									 numero     ,
									 complemento,
									 bairro     ,
									 cep        ,
									 fone       ,
									 email      ,
									 cpf        ,
									 rg         ,
									 categoria  ,
									 reclamado  ,
									 tbl_cidade.nome as cidade,
									 tbl_cidade.estado         ,
									 tbl_produto.referencia    ,
									 tbl_produto.descricao
							FROM tbl_hd_chamado
							JOIN tbl_hd_chamado_extra USING(hd_chamado)
							JOIN tbl_cidade           USING(cidade)
							LEFT JOIN tbl_produto     USING(produto)
							WHERE tbl_hd_chamado.hd_chamado = $callcenter";
					$res = pg_exec($con,$sql);
					if(pg_numrows($res) > 0){
						$nome        = pg_result($res,0,'nome');
						$endereco    = pg_result($res,0,'endereco');
						$numero      = pg_result($res,0,'numero');
						$bairro      = pg_result($res,0,'bairro');
						$cep         = pg_result($res,0,'cep');
						$fone        = pg_result($res,0,'fone');
						$email       = pg_result($res,0,'email');
						$categoria   = pg_result($res,0,'categoria');
						$cidade      = pg_result($res,0,'cidade');
						$estado      = pg_result($res,0,'estado');
						$reclamado   = pg_result($res,0,'reclamado');
						$referencia  = @pg_result($res,0,'referencia');
						$descricao   = @pg_result($res,0,'descricao');
						if($categoria == 'reclamacao_produto') $categoria = "Reclamação do Produto";
						if($categoria =="duvida_produto") $categoria= "Dúvida do Produto";
						if($categoria =="reclamacao_at") $categoria= "Reclamação da Assistência Técnica";
						if($categoria =="sugestao") $categoria= "Sugestão";
						if($categoria =="reclamacao_empresa") $categoria= "Reclamação da Empresa";
						if($categoria =="procon") $categoria= "Procon";
						if($categoria =="onde_comprar") $categoria= "Onde comprar";
					}
				}

				$corpo = "<P align=left><STRONG>Nota: Este e-mail gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
				<P align=left>$nome_atendente,</P>
				<P align=justify>
				O atendimento $callcenter foi transferido de <b>$nome_ultimo_atendente</b> para você
				</P>";
				if($login_fabrica == 24) {
					$corpo .= "<p align=justify>Informação do atendimento:</p>";
					$corpo .= "<p align=justify>Nome do consumidor: $nome&nbsp;&nbsp;Telefone: $fone</p>";
					$corpo .= "<p align=justify>E-mail: $email</p>";
					$corpo .= "<p align=justify>Endereço:$endereco&nbsp;$numero - $bairro - $cidade - $estado CEP: $cep</p>";
					$corpo .= "<p align=justify>Tipo de atendimento: $categoria</p>";
					if(strlen($referencia) > 0) {
						$corpo .="<p align=justify>Produto: $referencia - $descricao</p>";
					}
					$corpo .= "<p align=justify>Descrição: $reclamado</p>";
				}
				// HD 112313 (augusto) - Problema no cabeçalho do email, removidas partes com problema;
				$body_top  = "Content-type: text/html; charset=iso-8859-1 \n";
				$body_top .= "From: {$email_ultimo_atendente} \n";

				if ( @mail($email_atendente, stripslashes($assunto), $corpo, $body_top ) ){
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
				}else{
					$cidade = 'null';
				}
		}
		 // HD 122446 (augusto) - Lenoxx (11) - Salvar informações do consumidor se elas forem modificadas
		 // Para Lenox só é possível modificar as informações de cliente se o chamado ainda estiver aberto 
		 // HD 124579 (augusto) - Todas as fábricas: acrescentar update das informações do Produto
		if( strlen($hd_chamado)>0 && ( $login_fabrica != 11 || ( $login_fabrica == 11 && $xstatus_interacao == "'Aberto'" ) ) ){//*ja tem cadastro no telecontrol/
			$_serie				= (empty($_POST['serie'])) ? 'null' : "'".pg_escape_string($_POST['serie'])."'" ;
			$_nota_fiscal		= (empty($_POST['nota_fiscal'])) ? 'null' : pg_escape_string($_POST['nota_fiscal']) ;
			$_data_nf			= (empty($_POST['data_nf'])) ? 'null' : "'".pg_escape_string(converte_data($_POST['data_nf']))."'" ;
			$sql = "SELECT  tbl_hd_chamado.hd_chamado,
							tbl_hd_chamado.status
					FROM tbl_hd_chamado
					JOIN tbl_hd_chamado_extra USING(hd_chamado)
					where tbl_hd_chamado.hd_chamado = $hd_chamado";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				$xhd_chamado = pg_result($res,0,'hd_chamado');
				$xstatus     = pg_result($res,0,'status');
				// HD 124579 (augusto) - Permitir alterar produto para todas as fábricas
				//if(($login_fabrica == 59 OR $login_fabrica == 30) AND $xstatus == 'Aberto'){
				$sql_produto = " , produto = $xproduto "; // HD 76545 - HD 108048
				//}

				$sql = "UPDATE tbl_hd_chamado_extra set
							posto       = $xcodigo_posto,
							nome        = upper($xconsumidor_nome)       ,
							endereco    = upper($xconsumidor_endereco)   ,
							numero      = upper($xconsumidor_numero)     ,
							complemento = upper($xconsumidor_complemento),
							bairro      = upper($xconsumidor_bairro)     ,
							cep         = upper($xconsumidor_cep)        ,
							fone        = upper($xconsumidor_fone)       ,
							fone2        = upper($xconsumidor_fone2)     ,
							celular      = upper($xconsumidor_fone3)     ,
							email       = upper($xconsumidor_email)      ,
							cpf         = upper($xconsumidor_cpf)        ,
							rg          = upper($xconsumidor_rg)         ,
							nota_fiscal = {$_nota_fiscal},
							data_nf     = {$_data_nf},
							serie       = {$_serie},
							cidade      = $cidade                        ,
							defeito_reclamado_descricao = '$hd_extra_defeito'
							$sql_produto
						WHERE tbl_hd_chamado_extra.hd_chamado = $xhd_chamado";
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

	// !129655
	// HD 129655 - Gravar dúvidas selecionadas [augusto]
	gravarFaq();

	if (strlen($msg_erro) == 0){
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
		#$res = pg_exec($con,"COMMIT TRANSACTION");
		echo "<script language='javascript'>
				alert('Pré-os Alterada com Sucesso');
				window.location = '$PHP_SELF?callcenter=$hd_chamado';
			</script>";
	}else{
		//echo $msg_erro;
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
	}
}
}elseif(strlen($btn_acao) > 0){

	$nome			= $_POST['nome'];
	$cnpj				= $_POST['cnpj'];
	$ie				= $_POST['ie'];
	$email			= $_POST['email'];
	$telefone			= $_POST['telefone'];
	$cep				= $_POST['cep'];
	$endereco			= $_POST['endereco'];
	$numero			= $_POST['numero'];
	$complemento		= $_POST['complemento'];
	$bairro			= $_POST['bairro'];
	$cidade			= $_POST['cidade'];
	$estado			= $_POST['estado'];

	$numero_nf			= $_POST['numero_nf'];
	$data_nf			= $_POST['data_nf'];
	$tipo_atedimento		= $_POST['tipo_atedimento'];
	$codigo_postagem	= $_POST['codigo_postagem'];
	$consumidor_final	 	= $_POST['consumidor_final'];

	$qtde_produto		= $_POST['qtde_produto'];

	$posto_codigo		= $_POST['posto_codigo'];
	$posto_nome		= $_POST['posto_nome'];
	$posto_descricao		= $_POST['posto_descricao'];

	$anexo1 = $_FILES['anexo_1']['name'];
	$anexo2 = $_FILES['anexo_2']['name'];

	if($tipo_atedimento != 93)
		$anexo3 = $_FILES['anexo_3']['name'];
	else
		$anexo3 = "";

	$pre_os	= "t";

	if(strlen($data_nf) == 0 AND strlen($msg_erro) == 0){
        $msg_erro = "Data NF Inválida";
    }else{
		list($di, $mi, $yi) = explode("/", $data_nf);
        if(!@checkdate($mi,$di,$yi)){
            $msg_erro = "Data Inválida";
		}else{
			if(strlen($msg_erro)==0)
				$aux_data_nf = "$yi-$mi-$di";

			if($aux_data_nf > date('Y-m-d'))
				 $msg_erro = "Data Inválida";	
		}
	}

	// Valida DADOS para Bosch Security - 96
	if(empty($numero_nf) AND strlen($msg_erro) == 0){
        $msg_erro = "Número da NF Inválida";
    }

	if(strlen($anexo1) > 0 AND strlen($msg_erro) == 0){
		$extensao = array_reverse(explode('.',$anexo1));
		$extensao = strtolower($extensao[0]);
		if($extensao != 'pdf' AND $extensao != 'jpg' AND $extensao != 'jpeg')
			$msg_erro = "Nota Fiscal de Envio, somente exntensões 'PDF e JPEG'";
	}

	if(strlen($anexo2) > 0 AND strlen($msg_erro) == 0){
		$extensao = array_reverse(explode('.',$anexo2));
		$extensao = strtolower($extensao[0]);
		if($extensao != 'xml')
			$msg_erro = "Nota Fiscal de Envio, somente XML";
	}

	if($tipo_atedimento == 0 AND strlen($msg_erro) == 0){
        $msg_erro = "Tipo de Atendimento Inválido";
    }

	if(empty($consumidor_final) AND strlen($msg_erro) == 0){
        $msg_erro = "Consumidor Final Inválido";
    }

	for($i = 1;$i <= $qtde_produto;$i++){
		$serie							= $_POST['serie_'.$i];
		$produto						= $_POST['produto_'.$i];
		$descricao						= $_POST['descricao_'.$i];
		$quantidade						= $_POST['quantidade_'.$i];
		$defeito_reclamado_descricao	= $_POST['defeito_reclamado_descricao_'.$i];
		$defeito_reclamado				= $_POST['defeito_reclamado_'.$i];

		//Só vai validar se os paramentros principais estiver com valor...
		if((strlen($serie) != 0 OR strlen($produto) != 0 OR strlen($defeito_reclamado_descricao) != 0) AND strlen($msg_erro) == 0){
			if(strlen($serie) == 0){
				$msg_erro = "Número da Série Inválido! - Linha {$i}";
			}
			if(strlen($defeito_reclamado_descricao) == 0){
				$msg_erro = "Informe uma Defeito - Linha {$i}";
			}
			
			if(strlen($produto) == 0){
				$msg_erro = "Referência do Produto Inválido! - Linha {$i}";
			}else{
				$sql = "
						SELECT
							produto
						FROM
							tbl_produto
						JOIN
							tbl_linha ON tbl_produto.linha = tbl_linha.linha
						WHERE
							tbl_produto.ativo is true
							AND tbl_linha.fabrica = $login_fabrica
							AND tbl_produto.referencia_fabrica = '$produto'
						LIMIT 1
					";
				//echo "<br>".nl2br($sql);
				$res = pg_query($con,$sql);
				if(pg_num_rows($res)== 0)
					$msg_erro = "Referência do Produto Inválido ou Inexistente! - Linha {$i}";
			}
		}
	}
	// Fim do Valida dados para Bosch Security - 96

	$sql = "SELECT posto
				from tbl_posto_fabrica
				where codigo_posto='$posto_codigo'
				and fabrica = $login_fabrica";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		$xcodigo_posto = pg_result($res,0,0);
	}

	/* INICIO DA INSERT DE DADOS */
	if(strlen($msg_erro)==0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if(strlen($callcenter)==0) {
			$titulo = 'Atendimento interativo';
			$tab_atual = "reclamacao_produto";
			$status_interacao   = 'Aberto';

			$sql = "INSERT INTO tbl_hd_chamado (
							cliente_admin         ,
							data                  ,
							status                ,
							atendente             ,
							fabrica_responsavel   ,
							categoria             ,
							titulo                ,
							fabrica
						)values(
							$login_cliente_admin    ,
							current_timestamp       ,
							'$status_interacao'		,
							$login_admin            ,
							$login_fabrica          ,
							'$tab_atual'            ,
							'$titulo'               ,
							$login_fabrica
					)";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
				$res    = pg_query($con,"SELECT CURRVAL ('seq_hd_chamado')");
				$hd_chamado = pg_result ($res,0,0);
				//echo "<br><br>".nl2br($sql);
		}

		if(strlen($msg_erro)== 0 AND strlen($hd_chamado) != 0){
			
			if(strlen($nome)>0 and strlen($estado)>0 and strlen($cidade)>0){
			$sql = "SELECT tbl_cidade.cidade
						FROM tbl_cidade
						WHERE tbl_cidade.nome = '$cidade'
						AND tbl_cidade.estado = '$estado'
						limit 1";
				$res = pg_exec($con,$sql);
				if(pg_numrows($res)>0){
					$cidade = pg_result($res,0,0);
				}else{
					$sql = "INSERT INTO tbl_cidade(nome, estado)values(upper('$cidade'),upper('$estado'))";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
					$res    = pg_query ($con,"SELECT CURRVAL ('seq_cidade')");
					$cidade = pg_result ($res,0,0);
				}
			}

			$sql = "INSERT INTO tbl_hd_chamado_extra(
						hd_chamado			 ,
						posto                ,
						data_nf              ,
						nota_fiscal          ,
						abre_os              ,
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
						codigo_postagem      ,
						tipo_atendimento     ,
						consumidor_final_nome
					)VALUES(
						$hd_chamado                  ,
						$xcodigo_posto               ,
						'$aux_data_nf'          ,
						'$numero_nf'            ,
						'$pre_os'                    ,
						upper('$nome')           ,
						upper('$endereco')       ,
						upper('$numero')         ,
						upper('$complemento')    ,
						upper('$bairro')         ,
						upper('$cep')            ,
						upper('$telefone')       ,
						upper('$xconsumidor_fone2')  ,
						upper('$email')          ,
						upper('$cnpj')           ,
						upper('$ie')             ,
						'$cidade'                    ,
						'$codigo_postagem'      ,
						$tipo_atedimento        ,
						'$consumidor_final'
					)";

					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
					//echo "<br><br>".nl2br($sql);

					for($i = 1;$i <= $qtde_produto;$i++){
						
						$serie							= $_POST["serie_$i"];
						$produto						= $_POST["produto_$i"];
						$descricao						= $_POST["descricao_$i"];
						$quantidade						= $_POST["quantidade_$i"];
						$defeito_reclamado_descricao	= $_POST["defeito_reclamado_descricao_$i"];
						$defeito_reclamado				= $_POST["defeito_reclamado_$i"];

						if(strlen($serie) != 0 OR strlen($produto) != 0 OR strlen($defeito_reclamado_descricao) != 0){

							$sql = "
									SELECT
										produto, descricao
									FROM
										tbl_produto
									JOIN
										tbl_linha ON tbl_produto.linha = tbl_linha.linha
									WHERE
										tbl_linha.fabrica = $login_fabrica
										AND tbl_produto.referencia_fabrica = '$produto'
									LIMIT 1
							";
							//echo "<br>".nl2br($sql);
							$res = pg_query($con,$sql);
							$produto = pg_result($res,0,0);
							$descricao_produto = pg_result($res,0,0);

							$quantidade = (int) $quantidade;
							if($quantidade < 1)
								$quantidade = 1;

							for($y = 1;$y <= $quantidade;$y++){

								$sql = "INSERT INTO tbl_hd_chamado_item(
											hd_chamado						,
											data							,
											admin							,
											produto							,
											comentario						,
											serie							,
											defeito_reclamado_descricao		,
											defeito_reclamado		,
											status_item
										)values(
											$hd_chamado						,
											current_timestamp				,
											$login_admin					,
											$produto						,
											'$descricao_produto'			,
											'$serie'						,
											'$defeito_reclamado_descricao'	,
											$defeito_reclamado				,
											'Aberto'
										)";
								$res = pg_exec($con,$sql);
								$msg_erro .= pg_errormessage($con);
								//echo "<br><br>".($sql);
							}
						}
					}
		}

		if(strlen($msg_erro)== 0){
			if($email){
				$admin_email = $email;
			}else{
				$admin_email = "suporte@telecontrol.com.br";
			}

			$sql = "SELECT contato_email from tbl_posto_fabrica where posto = $xcodigo_posto and fabrica=$login_fabrica";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res) == 0){
				$email_posto = pg_result($res,0,contato_email);
			}else{
				$sql = "SELECT email from tbl_posto where posto = $xcodigo_posto";
				$res = pg_exec($con,$sql);
				$email_posto = pg_result($res,0,email);
			}

			$subject  = "Nova Pré-OS cadastrada pelo SAC da Fábrica";

			$message = "<br><br><b> Favor dar continuidade no atendimento dessa Pré-OS.<br><br>";
			$message .= "--<br>Att, <br>Suporte Telecontrol<br><br>";
			$message .= "<b>Esta é uma mensagem automática, não responda este e-mail</b>";

			$message_comunicado .= "<br>Foi aberta uma Pré-OS de número <b>$hd_chamado</b><br>";
			$message_comunicado .= "Favor dar continuidade no atendimento dessa Pré-OS.<br>";
			$message_comunicado .= "-------------<br><br>";

			$headers = "From: Call-center <suporte@telecontrol.com.br>\n";
			$headers .= "MIME-Version: 1.0\n";
			$headers .= "Content-type: text/html; charset=iso-8859-1\n";

			//$mail_ederson = "ederson.sandre@telecontrol.com.br";mail($mail_ederson,$subject,$message,$headers);
			$mail_andre = "andre.dias@br.bosch.com";	mail($mail_andre,$subject,$message,$headers);
			$mail_renato = "renato.lima2@br.bosch.com"; 	mail($mail_renato,$subject,$message,$headers);

			$peca = "null";		
			$produto = "null";
			$aux_familia = "null";
			$aux_linha = "null";
			$aux_extensao = "null";
			$aux_descricao = $subject;
			$aux_mensagem  = $message_comunicado;
			$aux_tipo      = "Comunicado";
			$posto	       = $xcodigo_posto;
			$aux_obrigatorio_os_produto = "'f'";
			$aux_obrigatorio_site = "'t'";
			$aux_tipo_posto = "null";
			$aux_ativo = "'t'";
			$aux_estado = "null";
			$aux_pais = "'BR'";
			$remetente_email = "$admin_email";
			$pedido_faturado="'f'";
			$pedido_em_garantia="'f'";
			$digita_os="'f'";
			$reembolso_peca_estoque="'f'";

			$sql = "INSERT INTO tbl_comunicado (
				peca                   ,
				produto                ,
				familia                ,
				linha                  ,
				extensao               ,
				descricao              ,
				mensagem               ,
				tipo                   ,
				fabrica                ,
				obrigatorio_os_produto ,
				obrigatorio_site       ,
				posto                  ,
				tipo_posto             ,
				ativo                  ,
				estado                 ,
				pais                   ,
				remetente_email        ,
				pedido_faturado        ,
				pedido_em_garantia     ,
				digita_os              ,
				reembolso_peca_estoque 
			) VALUES (
				$peca                       ,
				$produto                    ,
				$aux_familia                ,
				$aux_linha                  ,
				$aux_extensao               ,
				'$aux_descricao'            ,
				'$aux_mensagem'             ,
				'$aux_tipo'                 ,
				$login_fabrica              ,
				$aux_obrigatorio_os_produto ,
				$aux_obrigatorio_site       ,
				$posto                      ,
				$aux_tipo_posto             ,
				$aux_ativo                  ,
				$aux_estado                 ,
				$aux_pais                   ,
				'$remetente_email'          ,
				$pedido_faturado            ,
				$pedido_em_garantia         ,
				$digita_os                  ,
				$reembolso_peca_estoque     
			);";
			$res = @pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

		if (strlen($msg_erro) == 0){
				//$res = pg_exec($con,"ROLLBACK TRANSACTION");
				$res = pg_exec($con,"COMMIT TRANSACTION");

				echo "<script language='javascript'>
					alert('Pré-os Cadastrada com Sucesso');
					window.open('print_atendimento_gravado.php?hd_chamado=$hd_chamado','hd_chamado','scrollbars=yes,height=600, width=800');
					window.location = '$PHP_SELF';
				</script>"; 
			}else{
				$res = pg_exec($con,"ROLLBACK TRANSACTION");
			}
	}
	
	if(strlen($anexo1) > 0 OR strlen($anexo2) > 0 OR strlen($anexo3) > 0){
		$dir = "/var/www/assist/www/admin_cliente/anexos/";
		$cont = 0;

		if(strlen($anexo1) > 0){
			$file = $hd_chamado.".".strtolower(pathinfo($anexo1,PATHINFO_EXTENSION));
			if(move_uploaded_file($_FILES['anexo_1']['tmp_name'], $dir.$file))
				$cont = 2;
		}

		if(strlen($anexo2) > 0){
			if($cont == 0)
				$file = $hd_chamado.".".strtolower(pathinfo($anexo2,PATHINFO_EXTENSION));
			else
				$file = $hd_chamado."-{$cont}.".strtolower(pathinfo($anexo2,PATHINFO_EXTENSION));
			
			if(move_uploaded_file($_FILES['anexo_2']['tmp_name'], $dir.$file)){
				if($cont == 0)
					$cont = 2;
				else
					$cont = $cont + 1;
			}
		}

		if(strlen($anexo3) > 0){
			if($cont == 0)
				$file = $hd_chamado.".".strtolower(pathinfo($anexo3,PATHINFO_EXTENSION));
			else
				$file = $hd_chamado."-{$cont}.".strtolower(pathinfo($anexo3,PATHINFO_EXTENSION));
			
			move_uploaded_file($_FILES['anexo_3']['tmp_name'], $dir.$file);
		}
	}
	/* FIM DO INSERT DE DADOS */
}
//exit;
function saudacao(){ 
	$hora = date("H");
	echo ($hora >= 7 and $hora <= 11) ? "bom dia" : (($hora>=18) ? "boa noite" : "boa tarde");
}

$callcenter  = $_GET['callcenter'];
$imprimir_os = trim($_GET['imprimir_os']);


if(strlen($callcenter)>0){

	$sql = "SELECT	tbl_hd_chamado_extra.hd_chamado as callcenter,
					tbl_hd_chamado.admin as usuario_abriu,
					to_char(tbl_hd_chamado_extra.data_abertura,'DD/MM/YYYY') as abertura_callcenter,
					tbl_hd_chamado_extra.nome,
					tbl_hd_chamado_extra.endereco ,
					tbl_hd_chamado_extra.numero ,
					tbl_hd_chamado_extra.complemento ,
					tbl_hd_chamado_extra.bairro ,
					tbl_hd_chamado_extra.cep ,
					tbl_hd_chamado_extra.fone ,
					tbl_hd_chamado_extra.fone2 ,
					tbl_hd_chamado_extra.celular ,
					tbl_hd_chamado_extra.email ,
					tbl_hd_chamado_extra.cpf ,
					tbl_hd_chamado_extra.rg ,
					tbl_hd_chamado_extra.cliente ,
					tbl_hd_chamado_extra.consumidor_revenda,
					tbl_cidade.nome as cidade_nome,
					tbl_cidade.estado,
					tbl_hd_chamado_extra.origem,
					tbl_hd_chamado.admin AS admin_abriu,
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
					tbl_produto.voltagem,
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
					tbl_hd_chamado_extra.abre_os,
					tbl_hd_chamado_extra.leitura_pendente,
					tbl_hd_chamado.atendente as atendente_pendente,
					tbl_hd_chamado_extra.defeito_reclamado_descricao as hd_extra_defeito,
					tbl_hd_chamado_extra.numero_processo,
					tbl_hd_chamado_extra.tipo_registro  ,
					tbl_hd_chamado_extra.familia ,
					tbl_admin.login            AS admin_login ,
					tbl_admin.nome_completo    AS admin_nome_completo
		FROM tbl_hd_chamado
		JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
		JOIN tbl_admin  on tbl_hd_chamado.admin = tbl_admin.admin
		LEFT JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
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
			$callcenter                = pg_result($res,0,'callcenter');
			$usuario_abriu			   = pg_result($res,0,'usuario_abriu');
			$abertura_callcenter       = pg_result($res,0,'abertura_callcenter');
			$data_abertura_callcenter  = pg_result($res,0,'data');
			$natureza_chamado          = pg_result($res,0,'natureza_operacao');
			$consumidor_nome           = pg_result($res,0,'nome');
			$cliente                   = pg_result($res,0,'cliente');
			$consumidor_cpf            = pg_result($res,0,'cpf');
			$consumidor_rg             = pg_result($res,0,'rg');
			$consumidor_email          = pg_result($res,0,'email');
			$consumidor_fone           = pg_result($res,0,'fone');
			$consumidor_fone2          = pg_result($res,0,'fone2');
			$consumidor_fone3          = pg_result($res,0,'celular');
			$consumidor_cep            = pg_result($res,0,'cep');
			$consumidor_endereco      = pg_result($res,0,'endereco');
			$consumidor_numero        = pg_result($res,0,'numero');
			$consumidor_complemento   = pg_result($res,0,'complemento');
			$consumidor_bairro        = pg_result($res,0,'bairro');
			$consumidor_cidade        = pg_result($res,0,'cidade_nome');
			$consumidor_estado        = pg_result($res,0,'estado');
			$consumidor_revenda       = pg_result($res,0,'consumidor_revenda');
			$origem                   = pg_result($res,0,'origem');
			$assunto                  = pg_result($res,0,'assunto');
			$sua_os                   = pg_result($res,0,'sua_os');
			$os                       = pg_result($res,0,'os');
			$data_abertura            = pg_result($res,0,'data_abertura');
			$produto                  = pg_result($res,0,'produto');
			$produto_referencia       = pg_result($res,0,'produto_referencia');
			$produto_nome             = pg_result($res,0,'produto_nome');
			$voltagem                 = pg_result($res,0,'voltagem');
			$serie                    = pg_result($res,0,'serie');
			$data_nf                  = pg_result($res,0,'data_nf');
			$nota_fiscal              = pg_result($res,0,'nota_fiscal');
			$revenda                  = pg_result($res,0,'revenda');
			$revenda_nome             = pg_result($res,0,'revenda_nome');
			$posto                    = pg_result($res,0,'posto');
			$posto_nome               = pg_result($res,0,'posto_nome');
			$defeito_reclamado        = pg_result($res,0,'defeito_reclamado');
			$reclamado                = pg_result($res,0,'reclamado');
			$status_interacao         = pg_result($res,0,'status');
			$atendente                = pg_result($res,0,'atendente');
			$receber_informacoes	  = pg_result($res,0,'receber_info_fabrica');
			$codigo_posto	          = pg_result($res,0,'codigo_posto');
			$linha         	          = pg_result($res,0,'linha');
			$abre_os                  = pg_result($res,0,'abre_os');
			$leitura_pendente         = pg_result($res,0,'leitura_pendente');
			$atendente_pendente       = pg_result($res,0,'atendente_pendente');
			$categoria                = pg_result($res,0,'categoria');
			$hd_extra_defeito         = pg_result($res,0,'hd_extra_defeito');
			$numero_processo          = pg_result($res,0,'numero_processo');
			$tipo_registro            = pg_result($res,0,'tipo_registro');
			$admin_abriu              = pg_result($res,0,'admin_abriu');
			$familia                  = pg_result($res,0,'familia');
			$admin_login              = pg_result($res,0,'admin_login');
			$admin_nome_completo      = pg_result($res,0,'admin_nome_completo');

			if ($login_fabrica == 51 and $leitura_pendente == "t"){
				if ($atendente_pendente == $login_admin){
					$sql = "UPDATE tbl_hd_chamado_extra set leitura_pendente = null
							WHERE hd_chamado = $callcenter	";
					$res = pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}
			if(strlen($codigo_posto)>0) {
				$procon_codigo_posto = $codigo_posto;
				$procon_posto_nome   = $posto_nome;
				$codigo_posto_tab   = $codigo_posto;
				$posto_nome_tab     = $posto_nome;
			}

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
			} elseif($login_fabrica == 11) {

					$sub_tipo_reclamacao = array("mau_atendimento","posto_nao_contribui","demonstra_desorg","possui_bom_atend","demonstra_org","reclamacao_at_info");
					if(in_array($natureza_chamado, $sub_tipo_reclamacao) or $natureza_chamado == 'reclamacao_at'){
						$natureza_chamado2 = $natureza_chamado;
						$natureza_chamado = "reclamacao_at";
					}
					$sub_reclamacao_procon = array("pr_reclamacao_at","pr_info_at","pr_mau_atend","pr_posto_n_contrib","pr_demonstra_desorg","pr_bom_atend","pr_demonstra_org");

					if($natureza_chamado == 'procon' or in_array($natureza_chamado, $sub_reclamacao_procon) ) {
						$natureza_chamado2 = $natureza_chamado;
						$natureza_chamado  = "procon";
					}
					$tipo_atendimento = array(
							1 => 'reclamacao_produto',
							2 => 'reclamacao_empresa',
							3 => 'reclamacao_at',
							4 => 'duvida_produto',
							5 => 'sugestao',
							6 => 'procon' ,
							7 => 'onde_comprar');
			}else{
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
		if($assunto=='Indicação de Posto' and ($login_fabrica==5 or $login_fabrica == 24)) {
			$indicacao_posto='t';
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
					tbl_hd_chamado_extra.celular ,
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
					tbl_produto.voltagem,
					tbl_hd_chamado_extra.serie,
					to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') as data_nf,
					tbl_hd_chamado_extra.nota_fiscal,
					tbl_hd_chamado_extra.abre_os,
					tbl_hd_chamado_extra.leitura_pendente,
					tbl_hd_chamado.atendente as atendente_pendente,
					tbl_hd_chamado_extra.defeito_reclamado_descricao as hd_extra_defeito,
					tbl_hd_chamado_extra.tipo_registro ,
					tbl_admin.login            AS admin_login ,
					tbl_admin.nome_completo    AS admin_nome_completo
		FROM      tbl_hd_chamado
		JOIN      tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
		LEFT JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
		LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto
		LEFT JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		WHERE tbl_hd_chamado.hd_chamado = $Id";
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){
		echo $consumidor_nome           = pg_result($res,0,nome);
		$cliente                   = pg_result($res,0,cliente);
		$consumidor_cpf            = pg_result($res,0,cpf);
		$consumidor_rg             = pg_result($res,0,rg);
		$consumidor_email          = pg_result($res,0,email);
		$consumidor_fone           = pg_result($res,0,fone);
		$consumidor_fone2          = pg_result($res,0,fone2);
		$consumidor_fone3          = pg_result($res,0,celular);
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
		$voltagem                 = pg_result($res,0,voltagem);
		$serie                    = pg_result($res,0,serie);
		$data_nf                  = pg_result($res,0,data_nf);
		$nota_fiscal              = pg_result($res,0,nota_fiscal);
		$revenda                  = pg_result($res,0,consumidor_revenda);
		$abre_os                  = pg_result($res,0,abre_os);
		$leitura_pendente         = pg_result($res,0,leitura_pendente);
		$atendente_pendente       = pg_result($res,0,atendente_pendente);
		$hd_extra_defeito         = pg_result($res,0,hd_extra_defeito);
		$tipo_registro            = pg_result($res,0,tipo_registro);
		$admin_login              = pg_result($res,0,admin_login);
		$admin_nome_completo      = pg_result($res,0,admin_nome_completo);

		if ($login_fabrica == 51 and $leitura_pendente == "t"){
			if ($atendente_pendente == $login_admin){
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
<style type='text/css' medial='all'>

.input {
	font-size: 10px;
	font-family: verdana;
	BORDER-RIGHT: #666666 1px double;
	BORDER-TOP: #666666 1px double;
	BORDER-LEFT: #666666 1px double;
	BORDER-BOTTOM: #666666 1px double;
	BACKGROUND-COLOR: #ffffff
}

.respondido {
	font-size: 10px;
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

.input_req {
	font-size: 10px;
	font-family: verdana;
	BORDER-RIGHT: #666666 1px double;
	BORDER-TOP: #666666 1px double;
	BORDER-LEFT: #666666 1px double;
	BORDER-BOTTOM: #666666 1px double;
	BACKGROUND-COLOR: #ffffff;
}

.input_req2 {
	font-size: 10px;
	font-family: verdana;
	BORDER-RIGHT: #666666 1px double;
	BORDER-TOP: #666666 1px double;
	BORDER-LEFT: #666666 1px double;
	BORDER-BOTTOM: #666666 1px double;
	BACKGROUND-COLOR: #ffffff;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial" !important; 
    color:#FFFFFF;
    text-align:center;
	padding: 2px 0;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial" !important; 
    color:#FFFFFF;
    text-align:center;
}

.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 10px auto;
    border:1px solid #596d9b;
    padding: 2px 0;
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
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
    width: 700px;
    margin: 0 auto;
}

.formulario td{
	padding: 3px 0 3px 10px;
}

.btn_submit{
   text-align: center;
   padding: 15px 0;
}

.espaco{
    padding-left: 80px;
}

.frm{
	padding: 3px 1px;
	font-weight: normal;
}

#tabela_itens td{
	padding: 0;
	padding-bottom: 3px;
}
</style>

<!--=============== <FUNES> ================================!-->
<? include "javascript_pesquisas.php" ?>
<script language='javascript' src='js/ajax.js'></script>

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

function function1(linha2) {

	var linha = document.getElementById('qtde_produto').value;
//	alert(linha);
	linha = parseInt(linha) + 1;
	if (!document.getElementById('item'+linha)) {
	var tbl = document.getElementById('tabela_itens');
		//var lastRow = tbl.rows.length;	
		//var iteration = lastRow;

		//Atualiza a qtde de linhas
		$('#qtde_produto').val(linha);

	/*Criar TR - Linha*/
		var nova_linha = document.createElement('tr');
		nova_linha.setAttribute('rel', linha);

		/********************* COLUNA 1 ****************************/

		/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
		var el = document.createElement('label');
		el.innerHTML = '<strong>Série:</strong>';
		celula.appendChild(el);
		nova_linha.appendChild(celula);
		
		/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';

		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'serie_' +linha);
		el.setAttribute('id', 'serie_' + linha);
		el.setAttribute('class','input');
		celula.appendChild(el);
		var el = document.createElement('img');
		el.setAttribute('src', 'imagens/lupa.png');
		el.setAttribute('border', '0');
		el.setAttribute('align', 'absmiddle');
		el.setAttribute('style', 'cursor: pointer');
		el.onclick = function(){
			var nome       = document.getElementById('produto_nome_'+linha);
			var produto    = document.getElementById('produto_referencia_'+linha);
			var mapa_linha = $('#mapa_linha'+linha);
			var serie      = document.getElementById('serie_'+linha);
			fnc_pesquisa_serie(produto,nome,'serie',mapa_linha,serie);
		}
		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/********************* COLUNA 2 ****************************/
		/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
		var el = document.createElement('label');
		el.innerHTML = '<strong>Modelo:</strong>';
		celula.appendChild(el);
		nova_linha.appendChild(celula);
		
		/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';

		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'produto_referencia_' +linha);
		el.setAttribute('id', 'produto_referencia_' + linha);
		el.setAttribute('size','15');
		el.setAttribute('class','input');
		celula.appendChild(el);
		var el = document.createElement('img');
		el.setAttribute('src', 'imagens/lupa.png');
		el.setAttribute('border', '0');
		el.setAttribute('align', 'absmiddle');
		el.setAttribute('style', 'cursor: pointer');
		el.onclick = function(){
			var nome       = document.getElementById('produto_nome_'+linha);
			var produto    = document.getElementById('produto_referencia_'+linha);
			var mapa_linha = $('#mapa_linha'+linha);
			fnc_pesquisa_produto2(produto,nome,'referencia',mapa_linha);
		}

		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/********************* COLUNA 3 ****************************/
		/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
		var el = document.createElement('label');
		el.innerHTML = '<strong>Descrição:</strong>';
		celula.appendChild(el);
		nova_linha.appendChild(celula);
		
		/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';

		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'produto_nome_' +linha);
		el.setAttribute('id', 'produto_nome_' + linha);
		el.setAttribute('size','20');
		el.setAttribute('class','input');
		celula.appendChild(el);
		var el = document.createElement('img');
		el.setAttribute('src', 'imagens/lupa.png');
		el.setAttribute('border', '0');
		el.setAttribute('align', 'absmiddle');
		el.setAttribute('style', 'cursor: pointer');
		el.onclick = function(){
			var nome       = document.getElementById('produto_nome_'+linha);
			var produto    = document.getElementById('produto_referencia_'+linha);
			var mapa_linha = $('#mapa_linha'+linha);
			fnc_pesquisa_produto2(produto,nome,'descricao',mapa_linha);
		}
		celula.appendChild(el);
		nova_linha.appendChild(celula);

		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
		var el = document.createElement('label');
		el.innerHTML = '<strong>Defeito Reclamado  </strong>';
		celula.appendChild(el);
		nova_linha.appendChild(celula);

		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
		<?if ($login_fabrica == 52) {
			
		?>
		var teste_array = '<?	$sql = "SELECT distinct tbl_defeito_reclamado.descricao, tbl_defeito_reclamado.defeito_reclamado FROM tbl_diagnostico JOIN tbl_defeito_reclamado on tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado JOIN tbl_produto on tbl_diagnostico.linha = tbl_produto.linha and tbl_diagnostico.familia = tbl_produto.familia WHERE tbl_diagnostico.fabrica = $login_fabrica AND tbl_diagnostico.ativo is true"; $res1 = pg_exec ($con,$sql); if (pg_numrows($res1) > 0) { for ($x = 0 ; $x < pg_numrows($res1) ; $x++){$defeito_reclamado = trim(pg_result($res1,$x,defeito_reclamado)); $descricao  = trim(pg_result($res1,$x,descricao)); $descricao = substr($descricao,0,30); echo $defeito_reclamado;echo'/';echo $descricao;echo '|'; }	 }	?>';

		teste_array = teste_array.split('|');
		var qtd = teste_array.length;
		var el = document.createElement("select");
		el.setAttribute('name', 'defeito_reclamado_' + linha);
		el.setAttribute('id', 'defeito_reclamado_' + linha);
		el.setAttribute('class','input');
		elop=document.createElement("OPTION");
		elop.setAttribute('value','');
		texto1=document.createTextNode(" ");
		elop.appendChild(texto1);
		el.appendChild(elop);

		for ($i=0;$i<qtd;$i++) {
			var array = teste_array[$i].split('/');
			var codigo = array[0];
			var nome = array[1];

			if (codigo != '') {
				elop=document.createElement("OPTION");
				elop.setAttribute('value',codigo);
				texto1=document.createTextNode(nome);
				elop.appendChild(texto1);
				el.appendChild(elop);
			}
		}
		<?} else {	
		?>

		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'defeito_reclamado_' +linha);
		el.setAttribute('id', 'defeito_reclamado_' + linha);
		el.setAttribute('size','30');
		el.setAttribute('class','input');
		
		<?}?>
		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/************ FINALIZA LINHA DA TABELA ***********/
		var tbody = document.createElement('TBODY');
		tbody.appendChild(nova_linha);
		tbl.appendChild(tbody);

	}
}

//exclusivo para bosch 96 HD 
function add_linha_bosch_security(linha2) {
	var linha = document.getElementById('qtde_produto').value;
//	alert(linha);
	linha = parseInt(linha) + 1;
	if (!document.getElementById('item'+linha)) {
	var tbl = document.getElementById('tabela_itens');
		//Atualiza a qtde de linhas
		$('#qtde_produto').val(linha);

	/*Criar TR - Linha*/
		var nova_linha = document.createElement('tr');
		nova_linha.setAttribute('rel', linha);

		/********************* COLUNA 1 ****************************/
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';

		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'produto_' +linha);
		el.setAttribute('id', 'produto_' + linha);
		el.setAttribute('class','frm');
		el.setAttribute('style', 'width: 70px');
		celula.appendChild(el);
		var el = document.createElement('img');
		el.setAttribute('src', 'imagens/lupa.png');
		el.setAttribute('border', '0');
		el.setAttribute('align', 'absmiddle');
		el.setAttribute('style', 'cursor: pointer;');
		el.onclick = function(){
			var nome       = document.getElementById('produto_'+linha);
			var produto    = document.getElementById('descricao_'+linha);
			var mapa_linha = $('#mapa_linha'+linha);
			fnc_pesquisa_produto2(nome,produto,'referencia','');
		}

		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/********************* COLUNA 2 ****************************/
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';

		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'descricao_' +linha);
		el.setAttribute('id', 'descricao_' + linha);
		el.setAttribute('class','frm');
		el.setAttribute('style', 'width: 165px');
		celula.appendChild(el);
		var el = document.createElement('img');
		el.setAttribute('src', 'imagens/lupa.png');
		el.setAttribute('border', '0');
		el.setAttribute('align', 'absmiddle');
		el.setAttribute('style', 'cursor: pointer;');
		el.onclick = function(){
			var nome       = document.getElementById('produto_'+linha);
			var produto    = document.getElementById('descricao_'+linha);
			var mapa_linha = $('#mapa_linha'+linha);
			fnc_pesquisa_produto2(nome,produto,'descricao','');
		}
		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/********************* COLUNA 3 ****************************/
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';

		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'serie_' +linha);
		el.setAttribute('id', 'serie_' + linha);
		el.setAttribute('class','frm');
		el.setAttribute('style', 'width: 50px');
		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/********************* COLUNA 4 ****************************/
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'quantidade_' +linha);
		el.setAttribute('id', 'quantidade_' + linha);
		el.setAttribute('class','frm');
		el.setAttribute('size','1');
		el.setAttribute('value','1');
		el.setAttribute('style', 'cursor: pointer; width: 15px');
		el.setAttribute('onblur','javascript: if(document.frm_callcenter.quantidade_'+linha+'.value > 1){ alert("Atenção se os produtos desta linha tiverem série/lote ou defeitos diferentes devem ser digitados em linhas diferentes.");}');
		celula.appendChild(el);
		nova_linha.appendChild(celula);


		/********************* COLUNA 5 ****************************/
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
		var el = document.createElement('select');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'defeito_reclamado_' +linha);
		el.setAttribute('id', 'defeito_reclamado_' + linha);
		el.setAttribute('class','frm');
		el.setAttribute('style', 'cursor: pointer; width: 155px');
		el.setAttribute('onfocus',"atualizaDefeito(document.frm_callcenter.produto_"+linha+".value,"+linha+",'');");
		/*
		el.onfocus = function(ev){
			var linha_ref = $(this).attr('id');
			var re = /\d+$/;
			var linha = re.exec(linha_ref);
			var referencia = $("input[name=produto_"+linha+']').val();
			atualizaDefeito(referencia,linha);
		}
		*/

		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/********************* COLUNA 6 ****************************/
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'defeito_reclamado_descricao_' +linha);
		el.setAttribute('id', 'defeito_reclamado_descricao_' + linha);
		el.setAttribute('class','frm');
		el.setAttribute('style', 'cursor: pointer; width: 140px');
		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/************ FINALIZA LINHA DA TABELA ***********/
		var tbody = document.createElement('tbody');
		tbody.appendChild(nova_linha);
		tbl.appendChild(tbody);

	}
}

function criaCelula(texto) {
	var celula = document.createElement('td');
	var textoNode = document.createTextNode(texto);
	celula.appendChild(textoNode);
	return celula;
}

function buscaCEP(cep,endereco,bairro,cidade,estado) {
	if (endereco.value.length == 0 || 1 == 1) {
		http.open("GET", "http://www.telecontrol.com.br/assist/admin_cliente/ajax_cep.php?cep=" + escape(cep), true);
		http.onreadystatechange = function () { devolveCEP (http,endereco,bairro,cidade,estado) ; } ;
		http.send(null);
	}
}

function devolveCEP (http,endereco,bairro,cidade,estado) {
	if (http.readyState == 4) {
		if (http.status == 200) {
			results = http.responseText.split(";");
			if (typeof (results[0]) != 'undefined') endereco.value = results[0];
			if (typeof (results[1]) != 'undefined') bairro.value   = results[1];
			if (typeof (results[2]) != 'undefined') cidade.value   = results[2];
			if (typeof (results[3]) != 'undefined') estado.value   = results[3];
		}
	}
}

function fnc_pesquisa_serie (campo, campo2, tipo, mapa_linha,campo3) {
	if (tipo == "serie" ) {
		var xcampo = campo3;
	}
	if (xcampo.value != "") {
		var url = "";
		url = "produto_serie_pesquisa_fricon.php?campo=" + xcampo.value + "&tipo=" + tipo + "&mapa_linha=t&voltagem=t";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.serie   = campo3;
		janela.referencia   = campo;
		janela.descricao    = campo2;
		janela.mapa_linha   = mapa_linha;
		janela.voltagem     = document.frm_callcenter.voltagem;
		janela.focus();
	}
}

$(function() {
	// !129655 - Carregar dúvidas já cadastradas para este chamado [augusto]
	// !133157 - Carregar dúvidas quando o usuário não selecionou a aba dúvida, ou seja, a dúvida de produto [augusto]
	<?php if ( isset($callcenter) && ! empty($callcenter) ): ?>
			localizarFaq(document.frm_callcenter.produto_referencia.value,'faq_duvida_duvida','carregar');
			localizarFaq(document.frm_callcenter.produto_referencia.value,'faq_duvida_produto','carregar');
	<?php endif; ?>
});

<?
if($login_fabrica==25 OR $login_fabrica==59){
	$w=1;
}else if($login_fabrica == 45){
	$w=1;
	$posicao = $posicao-1;
}else if($login_fabrica == 46 ){
	$w=1;
	$posicao = $posicao-1;
}else if ($login_fabrica == 2 OR $login_fabrica == 11){
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
	});
$().ready(function() {

	function formatItem(row) {
		return row[1] + " - " + row[2];
	}

	function formatItemPosto(row) {
		return row[2] + " - " + row[3] + " (Fantasia:" + row[4] + ")";
	}

	/* Busca pelo Cdigo */
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
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&mapa_linha=t&voltagem=t";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;
		janela.mapa_linha   = mapa_linha;
		janela.voltagem     = document.frm_callcenter.voltagem;
		janela.focus();
	}else{
		alert("Informar toda ou parte da informação para realizar a pesquisa!");
	}
}

function MudaCampo(campo){
	if (campo.value == 'reclamacao_at') {
		document.getElementById('info_posto').style.display='inline';
	}else{
		document.getElementById('info_posto').style.display='none';
	}
}

function enviaEmail(callcenter){
	url = "envio_email_callcenter.php?callcenter=" + callcenter;
	janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=700, height=500, top=18, left=0");
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
<?PHP if($login_fabrica == 2) { ?>
function fnc_pesquisa_consumidor_callcenter (campo, tipo, tipo2) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_consumidor_callcenter_new.php?nome=" + campo.value + "&tipo=nome&tipo2=" + tipo2;
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor_callcenter_new.php?cpf=" + campo.value + "&tipo=cpf&tipo2=" + tipo2;
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

<?PHP } else {?>

function fnc_pesquisa_consumidor_callcenter (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_consumidor_callcenter_new.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor_callcenter_new.php?cpf=" + campo.value + "&tipo=cpf";
	}
	if (tipo == "telefone") {
		url = "pesquisa_consumidor_callcenter_new.php?telefone=" + campo.value + "&tipo=telefone";
	}
	if (tipo == "cep") {
		url = "pesquisa_consumidor_callcenter_new.php?cep=" + campo.value + "&tipo=cep";
	}
	if (tipo == "atendimento") {
		url = "pesquisa_consumidor_callcenter_new.php?atendimento=" + campo.value + "&tipo=atendimento";
	}

	if (tipo == "os") {
		url = "pesquisa_consumidor_callcenter_new.php?os=" + campo.value + "&tipo=os";
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

<?PHP }?>


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
	janela.posto_km_tab = document.frm_callcenter.posto_km_tab;
	janela.abas = $('#container-Principal');
	janela.focus();
}

/* ========== Função AJUSTA CAMPO DE DATAS =========================
Nome da Função : ajustar_data (input, evento)
		Ajusta a formatao da Máscara de DATAS a medida que ocorre
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
	url = "../admin/callcenter_interativo_defeitos.php?ajax=true&natureza="+ natureza +"&produto=" + produto;
//	alert(url);
	http1[curDateTime].open('get',url);

	var campo = document.getElementById('div_defeitos');
//alert(natureza);
	http1[curDateTime].onreadystatechange = function(){
		if(http1[curDateTime].readyState == 1) {
			campo.innerHTML = "<img src='../admin/imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
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
function localizarFaq(produto,local,action){
	var action          = ( action == undefined ) ? 'pesquisa' : action ;
	var faq_duvida      = (action == 'pesquisa') ? document.getElementById(local).value : '    ';
	var campo           = '#div_'+local;
	var hd_chamado      = '<?php echo $callcenter; ?>';
	var buscar_marcados = ( action == 'pesquisa' ) ? '0' : '1' ;
	if ( hd_chamado.length <= 0 ) {
		hd_chamado = 0;
	}
	if(produto.length==0 && action == 'pesquisa'){
		alert('Por favor selecione o produto');
		return 0;
	} else if ( produto.length==0 ) {
		return 0;
	}

	if(faq_duvida.length==0){
		alert('Por favor inserir a dúvida');
		return 0;
	}

	url    = "callcenter_interativo_ajax.php";
	params = {'ajax':'true', 'faq_duvida':'true','produto':produto,'duvida':faq_duvida,'hd_chamado':hd_chamado,'buscar_marcados':buscar_marcados };
	$(campo).empty();
	$.get(url,params,function(resposta) {
		$(campo).html(resposta);
		$('.chk_faq').click(function() {
			$('.chk_faq').parent().css('background','none').css('cursor','pointer')
					     .end().filter(':checked').parent().css('background','#BCCACD');
		});
	},'html');
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
	janela.posto_tab        = document.frm_callcenter.posto_tab;
	janela.posto_km_tab     = document.frm_callcenter.posto_km_tab;
	janela.codigo_posto_tab = document.frm_callcenter.codigo_posto_tab;
	janela.posto_nome_tab   = document.frm_callcenter.posto_nome_tab;

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

	/* Norte */
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

<?PHP if ($login_fabrica == 2) { ?>
function fnc_tipo_atendimento(tipo) {
		$('#cpf').val('');
	if (tipo.value == 'C') {
		$('#cpf').attr('maxLength', 14);
		$('#cpf').attr('size', 18);
		$('#label_cpf').html('CPF:');
		$('#label_nome').unbind(function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'assistencia'); } );
		$('#label_cnpj').unbind( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'assistencia'); } );
		$('#label_nome').unbind(function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'revenda'); } );
		$('#label_cnpj').unbind( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'revenda'); } );
		$('#label_nome').click( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'consumidor'); } );
		$('#label_cnpj').click( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'consumidor'); } );
		$('#cpf').keypress (function(e){
			return txtBoxFormat(document.frm_callcenter, this.name, '999.999.999-99', e);
		});
	} else {
		if (tipo.value == 'R') {
			$('#cpf').attr('maxLength', 18);
			$('#cpf').attr('size', 23);
			$('#label_cpf').html('CNPJ:');
			$('#label_nome').unbind(function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'consumidor'); } );
			$('#label_cnpj').unbind( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'consumidor'); } );
			$('#label_nome').unbind(function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'assistencia'); } );
			$('#label_cnpj').unbind( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'assistencia'); } );
			$('#label_nome').click( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'revenda'); } );
			$('#label_cnpj').click( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'revenda'); } );
			$('#cpf').keypress(function(e){
				return txtBoxFormat(document.frm_callcenter, this.name, '99.999.999/9999-99', e);
			});
		} else {
			$('#cpf').attr('maxLength', 18);
			$('#cpf').attr('size', 23);
			$('#label_cpf').html('CNPJ:');
			$('#label_nome').unbind(function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'consumidor'); } );
			$('#label_cnpj').unbind( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'consumidor'); } );
			$('#label_nome').unbind(function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'revenda'); } );
			$('#label_cnpj').unbind( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'revenda'); } );
			$('#label_nome').click( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'assistencia'); } );
			$('#label_cnpj').click(function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'assistencia'); } );

			$('#cpf').keypress(function(e){
				return txtBoxFormat(document.frm_callcenter, this.name, '99.999.999/9999-99', e);
			});
		}
	}
}

<? }elseif($login_fabrica == 59 or $login_fabrica == 24 or $login_fabrica == 30 or $login_fabrica == 52){ // HD 75777 ?>
function fnc_tipo_atendimento(tipo) {
		$('#cpf').val('');
	if (tipo.value == 'C') {
		$('#cpf').attr('maxLength', 14);
		$('#cpf').attr('size', 18);
		$('#label_cpf').html('CPF:');
		$('#cpf').keypress (function(e){
			return txtBoxFormat(document.frm_callcenter, this.name, '999.999.999-99', e);
		});
	} else {
		if (tipo.value == 'R') {
			$('#cpf').attr('maxLength', 18);
			$('#cpf').attr('size', 23);
			$('#label_cpf').html('CNPJ:');
			$('#cpf').keypress(function(e){
				return txtBoxFormat(document.frm_callcenter, this.name, '99.999.999/9999-99', e);
			});
		}
	}
}
<?}?>

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

function indicacao(check){
	if (check.checked){
		$('.input_req').val('Indicação de Posto');
		$('#telefone').val('(000) 0000-0000');
		$('#cpf').val('000.000.000-00');
		$('#cep').val('00000-000');
		$('#consumidor_numero').val('00');
		$('#hora_ligacao').val('00:00');
		$('#consumidor_estado').val('');
		$('#origem').val('');
		$('#consumidor_revenda').val('');
		$('#receber_informacoes').attr('checked', false);
		$('#status_interacao').val('Resolvido');


		$('.input_req').attr('readonly', true);
		$('.input_req').attr('disabled', true);

		$('#consumidor_estado').attr('disabled', true);
		$('#origem').attr('disabled', true);
		$('#consumidor_revenda').attr('disabled', true);
		$('#receber_informacoes').attr('disabled', true);
		$('#status_interacao').attr('disabled', true);


	} else {

		$('.input_req').val('');
		$('#consumidor_estado').val('');
		$('#origem').val('');
		$('#consumidor_revenda').val('');
		$('#receber_informacoes').attr('checked', false);

		input_req = $(".input_req").get();
		for(i = 0; i < input_req.length; i++){
			$(input_req[i]).removeAttr('readonly');
			$(input_req[i]).removeAttr('disabled');
		}

		$('#consumidor_estado').removeAttr('disabled');
		$('#origem').removeAttr('disabled');
		$('#consumidor_revenda').removeAttr('disabled');
		$('#receber_informacoes').removeAttr('disabled');
		$('#status_interacao').removeAttr('disabled');

	}
}



function indicacao_suggar(check){
	if (check.checked){
		$('.input_req').val('Indicação de Posto');
		//$('#telefone').val('(000) 0000-0000');
		$('#cpf').val('000.000.000-00');
		$('#cep').val('00000-000');
		$('#consumidor_numero').val('00');
		$('#hora_ligacao').val('00:00');
		//$('#consumidor_estado').val('');
		$('#origem').val('');
		$('#consumidor_revenda').val('');
		$('#receber_informacoes').attr('checked', false);
		$('#status_interacao').val('Resolvido');
		$('#data_nf').val('');
		$('#nota_fiscal').val('');
		$('#serie').val('');

		$('.input_req').attr('readonly', true);
		$('.input_req').attr('disabled', true);

		//$('#consumidor_estado').attr('disabled', true);
		$('#origem').attr('disabled', true);
		$('#consumidor_revenda').attr('disabled', true);
		$('#receber_informacoes').attr('disabled', true);
		$('#status_interacao').attr('disabled', true);


	} else {

		$('.input_req').val('');
		$('#consumidor_estado').val('');
		$('#origem').val('');
		$('#consumidor_revenda').val('');
		$('#receber_informacoes').attr('checked', false);

		input_req = $(".input_req").get();
		for(i = 0; i < input_req.length; i++){
			$(input_req[i]).removeAttr('readonly');
			$(input_req[i]).removeAttr('disabled');
		}

		$('#consumidor_estado').removeAttr('disabled');
		$('#origem').removeAttr('disabled');
		$('#consumidor_revenda').removeAttr('disabled');
		$('#receber_informacoes').removeAttr('disabled');
		$('#status_interacao').removeAttr('disabled');

	}
}


function liberar_campos(){
	input_req = $(".input_req").get();
	for(i = 0; i < input_req.length; i++){
		$(input_req[i]).removeAttr('readonly');
		$(input_req[i]).removeAttr('disabled');
	}
	select_req = $("select:disabled").get();
	for(i = 0; i < select_req.length; i++){
		$(select_req[i]).removeAttr('disabled');
	}
}

function mostraAnexo3(valor){
	var valor = valor;
	//alert(valor);
	if(valor != 93){
		$('span.anexo_3').css('display','block');
	}else{
		$('span.anexo_3').css('display','none');
		document.frm_callcenter.anexo_3.value ='';
	}
}


function atualizaDefeito(referencia,id_campo,valor){
	$.post(
		'ajax_defeito_reclamado.php',
		{referencia : referencia,valor:valor},
		function(resposta){
			$('#defeito_reclamado_'+id_campo).html(resposta);
		}
	);

};

</script>

<br><br>
<? if(strlen($msg_erro)>0) {

//recarrega informacoes
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
	$consumidor_fone3          = trim($_POST['consumidor_fone3']);
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
	$voltagem                  = trim($_POST['voltagem']);
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
	$hd_extra_defeito          = trim($_POST['hd_extra_defeito']);

?>

<body <? if ($login_fabrica==24) { ?> onload="javascript: var check = document.getElementById('indicacao_posto'); indicacao_suggar(check)"; <?}?>>
	<table width='700px' border='0' align='center' cellpadding="0" cellspacing="2" style='margin: 0 auto;'>
		<tr class='msg_erro'>
			<td><? echo $msg_erro; ?></td>
		</tr>
	</table>

<?}

$sql = "SELECT nome from tbl_fabrica where fabrica = $login_fabrica";
$res = pg_exec($con,$sql);
$nome_da_fabrica = pg_result($res,0,0);

?>


<?
#94971
if($login_fabrica==59 AND strlen($_GET['herdar'])>0){
	$id = $_GET['Id'];
	$end_herda = "?herdar=sim&Id=$id";
}


if($login_fabrica != 96){ ?>
<form name="frm_callcenter" method="post" action="<?$PHP_SELF?><?=$end_herda?>">
<input name="callcenter" class="input" type="hidden" value='<?echo $callcenter;?>'>
<table width="98%" border="0" align="center" cellpadding="2" cellspacing="2" style='font-size:12px'>
<tr>
	<td align='left'>

		<table width="100%" border='0'>
			<tr>
				<td align='left'><strong>Cadastro de Atendimento</strong></td>
				<? if (strlen($callcenter)>0) {?>
				<td align='left'><strong><? echo "Atentedimento Nº $callcenter";?></strong></td>
				<?}?>
			</tr>
		</table>
	</td>
</tr>

<tr>
	<td>

	<div id='div_consumidor' style='display:inline; Position:relative;background-color: #e6eef7;width:100%'>
		<table width='100%' border='0' align='center' cellpadding='2' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px' id='tabela_consumidor'>
		<!--HD36903-->
		<?PHP
			if ($login_fabrica == 2) {
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
		<?
			}elseif($login_fabrica == 59 or $login_fabrica == 24 or $login_fabrica == 30 or $login_fabrica == 52) {
		?>
		<tr>
			<td colspan='6'  align='left'>
				<table border='0' cellpadding='3' cellspacing='0' width="50%">
					<tr>
						<td align='left'>
							<b>Tipo Consumidor:</b>
						</td>
						<td align='left'>
							CPF
							<input type='radio' name='consumidor_revenda' value='C' <?PHP if (strlen($consumidor_cpf) == 14 or strlen($consumidor_cpf) == 0) { echo "CHECKED";}
							if(strlen($callcenter) > 0) { echo " disabled"; }?> onclick="fnc_tipo_atendimento(this)">
						</td>
						<td align='left'>
							CNPJ
							<input type='radio' name='consumidor_revenda' value='R' <?PHP if (strlen($consumidor_cpf) == 18) { echo "CHECKED";}
							if(strlen($callcenter) > 0) { echo " disabled"; }
							?> onclick="fnc_tipo_atendimento(this)">
						</td>
					<tr>
				</table>
			</td>
		</tr>
		<? } ?>
		<tr>
			<td align='left'><strong>Nome:</strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_nome_anterior" value="<?php echo $consumidor_nome; ?>" />
				<input name="consumidor_nome" id="consumidor_nome"  value='<?php echo $consumidor_nome ;?>' onkeyup="somenteMaiusculaSemAcento(this)" <? if($login_fabrica==11 and (strlen($callcenter) >0 or strlen($Id) >0) ){?>  readonly <?}?> <?if ($login_fabrica == 24) {?>class="input_req2"<?} else {?> class="input_req" <?}?> type="text" size="35" maxlength="500"
				 <? if($login_fabrica==11){?> onChange="javascript: this.value=this.value.toUpperCase();"<?}?>> <img src='imagens/lupa.png' id='label_nome' border='0' align='absmiddle' <? if($login_fabrica <>2) { ?> onclick='javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, "nome")' <?}?>style='cursor: pointer' >
			</td>
			<td align='left'><strong><span id='label_cpf'>
			<?
			if((strlen($consumidor_cpf) == 14 and strlen($callcenter) > 0) or strlen($callcenter) == 0) {
				echo "CPF:";
				$limite ='14';
			}elseif(strlen($consumidor_cpf) == 18 and strlen($callcenter) > 0){
				echo "CNPJ:";
				$limite = "18";
			}?>
			</span></strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_cpf_anterior" value="<?php echo $consumidor_cpf; ?>" />
				<input name="consumidor_cpf" id="cpf" value='<? echo $consumidor_cpf ;?>' class="input_req" type="text" size="18" maxlength="<?=$limite?>" onkeypress="return txtBoxFormat(this.form, this.name, '999.999.999-99', event);" <?PHP if($login_fabrica==11 and (strlen($callcenter) >0 or strlen($Id) >0) ){?>  readonly <?}?> >
				<img src='imagens/lupa.png' border='0' id='label_cnpj' align='absmiddle' style='cursor: pointer' <? if($login_fabrica <>2) { ?>  onclick='javascript: fnc_pesquisa_consumidor_callcenter 	(document.frm_callcenter.consumidor_cpf, "cpf")' <?}?>>
				<input name="cliente" id="cliente" value='<? echo $cliente ;?>' type="hidden">
			</td>
			<td align='left'><strong>Rg:</strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_rg_anterior" value="<?php echo $consumidor_rg; ?>" />
				<input name="consumidor_rg" value='<? echo $consumidor_rg ;?>'  class="input_req" type="text" size="14" maxlength="14" <? if($login_fabrica==11 and (strlen($callcenter) >0 or strlen($Id) >0) ){?>  readonly <?}?> >
			</td>
		</tr>
		<tr>
			<?php $endereco_readonly = ( $login_fabrica == 11 && isset($callcenter) && $callcenter > 0 && $status_interacao != 'Aberto' ) ? 'readonly' : '' ;?>
			<td align='left'><strong>E-mail:</strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_email_anterior" value="<?php echo $consumidor_email; ?>" />
				<input name="consumidor_email" value='<? echo $consumidor_email ?>' class="input_req" type="text" size="40" maxlength="500" <?php echo $endereco_readonly; ?> >
			</td>
			<td align='left'><strong>
								<? echo ($login_fabrica==59) ? "Telefone Residêncial" : "Telefone"; ?>
							</strong>
			</td>
			<td align='left'>
				<input type="hidden" name="consumidor_fone_anterior" value="<?php echo $consumidor_fone; ?>" />
				<input name="consumidor_fone" id="telefone" value='<? echo $consumidor_fone ;?>'  <? if ($login_fabrica == 24) { ?>class="input_req2"<? } else { ?> class="input_req" <? } ?>  type="text" size="18" maxlength="15" onkeypress="return txtBoxFormat(this.form, this.name, '(99) 9999-9999', event);" <?php echo $endereco_readonly; ?> >
			</td>
			<td align='left'><strong>Cep:</strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_cep_anterior" value="<?php echo $consumidor_cep; ?>" />
				<input name="consumidor_cep" id="cep" value="<? echo $consumidor_cep ;?>"  class="input_req" type="text" size="14" maxlength="9" onchange="buscaCEP(this.value, document.frm_callcenter.consumidor_endereco, document.frm_callcenter.consumidor_bairro, document.frm_callcenter.consumidor_cidade, document.frm_callcenter.consumidor_estado) ;" onkeypress="return txtBoxFormat(this.form, this.name, '99999-999', event);" <?php echo $endereco_readonly; ?> >
			</td>
		</tr>
		<tr>
			<td align='left'><strong>Endereço:</strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_endereco_anterior" value="<?php echo $consumidor_endereco; ?>" />
				<input name="consumidor_endereco" id='consumidor_endereco' value='<? echo $consumidor_endereco ;?>' class="input_req" type="text" size="40" maxlength="500" <?php echo $endereco_readonly; ?> >
			</td>
			<td align='left'><strong>Número:</strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_numero_anterior" value="<?php echo $consumidor_numero; ?>" />
				<input name="consumidor_numero" id='consumidor_numero' value='<? echo $consumidor_numero ;?>' class="input_req" type="text" size="18" maxlength="16" <?php echo $endereco_readonly; ?> >
			</td>
			<td align='left'><strong>Complem.</strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_complemento_anterior" value="<?php echo $consumidor_complemento; ?>" />
				<input name="consumidor_complemento" id='consumidor_complemento' value='<? echo $consumidor_complemento ;?>' class="input_req" type="text" size="14" maxlength="20" <?php echo $endereco_readonly; ?> >
			</td>
		</tr>
		<tr>
			<td align='left'><strong>Bairro:</strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_bairro_anterior" value="<?php echo $consumidor_bairro; ?>" />
				<input name="consumidor_bairro" id='consumidor_bairro' value='<? echo $consumidor_bairro ;?>' class="input_req" type="text" size="40" maxlength="30" <?php echo $endereco_readonly; ?> >
			</td>
			<td align='left'><strong>Cidade:</strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_cidade_anterior" value="<?php echo $consumidor_cidade; ?>" />
				<input name="consumidor_cidade" id='consumidor_cidade' value='<? echo $consumidor_cidade ;?>'  <?if ($login_fabrica == 24) {?>class="input_req2"<?} else {?> class="input_req" <?}?> type="text" size="18" maxlength="16" <?php echo $endereco_readonly; ?> >
				<input name="cidade"  class="input_req" value='<? echo $cidade ;?>' type="hidden">
			</td>
			<td align='left'><strong>Estado:</strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_estado_anterior" value="<?php echo $consumidor_estado; ?>" />
				<select name="consumidor_estado" id='consumidor_estado' style='width:81px; font-size:9px'>
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
						if ($consumidor_estado == $ArrayEstados[$i]) echo " selected";
						echo ">".$ArrayEstados[$i]."</option>\n";
					}?>
				</select>
			</td>
		</tr>
		<tr>
			<td align='left'><strong>Origem:</strong></td>
			<td align='left'>
				<select name='origem' id='origem' style='width:102px;font-size:9px'>
				<? if($login_fabrica ==3) { // HD 48900?>
				<option value=''></option>
				<? } ?>
				<option value='Telefone' <?PHP if ($origem == 'Telefone') { echo "Selected";}?>>Telefone</option>
				<option value='Email' <?PHP if ($origem == 'Email') { echo "Selected";}?>>E-mail</option>
				</select>
			</td>
			<!--HD36903-->
			<?PHP if ($login_fabrica != 2) {?>
			<td align='left'><strong>Tipo:</strong></td>
			<td align='left'>
				<select name="consumidor_revenda" id='consumidor_revenda' style='width:81px; font-size:9px'>
				<? if($login_fabrica ==3) { // HD 48900?>
				<option value=''></option>
				<? } ?>
				<option value='C' <? if($consumidor_revenda == "C") echo "Selected" ;?>>Consumidor</option>
				<option value='R' <? if($consumidor_revenda == "R") echo "Selected" ;?>>Revenda</option>
				</select>
			</td>
			<?PHP }?>
		</tr>
		<tr>
			<td align='left' colspan='1'><strong>Telefone Comercial:</strong></td>
			<td align='left' colspan='1'>
				<input type="hidden" name="consumidor_fone2_anterior" value="<?php echo $consumidor_fone2; ?>" />
				<input name="consumidor_fone2" id="telefone2" value='<?php echo $consumidor_fone2 ;?>'  class="input"  type="text" size="18" maxlength="14" onkeypress="return txtBoxFormat(this.form, this.name, '(99) 9999-9999', event);">
			</td>

			<td align='left' colspan='1'><strong>Telefone Celular:</strong></td>
			<td align='left' colspan='1'>
				<input type="hidden" name="consumidor_fone3_anterior" value="<?php echo $consumidor_fone3; ?>" />
				<input name="consumidor_fone3" id="telefone3" value='<?php echo $consumidor_fone3 ;?>'  class="input"  type="text" size="18" maxlength="14" onkeypress="return txtBoxFormat(this.form, this.name, '(99) 9999-9999', event);">
			</td>
		</tr>
		</table>
	</div>
	<br>
	<table width="100%" border='0'>
		<tr>
			<td align='left'><strong>Informações do produto</strong></td>
		</tr>
	</table>
	<?
		unset($defeito_reclamado);
	?>
	<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px' name='tabela_itens' id='tabela_itens'>
		<thead>
		<tr>
			<td align='left'><strong>NF compra:</strong></td>
			<td align='left'>
				<input type="hidden" name="nota_fiscal_anterior_$i" value="<?php echo $nota_fiscal; ?>"/>
				<input name="nota_fiscal" id="nota_fiscal" class="<?php echo ($login_fabrica==24) ? 'input_req' : 'input' ; ?>" value="<?php echo $nota_fiscal;?>" maxlength="10" />
			</td>
			<td align='left'><strong>Data NF:</strong></td>
			<td align='left'>
				<input type="hidden" name="data_nf_anterior_$i" value="<?php echo $data_nf; ?>" />
				<input name="data_nf" id="data_nf" class="<?php echo ($login_fabrica==24) ? 'input_req' : 'input' ; ?>" rel="data" value="<?php echo $data_nf ;?>">
			</td>
		</tr>
		</thead>
		<tbody>
		<?php 

		if (strlen($callcenter)>0) {

			$sql_produto = "SELECT produto,descricao,referencia,serie,defeito_reclamado,defeito_reclamado_descricao from tbl_hd_chamado_item join tbl_produto using(produto) where hd_chamado = $callcenter order by hd_chamado_item ";

			$res_produto = pg_query($con,$sql_produto);
			$qtde_produto = pg_num_rows($res_produto);
		}

		if (strlen($qtde_produto) == 0) {
			$qtde_produto = 1;
		}
			for ( $i = 1 ; $i <= $qtde_produto ; $i++ ) {

				if (strlen($msg_erro)>0) {
					$serie					= $_POST['serie_'.$i];
					$produto_referencia		= $_POST['produto_referencia_'.$i];
					$produto_nome			= $_POST['produto_nome_'.$i];
					$defeito_reclamado		= $_POST['defeito_reclamado_'.$i];
				} else {
					if (strlen($callcenter)>0) {
							$serie					= pg_fetch_result($res_produto,$i-1,serie);
							$produto_referencia		= pg_fetch_result($res_produto,$i-1,referencia);
							$produto_nome			= pg_fetch_result($res_produto,$i-1,descricao);
							if ($login_fabrica == 52) {
								$defeito_reclamado		= pg_fetch_result($res_produto,$i-1,defeito_reclamado);
							} else {
								$defeito_reclamado		= pg_fetch_result($res_produto,$i-1,defeito_reclamado_descricao);
							}
						}
				}
		?>
		<tr>
			<td align='left'><strong>Série:</strong></td>
			<td align='left'>
				<input type="hidden" name="serie_anterior_<?=$i?>" value="<?php echo $serie; ?>" />
				<input name="serie_<?=$i;?>" id="serie_<?=$i;?>" class="<?php echo ($login_fabrica==24) ? 'input_req' : 'input' ; ?>" value="<?php echo $serie;?>" />
				<img src='imagens/lupa.png' border='0' align='absmiddle'
				style='cursor: pointer'
				onclick="javascript: fnc_pesquisa_serie (document.frm_callcenter.produto_referencia_<?=$i;?>,document.frm_callcenter.produto_nome_<?=$i;?>,'serie',document.frm_callcenter.mapa_linha,document.frm_callcenter.serie_<?=$i;?>)">
			</td>
			<td align='left'><strong>Modelo</strong></td>
			<td align='left'>
				<input type="hidden" name="produto_referencia_anterior_<?=$i?>" value="<?php echo $produto_referencia;  ?>" />
				<input name="produto_referencia_<?=$i?>"  class="input"  value='<? echo $produto_referencia ;?>'
				onblur="javascript: fnc_pesquisa_produto2 (document.frm_callcenter.produto_referencia_<?=$i;?>,document.frm_callcenter.produto_nome_<?=$i;?>,'referencia',document.frm_callcenter.mapa_linha); <?php  if ($login_fabrica <> 51){ # HD 41923 ?>
					mostraDefeitos('Reclamado',document.frm_callcenter.produto_referencia_<?=$i;?>.value);
					<?php } ?>
					atualizaQuadroMapas();" type="text" size="15" maxlength="15"><img src='imagens/lupa.png' border='0' align='absmiddle'
				style='cursor: pointer'
				onclick="javascript: fnc_pesquisa_produto2 (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao',document.frm_callcenter.mapa_linha)">
			</td>
			<td align='left'><strong>Descrição:</strong></td>
			<td align='left'>
				<input type="hidden" name="produto_nome_anterior_<?=$i?>" value="<?php echo $produto_nome; ?>" />
				<input type='hidden' name='produto_<?=$i?>' value="<? echo $produto; ?>">
				<input name="produto_nome_<?=$i?>"  size='19' class="input" value='<?php echo $produto_nome ;?>'
				<? if ($login_fabrica <> 52) { ?> onblur="javascript: fnc_pesquisa_produto2 (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao',document.frm_callcenter.mapa_linha); <?php }if ($login_fabrica <> 51){ ?>
				mostraDefeitos('Reclamado',document.frm_callcenter.produto_referencia_<?=$i;?>.value);
				<?php } ?>
				atualizaQuadroMapas();" type="text" size="35" maxlength="500"><img src='imagens/lupa.png' border='0' align='absmiddle'
				style='cursor: pointer'
				onclick="javascript: fnc_pesquisa_produto2 (document.frm_callcenter.produto_referencia_<?=$i;?>,document.frm_callcenter.produto_nome_<?=$i;?>,'descricao',document.frm_callcenter.mapa_linha)">
			</td>
			<td align='left'>
				<strong>Defeito Reclamado</strong>
			</td>
			<td><? if ($login_fabrica == 52 ) {
			
			;?>
				<select class='input' name='defeito_reclamado_<?=$i?>' id='defeito_reclamado_<?=$i?>'>
					<option> </option>
					<?php
					$sqldef = "SELECT distinct tbl_defeito_reclamado.descricao,
								tbl_defeito_reclamado.defeito_reclamado
								FROM tbl_diagnostico 
								JOIN tbl_defeito_reclamado on tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
								JOIN tbl_produto on tbl_diagnostico.linha = tbl_produto.linha and tbl_diagnostico.familia = tbl_produto.familia
								WHERE tbl_diagnostico.fabrica = $login_fabrica
								AND tbl_diagnostico.ativo is true";
					$resdef = pg_exec($con,$sqldef);
					if (pg_num_rows($resdef)>0) {
							for ($w=0;$w<pg_num_rows($resdef);$w++) {
							unset($selected);
							$xdefeito_reclamado = pg_result($resdef,$w,defeito_reclamado);
							$descricao         = pg_result($resdef,$w,descricao);
							$descricao = substr($descricao,0,30);
						
							if ($defeito_reclamado == $xdefeito_reclamado) {
								$selected = "SELECTED";
							}
							echo "<option value='$xdefeito_reclamado' $selected> $descricao</option>";
						}
					}
				?>
			</select>
			<?} else {
				echo "<input class='input' type='text' name='defeito_reclamado_$i' id='defeito_reclamado_$i' size='30' value='$defeito_reclamado'>";
			}?>
			</td>
			<td>
				<input type='button' name='addlinha' value='+' onclick='function1(<?=$i?>)'>
			</td>
		</tr>
		<? }?>
		<INPUT TYPE='hidden' NAME='qtde_produto' value='<? echo $i= $i-1;?>' id='qtde_produto'>
		</tbody>
	</table>

	<? if($login_fabrica <> 3){ //HD 40086 ?>
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
					<? if ($login_fabrica == 59) {?>
						<option value='AC'>Acre</option>
						<option value='AL'>Alagoas</option>
						<option value='AP'>Amapá</option>
						<option value='AM'>Amazonas</option>
						<option value='BA'>Bahia</option>
						<option value='CE'>Ceará</option>
						<option value='DF'>Distrito Federal</option>
						<option value='GO'>Goiás</option>
						<option value='ES'>Espírito Santo</option>
						<option value='MA'>Maranhão</option>
						<option value='MT'>Mato Grosso</option>
						<option value='MS'>Mato Grosso do Sul</option>
						<option value='MG'>Minas Gerais</option>
						<option value='PA'>Pará</option>
						<option value='PB'>Paraiba</option>
						<option value='PR'>Paraná</option>
						<option value='PE'>Pernambuco</option>
						<option value='PI'>Piauí</option>
						<option value='RJ'>Rio de Janeiro</option>
						<option value='RN'>Rio Grande do Norte</option>
						<option value='RS'>Rio Grande do Sul</option>
						<option value='RO'>Rondônia</option>
						<option value='RR'>Roraima</option>
						<option value='SP'>São Paulo</option>
						<option value='SC'>Santa Catarina</option>
						<option value='SE'>Sergipe</option>
						<option value='TO'>Tocantins</option>
					<? } elseif ($login_fabrica == 5) {?>
						<option value='SUL'        >Sul</option>
						<option value='SP-capital' >São Paulo - Capital</option>
						<option value='SP-interior'>São Paulo - Interior</option>
						<option value='RJ'         >Rio de Janeiro</option>
						<option value='MG'         >Minas Gerais</option>
						<option value='PE'         >Pernambuco</option>
						<option value='BA'         >Bahia</option>
						<option value='BR-NEES'    >Nordeste + E.S.</option>
						<option value='BR-NCO'     >Norte + C.O.</option>
					<? } else {?>
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
					<? }?>
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
					<input name="codigo_posto_tab" id="codigo_posto_tab"  class="input" value='<?echo $codigo_posto_tab;?>'  type="text" size="15" maxlength="15">
				</td>
				<td align='left'><strong>Nome:</strong></td>
				<td align='left'>
					<input type='hidden' name='posto_tab' value="<? echo $posto_tab; ?>">
					<input name="posto_nome_tab" id="posto_nome_tab"  class="input" value='<?echo $posto_nome_tab ;?>'  type="text" size="35" maxlength="500">
				</td>
				<td align='left'><strong>Distancia Km(ida/volta):</strong></td>
				<td><input type='text' name='posto_km_tab' class="input"></td>
			</tr>
		<tr>
			<td colspan='6'>
			<?
			if(strlen($callcenter)==0){
				if ($login_fabrica == 14 or $login_fabrica == 43 or $login_fabrica == 66 or $login_fabrica == 52 or $login_fabrica == 30 or $login_fabrica == 85) {
					$checked = "CHECKED";
					$display = "block";
				}
				else {
					$display = "none";
				}
				echo "<tr><td align='left' colspan='6'>";
				echo "<strong><input type='checkbox' name='abre_os' id='abre_os' value='t' onClick='verificarImpressao(this)' $checked> Abrir OS para o esta Autorizada</strong>";
				echo "<div id='imprimir_os' style='display:$display'><strong>&nbsp;&nbsp;&nbsp;&nbsp;<input type='checkbox' name='imprimir_os' value='t'> Imprimir OS</strong></div>";
				echo "</td></tr>";
			}
			?>
			</td>
		</tr>
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>

			<tr>
				<td align='left' valign='top'><strong>Descrição:</strong></td>
				<td align='left' colspan='5'>
					<TEXTAREA NAME="reclamado_produto_x" ROWS="6" COLS="110"  class="input" style='display: none;font-size:10px' <? echo $read; ?>>
					<?
						#94971
						if($_GET['herdar']=='sim' AND $login_fabrica==59){
							$sql2 ="SELECT		tbl_hd_chamado_extra.reclamado
									FROM tbl_hd_chamado
									JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
									LEFT JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
									JOIN tbl_admin  on tbl_hd_chamado.atendente = tbl_admin.admin
									LEFT JOIN tbl_posto on tbl_hd_chamado_extra.posto = tbl_posto.posto
									LEFT JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto  and tbl_posto_fabrica.fabrica = 59
									LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto
									LEFT JOIN tbl_revenda on tbl_revenda.revenda = tbl_hd_chamado_extra.revenda
									LEFT JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
									LEFT JOIN tbl_os on tbl_os.os = tbl_hd_chamado_extra.os
									WHERE tbl_hd_chamado.fabrica_responsavel = 59
									AND tbl_hd_chamado.hd_chamado = $Id";
							$res2 = pg_exec($con,$sql2);

							if(pg_numrows($res2)>0){
								$reclamado2       = pg_result($res2,0,reclamado);
							}
							echo $reclamado2;
						}
					?>
					</TEXTAREA>
					<TEXTAREA NAME="reclamado_produto" ROWS="6" COLS="110"  class="input" style='font-size:10px' <? echo $read; ?>><?echo $reclamado ;?></TEXTAREA>
				</td>
			</tr>
			</table>
		<tr>
			<td colspan='6' align='center'><input type='submit' value='Gravar' name='btn_acao'></td>
		</tr>
		</table>
	<? } ?>
</table>
</form>
<?php } else {
		
		$sql = "SELECT	nome        ,
						cnpj        ,
						ie          ,
						email       ,
						fone        ,
						cep         ,
						endereco    ,
						numero      ,
						complemento ,
						bairro      ,
						cidade      ,
						estado
					FROM tbl_cliente_admin 
					WHERE cliente_admin = $login_cliente_admin 
					AND fabrica = $login_fabrica";
		//echo $sql;
		$res = pg_query($con,$sql);
		$dados = pg_fetch_array($res);
?>
	
	<form name="frm_callcenter" method="post" action="<?$PHP_SELF?>" enctype="multipart/form-data">
		<input name="callcenter"  type="hidden" value='<?echo $callcenter;?>'>
		<input name="cod_cliente" type="hidden" value='<?echo $dados['cliente_admin'];?>'>

	<table width="700px" border="0" align="center" cellpadding="0" cellspacing="1" class='formulario'>
		<tr>
			<td class='titulo_tabela' colspan='3'>Cadastro de Atendimento</td>
		</tr>
		<tr>
			<td width='300px'>&nbsp;</td>
			<td width='200px'>&nbsp;</td>
			<td width='200px'>&nbsp;</td>
		</tr>
		<tr>
			<td>
				Nome<br>
				<input type="text" name="nome" value="<?php echo $dados['nome']?>" class='frm' style='width: 270px' readonly /> 
			</td>
			<td>
				CNPJ<br>
				<input type="text" name="cnpj" value="<?php echo $dados['cnpj']?>" class='frm' style='width: 170px' readonly /> 
			</td>
			<td>
				IE<br>
				<input type="text" name="ie" value="<?php echo $dados['ie']?>" class='frm' style='width: 170px' readonly /> 
			</td>
		</tr>
		<tr>
			<td>
				E-mail<br>
				<input type="text" name="email" value="<?php echo $dados['email']?>" class='frm' style='width: 270px' readonly /> 
			</td>
			<td>
				Telefone<br>
				<input type="text" name="telefone" value="<?php echo $dados['fone']?>" class='frm' style='width: 170px' readonly /> 
			</td>
			<td>
				CEP<br>
				<input type="text" name="cep" value="<?php echo $dados['cep']?>" class='frm' style='width: 170px' readonly /> 
			</td>
		</tr>
		<tr>
			<td>
				Endereço<br>
				<input type="text" name="endereco" value="<?php echo $dados['endereco']?>" class='frm' style='width: 270px' readonly /> 
			</td>
			<td>
				Número<br>
				<input type="text" name="numero" value="<?php echo $dados['numero']?>" class='frm' style='width: 170px' readonly /> 
			</td>
			<td>
				Complemento<br>
				<input type="text" name="complemento" value="<?php echo $dados['complemento']?>" class='frm' style='width: 170px' readonly /> 
			</td>
		</tr>
		<tr>
			<td>
				Bairro<br>
				<input type="text" name="bairro" value="<?php echo $dados['bairro']?>" class='frm' style='width: 270px' readonly /> 
			</td>
			<td>
				Cidade<br>
				<input type="text" name="cidade" value="<?php echo $dados['cidade']?>" class='frm' style='width: 170px' readonly /> 
			</td>
			<td>
				Estado<br>
				<input type="text" name="estado" value="<?php echo $dados['estado']?>" class='frm' style='width: 170px' readonly /> 
			</td>
		</tr>
	</table>

	<br><br>
	<table width="700px" border="0" align="center" cellpadding="0" cellspacing="1" class='formulario'>
		<tr>
			<td class='titulo_tabela' colspan='6'>Informação do Produto</td>
		</tr>
		<tr>
			<td width='165px'>&nbsp;</td>
			<td width='200px'>&nbsp;</td>
			<td width='135px'>&nbsp;</td>
			<td width='20px'>&nbsp;</td>
			<td width='150px'>&nbsp;</td>
			<td width='20px'>&nbsp;</td>
		</tr>

		<tr>
			<td colspan='6'>
				Tipo Atendimento<br>
				<select name='tipo_atedimento' class='frm' style='width: 145px' onchange='mostraAnexo3(this.options[this.selectedIndex].value);'>
					<option value='0' selected>&nbsp;</option>
					<?php
						$sql = pg_query($con,"SELECT tipo_atendimento,descricao FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica}");
						while($atendimento = pg_fetch_array($sql)){
							$selected = ($atendimento[0] == $tipo_atedimento) ? " selected = 'selected' " : "";
							echo "<option value='{$atendimento[0]}' {$selected}>{$atendimento[1]}</option>";
						}
					?>
			</td>
		</tr>
		<tr>
			<td colspan='6' style='padding: 0'>
				<table cellpadding='0' cellspacing='0' border='0'>
					<tr>
						<td>
							Nota Fiscal de Envio(PDF ou JPEG)<br>
							<input type="file" name="anexo_1" class='frm' />
						</td>
						<td>
							(Arquivo XML) Caso Seja DANFE<br>
							<input type="file" name="anexo_2" class='frm' />
						</td>
						<td>
							<span class='anexo_3' style='display: none;' >
								Cópia Da Nota Fiscal de Compra (Garantia)<br>
								<input type="file" name="anexo_3" class='frm' />
							</span>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td>
				Data da NF<br>
				<input type="text" name="data_nf" rel='data' value="<?php echo $data_nf?>" class='frm' style='width: 123px' />
			</td>
			<td>
				Número da NF<br>
				<input type="text" name="numero_nf" value="<?php echo $numero_nf?>" class='frm' style='width: 110px' /> 
			</td>
			</td>
			<td colspan='3'>
				Código de Postagem<br>
				<input type="text" name="codigo_postagem" value="<?php echo $codigo_postagem?>" class='frm' style='width: 160px' />
			</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td colspan='5'>
				Consumidor Final<br>
				<input type="text" name="consumidor_final" value="<?php echo $consumidor_final?>" class='frm' style='width: 275px' />
			</td>
			<td align='right' style='padding-right: 10px'>
				<input type='button' name='addlinha' value=' + ' onclick='add_linha_bosch_security(<?=$i?>)' style='width: 30px; text-align: center' />
			</td>

		</tr>
			<tr>
				<td colspan='6'>
					<table width="700px" border="0" align="center" cellpadding="0" cellspacing="1" class='formulario'  name='tabela_itens' id='tabela_itens'>
						<tbody>
						<?php
							if (strlen($callcenter)>0) {

								$sql_produto = "SELECT produto,descricao,referencia,serie,defeito_reclamado,defeito_reclamado_descricao from tbl_hd_chamado_item join tbl_produto using(produto) where hd_chamado = $callcenter order by hd_chamado_item ";

								$res_produto = pg_query($con,$sql_produto);
								$qtde_produto = pg_num_rows($res_produto);
							}

							if (strlen($qtde_produto) == 0)
								$qtde_produto = 1;

							for ( $i = 1 ; $i <= $qtde_produto ; $i++ ) {

								$serie							= $_POST['serie_'.$i];
								$produto						= $_POST['produto_'.$i];
								$descricao						= $_POST['descricao_'.$i];
								$quantidade						= $_POST['quantidade_'.$i];
								$defeito_reclamado_descricao	= $_POST['defeito_reclamado_descricao_'.$i];
								$defeito_reclamado	= $_POST['defeito_reclamado_'.$i];

								if($quantidade < 1)
									$quantidade		= 1;
						?>
						<tr>
							<td style='padding: 0; width: 95px'>
								<?php if($i == 1) echo "Modelo<br>";?>
								<input name="produto_<?=$i?>"  class="frm" value='<? echo $produto ;?>' type="text" maxlength="15"  style='width: 70px' />
								<img  src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer' onclick="javascript: fnc_pesquisa_produto2 (document.frm_callcenter.produto_<?=$i;?>, document.frm_callcenter.descricao_<?=$i;?>, 'referencia', '')" >
							</td>
							<td style='padding: 0;width: 190px '>
								<?php if($i == 1) echo "Descrição<br>";?>
								<input name="descricao_<?=$i?>"  class="frm" value='<?php echo $descricao ;?>'type="text" maxlength="500"  style='width: 165px' />
								<img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer' onclick="javascript: fnc_pesquisa_produto2 (document.frm_callcenter.produto_<?=$i;?>, document.frm_callcenter.descricao_<?=$i;?>, 'descricao', '')"">
							</td>
							<td style='padding: 0; width: 60px '>
								<?php if($i == 1) echo "Série/Lote<br>";?>
								<input name="serie_<?=$i;?>" id="serie_<?=$i;?>" class="frm" value="<?php echo $serie;?>" style='width: 50px' />
							</td>
							<td style='padding: 0; width: 25px '>
								<?php if($i == 1) echo "Qtd<br>";?>
								<input name="quantidade_<?=$i?>" class="frm" value='<?php echo $quantidade ;?>' maxlength='2' style='width: 15px' onblur='javascript: if(document.frm_callcenter.quantidade_<?=$i?>.value > 1){ alert("Atenção se os produtos desta linha tiverem série/lote ou defeitos diferentes devem ser digitados em linhas diferentes.");}' />
							</td>
							<td style='padding: 0; width: 160px'>
								<?php if($i == 1) echo "Defeito Reclamado<br>";?>
								<select name='defeito_reclamado_<?php echo $i;?>' id='defeito_reclamado_<?php echo $i;?>' style='width: 155px;' class="frm" onfocus='atualizaDefeito(document.frm_callcenter.produto_<?=$i;?>.value,<?=$i;?>,"");'>
									<?php
										if(strlen($defeito_reclamado) > 0){
											echo "	<script type='text/javascript'>
														atualizaDefeito(document.frm_callcenter.produto_$i.value,$i,$defeito_reclamado);
													</script>";
										}
									?>
									<option value='0' id='opcoes' > - selecione -</option>
								</select>
							</td>
							<td style='padding: 0; width: 150px '>
								<?php if($i == 1) echo "Descrição Defeito<br>";?>
								<input class='frm' type='text' name='defeito_reclamado_descricao_<?php echo $i;?>' id='defeito_reclamado_<?php echo $i;?>' value='<?php echo $defeito_reclamado_descricao;?>'  style='width: 140px; margin-right: 5px' />
							</td>
						</tr>
						<?php }?>
						</tbody>
					</table>
				</td>
			</tr>
		<input type='hidden' name='qtde_produto' value='<? echo $i= $i-1;?>' id='qtde_produto' />
	</table>
		
	<?php
		$posto = pg_fetch_array(pg_query("SELECT codigo_posto, nome FROM tbl_posto_fabrica join tbl_posto ON tbl_posto.posto= tbl_posto_fabrica.posto and fabrica = $login_fabrica WHERE tbl_posto_fabrica.posto = 17673 and fabrica = $login_fabrica;"));	
	?>
	<br><br>
	<table width="700px" border="0" align="center" cellpadding="0" cellspacing="1" class='formulario'>
		<tr>
			<td class='titulo_tabela' colspan='3'>Posto</td>
		</tr>
		<tr>
			<td width='180px'>&nbsp;</td>
			<td width='233px'>&nbsp;</td>
			<td width='*'>&nbsp;</td>
		</tr>
		<tr>
			<td>
				Código<br>
				<input type="text" name="posto_codigo" value="<?php echo $posto[0];?>" class='frm' style='width: 130px' readonly /> 
			</td>
			</td>
			<td colspan='2'>
				Nome<br>
				<input type="text" name="posto_nome" id='data' value="<?php echo $posto[1];?>" class='frm' style='width: 223px' readonly />
			</td>
		</tr>
		<tr>
			<td colspan='3'>
				Descrição<br>
				<textarea name='posto_descricao' style='width: 670px; height: 80px' class='frm'><?php echo $posto_descricao; ?></textarea>
			</td>
		<tr>
			<td colspan='3'>
				<input type="checkbox" value='t' name="pre_os" checked disabled />&nbsp;&nbsp;Abrir Pré OS para o Posto Autorizado
			</td>
		</tr>
		<tr>
			<td class='btn_submit' colspan='3'><input type='submit' value='&nbsp;&nbsp;Gravar&nbsp;&nbsp;' name='btn_acao'></td>
		</tr>
	</table>
</form>
<?php 
	}
	
include "rodape.php";
?>
