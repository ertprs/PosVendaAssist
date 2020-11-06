<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

if($login_fabrica == 114){
         header("Location: menu_inicial.php");
         exit;
}
if($login_fabrica == 1){
	include "os_revenda_blackedecker.php";
	exit;
}
if($login_fabrica == 164){
    $sql = "select segmento_atuacao, descricao from tbl_segmento_atuacao  where fabrica = $login_fabrica and ativo is true";
    $resDestinacao = pg_query($con, $sql);

    $interno_p = false;

    $sql_posto_interno = "SELECT descricao
                          FROM tbl_posto_fabrica
                          JOIN tbl_tipo_posto USING(tipo_posto)
                          WHERE tbl_posto_fabrica.posto = $login_posto
                          AND tbl_posto_fabrica.fabrica = $login_fabrica";
    $res_posto_interno = pg_query($con, $sql_posto_interno);
    if (pg_num_rows($res_posto_interno) > 0) {
    	$interno_posto = pg_fetch_result($res_posto_interno, 0, "descricao");
    	if (trim(strtoupper($interno_posto)) == "POSTO INTERNO") {
    		$interno_p = true;
    	}
    }
}

include "funcoes.php";
/*  MLG 29/12/2010 - Toda a rotina de anexo de imagem da NF, inclusive o array com os parâmetros por fabricante, está num include.
	Para saber se a fábrica pede imagem da NF, conferir a variável (bool) '$anexaNotaFiscal'
	Para anexar uma imagem, chamar a função anexaNF($os, $_FILES['foto_nf'])
	Para mostrar a imagem: echo temNF($os); // Devolve um link: <a href='imagem' blank><img src='imagem[thumb'></a>
	Para saber se tem anexo: temNF($os, 'bool');
*/
include_once('anexaNF_inc.php');

if ($fabricaFileUploadOS) {
    if (!empty($os)) {
        $tempUniqueId = $os;
        $anexoNoHash = null;
    } else if (strlen(getValue("anexo_chave")) > 0) {
        $tempUniqueId = getValue("anexo_chave");
        $anexoNoHash = true;
    } else {
        if ($areaAdmin === true) {
            $tempUniqueId = $login_fabrica.$login_admin.date("dmYHis");
        } else {
            $tempUniqueId = $login_fabrica.$login_posto.date("dmYHis");
        }

        $anexoNoHash = true;
    }
}

function tipo_anexo($tempUniqueId = null) {
	global $login_fabrica, $con;
	$tipo_anexo = [];

	$sq = "SELECT JSON_FIELD('typeId', obs) AS tipo_anexo FROM tbl_tdocs WHERE fabrica = $login_fabrica AND hash_temp = '$tempUniqueId'";
	$rs = pg_query($con, $sq);
	if (pg_num_rows($rs) > 0) {
		for ($an=0; $an < pg_num_rows($rs); $an++) {
			$tipo_anexo[] = pg_fetch_result($rs, $an, 'tipo_anexo');
		}
	} else {
		$tipo_anexo[] = 'vazio';
	}

	return $tipo_anexo;
}

if($_POST["busca_defeito_constatado"]){

    $produto = $_POST["produto"];

    if($login_fabrica == 164 && $postoInterno != 't' && $login_posto != 113070){
		$whereTipoAtendimento = " AND tbl_defeito_constatado.descricao NOT IN ('SEM DEFEITO') ";
	}

    $sql = "SELECT DISTINCT
                tbl_defeito_constatado.descricao,
                tbl_defeito_constatado.defeito_constatado
                FROM tbl_diagnostico
                JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado AND tbl_defeito_constatado.fabrica = {$login_fabrica}
                JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia AND tbl_familia.fabrica = {$login_fabrica}
                JOIN tbl_produto ON tbl_produto.familia = tbl_familia.familia AND tbl_produto.fabrica_i = {$login_fabrica}
                WHERE tbl_diagnostico.fabrica = {$login_fabrica}
                AND tbl_produto.produto = {$produto}
                AND tbl_diagnostico.ativo IS TRUE
                $whereTipoAtendimento
                ORDER BY tbl_defeito_constatado.descricao ASC ";
    $res = pg_query($con, $sql);
    for($i=0; $i<pg_num_rows($res); $i++){
        $descricao              = pg_fetch_result($res, $i, descricao);
        $defeito_constatado     = pg_fetch_result($res, $i, defeito_constatado);

        $options .= "<option value='$defeito_constatado'>$descricao</option>";
    }
    echo $options;
    exit;
}


if($_POST["busca_defeito_reclamado"]){

	$produto = $_POST["produto"];

	$option = "<option value=''></option>";

	$sql = "SELECT
				tbl_diagnostico.defeito_reclamado,
				tbl_defeito_reclamado.descricao
			FROM tbl_diagnostico
			INNER JOIN tbl_produto ON tbl_produto.familia = tbl_diagnostico.familia
			INNER JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado
			WHERE
				tbl_produto.produto = {$produto}
				AND tbl_produto.fabrica_i = {$login_fabrica}
				AND tbl_diagnostico.fabrica = {$login_fabrica}
				AND tbl_defeito_reclamado.fabrica = {$login_fabrica}
				AND tbl_defeito_reclamado.ativo IS TRUE
				AND tbl_diagnostico.ativo IS TRUE
			ORDER BY tbl_defeito_reclamado.descricao ASC";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){

		$rows = pg_num_rows($res);

		for ($i = 0; $i < $rows; $i++) {

			$defeito_reclamado = pg_fetch_result($res, $i, "defeito_reclamado");
			$descricao         = pg_fetch_result($res, $i, "descricao");

			$option .= "<option value='{$defeito_reclamado}' > {$descricao} </option>";

		}

	}

	exit($option);

}

//if ($login_fabrica == 151 || $login_fabrica == 157) {
if (in_array($login_fabrica,array(81,151,157,3)) OR ($login_fabrica == 153 AND $login_posto == 20564) ){
	$anexaNotaFiscal = true;
}

if($login_fabrica == 35) {
	$aux_sql = "SELECT parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND posto = $login_posto LIMIT 1";
	$aux_res = pg_query($con, $aux_sql);
	$aux_par_ad = (array) json_decode(pg_fetch_result($aux_res, 0, 'parametros_adicionais'));

	if ($aux_par_ad["anexar_nf_os"] == "nao") {
		$anexaNotaFiscal = false;
	}else{
		$anexaNotaFiscal = true;
	}

}
if ($login_fabrica == 153 AND $login_posto != 20564) {
	$anexaNotaFiscal = false;
}
if (in_array($login_fabrica,array(137,165))) {// Verifica se o posto é Interno

    $sql = "SELECT posto
            FROM tbl_posto_fabrica
            JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = tbl_posto_fabrica.fabrica AND tbl_tipo_posto.posto_interno
            WHERE tbl_posto_fabrica.fabrica = " . $login_fabrica . "
            AND tbl_posto_fabrica.posto = " . $login_posto;
    $res = pg_query($con,$sql);

    if( pg_num_rows($res) > 0) {

        $posto_interno = true;

    }else{

    	$posto_interno = false;

    }
}

$sql          = "SELECT pedir_sua_os FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res          = pg_exec ($con,$sql);
$pedir_sua_os = pg_result ($res,0,pedir_sua_os);
$msg_erro     = "";
$qtde_item    = 20;
if (in_array($login_fabrica, array(11,151,172))) {
	if ($S3_sdk_OK) {
	    include_once S3CLASS;
	    $s3ve = new anexaS3('ve', (int) $login_fabrica);
	    $S3_online = is_object($s3ve);
	}
    # A class AmazonTC está no arquivo assist/class/aws/anexaS3.class.php
    $amazonTC = new AmazonTC("os", $login_fabrica);
}
/**
 * Rotina para a exclusão de anexo da OS
 **/
if ($_POST['ajax']=='excluir_nf') {
	if (($arquivo = $_POST['excluir_nf']) != '') {
		$ret = (excluirNF($arquivo, 'r')) ? 'ok' : 'KO';
	} else {
		$ret = 'KO';
	}
	exit($ret);
}
// HD 321132 - Exclusão da imagem - AJAX - Fim

if (strlen($_POST['qtde_item']) > 0)
	$qtde_item = $_POST['qtde_item'];

if (strlen($_POST['qtde_linhas']) > 0)
	$qtde_item = $_POST['qtde_linhas'];

if(strlen($_GET["lote"])>0){
	$qtde_linhas = $_GET["qtde_linhas"];
	$qtde_item   = $_GET["qtde_linhas"];
}

//if ($ip == '201.0.9.216') echo "Itens: $qtde_item | $qtde_linhas<br><br>";

$btn_acao = trim(strtolower($_POST['btn_acao']));

if (strlen($_GET['os_revenda']) > 0)
	$os_revenda = trim($_GET['os_revenda']);
if (strlen($_POST['os_revenda']) > 0)
	$os_revenda = trim($_POST['os_revenda']);


//pegar o campo explodida para Arge 137
if(strlen($os_revenda) > 0){
	$sql = "SELECT explodida FROM tbl_os_revenda WHERE os_revenda = $os_revenda";
	$res = pg_query($con, $sql);
	if(pg_num_rows($res)>0){
		$explodida = pg_fetch_result($res, 0, "explodida");
	}
}
//pegar o campo explodida para Arge 137


/* ====================  APAGAR  =================== */
if ($btn_acao == "apagar") {
	if(strlen($os_revenda) > 0){
		$sql = "DELETE FROM tbl_os_revenda_item
			USING tbl_os_revenda
			WHERE tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
			AND   tbl_os_revenda.os_revenda = $os_revenda
			AND   tbl_os_revenda.fabrica    = $login_fabrica
			AND   tbl_os_revenda.posto      = $login_posto;

			DELETE FROM tbl_os_revenda
				WHERE  tbl_os_revenda.os_revenda = $os_revenda
				AND    tbl_os_revenda.fabrica    = $login_fabrica
				AND    tbl_os_revenda.posto      = $login_posto";
		$res = pg_exec ($con,$sql);

		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);

		if (strlen ($msg_erro) == 0) {
			header("Location: $PHP_SELF");
			exit;
		}
	}
}

