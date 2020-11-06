<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';
include_once('../anexaNF_inc.php');
$msg_erro  = "";
$qtde_item = 150;
$qtde_item_visiveis = 10;

$btn_acao = trim (strtolower ($_POST['btn_acao']));


if (strlen($_GET['os_metal'])  > 0) $os_metal = trim($_GET['os_metal']);
if (strlen($_POST['os_metal']) > 0) $os_metal = trim($_POST['os_metal']);

if ($btn_acao == "gravar") {

	$posto_codigo = trim($_POST['posto_codigo']);
	if (strlen($posto_codigo) == 0) {
		$msg_erro .= " Digite o Código do Posto.";
	}else{
		$posto_codigo = str_replace("-","",$posto_codigo);
		$posto_codigo = str_replace(".","",$posto_codigo);
		$posto_codigo = str_replace("/","",$posto_codigo);
		$posto_codigo = substr($posto_codigo,0,14);
	}

	$hd_chamado = trim($_POST['hd_chamado']);

	if (strlen($posto_codigo) > 0) {
		$sql =	"SELECT tbl_posto.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica	ON tbl_posto.posto = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_posto_fabrica.codigo_posto = '$posto_codigo';";
		$res = pg_query ($con,$sql);
		if (pg_num_rows($res) > 0) {
			$posto = pg_fetch_result ($res,0,0);
		}else{
			$msg_erro .= " Posto $posto_codigo não cadastrado.";
		}
	}

	$consumidor_revenda = $_POST['consumidor_revenda'];
	if($login_fabrica ==1 ){
		$consumidor_revenda= "C";
	}
	if (strlen($consumidor_revenda)==0 ){
		//$msg_erro = "Selecione Consumidor ou Revenda.:";
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
		$consumidor_nome = "";
	}

	$consumidor_fone = $_POST['consumidor_fone'];
	if (strlen($consumidor_fone) > 0) {
		$consumidor_fone = $consumidor_fone ;
	}else{
		$consumidor_fone = "";
	}

	$consumidor_endereco  = $_POST['consumidor_endereco'];
	if (strlen($consumidor_endereco) > 0) {
		$consumidor_endereco = $consumidor_endereco ;
	}else{
		$consumidor_endereco = "";
	}

	$consumidor_numero  = $_POST['consumidor_numero'];
	if (strlen($consumidor_numero) > 0) {
		$consumidor_numero = $consumidor_numero ;
	}else{
		$consumidor_numero = "";
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
		$res = pg_query($con,$sql);
		if(pg_fetch_result($res,0,0) == 'f'){
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

	if (strlen($_POST['condicao']) > 0) {
		$xcondicao = $_POST['condicao'];
	}else{
		$xcondicao = " NULL ";
	}

	if (strlen($_POST['hd_chamado']) > 0) {
		$hd_chamado = $_POST['hd_chamado'];
	}else{
		$hd_chamado = "NULL" ;
	}

	$tipo_os_cortesia= $_POST['tipo_os_cortesia'];

	if(strlen($tipo_os_cortesia) ==0) {
		$msg_erro= "Selecione o tipo de Cortesia";
	}

	if ($xdata_nf == null){
		if ($tipo_os_cortesia == 'Garantia') $msg_erro .= " Digite a data de compra.";
	}

	if ($tipo_os_cortesia == 'Garantia') {
		if (strlen($nota_fiscal) == 0) $msg_erro .= " Digite a Nota Fiscal.";
		if ($xdata_nf == "null")        $msg_erro .= " Digite a Data da Compra.";
	}

	if (($tipo_os_cortesia == 'Sem Nota Fiscal' OR $tipo_os_cortesia == 'Fora de Garantia' OR $tipo_os_cortesia == 'Promotor') AND (strlen($nota_fiscal)>0 OR (strlen($xdata_nf)>0 AND $xdata_nf<>"null"))) {
		$msg_erro .= " Os dados da nota fiscal não devem ser informados para este tipo de Cortesia.";
	}

	if (($tipo_os_cortesia == 'Garantia' OR $tipo_os_cortesia == 'Mau uso' OR $tipo_os_cortesia == 'Devolução de valor') AND ((strlen($nota_fiscal) == 0 OR (strlen($xdata_nf) == 0 AND $xdata_nf == "null")) OR ((strlen($xrevenda_nome) == 0) OR (strlen($xrevenda_cnpj) == 0)))) {
		$msg_erro .= " Os dados da revenda devem ser informados para este tipo de Cortesia.";
	}
	
	if(strlen($hd_chamado) > 0 and $hd_chamado <> 'NULL'){
		
		$sql = "SELECT hd_chamado FROM tbl_hd_chamado WHERE hd_chamado = $hd_chamado and fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res) == 0){
			$msg_erro = "Chamado não Encontrado";
		}
	}


	if (strlen ($msg_erro) == 0) {

		$res = pg_query ($con,"BEGIN TRANSACTION");

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
						obs                ,
						posto              ,
						contrato           ,
						condicao           ,
						os_geo             ,
						fisica_juridica    ,
						cortesia           ,
						tipo_os_cortesia   ,
						hd_chamado         ,
						admin
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
						$xobs                             ,
						$posto                            ,
						$xcontrato                        ,
						$xcondicao                        ,
						't'                               ,
						$xfisica_juridica                 ,
						't'                               ,
						'$tipo_os_cortesia'               ,
						$hd_chamado                       ,
						$login_admin
					)";
		}else{

			$sql = "UPDATE tbl_os_revenda SET ";

			if($login_fabrica == 1){
				$sql .= "data_abertura         = $xdata_abertura ,";
			}

			$sql .= "	consumidor_nome       = '$consumidor_nome'        ,
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
						obs                = $xobs                       ,
						contrato           = $xcontrato                  ,
						condicao           = $xcondicao                  ,
						os_geo             = 't'                         ,
						tipo_os_cortesia   = '$tipo_os_cortesia'         ,
						retorno_visita     = '$retorno_visita'           ,
						fisica_juridica             = $xfisica_juridica  ,
						hd_chamado         = $hd_chamado                 ,
						admin              = $login_admin
					WHERE os_revenda = $os_metal 
					AND   fabrica    = $login_fabrica
					AND   posto      = $posto ";
		}
		//echo nl2br($sql);die;
		$res = pg_query ($con,$sql);
		
		$msg_erro .= pg_errormessage($con);
		
		if (strlen($msg_erro) == 0 and strlen($os_metal) == 0) {
			$res           = pg_query ($con,"SELECT CURRVAL ('seq_os_revenda')");
			$os_metal      = pg_fetch_result ($res,0,0);
			$msg_erro      .= pg_errormessage($con);
		}


		if (strlen($msg_erro) == 0) {
			$qtde_item = $_POST['qtde_item'];

			for ($i = 0 ; $i < $qtde_item ; $i++) {
				$novo                     = $_POST["novo_".$i];
				$os_revenda_item          = $_POST["os_revenda_item_".$i];
				$referencia               = $_POST["produto_referencia_".$i];
				$defeito_reclamado        = $_POST["defeito_reclamado_".$i];

				if (strlen($defeito_reclamado) == 0 and strlen($referencia)> 0){
					$msg_erro .= "Selecione o defeito reclamado.";
				}

				if ($defeito_reclamado == '0' and strlen($referencia)> 0) {
					$msg_erro .= "Selecione o defeito reclamado . <BR>";
				}


				if (strlen($os_revenda_item) > 0 AND strlen ($referencia)== 0 AND $novo == 'f') {
					$sql = "DELETE FROM tbl_os_revenda_item
							WHERE  os_revenda      = $os_metal
							AND    os_revenda_item = $os_revenda_item";
					$res = @pg_query($con,$sql);
					$msg_erro.= pg_errormessage($con);
				}

				if (strlen($msg_erro) > 0) {
					$os_metal="";
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
					$res = pg_query ($con,$sql);
					if (pg_num_rows ($res) == 0) {
						$msg_erro = "Produto $referencia não cadastrado";
						$linha_erro = $i;
					}else{
						$produto   = pg_fetch_result ($res,0,produto);
					}

					
					if (strlen($defeito_reclamado) == 0 and strlen($referencia)> 0) {
						$msg_erro .= "Defeito reclamado do produto é obrigatório.";
					}else{
						$xdefeito_reclamado = $defeito_reclamado;
					}

					if (strlen ($msg_erro) > 0) {
						$os_metal="";
						break;
					}
					if ($novo == 't'){
						$sql = "INSERT INTO tbl_os_revenda_item (
									os_revenda                ,
									produto                   ,
									defeito_reclamado         ,
									nota_fiscal               ,
									data_nf                   
								) VALUES (
									$os_metal            ,
									$produto                  ,
									$defeito_reclamado        ,
									$xnota_fiscal             ,
									$xdata_nf                 
								)";
					}else{
						$sql = "UPDATE tbl_os_revenda_item SET
									produto                     = $produto                  ,
									defeito_reclamado           = $defeito_reclamado        ,
									nota_fiscal                 = $xnota_fiscal             ,
									data_nf                     = $xdata_nf                  
								WHERE  os_revenda      = $os_metal 
								AND    os_revenda_item = $os_revenda_item";
					}
					$res = pg_query ($con,$sql);
					$msg_erro .= pg_errormessage($con);
					if(strlen($msg_erro) > 0){
						$msg_erro = substr($msg_erro,6);
					}
					if (strlen ($msg_erro) > 0) {						
						$os_metal="";
						break ;
					}
				}
			}
		}elseif (strlen ($msg_erro) > 0) {
			$os_metal="";
			exit;
		}

		if (strlen($msg_erro) == 0 AND strlen ($revenda_cnpj) > 0) {
			$sql  = "SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj";
			$res1 = pg_query ($con,$sql);

			if (pg_num_rows($res1) > 0) {
				$revenda = pg_fetch_result ($res1,0,revenda);
				$sql = "UPDATE tbl_revenda SET
							nome		= $xrevenda_nome          ,
							cnpj		= $xrevenda_cnpj          
						WHERE tbl_revenda.revenda = $revenda";
				$res3 = @pg_query ($con,$sql);
				$msg_erro .= pg_errormessage($con);
							
			}else{
				$sql = "INSERT INTO tbl_revenda (
							nome,
							cnpj
						) VALUES (
							$xrevenda_nome ,
							$xrevenda_cnpj 
						)";
				$res3 = @pg_query ($con,$sql);
				$msg_erro .= pg_errormessage ($con);
				$monta_sql .= "12: $sql<br>$msg_erro<br><br>";

				$sql = "SELECT currval ('seq_revenda')";
				$res3 = @pg_query ($con,$sql);
				$revenda = @pg_fetch_result ($res3,0,0);
			}

			$sql = "UPDATE tbl_os_revenda SET revenda = $revenda WHERE os_revenda = $os_metal AND fabrica = $login_fabrica";
			$res = @pg_query ($con,$sql);
		}

		if(strlen($msg_erro) ==0){
			$sql = "UPDATE tbl_os_revenda SET
							sua_os = '$os_metal'
					WHERE tbl_os_revenda.os_revenda  = $os_metal
					AND   tbl_os_revenda.posto       = $posto
					AND   tbl_os_revenda.fabrica     = $login_fabrica ";
			$res = pg_query ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if(strlen($os_metal) > 0 AND strlen($msg_erro) ==0){
			$sql = "SELECT fn_valida_os_revenda (os_revenda,posto,fabrica)
					FROM tbl_os_revenda
					WHERE os_revenda= $os_metal
					AND   posto = $posto
					AND   fabrica = $login_fabrica ";
			$res = @pg_query ($con,$sql);
			$msg_erro = @pg_errormessage($con);
			if(strlen($msg_erro) > 0 ){
				$msg_erro = substr($msg_erro,6);
			}
			if (strpos ($msg_erro,"nao cadastrada") > 0) {
				$msg_erro = " Erro inesperado, grave novamente.";
			}
		}

		if ( strlen($msg_erro) == 0 && $login_fabrica == 1) {

			$anexou = anexaNF( "r_" . $os_metal, $_FILES['foto_nf']);
			if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' é que executou OK

		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_query ($con,"COMMIT TRANSACTION");

			header ("Location: os_metal_finalizada.php?os_metal=$os_metal");
			exit;
		}else{
			$os_metal = "";
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
		}
	}
}

