<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

// CNPJ   = 05131296000101
// DÍGITO = 5 * 9 = 45

exit;

if (strlen($_GET['cnpj']) > 0)   $cnpj   = $_GET['cnpj'];
if (strlen($_GET['digito']) > 0) $digito = $_GET['digito'];


if (strlen($cnpj) > 0) {
	$dv = substr($cnpj,1,1) * substr($cnpj,6,1);
	if (strlen($digito) > 0) {
		if ($dv <> $digito) {
			echo "Digito nao confere";
			exit;
		}
	}
}

// AUTENTICAÇÃO
$sql = "SELECT      tbl_posto_fabrica.fabrica                  ,
					tbl_fabrica.nome                           ,
					tbl_fabrica.pedido_escolhe_condicao        ,
					tbl_fabrica.defeito_constatado_por_familia ,
					tbl_posto_fabrica.pedido_em_garantia       ,
					tbl_fabrica.logo
		FROM        tbl_posto
		JOIN        tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
		JOIN        tbl_fabrica       ON tbl_fabrica.fabrica     = tbl_posto_fabrica.fabrica
		WHERE       tbl_posto.cnpj                  = '$cnpj'
		AND         tbl_fabrica.sistema_offline     IS TRUE";

//liberado apenas para lorenzetti e blackedecker
//e para testes de Eigi com fabricante telecontrol
if ($ip <> '201.92.1.91' and $cnpj <> '00557849000160')
  $sql .= " AND (tbl_fabrica.fabrica = 19
            OR tbl_fabrica.fabrica = 1)";

$sql .= " ORDER BY    tbl_fabrica.fabrica;";
//		AND         tbl_posto_fabrica.credenciamento = 'CREDENCIADO'

$resX = pg_exec($con,$sql);

if (pg_numrows($resX) > 0) {
	for ($x=0; $x < pg_numrows($resX); $x++) {
		$fabricante        .= pg_result($resX,$x,fabrica);
		$fabricantes       .= pg_result($resX,$x,nome)."/*.xml";
		$links_fabricantes .= "ln -s /var/www/assist/www/xml/".pg_result($resX,$x,nome)." ".pg_result($resX,$x,nome);
		if ($x+1 < pg_numrows($resX)) {
			$fabricante        .= ",";
			$fabricantes       .= " ";//CONCATENA COM ESPACO PARA POR NA GERACAO DO ARQUIVO ZIP
			$links_fabricantes .= ";";//CONCATENA PARA GERAR OS LINKS SIMBOLICOS NA PASTA DOP POSTO DAS PASTAS DOS FABRICANTES
		}
	}
}


