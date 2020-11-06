<?php

if (verifica_tipo_posto("posto_interno","TRUE",$login_posto)) {

	$regras["os|os_posto"]["obrigatorio"] = true;

}

if (getValue("os[consumidor_revenda]") == 'R') {
    $regras["os|nota_fiscal"]["obrigatorio"] = false;
    $regras["os|data_compra"]["obrigatorio"] = false;
    $anexos_obrigatorios = [];
}

$funcoes_comunicado = [
	"envia_email_consumidor"
];

$pre_funcoes_fabrica = [
    "verifica_posto_troca",
];

$funcoes_fabrica = [
	"espelhar_anexos_callcenter"
];

$auditorias = [
	"auditoria_os_reincidente",
	"auditoria_troca_obrigatoria"
];

$valida_anexo_boxuploader = "valida_anexo_boxuploader";

$antes_valida_campos = "antes_valida_campos_hikari";
function antes_valida_campos_hikari() {
	global $con, $login_fabrica, $login_posto, $areaAdmin, $campos, $msg_erro;

	if (!$areaAdmin && !empty($campos['produto']['id'])) {

		$sql = "SELECT parametros_adicionais
				FROM tbl_posto_fabrica
				WHERE posto = {$login_posto}
				AND fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		$parametros_adicionais = json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'));

		if ($parametros_adicionais->posto_troca == "t") {

			$produto_id = $campos["produto"]["id"];

			$sql = "SELECT produto
                    FROM tbl_produto
                    WHERE fabrica_i = {$login_fabrica}
                    AND produto = {$produto_id}
                    AND troca_obrigatoria IS TRUE";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) == 0) {

				$msg_erro["msg"][] = traduz("Produto inválido");

            }

		}

	}

}

function espelhar_anexos_callcenter() {
	global $con, $login_fabrica, $campos, $msg_erro, $os;

	if (!empty($campos["os"]["hd_chamado"])) {

		$sqlInsertTdocs = "INSERT INTO tbl_tdocs (
                            tdocs_id,
                            fabrica,
                            contexto,
                            situacao,
                            obs,
                            referencia,
                            referencia_id
	                       ) SELECT tdocs_id,
                                    fabrica,
                                    'os' as contexto,
                                    situacao,
                                    obs,
                                    'os' as referencia,
                                    '{$os}' as referencia_id
                            FROM tbl_tdocs
                            WHERE tbl_tdocs.referencia  = 'callcenter'
                            AND tbl_tdocs.referencia_id = ".$campos["os"]["hd_chamado"]."
                            AND tbl_tdocs.fabrica = {$login_fabrica}";
         $resInsertTdocs = pg_query($con, $sqlInsertTdocs);

         if (pg_last_error()) {

         	$msg_erro["msg"][] = traduz("Erro ao gravar ordem de serviço #10");

         }

	}

}

/*
function envia_email_consumidor() {
	global $con, $login_fabrica, $campos, $os, $externalId, $externalId, $_REQUEST;
	
	include __DIR__."/../../../class/communicator.class.php";

	$mailTc = new TcComm('smtp@posvenda');

	$consumidor_email = $campos["consumidor"]["email"];

    if (!empty($consumidor_email) && empty($_REQUEST["os_id"])) {

        if (filter_var($consumidor_email, FILTER_VALIDATE_EMAIL)) {

        	$produto_descricao = $campos["produto"]["referencia"]." - ".$campos["produto"]["descricao"];
        	$nota_fiscal       = $campos["os"]["nota_fiscal"];

        	$assunto    = 'Hikari - Abertura de Ordem de Serviço';

            $conteudo = "Foi aberta a Ordem de Serviço {$os}.<br />
                         Produto: {$produto_descricao}.<br />
                         Nota fiscal: {$nota_fiscal}.<br />
                         Para mais informações entre em contato com a assistência.";

	        $mailTc->sendMail(
		        $consumidor_email,
		        $assunto,
		        $conteudo,
		        'noreply@telecontrol.com.br'
		    );

        }
    }
}
*/