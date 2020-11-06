<?php

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';
include '../../helpdesk/mlg_funciones.php';
include '../../classes/cep.php';

$login_fabrica = 152;
$admin         = 7374;


/**
 * Area para colocar os AJAX
 */
if (isset($_POST["ajax_busca_cidade"]) && !empty($_POST["estado"])) {
	$estado = strtoupper($_POST["estado"]);

	if (array_key_exists($estado, $array_estados())) {
		$sql = "SELECT DISTINCT * FROM (
					SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$estado}')
					UNION (
						SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$estado}')
					)
				) AS cidade
				ORDER BY cidade ASC";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$array_cidades = array();

			while ($result = pg_fetch_object($res)) {
				$array_cidades[] = $result->cidade;
			}

			$retorno = array("cidades" => $array_cidades);
		} else {
			$retorno = array("error" => utf8_encode("nenhuma cidade encontrada para o estado: {$estado}"));
		}
	} else {
		$retorno = array("error" => utf8_encode("estado não encontrado"));
	}

	exit(json_encode($retorno));
}

if (isset($_POST['ajax_busca_cep']) && !empty($_POST['cep'])) {
	require_once '../../classes/cep.php';
	$cep = $_POST['cep'];

	try {
		$retorno = CEP::consulta($cep);
		$retorno = array_map('utf8_encode', $retorno);
	} catch(Exception $e) {
		$retorno = array("error" => utf8_encode($e->getMessage()));
	}

	exit(json_encode($retorno));
}

