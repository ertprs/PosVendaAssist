<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica<>10) header ("Location: index.php");

if(!empty($_POST['analise'])){
	$hd_chamado = $_POST['analise'];
	$analise    = trim($_POST['valor']);

	$sql = "SELECT max(interacao) FROM tbl_hd_chamado_analise where hd_chamado = $hd_chamado";
	$res = pg_query($con,$sql);
	$interacao = (pg_num_rows($res) > 0) ? pg_fetch_result($res,0,0) : 0;
	$interacao++;

	if(strlen($analise) > 0) {
			$sql = "BEGIN TRANSACTION";
			$res = pg_query($con,$sql) ;

			$sql =	'INSERT INTO tbl_hd_chamado_analise (
							hd_chamado,
							analise,
							desenvolvimento,
							teste,
							interacao,
							admin
						) VALUES (
							'.$_POST['analise'].',
							\''.$analise.'\',
							false,
							false,
							'.$interacao.',
							'.$login_admin.'
						);';
			$res = pg_query($con,$sql);
			$msg_erro = pg_last_error($con);

			$sql = 'UPDATE  tbl_hd_chamado SET
					analise = \' \',
					admin_plano_teste = \''.$login_admin.'\'
				 WHERE  hd_chamado = '.$_POST['analise'].';';
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);

			if(empty($msg_erro)){
				$sql = "COMMIT TRANSACTION";
				$msg = "ok";
			}else{
				$sql = "ROLLBACK TRANSACTION";
				$msg = "erro";
			}
			$res = pg_query($con,$sql) ;
	}
	echo $msg;
	exit;
}

if(!empty($_POST['total_chamado'])){
	$sql = "SELECT array_to_string(array_agg(nome_completo),',')
		FROM tbl_admin
		WHERE fabrica = $login_fabrica
		AND   grupo_admin in (2,4)
		AND	  ultimo_acesso > current_timestamp - interval '3 days'
		AND   admin <> 2466
		AND   ativo
		AND   admin not in (SELECT admin FROM tbl_hd_chamado_atendente WHERE data_inicio::date=current_date and data_termino isnull); ";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0 ) {
		$admins = pg_fetch_result($res,0,0);
	}
	$sql = " SELECT
			hd_chamado,
			status,
			tipo_chamado,
			data,
			data_resolvido
			INTO TEMP conta
			FROM tbl_hd_chamado
			WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
			AND tbl_hd_chamado.titulo <> 'Atendimento interativo'
			AND tipo_chamado <> 6
			AND admin NOTNULL
			AND tbl_hd_chamado.status NOT IN('Cancelado','Suspenso') ;

		SELECT count(1) as total_dev,
			(select count(1) from conta where status not in('Novo','Resolvido','Aprovação')) as suporte,
			(select count(1) from conta where tipo_chamado = 5 and status<>'Resolvido') as erro,
			(select count(1) from conta where data::date=current_date) as aberto,
			(select count(1) from conta where data_resolvido::date=current_date and status = 'Resolvido') as resolvido
		FROM conta
		WHERE status <>'Resolvido';";
	$res = pg_query($con,$sql) ;
	$resultado = pg_fetch_all($res);
	foreach($resultado as $key => $valor) {
		foreach($valor as $conta ) {
			echo $conta,"||";
		}
	}
	echo $admins;
	exit;
}

if(!empty($_POST['fixed']) or !empty($_POST['deploy'])){
	$dados = $_POST;
	foreach($dados as $key => $valor){
		$$key =  $valor;
	}

	list($val1,$val2) = explode('&',$valores);
	list($texto,$coment) = explode('=',$val1);
	list($suporte,$admin) = explode('=',$val2);
	$coment = str_replace("+"," ",$coment);

	if(!empty($_POST['fixed'])){
			$sql  = "UPDATE tbl_hd_chamado SET
					status ='Validação',
					atendente = $admin
					WHERE  hd_chamado = $fixed;

					INSERT INTO tbl_hd_chamado_item (
						hd_chamado,
						comentario,
						admin,
						status_item,
						interno
					) VALUES (
						$fixed,
						'$coment',
						$login_admin,
						'Validação',
						't'
				);";
	}else{
			if(stripos($coment,'chamado')) {
				$status = "ValidaçãoHomologação";
			}elseif(stripos($coment,'omologa')) {
				$status = "EfetivaçãoHomologação";
			}else{
				$status = "Efetivação";
			}
			$coment = rawurldecode($coment);
			$sql  = "UPDATE tbl_hd_chamado SET
					status ='$status',
					atendente = $admin
					WHERE  hd_chamado = $deploy;

					INSERT INTO tbl_hd_chamado_item (
						hd_chamado,
						comentario,
						admin,
						status_item,
						interno
					) VALUES (
						$deploy,
						'$coment',
						$login_admin,
						'$status',
						't'
				);";
	}
	$res = pg_query($con,$sql);
	exit;
}

