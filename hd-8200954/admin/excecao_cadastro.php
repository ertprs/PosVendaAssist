<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
require_once '../helpdesk/mlg_funciones.php';
include_once dirname(__FILE__) . '/../class/AuditorLog.php';
use Posvenda\DistribuidorSLA;
$oDistribuidorSLA = new DistribuidorSLA();
$oDistribuidorSLA->setFabrica($login_fabrica);
$arr_troca_rev             = array(24,72,74,140,141,144,151,157); // para adicionar fabrica usando os campos troca_produto e revenda na mao de obra HD #321132
$arr_cnpj_rev              = array(74);                       // array para fabricas q gravam a revenda na excecao
$arr_usa_solucao           = array(1,6,40,72,74,127,149);
// -> inserido em: 13/09/2012 - gabriel Silveira - hd 937608

$arr_usa_peca_lancada       = array(40);                       // array para fabricas que usam a funcionalidade de exceção com peça lançada
$arr_posto_nao_obrigatorio  = array(40,72,120,127,149,165,183);
$arr_usa_tipo_atendimento   = array(40,116,141,144,148,151,158,169,170,176);
$usa_defeito_constatado     = in_array($login_fabrica, array(101,127,136,139,174,178));
$posto_nao_obrigatorio      = in_array($login_fabrica, $arr_posto_nao_obrigatorio) ? true : false;
$usa_peca_lancada           = in_array($login_fabrica, $arr_usa_peca_lancada);
$usa_solucao                = in_array($login_fabrica, $arr_usa_solucao );
$usa_tipo_posto             = in_array($login_fabrica, array(141,144,151,184,200));
$usa_familia                = in_array($login_fabrica, array(101,15,24,40,141,144,151,165,178,184,200));
$usa_servico                = in_array($login_fabrica, array(165));
$usa_eficiencia            = in_array($login_fabrica, array(158));

if (filter_input(INPUT_GET,"excecao_mobra")) {//url
	$excecao_mobra = filter_input(INPUT_GET,"excecao_mobra");
}

if (filter_input(INPUT_POST,"excecao_mobra")) {//formulario
	$excecao_mobra = filter_input(INPUT_POST,"excecao_mobra");
}

if (filter_input(INPUT_POST,"btnacao")) {
	$btnacao = filter_input(INPUT_POST,"btnacao");
}

if ($btnacao == "excluir" and strlen($excecao_mobra) > 0 ) {
	$res = pg_query($con,"BEGIN TRANSACTION");

	$AuditorLog = new AuditorLog();

	$sqlAuditor = retornaSqlAuditorLog($excecao_mobra);
	$AuditorLog->RetornaDadosSelect($sqlAuditor);

	$sql = "DELETE FROM tbl_excecao_mobra
			WHERE  tbl_excecao_mobra.fabrica       = $login_fabrica
			AND    tbl_excecao_mobra.excecao_mobra = $excecao_mobra";
	$res = @pg_query($con,$sql);
	$msg_erro = pg_last_error($con);

	if (strlen ($msg_erro) == 0) {
		$AuditorLog->RetornaDadosSelect()->EnviarLog('delete', 'tbl_excecao_mobra',$login_fabrica."*".$login_fabrica);
		
		$res = pg_query($con,"COMMIT TRANSACTION");

		header ("Location: $PHP_SELF?msg=".traduz("Excluído com Sucesso!"));
		exit;
	}else{
		$res = pg_query($con,"ROLLBACK TRANSACTION");
	}
}

function retornaSqlAuditorLog($ex_mo = null, $conds = null) {
	global $login_fabrica;

	if (!empty($ex_mo) || !empty($conds)) {

		$cond = (!empty($ex_mo)) ? " AND tbl_excecao_mobra.excecao_mobra = $ex_mo " : $conds;

		return "SELECT DISTINCT
					tbl_posto_fabrica.codigo_posto || ' - ' || tbl_posto_fabrica.nome_fantasia AS posto,
					tbl_produto.referencia || ' - ' || tbl_produto.descricao AS produto,
					tbl_linha.nome AS linha,
					tbl_excecao_mobra.adicional_mao_de_obra,
					tbl_excecao_mobra.percentual_mao_de_obra,
					tbl_excecao_mobra.mao_de_obra,
					tbl_familia.descricao AS familia,
					tbl_excecao_mobra.qtde_dias,
					tbl_excecao_mobra.troca_produto,
					tbl_revenda.nome AS revenda,
					tbl_solucao.descricao AS solucao,
					tbl_excecao_mobra.peca_lancada,
					tbl_tipo_atendimento.descricao AS tipo_atendimento,
					tbl_excecao_mobra.tx_administrativa AS taxa_administrativa,
					tbl_classificacao.descricao AS classificacao,
					tbl_servico_realizado.descricao AS servico_realizado,
					tbl_distribuidor_sla.unidade_negocio ||' - '||tbl_unidade_negocio.nome AS unidade_de_negocio,
					CASE WHEN tbl_excecao_mobra.eficiencia = 2 THEN 'D+1'
						 WHEN tbl_excecao_mobra.eficiencia = 3 THEN 'D+2'
						 WHEN tbl_excecao_mobra.eficiencia > 3 THEN 'Acima de D+2'
					ELSE ''
					END AS eficiencia
					FROM tbl_excecao_mobra
					LEFT JOIN tbl_posto_fabrica ON tbl_excecao_mobra.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					LEFT JOIN tbl_produto ON tbl_excecao_mobra.produto = tbl_produto.produto AND tbl_produto.fabrica_i = $login_fabrica
					LEFT JOIN tbl_linha ON tbl_excecao_mobra.linha = tbl_linha.linha AND tbl_linha.fabrica = $login_fabrica
					LEFT JOIN tbl_familia ON tbl_excecao_mobra.familia = tbl_familia.familia AND tbl_familia.fabrica = $login_fabrica
					LEFT JOIN tbl_revenda ON tbl_excecao_mobra.id_revenda = tbl_revenda.revenda
					LEFT JOIN tbl_solucao ON tbl_excecao_mobra.solucao = tbl_solucao.solucao AND tbl_solucao.fabrica = $login_fabrica
					LEFT JOIN tbl_tipo_atendimento ON tbl_excecao_mobra.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica
					LEFT JOIN tbl_classificacao ON tbl_excecao_mobra.classificacao = tbl_classificacao.classificacao AND tbl_classificacao.fabrica = $login_fabrica
					LEFT JOIN tbl_servico_realizado ON tbl_excecao_mobra.servico_realizado = tbl_servico_realizado.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
					LEFT JOIN tbl_distribuidor_sla ON tbl_excecao_mobra.distribuidor_sla = tbl_distribuidor_sla.distribuidor_sla AND tbl_distribuidor_sla.fabrica = $login_fabrica
					LEFT JOIN tbl_unidade_negocio ON tbl_distribuidor_sla.unidade_negocio = tbl_unidade_negocio.codigo
					WHERE tbl_excecao_mobra.fabrica = $login_fabrica
					$cond
					";
	} else {
		return "";
	}
}