$array_pais = array(
	"Brazil"                        => "Brazil",
	"Albania"                       => "Albania",
	"Algeria"                       => "Algeria",
	"Andorra"                       => "Andorra",
	"Angola"                        => "Angola",
	"Antigua and Barbuda"           => "Antigua and Barbuda",
	"Argentina"                     => "Argentina",
	"Armenia"                       => "Armenia",
	"Australia"                     => "Australia",
	"Austria"                       => "Austria",
	"Azerbaijan"                    => "Azerbaijan",
	"Bahamas"                       => "Bahamas",
	"Bahrain"                       => "Bahrain",
	"Bangladesh"                    => "Bangladesh",
	"Barbados"                      => "Barbados",
	"Belarus"                       => "Belarus",
	"Belgium"                       => "Belgium",
	"Belize"                        => "Belize",
	"Benin"                         => "Benin",
	"Bhutan"                        => "Bhutan",
	"Bolivia"                       => "Bolivia",
	"Bosnia and Herzegivona"        => "Bosnia and Herzegivona",
	"Botswana"                      => "Botswana",
	"Brazil"                        => "Brazil",
	"Brunei Darussalam"             => "Brunei Darussalam",
	"Bulgaria"                      => "Bulgaria",
	"Burkina Faso"                  => "Burkina Faso",
	"Burundi"                       => "Burundi",
	"Cambodia"                      => "Cambodia",
	"Cameroon"                      => "Cameroon",
	"Canada"                        => "Canada",
	"Canary Islands (Spain)"        => "Canary Islands (Spain)",
	"Cape Verde"                    => "Cape Verde",
	"Central African Republic"      => "Central African Republic",
	"Ceuta (Spain)"                 => "Ceuta (Spain)",
	"Chad"                          => "Chad",
	"Chile"                         => "Chile",
	"China, People's Republic of"   => "China, People's Republic of",
	"Colombia"                      => "Colombia",
	"Comoros"                       => "Comoros",
	"Congo, Democratic Rep. of the" => "Congo, Democratic Rep. of the",
	"Congo, Republic of the"        => "Congo, Republic of the",
	"Costa Rica"                    => "Costa Rica",
	"Cote d'Ivoire"                 => "Cote d'Ivoire",
	"Croatia"                       => "Croatia",
	"Cuba"                          => "Cuba",
	"Cyprus"                        => "Cyprus",
	"Czech Republic"                => "Czech Republic",
	"Denmark"                       => "Denmark",
	"Deunion"                       => "Deunion",
	"Djibouti"                      => "Djibouti",
	"Dominica"                      => "Dominica",
	"Dominican Republic"            => "Dominican Republic",
	"Dubai"                         => "Dubai",
	"Ecuador"                       => "Ecuador",
	"Egypt"                         => "Egypt",
	"El Salvador"                   => "El Salvador",
	"Equatorial Guinea"             => "Equatorial Guinea",
	"Eritrea"                       => "Eritrea",
	"Estonia"                       => "Estonia",
	"Ethiopia"                      => "Ethiopia",
	"Fiji"                          => "Fiji",
	"Finland"                       => "Finland",
	"France"                        => "France",
	"French Polynesia"              => "French Polynesia",
	"Gabon"                         => "Gabon",
	"Gambia"                        => "Gambia",
	"Georgia"                       => "Georgia",
	"Germany"                       => "Germany",
	"Ghana"                         => "Ghana",
	"Greece"                        => "Greece",
	"Grenada"                       => "Grenada",
	"Guadeloupe"                    => "Guadeloupe",
	"Guatemala"                     => "Guatemala",
	"Guinea"                        => "Guinea",
	"Guinea-Bissau"                 => "Guinea-Bissau",
	"Guyana"                        => "Guyana",
	"Haiti"                         => "Haiti",
	"Honduras"                      => "Honduras",
	"Hungary"                       => "Hungary",
	"Iceland"                       => "Iceland",
	"India"                         => "India",
	"Indonesia"                     => "Indonesia",
	"Iran"                          => "Iran",
	"Iraq"                          => "Iraq",
	"Ireland"                       => "Ireland",
	"Israel"                        => "Israel",
	"Italy"                         => "Italy",
	"Jamaica"                       => "Jamaica",
	"Japan"                         => "Japan",
	"Jordan"                        => "Jordan",
	"Kazakhstan"                    => "Kazakhstan",
	"Kenya"                         => "Kenya",
	"Korea, Republic of"            => "Korea, Republic of",
	"Kuwait"                        => "Kuwait",
	"Kyrgystan"                     => "Kyrgystan",
	"Latvia"                        => "Latvia",
	"Lebanon"                       => "Lebanon",
	"Lesotho"                       => "Lesotho",
	"Liberia"                       => "Liberia",
	"Libya"                         => "Libya",
	"Lithuania"                     => "Lithuania",
	"Macedonia"                     => "Macedonia",
	"Madagascar"                    => "Madagascar",
	"Madeira (Portugal)"            => "Madeira (Portugal)",
	"Malawi"                        => "Malawi",
	"Malaysia"                      => "Malaysia",
	"Maldives"                      => "Maldives",
	"Mali"                          => "Mali",
	"Malta"                         => "Malta",
	"Marshall Islands"              => "Marshall Islands",
	"Martinique"                    => "Martinique",
	"Mauritania"                    => "Mauritania",
	"Mauritius"                     => "Mauritius",
	"Mayotte (France)"              => "Mayotte (France)",
	"Melilla (Spain)"               => "Melilla (Spain)",
	"Mexico"                        => "Mexico",
	"Micronesia, Fed. States of"    => "Micronesia, Fed. States of",
	"Middle East"                   => "Middle East",
	"Moldova"                       => "Moldova",
	"Monaco"                        => "Monaco",
	"Mongolia"                      => "Mongolia",
	"Montenegro"                    => "Montenegro",
	"Morocco"                       => "Morocco",
	"Mozambique"                    => "Mozambique",
	"Myanmar"                       => "Myanmar",
	"Namibia"                       => "Namibia",
	"Nepal"                         => "Nepal",
	"Netherlands"                   => "Netherlands",
	"New Caledonia"                 => "New Caledonia",
	"New Zealand"                   => "New Zealand",
	"Nicaragua"                     => "Nicaragua",
	"Niger"                         => "Niger",
	"Nigeria"                       => "Nigeria",
	"Norway"                        => "Norway",
	"Oman"                          => "Oman",
	"Pakistan"                      => "Pakistan",
	"Palau"                         => "Palau",
	"Panama"                        => "Panama",
	"Papua New Guinea"              => "Papua New Guinea",
	"Paraguay"                      => "Paraguay",
	"Peru"                          => "Peru",
	"Philippines"                   => "Philippines",
	"Poland"                        => "Poland",
	"Portugal"                      => "Portugal",
	"Qatar"                         => "Qatar",
	"Reunion (France)"              => "Reunion (France)",
	"Romania"                       => "Romania",
	"Russia"               			=> "Russia",
	"Rwanda"                        => "Rwanda",
	"Samoa"                         => "Samoa",
	"Sao Tome and Principe"         => "Sao Tome and Principe",
	"Saudi Arabia"                  => "Saudi Arabia",
	"Senegal"                       => "Senegal",
	"Serbia"                        => "Serbia",
	"Seychelles"                    => "Seychelles",
	"Sierra Leone"                  => "Sierra Leone",
	"Singapore"                     => "Singapore",
	"Slovakia"                      => "Slovakia",
	"Slovenia"                      => "Slovenia",
	"Solomon Islands"               => "Solomon Islands",
	"Somalia"                       => "Somalia",
	"South Africa"                  => "South Africa",
	"Spain"                         => "Spain",
	"Sri Lanka"                     => "Sri Lanka",
	"St Helena (UK)"                => "St Helena (UK)",
	"St Kitts and Nevis"            => "St Kitts and Nevis",
	"St Lucia"                      => "St Lucia",
	"St Vincent and the Grenadines" => "St Vincent and the Grenadines",
	"Sudan"                         => "Sudan",
	"Suriname"                      => "Suriname",
	"Swaziland"                     => "Swaziland",
	"Sweden"                        => "Sweden",
	"Switzerland"                   => "Switzerland",
	"Syria"                         => "Syria",
	"Tahiti"                        => "Tahiti",
	"Taiwan"                        => "Taiwan",
	"Tajikistan"                    => "Tajikistan",
	"Tanzania, United Republic of"  => "Tanzania, United Republic of",
	"Tchad"                         => "Tchad",
	"Thailand"                      => "Thailand",
	"Togo"                          => "Togo",
	"Trinidad and Tobago"           => "Trinidad and Tobago",
	"Tunisia"                       => "Tunisia",
	"Turkey"                        => "Turkey",
	"Turkmenistan"                  => "Turkmenistan",
	"Uganda"                        => "Uganda",
	"Ukraine"                       => "Ukraine",
	"United Arab Emirates"          => "United Arab Emirates",
	"United Kingdom"                => "United Kingdom",
	"United States of America"      => "United States of America",
	"Uruguay"                       => "Uruguay",
	"Uzbekistan"                    => "Uzbekistan",
	"Vanuatu"                       => "Vanuatu",
	"Venezuela"                     => "Venezuela",
	"Vietnam"                       => "Vietnam",
	"Western Sahara"                => "Western Sahara",
	"Yemen"                         => "Yemen",
	"Zambia"                        => "Zambia",
	"Zimbabwe"                      => "Zimbabwe"
);