if ($btn_acao == "gravar") {
	//echo "<pre>" print_r($_POST) exit;
	if (strlen($_POST['sua_os']) > 0){
		$xsua_os = $_POST['sua_os'] ;
		//hd4617
		if ( !in_array($login_fabrica, array(3,5,11,30,172)) ) {
			$xsua_os = "000000" . trim ($xsua_os);
			$xsua_os = substr ($xsua_os, strlen ($xsua_os) - 7 , 7) ;
		}
		$xsua_os = "'". $xsua_os ."'";
	}else{
		$xsua_os = "null";
	}

	$preos = $_POST['preos'];

	$xdata_abertura = fnc_formata_data_pg($_POST['data_abertura']);
	$ano = (int)substr($xdata_abertura,1,4);
	$mes = (int)substr($xdata_abertura,6,2);
	$dia = (int)substr($xdata_abertura,9,2);;

	if(!checkdate($mes, $dia, $ano)) {
		$msg_erro = traduz("Data de abertura inválida.<br/>");
	}

	if($login_fabrica == 35) {
		if (!empty($xdata_abertura)) {

			if (!checkdate($mes, $dia, $ano)) {
				$msg_erro = "Data de abertura inválida";
			} else if (strtotime("{$ano}-{$mes}-{$dia}") < strtotime("today - 5 days")) {
				$msg_erro = "Data de abertura não pode ser anterior a 5 dias";
			}
		}

		if($anexaNotaFiscal) {
			$tipo_anexo	= tipo_anexo($tempUniqueId);
			if (!in_array('notafiscal', $tipo_anexo)) {
				$msg_erro = "Favor anexar a nota fiscal" ;
			}
		}
	}

	$xdata_nf = fnc_formata_data_pg($_POST['data_nf']);
	$ano = (int)substr($xdata_nf,1,4);
	$mes = (int)substr($xdata_nf,6,2);
	$dia = (int)substr($xdata_nf,9,2);

	if($login_fabrica == 72) {
		if($anexaNotaFiscal and strlen($_FILES['foto_nf']['name'][0]) == 0) {
			$msg_erro = "Favor anexar a nota fiscal";
		}
	}

	if ($login_fabrica == 151) {
		if(!empty($xdata_nf) && $xdata_nf != "null" && !checkdate($mes, $dia, $ano)) {
                        $msg_erro .= "Data de nota fiscal inválida.<br/>";
                } else {
			$xdata_nf_geral = $xdata_nf;
		}
	} else {
		if(!checkdate($mes, $dia, $ano)) {
			$msg_erro = traduz("Data de nota fiscal inválida.<br/>");
		}
	}

	if( in_array($login_fabrica, array(11,172)) && $xdata_abertura != 'null' && $xdata_nf != 'null' ) { // HD 689217

		list($di, $mi, $yi) = explode("/", $_POST['data_abertura']);
		list($df, $mf, $yf) = explode("/", $_POST['data_nf']);

        if(!checkdate($mi,$di,$yi) || !checkdate($mf,$df,$yf) )
            $msg_erro = traduz("Data Inválida");

		$aux_data_inicial = "$yf-$mf-$df";
		$aux_data_final = "$yi-$mi-$di";
		if (strtotime($aux_data_inicial.'+ 15 days') < strtotime($aux_data_final) ) {
            $msg_erro = traduz('O intervalo entre as datas não pode ser maior que 15 dias. ');
        }

	} // FIM HD 689217

	// HD 3466
	// HD 6701 15835
	if(($login_posto == '4260' && $login_fabrica == 6) && (in_array($login_fabrica, array(11,172)) && $login_posto == '20321')) {
		$msg_erro=null;
	}elseif($xdata_nf=="null" and $login_fabrica<>24 and $login_fabrica != 151){
		$msg_erro .= traduz("por.favor.inserir.a.data.da.nota.fiscal",$con,$cook_idioma);
	}

	$nota_fiscal = $_POST["nota_fiscal"];
	if (strlen($nota_fiscal) == 0) {
		if($login_fabrica==19 or $login_fabrica==40 or $login_fabrica>=80 && $login_fabrica != 151){
			$msg_erro = traduz("por.favor.inserir.a.nota.fiscal",$con,$cook_idioma);
		}else{
			$xnota_fiscal = 'null';
		}
	}else{
		if ($login_fabrica == 14 || $login_fabrica == 6 || ($login_posto == 14254 && in_array($login_fabrica, array(11,172)) )) {
			$nota_fiscal = trim ($nota_fiscal);
			$nota_fiscal = str_replace (".","",$nota_fiscal);
			$nota_fiscal = str_replace (" ","",$nota_fiscal);
			$nota_fiscal = str_replace ("-","",$nota_fiscal);
			//$nota_fiscal = "000000000000" . $nota_fiscal;
			//$nota_fiscal = substr ($nota_fiscal,strlen($nota_fiscal)-12,12);
			$nota_fiscal = str_pad($nota_fiscal, 12, "0", STR_PAD_LEFT);
			$xnota_fiscal = "'" . $nota_fiscal . "'" ;
		} else {
			$nota_fiscal = trim ($nota_fiscal);
			$nota_fiscal = str_replace (".","",$nota_fiscal);
			$nota_fiscal = str_replace (" ","",$nota_fiscal);
			$nota_fiscal = str_replace ("-","",$nota_fiscal);
			//$nota_fiscal = "000000" . $nota_fiscal;
			//$nota_fiscal = substr ($nota_fiscal,strlen($nota_fiscal)-9,9);
			$nota_fiscal = str_pad($nota_fiscal, 9, "0", STR_PAD_LEFT);
			if($login_fabrica==19){
				if(!is_numeric($nota_fiscal)){
					$msg_erro = traduz("numero.da.nota.fiscal.invalido",$con,$cook_idioma);
				}
			}
			$xnota_fiscal = "'" . $nota_fiscal . "'" ;
		}
	}

	$motivo = $_POST['motivo'];
	if(strlen($motivo)==0){
		if($login_fabrica == 19){
			$msg_erro = traduz("por.favor.informar.o.motivo",$con,$cook_idioma);
		}else{
			$motivo="null";
		}
	}

	if($login_fabrica == 137 && $posto_interno){
		$xcfop 					= $_POST['cfop'];
		$transportadora 		= $_POST['transportadora'];
		$nota_fiscal_saida 		= $_POST['nota_fiscal_saida'];
		$data_nota_fiscal_saida = $_POST['data_nota_fiscal_saida'];
	}

	if (strlen($_POST['revenda_cnpj']) > 0) {
		$revenda_cnpj  = $_POST['revenda_cnpj'];
		$revenda_cnpj  = str_replace (".","",$revenda_cnpj);
		$revenda_cnpj  = str_replace ("-","",$revenda_cnpj);
		$revenda_cnpj  = str_replace ("/","",$revenda_cnpj);
		$revenda_cnpj  = str_replace (" ","",$revenda_cnpj);
		$xrevenda_cnpj = "'". $revenda_cnpj ."'";
	}else{
		if($login_fabrica==19){
			$msg_erro = traduz("por.favor.inserir.o.cnpj.do.atacado",$con,$cook_idioma);
			$xrevenda_cnpj = "null";
		}else{
			$xrevenda_cnpj = "null";
		}
	}

	if (strlen($_POST['consumidor_cnpj']) > 0) {
		$consumidor_cnpj  = $_POST['consumidor_cnpj'];
		$consumidor_cnpj  = str_replace (".","",$consumidor_cnpj);
		$consumidor_cnpj  = str_replace ("-","",$consumidor_cnpj);
		$consumidor_cnpj  = str_replace ("/","",$consumidor_cnpj);
		$consumidor_cnpj  = str_replace (" ","",$consumidor_cnpj);
		$xconsumidor_cnpj = "'". $consumidor_cnpj ."'";
	}else{
		if($login_fabrica==19){
			$msg_erro = traduz("por.favor.inserir.o.cnpj.da.revenda",$con,$cook_idioma);
			$xconsumidor_cnpj = "null";
		}else{
			$xconsumidor_cnpj = "null";
		}
	}

	$campo_extra = "";
	$valor_campo_extra = "";
    $valor_update_campo_extra = "";

	if ($login_fabrica == 164 && $interno_p) {
		$revenda_estado = $_POST['revenda_estado'];

		if (strlen(trim($_POST['revenda_fantasia'])) > 0) {
			$revenda_fantasia = pg_escape_string(utf8_encode($_POST['revenda_fantasia']));
			$campo_extra['revenda_fantasia'] = $revenda_fantasia;
			$insert_campo_extra = ", '".json_encode($campo_extra)."'";
			$valor_campo_extra = ", campos_extra";
			$update_campo_extra = " '".json_encode($campo_extra)."'";
            $valor_update_campo_extra = ", campos_extra  = $update_campo_extra";

		}
	}


	if (strlen($_POST['taxa_visita']) > 0)
		$xtaxa_visita = "'". $_POST['taxa_visita'] ."'";
	else
		$xtaxa_visita = "null";

	if (strlen($_POST['regulagem_peso_padrao']) > 0)
		$xregulagem_peso_padrao = "'". $_POST['regulagem_peso_padrao'] ."'";
	else
		$xregulagem_peso_padrao = "null";

	if (strlen($_POST['certificado_conformidade']) > 0)
		$xcertificado_conformidade = "'". $_POST['certificado_conformidade'] ."'";
	else
		$xcertificado_conformidade = "null";

	$os_reincidente = "'f'";

	//HD 11082
	if (strlen($_POST['consumidor_email']) > 0)
		$xconsumidor_email = "'". $_POST['consumidor_email'] ."'";
	else
		$xconsumidor_email = "null";

	if (strlen($_POST['revenda_fone']) > 0) {
		$xrevenda_fone = "'". $_POST['revenda_fone'] ."'";
	}else{
		if( in_array($login_fabrica, array(11,172)) ){
			$msg_erro.="Digite o telefone da revenda.";
		}else{
			$xrevenda_fone = "null";
		}
	}

	if (strlen($_POST['revenda_email']) > 0) {
		$xrevenda_email = "'". $_POST['revenda_email'] ."'";
	}else{
		$xrevenda_email = "null";
	}

	// Verificação se o nº de série é reincidente
	if ($login_fabrica == 6 and 1 == 2) {
		$sqlX = "SELECT to_char (current_date - INTERVAL '30 days', 'YYYY-MM-DD')";
		$resX = pg_exec($con,$sqlX);
		$data_inicial = pg_result($resX,0,0)." 00:00:00";

		$sqlX = "SELECT to_char (current_date, 'YYYY-MM-DD')";
		$resX = pg_exec($con,$sqlX);
		$data_final = pg_result($resX,0,0)." 23:59:59";

		for ($i = 0 ; $i < $qtde_item ; $i++) {
			$produto_serie = $_POST["produto_serie_".$i];

			if (strlen($produto_serie) > 0) {
				$sql = "SELECT  tbl_os.os            ,
								tbl_os.sua_os        ,
								tbl_os.data_digitacao,
								tbl_os_extra.extrato
						FROM    tbl_os
						JOIN    tbl_os_extra ON tbl_os_extra.os = tbl_os.os
						WHERE   tbl_os.serie   = '$produto_serie'
						AND     tbl_os.fabrica = $login_fabrica
						AND     tbl_os.posto   = $login_posto
						AND     tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'
						ORDER BY tbl_os.data_digitacao DESC
						LIMIT 1";
				$res = pg_exec($con,$sql);

				if (pg_numrows($res) > 0) {
					$xxxos      = trim(pg_result($res,0,os));
					$xxxsua_os  = trim(pg_result($res,0,sua_os));
					$xxxextrato = trim(pg_result($res,0,extrato));

					if (strlen($xxxextrato) == 0) {
						$msg_erro .= traduz("n.de.serie.%.digitado.e.reincidente.%.favor.consultar.a.ordem.de.servico.%.e.acrescentar.itens.%.em.caso.de.duvida.entre.em.contato.com.a.fabrica.",$con,$cook_idioma,array("$produto_serie","<BR>","$xxxsua_os","<BR>"));
						$linha_erro = $i;
					}else{
						$os_reincidente = "'t'";
					}
				}
			}
		}
	}

	if ($xrevenda_cnpj <> "null") {
		if(in_array($login_fabrica, array(3,15,117,161))){
			$sql = "SELECT revenda,
					contato_razao_social as nome,
					contato_endereco as endereco,
					contato_numero as numero,
					contato_complemento as complemento,
					contato_bairro as bairro,
					contato_cep as cep,
					cidade,
					contato_fone as fone,
					cnpj
				FROM tbl_revenda_fabrica
				WHERE fabrica = $login_fabrica
				AND   cnpj = $xrevenda_cnpj";
		}else{			
			if($login_fabrica == 165) {
				$sql = "SELECT 
							LPAD(tbl_revenda.cnpj, 14, '0') AS cnpj, 
							revenda,
							nome,
							endereco,
							numero,
							complemento,
							bairro,
							cep,
							cidade,
							fone
							FROM    tbl_revenda
							WHERE   nome = '{$revenda_nome}'";
			} else {
				$sql =	"SELECT *
						FROM    tbl_revenda
						WHERE   cnpj = $xrevenda_cnpj";					
			}			
		}
		
		$res = pg_exec($con,$sql);

		if (in_array($login_fabrica, [141]) && pg_num_rows($res) == 0) {

			$razao_social = pg_escape_string(trim($_POST['revenda_nome']));

			$sqlInsRevenda = "INSERT INTO tbl_revenda (cnpj, nome, fone, email)
							  VALUES ({$xrevenda_cnpj},'$razao_social',$xrevenda_fone,$xrevenda_email)
							  RETURNING revenda";
			$resInsRevenda = pg_query($con, $sqlInsRevenda);

			$revenda_id = pg_fetch_result($resInsRevenda, 0, 'revenda');

			$sql =	"SELECT cnpj, nome, fone, email
					 FROM    tbl_revenda
					 WHERE   revenda = $revenda_id";

			$res = pg_exec($con,$sql);

		}

		if (pg_numrows($res) == 0){
			if($login_fabrica<>19)$msg_erro = traduz("cnpj.da.revenda.nao.cadastrado",$con,$cook_idioma);
			else $msg_erro = traduz("cnpj.do.atacado.nao.cadastrado",$con,$cook_idioma);
		}else{

			$revenda		= trim(pg_result($res,0,revenda));
			$nome			= trim(pg_result($res,0,nome));
			$endereco		= trim(pg_result($res,0,endereco));
			$numero			= trim(pg_result($res,0,numero));
			$complemento	= trim(pg_result($res,0,complemento));
			$bairro			= trim(pg_result($res,0,bairro));
			$cep			= trim(pg_result($res,0,cep));
			$cidade			= trim(pg_result($res,0,cidade));
			$fone			= trim(pg_result($res,0,fone));
			$cnpj			= trim(pg_result($res,0,cnpj));

			if (strlen($revenda) > 0)
				$xrevenda = "'". $revenda ."'";
			else
				$xrevenda = "null";

			if (strlen($nome) > 0)
				$xnome = "'". $nome ."'";
			else
				$xnome = "null";

			//hd 40364
			if ($login_fabrica==19 and $xnome == 'null') {
				$msg_erro .= "<BR>".traduz("o.cadastro.do.atacado.esta.incompleto.antes.de.proseguir.com.a.digitacao.da.os.acesse.o.link.cadastro.na.tela.inicial.depois.clique.no.link.cadastro.de.revenda.localize.o.atacado.e.complemente.seu.cadastro.campo.razao.social",$con,$cook_idioma).".";
			}

			if (strlen($endereco) > 0)
				$xendereco = "'". $endereco ."'";
			else
				$xendereco = "null";

			if (strlen($numero) > 0)
				$xnumero = "'". $numero ."'";
			else
				$xnumero = "null";

			if (strlen($complemento) > 0)
				$xcomplemento = "'". $complemento ."'";
			else
				$xcomplemento = "null";

			if (strlen($bairro) > 0)
				$xbairro = "'". $bairro ."'";
			else
				$xbairro = "null";

			if (strlen($cidade) > 0)
				$xcidade = "'". $cidade ."'";
			else
				$xcidade = "null";

			if (strlen($cep) > 0)
				$xcep = "'". $cep ."'";
			else
				$xcep = "null";

			if (strlen($fone) > 0)
				$xfone = "'". $fone ."'";
			else
				$xfone = "null";
			if (strlen($cnpj) > 0)
				$xcnpj = "'". $cnpj ."'";
			else
				$xcnpj = "null";

		}
	}else{
		$validacao = 'SIM';

		if ($login_fabrica == 14) {
			if ($login_posto == 7214 or $login_posto == 13562) {
				$validacao = 'NAO';

				if (strlen($revenda) == 0) $revenda = "null";
			}
		}

		if ($validacao == 'SIM') {
			$msg_erro .= traduz("cnpj.nao.informado",$con,$cook_idioma);
		}
	}

	if($login_fabrica == 35 and strlen(trim($revenda_cnpj))>0){
        $msg_erro .= VerificaBloqueioRevenda($revenda_cnpj, $login_fabrica);
    }


	//PARA LORENZETTI
	if($login_fabrica == 19 and strlen($msg_erro)==0){
		if ($xconsumidor_cnpj <> "null") {
			$sql =	"SELECT *
					FROM    tbl_revenda
					WHERE   cnpj = $xconsumidor_cnpj";
			$res = pg_exec($con,$sql);

			if (pg_numrows($res) > 0){
				$consumidor_revenda		= trim(pg_result($res,0,revenda));
				$consumidor_nome		= trim(pg_result($res,0,nome));
				$consumidor_cnpj		= trim(pg_result($res,0,cnpj));
			}
		}
	}


	if (strlen($nome) > 0)
		$xconsumidor_nome = "'". $consumidor_nome ."'";
	else
		$xconsumidor_nome = "null";

	//hd 40364
	if ($login_fabrica==19 and $xconsumidor_nome == 'null') {
		$msg_erro .= "<BR>".traduz("o.cadastro.da.revenda.esta.incompleto.antes.de.proseguir.com.a.digitacao.da.os.acesse.o.link.cadastro.na.tela.inicial.depois.clique.no.link.cadastro.de.revenda.localize.a.revenda.e.complemente.seu.cadastro.campo.razao.social",$con,$cook_idioma).".";
	}

	if (strlen($cnpj) > 0)
		$xconsumidor_cnpj = "'". $consumidor_cnpj ."'";
	else
		$xconsumidor_cnpj = "null";
	//--========================================--

	if (strlen($_POST['obs']) > 0) {
		$xobs = "'". str_replace("'","''",$_POST['obs']) ."'";
	}else{
		$xobs = "null";
	}

	if (strlen($_POST['contrato']) > 0) {
		$xcontrato = "'". $_POST['contrato'] ."'";
	}else{
		$xcontrato = "'f'";
	}

	$tipo_atendimento = $_POST['tipo_atendimento'];
	if (strlen (trim ($tipo_atendimento)) == 0){
		if(in_array($login_fabrica,array(141,144))){
			$msg_erro = traduz("Informe um tipo de atendimento");
		}else{
			$tipo_atendimento = 'null';
		}
	}

	if($login_fabrica == 137 && $posto_interno){

		$valor_adicional_justificativa = array(
			"transportadora" => $transportadora,
			"nota_fiscal_saida" => $nota_fiscal_saida,
			"data_nota_fiscal_saida" => $data_nota_fiscal_saida,
		);

		$valor_adicional_justificativa = json_encode($valor_adicional_justificativa);
		$valor_adicional_justificativa = "'".$valor_adicional_justificativa."'";

	}else{
		$valor_adicional_justificativa = "'null'";
	}

	if (strlen ($msg_erro) == 0) {

		if ( in_array($login_fabrica, array(11,172)) ) {

			$fabricas_arr = [11, 172];

			$res = pg_exec ($con,"BEGIN TRANSACTION");

			foreach ($fabricas_arr as $fabrica) {

				$sqlPostoSequencial = "UPDATE tbl_posto_fabrica
                                       SET sua_os = sua_os + 1
                                       WHERE posto = {$login_posto}
                                       AND fabrica = {$fabrica}";
                $resPostoSequencial = pg_query($con, $sqlPostoSequencial);

				#-------------- insere ------------
				$sql = "INSERT INTO tbl_os_revenda (
							fabrica,
							sua_os,
							data_abertura,
							data_nf,
							nota_fiscal,
							revenda,
							obs,
							digitacao,
							posto,
							tipo_atendimento,
							contrato,
							consumidor_nome,
							consumidor_cnpj,
							tipo_os,
							valor_adicional_justificativa,
							consumidor_email
						) VALUES (
							$fabrica,
							$xsua_os,
							$xdata_abertura,
							$xdata_nf,
							$xnota_fiscal,
							$revenda,
							$xobs,
							current_timestamp,
							$login_posto,
							$tipo_atendimento,
							$xcontrato,
							$xconsumidor_nome,
							$xconsumidor_cnpj,
							$motivo,
							$valor_adicional_justificativa,
							$xconsumidor_email
						) RETURNING  os_revenda";

				$res        = pg_query ($con, $sql);
				$os_revenda_valida = pg_fetch_result($res, 0, 'os_revenda');
				$msg_erro   = pg_errormessage($con);

				$arrFabricaOs[$fabrica] = $os_revenda_valida;

			}
		
				if (strlen($msg_erro) == 0 and strlen(trim($explodida)) == 0) {

					$array_serie_duplicada = array();
		            $array_series = array();

					for ($i = 0 ; $i < $qtde_item ; $i++) {

						$temErro = false;

						$fabrica_codigo_interno = $_POST["fabrica_codigo_interno_{$i}"];

						$codigo_interno              = trim($_POST["codigo_interno_{$i}"]);

						$referencia                  = trim($_POST["produto_referencia_".$i]);
						$serie                       = strtoupper(trim($_POST["produto_serie_".$i]));
						$type                        = strtoupper(trim($_POST["versao_produto_".$i]));
						$capacidade                  = $_POST["produto_capacidade_".$i];
						$type                        = $_POST["type_".$i];
						$embalagem_original          = $_POST["embalagem_original_".$i];
						$sinal_de_uso                = $_POST["sinal_de_uso_".$i];
						$aux_nota_fiscal             = trim($_POST["aux_nota_fiscal_".$i]);
						$aux_qtde                    = trim($_POST["aux_qtde_".$i]);
						$rg_produto                  = trim($_POST["rg_produto_".$i]);
						$defeito_reclamado_descricao = $_POST["defeito_reclamado_descricao_".$i];
						$data_fabricacao             = $_POST["data_fabricacao_".$i];

						$possui_codigo_interno 		 = $_POST['possui_codigo_interno_'.$i];

						if (strlen($embalagem_original) == 0) {
							$embalagem_original = "f";
						}
						if (strlen($sinal_de_uso) == 0) {
							$sinal_de_uso = "f";
						}
						if (strlen($aux_qtde) == 0) {
							$aux_qtde = "1";
						}

						$serie = "'". $serie ."'";

						if (strlen($type) == 0) {
							$type = "null";
						} else {
							$type = "'". $type ."'";
						}

						if (strlen($defeito_reclamado_descricao) == 0 and strlen($referencia) > 0) {
							$xdefeito_reclamado_descricao = " null ";
						}else{
							$xdefeito_reclamado_descricao = "'".$defeito_reclamado_descricao."'";
						}

						if(strlen($data_fabricacao) > 0){
							$xdata_fabricacao = preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$3-$2-$1', $data_fabricacao);
							$condicao_data_fabricao_value = ",'$xdata_fabricacao'";
						}

						if(empty($xdata_fabricacao)){
							$xdata_fabricacao = null;
						}

						if (strlen ($referencia) > 0) {
							
							$referencia = strtoupper ($referencia);

							$arrDadosProduto = valida_produto_pacific_lennox($referencia);

							$produto = "";
							$os_revenda = "";

							if (count($arrDadosProduto["fabrica"]) > 1) {

								if (empty($possui_codigo_interno)) {
									
									$msg_erro .= "Linha ".($i + 1).": Informe se o produto {$referencia} possui código interno ou não <br />";
									$temErro = true;

								} else {

									if ($possui_codigo_interno == "nao") {

										$produto 	= $arrDadosProduto["fabrica"][11]["produto"];
										$os_revenda = $arrFabricaOs["11"];
										$fabricaValida = 11;

									} else {

										$codigoInternoValido = false;
										foreach ($arrDadosProduto["fabrica"] as $fabricaId => $fabricaArr) {

											if ($fabricaArr["codigo_interno"] == $codigo_interno) {

												$os_revenda 		 = $arrFabricaOs[$fabricaId];
												$produto 			 = $fabricaArr["produto"];
												$codigoInternoValido = true;
												$fabricaValida       = $fabricaId;

											}

										}

										if (empty($codigo_interno)) {
											$msg_erro .= "Linha ".($i + 1).": Informe o código interno do produto {$referencia} <br />";
											$temErro = true;
										} else if (!$codigoInternoValido) {
											$msg_erro .= "Linha ".($i + 1).": Código interno informado no produto {$referencia} inválido <br />";
											$temErro = true;
										}

									}

								}

							} else if (count($arrDadosProduto["fabrica"]) == 1) {

								if (!empty($codigo_interno)) {

									$codigoInternoValido = false;
									foreach ($arrDadosProduto["fabrica"] as $fabricaId => $fabricaArr) {

										if ($fabricaArr["codigo_interno"] == $codigo_interno) {

											$os_revenda 		 = $arrFabricaOs[$fabricaId];
											$produto 			 = $fabricaArr["produto"];
											$codigoInternoValido = true;
											$fabricaValida       = $fabricaId;

										}

									}

									if (!$codigoInternoValido) {
										$msg_erro .= "Linha ".($i + 1).": Código interno informado no produto {$referencia} inválido <br />";
										$temErro = true;
									}

								} else {

									$fabArr = array_keys($arrDadosProduto["fabrica"]);

									$fabricaValida = $fabArr[0];
									$produto 	   = $arrDadosProduto["fabrica"][$fabArr[0]]["produto"];
									$os_revenda    = $arrFabricaOs[$fabArr[0]];

								}

							}

							$sqlSerieObrigatoria = "SELECT produto
													FROM tbl_produto
													WHERE produto = {$produto}
													AND numero_serie_obrigatorio IS TRUE";
							$resSerieObrigatoria = pg_query($con, $sqlSerieObrigatoria);

							if (pg_num_rows($resSerieObrigatoria) > 0) {

								if (empty(trim($_POST["produto_serie_".$i]))) {

									$msg_erro .= "Linha ".($i + 1).": O número de série é obrigatório para o produto {$referencia} <br />";
									$temErro = true;
								}

							}

							$xxxos = 'null';

							if ($temErro) {

								$erroLinha[(string) $i] = "sim";

							}

							if (strlen($msg_erro) == 0) {

								if (strlen($capacidade) == 0) {
									$xcapacidade = 'null';
								} else {
									$xcapacidade = "'".$capacidade."'";
								}

								if (strlen($aux_nota_fiscal)==0) {
									$aux_nota_fiscal = $xnota_fiscal;
								}

								if (strlen($rg_produto)==0) {
									$rg_produto = 'null';
								}else {
									$rg_produto = "'".$rg_produto."'";
								}

								$cond_defeito = " defeito_reclamado_descricao";

								if (strlen ($msg_erro) == 0) {
									$sql = "INSERT INTO tbl_os_revenda_item (
												os_revenda,
												produto,
												serie,
												nota_fiscal,
												data_nf,
												capacidade,
												type,
												$cond_defeito,
												embalagem_original,
												sinal_de_uso,
												os_reincidente,
												qtde,
												codigo_fabricacao,
												rg_produto,
												obs_causa,
												$campos_extra
												reincidente_os
												$condicao_data_fabricao
											) VALUES (
												$os_revenda,
												$produto,
												$serie,
												$aux_nota_fiscal,
												$xdata_nf,
												$xcapacidade,
												$type,
												$xdefeito_reclamado_descricao,
												'$embalagem_original',
												'$sinal_de_uso',
												$os_reincidente,
												$aux_qtde,
												'$codigo_interno',
												$rg_produto,
												'{$codigo_interno}',
												$campos_value
												$xxxos
												$condicao_data_fabricao_value
											) RETURNING os_revenda_item";

									$res = pg_query ($con,$sql);
									$msg_erro = pg_errormessage($con);

									$fabricasExplode[$fabricaValida] = true;

	                                $sql = "SELECT fn_valida_os_revenda($os_revenda, $login_posto, $fabricaValida)";
	                                $res = @pg_exec ($con,$sql);

	                                if (!empty(pg_errormessage($con))) {
					                    $msg_erro = pg_errormessage($con);
					                }

									if (strlen ($msg_erro) > 0) {
										break;
									}else{
										$os_revenda_item = pg_fetch_result($res,0,0);
									}
								}
							}
						}
					}

				}

				if (strlen($msg_erro) == 0) { // HD 321132 - Inicio
					/* ===========INÍCIO DO PROCESSO DA IMAGEM =============== */
					if ($anexaNotaFiscal) {

						$qt_anexo = 0;

						foreach($_FILES['foto_nf'] as $files){
							if(strlen($_FILES['foto_nf']['name'][$qt_anexo])==0){
								continue;
							}
							$dados_anexo['name']      = $_FILES['foto_nf']['name'][$qt_anexo];
							$dados_anexo['type']      = $_FILES['foto_nf']['type'][$qt_anexo];
							$dados_anexo['tmp_name']  = $_FILES['foto_nf']['tmp_name'][$qt_anexo];
							$dados_anexo['error']     = $_FILES['foto_nf']['error'][$qt_anexo];
							$dados_anexo['size']      = $_FILES['foto_nf']['size'][$qt_anexo];
						}

						$qt_anexo++;
					}

				}


				/* ===========FIM DO PROCESSO DA IMAGEM ================ */

				//upload temporario
		        $types = array("png", "jpg", "jpeg", "bmp", "pdf");

				if (strlen($msg_erro) == 0) {
			        $arrFilesUpload = array();
			        $arrLinks = array("img_os_revenda_1"=>array("link"=>"", "name"=>""));
			        foreach ($_FILES as $key => $imagem) {

			          	if((strlen(trim($imagem["name"])) > 0) && ($imagem["size"] > 0)){
				            if($key == "img_os_revenda_1"){
				            	$pathinfo = pathinfo($imagem['name']);
				            	$type  = $pathinfo['extension'];

				            	if (!in_array($type, $types)) {
				            		$msg_erro .= traduz("Formato inválido, são aceitos os seguintes formatos: png, jpeg, bmp e pdf");
				                	break;

				              	} else {
				              		if(strlen($os_revenda) > 0 ){
				                  		$fileName = "anexo_os_revenda_{$fabrica}_{$os_revenda}_{$key}";
				                	}else{
				                  		$fileName = "anexo_os_revenda_{$fabrica}_{$os_upload}_{$key}";
				                	}

				                	$arrFilesUpload[] = array("file_temp"=>$fileName.".".$type, "file_new"=>$fileName.".".$type);
				                	$amazonTC->tempUpload($fileName, $imagem, "", "");

				                	$arrLinks[$key]["link"] = $amazonTC->getLink("$fileName.$type", true, "", "");
				                	$arrLinks[$key]["name"] = $fileName.".".$type;
				              	}
				            }
			          	}
			        }

			        if(isset($_POST['tmp_img_os_revenda_1']) && strlen($_POST['tmp_img_os_revenda_1']) > 0){
			            $arrFilesUpload[] = array("file_temp"=>$_POST['tmp_img_os_revenda_1'], "file_new"=>$_POST['tmp_img_os_revenda_1']);

			            $arrLinks["img_os_revenda_1"]["link"] = $amazonTC->getLink($_POST['tmp_img_os_revenda_1'], true, "", "");
			            $arrLinks["img_os_revenda_1"]["name"] = $_POST['tmp_img_os_revenda_1'];
			        }

					//move anexo para bucket
		            if (count($arrFilesUpload) > 0) {
		                if ($amazonTC->moveTempToBucket($arrFilesUpload, $year, $month) === false) {
		                    $msg_erro = traduz("Erro ao salvar arquivos, por favor tente novamente <br />");
		                    $erro_upload = "true";
		                }
		            }

		        }

		        if(strlen($msg_erro) == 0){

		        	foreach ($arrFabricaOs as $fabricaId => $osRevendaId) {

		        		$sqlVerificaProduto = "SELECT os_revenda_item
		        							   FROM tbl_os_revenda_item
		        							   WHERE os_revenda = {$osRevendaId}";
		        		$resVerificaProduto = pg_query($con, $sqlVerificaProduto);

		        		if (pg_num_rows($resVerificaProduto) == 0) {

		        			pg_query($con, "DELETE FROM tbl_os_revenda WHERE os_revenda = {$osRevendaId}");
		        			unset($arrFabricaOs[$fabricaId]);

		        		}

		        	}

		            foreach ($fabricasExplode as $idFabrica => $val) {

		                $osRevenda = $arrFabricaOs[$idFabrica];

		                $sql = "SELECT fn_explode_os_revenda($osRevenda, $idFabrica)";
		                $res = pg_query ($con,$sql);

		                if (!empty(pg_errormessage($con)) && empty($msg_erro)) {
		                    $msg_erro = pg_errormessage($con);
		                }

		            }

					if(strlen($msg_erro) > 0){
						$res = pg_exec ($con,"ROLLBACK TRANSACTION");
					}else{
						$res = pg_exec ($con,"COMMIT TRANSACTION");
					}

		        } else {
					$res = pg_exec ($con,"ROLLBACK TRANSACTION");
				}

				$os_revenda = "";
				unset($_POST["os_revenda"]);

			if(strlen($msg_erro) == 0){

				// header("Location: os_revenda_explodida.php?sua_os=$sua_os");
				$data_hoje = date("d/m/Y");
				header("Location: os_revenda_consulta_lite.php?acao=PESQUISAR&data_inicial={$data_hoje}&data_final={$data_hoje}");
				exit;

			}else{
				unset($_POST["os_revenda"]);
				$os_revenda = "";
			}

		} else {

			$res = pg_exec ($con,"BEGIN TRANSACTION");

			if (strlen ($os_revenda) == 0) {
				#-------------- insere ------------
				$sql = "INSERT INTO tbl_os_revenda (
							fabrica          ,
							sua_os           ,
							data_abertura    ,
							data_nf          ,
							nota_fiscal      ,
							revenda          ,
							obs              ,
							digitacao        ,
							posto            ,
							tipo_atendimento ,
							contrato         ,
							consumidor_nome  ,
							consumidor_cnpj  ,
							tipo_os          ,
							valor_adicional_justificativa,
							consumidor_email
							$valor_campo_extra
						) VALUES (
							$login_fabrica                    ,
							$xsua_os                          ,
							$xdata_abertura                   ,
							$xdata_nf                         ,
							$xnota_fiscal                     ,
							$revenda                          ,
							$xobs                             ,
							current_timestamp                 ,
							$login_posto                      ,
							$tipo_atendimento                 ,
							$xcontrato                        ,
							$xconsumidor_nome                 ,
							$xconsumidor_cnpj                 ,
							$motivo                           ,
							$valor_adicional_justificativa    ,
							$xconsumidor_email
							$insert_campo_extra
						)";
			}else{
				$sql = "UPDATE tbl_os_revenda SET
							fabrica          = $login_fabrica                   ,
							sua_os           = $xsua_os                         ,
							data_abertura    = $xdata_abertura                  ,
							data_nf          = $xdata_nf                        ,
							nota_fiscal      = $xnota_fiscal                    ,
							revenda          = $revenda                         ,
							obs              = $xobs                            ,
							posto            = $login_posto                     ,
							tipo_atendimento = $tipo_atendimento                ,
							contrato         = $xcontrato                       ,
							consumidor_nome  = $xconsumidor_nome                ,
							consumidor_cnpj  = $xconsumidor_cnpj                ,
							valor_adicional_justificativa  = $valor_adicional_justificativa,
							tipo_os          = $motivo
							$valor_update_campo_extra
						WHERE os_revenda     = $os_revenda
						AND	 posto           = $login_posto
						AND	 fabrica         = $login_fabrica ";
			}
	//if ($ip == '201.42.45.176') echo "SQL : $sql<BR><BR>";
	//echo $sql;
			$res = @pg_exec ($con,$sql);
			$os_upload = pg_fetch_result($res, 0, 'os_revenda');
			$msg_erro = pg_errormessage($con);

			if (strlen($msg_erro) == 0 and strlen($os_revenda) == 0) {
				$res        = pg_exec ($con,"SELECT CURRVAL ('seq_os_revenda')");
				$os_revenda = pg_result ($res,0,0);
				$msg_erro   = pg_errormessage($con);

				// se nao foi cadastrado número da OS Fabricante (Sua_OS)
				if ($xsua_os == 'null' AND strlen($msg_erro) == 0 and strlen($os_revenda) <> 0) {
					//WELLINGTON ALTERAR 04/01
					//hd4617
					if ( !in_array($login_fabrica, array(1,3)) ) {
						$sql = "UPDATE tbl_os_revenda SET
										sua_os = '$os_revenda'
								WHERE tbl_os_revenda.os_revenda  = $os_revenda
								AND   tbl_os_revenda.posto       = $login_posto
								AND   tbl_os_revenda.fabrica     = $login_fabrica ";
						$res = pg_exec ($con,$sql);
						$msg_erro = pg_errormessage($con);
					}
				}

				if (strlen ($msg_erro) > 0) {
					$sql = "UPDATE tbl_cliente SET tbl_cliente.contrato = $xcontrato
							WHERE  tbl_cliente.cliente  = $revenda";
					$res = pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);
				}

				if (strlen ($msg_erro) > 0) {
					exit;
				}
			}

			if (in_array($login_fabrica, array(141,144)) && in_array($login_tipo_posto, array(452,453))) {
				$os_remanufatura = $_POST["os_remanufatura"];

	            if (empty($os_remanufatura)) {
	            	$sql = "SELECT classificacao_os FROM tbl_classificacao_os WHERE fabrica = {$login_fabrica} AND garantia IS FALSE";
	            	$res = pg_query($con, $sql);

	              	$os_remanufatura = pg_fetch_result($res, 0, "classificacao_os");

	            } else {
	            	$sql = "SELECT classificacao_os FROM tbl_classificacao_os WHERE fabrica = {$login_fabrica} AND garantia IS TRUE";
	            	$res = pg_query($con, $sql);

	              	$os_remanufatura = pg_fetch_result($res, 0, "classificacao_os");
	            }

	            $update = "UPDATE tbl_os_revenda SET classificacao_os = $os_remanufatura WHERE fabrica = {$login_fabrica} AND os_revenda = {$os_revenda}";
	            $res_update = pg_query($con, $update);

	            if (strlen(pg_last_error()) > 0) {
	            	$msg_erro = "Erro ao gravar os";
	            }
			}

			if (strlen($msg_erro) == 0 and strlen(trim($explodida)) == 0) {

				//$qtde_item = $_POST['qtde_item'];
				$sql = "DELETE FROM tbl_os_revenda_item WHERE  os_revenda = $os_revenda";
				$res = pg_exec($con,$sql);
				$msg_erro = pg_errormessage($con);
				//if ($ip == '201.0.9.216') echo "Itens (2): $qtde_item | $qtde_linhas<br><br>";

				/*if (in_array($login_fabrica, array(141, 144))) {
					$array_series = array();

					for ($i = 0; $i < $qtde_item; $i++) {
						$serie = strtoupper(trim($_POST["produto_serie_{$i}"]));
						if (strlen($serie) > 0) {
							$array_series[$i] = $serie;
						}
					}
				}*/

				$array_serie_duplicada = array();
	            $array_series = array();

				for ($i = 0 ; $i < $qtde_item ; $i++) {
					$referencia               = trim($_POST["produto_referencia_".$i]);
					$serie                    = strtoupper(trim($_POST["produto_serie_".$i]));
					$type                     = strtoupper(trim($_POST["versao_produto_".$i]));

					if($login_fabrica == 74){
						$produto_descricao                    = strtoupper(trim($_POST["produto_descricao_".$i]));
						if(strlen(trim($referencia))>0 AND strlen(trim($produto_descricao))>0 ) {

							$sql_linha = "select tbl_linha.codigo_linha
									        from
									        tbl_produto
									        inner join tbl_linha on tbl_produto.linha = tbl_linha.linha
									        where tbl_produto.referencia = '$referencia'
									        and tbl_produto.fabrica_i = $login_fabrica  ";
						    $res_linha = pg_query($con, $sql_linha);
						    if(pg_num_rows($res_linha)> 0){
						        $codigo_linha = pg_fetch_result($res_linha, 0, 'codigo_linha');
						    }

						    if($codigo_linha == "02"){
						      $cond_digita = " AND JSON_FIELD('digita_os_portateis', parametros_adicionais) = 't'";
						    }
						    if($codigo_linha == "01"){
						      $cond_digita = " AND JSON_FIELD('digita_os_fogo', parametros_adicionais) = 't'";
						    }

						    $sql_posto_fabrica = "SELECT posto
						                          FROM tbl_posto_fabrica
						                          WHERE posto = $login_posto
						                          AND tbl_posto_fabrica.fabrica = $login_fabrica
						                          $cond_digita";
						    $res_posto_fabrica = pg_query($con, $sql_posto_fabrica);

						    if(pg_num_rows($res_posto_fabrica) == 0 ){
						        $msg_erro .=  traduz(" Esse posto não é autorizado a abrir O.S dessa linha. ");
						        $campos_erro[] = $i;
						    }
						}
					}


					$capacidade               = $_POST["produto_capacidade_".$i];
					if($login_fabrica != 160 and !$replica_einhell){
						$type                     = $_POST["type_".$i];
					}
					$embalagem_original       = $_POST["embalagem_original_".$i];
					$sinal_de_uso             = $_POST["sinal_de_uso_".$i];
					//takashi 27/06
					$aux_nota_fiscal          = trim($_POST["aux_nota_fiscal_".$i]);
					$aux_qtde                 = trim($_POST["aux_qtde_".$i]);

					if($login_fabrica != 162){
						$rg_produto	              = trim($_POST["rg_produto_".$i]);
					}elseif($login_fabrica == 162){
						$rg_produto	              = trim($_POST["imei_".$i]);
					}

					if($login_fabrica == 164){
						$defeito_constatado =  $_POST["defeito_constatado_".$i];
						$destinacao 		=  $_POST["destinacao_".$i];

						$destinacao 		= (strlen($destinacao)> 0) ? $destinacao : 'null';
						$defeito_constatado = (strlen($defeito_constatado) > 0) ? $defeito_constatado : 'null';
					}

					$defeito_reclamado_descricao        = $_POST["defeito_reclamado_descricao_".$i];
					if (in_array($login_fabrica, array(141,144)) && strlen($serie) > 0) {
	                   $serie_validar = "#".$_POST["produto_serie_".$i]."#";
	                   if (in_array($serie_validar, $array_series)) {
	                        $msg_erro = traduz("Número de Série repetido");
	                        $array_serie_duplicada[] = $serie_validar;
	                  	} else {
	                  	    $array_series[] = $serie_validar;
	                   }
	              	}

	              	if($login_fabrica == 3){
						$xproduto_serie = "'".$_POST["produto_serie_".$i]."'";
						if(!empty($_POST['produto_serie_'.$i])) {
							include 'valida_serie_bloqueada.php';
						}
	          	    }

					$data_fabricacao    = $_POST["data_fabricacao_".$i];

					if($login_fabrica == 91){
						if(strlen(trim($referencia)) > 0){
							if(!$data_fabricacao) {
								$msg_erro = traduz("Informe a Data de Fabricação.");
							}
						}
					}

					if($login_fabrica==50) {
						if(strlen($msg_erro)==0){
							if($referencia <> '' && $serie <> ''){
								if(!$data_fabricacao) {
									$msg_erro = traduz("Informe a Data de Fabricação.");
								}
							}
						}
					}

					if(($login_fabrica==50 || $login_fabrica == 74 || $login_fabrica == 91) && strlen($data_fabricacao) > 0){

						$data_fabricacao_modif = preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$3-$2-$1', $data_fabricacao);


						/* ==============================*/

						if(strlen($msg_erro)==0){
							list($df, $mf, $yf) = explode("/", $data_fabricacao);

							if(!checkdate($mf,$df,$yf))
								$msg_erro = "Data Inválida";
						}

						if(strlen($msg_erro)==0){
							$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
							$resX = pg_query ($con,$sqlX);
							$aux_atual = pg_fetch_result ($resX,0,0);
						}

						if(empty($aux_atual)){
							$msg_erro = "Data Inválida";
						}

						if(strlen($msg_erro)==0){
							$sqlX = "SELECT '$aux_atual'::date  > '$data_fabricacao_modif'";
							$resX = pg_query($con,$sqlX);
							$periodo_data = pg_fetch_result($resX,0,0);
						}
						if($periodo_data == f){
							$msg_erro = "Data Inválida";
						}

						if(strlen($msg_erro)==0){
							$condicao_data_fabricao = ",data_fabricacao";
						}
					}

					/* =============================*/

					if (strlen($embalagem_original) == 0) $embalagem_original = "f";
					if (strlen($sinal_de_uso) == 0)       $sinal_de_uso = "f";
					//echo "Qtde: $aux_qtde";
					if ($login_fabrica == 19) {
						if (($aux_qtde) == 0 ) {
							if(strlen($referencia)>0) $msg_erro = traduz("favor.indicar.quantidade.de.produtos",$con,$cook_idioma);
						}
					}else{
						if (strlen($aux_qtde) == 0) $aux_qtde = "1";
					}

					if ($login_fabrica == 6 AND strlen($serie) > 0 AND strlen($referencia) == 0) {
						$serie_pesquisa = substr($serie,0,3);
						$sqlX = "SELECT tbl_produto.referencia
								FROM tbl_produto
								JOIN tbl_linha USING (linha)
								WHERE tbl_produto.radical_serie = $serie_pesquisa
								AND tbl_linha.fabrica = $login_fabrica;";
	//if ($ip == '201.0.9.216') echo "$i : $sqlX<BR><BR>";
						$resX = pg_exec($con,$sqlX);
						if (pg_numrows($resX) == 1) {
							$referencia = trim(pg_result($resX,0,0));
	//if ($ip == '201.0.9.216') echo "Ref. : $referencia<BR><BR>";
						}else{
							$msg_erro .= traduz("numero.de.serie.e.invalido",$con,$cook_idioma);
						}
					}

					if($login_fabrica == 40) { // HD 205803
						$serie = substr($serie,0,2)."".str_pad(substr($serie,2),7,"0",STR_PAD_LEFT);
						$serie = "'". strtoupper(trim($serie)) ."'";
					}else{
						if (strlen($serie) == 0 OR $login_fabrica==19 or $login_fabrica == 30)	$serie = "null";
						else						$serie = "'". $serie ."'";
					}

					if (strlen($type) == 0)		$type = "null";
					else						$type = "'". $type ."'";

					if (strlen($defeito_reclamado_descricao) == 0 and strlen($referencia) > 0) {
						if($login_fabrica == 50 OR $login_fabrica == 94 ){
							$msg_erro .= traduz("defeito.reclamado.do.produto.e.obrigatorio",$con,$cook_idioma);
						}else{
							$xdefeito_reclamado_descricao = " null ";
						}
					}else{
						$xdefeito_reclamado_descricao = ($login_fabrica == 50) ? $defeito_reclamado_descricao : "'".$defeito_reclamado_descricao."'";
					}

					if(strlen($data_fabricacao) > 0){
						$xdata_fabricacao = preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$3-$2-$1', $data_fabricacao);
						$condicao_data_fabricao_value = ",'$xdata_fabricacao'";
					}

					if(empty($xdata_fabricacao)){
						$xdata_fabricacao = null;

					}

					$xxxos = 'null';
	//if ($ip == '201.0.9.216') echo "Erro XX ($i): $msg_erro<br><br>";

					if ($login_fabrica == 6 and strlen($referencia) > 0) {

						$os_reincidente = "'f'";

						$sqlX = "SELECT to_char (current_date - INTERVAL '30 days', 'YYYY-MM-DD')";
						$resX = pg_exec($con,$sqlX);
						$data_inicial = pg_result($resX,0,0)." 00:00:00";

						$sqlX = "SELECT to_char (current_date, 'YYYY-MM-DD')";
						$resX = pg_exec($con,$sqlX);
						$data_final = pg_result($resX,0,0)." 23:59:59";

						if (strlen($serie) > 0) {
							$sql = "SELECT  tbl_os.os            ,
											tbl_os.sua_os        ,
											tbl_os.data_digitacao,
											tbl_os_extra.extrato
									FROM    tbl_os
									JOIN    tbl_os_extra ON tbl_os_extra.os = tbl_os.os
									WHERE   tbl_os.serie   = $serie
									AND     tbl_os.fabrica = $login_fabrica
									AND     tbl_os.posto   = $login_posto
									AND     tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'
									ORDER BY tbl_os.data_digitacao DESC
									LIMIT 1";
							$resZ = pg_exec($con,$sql);
	//if ($ip == '201.0.9.216') echo "SQL ($i): $sql<br><br>";

							if (pg_numrows($resZ) > 0) {
								$xxxos      = trim(pg_result($resZ,0,os));
								$xxxsua_os  = trim(pg_result($resZ,0,sua_os));
								$xxxextrato = trim(pg_result($resZ,0,extrato));

								if (strlen($xxxextrato) == 0) {
									$msg_erro_serie .= traduz("n.de.serie.%.digitado.e.reincidente.%.favor.consultar.a.ordem.de.servico.%.e.acrescentar.itens.%.em.caso.de.duvida.entre.em.contato.com.a.fabrica.",$con,$cook_idioma,array("$produto_serie","<BR>","$xxxsua_os","<BR>"));
									$linha_erro = $i;
	//if ($ip == '201.0.9.216') echo "Erro interno ($i): $msg_erro_serie<br><br>";
								}else{
									$os_reincidente = "'t'";
								}
							}
						}
					}
	//if ($ip == '201.0.9.216') echo "Erro interno ($i): $msg_erro_serie<br><br>";

					if (strlen($msg_erro_serie) > 0) {
						$msg_erro = $msg_erro_serie;
						break ;
					}

	//if ($ip == '201.0.9.216') echo "Erro ($i): $msg_erro<br><br>";

					if (strlen($msg_erro) == 0) {
	//if ($ip == '201.0.9.216') echo "Referencia ($i): $referencia + erro: $msg_erro<br><br>";

						if (strlen ($referencia) > 0) {
							$referencia = strtoupper ($referencia);
							$referencia = str_replace ("-","",$referencia);
							$referencia = str_replace (".","",$referencia);
							$referencia = str_replace ("/","",$referencia);
							$referencia = str_replace (" ","",$referencia);
							$referencia = "'". $referencia ."'";

							$sql = "SELECT  produto
									FROM    tbl_produto
									JOIN    tbl_linha USING (linha)
									WHERE   upper(referencia_pesquisa) = $referencia
									AND     tbl_linha.fabrica = $login_fabrica
									AND (tbl_produto.ativo IS TRUE or tbl_produto.parametros_adicionais::jsonb->>'ativacao_automatica' = 't' )";
							$res = pg_exec ($con,$sql);

							if (pg_numrows ($res) == 0) {
								$msg_erro = traduz("produto.%.nao.cadastrado",$con,$cook_idioma,array("$referencia"));
								$linha_erro = $i;
							}else{
								$produto   = pg_result ($res,0,produto);
							}
							if($login_fabrica==19){
								$sql = "SELECT  *
										FROM    tbl_tipo_atendimento_mao_obra
										WHERE   produto = $produto
										AND     mao_de_obra>0
										AND     tipo_atendimento = 6";

								$res = pg_exec ($con,$sql);
								if (pg_numrows ($res) == 0) {
									$msg_erro = traduz("produto.%.com.valor.de.mao.de.obra.para.troca.nao.cadastrado",$con,$cook_idioma,array("$referencia"));
									$linha_erro = $i;
								}
							}
							if (strlen($capacidade) == 0)
								$xcapacidade = 'null';
							else
								$xcapacidade = "'".$capacidade."'";

							if (strlen($aux_nota_fiscal)==0) {
								$aux_nota_fiscal = $xnota_fiscal;
							}else{
								if ( !in_array($login_fabrica, array(11,172)) ){
									$aux_nota_fiscal = "'".$aux_nota_fiscal."'";
								}
							}
							if (strlen($rg_produto)==0) {
								$rg_produto = 'null';
							}else {
								$rg_produto = "'".$rg_produto."'";
							}

							if (in_array($login_fabrica, array(141,144))) {
								$valor_unitario = $_POST['valor_unitario_'.$i];

								$rg_produto = "'".str_replace(",", ".", str_replace(".", "", $valor_unitario))."'";
							}

							if ($login_fabrica == 151) {
								$data_nf_item = $_POST['data_nf_'.$i];

								if(!empty($data_nf_item)){
									list($dia, $mes, $ano) = explode("/", $data_nf_item);

									if (!checkdate($mes, $dia, $ano)) {
										$msg_erro = traduz("Data da nota fiscal inválida");
										$linha_erro = $i;
									}

									$data_nf_item = $ano."-".$mes."-".$dia;
								}

								$xdata_nf = (empty($data_nf_item)) ? $xdata_nf_geral : "'".$data_nf_item."'";
							}

							if($login_fabrica == 153 and strlen($produto) > 0) {
								$sql = "SELECT numero_serie_obrigatorio FROM tbl_produto
										WHERE produto = $produto
										AND fabrica_i = $login_fabrica
										AND numero_serie_obrigatorio = 't'";
								$res = pg_query($con, $sql);
								if(pg_num_rows($res)>0){
									if(strlen($serie) > 0) {
										$aux_serie = str_replace("'","",$serie);
										$primeiro_caracter = substr($aux_serie, 0,1);
										$ano = substr($aux_serie, 1,2);
										$mes = substr($aux_serie, 3,2);
										$finais = substr($aux_serie, 5,5);

										$ano_limite = date("y") - 5;

										$array_primeiro = array(7,8,3);

										if(!in_array($primeiro_caracter, $array_primeiro)){
											$erro = TRUE;
										}

										if(strlen(trim($aux_serie)) != 10){
											$erro = TRUE;
										}

										if($ano < $ano_limite or $ano > date("y")){
											$erro = true;
										}

										if($mes > 12){
											$erro = true;
										}

										if($erro == 1 and $aux_serie <> 'S/N'){
											$msg_erro = traduz("Número de série inválido para produto %",null,null, [$referencia]);
											$erro = "";
										}
									}else{
										$msg_erro = "Informar número de série para produto $referencia";
									}
								}


							}

							if($login_fabrica == 137 && $posto_interno){
								$data_nf_item   = $_POST['data_nf_'.$i];
								$cfop 			= $_POST['cfop_'.$i];
								$valor_unitario = $_POST['valor_unitario_'.$i];
								$qtde 			= $_POST["aux_qtde_".$i];
								//$valor_total 	= $_POST['total_valor_nf'];
								$valor_total = $qtde  * $valor_unitario;

								$valor_total = number_format($valor_total, 2, '.', '');

								if(!empty($xcfop)){

									$cfop = (empty($cfop)) ? $xcfop : $cfop;

								}

								$arr_registro = array(
									"cfop" 	=> $cfop,
									"vu" 	=> $valor_unitario,
									"vt" 	=> $valor_total,
								);

								$rg_produto = json_encode($arr_registro);
								$rg_produto = "'".$rg_produto."'";

								if(!empty($data_nf_item)){
									list($dia, $mes, $ano) = explode("/", $data_nf_item);
									$data_nf_item = $ano."-".$mes."-".$dia;
								}

								$xdata_nf = (empty($data_nf_item)) ? $xdata_nf : "'".$data_nf_item."'";

							}

							if($login_fabrica == 50){
								$cond_defeito = " defeito_reclamado";
							}else if($login_fabrica == 94){
								$cond_defeito = " defeito_constatado_descricao";
							}else{
								$cond_defeito = " defeito_reclamado_descricao";
							}

							if($login_fabrica == 164){
								$campos_extra = " defeito_constatado_descricao,  ";
								$campos_value = " $defeito_constatado, ";

								$rg_produto = $destinacao;
							}

							if (strlen ($msg_erro) == 0) {
								$sql = "INSERT INTO tbl_os_revenda_item (
											os_revenda            ,
											produto               ,
											serie                 ,
											nota_fiscal           ,
											data_nf               ,
											capacidade            ,
											type                  ,
											$cond_defeito		  ,
											embalagem_original    ,
											sinal_de_uso          ,
											os_reincidente        ,
											qtde                  ,
											rg_produto            ,
											$campos_extra
											reincidente_os
											$condicao_data_fabricao
										) VALUES (
											$os_revenda           ,
											$produto              ,
											$serie                ,
											$aux_nota_fiscal      ,
											$xdata_nf             ,
											$xcapacidade          ,
											$type                 ,
											$xdefeito_reclamado_descricao,
											'$embalagem_original' ,
											'$sinal_de_uso'       ,
											$os_reincidente       ,
											$aux_qtde             ,
											$rg_produto           ,
											$campos_value
											$xxxos
											$condicao_data_fabricao_value
										) RETURNING os_revenda_item";
								//if ($ip == '201.42.45.176')
								//echo "Insert item $i$sql<BR><BR>";
								//echo $sql."<BR>";
										//echo nl2br($sql);
								$res = pg_exec ($con,$sql);
								$msg_erro = pg_errormessage($con);
								//echo "erro = ". $msg_erro;
								if (strlen ($msg_erro) > 0) {
									break ;
								}else{
									$os_revenda_item = pg_fetch_result($res,0,0);

									if($login_fabrica == 3){
										$sql = "
											SELECT validar_serie
											FROM tbl_produto
											WHERE produto = {$produto}
											LIMIT 1
																		";
										$res = pg_query($con, $sql);
										$validar_produto = pg_fetch_result($res, 0, 'validar_serie');

										if($validar_produto == 't') {

											$sql = "SELECT fn_valida_numero_serie_britania($serie)";
											$res = pg_query($con,$sql);

											$serie_valida = pg_fetch_result($res,0,0);

											if($serie_valida != "t"){
												$msg_erro = "Número de série $serie incorreto";
											}else{
												$sql = "SELECT fn_valida_mascara_serie_britania($serie,$produto,$login_fabrica)";
												$res = pg_query($con,$sql);
												$msg_erro = pg_errormessage($con);
											}
										}
										if (strlen ($msg_erro) > 0) {
											break;
										}
									}
								}
							}
						}
					}
				}

				if (strlen($msg_erro) == 0){
					$sql = "SELECT fn_valida_os_revenda($os_revenda,$login_posto,$login_fabrica)";
					//if ($ip == '201.0.9.216') echo "Funcao : $sql<BR><BR>";
					$res = @pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);
				}

			}

			if (strlen ($msg_erro) == 0) { // HD 321132 - Inicio

				/* ===========INÍCIO DO PROCESSO DA IMAGEM =============== */
				//zambaa
				if ($anexaNotaFiscal) {

					$qt_anexo = 0;

					foreach($_FILES['foto_nf'] as $files){
					// 	if(strlen($_FILES['foto_nf']['name'][$qt_anexo]) == 0){
					// 		$msg_erro = "O anexo da Nota Fiscal é obrigatório";
					// 		continue;
					// 	}

						$dados_anexo['name']      = $_FILES['foto_nf']['name'][$qt_anexo];
						$dados_anexo['type']      = $_FILES['foto_nf']['type'][$qt_anexo];
						$dados_anexo['tmp_name']  = $_FILES['foto_nf']['tmp_name'][$qt_anexo];
						$dados_anexo['error']     = $_FILES['foto_nf']['error'][$qt_anexo];
						$dados_anexo['size']      = $_FILES['foto_nf']['size'][$qt_anexo];

						if(in_array($login_fabrica, array(81,99))) {
							if(strlen($dados_anexo['name']) == 0) {
								$msg_erro = "O anexo da Nota Fiscal é obrigatório";
							}else{
								$anexou = anexaNF("r_$os_revenda", $dados_anexo); // Para anexar uma imagem de NF de os revenda, colocar um 'r' antes do nº
							}
						}
					}

					if(($login_fabrica == 137 AND $posto_interno <> true) or $login_fabrica == 3){

						if ($dados_anexo['name'] == '') {
							$msg_erro = "O anexo da Nota Fiscal é obrigatório";
						}else{
							$anexou = anexaNF("r_$os_revenda", $dados_anexo); // Para anexar uma imagem de NF de os revenda, colocar um 'r' antes do nº
							if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' é que executou OK
						}
					}

					if(!in_array($login_fabrica,array(3,81,99,137))){ // haystack)$login_fabrica <> 99 AND $login_fabrica <> 137){
						if ($dados_anexo['name'] != '') {
							$anexou = anexaNF("r_$os_revenda", $dados_anexo); // Para anexar uma imagem de NF de os revenda, colocar um 'r' antes do nº
							if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' é que executou OK
						}else{
							if(in_array($login_fabrica,array(136)) OR ($login_fabrica == 153 AND $login_posto == 20564) ){
								    $msg_erro = "O anexo da Nota Fiscal é obrigatório";
							} else if ($login_fabrica == 3) {
								$sqlVerificaAnexos = "SELECT tdocs, count(*) FROM tbl_tdocs WHERE referencia_id = '" . $_POST['os_revenda'] . "' GROUP BY tdocs;";
								$resAnexos = pg_query($con, $sqlVerificaAnexos);

								$tdocsId = pg_fetch_result($resAnexos, 0, 'tdocs');

								$sqlAtivos = "SELECT situacao FROM tbl_tdocs WHERE tdocs = " . $tdocsId . ";";
								$resAtivos = pg_query($con, $sqlAtivos);

								if (pg_fetch_result($resAtivos, 0, 'situacao') == 'inativo') {
									$msg_erro = 'O anexo da Nota Fiscal é obrigatório';
								}
							}
						}
					}

					$qt_anexo++;
				}
				 //HD-6889782
				 if ($login_fabrica == 157 or ($login_fabrica == 165 and !$login_posto_interno)) {
				 	   if($fabricaFileUploadOS){
				 	   		$anexo_chave_tdocs = trim($_POST['anexo_chave']);
				 	   		$sql = "SELECT tdocs
                        			FROM tbl_tdocs
                        			WHERE hash_temp = '$anexo_chave_tdocs'
                        			AND obs ~'\"typeId\":\"notafiscal\"'
                        			AND fabrica = $login_fabrica";
                             $res = pg_query($con, $sql);
	                       if(pg_num_rows($res) == 0){
	                       	    $msg_erro = 'O anexo da Nota Fiscal é obrigatório';
					 	   }
					    }
				 }


				if (array_key_exists('foto_nf_2', $_FILES) && in_array($login_fabrica, array(153)) && $login_posto == 20564) {
                    if($_FILES['foto_nf_2']['name'] == ''){
                        $msg_erro = "O anexo da Nota Fiscal é obrigatório";
                    } else {
                        $anexou = anexaNF("r_{$os_revenda}", $_FILES['foto_nf_2']); // Para anexar uma imagem de NF de os revenda,
                        if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' ï¿½ que executou OK
                    }
		        }

				if (array_key_exists('foto_nf_2', $_FILES) AND in_array($login_fabrica, array(157))) {
		   		    if($_FILES['foto_nf_2']['name'] == ''){
	                        $msg_erro = "O anexo da Nota Fiscal é obrigatório";
	                    } else {
	                        $anexou = anexaNF("r_{$os_revenda}", $_FILES['foto_nf_2']); // Para anexar uma imagem de NF de os revenda,
	                        if ($anexou !== 0) $msg_erro .= (is_numeric($anexou)) ? $msgs_erro[$anexou] : $anexou; // '0' ï¿½ que executou OK
	                    }
		        }


				/* ===========FIM DO PROCESSO DA IMAGEM ================ */

				//upload do segundo anexo da lenoxx
				if(in_array($login_fabrica,array(151))){
					$tipo_anexo	= tipo_anexo($tempUniqueId);
					if (!in_array('notafiscal', $tipo_anexo)) {
						$msg_erro = "Favor anexar a nota fiscal" ;
					}

				}
			} // HD 321132 - FIM

			if (strlen ($msg_erro) == 0) {

				if (!empty($os_revenda)) {
	                if (strlen(trim($_POST['anexo_chave'])) > 0) {
	                    $anexo_chave_tdocs = $_POST['anexo_chave'];

	                    if (!empty($anexo_chave_tdocs)) {
	                        $sql_update_tdocs = "UPDATE tbl_tdocs SET referencia_id = '$os_revenda' WHERE hash_temp = '$anexo_chave_tdocs' AND fabrica = $login_fabrica";
	                        $res_update_tdocs = pg_query($con, $sql_update_tdocs);
	                    }
	                }
	            }

				if (!empty($preos)) {

					$paramPreos = "&preos={$preos}";

				}

				$res = pg_exec ($con,"COMMIT TRANSACTION");
				header("Location: os_revenda_finalizada.php?os_revenda={$os_revenda}{$paramPreos}");
				exit;
			}else{
				if (strpos ($msg_erro,"tbl_os_revenda_unico") > 0) $msg_erro = traduz("o.numero.da.ordem.de.servico.do.fabricante.ja.esta.cadastrado",$con,$cook_idioma);
				if (strpos ($msg_erro,"null value in column \"data_abertura\" violates not-null constraint") > 0) $msg_erro = traduz("data.da.abertura.deve.ser.informada",$con,$cook_idioma);

				$os_revenda = '';
				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			}

		}

	}

} else if (!empty($_REQUEST['preos'])) {

	$hd_chamado = $_REQUEST['preos'];

	if (isset($_GET['preos'])) {

			$sqlCallcenter = "SELECT tbl_hd_chamado_extra.nota_fiscal,
									 to_char(tbl_hd_chamado_extra.data_nf, 'dd/mm/yyyy') as data_nota,
									 tbl_hd_chamado_extra.nome as nome_revenda,
									 tbl_hd_chamado_extra.cpf as cnpj_revenda,
									 tbl_hd_chamado_extra.fone as telefone_revenda,
									 tbl_hd_chamado_extra.email as email_revenda
							  FROM tbl_hd_chamado
							  JOIN tbl_hd_chamado_extra USING(hd_chamado)
							  WHERE tbl_hd_chamado.hd_chamado = {$hd_chamado}";
			$resCallcenter = pg_query($con, $sqlCallcenter);

			$revenda_nome 	  = pg_fetch_result($resCallcenter, 0, 'nome_revenda');
			$revenda_cnpj 	  = pg_fetch_result($resCallcenter, 0, 'cnpj_revenda');
			$revenda_fone 	  = pg_fetch_result($resCallcenter, 0, 'telefone_revenda');
			$revenda_email 	  = pg_fetch_result($resCallcenter, 0, 'email_revenda');
			$data_nf 		  = pg_fetch_result($resCallcenter, 0, 'data_nota');
			$nota_fiscal 	  = pg_fetch_result($resCallcenter, 0, 'nota_fiscal');

	} else {

		$revenda_nome 	  = $_POST['revenda_nome'];
		$revenda_cnpj 	  = $_POST['revenda_cnpj'];
		$revenda_fone 	  = $_POST['revenda_fone'];
		$revenda_email 	  = $_POST['revenda_email'];
		$data_nf 		  = $_POST['data_nf'];
		$nota_fiscal 	  = $_POST['nota_fiscal'];

	}

}

