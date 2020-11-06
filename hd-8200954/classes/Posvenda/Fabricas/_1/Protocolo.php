<?php
/**
 *
 * @author  Kaique
 * @version 2020.06.09
 *
*/
namespace Posvenda\Fabricas\_1;

use Posvenda\Model\GenericModel;

class Protocolo extends GenericModel
{

	private $_pdo;
	private $_fabrica;
	private $_con;
	private $_classTdocs;
	private $_classPdf;
	private $_tDocsAss;
	private $_mailer;

	public function __construct($fabrica, $con, $mailer = null)
	{

		$this->_con = $con;
		$this->_fabrica = $fabrica;
		$this->_mailer = $mailer;

	}

	public function enviaEmailTransferencia($protocolo, $tipo, $tcComm) {

		switch ($tipo) {
			case 'analista_contas_receber':
				
				$titulo = "Protocolo {$protocolo} Transferido para Analista de Contas a Receber";
				$corpo = "Prezado,<br /><br />

				O Protocolo {$protocolo} foi transferido pelo analista de pós-vendas para sua avaliação.
				";

				break;
			case 'analista_posvenda':
				
				$titulo = "Protocolo {$protocolo} Transferido para Analista de Pós-Venda";
				$corpo = "Prezado,<br /><br />

				O Protocolo {$protocolo} foi transferido pelo gerente de pós-vendas para sua avaliação.
				";

				break;
			case 'gerente_contas_receber':
				
				$titulo = "Protocolo {$protocolo} Transferido para Gerente de Contas a Receber";
				$corpo = "Prezado,<br /><br />

				O Protocolo {$protocolo} foi transferido pelo analista de contas a receber para sua avaliação.
				";

				break;
			case 'analista_contas_receber_reprova':
				
				$titulo = "Protocolo {$protocolo} Reprovado pelo Gerente de Contas a Receber";
				$corpo = "Prezado,<br /><br />

				O Protocolo {$protocolo} foi reprovado pelo gerente de contas a receber, e voltou para nova avaliação.
				";

				$tipo = "analista_contas_receber";

				break;
			case 'analista_contas_pagar':
				
				$titulo = "Protocolo {$protocolo} Transferido para Analista de Contas a Pagar";
				$corpo = "Prezado,<br /><br />

				O Protocolo {$protocolo} foi transferido pelo analista de pós-vendas para sua avaliação.
				";

				break;
			case 'gerente_contas_pagar':
				
				$titulo = "Protocolo {$protocolo} Transferido para Gerente de Contas a Pagar";
				$corpo = "Prezado,<br /><br />

				O Protocolo {$protocolo} foi transferido pelo analista de contas a pagar para sua avaliação.
				";

				break;
			default:
				# code...
				break;
		}

	    $tcComm->setEmailFrom('"Sistema Telecontrol" <helpdesk@telecontrol.com.br>');

	    $sqlDest = "
	        SELECT  email
	        FROM    tbl_admin
	        WHERE   fabrica = {$this->_fabrica}
	        AND     JSON_FIELD('permissao_contas',parametros_adicionais) ILIKE '%{$tipo}%'
	    ";
	    $resDest = pg_query($this->_con,$sqlDest);

	    //emails em cópia
	    $emails = [
			"gustavo.dominici@sbdinc.com",
			"marilma.amancio@sbdinc.com",
			"pagamento.garantia@sbdinc.com"
	    ];
	    while($email = pg_fetch_object($resDest)) {
	        $emails[] = $email->email;
	    }

	    $lista = implode(",",$emails);
	    $tcComm->addEmailDest($lista);
	    $tcComm->setEmailSubject(utf8_encode($titulo));
	    $tcComm->setEmailBody(utf8_encode($corpo));
	    if ($tcComm->sendMail()) {
	        return true;
	    }

	    return false;

	}

