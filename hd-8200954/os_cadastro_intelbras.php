<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
$pedir_sua_os = pg_result ($res,0,pedir_sua_os);
$pedir_defeito_reclamado_descricao = pg_result ($res,0,pedir_defeito_reclamado_descricao);

$os_offline = $_POST['os_offline'];
if($os_offline == '1'){
	$pedir_defeito_reclamado_descricao = "f";
}

/*======= <PHP> FUNÇOES DOS BOTÕES DE AÇÃO =========*/

$btn_acao = strtolower ($_POST['btn_acao']);

$msg_erro = "";

if ($btn_acao == "continuar") {
	$os = $_POST['os'];


	$sua_os_offline = $_POST['sua_os_offline'];
	if (strlen (trim ($sua_os_offline)) == 0) {
		$sua_os_offline = 'null';
	}else{
		$sua_os_offline = "'" . trim ($sua_os_offline) . "'";
	}


	$sua_os = $_POST['sua_os'];
	if (strlen (trim ($sua_os)) == 0) {
		$sua_os = 'null';
		if ($pedir_sua_os == 't') {
			$msg_erro .= " Digite o número da OS Fabricante.";
		}
	}else{
		if ($login_fabrica <> 1) {
			$sua_os = "000000" . trim ($sua_os);
			$sua_os = substr ($sua_os, strlen ($sua_os) - 6 , 6) ;
			$sua_os = "'" . $sua_os . "'" ;
		}
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

	if (strlen(trim($_POST['nota_fiscal'])) == 0) $xnota_fiscal = 'null';
	else             $xnota_fiscal = "'".trim($_POST['nota_fiscal'])."'";

	if ($login_fabrica == 14){
		if ($xnota_fiscal == 'null'){
			$msg_erro = "Digite o número da nota fiscal.";
		}
	}

	$xdata_nf = fnc_formata_data_pg(trim($_POST['data_nf']));
	if ($xdata_nf == 'null') $msg_erro .= " Digite a data de compra.";

	if (strlen(trim($_POST['produto_serie'])) == 0) $xproduto_serie = 'null';
	else         $xproduto_serie = "'". strtoupper(trim($_POST['produto_serie'])) ."'";

	if (strlen($xproduto_serie) > 20) $msg_erro = "Número de série inválido, não deve exceder 20 caracteres.";

	if (strlen(trim($_POST['codigo_fabricacao'])) == 0) $xcodigo_fabricacao = 'null';
	else             $xcodigo_fabricacao = "'".trim($_POST['codigo_fabricacao'])."'";

	if (strlen(trim($_POST['aparencia_produto'])) == 0) $xaparencia_produto = 'null';
	else             $xaparencia_produto = "'".trim($_POST['aparencia_produto'])."'";

	if (strlen(trim($_POST['acessorios'])) == 0) $xacessorios = 'null';
	else             $xacessorios = "'".trim($_POST['acessorios'])."'";

	if (strlen(trim($_POST['defeito_reclamado_descricao'])) == 0) $xdefeito_reclamado_descricao = 'null';
	else             $xdefeito_reclamado_descricao = "'".trim($_POST['defeito_reclamado_descricao'])."'";


	if (strlen(trim($_POST['defeito_reclamado'])) == 0) $xdefeito_reclamado = 'null';
	else             $xdefeito_reclamado = "'".trim($_POST['defeito_reclamado'])."'";

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

		$sqlX = "SELECT to_char (current_date, 'YYYY-MM-DD')";
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

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	if (strlen($os) == 0 AND strlen($msg_erro) == 0){
		/*================ INSERE NOVA OS =========================*/

		$produto = 0;

		if (strlen($_POST['produto_voltagem']) == 0)	$voltagem = "null";
		else									$voltagem = "'". $_POST['produto_voltagem'] ."'";

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
		}

		if (strlen($msg_erro) == 0) {
			$produto = @pg_result ($res,0,0);

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

		if (strlen ($msg_erro) == 0) {
			$sql = "INSERT INTO tbl_os (
						posto                                                          ,
						fabrica                                                        ,
						sua_os                                                         ,
						sua_os_offline                                                 ,
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
						nota_fiscal                                                    ,
						data_nf                                                        ,
						produto                                                        ,
						serie                                                          ,
						codigo_fabricacao                                              ,
						aparencia_produto                                              ,
						acessorios                                                     ,
						defeito_reclamado                                              ,
						defeito_reclamado_descricao                                    ,
						obs                                                            ,
						quem_abriu_chamado                                             ,
						consumidor_revenda                                             ,
						type                                                           ,
						satisfacao                                                     ,
						laudo_tecnico                                                  ,
						tipo_os_cortesia                                               ,
						os_reincidente
					) VALUES (
						$login_posto                                                   ,
						$login_fabrica                                                 ,
						$sua_os                                                        ,
						$sua_os_offline                                                ,
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
						$xnota_fiscal                                                  ,
						$xdata_nf                                                      ,
						$produto                                                       ,
						$xproduto_serie                                                ,
						$xcodigo_fabricacao                                            ,
						$xaparencia_produto                                            ,
						$xacessorios                                                   ,
						$xdefeito_reclamado                                            ,
						$xdefeito_reclamado_descricao                                  ,
						$xobs                                                          ,
						$xquem_abriu_chamado                                           ,
						$xconsumidor_revenda                                           ,
						$xtype                                                         ,
						$xsatisfacao                                                   ,
						$xlaudo_tecnico                                                ,
						$xtipo_os_cortesia                                             ,
						$os_reincidente
					);";

//echo $sql;
//$sql_igor = $sql;
 //if ($ip == '201.43.10.184') { echo nl2br($sql)."<br>"; exit; }

			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			$msg_erro = substr($msg_erro,6);

			if (strlen ($msg_erro) == 0) {
				$res = @pg_exec ($con,"SELECT CURRVAL ('seq_os')");
				$os  = pg_result ($res,0,0);

				$sql = "UPDATE tbl_os SET consumidor_nome = tbl_cliente.nome WHERE tbl_os.os = $os AND tbl_os.cliente IS NOT NULL AND tbl_os.cliente = tbl_cliente.cliente";
				$res = @pg_exec ($con,$sql);
//if ($ip = '192.168.0.55') echo "$sql<br>";

				$sql = "UPDATE tbl_os SET consumidor_cidade = tbl_cidade.nome , consumidor_estado = tbl_cidade.estado WHERE tbl_os.os = $os AND tbl_os.cliente IS NOT NULL AND tbl_os.consumidor_cidade IS NULL AND tbl_os.cliente = tbl_cliente.cliente AND tbl_cliente.cidade = tbl_cidade.cidade";
				$res = @pg_exec ($con,$sql);
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		$res = @pg_exec ($con,"SELECT CURRVAL ('seq_os')");
		$os  = pg_result ($res,0,0);

		$res      = @pg_exec ($con,"SELECT fn_valida_os($os, $login_fabrica)");
		$msg_erro = pg_errormessage($con);
//if ($ip = '192.168.0.55') echo ":: $msg_erro ::<br>";

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
				if($os_offline == '1'){
					echo "Location: os_cadastro_adicional.php?os=$os";
				}else{
					header ("Location: os_cadastro_adicional.php?os=$os");
				}
				exit;
			}
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}

	}else{
		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_nf\"") > 0)
		$msg_erro = " Data da compra maior que a data da abertura da Ordem de Serviço.";

		if (strpos ($msg_erro,"new row for relation \"tbl_os\" violates check constraint \"data_abertura_futura\"") > 0)
		$msg_erro = " Data da abertura deve ser inferior ou igual a data de digitação da OS no sistema (data de hoje).";

		if (strpos ($msg_erro,"tbl_os_unico") > 0)
			$msg_erro = " O Número da Ordem de Serviço do fabricante já esta cadastrado.";

		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}

}