if(!empty($_POST['passar'])){
	include_once 'funcoes.php';

	$dados = $_POST;
	$painel = new Painel($con);
	foreach($dados as $key => $valor){
		$$key =  $valor;
	}
	$sql = "SELECT backlog FROM tbl_backlog order by backlog desc limit 1";
	$res = pg_query($con,$sql);
	$backlog = pg_fetch_result($res,0,'backlog');
	$horas_faturadas = ($fat == 'true') ? $hr_anal : 0 ;

	$sql = "SELECT previsao_termino_interna FROM tbl_hd_chamado
			WHERE atendente = $dev
			AND   fabrica_responsavel = $login_fabrica
			AND   status ~* 'execu'
			and    previsao_termino_interna > current_date - interval '3 days'
			order by previsao_termino_interna desc limit 1;";
	$res = pg_query($con,$sql);
	$previsao_termino_interna = pg_fetch_result($res,0,'previsao_termino_interna');
    $previsao = (empty($previsao_termino_interna)) ? "current_timestamp" : "'$previsao_termino_interna'::timestamp";

	$sql = "UPDATE tbl_backlog_item SET
			backlog = $backlog,
			horas_analisadas = $hr_anal,
			desenvolvedor = $dev,
			prioridade = '$prioridade',
			suporte = case when suporte notnull then suporte else $suporte end,
			admin_alterou = $login_admin
		WHERE hd_chamado = $passar;

		INSERT INTO tbl_backlog_item(
			backlog,
			hd_chamado,
			projeto,
			prioridade,
			faturado,
			horas_analisadas,
			horas_faturadas,
			horas_utilizadas,
			analista,
			desenvolvedor,
			suporte,
			admin
		) SELECT DISTINCT
			$backlog,
			$passar,
			1,
			'$prioridade',
			$fat,
			$hr_anal,
			$horas_faturadas,
			0,
			$login_admin,
			$dev,
			$suporte,
			$login_admin
		FROM tbl_backlog
		LEFT JOIN tbl_backlog_item on tbl_backlog_item.backlog = tbl_backlog.backlog and tbl_backlog_item.hd_chamado = $passar
		WHERE hd_chamado ISNULL
		AND tbl_backlog.backlog = $backlog ;

		UPDATE tbl_hd_chamado SET
			status ='Aguard.Execução',
			atendente = $dev
		WHERE  hd_chamado = $passar;";

	if(!empty($backlog) and !empty($passar)) {
		$res = pg_query($con,$sql);
		prioridade($dev);
		$painel->setChamadoAtendente($passar);
	}
	exit;
}

if(!empty($_GET['admin_combo'])){

	switch($_GET['admin_combo']){
	      case 'suporte': $sql= "SELECT admin,nome_completo FROM tbl_admin WHERE fabrica = 10 AND ativo IS TRUE  and grupo_admin in (1,6);";
			break;
	      case 'desenvolvedor':  $sql= "SELECT admin,nome_completo FROM tbl_admin WHERE fabrica = 10 AND ativo IS TRUE and grupo_admin in (4,2,7,1) ;";
			break;
		  case 'all':  $sql= "SELECT admin,nome_completo FROM tbl_admin WHERE fabrica = 10 AND ativo IS TRUE and grupo_admin notnull;";
			break;

	}
        $res = pg_query($con, $sql);
	$resultado = pg_fetch_all($res);
	foreach($resultado as $key => $valor) {
		$resultado[$key] = array_map('utf8_encode',$valor);
	}
	if(pg_num_rows($res) > 0){
		echo "{'sucess':'true','admins':";
		echo json_encode($resultado);
		echo "}";
	}
	exit;
}
if(!empty($_GET['pesquisa_tipo'])) {
	if($_GET['pesquisa_tipo'] == 'sim' and empty($_POST['combo_tipo'])){
		echo "{'sucess':'false','tipoItems':'[]'}";
		exit;
	}

	switch($_POST['combo_tipo']) {
		case 'admin':$sql="SELECT
				admin::text as valor,
				UPPER(login) AS descricao
			FROM tbl_admin
			WHERE fabrica = $login_fabrica
			AND   grupo_admin notnull
			AND   NOT (admin = $login_admin)
			AND   ativo
			UNION 
			SELECT
		       		array_to_string(array_agg(admin),',') AS valor,
				'DESENVOLVEDOR' AS descricao
			FROM tbl_admin
		        JOIN tbl_grupo_admin using(grupo_admin)
			WHERE grupo_admin in (2, 4)
			AND   tbl_admin.ativo";
			break;
		case 'fabrica':$sql="SELECT
						fabrica as valor,
						UPPER(nome) AS descricao
				FROM tbl_fabrica
				WHERE ativo_fabrica";
			break;
		case 'tipo_hd':
			$sql="
				SELECT
				tipo_chamado as valor,
				descricao
		FROM tbl_tipo_chamado
		WHERE 1=1";
		break;
		case 'status':
			$sql="
				SELECT distinct fn_retira_especiais(status) as valor, status as descricao
				FROM tbl_hd_chamado
				WHERE fabrica_responsavel = $login_fabrica
				AND status NOT IN ('Cancelado')
				UNION
				select 'Financeiro' as valor, 'Financeiro' as descricao";
			break;
		case 'suporte':
			$sql="SELECT
				admin::text as valor,
				UPPER(login) AS descricao
			FROM tbl_admin
			WHERE fabrica = $login_fabrica
			AND   grupo_admin =6 
			AND   NOT (admin = $login_admin)
			AND   ativo
			UNION 
			SELECT
		       	 586::text as valor,
				 'RONALDO' AS descricao  ";
		break;

		case 'equipe':
			$sql="SELECT
				admin.parametros_adicionais::jsonb->>'equipe' as valor,
				upper(admin.parametros_adicionais::jsonb->>'equipe') as descricao
			FROM tbl_admin admin
			WHERE ativo
			and grupo_admin notnull
			and admin.parametros_adicionais::jsonb->>'equipe' notnull";
		break;
	}

	$res = pg_query($con,$sql);
	$resultado = pg_fetch_all($res);
	foreach($resultado as $key => $valor) {
		$resultado[$key] = array_map('utf8_encode',$valor);
	}
	if(pg_num_rows($res) > 0){
		echo "{'sucess':'true','tipoItems':";
		echo json_encode($resultado);
		echo "}";
	}
	exit;

}