if(strlen($msg_erro) == 0 AND strlen($os_revenda) > 0){
	if(in_array($login_fabrica, [3,117])){
                $campos = " tbl_revenda_fabrica.contato_razao_social AS revenda_nome , tbl_revenda_fabrica.cnpj AS revenda_cnpj , tbl_revenda_fabrica.contato_fone AS revenda_fone, tbl_revenda_fabrica.contato_email AS revenda_email, ";
                $join_revenda = " JOIN tbl_revenda_fabrica ON  tbl_os_revenda.revenda = tbl_revenda_fabrica.revenda AND tbl_revenda_fabrica.fabrica = $login_fabrica";
        }else{
        	if ($login_fabrica == 164 && $interno_p) {
        		$campos = " tbl_revenda.nome  AS revenda_nome, tbl_revenda.cnpj  AS revenda_cnpj, tbl_revenda.fone  AS revenda_fone, tbl_revenda.email AS revenda_email, tbl_estado.estado AS revenda_estado, ";
                $join_revenda = " JOIN tbl_revenda ON  tbl_os_revenda.revenda = tbl_revenda.revenda
                				  LEFT JOIN tbl_cidade ON tbl_revenda.cidade = tbl_cidade.cidade
                				  LEFT JOIN tbl_estado ON tbl_cidade.estado = tbl_estado.estado";
        	} else {
                $campos = " tbl_revenda.nome  AS revenda_nome, tbl_revenda.cnpj  AS revenda_cnpj, tbl_revenda.fone  AS revenda_fone, tbl_revenda.email AS revenda_email, ";
                $join_revenda = " JOIN tbl_revenda ON  tbl_os_revenda.revenda = tbl_revenda.revenda ";
        	}
        }

	// seleciona do banco de dados
	$sql = "SELECT  tbl_os_revenda.sua_os                                                ,
					tbl_os_revenda.obs                                                   ,
					tbl_os_revenda.contrato                                              ,
					to_char(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura  ,
					to_char(tbl_os_revenda.data_nf      ,'DD/MM/YYYY') AS data_nf        ,
					tbl_os_revenda.nota_fiscal                                           ,
					tbl_os_revenda.consumidor_nome                                       ,
					tbl_os_revenda.consumidor_cnpj                                       ,
					$campos
					tbl_os_revenda.explodida                                             ,
					tbl_os_revenda.valor_adicional_justificativa                                             ,
					tbl_os_revenda.tipo_atendimento                                      ,
					tbl_os_revenda_item.os_revenda_item                                  ,
					tbl_os_revenda.tipo_os as motivo                                     ,
					tbl_os_revenda.campos_extra
			FROM	tbl_os_revenda
			LEFt JOIN tbl_os_revenda_item ON tbl_os_revenda_item.os_revenda = tbl_os_revenda.os_revenda
			LEFT $join_revenda
			JOIN	tbl_fabrica on tbl_fabrica.fabrica = tbl_os_revenda.fabrica
			JOIN    tbl_posto           ON  tbl_posto.posto   = tbl_os_revenda.posto
			JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
			WHERE	tbl_os_revenda.os_revenda = $os_revenda
			AND		tbl_os_revenda.posto      = $login_posto
			AND		tbl_os_revenda.fabrica    = $login_fabrica ";
	$res = pg_exec($con, $sql);
//	if (getenv("REMOTE_ADDR") == "201.0.9.216") echo nl2br($sql)."<br>".pg_numrows($res); exit;

	if (pg_numrows($res) > 0){
		$qtde_item = pg_num_rows($res) + 3;
		if ($login_fabrica == 3 && $qtde_item > 40)
			$qtde_item = 40;
		if ( $login_fabrica == 3 && $_POST['qtde_linhas'] > $qtde_item) {
			$qtde_item = $qtde_linhas;
		} else {
			$qtde_linhas = $qtde_item;
		}

		$sua_os           = pg_result($res,0,sua_os);
		$data_abertura    = pg_result($res,0,data_abertura);
		$data_nf          = pg_result($res,0,data_nf);
		$nota_fiscal      = pg_result($res,0,nota_fiscal);
		$revenda_nome     = pg_result($res,0,revenda_nome);
		$revenda_cnpj     = pg_result($res,0,revenda_cnpj);
		$revenda_fone     = pg_result($res,0,revenda_fone);
		$revenda_email    = pg_result($res,0,revenda_email);
		$obs              = pg_result($res,0,obs);
		$contrato         = pg_result($res,0,contrato);
		$explodida        = pg_result($res,0,explodida);
		$os_revenda_item  = pg_result($res,0,os_revenda_item);
		$tipo_atendimento = pg_result($res,0,tipo_atendimento);
		$motivo           = pg_result($res,0,motivo);
		$consumidor_cnpj  = pg_result($res,0,consumidor_cnpj);
		$consumidor_nome  = pg_result($res,0,consumidor_nome);

		if ($login_fabrica == 164 && $interno_p) {
			$campo_extra      = pg_result($res,0,'campos_extra');
	                if (!empty($campo_extra)) {
        	                $campo_extra = json_decode($campo_extra, true);
                	        $revenda_fantasia = $campo_extra['revenda_fantasia'];
                	}
			$revenda_estado   = pg_result($res,0,'revenda_estado');
		}

		if($login_fabrica == 137 && $posto_interno){
			$dados_adicionais  = pg_result($res,0,valor_adicional_justificativa);

			$dados_adicionais 		= json_decode($dados_adicionais);
			$transportadora 		= $dados_adicionais->transportadora;
			$nota_fiscal_saida 		= $dados_adicionais->nota_fiscal_saida;
			$data_nota_fiscal_saida = $dados_adicionais->data_nota_fiscal_saida;

		}

		if (strlen($explodida) > 0 && strlen($os_revenda_item) > 0 && $login_fabrica != 137){
			header("Location:os_revenda_parametros.php");
			exit;
		}

		$sql = "SELECT *
				FROM   tbl_os
				WHERE  sua_os ILIKE '$sua_os-%'
				AND    posto   = $login_posto
				AND    fabrica = $login_fabrica";
		$resX = pg_exec($con, $sql);

		if (pg_numrows($resX) == 0) $exclui = 1;

		$sql = "SELECT  tbl_os_revenda.nota_fiscal,
						tbl_os_revenda_item.rg_produto,
						to_char(tbl_os_revenda.data_nf, 'DD/MM/YYYY') AS data_nf
				FROM	tbl_os_revenda_item
				JOIN	tbl_os_revenda ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
				WHERE	tbl_os_revenda.os_revenda = $os_revenda
				AND		tbl_os_revenda.posto      = $login_posto
				AND		tbl_os_revenda.fabrica    = $login_fabrica
				AND		tbl_os_revenda_item.nota_fiscal NOTNULL
				AND		tbl_os_revenda_item.data_nf     NOTNULL LIMIT 1";
		$res = pg_exec($con, $sql);

		if (pg_numrows($res) > 0){
			$nota_fiscal = pg_result($res,0,nota_fiscal);
			$data_nf     = pg_result($res,0,data_nf);
		}
	}else{
		header('Location: os_revenda.php');
		exit;
	}
}

$title          = traduz("cadastro.de.ordem.de.servico.revenda",$con,$cook_idioma);
$layout_menu    = 'os';

include "cabecalho.php";

// HD-7144987
if ($login_fabrica == 24) {
  if (verifica_posto_bloqueado_os($login_posto)) {
    
    $dados_os = retrona_os_bloqueada_interacao_posto($login_posto);

    if (count($dados_os) > 0) {
      $oss_posto = implode(",", $dados_os);
    }

    echo "<br><br><h3 style='background-color: #ff6161;'><center>A abertura de novas OS's bloqueado, favor responder as interação das OS's pendentes ! OS's: $oss_posto</center></h3>";
    die;
  }
}

$sql = "SELECT digita_os FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = @pg_exec($con,$sql);
$digita_os = pg_result ($res,0,0);
if ($digita_os == 'f') {
	echo "<H4>"; fecho("sem.permissao.de.acesso",$con,$cook_idioma); echo "</H4>";
	exit;
}

include "javascript_pesquisas.php";
include "javascript_calendario_new.php";
include 'js/js_css.php'; /* Todas libs js, jquery e css usadas no Assist - HD 969678 */

if($login_fabrica == 86) {

?>
    <h4>
        <?= traduz('Comunicado a Rede Credenciada de Postos Famastil F-Power e Taurus Premium.') ?>
<br />
        <?= traduz('A opção OS de Revenda está desabilitada. Agora, todas as ordem de serviço devem ser abertas com a nota fiscal de venda para o consumidor final. <br />Não estaremos mais aceitando nota de remessa em conserto.') ?>
    </h4>
 <!--   <script type="text/javascript">
        $(function(){
          $('input[rel="nota_fiscal"]').numeric();
          $('input[name^="aux_nota_fisca"]').numeric();

        });
    </script>-->
<?php
include "rodape.php";
exit;
}

if (in_array($login_fabrica, array(141,144))) {
?>
	<script src="plugins/price_format/jquery.price_format.1.7.min.js" ></script>
	<script src="plugins/price_format/config.js" ></script>
<?php
}
?>

<script type="text/javascript">
	function formatar(src, mask){
		var i = src.value.length;
		var saida = mask.substring(0,1);
		var texto = mask.substring(i)
		if (texto.substring(0,1) != saida){
			src.value += texto.substring(0,1);
		}
	}

	function excluiAnexo(img) {
		var nf = img.attr('file');
		var excluir_str  = '<?=fecho('confimar.excluir.imagem.nf',$con,$cook_idioma)?>';
		if (confirm(excluir_str) == false) return false;
		$('#nf_table_item').html('<p>Excluindo...</p>');
		$.post('<?=$PHP_SELF?>',
			   'ajax=exclNF&excluir_nf='+nf,
			   function(data) {
				if (data == 'ok') {
					alert("Anexo excluído!");
					var tr = img.parents('tr');
					var td = tr.find('td');
					td.remove();

					if (tr.find(td).length == 0) {
						var	input_html = '<input type="file" accept="image/jpg" ';
						input_html+= 'name="foto_nf[]" class="frm" ';
						input_html+= 'title="Selecione a imagem em formato JPG da Nota Fiscal para anexar à OS">';
						$('#str_nf').css('background-color','#FFFFC0').
									css('font-weight','bold').
									css('font-size',  '10px').
									text('Anexar Arquivo:');
						$('#nf_table_item').css('background-color','#FFFFC0').html(input_html);
					}
				} else {
					alert('<?= traduz("Não foi possível excluir a imagem da NF. Contate com a Telecontrol.") ?>');
				}
		});
	}

	$(document).ready(function() {
		$("input[name=sua_os]").numeric();

		Shadowbox.init();

		/* Exclui a imagem da NF se já existir, e devolve o INPUT file para poder anexar outra */
		$('.exclui_foto').click(function () {
			excluiAnexo($(this));
		});

		$('#nf_table_item img').css('cursor','pointer');
		$('#img_nota').click(function () {
			window.open('<?=$imagem_nf?>','Nota','status=yes,scrollbars=no,width=800,height=600');
		});

		<?php

			if(in_array($login_fabrica, array(141,144))){

				?>

				var qtde_linhas = $('select[name=qtde_linhas]').val();

				<?php

			}

		?>

	});

	function pesquisaRevenda(campo,tipo){
		var campo = campo.value;

		if (jQuery.trim(campo).length > 2){
			Shadowbox.open({
				content: "pesquisa_revenda_nv.php?"+tipo+"="+campo+"&tipo="+tipo,
				player:	 "iframe",
				title:   "Pesquisa Revenda",
				width:   800,
				height:  500
			});
		} else
			alert("<?= traduz('Informar toda ou parte da informação para realizar a pesquisa!') ?>");
	}

	function pesquisaNumeroSerie(serie, produto, posicao){
        var login_fabrica = <?=$login_fabrica?>;
        var serie = (login_fabrica != 165) ? jQuery.trim(serie.value) : serie;

        if (login_fabrica == 165 && serie.length == undefined) {
            serie = jQuery.trim(serie.value);
        }

		if (serie.length > 2){
			Shadowbox.open({
				content:	"produto_serie_pesquisa_nv.php?serie="+serie+"&posicao="+posicao,
				player:	"iframe",
				title:		"Pesquisa Número de Serie",
				width:	800,
				height:	500
			});
		} else {
			alert("<?= traduz('Informar toda ou parte da informação para realizar a pesquisa!') ?>"+serie.length);
        }
	}

	function pesquisaNumeroSerieBritania(serie, produto, posicao) {
		var campo = serie;
		var serie = jQuery.trim(serie.value);
		//var valida = /^\d{10}[A-Z]\d{3}[A-Z]$/;
		var valida = /^\d{10}[A-Z0-9]{5}$/;

		if (campo.value.match(valida)) {
			Shadowbox.open({
				content:	"produto_serie_pesquisa_britania_nv.php?serie="+serie+"&posicao="+posicao,
				player:	"iframe",
				title:		"Pesquisa Número de Serie",
				width:	800,
				height:	500
			});
		}else
			alert("<?= traduz('A pesquisa válida somente para o serial com 15 caracteres no formato NNNNNNNNNNLNNNL ou NNNNNNNNNNNNNNN !') ?>");
	}


	function pesquisaProduto(referencia, descricao, voltagem,tipo, posicao){
		var referencia	= jQuery.trim(referencia.value);
		var descricao	= jQuery.trim(descricao.value);
		var voltagem	= jQuery.trim(voltagem.value);
		var valor		= tipo == 'referencia' ? referencia : descricao ;

		if (valor.length > 2){
			Shadowbox.open({
				content:	"pesquisa_produto_nv.php?"+tipo+"="+valor+"&posicao="+posicao,
				player:	"iframe",
				title:		"Pesquisa Produto",
				width:	800,
				height:	500
			});
		}else
			alert("<?= traduz('Informar toda ou parte da informação para realizar a pesquisa!') ?>");
	}

	function VerificaBloqueioRevenda(cnpj, fabrica){
	  $.ajax({
	      type: "POST",
	      datatype: 'json',
	      url: "./admin/ajax_verifica_bloquei_revenda.php",
	      data: {VerificaBloqueioRevenda: true, cnpj:cnpj, fabrica:fabrica},
	      cache: false,
	      success: function(retorno){
	          var dados = $.parseJSON(retorno);
	          if(dados.retorno.length > 0){
	            alert(dados.retorno);
	          }
	      }
	  });
	}

	function retorna_peca(nome,cnpj,nome_cidade,fone,endereco,numero,complemento,bairro,cep,estado,email){
		gravaDados("revenda_nome",nome);
		gravaDados("revenda_cnpj",cnpj);
		gravaDados("revenda_fone",fone);
		gravaDados("revenda_email",email);
		gravaDados("revenda_cidade",nome_cidade);
		gravaDados("revenda_estado",estado);
		gravaDados("revenda_endereco",endereco);
		gravaDados("revenda_cep",cep);
		gravaDados("revenda_numero",numero);
		gravaDados("revenda_complemento",complemento);
		gravaDados("revenda_bairro",bairro);

		<?php if($login_fabrica == 35){ ?>
	        VerificaBloqueioRevenda(cnpj, <?=$login_fabrica ?> );
	    <?php } ?>
	}

	function retorna_produto(produto,referencia,descricao, posicao, voltagem, informatica){
		gravaDados("produto_referencia_"+posicao,referencia);
		gravaDados("produto_descricao_"+posicao,descricao);

		<?php if($login_fabrica == 162){?>
			if(informatica == 'f'){
				$("#imei_"+posicao).prop('readonly', false);
				$("#informatica_"+posicao).val(informatica);
			}
		<?php }?>

		<?php if($login_fabrica == 164){?>
				busca_defeito_constatado(produto, posicao);
		<?php }?>

		<?php
		if (in_array($login_fabrica, array(141,144))) {
		?>
			$("input[name=valor_unitario_"+posicao+"]").val("0,00").removeAttr('readonly');

			calculaTotal();
		<?php
		}
		?>

		<?php if($login_fabrica == 50){ ?>
		busca_defeito_reclamado(produto, posicao);
		<?php } ?>

	}

	function busca_defeito_constatado(produto, posicao){
        $.ajax({
            url: "os_revenda.php",
            type: "post",
            data: {
                busca_defeito_constatado: true,
                produto: produto
            },
            complete: function(data){
                var options = data.responseText;
                $("select[name='defeito_constatado_"+posicao+"']").html(options);
            }
        });
    }

	function retorna_numero_serie(produto,referencia,descricao, posicao,cnpj,nome,fone,email,serie,data_fabricacao = null){
		gravaDados("produto_referencia_"+posicao,referencia);
		gravaDados("produto_descricao_"+posicao,descricao);
		gravaDados("produto_descricao_"+posicao,descricao);
		<?php if($login_fabrica <> '3') { ?>
			var data = data_fabricacao.split('-');
			data_fabricacao = data[2]+'/'+data[1]+'/'+data[0];
			gravaDados("produto_serie_"+posicao,serie);
			gravaDados("data_fabricacao_"+posicao,data_fabricacao);
		<?php } ?>
		<?php if($login_fabrica == '50') { ?>
			gravaDados("revenda_cnpj",cnpj);
			gravaDados("revenda_nome",nome);
			gravaDados("revenda_fone",fone);
			gravaDados("revenda_email",email);
		<?php } ?>

		//gravaDados("produto_serie_"+posicao,numero_serie);
	}

	function gravaDados(name, valor){
		try {
			$("input[name="+name+"]").val(valor);
		} catch(err){
			return false;
		}
	}

	function busca_defeito_reclamado(produto, posicao){

    	$("input[name='produto_hidden_"+posicao+"']").val(produto);

    	$.ajax({
    		url: "os_revenda.php",
    		type: "post",
    		data: {
    			busca_defeito_reclamado: true,
  				produto: produto
    		},
    		complete: function(data){

    			var options = data.responseText;

    			$("select[name='defeito_reclamado_descricao_"+posicao+"']").html(options);

    		}
    	});

    }

	function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
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

	function fnc_pesquisa_revenda_consumidor (campo, tipo, consumidor_revenda) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome&consumidor_revenda="+consumidor_revenda;
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj&consumidor_revenda="+consumidor_revenda;
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.nome			= document.frm_os.consumidor_nome;
	janela.cnpj			= document.frm_os.consumidor_cnpj;
	janela.fone			= document.frm_os.consumidor_fone;
	janela.cidade		= document.frm_os.consumidor_cidade;
	janela.estado		= document.frm_os.consumidor_estado;
	janela.endereco		= document.frm_os.consumidor_endereco;
	janela.numero		= document.frm_os.consumidor_numero;
	janela.complemento	= document.frm_os.consumidor_complemento;
	janela.bairro		= document.frm_os.consumidor_bairro;
	janela.cep			= document.frm_os.consumidor_cep;
	janela.email		= document.frm_os.consumidor_email;
	janela.focus();
	}

