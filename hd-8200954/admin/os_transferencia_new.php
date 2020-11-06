<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="call_center";
include "autentica_admin.php";

include "funcoes.php";

$btn_acao = strtoupper($_POST['btn_acao']);
$erro = "";

if (strlen(trim($_POST["btn_acao"])) > 0)     $btn_acao     = strtoupper(trim($_POST["btn_acao"]));
if (strlen(trim($_POST["sua_os"])) > 0)       $sua_os       = trim($_POST["sua_os"]);
if (strlen(trim($_POST["posto_codigo"])) > 0) $posto_codigo = trim($_POST["posto_codigo"]);
if (strlen(trim($_POST["posto_nome"])) > 0)   $posto_nome   = trim($_POST["posto_nome"]);

if ($btn_acao == "CONSULTAR") {

	if (strlen($sua_os) == 0) $erro .= " Preencha o campo OS Fabricante para efetuar a pesquisa. ";

}

if ($btn_acao == "continuar") {
	$os = $_POST['os'];
	
	$sua_os = $_POST['sua_os'];
	if (strlen (trim ($sua_os)) == 0) {
		$sua_os = 'null';
		if ($pedir_sua_os == 't') {
			$msg_erro .= " Digite o número da OS Fabricante.";
		}
	}else{
		if ($login_fabrica <> 1) {
			if (strlen($sua_os) < 6) {
				$sua_os = "000000" . trim ($sua_os);
				$sua_os = substr ($sua_os,strlen ($sua_os) - 6 , 6);
			}
			if (strlen($sua_os) > 6) {
				$sua_os = substr ($sua_os, strlen ($sua_os) - 6 , 6) ;
			}
		}
		$sua_os = "'" . $sua_os . "'" ;
	}

	##### INÍCIO DA VALIDAÇÃO DOS CAMPOS #####
	$produto_referencia = strtoupper(trim($_POST['produto_referencia']));
	$produto_referencia = str_replace("-","",$produto_referencia);
	$produto_referencia = str_replace(" ","",$produto_referencia);
	$produto_referencia = str_replace("/","",$produto_referencia);
	$produto_referencia = str_replace(".","",$produto_referencia);

	if (strlen($produto_referencia) == 0) {
		$produto_referencia = 'null';
		$msg_erro .= " Digite o produto.";
	}else{
		$produto_referencia = "'".$produto_referencia."'" ;
	}

	$xdata_abertura = fnc_formata_data_pg(trim($_POST['data_abertura']));
	if ($xdata_abertura == 'null') $msg_erro .= " Digite a data de abertura da OS.";
	$cdata_abertura = str_replace("'","",$xdata_abertura);
	
	if (strlen(trim($_POST['consumidor_nome'])) == 0) $xconsumidor_nome = 'null';
	else $xconsumidor_nome = "'".str_replace("'","",trim($_POST['consumidor_nome']))."'";

	$consumidor_cpf = str_replace("-","",trim($_POST['consumidor_cpf']));
	$consumidor_cpf = str_replace(".","",$consumidor_cpf);
	$consumidor_cpf = str_replace("/","",$consumidor_cpf);
	$consumidor_cpf = str_replace(" ","",$consumidor_cpf);
	$consumidor_cpf = trim(substr($consumidor_cpf,0,14));

	if (strlen(trim($_POST['consumidor_cpf'])) == 0) $xconsumidor_cpf = 'null';
	else             $xconsumidor_cpf = "'".$consumidor_cpf."'";

	if (strlen(trim($_POST['consumidor_cidade'])) == 0) $xconsumidor_cidade = 'null';
	else             $xconsumidor_cidade = "'".trim($_POST['consumidor_cidade'])."'";

	if (strlen(trim($_POST['consumidor_cidade'])) == 0) $xconsumidor_cidade = 'null';
	else             $xconsumidor_cidade = "'".trim($_POST['consumidor_cidade'])."'";

	if (strlen(trim($_POST['consumidor_estado'])) == 0) $xconsumidor_estado = 'null';
	else             $xconsumidor_estado = "'".trim($_POST['consumidor_estado'])."'";

	if (strlen(trim($_POST['consumidor_fone'])) == 0) $xconsumidor_fone = 'null';
	else             $xconsumidor_fone = "'".trim($_POST['consumidor_fone'])."'";

	$revenda_cnpj = str_replace("-","",trim($_POST['revenda_cnpj']));
	$revenda_cnpj = str_replace(".","",$revenda_cnpj);
	$revenda_cnpj = str_replace(" ","",$revenda_cnpj);
	$revenda_cnpj = str_replace("/","",$revenda_cnpj);
	$revenda_cnpj = substr($revenda_cnpj,0,14);

	if (strlen($revenda_cnpj) <> 0 AND strlen($revenda_cnpj) <> 14) $msg_erro .= " Tamanho do CNPJ da revenda inválido.";

	if (strlen($revenda_cnpj) == 0) $xrevenda_cnpj = 'null';
	else             $xrevenda_cnpj = "'".$revenda_cnpj."'";

	if (strlen(trim($_POST['revenda_nome'])) == 0) $xrevenda_nome = 'null';
	else $xrevenda_nome = "'".str_replace("'","",trim($_POST['revenda_nome']))."'";

	if (strlen(trim($_POST['revenda_fone'])) == 0) $xrevenda_fone = 'null';
	else $xrevenda_fone = "'".str_replace("'","",trim($_POST['revenda_fone']))."'";

	if (strlen(trim($_POST['nota_fiscal'])) == 0) $xnota_fiscal = 'null';
	else             $xnota_fiscal = "'".trim($_POST['nota_fiscal'])."'";

	if ($login_fabrica == 14){
		if ($xnota_fiscal == 'null'){
			$msg_erro = "Digite o número da nota fiscal.";
		}
	}

	if (strlen ($_POST['troca_faturada']) == 0) $xtroca_faturada = 'null';
	else        $xtroca_faturada = "'".trim($_POST['troca_faturada'])."'";

	$xdata_nf = fnc_formata_data_pg(trim($_POST['data_nf']));
	if ($xdata_nf == null AND $xtroca_faturada <> 't') $msg_erro .= " Digite a data de compra.";

	if (strlen(trim($_POST['produto_serie'])) == 0) $xproduto_serie = 'null';
	else         $xproduto_serie = "'". strtoupper(trim($_POST['produto_serie'])) ."'";

	if (strlen(trim($_POST['codigo_fabricacao'])) == 0) $xcodigo_fabricacao = 'null';
	else             $xcodigo_fabricacao = "'".trim($_POST['codigo_fabricacao'])."'";

	if (strlen(trim($_POST['aparencia_produto'])) == 0) $xaparencia_produto = 'null';
	else             $xaparencia_produto = "'".trim($_POST['aparencia_produto'])."'";

	if (strlen(trim($_POST['acessorios'])) == 0) $xacessorios = 'null';
	else             $xacessorios = "'".trim($_POST['acessorios'])."'";

	if (strlen(trim($_POST['defeito_reclamado_descricao'])) == 0) $xdefeito_reclamado_descricao = 'null';
	else             $xdefeito_reclamado_descricao = "'".trim($_POST['defeito_reclamado_descricao'])."'";

	if (strlen(trim($_POST['obs'])) == 0) $xobs = 'null';
	else             $xobs = "'".trim($_POST['obs'])."'";

	if (strlen(trim($_POST['quem_abriu_chamado'])) == 0) $xquem_abriu_chamado = 'null';
	else             $xquem_abriu_chamado = "'".trim($_POST['quem_abriu_chamado'])."'";

	if (strlen($_POST['consumidor_revenda']) == 0) $msg_erro .= " Selecione consumidor ou revenda.";
	else                                $xconsumidor_revenda = "'".$_POST['consumidor_revenda']."'";

	if (strlen($_POST['type']) == 0) $xtype = 'null';
	else             $xtype = "'".$_POST['type']."'";

	if (strlen($_POST['satisfacao']) == 0) $xsatisfacao = "'f'";
	else             $xsatisfacao = "'".$_POST['satisfacao']."'";

	if (strlen ($_POST['laudo_tecnico']) == 0) $xlaudo_tecnico = 'null';
	else        $xlaudo_tecnico = "'".trim($_POST['laudo_tecnico'])."'";


	if ($login_fabrica == 14 ){
		if (strlen($produto_referencia) > 0 AND (strlen($xproduto_serie) == 0 OR $xproduto_serie == 'null')) {
			$sql = "SELECT  tbl_produto.numero_serie_obrigatorio
					FROM    tbl_produto
					JOIN    tbl_linha on tbl_linha.linha = tbl_produto.linha
					WHERE   upper(tbl_produto.referencia) = upper($produto_referencia)
					AND     tbl_linha.fabrica = $login_fabrica";
			$res = pg_exec($con,$sql);

			if (pg_numrows($res) > 0) {
				$numero_serie_obrigatorio = trim(pg_result($res,0,numero_serie_obrigatorio));
				
				if ($numero_serie_obrigatorio == 't') {
					$msg_erro .= "<br>Nº de Série $produto_referencia é obrigatório.";
				}
			}
		}
	}

	##### FIM DA VALIDAÇÃO DOS CAMPOS #####

	$os_reincidente = "'f'";
	
	##### Verificação se o nº de série é reincidente para a Tectoy #####
	if ($login_fabrica == 6) {
		$sqlX = "SELECT to_char (current_date - INTERVAL '30 days', 'YYYY-MM-DD')";
		$resX = pg_exec($con,$sqlX);
		$data_inicial = pg_result($resX,0,0);
		
		$sqlX = "SELECT to_char (current_date + INTERVAL '1 day', 'YYYY-MM-DD')";
		$resX = pg_exec($con,$sqlX);
		$data_final = pg_result($resX,0,0);

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
					$msg_erro .= "Nº de Série $produto_serie digitado é reincidente.<br>
					Favor reabrir a ordem de serviço $xxxsua_os e acrescentar itens.";
				}else{
					$os_reincidente = "'t'";
				}
			}
		}
	}

	##### Verificação se o nº de série é reincidente para a Britânia #####
	if ($login_fabrica == 3 and 1 == 2) {
		$sqlX = "SELECT to_char (current_date - INTERVAL '30 days', 'YYYY-MM-DD')";
		$resX = pg_exec($con,$sqlX);
		$data_inicial = pg_result($resX,0,0);
		
		$sqlX = "SELECT to_char (current_date, 'YYYY-MM-DD')";
		$resX = pg_exec($con,$sqlX);
		$data_final = pg_result($resX,0,0);
		
		if (strlen($produto_serie) > 0) {
			$sql = "SELECT  tbl_os.os            ,
							tbl_os.sua_os        ,
							tbl_os.data_digitacao
					FROM    tbl_os
					JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
					WHERE   tbl_os.serie   = '$produto_serie'
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_produto.numero_serie_obrigatorio IS TRUE
					AND     tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'
					ORDER BY tbl_os.data_digitacao DESC
					LIMIT 1";
			$res = pg_exec($con,$sql);
			
			if (pg_numrows($res) > 0) {
				$msg_erro .= "Nº de Série $produto_serie digitado é reincidente. Favor verificar.<br>Em caso de dúvida, entre em contato com a Fábrica.";
			}
		}
	}

	if ($login_fabrica == 7) $xdata_nf = $xdata_abertura;

