<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


if ($login_fabrica <> 1 OR ( $login_tipo_posto <> 90 AND $login_tipo_posto <> 36 AND $login_tipo_posto <> 82 AND $login_tipo_posto <> 83 AND $login_tipo_posto <> 84 ) ) {
	header ("Location: os_cadastro.php");
	exit;
}

include 'funcoes.php';

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = @pg_exec($con,$sql);
$pedir_sua_os = pg_result ($res,0,pedir_sua_os);
$pedir_defeito_reclamado_descricao = pg_result ($res,0,pedir_defeito_reclamado_descricao);

/*======= <PHP> FUNÇOES DOS BOTÕES DE AÇÃO =========*/

$btn_acao = strtolower ($_POST['btn_acao']);

$msg_erro = "";

if ($btn_acao == "continuar") {

	$tipo_os = "7";

	$os = $_POST['os'];

	$sua_os = $_POST['sua_os'];
	if (strlen (trim ($sua_os)) == 0) {
		$sua_os = 'null';
		if ($pedir_sua_os == 't') {
			$msg_erro .= " Digite o número da OS Fabricante.";
		}
	}else{
		$sua_os = "'" . $sua_os . "'" ;
	}

	##### INÍCIO DA VALIDAÇÃO DOS CAMPOS #####

	$tipo_atendimento = $_POST['tipo_atendimento'];
	if (strlen (trim ($tipo_atendimento)) == 0) $tipo_atendimento = 'null';

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

	##############################################################

	if (strlen(trim($_POST['consumidor_nome'])) == 0) $xconsumidor_nome = 'null';
	else $xconsumidor_nome = "'".str_replace("'","",trim($_POST['consumidor_nome']))."'";

	$consumidor_cpf = trim($_POST['consumidor_cpf']);
	$consumidor_cpf = str_replace("-","",$consumidor_cpf);
	$consumidor_cpf = str_replace(".","",$consumidor_cpf);
	$consumidor_cpf = str_replace("/","",$consumidor_cpf);
	$consumidor_cpf = str_replace(" ","",$consumidor_cpf);
	$consumidor_cpf = trim(substr($consumidor_cpf,0,14));

	if (strlen($consumidor_cpf) == 0) $xconsumidor_cpf = 'null';
	else                              $xconsumidor_cpf = "'".$consumidor_cpf."'";

	if (strlen(trim($_POST['consumidor_cidade'])) == 0) $xconsumidor_cidade = 'null';
	else             $xconsumidor_cidade = "'".trim($_POST['consumidor_cidade'])."'";

	if (strlen(trim($_POST['consumidor_cidade'])) == 0) $xconsumidor_cidade = 'null';
	else             $xconsumidor_cidade = "'".trim($_POST['consumidor_cidade'])."'";

	if (strlen(trim($_POST['consumidor_estado'])) == 0) $xconsumidor_estado = 'null';
	else             $xconsumidor_estado = "'".trim($_POST['consumidor_estado'])."'";

	if (strlen(trim($_POST['consumidor_fone'])) == 0) $xconsumidor_fone = 'null';
	else             $xconsumidor_fone = "'".trim($_POST['consumidor_fone'])."'";

	$revenda_cnpj = trim($_POST['revenda_cnpj']);
	$revenda_cnpj = str_replace("-","",$revenda_cnpj);
	$revenda_cnpj = str_replace(".","",$revenda_cnpj);
	$revenda_cnpj = str_replace(" ","",$revenda_cnpj);
	$revenda_cnpj = str_replace("/","",$revenda_cnpj);
	$revenda_cnpj = substr($revenda_cnpj,0,14);

	if (strlen($revenda_cnpj) <> 0 AND strlen($revenda_cnpj) <> 14) $msg_erro .= " Tamanho do CNPJ da revenda inválido.";

	if (strlen($revenda_cnpj) == 0) $xrevenda_cnpj = 'null';
	else                            $xrevenda_cnpj = "'".$revenda_cnpj."'";

	if (strlen(trim($_POST['revenda_nome'])) == 0) $xrevenda_nome = 'null';
	else $xrevenda_nome = "'".str_replace("'","",trim($_POST['revenda_nome']))."'";

	if (strlen(trim($_POST['revenda_fone'])) == 0) $xrevenda_fone = 'null';
	else $xrevenda_fone = "'".str_replace("'","",trim($_POST['revenda_fone']))."'";

	if (strlen(trim($_POST['nota_fiscal'])) == 0) $xnota_fiscal = 'null';
	else             $xnota_fiscal = "'".trim($_POST['nota_fiscal'])."'";

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

	if (strlen($_POST['satisfacao']) == 0) $xsatisfacao = "'f'";
	else             $xsatisfacao = "'".$_POST['satisfacao']."'";

	if (strlen ($_POST['laudo_tecnico']) == 0) $xlaudo_tecnico = 'null';
	else        $xlaudo_tecnico = "'".trim($_POST['laudo_tecnico'])."'";

	##### FIM DA VALIDAÇÃO DOS CAMPOS #####

	$os_reincidente = "'f'";

	$produto = 0;

	if (strlen($_POST['produto_voltagem']) == 0)	$voltagem = "null";
	else											$voltagem = "'". $_POST['produto_voltagem'] ."'";

	$sql = "SELECT tbl_produto.produto, tbl_produto.linha
			FROM   tbl_produto
			JOIN   tbl_linha USING (linha)
			WHERE  UPPER(tbl_produto.referencia_pesquisa) = UPPER($produto_referencia) ";
	if ($login_fabrica == 1) {
		$voltagem_pesquisa = str_replace("'","",$voltagem);
		$sql .= " AND tbl_produto.voltagem ILIKE '%$voltagem_pesquisa%'";
	}
	$sql .= " AND    tbl_linha.fabrica      = $login_fabrica
			AND    tbl_produto.ativo IS TRUE";

	$res = @pg_exec ($con,$sql);

	if (@pg_numrows ($res) == 0) {
		$msg_erro = " Produto $produto_referencia não cadastrado";
	}else{
		$produto = @pg_result ($res,0,produto);
		$linha   = @pg_result ($res,0,linha);
	}

	if ($login_fabrica == 1) {
		$sql =	"SELECT tbl_familia.familia, tbl_familia.descricao
				FROM tbl_produto
				JOIN tbl_familia USING (familia)
				WHERE tbl_familia.fabrica = $login_fabrica
				AND   tbl_familia.familia = 347
				AND   tbl_produto.produto = $produto;";
		$res = @pg_exec($con,$sql);
		if (pg_numrows($res) > 0) {
			$xtipo_os_cortesia = "'Compressor'";
		}else{
			$xtipo_os_cortesia = 'null';
		}
	}else{
		$xtipo_os_cortesia = 'null';
	}

	#----------- OS digitada pelo Distribuidor -----------------
	$digitacao_distribuidor = "null";
	if ($distribuidor_digita == 't'){
		$codigo_posto = strtoupper (trim ($_POST['codigo_posto']));
		$codigo_posto = str_replace (" ","",$codigo_posto);
		$codigo_posto = str_replace (".","",$codigo_posto);
		$codigo_posto = str_replace ("/","",$codigo_posto);
		$codigo_posto = str_replace ("-","",$codigo_posto);
		
		if (strlen ($codigo_posto) > 0) {
			$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica AND codigo_posto = '$codigo_posto' AND credenciamento = 'CREDENCIADO'";
			$res = @pg_exec($con,$sql);
			if (pg_numrows ($res) <> 1) {
				$msg_erro = "Posto $codigo_posto não cadastrado";
				$posto = $login_posto;
			}else{
				$posto = pg_result ($res,0,0);
				if ($posto <> $login_poso) {
					$sql = "SELECT posto FROM tbl_posto_linha WHERE posto = $posto AND distribuidor = $login_posto AND linha = $linha";
					$res = @pg_exec($con,$sql);
					if (pg_numrows ($res) <> 1) {
						$msg_erro = "Posto $codigo_posto não pertence a sua região";
						$posto = $login_posto;
					}else{
						$posto = pg_result ($res,0,0);
						$digitacao_distribuidor = $login_posto;
					}
				}
			}
		}
	}
	#------------------------------------------------------

	$res = @pg_exec($con,"BEGIN TRANSACTION");

	$os_offline = $_POST['os_offline'];
	if (strlen ($os_offline) == 0) $os_offline = "null";

	if (strlen($msg_erro) == 0){
		/*================ INSERE NOVA OS =========================*/
		if (strlen($os) == 0) {
			$sql =	"INSERT INTO tbl_os (
						tipo_atendimento                                               ,
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
						satisfacao                                                     ,
						laudo_tecnico                                                  ,
						tipo_os_cortesia                                               ,
						troca_faturada                                                 ,
						os_offline                                                     ,
						os_reincidente                                                 ,
						tipo_os                                                        ,
						digitacao_distribuidor
					) VALUES (
						$tipo_atendimento                                              ,
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
						$xsatisfacao                                                   ,
						$xlaudo_tecnico                                                ,
						$xtipo_os_cortesia                                             ,
						$xtroca_faturada                                               ,
						$os_offline                                                    ,
						$os_reincidente                                                ,
						$tipo_os                                                       ,
						$digitacao_distribuidor
					);";
		}else{
			$sql =	"UPDATE tbl_os SET
						tipo_atendimento            = $tipo_atendimento                 ,
						data_abertura               = $xdata_abertura                   ,
						cliente                     = (SELECT cliente FROM tbl_cliente WHERE cpf = $xconsumidor_cpf) ,
						revenda                     = (SELECT revenda FROM tbl_revenda WHERE cnpj = $xrevenda_cnpj)  ,
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
						satisfacao                  = $xsatisfacao                      ,
						laudo_tecnico               = $xlaudo_tecnico                   ,
						troca_faturada              = $xtroca_faturada                  ,
						tipo_os                     = $tipo_os                          ,
						tipo_os_cortesia            = $xtipo_os_cortesia
					WHERE os      = $os
					AND   fabrica = $login_fabrica
					AND   posto   = $posto;";
		}
		
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
				$res = @pg_exec ($con,"COMMIT TRANSACTION");

				//hd chamado 3371
				if ($login_fabrica == 1 and $pedir_sua_os == 'f') {
					$sua_os_repetiu = 't';
					while ($sua_os_repetiu == 't') {
						//veriica se esta sua_os é repetida
						$sql_sua_os = " SELECT sua_os
										FROM   tbl_os
										WHERE  fabrica =  $login_fabrica
										AND    posto   =  $login_posto
										AND    sua_os  =  (SELECT sua_os from tbl_os where os = $os)
										AND    os      <> $os";
						$res_sua_os = pg_exec($con, $sql_sua_os);

						if (pg_numrows($res_sua_os) > 0) {
							$sql_sua_os = " SELECT sua_os 
											FROM tbl_posto_fabrica
											WHERE fabrica = $login_fabrica
											AND   posto   = $login_posto";
							$res_sua_os = pg_exec($con, $sql_sua_os);
							$sua_os_atual = pg_result($res_sua_os,0,0);

							$sua_os_atual = $sua_os_atual + 1;

							$sql_sua_os = " UPDATE tbl_posto_fabrica SET sua_os = $sua_os_atual
											where  tbl_posto_fabrica.fabrica = $login_fabrica
											and    tbl_posto_fabrica.posto   = $login_posto";
							$res_sua_os = pg_exec($con, $sql_sua_os);
											
							$sql_sua_os = " UPDATE tbl_os set sua_os = lpad ($sua_os_atual,5,''0'') 
											WHERE  tbl_os.os      = $os
											and    tbl_os.fabrica = $login_fabrica";
							$res_sua_os = pg_exec($con, $sql_sua_os);
						} else {
							$sua_os_repetiu = 'f';
						}
					}
				}
				//

				header ("Location: os_cadastro_adicional.php?os=$os");
				exit;
			}else{
				$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
			}
		}else{
			$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
		}

	}else{
		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf\"") > 0)
		$msg_erro = " Data da compra maior que a data da abertura da Ordem de Serviço.";

		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_abertura\"") > 0)
		$msg_erro = " Data da abertura deve ser inferior ou igual a data de digitação da OS no sistema (data de hoje).";

		if (strpos ($msg_erro,"tbl_os_unico") > 0)
			$msg_erro = " O Número da Ordem de Serviço do fabricante já esta cadastrado.";

		$res = @pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}