$array_estado = array(
	'AC' => 'Acre',			
	'AL' => 'Alagoas',	
	'AM' => 'Amazonas',			
	'AP' => 'Amapá', 
	'BA' => 'Bahia',			
	'CE' => 'Ceara',		
	'DF' => 'Distrito Federal',	
	'ES' => 'Espírito Santo',
	'GO' => 'Goiás',			
	'MA' => 'Maranhão',	
	'MG' => 'Minas Gerais',		
	'MS' => 'Mato Grosso do Sul',
	'MT' => 'Mato Grosso',	
	'PA' => 'Pará',		
	'PB' => 'Paraíba',			
	'PE' => 'Pernambuco',
	'PI' => 'Piauí­',			
	'PR' => 'Paraná',	
	'RJ' => 'Rio de Janeiro',	
	'RN' => 'Rio Grande do Norte',
	'RO' => 'Rondônia',		
	'RR' => 'Roraima',	
	'RS' => 'Rio Grande do Sul', 
	'SC' => 'Santa Catarina',
	'SE' => 'Sergipe',		
	'SP' => 'São Paulo',	
	'TO' => 'Tocantins'
);

function validaCep() {
	global $_POST;

	$cep = $_POST["cep"];

	if (!empty($cep)) {
		try {
			$endereco = CEP::consulta($cep);

			if (!is_array($endereco)) {
				throw new Exception("CEP inválido");
			}
		} catch (Exception $e) {
			throw new Exception("CEP inválido");
		}
	}
}

function validaEstado() {
	global $array_estado, $_POST;

	$estado = strtoupper($_POST["estado"]);

	if (!empty($estado) && !in_array($estado, array_keys($array_estado))) {
		throw new Exception("Estado inválido");
	}
}

function validaCidade() {
	global $con, $_POST;

	$cidade = utf8_decode($_POST["cidade"]);
	$estado = strtoupper($_POST["estado"]);

	if (!empty($cidade) && !empty($estado)) {
		$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(estado) = '{$estado}' AND UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}'))";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			throw new Exception("Cidade não encontrada".$sql);
		}
	}
}

function validaEmail() {
	global $_POST;

	$email = $_POST["email"];

	if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
		throw new Exception("Email inválido");
	}
}