#	if (strlen ($consumidor_cpf) <> 0 and strlen ($consumidor_cpf) <> 11 and strlen ($consumidor_cpf) <> 14) $msg_erro .= 'Tamanho do CPF/CNPJ do cliente inválido';

#	if ($login_fabrica == 1 AND strlen($consumidor_cpf) == 0) $msg_erro .= 'Tamanho do CPF/CNPJ do cliente inválido';

	$produto = 0;

	if (strlen($_POST['produto_voltagem']) == 0)	$voltagem = "null";
	else											$voltagem = "'". $_POST['produto_voltagem'] ."'";

	$sql = "SELECT tbl_produto.produto
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  UPPER(tbl_produto.referencia_pesquisa) = UPPER($produto_referencia) ";
	if ($login_fabrica == 1) $sql .= " AND UPPER(tbl_produto.voltagem) = UPPER($voltagem)";
	$sql .= " AND    tbl_linha.fabrica      = $login_fabrica
			AND    tbl_produto.ativo IS TRUE";
	$res = pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		$msg_erro = " Produto $produto_referencia não cadastrado";
	}else{
		$produto = @pg_result ($res,0,0);
	}
	
	if ($xtroca_faturada <> "'t'") { // verifica troca faturada para a Black
	
	if (strlen($msg_erro) == 0) {
		
		$sql = "SELECT garantia FROM tbl_produto WHERE tbl_produto.produto = $produto";
		$res = @pg_exec ($con,$sql);
		
		if (@pg_numrows($res) == 0) {
			$msg_erro = " Produto $produto_referencia sem garantia";
		}
		
		if (strlen($msg_erro) == 0) {
			$garantia = trim(@pg_result($res,0,garantia));
			
			$sql = "SELECT ($xdata_nf::date + (($garantia || ' months')::interval))::date;";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			
			if (strlen($msg_erro) > 0) $msg_erro =  "Data da NF inválida.";
			
			if (strlen($msg_erro) == 0) {
				if (pg_numrows ($res) > 0) {
					$data_final_garantia = trim(pg_result($res,0,0));
				}
				
				if ($data_final_garantia < $cdata_abertura) {
					$msg_erro = " Produto $produto_referencia fora da garantia, vencida em ". substr($data_final_garantia,8,2) ."/". substr($data_final_garantia,5,2) ."/". substr($data_final_garantia,0,4);
				}
			}
		}
	}

	}

	if ($login_fabrica == 1) {
		$sql =	"SELECT tbl_familia.familia, tbl_familia.descricao
				FROM tbl_produto
				JOIN tbl_familia USING (familia)
				WHERE tbl_familia.fabrica = $login_fabrica
				AND   tbl_familia.familia = 347
				AND   tbl_produto.produto = $produto;";
		$res = pg_exec($con,$sql);
		if (pg_numrows($res) > 0) {
			$xtipo_os_cortesia = "'Compressor'";
		}else{
			$xtipo_os_cortesia = 'null';
		}
	}else{
		$xtipo_os_cortesia = 'null';
	}

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$os_offline = $_POST['os_offline'];
	if (strlen ($os_offline) == 0) $os_offline = "null";

	
	if (strlen($msg_erro) == 0){
		/*================ INSERE NOVA OS =========================*/

		if (strlen($os) == 0) {
			$sql =	"INSERT INTO tbl_os (
						posto                                                          ,
						fabrica                                                        ,
						sua_os                                                         ,
						data_abertura                                                  ,
						cliente                                                        ,
						revenda                                                        ,
						consumidor_nome                                                ,
						consumidor_cpf                                                 ,
						consumidor_cidade                                              ,
						consumidor_estado                                              ,
						consumidor_fone                                                ,
						revenda_cnpj                                                   ,
						revenda_nome                                                   ,
						revenda_fone                                                   ,
						nota_fiscal                                                    ,
						data_nf                                                        ,
						produto                                                        ,
						serie                                                          ,
						codigo_fabricacao                                              ,
						aparencia_produto                                              ,
						acessorios                                                     ,
						defeito_reclamado_descricao                                    ,
						obs                                                            ,
						quem_abriu_chamado                                             ,
						consumidor_revenda                                             ,
						type                                                           ,
						satisfacao                                                     ,
						laudo_tecnico                                                  ,
						tipo_os_cortesia                                               ,
						troca_faturada                                                 ,
						os_offline                                                     ,
						os_reincidente
					) VALUES (
						$login_posto                                                   ,
						$login_fabrica                                                 ,
						$sua_os                                                        ,
						$xdata_abertura                                                ,
						(SELECT cliente FROM tbl_cliente WHERE cpf = $xconsumidor_cpf) ,
						(SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj)  ,
						$xconsumidor_nome                                              ,
						$xconsumidor_cpf                                               ,
						$xconsumidor_cidade                                            ,
						$xconsumidor_estado                                            ,
						$xconsumidor_fone                                              ,
						$xrevenda_cnpj                                                 ,
						$xrevenda_nome                                                 ,
						$xrevenda_fone                                                 ,
						$xnota_fiscal                                                  ,
						$xdata_nf                                                      ,
						$produto                                                       ,
						$xproduto_serie                                                ,
						$xcodigo_fabricacao                                            ,
						$xaparencia_produto                                            ,
						$xacessorios                                                   ,
						$xdefeito_reclamado_descricao                                  ,
						$xobs                                                          ,
						$xquem_abriu_chamado                                           ,
						$xconsumidor_revenda                                           ,
						$xtype                                                         ,
						$xsatisfacao                                                   ,
						$xlaudo_tecnico                                                ,
						$xtipo_os_cortesia                                             ,
						$xtroca_faturada                                               ,
						$os_offline                                                    ,
						$os_reincidente
					);";
		}else{
			$sql =	"UPDATE tbl_os SET
						cliente                     = (SELECT cliente FROM tbl_cliente WHERE cpf = $xconsumidor_cpf)                                   ,
						revenda                     = (SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj)                                   ,
						consumidor_nome             = $xconsumidor_nome                 ,
						consumidor_cpf              = $xconsumidor_cpf                  ,
						consumidor_cidade           = $xconsumidor_cidade               ,
						consumidor_estado           = $xconsumidor_estado               ,
						consumidor_fone             = $xconsumidor_fone                 ,
						revenda_cnpj                = $xrevenda_cnpj                    ,
						revenda_nome                = $xrevenda_nome                    ,
						revenda_fone                = $xrevenda_fone                    ,
						nota_fiscal                 = $xnota_fiscal                     ,
						data_nf                     = $xdata_nf                         ,
						serie                       = $xproduto_serie                   ,
						codigo_fabricacao           = $xcodigo_fabricacao               ,
						aparencia_produto           = $xaparencia_produto               ,
						defeito_reclamado_descricao = $xdefeito_reclamado_descricao     ,
						consumidor_revenda          = $xconsumidor_revenda              ,
						type                        = $xtype                            ,
						satisfacao                  = $xsatisfacao                      ,
						laudo_tecnico               = $xlaudo_tecnico                   ,
						troca_faturada              = $xtroca_faturada                  ,
						tipo_os_cortesia            = $xtipo_os_cortesia                
					WHERE os      = $os
					AND   fabrica = $login_fabrica
					AND   posto   = $login_posto;";
		}