/* ============= Função PESQUISA DE PRODUTOS ====================
		Nome da Função : fnc_pesquisa_produto (codigo,descricao)
		Abre janela com resultado da pesquisa de Produtos pela
		referência (código) ou descrição (mesmo parcial).
=================================================================*/

function fnc_pesquisa_numero_serie (campo,campo2,campo3,campo4) {
	if (campo3.value != "") {
		var url = "";
		url = "pesquisa_numero_serie_os_revenda.php?produto_serie=" + campo3.value ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
		janela.serie	= campo3;
		janela.data_fabricacao = campo4;
		janela.focus();
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

<? if($login_fabrica == 3) { /* hd 17735 */ ?>
window.onload = function(){
	$("input[rel='produto_serie']").keypress(function(e) {
		var c = String.fromCharCode(e.which);
		var allowed = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890 ';   if (e.which != 8 && allowed.indexOf(c) < 0) return false;
		});
}
<? } ?>
<? if($login_fabrica == 141) { ?>
	window.onload = function(){
		$("input[rel='produto_serie']").on('blur',function(e) {
			var name = $(this).attr('name');
			var posicao = name.charAt(name.length-1);
			if ($(this).val() > 0) {
				$("[name='aux_qtde_" + posicao + "']").attr('readonly', 'readonly');
				$("[name='aux_qtde_" + posicao + "']").val(1);
			} else {
				$("[name='aux_qtde_" + posicao + "']").removeAttr('readonly');
				$("[name='aux_qtde_" + posicao + "']").val("");
			}
		});
	}
<? } ?>
	$(document).ready(function(){
		$("#data_nf").datepick({startdate:'01/01/2000'});
		$("input.hasDatepick").datepick({startdate:'01/01/2000'});
		$('input[rel="data"]').datepick({startdate:'01/01/2000'});
		$("#data_nf").mask("99/99/9999");
		$('input.hasDatepick').mask("99/99/9999");
		$('input[rel="data"]').mask("99/99/9999");
		$("#revenda_cnpj").mask("99.999.999/9999-99");

        $("input[type=file]").change(function(){
            var tamanho = $(this).prop('files')[0]['size'];

            /*if (parseInt(tamanho) > 2097152) {
                alert("Anexo não será aceito pois é maior que 2MB");
                $(this).val("");
            }*/
        });
	});


<? if($login_fabrica == 14 OR $login_fabrica == 30) {?>
	window.onload = function(){
		$("input[rel='nota_fiscal']").keypress(function(e) {
			var c = String.fromCharCode(e.which);
			var allowed = '1234567890';
			if (e.which != 8 && allowed.indexOf(c) < 0) return false;
		});
	}


<? }?>

<? if ($login_fabrica ==45){?>
	$(document).ready(function() {
		$('#data_abertura').readonly(true);
	});
<?}?>

	$(document).ready(function(){
		<?php
		if (in_array($login_fabrica, array(141,144))) {
		?>
			calculaTotal();

			$("input.moeda").priceFormat({
				prefix: '',
	            thousandsSeparator: '.',
	            centsSeparator: ',',
	            centsLimit: 2
			});

			$("input[name^=produto_referencia_],input[name^=produto_descricao_]").blur(function() {
				var tr = $(this).parents("tr");

				var referencia = $(tr).find("input[name^=produto_referencia_]").val();
				var descricao  = $(tr).find("input[name^=produto_descricao_]").val();
				var valor      = $(tr).find("input[name^=valor_unitario_]").val();

				if ((typeof referencia != "undefined" && referencia.length > 0) && (typeof descricao != "undefined" && descricao.length > 0)) {
					$(tr).find("input[name^=valor_unitario_]").removeAttr("readonly");
				} else {
					$(tr).find("input[name^=valor_unitario_]").val("0,00").attr({ "readonly": "readonly" });

					calculaTotal();
				}
			});
		<?php
		} else {
		?>
			somaTotalNF();
		<?php
		}
		?>

	});

	<?php
	if (in_array($login_fabrica, array(141,144))) {
	?>
		function calculaTotal() {
			var total = 0;

			$("tr[id^=linha_serie_]").each(function() {
				var produto_referencia = $(this).find("input[name^=produto_referencia_]").val();
				var produto_descricao  = $(this).find("input[name^=produto_descricao_]").val();

				if ((typeof produto_referencia != "undefined" && produto_referencia.length > 0) && (typeof produto_descricao != "undefined" && produto_descricao.length > 0)) {
					var valor = $(this).find("input[name^=valor_unitario_]").val().replace(/\./, "").replace(/,/, "");

					if (typeof valor == "undefined") {
						$(this).find("input[name^=valor_unitario_]").val("0,00");
					} else {
						total += parseFloat(valor);
					}
				}
			});

			if (total > 0) {
				$("#total_valor_nf").text(fmtMoeda(String(total)));
			} else {
				$("#total_valor_nf").text("0,00");
			}
		}
	<?php
	} else {
	?>
		function calculaTotal(cod){
			var qtde 			= $('input[name=aux_qtde_'+cod+']').val();
			if(qtde != ""){
				var valor_unitario 	= $('input[name=valor_unitario_'+cod+']').val();
				var valor_total 	= parseInt(qtde) * parseFloat(valor_unitario.replace(",", "."));

				if(valor_unitario == ""){
					valor_unitario = 0;
					valor_total = 0;
				}

				$('input[name=valor_unitario_'+cod+']').val(parseFloat(valor_unitario.replace(",", ".")).toFixed(2));
				$('input[name=valor_total_'+cod+']').val(valor_total.toFixed(2));

				somaTotalNF();
			}else{
				$('input[name=aux_qtde_'+cod+']').focus();
				alert('Por favor insira a quantidade!');
			}

		}
	<?php
	}
	?>

	function somaTotalNF(){

		var total_valor_nf = 0;
		var total = 0;

		$('input[name^=valor_total_]').each(function(){
			total = $(this).val();
			if(total.length > 0){
				total_valor_nf = parseFloat(total_valor_nf) + parseFloat(total);
			}
		});

		if(total_valor_nf == 0){
			$('#total_valor_nf').text('00.00');
		}else{
			$('#total_valor_nf').text(total_valor_nf.toFixed(2));
			$("input[name=total_valor_nf]").val(total_valor_nf.toFixed(2));
		}

	}
	<?php
	if (in_array($login_fabrica, [11, 172])) {
	?>
		$(function(){

			$("input[name^=possui_codigo_interno]").click(function(){

				let posicao = $(this).data("posicao");

				if ($(this).val() == 'sim') {
					$("input[name=codigo_interno_"+posicao+"]").show();
				} else {
					$("input[name=codigo_interno_"+posicao+"]").hide().val("");
				}

			});

		});
	<?php
	}
	?>

<?php
	if (in_array($login_fabrica, array(141, 144,165))) {
?>
    $(function() {
        $(document).on("keypress", "input[name^=produto_serie_]", function(e) {
            if ($("#utiliza_leitor_codigo_barras:checked").length > 0) {
<?php
        if (in_array($login_fabrica, array(141, 144))) {
?>
                var tr = $(this).parents("tr")[0];

                var produto_referencia = $(tr).find("input[name^=produto_referencia_]").val();
                var descricao_produto  = $(tr).find("input[name^=produto_descricao_]").val();
                var nota_fiscal        = $(tr).find("input[name^=aux_nota_fiscal_]").val();


                if ($(tr).prevAll("tr").first().length > 0) {
                    if ($(tr).prevAll("tr[id^=linha_serie_]").first().length == 0) {
                        if ($(tr).parents("table").prevAll("table").length > 0) {
                            var prev_tr = $(tr).parents("table").prevAll("table").first().find("tbody").find("tr[id^=linha_serie_]").last();
                        }
                    } else {
                        var prev_tr = $(tr).prevAll("tr[id^=linha_serie_]").first();
                    }

                    var prev_produto_referencia = $(prev_tr).find("input[name^=produto_referencia_]").val();
                    var prev_descricao_produto  = $(prev_tr).find("input[name^=produto_descricao_]").val();
                    var prev_nota_fiscal        = $(prev_tr).find("input[name^=aux_nota_fiscal_]").val();

                    if (produto_referencia.length == 0) {
                        produto_referencia = prev_produto_referencia;
                    }

                    if (descricao_produto.length == 0) {
                        descricao_produto = prev_descricao_produto;
                    }

                    if (nota_fiscal.length == 0) {
                        nota_fiscal = prev_nota_fiscal;
                    }

                    $(tr).find("input[name^=produto_referencia_]").val(produto_referencia);
                    $(tr).find("input[name^=produto_descricao_]").val(descricao_produto);
                    $(tr).find("input[name^=aux_nota_fiscal_]").val(nota_fiscal);
                }

                setTimeout(function() {
                    if ($(tr).nextAll("tr").length == 0) {
                        if ($(tr).parents("table").nextAll("table").length > 0) {
                            $(tr).parents("table").nextAll("table").first().find("tbody").find("tr").first().nextAll("tr").first().find("input[name^=produto_serie_]").focus();
                        }
                    } else {
                        $(tr).nextAll("tr").first().find("input[name^=produto_serie_]").focus();
                    }
				}, 500);
			}
		});
<?php
        } else {
?>
                var serie = $(this).val();
                var nome = $(this).attr("name");
                var aux = nome.split("_");
                var id = aux[2];
                var novoId = parseInt(id) + 1;
                if (e.which == 13) {
                    pesquisaNumeroSerie(serie,'null',id);
                    $("#produto_serie_"+novoId).focus();
                }
            }
        });
<?php
        }
?>
    });