if ($btnacao == 'gravar') {

	$posto      = $_POST['posto'];
	$posto_cnpj = preg_replace('/\D/', '', $_POST['posto_cnpj']);
	$posto_nome = $_POST['posto_nome'];

	if(strlen($posto_cnpj) > 0) {
		if ($login_fabrica == 151) {
			$posto_codigo = $_POST["posto_cnpj"];

			$sql_posto = "
				SELECT tbl_posto.posto
				FROM  tbl_posto
				JOIN  tbl_posto_fabrica USING(posto)
				WHERE tbl_posto_fabrica.codigo_posto = '$posto_codigo' AND tbl_posto_fabrica.fabrica = $login_fabrica";
		} else {
			$sql_posto = "SELECT posto FROM tbl_posto WHERE cnpj = '$posto_cnpj'";
		}

		$res_posto = pg_query($con, $sql_posto);

		if(pg_num_rows($res_posto) > 0){
			$posto = pg_fetch_result($res_posto, 0, 'posto');
		}else{
			$msg_erro = traduz("Posto % nï¿½o encontro",null,null,[$posto_cnpj]); 
		}
	}
	//	$produto          = $_POST["produto"];
    $linha              = filter_input(INPUT_POST,'linha');
    $familia            = filter_input(INPUT_POST,'familia');
    $referencia         = filter_input(INPUT_POST,'referencia');
    $descricao          = filter_input(INPUT_POST,'descricao');
    $mobra              = filter_input(INPUT_POST,'mobra');
    $adicional_mobra    = filter_input(INPUT_POST,'adicional_mobra');
    $percentual_mobra   = filter_input(INPUT_POST,'percentual_mobra');
    $solucao            = filter_input(INPUT_POST,'solucao');
    $servico_realizado  = filter_input(INPUT_POST,'servico_realizado');
    $todos_postos       = filter_input(INPUT_POST,'todos_postos');
    $peca_lancada       = filter_input(INPUT_POST,'peca_lancada');
    $tipo_posto         = filter_input(INPUT_POST,'tipo_posto');
    $defeito_constatado = filter_input(INPUT_POST,'defeito_constatado');
    $classificacao      = filter_input(INPUT_POST,'classificacao');
    if($usa_eficiencia){
		$eficiencia = filter_input(INPUT_POST,'eficiencia');
		if($eficiencia == ""){
			$eficiencia = 0;
		}
    } else {
    	$eficiencia = "null";
    }
    $unidade_negocio    = filter_input(INPUT_POST,'unidade_negocio',FILTER_VALIDATE_INT);
    $qtde_dias          = filter_input(INPUT_POST,'qtde_dias',FILTER_VALIDATE_INT);

	if (filter_input(INPUT_POST,'percentual_desconto')) {
		$percentual_desconto = number_format(filter_input(INPUT_POST,'percentual_desconto'),2,'.',',');
	} else {
		$percentual_desconto = "null";
	}

	if(empty($unidade_negocio)){
    	$unidade_negocio = "null";
    }

	if (($login_fabrica == 1 AND $todos_postos == 't')) {
		$posto_nao_obrigatorio = true;
	}

	$msg_erro = '';

	if ((empty($mobra) || $mobra == 0) && (empty($adicional_mobra) || $adicional_mobra == 0) && (empty($percentual_mobra) || $percentual_mobra == 0)) {
    	$msg_erro .= traduz("Informe a mão de obra<br>");
    	$msg_erro_campo["campo"][] = "mobra"; 
    }

	if (strlen($_POST["posto_cnpj"]) > 0) {
		$aux_posto_cnpj = "'". trim($_POST["posto_cnpj"]) ."'";
	} else if( !in_array($login_fabrica,$arr_troca_rev )  || $login_fabrica == 72 and !$posto_nao_obrigatorio ) {
		if ($tipo_atendimento != 217 && empty($posto) && $login_fabrica == 148) {
			$msg_erro .= traduz("Digite o CNPJ do Posto.<br/>");
			$msg_erro_campo["campo"][] = "posto";
		}
	}

	if (strlen($_POST["posto_nome"]) > 0) {
		$aux_posto_nome = "'". trim($_POST["posto_nome"]) ."'";
	} else if( !in_array($login_fabrica,$arr_troca_rev ) || $login_fabrica == 72 and !$posto_nao_obrigatorio ) {
		if ($tipo_atendimento != 217 && empty($posto) && $login_fabrica == 148) {
			$msg_erro .= traduz("Digite o Nome do Posto.<br/>");
			$msg_erro_campo["campo"][] = "posto";
		}
	}

	if (strlen($mobra) > 0 && strlen($adicional_mobra) > 0 && strlen($percentual_mobra) == 0) {
		$msg_erro .= "É necessário optar por apenas uma das opções: <br>Mão de obra <br>Adicional de mão de obra  <br>Percentual de mão de obra.<br/>";
	}

	if (strlen($mobra) > 0 && strlen($adicional_mobra) == 0 && strlen($percentual_mobra) > 0) {
		$msg_erro .= traduz("É necessário optar por apenas uma das opções: <br>Mão de obra <br> Adicional de mão de obra <br> Percentual de mão de obra.<br/>");
	}

	if (strlen($mobra) == 0 && strlen($adicional_mobra) > 0 && strlen($percentual_mobra) > 0) {
		$msg_erro .= traduz("É necessário optar por apenas uma das opções: <br>Mão de obra <br> Adicional de mão de obra <br> Percentual de mão de obra.<br/>");
	}

	if (strlen($mobra) > 0 && strlen($adicional_mobra) > 0 && strlen($percentual_mobra) == 0) {
		$msg_erro .= traduz("É necessário optar por apenas uma das opções: <br>Mão de obra <br> Adicional de mão de obra <br> Percentual de mão de obra.<br/>");
	}

	if (strlen($mobra) == 0 && strlen($adicional_mobra) == 0 && strlen($percentual_mobra) == 0 && strlen($percentual_desconto) == 0) {
		$msg_erro .= traduz("É necessário optar por uma das opções: <br>Mão de obra <br> Adicional de mão de obra <br> Percentual de mão de obra.<br/>");
	}

	$cond_verifica_replicacao = '';
	$cond_replicidade = '';

	if (strlen($produto) == 0) {
		$aux_produto = "null";

	}else{
		$aux_produto = "'$produto'";
	}

	if (!strlen($defeito_constatado)) {
		$defeito_constatado = "null";
	}

	if (!empty($tipo_posto)) {
		$cond_replicidade .= "AND tipo_posto = {$tipo_posto}";
	}

	if (strlen($linha) == 0) {
		$aux_linha = "null";
		$cond_verifica_replicacao .= "
										AND linha is null";
		$cond_replicidade .= "
										AND linha is null";
	}else{
		$aux_linha = "'$linha'";
		$cond_verifica_replicacao .= "
										AND linha = $linha";
		$cond_replicidade .= "
										AND linha = $linha";
	}

	if (strlen($familia) == 0) {
		$aux_familia = "null";
		$cond_verifica_replicacao .= "
										AND familia is null";
		$cond_replicidade .= "
										AND familia is null";
	}else{
		$aux_familia = "'$familia'";
		$cond_verifica_replicacao .= "
										AND familia = $familia";
		$cond_replicidade .= "
										AND familia = $familia";
	}

	if($aux_linha <> 'null' AND ($aux_familia <> 'null' OR strlen($referencia) <> 0 )){
		$msg_erro .= traduz("É necessário optar por uma das opções: Linha, Família ou Produto.<br/>");
	}

	if($aux_familia <> 'null' AND ($aux_linha <> 'null' OR strlen($referencia) <> 0)){
		$msg_erro .= traduz("É necessário optar por uma das opções: Linha, Família ou Produto.<br/>");
	}

	if(strlen($referencia) <> 0 AND ($aux_linha <> 'null' OR $aux_familia <> 'null')){
		$msg_erro .= traduz("É necessário optar por uma das opções: Linha, Família ou Produto.<br/>");
	}

	if ($login_fabrica == 6) {
		if (empty($referencia) and $aux_linha == 'null' and $aux_familia == 'null' and $solucao == '0') {
			$msg_erro .= traduz("É necessário optar por uma das opções: Linha, Família ou Produto.<br/>");
		}
	}

	if ($login_fabrica == 151) {
		if ($aux_linha == "null" && $aux_familia == "null" && strlen($referencia) == 0) {
			$msg_erro .= traduz("É necessário optar por uma das opções: Linha, Família ou Produto.<br/>");
		}
	}

	if (empty($peca_lancada)) {
		$peca_lancada = 'false';
	}

	if(empty($unidade_negocio)) {
		$unidade_negocio = "null";
	}
	if(!empty( $_POST['rev_troca'] ) ) {
		if ($_POST['rev_troca'] == 'rev') {

			$rev = 't';
			$troca = 'f';
			$cond_verifica_replicacao .= "
										AND (revenda is true AND troca_produto is false)";
			
			$cond_replicidade .= "
										AND (revenda is true AND troca_produto is false)";

			$cond_verifica_replicacao_troca_revenda = "
										AND (revenda is true AND troca_produto is false)";
		} else {

			$rev = 'f';
			$troca = 't';
			$cond_verifica_replicacao .= "
										AND (revenda is false AND troca_produto is true)";
			
			$cond_replicidade .= "
										AND (revenda is false AND troca_produto is true)";

			$cond_verifica_replicacao_troca_revenda = "
										AND (revenda is false AND troca_produto is true)";
		}

	}else{
		$rev = 'f'; $troca = 'f';
		$cond_verifica_replicacao .= "
										AND (revenda is false AND troca_produto is false)";

		$cond_replicidade .= "
										AND (revenda is false AND troca_produto is false)";

		$cond_verifica_replicacao_troca_revenda = "
										AND (revenda is false AND troca_produto is false)";
	}

	if( $_POST['rev_troca'] != '0' and strlen($referencia) > 0 && !in_array($login_fabrica, array(72)) )
		$msg_erro = null; // pode cadastrar para suggar apenas com produto e revenda/troca

	if ($login_fabrica == 72) {
		if ($troca == 't' && (!empty($produto) || !empty($linha) )){
			$msg_erro .= traduz("Não é possivel marcar a exceção como TROCA e adicionar produto ou linha");
		}
	}
	if(strlen($msg_erro) > 0 and $_POST['rev_troca'] == '0' and in_array($login_fabrica,$arr_troca_rev))
		$msg_erro .= ($login_fabrica == 140) ? "Revenda <br />" : 'Revenda / Troca <br />';

	//HD 728505: acrescentada solução para Atlas nas configurações de exceção de mão de obra
	//			 foi deixada a validação em aberto para todas as fábriucas, pois só valida se vier solução no POST
	$solucao = $_POST["solucao"];
	if (strlen($solucao) > 0) {
		$cond_verifica_replicacao .= "
										AND solucao = $solucao";

		$sql = "SELECT solucao, descricao FROM tbl_solucao WHERE fabrica={$login_fabrica} AND ativo IS TRUE AND solucao={$solucao}";
		$res_solucao = pg_query($con, $sql);
		if (pg_num_rows($res_solucao) == 0) {
			$msg_erro .= traduz("Solução selecionada é inexistente ou está inativa.<br />");
		}
	}else{
		$solucao = "null";
		$cond_verifica_replicacao .= "
										AND solucao is null";
	}

	if($login_fabrica == 116){
		if(!empty($referencia) AND empty($_POST['posto_cnpj']) AND empty($tipo_atendimento)){
			$msg_erro .= traduz("Informe CNPJ do Posto e/ou Tipo de Atendimento");
		}
	}

	if (!empty($tipo_atendimento)) {
		$cond_verifica_replicacao .= "
										AND tipo_atendimento = $tipo_atendimento";
	}else{
		$tipo_atendimento = "NULL";
		$cond_verifica_replicacao .= "
										AND tipo_atendimento is null";
	}

	if (strlen($mobra) == 0) {
		$aux_mobra = "null";
		$cond_verifica_replicacao .= "
										AND mao_de_obra is null";
	}else{
		$aux_mobra = "'$mobra'";
		$cond_verifica_replicacao .= "
										AND mao_de_obra = (SELECT fnc_limpa_moeda($aux_mobra))";
	}

	if (strlen($adicional_mobra) == 0) {
		$aux_adicional_mobra = "null";
		$cond_verifica_replicacao .= "
										AND adicional_mao_de_obra is null";
	}else{
		$aux_adicional_mobra = "'$adicional_mobra'";
		$cond_verifica_replicacao .= "
										AND adicional_mao_de_obra = (SELECT fnc_limpa_moeda($aux_adicional_mobra))";
	}

	if (strlen($percentual_mobra) == 0) {
		$aux_percentual_mobra = "null";
		$cond_verifica_replicacao .= "
										AND percentual_mao_de_obra is null";
	}else{
		$aux_percentual_mobra = "'$percentual_mobra'";
		$cond_verifica_replicacao .= "
										AND percentual_mao_de_obra = (SELECT fnc_limpa_moeda($aux_percentual_mobra)) ";
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_query($con,"BEGIN TRANSACTION");

		if (strlen($referencia) > 0) {
		// produto
			$sql = "SELECT tbl_produto.produto
					FROM   tbl_produto
					JOIN   tbl_linha USING (linha)
					WHERE  UPPER (tbl_produto.referencia) = UPPER ('$referencia')
					AND    tbl_linha.fabrica      = $login_fabrica";
			$res = pg_query($con,$sql);

			if (pg_num_rows ($res) == 0) {
				$msg_erro .= traduz("Produto % nï¿½o cadastrado.", null,null,[$referencia])."</br>";
			}else{
				$aux_produto = pg_fetch_result($res,0,0);
				$cond_verifica_replicacao .= "
										AND produto = $aux_produto ";

				$cond_replicidade .= "
										AND produto = $aux_produto ";
			}
		}else{
			$aux_produto = 'null';
			$cond_verifica_replicacao .= "
										AND produto is null";

			$cond_replicidade .= "
										AND produto is null";
		}

		if (strlen($msg_erro) == 0 AND $todos_postos != 't') {
			// posto
			$sql = "SELECT tbl_posto.posto
					FROM   tbl_posto
					JOIN   tbl_posto_fabrica USING (posto)
					WHERE  tbl_posto.cnpj            = '$posto_cnpj'
					AND    tbl_posto_fabrica.fabrica = $login_fabrica";
			if ($login_fabrica == 151) $sql = $sql_posto; /*HD - 4300021*/
			$res = pg_query($con,$sql);

			if (@pg_num_rows ($res) == 0 && ( !in_array($login_fabrica,$arr_troca_rev ) && strlen($posto_cnpj) > 0  ) ) {
				$msg_erro .= traduz("Posto % nï¿½o cadastro.", null,null,[$posto_cnpj])."<br/>";
			}else{
				$posto = @pg_fetch_result($res,0,0);
				$posto_cond = (!empty($posto)) ? " AND posto = $posto " : "";
				if (empty($posto)) {
					$posto = 'null';
				}
			}
			if ( in_array($login_fabrica,$arr_troca_rev ) && strlen($posto_cnpj) == 0 ) {
				$posto = 'null';
				$cond_posto = ' OR posto IS NULL ';
			}
		}else{
			$posto = 'null';
			$cond_posto = ' OR posto IS NULL ';
		}

		if(in_array($login_fabrica,$arr_cnpj_rev)) {

			if ( !empty($_POST['cnpj_revenda']) ) {

				$cnpj_revenda = preg_replace('/\D/', '', $_POST['cnpj_revenda']);
				$sql = "SELECT revenda FROM tbl_revenda WHERE tbl_revenda.cnpj = '" . $cnpj_revenda . "'";
				$res = pg_query($con,$sql);
				if ( pg_num_rows($res) == 0)
					$msg_erro .= traduz("Revenda Não Encontrada.")."<br/>";
				else
					$revenda_id = pg_fetch_result($res,0,0);

			} else
				$revenda_id = "null";
				//echo $rev; die;
		} else
			$revenda_id = "null";

		if (strlen($classificacao) == 0) {
			$classificacao = 'null';
		}

		$xcond_posto = '';
		if ($posto != 'null') {
			$xcond_posto = "AND posto = {$posto}";
		} else {
			$xcond_posto = "AND posto IS NULL";
		}

		if ($tipo_atendimento == "NULL") {
			$xcond_ta = "AND tipo_atendimento IS NULL";
		} else {
			$xcond_ta = "AND tipo_atendimento = $tipo_atendimento";
		}

		if($qtde_dias == "NULL" or strlen($qtde_dias) == 0 ){
			$xcond_qtde_dias = " AND qtde_dias IS NULL ";
		}else{
			$xcond_qtde_dias = " AND qtde_dias = {$qtde_dias} ";
		}

		if (strlen ($msg_erro) == 0) {
			if($todos_postos == 't'){
				$sql = "SELECT excecao_mobra
						FROM tbl_excecao_mobra
						WHERE fabrica = {$login_fabrica}
						AND posto = {$posto}
						{$cond_posto}
						AND linha = {$linha}
						AND solucao = {$solucao}
						{$cond1}
						{$cond2}";
				// echo nl2br($sql);
				$res=pg_query($con,$sql);
				if(pg_num_rows($res) >0) {
					$excecao_mobra=pg_fetch_result($res,0,excecao_mobra);
				}
			}

			if (strlen($excecao_mobra) == 0) {

				$recarga_de_gas = $_POST['recarga_de_gas'];
				//echo "<br>RECARGA GÁS =".$recarga_de_gas."<br>";
				$sql_solucao_sql = "SELECT * FROM tbl_solucao WHERE fabrica = {$login_fabrica} AND descricao iLIKE '%recarga de g%' ORDER BY solucao";
				$res_solucao_sql=pg_query($con,$sql_solucao_sql);
				//echo "<br><br>RESULTADO =".pg_num_rows($res_solucao_sql)."<br> LOGIN FABRICA =".$login_fabrica."<br> RECARGA DE GÁS =".$recarga_de_gas."<br><br>";

				$tipoLog = "insert";
				$AuditorLog = new AuditorLog('insert');

				if (pg_num_rows($res_solucao_sql) > 0 && $login_fabrica == 15 && $recarga_de_gas == 't') {
				    for($p=0;$p < pg_num_rows($res_solucao_sql);$p++) {
						$cod_solucao = pg_fetch_result($res_solucao_sql,$p,solucao);

						###INSERE NOVO REGISTRO
						$sql_soluc = "INSERT INTO tbl_excecao_mobra (
											fabrica,
											posto,
											produto,
											linha,
											familia,
											mao_de_obra,
											adicional_mao_de_obra,
											percentual_mao_de_obra,
											troca_produto,
											revenda,
											id_revenda,
											tipo_atendimento,
											defeito_constatado,
											classificacao,
											solucao
										) VALUES (
											$login_fabrica,
											$posto,
											$aux_produto,
											$aux_linha,
											$aux_familia,
											(SELECT fnc_limpa_moeda($aux_mobra)),
											(SELECT fnc_limpa_moeda($aux_adicional_mobra)),
											(SELECT fnc_limpa_moeda($aux_percentual_mobra)),
											'$troca',
											'$rev',
											$revenda_id,
											$tipo_atendimento,
											$defeito_constatado,
											$classificacao,
											$cod_solucao
										) RETURNING excecao_mobra ;";
								//echo "<BR><BR> ".nl2br($sql_soluc);exit;
								$res = pg_query($con,$sql_soluc);
								if (!pg_last_error()) {
									$ex_mo = pg_fetch_result($res, 0, 'excecao_mobra');

            						$sqlAuditor = retornaSqlAuditorLog($ex_mo);
                    				$AuditorLog->RetornaDadosSelect($sqlAuditor);
									$AuditorLog->EnviarLog($tipoLog, 'tbl_excecao_mobra',"$login_fabrica*$ex_mo");
								}

								$msg_erro = pg_last_error($con);
					}


				} else {
					if (in_array($login_fabrica, $arr_usa_solucao)){

						$solucao_ins_field = ', solucao';
						$solucao_ins_value = ", $solucao";

					}

					if ($login_fabrica == 6) {
						$query_excecoes_posto = pg_query($con, "select count(*) from tbl_excecao_mobra where posto = $posto and fabrica = $login_fabrica");
						$excecoes_posto = pg_fetch_result($query_excecoes_posto, 0, 0);

						if ($excecoes_posto > 0) {
							function operatorOverload($field, $comp) {
								$retorno = array();

								switch ($comp) {
									case "null":
										$retorno[0] = "IS";
										$retorno[1] = "IS NOT";
										break;
									case "f":
										$retorno[0] = "=";
										$retorno[1] = "<>";
										break;

								}

								if ($field == $comp) {
									return $retorno[0];
								} else {
									return $retorno[1];
								}
							}

							$solucao_del_field = str_replace(',', '', $solucao_ins_field);
							$solucao_del_value = str_replace(',', '', $solucao_ins_value);

							$produto_comp          = operatorOverload($aux_produto, "null");
							$linha_comp            = operatorOverload($aux_linha, "null");
							$familia_comp          = operatorOverload($aux_familia, "null");
							$mobra_comp            = operatorOverload($aux_mobra, "null");
							$adicional_mobra_comp  = operatorOverload($aux_adicional_mobra, "null");
							$percentual_mobra_comp = operatorOverload($aux_percentual_mobra, "null");
							$troca_comp            = operatorOverload($troca, "f");
							$rev_comp              = operatorOverload($rev, "f");
							$revenda_comp          = operatorOverload($revenda_id, "null");

							$AuditorLog = new AuditorLog();
							$tipoLog = "delete";

							$conds = "  AND posto = $posto
										AND produto $produto_comp $aux_produto
										AND linha $linha_comp $aux_linha
										AND familia $familia_comp $aux_familia
										AND mao_de_obra $mobra_comp NULL
										AND adicional_mao_de_obra $adicional_mobra_comp NULL
										AND percentual_mao_de_obra $percentual_mobra_comp NULL
										AND troca_produto $troca_comp '$troca'
										AND revenda $rev_comp '$rev'
										AND id_revenda $revenda_comp $revenda_id
										AND $solucao_del_field = $solucao_del_value
										AND defeito_constatado = $defeito_constatado";

							$sqlAuditor = retornaSqlAuditorLog($ex_mo, $conds);							
        					$AuditorLog->RetornaDadosSelect($sqlAuditor);

							$delete = "DELETE FROM tbl_excecao_mobra
										WHERE posto = $posto
										AND fabrica = $login_fabrica
										AND produto $produto_comp $aux_produto
										AND linha $linha_comp $aux_linha
										AND familia $familia_comp $aux_familia
										AND mao_de_obra $mobra_comp NULL
										AND adicional_mao_de_obra $adicional_mobra_comp NULL
										AND percentual_mao_de_obra $percentual_mobra_comp NULL
										AND troca_produto $troca_comp '$troca'
										AND revenda $rev_comp '$rev'
										AND id_revenda $revenda_comp $revenda_id
										AND $solucao_del_field = $solucao_del_value
										AND defeito_constatado = $defeito_constatado
										RETURNING excecao_mobra";
							$qry_delete = pg_query($con, $delete);
							$deleted_rows = pg_affected_rows($qry_delete);
							$rows = pg_fetch_all($qry_delete);
							if (!pg_last_error() && count($rows) > 0) {
								foreach ($rows as $key => $value) {
									$ex_mo = $value["excecao_mobra"];
									$AuditorLog->RetornaDadosSelect()->EnviarLog($tipoLog, 'tbl_excecao_mobra',"$login_fabrica*$ex_mo");
								}
							}
						}

					}

                    $solucao_ins_field .= (empty($tipo_posto))          ? "" : ",tipo_posto";
                    $solucao_ins_field .= (empty($qtde_dias))           ? "" : ",qtde_dias";
                    $solucao_ins_field .= (empty($servico_realizado))   ? "" : ",servico_realizado";

                    $solucao_ins_value .= (empty($tipo_posto))          ? "" : ",$tipo_posto";
                    $solucao_ins_value .= (empty($qtde_dias))           ? "" : ",$qtde_dias";
                    $solucao_ins_value .= (empty($servico_realizado))   ? "" : ",$servico_realizado";

                    if($usa_eficiencia){
						$column       = ", eficiencia ";
						$column_value = ", ".$eficiencia." ";
                    } else {
						$column       = "";
						$column_value = "";
                    }

                    $AuditorLog = new AuditorLog('insert');
                    $tipoLog = "insert";

					###INSERE NOVO REGISTRO
					$sql = "INSERT INTO tbl_excecao_mobra (
								fabrica,
								tx_administrativa,
								posto,
								produto,
								linha,
								familia,
								mao_de_obra,
								adicional_mao_de_obra,
								percentual_mao_de_obra,
								troca_produto,
								revenda,
								peca_lancada,
								id_revenda,
								classificacao,
								distribuidor_sla,
								tipo_atendimento,
								defeito_constatado
								{$column}
								$solucao_ins_field
							) VALUES (
								$login_fabrica,
								$percentual_desconto,
								$posto,
								$aux_produto,
								$aux_linha,
								$aux_familia,
								(SELECT fnc_limpa_moeda($aux_mobra)),
								(SELECT fnc_limpa_moeda($aux_adicional_mobra)),
								(SELECT fnc_limpa_moeda($aux_percentual_mobra)),
								'$troca',
								'$rev',
								$peca_lancada,
								$revenda_id,
								$classificacao,
								$unidade_negocio,
								$tipo_atendimento,
								$defeito_constatado
								$column_value
								$solucao_ins_value
							) RETURNING excecao_mobra ;";
							
					$res      = pg_query($con,$sql);
					if (!pg_last_error()) {
						$ex_mo = pg_fetch_result($res, 0, 'excecao_mobra');
						
						$sqlAuditor = retornaSqlAuditorLog($ex_mo);
        				$AuditorLog->RetornaDadosSelect($sqlAuditor);
						$AuditorLog->EnviarLog($tipoLog, 'tbl_excecao_mobra',"$login_fabrica*$ex_mo");
					}
					$msg_erro = pg_last_error($con);

				}

			} else {

				$executa_sql = false;

                $qtde_dias          = (empty($qtde_dias))           ? "NULL" : $qtde_dias;
                $servico_realizado  = (empty($servico_realizado))   ? "NULL" : $servico_realizado;
                $tipo_posto 		= (empty($tipo_posto))			? "NULL" : $tipo_posto;

                if($usa_eficiencia){
                	$eficiencia = "eficiencia = ".$eficiencia.",";
                } else {
                	$eficiencia = "";
                }

                $AuditorLog = new AuditorLog();
                $tipoLog = "update";
                $sqlAuditor = retornaSqlAuditorLog($excecao_mobra);
				$AuditorLog->RetornaDadosSelect($sqlAuditor);

				###ALTERA REGISTRO
				$sql = "UPDATE  tbl_excecao_mobra SET
								posto                  = $posto       ,
								tx_administrativa      = $percentual_desconto,
								produto                = $aux_produto ,
								linha                  = $aux_linha   ,
								familia                = $aux_familia ,
								mao_de_obra            = (SELECT fnc_limpa_moeda($aux_mobra)),
								adicional_mao_de_obra  = (SELECT fnc_limpa_moeda($aux_adicional_mobra)),
								percentual_mao_de_obra = (SELECT fnc_limpa_moeda($aux_percentual_mobra)),
								revenda                = '$rev'     ,
								troca_produto          = '$troca' 	,
								id_revenda             = $revenda_id,
								qtde_dias              = $qtde_dias,
								peca_lancada           = $peca_lancada,
								distribuidor_sla       = $unidade_negocio,
								{$eficiencia}
								solucao                = {$solucao},
								classificacao          = {$classificacao},
								tipo_atendimento       = $tipo_atendimento,
								defeito_constatado     = $defeito_constatado,
								servico_realizado      = $servico_realizado,
								tipo_posto             = {$tipo_posto}
						WHERE tbl_excecao_mobra.fabrica       = $login_fabrica
						AND   tbl_excecao_mobra.excecao_mobra = $excecao_mobra;";

				 //echo "<br>".nl2br($sql);exit;
				$res      = pg_query($con,$sql);
				if (!pg_last_error()) {
					$AuditorLog->RetornaDadosSelect()->EnviarLog($tipoLog, 'tbl_excecao_mobra',"$login_fabrica*$excecao_mobra");
				}
				$msg_erro = pg_last_error($con);

			}

			if ($executa_sql) {
				//$res = pg_query($con,$sql);
				if (pg_num_rows($res) > 0) $excecao_mobra = pg_fetch_result($res,0,excecao_mobra);

			} else {

				$excecao_mobra = "";

			}

		}

		if ($login_fabrica == 6 and ($deleted_rows > $excecoes_posto)) {
			$msg_erro = traduz("Falha ao gravar exceção.");
		}

		if (strlen ($msg_erro) == 0) {

			##CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
			$res = pg_query($con,"COMMIT");
			header ("Location: $PHP_SELF?msg=".traduz("Gravado com Sucesso!"));
			exit;
		}else{
			###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
			if (strpos ($msg_erro,"duplicate key violates unique constraint \"tbl_excecao_mobra_unico\"") > 0)
				$msg_erro = "Esta exceção já está cadastrada e não pode ser duplicada.";

			$res = pg_query($con,"ROLLBACK TRANSACTION");
		}
	}
}