	public function insereStatusProtocolo($protocolo, $codigoStatus) {
		global $login_admin;

		$dadosStatus = $this->getStatusByCodigo($codigoStatus);

		if ($dadosStatus) {

			$sql = "
				INSERT INTO tbl_extrato_agrupado_status (
					status_extrato_agrupado,
					extrato_agrupado_codigo,
					admin
				) VALUES (
					{$dadosStatus->status_extrato_agrupado},
					'{$protocolo}',
					{$login_admin}
				)
			";
			$res = pg_query($this->_con, $sql);

			if (!pg_last_error()) {

				return true;

			}

		}

		return false;

	}

	public function insereStatusExtrato($extrato, $obs, $justificativa = null) {
		global $login_admin;

		$json = "{}";
		if (!empty($justificativa)) {

			$json = json_encode([
				"justificativa" => $justificativa
			]);

		}

		$json = Utf8_ansi(str_replace("\\", "", $json));

		$sql = "INSERT INTO tbl_extrato_status (extrato, obs, fabrica, admin_conferiu, parametros_adicionais, data) 
				VALUES ({$extrato},'{$obs}', $this->_fabrica, $login_admin, '{$json}', current_timestamp)";
		$res = pg_query($this->_con, $sql);

		if (!pg_last_error()) {
			return true;
		}

		return false;

	}

	public function verificaProtocoloAuditado($protocolo) {

		$sql = "SELECT extrato
				FROM tbl_extrato_agrupado
				JOIN tbl_extrato USING(extrato)
				WHERE codigo = '{$protocolo}'
				AND (
					SELECT extrato
					FROM tbl_extrato_status
					WHERE obs IN ('Retido Contas Receber', 'Aprovado Contas Receber')
					AND tbl_extrato_status.extrato = tbl_extrato_agrupado.extrato
					LIMIT 1
				) IS NULL
				AND tbl_extrato.fabrica = {$this->_fabrica}";
		$res = pg_query($this->_con, $sql);

		if (pg_num_rows($res) == 0) {

			return true;

		}

		return false;

	}

	public function retornaProtocoloParaAnalise($protocolo) {

		$sql = "UPDATE tbl_extrato_status 
				SET obs = obs || ' (Reprovado Gerente)'
				WHERE extrato IN (
					SELECT extrato
					FROM tbl_extrato_agrupado
					JOIN tbl_extrato USING(extrato)
					WHERE codigo = '{$protocolo}'
					AND tbl_extrato.fabrica = {$this->_fabrica}
				)
				AND tbl_extrato_status.obs IN ('Retido Contas Receber', 'Aprovado Contas Receber')
				AND tbl_extrato_status.fabrica = {$this->_fabrica}";
		$res = pg_query($this->_con, $sql);

		if (!pg_last_error()) {
			return true;
		}

		return false;

	}

	public function getStatusByCodigo($codigoStatus) {

		$sql = "SELECT *
				FROM tbl_status_extrato_agrupado 
				WHERE codigo = '{$codigoStatus}'
				AND fabrica = {$this->_fabrica}";
		$res = pg_query($this->_con, $sql);

		if (pg_num_rows($res) == 0) return false;

		return pg_fetch_object($res);

	}

	public function removeExtratoProtocolo($extrato) {

		$sql = "DELETE FROM tbl_extrato_agrupado WHERE extrato = {$extrato}";
		$res = pg_query($this->_con, $sql);

		$sql = "DELETE FROM tbl_extrato_status 
				WHERE extrato = {$extrato}
				AND obs ILIKE '%Contas Receber%'";
		$res = pg_query($this->_con, $sql);

		if (!pg_last_error()) {

			return true;

		}

		return false;

	}

	public function getPermissoesLogin($admin) {

		$sql = "
			SELECT parametros_adicionais
			FROM tbl_admin
			WHERE admin = {$admin}
		";
		$res = pg_query($this->_con, $sql);

		$parametros_adicionais = json_decode(pg_fetch_result($res, 0, "parametros_adicionais"), true);

		return $parametros_adicionais["permissao_contas"];

	}

