<?php
$regras = array(
	"responsavel_solicitacao" => [
		"obrigatorio" => true
	],
	"providencia" => array(
		"obrigatorio" => true
	),
	"sub_item" => array(
		"obrigatorio" => true
	),
	"origem" => array(
		"obrigatorio" => true
	)
);

$arrOrigem = [
    "Telefone", "E-mail", "Judicial"
];

$arrSubItem = [
    "01" => "LCP's, vista explodida, manuais de serviço, manuais de usuário, IOM",
    "02" => "Solicitação de códigos",
    "03" => "Boletins, especificações, códigos de erro",
    "04" => "Informações da qualidade",
    "05" => "Tribunal da Justiça, PROCON, CDC30"
];

$arrProvidencia = [
    "LT"   => "Literatura técnica",
    "COD"  => "Código de componentes",
    "ITEC" => "Informação técnica",
    "OUT"  => "Outros"
];  

$enviar_email_helpdesk       = 'enviarEmailHelpdeskMidea';

function enviarEmailHelpdeskMidea($tipo_solicitacao,$os,$hd_chamado){
	global $con,$login_fabrica,$login_posto;
	$sql = "SELECT email, nome_completo
			  FROM tbl_admin
			 WHERE tbl_admin.fabrica = {$login_fabrica}
			 AND JSON_FIELD('suporte_tecnico', parametros_adicionais) = 't'
			";
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
		$mailer->Subject = "Abertura de chamado help-desk $hd_chamado";
		$msg = "Prezado(s),";
		$msg .= "<br /><br />Favor, acessar o sistema para visualizar a solicitação através do help-desk $hd_chamado";
		$msg .= "<br />Tipo Solicitação: $solicitacao_desc";
		$mailer->Body = $msg;

		$mailer->Send();
	}
}

function gravarAnexosBoxUploader() {

	global $con, $login_fabrica, $hd_chamado_item, $hd_chamado;
	
	$anexo_chave = $_POST["anexo_chave"];
	$anexo_chave_hidden = $_POST["anexo_chave_hidden"];

	$hd_referencia_id = (empty($hd_chamado_item)) ? $hd_chamado : $hd_chamado_item;

	if ($anexo_chave != $hd_referencia_id || $anexo_chave_hidden != $hd_referencia_id) {

		$getTdocs = "SELECT * FROM tbl_tdocs 
					 WHERE fabrica  = $login_fabrica
					 AND contexto   = 'help desk'
					 AND hash_temp = '{$anexo_chave}'
					 AND situacao   = 'ativo'";

		$resTdocs = pg_query($con, $getTdocs);

		$anexos = pg_fetch_all($resTdocs);

		if (!empty($anexos)) {

			$update = "UPDATE tbl_tdocs 
					   SET
						  referencia_id = $hd_referencia_id,
						  hash_temp  = NULL
					   WHERE fabrica = $login_fabrica
					   AND contexto  = 'help desk'
					   AND situacao  = 'ativo'
					   AND hash_temp IN ('$anexo_chave','$anexo_chave_hidden')";

			$uptodate = pg_query($con, $update);

			if (pg_num_rows($uptodate) == 0) {
				$msg_erro["msg"][] = traduz("Erro ao gravar anexos");
			}
		}
	}

}

function retorna_anexos_inseridos() {
	global $con, $login_fabrica, $hd_chamado_item, $fabricaFileUploadOS;

	$anexo_chave 	  = $_POST["anexo_chave"];
	$anexos_inseridos = [];

    if (!empty($hd_chamado_item)){
        $cond_tdocs = "AND tbl_tdocs.referencia_id = {$hd_chamado_item}";
    }else{
        $cond_tdocs = "AND tbl_tdocs.hash_temp = '{$anexo_chave}'";
    }

	$sql = "SELECT obs
			FROM   tbl_tdocs
			WHERE  tbl_tdocs.fabrica = {$login_fabrica}
			AND    tbl_tdocs.situacao = 'ativo'
			{$cond_tdocs}";

	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {

		while ($dados = pg_fetch_object($res)) {

			$json_obs = json_decode($dados->obs, true);

			$anexos_inseridos[] = $json_obs[0]['typeId'];

		}

	}

	return $anexos_inseridos;
}