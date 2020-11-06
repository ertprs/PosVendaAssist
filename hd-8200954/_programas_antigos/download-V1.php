<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

// CNPJ   = 05131296000101
// DÍGITO = 5 * 9 = 45


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
					tbl_fabrica.pedido_escolhe_condicao        ,
					tbl_fabrica.defeito_constatado_por_familia ,
					tbl_posto_fabrica.pedido_em_garantia       ,
					tbl_fabrica.logo
		FROM        tbl_posto
		JOIN        tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
		JOIN        tbl_fabrica       ON tbl_fabrica.fabrica     = tbl_posto_fabrica.fabrica
		WHERE       tbl_posto.cnpj                   = '$cnpj'
		AND         tbl_fabrica.sistema_offline     IS TRUE
		ORDER BY    tbl_fabrica.fabrica;";

//		AND         tbl_posto_fabrica.credenciamento = 'CREDENCIADO'

$resX = pg_exec($con,$sql);

if (pg_numrows($resX) > 0) {
	for ($x=0; $x < pg_numrows($resX); $x++) {
		$fabricante .= pg_result($resX,$x,fabrica);
		if ($x+1 < pg_numrows($resX)) $fabricante .= ",";
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
					trim(tbl_posto.fantasia)                       AS fantasia            ,
					trim(tbl_posto.fone)                           AS telefone            ,
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
			WHERE   tbl_posto.cnpj = '$cnpj'
			LIMIT 1;";
	$res = pg_exec($con,$sql);

	// cabeçalho
	$xml_posto .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
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
	
	if (pg_numrows($res) > 0) {
		$contador = 0;
		while ($ln_posto = pg_fetch_array($res)){
			$contador++;
			$cnpj                 = $ln_posto['cnpj'];
			$nome                 = str_replace('&','E', $ln_posto['nome']);
			$ie                   = $ln_posto['ie'];
			$fantasia             = $ln_posto['fantasia'];
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
			flush();
		}
	}else{
		$xml_posto .= "<ROW RowState=\"4\" cnpj=\"$cnpj\" nome=\"$nome\" ie=\"$ie\" fantasia=\"$fantasia\" fone=\"$fone\" fax=\"$fax\" contato=\"$contato\" email=\"$email\" situado=\"$situado\" suframa=\"$suframa\" obs=\"$obs\" endereco=\"$endereco\" numero=\"$numero\" complemento=\"$complemento\" bairro=\"$bairro\" cep=\"$cep\" cidade=\"$cidade\" estado=\"$estado\"/>\n";
		flush();
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
	$xml_fabrica .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
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
					tbl_posto_fabrica.item_aparencia
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
			
			if (strlen($pedido_em_garantia) == 0)     $pedido_em_garantia     = "f";
			if (strlen($pedido_faturado) == 0)        $pedido_faturado        = "t";
			if (strlen($reembolso_peca_estoque) == 0) $reembolso_peca_estoque = "f";
			if (strlen($digita_os) == 0)              $digita_os              = "t";
			if (strlen($item_aparencia) == 0)         $item_aparencia         = "f";
			
			$xml_posto_fabrica .= "<ROW RowState=\"4\" posto=\"$posto\" fabrica=\"$fabrica\" codigo_posto=\"$codigo_posto\" cobranca_endereco=\"$cobranca_endereco\" cobranca_numero=\"$cobranca_numero\" cobranca_complemento=\"$cobranca_complemento\" cobranca_bairro=\"$cobranca_bairro\" cobranca_cep=\"$cobranca_cep\" cobranca_cidade=\"$cobranca_cidade\" cobranca_estado=\"$cobranca_estado\" pedido_em_garantia=\"$pedido_em_garantia\" pedido_faturado=\"$pedido_faturado\" reembolso_peca_estoque=\"$reembolso_peca_estoque\" digita_os=\"$digita_os\" item_aparencia=\"$item_aparencia\"/>\n";
			flush();
		}
	}else{
		$xml_posto_fabrica .= "<ROW RowState=\"4\" posto=\"$posto\" fabrica=\"$fabrica\" codigo_posto=\"$codigo_posto\" cobranca_endereco=\"$cobranca_endereco\" cobranca_numero=\"$cobranca_numero\" cobranca_complemento=\"$cobranca_complemento\" cobranca_bairro=\"$cobranca_bairro\" cobranca_cep=\"$cobranca_cep\" cobranca_cidade=\"$cobranca_cidade\" cobranca_estado=\"$cobranca_estado\" pedido_em_garantia=\"$pedido_em_garantia\" pedido_faturado=\"$pedido_faturado\" reembolso_peca_estoque=\"$reembolso_peca_estoque\" digita_os=\"$digita_os\" item_aparencia=\"$item_aparencia\"/>\n";
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
	// CONDIÇÃO DE PAGAMENTO
	##################################################################################################################
	if (pg_numrows($resX) > 0) {
		$status = "f";
		
		$contador = 0;
		for ($x=0; $x < pg_numrows($resX); $x++) {
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
			$sql .= "ORDER BY tbl_fabrica.fabrica, lpad(tbl_condicao.codigo_condicao,10,0);";
			$res = pg_exec($con,$sql);
			
			if (pg_numrows($res) > 0) {
				if ($status == "f") {
					// cabeçalho
					$xml_condicao .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
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
			}else{
				$sql = "SELECT  trim(tbl_fabrica.fabrica)                          AS fabrica   ,
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
				
				if (pg_numrows($res) > 0) {
					if ($status == "f") {
						// cabeçalho
						$xml_condicao .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
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
				}
			}
		}
		
		if ($status == "t") {
			$xml_condicao .= "</ROWDATA>\n";
			$xml_condicao .= "</DATAPACKET>\n";
			
			// GERA XML DO ARQUIVO
			$file    = "xml/$cnpj/condicao.xml";
			$arquivo = fopen($file, "w+");
			fwrite($arquivo, $xml_condicao);
			fclose($arquivo);
		}
	}
	
	
	##################################################################################################################
	// TIPO DE PEDIDO
	##################################################################################################################
	if (pg_numrows($resX) > 0) {
		$status = "f";
		
		$contador = 0;
		for ($x=0; $x < pg_numrows($resX); $x++) {
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
						$xml_tipo_pedido .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
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
				
				if (pg_numrows($res) > 0) {
					if ($status == "f") {
						// cabeçalho
						$xml_tipo_pedido .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
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
				}
			}
		}
		
		if ($status == "t") {
			$xml_tipo_pedido .= "</ROWDATA>\n";
			$xml_tipo_pedido .= "</DATAPACKET>\n";
			
			// GERA XML DO ARQUIVO
			$file    = "xml/$cnpj/tipoped.xml";
			$arquivo = fopen($file, "w+");
			fwrite($arquivo, $xml_tipo_pedido);
			fclose($arquivo);
		}
	}
	
	
	##################################################################################################################
	// LINHA
	##################################################################################################################
	$sql = "SELECT  trim(tbl_fabrica.fabrica)                  AS fabrica    ,
					trim(tbl_linha.linha)                      AS linha      ,
					trim(tbl_linha.codigo_linha)               AS codigo     ,
					trim(replace(tbl_linha.nome,'\"','\'\''))  AS descricao
			FROM    tbl_linha
			JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_linha.fabrica ";
	if (strlen($fabricante) > 0) $sql .= "WHERE tbl_fabrica.fabrica IN ($fabricante) ";
	$sql .= "ORDER BY tbl_fabrica.fabrica, lpad(tbl_linha.codigo_linha,10,0);";
	$res = pg_exec($con,$sql);
	
	// cabeçalho
	$xml_linha .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
	$xml_linha .= "<DATAPACKET Version=\"2.0\">\n";
	$xml_linha .= "<METADATA>\n";
	$xml_linha .= "<FIELDS>\n";
	$xml_linha .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_linha .= "<FIELD attrname=\"linha\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_linha .= "<FIELD attrname=\"codigo\" fieldtype=\"string\" WIDTH=\"20\"/>\n";
	$xml_linha .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
	$xml_linha .= "</FIELDS>\n";
	$xml_linha .= "</METADATA>\n";
	$xml_linha .= "<ROWDATA>\n";
	
	$fabrica     = "";
	$linha       = "";
	$codigo      = "";
	$descricao   = "";
	
	if (pg_numrows($res) > 0) {
		$contador = 0;
		while ($ln_linha = pg_fetch_array($res)){
			$contador++;
			$fabrica     = $ln_linha['fabrica'];
			$linha       = $ln_linha['linha'];
			$codigo      = $ln_linha['codigo'];
			$descricao   = $ln_linha['descricao'];
			
			$xml_linha .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" linha=\"$linha\" codigo=\"$codigo\" descricao=\"$descricao\"/>\n";
			flush();
		}
	}else{
		$xml_linha .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" linha=\"$linha\" codigo=\"$codigo\" descricao=\"$descricao\"/>\n";
		flush();
	}
	
	$xml_linha .= "</ROWDATA>\n";
	$xml_linha .= "</DATAPACKET>\n";
	
	
	// GERA XML DO ARQUIVO
	$file    = "xml/$cnpj/linha.xml";
	$arquivo = fopen($file, "w+");
	fwrite($arquivo, $xml_linha);
	fclose($arquivo);
	
	
	##################################################################################################################
	// FAMILIA
	##################################################################################################################
	$sql = "SELECT  trim(tbl_fabrica.fabrica)                         AS fabrica    ,
					trim(tbl_familia.familia)                         AS familia    ,
					trim(tbl_familia.codigo_familia)                  AS codigo     ,
					trim(replace(tbl_familia.descricao,'\"','\'\''))  AS descricao
			FROM    tbl_familia
			JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_familia.fabrica ";
	if (strlen($fabricante) > 0) $sql .= "WHERE tbl_fabrica.fabrica IN ($fabricante) ";
	$sql .= "ORDER BY tbl_fabrica.fabrica, lpad(tbl_familia.codigo_familia,10,0);";
	$res = pg_exec($con,$sql);
	
	// cabeçalho
	$xml_familia .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
	$xml_familia .= "<DATAPACKET Version=\"2.0\">\n";
	$xml_familia .= "<METADATA>\n";
	$xml_familia .= "<FIELDS>\n";
	$xml_familia .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_familia .= "<FIELD attrname=\"familia\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_familia .= "<FIELD attrname=\"codigo\" fieldtype=\"string\" WIDTH=\"20\"/>\n";
	$xml_familia .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
	$xml_familia .= "</FIELDS>\n";
	$xml_familia .= "</METADATA>\n";
	$xml_familia .= "<ROWDATA>\n";
	
	$fabrica     = "";
	$familia     = "";
	$codigo      = "";
	$descricao   = "";
	
	if (pg_numrows($res) > 0) {
		$contador = 0;
		while ($ln_familia = pg_fetch_array($res)){
			$contador++;
			$fabrica     = $ln_familia['fabrica'];
			$familia     = $ln_familia['familia'];
			$codigo      = $ln_familia['codigo'];
			$descricao   = $ln_familia['descricao'];
			
			$xml_familia .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" familia=\"$familia\" codigo=\"$codigo\" descricao=\"$descricao\"/>\n";
			flush();
		}
	}else{
		$xml_familia .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" familia=\"$familia\" codigo=\"$codigo\" descricao=\"$descricao\"/>\n";
		flush();
	}
	
	$xml_familia .= "</ROWDATA>\n";
	$xml_familia .= "</DATAPACKET>\n";
	
	
	// GERA XML DO ARQUIVO
	$file    = "xml/$cnpj/familia.xml";
	$arquivo = fopen($file, "w+");
	fwrite($arquivo, $xml_familia);
	fclose($arquivo);
	
	
	##################################################################################################################
	// PEÇA
	##################################################################################################################
	$sql = "SELECT  trim(tbl_fabrica.fabrica)                      AS fabrica    ,
					trim(tbl_peca.peca)                            AS peca       ,
					trim(tbl_peca.referencia)                      AS referencia ,
					trim(replace(tbl_peca.descricao,'\"','\'\''))  AS descricao  ,
					trim(tbl_peca.origem)                          AS origem
			FROM    tbl_peca
			JOIN    tbl_fabrica      ON tbl_fabrica.fabrica      = tbl_peca.fabrica
			JOIN    tbl_lista_basica ON tbl_lista_basica.peca    = tbl_peca.peca
									AND tbl_lista_basica.fabrica IN ($fabricante)
									AND tbl_lista_basica.fabrica = tbl_fabrica.fabrica
			WHERE   tbl_peca.ativo IS TRUE ";
	if (strlen($fabricante) > 0) $sql .= "AND tbl_fabrica.fabrica IN ($fabricante) ";
	$sql .= "GROUP BY   tbl_fabrica.fabrica,
						tbl_peca.peca      ,
						tbl_peca.referencia,
						tbl_peca.descricao ,
						tbl_peca.origem
			ORDER BY    tbl_fabrica.fabrica,
						lpad(tbl_peca.referencia,20,0);";
	$res = pg_exec($con,$sql);
	
	// cabeçalho
	$xml_peca .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
	$xml_peca .= "<DATAPACKET Version=\"2.0\">\n";
	$xml_peca .= "<METADATA>\n";
	$xml_peca .= "<FIELDS>\n";
	$xml_peca .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_peca .= "<FIELD attrname=\"peca\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_peca .= "<FIELD attrname=\"referencia\" fieldtype=\"string\" WIDTH=\"20\"/>\n";
	$xml_peca .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
	$xml_peca .= "<FIELD attrname=\"qtde_pedido\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_peca .= "</FIELDS>\n";
	$xml_peca .= "</METADATA>\n";
	$xml_peca .= "<ROWDATA>\n";
	
	$fabrica     = "";
	$peca        = "";
	$referencia  = "";
	$descricao   = "";
	$origem      = "";
	
	if (pg_numrows($res) > 0) {
		$contador = 0;
		while ($ln_peca = pg_fetch_array($res)){
			$contador++;
			$fabrica     = $ln_peca['fabrica'];
			$peca        = $ln_peca['peca'];
			$referencia  = $ln_peca['referencia'];
			$descricao   = $ln_peca['descricao'];
			$origem      = $ln_peca['origem'];
			
			$xml_peca .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" peca=\"$peca\" referencia=\"$referencia\" descricao=\"$descricao\" qtde_pedido=\"\"/>\n";
			flush();
		}
	}else{
		$xml_peca .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" peca=\"$peca\" referencia=\"$referencia\" descricao=\"$descricao\" qtde_pedido=\"\"/>\n";
		flush();
	}
	
	$xml_peca .= "</ROWDATA>\n";
	$xml_peca .= "</DATAPACKET>\n";
	
	
	// GERA XML DO ARQUIVO
	$file    = "xml/$cnpj/peca.xml";
	$arquivo = fopen($file, "w+");
	fwrite($arquivo, $xml_peca);
	fclose($arquivo);
	
	
	##################################################################################################################
	// LISTA BÁSICA
	##################################################################################################################
	$sql = "SELECT  trim(tbl_fabrica.fabrica)                      AS fabrica    ,
					trim(tbl_produto.produto)                      AS produto    ,
					trim(tbl_peca.peca)                            AS peca       ,
					trim(tbl_peca.referencia)                      AS referencia ,
					trim(replace(tbl_peca.descricao,'\"','\'\''))  AS descricao  ,
					trim(tbl_peca.origem)                          AS origem     ,
					trim(tbl_lista_basica.type)                    AS type       ,
					trim(tbl_lista_basica.posicao)                 AS posicao    ,
					trim(tbl_lista_basica.qtde)                    AS qtde
			FROM    tbl_fabrica
			JOIN    tbl_lista_basica ON tbl_lista_basica.fabrica = tbl_fabrica.fabrica
			JOIN    tbl_produto      ON tbl_produto.produto      = tbl_lista_basica.produto
			JOIN    tbl_linha        ON tbl_linha.linha          = tbl_produto.linha
									AND tbl_linha.fabrica        = tbl_fabrica.fabrica
			JOIN    tbl_peca         ON tbl_peca.peca            = tbl_lista_basica.peca
									AND tbl_peca.fabrica         = tbl_fabrica.fabrica
			WHERE   tbl_peca.ativo    IS TRUE
			AND     tbl_produto.ativo IS TRUE ";
	if (strlen($fabricante) > 0) $sql .= "AND tbl_fabrica.fabrica IN ($fabricante) ";
	$sql .= "ORDER BY tbl_fabrica.fabrica, tbl_produto.produto, tbl_peca.peca;";
	$res = pg_exec($con,$sql);
	
	// cabeçalho
	$xml_lbm .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
	$xml_lbm .= "<DATAPACKET Version=\"2.0\">\n";
	$xml_lbm .= "<METADATA>\n";
	$xml_lbm .= "<FIELDS>\n";
	$xml_lbm .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_lbm .= "<FIELD attrname=\"produto\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_lbm .= "<FIELD attrname=\"peca\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_lbm .= "<FIELD attrname=\"referencia\" fieldtype=\"string\" WIDTH=\"20\"/>\n";
	$xml_lbm .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
	$xml_lbm .= "<FIELD attrname=\"origem\" fieldtype=\"string\" WIDTH=\"10\"/>\n";
	$xml_lbm .= "<FIELD attrname=\"type\" fieldtype=\"string\" WIDTH=\"10\"/>\n";
	$xml_lbm .= "<FIELD attrname=\"posicao\" fieldtype=\"string\" WIDTH=\"20\"/>\n";
	$xml_lbm .= "<FIELD attrname=\"qtde\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_lbm .= "<FIELD attrname=\"qtde_pedido\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_lbm .= "</FIELDS>\n";
	$xml_lbm .= "</METADATA>\n";
	$xml_lbm .= "<ROWDATA>\n";
	
	$fabrica     = "";
	$produto     = "";
	$peca        = "";
	$referencia  = "";
	$descricao   = "";
	$origem      = "";
	$type        = "";
	$posicao     = "";
	$qtde        = "";
	
	if (pg_numrows($res) > 0) {
		$contador = 0;
		while ($ln_lbm = pg_fetch_array($res)){
			$contador++;
			$fabrica     = $ln_lbm['fabrica'];
			$produto     = $ln_lbm['produto'];
			$peca        = $ln_lbm['peca'];
			$referencia  = $ln_lbm['referencia'];
			$descricao   = $ln_lbm['descricao'];
			$origem      = $ln_lbm['origem'];
			$type        = $ln_lbm['type'];
			$posicao     = $ln_lbm['posicao'];
			$qtde        = $ln_lbm['qtde'];
			
			flush();
			$xml_lbm .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" produto=\"$produto\" peca=\"$peca\" referencia=\"$referencia\" descricao=\"$descricao\" origem=\"$origem\" type=\"$type\" posicao=\"$posicao\" qtde=\"$qtde\" qtde_pedido=\"\"/>\n";
		}
	}else{
		$xml_lbm .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" produto=\"$produto\" peca=\"$peca\" referencia=\"$referencia\" descricao=\"$descricao\" origem=\"$origem\" type=\"$type\" posicao=\"$posicao\" qtde=\"$qtde\" qtde_pedido=\"\"/>\n";
		flush();
	}
	
	$xml_lbm .= "</ROWDATA>\n";
	$xml_lbm .= "</DATAPACKET>\n";
	
	
	// GERA XML DO ARQUIVO
	$file    = "xml/$cnpj/lbm.xml";
	$arquivo = fopen($file, "w+");
	fwrite($arquivo, $xml_lbm);
	fclose($arquivo);
	
	
	##################################################################################################################
	// PREÇOS (retirado por Wellington em 24/05/2006)
	##################################################################################################################
	/*if (pg_numrows($resX) > 0) {
		$status = "f";
		
		for ($x=0; $x < pg_numrows($resX); $x++) {
			$sql = "SELECT  trim(tbl_fabrica.fabrica)      AS fabrica    ,
							trim(tbl_condicao.condicao)    AS condicao   ,
							trim(tbl_tabela_item.peca)     AS peca       ,
							trim(tbl_tabela_item.preco)    AS preco
					FROM    tbl_fabrica
					JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
					JOIN    tbl_posto            ON tbl_posto.posto           = tbl_posto_fabrica.posto
					JOIN    tbl_tabela           ON tbl_tabela.fabrica        = tbl_fabrica.fabrica
					JOIN    tbl_tabela_item      ON tbl_tabela_item.tabela    = tbl_tabela.tabela
					JOIN    tbl_peca             ON tbl_peca.peca             = tbl_tabela_item.peca
												AND tbl_peca.fabrica          = tbl_fabrica.fabrica
					JOIN    tbl_condicao         ON tbl_condicao.tabela       = tbl_tabela.tabela
												AND tbl_condicao.fabrica      = tbl_fabrica.fabrica ";
			if (pg_result($resX,$x,pedido_escolhe_condicao) == 't') {
				$sql .= "JOIN tbl_posto_condicao ON tbl_posto_condicao.condicao = tbl_condicao.condicao
												AND tbl_posto_condicao.posto    = tbl_posto.posto ";
			}
			$sql .= "WHERE  tbl_peca.ativo       IS TRUE
					AND     tbl_condicao.visivel IS TRUE ";
			if (pg_result($resX,$x,pedido_escolhe_condicao) == 't') $sql .= "AND tbl_posto_condicao.visivel IS TRUE ";
			if (strlen(pg_result($resX,$x,fabrica)) > 0) $sql .= "AND tbl_fabrica.fabrica IN (". pg_result($resX,$x,fabrica) .") ";
			$sql .= "AND tbl_posto.cnpj = '$cnpj' ";
			$sql .= "ORDER BY tbl_fabrica.fabrica, tbl_tabela_item.preco;";
			$res = pg_exec($con,$sql);
			
			if (pg_numrows($res) > 0) {
				if ($status == "f") {
					// cabeçalho
					$xml_preco .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
					$xml_preco .= "<DATAPACKET Version=\"2.0\">\n";
					$xml_preco .= "<METADATA>\n";
					$xml_preco .= "<FIELDS>\n";
					$xml_preco .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
					$xml_preco .= "<FIELD attrname=\"condicao\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
					$xml_preco .= "<FIELD attrname=\"peca\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
					$xml_preco .= "<FIELD attrname=\"preco\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
					$xml_preco .= "</FIELDS>\n";
					$xml_preco .= "</METADATA>\n";
					$xml_preco .= "<ROWDATA>\n";
					
					$status = "t";
				}
				
				while ($ln_preco = pg_fetch_array($res)){
					$contador++;
					$fabrica     = $ln_preco['fabrica'];
					$condicao    = $ln_preco['condicao'];
					$peca        = $ln_preco['peca'];
					$preco       = number_format($ln_preco['preco'],2,",",".");
					
					flush();
					$xml_preco .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" condicao=\"$condicao\" peca=\"$peca\" preco=\"$preco\"/>\n";
				}
			}
		}
		
		if ($status == "t") {
			$xml_preco .= "</ROWDATA>\n";
			$xml_preco .= "</DATAPACKET>\n";
			
			// GERA XML DO ARQUIVO
			$file    = "xml/$cnpj/preco.xml";
			$arquivo = fopen($file, "w+");
			fwrite($arquivo, $xml_preco);
			fclose($arquivo);
		}
	}*/
	
	
	##################################################################################################################
	// PRODUTOS
	##################################################################################################################
	$sql = "SELECT      trim(tbl_fabrica.fabrica)                         AS fabrica   ,
						trim(tbl_produto.produto)                         AS produto   ,
						trim(tbl_produto.linha)                           AS linha     ,
						trim(tbl_produto.familia)                         AS familia   ,
						trim(tbl_produto.referencia)                      AS referencia,
						trim(replace(tbl_produto.descricao,'\"','\'\''))  AS descricao ,
						trim(tbl_produto.voltagem)                        AS voltagem  ,
						tbl_produto.garantia                                           ,
						tbl_produto.radical_serie                                      ,
						tbl_produto.numero_serie_obrigatorio                           ,
						tbl_produto.abre_os
			FROM        tbl_fabrica
			JOIN        tbl_linha        ON tbl_linha.fabrica        = tbl_fabrica.fabrica
			JOIN        tbl_produto      ON tbl_produto.linha        = tbl_linha.linha
			LEFT JOIN   tbl_lista_basica ON tbl_lista_basica.produto = tbl_produto.produto
										AND tbl_lista_basica.fabrica = tbl_fabrica.fabrica
										AND tbl_lista_basica.fabrica IN ($fabricante)
			LEFT JOIN   tbl_familia      ON tbl_familia.familia      = tbl_produto.familia
			WHERE       tbl_produto.ativo IS TRUE ";
	if (strlen($fabricante) > 0) $sql .= "AND (tbl_linha.fabrica IN ($fabricante) OR tbl_familia.fabrica IN ($fabricante)) ";
	$sql .= "GROUP BY   tbl_fabrica.fabrica                 ,
						tbl_produto.produto                 ,
						tbl_produto.linha                   ,
						tbl_produto.familia                 ,
						tbl_produto.referencia              ,
						tbl_produto.descricao               ,
						tbl_produto.voltagem                ,
						tbl_produto.garantia                ,
						tbl_produto.radical_serie           ,
						tbl_produto.numero_serie_obrigatorio,
						tbl_produto.abre_os
			ORDER BY    tbl_fabrica.fabrica,
						lpad(tbl_produto.referencia,20,0);";
	$res = pg_exec($con,$sql);
	
	// cabeçalho
	$xml_produto .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
	$xml_produto .= "<DATAPACKET Version=\"2.0\">\n";
	$xml_produto .= "<METADATA>\n";
	$xml_produto .= "<FIELDS>\n";
	$xml_produto .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_produto .= "<FIELD attrname=\"produto\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_produto .= "<FIELD attrname=\"linha\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_produto .= "<FIELD attrname=\"familia\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_produto .= "<FIELD attrname=\"referencia\" fieldtype=\"string\" WIDTH=\"20\"/>\n";
	$xml_produto .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
	$xml_produto .= "<FIELD attrname=\"voltagem\" fieldtype=\"string\" WIDTH=\"20\"/>\n";
	$xml_produto .= "<FIELD attrname=\"garantia\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_produto .= "<FIELD attrname=\"radical_serie\" fieldtype=\"string\" WIDTH=\"10\"/>\n";
	$xml_produto .= "<FIELD attrname=\"numero_serie_obrigatorio\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_produto .= "<FIELD attrname=\"abre_os\" fieldtype=\"string\" WIDTH=\"1\"/>\n";
	$xml_produto .= "</FIELDS>\n";
	$xml_produto .= "</METADATA>\n";
	$xml_produto .= "<ROWDATA>\n";
	
	$fabrica    = "";
	$produto    = "";
	$linha      = "";
	$familia    = "";
	$referencia = "";
	$descricao  = "";
	$voltagem   = "";
	$garantia   = "";
	$radical    = "";
	$serie_ob   = "";
	$abre_os    = "";
	
	if (pg_numrows($res) > 0) {
		$contador = 0;
		while ($ln_produto = pg_fetch_array($res)){
			$contador++;
			$fabrica    = trim($ln_produto['fabrica']);
			$produto    = trim($ln_produto['produto']);
			$linha      = trim($ln_produto['linha']);
			$familia    = trim($ln_produto['familia']);
			$referencia = trim($ln_produto['referencia']);
			$descricao  = trim($ln_produto['descricao']);
			$voltagem   = trim($ln_produto['voltagem']);
			$garantia   = trim($ln_produto['garantia']);
			$radical    = trim($ln_produto['radical']);
			$serie_ob   = trim($ln_produto['numero_serie_obrigatorio']);
			$abre_os    = trim($ln_produto['abre_os']);
			
			if (strlen($serie_ob) == 0) $serie_ob = "f";
			//if (strlen($abre_os) == 0)  $abre_os  = "f";
			
			$xml_produto .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" produto=\"$produto\" linha=\"$linha\" familia=\"$familia\" referencia=\"$referencia\" descricao=\"$descricao\" voltagem=\"$voltagem\" garantia=\"$garantia\" radical=\"$radical\" numero_serie_obrigatorio=\"$serie_ob\" abre_os=\"$abre_os\"/>\n";
			flush();
		}
	}else{
		$xml_produto .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" produto=\"$produto\" linha=\"$linha\" familia=\"$familia\" referencia=\"$referencia\" descricao=\"$descricao\" voltagem=\"$voltagem\" garantia=\"$garantia\" radical=\"$radical\" numero_serie_obrigatorio=\"$serie_ob\" abre_os=\"$abre_os\"/>\n";
		flush();
	}
	
	$xml_produto .= "</ROWDATA>\n";
	$xml_produto .= "</DATAPACKET>\n";
	
	
	// GERA XML DO ARQUIVO
	$file    = "xml/$cnpj/produto.xml";
	$arquivo = fopen($file, "w+");
	fwrite($arquivo, $xml_produto);
	fclose($arquivo);
	
	
	##################################################################################################################
	// SUB-PRODUTO
	##################################################################################################################
	$sql = "SELECT  trim(tbl_fabrica.fabrica)                         AS fabrica    ,
					trim(tbl_subproduto.produto_pai)                  AS produto_pai    ,
					trim(tbl_subproduto.produto_filho)                AS produto_filho
			FROM    tbl_subproduto
			JOIN    tbl_produto  ON tbl_produto.produto = tbl_subproduto.produto_pai
			JOIN    tbl_linha    ON tbl_linha.linha     = tbl_produto.linha
			JOIN    tbl_familia  ON tbl_familia.familia = tbl_produto.familia
 			JOIN    tbl_fabrica  ON tbl_fabrica.fabrica = tbl_linha.fabrica
								AND tbl_fabrica.fabrica = tbl_familia.fabrica
			WHERE   tbl_fabrica.os_item_subconjunto IS TRUE ";
	if (strlen($fabricante) > 0) $sql .= "AND tbl_fabrica.fabrica IN ($fabricante) ";
	$sql .= "ORDER BY tbl_fabrica.fabrica, tbl_subproduto.produto_pai, tbl_subproduto.produto_filho;";
	$res = pg_exec($con,$sql);
	
	// cabeçalho
	$xml_subproduto .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
	$xml_subproduto .= "<DATAPACKET Version=\"2.0\">\n";
	$xml_subproduto .= "<METADATA>\n";
	$xml_subproduto .= "<FIELDS>\n";
	$xml_subproduto .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_subproduto .= "<FIELD attrname=\"produto_pai\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_subproduto .= "<FIELD attrname=\"produto_filho\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_subproduto .= "</FIELDS>\n";
	$xml_subproduto .= "</METADATA>\n";
	$xml_subproduto .= "<ROWDATA>\n";
	
	$fabrica       = "";
	$produto_pai   = "";
	$produto_filho = "";
	
	if (pg_numrows($res) > 0) {
		$contador = 0;
		while ($ln_subproduto = pg_fetch_array($res)){
			$contador++;
			$fabrica       = $ln_subproduto['fabrica'];
			$produto_pai   = $ln_subproduto['produto_pai'];
			$produto_filho = $ln_subproduto['produto_filho'];
			
			$xml_subproduto .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" produto_pai=\"$produto_pai\" produto_filho=\"$produto_filho\"/>\n";
			flush();
		}
	}else{
		$xml_subproduto .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" produto_pai=\"$produto_pai\" produto_filho=\"$produto_filho\"/>\n";
		flush();
	}
	
	$xml_subproduto .= "</ROWDATA>\n";
	$xml_subproduto .= "</DATAPACKET>\n";
	
	
	// GERA XML DO ARQUIVO
	$file    = "xml/$cnpj/subproduto.xml";
	$arquivo = fopen($file, "w+");
	fwrite($arquivo, $xml_subproduto);
	fclose($arquivo);
	
	
	##################################################################################################################
	// DEFEITO RECLAMADO
	##################################################################################################################
	$fabricas = explode("," , $fabricante);
	
	// cabeçalho
	$xml_defeito_reclamado .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
	$xml_defeito_reclamado .= "<DATAPACKET Version=\"2.0\">\n";
	$xml_defeito_reclamado .= "<METADATA>\n";
	$xml_defeito_reclamado .= "<FIELDS>\n";
	$xml_defeito_reclamado .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_defeito_reclamado .= "<FIELD attrname=\"produto\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_defeito_reclamado .= "<FIELD attrname=\"linha\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_defeito_reclamado .= "<FIELD attrname=\"familia\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_defeito_reclamado .= "<FIELD attrname=\"defeito_reclamado\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_defeito_reclamado .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
	$xml_defeito_reclamado .= "</FIELDS>\n";
	$xml_defeito_reclamado .= "</METADATA>\n";
	$xml_defeito_reclamado .= "<ROWDATA>\n";
	
	for ($k=0; $k < count($fabricas); $k++) {
		$fabricas [$k] = trim ($fabricas [$k]);
		$fabrica = $fabricas [$k];
		
		$gerado = "nao";
		
		### DEFEITOS POR FAMILIA
		$sql = "SELECT      trim(tbl_fabrica.fabrica)                                  AS fabrica          ,
							trim(tbl_defeito_reclamado.linha)                          AS linha            ,
							trim(tbl_familia.familia)                                  AS familia          ,
							trim(tbl_defeito_reclamado.defeito_reclamado)              AS defeito_reclamado,
							trim(replace(tbl_defeito_reclamado.descricao,'\"','\'\'')) AS descricao
				FROM        tbl_fabrica
				JOIN        tbl_familia              ON tbl_familia.fabrica           = tbl_fabrica.fabrica
				JOIN        tbl_defeito_reclamado    ON tbl_defeito_reclamado.familia = tbl_familia.familia
				WHERE       tbl_fabrica.fabrica = $fabrica
				ORDER BY    tbl_defeito_reclamado.defeito_reclamado, lpad(tbl_defeito_reclamado.descricao,50,0);";
		$res = pg_exec($con,$sql);
		
		if (pg_numrows($res) > 0) {
			$fabrica           = "";
			$produto           = "";
			$linha             = "";
			$familia           = "";
			$defeito_reclamado = "";
			$descricao         = "";
			
			$contador = 0;
			while ($ln_defeito_reclamado = pg_fetch_array($res)){
				$contador++;
				$fabrica           = trim($ln_defeito_reclamado['fabrica']);
				$linha             = trim($ln_defeito_reclamado['linha']);
				$familia           = trim($ln_defeito_reclamado['familia']);
				$defeito_reclamado = trim($ln_defeito_reclamado['defeito_reclamado']);
				$descricao         = trim($ln_defeito_reclamado['descricao']);
				
				$xml_defeito_reclamado .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" produto=\"$produto\" linha=\"$linha\" familia=\"$familia\" defeito_reclamado=\"$defeito_reclamado\" descricao=\"$descricao\"/>\n";
				flush();
			}
			
			$gerado = "sim";
		}
		
		if ($gerado == "nao") {
			### DEFEITOS POR LINHA
			$sql = "SELECT      trim(tbl_fabrica.fabrica)                                  AS fabrica          ,
								trim(tbl_defeito_reclamado.linha)                          AS linha            ,
								trim(tbl_familia.familia)                                  AS familia          ,
								trim(tbl_defeito_reclamado.defeito_reclamado)              AS defeito_reclamado,
								trim(replace(tbl_defeito_reclamado.descricao,'\"','\'\'')) AS descricao
					FROM        tbl_fabrica
					JOIN        tbl_linha                ON tbl_linha.fabrica             = tbl_fabrica.fabrica
					JOIN        tbl_defeito_reclamado    ON tbl_defeito_reclamado.linha   = tbl_linha.linha
					WHERE       tbl_fabrica.fabrica = $fabrica
					ORDER BY    tbl_defeito_reclamado.defeito_reclamado, lpad(tbl_defeito_reclamado.descricao,50,0);";
			$res = pg_exec($con,$sql);
			
			if (pg_numrows($res) > 0) {
				$fabrica           = "";
				$produto           = "";
				$linha             = "";
				$familia           = "";
				$defeito_reclamado = "";
				$descricao         = "";
				
				$contador = 0;
				while ($ln_defeito_reclamado = pg_fetch_array($res)){
					$contador++;
					$fabrica           = trim($ln_defeito_reclamado['fabrica']);
					$linha             = trim($ln_defeito_reclamado['linha']);
					$familia           = trim($ln_defeito_reclamado['familia']);
					$defeito_reclamado = trim($ln_defeito_reclamado['defeito_reclamado']);
					$descricao         = trim($ln_defeito_reclamado['descricao']);
					
					$xml_defeito_reclamado .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" produto=\"$produto\" linha=\"$linha\" familia=\"$familia\" defeito_reclamado=\"$defeito_reclamado\" descricao=\"$descricao\"/>\n";
					flush();
				}
				
				$gerado = "sim";
			}
		}
		
		if ($gerado == "nao") {
			$xml_defeito_reclamado .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" produto=\"$produto\" linha=\"$linha\" familia=\"$familia\" defeito_reclamado=\"$defeito_reclamado\" descricao=\"$descricao\"/>\n";
			flush();
		}
	}
	
	$xml_defeito_reclamado .= "</ROWDATA>\n";
	$xml_defeito_reclamado .= "</DATAPACKET>\n";
	
	// GERA XML DO ARQUIVO
	$file    = "xml/$cnpj/defrecl.xml";
	$arquivo = fopen($file, "w+");
	
	fwrite($arquivo, $xml_defeito_reclamado);
	fclose($arquivo);

	
	##################################################################################################################
	// DEFEITO CONSTATADO
	##################################################################################################################
	if (pg_numrows($resX) > 0) {
		$status = "f";
		
		$contador = 0;
		for ($x=0; $x < pg_numrows($resX); $x++) {
			if (pg_result($resX,$x,defeito_constatado_por_familia) == "t") {
				$sql = "SELECT      trim(tbl_fabrica.fabrica)                                   AS fabrica           ,
									trim(tbl_produto.produto)                                   AS produto           ,
									''                                                          AS linha             ,
									trim(tbl_familia.familia)                                   AS familia           ,
									trim(tbl_defeito_constatado.defeito_constatado)             AS defeito_constatado,
									trim(tbl_defeito_constatado.codigo)                         AS codigo            ,
									trim(replace(tbl_defeito_constatado.descricao,'\"','\'\'')) AS descricao
						FROM        tbl_fabrica
						JOIN        tbl_familia                      ON tbl_familia.fabrica                       = tbl_fabrica.fabrica
						JOIN        tbl_familia_defeito_constatado   ON tbl_familia_defeito_constatado.familia    = tbl_familia.familia
						JOIN        tbl_defeito_constatado           ON tbl_defeito_constatado.defeito_constatado = tbl_familia_defeito_constatado.defeito_constatado
						JOIN        tbl_produto                      ON tbl_produto.familia                       = tbl_familia.familia ";
#				if (strlen(pg_result($resX,$x,fabrica)) > 0) $sql .= "AND tbl_fabrica.fabrica IN (". pg_result($resX,$x,fabrica) .") ";
				$sql .= "WHERE tbl_fabrica.fabrica IN (". pg_result($resX,$x,fabrica) .") ";
				$sql .= "ORDER BY tbl_fabrica.fabrica, tbl_produto.produto, tbl_familia.familia, tbl_defeito_constatado.defeito_constatado, lpad(tbl_defeito_constatado.descricao,150,0);";
				$res = pg_exec($con,$sql);
				
				if (pg_numrows($res) > 0) {
					if ($status == "f") {
						// cabeçalho
						$xml_defeito_constatado .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
						$xml_defeito_constatado .= "<DATAPACKET Version=\"2.0\">\n";
						$xml_defeito_constatado .= "<METADATA>\n";
						$xml_defeito_constatado .= "<FIELDS>\n";
						$xml_defeito_constatado .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
						$xml_defeito_constatado .= "<FIELD attrname=\"produto\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
						$xml_defeito_constatado .= "<FIELD attrname=\"linha\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
						$xml_defeito_constatado .= "<FIELD attrname=\"familia\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
						$xml_defeito_constatado .= "<FIELD attrname=\"defeito_constatado\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
						$xml_defeito_constatado .= "<FIELD attrname=\"codigo\" fieldtype=\"string\" WIDTH=\"10\"/>\n";
						$xml_defeito_constatado .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
						$xml_defeito_constatado .= "</FIELDS>\n";
						$xml_defeito_constatado .= "</METADATA>\n";
						$xml_defeito_constatado .= "<ROWDATA>\n";
						
						$status = "t";
					}
					
					while ($ln_defeito_constatado = pg_fetch_array($res)){
						$contador++;
						$fabrica            = trim($ln_defeito_constatado['fabrica']);
						$produto            = trim($ln_defeito_constatado['produto']);
						$linha              = trim($ln_defeito_constatado['linha']);
						$familia            = trim($ln_defeito_constatado['familia']);
						$defeito_constatado = trim($ln_defeito_constatado['defeito_constatado']);
						$codigo             = trim($ln_defeito_constatado['codigo']);
						$descricao          = trim($ln_defeito_constatado['descricao']);
						
						$xml_defeito_constatado .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" produto=\"$produto\" linha=\"$linha\" familia=\"$familia\" defeito_constatado=\"$defeito_constatado\" codigo=\"$codigo\" descricao=\"$descricao\"/>\n";
						flush();
					}
				}
			}else{
				$sql = "SELECT      trim(tbl_fabrica.fabrica)                                   AS fabrica           ,
									''                                                          AS produto           ,
									''                                                          AS linha             ,
									''                                                          AS familia           ,
									trim(tbl_defeito_constatado.defeito_constatado)             AS defeito_constatado,
									trim(tbl_defeito_constatado.codigo)                         AS codigo            ,
									trim(replace(tbl_defeito_constatado.descricao,'\"','\'\'')) AS descricao
						FROM        tbl_fabrica
						JOIN        tbl_defeito_constatado   ON tbl_defeito_constatado.fabrica = tbl_fabrica.fabrica ";
				if (strlen(pg_result($resX,$x,fabrica)) > 0) $sql .= "AND tbl_fabrica.fabrica IN (". pg_result($resX,$x,fabrica) .") ";
				$sql .= "ORDER BY tbl_fabrica.fabrica, tbl_defeito_constatado.defeito_constatado, lpad(tbl_defeito_constatado.descricao,150,0);";
				$res = pg_exec($con,$sql);
				
				if (pg_numrows($res) > 0) {
					if ($status == "f") {
						// cabeçalho
						$xml_defeito_constatado .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
						$xml_defeito_constatado .= "<DATAPACKET Version=\"2.0\">\n";
						$xml_defeito_constatado .= "<METADATA>\n";
						$xml_defeito_constatado .= "<FIELDS>\n";
						$xml_defeito_constatado .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
						$xml_defeito_constatado .= "<FIELD attrname=\"produto\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
						$xml_defeito_constatado .= "<FIELD attrname=\"linha\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
						$xml_defeito_constatado .= "<FIELD attrname=\"familia\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
						$xml_defeito_constatado .= "<FIELD attrname=\"defeito_constatado\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
						$xml_defeito_constatado .= "<FIELD attrname=\"codigo\" fieldtype=\"string\" WIDTH=\"10\"/>\n";
						$xml_defeito_constatado .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
						$xml_defeito_constatado .= "</FIELDS>\n";
						$xml_defeito_constatado .= "</METADATA>\n";
						$xml_defeito_constatado .= "<ROWDATA>\n";
						
						$status = "t";
					}
					
					while ($ln_defeito_constatado = pg_fetch_array($res)){
						$contador++;
						$fabrica            = trim($ln_defeito_constatado['fabrica']);
						$produto            = trim($ln_defeito_constatado['produto']);
						$linha              = trim($ln_defeito_constatado['linha']);
						$familia            = trim($ln_defeito_constatado['familia']);
						$defeito_constatado = trim($ln_defeito_constatado['defeito_constatado']);
						$codigo             = trim($ln_defeito_constatado['codigo']);
						$descricao          = trim($ln_defeito_constatado['descricao']);
						
						$xml_defeito_constatado .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" produto=\"$produto\" linha=\"$linha\" familia=\"$familia\" defeito_constatado=\"$defeito_constatado\" codigo=\"$codigo\" descricao=\"$descricao\"/>\n";
						flush();
					}
				}
			}
		}
		
		if ($status == "t") {
			$xml_defeito_constatado .= "</ROWDATA>\n";
			$xml_defeito_constatado .= "</DATAPACKET>\n";
			
			
			// GERA XML DO ARQUIVO
			$file    = "xml/$cnpj/defcons.xml";
			$arquivo = fopen($file, "w+");
			fwrite($arquivo, $xml_defeito_constatado);
			fclose($arquivo);
		}
	}
	
	
	##################################################################################################################
	// CAUSA DO DEFEITO
	##################################################################################################################
	$sql = "SELECT      trim(tbl_fabrica.fabrica)                              AS fabrica      ,
						trim(tbl_causa_defeito.causa_defeito)                  AS causa_defeito,
						trim(tbl_causa_defeito.codigo)                         AS codigo       ,
						trim(replace(tbl_causa_defeito.descricao,'\"','\'\'')) AS descricao
			FROM        tbl_fabrica
			JOIN        tbl_causa_defeito ON tbl_causa_defeito.fabrica = tbl_fabrica.fabrica ";
#	if (strlen($fabricante) > 0) $sql .= "WHERE tbl_fabrica.fabrica IN ($fabricante) ";
	$sql .= "WHERE tbl_fabrica.fabrica IN ($fabricante) ";
	$sql .= "ORDER BY tbl_fabrica.fabrica, lpad(tbl_causa_defeito.descricao,30,0);";
	$res = pg_exec($con,$sql);
	
	// cabeçalho
	$xml_causa_defeito .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
	$xml_causa_defeito .= "<DATAPACKET Version=\"2.0\">\n";
	$xml_causa_defeito .= "<METADATA>\n";
	$xml_causa_defeito .= "<FIELDS>\n";
	$xml_causa_defeito .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_causa_defeito .= "<FIELD attrname=\"causa_defeito\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_causa_defeito .= "<FIELD attrname=\"codigo\" fieldtype=\"string\" WIDTH=\"10\"/>\n";
	$xml_causa_defeito .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"30\"/>\n";
	$xml_causa_defeito .= "</FIELDS>\n";
	$xml_causa_defeito .= "</METADATA>\n";
	$xml_causa_defeito .= "<ROWDATA>\n";
	
	$fabrica       = "";
	$causa_defeito = "";
	$codigo        = "";
	$descricao     = "";
	
	if (pg_numrows($res) > 0) {
		$contador = 0;
		while ($ln_causa_defeito = pg_fetch_array($res)){
			$contador++;
			$fabrica       = trim($ln_causa_defeito['fabrica']);
			$causa_defeito = trim($ln_causa_defeito['causa_defeito']);
			$codigo        = trim($ln_causa_defeito['codigo']);
			$descricao     = trim($ln_causa_defeito['descricao']);
			
			$xml_causa_defeito .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" causa_defeito=\"$causa_defeito\" codigo=\"$codigo\" descricao=\"$descricao\"/>\n";
			flush();
		}
	}else{
		$xml_causa_defeito .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" causa_defeito=\"$causa_defeito\" codigo=\"$codigo\" descricao=\"$descricao\"/>\n";
		flush();
	}
	
	$xml_causa_defeito .= "</ROWDATA>\n";
	$xml_causa_defeito .= "</DATAPACKET>\n";
	
	
	// GERA XML DO ARQUIVO
	$file    = "xml/$cnpj/causadef.xml";
	$arquivo = fopen($file, "w+");
	fwrite($arquivo, $xml_causa_defeito);
	fclose($arquivo);
	
	
	##################################################################################################################
	// DEFEITO
	##################################################################################################################
	$sql = "SELECT      trim(tbl_fabrica.fabrica)                         AS fabrica  ,
						trim(tbl_defeito.defeito)                         AS defeito  ,
						trim(tbl_defeito.codigo_defeito)                  AS codigo   ,
						trim(replace(tbl_defeito.descricao,'\"','\'\''))  AS descricao
			FROM        tbl_fabrica
			JOIN        tbl_defeito ON tbl_defeito.fabrica = tbl_fabrica.fabrica ";
#	if (strlen($fabricante) > 0) $sql .= "WHERE tbl_fabrica.fabrica IN ($fabricante) ";
	$sql .= "WHERE tbl_fabrica.fabrica IN ($fabricante) ";
	$sql .= "ORDER BY tbl_fabrica.fabrica, lpad(tbl_defeito.descricao,30,0);";
	$res = pg_exec($con,$sql);
	
	// cabeçalho
	$xml_defeito .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
	$xml_defeito .= "<DATAPACKET Version=\"2.0\">\n";
	$xml_defeito .= "<METADATA>\n";
	$xml_defeito .= "<FIELDS>\n";
	$xml_defeito .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_defeito .= "<FIELD attrname=\"defeito\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_defeito .= "<FIELD attrname=\"codigo\" fieldtype=\"string\" WIDTH=\"10\"/>\n";
	$xml_defeito .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"30\"/>\n";
	$xml_defeito .= "</FIELDS>\n";
	$xml_defeito .= "</METADATA>\n";
	$xml_defeito .= "<ROWDATA>\n";
	
	$fabrica   = "";
	$defeito   = "";
	$codigo    = "";
	$descricao = "";
	
	if (pg_numrows($res) > 0) {
		$contador = 0;
		while ($ln_defeito = pg_fetch_array($res)){
			$contador++;
			$fabrica   = trim($ln_defeito['fabrica']);
			$defeito   = trim($ln_defeito['defeito']);
			$codigo    = trim($ln_defeito['codigo']);
			$descricao = trim($ln_defeito['descricao']);
			
			$xml_defeito .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" defeito=\"$defeito\" codigo=\"$codigo\" descricao=\"$descricao\"/>\n";
			flush();
		}
	}else{
		$xml_defeito .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" defeito=\"$defeito\" codigo=\"$codigo\" descricao=\"$descricao\"/>\n";
		flush();
	}
	
	$xml_defeito .= "</ROWDATA>\n";
	$xml_defeito .= "</DATAPACKET>\n";
	
	
	// GERA XML DO ARQUIVO
	$file    = "xml/$cnpj/defeito.xml";
	$arquivo = fopen($file, "w+");
	fwrite($arquivo, $xml_defeito);
	fclose($arquivo);
	
	
	##################################################################################################################
	// SERVIÇO REALIZADO
	##################################################################################################################
	$sql = "SELECT      trim(tbl_fabrica.fabrica)                                   AS fabrica            ,
						trim(tbl_servico_realizado.servico_realizado)               AS servico_realizado  ,
						trim(replace(tbl_servico_realizado.descricao,'\"','\'\''))  AS descricao
			FROM        tbl_fabrica
			JOIN        tbl_servico_realizado ON tbl_servico_realizado.fabrica = tbl_fabrica.fabrica
			and         tbl_servico_realizado.ativo IS TRUE ";
#	if (strlen($fabricante) > 0) $sql .= "AND tbl_fabrica.fabrica IN ($fabricante) ";
	$sql .= "WHERE tbl_fabrica.fabrica IN ($fabricante) ";
	$sql .= "ORDER BY tbl_fabrica.fabrica, lpad(tbl_servico_realizado.descricao,50,0);";
	$res = pg_exec($con,$sql);
	
	// cabeçalho
	$xml_servico_realizado .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
	$xml_servico_realizado .= "<DATAPACKET Version=\"2.0\">\n";
	$xml_servico_realizado .= "<METADATA>\n";
	$xml_servico_realizado .= "<FIELDS>\n";
	$xml_servico_realizado .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_servico_realizado .= "<FIELD attrname=\"servico_realizado\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_servico_realizado .= "<FIELD attrname=\"descricao\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
	$xml_servico_realizado .= "</FIELDS>\n";
	$xml_servico_realizado .= "</METADATA>\n";
	$xml_servico_realizado .= "<ROWDATA>\n";
	
	$fabrica           = "";
	$servico_realizado = "";
	$descricao         = "";
	
	if (pg_numrows($res) > 0) {
		$contador = 0;
		while ($ln_servico_realizado = pg_fetch_array($res)){
			$contador++;
			$fabrica           = trim($ln_servico_realizado['fabrica']);
			$servico_realizado = trim($ln_servico_realizado['servico_realizado']);
			$descricao         = trim($ln_servico_realizado['descricao']);
			
			$xml_servico_realizado .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" servico_realizado=\"$servico_realizado\" descricao=\"$descricao\"/>\n";
			flush();
		}
	}else{
		$xml_servico_realizado .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" servico_realizado=\"$servico_realizado\" descricao=\"$descricao\"/>\n";
		flush();
	}
	
	$xml_servico_realizado .= "</ROWDATA>\n";
	$xml_servico_realizado .= "</DATAPACKET>\n";
	
	
	// GERA XML DO ARQUIVO
	$file    = "xml/$cnpj/servreal.xml";
	$arquivo = fopen($file, "w+");
	fwrite($arquivo, $xml_servico_realizado);
	fclose($arquivo);
	
	
	##################################################################################################################
	// TRANSPORTADORAS
	##################################################################################################################
	$sql = "SELECT  tbl_transportadora.transportadora        ,
					tbl_transportadora_fabrica.fabrica       ,
					tbl_transportadora_fabrica.codigo_interno,
					tbl_transportadora.nome                  ,
					tbl_transportadora.cnpj                  ,
					tbl_transportadora.fantasia
			FROM    tbl_transportadora
			JOIN    tbl_transportadora_fabrica ON tbl_transportadora_fabrica.transportadora = tbl_transportadora.transportadora
			WHERE   tbl_transportadora_fabrica.ativo IS TRUE ";
	if (strlen($fabricante) > 0) $sql .= "AND tbl_transportadora_fabrica.fabrica IN ($fabricante) ";
	$sql .= "ORDER BY tbl_transportadora_fabrica.fabrica, lpad(tbl_transportadora_fabrica.codigo_interno,10,0);";
	$res = pg_exec($con,$sql);
	
	// cabeçalho
	$xml_transportadora .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
	$xml_transportadora .= "<DATAPACKET Version=\"2.0\">\n";
	$xml_transportadora .= "<METADATA>\n";
	$xml_transportadora .= "<FIELDS>\n";
	$xml_transportadora .= "<FIELD attrname=\"transportadora\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_transportadora .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_transportadora .= "<FIELD attrname=\"codigo_interno\" fieldtype=\"string\" WIDTH=\"20\"/>\n";
	$xml_transportadora .= "<FIELD attrname=\"nome\" fieldtype=\"string\" WIDTH=\"50\"/>\n";
	$xml_transportadora .= "<FIELD attrname=\"cnpj\" fieldtype=\"string\" WIDTH=\"14\"/>\n";
	$xml_transportadora .= "<FIELD attrname=\"fantasia\" fieldtype=\"string\" WIDTH=\"30\"/>\n";
	$xml_transportadora .= "</FIELDS>\n";
	$xml_transportadora .= "</METADATA>\n";
	$xml_transportadora .= "<ROWDATA>\n";
	
	$transportadora    = "";
	$fabrica           = "";
	$codigo_interno    = "";
	$nome              = "";
	$cnpj_transp       = "";
	$fantasia          = "";
	
	if (pg_numrows($res) > 0) {
		$contador = 0;
		
		while ($ln_transportadora = pg_fetch_array($res)){
			$contador++;
			$transportadora    = trim($ln_transportadora['transportadora']);
			$fabrica           = trim($ln_transportadora['fabrica']);
			$codigo_interno    = trim($ln_transportadora['codigo_interno']);
			$nome              = trim($ln_transportadora['nome']);
			$cnpj_transp       = trim($ln_transportadora['cnpj']);
			$fantasia          = trim($ln_transportadora['fantasia']);
			
			$xml_transportadora .= "<ROW RowState=\"4\" transportadora=\"$transportadora\" fabrica=\"$fabrica\" codigo_interno=\"$codigo_interno\" nome=\"$nome\" cnpj=\"$cnpj_transp\" fantasia=\"$fantasia\"/>\n";
			flush();
		}
	}else{
		$xml_transportadora .= "<ROW RowState=\"4\" transportadora=\"$transportadora\" fabrica=\"$fabrica\" codigo_interno=\"$codigo_interno\" nome=\"$nome\" cnpj=\"$cnpj_transp\" fantasia=\"$fantasia\"/>\n";
		flush();
	}
	
	$xml_transportadora .= "</ROWDATA>\n";
	$xml_transportadora .= "</DATAPACKET>\n";
	
	
	// GERA XML DO ARQUIVO
	$file    = "xml/$cnpj/transp.xml";
	$arquivo = fopen($file, "w+");
	fwrite($arquivo, $xml_transportadora);
	fclose($arquivo);
	
	
	##################################################################################################################
	// PEÇA FORA DE LINHA
	##################################################################################################################
	$sql = "SELECT  tbl_peca_fora_linha.fabrica,
					tbl_peca_fora_linha.peca
			FROM    tbl_peca_fora_linha
			JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca_fora_linha.fabrica ";
	if (strlen($fabricante) > 0) $sql .= "AND tbl_peca_fora_linha.fabrica IN ($fabricante) ";
	$sql .= "ORDER BY tbl_peca_fora_linha.fabrica, tbl_peca_fora_linha.peca;";
	$res = pg_exec($con,$sql);
	
	// cabeçalho
	$xml_peca_fora_linha .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
	$xml_peca_fora_linha .= "<DATAPACKET Version=\"2.0\">\n";
	$xml_peca_fora_linha .= "<METADATA>\n";
	$xml_peca_fora_linha .= "<FIELDS>\n";
	$xml_peca_fora_linha .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_peca_fora_linha .= "<FIELD attrname=\"peca\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_peca_fora_linha .= "</FIELDS>\n";
	$xml_peca_fora_linha .= "</METADATA>\n";
	$xml_peca_fora_linha .= "<ROWDATA>\n";
	
	$fabrica = "";
	$peca    = "";
	
	if (pg_numrows($res) > 0) {
		$contador = 0;
		
		while ($ln_peca_fora_linha = pg_fetch_array($res)){
			$contador++;
			$fabrica = trim($ln_peca_fora_linha['fabrica']);
			$peca    = trim($ln_peca_fora_linha['peca']);
			
			$xml_peca_fora_linha .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" peca=\"$peca\"/>\n";
			flush();
		}
	}else{
		$xml_peca_fora_linha .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" peca=\"$peca\"/>\n";
		flush();
	}
	
	$xml_peca_fora_linha .= "</ROWDATA>\n";
	$xml_peca_fora_linha .= "</DATAPACKET>\n";
	
	
	// GERA XML DO ARQUIVO
	$file    = "xml/$cnpj/obsoleta.xml";
	$arquivo = fopen($file, "w+");
	fwrite($arquivo, $xml_peca_fora_linha);
	fclose($arquivo);
	
	
	
	##################################################################################################################
	// PEÇA DE - PARA
	##################################################################################################################
	$sql = "SELECT  tbl_depara.fabrica  ,
					tbl_depara.peca_de  ,
					tbl_depara.peca_para
			FROM    tbl_depara
			JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_depara.fabrica ";
	if (strlen($fabricante) > 0) $sql .= "AND tbl_depara.fabrica IN ($fabricante) ";
	$sql .= "ORDER BY tbl_depara.fabrica, tbl_depara.peca_de;";
	$res = pg_exec($con,$sql);
	
	// cabeçalho
	$xml_depara .= "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
	$xml_depara .= "<DATAPACKET Version=\"2.0\">\n";
	$xml_depara .= "<METADATA>\n";
	$xml_depara .= "<FIELDS>\n";
	$xml_depara .= "<FIELD attrname=\"fabrica\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_depara .= "<FIELD attrname=\"peca_de\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_depara .= "<FIELD attrname=\"peca_para\" fieldtype=\"string\" WIDTH=\"15\"/>\n";
	$xml_depara .= "</FIELDS>\n";
	$xml_depara .= "</METADATA>\n";
	$xml_depara .= "<ROWDATA>\n";
	
	$fabrica   = "";
	$peca_de   = "";
	$peca_para = "";
	
	if (pg_numrows($res) > 0) {
		$contador = 0;
		
		while ($ln_depara = pg_fetch_array($res)){
			$contador++;
			$fabrica    = trim($ln_depara['fabrica']);
			$peca_de    = trim($ln_depara['peca_de']);
			$peca_para  = trim($ln_depara['peca_para']);
			
			$xml_depara .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" peca_de=\"$peca_de\" peca_para=\"$peca_para\"/>\n";
			flush();
		}
	}else{
		$xml_depara .= "<ROW RowState=\"4\" fabrica=\"$fabrica\" peca_de=\"$peca_de\" peca_para=\"$peca_para\"/>\n";
		flush();
	}
	
	$xml_depara .= "</ROWDATA>\n";
	$xml_depara .= "</DATAPACKET>\n";
	
	
	// GERA XML DO ARQUIVO
	$file    = "xml/$cnpj/depara.xml";
	$arquivo = fopen($file, "w+");
	fwrite($arquivo, $xml_depara);
	fclose($arquivo);
	
	##################################################################################################################
	// STATUS DAS ORDENS DE SERVIÇO
	##################################################################################################################
	$sql = "SELECT  tbl_os.sua_os                                    ,
					tbl_os.fabrica                                   ,
					tbl_os_status.status_os                          ,
					to_char(tbl_os_status.data, 'DD/MM/YYYY') AS data,
					tbl_os_status.observacao
			FROM    tbl_os
			JOIN    tbl_posto     ON tbl_posto.posto  = tbl_os.posto
			JOIN    tbl_os_status ON tbl_os_status.os = tbl_os.os
			WHERE   tbl_posto.cnpj = '$cnpj'
			AND     tbl_os_status.status_os IN (13,15) ";
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
	
	$os         = "";
	$fabrica    = "";
	$status     = "";
	$data       = "";
	$observacao = "";
	
	if (pg_numrows($res) > 0) {
		$contador = 0;
		
		while ($ln_status_os = pg_fetch_array($res)){
			$contador++;
			$os         = trim($ln_status_os['sua_os']);
			$fabrica    = trim($ln_status_os['fabrica']);
			$status     = trim($ln_status_os['status_os']);
			$data       = trim($ln_status_os['data']);
			$observacao = trim($ln_status_os['observacao']);
			
			$xml_status_os .= "<ROW RowState=\"4\" os=\"$os\" fabrica=\"$fabrica\" status_os=\"$status\" data=\"$data\" observacao=\"$observacao\"/>\n";
			flush();
		}
	}else{
		$xml_status_os .= "<!--<ROW RowState=\"4\" os=\"$os\" fabrica=\"$fabrica\" status_os=\"$status\" data=\"$data\" observacao=\"$observacao\"/>-->\n";
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
	// GERA ARQUIVO ZIP PARA DOWNLOAD
	##################################################################################################################
	#$controle = `date +%M%S`;
	#$arq      = $cnpj . substr($controle,0,4);
	#$x        = `cd xml/$cnpj; rm -rf *.zip; zip -o $cnpj.zip *.xml logos/*; rm -rf *.xml logos*/`;
	
	$controle = `date +%j%M%S`;
	$arq      = $cnpj . substr($controle,0,7);
	$x        = `cd xml/$cnpj; rm -rf *.zip; zip -o $arq.zip *.xml logos/*; rm -rf *.xml logos*/`;
	
	echo "<!--ARQUIVO-I-->$arq.zip<!--ARQUIVO-F-->";
}else{
	echo "<!--OFFLINE-I-->SISTEMA NÃO LIBERADO<!--OFFLINE-F-->";
}
?>