if ($_POST["ajax_enviar"]) {
	$regras = array(
		"nome",
		"email",
		"telefone",
		"pais",
		"contato",
		"mensagem"
	);

	/*
		NÃO SERÁ MAIS CAMPO OBRIGATÓRIO PRODUTO
	*/
	// if($_POST['contato'] == "centro_suporte"){
	// 	$regras[] = "produto";
	// }

	if($_POST['pais'] == "Brazil"){
		$regras[] = "estado";
		$regras[] = "cidade";
	}else{
		$regras[] = "estado_input";
		$regras[] = "cidade_input";
	}

	$msg_erro = array(
		"msg"    => array(),
		"campos" => array()
	);

	foreach ($regras as $campo) {
		$input = trim($_POST[$campo]);

		if (empty($input)) {
			$msg_erro["msg"]["obg"] = utf8_encode("Preencha todos os campos obrigatórios");
			$msg_erro["campos"][]   = $campo;
		}
	}

	if (count($msg_erro["msg"]) > 0) {
		$retorno = array("erro" => $msg_erro);
	} else {
		$contato            = utf8_decode($_POST["contato"]);
		$nome               = utf8_decode(trim($_POST["nome"]));
		$cargo              = utf8_decode(trim($_POST["cargo"]));
		$empresa            = utf8_decode(trim($_POST["empresa"]));
		$endereco           = utf8_decode(trim($_POST["endereco"]));
		$numero             = utf8_decode(trim($_POST["numero"]));
		$complemento        = utf8_decode(trim($_POST["complemento"]));
		$bairro             = utf8_decode(trim($_POST["bairro"]));
		$cidade             = utf8_decode($_POST["cidade"]);
		$estado             = $_POST["estado"];
		$cep                = trim($_POST["cep"]);
		$pais               = trim($_POST["pais"]);
		$telefone           = trim($_POST["telefone"]);
		$email              = trim($_POST["email"]);
		$receber_informacao = utf8_decode($_POST["receber_informacao"]);
		$mensagem           = utf8_decode(trim($_POST["mensagem"]));
		

		$contato_setor = array("venda" => array("descricao" => "Vendas", "email" => "vendas@esab.com.br"),
			"centro_suporte" => array("descricao" => "Centro de Suporte ao Cliente ESAB", "email" => "faleconosco@esab.com.br"),
			"certificado"    => array("descricao" => "Certificados de Consumiveis", 	  "email" => "alexson.santos@esab.com.br"),
			"revista"        => array("descricao" => "Revista Solução", 			      "email" => "marketing@esab.com.br"),
			"outro"          => array("descricao" => "Outros", 							  "email" => "faleconosco@esab.com.br"));

		if ($contato == "centro_suporte" || $contato == "outro") {
			$familia = utf8_decode(trim($_POST["familia"]));
			$produto = utf8_decode(trim($_POST["produto"]));

			if(!empty($familia)){
				$sql = "SELECT descricao FROM tbl_familia WHERE familia = $familia";
				$res = pg_query($con,$sql);

				$descricao_familia = pg_fetch_result($res, 0, "descricao");
			}

			if(!empty($produto)){
				$sql = "SELECT descricao FROM tbl_produto WHERE produto = $produto";
				$res = pg_query($con,$sql);

				$descricao_produto = pg_fetch_result($res, 0, "descricao");
			}

			try {
				pg_query($con, "BEGIN");

				$sql = "INSERT INTO tbl_hd_chamado (
							admin, 
							data, 
							atendente, 
							fabrica_responsavel, 
							fabrica, 
							titulo,
							status
						) VALUES (
							$admin,
							CURRENT_TIMESTAMP, 
							$admin, 
							{$login_fabrica}, 
							{$login_fabrica}, 
							'Atemdimento Fale Conosco',
							'Aberto'
						) RETURNING hd_chamado";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao abrir o atendimento");
				}

				$hd_chamado = pg_fetch_result($res, 0, "hd_chamado");

				if($pais != "Brazil"){
					$estado = "EX";
				}

				$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = '{$cidade}' AND UPPER(estado) = UPPER('{$estado}')";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$cidade_id = pg_fetch_result($res, 0, "cidade");
				} else {
					$sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = '{$cidade}' AND UPPER(estado) = UPPER('{$estado}')";
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

						if (strlen(pg_last_error()) > 0) {
							throw new Exception("Erro ao abrir o atendimento");
						}

						$cidade_id = pg_fetch_result($res, 0, "cidade");
					}else{
						$sql = "INSERT INTO tbl_cidade (
									nome, estado
								) VALUES (
									'{$cidade}', '{$estado}'
								) RETURNING cidade";
						$res = pg_query($con, $sql);

						if (strlen(pg_last_error()) > 0) {
							throw new Exception("Erro ao abrir o atendimento");
						}

						$cidade_id = pg_fetch_result($res, 0, "cidade");
					}
				}

				$cep = preg_replace("/\D/", "", $cep);

				$coluna = "";
				$value_coluna = "";

				if(!empty($produto)){
					$coluna = ",produto";
					$produto = ",".$produto;
				}

				if($pais != "Brazil"){
					$coluna .= ", array_campos_adicionais";
					$value_coluna[] = array("pais" => utf8_encode($pais));
					$value_coluna = json_encode($value_coluna);
					$value_coluna = ", '".str_replace("\\", "\\\\", $value_coluna)."'";
				}

				$sql = "INSERT INTO tbl_hd_chamado_extra (
							hd_chamado,
							nome,
							email,
							fone,
							cep,
							cidade,
							bairro,
							endereco,
							numero,
							complemento
							{$coluna}
						) VALUES (
							{$hd_chamado},
							'{$nome}',
							'{$email}',
							'{$telefone}',
							'{$cep}',
							{$cidade_id},
							'{$bairro}',
							'{$endereco}',
							'{$numero}',
							'{$complemento}'
							{$produto}
							{$value_coluna}
						)";

				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception(utf8_encode("Erro ao abrir o atendimento"));
				}

				$sql = "INSERT INTO tbl_hd_chamado_item (
							hd_chamado, 
							admin, 
							comentario
						) VALUES (
							{$hd_chamado},
							$admin,
							'".$contato_setor[$contato]['descricao']." {$mensagem}'
						)";	
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao abrir o atendimento");
				}

				$headers  = 'From: '.$contato_setor[$contato]['descricao'].' - ESAB <helpdesk@telecontrol.com.br>' . "\r\n";
				$headers .= 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

				$mensagem_email = "Foi aberto o atendimento <a href=\"http://posvenda.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=$hd_chamado\">$hd_chamado</a> pela página do Fale Conosco ESAB. Por favor, verificar o Chamado.";

				if ($_serverEnvironment == "development") {
					$admin_email = "rafael.macedo@telecontrol.com.br";
				} else {
					$admin_email = "";
				}

				mail($admin_email, "Atendimento aberto pelo fale conosco", $mensagem_email, $headers);
				
				pg_query($con, "COMMIT");

				$retorno = array("sucesso" => true, "hd_chamado" => $hd_chamado);
			} catch (Exception $e) {
				$msg_erro["msg"][] = $e->getMessage();
				$retorno = array("erro" => $msg_erro);
				pg_query($con, "ROLLBACK");
			}
		}

		$assunto_email = $contato_setor[$contato]["descricao"]." - ESAB";

		$mensagem_email = "
			<table style='border-collapse: collapse;' >
				<tbody>
					<tr>
						<th style='background-color: #ffe600; color: #000;' >Nome</th>
						<td>{$nome}</td>
					</tr>
					<tr>
						<th style='background-color: #ffe600; color: #000;' >E-mail</th>
						<td>{$email}</td>
					</tr>
					<tr>
						<th style='background-color: #ffe600; color: #000;' >Telefone</th>
						<td>{$telefone}</td>
					</tr>
					<tr>
						<th style='background-color: #ffe600; color: #000;' >CEP</th>
						<td>{$cep}</td>
					</tr>
					<tr>
						<th style='background-color: #ffe600; color: #000;' >País</th>
						<td>{$pais}</td>
					</tr>
					<tr>
						<th style='background-color: #ffe600; color: #000;' >Estado</th>
						<td>{$estado}</td>
					</tr>
					<tr>
						<th style='background-color: #ffe600; color: #000;' >Cidade</th>
						<td>{$cidade}</td>
					</tr>
					<tr>
						<th style='background-color: #ffe600; color: #000;' >Bairro</th>
						<td>{$bairro}</td>
					</tr>
					<tr>
						<th style='background-color: #ffe600; color: #000;' >Endereço</th>
						<td>{$endereco}</td>
					</tr>
					<tr>
						<th style='background-color: #ffe600; color: #000;' >Número</th>
						<td>{$numero}</td>
					</tr>
					<tr>
						<th style='background-color: #ffe600; color: #000;' >Complemento</th>
						<td>{$complemento}</td>
					</tr>";

		if ($contato == "centro_suporte" || $contato == "outro") {
			$mensagem_email .= "
					<tr>
						<th style='background-color: #ffe600; color: #000;' >Família</th>
						<td>{$descricao_familia}</td>
					</tr>
					<tr>
						<th style='background-color: #ffe600; color: #000;' >Produto</th>
						<td>{$descricao_produto}</td>
					</tr>
					";
		}

		$mensagem_email .= "<tr>
						<th style='background-color: #ffe600; color: #000;' >Contato / Email</th>
						<td>".$contato_setor[$contato]['descricao']." / ".$contato_setor[$contato]['email']."</td>
					</tr>
					<tr>
						<th style='background-color: #ffe600; color: #000;' >Mensagem</th>
						<td>{$mensagem}</td>
					</tr>
				</tbody>
			</table>";

		$headers  = 'From: '.$contato_setor[$contato]["descricao"].' - ESAB <helpdesk@telecontrol.com.br>' . "\r\n";
		$headers .= 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

		if ($_serverEnvironment == "development") {
			$enviar_email = "rafael.macedo@telecontrol.com.br";
		}else{
			$enviar_email = $contato_setor[$contato]["email"];
		}

		if (!mail($enviar_email, $assunto_email, $mensagem_email, $headers)) {
			$msg_erro["msg"][] = "Erro ao enviar mensagem";
			$retorno = array("erro" => $msg_erro);
		} else {
			$retorno = array("sucesso" => true);
		}
	}

	exit(json_encode($retorno));
}