	public function listaProtocolos($conds = []) {

		$cond = count($conds) > 0 ? "AND ".implode(" AND ", $conds) : "";

		$sqlProtocolo = "
	        SELECT  DISTINCT
	                tbl_extrato_agrupado.codigo                             AS protocolo,
	                TO_CHAR(tbl_extrato_agrupado.data_agrupa,'DD/MM/YYYY')  AS data_protocolo,
	                status_protocolo.descricao                              AS status
	        FROM    tbl_extrato_agrupado
	        JOIN    tbl_extrato USING(extrato)
	        LEFT JOIN LATERAL (

	            SELECT tbl_status_extrato_agrupado.codigo,
	                   tbl_status_extrato_agrupado.descricao
	            FROM tbl_status_extrato_agrupado
	            JOIN tbl_extrato_agrupado_status USING(status_extrato_agrupado)
	            WHERE tbl_extrato_agrupado_status.extrato_agrupado_codigo = tbl_extrato_agrupado.codigo
	            ORDER BY tbl_extrato_agrupado_status.data_input DESC
	            LIMIT 1

	        ) status_protocolo ON true
	        WHERE   tbl_extrato.fabrica = {$this->_fabrica}
	        AND status_protocolo.descricao IS NOT NULL
	        {$cond}
	        ORDER BY tbl_extrato_agrupado.codigo
	    ";
	    $resProtocolo = pg_query($this->_con, $sqlProtocolo);

	    $retorno = [];
	    if (pg_num_rows($resProtocolo) > 0) {

	    	$retorno = pg_fetch_all($resProtocolo);

	    }

	    return $retorno;

	}

	public function avisoEmailProtocolo($login_fabrica,$protocolo)
	{
	    
		$mailer = $this->_mailer;

		if (is_null($mailer)) return true;

	    $mailer->setEmailFrom('"Sistema Telecontrol" <helpdesk@telecontrol.com.br>');
	    $sqlDest = "
	        SELECT  email
	        FROM    tbl_admin
	        WHERE   fabrica = $login_fabrica
	        AND     JSON_FIELD('pagamento_garantia',parametros_adicionais)::BOOL IS TRUE
	    ";
	    $resDest = pg_query($this->_con,$sqlDest);

	    while($email = pg_fetch_object($resDest)) {
	        //$emails[] = $email->email;
	        $emails[] = "kaique.magalhaes@telecontrol.com.br";
	    }

	    $lista = implode(",",$emails);
	    $mailer->addEmailDest($lista);
	    $mailer->setEmailSubject("EXTRATOS: Protocolos Aprovados");
	    $corpo = "
	        Prezado(a),

	        Protocolo nº $protocolo foi aprovado.

	    ";
	    $mailer->setEmailBody($corpo);
	    if ($mailer->sendMail()) {
	        return true;
	    }

	    return false;

	}

