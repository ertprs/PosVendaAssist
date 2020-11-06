<?php
$attCfg = array(
	'labels' => array(
		0 => 'Anexar',
		1 => 'Nota Fiscal',
		2 => 'Etiqueta',
		3 => 'Produto (1)',
		4 => 'Produto (2)',
		5 => 'Produto (3)',
	)
);
/*
	,
	'obrigatorio' => array(0,0,1,1,0,0)
*/
$fabrica_qtde_anexos = count($attCfg['labels']);
$GLOBALS['attCfg'] = $attCfg;

$verifica_atendimento_aberto = 'verificaHelpdeskAbertoEsmaltec';
$enviar_email_helpdesk       = 'enviarEmailHelpdeskEsmaltec';

$regras['anexo']['function'] = array('validaAnexosEsmaltec');

// Valida anexos
function validaAnexosEsmaltec() {
	global $campos, $attCfg, $login_fabrica, $con;

	$sql_bloqueados = "SELECT JSON_FIELD('anexo_obrigatorio', campo_obrigatorio), JSON_FIELD('anexos', informacoes_adicionais) from tbl_tipo_solicitacao where tipo_solicitacao = ".$campos["tipo_solicitacao"]." AND fabrica = $login_fabrica" ;
	$res_bloqueados = pg_query($con, $sql_bloqueados);

	if(pg_num_rows($res_bloqueados) > 0){
		$campo_obrigatorio 		= json_decode(pg_fetch_result($res_bloqueados, 0, 0), true);
		$informacoes_adicionais = json_decode(pg_fetch_result($res_bloqueados, 0, 1), true);
	}

	if (empty($campos['anexo']) && !empty($_POST['anexo_chave'])) {
		$sql_anexos = "SELECT tdocs FROM tbl_tdocs WHERE fabrica = $login_fabrica AND hash_temp = '".$_POST['anexo_chave']."'";
		$res_anexos = pg_query($con, $sql_anexos);
		if (pg_num_rows($res_anexos) > 0) {
			$anexos_hash = pg_fetch_all($res_anexos);
		}
	}

	for ($i=0; $i < count($informacoes_adicionais['anexos']); $i++) {

		if ($campo_obrigatorio['anexo_obrigatorio'][$i] == 1 && ((empty($campos['anexo'][$i]) && empty($anexos_hash[$i]['tdocs'])) || ($campos['anexo'][$i] == 'null' && empty($anexos_hash[$i]['tdocs'])))) {
			$msg .= 'Anexo <strong>' . $informacoes_adicionais['anexos'][$i] . '</strong> é obrigatório.<br />';
		}

	}

	
	if ($msg)
		throw new Exception ($msg);
}

function verificaHelpdeskAbertoEsmaltec($os){

	global $con,$login_fabrica;

	$sql = "SELECT hd_chamado
		FROM tbl_hd_chamado
		JOIN tbl_hd_chamado_extra USING(hd_chamado)
		WHERE tbl_hd_chamado.fabrica_responsavel = {$login_fabrica}
		AND tbl_hd_chamado.titulo = 'Help-Desk Posto'
		AND tbl_hd_chamado.status NOT IN('Finalizado','Cancelado')
		AND tbl_hd_chamado_extra.os = {$os}";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) > 0){
		$hd_aberto = pg_fetch_result($res,0,'hd_chamado');

		throw new Exception("J&aacute; existe o Help-Desk ".$hd_aberto." aberto para a Ordem de Servi&ccedil;o informada");
	}
}

function enviarEmailHelpdeskEsmaltec($tipo_solicitacao,$os,$hd_chamado){
	global $con,$login_fabrica,$login_posto;
	$sql = "SELECT email, nome_completo
			  FROM tbl_admin
			  JOIN tbl_admin_atendente_estado USING(admin)
			 WHERE tbl_admin.fabrica = $login_fabrica
			   AND tbl_admin.email IS NOT NULL
			   AND tbl_admin_atendente_estado.tipo_solicitacao = $tipo_solicitacao
			   AND (
					tbl_admin.nao_disponivel IS NULL
					OR LENGTH(tbl_admin.nao_disponivel) = 0
			)";
	// exit(nl2br($sql));
	$res = pg_query($con,$sql);

	$sqlT = "
		SELECT  descricao
		FROM    tbl_tipo_solicitacao
		WHERE   fabrica             = $login_fabrica
		AND     tipo_solicitacao    = $tipo_solicitacao
		";
	$resT = pg_query($con,$sqlT);
	$solicitacao_desc = pg_fetch_result($resT,0,descricao);

	if(!empty($login_posto)){
		$sqlUfPosto = "
			SELECT  nome, cnpj
			FROM    tbl_posto
			WHERE   posto = $login_posto
			";
		$resUfPosto = pg_query($con,$sqlUfPosto);

		$postoNome  = pg_fetch_result($resUfPosto,0,nome);
		$postoCnpj  = pg_fetch_result($resUfPosto,0,cnpj);

		if(pg_num_rows($res) > 0){
			$emails = pg_fetch_all_columns($res,0);
			$nomes  = pg_fetch_all_columns($res,1);

			$mailer = new PHPMailer;

			$mailer->isSMTP();
			$mailer->IsHTML();

			$mailer->From = "no-reply@telecontrol.com.br";
			$mailer->FromName = "Posvenda Telecontrol";

			foreach($emails as $indice=>$email){
				$mailer->addAddress($email,$nomes[$indice]);
			}
// 			$mailer->addBCC("william.brandino@telecontrol.com.br","William Ap. Brandino");
// 			$mailer->addBCC("joao.junior@telecontrol.com.br","João Junior");
			$mailer->Subject = "Abertura de chamado help-desk (Posto) $hd_chamado";
			$msg = "Prezado(s),";
			$msg .= "<br /><br />Favor, acessar o sistema para visualizar a solicitação do posto através de help-desk $hd_chamado";
			$msg .= "<br />Tipo Solicitação: $solicitacao_desc";
			$msg .= "<br />OS: $os";
			$msg .= "<br />Posto: $postoNome";
			$msg .= "<br />CNPJ: $postoCnpj";
			$mailer->Body = $msg;

			$mailer->Send();
		}
	}
}