if(isset($_POST['trabalho'])) {
	$sql="SELECT
		TO_CHAR(data_inicio,' hh24:mi')            AS hora_inicio ,
		tbl_hd_chamado.hd_chamado                                 ,
		tbl_hd_chamado.titulo                                     ,
		tbl_fabrica.nome
	FROM tbl_hd_chamado_atendente
	JOIN tbl_admin using(admin)
	JOIN tbl_hd_chamado using(hd_chamado)
	JOIN tbl_fabrica    ON tbl_fabrica.fabrica = tbl_hd_chamado.fabrica
	WHERE data_inicio BETWEEN  (current_date||' 00:00:00')::timestamp and (current_date||' 23:59:59')::timestamp
	and tbl_hd_chamado_atendente.admin = $login_admin
	and tbl_hd_chamado_atendente.data_termino is null
	ORDER BY hora_inicio desc limit 1";

	$res = pg_query ($con,$sql);
	if(pg_num_rows($res) >0 ) {
		$hora_inicio  = pg_fetch_result($res,0,hora_inicio);
		$hd_chamado   = pg_fetch_result($res,0,hd_chamado);
		$titulo       = substr(pg_fetch_result($res,0,titulo),0,30);
		$nome_fabrica = pg_fetch_result($res,0,nome);

		$resposta ="<div><a href='adm_chamado_detalhe.php?hd_chamado=$hd_chamado&KeepThis=true&TB_iframe=true&height=590&width=940' class='thickbox' target='_blank'>$hd_chamado</a>&nbsp;-&nbsp;$titulo&nbsp;-&nbsp;$nome_fabrica&nbsp;</div>";

	}else{
		$resposta = "<div style='color:red;font-weight:bold'>Atenção. Você não está trabalhando.</div>";
	}
	echo $resposta;
	exit;
}