###CARREGA REGISTRO
if (strlen($excecao_mobra) > 0) {

	$sql = "SELECT  tbl_posto.posto,
					tbl_posto.cnpj,
					tbl_posto.nome,
					tbl_produto.produto,
					tbl_produto.referencia,
					tbl_produto.descricao,
					tbl_excecao_mobra.linha,
					tbl_excecao_mobra.familia,
					tbl_excecao_mobra.mao_de_obra,
					tbl_excecao_mobra.adicional_mao_de_obra,
					tbl_excecao_mobra.percentual_mao_de_obra,
					tbl_excecao_mobra.troca_produto,
					tbl_excecao_mobra.revenda,
					tbl_excecao_mobra.id_revenda,
					tbl_excecao_mobra.solucao,
					tbl_excecao_mobra.servico_realizado,
					tbl_excecao_mobra.tipo_posto,
					tbl_excecao_mobra.qtde_dias,
					tbl_excecao_mobra.tipo_atendimento,
					tbl_excecao_mobra.peca_lancada,
					tbl_excecao_mobra.classificacao,
					tbl_excecao_mobra.distribuidor_sla,
					tbl_excecao_mobra.eficiencia,
					tbl_excecao_mobra.tx_administrativa AS percentual_desconto,
					tbl_excecao_mobra.defeito_constatado
			FROM    tbl_excecao_mobra
			LEFT JOIN tbl_posto   ON tbl_posto.posto     = tbl_excecao_mobra.posto
			LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_excecao_mobra.produto
			LEFT JOIN tbl_linha   ON tbl_linha.linha     = tbl_excecao_mobra.linha
			WHERE   tbl_excecao_mobra.fabrica            = $login_fabrica
			AND     tbl_excecao_mobra.excecao_mobra      = $excecao_mobra;";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		$posto              = trim(pg_fetch_result($res,0,'posto'));
		$posto_cnpj         = trim(pg_fetch_result($res,0,'cnpj'));
		$posto_nome         = trim(pg_fetch_result($res,0,'nome'));
		$produto            = trim(pg_fetch_result($res,0,'produto'));
		$referencia         = trim(pg_fetch_result($res,0,'referencia'));
		$descricao          = trim(pg_fetch_result($res,0,'descricao'));
		$linha              = trim(pg_fetch_result($res,0,'linha'));
		$familia            = trim(pg_fetch_result($res,0,'familia'));
		$rev                = trim(pg_fetch_result($res,0,'revenda'));
		$troca_prod         = trim(pg_fetch_result($res,0,'troca_produto'));
		$rev_id             = trim(pg_fetch_result($res,0,'id_revenda'));
		$mobra              = trim(pg_fetch_result($res,0,'mao_de_obra'));
		$tipo_posto         = trim(pg_fetch_result($res,0,'tipo_posto'));
		$qtde_dias          = trim(pg_fetch_result($res,0,'qtde_dias'));
		$tipo_atendimento   = trim(pg_fetch_result($res,0,'tipo_atendimento'));
		$adicional_mobra    = trim(pg_fetch_result($res,0,'adicional_mao_de_obra'));
		$percentual_mobra   = trim(pg_fetch_result($res,0,'percentual_mao_de_obra'));
		$solucao            = pg_fetch_result($res, 0, 'solucao');
		$servico_realizado  = pg_fetch_result($res, 0, 'servico_realizado');
		$peca_lancada       = pg_fetch_result($res, 0, 'peca_lancada');
		$defeito_constatado = pg_fetch_result($res,0,'defeito_constatado');
		$classificacao 		= pg_fetch_result($res,0,'classificacao');
		$percentual_desconto= pg_fetch_result($res,0,'percentual_desconto');

		$xUnidadeNegocio = "";
		$xUnidadeNegocioNome = "";
		if ($usa_eficiencia) {
			$unidade_negocio = pg_fetch_result($res,0,'distribuidor_sla');
			$eficiencia      = pg_fetch_result($res,0,'eficiencia');

			if (strlen($unidade_negocio) > 0) {

	            $unidadeNegocio = $oDistribuidorSLA->SelectUnidadeNegocioNotIn(null, $unidade_negocio);
				$xUnidadeNegocio = $unidadeNegocio[0]["unidade_negocio"];
				$xUnidadeNegocioNome = $unidadeNegocio[0]["cidade"];
  
			}
		}

		$posto_cnpj       = (!empty($posto_cnpj)) ? preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $posto_cnpj) : "";

		$mobra    = (strlen($mobra) == 0) ? "" : number_format($mobra,2,",",".");
		$adicional_mobra    = (strlen($adicional_mobra) == 0) ? "" : number_format($adicional_mobra,2,",",".");
		$percentual_mobra    = (strlen($percentual_mobra) == 0) ? "" : number_format($percentual_mobra,2,",",".");
		$percentual_desconto    = (strlen($percentual_desconto) == 0) ? "" : number_format($percentual_desconto,2,",",".");

		if(!empty($rev_id)) {
			$sql = "SELECT cnpj, nome FROM tbl_revenda WHERE revenda = " . $rev_id;
			$res = pg_query($con,$sql);
			if(pg_num_rows($res)) {
				$cnpj_revenda = pg_fetch_result($res,0,'cnpj');
				$nome_revenda = pg_fetch_result($res,0,'nome');
			}
		}
	}
}