	public function gerarProtocoloArquivo($login_fabrica,$login_admin,$login_nome_completo,$protocolo,$tipo,$just = null)
	{

	    $tDocs  	= $this->_classTdocs;
	    $tDocsAss	= $this->_tDocsAss;
	    $pdf    	= $this->_classPdf;

	    $total_extrato = 0;

	    $topo = "<table autosize='1' cellspacing='0' cellpadding='0' style='width:100%;'>
	                <tr>
	                    <th rowspan='2' style='width:6.5cm;'>
	                        <img src='../logos/logo_black_2017.png' style='max-height:55px;max-width:240px;' border='0' />
	                    </th>
	                    <th nowrap='nowrap' width='65%'>RELATÓRIO DE APROVAÇÃO DA DIRETORIA FINANCEIRA</th>
	                    <th>Página {PAGENO}/{nb}</th>
	                </tr>
	                <tr>
	                    <th nowrap='nowrap'>CONTROLE DE GARANTIAS Nº $protocolo ".date('d/m/Y')."</th>
	                </tr>
	            </table>
	            <br /><br />
	            ";

	    $cabecalho = "
	        <br /><br />
	        <table autosize='1' cellspacing='0' cellpadding='0' style='border:1px solid #000;width:100%;'>
	            <thead>
	                <tr>
	                    <th style='border:1px solid #000;'>CÓDIGO</th>
	                    <th style='border:1px solid #000;'>POSTO</th>
	                    <th style='border:1px solid #000;'>DATA GERAÇÃO</th>
	                    <th style='border:1px solid #000;'>EXTRATO</th>
	                    <th style='border:1px solid #000;'>NF AUTORIZADO</th>
	                    <th style='border:1px solid #000;'>TOTAL</th>
	                </tr>
	            </thead>
	            <tbody>
	    ";

	    $sqlGeracao = "
	        SELECT  tbl_posto_fabrica.codigo_posto,
	                tbl_posto.nome,
	                TO_CHAR(tbl_extrato.data_geracao,'DD/MM/YYYY') AS data_geracao,
	                tbl_extrato.protocolo AS numero_extrato,
	                tbl_extrato_extra.nota_fiscal_mao_de_obra AS nf_autorizada,
					tbl_extrato.total,
					tbl_extrato.extrato
	        FROM    tbl_extrato_agrupado
	        JOIN    tbl_extrato         USING(extrato)
	        JOIN    tbl_extrato_extra   USING(extrato)
	        JOIN    tbl_posto_fabrica   USING(fabrica,posto)
	        JOIN    tbl_posto           USING(posto)
	        WHERE   tbl_extrato.fabrica         = $login_fabrica
	        AND     tbl_extrato_agrupado.codigo = '$protocolo'
	  ORDER BY      tbl_posto_fabrica.codigo_posto
	    ";
	    $resGeracao = pg_query($this->_con,$sqlGeracao);

	    while ($extratos = pg_fetch_object($resGeracao)) {
	        $bold = ($extratos->total > 3000) ? "font-weight:bold;" : "";
			$total   = $extratos->total;
			$extrato = $extratos->extrato;
			if(!empty($extrato)) {
	               $totalTx =  somaTxExtratoBlack($extrato); 
	               $total+=$totalTx;
			}
	        $conteudo .= "
	            <tr >
	                <td style='border:1px solid #000;".$bold."'>".$extratos->codigo_posto."</td>
	                <td style='border:1px solid #000;".$bold."'>".$extratos->nome."</td>
	                <td style='border:1px solid #000;".$bold."'>".$extratos->data_geracao."</td>
	                <td style='border:1px solid #000;".$bold."'>".$extratos->numero_extrato."</td>
	                <td style='border:1px solid #000;".$bold."'>".$extratos->nf_autorizada."</td>
	                <td style='border:1px solid #000;".$bold."'>R$ ".number_format($total,2,',','.')."</td>
	            </tr>
	        ";
			$total_extrato += $total;
	    }
	    $conteudo .= "
	            </tbody>
	            <tfoot>
	                <tr>
	                    <td colspan='6' style='font-weight:bold;text-align:right;'>TOTAL EXTRATOS: R$".number_format($total_extrato,2,',','.')."</td>
	                </tr>
	            </tfoot>
	        </table>
	    ";

	    $dadosAprovadores = $this->getAprovadoresProtocolo($protocolo);

	    if ($tipo == "aprovar" || $tipo == "alterar" || $tipo == "finalizar") {

	        //$img_assinatura = $tDocsAss->getDocumentsByRef($login_admin)->url;

	        $assinaturaComercial = "
                        ____________________________________
                        <br />
                        Analista Comercial
                        <br />
                        ____/____/________
            ";

	        if (!empty($dadosAprovadores["aprovador_comercial"])) {

	        	$img_assinatura = $tDocsAss->getDocumentsByRef($dadosAprovadores["id_aprovador_comercial"])->url;

	        	$assinaturaComercial = "
	        		<img id='imagem_firma' src='{$img_assinatura}' height='96' />
                    <br />
                    {$dadosAprovadores["aprovador_comercial"]}
                    <br />
                    Analista Comercial
                    <br />
                    ".$dadosAprovadores["data_aprovacao_comercial"]."
	        	";

	        }

	        $assinaturaPosvenda = "
                        ____________________________________
                        <br />
                        Gerente de Pós-venda
                        <br />
                        ____/____/________
            ";

	        if (!empty($dadosAprovadores["aprovador_posvenda"])) {

	        	$img_assinatura = $tDocsAss->getDocumentsByRef($dadosAprovadores["id_aprovador_posvenda"])->url;

	        	$assinaturaPosvenda = "
	        		<img id='imagem_firma' src='{$img_assinatura}' height='96' />
                    <br />
                    {$dadosAprovadores["aprovador_posvenda"]}
                    <br />
                    Gerente de Pós-venda
                    <br />
                    ".$dadosAprovadores["data_aprovacao_posvenda"]."
	        	";

	        }

	        $assinaturaContasReceber = "
	        	<br /><br />
                ____________________________________
                <br />
                Gerente de Contas a Receber
                <br />
                ____/____/________
	        ";

	        if (!empty($dadosAprovadores["aprovador_contas_receber"])) {

	        	$img_assinatura = $tDocsAss->getDocumentsByRef($dadosAprovadores["id_aprovador_contas_receber"])->url;

	        	$assinaturaContasReceber = "
	        		<img id='imagem_firma' src='{$img_assinatura}' height='96' />
                    <br />
                    {$dadosAprovadores["aprovador_contas_receber"]}
                    <br />
                    Gerente de Contas a Receber
                    <br />
                    ".$dadosAprovadores["data_aprovacao_contas_receber"]."
	        	";

	        }

	        $assinaturaContasPagar = "
	        	<br /><br />
                ____________________________________
                <br />
                Gerente de Contas a Pagar
                <br />
                ____/____/________
	        ";

	        if ($tipo == "finalizar") {

	        	$img_assinatura = $tDocsAss->getDocumentsByRef($login_admin)->url;

	        	$assinaturaContasPagar = "
	        		<img id='imagem_firma' src='{$img_assinatura}' height='96' />
                    <br />
                    {$login_nome_completo}
                    <br />
                    Gerente de Contas a Pagar
                    <br />
                    ".date("d/m/Y")."
	        	";

	        }

	        //contas receber bloqueado por enquanto
	        $assinaturaContasReceber = "";

	        $conteudo .= "
	            <br />
	            <table autosize='1' cellspacing='0' cellpadding='0' style='width:100%;'>
	                <tr>
	                    <td align='center'>
	                        {$assinaturaComercial}
	                    </td>
	                    <td align='center'>
	                        {$assinaturaPosvenda}
	                    </td>
	                </tr>
	               	<tr>
	                    <td align='center'>
	                        {$assinaturaContasPagar}
	                    </td>
	                </tr>
	            </table>
	        ";
	    } else if ($tipo == "reprovar") {
	        $conteudo .= "
	            <table autosize='1' cellspacing='0' cellpadding='0' style='width:100%;'>
	                <tr>
	                    <td align='center'>Motivo da Reprova: $just</td>
	                </tr>
	            </table>
	        ";
	    } else if ($tipo == "ressalva") {
	        $img_assinatura = $tDocsAss->getDocumentsByRef($login_admin)->url;
	        $conteudo .= "
	            <table autosize='1' cellspacing='0' cellpadding='0' style='width:100%;'>
	                <tr>
	                    <td align='center'>
	                        ________________________________________________________
	                        <br />
	                        Jéssica Brito
	                        <br />
	                        Analista Comercial
	                        <br />
	                        ____/____/________
	                    </td>
	                    <td align='center'>
	                        <img id='imagem_firma' src='$img_assinatura' height='96' />
	                        <br />
	                        $login_nome_completo
	                    </td>
	                    <td align='center'>
	                        ________________________________________________________
	                        <br />
	                        Adriano Fortuna
	                        <br />
	                        Financeiro
	                        <br />
	                        ____/____/________
	                    </td>
	                </tr>
	                <tr>
	                    <td colspan='3' align='center'>Motivo da aprovação após prévia reprovação: $just</td>
	                </tr>
	            </table>
	        ";
	    }

	    $topo = utf8_encode($topo);

	    $tipoCaminho = ($tipo == "alterar") ? "aprovar" : $tipo;

	    $arquivo = utf8_encode($cabecalho).utf8_encode($conteudo);
	    $caminho = "xls/protocolo_".$login_fabrica."_".$protocolo."_".$tipoCaminho.".pdf";

	    $pdf->allow_html_optional_endtags = true;
	    $pdf->setAutoTopMargin = 'stretch';
	    $pdf->SetTitle("PROTOCOLO ".$protocolo);
	    $pdf->SetDisplayMode('fullpage');
	    $pdf->AddPage('L');
	    $pdf->SetHTMLHeader($topo,'O',true);
	    $pdf->WriteHTML($arquivo);
	    $pdf->Output($caminho,'F');

	    $retorno = $tDocs->uploadFileS3($caminho,$protocolo,false);

	    $gerado = $tDocs->getDocumentsByName("protocolo_".$login_fabrica."_".$protocolo."_".$tipoCaminho.".pdf")->url;

	    if ($tipo == "reprovar") {
	        $acao = "reprovado";
	    } else if ($tipo == "ressalva") {
	        $acao = "aprovado com observação";
	        $this->avisoEmailProtocolo($login_fabrica,$protocolo);
	    } else {
	        $acao = "";
	        $this->avisoEmailProtocolo($login_fabrica,$protocolo);
	    }
	    return json_encode(array("ok"=>TRUE,"protocolo"=>$gerado,"acao"=>utf8_encode($acao)));
	}