if(isset($_GET['comentario'])) {
		$hd_chamado_item = $_GET['comentario'];
		if(!empty($hd_chamado_item)) {
			$sql = " SELECT comentario
				FROM tbl_hd_chamado_item
				WHERE hd_chamado_item  = $hd_chamado_item
			";
			$res = pg_query($con,$sql);

			if(pg_num_rows($res) > 0){
				$comentario = str_replace("
	","<br>",pg_fetch_result($res,0,0));
				echo $comentario;
			}else{
				echo  "Nenhum resultado encontrado";
			}
		}
		exit;
}

function acerta_html($valor) {
	$valor1 = array('<','>');
	$valor2 = array('&lt;','&gt;');
	$valor = nl2br(str_replace($valor1,$valor2,$valor));
	return $valor;
}

if(isset($_GET['hd_analise'])) {
	$hd_chamado = $_GET['hd_analise'];
	$sql= "SELECT   interacao+5 as interacao,
			analise,
			hd_chamado_analise
			FROM tbl_hd_chamado_analise
			WHERE hd_chamado = $hd_chamado
			 AND excluido IS NOT TRUE
			 union
			 SELECT 0 as interacao,
			 plano_teste as analise,
			 hd_chamado as hd_chamado_analise
			 FROM tbl_hd_chamado
			 WHERE hd_chamado = $hd_chamado
			 UNION
			 SELECT  interacao,
			 requisito as analise,
			 hd_chamado as hd_chamado_analise
			 FROM tbl_hd_chamado_requisito
			 WHERE hd_chamado = $hd_chamado
			AND excluido IS NOT TRUE
			";
	$res = @pg_query ($con,$sql);
	$resultado = pg_fetch_all($res);

	if(pg_num_rows($res) > 0){
		foreach($resultado as $key => $valor) {
			$resultado[$key] = array_map('utf8_encode',$valor);
		}

		foreach($resultado as $key => $valor) {
			$resultado[$key] = array_map('acerta_html',$valor);
		}
		echo "{'sucess':'true','chamados':";
		echo json_encode($resultado);
		echo "}";
	}else{
		echo "{'sucess':'false','chamados':'[]'}";
	}

	exit;
}

if(isset($_POST['hd_chamado'])) {
	$hd_chamado = (isset($_GET['hd_chamado']))? $_GET['hd_chamado'] :$_POST['hd_chamado'];
	$mostra_anexo = $_POST['anexo'];
	$sql= "SELECT   tbl_hd_chamado_item.hd_chamado_item,
			tbl_hd_chamado_item.data                                     ,
			case when interno then 't' else 'f' end as interno           ,
			tbl_admin.nome_completo                            AS autor  ,
			tbl_admin.fone
			FROM tbl_hd_chamado_item
			JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado_item.admin
			WHERE hd_chamado = $hd_chamado
			AND tbl_hd_chamado_item.comentario not like 'Término de trabalho automático'
			 AND tbl_hd_chamado_item.comentario not like 'Chamado Transferido'
			 AND tbl_hd_chamado_item.comentario not like 'Categoria Alterada de%'
			 AND tbl_hd_chamado_item.comentario not like 'Início do Trabalho';
			";
	$res = pg_query ($con,$sql);
	$resultados = pg_fetch_all($res);
	$resultado_aux = array();
	foreach($resultados as $chave => $valores) {
		$anexo = "";
		$dir = "documentos/";
		$dh  = opendir($dir);
		$hd_chamado_item = $valores['hd_chamado_item'];
		while (false !== ($filename = readdir($dh))) {
			if (strpos($filename,"$hd_chamado_item") !== false){
				$po = strlen($hd_chamado_item);
				if(substr($filename, 0,$po)==$hd_chamado_item){
					$anexo = '<a href=documentos/'.$filename.' target="blank"><img src="imagem/clips.gif" border="0">Baixar</a>';
				}else{
					$anexo = "";
				}
			}
		}
		if(empty($anexo)) {
			if(!empty($mostra_anexo)) {
				continue;
			}
		}
		$valores['anexo'] = $anexo;
		array_push($resultado_aux,$valores);
	}
	if(count($resultado_aux) > 0){
		echo "{'sucess':'true','chamados':";
		echo json_encode($resultado_aux);
		echo "}";
	}else{
		echo "{'sucess':'false','chamados':'[]'}";
	}
	exit;
}else{
	$data = " tbl_hd_chamado.data ";
	$campos = "	CASE
		WHEN tbl_hd_chamado.status = 'Requisitos' AND tbl_hd_chamado_requisito.hd_chamado_requisito IS NOT NULL THEN 'C/Requisitos'
		WHEN tbl_hd_chamado.status = 'Requisitos' AND tbl_hd_chamado_requisito.hd_chamado_requisito IS NULL THEN 'S/Requisitos'
		WHEN tbl_hd_chamado.status = 'Orçamento' AND tbl_hd_chamado.hora_desenvolvimento IS NOT NULL THEN 'C/Orçamento'
		WHEN tbl_hd_chamado.status = 'Orçamento' AND tbl_hd_chamado.hora_desenvolvimento IS NULL THEN 'S/Orçamento'
		WHEN tbl_hd_chamado.status = 'Análise' AND tbl_hd_chamado.analise IS NOT NULL THEN 'C/Análise'
		WHEN tbl_hd_chamado.status = 'Análise' AND tbl_hd_chamado.analise IS NULL THEN 'S/Análise'
		ELSE tbl_hd_chamado.status
			END AS status ";
		$admin_des = " JOIN tbl_admin atendente ON tbl_hd_chamado.atendente    = atendente.admin ";
		$fila =  "AND atendente = $login_admin ";

		$cond = " $fila
			AND    tbl_hd_chamado.titulo <> 'Atendimento interativo'
			AND status NOT IN ('Resolvido','Cancelado','Novo','Aprovação') ";

	if(isset($_POST['query']) and strlen($_POST['query']) > 3 and !isset($_POST['tipo'])) {
		$pesquisa_hd = trim($_POST['query']);
		if(strlen($pesquisa_hd) > 3) {
			if(preg_match('/\D/',$pesquisa_hd)) {
				if(strlen($pesquisa_hd) > 5) {
					$pesquisa_hd = strtoupper($pesquisa_hd);
					$cond = " and upper(tbl_hd_chamado.titulo) ilike '%$pesquisa_hd%' ";
				}else{
					$cond = " AND 1=2 ";
				}
			}else{
				$cond = " AND tbl_hd_chamado.hd_chamado::text  ~  '$pesquisa_hd' ";
			}
		}
	}else{
		if(isset($_POST['fabrica'])) {
			$cond = " AND    tbl_hd_chamado.titulo <> 'Atendimento interativo'
				AND status NOT IN ('Resolvido','Cancelado','Aprovação')
				AND tbl_hd_chamado.fabrica = ".$_POST['fabrica'];
		}elseif(isset($_POST['admin'])) {
			$admin = $_POST['admin'];
			if(strlen($admin) > 20) {
				$cond_dev = " and tbl_hd_chamado.hd_chamado in (select hd_chamado from tbl_hd_chamado_atendente where  data_termino IS NULL ) ";
			}
			$cond = " AND atendente IN ( $admin )
				AND    tbl_hd_chamado.titulo <> 'Atendimento interativo'
				$cond_dev
				AND status NOT IN ('Resolvido','Cancelado','Aprovação') ";
		}else{
			if(!empty($_POST['tipo'])) {
				switch($_POST['tipo']) {
				case 'Meus':
					if($grupo_admin == 6) {
						$fila =  " AND (tbl_hd_chamado.atendente = $login_admin or tbl_hd_chamado.login_admin = $login_admin)";
					}else{
						$fila =  "AND atendente = $login_admin ";

					}
					$cond = " $fila
						AND    tbl_hd_chamado.titulo <> 'Atendimento interativo'
						AND status NOT IN ('Resolvido','Cancelado','Aprovação') ";
					break;
				case '2':
					$cond =" AND    tbl_hd_chamado.tipo_chamado = 5
						AND    tbl_hd_chamado.titulo <> 'Atendimento interativo'
						AND status NOT IN ('Resolvido','Cancelado','Orçamento','Novo','Aprovação')";
					break;
				case 'aberto':
					$cond =" AND    tbl_hd_chamado.data::date=current_date
						AND    tbl_hd_chamado.titulo <> 'Atendimento interativo'							";
					break;
				case 'resolvido':
					$cond =" AND    tbl_hd_chamado.data_resolvido::date=current_date
						AND  tbl_hd_chamado.status = 'Resolvido'
						AND    tbl_hd_chamado.titulo <> 'Atendimento interativo'";
					break;
				case 'prazo':
					$cond =" AND  previsao_termino notnull 
						AND tbl_hd_chamado.tipo_chamado <> 6
						AND status NOT IN ('Resolvido','Cancelado','Orçamento','Novo','Aprovação')";
					$campos = " CASE 
						WHEN previsao_termino::DATE - CURRENT_DATE  = 2 then 'Falta 2 dias'
						WHEN previsao_termino::DATE - CURRENT_DATE  = 1 then 'Falta 1 dia'
						WHEN previsao_termino::DATE - CURRENT_DATE  = 0 then 'Vence HOJE'
						WHEN previsao_termino::DATE < CURRENT_DATE then 'Venceu PRAZO'
						ELSE to_char(previsao_termino,'DD/MM/YYYY') END AS status
							";
					$admin_des = "LEFT JOIN tbl_admin atendente ON tbl_backlog_item.desenvolvedor    = atendente.admin ";
					$order_by = " ORDER BY status DESC ";
					break;
				case 'prazo_interno':
					$cond =" AND  previsao_termino_interna notnull 
						AND tbl_hd_chamado.tipo_chamado <> 6
						AND status NOT IN ('Resolvido','Cancelado','Orçamento','Novo','Aprovação')";
					$campos = " CASE 
						WHEN previsao_termino_interna::DATE - CURRENT_DATE  = 2 then 'Falta 2 dias'
						WHEN previsao_termino_interna::DATE - CURRENT_DATE  = 1 then 'Falta 1 dia'
						WHEN previsao_termino_interna::DATE - CURRENT_DATE  = 0 then 'Vence HOJE'
						WHEN previsao_termino_interna::DATE < CURRENT_DATE then 'Venceu PRAZO'
						ELSE to_char(previsao_termino_interna, 'DD/MM/YYYY') END AS status
							";
					$admin_des = "LEFT JOIN tbl_admin atendente ON tbl_backlog_item.desenvolvedor    = atendente.admin ";
					$order_by = " ORDER BY status DESC ";
					break;
				case 'ajuda':
					$cond = " and (previsao_termino_interna < now() or (extract(hour from (now() - previsao_termino_interna::timestamp)) <= 1 and now() > previsao_termino_interna)) 
						AND status in ('Execução','Aguard.Execução','Correção') 
						AND ((atendente.grupo_admin in (2,4) and tbl_admin.grupo_admin in(2,4)) or tbl_admin.grupo_admin in (2,4))";
					$admin_des = "LEFT JOIN tbl_admin atendente ON tbl_backlog_item.desenvolvedor    = atendente.admin ";
					break;
				case 'interno':
					$cond = " 
						AND status NOT IN ('Resolvido','Cancelado','Novo','Aprovação' $status_parado) 
						AND tbl_hd_chamado.titulo <>'Atendimento interativo'
						AND tbl_hd_chamado.tipo_chamado <> 6
						AND tbl_fabrica.parametros_adicionais  ~'telecontrol_distrib|interno_telecontrol'
						AND tbl_fabrica.parametros_adicionais  !~'controle_distrib_telecontrol'";
					break;

				case 'todos':
					$status_parado = ($grupo_admin != 11) ? ",'Parado'" : "";
					$cond ="  AND    tbl_hd_chamado.titulo <> 'Atendimento interativo'
						AND tbl_hd_chamado.tipo_chamado <> 6 
						AND status NOT IN ('Resolvido','Cancelado','Novo','Aprovação' $status_parado) ";
					break;
				}
			}elseif(!empty($_POST['tipo_chamado'])){
				$cond ="  AND    tbl_hd_chamado.titulo <> 'Atendimento interativo'
					AND status NOT IN ('Resolvido','Cancelado','Aprovação')
					AND tbl_hd_chamado.tipo_chamado = ". $_POST['tipo_chamado'];
			}elseif(!empty($_POST['equipe'])){
				$cond ="  AND    tbl_hd_chamado.titulo <> 'Atendimento interativo'
					AND status NOT IN ('Resolvido','Cancelado','Aprovação')
					AND (tbl_fabrica.parametros_adicionais::jsonb->>'equipe' = '". str_replace("%20"," ", $_POST['equipe'])."' or tbl_admin.parametros_adicionais::jsonb->>'equipe' = '". str_replace("%20"," ", $_POST['equipe'])."' )";

			}elseif(!empty($_POST['status'])){
				$status_resolvido = explode('!',$_POST['status']);

					list($di, $mi, $yi) = explode("/", $status_resolvido[1]);
					list($df, $mf, $yf) = explode("/", $status_resolvido[2]);

					if(!empty($yf) ) {
						$data1 = "$yi-$mi-$di";
						$data2 = "$yf-$mf-$df";

						if(strtotime($data1) < strtotime($data2)){
							$data_inicial = $data1;
							$data_final   = $data2;
						}else{
							$data_inicial = $data2;
							$data_final   = $data1;
						}
					}

				if(stripos($_POST['status'],"Resolvido") !== false) {
					$data = " tbl_hd_chamado.data_resolvido as data ";
					$cond ="  AND    tbl_hd_chamado.titulo <> 'Atendimento interativo'
						AND tbl_hd_chamado.status IN ('Resolvido')
						AND data_resolvido BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' ";
					if(!empty($status_resolvido[3])){
						$cond .=" AND tbl_hd_chamado.fabrica = $status_resolvido[3] ";
					}
				}elseif(stripos($_POST['status'],"aberto") !== false) {
					$data = " tbl_hd_chamado.data as data ";
					$cond ="  AND    tbl_hd_chamado.titulo <> 'Atendimento interativo'
						AND tbl_hd_chamado.data BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' ";
				}elseif(stripos($_POST['status'],"amento") !== false and !empty($data_inicial)) {
					$data = " tbl_hd_chamado.data as data ";
					$cond ="  AND    tbl_hd_chamado.titulo <> 'Atendimento interativo'
						AND tbl_hd_chamado.data_aprovacao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' ";
				}elseif(stripos($_POST['status'],"amento") !== false) {
					$data = " tbl_hd_chamado.data as data ";
					$cond ="  AND    tbl_hd_chamado.status='Orçamento' ";

				}elseif(stripos($_POST['status'],"requisito") !== false) {
					$data = " tbl_hd_chamado.data as data ";
					$cond ="  AND    tbl_hd_chamado.titulo <> 'Atendimento interativo'
						AND tbl_hd_chamado_requisito.data_requisito_aprova BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59' ";

				}elseif(strpos($_POST['status'],"Financeiro") !== false) {
					$cond = " AND    tbl_hd_chamado.titulo <> 'Atendimento interativo'
						AND tbl_hd_chamado.status not in  ('Resolvido','Cancelado','Novo','Aprovação')
						AND hora_desenvolvimento > 0 and data_aprovacao + interval '10 days' < current_timestamp ";

				}else{
					$cond ="  AND    tbl_hd_chamado.titulo <> 'Atendimento interativo'
						AND fn_retira_especiais(tbl_hd_chamado.status) IN ('".utf8_decode($_POST['status'])."')";
				}
			}

			if(isset($_POST['suporte'])) {
				$suporte_id = $_POST['suporte'];
				$cond = " AND (tbl_hd_chamado.atendente in ($suporte_id) or tbl_hd_chamado.login_admin in ($suporte_id) )
					AND    tbl_hd_chamado.titulo <> 'Atendimento interativo'
					AND status NOT IN ('Resolvido','Cancelado','Novo','Aprovação') ";
			}
		}
	}

	$join_cond = "LEFT JOIN tbl_backlog_item USING (hd_chamado) LEFT JOIN tbl_backlog USING (backlog) 
		LEFT JOIN tbl_admin ba on tbl_backlog_item.desenvolvedor = ba.admin";

	$status_parado = ($grupo_admin != 11) ? ",'Parado'" : "";
	$sql = "SELECT
		count(DISTINCT tbl_hd_chamado.hd_chamado)
		FROM tbl_hd_chamado
		LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
		JOIN tbl_admin   ON tbl_hd_chamado.admin    = tbl_admin.admin
		JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica
		LEFT JOIN tbl_hd_chamado_requisito USING(hd_chamado)
		$join_cond
		$admin_des
		WHERE tbl_hd_chamado.fabrica_responsavel = 10
		$cond";
	if($_POST['tipo'] == 'todos') {
		$sql = "SELECT  count(1) as total_chamado
			FROM tbl_hd_chamado
			JOIN tbl_admin using(admin)
			JOIN tbl_fabrica ON tbl_hd_chamado.fabrica = tbl_fabrica.fabrica
			WHERE tbl_hd_chamado.status not in ('Cancelado','Resolvido','Novo','Aprovação' $status_parado)
			AND   tbl_hd_chamado.titulo <>'Atendimento interativo'
			AND   tbl_hd_chamado.tipo_chamado <> 6
			AND   tbl_hd_chamado.fabrica_responsavel = 10";
	}
	$res = pg_query($con,$sql);
	$total = pg_fetch_result($res,0,0);

	$sql = "SELECT
		tbl_hd_chamado.hd_chamado,
		tbl_hd_chamado.admin    ,
		tbl_admin.nome_completo ,
		tbl_admin.login         ,
		$data                   ,
		tbl_hd_chamado.prioridade_supervisor,
		tbl_hd_chamado.campos_adicionais,  
		tbl_hd_chamado.previsao_termino AS previsao,
		tbl_hd_chamado.previsao_termino::date AS previsao_termino,
		tbl_hd_chamado.previsao_termino::date < current_date  as passou_pc,
		tbl_hd_chamado.previsao_termino_interna AS previsao_termino_interna,
		tbl_hd_chamado.previsao_termino_interna::date < current_date AS passou_pi ,
		tbl_hd_chamado.data_envio_aprovacao,
		tbl_hd_chamado.titulo,
		tbl_hd_chamado.fabrica,
		tbl_hd_chamado.atendente,
		tbl_hd_chamado.prazo_horas                           ,
		tbl_hd_chamado.exigir_resposta,
		tbl_hd_chamado.cobrar,
		tbl_hd_chamado.hora_desenvolvimento,
		tbl_hd_chamado.data_aprovacao,
		tbl_hd_chamado.hora_faturada,
		tbl_backlog_item.prioridade,
		tbl_hd_chamado.tipo_chamado,
		tbl_hd_chamado.analise          ,
		tbl_hd_chamado.valor_desconto          ,
		tbl_fabrica.nome AS fabrica_nome,
		tbl_tipo_chamado.descricao as tipo_chamado_descricao,
		tbl_fabrica.logo,
		tbl_fabrica.parametros_adicionais::jsonb->>'equipe' as equipe,
		http,
		(select data_prazo from tbl_status_chamado where tbl_status_chamado.hd_chamado = tbl_hd_chamado.hd_chamado and fn_retira_especiais(tbl_hd_chamado.status)=  tbl_status_chamado.status and tbl_hd_chamado.atendente = tbl_status_chamado.admin order by status_chamado desc limit 1) as data_prazo,
		case when tbl_backlog_item.desenvolvedor notnull then ba.login else ' '  end as desenvolvedor,
			tbl_admin.login as atendente_nome,
			(select hd_chamado from tbl_hd_chamado_atendente where admin = tbl_hd_chamado.atendente and data_termino IS NULL ORDER BY data_inicio DESC LIMIT 1) as trabalho,
			(select nome_completo from tbl_hd_chamado_atendente join tbl_admin using(admin) where admin = tbl_hd_chamado.atendente and data_termino IS NULL ORDER BY data_inicio DESC LIMIT 1) as trabalho_nome,
			(select to_char(data,'YYYY-MM-DD') FROM tbl_hd_chamado_item it JOIN tbl_admin USING(admin) WHERE it.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_admin.fabrica <> 10 AND interno IS NOT TRUE AND comentario like '%MENTO APROVADO%' ORDER BY data DESC LIMIT 1) AS aprovado,
			(select nome_completo FROM tbl_hd_chamado_item it JOIN tbl_admin USING(admin) WHERE it.hd_chamado = tbl_hd_chamado.hd_chamado AND (tbl_admin.grupo_admin in(6) or tbl_admin.admin = 586) AND interno ORDER BY data DESC LIMIT 1) AS suporte,
			(select nome_completo FROM tbl_hd_chamado_item it JOIN tbl_admin USING(admin) WHERE it.hd_chamado = tbl_hd_chamado.hd_chamado AND tbl_admin.grupo_admin not in (7,4) AND interno and tbl_admin.admin <> $login_admin ORDER BY data DESC LIMIT 1) AS ultimo_admin,
			(select nome_completo FROM tbl_hd_chamado_requisito it JOIN tbl_admin USING(admin) WHERE it.hd_chamado = tbl_hd_chamado.hd_chamado order by hd_chamado_requisito DESC LIMIT 1) AS suporte_requisito,
			(select nome_completo FROM tbl_admin WHERE tbl_admin.admin = tbl_hd_chamado.login_admin ) AS login_admin,
			tbl_backlog_item.horas_analisadas,
			(select count(1) from tbl_hd_chamado_item join tbl_admin using(admin) where tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado and status_item ='Previsao Cadastrada' and grupo_admin in (2,4)) as previsao_pro,
			(select count(1) from tbl_hd_chamado_item join tbl_admin using(admin) where tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado and status_item ='Previsao Cadastrada' and grupo_admin in (1,7)) as previsao_ana,
			(select to_char(data_prazo, 'DD/MM/YY hh24:mm') from tbl_status_chamado where tbl_status_chamado.hd_chamado = tbl_hd_chamado.hd_chamado order by data_input desc limit 1) AS pre_etapa,
			$campos
			FROM tbl_hd_chamado
			LEFT JOIN tbl_tipo_chamado on tbl_tipo_chamado.tipo_chamado = tbl_hd_chamado.tipo_chamado
			JOIN tbl_admin   ON tbl_hd_chamado.atendente    = tbl_admin.admin
			JOIN tbl_fabrica ON tbl_hd_chamado.fabrica  = tbl_fabrica.fabrica and tbl_fabrica.ativo_fabrica
			LEFT JOIN tbl_hd_chamado_questionario USING(hd_chamado)
			LEFT JOIN tbl_hd_chamado_requisito USING(hd_chamado)
			$join_cond
			$admin_des
			WHERE tbl_hd_chamado.fabrica_responsavel = 10
			$cond
			$order_by
			";
	if(strlen($_POST['start']) > 0 and strlen($_POST['limit']) > 0) {
		$sql .= " offset ".$_POST['start'] ." limit ".$_POST['limit'];
	}
	
	$res = pg_query ($con,$sql);

	if (@pg_num_rows($res) > 0) {
		echo "{'total':'$total','chamados':[";
		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
			$hd_chamado               = pg_fetch_result($res,$i,hd_chamado);
			$admin                    = pg_fetch_result($res,$i,admin);
			$login                    = pg_fetch_result($res,$i,login);
			$data                     = pg_fetch_result($res,$i,data);
			$titulo                   = pg_fetch_result($res,$i,titulo);
			$status                   = pg_fetch_result($res,$i,status);
			$atendente                = pg_fetch_result($res,$i,atendente);
			$exigir_resposta          = pg_fetch_result($res,$i,exigir_resposta);
			$nome_completo            = trim(pg_fetch_result($res,$i,nome_completo));
			$fabrica_nome             = trim(pg_fetch_result($res,$i,fabrica_nome));
			$fabrica_id               = trim(pg_fetch_result($res,$i,fabrica));
			$previsao                 = trim(pg_fetch_result($res,$i,previsao));
			$trabalho                 = trim(pg_fetch_result($res,$i,trabalho));
			$trabalho_nome            = trim(pg_fetch_result($res,$i,trabalho_nome));
			$previsao_termino         = trim(pg_fetch_result($res,$i,previsao_termino));
			$previsao_termino_interna = trim(pg_fetch_result($res,$i,previsao_termino_interna));
			$data_prazo               = trim(pg_fetch_result($res,$i,'data_prazo'));
			$passou_pc                = trim(pg_fetch_result($res,$i,'passou_pc'));
			$passou_pi                = trim(pg_fetch_result($res,$i,'passou_pi'));
			$data_envio_aprovacao     = trim(pg_fetch_result($res,$i,'data_envio_aprovacao'));
			$cobrar                   = trim(pg_fetch_result($res,$i,cobrar));
			$analise                  = trim(pg_fetch_result($res,$i,'analise'));
			$atendente_nome           = strtoupper(trim(pg_fetch_result($res,$i,atendente_nome)));
			$desenvolvedor            = strtoupper(trim(pg_fetch_result($res,$i,'desenvolvedor')));
			$hora_desenvolvimento     = trim(pg_fetch_result($res,$i,hora_desenvolvimento));
			$hora_faturada     = trim(pg_fetch_result($res,$i,hora_faturada));
			$horas_analisadas         = pg_fetch_result($res,$i,'horas_analisadas');
			$tipo_chamado_descricao   = trim(pg_fetch_result($res,$i,tipo_chamado_descricao));
			$previsao_pro			= pg_fetch_result($res,$i,'previsao_pro');
			$data_aprovacao  		= pg_fetch_result($res,$i,'data_aprovacao');
			$previsao_ana			= pg_fetch_result($res,$i,'previsao_ana');
			$tipo_chamado          	= trim(pg_fetch_result($res,$i,tipo_chamado));
			$logo          	= trim(pg_fetch_result($res,$i,logo));
			$prazo_horas           	= pg_fetch_result($res,$i,prazo_horas);
			$http                  	= pg_fetch_result($res,$i,http);
			$total_prazo_horas = $total_prazo_horas +$prazo_horas;
			$pre_etapa             = pg_fetch_result($res, $i, 'pre_etapa');
			$prioridade            = pg_fetch_result($res,$i,prioridade);
			$prioridade_supervisor = pg_fetch_result($res,$i,'prioridade_supervisor');
			$aprovado              = pg_fetch_result($res,$i,'aprovado');
			$suporte               = pg_fetch_result($res,$i,'suporte');
			$admin_suporte         = pg_fetch_result($res,$i,'login_admin');
			$ultimo_admin          = pg_fetch_result($res,$i,'ultimo_admin');
			$equipe                = ucwords(pg_fetch_result($res,$i,'equipe'));
			$valor_desconto        = pg_fetch_result($res,$i,'valor_desconto');
			$suporte_requisito     = pg_fetch_result($res,$i,'suporte_requisito');

			$campos_adicionais 	   = pg_fetch_result($res, $i, 'campos_adicionais');
			$campos_adicionais 	   = json_decode($campos_adicionais, true);
			$impacto_financeiro    = $campos_adicionais['impacto_financeiro'];

			if($impacto_financeiro == 1){
				$impacto_financeiro = 'Sim';
			}

			if($impacto_financeiro == 2){
				$impacto_financeiro = 'Não';
			}

			if(isset($campos_adicionais['prioridade'])){
				$clasPrioriddade = $campos_adicionais['prioridade'];
			}else{
				$clasPrioriddade = "";
			}

			$suporte = (!empty($suporte_requisito)) ? $suporte_requisito : $suporte;
			$suporte = (!empty($suporte)) ? $suporte : $ultimo_admin;
			$suporte = (!empty($admin_suporte)) ? $admin_suporte : $suporte;

			if(empty($previsao_termino_interna) and !empty($data_prazo)) {
				$previsao_termino_interna = $data_prazo;
				if(date('Y-m-d') > date('Y-m-d', strtotime($data_prazo))){
					$passou_pi = 't';
				}   
			}


			$suporte_id = "435";
			if(!empty($suporte)) {
				$sqls = "SELECT admin FROM tbl_admin where fabrica = $login_fabrica and ativo and nome_completo='$suporte'";
				$ress = pg_query($con,$sqls);
				if(pg_num_rows($ress) > 0) {
					$suporte_id = pg_fetch_result($ress,0,admin);
				}
			}else{
				$suporte = "SUPORTE";
			}
			unset($valor_total);
			if($hora_faturada > 0 and !empty($data_aprovacao)) {
				$sqlx = "SELECT valor_hora_franqueada from tbl_hd_franquia where fabrica = $fabrica_id and periodo_fim isnull limit 1 "; 
				$resh = pg_query($con, $sqlx);
				$valor = pg_fetch_result($resh, 0 , 'valor_hora_franqueada')  ; 
				$valor_desconto = ($valor_desconto > 0) ? $valor_desconto : 0 ; 
				$valor_total = ($hora_faturada * $valor) - $valor_desconto; 
			}elseif($hora_desenvolvimento > 0  and !empty($data_envio_aprovacao)) {
				$sqlx = "SELECT valor_hora_franqueada from tbl_hd_franquia where fabrica = $fabrica_id and periodo_fim isnull limit 1 "; 
				$resh = pg_query($con, $sqlx);
				$valor = pg_fetch_result($resh, 0 , 'valor_hora_franqueada')  ; 
				$valor_desconto = ($valor_desconto > 0) ? $valor_desconto : 0 ; 
				$valor_total = ($hora_desenvolvimento * $valor) - $valor_desconto; 
			}

			if (strlen($prazo_horas) > 0) $prazo_horas = $prazo_horas."h";

			$titulo = str_replace('\'','',str_replace('\\','',$titulo));
			$http = str_replace('\'','',str_replace('\\','',$http));
			$titulo = htmlspecialchars($titulo);
			if(!empty($prioridade_supervisor)) {
				$titulo = $prioridade_supervisor. " - " .$titulo;
			}
			if($tipo_chamado == 5 and empty($prioridade)) {
				$prioridade ='1';
			}
			if(strlen($titulo) == 0) {
				$titulo = ".....";
			}
			$http   = htmlspecialchars($http);
			if(!empty($previsao_pro)) {
				$http = "Adiado $previsao_pro vez por programador e adiado $previsao_ana vez por analista";
			}
			$atendente_nome = substr($atendente_nome,0,10);
			$suporte = strtoupper($suporte);
			$nome_completo2 = explode (' ',$suporte);
			$trabalho_nome = strtoupper($trabalho_nome);
			$nome_trabalho = explode (' ',$trabalho_nome);

			$nome_completo = (empty($nome_completo2[1])) ? $nome_completo2[0] : substr($nome_completo2[0],0,7). " ".substr($nome_completo2[1],0,4).".";
			$nome_inicial = (empty($nome_trabalho[1])) ? substr($nome_trabalho[0],0,2) : substr($nome_trabalho[0],0,1). "". substr($nome_trabalho[1],0,1);
			$nome_inicial = (empty($nome_trabalho[2])) ? $nome_inicial : substr($nome_trabalho[0],0,1). "". substr($nome_trabalho[2],0,1);

			echo ($i == 0) ? "":",";
			echo "{'hd_chamado':'$hd_chamado','titulo':'$titulo','suporte':'$nome_completo','fabrica':'$fabrica_nome','data':'$data','status':'$status','tipo':'$tipo_chamado_descricao','prazo':'$prazo_horas','tipo_chamado':'$tipo_chamado','exigir_resposta':'$exigir_resposta','previsao':'$previsao_termino','http':'$http','atendente_nome':'$atendente_nome','prioridade':'$prioridade','trabalho':'$trabalho','atendente':'$atendente','login_admin':'$login_admin','horas_analisadas':'$horas_analisadas','fabrica_id':'$fabrica_id','logo':'$logo', 'desenvolvedor':'$desenvolvedor','horas_cobradas':'$hora_desenvolvimento','suporte_id':'$suporte_id','nome_inicial':'$nome_inicial', 'previsao_interna':'$previsao_termino_interna', 'passou_pc':'$passou_pc', 'passou_pi':'$passou_pi' , 'valor_desconto':'$valor_desconto', 'valor_total': '$valor_total', 'equipe':'$equipe', 'impacto_financeiro':'$impacto_financeiro', 'clasPrioriddade': '$clasPrioriddade', 'pre_etapa': '$pre_etapa' }";
		}
		echo "]}";
	}else{
		echo "{'sucess':'false','chamados':[]}";
	}
	exit;
}

	exit;
?> 