//if ( $ip == '201.0.9.216') echo nl2br($sql);

		$sql_OS = $sql;
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);

		if (strlen ($msg_erro) == 0) {
			if (strlen($os) == 0) {
				$res = @pg_exec ($con,"SELECT CURRVAL ('seq_os')");
				$os  = pg_result ($res,0,0);
			}

			$sql = "UPDATE tbl_os SET consumidor_nome = tbl_cliente.nome WHERE tbl_os.os = $os AND tbl_os.cliente IS NOT NULL AND tbl_os.cliente = tbl_cliente.cliente";
			$res = @pg_exec ($con,$sql);
//if ($ip = '192.168.0.55') echo "$sql<br>";

			$sql = "UPDATE tbl_os SET consumidor_cidade = tbl_cidade.nome , consumidor_estado = tbl_cidade.estado WHERE tbl_os.os = $os AND tbl_os.cliente IS NOT NULL AND tbl_os.consumidor_cidade IS NULL AND tbl_os.cliente = tbl_cliente.cliente AND tbl_cliente.cidade = tbl_cidade.cidade";
			$res = @pg_exec ($con,$sql);
		}
	}

	if (strlen ($msg_erro) == 0) {
		if (strlen($os) == 0) {
			$res = @pg_exec ($con,"SELECT CURRVAL ('seq_os')");
			$os  = pg_result ($res,0,0);
		}
		
		$res      = @pg_exec ($con,"SELECT fn_valida_os($os, $login_fabrica)");
		$msg_erro = pg_errormessage($con);

		#--------- grava OS_EXTRA ------------------
		if (strlen ($msg_erro) == 0) {
			$taxa_visita				= str_replace (",",".",trim ($_POST['taxa_visita']));
			$visita_por_km				= trim ($_POST['visita_por_km']);
			$hora_tecnica				= str_replace (",",".",trim ($_POST['hora_tecnica']));
			$regulagem_peso_padrao		= str_replace (",",".",trim ($_POST['regulagem_peso_padrao']));
			$certificado_conformidade	= str_replace (",",".",trim ($_POST['certificado_conformidade']));
			$valor_diaria				= str_replace (",",".",trim ($_POST['valor_diaria']));

			if (strlen ($taxa_visita)				== 0) $taxa_visita					= '0';
			if (strlen ($visita_por_km)				== 0) $visita_por_km				= 'f';
			if (strlen ($hora_tecnica)				== 0) $hora_tecnica					= '0';
			if (strlen ($regulagem_peso_padrao)		== 0) $regulagem_peso_padrao		= '0';
			if (strlen ($certificado_conformidade)	== 0) $certificado_conformidade		= '0';
			if (strlen ($valor_diaria)				== 0) $valor_diaria					= '0';

			$sql = "UPDATE tbl_os_extra SET
						taxa_visita              = $taxa_visita             ,
						visita_por_km            = '$visita_por_km'         ,
						hora_tecnica             = $hora_tecnica            ,
						regulagem_peso_padrao    = $regulagem_peso_padrao   ,
						certificado_conformidade = $certificado_conformidade,
						valor_diaria             = $valor_diaria ";
			
			if ($os_reincidente == "'t'") {
				$sql .= ", os_reincidente = $xxxos ";
			}
			
			$sql .= "WHERE tbl_os_extra.os = $os";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);

			if (strlen ($msg_erro) == 0) {
				$res = pg_exec ($con,"COMMIT TRANSACTION");

				header ("Location: os_cadastro_adicional.php?os=$os");
				exit;
			}
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}

	}else{
		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf\"") > 0)
		$msg_erro = " Data da compra maior que a data da abertura da Ordem de Serviço.";
		
		if (strpos ($msg_erro,"tbl_os_unico") > 0)
			$msg_erro = " O Número da Ordem de Serviço do fabricante já esta cadastrado.";

		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}

