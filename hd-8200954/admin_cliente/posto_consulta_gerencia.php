<?php

include_once '../dbconfig.php';
include_once '../includes/dbconnect-inc.php';
$admin_privilegios="gerencia";
include 'autentica_admin.php';
include_once '../funcoes.php';


function mascara($val, $mascara){
	$maskared = '';
	$k = 0;
	for($i = 0; $i<=strlen($mascara)-1; $i++){
		if($mascara[$i] == '#'){
			if(isset($val[$k]))
				$maskared .= $val[$k++];
		}else{
			if(isset($mascara[$i]))
				$maskared .= $mascara[$i];
		}
	}
	return $maskared;
}

if (strlen($_GET["btnacao"]) > 0) $btnacao = trim(strtolower($_GET["btnacao"]));
if (strlen($_GET["posto"]) > 0) $posto = $_GET["posto"];

if(strlen($_POST["gerar_xls"])>0) $gerar_xls = $_POST["gerar_xls"];
else                              $gerar_xls = $_GET["gerar_xls"];

if(strlen($_POST["codigo_posto_codigo"])>0) $codigo_posto_codigo = $_POST["codigo_posto_codigo"];
else                                        $codigo_posto_codigo = $_GET["codigo_posto_codigo"];

if(strlen($_POST["posto_codigo"])>0) $posto_codigo = $_POST["posto_codigo"];
else                                 $posto_codigo = $_GET["posto_codigo"];

if(strlen($_POST["posto_nome"])>0) $posto_nome = $_POST["posto_nome"];
else                               $posto_nome = $_GET["posto_nome"];

if(strlen($_POST["posto_cidade"])>0) $posto_cidade = $_POST["posto_cidade"];
else                              $posto_cidade = $_GET["posto_cidade"];

if(strlen($_POST["posto_estado"])>0) $posto_estado = $_POST["posto_estado"];
else                              $posto_estado = $_GET["posto_estado"];

if(strlen($_POST["posto_bairro"])>0) $posto_bairro = $_POST["posto_bairro"];
else                                 $posto_bairro = $_GET["posto_bairro"];

if(strlen($_POST["contrato"]) > 0) $contrato        = $_POST["contrato"];
else                               $contrato        = $_POST["contrato"];

if(strlen($_POST["inspetor"])>0) $inspetor = $_POST["inspetor"];
else                                 $inspetor = $_GET["inspetor"];

//echo $contrato;exit;

$data_inicial       = $_POST['data_inicial'];
$data_final         = $_POST['data_final'];

if(isset($_GET["linha"])){
	$linha = $_GET["linha"];
}
if(isset($_POST["linha"])){
	if($login_fabrica == 86){
		if(count($linha)>0){
			$linha = $_POST["linha"];
		}
	}else{
		if (strlen($linha) == 0) {
			$linha = $_POST["linha"];
		}
	}
}
if(strlen($_POST["credenciamento"])>0) $credenciamento = $_POST["credenciamento"];
else                                   $credenciamento = $_GET["credenciamento"];

if(strlen($_POST["tipo_Posto"])>0) $tipo_Posto = $_POST["tipo_Posto"];
else                               $tipo_Posto = $_GET["tipo_Posto"];

if( in_array($login_fabrica, array(11,172)) ){
	if(strlen($_POST["atendimento_lenoxx"])>0) $atendimento_lenoxx = $_POST["atendimento_lenoxx"];
	else                                       $atendimento_lenoxx = $_GET["atendimento_lenoxx"];
}

if ((strlen ($posto_codigo)==0) AND (strlen($posto_nome)==0) AND (strlen($posto_cidade)==0) AND (strlen($posto_estado)==0) AND (strlen($credenciamento)==0) AND (strlen($tipo_posto)==0) AND (strlen($codigo_posto_codigo)==0) AND strlen($btn_acao)>0) {
	if ( $login_fabrica == 86)  {
		if(count($linha)==0){
			$msg_erro["msg"][] = "Escolha um parâmetro para a pesquisa";
		}
	}else{
		if($login_fabrica == 35 OR $login_fabrica == 91 OR $login_fabrica == 86){
			if(count($linha)==0 AND empty($data_inicial) AND empty($data_final)){
				$msg_erro["msg"][] = "Escolha um parâmetro para a pesquisa";
			}
		}else{
			if(count($linha)==0){
				$msg_erro["msg"][] = "Escolha um parâmetro para a pesquisa";
			}
		}
	}
}

if((!empty($data_inicial)) AND (!empty($data_final))){
	if(empty($_POST["credenciamento"])){
		$msg_erro["msg"][] = "Escolha um status para a pesquisa";
	}
}

if(($login_fabrica == 35 OR $login_fabrica == 91 OR $login_fabrica == 86 ) AND strlen($data_inicial) > 0 AND strlen($data_final) > 0){

	list($di, $mi, $yi) = explode("/", $data_inicial);
	list($df, $mf, $yf) = explode("/", $data_final);

	if (!checkdate($mi, $di, $yi) or !checkdate($mf, $df, $yf)) {
		$msg_erro["msg"][]    = "Data Inválida";
		$msg_erro["campos"][] = "data";
	} else {
		$aux_data_inicial = "{$yi}-{$mi}-{$di}";
		$aux_data_final   = "{$yf}-{$mf}-{$df}";

		if (strtotime($aux_data_final) < strtotime($aux_data_inicial)) {
			$msg_erro["msg"][]    = "Data Final não pode ser menor que a Data Inicial";
			$msg_erro["campos"][] = "data";
		}
	}

	$sql_wanke = " AND tbl_credenciamento.data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59' ";

	$sub_sql_wanke .= " AND data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59' ";

}
	$status = $_POST['credenciamento'];

	if(isset($_POST['detalhado'])){
		$detalhado = $_POST["detalhado"];
	}

if(($login_fabrica == 35 OR $login_fabrica == 91 or $login_fabrica == 86) AND $_POST['credenciamento']){
	$sub_sql_wanke .= " AND status = '".$_POST['credenciamento']."' ";
}

 $array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
  "AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
  "ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
  "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
  "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
  "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
  "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
  "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");