if ($btn_acao == "locacao") {
	$serie_locacao = trim($_POST["serie_locacao"]);
	if (strlen($serie_locacao) > 0) {
		$sql = "SELECT tbl_locacao.locacao         ,
						tbl_locacao.produto         ,
						tbl_produto.referencia      ,
						tbl_produto.descricao       ,
						tbl_produto.voltagem        ,
						tbl_locacao.execucao        ,
						tbl_locacao.type            ,
						tbl_locacao.serie           ,
						tbl_locacao.data_fabricacao ,
						tbl_locacao.pedido          ,
						tbl_locacao.nota_fiscal     ,
						to_char(tbl_locacao.data_emissao,'DD/MM/YYYY') AS data_emissao,
						tbl_locacao.data_vencimento ,
						tbl_locacao.posto           
				FROM   tbl_locacao 
				JOIN tbl_produto ON tbl_produto.produto = tbl_locacao.produto
				WHERE  tbl_locacao.posto   = $login_posto 
				AND    tbl_locacao.serie   = '$serie_locacao'";
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 0) {
			$msg_erro = " Nenhum produto encontrado com esse número de série. ";
		}else{
			$locacao            = trim(pg_result($res,0,locacao));
			$type               = trim(pg_result($res,0,type));
			$serie              = trim(pg_result($res,0,serie));
			$data_fabricacao    = trim(pg_result($res,0,data_fabricacao));
			$pedido             = trim(pg_result($res,0,pedido));
			$nota_fiscal        = trim(pg_result($res,0,nota_fiscal));
			$data_nf            = trim(pg_result($res,0,data_emissao));
			$data_vencimento    = trim(pg_result($res,0,data_vencimento));
			$execucao           = trim(pg_result($res,0,execucao));
			$produto            = trim(pg_result($res,0,produto));
			$produto_referencia = trim(pg_result($res,0,referencia));
			$produto_descricao  = trim(pg_result($res,0,descricao));
			$produto_voltagem   = trim(pg_result($res,0,voltagem));
			$tipo_os            = "7";

			$data = date("Ymd");
			$x_data_vencimento = substr($data_vencimento,6,4) . substr($data_vencimento,3,2) . substr($data_vencimento,0,2);
			
			if ($x_data_vencimento < $data) $msg_erro = " O nº de série $serie está com a garantia expirada, vencida em $data_vencimento. ";

/*			
			$sql = "SELECT  tbl_locacao.locacao                                                  ,
							tbl_locacao.type                                                     ,
							tbl_locacao.serie                                                    ,
							tbl_locacao.data_fabricacao                                          ,
							tbl_locacao.pedido                                                   ,
							tbl_locacao.nota_fiscal                                              ,
							TO_CHAR(tbl_locacao.data_emissao,'DD/MM/YYYY')    AS data_emissao    ,
							TO_CHAR(tbl_locacao.data_vencimento,'DD/MM/YYYY') AS data_vencimento ,
							tbl_locacao.execucao                                                 ,
							tbl_produto.produto                                                  ,
							tbl_produto.referencia                                               ,
							tbl_produto.descricao                                                ,
							tbl_produto.voltagem                                                 ,
							tbl_os.os
					FROM    tbl_locacao
					JOIN    tbl_os ON tbl_os.serie = tbl_locacao.serie
					JOIN    tbl_produto ON tbl_produto.produto = tbl_locacao.produto
					WHERE   tbl_locacao.serie = '$serie_locacao'
					AND     tbl_os.fabrica = $login_fabrica
					AND     tbl_os.tipo_os = 7
					AND     tbl_os.posto   = $login_posto;";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) == 1) {
				$locacao            = trim(pg_result($res,0,locacao));
				$type               = trim(pg_result($res,0,type));
				$produto_serie      = trim(pg_result($res,0,serie));
				$data_fabricacao    = trim(pg_result($res,0,data_fabricacao));
				$pedido             = trim(pg_result($res,0,pedido));
				$nota_fiscal        = trim(pg_result($res,0,nota_fiscal));
				$data_nf            = trim(pg_result($res,0,data_emissao));
				$data_vencimento    = trim(pg_result($res,0,data_vencimento));
				$execucao           = trim(pg_result($res,0,execucao));
				$produto            = trim(pg_result($res,0,produto));
				$produto_referencia = trim(pg_result($res,0,referencia));
				$produto_descricao  = trim(pg_result($res,0,descricao));
				$produto_voltagem   = trim(pg_result($res,0,voltagem));
				$tipo_os            = "7";
				$os                 = trim(pg_result($res,0,os));
				
				$data = date("Ymd");
				$x_data_vencimento = substr($data_vencimento,6,4) . substr($data_vencimento,3,2) . substr($data_vencimento,0,2);
				
				if ($x_data_vencimento < $data) $msg_erro = " O nº de série $serie está com a garantia expirada, vencida em $data_vencimento. ";
			}else{
				$msg_erro = " OS com o númere de série $serie_locacao não existe. ";
			}
*/
		}
	}
}