/*================ LE OS DA BASE DE DADOS =========================*/
$os = $_GET['os'];
if (strlen ($os) > 0) {
	$sql =	"SELECT tbl_os.sua_os                                                    ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura      ,
					tbl_os.consumidor_nome                                           ,
					tbl_os.consumidor_cpf                                            ,
					tbl_os.consumidor_cidade                                         ,
					tbl_os.consumidor_fone                                           ,
					tbl_os.consumidor_estado                                         ,
					tbl_os.revenda_cnpj                                              ,
					tbl_os.revenda_nome                                              ,
					tbl_os.nota_fiscal                                               ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')       AS data_nf            ,
					tbl_os.consumidor_revenda                                        ,
					tbl_os.aparencia_produto                                         ,
					tbl_os.codigo_fabricacao                                         ,
					tbl_os.type                                                      ,
					tbl_os.satisfacao                                                ,
					tbl_os.laudo_tecnico                                             ,
					tbl_os.tipo_os_cortesia                                          ,
					tbl_os.serie                                                     ,
					tbl_os.troca_faturada                                            ,
					tbl_produto.referencia                     AS produto_referencia ,
					tbl_produto.descricao                      AS produto_descricao  ,
					tbl_produto.voltagem                       AS produto_voltagem   
			FROM tbl_os
			JOIN      tbl_produto  ON tbl_produto.produto       = tbl_os.produto
			LEFT JOIN tbl_os_extra ON tbl_os.os                 = tbl_os_extra.os
			WHERE tbl_os.os = $os
			AND   tbl_os.posto = $login_posto
			AND   tbl_os.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows ($res) == 1) {
		$sua_os				= pg_result ($res,0,sua_os);
		$data_abertura		= pg_result ($res,0,data_abertura);
		$consumidor_nome	= pg_result ($res,0,consumidor_nome);
		$consumidor_cpf 	= pg_result ($res,0,consumidor_cpf);
		$consumidor_cidade	= pg_result ($res,0,consumidor_cidade);
		$consumidor_fone	= pg_result ($res,0,consumidor_fone);
		$consumidor_estado	= pg_result ($res,0,consumidor_estado);
		$revenda_cnpj		= pg_result ($res,0,revenda_cnpj);
		$revenda_nome		= pg_result ($res,0,revenda_nome);
		$nota_fiscal		= pg_result ($res,0,nota_fiscal);
		$data_nf			= pg_result ($res,0,data_nf);
		$consumidor_revenda	= pg_result ($res,0,consumidor_revenda);
		$aparencia_produto	= pg_result ($res,0,aparencia_produto);
		$codigo_fabricacao	= pg_result ($res,0,codigo_fabricacao);
		$type				= pg_result ($res,0,type);
		$satisfacao			= pg_result ($res,0,satisfacao);
		$laudo_tecnico		= pg_result ($res,0,laudo_tecnico);
		$tipo_os_cortesia	= pg_result ($res,0,tipo_os_cortesia);
		$produto_serie		= pg_result ($res,0,serie);
		$produto_referencia	= pg_result ($res,0,produto_referencia);
		$produto_descricao	= pg_result ($res,0,produto_descricao);
		$produto_voltagem	= pg_result ($res,0,produto_voltagem);
		$troca_faturada		= pg_result ($res,0,troca_faturada);
	}
}