/* ====================  APAGAR  =================== */
if ($btn_acao == "apagar") {
	if(strlen($os_metal) > 0){
		$sql = "DELETE FROM tbl_os_revenda 
				WHERE tbl_os_revenda.os_revenda = $os_metal 
				AND   tbl_os_revenda.fabrica    = $login_fabrica";
		$res = pg_query ($con,$sql);
		
		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);
		
		if (strlen ($msg_erro) == 0) {
			header("Location: $PHP_SELF");
			exit;
		}
	}
}


$title			= "CADASTRO DE ORDEM DE SERVIÇO - METAIS SANITÁRIOS"; 
$layout_menu	= "callcenter";

include "cabecalho.php";
include "javascript_pesquisas.php";

?>
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='ajax_cep.js'></script>
<!-- <script type="text/javascript" src="js/jquery-1.3.2.js"></script> -->
<script language='javascript' src='js/jquery-1.4.2.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>

<script type="text/javascript" src="js/jquery.blockUI_2.39.js"></script>
<script type="text/javascript" src="js/plugin_verifica_servidor.js"></script>

<script language="JavaScript">

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
		$("input[rel='data']").maskedinput("99/99/9999");
		$("input[rel='fone']").maskedinput("(99) 9999-9999");
		$("input[rel='cep']").maskedinput("99999-999");
		$("input[rel='cnpj']").maskedinput("99.999.999/9999-99");
		
		$("input[name=nota_fiscal]").numeric({allow:"-"});
		$("#revenda_cnpj").numeric({allow:"./-"});
		$("#consumidor_cnpj").numeric({allow:"./-"});
		$("#consumidor_cidade").alpha();

	});


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
		else{
			alert("Informe toda ou parte da informação para realizar a pesquisa");
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
		}else{
			janela.proximo = document.frm_os.data_abertura;
		}
		janela.focus();
	}
	 else{
        alert("Informar toda ou parte da informação para realizar a pesquisa!");
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

	function fnc_pesquisa_revenda (campo, tipo) {
		var url = "";
		if (tipo == "nome") {
			url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
		}
		if (tipo == "cnpj") {
			url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
		}
		if (campo.value != "") {
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
		else{
			alert("Informar toda ou parte da informação para realizar a pesquisa!");
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


	
	function qtdeItens(campo){
		var linha = 0;
		if (campo.value > 0){
			$(".tabela tr").each( function (){
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
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.espaco{
	padding-left:10px;
}
</style>

<? 

if (strlen($os_metal) > 0) {
	$sql = "SELECT  
					tbl_os_revenda.os_revenda,
					TO_CHAR(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura,
					tbl_os_revenda.posto,
					tbl_os_revenda.qtde_km,
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
					tbl_os_revenda.hd_chamado           ,
					tbl_os_revenda.tipo_os_cortesia     ,
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
	$res = pg_query($con, $sql);
	//echo "sql: $sql";
	if (pg_num_rows($res) > 0) {
		$os_metal       = trim(pg_fetch_result($res,0,os_revenda));
		$consumidor_revenda  = trim(pg_fetch_result($res,0,consumidor_revenda));
		$data_abertura       = trim(pg_fetch_result($res,0,data_abertura));
		$consumidor_nome     = trim(pg_fetch_result($res,0,consumidor_nome));
		$consumidor_cnpj     = trim(pg_fetch_result($res,0,consumidor_cnpj));
		$consumidor_cidade   = trim(pg_fetch_result($res,0,consumidor_cidade));
		$consumidor_estado   = trim(pg_fetch_result($res,0,consumidor_estado));

		$consumidor_fone       = trim(pg_fetch_result($res,0,consumidor_fone));
		$consumidor_email      = trim(pg_fetch_result($res,0,consumidor_email));
		$consumidor_endereco   = trim(pg_fetch_result($res,0,consumidor_endereco));
		$consumidor_numero     = trim(pg_fetch_result($res,0,consumidor_numero));
		$consumidor_cep        = trim(pg_fetch_result($res,0,consumidor_cep));
		$consumidor_complemento= trim(pg_fetch_result($res,0,consumidor_complemento));
		$consumidor_bairro     = trim(pg_fetch_result($res,0,consumidor_bairro));

		$qtde_km             = trim(pg_fetch_result($res,0,qtde_km));
		$valor_adicional     = trim(pg_fetch_result($res,0,valor_adicional));
		$valor_adicional_justificativa= trim(pg_fetch_result($res,0,valor_adicional_justificativa));
		
		$obs                 = trim(pg_fetch_result($res,0,obs));
		$contrato            = trim(pg_fetch_result($res,0,contrato));
		$nota_fiscal         = trim(pg_fetch_result($res,0,nota_fiscal));
		$data_nf             = trim(pg_fetch_result($res,0,data_nf));
		$retorno_visita      = trim(pg_fetch_result($res,0,retorno_visita));
		$fisica_juridica	 = trim(pg_fetch_result($res,0,fisica_juridica));
		$revenda             = trim(pg_fetch_result($res,0,revenda));
		$revenda_nome        = trim(pg_fetch_result($res,0,revenda_nome));
		$revenda_cnpj        = trim(pg_fetch_result($res,0,revenda_cnpj));
		$revenda_fone        = trim(pg_fetch_result($res,0,revenda_fone));
		$revenda_endereco    = trim(pg_fetch_result($res,0,revenda_endereco));
		$revenda_numero      = trim(pg_fetch_result($res,0,revenda_numero));
		$revenda_complemento = trim(pg_fetch_result($res,0,revenda_complemento));
		$revenda_bairro      = trim(pg_fetch_result($res,0,revenda_bairro));
		$revenda_cep         = trim(pg_fetch_result($res,0,revenda_cep));
		$tipo_atendimento    = trim(pg_fetch_result($res,0,tipo_atendimento));
		$revenda_cidade      = trim(pg_fetch_result($res,0,revenda_cidade));
		$revenda_estado      = trim(pg_fetch_result($res,0,revenda_estado));
		$posto_codigo        = trim(pg_fetch_result($res,0,codigo_posto));
		$posto_nome          = trim(pg_fetch_result($res,0,nome));
		$posto_cidade        = trim(pg_fetch_result($res,0,posto_cidade));
		$posto_estado        = trim(pg_fetch_result($res,0,posto_estado));
		$hd_chamado          = trim(pg_fetch_result($res,0,hd_chamado));

		$taxa_visita         = trim(pg_fetch_result($res,0,taxa_visita));
		$hora_tecnica        = trim(pg_fetch_result($res,0,hora_tecnica));
		$visita_por_km       = trim(pg_fetch_result($res,0,visita_por_km));

		$taxa_visita			= trim(pg_fetch_result ($res,0,taxa_visita));
		$visita_por_km			= trim(pg_fetch_result ($res,0,visita_por_km));
		$valor_por_km			= trim(pg_fetch_result ($res,0,valor_por_km));
		$hora_tecnica			= trim(pg_fetch_result ($res,0,hora_tecnica));
		$valor_diaria			= trim(pg_fetch_result ($res,0,valor_diaria));
		$veiculo				= trim(pg_fetch_result ($res,0,veiculo));
		$regulagem_peso_padrao	=trim(pg_fetch_result ($res,0,regulagem_peso_padrao));

		$desconto_deslocamento	= trim(pg_fetch_result ($res,0,desconto_deslocamento));
		$desconto_hora_tecnica	= trim(pg_fetch_result ($res,0,desconto_hora_tecnica));
		$desconto_diaria		= trim(pg_fetch_result ($res,0,desconto_diaria));
		$condicao				= trim(pg_fetch_result ($res,0,condicao));
		$tipo_os_cortesia		= trim(pg_fetch_result ($res,0,tipo_os_cortesia));

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
	$taxa_visita          = $_POST['taxa_visita'];
	$hora_tecnica         = $_POST['hora_tecnica'];
	$cobrar_percurso      = $_POST['cobrar_percurso'];
	$visita_por_km        = $_POST['visita_por_km'];
	$diaria               = $_POST['diaria'];
	$obs                  = $_POST['obs'];
	$fisica_juridica      = $_POST['fisica_juridica'];
	$retorno_visita       = $_POST['retorno_visita'];
	$hd_chamado           = $_POST['hd_chamado'];
	$tipo_os_cortesia     = $_POST['tipo_os_cortesia'];

?>
<table border="0" cellpadding="0" cellspacing="0" align="center"  width = '700'>
<tr>
	<td valign="middle" align="center" class='msg_erro'>
		<? echo $msg_erro ?>
	</td>
</tr>
</table>
<?
}

?>

<form name="frm_os" id='frm_os' method="post" action="<? echo $PHP_SELF ?>" enctype="multipart/form-data">
<input type='hidden' name='os_metal' value='<?=$os_metal?>'>


<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" class="formulario">
	<tr class='titulo_tabela'>
		<td> Cadastrar Ordem de Serviço - Metais Sanitários </td>
	</tr>
	<tr>
		<td valign="top" align="left">
		<?
			if ($consumidor_revenda == 'R'){
				$esconde_cliente = " style='display:none'; ";
			}
		?>
		<div id='tbl_cliente' <?=$esconde_cliente?>>
			<input type='hidden' name='cliente_cliente'>
			<input type='hidden' name='consumidor_complemento'>

			
			<table width="100%" border="0" cellspacing="2" cellpadding="2" class="formulario">
				<tr>
					<td class='espaco' width='250'>
						Código do Posto
						<br />
						<input class="frm" type="text" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>">&nbsp;<img src='imagens/lupa.png'
						border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2
						(document.frm_os.posto_codigo,document.frm_os.posto_nome,'codigo')">
					</td>

					<td nowrap>
						Nome Posto
						<br>
						<input class="frm" type="text" name="posto_nome" size="50" value="<? echo $posto_nome ?>" >&nbsp;<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_os.posto_codigo,document.frm_os.posto_nome,'nome')" style="cursor:pointer;"></A>
					</td>
				</tr>
				<tr>
					<td width='250' class='espaco'>
						Número Chamado
						<br />
						<input class="frm" type="text" name="hd_chamado" size="15" value="<? echo $hd_chamado ?>">
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">Tipo da OS Cortesia</font>
						<br>
						<select name='tipo_os_cortesia' class="frm">
						<? if(strlen($tipo_os_cortesia) == 0) echo "<option value=''></option>"; ?>
						<option value='Garantia' <? if($tipo_os_cortesia == 'Garantia') echo "selected"; ?>>Garantia</option>
						<option value='Sem Nota Fiscal' <? if($tipo_os_cortesia == 'Sem Nota Fiscal') echo "selected"; ?>>Sem Nota Fiscal</option>
						<option value='Fora da Garantia' <? if($tipo_os_cortesia == 'Fora da Garantia') echo "selected"; ?>>Fora da Garantia</option>
						<option value='Mau uso' <? if($tipo_os_cortesia == 'Mau uso') echo "selected"; ?>>Mau uso</option>
						<option value='Devolução de valor' <? if($tipo_os_cortesia == 'Devolução de valor') echo "selected"; ?>>Devolução de valor</option>
					</select>
					</td>
				</tr>
			
			<table width="100%" border="0" cellspacing="2" cellpadding="2" class="formulario">
				<tr>
					<td colspan='6' align='center' class='subtitulo'>Dados do Cliente</td>
				</tr>
				<tr>
					<td class='espaco' width='250'>Nome<br>
						<input class="frm" type="text" name="consumidor_nome" size="35" maxlength="50" value="<? echo $consumidor_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)">&nbsp;
					</td>
					<td width='250'>
						CPF/CNPJ<br>
						<input class="frm" type="text" name="consumidor_cnpj" id="consumidor_cnpj" size="20" maxlength="18" value="<? echo $consumidor_cnpj ?>">&nbsp;
					</td>
					<td >
						Tipo Consumidor
						<br />
						<SELECT NAME="fisica_juridica" class='frm'>
							<OPTION></OPTION>
							<OPTION VALUE="F" <?if($fisica_juridica =='F') echo " SELECTED ";?>>Pessoa Física</OPTION>
							<OPTION VALUE="J" <?if($fisica_juridica =='J') echo " SELECTED ";?>>Pessoa Jurídica</OPTION>
						</SELECT>
					</td >
				</tr>
				<tr>
					<td class='espaco'>
						Telefone <br />
						<input class="frm" type="text" name="consumidor_fone" rel='fone' size="18" maxlength="16" value="<? echo $consumidor_fone ?>">
					</td>
					<td colspan="2">
						E-mail <br />
						<input class="frm" type="text" name="consumidor_email" size="35" maxlength="50" value="<? echo $consumidor_email ?>">
					</td>
				</tr>
			</table>
			
			<table width="100%" border="0" cellspacing="2" cellpadding="2" class="formulario">
				<tr>
					<td class='espaco' width='250'>CEP <br />
						<input class="frm addressZip" type="text" name="consumidor_cep" rel='cep' size="10" maxlength="10" value="<? echo $consumidor_cep ?>">
					</td>
					<td >Estado <br />
                        <select id="consumidor_estado" name="consumidor_estado" class="frm addressState">
                            <option value="" >Selecione</option>
                            <?php
                            #O $array_estados() está no arquivo funcoes.php
                            foreach ($array_estados() as $sigla => $nome_estado) {
                                $selected = ($sigla == $consumidor_estado) ? "selected" : "";

                                echo "<option value='{$sigla}' {$selected} >{$nome_estado}</option>";
                            }
                            ?>
                        </select>
                    </td>
					<td >Cidade <br />
                        <select id="consumidor_cidade" name="consumidor_cidade" class="frm addressCity" style="width:150px">
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
                        <!-- <input class="frm" type="text" name="consumidor_cidade" id="consumidor_cidade" size="20" maxlength="18" value="<? echo $consumidor_cidade ?>"> -->
                    </td>
				</tr>
				<tr>
					<td class='espaco'>Bairro <br />
						<input class="frm addressDistrict" type="text" name="consumidor_bairro" id="consumidor_bairro" size="20" maxlength="50" value="<? echo $consumidor_bairro ?>">
					</td>
					<td width='250'>Endereço <br />
                        <input class="frm address" type="text" name="consumidor_endereco" id="consumidor_endereco" size="28" maxlength="50" value="<? echo $consumidor_endereco ?>">
                    </td>
					<td >Número <br />
                        <input class="frm" type="text" name="consumidor_numero" id="consumidor_numero" size="4" maxlength="5" value="<? echo $consumidor_numero ?>">
                    </td>
				</tr>
			</table>
		</div>
			
<!-- Revenda -->
		
			<input type='hidden' name='revenda'>
			<input class="frm" type="hidden" name="revenda_fone" value="<? echo $revenda_fone ?>">
			<input class="frm" type="hidden" name="revenda_email" value="<? echo $revenda_email ?>" >
			<input class="frm" type="hidden" name="revenda_endereco" value="<? echo $revenda_endereco ?>">
			<input class="frm" type="hidden" name="revenda_numero" value="<? echo $revenda_numero ?>">
			<input class="frm" type="hidden" name="revenda_complemento"  value="<? echo $revenda_complemento ?>">
			<input class="frm" type="hidden" name="revenda_cep"  value="<? echo $revenda_cep ?>" >
			<input class="frm" type="hidden" name="revenda_bairro" value="<? echo $revenda_bairro ?>">
			<input class="frm" type="hidden" name="revenda_cidade" value="<? echo $revenda_cidade ?>">
			<input class="frm" type="hidden" name="revenda_estado" value="<? echo $revenda_estado ?>">
			<table width="100%" border="0" cellspacing="2" cellpadding="2">
				<tr class="menu_top">
					<td colspan='4' align='center' class='subtitulo'>Dados da Revenda</td>
				</tr>
				<tr>
					<td width='250' class='espaco'>
						CNPJ <br />
						<input class="frm" type="text" name="revenda_cnpj" id="revenda_cnpj" size="20" maxlength="18" value="<? echo $revenda_cnpj ?>">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor:pointer;'>
					</td>
					<td>
						Nome <br />
						<input class="frm" type="text" name="revenda_nome" size="35" maxlength="50" value="<? echo $revenda_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)">&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' style='cursor:pointer;'>
					</td>					
				</tr>
			</table>

			
			<table width="100%" border="0" cellspacing="3" cellpadding="2" class="formulario">
				<tr>
					<td class='espaco' width='250'>
						Data Abertura <br />
						<input class="frm" type="text" name="data_abertura" rel='data' size="12" maxlength='10' value="<? echo $data_abertura ?>">
					</td>
					<td width='250'>
						Nota Fiscal <br />
						<input class="frm" type="text" name="nota_fiscal" size="20" maxlength='20' value="<? echo $nota_fiscal ?>">
					</td>
					<td>
						Data da Compra <br />
						<input class="frm" type="text" name="data_nf" rel='data' size="12" maxlength='10' value="<? echo $data_nf ?>">
					</td> 
				</tr>
				<tr>
					
					<td colspan='3' class='espaco'>
						Tipo de Atendimento <br />
						<select name="tipo_atendimento" id='tipo_atendimento' style='width:230px;' class='frm'>
						<?
						$cond_tipo_atendimento = " 1=1 ";
						if($login_fabrica ==1){
							$cond_tipo_atendimento = " tipo_atendimento in(64,65,69) ";
						}

						$sql = "SELECT * 
								FROM tbl_tipo_atendimento 
								WHERE fabrica = $login_fabrica
								AND   ativo IS TRUE 
								AND   $cond_tipo_atendimento 
								ORDER BY tipo_atendimento ";
						$res = pg_query ($con,$sql) ;

						for ($i = 0 ; $i < pg_num_rows ($res) ; $i++ ) {
							echo "<option ";
							if ($tipo_atendimento == pg_fetch_result ($res,$i,tipo_atendimento) ) {
								echo " selected ";
							}
							echo " value='" . pg_fetch_result ($res,$i,tipo_atendimento) . "'>" ;
							echo pg_fetch_result ($res,$i,codigo) . " - " . pg_fetch_result ($res,$i,descricao) ;
							echo "</option>";
						}
						?>
						</select>

					</td>
				</tr>

				<?php if ($login_fabrica == 1) : ?>
					<tr>
						<td align='center' colspan='3' style="padding-left:15px;">
							<label for="foto_nf">Anexar NF</label>
							<input type="file" name="foto_nf" id="foto_nf" />
						</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
					</tr>
				<?php endif; ?>	

			</table>

			<table width="100%" border="0" cellspacing="3" cellpadding="2" class="formulario">
				<tr>
					<td>
						Observações
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

echo "<table border='0' width='700' cellpadding='1' cellspacing='2' align='center'  class='tabela'>\n";

echo "<tr>\n";
echo "<td  colspan='3' style='border:0px;'></td>\n";
echo "<td  align='right' bgcolor='#D9E2EF'>
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


echo "<tr class='titulo_coluna'>\n";
echo "<td align='center'>#</td>\n";
echo "<td align='center'>Produto</td>\n";
echo "<td align='center'>Descrição do produto</td>\n";
echo "<td align='center'>Defeito Reclamado</td>\n";
echo "</tr>\n";

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
					tbl_produto.descricao                          ,
					tbl_defeito_reclamado.descricao as df_descricao
			FROM	tbl_os_revenda
			JOIN	tbl_os_revenda_item USING(os_revenda)
			JOIN	tbl_produto         USING (produto)
			JOIN	tbl_defeito_reclamado USING(defeito_reclamado)
			WHERE	tbl_os_revenda.fabrica         = $login_fabrica
			AND		tbl_os_revenda_item.os_revenda = $os_metal 
			ORDER BY tbl_os_revenda_item.os_revenda_item ASC ";
	$res = pg_query($con, $sql);
	$qtde_item_os = pg_num_rows($res);
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
	$certificado_conformidade = "";
	$defeito_reclamado        = "";

	if (strlen($os_metal) > 0 AND $i < $qtde_item_os AND strlen($msg_erro)==0){
		$novo                     = 'f';
		$os_revenda_item          = pg_fetch_result($res,$i,os_revenda_item);
		$produto_referencia       = pg_fetch_result($res,$i,referencia);
		$produto_descricao        = pg_fetch_result($res,$i,descricao);
		$produto_serie            = pg_fetch_result($res,$i,serie);
		$produto_capacidade       = pg_fetch_result($res,$i,capacidade);
		$certificado_conformidade = pg_fetch_result($res,$i,certificado_conformidade);
		$defeito_reclamado        = pg_fetch_result($res,$i,defeito_reclamado);
		$df_descricao             = pg_fetch_result($res,$i,df_descricao);

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
		$cobrar_certificado       = $_POST["cobrar_certificado_".$i];
		$certificado_conformidade = $_POST["certificado_conformidade_".$i];
		$defeito_reclamado        = $_POST["defeito_reclamado_".$i];
	}

	$certificado_conformidade = number_format($certificado_conformidade,2,",",".");


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
	echo "<td align='center'><input class='frm' type='text' name='produto_referencia_$i'  rel='produtos' size='8' maxlength='50' value='$produto_referencia'><img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,\"referencia\",document.frm_os.produto_voltagem_$i,document.frm_os.produto_capacidade_$i)' style='cursor:pointer;'></td>\n";
	echo "<td align='center'><input class='frm' type='text' name='produto_descricao_$i' onBlur='busca_valores_produto($i);' size='30' maxlength='18' value='$produto_descricao'><img src='imagens/lupa.png' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i,\"descricao\",document.frm_os.produto_voltagem_$i,document.frm_os.produto_capacidade_$i)' style='cursor:pointer;'></td>\n";
	echo "<td align='center'><select name='defeito_reclamado_$i'  id='defeito_reclamado_$i' style='width: 300px;' onfocus='listaDefeitos($i, document.frm_os.produto_referencia_$i.value);' class='frm'>";
	if(strlen($os_revenda_item) > 0) {
		echo "<option value='$defeito_reclamado'>$df_descricao</option>";
	}else{
		if(strlen($defeito_reclamado) > 0){
			echo "<option id='opcoes' value='$defeito_reclamado' selected></option>";
		}
		else{
			echo "<option id='opcoes' value='0'></option>";
		}
	}
	echo "</select>";
	echo "</td>";
	echo "</tr>\n";
}

?>
</table>

<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr>
		<td colspan='8' align='center'>
			<br>
			<input type='hidden' name='btn_acao' value=''>
			<input type="button" value="Gravar" rel='frm_os' class='verifica_servidor' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ;} " ALT='Gravar' border='0' style='cursor:pointer;'>
		</td>
	</tr>
</table>
</form>

<br>
<script language='javascript' src='address_components.js'></script>
<? include 'rodape.php'; ?>