	public function aprovaProtocolo($login_fabrica,$login_admin,$login_nome_completo,$protocolo,$tipo,$just = null,$classTdocs,$classPdf, $classTdocsAss)
	{

		$this->_classTdocs = $classTdocs;
		$this->_tDocsAss   = $classTdocsAss;
		$this->_classPdf   = $classPdf;

	    switch ($tipo) {
	        case "reprovar":
	            $sql = "
	                UPDATE  tbl_extrato_agrupado
	                SET     admin = $login_admin,
	                        reprovado = CURRENT_TIMESTAMP,
	                        motivo_reprovacao = '".pg_escape_string($just)."'
	                WHERE   codigo = '$protocolo'
	            ";
	            break;
	        case "aprovar":
	            $sql = "
	                UPDATE  tbl_extrato_agrupado
	                SET     admin = $login_admin,
	                        aprovado = CURRENT_TIMESTAMP
	                WHERE   codigo = '$protocolo'
	            ";
	            break;
	        case "ressalva":
	            $sql = "
	                UPDATE  tbl_extrato_agrupado
	                SET     admin = $login_admin,
	                        aprovado = CURRENT_TIMESTAMP,
	                        motivo_cancela_reprovacao = '".pg_escape_string($just)."'
	                WHERE   codigo = '$protocolo'
	                AND     reprovado IS NOT NULL
	            ";
	            break;
	    }

	    $res = pg_query($this->_con,$sql);

	    if (in_array($tipo, ["aprovar","ressalva"])) {

	        $this->insereStatusProtocolo($protocolo, 'an_pv');

	    }

	    if (pg_last_error()) {
	        return "erro";
	    }

	    $retorno = $this->gerarProtocoloArquivo($login_fabrica,$login_admin,$login_nome_completo,$protocolo,$tipo,$just);

	    return $retorno;
	}