/*================ LE OS DA BASE DE DADOS =========================*/
if (strlen($_GET['os'] ) > 0) $os = $_GET['os'];
if (strlen($_POST['os']) > 0) $os = $_POST['os'];

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
					tbl_produto.voltagem                       AS produto_voltagem   ,
					tbl_posto_fabrica.codigo_posto
			FROM tbl_os
			JOIN      tbl_produto  ON tbl_produto.produto       = tbl_os.produto
			JOIN      tbl_posto_fabrica ON tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.posto = $posto
			LEFT JOIN tbl_os_extra ON tbl_os.os                 = tbl_os_extra.os
			WHERE tbl_os.os = $os
			AND   tbl_os.posto = $posto
			AND   tbl_os.fabrica = $login_fabrica";
	$res = @pg_exec ($con,$sql);

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
		$codigo_posto		= pg_result ($res,0,codigo_posto);

		if (strlen($produto_serie) > 0) {
			$sql = "SELECT tbl_locacao.locacao         ,
							tbl_locacao.execucao       ,
							tbl_locacao.data_fabricacao,
							tbl_locacao.pedido         ,
							tbl_locacao.data_vencimento
					FROM    tbl_locacao 
					WHERE   tbl_locacao.posto   = $login_posto 
					AND     tbl_locacao.serie   = '$produto_serie'";
			$res = pg_exec($con,$sql);

			if (pg_numrows($res) == 0) {
				$msg_erro = " Nenhum produto encontrado com esse número de série. ";
			}else{
				$locacao            = trim(pg_result($res,0,locacao));
				$data_fabricacao    = trim(pg_result($res,0,data_fabricacao));
				$pedido             = trim(pg_result($res,0,pedido));
				$data_vencimento    = trim(pg_result($res,0,data_vencimento));
				$execucao           = trim(pg_result($res,0,execucao));
				$tipo_os            = "7";
			}
		}
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
	$codigo_posto				= $_POST['codigo_posto'];
	$serie						= $_POST['numero_serie'];
}

