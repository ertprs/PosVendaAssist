<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if(strlen($_GET['extrato']) == 0){
	echo "<script>";
	echo "close();";
	echo "</script>";
	exit;
}

$extrato = trim($_POST['extrato']);
if(strlen($_GET['extrato']) > 0){
	$extrato = trim($_GET['extrato']);
}

$title = "EXTRATO - DETALHADO";
if($sistema_lingua == "ES") $title = "EXTRACTO - DETALLADO";
?>

<style type="text/css">

.Titulo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;

	color:#000000;
}
.Titulo2 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#000000;
}
.Conteudo {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: normal;
	color:#000000;
}

.Conteudo2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color:#000000;
}

.inicio {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#6A6A6A;
}

</style>


<p>
<?
# ----------------------------------------- #
# -- VERIFICA SE É POSTO OU DISTRIBUIDOR -- #
# ----------------------------------------- #
$sql = "SELECT  DISTINCT
				tbl_tipo_posto.tipo_posto     ,
				tbl_posto.estado
		FROM    tbl_tipo_posto
		JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
									AND tbl_posto_fabrica.posto      = $login_posto
									AND tbl_posto_fabrica.fabrica    = $login_fabrica
		JOIN    tbl_posto            ON tbl_posto.posto = tbl_posto_fabrica.posto
		WHERE   tbl_tipo_posto.distribuidor IS TRUE
		AND     tbl_posto_fabrica.fabrica = $login_fabrica
		AND     tbl_tipo_posto.fabrica    = $login_fabrica
		AND     tbl_posto_fabrica.posto   = $login_posto ";
$res = pg_exec ($con,$sql);
if (pg_numrows($res) == 0) $tipo_posto = "P"; else $tipo_posto = "D";


if ($login_posto != 4311) {
	$cond = "AND tbl_posto_fabrica.fabrica      = $login_fabrica";
}

$sql = "SELECT  tbl_os.sua_os                                                               ,
				tbl_os.sua_os_offline                                                       ,
				tbl_os.os                                                                   ,
				tbl_os.mao_de_obra                                                          ,
				tbl_os.mao_de_obra_distribuidor                                             ,
				tbl_os.pecas                                                                ,
				to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao             ,
				to_char(tbl_extrato_extra.baixado,'DD/MM/YYYY') AS baixado                  ,
				to_char(tbl_extrato.liberado,'DD/MM/YYYY')       AS liberado                ,
				lpad (tbl_extrato.protocolo,5,'0')               AS protocolo               ,
				tbl_extrato_extra.obs                                                       ,
				tbl_os_extra.mao_de_obra                         AS extra_mo                ,
				tbl_os_extra.custo_pecas                         AS extra_pecas             ,
				tbl_os_extra.taxa_visita                         AS extra_instalacao        ,
				tbl_os_extra.deslocamento_km                     AS extra_deslocamento      ,
				tbl_os.qtde_km_calculada                         AS qtde_km_calculada       ,
				tbl_admin.login                              AS admin                       ,
				troca_admin.login                            AS troca_admin                 ,
				to_char(tbl_os.data_digitacao,'DD/MM/YYYY')  AS data_digitacao              ,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura               ,
				to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento             ,
				to_char(tbl_os.finalizada,'DD/MM/YYYY')      AS finalizada                  ,
				to_char(tbl_os.data_nf_saida,'DD/MM/YYYY')   AS data_nf_saida               ,
				tbl_os.tipo_atendimento                                                     ,
				tbl_os.tecnico_nome                                                         ,
				tbl_tipo_atendimento.descricao                 AS nome_atendimento          ,
				tbl_tipo_atendimento.codigo                    AS codigo_atendimento        ,
				tbl_os.consumidor_nome                                                      ,
				tbl_os.consumidor_fone                                                      ,
				tbl_os.consumidor_celular                                                   ,
				tbl_os.consumidor_fone_comercial                                            ,
				tbl_os.consumidor_fone_recado                                               ,
				tbl_os.consumidor_endereco                                                  ,
				tbl_os.consumidor_numero                                                    ,
				tbl_os.consumidor_complemento                                               ,
				tbl_os.consumidor_bairro                                                    ,
				tbl_os.consumidor_cep                                                       ,
				tbl_os.consumidor_cidade                                                    ,
				tbl_os.consumidor_estado                                                    ,
				tbl_os.consumidor_cpf                                                       ,
				tbl_os.consumidor_email                                                     ,
				tbl_os.revenda_nome                                                         ,
				lpad(tbl_os.revenda_cnpj, 14, '0') AS revenda_cnpj                          ,
				tbl_os.revenda_fone                                                         ,
				tbl_os.nota_fiscal                                                          ,
				tbl_os.nota_fiscal_saida                                                    ,
				tbl_os.cliente                                                              ,
				tbl_os.revenda                                                              ,
				tbl_os.rg_produto                                                           ,
				tbl_os.defeito_reclamado_descricao       AS defeito_reclamado_descricao_os  ,
				tbl_marca.marca                                                             ,
				tbl_marca.nome as marca_nome                                                ,
				tbl_os.qtde_produtos as qtde                                                ,
				tbl_os.tipo_os                                                              ,
				to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf                     ,
				tbl_defeito_reclamado.defeito_reclamado      AS defeito_reclamado           ,
				tbl_defeito_reclamado.descricao              AS defeito_reclamado_descricao ,
				tbl_causa_defeito.descricao                  AS causa_defeito_descricao     ,
				tbl_defeito_constatado.defeito_constatado    AS defeito_constatado          ,
				tbl_defeito_constatado.descricao             AS defeito_constatado_descricao,
				tbl_defeito_constatado.codigo                AS defeito_constatado_codigo   ,
				tbl_causa_defeito.causa_defeito              AS causa_defeito               ,
				tbl_causa_defeito.descricao                  AS causa_defeito_descricao     ,
				tbl_causa_defeito.codigo                     AS causa_defeito_codigo        ,
				tbl_os.aparencia_produto                                                    ,
				tbl_os.acessorios                                                           ,
				tbl_os.consumidor_revenda                                                   ,
				tbl_os.obs                                                                  ,
				tbl_os.excluida                                                             ,
				tbl_produto.produto                                                         ,
				tbl_produto.referencia                                                      ,
				tbl_produto.descricao                                                       ,
				tbl_produto.voltagem                                                        ,
				tbl_produto.troca_obrigatoria                                               ,
				tbl_os.qtde_produtos                                                        ,
				tbl_os.serie                                                                ,
				tbl_os.codigo_fabricacao                                                    ,
				tbl_posto_fabrica.codigo_posto               AS posto_codigo                ,
				tbl_posto.nome                               AS posto_nome                  ,
				tbl_os.ressarcimento                                                        ,
				tbl_os.certificado_garantia                                                 ,
				tbl_os_extra.os_reincidente                                                 ,
				tbl_os_extra.orientacao_sac                                                 ,
				tbl_os.solucao_os                                                           ,
				tbl_os.posto                                                                ,
				tbl_os.promotor_treinamento                                                 ,
				tbl_os.fisica_juridica                                                      ,
				tbl_os.troca_garantia                                                       ,
				tbl_os.troca_garantia_admin                                                 ,
				tbl_os.troca_faturada                                                       ,
				tbl_os_extra.tipo_troca                                                     ,
				tbl_os.os_posto                                                             ,
				to_char(tbl_os.finalizada,'DD/MM/YYYY HH24:MI') as data_ressarcimento       ,
				serie_reoperado                                                             ,
				tbl_extrato.extrato                                                         ,
				to_char(tbl_extrato_pagamento.data_pagamento, 'dd/mm/yyyy') AS data_previsao,
				to_char(tbl_extrato_pagamento.data_pagamento, 'dd/mm/yyyy') AS data_pagamento                                                              ,
				tbl_os.fabricacao_produto                                                   ,
				tbl_os.qtde_km                                                              ,
				tbl_os.os_numero
		FROM tbl_os_extra
		JOIN tbl_os            ON tbl_os.os           = tbl_os_extra.os
		JOIN tbl_extrato       ON tbl_extrato.extrato = tbl_os_extra.extrato
		JOIN tbl_extrato_extra ON tbl_extrato.extrato = tbl_extrato_extra.extrato
		JOIN tbl_posto         ON tbl_posto.posto                       = tbl_os.posto
		JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto               = tbl_os.posto $cond
		LEFT JOIN  tbl_extrato_pagamento ON tbl_extrato_pagamento.extrato        = tbl_extrato.extrato
		LEFT JOIN  tbl_admin              ON tbl_os.admin                          = tbl_admin.admin
		LEFT JOIN  tbl_admin troca_admin  ON tbl_os.troca_garantia_admin = troca_admin.admin
		LEFT JOIN  tbl_defeito_reclamado  ON tbl_os.defeito_reclamado              = tbl_defeito_reclamado.defeito_reclamado
		LEFT JOIN  tbl_defeito_constatado ON tbl_os.defeito_constatado             = tbl_defeito_constatado.defeito_constatado
		LEFT JOIN  tbl_causa_defeito      ON tbl_os.causa_defeito                  = tbl_causa_defeito.causa_defeito
		LEFT JOIN  tbl_produto            ON tbl_os.produto                        = tbl_produto.produto
		LEFT JOIN  tbl_tipo_atendimento   ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
		LEFT JOIN tbl_marca on tbl_produto.marca = tbl_marca.marca
		WHERE tbl_os_extra.extrato = $extrato
		AND tbl_os.posto         = $login_posto ";
if ($login_e_distribuidor != "t") {
	$sql .= "AND tbl_os.posto = $login_posto ";
}
$sql .= "ORDER BY    lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0')          ASC,
				replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC";

$res = pg_exec ($con,$sql);