if ($_GET["ajax_carrega_cidades"]) {
	$estado = strtoupper(trim($_GET["estado"]));

	if (empty($estado)) {
		$retorno = array("erro" => utf8_encode("Estado não informado"));
	} else {
		$sql = "SELECT DISTINCT nome FROM tbl_cidade WHERE UPPER(estado) = '{$estado}' ORDER BY nome ASC";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			$retorno = array("erro" => "Erro ao carregar cidades");
		} else {
			$retorno = array("cidades" => array());

			while ($cidade = pg_fetch_object($res)) {
				$retorno["cidades"][] = utf8_encode(strtoupper($cidade->nome));
			}
		}
	}

	exit(json_encode($retorno));
}

if ($_GET["ajax_carrega_produto"]) {
	$familia = trim($_GET["familia"]);

	if (empty($familia)) {
		$retorno = array("resultado" => false, "erro" => utf8_encode("Família não informado"));
	} else {
		$sql = "SELECT DISTINCT produto, descricao FROM tbl_produto WHERE familia = {$familia} ORDER BY descricao ASC";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) == 0) {
			$retorno = array("erro" => utf8_encode("Erro ao carregar os produtos da família"));
		} else {
			$retorno = array();
			while($array_produto = pg_fetch_object($res)){
				$retorno[] = array("produto" => $array_produto->produto, 
					"descricao" => utf8_encode($array_produto->descricao));
			}
		}
	}

	exit(json_encode($retorno));
}
?>