$body_onload = "javascript: document.frm_os.sua_os.focus()";

/* PASSA PARÂMETRO PARA O CABEÇALHO (não esquecer ===========*/

/* $title = Aparece no sub-menu e no título do Browser ===== */
$title = "Cadastro de Ordem de Serviço";

/* $layout_menu = Determina a aba em destaque do MENU ===== */
$layout_menu = 'os';

include "cabecalho.php";

$sql = "SELECT digita_os FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
$res = @pg_exec($con,$sql);
$digita_os = pg_result ($res,0,0);
if ($digita_os == 'f') {
	echo "<H4>Sem permissão de acesso.</H4>";
	exit;
}

?>

<!--=============== <FUNÇÕES> ================================!-->


<? include "javascript_pesquisas.php" ?>

<script language="JavaScript">

/* ============= Função PESQUISA DE CONSUMIDOR POR NOME ====================
Nome da Função : fnc_pesquisa_consumidor_nome (nome, cpf)
=================================================================*/
function fnc_pesquisa_consumidor (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_consumidor.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor.php?cpf=" + campo.value + "&tipo=cpf";
	}
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.cliente		= document.frm_os.consumidor_cliente;
	janela.nome			= document.frm_os.consumidor_nome;
	janela.cpf			= document.frm_os.consumidor_cpf;
	janela.rg			= document.frm_os.consumidor_rg;
	janela.cidade		= document.frm_os.consumidor_cidade;
	janela.estado		= document.frm_os.consumidor_estado;
	janela.fone			= document.frm_os.consumidor_fone;
	janela.endereco		= document.frm_os.consumidor_endereco;
	janela.numero		= document.frm_os.consumidor_numero;
	janela.complemento	= document.frm_os.consumidor_complemento;
	janela.bairro		= document.frm_os.consumidor_bairro;
	janela.cep			= document.frm_os.consumidor_cep;
	janela.focus();
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
</script>