	public function excluiProtocolo($login_fabrica,$protocolo)
	{
	    pg_query($this->_con,"BEGIN TRANSACTION");

	    $sql = "
	        DELETE FROM tbl_extrato_agrupado
	        WHERE codigo = '$protocolo'
	    ";
	    $res = pg_query($this->_con,$sql);

	    if (pg_last_error($this->_con)) {
	        pg_query($this->_con,"ROLLBACK TRANSACTION");

	        return "Não foi possível excluir o Protocolo";
	    }

	    $tDocs  = $this->_classTdocs;

	    $realProtocolo  = (int)$protocolo;
	    $arquivos = $tDocs->getDocumentsByRef($realProtocolo)->attachListInfo;

	    foreach ($arquivos as $arq) {
	        $tDocs->deleteFileById($arq['tdocs_id']);
	    }

	    pg_query($this->_con,"COMMIT TRANSACTION");
	    return json_encode(array("ok"=>true));
	}

	public function getAprovadoresProtocolo($protocolo) {

		$sql = "SELECT DISTINCT ON (codigo)
					an_comercial.nome_completo 	     						as aprovador_comercial,
					an_comercial.admin 				 						as id_aprovador_comercial,
					TO_CHAR(tbl_extrato_agrupado.data_agrupa, 'dd/mm/yyyy') as data_aprovacao_comercial,
					ge_posvenda.nome_completo 		 						as aprovador_posvenda,
					ge_posvenda.admin 				 						as id_aprovador_posvenda,
					TO_CHAR(tbl_extrato_agrupado.aprovado, 'dd/mm/yyyy') 	as data_aprovacao_posvenda,
					ge_receber.nome_completo                                as aprovador_contas_receber,
					ge_receber.admin                                        as id_aprovador_contas_receber,
					TO_CHAR(ge_receber.data_input, 'dd/mm/yyyy') 			as data_aprovacao_contas_receber,
					ge_pagar.nome_completo                                as aprovador_contas_pagar,
					ge_pagar.admin                                        as id_aprovador_contas_pagar,
					TO_CHAR(ge_pagar.data_input, 'dd/mm/yyyy') 			as data_aprovacao_contas_pagar
				FROM tbl_extrato_agrupado
				JOIN tbl_extrato ON tbl_extrato.extrato = tbl_extrato_agrupado.extrato
				JOIN tbl_admin an_comercial ON an_comercial.admin = tbl_extrato_agrupado.admin_agrupa
				LEFT JOIN tbl_admin ge_posvenda ON ge_posvenda.admin = tbl_extrato_agrupado.admin
				LEFT JOIN LATERAL (
					SELECT tbl_admin.nome_completo,
						   tbl_admin.admin,
						   tbl_extrato_agrupado_status.data_input
                    FROM tbl_extrato_agrupado_status
                    JOIN tbl_status_extrato_agrupado USING(status_extrato_agrupado)
                    JOIN tbl_admin ON tbl_extrato_agrupado_status.admin = tbl_admin.admin
                    WHERE tbl_extrato_agrupado_status.extrato_agrupado_codigo = tbl_extrato_agrupado.codigo
                    AND tbl_status_extrato_agrupado.codigo = 'an_cp'
                    LIMIT 1
				) ge_receber ON true
				LEFT JOIN LATERAL (
					SELECT tbl_admin.nome_completo,
						   tbl_admin.admin,
						   tbl_extrato_agrupado_status.data_input
                    FROM tbl_extrato_agrupado_status
                    JOIN tbl_status_extrato_agrupado USING(status_extrato_agrupado)
                    JOIN tbl_admin ON tbl_extrato_agrupado_status.admin = tbl_admin.admin
                    WHERE tbl_extrato_agrupado_status.extrato_agrupado_codigo = tbl_extrato_agrupado.codigo
                    AND tbl_status_extrato_agrupado.codigo = 'final'
                    LIMIT 1
				) ge_pagar ON true
				WHERE tbl_extrato_agrupado.codigo = '{$protocolo}'
				AND tbl_extrato.fabrica = {$this->_fabrica}";
		$res = pg_query($this->_con, $sql);

		$dadosAdmin = pg_fetch_array($res);

		return $dadosAdmin;

	}

}