if (!empty($msg_erro)) {
	$posto_nome        = trim($_POST['posto_nome']);
	$posto_cnpj        = trim($_POST['posto_cnpj']);
	$referencia        = trim($_POST['referencia']);
	$descricao         = trim($_POST['descricao']);
	$linha             = trim($_POST['linha']);

	if (in_array($login_fabrica,$arr_troca_rev)) {
		$troca             = trim($_POST['rev_troca']);
	}

	$mobra             = trim($_POST['mobra']);
	$adicional_mobra   = trim($_POST['adicional_mobra']);
	$percentual_mobra  = trim($_POST['percentual_mobra']);
	$percentual_desconto  = trim($_POST['percentual_desconto']);

	$mobra    = (strlen($mobra) == 0) ? "" : number_format($mobra,2,",",".");
	$adicional_mobra    = (strlen($adicional_mobra) == 0) ? "" : number_format($adicional_mobra,2,",",".");
	$percentual_mobra    = (strlen($percentual_mobra) == 0) ? "" : number_format($percentual_mobra,2,",",".");
	$percentual_desconto    = (strlen($percentual_desconto) == 0) ? "" : number_format($percentual_desconto,2,",",".");
}

$layout_menu = 'cadastro';
$title = traduz("CADASTRAMENTO DE EXCEÇÕES DE MÃO-DE-OBRA");
include 'cabecalho_new.php';

$plugins = array(
	"autocomplete",
	"datepicker",
	"shadowbox",
	"mask",
	"dataTable",
	"alphanumeric"
);

include("plugin_loader.php");

?>
<script type="text/javascript">

function somenteNumeros(e) {
	var tecla=(window.event)?event.keyCode:e.which;
	if((tecla > 47 && tecla < 58)) return true;
	else{
		if (tecla != 8) return false;
		else return true;
	}
}


//A antiga mascara no cnpj, foi retirada pois nao estava permitindo o parcial preenchimento do campo para buscas
//	$(document).ready(function(){
//		$("#posto_cnpj").maskedinput("99.999.999/9999-99",{placeholder:" "});
 //
//	});

$(document).ready(function() {
	Shadowbox.init();

	if ($('#descricao').val() != '' && $('#descricao').val() != null && $('#referencia').val() != '' && $('#referencia').val() != null) {
		$('#linha').attr('disabled',true);
		$('#familia').attr('disabled',true);
	} else {
		$('#linha').attr('disabled',false);
		$('#familia').attr('disabled',false);
	}

	$("span[rel=lupa]").click(function () {
		$.lupa($(this));
	});

	<?php if ($login_fabrica == 151) { /*HD - 4300021*/ ?>
		$("#cnpj_revenda").mask("99.999.999/9999-99",{placeholder:""});
	<?php } else { ?>
		$("#posto_cnpj,#cnpj_revenda").mask("99.999.999/9999-99",{placeholder:""});
	<?php } ?>

	$(".excecao").change(function(){
		var id = $(this).attr("id");

		$(".excecao").each(function() {
			if ($(this).attr("id") != id) {
				$(this).val("");
			}
		});
		
	});

	$("#cnpj_revenda").numeric();

	if ( $('.linha, #familia').val() != '' && $('.linha, #familia').val() != undefined){
		$('#referencia').attr('disabled',true);
		$('#descricao').attr('disabled',true);
	}else{
		$('#referencia').attr('disabled',false);
		$('#descricao').attr('disabled',false);
	}

	$('.referencia').blur(function() {
		if ( $('#referencia').val() != '' || $('#descricao').val() != '' ){
			$('#linha').attr('disabled',true);
			$('#familia').attr('disabled',true);
		}else{
			$('#linha').attr('disabled',false);
			$('#familia').attr('disabled',false);
			$('input[name=produto]').val('');
		}
	});

	$('.linha, #familia').change(function() {
		if ( $('.linha, #familia, #solucao').val() != '' ){
			$('#referencia').attr('disabled',true);
			$('#descricao').attr('disabled',true);
		}else{
			$('#referencia').attr('disabled',false);
			$('#descricao').attr('disabled',false);
		}
	});


	$('#recarga_de_gas').click(function() {
		var check_recarga = $("#recarga_de_gas").is(':checked');

		if(check_recarga == true) {
			$('#referencia').val('');
			$('#descricao').val('');
			$('#referencia').attr("disabled", true);
			$('#descricao').attr("disabled", true);
			$('#linha').attr("disabled", true);
			$('#familia').attr("disabled", true);
		}else {
			$('#referencia').attr("disabled", false);
			$('#descricao').attr("disabled", false);
			$('#linha').attr("disabled", false);
			$('#familia').attr("disabled", false);
		}
   });

	$('#troca').click(function() {

		var check_troca = $("#troca").is(':checked');

		if(check_troca == true) {
			$('#referencia').val('');
			$('#descricao').val('');
			$('#referencia').attr("disabled", true);
			$('#descricao').attr("disabled", true);
			$('#linha').attr("disabled", true);
			$('#familia').attr("disabled", true);
		}else {
			$('#referencia').attr("disabled", false);
			$('#descricao').attr("disabled", false);
			$('#linha').attr("disabled", false);
			$('#familia').attr("disabled", false);
		}
   });

	<?php if ($login_fabrica == 72) { ?>
		var check_troca = $("#troca").is(':checked');
		if(check_troca == true) {
			$('#referencia').val('');
			$('#descricao').val('');
			$('#referencia').attr("disabled", true);
			$('#descricao').attr("disabled", true);
			$('#linha').attr("disabled", true);
			$('#familia').attr("disabled", true);
		}else {
			$('#referencia').attr("disabled", false);
			$('#descricao').attr("disabled", false);
			$('#linha').attr("disabled", false);
			$('#familia').attr("disabled", false);
		}
	<?php } ?>

	$("#btn_filtro").click(function() {
		location.href=window.location.pathname + "?listar=ok";
	});

	$("#cnpj_revenda").numeric();
	$("#mobra").numeric({allow: ','});
	$("#adicional_mobra").numeric({allow: ','});
	$("#percentual_mobra").numeric({allow: ','});

	$('.referencia').blur(function() {
		if ( $('#referencia').val() != '' || $('#descricao').val() != '' ){
			$('#linha').attr('disabled',true);
		}else{
			$('#linha').attr('disabled',false);
			$('input[name=produto]').val('');
		}
	});

	$('.linha').change(function() {
		if ( $('#linha').val() != '' ){
			$('#referencia').attr('disabled',true);
			$('#descricao').attr('disabled',true);
		}else{
			$('#referencia').attr('disabled',false);
			$('#descricao').attr('disabled',false);
		}
	});

	$('#recarga_de_gas').click(function() {
		var check_recarga = $("#recarga_de_gas").is(':checked');

		if(check_recarga == true) {
			$('#referencia').val('');
			$('#descricao').val('');
			$('#referencia').attr("disabled", true);
			$('#descricao').attr("disabled", true);
			$('#linha').attr("disabled", true);
			$('#familia').attr("disabled", true);
		}else {
			$('#referencia').attr("disabled", false);
			$('#descricao').attr("disabled", false);
			$('#linha').attr("disabled", false);
			$('#familia').attr("disabled", false);
		}
    });

	$('#troca').click(function() {

		var check_troca = $("#troca").is(':checked');

		if(check_troca == true) {
			$('#referencia').val('');
			$('#descricao').val('');
			$('#referencia').attr("disabled", true);
			$('#descricao').attr("disabled", true);
			$('#linha').attr("disabled", true);
			$('#familia').attr("disabled", true);
		}else {
			$('#referencia').attr("disabled", false);
			$('#descricao').attr("disabled", false);
			$('#linha').attr("disabled", false);
			$('#familia').attr("disabled", false);
		}
   });

	// Comentei pois está duplicado e sem if da fábrica que usa 72
	/*var check_troca = $("#troca").is(':checked');
	if(check_troca == true) {
		$('#referencia').val('');
		$('#descricao').val('');
		$('#referencia').attr("disabled", true);
		$('#descricao').attr("disabled", true);
		$('#linha').attr("disabled", true);
		$('#familia').attr("disabled", true);
	}else {
		$('#referencia').attr("disabled", false);
		$('#descricao').attr("disabled", false);
		$('#linha').attr("disabled", false);
		$('#familia').attr("disabled", false);
	}*/

	// Ação dos botões
	$("form[name=frm_excecao]").on('click', 'button', function() {
		var btn = $('input[name=btnacao]');
		if ($(this).attr('type') == 'reset') {
			$("form[name=frm_excecao]").find("input:not([type=button]),select").val('');
			return false;
		}

		if (btn.val() != '') {
			alert ("Aguarde submissão!");
			return;
		}
		btn.val($(this).val());
		document.frm_excecao.submit();
	});
});

function retorna_posto(retorno){
    
    <?php if ($login_fabrica == 151) { /*HD - 4300021*/ ?>
    	$("#posto_cnpj").val(retorno.codigo);
    <?php } else { ?>
    	$("#posto_cnpj").val(retorno.cnpj);
    <?php } ?>

	$("#posto_nome").val(retorno.nome);
}

function retorna_revenda(retorno) {
    $("#nome_revenda").val(retorno.razao);
    $("#cnpj_revenda").val(retorno.cnpj);
}    

function retorna_produto (retorno) {
	$("#referencia").val(retorno.referencia);
	$("#descricao").val(retorno.descricao);
}