/*============= RECARREGA FORM EM CASO DE ERRO ==================*/

if (strlen ($msg_erro) > 0) {
	$os					= $_POST['os'];
	$sua_os				= $_POST['sua_os'];
	$data_abertura		= $_POST['data_abertura'];
	$consumidor_nome	= $_POST['consumidor_nome'];
	$consumidor_cpf 	= $_POST['consumidor_cpf'];
	$consumidor_cidade	= $_POST['consumidor_cidade'];
	$consumidor_fone	= $_POST['consumidor_fone'];
	$consumidor_estado	= $_POST['consumidor_estado'];
	$revenda_cnpj		= $_POST['revenda_cnpj'];
	$revenda_nome		= $_POST['revenda_nome'];
	$nota_fiscal		= $_POST['nota_fiscal'];
	$data_nf			= $_POST['data_nf'];
	$produto_referencia	= $_POST['produto_referencia'];
	$produto_descricao	= $_POST['produto_descricao'];
	$produto_voltagem	= $_POST['produto_voltagem'];
	$produto_serie		= $_POST['produto_serie'];
	$cor				= $_POST['cor'];
	$consumidor_revenda	= $_POST['consumidor_revenda'];

	$type				= $_POST['type'];
	$satisfacao			= $_POST['satisfacao'];
	$laudo_tecnico		= $_POST['laudo_tecnico'];

	$obs				= $_POST['obs'];
//	$chamado			= $_POST['chamado'];
	$quem_abriu_chamado = $_POST['quem_abriu_chamado'];
	$taxa_visita				= $_POST['taxa_visita'];
	$visita_por_km				= $_POST['visita_por_km'];
	$hora_tecnica				= $_POST['hora_tecnica'];
	$regulagem_peso_padrao		= $_POST['regulagem_peso_padrao'];
	$certificado_conformidade	= $_POST['certificado_conformidade'];
	$valor_diaria				= $_POST['valor_diaria'];
}