<?php
	}
?>

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
	text-align: left;
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

input[readonly=readonly] {
	background-color: #D9D9D9;
}

</style>

<!-- ============= <HTML> COMEÇA FORMATAÇÃO ===================== -->

<?
if (strlen ($msg_erro) > 0) {
?>
<table border="0" cellpadding="0" cellspacing="0" width='700' align="center" bgcolor="#ffffff">
<tr>
	<td height="27" valign="middle" width='100%' align="center"
         style="padding:4px 8px;background:red;color: white;font-weight:bold">
<?
	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$msg_erro = substr($msg_erro, 6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo $erro . $msg_erro;
?>
	</td>
</tr>
</table>
<?
}
//echo $msg_debug;
?>
<?
if ($ip <> "201.0.9.216" and $ip <> "200.140.205.237" and 1==2) {
?>

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr>
		<td nowrap><font size="1" face="Geneva, Arial, Helvetica, san-serif">ATENÇÃO: <br><br> A PÁGINA FOI RETIRADA DO AR PARA QUE POSSAMOS MELHORAR A PERFORMANCE DE LANÇAMENTO.</font></td>
	</tr>
</table>

<? exit; ?>

<? } ?>

<br>

<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr class="menu_top">
		<td nowrap>
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">
				<? fecho("atencao.%.as.ordens.de.servico.digitadas.neste.modulo.so.serao.validas.apos.o.clique.em.gravar.e.depois.em.explodir",$con,$cook_idioma,array("<br />")); ?>
				<?php
				if(in_array($login_fabrica, array(11,172))){
					echo traduz("<br /> É de suma importância o registro do código interno quando ele existir no produto, caso o produto não contenha o código interno, favor deixar o campo em branco. ");
				}
				?>
			</font>
		</td>
	</tr>
</table>

<?php
if ($login_fabrica == 153) { ?>
	<br>

	<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
		<tr class="menu_top">
			<td nowrap align="center">
			<font size="1" face="Geneva, Arial, Helvetica, san-serif">
				**Prezado posto**
			</font>
			</td>
		</tr>
		<tr class="menu_top">
			<td nowrap align="center">
				<br>
			</td>
		</tr>
		<tr class="menu_top">
			<td nowrap align="left">
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
					**Para abertura de todas as OS's de Revendas será necessário:**<br>
					I. Anexo da imagem da etiqueta com o numero de serie;<br>
					II. Anexo da nota fiscal de compra com o Distribuidor (em caso de aparelho de estoque da revenda)<br>
					e anexo da nota fiscal de remessa para conserto.<br><br>
					**Não será mais permitido apenas o anexo da nota fiscal de remessa para conserto.<br>
					Caso não seja anexado estes arquivos , a garantia será negada e o pagamento de mão de obra <br>
					 não será pago ao posto autorizado.**<br><br>

					**Em caso de dúvidas, favor nos retornar 0800-718-7825 .**<br><br>

					**Agradecemos a compreensão.**

				</font>
			</td>


		</tr>
	</table>
<?
}
//Wellington - O AVISO APARECERÁ NAS 5 PRIMEIRAS OSs LANÇADAS PELO POSTO APÓS A LIBERAÇÃO DO NOVO MÉTODO
$sql = "SELECT count(*)
		FROM tbl_os
		WHERE fabrica = $login_fabrica
		AND posto = $login_posto
		AND data_digitacao > '2007-01-04 08:49:23.107909-02'
		HAVING count(*) > 5
		LIMIT 1";
//$res = pg_exec($con,$sql);
//$login_posto==6359 and
if (in_array($login_fabrica, array(11,172)) && @pg_numrows($res) == 0 && 1 == 2) {
	echo "<center><table width=650><tr><td>";
	echo "<font face='Verdana' size='2' color='#0000FF'><left><b>A partir de 04/01/2007 o número da OS Fabricante não será digitado pelo posto autorizado, este número será gerado automaticamente pelo sistema quando o botão <i><u>Gravar</i></u> for clicado. A via de papel não deverá mais ser preenchida a mão, ela deve ser impressa pelo site e enviada ao fabricante.<br>Em caso de dúvidas entrar em contato com o fabricante.</b></left></font>";
	echo "</td></tr></table><center>";
}
?>

<br>

<table width="700" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
	<tr >
		<td>
			<img height="1" width="20" src="imagens/spacer.gif">
		</td>
		<td valign="top" align="left">
			<!--------------- Formulário -->
			<form name="frm_os" id="frm_os" method="post" action="<? echo $PHP_SELF ?>" enctype='multipart/form-data'>
				<input type="hidden" name="preos" value="<?= $_REQUEST['preos'] ?>" />
				<table width="100%" border="0" cellspacing="3" cellpadding="2">
					<?
						if (strlen($_GET['os_revenda']) > 0)
							$os_revenda = trim($_GET['os_revenda']);
						if (strlen($_POST['os_revenda']) > 0)
							$os_revenda = trim($_POST['os_revenda']);
					?>
					<input type='hidden' name='os_revenda' value='<? echo $os_revenda; ?>'>
					<input name="sua_os" type="hidden" value="<? echo $sua_os ?>">
					<? if ($login_fabrica == 19) { ?>
						<tr class="menu_top">
							<td nowrap >
								<font size="1" face="Geneva, Arial, Helvetica, san-serif">
									<? fecho("tipo.de.atendimento",$con,$cook_idioma); ?>
								</font>
							</td>
							<td nowrap colspan='2'>
								<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? fecho("motivo",$con,$cook_idioma); ?></font>
							</td>
						</tr>
						<tr>
							<td nowrap align='center'>
								<font size="2" face="Geneva, Arial, Helvetica, san-serif">
									<input type='hidden' name="tipo_atendimento" value='6'>
										6-<? fecho("troca",$con,$cook_idioma); ?>
								</font>
							</td>
							<td nowrap align='center' colspan='2'>
								<font size="2" face="Geneva, Arial, Helvetica, san-serif">
									<input type="radio" NAME="motivo" value='12' <? if ($motivo==12)echo "checked";?>>
										&nbsp;<? fecho("inclusao",$con,$cook_idioma); ?> &nbsp;&nbsp;&nbsp;
									<input type="radio" NAME="motivo" value='11' <? if ($motivo==11)echo "checked";?>>
									&nbsp;<? fecho("solicitacao",$con,$cook_idioma); ?>
								</font>
							</td>
						</tr>
					<? } ?>
					<? if (in_array($login_fabrica,array(40,141,144))) { ?>
						</table>
						<table width="100%" border="0" cellspacing="3" cellpadding="2">
						<tr class="menu_top">
							<td nowrap >
								<font size="1" face="Geneva, Arial, Helvetica, san-serif">
									<? fecho("tipo.de.atendimento",$con,$cook_idioma); ?>
								</font>
							</td>
							<?php
							if (in_array($login_fabrica, array(141,144)) && in_array($login_tipo_posto, array(452,453))) {
							?>
								<td nowrap>
									<font size="1" face="Geneva, Arial, Helvetica, san-serif">
										Remanufatura
									</font>
								</td>
							<?php
							}
							?>
						</tr>
						<tr>
							<td nowrap align='center' >
								<font size="2" face="Geneva, Arial, Helvetica, san-serif">
									<input type='hidden' name="tipo_atendimento" value='6'>
										<?
											if (in_array($login_fabrica,array(141,144))) {
												$km_google = " AND km_google IS NOT TRUE ";
											}

											echo '<select name="tipo_atendimento" size="1" class="frm">';
											$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica and ativo $km_google ORDER BY tipo_atendimento";
											$res = pg_exec ($con,$sql) ;
											echo "<option selected></option>";

											if (in_array($login_fabrica,array(165)) && $posto_interno && empty($tipo_atendimento )) {
												$tipo_atendimento = 286;
											}


											if (in_array($login_fabrica, [141])) {
												echo "<option value='175'>Garantia</option>\n";
											} else {
												for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
													echo "<option ";
													if ($tipo_atendimento == pg_result ($res,$i,tipo_atendimento) )
														echo " selected ";
													echo " value='" . pg_result ($res,$i,tipo_atendimento) . "'>" ;
													echo pg_result ($res,$i,tipo_atendimento) . " - " . pg_result ($res,$i,descricao) ;
													echo "</option>\n";
												}
											}
											echo "</select>";

										?>
								</font>
							</td>
							<?php
							if (in_array($login_fabrica, array(141,144)) && in_array($login_tipo_posto, array(452,453))) {
								$checked = ($os_remanufatura == 1) ? "checked" : "";
							?>
								<td nowrap style="text-align: center;">
									<font size="2" face="Geneva, Arial, Helvetica, san-serif">
										<input type='checkbox' name='os_remanufatura' value='1' <?=$checked?> />
									</font>
								</td>
							<?php
							}
							?>
						</tr>
						</table>
						<table width="100%" border="0" cellspacing="3" cellpadding="2">
					<?}?>
					<tr class="menu_top">
						<? if ($pedir_sua_os == 't') { ?>
							<td nowrap>
								<font size="1" face="Geneva, Arial, Helvetica, san-serif">
									<? in_array($login_fabrica, array(157)) ? fecho("os.interna",$con,$cook_idioma) : fecho("os.fabricante",$con,$cook_idioma); ?>
								</font>
							</td>
						<? } ?>
						<td nowrap>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">
								<? fecho("data.abertura",$con,$cook_idioma); ?>
							</font>
						</td>
						<td nowrap>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">
								<? fecho("nota.fiscal",$con,$cook_idioma); ?>
							</font>
						</td>
						<td nowrap>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">
								<? fecho("data.nota",$con,$cook_idioma); ?>
							</font>
						</td>

						<?php
						if($login_fabrica == 137 && $posto_interno){
							?>
							<td nowrap align='center'>
								<font size="1" face="Geneva, Arial, Helvetica, san-serif">
									CFOP
								</font>
							</td>
							<?php
						}
						?>

					</tr>
				<tr>
					<? if ($pedir_sua_os == 't') { ?>
					<td nowrap align='center'>
						<? if ($login_fabrica==5) { ?>
							<input name="sua_os" class="frm" type="text" size="6" maxlength="6" ReadOnly onclick="alert('Mantenha esse campo em branco para geração automática de número de ordem de serviço.\nCaso tenha alguma dúvida, entrar em contato com a Mondial através do 0800-7707810 ou ata@mondialline.com.br');" value="<? echo $sua_os ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;<? fecho("digite.aqui.o.numero.da.os.do.fabricante",$con,$cook_idioma); ?>');" onkeyup="checarNumero(this)">
						<? } else {?>
							<input name="sua_os" class="frm" type="text" size="10" maxlength="10" value="<? echo $sua_os ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;<? fecho("digite.aqui.o.numero.da.os.do.fabricante",$con,$cook_idioma); ?>');" onkeyup="checarNumero(this)">
						<? } ?>
					</td>
					<? } ?>
					<td nowrap align='center'>