if ( (count($msg_erro) == 0) && ((strlen ($posto_codigo) > 0) OR (strlen($posto_nome) > 0) OR (strlen($posto_cidade) > 0) OR (strlen($posto_estado) > 0) OR (strlen($linha) > 0) OR (count($linha) > 0) OR (strlen($credenciamento) > 0) OR (strlen($tipo_posto) > 0) OR (strlen($codigo_posto_codigo) > 0 ) OR (strlen($data_inicial) > 0 AND strlen($data_final) > 0))) {
	if($login_fabrica != 151){ $distinct = "DISTINCT" ; }

	if(in_array($login_fabrica, array(74))){ 
		$campo_telefone = 'contato_telefones';
	}else{
		$campo_telefone = 'contato_fone_comercial';
	}

	$sql_linha = "SELECT nome, linha FROM tbl_linha where fabrica = $login_fabrica";
	$res_linha = pg_query($con, $sql_linha);
	$nome_dados_linha_arr = array();

	for($l=0; $l<pg_num_rows($res_linha); $l++){
		$nome 	= pg_fetch_result($res_linha, $l, 'nome');
		$cod_linha 	= pg_fetch_result($res_linha, $l, 'linha');

		$nome_dados_linha_arr[$cod_linha] = $nome;
		
	}

	$fabrica_gestao = "SELECT fabrica FROM tbl_fabrica WHERE parametros_adicionais::jsonb->>'telecontrol_distrib' = 't'
					   AND fabrica = $login_fabrica";

	$res_fabrica_gestao = pg_query($con, $fabrica_gestao);

	$tem_fabrica_gestao = false;

	if (pg_num_rows($res_fabrica_gestao) > 0) {
		$tem_fabrica_gestao = true;
	}

	$sql = "SELECT	$distinct
					tbl_posto.posto                 ,
					tbl_posto_fabrica.nome_fantasia ,
					tbl_posto.nome                  ,
					tbl_posto.cnpj,
					tbl_posto.ie                    ,
					tbl_posto_fabrica.$campo_telefone AS fone,
					tbl_posto_fabrica.contato_endereco AS endereco,
					tbl_posto_fabrica.contato_bairro   AS bairro,
					tbl_posto_fabrica.contato_cep      AS cep   ,
					tbl_posto_fabrica.contato_numero as numero,
					tbl_posto_fabrica.contato_complemento as complemento,
					tbl_posto_fabrica.contato_cidade   AS cidade,
					tbl_posto_fabrica.contato_estado   AS estado,
					tbl_posto_fabrica.contato_bairro   AS bairro,
					tbl_posto_fabrica.contato_cel,
					tbl_posto_fabrica.codigo_posto   ,
					tbl_posto_fabrica.digita_os      ,
					tbl_posto_fabrica.contato_fax,
					tbl_posto_fabrica.valor_km, 
					tbl_posto_fabrica.parametros_adicionais,";					

					if ($tem_fabrica_gestao) {
							$sql .= "(SELECT TO_CHAR(data, 'DD/MM/YYYY')
									FROM tbl_credenciamento
									WHERE posto = tbl_posto.posto
									AND fabrica = $login_fabrica
									ORDER BY data DESC
									LIMIT 1) AS data_credenciamento,";
					}

					if ($login_fabrica == 151) {
						$sql .= 'tbl_posto_fabrica.contato_telefones AS telefones, ';
					}

					if($login_fabrica == 35 and $detalhado == 't'){
						$sql .= " tbl_posto_fabrica.desconto, 
						tbl_posto_fabrica.valor_km, 
						tbl_posto_fabrica.item_aparencia, 
						tbl_posto_fabrica.divulgar_consumidor,
						tbl_posto_fabrica.obs as observacao_interna,
						tbl_posto_fabrica.cobranca_endereco,
						tbl_posto_fabrica.cobranca_numero,
						tbl_posto_fabrica.cobranca_complemento,
						tbl_posto_fabrica.cobranca_bairro,
						tbl_posto_fabrica.cobranca_cep,
						tbl_posto_fabrica.cobranca_cidade,
						tbl_posto_fabrica.cobranca_estado, 
						tbl_posto_fabrica.pedido_faturado, 
						tbl_posto_fabrica.pedido_em_garantia,
						tbl_posto_fabrica.digita_os, 
						tbl_posto_fabrica.nomebanco,
						tbl_posto_fabrica.favorecido_conta,
						tbl_posto_fabrica.cpf_conta,
						tbl_posto_fabrica.tipo_conta,
						tbl_posto_fabrica.contato_atendentes as representante_legal,
						tbl_posto_fabrica.conta,
						tbl_posto_fabrica.agencia,
						tbl_posto_fabrica.banco,
						tbl_posto_fabrica.obs_conta,
						tbl_posto.suframa, 
						tbl_posto.capital_interior,
						tbl_transportadora.nome as nome_transportadora,
						tbl_transportadora_fabrica.codigo_interno as codigo_interno_trans, 
						";
					}					

					if($login_fabrica == 35 OR $login_fabrica == 91 or $login_fabrica == 86){
					$sql .= "
						(SELECT status FROM tbl_credenciamento WHERE posto = tbl_posto.posto AND fabrica = $login_fabrica $sub_sql_wanke ORDER BY data DESC LIMIT 1) AS credenciamento,
						(SELECT to_char(data,'DD/MM/YYYY') FROM tbl_credenciamento WHERE posto = tbl_posto.posto AND fabrica = $login_fabrica $sub_sql_wanke ORDER BY data DESC LIMIT 1) AS data_status, ";
					}else{
						$sql .= "tbl_posto_fabrica.credenciamento,";
					}

					if($login_fabrica == 86 AND $_POST['credenciamento'] == "EM DESCREDENCIAMENTO"){
						$sql .= "(SELECT dias FROM tbl_credenciamento WHERE posto = tbl_posto.posto AND fabrica = $login_fabrica $sub_sql_wanke ORDER BY data DESC LIMIT 1) AS dias,  
							(SELECT texto FROM tbl_credenciamento WHERE posto = tbl_posto.posto AND fabrica = $login_fabrica $sub_sql_wanke ORDER BY data DESC LIMIT 1) AS obs_tbl_credenciamento, ";
					}

					$sql .= "tbl_posto_fabrica.atendimento,
					tbl_posto_fabrica.contato_nome as contato,
					tbl_posto_fabrica.contato_email,
					tbl_tipo_posto.descricao  as tipo_posto
					";

			if ((strlen($linha) > 0 || count($linha)) && !in_array($login_fabrica, array(35,117))) {
				 $sql .= ", tbl_linha.nome as linha_descricao ";
			}elseif ($login_fabrica == 151) {
				 $sql .= ", tbl_linha.nome as linha_descricao ";
			}

			if (in_array($login_fabrica, [151])) {
				$sql .= "
					, TO_CHAR((
						SELECT MAX(data)
						FROM tbl_credenciamento
						WHERE fabrica = {$login_fabrica}
						AND posto = tbl_posto_fabrica.posto
						AND status = 'CREDENCIADO'
						LIMIT 1
					), 'DD/MM/YYYY') AS data_credenciado,
					TO_CHAR((
						SELECT MAX(data)
						FROM tbl_credenciamento
						WHERE fabrica = {$login_fabrica}
						AND posto = tbl_posto_fabrica.posto
						AND status = 'DESCREDENCIADO'
						LIMIT 1
					), 'DD/MM/YYYY') AS data_descredenciado,
					TO_CHAR((
						SELECT MAX(data)
						FROM tbl_credenciamento
						WHERE fabrica = {$login_fabrica}
						AND posto = tbl_posto_fabrica.posto
						AND status = 'EM CREDENCIAMENTO'
						LIMIT 1
					), 'DD/MM/YYYY') AS data_credenciamento,
					TO_CHAR((
						SELECT MAX(data)
						FROM tbl_credenciamento
						WHERE fabrica = {$login_fabrica}
						AND posto = tbl_posto_fabrica.posto
						AND status = 'EM DESCREDENCIAMENTO'
						LIMIT 1
					), 'DD/MM/YYYY') AS data_descredenciamento,
					tbl_posto_fabrica.divulgar_consumidor
				";
			}

	$sql .= "FROM   tbl_posto
			JOIN    tbl_posto_fabrica    ON tbl_posto.posto           = tbl_posto_fabrica.posto
			JOIN    tbl_fabrica          ON tbl_fabrica.fabrica       = tbl_posto_fabrica.fabrica
		    JOIN   tbl_tipo_posto      ON tbl_tipo_posto.tipo_posto  = tbl_posto_fabrica.tipo_posto and tbl_tipo_posto.fabrica=tbl_Posto_fabrica.fabrica ";

	if($login_fabrica == 35){
		$sql .= "LEFT JOIN tbl_transportadora_fabrica on tbl_transportadora_fabrica.transportadora = tbl_posto_fabrica.transportadora AND tbl_transportadora_fabrica.fabrica = $login_fabrica ";
		$sql .= "LEFT JOIN tbl_transportadora on tbl_transportadora_fabrica.transportadora = tbl_transportadora.transportadora ";
	}

	if (strlen($linha) > 0 || count($linha) > 0 AND $login_fabrica == 35) {	
		$sql .= "JOIN   tbl_posto_linha      ON tbl_posto_linha.posto     = tbl_posto.posto
				 ";
	}elseif (strlen($linha) > 0 || count($linha) > 0 ) {
		$sql .= "JOIN   tbl_posto_linha      ON tbl_posto_linha.posto     = tbl_posto.posto
				 JOIN    tbl_linha ON tbl_linha.linha           = tbl_posto_linha.linha ";
	}elseif ($login_fabrica == 151) {
		$sql .= "JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto.posto
				JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha and tbl_linha.fabrica = $login_fabrica";
	}
	
	if($login_fabrica == 35 OR $login_fabrica == 91 or $login_fabrica == 86){
		$sql .= " JOIN tbl_credenciamento ON tbl_posto_fabrica.posto = tbl_credenciamento.posto AND tbl_credenciamento.fabrica = $login_fabrica ";
	}

	$sql .= "WHERE   tbl_posto_fabrica.fabrica = $login_fabrica ";

	if($login_fabrica == 35){
		
		if(isset($_POST['pedido_garantia'])){
			$sql .= " AND tbl_posto_fabrica.pedido_em_garantia is true ";
		}

		if(isset($_POST['pedido_faturado'])){
			$sql .= " AND tbl_posto_fabrica.pedido_faturado is true ";			
		}

		if(isset($_POST['digita_os'])){
			$sql .= " AND tbl_posto_fabrica.digita_os is true ";			
		}
	}

	if(($login_fabrica == 86) and (!empty($data_inicial)) AND (!empty($data_final) ) ){
		$sql .= "  AND tbl_credenciamento.data BETWEEN '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'  ";
	}

	$xposto_codigo = str_replace (" " , "" , $posto_codigo);
	$xposto_codigo = str_replace ("-" , "" , $xposto_codigo);
	$xposto_codigo = str_replace ("/" , "" , $xposto_codigo);
	$xposto_codigo = str_replace ("." , "" , $xposto_codigo);

	//HD 110541
	if (strlen ($atendimento_lenoxx) > 0 and $atendimento_lenoxx <> " ") $sql .= " AND tbl_posto_fabrica.atendimento ='$atendimento_lenoxx'";
	if (strlen ($posto_codigo) > 0 ) $sql .= " AND tbl_posto.cnpj like '%$xposto_codigo%' ";
	if (strlen ($codigo_posto_codigo) > 0 ) $sql .= " AND tbl_posto_fabrica.codigo_posto ='$codigo_posto_codigo'";
	if (strlen ($posto_nome) > 0 )   $sql .= " AND (tbl_posto.nome  ILIKE '%$posto_nome%' OR tbl_posto.nome_fantasia ILIKE '%$posto_nome%')";
	if (strlen ($posto_cidade) > 0 ) $sql .= " AND tbl_posto_fabrica.contato_cidade = '$posto_cidade'";
	if (strlen ($posto_estado) > 0 ) $sql .= " AND tbl_posto_fabrica.contato_estado = '$posto_estado'";
	if (strlen ($posto_bairro) > 0 ) $sql .= " AND tbl_posto_fabrica.contato_bairro ILIKE '%$posto_bairro%'";

	if (strlen($inspetor) > 0 && in_array($login_fabrica, array(169,170))) $sql .= " AND tbl_posto_fabrica.admin_sap = $inspetor";

	if (strlen($linha) > 0 || count($linha) > 0 ) {

			$condJoinLinha = " IN (";
			for($i = 0; $i < count($linha); $i++){
				if($i == count($linha)-1 ){
					$condJoinLinha .= $linha[$i].")";
				}else {
					$condJoinLinha .= $linha[$i].", ";
				}
			}
			if($login_fabrica == 35){
				$sql .=	" AND tbl_posto_linha.linha {$condJoinLinha} ";
			}else{
				$sql .=	" AND tbl_linha.linha {$condJoinLinha} ";
			}
			

	}

	if(($login_fabrica == 35 OR $login_fabrica == 91) AND strlen($data_inicial) > 0 AND strlen($data_final) > 0){
		$sql .= $sql_wanke;
	}

	if($login_fabrica == 86){
		if($contrato == "t"){
			$condContrato = " AND tbl_posto_fabrica.parametros_adicionais ~* E'\"contrato\":\"t\"' ";
		}elseif($contrato == 'f'){
      $condContrato = " AND (tbl_posto_fabrica.parametros_adicionais !~* E'\"contrato\":\"t\"' OR tbl_posto_fabrica.parametros_adicionais is null) ";
    }

    $sql.= $condContrato;
	}

	if (strlen ($credenciamento) > 0 ){
		if($login_fabrica == 35 OR $login_fabrica == 91){
			$sql .= " AND tbl_credenciamento.status = '$credenciamento' ";
		}elseif($login_fabrica != 86){
			$sql .= " AND tbl_posto_fabrica.credenciamento = '$credenciamento' ";
		}

		if((!empty($data_inicial)) AND (!empty($data_final))){
			$sql .= " AND tbl_credenciamento.status = '$credenciamento' ";
		}else{
			$sql .= " AND tbl_posto_fabrica.credenciamento = '$credenciamento' ";
		}

	}

	if (strlen ($tipo_posto) > 0 ) $sql .= " AND tbl_tipo_posto.tipo_posto = '$tipo_posto' ";

	if ( in_array($login_fabrica, array(11,172)) ) {
		$sql .= " AND tbl_posto_fabrica.credenciamento <> 'REPROVADO' ";
	}
	$sql .= " ORDER BY cidade, tbl_posto.nome";

	$res_consulta = pg_exec ($con,$sql);

	$count = pg_num_rows($res_consulta);

	if(isset($_POST["gerar_excel"]) && $_POST["gerar_excel"]== "true"){

		$data = date("d-m-Y-H:i");

		$fileName = "relatorio_consulta_gerencia-{$data}.csv";

		$file = fopen("/tmp/{$fileName}", "w");

		$head = "Codigo;Nome Fantasia;Razão Social;";
		if (in_array($login_fabrica,array(80,169,170))){
			$head .= "Linha;";
		}

		if($login_fabrica == 35){
			$desc_contato_cel = "Celular Contato; ";
		}
		if($login_fabrica == 151){
			$desc_contato_cel = "fone 2; fone 3; celular;";
		}
		$head .= "Endereço;Numero;Complemento;Bairro;Cidade;UF;Cep;Fone;$desc_contato_cel Fax;CNPJ;Inscrição Estadual;Nome para Contato;E-mail;";

		if (in_array($login_fabrica, [35]) && $detalhado == 't') {
			$head .= "Representante Legal;";
		}
		
		if($login_fabrica == 151){
			$head .= "Valor Mão Obra;";
		}

		if($login_fabrica == 86){
			if($status == "CREDENCIADO"){
				$head .= "Data Inclusão;";
			}if($status == "DESCREDENCIADO"){
				$head .= "Data Descredenciamento;";
			}if($status == "EM DESCREDENCIAMENTO"){
				$head .= "Data Inclusão;Data Em Descredenciamento;Dias;Observação;";
			}
		}

		if($login_fabrica == 45){
			$head .= "Linhas;";
		}

		if($login_fabrica == 35 OR $login_fabrica == 91){
			$head .= "Status;";		

			if($login_fabrica == 35){
				if($detalhado == 't'){
					$head .= "Data Status;";
					$head .= "Tipo Posto;";
					$head .= "Região Suframa;";
					$head .= "Capital/Interior;";
					$head .= "Transportadora;";
					$head .= "Desconto;";
					$head .= "Valor KM;";
					$head .= "Item Aparência;";
					$head .= "Divulga Consumidor;";
					$head .= utf8_encode("Observação Interna;");
					$head .= utf8_encode("Observação (Cadence);");
					$head .= utf8_encode("Observação (Oster);");
					$head .= utf8_encode("Informações de Cobrança;");
					$head .= "Banco;";
					$head .= utf8_encode("Agência;");
					$head .= "Conta;";
					$head .= "Tipo de Conta;";
					$head .= "Favorecido;";
					$head .= "Observação;";




					foreach($nome_dados_linha_arr as $valores){
						$head .= "$valores;";
					}
					$head .= utf8_encode("Pedido Faturado;");
					$head .= utf8_encode("Pedido em Garantia;");
					$head .= utf8_encode("Digita OS;");
				}

				$head .= "\n";
			}else{
				$head .= "Data Status;\n";
			}
		}else{
			$head .= "Status;";
			if ($tem_fabrica_gestao) {
				$head .= "Data de Credenciamento;";
			}

			if (in_array($login_fabrica, [151])) {
				$head .= "Último Credenciamento;Último Descredenciamento;Último Em Credenciamento;Último Em Descredenciamento;";
			}

			if (in_array($login_fabrica, [169,170])) {
				$head .= "Valor Km;";
			}
			$head .= "Tipo Posto;";

			if (in_array($login_fabrica, [151])) {
				$head .= "Divulgação;";
			}

			if (!in_array($login_fabrica,array(169,170))){
				$head .= "Linha;";
			}
			if ($login_fabrica != 104){
				$head .="\n ";
				}
		}
		if($login_fabrica == 104){
			$sql_teste = "SELECT DISTINCT nome AS nome_linha,
								linha
							FROM tbl_linha
							WHERE tbl_linha.fabrica = $login_fabrica
							ORDER BY nome_linha";
			$res_teste = pg_query($con, $sql_teste);
			$linhas_vonder = pg_fetch_all ($res_teste);
			for($z=0; $z<count($linhas_vonder);$z++){
						$head .= $linhas_vonder[$z]["nome_linha"].";";

						}
				$head .= "\n ";
		}
        if ($login_fabrica == 117) {
                $head = "Nome Fantasia;Razão Social;Endereço;Numero;Bairro;Cidade;UF;Cep;Fone;CNPJ;Inscrição Estadual;Nome para Contato;E-mail;Status;Tipo Posto;";
                $head .="\n";
        }

		fwrite($file, $head);
		$dados_posto_linha_arr_ex = array();
		$limpa_obs = "/\r\n|\n|\r/";
		for($i = 0; $i<$count;$i++) {

			$posto          = trim(pg_result($res_consulta,$i,posto));
			$codigo_posto   = trim(pg_result($res_consulta,$i,codigo_posto));
			$cnpj           = trim(pg_result($res_consulta,$i,cnpj));
			$ie             = trim(pg_result($res_consulta,$i,ie));
			$fone           = trim(pg_result($res_consulta,$i,fone));
			$telefones      = trim(pg_result($res_consulta,$i,telefones));			
			$nome_fantasia  = trim(pg_result($res_consulta,$i,nome_fantasia));
			$nome           = trim(pg_result($res_consulta,$i,nome));
			$endereco       = trim(pg_result($res_consulta,$i,endereco));
			$numero         = trim(pg_result($res_consulta,$i,numero));
			$complemento    = trim(pg_result($res_consulta,$i,complemento));
			$cep            = trim(pg_result($res_consulta,$i,cep));
			$cidade         = trim(pg_result($res_consulta,$i,cidade));
			$estado         = trim(pg_result($res_consulta,$i,estado));
			$credenciamento = trim(pg_result($res_consulta,$i,credenciamento));
			$bairro         = trim(pg_result($res_consulta,$i,bairro));
			$digita_os      = trim(pg_result($res_consulta,$i,digita_os));
			$contato        = trim(pg_result($res_consulta,$i,contato));
			$email          = trim(pg_result($res_consulta,$i,contato_email));
			$email          = str_replace(";", "|", $email);
			$data_status    = trim(pg_result($res_consulta,$i,data_status));
			$contato_fax					= trim(pg_result($res_consulta,$i,contato_fax)); 
			$parametros_adicionais = trim(pg_result($res_consulta,$i,parametros_adicionais));

			$parametros_adicionais = json_decode($parametros_adicionais,true);
			$contato_cel    = pg_fetch_result($res_consulta, $i, contato_cel);

				$valor_km 						= trim(pg_result($res_consulta,$i,valor_km));
			if($login_fabrica == 35 and $detalhado == 't'){
				
				$obs_cadence 	= preg_replace($limpa_obs, "", $parametros_adicionais['obs_cadence']);
				$obs_oster 		= preg_replace($limpa_obs, "", $parametros_adicionais['obs_oster']);

				$desconto 						= trim(pg_result($res_consulta,$i,desconto));
				$suframa 						= trim(pg_result($res_consulta,$i,suframa));
				$suframa = ($suframa == 'f')? "Não" : "Sim";
				$capital_interior 				= trim(pg_result($res_consulta,$i,capital_interior));

				$nome_transportadora 			    = trim(pg_result($res_consulta,$i,nome_transportadora));
				$codigo_interno_trans 			    = trim(pg_result($res_consulta,$i,codigo_interno_trans));

				$nome_codigo_transportadora = ( (!empty($codigo_interno_trans)) AND (!empty($nome_transportadora)))? "$codigo_interno_trans - $nome_transportadora" : "$nome_transportadora";

				
				$valor_km 						= trim(pg_result($res_consulta,$i,valor_km));
				$item_aparencia 				= (trim(pg_result($res_consulta,$i,item_aparencia)) == 't')? "Sim": "Não";
				$divulgar_consumidor 			= (trim(pg_result($res_consulta,$i,divulgar_consumidor)) == "t" )? "Sim": "Não";
				$observacao_interna 			= preg_replace($limpa_obs, "", trim(pg_result($res_consulta,$i,observacao_interna)));
				$cobranca_endereco 				= trim(pg_result($res_consulta,$i,cobranca_endereco));
				$cobranca_numero 				= trim(pg_result($res_consulta,$i,cobranca_numero));
				$cobranca_complemento 			= trim(pg_result($res_consulta,$i,cobranca_complemento));
				$cobranca_bairro 				= trim(pg_result($res_consulta,$i,cobranca_bairro));
				$cobranca_cep 					= trim(pg_result($res_consulta,$i,cobranca_cep));
				$cobranca_cidade 				= trim(pg_result($res_consulta,$i,cobranca_cidade));
				$cobranca_estado 				= trim(pg_result($res_consulta,$i,cobranca_estado));
				
				$pedido_faturado 				= trim(pg_result($res_consulta,$i,pedido_faturado));
				$pedido_em_garantia 			= trim(pg_result($res_consulta,$i,pedido_em_garantia));
				$digita_os 						= trim(pg_result($res_consulta,$i,digita_os));

				$nomebanco 						= trim(pg_result($res_consulta,$i,nomebanco));
				$favorecido_conta 				= trim(pg_result($res_consulta,$i,favorecido_conta));
				$cpf_conta 						= trim(pg_result($res_consulta,$i,cpf_conta));
				$tipo_conta 					= trim(pg_result($res_consulta,$i,tipo_conta));
				$conta 							= trim(pg_result($res_consulta,$i,conta));
				$agencia 						= trim(pg_result($res_consulta,$i,agencia));
				$banco 							= trim(pg_result($res_consulta,$i,banco));
				$obs_conta 						= trim(pg_result($res_consulta,$i,obs_conta));
				$obs_conta 			= preg_replace($limpa_obs, "", $obs_conta);

				$representante_legal            = trim(pg_fetch_result($res_consulta, $i, 'representante_legal'));

				$observacao_interna 			= preg_replace($limpa_obs, "", $obs_conta);			

				$informacao_bancaria_excel = "$banco - $nomebanco $agencia $conta $tipo_conta $cpf_conta $favorecido_conta $obs_conta";

			 	$informacao_cobranca_excel = "$cobranca_cep $cobranca_endereco $cobranca_numero $cobranca_bairro  $cobranca_cidade $cobranca_estado ";

			 	$sql_dados_posto_linha = "SELECT tbl_posto_linha.posto, tbl_posto_linha.linha
											from tbl_posto_linha
											inner join tbl_linha on tbl_linha.linha = tbl_posto_linha.linha
											where tbl_linha.fabrica = $login_fabrica 
											and tbl_posto_linha.posto = $posto";
				$res_dados_posto_linha = pg_query($con, $sql_dados_posto_linha);
				for($pl=0; $pl<pg_num_rows($res_dados_posto_linha); $pl++){
					$linha 							= pg_fetch_result($res_dados_posto_linha, $pl, linha);
					$dados_posto_linha_arr_ex[$posto][] = $linha;
				}
			}

			if (in_array($login_fabrica, [151])) {
				$data_credenciado = pg_fetch_result($res_consulta, $i, "data_credenciado");
				$data_descredenciado = pg_fetch_result($res_consulta, $i, "data_descredenciado");
				$data_credenciamento = pg_fetch_result($res_consulta, $i, "data_credenciamento");
				$data_descredenciamento = pg_fetch_result($res_consulta, $i, "data_descredenciamento");

				$divulgar_consumidor = (pg_fetch_result($res_consulta, $i, "divulgar_consumidor") == 't') ? "Sim" : "Não";
			}


			if(in_array($login_fabrica, array(74))){
				$fone = str_replace(array("{","}"), array("",""), $fone);

				$fone = explode(",", $fone);
				$idxRemover = array();
				foreach ($fone as $idx => $value) {
					if($value == '""'){
						$idxRemover[] = $idx;
					}
				}				
				foreach ($idxRemover as $value) {
					unset($fone[$value]);
				}

				$fone = implode("/ ", $fone);
				$fone = str_replace('"', "", $fone);

				if($fone == "NULL"){
					$fone = "";
				}
			}
			if ($login_fabrica == 151) {
	            $chars_replace = array('{','}','"');
	            $contato_telefones = str_replace($chars_replace, "", $telefones);

	            $fones = array();
	            $fones = explode(',', $contato_telefones);

	            $fone1 = $fones[0];
	            $fone2 = $fones[1];
	            $fone3 = $fones[2];

	            if(empty($fone1)){
	            	$fone1 = $fone;
	            }

	            $fone = $fone1 . ';' . $fone2 . ';' . $fone3;

			}

			$ie = (empty($ie) OR strtoupper($ie) == "ISENTO") ? $ie : "'".$ie;

			$tipo_posto_descricao   = trim(pg_result($res_consulta,$i,'tipo_posto'));
			$linha   				= trim(pg_result($res_consulta,$i,'linha_descricao'));

			if($login_fabrica == 86 AND $_POST['credenciamento'] == "EM DESCREDENCIAMENTO"){ 
				$dias_p_descredenciar 		= trim(pg_result($res_consulta,$i,dias));
				$obs_tbl_credenciamento		= trim(pg_result($res_consulta,$i,obs_tbl_credenciamento));

				if(!empty($data_status)){
					list($dnf, $mnf, $ynf) = explode("/", $data_status);
					$data = $ynf."-".$mnf."-".$dnf;

					$date = date_create($data);
					date_add($date, date_interval_create_from_date_string($dias_p_descredenciar.' days'));
					$data_descredenciamento = date_format($date, 'Y-m-d');

					list($ynf, $mnf, $dnf) = explode("-", $data_descredenciamento);
					$data2 = $dnf."/".$mnf."/".$ynf;
				}

				if(empty($data_status)){
					$data2 = "";
				}

			}

			if($login_fabrica==45){// HD 19498 13/5/2008
				$sql_linha = "SELECT DISTINCT nome AS nome_linha
									from tbl_linha
							JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha
							WHERE tbl_linha.fabrica = $login_fabrica
							AND   tbl_posto_linha.posto = $posto";
				$resl = pg_exec($con, $sql_linha);

				if(pg_numrows($resl)>0){
					$linhas = "";
					for($z=0; $z<pg_numrows($resl);$z++){
						$nome_linha = pg_result($resl, $z, nome_linha);
						$linhas .= $nome_linha.",";
						$xlinhas = substr($linhas,0, -1);
					}
				}
			}
			$body = "$codigo_posto;$nome_fantasia;$nome;";
			if (in_array($login_fabrica,array(80,169,170))){

				$sqlqtde = "SELECT count(*) from tbl_linha where fabrica = $login_fabrica and ativo is true";
				$resqtde = pg_exec($sqlqtde);

				$num_linha = pg_result($resqtde,0,0);

				$sql_linha = "SELECT DISTINCT nome AS nome_linha, tbl_linha.linha
							FROM tbl_linha
						JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha
						WHERE    tbl_linha.fabrica = $login_fabrica
							AND  tbl_posto_linha.posto = $posto
							AND  tbl_linha.ativo is true
						ORDER BY nome_linha;";
				$res_linha = pg_exec ($con,$sql_linha);
				unset($linha_nome);
				unset($linha_escreve);
				if (pg_numrows($res_linha)>0){
					$linha_cont = pg_numrows($res_linha);
					$linha_nome = "";
					$linha_escreve = "";

					for ($x=0; $x<pg_numrows($res_linha);$x++){

						if (in_array($login_fabrica,array(169,170))){
							$linha_nome[] = pg_result($res_linha, $x, 'linha') . " - " .pg_result($res_linha, $x, 'nome_linha');
						} else {
							$linha_nome[] = pg_result($res_linha, $x, nome_linha);
						}
					}
					if ($num_linha == $linha_cont) {
						$linha_escreve = 'Todas as Linhas';
					} else {
						$linha_escreve = implode($linha_nome,'/');
					}
				}
				$body .= "$linha_escreve;";
			}
				$cnpj = mascara($cnpj,'##.###.###/####-##');
			
			if($login_fabrica == 35 || $login_fabrica == 151){
				$valor_contato_cel = $contato_cel ."; ";
			}else{
				$valor_contato_cel = "";
			}

			if ($tem_fabrica_gestao) {
				$data_credenciamento = trim(pg_result($res_consulta,$i,'data_credenciamento'));
			}

			$body .= "$endereco;$numero;$complemento;$bairro;$cidade;$estado;$cep;$fone; $valor_contato_cel $contato_fax;$cnpj;$ie;$contato;$email;";

			if (in_array($login_fabrica, [35]) && $detalhado == 't') {
				$body .= "{$representante_legal};";
			}

			if($login_fabrica == 151){
				$body .= "{$parametros_adicionais['valor_mao_obra']};";
			}

			if($login_fabrica == 86){
				if($status == "CREDENCIADO"){
					$body .= "$data_status;";
				}if($status == "DESCREDENCIADO"){
					$body .= "$data_status;";
				}if($status == "EM DESCREDENCIAMENTO"){
					$body .= "$data_status;$data2;$dias_p_descredenciar;$obs_tbl_credenciamento;";
				}
			}


			if($login_fabrica==45){
				$body .= "$xlinhas;";
			}

			if($login_fabrica == 35 OR $login_fabrica == 91){
				$body .= "$credenciamento;";				

				if($login_fabrica == 35){
					if($detalhado == 't'){
						$body .= "$data_status;";
						$body .= "$tipo_posto_descricao;";

						$body .= "$suframa;";
						$body .= "$capital_interior;";

						$body .= "$nome_codigo_transportadora;";						

						$body .= "$desconto;";	
						$body .= "$valor_km;";	
						$body .= "$item_aparencia;";
						$body .= "$divulgar_consumidor;";
						$body .= "$observacao_interna;";
						$body .= "$obs_cadence;";
						$body .= "$obs_oster;";
						$body .= "$informacao_cobranca_excel;";
						$body .= "$banco - $nomebanco;";
						$body .= "$agencia;";
						$body .= "$conta;";
						$body .= "$tipo_conta;";
						$body .= "$cpf_conta - $favorecido_conta;";						
						$body .= "$obs_conta;";

						foreach(array_keys($nome_dados_linha_arr) as $arr_posto){
							if(in_array($arr_posto, $dados_posto_linha_arr_ex[$posto])){
								$body .= "Sim;";
							}else{
								$body .= "Não;";
							}
						}

						if($pedido_faturado == 't'){
							$body .= "Sim;";
						}else{
							$body .= "Não;";
						}
						if($pedido_em_garantia == 't'){
							$body .= "Sim;";
						}else{
							$body .= "Não;";
						}
						if($digita_os == 't'){
							$body .= "Sim;";
						}else{
							$body .= "Não;";
						}
					}

				}else{
					$body .= "$data_status\n";
				}



			}else{
				$body .= "$credenciamento;";

				if ($tem_fabrica_gestao) {
					$body .= "$data_credenciamento;";
				}
				
				if (in_array($login_fabrica, [151])) {
					$body .= "$data_credenciado;$data_descredenciado;$data_credenciamento;$data_descredenciamento;";
				}
				
				if (in_array($login_fabrica, [169,170])) {
					$body .= "$valor_km;";
				}
				$body .= "$tipo_posto_descricao;";

				if (in_array($login_fabrica, [151])) {
					$body .= "$divulgar_consumidor;";
				}
				$body .= "$linha;";
			}

			if($login_fabrica==104){// HD 1421942 10/02/2014
				for($z=0; $z < count($linhas_vonder);$z++){
					$sql_linha = "SELECT DISTINCT posto
								from tbl_posto_linha
						WHERE tbl_posto_linha.posto = $posto
						AND   tbl_posto_linha.linha = ".$linhas_vonder[$z]["linha"];
					$resl = pg_query($con, $sql_linha);
					if (pg_num_rows($resl)>0 ){
						$body .= "X;";
					}else{
						$body .= ";";
					}
				}
			}

			if ($login_fabrica != 35 OR $login_fabrica != 91)$body .= "\n";
			fwrite($file, $body);
		}

			if (file_exists("/tmp/{$fileName}")) {
				system("mv /tmp/{$fileName} xls/{$fileName}");

				echo "xls/{$fileName}";
			}
		exit;
	}
}