$body_onload = "javascript: document.frm_os.sua_os.focus()";

/* PASSA PARÂMETRO PARA O CABEÇALHO (não esquecer ===========*/

/* $title = Aparece no sub-menu e no título do Browser ===== */
$title = "Cadastro de Ordem de Serviço"; 

/* $layout_menu = Determina a aba em destaque do MENU ===== */
$layout_menu = 'os';

include "cabecalho.php";
?>

<style type="text/css">
.Menu {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596D9B;
	background-color: #D9E2EF;
}
.Conteudo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color:#000000;
	background-color: #FFFFFF;
}
.Tabela {
	border: 1px solid #CED7E7;
}
</style>

<script language="JavaScript" type="text/javascript">
var req;

function loadXMLDoc(url, campo) {
	req = null;

	/* Procura por um objeto nativo (Mozilla/Safari) */
	if (window.XMLHttpRequest) {
		req = new XMLHttpRequest();
		req.onreadystatechange = processReqChange;
		req.open("GET", url, true);
		req.send(null);

	/* Procura por uma versão ActiveX (IE) */
	} else if (window.ActiveXObject) {
		req = new ActiveXObject("Microsoft.XMLHTTP");
		if (req) {
			req.onreadystatechange = function processReqChange() {
								// Apenas quando o estado for "completado" */
								if (req.readyState == 4) { 
									/* Apenas se o servidor retornar "OK" */
									if (req.status == 200) { 
										// procura pela div id="dados_os" e insere o conteudo 
										// retornado nela, como texto HTML
										alert(req.responseText);
										document.getElementById(campo).innerHTML = req.responseText;
									}else{
										alert("Houve um problema ao obter os dados:\n" + req.statusText);
									}
								}
							}
			req.open("GET", url, true);
			req.send();
		}
	}
}