/*================ LE OS DA BASE DE DADOS =========================*/
$os = $HTTP_GET_VARS['os'];
if (strlen ($os) > 0) {
	$sql = "SELECT * FROM tbl_os WHERE oid = $os AND posto = $login_posto";
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
//if ($ip == '201.0.9.216') echo $msg_erro;

//echo "<center><b>Programa em Teste.</b></center>";

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
		$resT = pg_exec ($con,$sqlT);
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
	echo $erro . $msg_erro  "<br>" ;

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

		<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input class="frm" type="hidden" name="os" value="<? echo $_GET['os'] ?>">

		<p>
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

			<?
			if (trim (strlen ($data_abertura)) == 0 AND $login_fabrica == 7) {
				$data_abertura = $hoje;
			}
			?>

			<? if ($login_fabrica == 6){ ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">N. Série</font>
				<br>
				<input class="frm" type="text" name="produto_serie" size="12" maxlength="20" value="<? echo $produto_serie ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o número de série do aparelho.'); ">
				&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto_serie (document.frm_os.produto_serie,'frm_os')"  style='cursor: pointer'></A>
				<script>
				<!--
				function fnc_pesquisa_produto_serie (campo,form) {
					if (campo.value != "") {
						var url = "";
						url = "produto_serie_pesquisa.php?campo=" + campo.value + "&form=" + form ;
						janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
						janela.focus();
					}
				}
				-->
				</script>
			</td>
			<? } ?>

			<td nowrap>
				<?
				if ($login_fabrica == 3) {
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Código do Produto</font>";
				}else{
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Referência do Produto</font>";
				}
				?>
				<br>
				<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a referência do produto e clique na lupa para efetuar a pesquisa.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'referencia',document.frm_os.produto_voltagem,document.frm_os.produto_pai_referencia, document.frm_os.produto_pai_descricao,document.frm_os.produto_avo_referencia, document.frm_os.produto_avo_descricao) " style='cursor: hand'>
			</td>
			<td nowrap>
				<?
				if ($login_fabrica == 3) {
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Modelo do Produto</font>";
				}else{
					echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>Descrição do Produto</font>";
				}
				?>
				<br>
				<input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o modelo do produto e clique na lupa para efetuar a pesquisa.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os.produto_referencia,document.frm_os.produto_descricao,'descricao',document.frm_os.produto_voltagem,document.frm_os.produto_pai_referencia, document.frm_os.produto_pai_descricao,document.frm_os.produto_avo_referencia, document.frm_os.produto_avo_descricao)"  style='cursor: pointer'></A>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Voltagem</font>
				<br>
				<input class="frm" type="text" name="produto_voltagem" size="5" value="<? echo $produto_voltagem ?>" readonly>
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Abertura</font>
				<br>
				<input name="data_abertura" size="12" maxlength="10"value="<?  if (strlen($data_abertura) == 0) $data_abertura = date("d/m/Y"); echo $data_abertura; ?>" type="text" class="frm" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a Data da Abertura da OS.'); " tabindex="0"><br><font face='arial' size='1'>Ex.: 25/10/2004</font>
			</td>
			<? if ($login_fabrica <> 6){ ?>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">N. Série</font>
				<br>
				<input class="frm" type="text" name="produto_serie" size="12" maxlength="20" value="<? echo $produto_serie ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite aqui o número de série do aparelho.'); ">
			</td>
			<? } ?>
		</tr>
		</table>

		<? if ($login_fabrica == 14) { ?>
		<!-- ------------ Código do Produto Pai para INTENBRÁS -------------- -->
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr valign='top'>
			<td nowrap><img src='imagens/pixel.gif' width='8' height='1'></td>

			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Referência Equipamento</font>
				<br>
				<input class="frm" type="text" name="produto_pai_referencia" size="15" maxlength="20" value="<? echo $produto_pai_referencia ?>" disabled >
				<br>
				<input class="frm" type="text" name="produto_avo_referencia" size="15" maxlength="20" value="<? echo $produto_avo_referencia ?>" disabled >
			</td>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Descrição Equipamento</font>
				<br>
				<input class="frm" type="text" name="produto_pai_descricao" size="50" value="<? echo $produto_pai_descricao ?>" disabled >
				<br>
				<input class="frm" type="text" name="produto_avo_descricao" size="50" value="<? echo $produto_avo_descricao ?>" disabled >
			</td>

			<td nowrap width='100%'><img src='imagens/pixel.gif' width='10' height='1'></td>

		</tr>
		</table>



		<? } ?>



		<? if ($login_fabrica == 1) { ?>
		<!-- ------------ Código de Fabricação para BLACK&DECKER -------------- -->
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr valign='top'>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Código Fabricação</font>
				<br>
				<input  name ="codigo_fabricacao" class ="frm" type ="text" size ="13" maxlength="20" value ="<? echo $codigo_fabricacao ?>" onblur = "this.className='frm'; displayText('&nbsp;');" onfocus ="this.className='frm-on';displayText('&nbsp;Digite aqui o número do Código de Fabricação.');">
			</td>
			<td nowrap>
<!--
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Versão/Type</font>
				<br>
-->
<?
/*
				echo "<select name='type' class ='frm'>\n";
				echo "<option value=''></option>\n";
				echo "<option value='Tipo 1'"; if($type == 'Tipo 1') echo " selected"; echo " >Tipo 1</option>\n";
				echo "<option value='Tipo 2'"; if($type == 'Tipo 2') echo " selected"; echo " >Tipo 2</option>\n";
				echo "<option value='Tipo 3'"; if($type == 'Tipo 3') echo " selected"; echo " >Tipo 3</option>\n";
				echo "<option value='Tipo 4'"; if($type == 'Tipo 4') echo " selected"; echo " >Tipo 4</option>\n";
				echo "<option value='Tipo 5'"; if($type == 'Tipo 5') echo " selected"; echo " >Tipo 5</option>\n";
				echo "<option value='Tipo 6'"; if($type == 'Tipo 6') echo " selected"; echo " >Tipo 6</option>\n";
				echo "<option value='Tipo 7'"; if($type == 'Tipo 7') echo " selected"; echo " >Tipo 7</option>\n";
				echo "<option value='Tipo 8'"; if($type == 'Tipo 8') echo " selected"; echo " >Tipo 8</option>\n";
				echo "<option value='Tipo 9'"; if($type == 'Tipo 9') echo " selected"; echo " >Tipo 9</option>\n";
				echo "<\select>&nbsp;";
*/
?>
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
		<? } ?>

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
				<input class="frm" type="text" name="consumidor_nome"   size="30" maxlength="50" value="<? echo $consumidor_nome ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira aqui o nome do Cliente.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_nome, "nome")'  style='cursor: pointer'>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">CPF/CNPJ do Consumidor</font>
				<br>
				<input class="frm" type="text" name="consumidor_cpf"   size="17" maxlength="18" value="<? echo $consumidor_cpf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Digite o CPF do consumidor. Pode ser digitado diretamente, ou separado com pontos e traços.');">&nbsp;<img src='imagens/btn_buscar5.gif' border='0' align='absmiddle' onclick='javascript: fnc_pesquisa_consumidor (document.frm_os.consumidor_cpf,"cpf")'  style='cursor: pointer'>
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Fone</font>
				<br>
				<input class="frm" type="text" name="consumidor_fone"   size="15" maxlength="20" value="<? echo $consumidor_fone ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Insira o telefone com o DDD. ex.: 14/4455-6677.');">
			</td>
		</tr>
		</table>

		<hr>

		<?
		if ($login_fabrica == 7) {
#			echo "<!-- ";
		}
		?>

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
				<input class="frm" type="text" name="nota_fiscal"  size="8"  maxlength="8"  value="<? echo $nota_fiscal ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com o número da Nota Fiscal.');">
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data Compra</font>
				<br>
				<input class="frm" type="text" name="data_nf"    size="12" maxlength="10" value="<? echo $data_nf ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Entre com a data da compra. Verifique se o produto está dentro do PRAZO DE GARANTIA.');" tabindex="0"><br><font face='arial' size='1'>Ex.: 25/10/2004</font>
			</td>
		</tr>
		</table>
		<?
		if ($login_fabrica == 7) {
#			echo " -->";
		}
		?>

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
<? if ($login_fabrica <> 1) { ?>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Acessórios</font>
				<br>
				<input class="frm" type="text" name="acessorios" size="30" value="<? echo $acessorios ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Texto livre com os acessórios deixados junto ao produto.');">
			</td>
<? } ?>
		</tr>

		</table>
	

		<? if ($pedir_defeito_reclamado_descricao == 't') { ?>

		<hr>

		<center>
		<font size="1" face="Geneva, Arial, Helvetica, san-serif">
		Descrição do Defeito Reclamado pelo Consumidor
		</font>
		<br>
		<textarea class='frm' name='defeito_reclamado_descricao' cols='70' rows='5'><? echo $defeito_reclamado_descricao ?></textarea>


		<? }  # Final do IF do Defeito_Reclamado_Descricao ?>


		<?
		if ($login_fabrica == 7) {
		?>


		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
		<hr>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Chamado aberto por</font>
				<br>
				<input class="frm" type="text" name="quem_abriu_chamado" size="20" maxlength="30" value="<? echo $quem_abriu_chamado ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;Nome do funcionário do cliente que abriu este chamado.');">
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Observações</font>
				<br>
				<input class="frm" type="text" name="obs" size="50" value="<? echo $obs ?>" onblur="this.className='frm'; displayText('&nbsp;');" onfocus="this.className='frm-on'; displayText('&nbsp;ObservaçÕes e dados adicionais desta OS.');">
			</td>
		</tr>
		</table>


		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Taxa de Visita</font>
				<br>
				<input class="frm" type="text" name="taxa_visita" size="8" maxlength="10" value="<? echo $taxa_visita ?>" >
				&nbsp;
				<input class="frm" type='checkbox' name='visita_por_km' value='t' <? if ($visita_por_km == 't') echo " checked " ?> >Km
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Hora Técnica</font>
				<br>
				<input class="frm" type="text" name="hora_tecnica" size="8" maxlength='10' value="<? echo $hora_tecnica ?>" >
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Regulagem</font>
				<br>
				<input class="frm" type="text" name="regulagem_peso_padrao" size="8" maxlength='10' value="<? echo $regulagem_peso_padrao ?>" >
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Certificado</font>
				<br>
				<input class="frm" type="text" name="certificado_conformidade" size="8" maxlength='10' value="<? echo $certificado_conformidade ?>" >
			</td>
			<td>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Diária</font>
				<br>
				<input class="frm" type="text" name="valor_diaria" size="8" maxlength='10' value="<? echo $valor_diaria ?>" >
			</td>
		</tr>
		</table>

		<?
		}
		?>

	</td>

	<td><img height="1" width="16" src="imagens/spacer.gif"></td>
</tr>
</table>




<hr width='700'>

<table width="100%" border="0" cellspacing="5" cellpadding="0">
<tr>
	<td height="27" valign="middle" align="center" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value="">
		<img src='imagens/btn_continuar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='continuar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Continuar com Ordem de Serviço" border='0' style='cursor: pointer'>
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

</form>

<p>

<? include "rodape.php";?>