<!-- ============= <PHP> VERIFICA DUPLICIDADE DE OS  =============
		Verifica a existência de uma OS com o mesmo número e em
		caso positivo passa a mensagem para o usuário.
=============================================================== -->
<?
if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"tbl_os_sua_os") > 0) $msg_erro = "Esta ordem de serviço já foi cadastrada";
?>

<!-- ============= <HTML> COMEÇA FORMATAÇÃO ===================== -->

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" width = '730'>
<tr>
	<td valign="middle" align="center" class='error'>
<?
	if ($login_fabrica == 1 AND ( strpos($msg_erro,"É necessário informar o type para o produto") !== false OR strpos($msg_erro,"Type informado para o produto não é válido") !== false ) ) {
		$produto_referencia = trim($_POST["produto_referencia"]);
		$produto_voltagem   = trim($_POST["produto_voltagem"]);
		$sqlT =	"SELECT tbl_lista_basica.type
				FROM tbl_produto
				JOIN tbl_lista_basica USING (produto)
				WHERE UPPER(tbl_produto.referencia_pesquisa) = UPPER('$produto_referencia')
				AND   tbl_produto.voltagem = '$produto_voltagem'
				AND   tbl_lista_basica.fabrica = $login_fabrica
				AND   tbl_produto.ativo IS TRUE
				GROUP BY tbl_lista_basica.type
				ORDER BY tbl_lista_basica.type;";
		$resT = @pg_exec ($con,$sqlT);
		if (pg_numrows($resT) > 0) {
			$s = pg_numrows($resT) - 1;
			for ($t = 0 ; $t < pg_numrows($resT) ; $t++) {
				$typeT = pg_result($resT,$t,type);
				$result_type = $result_type.$typeT;

				if ($t == $s) $result_type = $result_type.".";
				else          $result_type = $result_type.",";
			}
			$msg_erro .= "<br>Selecione o Type: $result_type";
		}
	}

	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg_erro = substr($msg_erro, 6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}
	echo "<!-- ERRO INICIO -->";
	//echo $erro . $msg_erro . "<br><!-- " . $sql . "<br>" . $sql_OS . " -->";
	echo $erro . $msg_erro;
	echo "<!-- ERRO FINAL -->";