if (strlen($fabricante) > 0) {
	// CRIA DIRETÓRIO DO POSTO
	echo `cd xml; rm -rf $cnpj; mkdir $cnpj; chmod 777 $cnpj; cd $cnpj; mkdir logos; chmod 777 logos`;

	######################################################################################################################
	// POSTO
	######################################################################################################################
	$sql = "SELECT  trim(tbl_posto.cnpj)                           AS cnpj                ,
					trim(tbl_posto.nome)                           AS nome                ,
					trim(tbl_posto.ie)                             AS ie                  ,
					trim(tbl_posto.nome_fantasia)                  AS nome_fantasia       ,
					trim(tbl_posto_fabrica.contato_fone_comercial)                           AS telefone            ,
					trim(tbl_posto.fax)                            AS fax                 ,
					trim(tbl_posto.contato)                        AS contato             ,
					trim(tbl_posto.email)                          AS email               ,
					trim(tbl_posto.capital_interior)               AS situado             ,
					tbl_posto.suframa                                                     ,
					trim(tbl_posto_fabrica.obs)                    AS obs                 ,
					trim(tbl_posto.endereco)                       AS endereco            ,
					trim(tbl_posto.numero)                         AS numero              ,
					trim(tbl_posto.complemento)                    AS complemento         ,
					trim(tbl_posto.bairro)                         AS bairro              ,
					trim(tbl_posto.cep)                            AS cep                 ,
					trim(tbl_posto.cidade)                         AS cidade              ,
					trim(tbl_posto.estado)                         AS estado
			FROM    tbl_posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE   tbl_posto.cnpj = '$cnpj'
			LIMIT 1;";
	$res = pg_query($con,$sql);

	// cabeçalho
	$xml_posto .= "<"."?xml version=\"1.0\" encoding=\"ISO-8859-1\"?".">\n";    // Quebrei a string para faciliar a edição
	$xml_posto .= "<DATAPACKET Version=\"2.0\">\n";
	$xml_posto .= "<METADATA>\n";
	$xml_posto .= "<FIELDS>\n";
	$xml_posto .= "<FIELD attrname=\"cnpj\" fieldtype=\"string\" WIDTH=\"14\"/>\n";
	$xml_posto .= "<FIELD attrname=\"nome\" fieldtype=\"string\" WIDTH=\"60\"/>\n";
	$xml_posto .= "<FIELD attrname=\"ie\" fieldtype=\"string\" WIDTH=\"30\"/>\n";
	$xml_posto .= "<FIELD attrname=\"fantasia\" fieldtype=\"string\" WIDTH=\"30\"/>\n";
	$xml_posto .= "<FIELD attrname=\"fone\" fieldtype=\"string\" WIDTH=\"30\"/>\n";
	$xml_posto .= "<FIELD attrname=\"fax\" fieldtype=\"string\" WIDTH=\"30\"/>\n";
	$xml_posto .= "<FIELD attrname=\"contato\" fieldtype=\"string\" WIDTH=\"30\"/>\n";
	$xml_posto .= "<FIELD attrname=\"email\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
	$xml_posto .= "<FIELD attrname=\"situado\" fieldtype=\"string\" WIDTH=\"10\"/>\n";
	$xml_posto .= "<FIELD attrname=\"suframa\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_posto .= "<FIELD attrname=\"obs\" fieldtype=\"string\" WIDTH=\"255\"/>\n";
	$xml_posto .= "<FIELD attrname=\"endereco\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
	$xml_posto .= "<FIELD attrname=\"numero\" fieldtype=\"string\" WIDTH=\"10\"/>\n";
	$xml_posto .= "<FIELD attrname=\"complemento\" fieldtype=\"string\" WIDTH=\"20\"/>\n";
	$xml_posto .= "<FIELD attrname=\"bairro\" fieldtype=\"string\" WIDTH=\"40\"/>\n";
	$xml_posto .= "<FIELD attrname=\"cep\" fieldtype=\"string\" WIDTH=\"8\"/>\n";
	$xml_posto .= "<FIELD attrname=\"cidade\" fieldtype=\"string\" WIDTH=\"30\"/>\n";
	$xml_posto .= "<FIELD attrname=\"estado\" fieldtype=\"string\" WIDTH=\"2\"/>\n";
	$xml_posto .= "</FIELDS>\n";
	$xml_posto .= "</METADATA>\n";
	$xml_posto .= "<ROWDATA>\n";

	$cnpj                 = "";
	$nome                 = "";
	$ie                   = "";
	$fantasia             = "";
	$fone                 = "";
	$fax                  = "";
	$contato              = "";
	$email                = "";
	$situado              = "";
	$suframa              = "";
	$obs                  = "";
	$endereco             = "";
	$numero               = "";
	$complemento          = "";
	$bairro               = "";
	$cep                  = "";
	$cidade               = "";
	$estado               = "";

	if (pg_num_rows($res) > 0) {
		$contador = 0;
		while ($ln_posto = pg_fetch_assoc($res)){
			$contador++;
			$cnpj                 = $ln_posto['cnpj'];
			$nome                 = str_replace('&','E', $ln_posto['nome']);
			$ie                   = $ln_posto['ie'];
			$fantasia             = $ln_posto['nome_fantasia'];
			$fone                 = $ln_posto['telefone'];
			$fax                  = $ln_posto['fax'];
			$contato              = $ln_posto['contato'];
			$email                = $ln_posto['email'];
			$situado              = $ln_posto['situado'];
			$suframa              = $ln_posto['suframa'];
			$obs                  = $ln_posto['obs'];
			$endereco             = $ln_posto['endereco'];
			$numero               = $ln_posto['numero'];
			$complemento          = $ln_posto['complemento'];
			$bairro               = $ln_posto['bairro'];
			$cep                  = $ln_posto['cep'];
			$cidade               = $ln_posto['cidade'];
			$estado               = $ln_posto['estado'];

			if (strlen($suframa) == 0) $suframa = "f";

			$xml_posto .= "<ROW RowState=\"4\" cnpj=\"$cnpj\" nome=\"$nome\" ie=\"$ie\" fantasia=\"$fantasia\" fone=\"$fone\" fax=\"$fax\" contato=\"$contato\" email=\"$email\" situado=\"$situado\" suframa=\"$suframa\" obs=\"$obs\" endereco=\"$endereco\" numero=\"$numero\" complemento=\"$complemento\" bairro=\"$bairro\" cep=\"$cep\" cidade=\"$cidade\" estado=\"$estado\"/>\n";
// 			flush();
		}
	}else{
		$xml_posto .= "<ROW RowState=\"4\" cnpj=\"$cnpj\" nome=\"$nome\" ie=\"$ie\" fantasia=\"$fantasia\" fone=\"$fone\" fax=\"$fax\" contato=\"$contato\" email=\"$email\" situado=\"$situado\" suframa=\"$suframa\" obs=\"$obs\" endereco=\"$endereco\" numero=\"$numero\" complemento=\"$complemento\" bairro=\"$bairro\" cep=\"$cep\" cidade=\"$cidade\" estado=\"$estado\"/>\n";
// 		flush();
	}

	$xml_posto .= "</ROWDATA>\n";
	$xml_posto .= "</DATAPACKET>\n";

	// GERA XML DO ARQUIVO
	$file    = "xml/$cnpj/posto.xml";
	$arquivo = fopen($file, "w+");
	fwrite($arquivo, $xml_posto);
	fclose($arquivo);


	##################################################################################################################
	// FABRICAS
	##################################################################################################################
	$sql = "SELECT  trim(tbl_fabrica.fabrica)                 AS fabrica,
					trim(tbl_fabrica.nome)                    AS nome   ,
					trim(tbl_fabrica.email_gerente)           AS email  ,
					tbl_fabrica.logo                                    ,
					tbl_fabrica.inibe_revenda                           ,
					tbl_fabrica.linha_pedido                            ,
					tbl_fabrica.os_contrato                             ,
					tbl_fabrica.pedido_escolhe_transportadora           ,
					tbl_fabrica.contrato_manutencao                     ,
					tbl_fabrica.os_item_subconjunto                     ,
					tbl_fabrica.os_item_serie                           ,
					tbl_fabrica.os_item_descricao                       ,
					tbl_fabrica.pedir_sua_os                            ,
					tbl_fabrica.pergunta_qtde_os_item                   ,
					tbl_fabrica.pedido_escolhe_condicao                 ,
					tbl_fabrica.vista_explodida_automatica              ,
					tbl_fabrica.os_item_aparencia                       ,
					tbl_fabrica.defeito_constatado_por_familia          ,
					tbl_fabrica.multimarca                              ,
					tbl_fabrica.acrescimo_tabela_base                   ,
					tbl_fabrica.acrescimo_financeiro                    ,
					tbl_fabrica.pedido_via_distribuidor                 ,
					tbl_fabrica.os_defeito                              ,
					tbl_fabrica.pedir_defeito_reclamado_descricao       ,
					tbl_fabrica.pedir_causa_defeito_os_item             ,
					tbl_fabrica.in_out                                  ,
					tbl_fabrica.email_gerente                           ,
					tbl_fabrica.posicao_pagamento_extrato_automatico    ,
					tbl_fabrica.defeito_constatado_por_linha            ,
					tbl_fabrica.codigo_fabricacao                       ,
					tbl_fabrica.type                                    ,
					tbl_fabrica.satisfacao                              ,
					tbl_fabrica.laudo_tecnico                           ,
					tbl_fabrica.data_abertura_os_automatica
			FROM    tbl_fabrica ";
	if (strlen($fabricante) > 0) $sql .= "WHERE tbl_fabrica.fabrica IN ($fabricante) ";
	$sql .= "ORDER BY tbl_fabrica.fabrica;";
	$res = pg_exec($con,$sql);

	// cabeçalho
	$xml_fabrica .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?".">\n";
	$xml_fabrica .= "<DATAPACKET Version=\"2.0\">\n";
	$xml_fabrica .= "<METADATA>\n";
	$xml_fabrica .= "<FIELDS>\n";
	$xml_fabrica .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"nome\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"email_gerente\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"logo\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"inibe_revenda\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"linha_pedido\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"os_contrato\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"pedido_escolhe_transportadora\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"contrato_manutencao\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"os_item_subconjunto\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"os_item_serie\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"os_item_descricao\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"pedir_sua_os\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"pergunta_qtde_os_item\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"pedido_escolhe_condicao\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"vista_explodida_automatica\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"os_item_aparencia\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"defeito_constatado_por_familia\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"multimarca\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"acrescimo_tabela_base\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"acrescimo_financeiro\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"pedido_via_distribuidor\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"os_defeito\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"pedir_defeito_reclamado_descricao\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"pedir_causa_defeito_os_item\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"in_out\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"email_gerente\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"posicao_pagamento_extrato_automatico\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"defeito_constatado_por_linha\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"codigo_fabricacao\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"type\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"satisfacao\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"data_abertura_os_automatica\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "<FIELD attrname=\"laudo_tecnico\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_fabrica .= "</FIELDS>\n";
	$xml_fabrica .= "</METADATA>\n";
	$xml_fabrica .= "<ROWDATA>\n";

	$fabrica                               = "";
	$nome                                  = "";
	$email_gerente                         = "";
	$logo                                  = "";
	$inibe_revenda                         = "";
	$linha_pedido                          = "";
	$os_contrato                           = "";
	$pedido_escolhe_transportadora         = "";
	$contrato_manutencao                   = "";
	$os_item_subconjunto                   = "";
	$os_item_serie                         = "";
	$os_item_descricao                     = "";
	$pedir_sua_os                          = "";
	$pergunta_qtde_os_item                 = "";
	$pedido_escolhe_condicao               = "";
	$vista_explodida_automatica            = "";
	$os_item_aparencia                     = "";
	$defeito_constatado_por_familia        = "";
	$multimarca                            = "";
	$acrescimo_tabela_base                 = "";
	$acrescimo_financeiro                  = "";
	$pedido_via_distribuidor               = "";
	$os_defeito                            = "";
	$pedir_defeito_reclamado_descricao     = "";
	$pedir_causa_defeito_os_item           = "";
	$in_out                                = "";
	$email_gerente                         = "";
	$posicao_pagamento_extrato_automatico  = "";
	$defeito_constatado_por_linha          = "";
	$codigo_fabricacao                     = "";
	$type                                  = "";
	$satisfacao                            = "";
	$laudo_tecnico                         = "";
	$data_abertura_os_automatica           = "";

	if (pg_numrows($res) > 0) {
		$contador = 0;
		while ($ln_fabrica = pg_fetch_array($res)){
			$contador++;
			$fabrica                               = $ln_fabrica['fabrica'];
			$nome                                  = $ln_fabrica['nome'];
			$email_gerente                         = $ln_fabrica['email'];
			$logo                                  = $ln_fabrica['logo'];
			$inibe_revenda                         = $ln_fabrica['inibe_revenda'];
			$linha_pedido                          = $ln_fabrica['linha_pedido'];
			$os_contrato                           = $ln_fabrica['os_contrato'];
			$pedido_escolhe_transportadora         = $ln_fabrica['pedido_escolhe_transportadora'];
			$contrato_manutencao                   = $ln_fabrica['contrato_manutencao'];
			$os_item_subconjunto                   = $ln_fabrica['os_item_subconjunto'];
			$os_item_serie                         = $ln_fabrica['os_item_serie'];
			$os_item_descricao                     = $ln_fabrica['os_item_descricao'];
			$pedir_sua_os                          = $ln_fabrica['pedir_sua_os'];
			$pergunta_qtde_os_item                 = $ln_fabrica['pergunta_qtde_os_item'];
			$pedido_escolhe_condicao               = $ln_fabrica['pedido_escolhe_condicao'];
			$vista_explodida_automatica            = $ln_fabrica['vista_explodida_automatica'];
			$os_item_aparencia                     = $ln_fabrica['os_item_aparencia'];
			$defeito_constatado_por_familia        = $ln_fabrica['defeito_constatado_por_familia'];
			$multimarca                            = $ln_fabrica['multimarca'];
			$acrescimo_tabela_base                 = $ln_fabrica['acrescimo_tabela_base'];
			$acrescimo_financeiro                  = $ln_fabrica['acrescimo_financeiro'];
			$pedido_via_distribuidor               = $ln_fabrica['pedido_via_distribuidor'];
			$os_defeito                            = $ln_fabrica['os_defeito'];
			$pedir_defeito_reclamado_descricao     = $ln_fabrica['pedir_defeito_reclamado_descricao'];
			$pedir_causa_defeito_os_item           = $ln_fabrica['pedir_causa_defeito_os_item'];
			$in_out                                = $ln_fabrica['in_out'];
			$email_gerente                         = $ln_fabrica['email_gerente'];
			$posicao_pagamento_extrato_automatico  = $ln_fabrica['posicao_pagamento_extrato_automatico'];
			$defeito_constatado_por_linha          = $ln_fabrica['defeito_constatado_por_linha'];
			$codigo_fabricacao                     = $ln_fabrica['codigo_fabricacao'];
			$type                                  = $ln_fabrica['type'];
			$satisfacao                            = $ln_fabrica['satisfacao'];
			$laudo_tecnico                         = $ln_fabrica['laudo_tecnico'];
			$data_abertura_os_automatica           = $ln_fabrica['data_abertura_os_automatica'];

			if (strlen($inibe_revenda) == 0)                         $inibe_revenda                        = "f";
			if (strlen($linha_pedido) == 0)                          $linha_pedido                         = "f";
			if (strlen($os_contrato) == 0)                           $os_contrato                          = "f";
			if (strlen($pedido_escolhe_transportadora) == 0)         $pedido_escolhe_transportadora        = "f";
			if (strlen($contrato_manutencao) == 0)                   $contrato_manutencao                  = "f";
			if (strlen($os_item_subconjunto) == 0)                   $os_item_subconjunto                  = "f";
			if (strlen($os_item_serie) == 0)                         $os_item_serie                        = "f";
			if (strlen($os_item_descricao) == 0)                     $os_item_descricao                    = "f";
			if (strlen($pedir_sua_os) == 0)                          $pedir_sua_os                         = "t";
			if (strlen($pergunta_qtde_os_item) == 0)                 $pergunta_qtde_os_item                = "f";
			if (strlen($pedido_escolhe_condicao) == 0)               $pedido_escolhe_condicao              = "t";
			if (strlen($vista_explodida_automatica) == 0)            $vista_explodida_automatica           = "f";
			if (strlen($os_item_aparencia) == 0)                     $os_item_aparencia                    = "f";
			if (strlen($defeito_constatado_por_familia) == 0)        $defeito_constatado_por_familia       = "f";
			if (strlen($multimarca) == 0)                            $multimarca                           = "f";
			if (strlen($acrescimo_tabela_base) == 0)                 $acrescimo_tabela_base                = "f";
			if (strlen($acrescimo_financeiro) == 0)                  $acrescimo_financeiro                 = "f";
			if (strlen($pedido_via_distribuidor) == 0)               $pedido_via_distribuidor              = "f";
			if (strlen($os_defeito) == 0)                            $os_defeito                           = "f";
			if (strlen($pedir_defeito_reclamado_descricao) == 0)     $pedir_defeito_reclamado_descricao    = "f";
			if (strlen($pedir_causa_defeito_os_item) == 0)           $pedir_causa_defeito_os_item          = "f";
			if (strlen($in_out) == 0)                                $in_out                               = "f";
			if (strlen($posicao_pagamento_extrato_automatico) == 0)  $posicao_pagamento_extrato_automatico = "f";
			if (strlen($defeito_constatado_por_linha) == 0)          $defeito_constatado_por_linha         = "f";
			if (strlen($codigo_fabricacao) == 0)                     $codigo_fabricacao                    = "f";
			if (strlen($type) == 0)                                  $type                                 = "f";
			if (strlen($satisfacao) == 0)                            $satisfacao                           = "f";
			if (strlen($laudo_tecnico) == 0)                         $laudo_tecnico                        = "f";
			if (strlen($data_abertura_os_automatica) == 0)           $data_abertura_os_automatica          = "f";

			$xml_fabrica .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" nome=\"$nome\" email_gerente=\"$email_gerente\" logo=\"$logo\" inibe_revenda=\"$inibe_revenda\" linha_pedido=\"$linha_pedido\" os_contrato=\"$os_contrato\" pedido_escolhe_transportadora=\"$pedido_escolhe_transportadora\" contrato_manutencao=\"$contrato_manutencao\" os_item_subconjunto=\"$os_item_subconjunto\" os_item_serie=\"$os_item_serie\" os_item_descricao=\"$os_item_descricao\" pedir_sua_os=\"$pedir_sua_os\" pergunta_qtde_os_item=\"$pergunta_qtde_os_item\" pedido_escolhe_condicao=\"$pedido_escolhe_condicao\" vista_explodida_automatica=\"$vista_explodida_automatica\" os_item_aparencia=\"$os_item_aparencia\" defeito_constatado_por_familia=\"$defeito_constatado_por_familia\" multimarca=\"$multimarca\" acrescimo_tabela_base=\"$acrescimo_tabela_base\" acrescimo_financeiro=\"$acrescimo_financeiro\" pedido_via_distribuidor=\"$pedido_via_distribuidor\" os_defeito=\"$os_defeito\" pedir_defeito_reclamado_descricao=\"$pedir_defeito_reclamado_descricao\" pedir_causa_defeito_os_item=\"$pedir_causa_defeito_os_item\" in_out=\"$in_out\" posicao_pagamento_extrato_automatico=\"$posicao_pagamento_extrato_automatico\" defeito_constatado_por_linha=\"$defeito_constatado_por_linha\" codigo_fabricacao=\"$codigo_fabricacao\" type=\"$type\" satisfacao=\"$satisfacao\" laudo_tecnico=\"$laudo_tecnico\" data_abertura_os_automatica=\"$data_abertura_os_automatica\"/>\n";
			flush();
		}
	}else{
		$xml_fabrica .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" nome=\"$nome\" email_gerente=\"$email_gerente\" logo=\"$logo\" inibe_revenda=\"$inibe_revenda\" linha_pedido=\"$linha_pedido\" os_contrato=\"$os_contrato\" pedido_escolhe_transportadora=\"$pedido_escolhe_transportadora\" contrato_manutencao=\"$contrato_manutencao\" os_item_subconjunto=\"$os_item_subconjunto\" os_item_serie=\"$os_item_serie\" os_item_descricao=\"$os_item_descricao\" pedir_sua_os=\"$pedir_sua_os\" pergunta_qtde_os_item=\"$pergunta_qtde_os_item\" pedido_escolhe_condicao=\"$pedido_escolhe_condicao\" vista_explodida_automatica=\"$vista_explodida_automatica\" os_item_aparencia=\"$os_item_aparencia\" defeito_constatado_por_familia=\"$defeito_constatado_por_familia\" multimarca=\"$multimarca\" acrescimo_tabela_base=\"$acrescimo_tabela_base\" acrescimo_financeiro=\"$acrescimo_financeiro\" pedido_via_distribuidor=\"$pedido_via_distribuidor\" os_defeito=\"$os_defeito\" pedir_defeito_reclamado_descricao=\"$pedir_defeito_reclamado_descricao\" pedir_causa_defeito_os_item=\"$pedir_causa_defeito_os_item\" in_out=\"$in_out\" posicao_pagamento_extrato_automatico=\"$posicao_pagamento_extrato_automatico\" defeito_constatado_por_linha=\"$defeito_constatado_por_linha\" codigo_fabricacao=\"$codigo_fabricacao\" type=\"$type\" satisfacao=\"$satisfacao\" laudo_tecnico=\"$laudo_tecnico\" data_abertura_os_automatica=\"$data_abertura_os_automatica\"/>\n";
		flush();
	}

	$xml_fabrica .= "</ROWDATA>\n";
	$xml_fabrica .= "</DATAPACKET>\n";

	// GERA XML DO ARQUIVO
	$file    = "xml/$cnpj/fabrica.xml";
	$arquivo = fopen($file, "w+");
	fwrite($arquivo, $xml_fabrica);
	fclose($arquivo);

	for ($x=0; $x < pg_numrows($resX); $x++) {
		$logo = trim(pg_result($resX,$x,logo));
		echo `cp logos/$logo xml/$cnpj/logos/`;
	}


	##################################################################################################################
	// POSTO x FÁBRICA
	##################################################################################################################
	$sql = "SELECT  trim(tbl_posto_fabrica.posto)                  AS posto               ,
					trim(tbl_posto_fabrica.fabrica)                AS fabrica             ,
					trim(tbl_posto_fabrica.codigo_posto)           AS codigo_posto        ,
					trim(tbl_posto_fabrica.cobranca_endereco)      AS endereco_cobranca   ,
					trim(tbl_posto_fabrica.cobranca_numero)        AS numero_cobranca     ,
					trim(tbl_posto_fabrica.cobranca_complemento)   AS complemento_cobranca,
					trim(tbl_posto_fabrica.cobranca_bairro)        AS bairro_cobranca     ,
					trim(tbl_posto_fabrica.cobranca_cep)           AS cep_cobranca        ,
					trim(tbl_posto_fabrica.cobranca_cidade)        AS cidade_cobranca     ,
					trim(tbl_posto_fabrica.cobranca_estado)        AS estado_cobranca     ,
					tbl_posto_fabrica.pedido_em_garantia                                  ,
					tbl_posto_fabrica.pedido_faturado                                     ,
					tbl_posto_fabrica.reembolso_peca_estoque                              ,
					tbl_posto_fabrica.digita_os                                           ,
					tbl_posto_fabrica.item_aparencia                                      ,
					(
					SELECT sua_os_offline
					FROM tbl_os
					WHERE tbl_os.fabrica = tbl_posto_fabrica.fabrica
					AND   tbl_posto_fabrica.posto = tbl_os.posto
					AND   tbl_os.sua_os_offline NOTNULL
					ORDER BY sua_os_offline DESC LIMIT 1
					) AS sua_os_offline
			FROM    tbl_posto_fabrica
			JOIN    tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			WHERE   tbl_posto.cnpj = '$cnpj' ";
	if (strlen($fabricante) > 0) $sql .= "AND tbl_posto_fabrica.fabrica IN ($fabricante) ";
	$sql .= "ORDER BY tbl_posto_fabrica.fabrica, tbl_posto_fabrica.posto;";
	$res = pg_exec($con,$sql);


	// cabeçalho
	$xml_posto_fabrica .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
	$xml_posto_fabrica .= "<DATAPACKET Version=\"2.0\">\n";
	$xml_posto_fabrica .= "<METADATA>\n";
	$xml_posto_fabrica .= "<FIELDS>\n";
	$xml_posto_fabrica .= "<FIELD attrname=\"posto\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_posto_fabrica .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_posto_fabrica .= "<FIELD attrname=\"codigo_posto\" fieldtype=\"string\" WIDTH=\"20\"/>\n";
	$xml_posto_fabrica .= "<FIELD attrname=\"endereco_cobranca\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
	$xml_posto_fabrica .= "<FIELD attrname=\"numero_cobranca\" fieldtype=\"string\" WIDTH=\"10\"/>\n";
	$xml_posto_fabrica .= "<FIELD attrname=\"complemento_cobranca\" fieldtype=\"string\" WIDTH=\"20\"/>\n";
	$xml_posto_fabrica .= "<FIELD attrname=\"bairro_cobranca\" fieldtype=\"string\" WIDTH=\"40\"/>\n";
	$xml_posto_fabrica .= "<FIELD attrname=\"cep_cobranca\" fieldtype=\"string\" WIDTH=\"8\"/>\n";
	$xml_posto_fabrica .= "<FIELD attrname=\"cidade_cobranca\" fieldtype=\"string\" WIDTH=\"30\"/>\n";
	$xml_posto_fabrica .= "<FIELD attrname=\"estado_cobranca\" fieldtype=\"string\" WIDTH=\"2\"/>\n";
	$xml_posto_fabrica .= "<FIELD attrname=\"pedido_em_garantia\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_posto_fabrica .= "<FIELD attrname=\"pedido_faturado\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_posto_fabrica .= "<FIELD attrname=\"reembolso_peca_estoque\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_posto_fabrica .= "<FIELD attrname=\"digita_os\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_posto_fabrica .= "<FIELD attrname=\"item_aparencia\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_posto_fabrica .= "<FIELD attrname=\"ultima_sua_os_offline\" fieldtype=\"string\" WIDTH=\"20\"/>\n";
	$xml_posto_fabrica .= "</FIELDS>\n";
	$xml_posto_fabrica .= "</METADATA>\n";
	$xml_posto_fabrica .= "<ROWDATA>\n";

	$posto                   = "";
	$fabrica                 = "";
	$codigo_posto            = "";
	$cobranca_endereco       = "";
	$cobranca_numero         = "";
	$cobranca_complemento    = "";
	$cobranca_bairro         = "";
	$cobranca_cep            = "";
	$cobranca_cidade         = "";
	$cobranca_estado         = "";
	$pedido_em_garantia      = "";
	$pedido_faturado         = "";
	$reembolso_peca_estoque  = "";
	$digita_os               = "";
	$item_aparencia          = "";
	$ultima_sua_os_offline   = "";

	if (pg_numrows($res) > 0) {
		$contador = 0;
		while ($ln_posto_fabrica = pg_fetch_array($res)){
			$contador++;
			$posto                   = $ln_posto_fabrica['posto'];
			$fabrica                 = $ln_posto_fabrica['fabrica'];
			$codigo_posto            = $ln_posto_fabrica['codigo_posto'];
			$cobranca_endereco       = $ln_posto_fabrica['cobranca_endereco'];
			$cobranca_numero         = $ln_posto_fabrica['cobranca_numero'];
			$cobranca_complemento    = $ln_posto_fabrica['cobranca_complemento'];
			$cobranca_bairro         = $ln_posto_fabrica['cobranca_bairro'];
			$cobranca_cep            = $ln_posto_fabrica['cobranca_cep'];
			$cobranca_cidade         = $ln_posto_fabrica['cobranca_cidade'];
			$cobranca_estado         = $ln_posto_fabrica['cobranca_estado'];
			$pedido_em_garantia      = $ln_posto_fabrica['pedido_em_garantia'];
			$pedido_faturado         = $ln_posto_fabrica['pedido_faturado'];
			$reembolso_peca_estoque  = $ln_posto_fabrica['reembolso_peca_estoque'];
			$digita_os               = $ln_posto_fabrica['digita_os'];
			$item_aparencia          = $ln_posto_fabrica['item_aparencia'];
			$ultima_sua_os_offline   = $ln_posto_fabrica['sua_os_offline'];

			if (strlen($pedido_em_garantia) == 0)     $pedido_em_garantia     = "f";
			if (strlen($pedido_faturado) == 0)        $pedido_faturado        = "t";
			if (strlen($reembolso_peca_estoque) == 0) $reembolso_peca_estoque = "f";
			if (strlen($digita_os) == 0)              $digita_os              = "t";
			if (strlen($item_aparencia) == 0)         $item_aparencia         = "f";

			$xml_posto_fabrica .= "<ROW RowState=\"4\" posto=\"$posto\" fabrica=\"$fabrica\" codigo_posto=\"$codigo_posto\" cobranca_endereco=\"$cobranca_endereco\" cobranca_numero=\"$cobranca_numero\" cobranca_complemento=\"$cobranca_complemento\" cobranca_bairro=\"$cobranca_bairro\" cobranca_cep=\"$cobranca_cep\" cobranca_cidade=\"$cobranca_cidade\" cobranca_estado=\"$cobranca_estado\" pedido_em_garantia=\"$pedido_em_garantia\" pedido_faturado=\"$pedido_faturado\" reembolso_peca_estoque=\"$reembolso_peca_estoque\" digita_os=\"$digita_os\" item_aparencia=\"$item_aparencia\" ultima_sua_os_offline=\"$ultima_sua_os_offline\"/>\n";
			flush();
		}
	}else{
		$xml_posto_fabrica .= "<ROW RowState=\"4\" posto=\"$posto\" fabrica=\"$fabrica\" codigo_posto=\"$codigo_posto\" cobranca_endereco=\"$cobranca_endereco\" cobranca_numero=\"$cobranca_numero\" cobranca_complemento=\"$cobranca_complemento\" cobranca_bairro=\"$cobranca_bairro\" cobranca_cep=\"$cobranca_cep\" cobranca_cidade=\"$cobranca_cidade\" cobranca_estado=\"$cobranca_estado\" pedido_em_garantia=\"$pedido_em_garantia\" pedido_faturado=\"$pedido_faturado\" reembolso_peca_estoque=\"$reembolso_peca_estoque\" digita_os=\"$digita_os\" item_aparencia=\"$item_aparencia\" ultima_sua_os_offline=\"$ultima_sua_os_offline\"/>\n";
		flush();
	}

	$xml_posto_fabrica .= "</ROWDATA>\n";
	$xml_posto_fabrica .= "</DATAPACKET>\n";

	// GERA XML DO ARQUIVO
	$file    = "xml/$cnpj/postofab.xml";
	$arquivo = fopen($file, "w+");
	fwrite($arquivo, $xml_posto_fabrica);
	fclose($arquivo);


	##################################################################################################################
	// STATUS DAS ORDENS DE SERVIÇO
	##################################################################################################################
	$sql = "SELECT  tbl_os.os                                    ,
					tbl_os.fabrica                                   ,
					tbl_os_status.status_os                          ,
					to_char(tbl_os_status.data, 'DD/MM/YYYY') AS data,
					tbl_os_status.observacao
			FROM    tbl_os
			JOIN    tbl_posto     ON tbl_posto.posto  = tbl_os.posto
			JOIN    tbl_os_status ON tbl_os_status.os = tbl_os.os
			WHERE   tbl_posto.cnpj = '$cnpj'
			AND     tbl_os.sua_os_offline NOTNULL
			AND     tbl_os_status.status_os IN (13,14,15) ";
	if (strlen($fabricante) > 0) $sql .= "AND tbl_os.fabrica IN ($fabricante) ";
	$sql .= "ORDER BY tbl_os_status.data;";
	$res = pg_exec($con,$sql);

	// cabeçalho
	$xml_status_os .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
	$xml_status_os .= "<DATAPACKET Version=\"2.0\">\n";
	$xml_status_os .= "<METADATA>\n";
	$xml_status_os .= "<FIELDS>\n";
	$xml_status_os .= "<FIELD attrname=\"os\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_status_os .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_status_os .= "<FIELD attrname=\"status_os\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_status_os .= "<FIELD attrname=\"data\" fieldtype=\"string\" WIDTH=\"10\"/>\n";
	$xml_status_os .= "<FIELD attrname=\"observacao\" fieldtype=\"string\" WIDTH=\"255\"/>\n";
	$xml_status_os .= "</FIELDS>\n";
	$xml_status_os .= "</METADATA>\n";
	$xml_status_os .= "<ROWDATA>\n";

	$os             = "";
	$sua_os_offline = "";
	$fabrica        = "";
	$status         = "";
	$data           = "";
	$observacao     = "";

	if (pg_numrows($res) > 0) {
		$contador = 0;

		while ($ln_status_os = pg_fetch_array($res)){
			$contador++;
			$os             = trim($ln_status_os['os']);
			$fabrica        = trim($ln_status_os['fabrica']);
			$status         = trim($ln_status_os['status_os']);
			$data           = trim($ln_status_os['data']);
			$observacao     = trim($ln_status_os['observacao']);

			$xml_status_os .= "<ROW RowState=\"4\" os=\"$os\" sua_os_offline=\"$sua_os_offline\" fabrica=\"$fabrica\" status_os=\"$status\" data=\"$data\" observacao=\"$observacao\"/>\n";
			flush();
		}
	}else{
		$xml_status_os .= "<!--<ROW RowState=\"4\" os=\"$os\" sua_os_offline=\"$sua_os_offline\" fabrica=\"$fabrica\" status_os=\"$status\" data=\"$data\" observacao=\"$observacao\"/>-->\n";
		flush();
	}

	$xml_status_os .= "</ROWDATA>\n";
	$xml_status_os .= "</DATAPACKET>\n";


	// GERA XML DO ARQUIVO
	$file    = "xml/$cnpj/os_status.xml";
	$arquivo = fopen($file, "w+");
	fwrite($arquivo, $xml_status_os);
	fclose($arquivo);



	##################################################################################################################
	// CONDIÇÃO DE PAGAMENTO
	##################################################################################################################
	if (pg_numrows($resX) > 0) {

		$contador = 0;
		for ($x=0; $x < pg_numrows($resX); $x++) {
			$fabricante_nome = pg_result($resX,$x,nome);

			//CRIA PASTA DO FABRICANTE
			if (!is_dir("xml/".$fabricante_nome)) {
				echo ` cd xml; mkdir $fabricante_nome; chmod 777 $fabricante_nome; cd $fabricante_nome; `;
			}else{
				echo ` cd xml; chmod 777 $fabricante_nome; cd $fabricante_nome; ` ;
			}

			$sql = "SELECT  trim(tbl_fabrica.fabrica)                          AS fabrica   ,
							trim(tbl_condicao.condicao)                        AS condicao  ,
							trim(tbl_condicao.codigo_condicao)                 AS codigo    ,
							trim(replace(tbl_condicao.descricao,'\"','\'\''))  AS descricao ,
							trim(tbl_condicao.tabela)                          AS tabela
					FROM    tbl_condicao
					JOIN    tbl_fabrica          ON tbl_fabrica.fabrica         = tbl_condicao.fabrica
					JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.fabrica   = tbl_fabrica.fabrica
					JOIN    tbl_posto            ON tbl_posto.posto             = tbl_posto_fabrica.posto
					JOIN    tbl_posto_condicao   ON tbl_posto_condicao.condicao = tbl_condicao.condicao
												AND tbl_posto_condicao.posto    = tbl_posto.posto
					WHERE   tbl_condicao.visivel       IS TRUE
					AND     tbl_posto_condicao.visivel IS TRUE
					AND     tbl_posto.cnpj = '$cnpj' ";
			if (strlen(pg_result($resX,$x,fabrica)) > 0) $sql .= "AND tbl_fabrica.fabrica IN (". pg_result($resX,$x,fabrica) .") ";
			$sql .= "ORDER BY lpad(tbl_condicao.codigo_condicao,10,0);";
			$res = pg_exec($con,$sql);

			$status = "f";

			if (pg_numrows($res) > 0) {
				if ($status == "f") {
					// cabeçalho
					$xml_condicao  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
					$xml_condicao .= "<DATAPACKET Version=\"2.0\">\n";
					$xml_condicao .= "<METADATA>\n";
					$xml_condicao .= "<FIELDS>\n";
					$xml_condicao .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
					$xml_condicao .= "<FIELD attrname=\"condicao\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
					$xml_condicao .= "<FIELD attrname=\"codigo\" fieldtype=\"string\" WIDTH=\"10\"/>\n";
					$xml_condicao .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"20\"/>\n";
					$xml_condicao .= "<FIELD attrname=\"tabela\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
					$xml_condicao .= "</FIELDS>\n";
					$xml_condicao .= "</METADATA>\n";
					$xml_condicao .= "<ROWDATA>\n";

					$status = "t";
				}

				while ($ln_condicao = pg_fetch_array($res)){
					$contador++;
					$fabrica    = $ln_condicao['fabrica'];
					$condicao   = $ln_condicao['condicao'];
					$codigo     = $ln_condicao['codigo'];
					$descricao  = $ln_condicao['descricao'];
					$tabela     = $ln_condicao['tabela'];

					$xml_condicao .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" condicao=\"$condicao\" codigo=\"$codigo\" descricao=\"$descricao\" tabela=\"$tabela\"/>\n";
					flush();
				}

				if ($status == "t") {
					$xml_condicao .= "</ROWDATA>\n";
					$xml_condicao .= "</DATAPACKET>\n";

					// GERA XML DO ARQUIVO
					$file    = "xml/$fabricante_nome/condicao.xml";
					$arquivo = fopen($file, "w+");
					fwrite($arquivo, $xml_condicao);
					fclose($arquivo);
				}

			}else{
				$sql = "SELECT  trim(tbl_fabrica.fabrica)                          AS fabrica   ,
								trim(tbl_fabrica.nome)                             AS nome      ,
								trim(tbl_condicao.condicao)                        AS condicao  ,
								trim(tbl_condicao.codigo_condicao)                 AS codigo    ,
								trim(replace(tbl_condicao.descricao,'\"','\'\''))  AS descricao ,
								trim(tbl_condicao.tabela)                          AS tabela
						FROM    tbl_condicao
						JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_condicao.fabrica
						WHERE   tbl_condicao.visivel IS TRUE ";
				if (strlen(pg_result($resX,$x,fabrica)) > 0) $sql .= "AND tbl_fabrica.fabrica IN (". pg_result($resX,$x,fabrica) .") ";
				$sql .= "ORDER BY tbl_fabrica.fabrica, lpad(tbl_condicao.codigo_condicao,10,0);";
				$res = pg_exec ($con,$sql);

				if ($status == "f") {
					// cabeçalho
					$xml_condicao  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
					$xml_condicao .= "<DATAPACKET Version=\"2.0\">\n";
					$xml_condicao .= "<METADATA>\n";
					$xml_condicao .= "<FIELDS>\n";
					$xml_condicao .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
					$xml_condicao .= "<FIELD attrname=\"condicao\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
					$xml_condicao .= "<FIELD attrname=\"codigo\" fieldtype=\"string\" WIDTH=\"10\"/>\n";
					$xml_condicao .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"20\"/>\n";
					$xml_condicao .= "<FIELD attrname=\"tabela\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
					$xml_condicao .= "</FIELDS>\n";
					$xml_condicao .= "</METADATA>\n";
					$xml_condicao .= "<ROWDATA>\n";

					$status = "t";
				}

				while ($ln_condicao = pg_fetch_array($res)){
					$contador++;
					$fabrica    = $ln_condicao['fabrica'];
					$condicao   = $ln_condicao['condicao'];
					$codigo     = $ln_condicao['codigo'];
					$descricao  = $ln_condicao['descricao'];
					$tabela     = $ln_condicao['tabela'];

					$xml_condicao .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" condicao=\"$condicao\" codigo=\"$codigo\" descricao=\"$descricao\" tabela=\"$tabela\"/>\n";
					flush();
				}

				if ($status == "t") {
					$xml_condicao .= "</ROWDATA>\n";
					$xml_condicao .= "</DATAPACKET>\n";

					// GERA XML DO ARQUIVO
					$file    = "xml/$fabricante_nome/condicao.xml";
					$arquivo = fopen($file, "w+");
					fwrite($arquivo, $xml_condicao);
					fclose($arquivo);
				}
			}
		}
	}


	##################################################################################################################
	// TIPO DE PEDIDO
	##################################################################################################################
	if (pg_numrows($resX) > 0) {

		$contador = 0;
		for ($x=0; $x < pg_numrows($resX); $x++) {

			$fabricante_nome = pg_result($resX,$x,nome);

			//CRIA PASTA DO FABRICANTE
			if (!is_dir("xml/".$fabricante_nome)) {
				echo ` cd xml; mkdir $fabricante_nome; chmod 777 $fabricante_nome; cd $fabricante_nome; `;
			}else{
				echo ` cd xml; chmod 777 $fabricante_nome; cd $fabricante_nome; ` ;
			}

			$status = "f";

			if (pg_result($resX,$x,pedido_em_garantia) == "t") {
				$sql = "SELECT  trim(tbl_fabrica.fabrica)                             AS fabrica    ,
								trim(tbl_tipo_pedido.tipo_pedido)                     AS tipo_pedido,
								trim(tbl_tipo_pedido.codigo)                          AS codigo     ,
								trim(replace(tbl_tipo_pedido.descricao,'\"','\'\''))  AS descricao
						FROM    tbl_tipo_pedido
						JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_tipo_pedido.fabrica ";
				if (strlen(pg_result($resX,$x,fabrica)) > 0) $sql .= "AND tbl_fabrica.fabrica IN (". pg_result($resX,$x,fabrica) .") ";
				$sql .= "ORDER BY tbl_fabrica.fabrica, lpad(tbl_tipo_pedido.codigo,10,0);";
				$res = pg_exec($con,$sql);

				if (pg_numrows($res) > 0) {
					if ($status == "f") {
						// cabeçalho
						$xml_tipo_pedido  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
						$xml_tipo_pedido .= "<DATAPACKET Version=\"2.0\">\n";
						$xml_tipo_pedido .= "<METADATA>\n";
						$xml_tipo_pedido .= "<FIELDS>\n";
						$xml_tipo_pedido .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
						$xml_tipo_pedido .= "<FIELD attrname=\"tipo_pedido\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
						$xml_tipo_pedido .= "<FIELD attrname=\"codigo\" fieldtype=\"string\" WIDTH=\"10\"/>\n";
						$xml_tipo_pedido .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"30\"/>\n";
						$xml_tipo_pedido .= "</FIELDS>\n";
						$xml_tipo_pedido .= "</METADATA>\n";
						$xml_tipo_pedido .= "<ROWDATA>\n";

						$status = "t";
					}

					while ($ln_tipo_pedido = pg_fetch_array($res)){
						$contador++;
						$fabrica     = $ln_tipo_pedido['fabrica'];
						$tipo_pedido = $ln_tipo_pedido['tipo_pedido'];
						$codigo      = $ln_tipo_pedido['codigo'];
						$descricao   = $ln_tipo_pedido['descricao'];

						$xml_tipo_pedido .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" tipo_pedido=\"$tipo_pedido\" codigo=\"$codigo\" descricao=\"$descricao\"/>\n";
						flush();
					}

					if ($status == "t") {
						$xml_tipo_pedido .= "</ROWDATA>\n";
						$xml_tipo_pedido .= "</DATAPACKET>\n";

						// GERA XML DO ARQUIVO
						$file    = "xml/$fabricante_nome/tipoped.xml";
						$arquivo = fopen($file, "w+");
						fwrite($arquivo, $xml_tipo_pedido);
						fclose($arquivo);
					}
				}
			}else{
				$sql = "SELECT  trim(tbl_fabrica.fabrica)                             AS fabrica    ,
								trim(tbl_tipo_pedido.tipo_pedido)                     AS tipo_pedido,
								trim(tbl_tipo_pedido.codigo)                          AS codigo     ,
								trim(replace(tbl_tipo_pedido.descricao,'\"','\'\''))  AS descricao
						FROM    tbl_tipo_pedido
						JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_tipo_pedido.fabrica ";
				if (strlen(pg_result($resX,$x,fabrica)) > 0) $sql .= "AND tbl_fabrica.fabrica IN (". pg_result($resX,$x,fabrica) .") ";
				$sql .= "AND   (tbl_tipo_pedido.descricao ILIKE '%Faturado%' OR tbl_tipo_pedido.descricao ILIKE '%Venda%')
						ORDER BY tbl_fabrica.fabrica, lpad(tbl_tipo_pedido.codigo,10,0);";
				$res = pg_exec($con,$sql);

				if ($status == "f") {
					// cabeçalho
					$xml_tipo_pedido  = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
					$xml_tipo_pedido .= "<DATAPACKET Version=\"2.0\">\n";
					$xml_tipo_pedido .= "<METADATA>\n";
					$xml_tipo_pedido .= "<FIELDS>\n";
					$xml_tipo_pedido .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
					$xml_tipo_pedido .= "<FIELD attrname=\"tipo_pedido\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
					$xml_tipo_pedido .= "<FIELD attrname=\"codigo\" fieldtype=\"string\" WIDTH=\"10\"/>\n";
					$xml_tipo_pedido .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"30\"/>\n";
					$xml_tipo_pedido .= "</FIELDS>\n";
					$xml_tipo_pedido .= "</METADATA>\n";
					$xml_tipo_pedido .= "<ROWDATA>\n";

					$status = "t";
				}

				while ($ln_tipo_pedido = pg_fetch_array($res)){
					$contador++;
					$fabrica     = $ln_tipo_pedido['fabrica'];
					$tipo_pedido = $ln_tipo_pedido['tipo_pedido'];
					$codigo      = $ln_tipo_pedido['codigo'];
					$descricao   = $ln_tipo_pedido['descricao'];

					$xml_tipo_pedido .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" tipo_pedido=\"$tipo_pedido\" codigo=\"$codigo\" descricao=\"$descricao\"/>\n";
					flush();
				}

				if ($status == "t") {
					$xml_tipo_pedido .= "</ROWDATA>\n";
					$xml_tipo_pedido .= "</DATAPACKET>\n";

					// GERA XML DO ARQUIVO
					$file    = "xml/$fabricante_nome/tipoped.xml";
					$arquivo = fopen($file, "w+");
					fwrite($arquivo, $xml_tipo_pedido);
					fclose($arquivo);
				}
			}
		}
	}


	###############################################################################################################
	// GERA ARQUIVO ZIP PARA DOWNLOAD
	###############################################################################################################
	$controle = `date +%j%M%S`;
	$arq      = $cnpj . substr($controle,0,7);
	$x        = `cd xml/$cnpj; $links_fabricantes ; ln -s /var/www/assist/www/xml/geracao.txt geracao.txt; rm -rf *.zip; zip -o $arq.zip *.xml geracao.txt logos/* $fabricantes`;

	echo "<!--ARQUIVO-I-->$arq.zip<!--ARQUIVO-F-->";
}else
	echo "<!--OFFLINE-I-->SISTEMA NÃO LIBERADO<!--OFFLINE-F-->";

?>