function processReqChange() {
	// Apenas quando o estado for "completado" */
	if (req.readyState == 4) { 
		/* Apenas se o servidor retornar "OK" */
		if (req.status == 200) { 
			// procura pela div id="dados_os" e insere o conteudo 
			// retornado nela, como texto HTML 
			document.getElementById('dados_os').innerHTML = req.responseText; 
		}else{
			alert("Houve um problema ao obter os dados:\n" + req.statusText);
		}
	}
}

function ConsultarOS(sua_os, posto_codigo) {
	loadXMLDoc("os_transferencia_consulta_os.php?sua_os=" + sua_os + "&posto_codigo=" + posto_codigo, 'dados_os');
}

function ConsultarPosto(codigo, nome) {
	loadXMLDoc("os_transferencia_consulta_os.php?posto_codigo_destino=" + codigo + "&posto_nome_destino=" + nome, 'dados_posto');
}
</script>

<? include "javascript_pesquisas.php" ?>

<? if (strlen($erro) > 0) { ?>
<table width="650" border="0" cellpadding="0" cellspacing="0" align="center">
	<tr>
		<td valign="middle" align="center" class="error"><?echo $erro?></td>
	</tr>
</table>
<br>
<? } ?>

<form name="frm_tranferencia" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="btn_acao" value="">