$totalRegistros = pg_numrows($res);
if ($totalRegistros == 0){
	echo "<script>";
	echo "close();";
	echo "</script>";
	exit;
}elseif ($totalRegistros > 0){
	$ja_baixado = false ;
	$protocolo = pg_result ($res,0,protocolo) ;

	//HD 6204 PA pediu colocar logo da fabrica para identificar
	$login_fabrica_nome=strtolower($login_fabrica_nome);

	echo "<TABLE width='650' align='center' border='1' cellspacing='0' cellpadding='1' style='border-collapse: collapse' bordercolor='#000000'>\n";
	echo "<tr>";
	echo "	<td class='Titulo' colspan='3' align='center'>";
	echo "<IMG SRC=\"logos/cabecalho_print_";
	echo "$login_fabrica_nome.gif\" width='120' height='40' ALT='EXTRATO' align=left >";
	echo "	<BR><b>$title<br>";
	echo "Extrato ";
	echo $extrato;
	if($sistema_lingua == "ES") echo " generado en ";
	else                        echo " gerado em ";
	echo pg_result ($res,0,data_geracao) ;
	echo "	</b><BR><BR></td>";
	echo "</tr>";
	echo "</TABLE>\n";


//--=== DADOS DO POSTO============================================================================================--\\
	$sql2 = "SELECT  tbl_posto_fabrica.codigo_posto                          ,
					tbl_posto.posto                                         ,
					tbl_posto.nome                                          ,
					tbl_posto.endereco                                      ,
					tbl_posto.cidade                                        ,
					tbl_posto.estado                                        ,
					tbl_posto.cep                                           ,
					tbl_posto.fone                                          ,
					tbl_posto.fax                                           ,
					tbl_posto.contato                                       ,
					tbl_posto.email                                         ,
					tbl_posto.cnpj                                          ,
					tbl_posto.ie                                            ,
					tbl_posto_fabrica.banco                                 ,
					tbl_posto_fabrica.agencia                               ,
					tbl_posto_fabrica.conta                                 ,
					tbl_extrato.protocolo                                   ,
					to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data 
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
									  AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN    tbl_extrato ON tbl_extrato.posto = tbl_posto.posto
			WHERE   tbl_extrato.extrato = $extrato;";
	$res2 = pg_exec ($con,$sql2);

	if (pg_numrows($res2) > 0) {
		$codigo        = trim(pg_result($res2,0,codigo_posto));
		$posto         = trim(pg_result($res2,0,posto));
		$nome          = trim(pg_result($res2,0,nome));
		$endereco      = trim(pg_result($res2,0,endereco));
		$cidade        = trim(pg_result($res2,0,cidade));
		$estado        = trim(pg_result($res2,0,estado));
		$cep           = substr(pg_result($res2,0,cep),0,2) .".". substr(pg_result($res2,0,cep),2,3) ."-". substr(pg_result($res2,0,cep),5,3);
		$fone          = trim(pg_result($res2,0,fone));
		$fax           = trim(pg_result($res2,0,fax));
		$contato       = trim(pg_result($res2,0,contato));
		$email         = trim(pg_result($res2,0,email));
		$cnpj          = trim(pg_result($res2,0,cnpj));
		$ie            = trim(pg_result($res2,0,ie));
		$banco         = trim(pg_result($res2,0,banco));
		$agencia       = trim(pg_result($res2,0,agencia));
		$conta         = trim(pg_result($res2,0,conta));
		$data_extrato  = trim(pg_result($res2,0,data));
		$protocolo     = trim(pg_result($res2,0,protocolo));
		

		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
		echo "<tr>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>";
		if($sistema_lingua == "ES") echo "Período: ";
		else                        echo "Período: ";
		echo "</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='100' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$inicio_extrato</font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='40' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>";
		if($sistema_lingua == "ES") echo "Hasta";
		else                        echo "Até:";
		echo "</b></font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='120' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$final_extrato</font>\n";
		echo "</td>\n";
		
		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='40' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>";
		if($sistema_lingua == "ES") echo "Fecha:";
		else                        echo "Data:";
		echo "</b></font>\n";
		echo "</td>\n";

		echo "<td bgcolor='#FFFFFF' align='left'>\n";
		echo "<img src='imagens/pixel.gif' width='230' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$data_atual</font>\n";
		echo "</td>\n";
		
		echo "</tr>\n";
		echo "</table>\n";

		echo "<TABLE width='650' align='center' border='1' cellspacing='0' cellpadding='0' style='border-collapse: collapse' bordercolor='#000000'>\n";
		echo "<TR class='Conteudo2'>\n";
		echo "<TD>";
	
		echo "<table border='0' >";
		echo "<tr>";
		echo "<td class='Titulo2'>";
		if($sistema_lingua == "ES") echo "SERVICIO";
		else                        echo "POSTO";
		echo "</td>";
		echo "<td class='Conteudo2'>$nome</td>";
		echo "</tr>";
		echo "<td class='Titulo2'>";
		if($sistema_lingua == "ES") echo "DIRECCIÓN";
		else                        echo "ENDEREÇO";
		echo "</td>";
		echo "<td class='Conteudo2' width='200'>$endereco,$numero</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td class='Titulo2'>";
		if($sistema_lingua == "ES") echo "CIUDAD";
		else                        echo "CIDADE";
		echo "</td>";
		echo "<td class='Conteudo2'>$cidade - $estado</td>";
		echo "<td class='Titulo2'>";
		if($sistema_lingua == "ES") echo "APARATO POSTAL";
		else                        echo "CEP";
		echo "</td>";
		echo "<td class='Conteudo2'>$cep</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td class='Titulo2'>";
		if($sistema_lingua == "ES") echo "TELÉFONO";
		else                        echo "TELEFONE";
		echo "</td>";
		echo "<td class='Conteudo2'>$fone</td>";
		echo "<td class='Titulo2'>";
		if($sistema_lingua == "ES") echo "FAX";
		else                        echo "FAX";
		echo "</td>";
		echo "<td class='Conteudo2'>$fax</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td class='Titulo2'>";
		if($sistema_lingua == "ES") echo "IDENTIFICACIÓN";
		else                        echo "CNPJ";
		echo "</td>";
		echo "<td class='Conteudo2'>$cnpj</td>";
		echo "<td class='Titulo2'>";
		if($sistema_lingua == "ES") echo "IDENTIFICACIÓN 2";
		else                        echo "IE/RG";
		echo "</td>";
		echo "<td class='Conteudo2'>$ie</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td class='Titulo2'>";
		if($sistema_lingua == "ES") echo "E-MAIL";
		else                        echo "EMAIL";
		echo "</td>";
		echo "<td class='Conteudo2'>$email</td>";
		echo "</tr>";
		echo "</table>";
	
		echo "</TD>";
		echo "</TR>";
		echo "</TABLE>";

	}
//--=== DADOS DO POSTO============================================================================================--\\





	echo "<br>";





//--=== OS DENTRO DO EXTRATO =====================================================================================--\\
	echo "<TABLE width='650' align='center' border='1' cellspacing='0' cellpadding='1' style='border-collapse: collapse' bordercolor='#000000'>\n";
	echo "<TR class='Titulo2'>\n";
		echo "<TD >OS</TD>\n";
		echo "<td align='center' >MO</td>\n";
		echo "<td align='center'>KM</td>\n";
	echo "	</TR>\n";

	$total             = 0;
	$total_mao_de_obra = 0;
	$total_pecas       = 0;
	$total_km          = 0;
	
	$total_mao_de_obra_revenda = 0;
	$total_pecas_revenda       = 0;
	
	$total_extra_mo            = 0;
	$total_extra_pecas         = 0;
	$total_extra_instalacao    = 0;
	$total_extra_deslocamento  = 0;
	$total_extra_total         = 0;
	
	for ($i = 0 ; $i < $totalRegistros; $i++) {

		$os                          = pg_fetch_result ($res,$i,os);
		$sua_os                      = pg_fetch_result ($res,$i,sua_os);
		$admin                       = pg_fetch_result ($res,$i,admin);
		$data_digitacao              = pg_fetch_result ($res,$i,data_digitacao);
		$data_abertura               = pg_fetch_result ($res,$i,data_abertura);
		$data_fechamento             = pg_fetch_result ($res,$i,data_fechamento);
		$data_finalizada             = pg_fetch_result ($res,$i,finalizada);
		$data_nf_saida               = pg_fetch_result ($res,$i,data_nf_saida);

		//--==== INFORMACOES DO CONSUMIDOR =================================================
		$consumidor_nome             = pg_fetch_result ($res,$i,consumidor_nome);
		$consumidor_endereco         = pg_fetch_result ($res,$i,consumidor_endereco);
		$consumidor_numero           = pg_fetch_result ($res,$i,consumidor_numero);
		$consumidor_complemento      = pg_fetch_result ($res,$i,consumidor_complemento);
		$consumidor_bairro           = pg_fetch_result ($res,$i,consumidor_bairro);
		$consumidor_cidade           = pg_fetch_result ($res,$i,consumidor_cidade);
		$consumidor_estado           = pg_fetch_result ($res,$i,consumidor_estado);
		$consumidor_cep              = pg_fetch_result ($res,$i,consumidor_cep);
		$consumidor_fone             = pg_fetch_result ($res,$i,consumidor_fone);
		$consumidor_celular          = pg_fetch_result ($res,$i,consumidor_celular);
		$consumidor_fone_comercial   = pg_fetch_result ($res,$i,consumidor_fone_comercial);
		$consumidor_fone_recado      = pg_fetch_result ($res,$i,consumidor_fone_recado);
		$consumidor_cpf              = pg_fetch_result ($res,$i,consumidor_cpf);
		$consumidor_email            = pg_fetch_result ($res,$i,consumidor_email);
		$fisica_juridica             = pg_fetch_result ($res,$i,fisica_juridica);
		$data_ressarcimento          = pg_fetch_result ($res,$i,data_ressarcimento);

		if($fisica_juridica=="F"){
			$fisica_juridica = traduz("pessoa.fisica",$con,$cook_idioma);
		}
		if($fisica_juridica=="J"){
			$fisica_juridica = traduz("pessoa.juridica",$con,$cook_idioma);
		}


		//--==== INFORMACOES DA REVENDA ====================================================
		$revenda_cnpj                = pg_fetch_result ($res,$i,revenda_cnpj);
		$revenda_nome                = pg_fetch_result ($res,$i,revenda_nome);
		$revenda_fone                = pg_fetch_result ($res,$i,revenda_fone);
		$nota_fiscal                 = pg_fetch_result ($res,$i,nota_fiscal);
		$nota_fiscal_saida           = pg_fetch_result ($res,$i,nota_fiscal_saida);
		$data_nf                     = pg_fetch_result ($res,$i,data_nf);
		$cliente                     = pg_fetch_result ($res,$i,cliente);
		$revenda                     = pg_fetch_result ($res,$i,revenda);
		$consumidor_revenda          = pg_fetch_result ($res,$i,consumidor_revenda);
		if($consumidor_revenda == "C") {
			$consumidor_revenda = "CONSUMIDOR";
		} else {
			$consumidor_revenda = "REVENDA";
		}
	

		//--==== INFORMACOES DO PRODUTO ====================================================
		$produto                      = pg_fetch_result ($res,$i,produto);
		$aparencia_produto            = pg_fetch_result ($res,$i,aparencia_produto);
		$acessorios                   = pg_fetch_result ($res,$i,acessorios);
		$produto_referencia           = pg_fetch_result ($res,$i,referencia);
		$produto_descricao            = pg_fetch_result ($res,$i,descricao);
		$produto_voltagem             = pg_fetch_result ($res,$i,voltagem);
		$serie                        = pg_fetch_result ($res,$i,serie);
		$codigo_fabricacao            = pg_fetch_result ($res,$i,codigo_fabricacao);
		$troca_obrigatoria            = pg_fetch_result ($res,$i,troca_obrigatoria);
		$rg_produto                   = pg_fetch_result ($res,$i,rg_produto);

		//--==== DEFEITOS RECLAMADOS =======================================================
		$defeito_reclamado            = pg_fetch_result ($res,$i,defeito_reclamado);
		$defeito_reclamado_descricao  = pg_fetch_result ($res,$i,defeito_reclamado_descricao);
		$defeito_reclamado_descricao_os= pg_fetch_result ($res,$i,defeito_reclamado_descricao_os);
		$os_posto                     = pg_fetch_result ($res,$i,os_posto);

		if (strlen($defeito_reclamado_descricao)==0){
			$defeito_reclamado_descricao = $defeito_reclamado_descricao_os;
		}

		//--==== DEFEITOS CONSTATADO =======================================================
		$defeito_constatado           = pg_fetch_result ($res,$i,defeito_constatado);
		$defeito_constatado_codigo    = pg_fetch_result ($res,$i,defeito_constatado_codigo);
		$defeito_constatado_descricao = pg_fetch_result ($res,$i,defeito_constatado_descricao);

		//--==== CAUSA DO DEFEITO ==========================================================
		$causa_defeito                = pg_fetch_result ($res,$i,causa_defeito);
		$causa_defeito_codigo         = pg_fetch_result ($res,$i,causa_defeito_codigo);
		$causa_defeito_descricao      = pg_fetch_result ($res,$i,causa_defeito_descricao);
		$posto_codigo                 = pg_fetch_result ($res,$i,posto_codigo);
		$posto_nome                   = pg_fetch_result ($res,$i,posto_nome);
		$obs                          = pg_fetch_result ($res,$i,obs);
		$qtde_produtos                = pg_fetch_result ($res,$i,qtde_produtos);
		$excluida                     = pg_fetch_result ($res,$i,excluida);
		$os_reincidente               = trim(pg_fetch_result ($res,$i,os_reincidente));
		$orientacao_sac               = trim(pg_fetch_result ($res,$i,orientacao_sac));
		$sua_os_offline               = trim(pg_fetch_result ($res,$i,sua_os_offline));
		$solucao_os                   = trim (pg_fetch_result($res,$i,solucao_os));
		$posto_verificado             = trim(pg_fetch_result ($res,$i,posto));
		$marca_nome                   = trim(pg_fetch_result($res,$i,marca_nome));
		$marca                        = trim(pg_fetch_result($res,$i,marca));
		$ressarcimento                = trim(pg_fetch_result($res,$i,ressarcimento));
		$certificado_garantia         = trim(pg_fetch_result($res,$i,certificado_garantia));
		$troca_garantia               = trim(pg_fetch_result($res,$i,troca_garantia));
		$troca_faturada               = trim(pg_fetch_result($res,$i,troca_faturada));
		$troca_garantia_admin         = trim(pg_fetch_result($res,$i,troca_garantia_admin));
		$troca_admin                  = trim(pg_fetch_result($res,$i,troca_admin));
		$qtde                         = pg_fetch_result ($res,$i,qtde);
		$tipo_os                      = pg_fetch_result ($res,$i,tipo_os);
		$tipo_atendimento             = trim(pg_fetch_result($res,$i,tipo_atendimento));
		$tecnico_nome                 = trim(pg_fetch_result($res,$i,tecnico_nome));
		$nome_atendimento             = trim(pg_fetch_result($res,$i,nome_atendimento));
		$codigo_atendimento           = trim(pg_fetch_result($res,$i,codigo_atendimento));
		$tipo_troca                   = trim(pg_fetch_result($res,$i,tipo_troca));
		$numero_controle              = trim(pg_fetch_result($res,$i,serie_reoperado)); //HD 56740

		//--==== AUTORIZAÇÃO CORTESIA =====================================
		//        $autorizacao_cortesia = trim(pg_fetch_result($res,$i,autorizacao_cortesia));
		$promotor_treinamento         = trim(pg_fetch_result($res,$i,promotor_treinamento));

		//--==== Dados Extrato HD 61132 ====================================
		$extrato                      = trim(pg_fetch_result($res,$i,extrato));
		$data_previsao                = trim(pg_fetch_result($res,$i,data_previsao));
		$data_pagamento               = trim(pg_fetch_result($res,$i,data_pagamento));

		// HD 64152
		$fabricacao_produto           = trim(pg_fetch_result($res,$i,fabricacao_produto));
		$qtde_km                      = trim(pg_fetch_result($res,$i,qtde_km));
		$os_numero                    = trim(pg_fetch_result($res,$i,os_numero));
		if(strlen($qtde_km) == 0) $qtde_km = 0;


		//--=== Tradução para outras linguas ============================= Raphael HD:1212
		# HD 13940 - Ultimo Status para as Aprovações de OS
		$sql = "SELECT status_os, observacao
				FROM tbl_os_status
				WHERE os = $os
				AND status_os IN (92,93,94)
				ORDER BY data DESC
				LIMIT 1";
		$res_status = pg_query($con,$sql);
		if (pg_num_rows($res_status) >0) {
			$status_recusa_status_os  = trim(pg_fetch_result($res_status,0,status_os));
			$status_recusa_observacao = trim(pg_fetch_result($res_status,0,observacao));
			if($status_recusa_status_os == 94){
				$os_recusada = 't';
			}
		}


		# HD 44202 - Ultimo Status para as Aprovações de OS aberta a mais de 90 dias
		$sql = "SELECT status_os, observacao
				FROM tbl_os_status
				WHERE os = $os
				AND status_os IN (120,122,123,126,140,141,142,143)
				ORDER BY data DESC
				LIMIT 1";
		$res_status = pg_query($con,$sql);

		if (pg_num_rows($res_status) > 0) {
			$status_os_aberta     = trim(pg_fetch_result($res_status,0,status_os));
			$status_os_aberta_obs = trim(pg_fetch_result($res_status,0,observacao));
		}


		if (strlen($revenda) > 0) {
			$sql = "SELECT  tbl_revenda.endereco   ,
							tbl_revenda.numero     ,
							tbl_revenda.complemento,
							tbl_revenda.bairro     ,
							tbl_revenda.cep        ,
							tbl_revenda.email
					FROM    tbl_revenda
					WHERE   tbl_revenda.revenda = $revenda;";
			$res1 = pg_query ($con,$sql);

			if (pg_num_rows($res1) > 0) {
				$revenda_endereco    = strtoupper(trim(pg_fetch_result ($res1,0,endereco)));
				$revenda_numero      = trim(pg_fetch_result ($res1,0,numero));
				$revenda_complemento = strtoupper(trim(pg_fetch_result ($res1,0,complemento)));
				$revenda_bairro      = strtoupper(trim(pg_fetch_result ($res1,0,bairro)));
				$revenda_email       = trim(pg_fetch_result ($res1,0,email));
				$revenda_cep         = trim(pg_fetch_result ($res1,0,cep));
				$revenda_cep         = substr($revenda_cep,0,2) .".". substr($revenda_cep,2,3) ."-". substr($revenda_cep,5,3);
			}
		}
		if (strlen($revenda_cnpj) == 14){
			$revenda_cnpj = substr($revenda_cnpj,0,2) .".". substr($revenda_cnpj,2,3) .".". substr($revenda_cnpj,5,3) ."/". substr($revenda_cnpj,8,4) ."-". substr($revenda_cnpj,12,2);
		}elseif(strlen($consumidor_cpf) == 11){
			$revenda_cnpj = substr($revenda_cnpj,0,3) .".". substr($revenda_cnpj,3,3) .".". substr($revenda_cnpj,6,3) ."-". substr($revenda_cnpj,9,2);
		}

		if($aparencia_produto=='NEW'){
			$aparencia = traduz("bom.estado",$con,$cook_idioma);
			$aparencia_produto= $aparencia_produto.' - '.$aparencia;
		}
		if($aparencia_produto=='USL'){
			$aparencia = traduz("uso.intenso",$con,$cook_idioma);
			$aparencia_produto= $aparencia_produto.' - '.$aparencia;
		}
		if($aparencia_produto=='USN'){
			$aparencia = traduz("uso.normal",$con,$cook_idioma);
			$aparencia_produto= $aparencia_produto.' - '.$aparencia;
		}
		if($aparencia_produto=='USH'){
			$aparencia = traduz("uso.pesado",$con,$cook_idioma);
			$aparencia_produto= $aparencia_produto.' - '.$aparencia;
		}
		if($aparencia_produto=='ABU'){
			$aparencia = traduz("uso.abusivo",$con,$cook_idioma);
			$aparencia_produto= $aparencia_produto.' - '.$aparencia;
		}
		if($aparencia_produto=='ORI'){
			$aparencia = traduz("original.sem.uso",$con,$cook_idioma);
			$aparencia_produto= $aparencia_produto.' - '.$aparencia;
		}
		if($aparencia_produto=='PCK'){
			$aparencia = traduz("embalagem",$con,$cook_idioma);
			$aparencia_produto= $aparencia_produto.' - '.$aparencia;
		}

		if (strlen($sua_os) == 0) $sua_os = $os;

		$title = traduz("confirmacao.de.ordem.de.servico",$con,$cook_idioma);






		//$os				 = trim(pg_result ($res,$i,os));
		//$sua_os			 = trim(pg_result ($res,$i,sua_os));
		$mao_de_obra	 = trim(pg_result ($res,$i,mao_de_obra));
		$mao_de_obra_distribuidor = trim(pg_result ($res,$i,mao_de_obra_distribuidor));
		$pecas			 = trim(pg_result ($res,$i,pecas));
		$qtde_km_calculada = trim(pg_result ($res,$i,qtde_km_calculada));
		//$consumidor_nome = strtoupper(trim(pg_result ($res,$i,consumidor_nome)));
		$consumidor_str	 = substr($consumidor_nome,0,40);
		//$data_abertura   = trim (pg_result ($res,$i,data_abertura));
		//$data_fechamento = trim (pg_result ($res,$i,data_fechamento));
		$baixado         = pg_result ($res,$i,baixado) ;
		$liberado        = pg_result ($res,$i,liberado) ;
		$obs             = pg_result ($res,$i,obs) ;
		
		$total_km += $qtde_km_calculada;

		if (strlen($baixado) > 0) $ja_baixado = true ;
		

		$total_mao_de_obra += $mao_de_obra;
		$mao_de_obra         = $mao_de_obra;
		$pecas_posto         = $pecas;
		$total_pecas        += $pecas ;

		echo "<TR class='Conteudo2' align='center'>\n";
			echo "<TD class='Conteudo' align='left'>";

//===============================================

				if ($retorno==1 AND strlen($nota_fiscal_envio)==0){
					$sql_status = "SELECT status_os, observacao
									FROM tbl_os_status
									WHERE os=$os
									AND status_os IN (72,73,62,64,65,87,88)
									ORDER BY data DESC LIMIT 1";
					$res_status = pg_query($con,$sql_status);
					$resultado = pg_num_rows($res_status);

					if ($resultado==1){
						$status_os          = trim(pg_fetch_result($res_status,0,status_os));
						$status_observacao  = trim(pg_fetch_result($res_status,0,observacao));
						if ($status_os==65){
							if ($login_fabrica==3){
								echo "<br>
									<center>
									<b style='font-size:'15px''>".traduz("este.produto.deve.ser.enviado.para.a.assistencia.tecnica.da.fabrica",$con,$cook_idioma).".</b><br>
									<div style='font-family:verdana;border:1px dashed #666666;padding:10px;width:400px;align:center' align='center'>
										<b style='font-size:14px;color:red'>".strtoupper(traduz("urgente.produto.para.reparo",$con,$cook_idioma))."</b><br><br>
										<b style='font-size:14px'>BRITÂNIA ELETRODOMÉSTICOS LTDA</b>.<br>
										<b style='font-size:12px'>Rua Dona Francisca, 8300 Mod 4 e 5 Bloco A<br>
										Cep 89.239-270 - Joinville - SC<br>
										A/C ASSISTÊNCIA TÉCNICA</b>
									</div></center><br>
								";
							}else{
								echo "<br>
									<center>
									<b style='font-size:'15px''>".traduz("este.produto.deve.ser.enviado.para.a.assistencia.tecnica.da.fabrica",$con,$cook_idioma).".</b><br></center><br>
								";
							}
						}
						if ($status_os==72){
							echo "<br>
								<center>
								<b style='font-size:'15px''>".traduz("pedido.de.peca.necessita.de.autorizacao",$con,$cook_idioma)."</b><br>
								<div style='font-family:verdana;border:1px dashed #666666;padding:10px;width:400px;align:center' align='center'>
									<b style='font-size:12px'>".traduz("a.peca.solicitada.necessita.de.autorizacao",$con,$cook_idioma).". <br>".traduz("aguarde.a.fabrica.analisar.seu.pedido",$con,$cook_idioma).".</b>
								</div></center><br>
							";
						}
						if ($status_os==73){
							echo "<br>
								<center>
								<b style='font-size:'15px''>".traduz("pedido.de.peca.necessita.de.autorizacao",$con,$cook_idioma)."</b><br>
								<div style='font-family:verdana;border:1px dashed #666666;padding:10px;width:400px;align:center' align='center'>
									<b style='font-size:12px'>$status_observacao</b>
								</div></center><br>
							";
						}
					}
				}

				if ($retorno==1 AND strlen($msg_erro)>0){
					if (strpos($msg_erro,'date')){
						//$msg_erro = "Data de envio incorreto!";
					}
					echo "<center>
					<div style='font-family:verdana;width:400px;align:center;background-color:#FF0000' align='center'>
						<b style='font-size:14px;color:white'>".strtoupper(traduz("erro",$con,$cook_idioma))."<br>$msg_erro </b>
					</div></center>";
				}else {
					if (strlen($msg)>0){
						echo "<center>
						<div style='font-family:verdana;width:400px;align:center;' align='center'>
							<b style='font-size:14px;color:black'>".strtoupper(traduz("erro",$con,$cook_idioma))."<br>$msg</b>
						</div></center>";
					}
				}

				if (strlen($msg_erro)>0){
					echo "<center>
					<div style='font-family:verdana;width:400px;align:center;background-color:#FF0000' align='center'>
						<b style='font-size:14px;color:white'>".strtoupper(traduz("erro",$con,$cook_idioma))."<br>$msg_erro</b>
					</div></center>";
				}

				if ($retorno==1 AND !$nota_fiscal_envio AND !$data_nf_envio AND (!$numero_rastreamento_envio OR $login_fabrica==6 OR $login_fabrica == 14)) {
					?>
					<br>
					<form name="frm_consulta" method="post" action="<?echo "$PHP_SELF?os=$os"?>">
						<TABLE width='400' border="1" cellspacing="2" cellpadding="0" align='center' style='border-collapse: collapse' bordercolor='#485989'>
								<TR>
									<TD class="inicio" background='admin/imagens_admin/azul.gif' height='19px'> &nbsp;<? echo traduz("envio.do.produto.a.fabrica",$con,$cook_idioma);?></TD>
								</TR>
								<TR>
									<TD class="subtitulo" height='19px'><? echo strtoupper(traduz("preencha.os.dados.do.envio.do.produto.a.fabrica",$con,$cook_idioma));?></TD>
								</TR>
								<TR>
									<TD class="titulo3"><br>
									<? echo traduz("numero.da.nota.fiscal",$con,$cook_idioma);?>&nbsp;<input class="inpu" type="text" name="txt_nota_fiscal" size="25" maxlength="6" value="<? echo 	$nota_fiscal_envio_p ?>">
									<br>
									<? echo  traduz("data.da.nota.fiscal.do.envio",$con,$cook_idioma);?> &nbsp;<input class="inpu" type="text" name="txt_data_envio" size="25" maxlength="10" value="<? echo $data_envio_p ?>">
									<br>

									<?  if ($login_fabrica <> 6){ ?>
										<? echo traduz("numero.o.objeto.pac",$con,$cook_idioma);?> &nbsp;<input class="inpu" type="text" name="txt_rastreio" size="25" maxlength="13" value="<? echo $numero_rastreio_p ?>"> <br>
										Ex.: SS987654321
										<br>
									<? } ?>

									<center><input type="hidden" name="btn_acao" value="">
									<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_consulta.btn_acao.value == '' ) { document.frm_consulta.btn_acao.value='gravar' ; document.frm_consulta.submit() } else { alert ('<?fecho ("aguarde.submissao",$con,$cook_idioma);?>') }" ALT="<?fecho("gravar.dados",$con,$cook_idioma);?>" id='botao_gravar' border='0' style="cursor:pointer;"></center><br>
									</TD>
								</TR>
						</TABLE>
					</form><br><br>
				<?
				}

				if ($retorno==1 AND $nota_fiscal_envio AND $data_nf_envio AND ($numero_rastreamento_envio OR $login_fabrica==6 or $login_fabrica == 14)) {
					if (strlen($envio_chegada)==0){
						echo "<BR><b style='font-size:14px;color:#990033'>".traduz("o.produto.foi.enviado.mas.a.fabrica.ainda.nao.confirmou.seu.recebimento",$con,$cook_idioma).".<br> .".traduz("aguarde.a.fabrica.confirmar.o.recebimento.efetuar.o.reparo.e.retornar.o.produto.ao.seu.posto",$con,$cook_idioma).".</b><BR>";
					}else {
						if (strlen($data_nf_retorno)==0){
							echo "<BR><b style='font-size:14px;color:#990033'>".traduz("o.produto.foi.recebido.pela.fabrica.em.%",$con,$cook_idioma,array($envio_chegada))."<br> ".traduz("aguarde.a.fabrica.efetuar.o.reparo.e.enviar.ao.seu.posto",$con,$cook_idioma).".</b><BR>";
						}
						else{
							if (strlen($retorno_chegada)==0){
								echo "<BR><b style='font-size:14px;color:#990033'>".traduz("o.reparo.do.produto.foi.feito.pela.fabrica.e.foi.enviado.ao.seu.posto.em.%",$con,$cook_idioma,array($data_nf_retorno))."<br>".traduz("confirme.apos.o.recebimento",$con,$cook_idioma)."</b><BR>";
							}
							else {
								#echo "<BR><b style='font-size:14px;color:#990033'>O REPARO DO PRODUTO FOI FEITO PELA FÁBRICA.</b><BR>";
							}
						}
					}
					?>
					<?
					if ($nota_fiscal_retorno AND $retorno_chegada=="") {?>
					<form name="frm_confirm" method="post" action="<?echo "$PHP_SELF?os=$os&chegada=$os"?>">
						<TABLE width='420' border="1" cellspacing="0" cellpadding="0" align='center' style='border-collapse: collapse' bordercolor='#485989'>
								<TR>
									<TD class="inicio" background='admin/imagens_admin/azul.gif' height='19px'> <?echo traduz("confirme.a.data.do.recebimento",$con,$cook_idioma);?></TD>
								</TR>
							<TR>
								<TD class="subtitulo" height='19px' colspan='2'><?echo traduz("o.produto.foi.enviado.para.seu.posto.confirme.seu.recebimento",$con,$cook_idioma);?></TD>
							</TR>
									<TD class="titulo3"><br>
									<?echo traduz("data.da.chegada.do.produto",$con,$cook_idioma);?>&nbsp;<input class="inpu" type="text" name="txt_data_chegada_posto" size="20" maxlength="10" value=""> <br><br>
									<center>
									<input type="hidden" name="btn_acao" value="">
									<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_confirm.btn_acao.value == '' ) { document.frm_confirm.btn_acao.value='confirmar' ; document.frm_confirm.submit() } else { alert ('<?fecho ("aguarde.submissao",$con,$cook_idioma);?>') }" ALT="<?fecho("gravar.dados",$con,$cook_idioma);?>" id='botao_gravar' border='0' style="cursor:pointer;"></center><br>
									</TD>
								</TR>
						</TABLE>
					</form>
					<?}?>

					<br>
					<TABLE width='420' border="1" cellspacing="0" cellpadding="0" align='center' style='border-collapse: collapse' bordercolor='#485989'>
							<TR>
								<TD class="inicio" background='admin/imagens_admin/azul.gif' height='19px' colspan='2'> &nbsp;<?echo traduz("envio.do.produto.a.fabrica",$con,$cook_idioma);?></TD>
							</TR>
							<TR>
								<TD class="subtitulo" height='19px' colspan='2'><?echo traduz("informacoes.do.envio.do.produto.a.fabrica",$con,$cook_idioma);?></TD>
							</TR>
							<TR>
								<TD class="titulo3"><?echo traduz("numero.da.nota.fiscal.de.envio",$con,$cook_idioma);?> </TD>
								<TD class="conteudo" >&nbsp;<? echo $nota_fiscal_envio ?></TD>
							</TR>
							<TR>
								<TD class="titulo3"><?echo traduz("data.da.nota.fiscal.do.envio",$con,$cook_idioma);?> </TD>
								<TD class="conteudo" >&nbsp;<? echo $data_nf_envio ?></TD>
							</TR>
							<?  if ($login_fabrica <> 6){ ?>
							<TR>
								<TD class="titulo3"><?echo traduz("numero.o.objeto.pac",$con,$cook_idioma);?> </TD>
								<TD class="conteudo" >&nbsp;<? echo "<a href='http://www.websro.com.br/correios.php?P_COD_UNI=$numero_rastreamento_envio"."BR' target='_blank'>$numero_rastreamento_envio</a>" ?></TD>
							</TR>
							<? } ?>
							<TR>
								<TD class="titulo3"><?echo traduz("data.da.chegada.a.fabrica",$con,$cook_idioma);?> </TD>
								<TD class="conteudo" >&nbsp;<? echo $envio_chegada; ?></TD>
							</TR>
							<TR>
								<TD class="inicio" background='admin/imagens_admin/azul.gif' height='19px' colspan='2'> &nbsp;<?echo traduz("retorno.do.produto.da.fabrica.ao.posto",$con,$cook_idioma);?></TD>
							</TR>
							<TR>
								<TD class="subtitulo" height='19px' colspan='2'><?echo traduz("informacoes.do.retorno.do.produto.ao.posto",$con,$cook_idioma);?></TD>
							</TR>
							<TR>
								<TD class="titulo3"><?echo traduz("numero.da.nota.fiscal.do.retorno",$con,$cook_idioma);?> </TD>
								<TD class="conteudo" >&nbsp;<? echo $nota_fiscal_retorno ?></TD>
							</TR>
							<TR>
								<TD class="titulo3"><?echo traduz("data.do.retorno",$con,$cook_idioma);?> </TD>
								<TD class="conteudo" >&nbsp;<? echo $data_nf_retorno ?></TD>
							</TR>
							<?  if ($login_fabrica <> 6){ ?>
							<TR>
								<TD class="titulo3"><?echo traduz("numero.o.objeto.pac.de.retorno",$con,$cook_idioma);?> </TD>
								<TD class="conteudo" >&nbsp;<? echo ($numero_rastreamento_retorno)?"<a href='http://www.websro.com.br/correios.php?P_COD_UNI=$numero_rastreamento_retorno"."BR' target='_blank'>$numero_rastreamento_retorno</a>":""; ?></TD>
							</TR>
							<? } ?>
							<TR>
								<TD class="titulo3" ><?echo traduz("data.da.chegada.ao.posto",$con,$cook_idioma);?></TD>
								<TD class="conteudo" >&nbsp;<? echo $retorno_chegada ?></TD>
							</TR>
					</TABLE>
				<br><br>
				<?
				}
				?>

				<table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
					<tr >
						<td rowspan='4' class='conteudo' width='300' ><center><?echo traduz("os.fabricante",$con,$cook_idioma);?><br>&nbsp;<b><FONT SIZE='3' COLOR='#000000'>
							<strong>
							<?
							if (strlen($consumidor_revenda) > 0) echo $sua_os ."</FONT> - ". $consumidor_revenda;
							else echo $sua_os;
							
							if(strlen($sua_os_offline)>0){
								echo "<table width='300' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>";
								echo "<tr >";
								echo "<td class='conteudo' width='300' height='25' align='center'><BR><center>";
								fecho ("os.off.line",$con,$cook_idioma);
								echo " - $sua_os_offline";
								echo "</center></td>";
								echo "</tr>";
								echo "</table>";
							}
							?>
							</strong>
							</b></center>
						</td>
						<td class='inicio' height='15' colspan='4'></td>
					</TR>
					<TR>
						<td class='titulo' width='100' height='15' align='right'><?echo traduz("abertura",$con,$cook_idioma);?></TD>
						<td class='conteudo' width='100' height='15' align='left'>&nbsp;<?echo $data_abertura?></td>
						<td class='titulo' width='100' height='15' align='right'><?echo traduz("digitacao",$con,$cook_idioma);?></TD>
						<td class='conteudo' width='100' height='15' align='left'>&nbsp;<? echo $data_digitacao ?></td>
					</tr>
					<tr>
						<td class='titulo' width='100' height='15' align='right'><?echo traduz("fechamento",$con,$cook_idioma);?></TD>
						<td class='conteudo' width='100' height='15' id='data_fechamento' align='left'>&nbsp;<? echo $data_fechamento ?></td>
						<td class='titulo' width='100' height='15' align='right'><?echo traduz("finalizada",$con,$cook_idioma);?></TD>
						<td class='conteudo' width='100' height='15' id='finalizada' align='left'>&nbsp;<? echo $data_finalizada ?></td>

					</tr>
					<tr>
						<TD class="titulo"  height='15' align='right'><?echo traduz("data.da.nf",$con,$cook_idioma);?></TD>
						<TD class="conteudo"  height='15' align='left'>&nbsp;<? echo $data_nf ?></TD>
						<td class='titulo' width='100' height='15' align='right'><?echo traduz("fechado.em",$con,$cook_idioma);?></TD>
						<td class='conteudo' width='100' height='15' align='left'>&nbsp;
							<?
							if(strlen($data_fechamento)>0 AND strlen($data_abertura)>0){
											$sql_data = "SELECT SUM(data_fechamento - data_abertura)as final FROM tbl_os WHERE os=$os";
								$resD = pg_query ($con,$sql_data);
								if (pg_num_rows ($resD) > 0) {
									$total_de_dias_do_conserto = pg_fetch_result ($resD,0,'final');
								}
								if($total_de_dias_do_conserto==0) {
									fecho("no.mesmo.dia",$con,$cook_idioma) ;
								}
								else echo $total_de_dias_do_conserto;
								if($total_de_dias_do_conserto==1) {
									echo " ".traduz("dia",$con,$cook_idioma) ;
								}
								if($total_de_dias_do_conserto>1) {
									echo " ".traduz("dias",$con,$cook_idioma);
								}
							}else{
								echo strtoupper(traduz("nao.finalizado",$con,$cook_idioma));
							}
							?>
						</td>
					</tr>
				</table>
				

				<table width='700' border="0" style="border-top:1px solid #D2D2D2" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
					<?
					if(strlen($os) > 0){ // HD 79844
						$sql2="SELECT to_char(data_fabricacao,'DD/MM/YYYY') as data_fabricacao
								FROM tbl_os
								JOIN tbl_numero_serie USING (serie)
								WHERE os=$os ";
						$res2=pg_query($con,$sql2);
						if(pg_num_rows($res2) > 0){
							$data_fabricacao = pg_fetch_result($res2,0,data_fabricacao);
						}
					}
					?>

					<tr >
						<TD class="titulo" height='15' width='90'><?echo traduz("referencia",$con,$cook_idioma);?></TD>
						<TD class="conteudo" height='15' >&nbsp;<? echo $produto_referencia ?></TD>
						<TD class="titulo" height='15' width='90'><?echo traduz("descricao",$con,$cook_idioma);?></TD>
						<TD class="conteudo" height='15' >&nbsp;<? echo $produto_descricao ?></TD>
						<TD class="titulo" height='15' width='90'><?
							echo traduz("n.de.serie",$con,$cook_idioma);
							?>
							&nbsp;
						</TD>
						<TD class="conteudo" height='15'>&nbsp;<? echo $serie ?>&nbsp;</TD>
						<?if(strlen($data_fabricacao) > 0){?>
							<TD class="titulo" height='15' width='90'>DATA FABRICAÇÃO</TD>
							<TD class="conteudo" height='15'>&nbsp;<? echo $data_fabricacao ?>&nbsp;</TD>
						<?}?>
					</tr>
				</table>







				<? if (strlen($aparencia_produto) > 0) { ?>
					<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
					<TR>
						<td class='titulo' height='15' width='300'><?echo traduz("aparencia.geral.do.aparelho.produto",$con,$cook_idioma);?></TD>
						<td class="conteudo">&nbsp;<? echo $aparencia_produto ?></td>
					</TR>
					</TABLE>
				<? } ?>

				<? if (strlen($acessorios) > 0) { ?>
					<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
					<TR>
						<TD class='titulo' height='15' width='300'><?echo traduz("acessorios.deixados.junto.com.o.aparelho",$con,$cook_idioma);?></TD>
						<TD class="conteudo">&nbsp;<? echo $acessorios; ?></TD>
					</TR>
					</TABLE>
				<? } ?>

				<? if (strlen($defeito_reclamado) > 0) { ?>
				<TABLE width="700px" border="0" cellspacing="1" cellpadding="0" align='center'class='Tabela'>
					<TR>
						<TD class='titulo' height='15'width='300'>&nbsp;<?echo traduz("informacoes.sobre.o.defeito",$con,$cook_idioma);?></TD>
						<TD class="conteudo" >&nbsp;
							<?
							if (strlen($defeito_reclamado) > 0) {
								$sql = "SELECT tbl_defeito_reclamado.descricao
										FROM   tbl_defeito_reclamado
										WHERE  tbl_defeito_reclamado.defeito_reclamado = '$defeito_reclamado'";
								$res_def = pg_query ($con,$sql);

								if (pg_num_rows($res_def) > 0) {
									$descricao_defeito = trim(pg_fetch_result($res_def,0,descricao));

									echo $descricao_defeito ." - ".$defeito_reclamado_descricao;
								}
							}
							?>
						</TD>
					</TR>
				</TABLE>
				<? } ?>


				<TABLE width="700px" border="0" style="border-top:1px solid #D2D2D2" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
					<TR>
						<TD class="titulo" height='15' width='90' align='right'><?echo traduz("reclamado",$con,$cook_idioma);?></TD>
						<TD class="conteudo" height='15' width='140' align='left'> &nbsp;<?
							echo $descricao_defeito ; if($defeito_reclamado_descricao)echo " - ".$defeito_reclamado_descricao;?>
						</TD>
						<TD class="titulo" height='15' width='90' align='right'><? if($login_fabrica==20){echo traduz("reparo",$con,$cook_idioma);}else echo traduz("constatado",$con,$cook_idioma);?> </TD>
						<td class="conteudo" height='15' align='left'>&nbsp;
							<?
							echo $defeito_constatado_descricao;
							?>
						</TD>
					</TR>

					<TR>
						<TD class="titulo" height='15' width='90'>
							<?
							echo traduz("solucao",$con,$cook_idioma);
							?>
							&nbsp;
						</td>
						<td class="conteudo" colspan='3' height='15'>&nbsp;
							<?
							if (strlen($solucao_os)>0){
								//chamado 1451 - não estava validando a data...
								$sql_data = "SELECT SUM(validada - '2006-11-05')as total_dias FROM tbl_os WHERE os=$os";
								$resD = pg_query ($con,$sql_data);
								if (pg_num_rows ($resD) > 0) {
									$total_dias = pg_fetch_result ($resD,0,total_dias);
								}
								//if($ip=="201.27.30.194") echo $total_dias;
								if ( ($total_dias > 0 AND $login_fabrica==6) OR ($login_fabrica==11)  or $login_fabrica==15 or $login_fabrica==3 or $login_fabrica == 50){
									$sql="select descricao from tbl_solucao where solucao=$solucao_os and fabrica=$login_fabrica limit 1";
									$xres = pg_query($con, $sql);
									if (pg_num_rows($xres)>0){
										$xsolucao = trim(pg_fetch_result($xres,0,descricao));
										echo "$xsolucao";
									}else{
										$xsql="SELECT descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";
										$xres = pg_query($con, $xsql);
										$xsolucao = trim(@pg_fetch_result($xres,0,descricao));
										echo "$xsolucao";
									}
								//if($ip=="201.27.30.194") echo $sql;
								}else{
									$xsql="SELECT descricao from tbl_servico_realizado where servico_realizado= $solucao_os limit 1";
									$xres = pg_query($con, $xsql);
									if (pg_num_rows($xres)>0){
										$xsolucao = trim(pg_fetch_result($xres,0,descricao));
										echo "$xsolucao  - $data_digitacao";
									}else{
										$sql="select descricao from tbl_solucao where solucao=$solucao_os and     fabrica=$login_fabrica limit 1";
										$xres = pg_query($con, $sql);
										$xsolucao = trim(pg_fetch_result($xres,0,descricao));
										echo "$xsolucao";
									}
								}
							}
							?>
						</TD>
					</TR>
				</TABLE>

				<TABLE width="700px" border="0" style="border-top:1px solid #D2D2D2" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
					<TR>
						<TD class="titulo" height='15' align='right'>Consumidor</TD>
						<TD class="conteudo" height='15' width='300' align='left'>&nbsp;<? echo $consumidor_nome ?></TD>
						<TD class="titulo" align='right'><?echo traduz("fone1",$con,$cook_idioma);?></TD>
						<TD class="conteudo" height='15' align='left'>&nbsp;<? echo $consumidor_fone ?></TD>
					</TR>
					<TR>
						<TD class="titulo" height='15' align='right'><?echo traduz("cpf.consumidor",$con,$cook_idioma);?></TD>
						<TD class="conteudo" height='15' align='left'>&nbsp;<? echo $consumidor_cpf ?></TD>
						<TD class="titulo" height='15' align='right'><?echo traduz("cep",$con,$cook_idioma);?></TD>
						<TD class="conteudo" height='15' align='left'>&nbsp;<? echo $consumidor_cep ?></TD>
					</TR>
					<TR>
						<TD class="titulo" height='15' align='right'><?// callcenter=true ?>
							<?echo traduz("endereco",$con,$cook_idioma);?></TD>
						<TD class="conteudo" height='15' align='left'>&nbsp;<? echo $consumidor_endereco ?></TD>
						<TD class="titulo" height='15' align='right'><?echo traduz("numero",$con,$cook_idioma);?></TD>
						<TD class="conteudo" height='15' align='left'>&nbsp;<? echo $consumidor_numero ?></TD>
					</TR>
					<TR>
						<TD class="titulo" height='15' align='right'><?echo traduz("complemento",$con,$cook_idioma);?></TD>
						<TD class="conteudo" height='15' align='left'>&nbsp;<? echo $consumidor_complemento ?></TD>
						<TD class="titulo" height='15' align='right'><?echo traduz("bairro",$con,$cook_idioma);?></TD>
						<TD class="conteudo" height='15' align='left'>&nbsp;<? echo $consumidor_bairro ?></TD>
					</TR>
					<TR>
						<TD class="titulo" align='right'><?echo traduz("cidade",$con,$cook_idioma);?></TD>
						<TD class="conteudo" align='left'>&nbsp;<? echo $consumidor_cidade ?></TD>
						<TD class="titulo" align='right'><?echo traduz("estado",$con,$cook_idioma);?></TD>
						<TD class="conteudo" align='left'>&nbsp;<? echo $consumidor_estado ?></TD>
					</TR>
					<TR>
						<TD class="titulo" align='right'><?echo traduz("email",$con,$cook_idioma);?></TD>
						<TD class="conteudo" align='left'>&nbsp;<? echo $consumidor_email ?></TD>
						<TD class="titulo">&nbsp;</TD>
						<TD class="conteudo">&nbsp;</TD>
					</TR>
				</TABLE>

				<?
				/*COLORMAQ TEM 2 REVENDAS*/
				$sql = "SELECT
							cnpj,
							to_char(data_venda, 'dd/mm/yyyy') as data_venda
						FROM tbl_numero_serie
						WHERE serie = trim('$serie')";
				$res_serie = pg_query ($con,$sql);

				if (pg_num_rows ($res_serie) > 0) {


					$txt_cnpj       = trim(pg_fetch_result($res_serie,0,cnpj));
					$data_venda = trim(pg_fetch_result($res_serie,0,data_venda));

					$sql = "SELECT      tbl_revenda.nome              ,
										tbl_revenda.revenda           ,
										tbl_revenda.cnpj              ,
										tbl_revenda.cidade            ,
										tbl_revenda.fone              ,
										tbl_revenda.endereco          ,
										tbl_revenda.numero            ,
										tbl_revenda.complemento       ,
										tbl_revenda.bairro            ,
										tbl_revenda.cep               ,
										tbl_revenda.email             ,
										tbl_cidade.nome AS nome_cidade,
										tbl_cidade.estado
							FROM        tbl_revenda
							LEFT JOIN   tbl_cidade USING (cidade)
							LEFT JOIN   tbl_estado using(estado)
							WHERE       tbl_revenda.cnpj ='$txt_cnpj' ";

					$res_revenda = pg_query ($con,$sql);

					# HD 31184 - Francisco Ambrozio (06/08/08) - detectei que pode haver
					#   casos em que o SELECT acima não retorna resultado nenhum.
					#   Acrescentei o if para que não dê erros na página.
					$msg_revenda_info = "";
					if (pg_num_rows ($res_revenda) > 0) {
						$revenda_nome_1       = trim(pg_fetch_result($res_revenda,0,nome));
						$revenda_cnpj_1       = trim(pg_fetch_result($res_revenda,0,cnpj));

						$revenda_bairro_1     = trim(pg_fetch_result($res_revenda,0,bairro));
						$revenda_cidade_1     = trim(pg_fetch_result($res_revenda,0,cidade));
						$revenda_fone_1       = trim(pg_fetch_result($res_revenda,0,fone));
					}else{
						$msg_revenda_info = traduz("nao.foi.possivel.obter.informacoes.da.revenda.cliente.colormaq.nome.cnpj.e.telefone",$con,$cook_idioma);
					}
					?>

					<TABLE width="700px" border="0" style="border-top:1px solid #D2D2D2" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
						<? if (strlen($msg_revenda_info) > 0){
										echo "<tr>";
										echo "<td class='conteudo' colspan= '4' height='15'><center>$msg_revenda_info</center></td>";
										echo "</tr>";
									} ?>
						<TR>
							<TD class="titulo"  height='15' >Revenda</TD>
							<TD class="conteudo"  height='15' width='300'>&nbsp;<? echo $revenda_nome_1 ?></TD>
							<TD class="titulo"  height='15' width='80'><?echo traduz("cnpj.revenda",$con,$cook_idioma);?></TD>
							<TD class="conteudo"  height='15'>&nbsp;<? echo $revenda_cnpj_1 ?></TD>
						</TR>
						<TR>
						<?//HD 6701 15529 Para posto 4260 Ivo Cardoso mostra a nota fiscal?>
							<TD class="titulo"  height='15'><?echo traduz("fone",$con,$cook_idioma);?></TD>
							<TD class="conteudo"  height='15'>&nbsp;<?=$revenda_fone_1?></TD>
							<TD class="titulo"  height='15'>&nbsp;<?echo traduz("data.da.nf",$con,$cook_idioma);?></TD>
							<TD class="conteudo"  height='15'>&nbsp;<?=$data_venda; ?></TD>
						</TR>
					</TABLE>
				<?
				}
				/*COLORMAQ TEM 2 REVENDAS - FIM*/
				?>

				<TABLE width="700px" border="0" style="border-top:1px solid #D2D2D2" cellspacing="1" cellpadding="0" align='center' class='Tabela'>
					<TR>
						<TD class="titulo"  height='15' >Revenda</TD>
						<TD class="conteudo"  height='15' width='300'>&nbsp;<? echo $revenda_nome ?></TD>
						<TD class="titulo"  height='15' width='80'><?echo traduz("cnpj.revenda",$con,$cook_idioma);?></TD>
						<TD class="conteudo"  height='15'>&nbsp;<? echo $revenda_cnpj ?></TD>
					</TR>
					<TR>
						<?//HD 6701 15529 Para posto 4260 Ivo Cardoso mostra a nota fiscal?>
						<TD class="titulo"  height='15'><?echo traduz("nf.numero",$con,$cook_idioma);?></TD>
						<TD class="conteudo vermelho"  height='15'>&nbsp;<? echo $nota_fiscal ?></FONT></TD>
						<TD class="titulo"  height='15'><?echo traduz("data.da.nf",$con,$cook_idioma);?></TD>
						<TD class="conteudo"  height='15'>&nbsp;<? echo $data_nf; ?></TD>
					</TR>
					<TR>
						<TD class="titulo"  height='15' ><?echo traduz("fone",$con,$cook_idioma);?></TD>
						<TD class="conteudo"  height='15' width='300'>&nbsp;<? echo $revenda_fone ?></TD>
						 <TD class="titulo"  height='15'></TD>
						<TD class="conteudo"  height='15'>&nbsp;</TD>
					</TR>
				</TABLE>

				<?
				$sql = "SELECT  tbl_produto.referencia                                        ,
								tbl_produto.descricao                                         ,
								tbl_os_produto.serie                                          ,
								tbl_os_produto.versao                                         ,
								tbl_os_item.serigrafia                                        ,
								tbl_os_item.pedido    AS pedido                               ,
								tbl_os_item.peca                                              ,
								TO_CHAR (tbl_os_item.digitacao_item,'DD/MM') AS digitacao_item,
								tbl_defeito.descricao AS defeito                              ,
								tbl_peca.referencia   AS referencia_peca                      ,
								tbl_os_item_nf.nota_fiscal                                    ,
								tbl_peca.descricao    AS descricao_peca                       ,
								tbl_servico_realizado.descricao AS servico_realizado_descricao,
								tbl_status_pedido.descricao     AS status_pedido              ,
								tbl_produto.referencia          AS subproduto_referencia      ,
								tbl_produto.descricao           AS subproduto_descricao       ,
								tbl_lista_basica.posicao
						FROM    tbl_os_produto
						JOIN    tbl_os_item USING (os_produto)
						JOIN    tbl_produto USING (produto)
						JOIN    tbl_peca    USING (peca)
						JOIN    tbl_lista_basica       ON  tbl_lista_basica.produto = tbl_os_produto.produto
													   AND tbl_lista_basica.peca    = tbl_peca.peca
						LEFT JOIN    tbl_defeito USING (defeito)
						LEFT JOIN    tbl_servico_realizado USING (servico_realizado)
						LEFT JOIN    tbl_os_item_nf    ON  tbl_os_item.os_item      = tbl_os_item_nf.os_item
						LEFT JOIN    tbl_pedido        ON  tbl_os_item.pedido       = tbl_pedido.pedido
						LEFT JOIN    tbl_status_pedido ON  tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
						WHERE   tbl_os_produto.os = $os
						ORDER BY tbl_peca.descricao";

				# HD 153693
				$ordem = " ORDER BY tbl_peca.descricao ";

				$sql = "/*( */
						SELECT  tbl_produto.referencia                                         ,
								tbl_produto.descricao                                          ,
								tbl_os_produto.serie                                           ,
								tbl_os_produto.versao                                          ,
								tbl_os_item.os_item                                            ,
								tbl_os_item.serigrafia                                         ,
								tbl_os_item.pedido                                             ,
								tbl_os_item.pedido_item                                        ,
								tbl_os_item.peca                                               ,
								tbl_os_item.posicao                                            ,
								tbl_os_item.obs                                                ,
								tbl_os_item.custo_peca                                         ,
								tbl_os_item.servico_realizado AS servico_realizado_peca        ,
								tbl_os_item.peca_serie                                         ,
								tbl_os_item.peca_serie_trocada                                 ,
								TO_CHAR (tbl_os_item.digitacao_item,'DD/MM') AS digitacao_item ,
								case
									when tbl_pedido.pedido_blackedecker > 499999 then
										lpad ((tbl_pedido.pedido_blackedecker-500000)::text,5,'0')
									when tbl_pedido.pedido_blackedecker > 399999 then
										lpad ((tbl_pedido.pedido_blackedecker-400000)::text,5,'0')
									when tbl_pedido.pedido_blackedecker > 299999 then
										lpad ((tbl_pedido.pedido_blackedecker-300000)::text,5,'0')
									when tbl_pedido.pedido_blackedecker > 199999 then
										lpad ((tbl_pedido.pedido_blackedecker-200000)::text,5,'0')
									when tbl_pedido.pedido_blackedecker > 99999 then
										lpad ((tbl_pedido.pedido_blackedecker-100000)::text,5,'0')
								else
									lpad(tbl_pedido.pedido_blackedecker::text,5,'0')
								end                                      AS pedido_blackedecker,
								tbl_pedido.seu_pedido                    AS seu_pedido         ,
								tbl_pedido.distribuidor                                        ,
								tbl_defeito.descricao           AS defeito                     ,
								tbl_peca.referencia             AS referencia_peca             ,
								tbl_peca.bloqueada_garantia     AS bloqueada_pc                ,
								tbl_peca.peca_critica           AS peca_critica                ,
								tbl_peca.retorna_conserto       AS retorna_conserto            ,
								tbl_peca.devolucao_obrigatoria  AS devolucao_obrigatoria       ,
								tbl_os_item_nf.nota_fiscal                                     ,
								TO_CHAR(tbl_os_item_nf.data_nf,'DD/MM/YYYY') AS data_nf        ,
								tbl_peca.descricao              AS descricao_peca              ,
								tbl_servico_realizado.descricao AS servico_realizado_descricao ,
								tbl_status_pedido.descricao     AS status_pedido               ,
								tbl_produto.referencia          AS subproduto_referencia       ,
								tbl_produto.descricao           AS subproduto_descricao        ,
								tbl_os_item.preco                                              ,
								tbl_os_item.qtde                                               ,
								tbl_os_item.faturamento_item    AS faturamento_item
						FROM    tbl_os_produto
						JOIN    tbl_os_item USING (os_produto)
						JOIN    tbl_produto USING (produto)
						JOIN    tbl_peca    USING (peca)
						LEFT JOIN tbl_defeito USING (defeito)
						LEFT JOIN tbl_servico_realizado USING (servico_realizado)
						LEFT JOIN tbl_os_item_nf     ON tbl_os_item.os_item      = tbl_os_item_nf.os_item
						LEFT JOIN tbl_pedido         ON tbl_os_item.pedido       = tbl_pedido.pedido
						LEFT JOIN tbl_status_pedido  ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
						WHERE   tbl_os_produto.os = $os
						$ordem
						/*
					)UNION(
						SELECT  tbl_produto.referencia                                         ,
								tbl_produto.descricao                                          ,
								NULL                   AS  serie                               ,
								NULL                   AS  versao                              ,
								tbl_orcamento_item.orcamento_item                              ,
								NULL                   AS serigrafia                           ,
								tbl_orcamento_item.pedido                                      ,
								tbl_orcamento_item.pedido_item                                 ,
								tbl_orcamento_item.peca                                        ,
								NULL AS posicao                                                ,
								NULL AS obs                                                    ,
								NULL as custo_peco                                             ,
								tbl_orcamento_item.servico_realizado AS servico_realizado_peca ,
								tbl_os_item.peca_serie                                         ,
								tbl_os_item.peca_serie_trocada                                 ,
								NULL AS digitacao_item                                         ,
								CASE WHEN tbl_pedido.pedido_blackedecker > 99999 then
									 LPAD((tbl_pedido.pedido_blackedecker - 100000)::text,5,'0')
								ELSE
									LPAD(tbl_pedido.pedido_blackedecker::text,5,'0')
								end                                      AS pedido_blackedecker,
								tbl_pedido.seu_pedido           AS seu_pedido                  ,
								tbl_pedido.distribuidor                                        ,
								tbl_defeito.descricao           AS defeito                     ,
								tbl_peca.referencia             AS referencia_peca             ,
								tbl_peca.bloqueada_garantia     AS bloqueada_pc                ,
								tbl_peca.peca_critica           AS peca_critica                ,
								tbl_peca.retorna_conserto       AS retorna_conserto            ,
								tbl_peca.devolucao_obrigatoria  AS devolucao_obrigatoria       ,
								NULL AS nota_fiscal                                            ,
								NULL AS data_nf                                                ,
								tbl_peca.descricao              AS descricao_peca              ,
								tbl_servico_realizado.descricao AS servico_realizado_descricao ,
								tbl_status_pedido.descricao     AS status_pedido               ,
								tbl_produto.referencia          AS subproduto_referencia       ,
								tbl_produto.descricao           AS subproduto_descricao        ,
								tbl_orcamento_item.preco                                       ,
								tbl_orcamento_item.qtde                                        ,
								NULL AS faturamento_item
						FROM    tbl_os
						JOIN    tbl_orcamento ON tbl_orcamento.os = tbl_os.os
						JOIN    tbl_orcamento_item ON tbl_orcamento_item.orcamento = tbl_orcamento.orcamento
						JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
						JOIN    tbl_peca    USING (peca)
						LEFT JOIN tbl_defeito USING (defeito)
						LEFT JOIN tbl_servico_realizado USING (servico_realizado)
						LEFT JOIN tbl_pedido         ON tbl_orcamento_item.pedido       = tbl_pedido.pedido
						LEFT JOIN tbl_status_pedido  ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido
						WHERE   tbl_os.os = $os
						ORDER BY tbl_peca.descricao
					)*/";
				// Adicionei Este UNION - Fabio 09-10-2007
				$res_peca = pg_query($con,$sql);
				$total = pg_num_rows($res_peca);
				?>

				<TABLE width="700px" border="1" bordercolor="#D2D2D2" style="border-collapse:collapse; margin-bottom:10px" cellspacing="1" cellpadding="0" align='center'  class='Tabela'>
					<TR>
						<?
						if($os_item_subconjunto == 't') {
							echo "<TD class='titulo2'>".traduz("subconjunto",$con,$cook_idioma)."</TD>";
							echo "<TD class='titulo2'>".traduz("posicao",$con,$cook_idioma)."</TD>";
						}
						?>
						<TD class="titulo2">
							<? echo traduz("componente",$con,$cook_idioma); ?>
						</TD>
						<TD class="titulo2">
						<? echo traduz("qtd",$con,$cook_idioma); ?></TD>
						<TD class="titulo2"><?echo traduz("digita",$con,$cook_idioma);?></TD>
						<TD class="titulo2">
							<? echo traduz("defeito",$con,$cook_idioma);?>
						</TD>
						<TD class="titulo2">
							<? echo traduz("servico",$con,$cook_idioma);?>
						</TD>
						<TD class="titulo2"><?echo traduz("pedido",$con,$cook_idioma);?></TD>
						<TD class="titulo2"><?echo traduz("nota.fiscal",$con,$cook_idioma);?></TD>
						<TD class="titulo2"><?echo traduz("emissao",$con,$cook_idioma);?></TD>
					</TR>

					<?
					# Exibe legenda de Peças de Retorno Obrigatório para a Gama
					$exibe_legenda = 0;
					for ($j = 0 ; $j < $total ; $j++) {
						$pedido                  = trim(pg_fetch_result($res_peca,$j,pedido));
						$pedido_item             = trim(pg_fetch_result($res_peca,$j,pedido_item));
						$pedido_blackedecker     = trim(pg_fetch_result($res_peca,$j,pedido_blackedecker));
						$seu_pedido              = trim(pg_fetch_result($res_peca,$j,seu_pedido));
						$os_item                 = trim(pg_fetch_result($res_peca,$j,os_item));
						$peca                    = trim(pg_fetch_result($res_peca,$j,peca));
						$faturamento_item        = trim(pg_fetch_result($res_peca,$j,faturamento_item));
						$nota_fiscal         = trim(pg_fetch_result($res_peca,$j,nota_fiscal));
						$data_nf             = trim(pg_fetch_result($res_peca,$j,data_nf));
						$status_pedido           = trim(pg_fetch_result($res_peca,$j,status_pedido));
						$obs_os_item             = trim(pg_fetch_result($res_peca,$j,obs));
						$distribuidor            = trim(pg_fetch_result($res_peca,$j,distribuidor));
						$digitacao               = trim(pg_fetch_result($res_peca,$j,digitacao_item));
						$preco                   = trim(pg_fetch_result($res_peca,$j,preco));
						$descricao_peca          = trim(pg_fetch_result($res_peca,$j,descricao_peca));
						$preco                   = number_format($preco,2,',','.');
						$peca_serie              = trim(pg_fetch_result($res_peca,$j,peca_serie));
						$peca_serie_trocada      = trim(pg_fetch_result($res_peca,$j,peca_serie_trocada));

						/*Nova forma de pegar o número do Pedido - SEU PEDIDO  HD 34403 */
						if (strlen($seu_pedido)>0){
							$pedido_blackedecker = fnc_so_numeros($seu_pedido);
						}
			
						/*====--------- INICIO DAS NOTAS FISCAIS ----------===== */
						if (strlen ($nota_fiscal) == 0){
							if (strlen($pedido) > 0) {
								$sql  = "SELECT trim(tbl_faturamento.nota_fiscal) As nota_fiscal         ,
												TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao
										FROM    tbl_faturamento
										JOIN    tbl_faturamento_item USING (faturamento)
										WHERE   tbl_faturamento.pedido    = $pedido
										AND     tbl_faturamento_item.peca = $peca ";
								$resx = pg_query ($con,$sql);

								if (pg_num_rows ($resx) > 0) {
									$nf      = trim(pg_fetch_result($resx,0,nota_fiscal));
									$data_nf = trim(pg_fetch_result($resx,0,emissao));
									$link = 1;
								}else{
									$condicao_01 = "";
									if (strlen ($distribuidor) > 0) {
										$condicao_01 = " AND tbl_faturamento.distribuidor = $distribuidor ";
									}
									$sql  = "SELECT
												trim(tbl_faturamento.nota_fiscal)                AS nota_fiscal ,
												TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY')   AS emissao,
												tbl_faturamento.posto                            AS posto
											FROM    tbl_faturamento
											JOIN    tbl_faturamento_item USING (faturamento)
											WHERE   tbl_faturamento_item.pedido = $pedido
											AND     tbl_faturamento_item.peca   = $peca
											$condicao_01 ";
									$resx = pg_query ($con,$sql);

									if (pg_num_rows ($resx) > 0) {
										$nf           = trim(pg_fetch_result($resx,0,nota_fiscal));
										$data_nf      = trim(pg_fetch_result($resx,0,emissao));
										$link         = 1;
									}else{
										$nf      = "Pendente";
										$data_nf = "";
										$link    = 1;
									}
								}
							}else{
								$nf = "";
								$data_nf = "";
								$link = 0;
							}
						}else{
							$nf = $nota_fiscal;
						}

						// $status_os -> variavel pegada lá em cima
						$msg_peca_intervencao="";

						$bloqueada_pc           = pg_fetch_result($res_peca,$j,bloqueada_pc);
						$peca_critica           = pg_fetch_result($res_peca,$j,peca_critica);
						$servico_realizado_peca = pg_fetch_result($res_peca,$j,servico_realizado_peca);
						$retorna_conserto       = pg_fetch_result($res_peca,$j,retorna_conserto);

						$devolucao_obrigatoria  = pg_fetch_result($res_peca,$j,devolucao_obrigatoria);

						?>
						<TR class="conteudo">
							<?
							if($os_item_subconjunto == 't') {
								echo "<TD style=\"text-align:left; font-size:10px;\">".pg_fetch_result($res_peca,$j,subproduto_referencia) . " - " . pg_fetch_result($res_peca,$j,subproduto_descricao)."</TD>";
								echo "<TD style=\"text-align:center;\">".pg_fetch_result($res_peca,$j,posicao)."</TD>";
							}
							?>
							<TD style="text-align:left; font-size:10px;"><? echo pg_fetch_result($res_peca,$j,referencia_peca) . " - " . $descricao_peca; echo $msg_peca_intervencao?></TD>
							<TD style="text-align:center; font-size:10px;"><? echo pg_fetch_result($res_peca,$j,qtde) ?></TD>
							<TD style="text-align:center; font-size:10px;"><? echo pg_fetch_result($res_peca,$j,digitacao_item) ?></TD>
							<TD style="text-align:right; font-size:10px;"><?  echo pg_fetch_result($res_peca,$j,defeito); ?></TD>
							<TD style="text-align:right; font-size:10px;"><?  echo pg_fetch_result($res_peca,$j,servico_realizado_descricao) ?></TD>
							<TD style="text-align:CENTER; font-size:10px;">
								<?php echo $pedido; ?>
							</TD>
							<TD style="text-align:CENTER; font-size:10px;" nowrap <? if (strlen($data_nf)==0) echo "colspan='2'"; ?>>
								<?
								if (strtolower($nf) <> 'pendente' and strtolower($nf) <> 'atendido'){
									if ($link == 1) {
										echo $nf;
									}else{
										echo "<acronym title='Nota Fiscal do fabricante.' style='cursor:help;'> $nf ";
									}
								}else{
									$sql  = "SELECT * FROM tbl_pedido_cancelado WHERE os=$os AND peca=$peca and pedido=$pedido";
									$resY = pg_query ($con,$sql);
									if (pg_num_rows ($resY) > 0) {
										echo "<acronym title='".pg_fetch_result ($resY,0,motivo)."'>Cancelado</acronym>" ;
									} else {
										if( strtolower($nf) <> 'atendido'){
										echo "<acronym title='".traduz("pendente.com.o.fabricante",$con,$cook_idioma).".' style='cursor:help;'>";
										}
										echo "$nf &nbsp;";
									}
								}
								?>
							</TD>

							<?//incluido data de emissao por Wellington chamado 141 help-desk
							if (strlen($data_nf) > 0){
								echo "<TD style='text-align:CENTER; font-size:10px;' nowrap>";
									echo "$data_nf ";
								echo "</TD>";
							}
							?>
						</TR>
					<?
					}
					?>
				</TABLE>
				<?php