<!DOCTYPE html />
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
	<meta name="language" content="pt-br" />

	<!-- jQuery -->
	<script type="text/javascript" src="plugins/jquery-1.11.3.min.js" ></script>

	<!-- Bootstrap -->
	<script type="text/javascript" src="plugins/bootstrap/js/bootstrap.min.js" ></script>
	<link rel="stylesheet" type="text/css" href="plugins/bootstrap/css/bootstrap.min.css" />

	<!-- Plugins Adicionais -->
	<script type="text/javascript" src="../../plugins/jquery.mask.js"></script>
	<script type="text/javascript" src="../../plugins/jquery.alphanumeric.js"></script>
	<script type="text/javascript" src="../../plugins/fancyselect/fancySelect.js"></script>
	<link rel="stylesheet" type="text/css" href="../../plugins/fancyselect/fancySelect.css" />

	<style>
	html {
		font-family: "Open Sans", sans-serif;
		font-size: 14px;
		font-weight: 300;
		line-height: 1.42857;
		color: #3E3E3D;
	}

	h2 {
	    font-size: 114% !important;
	    line-height: 111%;
	    font-weight: bold;
	    padding-top: 10px;
	    padding-right: 10px;
	    padding-bottom: 10px;
	    margin: 0;
	    color: #000000;
	}

	div.sub_cabecalho {
		background: #ffe600;
	}

	div.body_formulario {
	    background: #dbe2e8;
	    margin-bottom: 5px;
	    padding-bottom: 10px;
	}

	div.container {
		max-width: 800px;
	}

	div.informacao_adicional {
		margin-top: 5px;
	}

	.campo_obrigatorio {
		color: #808285;
	}

	legend {
	    /* font-weight: bold; */
	    font-weight: 900;
	    font-family: "Arial Black", Arial, Helvetica, sans-serif;
		border: medium none;
		font-size: 285%;
	}

	legend, label {
	    color: #808285;
	    font-weight: bold;
	}

	label {
	    font-size: 114%;
	    margin: 0;
	    padding-bottom: 7px;
	    padding-top: 7px;
	    line-height: 16px;
        cursor: pointer;
    	display: block;
	}

	div.form-group {
		background: #dbe2e8;
		padding-bottom: 1%;
		margin-bottom: 0px;
	}

	input:focus, textarea:focus {
        background: #fafafa !important;
	    border-color: #999999;
	    outline: none;
	}

	input, select, textarea {
	    border: 0px !important;
	    background-color: #edf2f5 !important;
	    -webkit-box-shadow: none;
	    box-shadow: none;
	    color: #808285;
	    padding-bottom: 1%;
	}

	select option {
		background-color: #fff;
	}

	textarea {
		height: auto !important;
	}

	div.botao_enviar {
		width: 100%;
	}

	button {
		margin-left: 80%;
		background-color: #ffe600;
	}

	button:hover {
		background-color: #FFF59B;
	}

	h1, h2, h3, h4, h5, p, li, th, td, form, button {
	    font-family: Arial, Helvetica, sans-serif;
	    font-size: 1rem;
	}

	#msg_erro, #msg_sucesso {
		display: none;
	}

	span.loading {
		color: #ffe600;
		margin-left: 20px;
	}

	.alert {
		border: medium none;
		font-weight: 300;
		padding: 15px 20px;
		border-radius: 3px;
	}

	.alert-danger {
		background-color: #EE6057;
		color: #FFF;
	}

	.alert-success {
		background-color: #B6D334;
		color: #FFF;
	}

	.campo-vazio {
		border: 1px solid #ff0000 !important;
	}
	</style>

	<script>

	$(function() {
		$("#telefone").each(function() {
            if ($(this).val().match(/^\(1\d\) 9/i)) {
                $(this).mask("(00) 00000-0000", $(this).val());
            } else {
                $(this).mask("(00) 0000-0000", $(this).val());
            }
        });

        $("#telefone").keypress(function() {
            if ($(this).val().match(/^\(1\d\) 9/i)) {
                $(this).mask("(00) 00000-0000");
            } else {
               $(this).mask("(00) 0000-0000");
            }
        });

        $("#cep").mask("99999-999");
        $("#numero").numeric();

        $("input, textarea, select").blur(function() {
        	var valor = $.trim($(this).val());

        	if (valor.length > 0) {
        		if ($(this).parents("div.form-group").hasClass("has-error")) {
        			$(this).parents("div.form-group").removeClass("has-error");
        		}
        	}
        });

        // $("#estado").on("change", function() {
        // 	$(this).trigger("change.$");
        // });

		$("#estado").on("change", function() {
			var value = $(this).val();

			if (value.length > 0) {
				carregaCidades(value);
			} else {
				$("#cidade").find("option:first").nextAll().remove();
				$("#cidade").trigger("update");
			}
		});

		$("#familia").on("change", function() {
			var value = $(this).val();

			if (value.length > 0) {
				carregaProduto(value);
			} else {
				$("#cidade").find("option:first").nextAll().remove();
			}
		});

		$("#contato").on("change", function(){
			var contato = $(this).val();

			if(contato == "centro_suporte" || contato == "outro"){
				$("#div_familia").show();
			}else{
				$("#div_familia").hide();
				$("#familia").find("option:first");
				$("#produto").find("option:first").nextAll().remove();
			}
		});

		$("#pais").on("change", function(){
			var pais = $(this).val();

			if(pais == "Brazil"){
				$("#div_estado_select").show();
				$("#div_estado_input").hide();
			}else{
				$("#div_estado_select").hide();
				$("#div_estado_input").show();
			}
		});

		$("#enviar").click(function() {
			var btn      = $(this);
			var formData = $("#form_fale_conosco").serializeArray();

			var data = {};

			$("#form_fale_conosco").find("input, textarea, select").each(function() {
				var name  = $(this).attr("name");
				var value = $(this).val();

				// if($("#pais").val() != "Brazil"){
				// 	if(name == "estado_input"){
				// 		name = "estado";
				// 	}

				// 	if(name == "cidade_input"){
				// 		name = "cidade";
				// 	}
				// }
				data[name] = value;
			});

			data.ajax_enviar = true;

			$.ajax({
				url: "callcenter_cadastra_esab.php",
				type: "post",
				data: data,
				beforeSend: function() {
					$("div.input.erro").removeClass("erro");
					$("#msg_erro").html("").hide();
					$("#msg_sucesso").hide();
					$(btn).button("loading");
				}
			}).done(function(data) {
				data = JSON.parse(data);

				if (data.erro) {
					var msg_erro = [];

					$.each(data.erro.msg, function(key, value) {
						msg_erro.push(value);
					});

					$("#msg_erro").html("<span style='font-weight: bold;' >Desculpe!</span><br />"+msg_erro.join("<br />"));

					data.erro.campos.forEach(function(input) {
						$("input[name="+input+"], textarea[name="+input+"], select[name="+input+"]").addClass("campo-vazio");
					});

					$("#msg_erro").show();
				} else {
					if (typeof data.hd_chamado != "undefined") {
						$("#msg_sucesso").html("<span style='font-weight: bold;'>Obrigado!</span> Recebemos seu contato em breve retornaremos.<br />Protocolo: "+data.hd_chamado).show();
					} else {
						$("#msg_sucesso").html("<span style='font-weight: bold;'>Obrigado!</span> Recebemos seu contato em breve retornaremos.").show();
					}

					if($("#receber_informacao").val() == "t"){
						var informacao = {
							receber_informacao : true,
							email : $("#email").val()
						};

						window.parent.postMessage(informacao, "*");

					}

					$("div.form-group").find("input, textarea, select").val("");
					// $("#estado, #cidade, #departamento, #familia, #produto").trigger("update");
					$("#div_familia, #div_produto").hide();
				}

				$(document).scrollTop(0);
				$(btn).button("reset");
			});
		});

		/**
		 * Evento para buscar o endereço do cep digitado
		 */
		$("input[id=cep]").blur(function() {
			if ($(this).attr("readonly") == undefined && $("#pais").val() == "Brazil") {
				busca_cep($(this).val());
			}
		});
	});

	/**
	 * Função que faz um ajax para buscar o cep nos correios
	 */
	function busca_cep(cep) {
		if (cep.length > 0) {
			var img = $("<img />", { src: "../../admin/imagens/loading_img.gif", css: { width: "34px", height: "34px" } });

			$.ajax({
				async: true,
				url: "callcenter_cadastra_esab.php",
				type: "POST",
				data: { ajax_busca_cep: true, cep: cep },
				beforeSend: function() {
					$("#estado").hide().after(img.clone());
					$("#cidade").hide().after(img.clone());
					$("#bairro").hide().after(img.clone());
					$("#endereco").hide().after(img.clone());
				},
				complete: function(data) {
					data = $.parseJSON(data.responseText);

					if (data.error) {
						alert(data.error);
						$("#cidade").show().next().remove();
					} else {
						$("#estado").val(data.uf);

						busca_cidade(data.uf, retiraAcentos(data.cidade).toUpperCase());

						$("#cidade").val(retiraAcentos(data.cidade).toUpperCase());

						if (data.bairro.length > 0) {
							$("#bairro").val(data.bairro);
						}

						if (data.end.length > 0) {
							$("#endereco").val(data.end);
						}
					}

					$("#estado").show().next().remove();
					$("#bairro").show().next().remove();
					$("#endereco").show().next().remove();

					if ($("#bairro").val().length == 0) {
						$("#bairro").focus();
					} else if ($("#endereco").val().length == 0) {
						$("#endereco").focus();
					} else if ($("#numero").val().length == 0) {
						$("#numero").focus();
					}
				}
			});
		}
	}

	/**
	 * Função que busca as cidades do estado e popula o select cidade
	 */
	function busca_cidade(estado, cidade) {
		$("#cidade").find("option").first().nextAll().remove();

		if (estado.length > 0) {
			$.ajax({
				async: false,
				url: "callcenter_cadastra_esab.php",
				type: "POST",
				data: { ajax_busca_cidade: true, estado: estado },
				beforeSend: function() {
					if ($("#cidade").next("img").length == 0) {
						$("#cidade").hide().after($("<img />", { src: "../../admin/imagens/loading_img.gif", css: { width: "34px", height: "34px" } }));
					}
				},
				complete: function(data) {
					data = $.parseJSON(data.responseText);

					if (data.error) {
						alert(data.error);
					} else {
						$.each(data.cidades, function(key, value) {
							var option = $("<option></option>", { value: value, text: value});

							$("#cidade").append(option);
						});
					}
					$("#cidade").show().next().remove();
				}
			});
		}

		if(typeof cidade != "undefined" && cidade.length > 0){
			$('#cidade option[value='+cidade+']').attr('selected','selected');
		}
	}

	/**
	 * Função para retirar a acentuação
	 */
	function retiraAcentos(palavra){
		var com_acento = 'áàãâäéèêëíìîïóòõôöúùûüçÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÖÔÚÙÛÜÇ';
		var sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';
	    var newPalavra = "";

	    for(i = 0; i < palavra.length; i++) {
	    	if (com_acento.search(palavra.substr(i, 1)) >= 0) {
	      		newPalavra += sem_acento.substr(com_acento.search(palavra.substr(i, 1)), 1);
	      	} else {
	       		newPalavra += palavra.substr(i, 1);
	    	}
	    }
	    return newPalavra.toUpperCase();
	}

	function carregaCidades(estado) {
		var select_cidade = $("#cidade");

		$.ajax({
			url: "callcenter_cadastra_esab.php",
			type: "get",
			data: { ajax_carrega_cidades: true, estado: estado },
			beforeSend: function() {
				$(select_cidade).find("option:first").nextAll().remove();
				$("#cidade_label").append("<span class='loading' >carregando...</span>")
			}
		}).done(function(data) {
			data = JSON.parse(data);

			if (data.erro) {
				alert(data.erro);
			} else {
				data.cidades.forEach(function(cidade) {
					var option = $("<option></option>", {
						value: cidade,
						text: cidade
					});

					$(select_cidade).append(option);
				});

				$("#cidade_label span.loading").remove();
			}

			// $(select_cidade).trigger("update");
		});
	}

	function carregaProduto(familia) {
		var select_produto = $("#produto");

		$.ajax({
			url: "callcenter_cadastra_esab.php",
			type: "get",
			data: { ajax_carrega_produto: true, familia: familia },
			beforeSend: function() {
				$(select_produto).find("option:first").nextAll().remove();
				$("#produto_label").append("<span class='loading' >carregando...</span>")
			}
		}).done(function(data) {
			data = JSON.parse(data);

			if (data.erro) {
				alert(data.erro);
			} else {
				data.forEach(function(value) {
					var option = $("<option></option>", {
						value: value.produto,
						text: value.descricao
					});

					$(select_produto).append(option);
				});

				$("#produto_label span.loading").remove();
			}

			// $(select_produto).trigger("update");
		});
	}
	</script>

	<title>Fale Conosco</title>