<table width="650" border="0" cellpadding="0" cellspacing="2" align="center" class="Tabela">
	<tr class="Menu">
		<td colspan="3">INFORMAÇÕES PARA CONSULTA</td>
	</tr>
	<tr class="Menu">
		<td>OS FABRICANTE</td>
		<td>CÓDIGO DO POSTO</td>
		<td>NOME DO POSTO</td>
	</tr>
	<tr>
		<td><input type="text" name="sua_os" size="20" value="<?echo $sua_os?>" class="frm" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui a OS do Fabricante.');"></td>
		<td><input type="text" name="posto_codigo_origem" size="15" value="<?echo $posto_codigo?>" class="frm" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o código do posto e clique na lupa para efetuar a pesquisa.');">&nbsp;<img border="0" src="imagens/btn_buscar5.gif" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_tranferencia.posto_codigo_origem, document.frm_tranferencia.posto_nome_origem, 'codigo')" style="cursor: pointer;"></td>
		<td><input type="text" name="posto_nome_origem" size="50" value="<?echo $posto_nome?>" class="frm" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o nome do posto e clique na lupa para efetuar a pesquisa.');">&nbsp;<img border="0" src="imagens/btn_buscar5.gif" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_tranferencia.posto_codigo_origem, document.frm_tranferencia.posto_nome_origem, 'nome')" style="cursor: pointer;"></td>
	</tr>
</table>

<br>

<img border="0" src="imagens/btn_pesquisar_400.gif" onclick="javascript: ConsultarOS (document.frm_tranferencia.sua_os.value, document.frm_tranferencia.posto_codigo_origem.value);" ALT="Consultar OS Fabricante" style="cursor: pointer;">

<br>

<? if (strlen($sua_os) > 0) { ?>
<script language="JavaScript" type="text/javascript">
	ConsultarOS (document.frm_tranferencia.sua_os.value, document.frm_tranferencia.posto_codigo_origem.value);
</script>
<? } ?>

<div id="dados_os"></div>

<br>

<table width="650" border="0" cellpadding="0" cellspacing="2" align="center" class="Tabela">
	<tr class="Menu">
		<td colspan="3">INFORMAÇÕES PARA CONSULTA</td>
	</tr>
	<tr class="Menu">
		<td>CÓDIGO DO POSTO</td>
		<td>NOME DO POSTO</td>
	</tr>
	<tr>
		<td><input type="text" name="posto_codigo_destino" size="15" value="<?echo $posto_codigo?>" class="frm" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o código do posto e clique na lupa para efetuar a pesquisa.');">&nbsp;<img border="0" src="imagens/btn_buscar5.gif" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_tranferencia.posto_codigo_destino, document.frm_tranferencia.posto_nome_destino, 'codigo')" style="cursor: pointer;"></td>
		<td><input type="text" name="posto_nome_destino" size="50" value="<?echo $posto_nome?>" class="frm" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o nome do posto e clique na lupa para efetuar a pesquisa.');">&nbsp;<img border="0" src="imagens/btn_buscar5.gif" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_tranferencia.posto_codigo_destino, document.frm_tranferencia.posto_nome_destino, 'nome')" style="cursor: pointer;"></td>
	</tr>
</table>

<br>

<img border="0" src="imagens/btn_pesquisar_400.gif" onclick="javascript: ConsultarPosto (document.frm_tranferencia.posto_codigo_destino.value, document.frm_tranferencia.posto_nome_destino.value);" ALT="Consultar OS Fabricante" style="cursor: pointer;">

<br>

<? if (strlen($posto_codigo_destino) > 0) { ?>
<script language="JavaScript" type="text/javascript">
	ConsultarPosto (document.frm_tranferencia.posto_codigo_destino.value, document.frm_tranferencia.posto_nome_destino.value);
</script>
<? } ?>

<div id="dados_posto"></div>

<br>

<img border="0" src="imagens/btn_alterarcinza.gif" onclick="javascript: alert(document.all.getElementById('dados_posto').fone.value);" ALT="Consultar OS Fabricante" style="cursor: pointer;">
<!--<img border="0" src="imagens/btn_alterarcinza.gif" onclick="javascript: if (document.frm_tranferencia.btn_acao.value == '' ) { document.frm_tranferencia.btn_acao.value='ALTERAR' ; document.frm_tranferencia.submit() } else { alert ('Aguarde submissão') }" ALT="Consultar OS Fabricante" style="cursor: pointer;">-->

</form>

<br>

<? include "rodape.php";?>