//=================================================

			echo "</TD>\n";
			echo "<td align='right' style='padding-right:5px; font-weight:bold'> " . number_format ($mao_de_obra,2,",",".") . "</td>\n";
			echo "<td align='right' style='padding-right:5px; font-weight:bold'> " . number_format ($pecas_posto,2,",",".") . "</td>\n";
		echo "	</TR>\n";
	}
	
	echo "<tr class='Conteudo'>\n";
	echo "<td colspan></td>\n";

	echo "<td align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b>  " . number_format ($total_mao_de_obra,2,",",".") . "</b></td>\n";
	echo "<td align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b>  " . number_format ($total_km,2,",",".") . "</b></td>\n";

	echo "</tr>\n";
	
	echo "<tr class='Conteudo'>\n";
	echo "<td  align=\"center\" style='padding-right:2px'><b>";
	if($sistema_lingua == "ES") echo "TOTAL (MO + PIEZAS)";
	else                        echo "TOTAL (MO + Peças)";
	echo "</b></td>\n";
	echo "<td colspan=\"2\" bgcolor='$cor' align='center'><b>  " . number_format ($total_mao_de_obra + $total_mao_de_obra_revenda + $total_pecas + $total_pecas_revenda +$total_km,2,",",".") . "</b></td>\n";

	echo "</tr>\n";
	echo "</TABLE>\n";

	echo "<tr>";
	echo "<td>";

	##### LANÇAMENTO DE EXTRATO AVULSO - INÍCIO #####
	$sql =	"SELECT tbl_lancamento.descricao         ,
					tbl_extrato_lancamento.historico ,
					tbl_extrato_lancamento.valor     
			FROM tbl_extrato_lancamento
			JOIN tbl_lancamento USING (lancamento)
			WHERE tbl_extrato_lancamento.extrato = $extrato
			AND   tbl_lancamento.fabrica = $login_fabrica";
	$res_avulso = pg_exec($con,$sql);

	if (pg_numrows($res_avulso) > 0) {
		echo "<br></br>";
		echo "<table width='650' align='center' border='1' cellspacing='0' cellpadding='1' style='border-collapse: collapse' bordercolor='#000000'>\n";
		echo "<tr class='Titulo'>\n";
		echo "<td class='Conteudo' nowrap colspan='3'><B>LANÇAMENTO DE EXTRATO AVULSO<B></td>\n";
		echo "</tr>\n";
		echo "<tr class='Titulo'>\n";
		echo "<td class='Titulo' nowrap nowrap><B>DESCRIÇÃO</B></td>\n";
		echo "<td class='Titulo' align='center' nowrap><B>HISTÓRICO</B></td>\n";
		echo "<td class='Titulo' align='center' nowrap><B>VALOR</B></td>\n";
		echo "</tr>\n";
		for ($j = 0 ; $j < pg_numrows($res_avulso) ; $j++) {
			$cor = ($j % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

			echo "<tr class='Conteudo' >\n";
			echo "<td class='Conteudo' width='45%'>" . pg_result($res_avulso, $j, descricao) . "&nbsp;</td>";
			echo "<td class='Conteudo' width='45%'>" . pg_result($res_avulso, $j, historico) . "&nbsp;</td>";
			echo "<td class='Conteudo' width='10%' align='right'> " . number_format( pg_result($res_avulso, $j, valor), 2, ',', '.') . "&nbsp;</td>";
			echo "</tr>";
		}
			echo "</table>\n";
	echo "</td>";
	echo "</tr>";
	}
}





echo "</TABLE>\n";

?>

<BR>

<? if ($ja_baixado == true) { ?>
<TABLE width='650' border='1' cellspacing='1' cellpadding='0' align='center' style='border-collapse: collapse' bordercolor='#000000'>
<TR>
	<TD height='20' class="Conteudo" colspan='4'>PAGAMENTO</TD>
</TR>
<TR>
	<TD align='left' class="Conteudo" width='20%'>EXTRATO PAGO EM: </TD>
	<TD class="Conteudo" width='15%'><? echo $baixado; ?></TD>
	<TD align='left' class="Conteudo" width='15%'><center>OBSERVAÇÃO:</center></TD>
	<TD class="Conteudo" width='50%'><? echo $obs;?>
	</td>
</TR>
</TABLE>
<? } ?>

<br>

<p>

<script>
	window.print();
</script>