</head>
<body>

<div class="container" >
	<form id="form_fale_conosco" method="post" >
		<legend>FALE CONOSCO</legend>
		<p>
			<strong>Para entrar em contato, preencha o formulário abaixo, selecionando o Contato</strong>
		<p>

		<div id="msg_erro" class="alert alert-danger" ></div>

		<div id="msg_sucesso" class="alert alert-success" ></div>

		<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 campo_obrigatorio" >
			<b>* Campos obrigatórios</b>
		</div>

		<!-- <div class="body_formulario"> -->
			<div class="sub_cabecalho col-xs-12 col-sm-12 col-md-12 col-lg-12">
				<h2>Informações Pessoais</h2>
			</div>
		
			<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12">
				<label for="contato">Contato<span class="campo_obrigatorio">*</span></label>
				<select class="form-control" id="contato" name="contato">
					<option value="">Favor Selecionar</option>
					<option value="venda">Vendas</option>
					<option value="centro_suporte">Centro de Suporte ao Cliente ESAB</option>
					<option value="certificado">Certificados de Consumiveis</option>
					<option value="revista">Revista Solução</option>
					<option value="outro">Outro</option>
				</select>
			</div>

			<div class="div_familia" id="div_familia" style="display:none;">
				<?php
					$sql = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = {$login_fabrica}";
					$res = pg_query($con,$sql);

					if(pg_num_rows($res) > 0){
				?>
				<div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
					<label id="produto_label" for="familia" >Família</label>
					<select class="form-control" id="familia" name="familia" >
						<option value="" >Selecione</option>
						<?php
						while ($array_familia = pg_fetch_object($res)) {
							echo "<option value='".$array_familia->familia."' >".$array_familia->descricao."</option>";
						}
						?>
					</select>
				</div>

				<div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
					<label for="produto" >Produto</label>
					<select class="form-control" id="produto" name="produto" >
						<option value="" >Selecione</option>
					</select>
				</div>
				<?php }
				?>
			</div>

			<div class="form-group col-xs-12 col-sm-4 col-md-4 col-lg-4" >
				<label for="nome" >Nome<span class="campo_obrigatorio">*</span></label>
				<input type="text" class="form-control" id="nome" name="nome" />
			</div>

			<div class="form-group col-xs-12 col-sm-4 col-md-4 col-lg-4" >
				<label for="cargo" >Cargo</label>
				<input type="text" class="form-control" id="cargo" name="cargo" />
			</div>

			<div class="form-group col-xs-12 col-sm-4 col-md-4 col-lg-4" >
				<label for="empresa" >Empresa</label>
				<input type="text" class="form-control" id="empresa" name="empresa" />
			</div>

			<div class="form-group col-xs-12 col-sm-4 col-md-4 col-lg-4" >
				<label for="pais" >País<span class="campo_obrigatorio">*</span></label>
				<select class="form-control" id="pais" name="pais" >
					<option value="" >Selecione</option>
					<?php
					foreach ($array_pais as $sigla => $nome) {
						echo "<option value='{$sigla}' >{$nome}</option>";
					}
					?>
				</select>
			</div>

			<div class="form-group col-xs-12 col-sm-4 col-md-4 col-lg-4" >
				<label for="cep" >CEP</label>
				<input type="text" class="form-control" id="cep" name="cep" />
			</div>

			<div class="div_estado_select" id="div_estado_select" style="display:none;">
				<div class="form-group col-xs-12 col-sm-4 col-md-4 col-lg-4" >
					<label for="estado" >Estado<span class="campo_obrigatorio">*</span></label>
					<select class="form-control" id="estado" name="estado" >
						<option value="" >Selecione</option>
						<?php
						foreach ($array_estado as $sigla => $nome) {
							echo "<option value='{$sigla}' >{$nome}</option>";
						}
						?>
					</select>
				</div>

				<div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
					<label id="cidade_label" for="cidade" >Cidade<span class="campo_obrigatorio">*</span></label>
					<select class="form-control" id="cidade" name="cidade" >
						<option value="" >Selecione</option>
					</select>
				</div>
			</div>

			<div class="div_estado_input" id="div_estado_input">
				<div class="form-group col-xs-12 col-sm-4 col-md-4 col-lg-4" >
					<label for="estado_input" >Estado<span class="campo_obrigatorio">*</span></label>
					<input type="text" class="form-control" readonly id="estado_input" name="estado_input" />
				</div>

				<div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
					<label id="cidade_label" for="cidade_input" >Cidade<span class="campo_obrigatorio">*</span></label>
					<input type="text" class="form-control" id="cidade_input" name="cidade_input" />
				</div>
			</div>

			<div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
				<label for="bairro" >Bairro</label>
				<input type="text" class="form-control" id="bairro" name="bairro" />
			</div>

			<div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
				<label for="endereco" >Endereço</label>
				<input type="text" class="form-control" id="endereco" name="endereco" />
			</div>

			<div class="form-group col-xs-6 col-sm-2 col-md-2 col-lg-2" >
				<label for="numero" >Número</label>
				<input type="text" class="form-control" id="numero" name="numero" />
			</div>

			<div class="form-group col-xs-6 col-sm-4 col-md-4 col-lg-4" >
				<label for="complemento" >Complemento</label>
				<input type="text" class="form-control" id="complemento" name="complemento" />
			</div>

			<div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
				<label for="telefone" >Telefone<span class="campo_obrigatorio">*</span></label>
				<input type="text" class="form-control" id="telefone" class="telefone" name="telefone" />
			</div>

			<div class="form-group col-xs-12 col-sm-6 col-md-6 col-lg-6" >
				<label for="email" >E-mail<span class="campo_obrigatorio">*</span></label>
				<input type="text" class="form-control" id="email" name="email" />
			</div>
			<p></p>
		<!-- </div> -->

		<div class="sub_cabecalho informacao_adicional col-xs-12 col-sm-12 col-md-12 col-lg-12">
			<h2>Informações Adicionais</h2>
		</div>

		<!-- <div class="body_formulario"> -->
			<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
				<div class="checkbox">
					<label>
						<input type="checkbox" name="receber_informacao" id="receber_informacao" value="t"><b>Sim! Eu gostaria de receber informações periódicas da ESAB.</b></label>
				</div>
			</div>

			<div class="form-group col-xs-12 col-sm-12 col-md-12 col-lg-12" >
				<label for="mensagem" >Mensagem<span class="campo_obrigatorio">*</span></label>
				<textarea class="form-control" name="mensagem" id="mensagem" rows="6" ></textarea>
			</div>

			<div class="form-group botao_enviar col-xs-12 col-sm-4 col-md-4 col-lg-4" >
				<button type="button" id="enviar" class="btn btn-lg" data-loading-text="ENVIANDO..." >ENVIAR</button>
			</div>
		<!-- </div> -->
	</form>
</div>

<br /><br />
		
</body>
</html>