<!-- 						<input name="data_abertura" size="12" maxlength="10" value="<? if (strlen($data_abertura) == 0) $data_abertura = date("d/m/Y"); echo $data_abertura; ?>" type="text" class="frm" tabindex="0" <? if ($login_fabrica == 1) echo " readonly";?> > <font face='arial' size='1'> Ex.: <? echo date("d/m/Y"); ?></font> -->


						<? // monteiro
							if ($login_fabrica == 104){
						?>
							<input name="data_abertura" size="11" maxlength="10" value="<? echo $data_abertura = date("d/m/Y"); ?>" type="text" id='data_abertura' readonly ><br><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
						<?
							}else{
								$data_pick   = "";
								$dadosExtras = "";
								if ($login_fabrica == 165 && $posto_interno) {
									$dadosExtras = "readonly='readonly' rel=''";
								}
 								if(strlen($os_revenda) > 0 and in_array($login_fabrica,array(11,15))){
									$data_pick = " ReadOnly='true' ";
								} elseif($login_fabrica <> 11) {
									if (empty($dadosExtras)) {
										$data_pick = "rel='data'";
									}
								}
						?>
						<input name="data_abertura" size="11" maxlength="10" value="<? if (strlen($data_abertura) == 0 and $login_fabrica <> 1) $data_abertura = date("d/m/Y"); echo $data_abertura;?>"  <?php echo $data_pick;?> <?php echo $dadosExtras;?> type="text" id='data_abertura'  <? if ( in_array($login_fabrica, array(11,172)) || $login_fabrica == 140) echo " readonly"; ?> ><br><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
						</td>
						<?
							}
						?>
					<?
						if($login_fabrica ==45){ // HD 31076
							$maxlength = "14";
						}elseif($login_posto==20314 OR $login_fabrica ==14){
							$maxlength = "12";
						}else{
							$maxlength = "8";
						}
					?>
					<td nowrap align='center'>
						<? if ($login_fabrica == 14 || ($login_posto == 14254 && in_array($login_fabrica, array(11,172)) ))
								echo "<input name='nota_fiscal' size='12' maxlength='12' value='$nota_fiscal' type='text' rel='nota_fiscal' class='frm' tabindex='0' >";
							else
								echo "<input name='nota_fiscal' size='8' maxlength='20' rel='nota_fiscal' value='$nota_fiscal' type='text' class='frm' tabindex='0'>";

						?>
					</td>

					<td nowrap align='center'>
						<input name="data_nf" size="11" maxlength="10"value="<? echo $data_nf ?>" type="text" class="frm" tabindex="0" id='data_nf'> <font face='arial' size='1'> Ex.: 10/08/2005</font>
					</td>

					<?php
					if($login_fabrica == 137 && $posto_interno){
						?>
						<td nowrap align='center'>
							<input name="cfop" size="11" maxlength="10"value="<? echo $cfop ?>" type="text" class="frm" tabindex="0" id='cfop' onblur="insereCFOP(this.value)">
						</td>
						<?php
					}
					?>

				</tr>

				<?php

					if($login_fabrica == 137 && $posto_interno){

						?>

						<tr class="menu_top">
							<td nowrap align='center' colspan="2">
								<font size="1" face="Geneva, Arial, Helvetica, san-serif">
									<?= traduz('Transportadora') ?>
								</font>
							</td>
							<td nowrap align='center' colspan="2">
								<font size="1" face="Geneva, Arial, Helvetica, san-serif">
									<?= traduz('Nota Fiscal Saida') ?>
								</font>
							</td>
							<td nowrap align='center'>
								<font size="1" face="Geneva, Arial, Helvetica, san-serif">
									<?= traduz('Data Nota Fiscal Saida') ?>
								</font>
							</td>
						</tr>

						<tr>
							<td nowrap align='center' colspan="2">
								<input name="transportadora" size="20" value="<? echo $transportadora ?>" type="text" class="frm" tabindex="0" id='transportadora'>
							</td>
							<td nowrap align='center' colspan="2">
								<input name="nota_fiscal_saida" size="11" maxlength="10" value="<? echo $nota_fiscal_saida ?>" type="text" class="frm" tabindex="0" id='nota_fiscal_saida'>
							</td>
							<td nowrap align='center'>
								<input name="data_nota_fiscal_saida" size="11" maxlength="10" value="<? echo $data_nota_fiscal_saida ?>" type="text" class="frm hasDatepick" rel="data" tabindex="0" id='data_nota_fiscal_saida'>
							</td>
						</tr>

						<?php

					}

				?>

			<?php
			if (!$fabricaFileUploadOS && $anexaNotaFiscal and (!empty($qtde_item))) {
				$temImg = temNF("r_$os", 'count');
				$inputAnexoColSpan = ($temImg) ? '2':'3'; ?>
				<?php
				if ($login_fabrica <> 151){ ?>
					<tr class='menu_top'>
						<td colspan="4"><?=traduz('imagem.da.nota.fiscal', $con, $cook_idioma)?></td>
					</tr>
					<tr>
<!-- 						<td nowrap> -->
						<?php
						if($temImg) {
							echo temNF('r_' . $os, 'linkEx');
							echo $include_imgZoom;
						}
						if (($anexa_duas_fotos and $temImg < LIMITE_ANEXOS) or $temImg == 0) { ?>
							<td nowrap align='center' size='11' id='nf_table_item' colspan='100%'>
								<?=$inputNotaFiscal?>
								<?php
								if (in_array($login_fabrica,array(81,151,157,165)) OR ($login_fabrica == 153 AND $login_posto == 20564) ){ ?>
									<br />
									<?=$inputNotaFiscal2?>
								<?php
								}
						} ?>
						</td>
					</tr>
				<?php
				}

			 	if(in_array($login_fabrica, array(151))){ ?>
					<tr class='menu_top'>
						<td colspan="4"><?= traduz('Anexo') ?></td>
					</tr>
					<tr>
						<td nowrap>
						&nbsp;
						</td>
						<td nowrap align='center' size='11' colspan="4"><?
						if((strlen($msg_erro)>0 )&& ($erro_upload == "true")){

					        foreach ($arrLinks as $key => $values) {?>
					        	<label style="position:relative;top:-3px;left:-10px;font-size:10px;font-family:verdana,arial,helvetica,sans-serif">Anexo: </label>

					                    <?//se para a $key há um link, abre tag img para imagem
					                    if(strlen($values["link"]) > 0 ){
					                        $pathinfo = pathinfo($values["link"]);
					                        list($ext,$params) = explode("?", $pathinfo["extension"]);

					                        if($ext == "pdf"){ ?>
					                            <a href="<?=$values["link"]?>">
					                                <img id="<?=$key?>" name="<?=$key?>" src="imagens/adobe.JPG"/>
					                                <img id="<?=$key?>" name="<?=$key?>" src="<?=$values["link"]?>"></img>
					                            </a>
					                        <? }else{ ?>
					                            <img id="<?=$key?>" name="<?=$key?>" src="<?=$values["link"]?>"></img>
					                        <? } ?>
					                        <input type="hidden" value="<?=$values['name']?>" name="tmp_<?=$key?>">

					                   <? }else{?>
					                        <input type="file" class="frm" name="<?=$key?>" id="<?=$key?>"/> <?
					                   }?>
					                </td>
					           </tr><?
					        }
    					}else{
    						if(isset($os)){
    							$amazonTC->getObjectList("anexo_os_revenda_{$login_fabrica}_{$os}_img_os_revenda_1",false,"","");
    						}


		                    $link = '';
		                    $file = $amazonTC->files[0];

		                    if (!empty($file)) {
		                        $link  = $amazonTC->getLink(basename($file));
		                        $thumb = $amazonTC->getLink("thumb_".basename($file));
		                    }
		                    $pathinfo = pathinfo($link);
		                    list($ext,$params) = explode("?", $pathinfo["extension"]);

		                    if(strlen($link) > 0){ ?>
		                        <label style="position:relative;top:-3px;left:-10px;font-size:10px;font-family:verdana,arial,helvetica,sans-serif"> <?= traduz('Anexo') ?>: </label>
		                        <a href="<?=$link?>">
		                            <? if($ext == "pdf"){ ?>
		                                <img id="img_os_revenda_1" name="img_os_revenda_1" src="imagens/adobe.JPG"/>
		                            <? }else{ ?>
		                                <img id="img_os_revenda_1" name="img_os_revenda_1" src="<?=$thumb?>"/>
		                            <? } ?>
		                        </a>
		                   <? }else{ ?>
		                        <label style="position:relative;top:-3px;left:-10px;font-size:10px;font-family:verdana,arial,helvetica,sans-serif"> <?= traduz('Inserir Anexo') ?>: </label>
		                        <input type="file" class="frm" name="img_os_revenda_1" id="img_os_revenda_1"/>
		            	<?  } ?>
		            <?  } ?>
                </td>
            </tr>

				 <? } ?>
			 <? } ?>
			<?if( in_array($login_fabrica, array(11,172)) ) { //HD 11082?>
				<tr class="menu_top">
					<td colspan='3'>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? fecho("email.de.contato",$con,$cook_idioma); ?></font>
					</td>
				</tr>
				<tr>
					<td colspan='3' align='center'>
						<input class="frm" type="text" name="consumidor_email" size="68" value="<? echo $consumidor_email?>" >
					</td>
				</tr>
			<? } ?>
			</table>
			<? if($login_fabrica == 19){ $revenda_aux = " Atacado";}else $revenda_aux = " Revenda";?>
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<?php if ($login_fabrica == 157) { ?>
							<td>
								<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? fecho("cnpj",$con,$cook_idioma); ?><?=$revenda_aux;?></font>
							</td>
							<td>
								<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? fecho("nome",$con,$cook_idioma); ?><?=$revenda_aux;?></font>
							</td>
					<?php } else { ?>
							<td>
								<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? fecho("nome",$con,$cook_idioma); ?><?=$revenda_aux;?></font>
							</td>
							<td>
								<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? fecho("cnpj",$con,$cook_idioma); ?><?=$revenda_aux;?></font>
							</td>
					<?php } ?>


					<?php if ($login_fabrica == 164 && $interno_p) { ?>
							<td>
								<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? fecho("UF",$con,$cook_idioma); ?><?=$revenda_aux;?></font>
							</td>
							<td>
								<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? fecho("Nome Fantasia / Transferência",$con,$cook_idioma); ?></font>
							</td>
					<?php } ?>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? fecho("fone",$con,$cook_idioma); ?><?=$revenda_aux;?></font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? fecho("email",$con,$cook_idioma); ?><?=$revenda_aux;?></font>
					</td>
				</tr>
			<!-- Foi modificado por Fernando. Foi colocado o readonly nos campos Fone e e-mail
				por ser apenas de leitura caso haja necessidade de alteração tem que ir em
				cadastro para alterar os dados da revenda. -->
				<?
				if($login_fabrica == 154){
					$sql = "SELECT tbl_posto.cnpj ,
					tbl_posto_fabrica.codigo_posto as nome,
					tbl_posto_fabrica.contato_cep as cep,
					tbl_posto_fabrica.contato_cidade as cidade,
					tbl_posto_fabrica.contato_complemento as complemento,
					tbl_posto_fabrica.contato_bairro as bairro,
					tbl_posto_fabrica.contato_endereco as endereco,
					tbl_posto_fabrica.contato_numero as numero,
					tbl_posto.fone
				FROM tbl_posto_fabrica
				INNER JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
				INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$login_fabrica}
			 	WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
				AND tbl_tipo_posto.tipo_revenda IS TRUE
				AND tbl_posto_fabrica.posto = {$login_posto}";
					$res = pg_query($con,$sql);

					if(pg_num_rows($res) > 0){
						$tipo_revenda=true;
						$cnpj = pg_fetch_result($res,0,"cnpj");
						$nome = pg_fetch_result($res,0,"nome");
						$cep = pg_fetch_result($res,0,"cep");
						$cidade = pg_fetch_result($res,0,"cidade");
						$complemento = pg_fetch_result($res,0,"complemento");
						$bairro = pg_fetch_result($res,0,"bairro");
						$endereco = pg_fetch_result($res,0,"endereco");
						$numero = pg_fetch_result($res,0,"numero");
						$fone = pg_fetch_result($res,0,"fone");

					?>
					<tr>
						<td align='center'>
							<input class="frm" type="text" name="revenda_nome" size="25" maxlength="50" readonly="true" value="<? echo $nome ?>">&nbsp;
						</td>
						<td align='center'>
							<input class="frm" type="text" name="revenda_cnpj" id="revenda_cnpj" size="14" readonly="true" maxlength="18" value="<? echo $cnpj ?>">&nbsp;
						</td>
						<td align='center'>
							<input readonly class="frm" type="text" name="revenda_fone" size="11"  maxlength="20"  value="<? echo $fone ?>" >
						</td>
					</tr>
					</table>

					<input type="hidden" name="revenda_cidade" value="<?=$cidade?>">
					<input type="hidden" name="revenda_estado" value="">
					<input type="hidden" name="revenda_endereco" value="">
					<input type="hidden" name="revenda_cep" value="<?=$cep?>">
					<input type="hidden" name="revenda_numero" value="<?=$numero?>">
					<input type="hidden" name="revenda_complemento" value="<?=$complemento?>">
					<input type="hidden" name="revenda_bairro" value="<?=$bairro?>">

				<?php
					}else{
						?>
						<td align='center'>
							<input class="frm" type="text" name="revenda_nome" size="25" maxlength="50" value="<? echo $revenda_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)">&nbsp;
							<? if($login_fabrica != 24){ ?>
								<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: pesquisaRevenda (document.frm_os.revenda_nome, "nome")' style='cursor:pointer;'>
							<? } ?>
						</td>
						<td align='center'>
							<input class="frm" type="text" name="revenda_cnpj" id="revenda_cnpj" size="14" maxlength="18" value="<? echo $revenda_cnpj ?>">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: pesquisaRevenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor:pointer;'>
						</td>
						<td align='center'>
							<input readonly class="frm" type="text" name="revenda_fone" size="11"  maxlength="20"  value="<? echo $revenda_fone ?>" >
						</td>
						<td align='center'>
							<input readonly class="frm" type="text" name="revenda_email" size="11" maxlength="50" value="<? echo $revenda_email ?>" tabindex="0">
								</td>
							</tr>
						</table>

					<input type="hidden" name="revenda_cidade" value="">
					<input type="hidden" name="revenda_estado" value="">
					<input type="hidden" name="revenda_endereco" value="">
					<input type="hidden" name="revenda_cep" value="">
					<input type="hidden" name="revenda_numero" value="">
					<input type="hidden" name="revenda_complemento" value="">
					<input type="hidden" name="revenda_bairro" value="">
			<?
					}

				}else{

					if ($login_fabrica == 157) {
			?>
						<tr>
							<td align='center'>
								<input class="frm" type="text" name="revenda_cnpj" id="revenda_cnpj" size="14" maxlength="18" value="<? echo $revenda_cnpj ?>">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: pesquisaRevenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor:pointer;'>
							</td>
							<td align='center'>
								<input class="frm" type="text" name="revenda_nome" size="25" maxlength="50" value="<? echo $revenda_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)">&nbsp;
								<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: pesquisaRevenda (document.frm_os.revenda_nome, "nome")' style='cursor:pointer;'>
							</td>
			  <?php } else { ?>
						<tr>
							<td align='center'>
								<input class="frm" type="text" name="revenda_nome" size="25" maxlength="50" value="<? echo $revenda_nome ?>" onkeyup="somenteMaiusculaSemAcento(this)">&nbsp;
								<? if($login_fabrica != 24){ ?>
									<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: pesquisaRevenda (document.frm_os.revenda_nome, "nome")' style='cursor:pointer;'>
								<? } ?>
							</td>
							<td align='center'>
								<input class="frm" type="text" name="revenda_cnpj" id="revenda_cnpj" size="14" maxlength="18" value="<? echo $revenda_cnpj ?>">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: pesquisaRevenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor:pointer;'>
							</td>

			  <?php } ?>

					<?php if ($login_fabrica == 164 && $interno_p) { ?>
							<td align='center'>
								<input readonly class="frm" type="text" name="revenda_estado" size="2"  maxlength="2"  value="<? echo $revenda_estado ?>" >
							</td>
							<td align='center'>
								<input class="frm" type="text" name="revenda_fantasia" size="30"  maxlength="100"  value="<? echo $revenda_fantasia ?>" >
							</td>
					<?php } ?>

					<td align='center'>
						<input class="frm" type="text" name="revenda_fone" size="11"  maxlength="20"  value="<? echo $revenda_fone ?>" >
					</td>
					<td align='center'>
						<input readonly class="frm" type="text" name="revenda_email" size="11" maxlength="50" value="<? echo $revenda_email ?>" tabindex="0">
					</td>
				</tr>
			</table>

			<input type="hidden" name="revenda_cidade" value="">
			<input type="hidden" name="revenda_estado" value="">
			<input type="hidden" name="revenda_endereco" value="">
			<input type="hidden" name="revenda_cep" value="">
			<input type="hidden" name="revenda_numero" value="">
			<input type="hidden" name="revenda_complemento" value="">
			<input type="hidden" name="revenda_bairro" value="">
		<? } ?>