$layout_menu = "gerencia";
$title = "CONSULTA DE POSTOS";

if ($gerar_xls == "t" and count($msg_erro)==0) {
	ob_start();
}
else {
	include "cabecalho_new.php";
	$plugins = array(
		"multiselect",
		"autocomplete",
		"datepicker",
		"shadowbox",
		"mask",
		"dataTable"
	);

	include("../admin/plugin_loader.php");
}

if ($gerar_xls == "t" and count($msg_erro)==0) {
}
else {
?>

<script type='text/javascript'>

$(function(){

    $.datepickerLoad(Array("data_final", "data_inicial"));
    $.autocompleteLoad(Array("produto", "peca", "posto"));

    Shadowbox.init();

    $("span[rel=lupa]").click(function () {
        $.lupa($(this));
    });

    $("#linha").multiselect({
        selectedText: "selecionados # de #"
    });

});
function retorna_posto(retorno){
    $("#codigo_posto_codigo").val(retorno.codigo);
	$("#posto_nome").val(retorno.nome);
	$("#posto_codigo").val(retorno.cnpj);
}


function formata_cnpj(cnpj, form){
	var mycnpj = '';
		mycnpj = mycnpj + cnpj;
		myrecord = "posto_codigo";
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

</script>
<!--

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->
<?

if(strlen($msg_erro)>0){
	echo "<div align='center'><div class='msg_erro' style='width:850px;'>$msg_erro</div></div>";
}

if(isset($_POST["linha"])){
	if($login_fabrica == 86){
		if(count($linha)>0){
			$linha = $_POST["linha"];
		}
	}else{
		if (strlen($linha) == 0) {
			$linha = $_POST["linha"];
		}
	}
}

if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>
<form name='frm_posto' method='POST' ACTION='<?=$PHP_SELF?>' align='center' class='form-search form-inline tc_formulario'>
	<div class='titulo_tabela '>Parâmetros de Pesquisa</div>
	<br/>

	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='codigo_posto'>Código Posto</label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
						<input type="text" name="codigo_posto_codigo" id="codigo_posto_codigo" class='span12' value="<? echo $codigo_posto_codigo ?>" >
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='descricao_posto'>Nome Posto</label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
						<input type="text" name="posto_nome" id="posto_nome" class='span12' value="<? echo $posto_nome ?>" >&nbsp;
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>CNPJ</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type='text' id='posto_codigo' name='posto_codigo' size='20' maxlength='18'value='<?=$posto_codigo?>' onKeyUp="javascript: formata_cnpj(this.value, 'frm_posto')" >
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="cnpj" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Tipo Posto</label>
					<div class='controls controls-row'> <?
						$sql = "SELECT  *
								FROM    tbl_tipo_posto
								WHERE   tbl_tipo_posto.fabrica = $login_fabrica AND ativo
								ORDER BY tbl_tipo_posto.descricao;";
						$res = pg_exec ($con,$sql);

						if (pg_numrows($res) > 0) {
							echo "<select class='frm' style='width: 150px;' name='tipo_posto' class='frm'>\n";
							echo "<option value=''>TODOS</option>\n";

							for ($x = 0 ; $x < pg_numrows($res) ; $x++){
								$aux_tipo_posto = trim(pg_result($res,$x,tipo_posto));
								$aux_descricao  = trim(pg_result($res,$x,descricao));

								echo "<option value='$aux_tipo_posto'"; if ($tipo_posto == $aux_tipo_posto) echo " SELECTED "; echo ">$aux_descricao</option>\n";
							}
							echo "</select>\n";
						}?>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='codigo_posto'>Estado</label>
					<div class='controls controls-row'>
						<select name='posto_estado' class='frm addressState' id='posto_estado'>
							<option value='' selected>Todos Estados</option><?
						    foreach ($array_estado as $k => $v) {
								echo '<option value="'.$k.'"'.($posto_estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
							}?>
						</select>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Cidade</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<select id="posto_cidade" name="posto_cidade" class="span10 addressCity">
								<option value="" >Selecione</option>
								<?php
								if (strlen($posto_estado) > 0) {
									$sql = "SELECT DISTINCT * FROM (
												SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('".$posto_estado."')
												UNION (
													SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('".$posto_estado."')
												)
											) AS cidade
											ORDER BY cidade ASC";
									$res = pg_query($con, $sql);

									if (pg_num_rows($res) > 0) {
										while ($result = pg_fetch_object($res)) {
											$selected  = (trim($result->cidade) == trim($posto_cidade)) ? "SELECTED" : "";

											echo "<option value='{$result->cidade}' {$selected} >{$result->cidade} </option>";
										}
									}
								}
								?>
							<!-- <input type='text' name='posto_cidade' id='posto_cidade' size='20' value='<?=$posto_cidade?>' class='frm' readonly=''> -->
							</select>
						</div>
					</div>
				</div>
			</div>			
			<div class='span2'></div>
		</div>

		<div class='row-fluid'>
			<div class='span2'></div>
			<?php if (!in_array($login_fabrica, array(35,169,170))) {?>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Bairro</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type='text' name='posto_bairro' size='20' value='<?=$posto_bairro?>' class='frm'>
						</div>
					</div>
				</div>
			</div>
			<?php } elseif(!in_array($login_fabrica, array(35))) {?>
			<div class='span4'>
				<div class='control-group <?=(in_array("inspetor", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='inspetor'>Inspetor</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<?php
				                $sqlInspetor = "SELECT admin,
				                                      login,
				                                      nome_completo
				                                 FROM tbl_admin
				                                WHERE tbl_admin.fabrica = {$login_fabrica}
				                                  AND tbl_admin.admin_sap IS TRUE
				                                  AND tbl_admin.ativo IS TRUE
				                             ORDER BY tbl_admin.nome_completo ASC;";
				                $resInspetor  = pg_query($con, $sqlInspetor);
				                $rowsInspetor = pg_fetch_all($resInspetor);
				            ?>
				            <select class='frm' name="inspetor" id="inspetor">
				                <option value="">Escolha ...</option>
				                <?php foreach($rowsInspetor as $k => $rows) { ?>
				                        <option value="<?php echo $rows['admin']; ?>" <?php echo ($rows['admin'] == $inspetor) ? 'selected="selected"' : '' ; ?>><?php echo empty($rows['nome_completo']) ? $rows['login'] : $rows['nome_completo']; ?></option>
				                <?php } ?>
				            </select>
						</div>
					</div>
				</div>
			</div>
			<?php } ?>
			<div class='span4'>
				<div class='control-group <?=(in_array("linha", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='linha'><?=($login_fabrica == 117)?"Macro - Família":"Linha"?></label>
					<div class='controls controls-row'>
						<?
                        if ($login_fabrica == 117) {
                                $sql_linha = "SELECT DISTINCT tbl_linha.linha,
                                               tbl_linha.nome
                                            FROM tbl_linha
                                                JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
                                                JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha
                                            WHERE tbl_macro_linha_fabrica.fabrica = $login_fabrica
                                                AND     tbl_linha.ativo = TRUE
                                            ORDER BY tbl_linha.nome;";
                        } else {						
							$sql_linha = "SELECT
												linha,
												nome
										  FROM tbl_linha
										  WHERE tbl_linha.fabrica = $login_fabrica
										  ORDER BY tbl_linha.nome ";
						}
							$res_linha = pg_query($con, $sql_linha); ?>
								<select name="linha[]" id="linha" multiple="multiple" class='span12'>
									<?php

									$selected_linha = array();
									foreach (pg_fetch_all($res_linha) as $key) {
										if(isset($linha)){
											foreach ($linha as $id) {
												if ( isset($linha) && ($id == $key['linha']) ){
													$selected_linha[] = $id;
												}
											}
										} ?>


										<option value="<?php echo $key['linha']?>" <?php if( in_array($key['linha'], $selected_linha)) echo "SELECTED"; ?> >

											<?php echo $key['nome']?>

										</option>
							  <?php } ?>
							</select>
					</div>
				</div>
			</div>
			<?php if($login_fabrica == 35){?>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto_digita", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='posto_digita'>Posto pode Digitar</label>
					<div class='controls controls-row'>
						<div class="form-check">
						  <label class="form-check-label">
						    <input class="form-check-input" type="checkbox" name="pedido_faturado" <?php echo (isset($_POST['pedido_faturado']))? " checked " : " "; ?> value="t">
						    Pedido Faturado (Manual)
						  </label>
						  <br>
						  <label class="form-check-label">
						    <input class="form-check-input" type="checkbox" name="pedido_garantia" <?php echo (isset($_POST['pedido_garantia']))? " checked " : " "; ?> value="t">
						    Pedido em Garantia (Manual)
						  </label>
						  <br>
						  <label class="form-check-label">
						    <input class="form-check-input" type="checkbox" name="digita_os" <?php echo (isset($_POST['digita_os']))? " checked " : " "; ?> value="t">
						    Digita OS
						  </label>
						</div>
					</div>
				</div>
			</div>
			<?php } ?>
			<div class='span2'></div>
		</div>
		<?php

			if($login_fabrica == 91 OR $login_fabrica == 86){
		?>
		<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='data_inicial'>Data Inicial</label>
						<div class='controls controls-row'>
							<div class='span4'>
								<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='span12' value= "<?=$data_inicial?>">
							</div>
						</div>
					</div>
				</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_final'>Data Final</label>
					<div class='controls controls-row'>
						<div class='span4'>
							<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='span12' value="<?=$data_final?>" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<?php
			}
		?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>Status</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<select class='frm' style='width: 150px;' name='credenciamento' >
								<option value=''>TODOS</option>
								<option value='CREDENCIADO' <? if ($credenciamento== "CREDENCIADO") echo " SELECTED "; ?> >CREDENCIADO</option>
								<option value='DESCREDENCIADO' <? if ($credenciamento== "DESCREDENCIADO") echo " SELECTED "; ?> >DESCREDENCIADO</option>
								<option value='EM CREDENCIAMENTO' <? if ($credenciamento== "EM CREDENCIAMENTO") echo " SELECTED "; ?> >EM CREDENCIAMENTO</option>
								<option value='EM DESCREDENCIAMENTO' <? if ($credenciamento== "EM DESCREDENCIAMENTO") echo " SELECTED "; ?> >EM DESCREDENCIAMENTO</option>
								<?
								if ( in_array($login_fabrica, array(11,172)) ) {
									echo "<option value='REPROVADO'"; if ($credenciamento== "REPROVADO") echo " SELECTED "; echo ">REPROVADO</option>\n";
								}?>

								</select>
						</div>
					</div>
				</div>
			</div>
			<?php if($login_fabrica == 35){ ?>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'><br></label>
					<div class='controls controls-row'>
						<input class="form-check-input" type="checkbox" name="detalhado" <?php echo (isset($_POST['detalhado']))? " checked " : " "; ?> value="t">
						    Mostrar Informações Detalhadas
					</div>
				</div>
			</div>
			<?php } ?>

			<?php
				if($login_fabrica == 86){
			?>
      <div class='span4'>
        <div class='control-group'>
          <label class='control-label' for='contrato'>Contrato</label>
          <div class='controls controls-row'>
            <div class='span12'>
              <select class='frm' name='contrato'>
                <option value=''>TODOS</option>
                <option value='t' <? if ($contrato== "t") echo " SELECTED "; ?> >POSTO COM CONTRATO</option>
                <option value='f' <? if ($contrato== "f") echo " SELECTED "; ?> >POSTO SEM CONTRATO</option>
                </select>
            </div>
          </div>
        </div>
      </div>

			<div class="span2"></div>
			<?php
				}
			?>

			<!-- <div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'></label>
					<div class='controls controls-row'>


           Gerar em Excel <input type='checkbox' name='gerar_xls' value='t' <? if($gerar_xls=='t') echo ' checked ';?>>


					</div>
				</div>
			</div> -->
		</div>

		<?//HD 110541
		if( in_array($login_fabrica, array(11,172)) ){ ?>
			<div class='row-fluid'>
				<div class='span2'></div>

					<div class='span4'>
						<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
							<label class='control-label' for='codigo_posto'>Atendimento</label>
							<div class='controls controls-row'>

								<select class='frm' style='width: 150px;' name='atendimento_lenoxx' >
									<option value=' '> </option>
									<option value='b'>BALCÃO</option>
		 							<option value='r'>REVENDA</option>
									<option value='t'>BALCÃO/REVENDA</option>
								</select>
							</div>
						</div>
					</div>

					<div class='span2'></div>
			</div>
		<?	} ?>
		<input type='hidden' id='btn_click' name='btn_acao' value=''><br/>
	<div class="row-fluid">
            <!-- margem -->
            <div class="span4"></div>

            <div class="span4">
                <div class="control-group">
                    <div class="controls controls-row tac">
                        <button type="button" class="btn" value="Gravar" alt="Gravar formulário" onclick="submitForm($(this).parents('form'),'ok');" > Filtrar</button>
                    </div>
                </div>
            </div>

            <!-- margem -->
            <div class="span4"></div>
        </div>
</form>

</div>
<?

}

if ( (count($msg_erro) == 0) && ((strlen ($posto_codigo) > 0) OR (strlen($posto_nome) > 0) OR (strlen($posto_cidade) > 0) OR (strlen($posto_estado) > 0) OR (strlen($linha) > 0) OR (count($linha) > 0) OR (strlen($credenciamento) > 0) OR (strlen($tipo_posto) > 0) OR (strlen($codigo_posto_codigo) > 0 ) OR (strlen($data_inicial) > 0 AND strlen($data_final) > 0)) ) {

	$count = pg_num_rows($res_consulta);
	/* if (pg_numrows ($res_consulta) == 1) {
		$posto          = trim(pg_result($res_consulta,0,posto));
		?>
		<script>
			window.location="posto_consulta_detalhe.php?posto=<? echo $posto; ?>"
		</script>
		<?
	} */

	if (pg_numrows ($res_consulta) > 0) {?>
		<table width='850px' id="relatorio_listagem" name="relatorio_listagem" class='table table-striped table-bordered table-hover table-large'>
			<thead>
				<tr class = 'titulo_coluna'>
					<th align='center' width='50%' nowrap>Código</th>
					<th align='center' width='50%' nowrap>Nome Fantasia</th>
					<th align='center' width='50%' nowrap>Razão Social</th><?
					if (in_array($login_fabrica, array(11,80,169,170,172))) echo "<th alin='center'norwap>Linha</th>";?>
					<th align='center' width='15%' nowrap>Endereço</th>
					<th align='center' width='15%' nowrap>Número</th>
					<th align='center' width='15%' nowrap>Complemento</th>
					<th align='center' width='15%' nowrap>Bairro</th>
					<th align='center' width='15%' nowrap>Cidade</th>
					<th align='center' width='5%' nowrap>UF</th>
					<th align='center' width='5%' nowrap>Cep</th>
					<th align='center' width='50%' nowrap>Fone</th>
					<?php if($login_fabrica == 151){ ?>
						<th align='center' width='50%' nowrap>Fone 2</th>
						<th align='center' width='50%' nowrap>Fone 3</th>
						<th align='center' width='50%' nowrap>Celular</th>
						<th align='center' width='50%' nowrap>Fax</th>
					<?php } ?>
					<?php if($login_fabrica == 35){ ?>
						<th align='center' width='50%' nowrap>Celular Contato</th>
						<th align='center' width='50%' nowrap>Fax</th>
					<?php } ?>
					<th align='center' width='20%' nowrap>CNPJ</th>
					<th align='center' width='20%' nowrap>Inscrição Estadual</th>
					<th align='center' width='50%' nowrap>Nome para Contato</th>
					<th align='center' width='50%' nowrap>E-mail</th>
					<?php
					if (in_array($login_fabrica, [35]) && $detalhado == 't') { ?>
						<th class="tac">Representante Legal</th>
					<?php
					}

						if($login_fabrica==45) echo "<th align='center' width='50%' nowrap>Linhas</th>";
						if($login_fabrica==151) echo "<th align='center' width='50%' nowrap>Valor Mão Obra</t     h>";

					?>
					<th align='center' width='10%' nowrap>Status	</th>

					<?php if (in_array($login_fabrica, [151])) { ?>
						<th align='center'>Último Credenciamento</th>
						<th align='center'>Último Descredenciamento</th>
						<th align='center'>Último Em Credenciamento</th>
						<th align='center'>Último Em Descredenciamento</th>
					<?php } ?>
					<?php
					if (in_array($login_fabrica,array(169,170))){

					?>
						<th align='center' width='10%' nowrap>Valor KM</th>


					<?php
					}
					?>
					<th align='center' width='10%' nowrap>Tipo Posto</th>
					<?= (in_array($login_fabrica, [151])) ? "<th align='center' nowrap>Divulgação</th>" : "" ?>
					<?php if($login_fabrica ==35 AND $detalhado == 't'){?>
						<th align='center' width='10%' nowrap>Região Suframa</th>
						<th align='center' width='10%' nowrap>Capital/Interior</th>
						<th align='center' width='10%' nowrap>Transportadora</th>
						<th align='center' width='10%' nowrap>Desconto</th>
						<th align='center' width='10%' nowrap>Valor KM</th>
						<th align='center' width='10%' nowrap>Item Aparência</th>
						<th align='center' width='10%' nowrap>Divulga Consumidor</th>
						<th align='center' width='10%' nowrap>Observação Interna</th>
						<th align='center' width='10%' nowrap>Observação (Cadence)</th>
						<th align='center' width='10%' nowrap>Observação (Oster)</th>
						<th align='center' width='10%' nowrap>Informações de Cobrança</th>
						<th align='center' width='10%' nowrap>Banco</th>
						<th align='center' width='10%' nowrap>Agência</th>
						<th align='center' width='10%' nowrap>Conta</th>
						<th align='center' width='10%' nowrap>Tipo de Conta</th>
						<th align='center' width='10%' nowrap>Nome Favorecido</th>
						<th align='center' width='10%' nowrap>Observação Conta</th>
				
						<?php 

						foreach($nome_dados_linha_arr as $valores){
							echo "<th align='center' width='10%' nowrap>$valores</th>";
						}

						echo "<th align='center' width='10%' nowrap>Pedido Faturado</th>";
						echo "<th align='center' width='10%' nowrap>Pedido em Garantia</th>";
						echo "<th align='center' width='10%' nowrap>Digita OS</th>";

						?>
					<?php } ?>


					<?if($login_fabrica==151) echo "<th align='center' width='50%' nowrap>Linha</th>";?>
					

					<?php if($login_fabrica == 86){ ?>
						<?php if($status == "CREDENCIADO"){?>
							<th align='center' width='10%' nowrap>Data Inclusão</th>
						<?php }	?>

						<?php if($status == "DESCREDENCIADO"){ ?>
							<th align='center' width='10%' nowrap>Data Descredenciamento</th>
						<?php }	?>

						<?php if($status == "EM DESCREDENCIAMENTO"){ ?>
							<th align='center' width='10%' nowrap>Dias/Desc</th>
							<th align='center' width='10%' nowrap>Data Inclusão</th>
							<th align='center' width='10%' nowrap>Data Em Descredenciamento</th>
							<th align='center' width='10%' nowrap>Observação</th>
						<?php }	?>						
					<?php }	?>		

					<?
					if($login_fabrica == 35 OR $login_fabrica == 91) 
						echo "<th align='center' width='50%' nowrap>Data Status</th>";
						
						if ($login_fabrica == 35) {
							echo "<th align='center' width='50%' nowrap>Isento Anexo NF da OS</th>";
						}
					if ($gerar_xls != "t" && !in_array($login_fabrica, [167, 203])) {
						echo "<th align='center' width='5%'>Ações</th>";
					}?>
				</tr>
			</thead><tbody>
		<?
		$dados_posto_linha_arr = array();
		$limpa_obs = "/\r\n|\n|\r/";

		for ($i = 0 ; $i < $count ; $i++) {

			$posto                 = trim(pg_result($res_consulta, $i, "posto"));
			$cnpj                  = trim(pg_result($res_consulta, $i, "cnpj"));
			$codigo_posto          = trim(pg_result($res_consulta, $i, "codigo_posto"));
			$ie                    = trim(pg_result($res_consulta, $i, "ie"));
			$fone                  = trim(pg_result($res_consulta, $i, "fone"));
			$telefones             = trim(pg_result($res_consulta, $i, "telefones"));	
			$nome_fantasia         = trim(pg_result($res_consulta, $i, "nome_fantasia"));
			$nome                  = trim(pg_result($res_consulta, $i, "nome"));
			$endereco              = trim(pg_result($res_consulta, $i, "endereco"));
			$numero                = trim(pg_result($res_consulta, $i, "numero"));
			$complemento           = trim(pg_result($res_consulta, $i, "complemento"));
			$cep                   = trim(pg_result($res_consulta, $i, "cep"));
			$cidade                = ucwords(trim(pg_result($res_consulta, $i, "cidade")));
			$estado                = trim(pg_result($res_consulta, $i, "estado"));
			$credenciamento        = trim(pg_result($res_consulta, $i, "credenciamento"));
			$bairro                = trim(pg_result($res_consulta, $i, "bairro"));
			$digita_os             = trim(pg_result($res_consulta, $i, "digita_os"));
			$contato               = trim(pg_result($res_consulta, $i, "contato"));
			$email                 = trim(pg_result($res_consulta, $i, "contato_email"));
			$data_status           = trim(pg_result($res_consulta, $i, "data_status"));
			$parametros_adicionais = trim(pg_result($res_consulta, $i, "parametros_adicionais"));
			$linha_descricao       = trim(pg_result($res_consulta, $i, "linha_descricao"));
			$parametros_adicionais = json_decode($parametros_adicionais,true);
			$contato_fax					= trim(pg_result($res_consulta,$i,contato_fax));
			$valor_km 	       = number_format(trim(pg_result($res_consulta,$i,valor_km)), 2, ',', ' ');
			if($login_fabrica == 151){
				$contato_cel 					= trim(pg_result($res_consulta,$i,contato_cel));
				$contato_fax					= trim(pg_result($res_consulta,$i,contato_fax));
			}
			
			if($login_fabrica == 35){
				$obs_cadence 					=  preg_replace($limpa_obs, "", utf8_decode($parametros_adicionais['obs_cadence']));
				$obs_oster 						= preg_replace($limpa_obs, "", utf8_decode($parametros_adicionais['obs_oster']));


				$obs_oster 		= str_replace($limpa_obs, "", $parametros_adicionais['obs_oster']);
				$anexar_nf_os	= str_replace($limpa_obs, "", $parametros_adicionais['anexar_nf_os']);

				$desconto 						= trim(pg_result($res_consulta,$i,desconto));
				$contato_cel 					= trim(pg_result($res_consulta,$i,contato_cel));
				

				$suframa 						= trim(pg_result($res_consulta,$i,suframa));
				$capital_interior 			    = trim(pg_result($res_consulta,$i,capital_interior));

				$nome_transportadora 			    = trim(pg_result($res_consulta,$i,nome_transportadora));
				$codigo_interno_trans 			    = trim(pg_result($res_consulta,$i,codigo_interno_trans));
				
				$valor_km 						= number_format(trim(pg_result($res_consulta,$i,valor_km)), 2, ',', ' ');
				$item_aparencia 				= (trim(pg_result($res_consulta,$i,item_aparencia)) == 't')? "Sim" : "Não" ;
				$divulgar_consumidor 			= (trim(pg_result($res_consulta,$i,divulgar_consumidor)) == 't')? "Sim" : "Não";
				$observacao_interna 			= trim(pg_result($res_consulta,$i,observacao_interna));
				$cobranca_endereco 				= trim(pg_result($res_consulta,$i,cobranca_endereco));
				$cobranca_numero 				= trim(pg_result($res_consulta,$i,cobranca_numero));
				$cobranca_complemento 			= trim(pg_result($res_consulta,$i,cobranca_complemento));
				$cobranca_bairro 				= trim(pg_result($res_consulta,$i,cobranca_bairro));
				$cobranca_cep 					= trim(pg_result($res_consulta,$i,cobranca_cep));
				$cobranca_cidade 				= trim(pg_result($res_consulta,$i,cobranca_cidade));
				$cobranca_estado 				= trim(pg_result($res_consulta,$i,cobranca_estado));

				$pedido_faturado 				= trim(pg_result($res_consulta,$i,pedido_faturado));
				$pedido_em_garantia 			= trim(pg_result($res_consulta,$i,pedido_em_garantia));
				$digita_os 						= trim(pg_result($res_consulta,$i,digita_os));

				$nomebanco 						= trim(pg_result($res_consulta,$i,nomebanco));
				$favorecido_conta 				= trim(pg_result($res_consulta,$i,favorecido_conta));
				$cpf_conta 						= trim(pg_result($res_consulta,$i,cpf_conta));
				$tipo_conta 					= trim(pg_result($res_consulta,$i,tipo_conta));
				$conta 							= trim(pg_result($res_consulta,$i,conta));
				$agencia 						= trim(pg_result($res_consulta,$i,agencia));
				$banco 							= trim(pg_result($res_consulta,$i,banco));
				$obs_conta 						= trim(pg_result($res_consulta,$i,obs_conta));

				$representante_legal            = trim(pg_fetch_result($res_consulta, $i, 'representante_legal'));


				$sql_dados_posto_linha = "SELECT tbl_posto_linha.posto, tbl_posto_linha.linha
											from tbl_posto_linha
											inner join tbl_linha on tbl_linha.linha = tbl_posto_linha.linha
											where tbl_linha.fabrica = $login_fabrica 
											and tbl_posto_linha.posto = $posto";
				$res_dados_posto_linha = pg_query($con, $sql_dados_posto_linha);
				for($pl=0; $pl<pg_num_rows($res_dados_posto_linha); $pl++){
					$linha 							= pg_fetch_result($res_dados_posto_linha, $pl, linha);
					$dados_posto_linha_arr[$posto][] = $linha;
				}
			}

			if (in_array($login_fabrica, [151])) {
				$data_credenciado = pg_fetch_result($res_consulta, $i, "data_credenciado");
				$data_descredenciado = pg_fetch_result($res_consulta, $i, "data_descredenciado");
				$data_credenciamento = pg_fetch_result($res_consulta, $i, "data_credenciamento");
				$data_descredenciamento = pg_fetch_result($res_consulta, $i, "data_descredenciamento");

				$divulgar_consumidor = (pg_fetch_result($res_consulta, $i, "divulgar_consumidor") == 't') ? "Sim" : "Não";
			}

            if ($login_fabrica == 117) {
                    //$posto_ant = 0; 
                    if ($posto_ant == $posto) {
                            continue;
                    }
                    $posto_ant = $posto;
            }

			if(in_array($login_fabrica, array(74))){
				$fone = str_replace(array("{","}"), array("",""), $fone);

				$fone = explode(",", $fone);
				$idxRemover = array();
				foreach ($fone as $idx => $value) {
					if($value == '""'){
						$idxRemover[] = $idx;
					}
				}				
				foreach ($idxRemover as $value) {
					unset($fone[$value]);
				}

				$fone = implode(", ", $fone);
				$fone = str_replace('"', "", $fone);

				if($fone == "NULL"){
					$fone = "";
				}
			}

      		if($login_fabrica == 86 AND $_POST['credenciamento'] == "EM DESCREDENCIAMENTO"){ 
				$dias_p_descredenciar 		= trim(pg_result($res_consulta,$i,dias));
				$obs_tbl_credenciamento		= trim(pg_result($res_consulta,$i,obs_tbl_credenciamento));

				if(!empty($data_status)){
					list($dnf, $mnf, $ynf) = explode("/", $data_status);
					$data = $ynf."-".$mnf."-".$dnf;

					$date = date_create($data);
					date_add($date, date_interval_create_from_date_string($dias_p_descredenciar.' days'));
					$data_descredenciamento = date_format($date, 'Y-m-d');

					list($ynf, $mnf, $dnf) = explode("-", $data_descredenciamento);

					$data2 = $dnf."/".$mnf."/".$ynf;
				}
				if(empty($data_status)){
					$data2 = "";
				}
			}


			$tipo_posto_descricao   = trim(pg_result($res_consulta,$i,'tipo_posto'));
			$linha    				= trim(pg_result($res_consulta,$i,'linha_descricao'));

			if($login_fabrica==45){// HD 19498 13/5/2008
				$sql_linha = "SELECT DISTINCT nome AS nome_linha
									from tbl_linha
							JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha
							WHERE tbl_linha.fabrica = $login_fabrica
							AND   tbl_posto_linha.posto = $posto";
				$resl = pg_exec($con, $sql_linha);

				if(pg_numrows($resl)>0){
					$linhas = "";
					for($z=0; $z<pg_numrows($resl);$z++){
						$nome_linha = pg_result($resl, $z, nome_linha);
						$linhas .= $nome_linha.",";
						$xlinhas = substr($linhas,0, -1);
					}
				}
			}


			//HD 12220 18/1/2008
			if ($credenciamento == 'CREDENCIADO'){
				$xcredenciamento = 'C';
				$cor_fundo = '#D9E2EF';
				$cor_texto = '#000000';
				#HD 110541
				if($digita_os <> "t" && in_array($login_fabrica, array(11,172)) ){
					$xcredenciamento .= '/B';
				}
			}else if ($credenciamento == 'DESCREDENCIADO') {
				$xcredenciamento = 'D';
				$cor_fundo = '#BC053D';
				$cor_texto = '#FFFFFF';
			}else if ($credenciamento == 'EM CREDENCIAMENTO'){
				$xcredenciamento = 'EC';
				$cor_fundo = '#F27900';
				$cor_texto = '#FFFFFF';
			}else if ($credenciamento == 'EM DESCREDENCIAMENTO'){
				$xcredenciamento = 'ED';
				$cor_fundo = '#F27900';
				$cor_texto = '#FFFFFF';
			}

			if($i%2==0) $cor="#F7F5F0"; else $cor="#F1F4FA";?>
			<tr>

			<td align='left' nowrap><?=$codigo_posto?></td>
			<td align='left' nowrap><?=$nome_fantasia?></td>
			<td align='left' nowrap><?=$nome?></td>
			<?php if(in_array($login_fabrica, array(11,172))){ echo "<td align='left'> {$linha_descricao} </td>"; } ?>
			<?
			if (in_array($login_fabrica,array(80,169,170))){

				$sqlqtde = "SELECT count(*) from tbl_linha where fabrica = $login_fabrica and ativo is true";
				$resqtde = pg_exec($sqlqtde);

				$num_linha = pg_result($resqtde,0,0);

				$sql_linha = "SELECT DISTINCT nome AS nome_linha, tbl_linha.linha,tbl_linha.codigo_linha
							FROM tbl_linha
						JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_linha.linha
						WHERE    tbl_linha.fabrica = $login_fabrica
							AND  tbl_posto_linha.posto = $posto
							AND  tbl_linha.ativo is true
						ORDER BY nome_linha;";
				$res_linha = pg_exec ($con,$sql_linha);

				unset($linha_nome);
				unset($linha_escreve);
				if (pg_numrows($res_linha)>0){
					$linha_cont = pg_numrows($res_linha);
					$linha_nome = "";
					$linha_escreve = "";
					for ($x=0; $x<pg_numrows($res_linha);$x++){
						if (in_array($login_fabrica,array(169,170))){
							$linha_nome[] = pg_result($res_linha, $x, 'codigo_linha') . " - " .pg_result($res_linha, $x, 'nome_linha');
						} else {
							$linha_nome[] = pg_result($res_linha, $x, 'nome_linha');
						}
					}
					if ($num_linha == $linha_cont) {
						$linha_escreve = 'Todas as Linhas';
					} else {
						if (in_array($login_fabrica,array(169,170))){
							$linha_escreve = implode($linha_nome,' <br /> ');
						} else {
							$linha_escreve = implode($linha_nome,'/');
						}
					}
				}
				echo "<td class=table_line align='left' nowrap>$linha_escreve</td>";
			}
			if ($login_fabrica == 151) {
	            $chars_replace = array('{','}','"');
	            $contato_telefones = str_replace($chars_replace, "", $telefones);

	            $fones = array();
	            $fones = explode(',', $contato_telefones);

	            if(strlen($fone)==0 and strlen($fones[0])>0 ){
	                $fone  = $fones[0];
	            }
	            $fone2 = $fones[1];
	            $fone3 = $fones[2];
			}

			echo "<td align='left' nowrap>$endereco</td>";
			echo "<td align='left' nowrap>$numero</td>";
			echo "<td align='left' nowrap>$complemento</td>";

				echo "<td align='left' nowrap>$bairro</td>";

			echo "<td align='left' nowrap>$cidade</a></td>";
			echo "<td align='left'>$estado</td>";
			echo "<td align='left'>$cep</td>";
			echo "<td class=table_line align='left' nowrap>$fone</td>";
			if($login_fabrica == 151){
				echo "<td class=table_line align='left' nowrap>$fone2</td>";
				echo "<td class=table_line align='left' nowrap>$fone3</td>";
				echo "<td class=table_line align='left' nowrap>$contato_cel</td>";
				echo "<td class=table_line align='left' nowrap>$contato_fax</td>";
			}
			if($login_fabrica == 35){
				echo "<td class=table_line align='left' nowrap>$contato_cel</td>";
				echo "<td class=table_line align='left' nowrap>$contato_fax</td>";
			}
			echo "<td align='left'>$cnpj</td>";
			echo "<td align='left'>$ie</td>";
			echo "<td align='left' nowrap>$contato</td>";
			echo "<td align='left' nowrap>$email</td>";		

			if (in_array($login_fabrica, [35]) && $detalhado == 't') {
				echo "<td>{$representante_legal}</td>";
			}	
			
			if($login_fabrica==151)echo "<td class='table_line tar'>".number_format($parametros_adicionais['valor_mao_obra'],2,',','.')."</td>";
			if($login_fabrica==45)echo "<td class=table_line align='left'>$xlinhas</td>";
			echo "<td align='CENTER' style='background-color:$cor_fundo; color:$cor_texto;'>$credenciamento</td>";

			if (in_array($login_fabrica, [151])) {
				echo "<td style='text-align:center;vertical-align:middle'>$data_credenciado</td>";
				echo "<td style='text-align:center;vertical-align:middle'>$data_descredenciado</td>";
				echo "<td style='text-align:center;vertical-align:middle'>$data_credenciamento</td>";
				echo "<td style='text-align:center;vertical-align:middle'>$data_descredenciamento</td>";
			}


			if (in_array($login_fabrica, [169,170])) {
				echo "<td style='text-align;center;vertical-align:middle'>$valor_km</td>";
			}

			echo "<td class=table_line align='center'><center>$tipo_posto_descricao</center></td>";

			if (in_array($login_fabrica, [151])) {
				echo "<td style='text-align;center;vertical-align:middle'>$divulgar_consumidor</td>";
			}

			if($login_fabrica == 35 and $detalhado == 't'){
				echo "<td align='left' class='tac' nowrap>";
					echo ($suframa == 'f')? "Não" : "Sim";
				echo "</td>";
				echo "<td align='left' class='tac' nowrap>$capital_interior</td>";

				$nome_codigo_transportadora = ( (!empty($codigo_interno_trans)) AND (!empty($nome_transportadora)))? "$codigo_interno_trans - $nome_transportadora" : "$nome_transportadora";

				echo "<td align='left' class='tac' nowrap>$nome_codigo_transportadora</td>";
				echo "<td align='left' class='tac' nowrap>$desconto</td>";
				echo "<td align='left' class='tac' nowrap>R$ $valor_km</td>";
				echo "<td align='left' class='tac' nowrap>$item_aparencia</td>";
				echo "<td align='left' class='tac' nowrap>$divulgar_consumidor</td>";
				echo "<td align='left' class='tac' nowrap>$observacao_interna</td>";
				echo "<td align='left' class='tac' nowrap>$obs_cadence</td>";
				echo "<td align='left' class='tac' nowrap>$obs_oster</td>";
				echo "<td align='left' class='tac' nowrap>$cobranca_cep $cobranca_endereco $cobranca_numero $cobranca_bairro  $cobranca_cidade $cobranca_estado</td>";

				echo "<td align='left' class='tac' nowrap>$banco - $nomebanco</td>";
				echo "<td align='left' class='tac' nowrap>$agencia</td>";
				echo "<td align='left' class='tac' nowrap>$conta</td>";
				echo "<td align='left' class='tac' nowrap>$tipo_conta</td>";
				echo "<td align='left' class='tac' nowrap>$cpf_conta - $favorecido_conta</td>";
				echo "<td align='left' class='tac' nowrap>$obs_conta</td>";




				foreach(array_keys($nome_dados_linha_arr) as $arr_posto){

					if(in_array($arr_posto, $dados_posto_linha_arr[$posto])){
						echo "<td align='left' class='tac' nowrap>X</td>";
					}else{
						echo "<td align='left' class='tac' nowrap></td>";
					}
				}
				if($pedido_faturado == 't'){
					echo "<td align='left' class='tac' nowrap>X</td>";
				}else{
					echo "<td align='left' class='tac' nowrap></td>";
				}

				if($pedido_em_garantia == 't'){
					echo "<td align='left' class='tac' nowrap>X</td>";
				}else{
					echo "<td align='left' class='tac' nowrap></td>";
				}

				if($digita_os == 't'){
					echo "<td align='left' class='tac' nowrap>X</td>";
				}else{
					echo "<td align='left' class='tac' nowrap></td>";
				}
				
			}
			if (!in_array($login_fabrica, array(151,167,203))) {
				echo "<td align='left' class='tac' nowrap>$data_status</td>";
			}

			if(in_array($login_fabrica,array(151))) echo "<td class=table_line align='left'>$linha</td>";

			if($login_fabrica == 86){

				if($status == "CREDENCIADO"){
					echo "<td align='left' nowrap>$data_status</td>";
				}
				if($status == "DESCREDENCIADO"){
					echo "<td align='left' nowrap>$data_status</td>";
				}
				if($status == "EM DESCREDENCIAMENTO"){
					echo "<td align='left' nowrap>$dias_p_descredenciar</td>";
					echo "<td align='left' nowrap>$data_status</td>";
					echo "<td align='left' nowrap>$data2</td>";
					echo "<td align='left' nowrap>$obs_tbl_credenciamento</td>";
				}				
			}	

			if ($login_fabrica == 35) {
				if ($anexar_nf_os == "nao") {
					$aux_isento = "X";
				}
				echo "<td nowrap><center>$aux_isento</center></td>";
			}
			
			if ($gerar_xls != "t" && !in_array($login_fabrica, [167, 203])) {
			//echo "<TD><a href = 'posto_consulta_detalhe.php?posto=$posto'><img src='imagens/btn_consultar_azul.gif'></a></TD>";
			echo "<td><a href = 'posto_consulta_detalhe.php?posto=$posto' target='_blank'><button type='button' class='btn-mini btn-success'>Consultar</button></a></td>";
			}
			echo "</tr>";
		}

		echo "</tbody></table>";

		echo "<br><br>";
		echo "<table class='table table-bordered' width='100%' cellpadding='1' cellspacing='1'>";
		echo "<tr class='titulo_tabela'><td colspan='8' class='titulo_tabela'>LEGENDA STATUS</td></tr>";
		echo "<tr>";
			echo "<td width='5%' bgcolor = '#FFFFFF'>C</td><td align='left' width='20%' >Credenciado</td>";
			echo "<td bgcolor='#BC053D' width='5%'><font color='#FFFFFF'>D</font></td><td align='left' width='20%' >Descredenciado</td>";
			echo "<td bgcolor='#F27900' width='5%'><font color='#FFFFFF'>EC</font></td><td align='left' width='20%' >Em Credenciamento</td>";
			echo "<td bgcolor='#F27900' width='5%'><font color='#FFFFFF'>ED</font></td><td align='left' width='20%' >Em Descredenciamento</td>";
		echo "</tr>";
		echo "</table>";

		echo "<br><br>";

		if ($count > 50) {
		?>
			<script>
				$.dataTableLoad({ table: "#relatorio_listagem" });
			</script>
		<?php

		}

			$jsonPOST = excelPostToJson($_POST);

			?>

			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='../admin/imagens/excel.png' /></span>
				<span class="txt">Gerar Arquivo Excel</span>
			</div>

		<?
		}else{
					echo '
					<div class="container">
					<div class="alert">
						    <h4>Nenhum resultado encontrado</h4>
					</div>
					</div>';
		}
}
?>
<script language='javascript' src='address_components.js'></script>
<? if ($gerar_xls == "t" && strlen($msg_erro) == 0) {
        //Redireciona a saida da tela, que estava em buffer, para a variÃÂ¡vel
        $hora = time();
        $xls = "xls/posto_consulta_".$login_admin."_data_".$hora.".xls";

        $saida = ob_get_clean();

        $arquivo = fopen($xls, "w");
        fwrite($arquivo, $saida);
        fclose($arquivo);

        header("location:$xls");
		die;
}
else {
	include "../admin/rodape.php";
}
?>
