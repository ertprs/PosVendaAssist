<?php

if ($areaAdmin === false) {
	$funcoes_fabrica = [
		"enviaEmailAdminUnidadeOs"
	];
}

function enviaEmailAdminUnidadeOs() {
	global $con, $login_fabrica, $os, $interacao_mensagem;

	$sqlUnidadeOs = "SELECT tbl_unidade_negocio.codigo || ' - ' || tbl_unidade_negocio.nome as descricao_unidade,
							tbl_admin.admin,tbl_admin.email
					 FROM tbl_os_campo_extra
					 JOIN tbl_unidade_negocio ON tbl_os_campo_extra.campos_adicionais::jsonb->>'unidadeNegocio' = tbl_unidade_negocio.codigo
					 JOIN tbl_admin ON tbl_admin.parametros_adicionais::jsonb->'unidades_interacao_os' ? tbl_unidade_negocio.unidade_negocio::text
					 AND  tbl_admin.fabrica = {$login_fabrica}
					 WHERE tbl_os_campo_extra.os = {$os}
					 AND tbl_admin.parametros_adicionais IS NOT NULL AND tbl_admin.parametros_adicionais NOT IN ('{}','f','t','')
					 AND tbl_os_campo_extra.campos_adicionais IS NOT NULL AND tbl_os_campo_extra.campos_adicionais NOT IN ('{}','f','t','')
					 AND tbl_os_campo_extra.fabrica = {$login_fabrica}";

	$resUnidadeOs = pg_query($con, $sqlUnidadeOs);

	if (pg_num_rows($resUnidadeOs) > 0) {
		while ($adm = pg_fetch_array($resUnidadeOs)) {

			$email_admin      = $adm['email'];
			$unidade_negocio  = $adm['descricao_unidade'];

			$assunto = "Interação na OS {$os} - Unidade de Negócio {$unidade_negocio}";

			$dados_os = getOsData($os, true);

			$produto       = $dados_os['referencia_produto']." - ".$dados_os['nome_produto'];
			$consumidor    = $dados_os['consumidor_nome'];
			$data_abertura = $dados_os['data_abertura'];

			$mensagem = "
				<p>
					<strong>Ordem de Serviço:</strong> {$os} <br />
					<strong>Data de Abertura:</strong> {$data_abertura} <br />
					<strong>Produto:</strong> {$produto} <br />
					<strong>Consumidor:</strong> {$consumidor} <br />
					<strong>Mensagem:</strong> {$interacao_mensagem}
				</p>
			";

			$headers  = "MIME-Version: 1.0 \r\n";
    		$headers .= "content-type: text/html; charset=iso-8859-1 \r\n";
    		$headers .= "From: Telecontrol <noreply@telecontrol.com.br> \r\n";

			enviarEmail($email_admin, $assunto, $mensagem, $headers);
		}
	}
}