</script>
<? if( isset ($_GET['msg']) ) { ?>
	<div class='alert alert-success'>
		<h4><?= $_GET['msg']; ?></h4>
	</div>
<? }
if (strlen($msg_erro) > 0) { ?>
	<div class='alert alert-danger'>
		<h4><?= $msg_erro; ?></h4>
	</div>
<? } ?>
<div id="wrapper">
	<div class="row">
		<b class="obrigatorio pull-right">  * <?=traduz('Campos obrigatórios')?> </b>
	</div>
	<form class='form-search form-inline tc_formulario' name="frm_excecao" method="post" action="<?= $PHP_SELF ?>">
		<input type="hidden" name="posto"   value="<?= $posto ?>">
		<input type="hidden" name="produto" value="<?= $produto ?>">
		<input type="hidden" name="linha"   value="<?= $linha ?>">
		<? if (!$usa_familia) { ?>
			<input type="hidden" name="familia" value="<?= $familia ?>">
		<? } ?>
		<input type="hidden" name="excecao_mobra" value="<?= $excecao_mobra ?>">
			<div class="titulo_tabela">Cadastro</div>
			<br />
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro_campo["campo"])) ? "error" : ""?>'>
					<?php
						/* HD - 4300021*/
						if ($login_fabrica == 151) {
							$campo_cnpj = traduz("Código do Posto");
						} else {
							$campo_cnpj = traduz("CNPJ Posto");
						}
					?>
					<label class='control-label' for='codigo_posto' id="lbl_codigo_posto">
						<?=$campo_cnpj;?>
					</label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<?php 
							if (!in_array($login_fabrica,$arr_posto_nao_obrigatorio)) {
							?>
								<h5 class='asteristico'>*</h5>
							<?php
							}
							?>
							<input type="text" class="frm" name="posto_cnpj" <? if ($login_fabrica != 151) { ?> onKeyPress="return somenteNumeros(event)" <? } ?> id="posto_cnpj" value="<?= $posto_cnpj ?>" maxlength="20">
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<?php if ($login_fabrica == 151) { ?>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
							<?php } else { ?>
								<input type="hidden" name="lupa_config" tipo="posto" parametro="cnpj" />
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("posto", $msg_erro_campo["campo"])) ? "error" : ""?>'>
					<label class='control-label' for='descricao_posto'>
						<?=traduz('Nome Posto')?>
					</label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<?php 
							if (!in_array($login_fabrica,$arr_posto_nao_obrigatorio)) {
							?>
								<h5 class='asteristico'>*</h5>
							<?php
							}
							?>
							<input type="text" class="frm" id="posto_nome" name="posto_nome" value="<?= $posto_nome ?>" maxlength="70">
							<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<?php if ($login_fabrica == 1) { ?>
			<div class='row-fluid'>
			<div class='span2'></div>
				<div class='span8'>
					 <label class="checkbox">
							<?php $checked = ($todos_postos == 't') ? 'checked' : ''; ?>
							<input type='checkbox' name='todos_postos' <?=$checked?> value='t' />Todos os Postos
					</label>
				</div>
			<div class='span2'></div>	
			</div>
		<? } 

		if (!in_array($login_fabrica, array(183))) { ?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_referencia'><?=traduz('Código Produto')?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" class="frm referencia" name="referencia" id="referencia" value="<? echo $referencia ?>" maxlength="20">
							<span class='add-on' rel="lupa" ><i class='icon-search'></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="referencia" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_descricao'><?=traduz('Descrição Produto')?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" class="frm referencia" name="descricao" id="descricao" value="<? echo $descricao ?>" maxlength="70">
							<span class='add-on' rel="lupa" ><i class='icon-search' ></i></span>
							<input type="hidden" name="lupa_config" tipo="produto" parametro="descricao" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<? } 

		if(in_array($login_fabrica,$arr_cnpj_rev)) { ?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_referencia'><?=traduz('CNPJ Revenda')?></label>
					<div class='controls controls-row'>
						<div class='span7 input-append'>
							<input type="text" name="cnpj_revenda" value="<?php echo $cnpj_revenda; ?>" class="frm" id="cnpj_revenda" />
							<span class="add-on" rel="lupa" >
                                <i class="icon-search"></i>
                            </span>
                            <input type="hidden" name="lupa_config" tipo="revenda" parametro="cnpj" />
						</div>
					</div>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='produto_descricao'><?=traduz('Nome Revenda')?></label>
					<div class='controls controls-row'>
						<div class='span12 input-append'>
							<input type="text" id="nome_revenda" name="nome_revenda" value="<?php echo $nome_revenda; ?>" class="frm" size="50" />
							<span class="add-on" rel="lupa" >
                                <i class="icon-search"></i>
                            </span>
                            <input type="hidden" name="lupa_config" tipo="revenda" parametro="razao_social" />
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<?php } ?>
		<?php if ($login_fabrica != 183){ ?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
			<?php 
			if ($login_fabrica != 40) { ?>

					<?php $linha_elgin = ($login_fabrica == 117) ? 'Macro - Família' : traduz('Linha'); ?>
					<label class='control-label' for='produto_descricao'>
	                	<?=$linha_elgin?>
	            	</label>
	            	<div class='controls controls-row'>
						<?
						$linhaLabel = ($login_fabrica == 15) ? 'Família' : 'Linha';
						$disabled_linha = ($referencia) ? 'disabled' : ' ';

						if ($login_fabrica == 117) {
	                        $linhas = pg_fetch_pairs($con, 
	                        	"SELECT DISTINCT
				                        tbl_linha.linha,
				                        tbl_linha.nome
				                FROM tbl_linha
				                JOIN tbl_macro_linha_fabrica ON tbl_linha.linha = tbl_macro_linha_fabrica.linha
				                JOIN tbl_macro_linha ON tbl_macro_linha_fabrica.macro_linha = tbl_macro_linha.macro_linha AND tbl_macro_linha.ativo
				                WHERE tbl_linha.fabrica = $login_fabrica
				                ORDER BY tbl_linha.nome ");
	                    } else {
							$linhas = pg_fetch_pairs($con, "SELECT linha, nome FROM tbl_linha WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY nome");
						}
						$select_linhas = array2select('linha', 'linha', $linhas, $linha, "class='frm' $disabled_linha", ''.traduz("ESCOLHA").'', true);
						echo $select_linhas;
						?>
					</div>	
			<?php } ?>
				</div>
			</div>
			<div class='span4'>
				<?php if ($login_fabrica == 158) { ?>
					<label class='control-label' for='produto_descricao'>
						Classificação
					</label>
					<div class='controls controls-row'>
						<?
						$classifLabel = 'Classificação';
						$disabled_linha = ($referencia) ? 'disabled' : ' ';

						$classificacoes = pg_fetch_pairs($con, "SELECT classificacao, descricao FROM tbl_classificacao WHERE fabrica = {$login_fabrica} ORDER BY descricao");
						$select_linhas = array2select('classificacao', 'classificacao', $classificacoes, $classificacao, "class='frm'", 'ESCOLHA', true);
						echo $select_linhas;
						?>
					</div>
			</div>
			<div class="span2"></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>		
					<label class='control-label' for='produto_descricao'>
						<?=traduz('Unidade de Negócio')?>
					</label>
					<div class='controls controls-row'>	
	                    <select name="unidade_negocio" id="unidade_negocio" class="frm">
	                    	<option value="">ESCOLHA ...</option>
		                    <?php    
		                        $distribuidores_disponiveis = $oDistribuidorSLA->SelectUnidadeNegocioNotIn();
		                        
		                        $unidadesMinasGerais = \Posvenda\Regras::getUnidades("unidadesMinasGeraisFiliais", $login_fabrica);
		                        
		                        foreach ($distribuidores_disponiveis as $unidadeNegocio) {
		                            if (in_array($unidadeNegocio["unidade_negocio"], $unidadesMinasGerais)) {
		                                unset($unidadeNegocio["unidade_negocio"]);
		                                continue;
		                            }
		                            $unidade_negocio_agrupado[$unidadeNegocio["distribuidor_sla"]] = $unidadeNegocio["cidade"];
		                        }

		                        foreach ($unidade_negocio_agrupado as $unidade => $descricaoUnidade) {
		                        $selected = ($unidade == $unidade_negocio) ? 'selected' : '';
		                    ?>
	                        <option value="<?= $unidade; ?>" <?= $selected; ?>><?= $descricaoUnidade; ?></option>
	                    <?php } ?>
						</select>
					</div>		
				</div>
				<?php } ?>
			</div>
			<?php if($usa_eficiencia){ ?>
			<div class='span4'>
			<?php if ($login_fabrica == 158) { ?>
				<div class='control-group <?=(in_array("produto", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='eficiencia'>Eficiência</label>
					<div class='controls controls-row'>
	                    <select name="eficiencia" id="eficiencia" class="frm">
							<option value="">ESCOLHA ...</option>
	                        <option value="2" <?php echo $eficiencia == 2 ? "selected" : ""; ?> >D+1</option>
	                        <option value="3" <?php echo $eficiencia == 3 ? "selected" : ""; ?> >D+2</option>
	                        <option value="4" <?php echo $eficiencia == 4 ? "selected" : ""; ?> >Acima de D+2</option>
						</select>
					</div>
				</div>
			<?php } ?>
			</div>
			<?php } ?>
			<div class="span2"></div>
		</div>
		<?php } ?>
		<?php
		if ($usa_defeito_constatado) { ?>
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='codigo_posto'><?=traduz('Defeito Constatado')?></label>
						<div class='controls controls-row'>
							<div class='span7 input-append'>
								<?php
									echo array2select(
										'defeito_constatado', 'defeito_constatado',
										pg_fetch_pairs(
											$con,
											"SELECT defeito_constatado, descricao
											   FROM tbl_defeito_constatado
											  WHERE fabrica=$login_fabrica
											  ORDER BY descricao"
										),
										$defeito_constatado,
										"class='frm'",
										'ESCOLHA', true
									);
								?>
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
		<?php }
		if(in_array($login_fabrica,$arr_troca_rev)) { /* HD 362629 */ ?>
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
						<? if (!in_array($login_fabrica, array(72))) { ?>
								<?
									$titulo_coluna = (in_array($login_fabrica, array(140,157))) ? "Tipo de OS Revenda" : "Revenda / Troca ";
									$titulo_coluna = (in_array($login_fabrica, array(141,144,151))) ? "Troca de Produto" : $titulo_coluna;
								?>
								<label class='control-label' for='codigo_posto'><?= $titulo_coluna ?></label>
								<div class='controls controls-row'>
									<div class='span7 input-append'>			
										<? if(!in_array($login_fabrica, array(140,157))) {  ?>
											<select name="rev_troca" class="frm">
												<option value="0">ESCOLHA</option>
												<?php
												if (in_array($login_fabrica, array(141,144))) {
													?>
													<option value="troca" <?= $rev == 't' ? 'selected' : '' ?>>Sim</option>
													<option value="">Não</option>
													<?php
												} else { ?>
													<option value="rev" <?= $rev == 't' ? 'selected' : '' ?>>Revenda</option>
													<?php if(!in_array($login_fabrica, array(140,141,144))){ ?>
														<option value="troca" <?= $troca_prod == 't' ? 'selected' : '' ?>>Troca</option>
													<?php } ?>
											<?php } ?>
										</select>
				
									<? }else{ ?>
										Revenda Troca
										<input type="checkbox" name="rev_troca" class="frm" value="rev" <?= $rev == 't' ? 'checked' : ''; ?> />
									<? } ?>
									</div>	
								</div>
						<? } else { ?>
								<label class='control-label' for='codigo_posto'>Troca</label>
									<?php
									if ($troca == 'troca' || $troca_prod == 't') {
										$checked_troca = " CHECKED ";
									} else {
										$checked_troca = " ";
									}
									?>
									<input type="checkbox" name="rev_troca" id="troca" <?=$checked_troca?> value="troca" >

						<? } ?>
					</div>
				</div>
				<div class='span2'></div>
			</div>	
		<?php	
		} 

		if($usa_solucao) { /* HD 362629 */ ?>
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='codigo_posto'>Solução</label>
						<div class='controls controls-row'>
							<div class='span7 input-append'>
								<?php
								echo array2select(
									'solucao', 'solucao',
									pg_fetch_pairs(
										$con,
										"SELECT solucao, descricao
										   FROM tbl_solucao
										  WHERE fabrica=$login_fabrica AND ativo IS TRUE
										  ORDER BY descricao"
									),
									$solucao,
									"class='frm'",
									'ESCOLHA', true
								);
								if ($login_fabrica == 1) { ?>
										<div>
											*Este campo servirá apenas para OS de Satisfação e Troca
										</div>
								<?  } ?>
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
		<? } 

		 if ($usa_familia) { ?>
		 	<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='codigo_posto'>Família</label>
						<div class='controls controls-row'>
							<div class='span7 input-append'>
								<?
								##### INÍCIO FAMÍLIA #####
								echo array2select(
									'familia', 'familia',
									pg_fetch_pairs(
										$con,
										"SELECT familia, descricao
										   FROM tbl_familia
										  WHERE fabrica=$login_fabrica AND ativo IS TRUE
										  ORDER BY descricao"
									),
									$familia,
									"class='frm'",
									'ESCOLHA', true
								);
								##### FIM FAMÍLIA #####
								?>
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
<?php
            }
            if ($usa_servico) {
?>
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='codigo_posto'>Serviço Realizado</label>
						<div class='controls controls-row'>
							<div class='span7 input-append'>
								<?
								##### INÍCIO SERVIÇO #####
								echo array2select(
									'servico_realizado', 'servico_realizado',
									pg_fetch_pairs(
										$con,
										"SELECT servico_realizado, descricao
										   FROM tbl_servico_realizado
										  WHERE fabrica=$login_fabrica
										  AND ativo IS TRUE
										  ORDER BY descricao"
									),
									$servico_realizado,
									"class='frm'",
									'ESCOLHA', true
								);
								##### FIM SERVIÇO #####
								?>
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
		<?php }
		if (in_array($login_fabrica, array(141,144,145,151,164,183,184,186,200))) { ?>
			<div class='row-fluid'>
				<div class='span2'></div>
				
				<?php if (!in_array($login_fabrica, array(145,184,200))) { ?>
					<div class='span4'>
						<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
							<label class='control-label' for='codigo_posto'><?=traduz('Qtde de dias')?></label>
							<div class='controls controls-row'>
								<input type="text" class="frm <?= ($login_fabrica ==183)?'span4':'' ?>" id="qtde_dias" name="qtde_dias" value="<?=$qtde_dias?>" size="5" maxlength="20" >
							</div>
						</div>
					</div>
				<?php }
				if ($usa_tipo_posto) { ?>
					<div class='span4'>
						<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
							<label class='control-label' for='codigo_posto'><?=traduz('Tipo de Posto')?></label>
							<div class='controls controls-row'>
								<?php
								echo array2select(
									'tipo_posto', 'tipo_posto',
									pg_fetch_pairs(
										$con,
										"SELECT tipo_posto, descricao
										   FROM tbl_tipo_posto
										  WHERE fabrica=$login_fabrica AND ativo IS TRUE
										  ORDER BY descricao"
									),
									$tipo_posto,
									"class='frm'",
									'ESCOLHA', true
								);
								?>
							</div>
						</div>
					</div>
				<?php } ?>
				<div class='span2'></div>
			</div>
		<?php }
		if (in_array($login_fabrica, $arr_usa_tipo_atendimento)) { ?>
			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
						<label class='control-label' for='codigo_posto'><?=traduz('Tipo de Atendimento')?></label>
						<div class='controls controls-row'>
							<div class='span6 input-append'>
								<?
								##### INÍCIO tipo de Atendimento #####
								echo array2select(
									'tipo_atendimento', 'tipo_atendimento',
									pg_fetch_pairs(
										$con,
										"SELECT tipo_atendimento, descricao
										   FROM tbl_tipo_atendimento
										  WHERE fabrica=$login_fabrica AND ativo IS TRUE
										  ORDER BY descricao"
									),
									$tipo_atendimento,
									"class='frm'",
									'ESCOLHA', true
								);
								##### FIM tipo de atendimento #####
								?>
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>
		<?php } ?>

		<?php if ($login_fabrica != 183){ ?>
		<br /><br />
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span3'>
				<div class='control-group'>
					<label class='control-label' for='codigo_posto'>
						<?=traduz('Mão de obra')?><img src="../imagens/help.png" title="Necessário se for apenas por este tópico" style="cursor:help">
					</label>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("mobra", $msg_erro_campo["campo"])) ? "error" : ""?>'>
					<div class='controls'>
						<div class='span3'>
							<input type="text" class="span12 excecao" id="mobra" name="mobra" value="<?= $mobra ?>" size="5" maxlength="20" >
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span3'>
				<div class='control-group'>
					<label class='control-label' for='codigo_posto'>
						<?=traduz('Adicional de Mão-de-obra')?><img src="../imagens/help.png" title="Necessário se for apenas por este tópico" style="cursor:help">
					</label>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("mobra", $msg_erro_campo["campo"])) ? "error" : ""?>'>
					<div class='controls'>
						<div class='span3'>
							<input type="text" class="span12 excecao" id="adicional_mobra" name="adicional_mobra" value="<?= $adicional_mobra ?>" size="5" maxlength="20">
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>
		<?php } ?>
		<div class='row-fluid'>
			<div class='span2'></div>
			<div class='span3'>
				<div class='control-group'>
					<label class='control-label' for='codigo_posto'>
						<?=traduz('Percentual de Mão de obra')?><img src="../imagens/help.png" title="Necessário se for apenas por este tópico" style="cursor:help">
					</label>
				</div>
			</div>
			<div class='span4'>
				<div class='control-group <?=(in_array("mobra", $msg_erro_campo["campo"])) ? "error" : ""?>'>
					<div class='controls'>
						<div class='span3'>
							<input type="text" class="span12 excecao" id="percentual_mobra" name="percentual_mobra" value="<?= $percentual_mobra ?>" size="5" maxlength="20">
						</div>
					</div>
				</div>
			</div>
			<div class='span2'></div>
		</div>

		<?php if ($login_fabrica == 72) {?>

			<div class='row-fluid'>
				<div class='span2'></div>
				<div class='span4'>
					<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
						<label><?=traduz('Percentual de Desconto')?></label>
						<div class='controls controls-row'>
							<div class='span3'>
								<input type="text" class="frm" id="percentual_desconto" name="percentual_desconto" value="<?= $percentual_desconto ?>" size="5" maxlength="20">
							</div>
						</div>
					</div>
				</div>
				<div class='span2'></div>
			</div>

		<?php }
		if($login_fabrica == 15) {
			$altera_recarga_gas = "";
			if(strlen($solucao) >0) {
				$altera_recarga_gas = "checked";
			} ?>

			<div class='row-fluid'>
				<div class='span2'></div>
					<div class='span8'>
						 <label class="checkbox">
								<?php $checked = ($todos_postos == 't') ? 'checked' : ''; ?>
								<input type="checkbox" class="frm" name="recarga_de_gas" <?= $altera_recarga_gas;?> id="recarga_de_gas" value="t" size="20" maxlength="20">
								<?=traduz('Recarga de Gás')?>
								<img src="../imagens/help.png" title="Recarga de Gás" style="cursor:help">
						</label>
					</div>
				<div class='span2'></div>	
			</div>

		<?php }
		if ($usa_peca_lancada) { ?>
			<div class='row-fluid'>
				<div class='span2'></div>
					<div class='span8'>
						 <label class="checkbox">
								<? $checked_peca_lancada = ($peca_lancada == 't') ? "CHECKED" : ''; ?>
								<input type="checkbox" name="peca_lancada" id="peca_lancada" class="frm" value="true" <?= $checked_peca_lancada ?> />
								<?=traduz('Somente com Peças Lançadas')?>
						</label>
					</div>
				<div class='span2'></div>	
			</div>
		<? } ?>

		<br /><br />
		<input type='hidden' name='btnacao' value=''>

		<!-- button[type=button][value=$][title]{$} -->
		<button class="btn btn-primary" type="button" value="gravar"  title="Criar nova exceção"><?=traduz('Gravar')?></button>
		<button class="btn btn-danger" type="button" value="excluir" title="Excluir exceção usando o filtro"><?=traduz('Excluir')?></button>
		<button class="btn btn-warning" type="reset"  value="limpar"  title="Limpa o formulário"><?=traduz('Limpar')?></button>
		<br /><br />
		<button class="btn btn-default" type="button" value="listar"  title="Mostra exceções conforme o filtro"><?=traduz('Pesquisar as exceções')?></button>

	<input class='btn btn-primary' type='button' id='btn_filtro' value='<?=traduz("Listar TODAS as exceções")?>' />
	<?php if (!empty($excecao_mobra)) { ?>
				<a class="btn btn-info" rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_excecao_mobra&id=<?php echo $excecao_mobra; ?>' name="btnAuditorLog"><?=traduz('Visualizar Log Auditor')?></a>
	<?php } else { ?>
			<a class="btn btn-info" rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_excecao_mobra&id=<?=$login_fabrica."*".$login_fabrica?>' name="btnAuditorLog"><?=traduz('Visualizar Log Auditor Geral')?></a>
	<?php } ?>
	<br /><br />