?>
	</td>
</tr>
</table>

<? } ?>


<?
$sql = "SELECT TO_CHAR (current_timestamp , 'DD/MM/YYYY' )";
$res = @pg_exec ($con,$sql);
$hoje = @pg_result ($res,0,0);
?>

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>

	<td valign="top" align="left">

		<!-- ------------- Formulário ----------------- -->

		<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input type="hidden" name="btn_acao">
		<input type="hidden" name="os" value="<? echo $os; ?>">

<?
echo "<table width='700' border='0' cellspacing='2' cellpadding='5' align='center'>";
echo "<tr>";
echo "<td align='center' bgcolor='#6699FF' style='font-color:#ffffff ; font-size:12px'>";
echo "<B>Conforme comunicado de 04/01/2006, as OS's abertas até o dia 31/12/2005 poderão ser digitadas até o dia 31/01/2006.<br>Pedimos atenção especial com relação a esse prazo, pois depois do dia 01/02/2006 somente aceitaremos a abertura das OS's com data posterior a 02/01/2006.</B>";
echo "</td>";
echo "</tr>";
echo "</table>";
?>

		<p>
<?
		if (strlen($locacao) > 0){
			#echo "<br><a href='os_cadastro_novo.php'><center>Clique aqui para fazer uma nova pesquisa do N° de Série</center><br></a>";
		}else{
?>
			<fieldset style="padding: 10;">
				<legend align="center"><font color="#000000" size="2">Locação</font></legend>
				<br>
				<center>
					<font color="#000000" size="2">Nº de Série</font>
					<input class="frm" type="text" name="serie_locacao" size="15" maxlength="20" value="<? echo $serie_locacao; ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com o número de série Locação e clique no botão para efetuar a pesquisa.');">
					<img border="0" src="imagens/btn_continuar.gif" align="absmiddle" onclick="javascript: if (document.forms[0].btn_acao.value == '') { document.forms[0].btn_acao.value='locacao'; document.forms[0].submit(); } else { alert('Não clique no botão voltar do navegador, utilize somente os botões da tela'); }" style="cursor: hand" alt="Clique aqui p/ localizar o número de série">
				</center>
			</fieldset>
			</form>
<?
		}
		
		if ($tipo_os == "7" AND strlen($locacao) > 0) {
			echo "<input type='hidden' name='locacao' value='$locacao'>";
?>
		

		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr valign='top'>
			<td nowrap>
				<? if ($pedir_sua_os == 't') { ?>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS Fabricante</font>
				<br>
				<input  name ="sua_os" class ="frm" type ="text" size ="10" maxlength="20" value ="<? echo $sua_os ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número da OS do Fabricante.');">
				<?
				} else {
					echo "&nbsp;";
					echo "<input type='hidden' name='sua_os'>";
				}
				?>
			</td>
			<td nowrap>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Referência do Produto</font>
				<br>
				<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" readonly>
			</td>
			<td nowrap>
			<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Descrição do Produto</font>
				<br>
				<input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>" readonly>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Voltagem</font>
				<br>
				<input class="frm" type="text" name="produto_voltagem" size="5" value="<? echo $produto_voltagem ?>" readonly>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Abertura</font>
				<br>
				<input name="data_abertura" size="12" maxlength="10"value="<?  if (strlen($data_abertura) > 0) echo $data_abertura; ?>" type="text" class="frm" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a Data da Abertura da OS.'); " tabindex="0"><br><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
			</td>
			<td nowrap>
				<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Nº de Série</font>
				<br>
				<input class="frm" type="text" name="numero_serie" size="15" value="<? echo $serie ?>" readonly>
			</td>
		</tr>
		</table>
		
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr valign='top'>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Código Fabricação</font>
				<br>
				<input  name ="codigo_fabricacao" class ="frm" type ="text" size ="13" maxlength="20" value ="<? echo $codigo_fabricacao ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número do Código de Fabricação.');">
			</td>
			<td nowrap>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">30 dias Satisfação DeWALT</font>
				<br>
				<input name ="satisfacao" class ="frm" type ="checkbox" value="t" <? if ($satisfacao == 't') echo "checked"; ?>>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Laudo técnico</font>
				<br>
				<input  name ="laudo_tecnico" class ="frm" type ="text" size ="20" maxlength="50" value ="<? echo $laudo_tecnico; ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o laudo técnico.');">
			</td>
		</tr>
		</table>
		
		<hr>
		<input type="hidden" name="consumidor_cliente">
		<input type="hidden" name="consumidor_endereco">
		<input type="hidden" name="consumidor_numero">
		<input type="hidden" name="consumidor_complemento">
		<input type="hidden" name="consumidor_bairro">
		<input type="hidden" name="consumidor_cep">
		<input type="hidden" name="consumidor_cidade">
		<input type="hidden" name="consumidor_estado">
		<input type="hidden" name="consumidor_rg">

		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Consumidor</font>
				<br>
				<input class="frm" type="text" name="consumidor_nome" size="30" maxlength="50" value="<? echo $consumidor_nome ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira aqui o nome do Cliente.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, "nome")'  style='cursor: pointer'>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">CPF/CNPJ do Consumidor</font>
				<br>
				<input class="frm" type="text" name="consumidor_cpf" size="17" maxlength="18" value="<? echo $consumidor_cpf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CPF do consumidor. Pode ser digitado diretamente, ou separado com pontos e traços.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_cpf,"cpf")'  style='cursor: pointer'>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone</font>
				<br>
				<input class="frm" type="text" name="consumidor_fone" size="15" maxlength="20" value="<? echo $consumidor_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
			</td>
		</tr>
		</table>

		<hr>

		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr valign='top'>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome Revenda</font>
				<br>
				<input class="frm" type="text" name="revenda_nome" size="30" maxlength="50" value="<? echo $revenda_nome ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o nome da REVENDA onde foi adquirido o produto.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_nome, "nome")' style='cursor: pointer'>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">CNPJ Revenda</font>
				<br>
				<input class="frm" type="text" name="revenda_cnpj" size="20" maxlength="18" value="<? echo $revenda_cnpj ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o número no Cadastro Nacional de Pessoa Jurídica.'); " onKeyUp="formata_cnpj(this.value, 'frm_os')">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_revenda (document.frm_os.revenda_cnpj, "cnpj")' style='cursor: pointer'>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nota Fiscal</font>
				<br>
				<input class="frm" type="text" name="nota_fiscal" size="8"  maxlength="8"  value="<? echo $nota_fiscal ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com o número da Nota Fiscal.');" readonly>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Compra</font>
				<br>
				<input class="frm" type="text" name="data_nf" size="12" maxlength="10" value="<? echo $data_nf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a data da compra. Verifique se o produto está dentro do PRAZO DE GARANTIA.');" tabindex="0" readonly><br><font face='arial' size='1'>Ex.: <? echo date("d/m/Y"); ?></font>
			</td>
		</tr>
		</table>

		<hr>

		<table width="100%" border="0" cellspacing="5" cellpadding="2">
		<tr>
			<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Consumidor</font>&nbsp;<input type="radio" name="consumidor_revenda" value='C' <? if (strlen($consumidor_revenda) == 0 OR $consumidor_revenda == 'C') echo "checked"; ?>></td>

			<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">ou</font></td>

			<td><font size="1" face="Geneva, Arial, Helvetica, san-serif">Revenda</font>&nbsp;<input type="radio" name="consumidor_revenda" value='R' <? if ($consumidor_revenda == 'R') echo " checked"; ?>>&nbsp;&nbsp;</td>

			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Aparência do Produto</font>
				<br>
				<input class="frm" type="text" name="aparencia_produto" size="30" value="<? echo $aparencia_produto ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Texto livre com a aparência externa do aparelho deixado no balcão.');">
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Acessórios</font>
				<br>
				<input class="frm" type="text" name="acessorios" size="30" value="<? echo $acessorios ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Texto livre com os acessórios deixados junto ao produto.');">
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Troca faturada</font><br>
				<input class="frm" type="checkbox" name="troca_faturada" value="t"<? if ($troca_faturada == 't') echo " checked";?>>
			</td>
		</tr>
		</table>
	</td>

	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
</table>

<hr width='700'>

<table width="100%" border="0" cellspacing="5" cellpadding="0">
<tr>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() }" ALT="Continuar com Ordem de Serviço" border='0' style='cursor: hand;'>
	</td>
</tr>
</table>

<input type='hidden' name = 'revenda_fone'>
<input type='hidden' name = 'revenda_cidade'>
<input type='hidden' name = 'revenda_estado'>
<input type='hidden' name = 'revenda_endereco'>
<input type='hidden' name = 'revenda_numero'>
<input type='hidden' name = 'revenda_complemento'>
<input type='hidden' name = 'revenda_bairro'>
<input type='hidden' name = 'revenda_cep'>
<input type='hidden' name = 'revenda_email'>

<?
		}
?>

</form>

<p>

<? include "rodape.php";?>