<?if($login_fabrica == 19 ){?>

			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? fecho("nome.revenda",$con,$cook_idioma); ?></font>
					</td>
					<td>
		<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? fecho("cnpj",$con,$cook_idioma); ?> <?if($login_fabrica == 19
){echo " COMPLETO ";} ?><? fecho("revenda",$con,$cook_idioma); ?></font>
					</td>
				</tr>
				<tr>
					<td align='center'>

		<!--TAKASHI 24-10 DESABILITAMOS NOME DA REVENDA, POIS ESTAVA PEGANDO REVENDAS QUE NAO ERAM DA LORENZETTI,
AUTORIZADO POR NATANAEL E SAMUEL -->
		<input class="frm" type="text" name="consumidor_nome" size="28" maxlength="50" value="<? echo
		$consumidor_nome ?>" <?if($login_fabrica == 99 ){ echo "disabled";}?>>&nbsp;<?if($login_fabrica <> 99 ){
?><img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript:
		fnc_pesquisa_revenda_consumidor (document.frm_os.consumidor_nome, "nome","C")' style='cursor:pointer;'> <? } ?>
					</td>
					<td align='center'>
						<input class="frm" type="text" name="consumidor_cnpj" size="20" maxlength="14" value="<? echo $consumidor_cnpj ?>">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda_consumidor (document.frm_os.consumidor_cnpj, "cnpj","C")' style='cursor:pointer;'>
					</td>
				</tr>
			</table>


<?}?>
			<table width="100%" border="0" cellspacing="3" cellpadding="2">
				<tr class="menu_top">
<?
	if($login_fabrica == 7){
?>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? fecho("contato",$con,$cook_idioma); ?></font>
					</td>
<?
}
?>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? fecho("observacoes",$con,$cook_idioma); ?></font>
					</td>
					<td>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><? fecho("qtde.linhas",$con,$cook_idioma); ?></font>
					</td>
				</tr>

				<tr>
<?
	if($login_fabrica == 7){
?>
					<td align='center'>
						<input type="checkbox" name="contrato" value="t" <? if ($contrato == 't') echo " checked"?>>
					</td>
<?
}
if (strlen($qtde_linhas)==0) {
	if (in_array($login_fabrica, [141])) {
		$qtde_linhas = '100';
		$qtde_item='100';
	}else if (in_array($login_fabrica, array(144))) {
		$qtde_linhas = '40';
		$qtde_item='40';
	} else {
		$qtde_linhas = '05';
		$qtde_item='05';
	}
}

?>
					<td align='center'>
						<input class="frm" type="text" name="obs" size="68" value="<? echo $obs ?>">
					</td>
					<td align='center'>
						<select size='1' class="frm" name='qtde_linhas' onChange="javascript: document.frm_os.submit(); ">

							<?if (in_array($login_fabrica, [141])) {
							?>
								<option value='100' <? if ($qtde_linhas <= 100) echo 'selected'; ?>>100</option>
								<option value='150' <? if ($qtde_linhas <= 150 AND $qtde_linhas > 100) echo 'selected'; ?>>150</option>
								<option value='200' <? if ($qtde_linhas <= 200 AND $qtde_linhas > 150) echo 'selected'; ?>>200</option>
								<option value='300' <? if ($qtde_linhas <= 300 AND $qtde_linhas > 200) echo 'selected'; ?>>300</option>
							<?php
							} else if (in_array($login_fabrica, array(144))) {
							?>
								<option value='40' <? if ($qtde_linhas <= 40) echo 'selected'; ?>>40</option>
								<option value='60' <? if ($qtde_linhas <= 60 AND $qtde_linhas > 40) echo 'selected'; ?>>60</option>
								<option value='100' <? if ($qtde_linhas <= 100 AND $qtde_linhas > 60) echo 'selected'; ?>>100</option>
							<?php
							} else {
							?>
								<option value='05' <? if ($qtde_linhas <= 05) echo 'selected'; ?>>05</option>
								<option value='10' <? if ($qtde_linhas <= 10 AND $qtde_linhas > 05) echo 'selected'; ?>>10</option>
								<option value='20' <? if ($qtde_linhas <= 20 AND $qtde_linhas > 10) echo 'selected'; ?>>20</option>
								<option value='30' <? if ($qtde_linhas <= 30 AND $qtde_linhas > 20) echo 'selected'; ?>>30</option>
								<option value='40' <? if ($qtde_linhas <= 40 AND $qtde_linhas > 30) echo 'selected'; ?>>40</option>
							<?php
							}

							if ($qtde_linhas > 40) {
								echo "<option value='$qtde_linhas'";
								if ($qtde_linhas > 40) echo 'selected';
								echo ">$qtde_linhas</option>";
							}
							#alteracao de qtdade de 300 para 600 a pedido de ronaldo no dia 03/06/2009
							if ( in_array($login_fabrica, array(11,172)) ) {
								echo "<option value='600'";
								if ($qtde_linhas == 600) echo 'selected';
								echo ">600</option>";
							}
							#Masterfrio 100 200 300
							if ($login_fabrica == 40) {
								echo "<option value='100'";
								if ($qtde_linhas == 100) echo 'selected';
								echo ">100</option>";
								echo "<option value='200'";
								if ($qtde_linhas == 200) echo 'selected';
								echo ">200</option>";
								echo "<option value='300'";
								if ($qtde_linhas == 300) echo 'selected';
								echo ">300</option>";
							}
							if ($login_fabrica == 80) {
								echo "<option value='500'";
								if ($qtde_linhas == 500) echo 'selected';
								echo ">500</option>";
							}
							?>
						</select>
					</td>
				</tr>

			</table>
		</td>
		<td><img height="1" width="16" src="imagens/spacer.gif"></td>
	</tr>
</table>

<?
#$login_fabrica = 91;
if (strlen($os_revenda) > 0) {
	$sql = "SELECT      tbl_produto.produto,
						tbl_os_revenda_item.data_nf,
						tbl_os_revenda_item.rg_produto
			FROM        tbl_os_revenda_item
			JOIN        tbl_produto   USING (produto)
			JOIN        tbl_os_revenda USING (os_revenda)
			WHERE       tbl_os_revenda_item.os_revenda = $os_revenda
			ORDER BY    tbl_os_revenda_item.os_revenda_item";
	$res_os = pg_exec ($con,$sql);

}

// monta o FOR
echo "<input class='frm' type='hidden' name='qtde_item' value='$qtde_item'>";
echo "<input type='hidden' name='btn_acao' value=''>";

if (in_array($login_fabrica, array(141,144)) || ($login_fabrica == 165 && $posto_interno)) {
	$utiliza_leitor_codigo_barras = ($_POST["utiliza_leitor_codigo_barras"] == "t") ? "checked" : "";

	echo "<input type='checkbox' id='utiliza_leitor_codigo_barras' name='utiliza_leitor_codigo_barras' $utiliza_leitor_codigo_barras value='t' /> Utilizar leitor de código de barras";
}

if($login_fabrica == 50){

	$sql_dr = "SELECT defeito_reclamado, descricao FROM tbl_defeito_reclamado WHERE fabrica = {$login_fabrica} AND ativo";
	$res_dr = pg_query($con, $sql_dr);

}