<?
$sql = "SELECT  *
		FROM    tbl_excecao_mobra
		JOIN    tbl_produto ON tbl_produto.produto = tbl_excecao_mobra.produto
		JOIN    tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
		JOIN    tbl_posto   ON tbl_posto.posto     = tbl_excecao_mobra.posto
		WHERE   tbl_linha.fabrica = $login_fabrica;";
$res = pg_query($con,$sql);

if (pg_num_rows($res) > 0) {

// <hr>
// <div id='subBanner'>
// 	<h1>.:: Relação de Exceções de Mão-de-Obra ::.</h1>
// 	<h2>Para efetuar alterações, clique na descrição do produto.</h2>
// </div>

}

// $sql = "SELECT      DISTINCT
// 					tbl_excecao_mobra.produto
// 		FROM        tbl_excecao_mobra
// 		JOIN        tbl_produto ON tbl_produto.produto = tbl_excecao_mobra.produto
// 		JOIN        tbl_linha   ON tbl_linha.linha     = tbl_produto.linha
// 		JOIN        tbl_posto   ON tbl_posto.posto     = tbl_excecao_mobra.posto
// 		WHERE       tbl_linha.fabrica = $login_fabrica
// 		ORDER BY    tbl_excecao_mobra.produto;";
// $res = pg_query($con,$sql);
//
// for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
// 	$div = false;
//
// 	$produto = trim(pg_fetch_result($res,$x,produto));
//
// 	$sql = "SELECT      tbl_excecao_mobra.excecao_mobra ,
// 						tbl_posto.cnpj                  ,
// 						tbl_posto.nome                  ,
// 						tbl_produto.referencia          ,
// 						tbl_produto.descricao           ,
// 						tbl_excecao_mobra.mao_de_obra
// 			FROM        tbl_excecao_mobra
// 			JOIN        tbl_produto ON  tbl_produto.produto = tbl_excecao_mobra.produto
// 			JOIN        tbl_linha   ON  tbl_linha.linha     = tbl_produto.linha
// 			JOIN        tbl_posto   ON  tbl_posto.posto     = tbl_excecao_mobra.posto
// 			WHERE       tbl_linha.fabrica         = $login_fabrica
// 			AND         tbl_excecao_mobra.produto = $produto
// 			ORDER BY    tbl_produto.descricao;";
// 	$res0 = pg_query($con,$sql);
//
// 	if (pg_num_rows($res0) > 0) {
// 		$div = true;
// 	}
//
// 	if ($div == true) {
// 		#echo "<div id=\"wrapper\">\n";
// 	}
//
// 	for ($y = 0 ; $y < pg_num_rows($res0) ; $y++){
// 		$excecao_mobra  = trim(pg_fetch_result($res0,$y,excecao_mobra));
// 		$posto_cnpj     = trim(pg_fetch_result($res0,$y,cnpj));
// 		$fposto_cnpj    = substr($posto_cnpj,0,2) .".". substr($posto_cnpj,2,3) .".". substr($posto_cnpj,5,3) ."/". substr($posto_cnpj,8,4) ."-". substr($posto_cnpj,12,2);
// 		$posto_nome     = trim(pg_fetch_result($res0,$y,nome));
// 		$referencia     = trim(pg_fetch_result($res0,$y,referencia));
// 		$descricao      = trim(pg_fetch_result($res0,$y,descricao));
// 		$mobra          = trim(pg_fetch_result($res0,$y,mao_de_obra));
//
// 		if ($posto_cnpj <> $posto_cnpj_anterior) {
// 			echo "<hr>\n";
//
// 			echo "<div id='middleCol'>\n";
// 			echo "    <h1>« $fposto_cnpj - $posto_nome »</h1>\n";
// 			echo "</div>\n";
//
// 			$quebra = true;
// 		}else{
// 			$quebra = false;
// 			echo "<div id='wrapper'>\n";
// 				echo "<div id='middleCol'>\n";
// 					echo "    $referencia - <a href='$PHP_SELF?excecao_mobra=$excecao_mobra'>$descricao</a> - ". number_format($mobra,2,",",".") ."\n";
// 				echo "</div>\n";
// 			echo "</div>\n";
//
// 		}
//
// 		if ($quebra == true) {
// 			echo "<div id='wrapper'>\n";
// 				echo "<div id='middleCol'>\n";
// 					echo "    $referencia - <a href='$PHP_SELF?excecao_mobra=$excecao_mobra'>$descricao</a> - ". number_format($mobra,2,",",".") ."\n";
// 				echo "</div>\n";
// 			echo "</div>\n";
// 		}
//
// 		$posto_cnpj_anterior = trim(pg_fetch_result($res0,$y,cnpj));
// 	}
//
// 	if ($div == true) {
// 		#echo "</div>\n";
// 	}
// }
?>
</form>
</div>
<?
if ($btnacao == 'listar' || $_REQUEST['listar'] == 'ok') {
	if (count(array_filter($_REQUEST)) > 1) {
		$where = array();

		/*HD - 4300021*/
		if ($login_fabrica == 151) {
			$aux_cnpj = getPost('posto_cnpj');

			if (strlen($aux_cnpj) > 0) {
				$where['tbl_posto_fabrica.codigo_posto'] = $aux_cnpj;
				$where['tbl_posto_fabrica.fabrica']      = $login_fabrica;
			}
		} else {
			$where['tbl_posto.cnpj']                     = preg_replace('/\D/', '', getPost('posto_cnpj'));
		}

		$where['tbl_excecao_mobra.linha']            = (int) getPost('linha')            ? : '';
		$where['tbl_excecao_mobra.familia']          = (int) getPost('familia')          ? : '';
		$where['tbl_excecao_mobra.produto']          = (int) getPost('produto')          ? : '';
		$where['tbl_excecao_mobra.qtde_dias']        = (int) getPost('qtde_dias')        ? : '';
		$where['tbl_excecao_mobra.tipo_atendimento'] = (int) getPost('tipo_atendimento') ? : '';

		if (empty($produto)) {
			$where['tbl_produto.referencia'] = getPost('referencia');
			$where['tbl_produto.descricao%'] = getPost('descricao'); // ILIKE!
		}

		$where = array_filter($where, 'strlen');

		if (getPost('rev_troca') == 'rev') {
			$where['tbl_excecao_mobra.troca_produto'] = false;
			$where['tbl_excecao_mobra.revenda'] = true;
		}

		if (getPost('rev_troca') == 'troca') {
			$where['tbl_excecao_mobra.troca_produto'] = true;
			$where['tbl_excecao_mobra.revenda'] = false;
		}

		if($usa_eficiencia){
			if((int) getPost('eficiencia') > 0){
				$where["tbl_excecao_mobra.eficiencia"] = (int) getPost('eficiencia') ? : '';
			}

			if((int) getPost('unidade_negocio') > 0){
				$where["tbl_excecao_mobra.distribuidor_sla"] = (int) getPost('unidade_negocio') ? : '';
			}
		}

		$filtros = 'AND ' . sql_where($where);
	}

	$sql = "
        SELECT  tbl_excecao_mobra.excecao_mobra,
                tbl_posto_fabrica.codigo_posto,
                tbl_posto.cnpj,
                tbl_posto.nome,
                tbl_produto.produto,
                tbl_produto.referencia_fabrica as produto_referencia_fabrica,
                tbl_produto.referencia,
                tbl_produto.descricao,
                tbl_linha.nome           AS linha,
                tbl_tipo_posto.descricao AS tipo_posto,
                tbl_excecao_mobra.familia,
                tbl_familia.descricao AS familia_descricao,
                tbl_excecao_mobra.mao_de_obra,
                tbl_excecao_mobra.adicional_mao_de_obra,
                tbl_excecao_mobra.percentual_mao_de_obra,
                tbl_excecao_mobra.revenda,
                tbl_excecao_mobra.troca_produto,
                tbl_tipo_atendimento.descricao as tipo_atendimento_descricao,
                tbl_excecao_mobra.id_revenda,
                tbl_excecao_mobra.peca_lancada,
                tbl_excecao_mobra.distribuidor_sla,
                tbl_excecao_mobra.qtde_dias,
                tbl_excecao_mobra.eficiencia,
                tbl_solucao.descricao AS solucao_descricao,
                tbl_servico_realizado.descricao AS servico_descricao,
                tbl_defeito_constatado.descricao AS defeito_constatado_descricao
        FROM    tbl_excecao_mobra
   LEFT JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto                     = tbl_excecao_mobra.posto
                                    AND tbl_posto_fabrica.fabrica                   = tbl_excecao_mobra.fabrica
   LEFT JOIN    tbl_posto           ON  tbl_posto.posto                             = tbl_posto_fabrica.posto
   LEFT JOIN    tbl_produto         ON  tbl_produto.produto                         = tbl_excecao_mobra.produto
   LEFT JOIN    tbl_linha   AS l1   ON  l1.linha                                    = tbl_produto.linha
                                    AND l1.fabrica                                  = tbl_excecao_mobra.fabrica
   LEFT JOIN tbl_familia    AS ff   ON  ff.familia                                  = tbl_produto.familia
                                    AND l1.fabrica                                  = tbl_excecao_mobra.fabrica
   LEFT JOIN tbl_linha              ON  tbl_linha.linha                             = tbl_excecao_mobra.linha
                                    AND tbl_linha.fabrica                           = tbl_excecao_mobra.fabrica
   LEFT JOIN tbl_tipo_posto         ON  tbl_excecao_mobra.tipo_posto                = tbl_tipo_posto.tipo_posto
   LEFT JOIN tbl_familia            ON  tbl_familia.familia                         = tbl_excecao_mobra.familia
                                    AND tbl_familia.fabrica                         = tbl_excecao_mobra.fabrica
   LEFT JOIN tbl_solucao            ON  tbl_excecao_mobra.solucao                   = tbl_solucao.solucao
                                    AND tbl_solucao.fabrica                         = tbl_excecao_mobra.fabrica
   LEFT JOIN tbl_servico_realizado  ON  tbl_excecao_mobra.servico_realizado         = tbl_servico_realizado.servico_realizado
                                    AND tbl_servico_realizado.fabrica               = tbl_excecao_mobra.fabrica
   LEFT JOIN tbl_tipo_atendimento   ON  tbl_excecao_mobra.tipo_atendimento          = tbl_tipo_atendimento.tipo_atendimento
   LEFT JOIN tbl_defeito_constatado ON  tbl_defeito_constatado.defeito_constatado   = tbl_excecao_mobra.defeito_constatado
                                    AND tbl_defeito_constatado.fabrica              = tbl_excecao_mobra.fabrica
        WHERE   tbl_excecao_mobra.fabrica = $login_fabrica
                $filtros
  ORDER BY      tbl_posto.nome;
    ";

	$res = pg_query($con,$sql);
	$contador_res = pg_num_rows($res);

	if (pg_num_rows($res) > 0) {

		if(in_array($login_fabrica,[1,151])){
			$data = date("d-m-Y-H-i");
			$fileName = "excecao_cadastro_{$login_admin}.csv";
			$file = fopen("xls/{$fileName}", "w");

			$head = utf8_encode("Código Exceção").";Nome Posto;".utf8_encode("Código do Posto").";Linha; Familia;Produto;".utf8_encode("Solução").";".utf8_encode("Mão-de-Obra").";Adicional;Percentual \r\n";

			if($login_fabrica == 151){
				$head = utf8_encode("Código Exceção").";Nome Posto;".utf8_encode("Código do Posto").";Linha; Familia;Produto; Tipo de Atendimento; Tipo de Posto; Qtde de Dias;".utf8_encode("Mão-de-Obra").";Adicional;Percentual; Troca/Revenda \r\n";
			}

			fwrite($file, $head);
			$body = '';
		}
?>
<table class="table table-bordered table-fixed" id="tabela_excecoes">
	<thead>
	    <tr class='titulo_coluna'>
        	<th><?=($login_fabrica == 183) ? traduz('Código Bonificação') : traduz('Código da Exceção'); ?></th>
	        <th><?=traduz('Nome do Posto');?></th>
	        <th><?=traduz('Código do Posto');?></th>
	        <?php if ($login_fabrica == 158) { ?>
			<th><?=traduz('Unidade de Negócio');?></th>
			<th><?=traduz('Eficiência');?></th>
		<?php }
	        if ($login_fabrica == 117) { ?>
	        	<th><?=traduz('Macro - Família');?></th>
	        <?php } else { ?>
			<?php if ($login_fabrica != 183){ ?>
	        		<th><?=traduz('Linha');?></th>
			<?php }
		}
		if (!in_array($login_fabrica, array(149,183))) { ?>
	        	<th><?=traduz('Família');?></th>
		<?php }
		if ($login_fabrica == 171) { ?>
	        	<th><?=traduz('Refência Fábrica');?></th>
		<?php }
		if ($login_fabrica != 183){ ?>
	        	<th><?=traduz('Produto');?></th>
        	<?php }
		if (in_array($login_fabrica, array(101,127,136,139))) { ?>
			<th><?=traduz('Defeito Constatado');?></th>
		<?php }
		echo (in_array($login_fabrica, $arr_usa_tipo_atendimento)) ? "<th>Tipo de Atendimento</th>" : "";
		if ($usa_solucao || $login_fabrica == 15) { ?>
			<th><?=traduz('Solução');?></th>
		<?php }
		if (in_array($login_fabrica, array(141,144,151,164,183,184,186,200))) {
			if (!in_array($login_fabrica, [183,186])) { ?>
				<th><?=traduz('Tipo de Posto');?></th> 
			<?php }
			if (!in_array($login_fabrica, [184,200])) { ?>
				<th><?=traduz('Qtde de Dias');?></th>
			<?php }
		} ?>
		<?php if ($login_fabrica != 183){ ?>
			<th><?=traduz('Mão-de-Obra');?></th>
			<th><?=traduz('Adicional');?></th>
		<?php } ?>
		<th><?=traduz('Percentual');?></th>
		<?php if (in_array($login_fabrica,$arr_troca_rev)){
			if (!in_array($login_fabrica,array(72,141,144))){
				if ($login_fabrica == 140) { ?>
		       			<th><?=traduz('OS Revenda');?></th>
				<?php } else { ?>
	       				<th><?=traduz('Troca/Revenda');?></th>
				<?php }
			} else { ?>
	        		<th><?=traduz('Troca');?></th>
			<?php }
		}
		if (in_array($login_fabrica, $arr_cnpj_rev)) { ?>
		       	<th><?=traduz('Revenda');?></th>
		<?php }
		if ($usa_peca_lancada) { ?>
	        	<th><?=traduz('Peça Lançada');?></th>
		<?php } ?>
	    </tr>
    </thead>
	<?php for ($z = 0 ; $z < $contador_res; $z++) {
		$excecao_mobra              = trim(pg_fetch_result($res,$z,'excecao_mobra'));
		$cnpj                       = trim(pg_fetch_result($res,$z,'cnpj'));
		$codigo_posto               = trim(pg_fetch_result($res,$z,'codigo_posto'));
		$posto                      = trim(pg_fetch_result($res,$z,'nome'));
		$produto                    = trim(pg_fetch_result($res,$z,'produto'));
		$produto_referencia_fabrica = trim(pg_fetch_result($res,$z,'produto_referencia_fabrica'));
		$produto_descricao          = trim(pg_fetch_result($res,$z,'referencia')) ."-". trim(pg_fetch_result($res,$z,'descricao'));
		//if (strlen($'produto')    == '1') $produto = "TODOS";
		$linha                      = trim(pg_fetch_result($res,$z,'linha'));
		$familia                    = trim(pg_fetch_result($res,$z,'familia'));
		$familia_descricao          = trim(pg_fetch_result($res,$z,'familia_descricao'));
		$mobra                      = (float) trim(pg_fetch_result($res,$z,'mao_de_obra'));
		$adicional_mobra            = (float) trim(pg_fetch_result($res,$z,'adicional_mao_de_obra'));
		$percentual_mobra           = (float) trim(pg_fetch_result($res,$z,'percentual_mao_de_obra'));
		$rev                        = trim(pg_fetch_result($res,$z,'revenda'));
		$troca                      = trim(pg_fetch_result($res,$z,'troca_produto'));
		$revenda_id                 = pg_fetch_result($res,$z,'id_revenda');
		$xUnidadeNegocio            = "";
		$xUnidadeNegocioNome        = "";

		if ($usa_eficiencia) {	
			$unidade_negocio = pg_fetch_result($res,$z,'distribuidor_sla');
			$eficiencia      = pg_fetch_result($res,$z,'eficiencia');

			if($eficiencia == "" || $eficiencia == 0){
				$eficiencia = "Nenhum";
			} else if($eficiencia <= 3){
				$eficiencia = "D+".($eficiencia-1);
			} else {
				$eficiencia = "Acima de D+2";
			}

			if (strlen($unidade_negocio) > 0) {
            	$yunidadeNegocio     = $oDistribuidorSLA->SelectUnidadeNegocioNotIn(null, $unidade_negocio);
				$xUnidadeNegocio     = $yunidadeNegocio[0]["unidade_negocio"];
				$xUnidadeNegocioNome = $yunidadeNegocio[0]["cidade"];
			}
		}
		$tipo_posto                 = pg_fetch_result($res,$z,'tipo_posto');
		$tipo_posto_excel = (empty($tipo_posto)) ? "TODOS" : $tipo_posto;
		$qtde_dias                  = pg_fetch_result($res,$z,'qtde_dias');
		$solucao_descricao          = pg_fetch_result($res, $z, 'solucao_descricao');
		$servico_descricao          = pg_fetch_result($res, $z, 'servico_descricao');
		$tipo_atendimento_descricao = pg_fetch_result($res, $z, 'tipo_atendimento_descricao');
		$peca_lancada               = pg_fetch_result($res, $z, 'peca_lancada');
		
		if(strlen($familia_descricao) > 0){
			$familia_descricao_excel = "$familia_descricao";
		}else{
			$familia_descricao_excel 	= "TODAS DA LINHA ESCOLHIDA";
		}

		if (strlen($familia_descricao) == 0) $familia_descricao = "<i style='color: #959595'>TODAS</i>";
		$cnpj                       = (!empty($cnpj)) ? preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj) : "";

		if (in_array($login_fabrica, array(101,127,136,139))) {
			$defeito_constatado_descricao = pg_fetch_result($res, $z, "defeito_constatado_descricao");
		}

		// $percentual_mobra = empty($percentual_mobra) ? 0 : $percentual_mobra;
		// $adicional_mobra  = empty($adicional_mobra) ? 0 : $adicional_mobra;
		// $mobra = (empty($mobra)) ? 0 : $mobra;

		if($login_fabrica == 15) {
			if($solucao_descricao <> '') {
				if(strlen($linha) > 0){
					$familia_descricao = "<i style='color: #959595;'>&nbsp;</i>";
					$produto_descricao = "<i style='color: #959595'>&nbsp;</i>";
				}

				if(strlen($familia) > 0){
					$linha             = "&nbsp;";
					$produto_descricao = "<i style='color: #959595'>&nbsp;</i>";
				}

				if(strlen($produto) > 0){
					$linha             = "&nbsp;";
					$familia           = "&nbsp;";
				}

				if(strlen($linha) == 0 AND strlen($familia) == 0 AND strlen($produto) == 0){
					$linha             = "<i style='color: #959595;'>&nbsp;</i>";
					$familia_descricao = "<i style='color: #959595;'>&nbsp;</i>";
					$produto_descricao = "<i style='color: #959595;'>&nbsp;</i>";
				}

			}else{

				if(strlen($linha) > 0){
					$familia_descricao = "<i style='color: #959595;'>TODAS DA LINHA ESCOLHIDAs</i>";
					$produto_descricao = "<i style='color: #959595'>TODOS DA FAMILIA ESCOLHIDAs</i>";
				}

				if(strlen($familia) > 0){
					$linha             = "&nbsp;";
					$produto_descricao = "<i style='color: #959595'>".traduz("TODOS DA FAMILIA ESCOLHIDA")."</i>";
				}
			}

			if(strlen($produto) > 0){
				$linha             = "&nbsp;";
				$familia           = "&nbsp;";
			}

			if(strlen($linha) == 0 AND strlen($familia) == 0 AND strlen($produto) == 0){
				$linha             = "<i style='color: #959595;'>&nbsp;</i>";
				$familia_descricao = "<i style='color: #959595;'>&nbsp;</i>";
				$produto_descricao = "<i style='color: #959595;'>&nbsp;</i>";
			}

		}else{

			if(strlen($linha) > 0){
				$linha_excel  = "$linha";
			}else{
				$linha_excel  = traduz("TODAS");
			}

			if(strlen($produto_referencia_fabrica) > 0 ){
				$produto_descricao_excel 	= "$produto_descricao";					
			}else{
				$produto_descricao_excel 	= traduz("TODOS DA FAMILIA ESCOLHIDA");
			}

			if (empty($tipo_posto)) {
				$tipo_posto = "<i style='color: #959595'>".traduz("TODOS")."</i>";
			}

			if(strlen($linha) > 0){
				$familia_descricao = "<i style='color: #959595;'>".traduz("TODAS DA LINHA ESCOLHIDA")."</i>";
				$produto_descricao = "<i style='color: #959595'>".traduz("TODOS DA FAMILIA ESCOLHIDA")."</i>";
			}

			if(strlen($familia) > 0){
				$linha             = "&nbsp;";
				$produto_descricao = "<i style='color: #959595'>".traduz("TODOS DA FAMILIA ESCOLHIDA")."</i>";
			}

			if(strlen($produto) > 0){
				$linha             = "&nbsp;";
				$familia           = "&nbsp;";
			}					

			if(strlen($linha) == 0 AND strlen($familia) == 0 AND strlen($produto) == 0){
				$linha             = "<i style='color: #959595;'>".traduz("TODAS")."</i>";
				$familia_descricao = "<i style='color: #959595;'>".traduz("TODAS DA LINHA ESCOLHIDA")."</i>";
				$produto_descricao = "<i style='color: #959595;'>".traduz("TODOS DA FAMILIA ESCOLHIDA")."</i>";
			}

			if(strlen($codigo_posto) == 0){
				$codigo_posto = "<i style='color: #959595'>".traduz("TODOS OS POSTOS")."</i>";
				$posto = "<i style='color: #959595'>".traduz("TODOS OS POSTOS")."</i>";
				$codigo_posto_excel = traduz("TODOS OS POSTOS");
				$posto_excel = traduz("TODOS OS POSTOS");
			}else{
				$codigo_posto_excel = "$codigo_posto";
				$posto_excel = "$posto";
			}
		}
		
		if($login_fabrica == 1){
			$body .= "$excecao_mobra;$posto_excel;$codigo_posto_excel;$linha_excel;$familia_descricao_excel;$produto_descricao_excel;$solucao_descricao;".number_format((float)$mobra,2,",",".").";".number_format((float)$adicional_mobra,2,",",".").";".number_format((float)$percentual_mobra,2,",",".")." \r\n "; 
		}else{
			$troca_rev = $rev == 't' ? 'Revenda' : '';
			$troca_rev = $troca == 't' ? 'Troca' : $troca_rev;

			$body .= "$excecao_mobra;$posto_excel;$codigo_posto_excel;$linha_excel;$familia_descricao_excel;$produto_descricao_excel;$tipo_atendimento_descricao;$tipo_posto_excel;$qtde_dias;".number_format((float)$mobra,2,",",".").";".number_format((float)$adicional_mobra,2,",",".").";".number_format((float)$percentual_mobra,2,",",".").";$troca_rev \r\n ";
		}
		
?>
    <tr>
        <td class='tac'><font size='1' face='Verdana, Arial, Helvetica, san-serif'><a href='<?=$PHP_SELF?>?excecao_mobra=<?=$excecao_mobra?>'><?=$excecao_mobra?></a></font></td>
        <td class='tac'><font size='1' face='Verdana, Arial, Helvetica, san-serif'><a href='<?=$PHP_SELF?>?excecao_mobra=<?=$excecao_mobra?>'><?=$codigo_posto?></a></font></td>
        <td align='left'><font size='1' face='Verdana, Arial, Helvetica, san-serif'><a href='<?=$PHP_SELF?>?excecao_mobra=<?=$excecao_mobra?>'><?=$posto?></a></font></td>
        
		<?php if ($usa_eficiencia){?>
		       <td align='left'><font size='1' face='Verdana, Arial, Helvetica, san-serif'><?=$xUnidadeNegocioNome;?></font></td>
		       <td style="text-align: center"><font size='1' face='Verdana, Arial, Helvetica, san-serif'><?=$eficiencia;?></font></td>
		<?php }?>
		<?php if ($login_fabrica != 183){ ?>
        <td align='left'><font size='1' face='Verdana, Arial, Helvetica, san-serif'><?=$linha?></font></td>
<?php
		}
			if(!in_array($login_fabrica, array(149,183))){
?>
        <td align='left'><font size='1' face='Verdana, Arial, Helvetica, san-serif'><?=$familia_descricao?></font></td>
<?php
			}
?>
		<?php if ($login_fabrica == 171) {?>
        <td align='left'><font size='1' face='Verdana, Arial, Helvetica, san-serif'><?=$produto_referencia_fabrica?></font></td>
		<?php }?>
		<?php if ($login_fabrica != 183){ ?>
        <td align='left'><font size='1' face='Verdana, Arial, Helvetica, san-serif'><?=$produto_descricao?></font></td>
        <?php } ?>
<?php
			if (in_array($login_fabrica, array(101,127,136,139))) {
?>
        <td align='left'><font size='1' face='Verdana, Arial, Helvetica, san-serif'><?=$defeito_constatado_descricao?></font></td>
<?php
            }
			if (in_array($login_fabrica, $arr_usa_tipo_atendimento)) {
				if (empty($tipo_atendimento_descricao)) {
?>
        <td align='left'><i style='color: #959595;'><?=traduz('TODOS')?></i></td>
<?php
				}else{
?>
        <td align='left'><font size='1' face='Verdana, Arial, Helvetica, san-serif'><?=$tipo_atendimento_descricao?></font></td>
<?php
				}
			}
			if ($usa_solucao || $login_fabrica == 15) echo "<td align='left'><font size='1' face='Verdana, Arial, Helvetica, san-serif'>$solucao_descricao</font></td>";
			if (in_array($login_fabrica, array(141,144,151,164,183,184,186,200))) {
				if (!in_array($login_fabrica,[183,186])) {
					echo "<td>$tipo_posto</td>";
				}
				if (!in_array($login_fabrica, [184,200])) {
					echo "<td>$qtde_dias</td>";
				}
			} 
			?>
			<?php if ($login_fabrica != 183){ ?>
			<td class='tac'><font size='1' face='Verdana, Arial, Helvetica, san-serif'><?= number_format((float)$mobra,2,",",".") ?></font></td>
			<td class='tac'><font size='1' face='Verdana, Arial, Helvetica, san-serif'><?= number_format((float)$adicional_mobra,2,",",".") ?></font></td>
			<?php } ?>
			<td class='tac'><font size='1' face='Verdana, Arial, Helvetica, san-serif'><?= number_format((float)$percentual_mobra,2,",",".") ?></font></td>

			<?php
			if (in_array($login_fabrica,$arr_troca_rev)) {
				if($login_fabrica == 140){
					$troca_rev = ($rev == 't') ? '<img src="imagens/check.gif">' : '';
				}else if(in_array($login_fabrica,array(141,144))){
					$troca_rev = ($troca == 't') ? '<img src="imagens/check.gif">' : '';
				}else{
					$troca_rev = $rev == 't' ? 'Revenda' : '-';
					$troca_rev = $troca == 't' ? 'Troca' : $troca_rev;
				}
?>
        <td align='center'><font size='1' face='Verdana, Arial, Helvetica, san-serif'><?=$troca_rev?></font></td>
<?php
			}
			if (in_array($login_fabrica, $arr_cnpj_rev) && !empty($revenda_id) ) {

				$sql_revenda = "SELECT nome FROM tbl_revenda WHERE revenda = " . $revenda_id;
				$res_revenda = pg_query($con,$sql_revenda);
				if (pg_num_rows($res) == 0) {
?>
        <td>-</td>
<?php
                } else {
?>
        <td><?=pg_fetch_result($res_revenda,0,0)?></td>
<?php
                }
			} else if(in_array($login_fabrica, $arr_cnpj_rev)) {
?>
        <td>-</td>
<?php
                if ($usa_peca_lancada) {
                    if (!empty($peca_lancada) and $peca_lancada == 't') {
?>
        <td><img src="imagens/ativo.png" alt="\"></td>
<?php
                    }else{
?>
        <td><img src="imagens/inativo.png" alt="\">
<?php
                    }
                }
            }
?>
    </tr>
<?php
        }
        fwrite($file, $body);
?>

</table>
<br />
		<?php
			if(in_array($login_fabrica,[1,151])){
		?>
		<div class="btn_excel">
			
			<span><a href="./xls/<?=$fileName?>" targer="_blank">
				<img style="width:40px ; height:40px;" src='imagens/icon_csv.png' /></span>
			
			<span class="txt"><?=traduz('Gerar Arquivo CSV')?></span>
			</a>
		</div>
		<br />
		<?php } ?>
<?php
	} else {
		echo "<font style='font:14px Arial; '>Não Foram Encontrados Resultados para esta Pesquisa!</font>";
	}
}
?>
<script>
	$.dataTableLoad({ table: "#tabela_excecoes" });
</script>
<?php
include "rodape.php";
?>