for ($i=0; $i<$qtde_item; $i++) {

	$novo               = 't';
	$os_revenda_item    = "";
	$referencia_produto = "";
	$serie              = "";
	$produto_descricao  = "";
	$capacidade         = "";
	$type               = "";
	$embalagem_original = "";
	$sinal_de_uso       = "";
	$aux_nota_fiscal    = "";
	$rg_produto         = "";

	if ($i % 20 == 0) {
		#if ($i > 0) {
		#	echo "<tr>";
		#	echo "<td colspan='5' align='center'>";
		#	echo "<img src='imagens/btn_gravar.gif' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }\" ALT='Gravar' border='0' style='cursor:pointer;'>";

		#	if (strlen ($os_revenda) > 0 AND strlen($exclui) > 0) {
		#		echo "&nbsp;&nbsp;<img src='imagens/btn_apagar.gif' style='cursor:pointer' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente apagar esta OS?') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); }else{ return; }; } else { alert ('Aguarde submissão') }\" ALT='Apagar a Ordem de Serviço' border='0'>";
		#	}

		#	echo "</td>";
		#	echo "</tr>";
		#	echo "</table>";
		#}

		echo "<table width='650' border='0' cellpadding='1' cellspacing='2' align='center' bgcolor='#ffffff'>";
		echo "<tr class='menu_top'>";
		if (!in_array($login_fabrica, array(19,30,145,151,162,171))){
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>";
			if($login_fabrica == 35){
				echo "PO#";
			}elseif($login_fabrica == 137 && $posto_interno){
				fecho("numero.de.lote",$con,$cook_idioma);
			}else{
				if($login_fabrica == 160 or $replica_einhell){
					echo "Nº Lote";
				}else{
					fecho("numero.de.serie",$con,$cook_idioma);
				}
			}
			echo "</font></td>";
		}
		if($login_fabrica == 160 or $replica_einhell){
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>";
				echo "Versão do Produto";
			echo "</font></td>";
		}

		if (in_array($login_fabrica, array(11,172))) {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'> Possui Cód. Interno? </font></td>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'> Código Interno </font></td>";
		}

		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>"; fecho("produto",$con,$cook_idioma); echo "</font></td>";
		echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>"; fecho("descricao.do.produto",$con,$cook_idioma); echo "</font></td>";

		if($login_fabrica == 162){
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>"; fecho("numero.de.serie",$con,$cook_idioma); echo "</font></td>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>"; fecho("IMEI",$con,$cook_idioma); echo "</font></td>";
		}

		if($login_fabrica == 164){
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>"; fecho("defeito.constatado",$con,$cook_idioma); echo "</font></td>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>"; fecho("destinacao",$con,$cook_idioma); echo "</font></td>";
		}

		//takashi27/06
  		if(!in_array($login_fabrica,array(19,35,141,157,162,165))){
			if ( !in_array($login_fabrica, array(11,172)) ) {
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>"; fecho("nota.fiscal",$con,$cook_idioma); echo "</font></td>";
			} else {
				echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'> Rg do Produto</font></td>";
			}
		}

		if ($login_fabrica == 151) {
                        echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Data NF</font></td>";
                }

		if (in_array($login_fabrica, array(19,121,136,139,141,145,151,164)) or ($login_fabrica == 137 && $posto_interno)){
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>"; fecho("qtde",$con,$cook_idioma); echo"</font></td>";
		}
		if ($login_fabrica == 7) {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>"; fecho("capacidade.kg",$con,$cook_idioma); echo "</font></td>";
		}

		if ($login_fabrica == 1 ) {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>"; fecho("type",$con,$cook_idioma); echo "</font></td>\n";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>"; fecho("embalagem.original",$con,$cook_idioma); echo "</font></td>\n";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>"; fecho("sinal.de.uso",$con,$cook_idioma); echo "</font></td>\n";
		}

		if ($login_fabrica == 50){ # HD  79844
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>"; fecho("defeito.reclamado",$con,$cook_idioma); echo "</font></td>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Data Fabricação</font></td>";

		}

		if($login_fabrica == 94){
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>"; fecho("defeito.reclamado",$con,$cook_idioma); echo "</font></td>";
		}

		if ($login_fabrica == 91 or $login_fabrica == 74){ # HD  79844
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Data Fabricação</font></td>";
		}

		if ($login_fabrica == 137 && $posto_interno){ # HD  79844
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Data NF</font></td>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>CFOP</font></td>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Valor Unitário</font></td>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Valor Total</font></td>";
		}


		if (in_array($login_fabrica, array(141,144))) {
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif'>Valor Unitário</font></td>";
		}

		echo "</tr>";
	}

	if (strlen($os_revenda) > 0 and strlen(trim($msg_erro))==0){
		if (@pg_numrows($res_os) > 0) {
			$produto = trim(@pg_result($res_os,$i,produto));

			if($login_fabrica == 137 && $posto_interno){

				$data_nf     = pg_result($res_os,$i,data_nf);

				if(!empty($data_nf)){
					list($ano, $mes, $dia) = explode("-", $data_nf);
					$data_nf = $dia."/".$mes."/".$ano;
				}

				$rg_produto  = pg_result($res_os,$i,rg_produto);

				$rg_produto = json_decode($rg_produto);

				$cfop 			= $rg_produto->cfop;
				$valor_unitario = $rg_produto->vu;
				$valor_total 	= $rg_produto->vt;

			}

		}

		if(strlen($produto) > 0){
			// seleciona do banco de dados
			$sql = "SELECT   tbl_os_revenda_item.os_revenda_item ,
							 tbl_os_revenda_item.serie              ,
							 tbl_os_revenda_item.capacidade         ,
							 tbl_os_revenda_item.nota_fiscal        ,
							 tbl_os_revenda_item.type               ,
							 tbl_os_revenda_item.embalagem_original ,
							 tbl_os_revenda_item.sinal_de_uso       ,
							 tbl_os_revenda_item.qtde               ,
							 tbl_os_revenda_item.defeito_reclamado_descricao,
							 tbl_os_revenda_item.defeito_constatado_descricao,
							 to_char(tbl_os_revenda_item.data_fabricacao,'DD/MM/YYYY') as data_fabricacao,
							 tbl_produto.referencia                 ,
							 tbl_produto.descricao,
							 tbl_os_revenda_item.rg_produto,
							 TO_CHAR(tbl_os_revenda_item.data_nf, 'DD/MM/YYYY') AS data_nf
					FROM	 tbl_os_revenda
					JOIN	 tbl_os_revenda_item ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
					JOIN	 tbl_produto ON tbl_produto.produto = tbl_os_revenda_item.produto
					WHERE	 tbl_os_revenda_item.os_revenda = $os_revenda
					ORDER BY os_revenda_item";
//echo $sql;
			$res = pg_exec($con, $sql);

			if (@pg_numrows($res) == 0) {
				$novo               = 't';
				$os_revenda_item    = $_POST["item_".$i];
				$referencia_produto = $_POST["produto_referencia_".$i];
				$serie              = $_POST["produto_serie_".$i];
				$produto_descricao  = $_POST["produto_descricao_".$i];
				$capacidade         = $_POST["produto_capacidade_".$i];
				$type               = $_POST["type_".$i];
				$embalagem_original = $_POST["embalagem_original_".$i];
				$sinal_de_uso       = $_POST["sinal_de_uso_".$i];
				$aux_nota_fiscal    = $_POST["aux_nota_fiscal_".$i];
				$rg_produto         = $_POST["rg_produto_".$i];
				$aux_qtde           = $_POST["aux_qtde_".$i];

				$defeito_reclamado_descricao= $_POST["defeito_reclamado_descricao_".$i];

				$data_fabricacao    = $_POST["data_fabricacao_".$i];

				if ($login_fabrica == 151) {
		            $data_nf = $_POST["data_nf_{$i}"];
            	}

            	if($login_fabrica == 164){
					$destinacao    			= pg_result($res,$i,rg_produto);
					$defeito_constatado    	= pg_result($res,$i,defeito_constatado_descricao);
				}

			}else{
				$novo               = 'f';
				$os_revenda_item    = pg_result($res,$i,os_revenda_item);
				$referencia_produto = pg_result($res,$i,referencia);
				$produto_descricao  = pg_result($res,$i,descricao);
				$serie              = pg_result($res,$i,serie);
				$capacidade         = pg_result($res,$i,capacidade);
				$type               = pg_result($res,$i,type);
				$embalagem_original = pg_result($res,$i,embalagem_original);
				$sinal_de_uso       = pg_result($res,$i,sinal_de_uso);
				$aux_nota_fiscal    = pg_result($res,$i,nota_fiscal);
				$aux_qtde           = pg_result($res,$i,qtde);

				if($login_fabrica == 94){
					$defeito_reclamado_descricao= pg_result($res,$i,defeito_constatado_descricao);
				}else{
					$defeito_reclamado_descricao= pg_result($res,$i,defeito_reclamado_descricao);
				}

				if($login_fabrica == 164){
					$destinacao    			= pg_result($res,$i,rg_produto);
					$defeito_constatado    	= pg_result($res,$i,defeito_constatado_descricao);
				}

				$data_fabricacao	= pg_result($res,$i,data_fabricacao);
				$rg_produto         = pg_result($res,$i,rg_produto);

				if(strlen($serie) > 0) {
					$sqld = "SELECT to_char(tbl_numero_serie.data_fabricacao,'DD/MM/YYYY') as data_fabricacao
							FROM tbl_os_revenda_item
							JOIN tbl_numero_serie USING(serie)
							WHERE tbl_os_revenda_item.os_revenda_item = $os_revenda_item";
					$resd = pg_exec($con,$sqld);
					if(pg_numrows($resd) > 0) {
						//$data_fabricacao = pg_result($resd,0,data_fabricacao);
					}
				}

				if ($login_fabrica == 151) {
					$data_nf = pg_fetch_result($res, $i, "data_nf");
				}
			}
		}else{
			$novo               = 't';
		}
	}else{

		$novo               = 't';
		$os_revenda_item    = $_POST["item_".$i];
		$referencia_produto = $_POST["produto_referencia_".$i];
		$serie              = $_POST["produto_serie_".$i];
		$produto_descricao  = $_POST["produto_descricao_".$i];
		$capacidade         = $_POST["produto_capacidade_".$i];
		$type               = $_POST["type_".$i];
		$embalagem_original = $_POST["embalagem_original_".$i];
		$sinal_de_uso       = $_POST["sinal_de_uso_".$i];
		$aux_nota_fiscal    = $_POST["aux_nota_fiscal_".$i];
		$rg_produto         = $_POST["rg_produto_".$i];
		$aux_qtde           = $_POST["aux_qtde_".$i];
		$defeito_reclamado_descricao= $_POST["defeito_reclamado_descricao_".$i];
		$data_fabricacao    = $_POST["data_fabricacao_".$i];

		if ($login_fabrica == 151) {
			$data_nf = $_POST["data_nf_{$i}"];
		}

		if($login_fabrica == 162){
			$aux_imei   		= $_POST["imei_".$i];
			$aux_informatica    = $_POST["informatica_".$i];
		}

		if($login_fabrica == 164){
			$defeito_constatado = $_POST["defeito_constatado_".$i];
			$destinacao     	= $_POST["destinacao_".$i];
		}

		$codigo_interno = $_POST["codigo_interno_".$i];

//echo $aux_qtde;
//echo $os_revenda;
	}

	$serie_validar = "#{$serie}#";

	if (strlen($msg_erro) > 0 && in_array($login_fabrica, array(141,144)) && in_array($serie_validar, $array_serie_duplicada)) {
           $linha_erro = $i;
    }

	echo "<input type='hidden' name='novo_$i' value='$novo'>\n";
	echo "<input type='hidden' name='item_$i' value='$os_revenda_item'>\n";

	if($login_fabrica == 74){
		$erro_linha_produto = (in_array($i, $campos_erro))? " style='background-color:#FA8072' " : "";
	}

	$erro_linha_produto = "";
	if (!empty($erroLinha[$i])) {
		$erro_linha_produto = " style='background-color:#FA8072' ";
	}

	echo "<tr id='linha_serie_{$i}' $erro_linha_produto "; if (strlen($linha_erro) > 0 && $linha_erro == $i AND strlen ($msg_erro) > 0) echo "bgcolor='#ffcccc'"; echo ">\n";
	echo "<input type='hidden' name='voltagem_$i' value=''>";
	$converte = "";
	if($login_fabrica ==3) {
		$converte = 'onKeyUp="javascript:somenteMaiusculaSemAcento(this);"';
	}
	if($login_fabrica == 6) {
		$converte = 'onKeyUp="javascript:checarNumero(this);"';
	}
	if(!in_array($login_fabrica,array(19, 30, 145, 151, 171))){

		if(in_array($login_fabrica, array(141,144))){
			$tag_serie = "serie='serie_{$i}'";
		}

        $maxlength = 30;

		if($login_fabrica != 162){
			echo "<td align='center' nowrap><input class='frm' type='text' name='produto_serie_$i' id='produto_serie_$i' rel='produto_serie' size='8'  maxlength='$maxlength' $converte value='$serie' $tag_serie> &nbsp;";
		}
		if($login_fabrica<>24 and $login_fabrica <> 94 and $login_fabrica <> 162){
			if($login_fabrica==50){
				echo "<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: pesquisaNumeroSerie (document.frm_os.produto_serie_$i, document.frm_os.produto_referencia_$i, $i)\" style='cursor:pointer;'>";
			}else{
				if($login_fabrica==3){
					echo "<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: pesquisaNumeroSerieBritania (document.frm_os.produto_serie_$i, document.frm_os.produto_referencia_$i, $i)\" style='cursor:pointer;'>";
				}else{
					if($login_fabrica != 160 and !$replica_einhell){
						echo "<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick=\"javascript: pesquisaNumeroSerie (document.frm_os.produto_serie_$i, document.frm_os.produto_referencia_$i, $i)\" style='cursor:pointer;'>";
					}
				}
			}
		}
		echo "</td>\n";

		if($login_fabrica == 160 or $replica_einhell){
			echo "<td align='center' nowrap>
				<input class='frm' type='text' name='versao_produto_$i' id='versao_produto_$i' rel='versao_produto' size='8'  maxlength='20'  value='$type'> &nbsp;";
			echo "</td>\n";
		}
	}

	if(in_array($login_fabrica, array(11,172))){

		$exibeCheckboxInterno = "f";

		$displayPergunta = "none";

		if (!empty($referencia_produto)) {

			$arrDadosProduto = valida_produto_pacific_lennox($referencia_produto);

			if (count($arrDadosProduto["fabrica"]) > 1) {

				$displayPergunta = "block";

			}

		}

		$displayCodigo = "";

		if ($_POST['possui_codigo_interno_'.$i] == "nao") {
			$displayCodigo = "hidden";
		}

		echo "<td align='center' nowrap>
				<div id='botoes_sim_nao_{$i}' style='display: {$displayPergunta};'>
					<label style='font-weight: bolder;color: darkgreen;cursor: pointer;'>
						<input type='radio' data-posicao='{$i}' name='possui_codigo_interno_{$i}' value='sim' ".(($_POST['possui_codigo_interno_'.$i] == 'sim') ? "checked" : "")." /> Sim
					</label>
					<label style='font-weight: bolder;color: darkred;cursor: pointer;'>
						<input type='radio' data-posicao='{$i}' name='possui_codigo_interno_{$i}' value='nao' ".(($_POST['possui_codigo_interno_'.$i] == 'nao') ? "checked" : "")." /> Não
					</label>
				</div>
			  </td>\n
			  <td align='center' nowrap>
				<input type='text' name='codigo_interno_{$i}' value='{$codigo_interno}' maxlength='15' class='frm' maxlength='50' {$displayCodigo} /> 
			  </td>\n";
	}

	echo "<td align='center' nowrap><input class='frm' type='text' name='produto_referencia_$i' size='12' maxlength='50' value='$referencia_produto'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: pesquisaProduto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i, document.frm_os.voltagem_$i,\"referencia\",$i)' style='cursor:pointer;'></td>\n";
	echo "<td align='center' nowrap><input class='frm' type='text' name='produto_descricao_$i' size='40' maxlength='50' value='$produto_descricao'>&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: pesquisaProduto (document.frm_os.produto_referencia_$i,document.frm_os.produto_descricao_$i, document.frm_os.voltagem_$i,\"descricao\",$i)' style='cursor:pointer;'>

	</td>\n";

	if($login_fabrica == 162){
			echo "<td align='center' nowrap><input class='frm' type='text' name='produto_serie_$i' id='produto_serie_$i' rel='produto_serie' size='8'  maxlength='30' $converte value='$serie' $tag_serie> &nbsp;";
	}

	if($login_fabrica == 164){
		echo "<td align='center' nowrap><select name='defeito_constatado_$i' id='defeito_constatado_$i' style='width:250px;'>";
			echo "<option value=''>Defeito Constatado</option>";

			if(isset($_POST) or strlen($os_revenda_item)>0){

                if(strlen($produto_item)>0 ){
                    $cond_produto = " AND tbl_produto.produto = {$produto_item} ";
                }else{
                    $cond_produto = " AND tbl_produto.referencia = '$referencia_produto' ";
                }
                $sql = "SELECT DISTINCT
                            tbl_defeito_constatado.descricao,
                            tbl_defeito_constatado.defeito_constatado
                            FROM tbl_diagnostico
                            JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_diagnostico.defeito_constatado AND tbl_defeito_constatado.fabrica = {$login_fabrica}
                            JOIN tbl_familia ON tbl_familia.familia = tbl_diagnostico.familia AND tbl_familia.fabrica = {$login_fabrica}
                            JOIN tbl_produto ON tbl_produto.familia = tbl_familia.familia AND tbl_produto.fabrica_i = {$login_fabrica}
                            WHERE tbl_diagnostico.fabrica = {$login_fabrica}
                            $cond_produto
                            AND tbl_diagnostico.ativo IS TRUE
                            ORDER BY tbl_defeito_constatado.descricao ASC ";
                $res = pg_query($con, $sql);
                for($z=0; $z<pg_num_rows($res); $z++){
                    $descricao              = pg_fetch_result($res, $z, descricao);
                    $defeito_constatado_bd     = pg_fetch_result($res, $z, defeito_constatado);

                    if($defeito_constatado_bd == $defeito_constatado){
                        $selected = " selected ";
                    }else{
                        $selected = "";;
                    }
                    echo "<option value='$defeito_constatado' $selected >$descricao</option>";
                }
            }

		echo "</select></td>";
		echo "<td align='center' nowrap><select name='destinacao_$i' id='destinacao_$i'>";

			echo "<option value=''>Destinação</option>";
            for($a=0; $a<pg_num_rows($resDestinacao); $a++){
                $segmento_atuacao   = pg_fetch_result($resDestinacao, $a, "segmento_atuacao");
                $descricao          = pg_fetch_result($resDestinacao, $a, "descricao");

                if($destinacao == $segmento_atuacao){
                    $selected = " selected ";
                }else{
                    $selected = " ";
                }

                if($login_posto != 113070){
                	echo "<option value='$segmento_atuacao' $selected >$descricao</option>";
                } else {
                	if($segmento_atuacao != 10){
                		echo "<option value='$segmento_atuacao' $selected >$descricao</option>";
                	}
                }
            }


		echo "</select></td>";
	}


	if(!in_array($login_fabrica,array(19,35,141,157,165))){
		if ($login_fabrica == 14) {?>
			<td align='center'><input class='frm' type='text' onkeypress="return txtBoxFormat(this.form, this.name, '999999999999', event);" name='<? echo "aux_nota_fiscal_$i"?>'  size='12' rel='nota_fiscal' maxlength='12' value='<? echo "$aux_nota_fiscal"?>'></td>
		<? } else {
			if ( in_array($login_fabrica, array(11,172)) ){
				echo "<td align='center'>
				<input class='frm' type='text' name='rg_produto_$i'  size='10'  maxlength='10'  value='$rg_produto'></td>\n";
			}elseif($login_fabrica != 162 ){
				echo "<td align='center'>
				<input class='frm' type='text' name='aux_nota_fiscal_$i'  size='6'  maxlength='20'  value='$aux_nota_fiscal'></td>\n";
			}elseif($login_fabrica == 162){
				if($aux_informatica == 'f'){
					$prop_readonly = " ";
				}else{
					$prop_readonly = " readonly='true' ";
				}

				echo "<td align='center'>
				<input class='frm' type='text' name='imei_$i' id='imei_$i'  size='6'  maxlength='18'  value='$aux_imei' $prop_readonly >
				<input class='frm' type='hidden' name='informatica_$i' id='informatica_$i'  size='6'  maxlength='20'  value='$aux_informatica' >
				</td>\n";

			}
		}
	}

	if ($login_fabrica == 151) {
                echo "<td align='center'><input class='frm hasDatepick' type='text' name='data_nf_$i' size='11' value='$data_nf' ></td>\n";
        }


	if(in_array($login_fabrica, array(19,121,136,139,141,145,151,164)) or ($login_fabrica == 137 && $posto_interno)){
		echo "<td align='center'><input class='frm' type='text' name='aux_qtde_$i'  size='2'  maxlength='2'  value='$aux_qtde'></td>\n";
		$aux_qtde='';
	}

	if ($login_fabrica == 7) {
		echo "<td align='center'><input class='frm' type='text' name='produto_capacidade_$i'  size='9' maxlength='20' value='$capacidade'></td>\n";
	}


	if ($login_fabrica == 1) {
	?>
		<td align='center' nowrap>
		&nbsp;
		<select name='type_<? echo $i ?>' class='frm'>
			<? if(strlen($type) == 0) { ?><option value='' selected></option><? } ?>
			<option value='Tipo 1' <? if($type == 'Tipo 1') echo "selected"; ?>>Tipo 1</option>
			<option value='Tipo 2' <? if($type == 'Tipo 2') echo "selected"; ?>>Tipo 2</option>
			<option value='Tipo 3' <? if($type == 'Tipo 3') echo "selected"; ?>>Tipo 3</option>
			<option value='Tipo 4' <? if($type == 'Tipo 4') echo "selected"; ?>>Tipo 4</option>
			<option value='Tipo 5' <? if($type == 'Tipo 5') echo "selected"; ?>>Tipo 5</option>
			<option value='Tipo 6' <? if($type == 'Tipo 6') echo "selected"; ?>>Tipo 6</option>
			<option value='Tipo 7' <? if($type == 'Tipo 7') echo "selected"; ?>>Tipo 7</option>
			<option value='Tipo 8' <? if($type == 'Tipo 8') echo "selected"; ?>>Tipo 8</option>
			<option value='Tipo 9' <? if($type == 'Tipo 9') echo "selected"; ?>>Tipo 9</option>
			<option value='Tipo 10' <? if($type == 'Tipo 10') echo "selected"; ?>>Tipo 10</option>
		</select>
		&nbsp;
		</td>
		<td align='center' nowrap>
			&nbsp;
			<input class='frm' type="radio" name="embalagem_original_<? echo $i ?>" value="t" <? if ($embalagem_original == 't' OR strlen($embalagem_original) == 0) echo "checked"; ?>>
			<font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b><? fecho("sim",$con,$cook_idioma); ?></b></font>
			<input class='frm' type="radio" name="embalagem_original_<? echo $i ?>" value="f" <? if ($embalagem_original == 'f') echo "checked"; ?>>
			<font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b><? fecho("nao",$con,$cook_idioma); ?></b></font>
			&nbsp;
		</td>
		<td align='center' nowrap>
			&nbsp;
			<input class='frm' type="radio" name="sinal_de_uso_<? echo $i ?>" value="t" <? if ($sinal_de_uso == 't') echo "checked"; ?>>
			<font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b><? fecho("sim",$con,$cook_idioma); ?></font>
			<input class='frm' type="radio" name="sinal_de_uso_<? echo $i ?>" value="f" <? if ($sinal_de_uso == 'f'  OR strlen($sinal_de_uso) == 0) echo "checked"; ?>>
			<font size='1' face='Verdana, Tahoma, Geneva, Arial, Helvetica, san-serif'><b><? fecho("nao",$con,$cook_idioma); ?></font>
			&nbsp;
		</td>
	<?
	}

	if ($login_fabrica == 50) { # HD  79844
		echo "<td align='center'> <!-- <input class='frm' type='text' name='defeito_reclamado_descricao_$i'  size='20' maxlength='50' value='$defeito_reclamado_descricao'> --> \n";

			echo "<input type='hidden' name='produto_hidden_{$i}' value='".$_POST["produto_hidden_".$i]."'>";

			echo "<select name='defeito_reclamado_descricao_$i' class='frm' style='min-width: 200px;'>";

				if(strlen($_POST["produto_hidden_".$i])){

	            	$option = "<option value=''></option>";

	            	$produto = $_POST["produto_hidden_".$i];

	            	$sql_dr = "SELECT
								tbl_diagnostico.defeito_reclamado,
								tbl_defeito_reclamado.descricao
							FROM tbl_diagnostico
							INNER JOIN tbl_produto ON tbl_produto.familia = tbl_diagnostico.familia
							INNER JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_diagnostico.defeito_reclamado
							WHERE
								tbl_produto.produto = {$produto}
								AND tbl_produto.fabrica_i = {$login_fabrica}
								AND tbl_diagnostico.fabrica = {$login_fabrica}
								AND tbl_defeito_reclamado.fabrica = {$login_fabrica}
								AND tbl_defeito_reclamado.ativo IS TRUE";
					$res_dr = pg_query($con, $sql_dr);

					if(pg_num_rows($res_dr) > 0){

						$rows = pg_num_rows($res_dr);

						for ($k = 0; $k < $rows; $k++) {

							$defeito_reclamado = pg_fetch_result($res_dr, $k, "defeito_reclamado");
							$descricao         = pg_fetch_result($res_dr, $k, "descricao");

							$selected = ($defeito_reclamado == $_POST["defeito_reclamado_descricao_".$i]) ? "selected" : "";

							$option .= "<option value='{$defeito_reclamado}' {$selected} > {$descricao} </option>";

						}

					}

					echo $option;

	            }else{

	            	echo "<option value=''></option>";

	            }

			echo "</select>";

		echo "</td>";

		?>
		<td align='center'><input class='frm' type='text' name="<?php echo 'data_fabricacao_'.$i;?>" OnKeyPress="formatar(this, '##/##/####');" size='12' maxlength='10' value='<?php echo $data_fabricacao;?>'></td>
		<?php
	}

	if($login_fabrica == 94){
		echo "<td align='center'><input class='frm' type='text' name='defeito_reclamado_descricao_$i'  size='20' maxlength='50' value='$defeito_reclamado_descricao'></td>\n";
	}

	if ($login_fabrica == 91 or $login_fabrica == 74) { # HD  79844
		?>
		<td align='center'><input class='frm' type='text' name="<?php echo 'data_fabricacao_'.$i;?>" OnKeyPress="formatar(this, '##/##/####');" size='12' maxlength='10' value='<?php echo $data_fabricacao;?>'></td>
		<?php
	}

	if ($login_fabrica == 137 && $posto_interno) {
		echo "<td align='center'><input class='frm hasDatepick' type='text' name='data_nf_$i' size='11' maxlength='11' value='$data_nf' rel='data'></td>\n";
		echo "<td align='center'><input class='frm' type='text' name='cfop_$i'  size='9' maxlength='20' value='$cfop'></td>\n";
		echo "<td align='center'><input class='frm' type='text' name='valor_unitario_$i' onblur='calculaTotal(\"$i\")'  size='9' maxlength='20' value='$valor_unitario'></td>\n";
		echo "<td align='center'><input class='frm' type='text' name='valor_total_$i'  size='9' maxlength='20' value='$valor_total'></td>\n";
	}


	if (in_array($login_fabrica, array(141,144))) {
		$valor_unitario = $_POST["valor_unitario_$i"];

		$readonly = (strlen($referencia_produto) > 0 && strlen($produto_descricao) > 0) ? "" : "readonly='readonly'";

		echo "<td align='center'><input class='frm moeda' type='text' name='valor_unitario_$i' onblur='calculaTotal();'  size='9' maxlength='20' value='$valor_unitario' $readonly ></td>\n";
	}

	echo "</tr>\n";

	// limpa as variaveis
	$novo                        = '';
	$os_revenda_item             = '';
	$referencia_produto          = '';
	$serie                       = '';
	$produto_descricao           = '';
	$capacidade                  = '';
	$defeito_reclamado_descricao = '';
	$data_fabricacao             = '';
	$rg_produto             	 = '';
	$data_nf             		 = '';
	$cfop             			 = '';
	$valor_total                 = '';
	$valor_unitario              = '';
}


if(($login_fabrica == 137 && $posto_interno) || in_array($login_fabrica, array(141,144))){
	?>
		<tr>
			<td colspan="<?=(in_array($login_fabrica, array(141,144))) ? 3 : 7?>"></td>
			<td style="font-size: 12px; font-weight: bold;" nowrap>Valor Total NF</td>
			<td style="font-size: 12px; font-weight: bold; color: #ff0000;" align="center">
				<input type="hidden" name="total_valor_nf" value="<?=$_POST['total_valor_nf']?>" />
				<span id="total_valor_nf">00.00</span>
			</td>
		</tr>
	<?php
}

echo "<tr>";
echo "<td colspan='5' align='center'>";
echo "<br>";
//echo "<input type='hidden' name='btn_acao' value=''>";

if ($fabricaFileUploadOS) {
    $boxUploader = array(
        "div_id" => "div_anexos",
        "prepend" => $anexo_prepend,
        "context" => "os",
        "bootstrap" => false,
        "unique_id" => $tempUniqueId,
        "hash_temp" => $anexoNoHash,
        "reference_id" => $tempUniqueId
    );

    include "box_uploader.php";

}

echo "<br /><br />";
echo "<img src='imagens/btn_gravar.gif' name='nome_frm_os' class='verifica_servidor' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; } else { }\" ALT='Gravar' border='0' style='cursor:pointer;'>";


if (strlen ($os_revenda) > 0 AND strlen($exclui) > 0) {
	echo "&nbsp;&nbsp;<img src='imagens/btn_apagar.gif' style='cursor:pointer' onclick=\"javascript: if (document.frm_os.btn_acao.value == '' ) { if(confirm('Deseja realmente apagar esta OS?') == true) { document.frm_os.btn_acao.value='apagar'; document.frm_os.submit(); }else{ return; }; } else { alert ('Aguarde submissão') }\" ALT='Apagar a Ordem de Serviço' border='0'>";
}

echo "</td>";
echo "</tr>";
echo "</table>";
?>


</form>

<br>

<? include "rodape.php";?>
