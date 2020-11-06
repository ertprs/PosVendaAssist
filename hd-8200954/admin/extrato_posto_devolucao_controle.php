<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="financeiro";
include "autentica_admin.php";
include 'funcoes.php';
include '../helpdesk/mlg_funciones.php';
include_once "class/tdocs.class.php";

if($login_fabrica == 3){
	$tDocs = new TDocs($con, $login_fabrica);
}

if (in_array($login_fabrica,array(35,50,51,81,90,91,114,117,121,125,138,140,141,144,145,147))) {
	header('location:extrato_posto_devolucao_controle_novo_lgr.php');
	exit;
}

$array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
  "AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
  "ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
  "MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
  "PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
  "RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
  "RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
  "SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");

if($login_fabrica == 3){
	$layout_menu = 'financeiro';
}else{
	$layout_menu = 'auditoria';
}
$title = traduz('CONSULTA NOTAS DE DEVOLUÇÃO');

//HD 348716 - INICIO
if($_POST['up_obs'] == 1){
	$faturamento = $_POST['nota_fiscal'];
	$obs = utf8_decode($_POST['obs']);
	$sql = "UPDATE tbl_faturamento
				SET obs = '$obs'
				WHERE faturamento = $faturamento
				AND fabrica = $login_fabrica";
	$res = pg_query($con,$sql);
	if(strlen(pg_last_error($con)) == 0){
		echo "ok";
	}
	else{
		echo traduz("Erro ao Gravar Observação");
	}
	exit;
}
//HD 348716 - FIM

$sql = "SELECT posto_fabrica
		FROM tbl_fabrica
		WHERE fabrica = $login_fabrica ";
$res2 = pg_query ($con,$sql);
$posto_da_fabrica = pg_fetch_result ($res2,0,0);
// $posto_da_fabrica = 6359;

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i=0; $i<pg_num_rows ($res); $i++ ){
				$cnpj = trim(pg_fetch_result($res,$i,cnpj));
				$nome = trim(pg_fetch_result($res,$i,nome));
				$codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}



$btn_acao = trim(strtolower($_REQUEST['btnacao']));
if($btn_acao=='filtrar' ) {

	$posto                = $_REQUEST['posto'];
	$filtroTodos          = $_REQUEST['filtroTodos'];
	$filtroObrigatorio    = $_REQUEST['filtroObrigatorio'];
	$filtroNaoObrigatorio = $_REQUEST['filtroNaoObrigatorio'];
	$data_inicial         = $_REQUEST['data_inicial'];
	$data_final           = $_REQUEST['data_final'];
	$filtroRecebidos      = $_REQUEST['filtroRecebidos'];
	$nota_devolucao       = $_REQUEST['nota_devolucao'];
	$posto_devolucao      = $_REQUEST['posto_devolucao'];
	$nf                   = $_REQUEST['nf'];
	$data_nf_envio        = $_REQUEST['data_nf_envio'];
	$posto_devendo        = $_REQUEST['posto_devendo'];
	$devolucao            = $_REQUEST['devolucao'];
	$pendencias_postos    = $_REQUEST['pendencias_postos'];
	$posto_nome           = $_REQUEST['posto_nome'];
	$posto_codigo         = $_REQUEST['posto_codigo'];

	if($login_fabrica == 3){
		$posto  	 = $_REQUEST['posto'];
		$inspetor 	 = $_REQUEST['inspetor'];
		$estado 	 = $_REQUEST['estado'];
	}

	if($login_fabrica == 24){
        if((!dateFormat($data_inicial) or !dateFormat($data_final)) and
            strlen($nota_devolucao) == 0 and strlen($posto_codigo) == 0) {
			$msg_erro["msg"][] =  traduz('Informe uma data, nota de devolução ou Posto.');
			$msg_erro["campos"][] = "data";
		}

		if(strlen($filtroRecebidos) == 0 AND strlen($filtroTodos) == 0 AND strlen($nota_devolucao) == 0){
			$msg_erro["msg"][] =  traduz("Selecione um filtro: 'notas com recebimento pendente ' ou 'notas recebidas'");
		}
	}else{
		if(strlen($msg_erro)==0){
			if(strlen($data_inicial)==0 OR strlen($data_final)==0){
				$msg_erro["msg"][]    = traduz("Preencha todos os campos obrigatórios.");
				$msg_erro["campos"][] = "data";
			}
		}
	}

	if (count($msg_erro)==0) {
		//Início Validação de Datas
		if ($data_inicial) {
			if (!$nova_data_inicial = dateFormat($data_inicial, 'dmy')) {				
				$msg_erro["msg"][]    = traduz("Data Inválida.");
				$msg_erro["campos"][] = "data";
			}
		}
		if ($data_final) {
			if (!$nova_data_final = dateFormat($data_final, 'dmy')){
				$msg_erro["msg"][]    = traduz("Data Inválida.");
				$msg_erro["campos"][] = "data";
			}
		}
		if (strlen($msg_erro)==0) {
			if ($nova_data_final < $nova_data_inicial){
				$msg_erro["msg"][]    = traduz("Data Inválida.");
				$msg_erro["campos"][] = "data";
			}
		}
	} //Fim Validação de Datas
	//if ($msg_erro) $msg_erro .= $data_inicial . ' / ' . $data_final;


	if(count($msg_erro)==0 ){

		if(count($estado)){
				$where_estado .= " AND tbl_posto_fabrica.contato_estado in ('".implode("','", $estado)."') ";
			}

			if(count($inspetor)){
				$where_inspetor .= " AND tbl_posto_fabrica.admin_sap in (".implode(",", $inspetor).") ";
			}


			if (strlen($posto_codigo)>0){
				$sql_adicional = " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
			}
			if (strlen($filtroTodos)>0 || strlen($filtroRecebidos)>0){
				if (strlen($filtroTodos)>0){
					$sql_adicional_2 = " AND tbl_faturamento.conferencia IS NULL ";
				}
				if (strlen($filtroRecebidos)>0){
					$sql_adicional_2 = " AND tbl_faturamento.conferencia IS NOT NULL ";
				}
				if (strlen($filtroTodos)>0 && strlen($filtroRecebidos)>0){
					$sql_adicional_2 = "";
				}
			}

			if (strlen($filtroObrigatorio)>0 AND strlen($filtroNaoObrigatorio)==0){
				$sql_adicional_5  = " AND tbl_faturamento.movimento = 'RETORNAVEL' ";
			}

			if (strlen($filtroObrigatorio)==0 AND strlen($filtroNaoObrigatorio)>0){
				$sql_adicional_5  = " AND tbl_faturamento.movimento = 'NAO_RETOR.' ";
			}

			if (strlen($data_inicial)>0 AND strlen($data_final)>0){
				$tmp_data_inicial = dateFormat($data_inicial, 'dmy');
				$tmp_data_final   = dateFormat($data_final,   'dmy');
				$sql_adicional_3  = " AND tbl_faturamento.emissao BETWEEN '$tmp_data_inicial' AND '$tmp_data_final' ";
			}

			if ((strlen($data_inicial)==0 AND strlen($data_final)>0) OR (strlen($data_inicial)>0 AND strlen($data_final)==0)){
				$msg_erro = traduz("Data Inválida");
			}

			if (strlen($nota_devolucao)>0){
				$sql_adicional_4 = " AND tbl_faturamento.nota_fiscal LIKE '%$nota_devolucao' ";
			}

			if($login_fabrica == 3){
				$colunas_extra = "tbl_admin.nome_completo, tbl_posto_fabrica.contato_estado, ";
				$join_admin_sap = " left join tbl_admin on tbl_admin.admin = tbl_posto_fabrica.admin_sap AND tbl_admin.fabrica = $login_fabrica ";
			}

			$sql = "SELECT tbl_faturamento.faturamento,
							to_char(tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
							to_char(tbl_faturamento.conferencia,'DD/MM/YYYY') AS conferencia,
							to_char(tbl_faturamento.cancelada,'DD/MM/YYYY') AS cancelada,
							tbl_faturamento.devolucao_concluida,
							tbl_faturamento.nota_fiscal,
							tbl_faturamento.extrato_devolucao,
							tbl_faturamento.total_nota            AS total_nota,
							tbl_faturamento.valor_ipi             AS valor_ipi,
							tbl_posto.nome                        AS nome_posto,
							tbl_posto.posto                       AS posto,
							tbl_posto_fabrica.codigo_posto        AS codigo_posto,
							$colunas_extra
							tbl_extrato.admin_lgr,
							tbl_extrato.extrato
					FROM tbl_faturamento
					JOIN tbl_posto ON tbl_faturamento.distribuidor = tbl_posto.posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					$join_admin_sap
					left JOIN tbl_extrato ON tbl_extrato.extrato = tbl_faturamento.extrato_devolucao
					WHERE tbl_faturamento.distribuidor IS NOT NULL
					AND tbl_faturamento.posto     = $posto_da_fabrica
					AND tbl_faturamento.fabrica   = $login_fabrica
					AND tbl_posto_fabrica.fabrica = $login_fabrica
					$sql_adicional
					$sql_adicional_2
					$sql_adicional_3
					$sql_adicional_4
					$sql_adicional_5
					$where_estado
					$where_inspetor
					ORDER BY faturamento DESC; ";
			$res_notas = @pg_query ($con,$sql);

			if(isset($_POST["gerar_excel"])){
				$data = date("d-m-Y-H:i");

				$fileName = "extrato_posto_devolucao_controle-{$data}.csv";

				$file = fopen("/tmp/{$fileName}", "w");

				$head = traduz("Código").";" . traduz("Razão Social").";";
				
				if($login_fabrica == 3){
					$head .= "Estado;Inspetor;";
					$head .= "Extrato;Data Extrato;Referência Peça;Descrição Peça;Quantidade;Nota Fiscal (Nº);Data Último Comprovante Envio;\r\n";
				}else{
					if ($login_fabrica == 151) {
						$head .= "Extrato;Data Extrato;Nota Fiscal (Nº);Data Conferência;Valor Nota; Cancelada;\r\n";
					} else {
						$head .= "Extrato;Data Extrato;Nota Fiscal (Nº);\r\n";
					}
				}

				fwrite($file, $head);

				$body = "";
				if ($login_fabrica == 151) {
					unset($arr_nf);
				}

				for ($i=0;$i<pg_num_rows($res_notas);$i++){
					$faturamento			= pg_fetch_result($res_notas,$i,'faturamento');
					$emissao				= pg_fetch_result($res_notas,$i,'emissao');
					$conferencia			= pg_fetch_result($res_notas,$i,'conferencia');
					$cancelada				= pg_fetch_result($res_notas,$i,'cancelada');
					$devolucao_concluida	= pg_fetch_result($res_notas,$i,'devolucao_concluida');
					$nota_fiscal			= pg_fetch_result($res_notas,$i,'nota_fiscal');
					if ($login_fabrica == 151) {
						if (isset($arr_nf)) {
							if (in_array($nota_fiscal, $arr_nf)) {
								continue;
							} else {
								$arr_nf[] = $nota_fiscal;
							}
						} else {
							$arr_nf[] = $nota_fiscal;
						}
						$extrato = pg_fetch_result($res_notas,$i,'extrato_devolucao');
					} else {
						$extrato			= pg_fetch_result($res_notas,$i,'extrato');
					}
					$nf_total_nota			= pg_fetch_result($res_notas,$i,'total_nota');
					$nf_valor_ipi			= pg_fetch_result($res_notas,$i,'valor_ipi');
		//			$nf_total_nota			= $nf_total_nota + $nf_valor_ipi;
					$nf_total_nota			= number_format($nf_total_nota,2,',','.');
					$extrato_devolucao		= pg_fetch_result($res_notas,$i,'extrato_devolucao');
					$nome_posto_1			= pg_fetch_result($res_notas,$i,'nome_posto');
					$cod_posto				= pg_fetch_result($res_notas,$i,'posto');
					$codigo_posto_1			= pg_fetch_result($res_notas,$i,'codigo_posto');
					$admin_lgr				= pg_fetch_result($res_notas,$i,'admin_lgr');


					if (empty($extrato_devolucao) AND $login_fabrica != 3) { // HD 738953

						$sql = "SELECT 
								DISTINCT extrato_devolucao
								FROM tbl_faturamento_item
								INNER JOIN tbl_extrato on tbl_extrato.extrato = tbl_faturamento_item.extrato_devolucao								
								WHERE tbl_faturamento_item.faturamento = $faturamento";
						$res2 = pg_query($con,$sql);

						if ( pg_num_rows($res2) ) {
							$extrato_devolucao = pg_result($res2,0,0);
						}
					}

					if($login_fabrica == 3){
						$contato_estado   = pg_fetch_result($res_notas,$i,'contato_estado');
						$nome_completo 	  = pg_fetch_result($res_notas,$i,'nome_completo');

						$tDocs->setContext('lgr');
						if ($tDocs->getDocumentsByRef($faturamento)->attachListInfo) {
							//$anexo = $tDocs->url;
							$anexo_ultimo = $tDocs->getDocumentsByRef($faturamento)->attachListInfo;
						}else{
							$anexo_ultimo = null;
						}

						/*if(count($anexo_ultimo)>0){
							$ultimo_arr = array_pop($anexo_ultimo);
							$data_comprovante = mostra_data($ultimo_arr['extra']['date']);
						}else{
							$data_comprovante = "";
						}
						*/
					
						$temComprovante = $tDocs->getDocumentsByRef($extrato_devolucao,'comprovantelgr')->attachListInfo;

						if(count($temComprovante)>0){
							$ultimo_arr = array_pop($temComprovante);
							$data_comprovante = mostra_data($ultimo_arr['extra']['date']);
						}else{
							$data_comprovante = "";
						}

						$sql_pecas = " SELECT
										tbl_peca.referencia,
										tbl_peca.descricao,
										tbl_faturamento_item.qtde
										FROM tbl_faturamento
										JOIN tbl_faturamento_item USING (faturamento)
										JOIN tbl_peca USING (peca)
										WHERE tbl_faturamento.fabrica = $login_fabrica
										AND tbl_faturamento.faturamento = $faturamento
										GROUP BY
										tbl_peca.referencia,
										tbl_peca.descricao,
										tbl_faturamento_item.qtde
										ORDER BY tbl_peca.referencia";
						$res_pecas = pg_query($con, $sql_pecas);
						$bodypecas = "";
						for($a= 0; $a<pg_num_rows($res_pecas); $a++){
							$referencia = pg_fetch_result($res_pecas, $a, referencia);
							$descricao 	= pg_fetch_result($res_pecas, $a, descricao);
							$qtde 		= pg_fetch_result($res_pecas, $a, qtde);

							if($a == 0){
								$bodypecas .= ";$referencia;$descricao;$qtde;$nota_fiscal;$data_comprovante; \r\n";
							}else{
								if($login_fabrica == 3){
									$bodypecas .= ";;;;;;$referencia;".substr($descricao, 0, 30).";$qtde; \r\n";
								}else{
									$bodypecas .= ";;;;;;;$referencia;".substr($descricao, 0, 30).";$qtde; \r\n";
								}
							}
						}
					}

					$body .= "$codigo_posto_1;$nome_posto_1;";

					if($login_fabrica == 3){
						$body .= "$contato_estado;$nome_completo;";
					}

					if ($login_fabrica == 151) {
						$body .= "$extrato;$emissao;$nota_fiscal;$conferencia;$nf_total_nota;$cancelada";
					} else {
						$body .= "$extrato_devolucao;$emissao";
					}

					if($login_fabrica == 3){							
						$body .= $bodypecas;
						//$body .= ";$nota_fiscal;$data_comprovante";
					}else{
						$body .= "\r\n";
					}						
				}

				$body .= "\r\n";

				fwrite($file, $body);

				if (file_exists("/tmp/{$fileName}")) {
					system("mv /tmp/{$fileName} xls/{$fileName}");

					echo "xls/{$fileName}";
				}
				
				exit;
			}
	}


}

$agrupar = "true";

/**
 * Enviar os e-mails das notas de devolucao para os postos da Colormaq
 * HD 107532
 *
 * @author Augusto Pascutti <augusto.hp@gmail.com>
 */

if ( $login_fabrica == 50 && isset($_POST['enviar_emails']) ) {

	/**
	 * Arquivo contento os dados do e-mail
	 */
	define('COLORMAQ_ARQUIVO_EMAIL_DEVOLUCAO', __DIR__ . '/'.'documentos/colormaq_email_devolucao.txt');

	/**
	 * Retorna o email de nf de decolucao da colormaq.
	 *
	 * @return array('email_de'=>'string',
	 *               'assunto'=>'string',
	 *               'mensagem'=>'string')
	 */
	function colormaq_retorna_email_devolucao() {
	    global $login_fabrica;

	    if ( $login_fabrica != 50 || ! file_exists(COLORMAQ_ARQUIVO_EMAIL_DEVOLUCAO) ) { return array(); }
	    $handler  = fopen(COLORMAQ_ARQUIVO_EMAIL_DEVOLUCAO,'r');
	    if ( ! is_resource($handler) ) { return array(); }
	    $conteudo = fread($handler, filesize(COLORMAQ_ARQUIVO_EMAIL_DEVOLUCAO));
	    fclose($handler);
	    if ( empty($conteudo) ) { return array(); }
	    $conteudo = unserialize($conteudo);
	    if ( is_array($conteudo) ) {
	        return $conteudo;
	    }
	    return array();
	}



	/**
	 * Retorna o email e o assunto formatado com os dados informados para função.
	 *
	 * @param string $nota_fiscal
	 * @param string $extrato
	 * @param string $emissao
	 * @param string $posto
	 * @return array('assunto'=>'string'
	 *               'mensagem'=>'string')
	 */
	function colormaq_retorna_email_formatado($nota_fiscal, $extrato, $emissao, $posto) {
	    $dados = colormaq_retorna_email_devolucao();

	    if ( count($dados) <= 0 ) { return array(); }
	    $assunto  = $dados['assunto'];
	    $mensagem = $dados['mensagem'];

	    $replace  = array('__NF__'=>'nota_fiscal',
	                      '__EXTRATO__'=>'extrato',
	                      '__DATA_EMISSAO__'=>'emissao',
	                      '__POSTO__'=>'posto');
	    foreach ($replace as $search=>$replace) {
	        $assunto  = str_replace($search, $$replace, $assunto);
	        $mensagem = str_replace($search, $$replace, $mensagem);
	    }
	    return array('assunto'=>$assunto,
	                 'mensagem'=>$mensagem);
	}

	$erros_envio_email   = array();
	$sucesso_envio_email = 0;
	foreach ( $_POST['nf_enviar_emails'] as $nf_id ) {
		// consultar dados da nf e enviar o email
		$nf  = pg_escape_string($nf_id);
	    $sql = "SELECT f.nota_fiscal, f.total_nota, f.extrato_devolucao, to_char(f.emissao,'dd/mm/yyyy') as emissao,
	                   pf.codigo_posto as posto, p.nome, p.email
	            FROM tbl_faturamento f
	            INNER JOIN tbl_posto p ON (f.distribuidor = p.posto)
	            INNER JOIN tbl_posto_fabrica pf ON (p.posto = pf.posto AND f.fabrica = pf.fabrica)
	            WHERE f.fabrica = {$login_fabrica}
	            AND f.nota_fiscal = '{$nf}'";
	    $res = pg_query($con,$sql);
	    $rows= pg_num_rows($res);
	    if ( $rows <= 0 ) { return false; }
	    $nota_fiscal = pg_fetch_result($res,0,'nota_fiscal');
	    $extrato     = pg_fetch_result($res,0,'extrato_devolucao');
	    $emissao     = pg_fetch_result($res,0,'emissao');
	    $posto       = pg_fetch_result($res,0,'posto');
	    $posto_nome  = pg_fetch_result($res,0,'nome');
	    $posto       = "{$posto} - {$posto_nome}";
	    $email       = pg_fetch_result($res,0,'email');
	    $email_dados = colormaq_retorna_email_devolucao();
	    $dados       = colormaq_retorna_email_formatado($nota_fiscal,$extrato,$emissao,$posto);
	    $header      = "Content-type: text/plain; enconding=iso-8859-1 \r\n";
	    $header      = "From: {$email_dados['email_de']}\r\n";
		if ( ! mail($email, utf8_encode($dados['assunto']), utf8_encode($dados['mensagem']), $header) ) {
			$erros_envio_email[] = $nf_id;
		} else {
			$sucesso_envio_email++;
		}
	}
}
// fim HD 107532

if (isset($_POST["btn_acao"])){
	$btn_acao = trim($_POST["btn_acao"]);

	if ($btn_acao == "gravar"){

		if(strlen($msg_erro)==0){
			$qtde_linhas = trim($_POST['qtde_notas']);
			$acao        = trim($_POST['acao']);

			if (strlen($qtde_linhas)>0 AND strlen($acao)>0 ){

				$res = @pg_query($con,"BEGIN TRANSACTION");

				for ($i=0 ; $i < $qtde_linhas ; $i++){

					$faturamento = trim($_POST['faturamento_'.$i]);
					if (strlen($faturamento)==0) continue;

					if ($acao == "confirmar"){
						$sql = "UPDATE tbl_faturamento
									SET conferencia     = CURRENT_TIMESTAMP,
									cancelada           = NULL,
									devolucao_concluida = 't'
								  WHERE faturamento  = $faturamento
									AND fabrica      = $login_fabrica
									AND distribuidor IS NOT NULL";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_last_error($con);

						$sql = "UPDATE tbl_faturamento_item
								SET qtde_inspecionada = qtde
								WHERE faturamento = $faturamento";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_last_error($con);
					}

					if ($acao == "nao_recebida"){

						$sql = "SELECT distribuidor,nota_fiscal,emissao,extrato_devolucao
								FROM tbl_faturamento
								WHERE faturamento = $faturamento
								AND fabrica = $login_fabrica";
						$res = pg_query($con,$sql);
						if (pg_num_rows($res)>0){
							$posto       = pg_fetch_result($res,0,'distribuidor'); # distribuidor é o posto
							$nota_fiscal = pg_fetch_result($res,0,'nota_fiscal');
							$emissao     = pg_fetch_result($res,0,'emissao');
							$extrato     = pg_fetch_result($res,0,'extrato_devolucao');
						}else{
							continue;
						}

						$sql = "UPDATE tbl_faturamento
								SET
									conferencia = CURRENT_TIMESTAMP,
									cancelada   = CURRENT_DATE,
									devolucao_concluida = 'f'
								WHERE faturamento = $faturamento
								AND fabrica = $login_fabrica
								AND distribuidor IS NOT NULL";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_last_error($con);
						$sql = "INSERT INTO tbl_lgr_cancelado
								(posto,nota_fiscal,data_cancelamento,data_nf,usuario,fabrica,foi_cancelado)
								VALUES
								($posto,$nota_fiscal,CURRENT_DATE,'$emissao',$login_admin,$login_fabrica,'t')";
						//echo "<hr>".nl2br($sql);
						$res = pg_query($con,$sql);
						$msg_erro .= pg_last_error($con);

						$sql3="SELECT peca,qtde FROM tbl_faturamento_item WHERE faturamento = $faturamento";
						$res2 = pg_query($con,$sql3);


						for ($j=0; $j< pg_num_rows($res2); $j++){
							$peca = pg_fetch_result($res2,$j,peca);
							$qtde = pg_fetch_result($res2,$j,qtde);

							$sqlAtualiza = "UPDATE tbl_extrato_lgr
											SET qtde_nf = qtde_nf - $qtde
											WHERE peca	= $peca
											AND extrato = $extrato";
							$resA = pg_query($con,$sqlAtualiza);
							$msg_erro .= pg_last_error($con);
						}

						if ( in_array($login_fabrica, array(11,172)) ){ # HD 284618
							$mensagem_comunicado = traduz("O Fabricante cancelou a sua NF. n° $nota_fiscal referente ao extrato $extrato, favor preencher novamente a NF para a regularização do mesmo ou entrar em contato com o fabricante para o maior esclarecimento");

								# Envia um comunicado para o PA
							$sql = "INSERT INTO tbl_comunicado (
										descricao              ,
										mensagem               ,
										tipo                   ,
										fabrica                ,
										obrigatorio_os_produto ,
										obrigatorio_site       ,
										posto                  ,
										ativo
									) VALUES (
										'Nota Fiscal de Devolução Cancelada',
										'$mensagem_comunicado',
										'LGR',
										$login_fabrica,
										'f',
										't',
										$posto,
										't'
									);";
							$res = pg_query($con,$sql);
							$msg_erro .= pg_last_error($con);
						}
					}
				}

				if (strlen($msg_erro)>0){
					$res = @pg_query ($con,"ROLLBACK TRANSACTION");
				}
				else {
					$res = @pg_query ($con,"COMMIT TRANSACTION");
					$msg = traduz("Gravado com sucesso!");
				}
			}
		}
	}
}

$verPopup    = trim($_GET['pop_up']);
$xNotaFiscal = trim($_GET['nota_fiscal']);

if($login_fabrica == 153 and !empty($verPopup)) {
	header("location:extrato_posto_devolucao_controle_novo_lgr.php?pop_up=$verPopup&nota_fiscal=$xNotaFiscal");
}
#########################################################################################################
if ($verPopup == "sim"){

	$faturamento = $xNotaFiscal;
	$btn_acao = trim($_POST['btn_acao']);

	if (strlen($faturamento)==0){
		echo traduz("Nenhuma nota.");
		//echo "<script languague='javascript'>this.close();</script>";
		exit;
	}

	if($login_fabrica == 177){
		$campos_anauger = " tbl_faturamento.transportadora, 
			tbl_faturamento.transp, tbl_faturamento.qtde_volume, ";
	}

	$sql = "SELECT  faturamento,
			extrato_devolucao,
			TO_CHAR(emissao,'DD/MM/YYYY') as emissao,
			TO_CHAR(tbl_faturamento.conferencia,'DD/MM/YYYY HH24:MI') AS conferencia,
			TO_CHAR(tbl_faturamento.cancelada,'DD/MM/YYYY') AS cancelada,
			$campos_anauger 
			devolucao_concluida,
			nota_fiscal,
			distribuidor,
			posto,
			movimento,
			cfop,
			obs,
			chave_nfe,
			status_nfe
		FROM tbl_faturamento
		WHERE distribuidor IS NOT NULL
		AND fabrica        = $login_fabrica
		AND faturamento    = $faturamento";
	$resD = pg_query ($con,$sql);
	$qtde_for=pg_num_rows ($resD);

	if ($qtde_for == 0) {
		echo traduz("Nenhuma nota encontrada.");
		echo "<script languague='javascript'>this.close();</script>";
		exit;
	}

	$nota_fiscal = pg_fetch_result($resD,0,'nota_fiscal');
	$posto       = pg_fetch_result($resD,0,'distribuidor'); // distrib eh posto
	$faturamento = trim($_POST['faturamento']);
	$obs         = trim($_POST['obs']);

	if ($login_fabrica == 158) {
		$chave = pg_fetch_result($resD, 0, "chave_nfe");
		$n_log = pg_fetch_result($resD, 0, "status_nfe");
		$obs = pg_fetch_result($resD, 0, "obs");
	}

	if($btn_acao == 'gravar_obs' and !empty($faturamento)) {
		$resX = pg_query ($con,"BEGIN TRANSACTION");
		$sql = "UPDATE tbl_faturamento SET
					obs='$obs'
				WHERE faturamento = $faturamento
				AND fabrica = $login_fabrica";
		$res = pg_query ($con,$sql);
		$msg_erro .= pg_last_error($con);
		if (strlen($msg_erro) == 0) {
			$resX = pg_query ($con,"COMMIT TRANSACTION");
			$msg = traduz("Conferência gravada com Sucesso!");
		}else{
			$resX = pg_query ($con,"ROLLBACK TRANSACTION");
		}
		if($login_fabrica != 153) {
			echo "<script language='javascript'>opener.window.location.reload(); </script>";
		}
	}

	if ($btn_acao=="gravar_conferencia"){

		$resX = pg_query ($con,"BEGIN TRANSACTION");

		$qtde_linhas = trim($_POST['qtde_linhas']);

		if (strlen($qtde_linhas)==0){
			$qtde_linhas = 0;
		}

		$qtde_nao_devolvidas = 0;

		for ($i = 0; $i < $qtde_linhas ; $i++){

			$peca       = trim($_POST["peca_$i"]);
			$preco      = trim($_POST["preco_$i"]);
			$icms       = trim($_POST["icms_$i"]);
			$ipi        = trim($_POST["ipi_$i"]);
			$st         = trim($_POST["st_$i"]);
			$qtde_total = trim($_POST["qtde_$i"]);
			$qtde_total_inspecionada = trim($_POST["qtde_insp_$i"]);

			if (in_array($login_fabrica, array(158,177)) ) {
				$faturamento_item = $_POST["faturamento_item_$i"];

				$sql = "UPDATE tbl_faturamento_item SET
						qtde_inspecionada = NULL
					FROM tbl_faturamento
					WHERE tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
						AND tbl_faturamento.fabrica        = $login_fabrica
						AND tbl_faturamento.faturamento    = $faturamento
						AND tbl_faturamento_item.faturamento_item = $faturamento_item";
			} else {
				if($st > 0) {
					$cond_st = " AND tbl_faturamento_item.valor_subs_trib = '$st' ";
				}else{
					$cond_st = "";
				}
				$sql = "UPDATE tbl_faturamento_item SET
						qtde_inspecionada = NULL
					FROM tbl_faturamento
					WHERE tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
						AND tbl_faturamento.fabrica        = $login_fabrica
						AND tbl_faturamento.faturamento    = $faturamento
						AND tbl_faturamento_item.peca      = $peca
						AND tbl_faturamento_item.preco     = $preco
						AND tbl_faturamento_item.aliq_ipi  = $ipi
						AND tbl_faturamento_item.aliq_icms = $icms 
						$cond_st ";				
			}

			$res = pg_query ($con,$sql);
			$msg_erro .= pg_last_error($con);

			if (strlen($qtde_total_inspecionada)==0 OR strlen($peca)==0 OR strlen($qtde_total)==0){
				continue;
			}

			if ($qtde_total_inspecionada > $qtde_total){
				$qtde_total_inspecionada = $qtde_total;
			}

			$qtde_total_inspecionada_aux = $qtde_total_inspecionada;

			$qtde_nao_devolvidas += $qtde_total - $qtde_total_inspecionada;

			#Se digitou a quantidade inspecionada
			if ( $qtde_total_inspecionada >0 ){
				if (in_array($login_fabrica, array(158,177))) {
					$whereSelect = "AND tbl_faturamento_item.faturamento_item = $faturamento_item";
				} else {
					if($st > 0) {
						$cond_st = " AND tbl_faturamento_item.valor_subs_trib = '$st' ";
					}else{
						$cond_st = "";
					}
					$whereSelect = "
						AND tbl_faturamento_item.peca  = $peca
						AND tbl_faturamento_item.preco = $preco
						AND tbl_faturamento_item.aliq_icms  = $icms
						AND tbl_faturamento_item.aliq_ipi  =  $ipi
						$cond_st
					";
				}

				if($login_fabrica == 158){
					$condGarantia = " , tbl_faturamento.garantia  ";
				}

				$sql = "SELECT
							tbl_faturamento_item.faturamento,
							tbl_faturamento_item.faturamento_item,
							tbl_faturamento_item.peca,
							tbl_faturamento_item.qtde,
							tbl_faturamento_item.qtde_inspecionada
							$condGarantia
						FROM tbl_faturamento
						JOIN tbl_faturamento_item USING(faturamento)
						WHERE tbl_faturamento.fabrica       = $login_fabrica
							AND tbl_faturamento.faturamento = $faturamento
							AND tbl_faturamento_item.qtde -
								CASE WHEN tbl_faturamento_item.qtde_inspecionada IS NULL
									THEN 0
									ELSE tbl_faturamento_item.qtde_inspecionada
								END
								> 0
							{$whereSelect}
						ORDER BY tbl_faturamento.emissao ASC";
				$res = pg_query ($con,$sql);

				for ( $j=0; $j< pg_num_rows($res); $j++ ){

					if ( $qtde_total_inspecionada_aux <= 0 ){
						break;
					}

					$faturamento_item	= pg_fetch_result ($res,$j,'faturamento_item');
					$peca				= pg_fetch_result ($res,$j,'peca');
					$qtde				= pg_fetch_result ($res,$j,'qtde');
					$qtde_inspecionada	= pg_fetch_result ($res,$j,'qtde_inspecionada');

					if($login_fabrica == 158){
						$garantia	= pg_fetch_result ($res,$j,'garantia');
					}

					if (strlen($qtde_inspecionada)==0){
						$qtde_inspecionada = 0;
					}

					$qtde = $qtde - $qtde_inspecionada;

					if ( $qtde  - $qtde_total_inspecionada_aux < 0 ){
						$qtde_atualizar = $qtde;
					}else{
						$qtde_atualizar = $qtde_total_inspecionada_aux;
					}
					$qtde_atualizar = str_replace(",",".",$qtde_atualizar);


					$sql2 = "UPDATE tbl_faturamento_item
							SET qtde_inspecionada = (CASE WHEN tbl_faturamento_item.qtde_inspecionada IS NULL THEN 0 ELSE tbl_faturamento_item.qtde_inspecionada END) + $qtde_atualizar
							WHERE faturamento = $faturamento
							AND faturamento_item = $faturamento_item";
					$res2 = pg_query ($con,$sql2);
					$msg_erro .= pg_last_error($con);

					$qtde_total_inspecionada_aux = $qtde_total_inspecionada_aux - $qtde_atualizar;

					if (strlen($msg_erro)>0){
						break;
					}
				}
			}
			#Se digitou ZERO - não inspecionou
			if ( $qtde_total_inspecionada == 0 ){
				if ($login_fabrica == 158) {
					$whereUpdate = "AND tbl_faturamento_item.faturamento_item = $faturamento_item";
				} else {
					if($st > 0) {
						$cond_st = " AND tbl_faturamento_item.valor_subs_trib = '$st' ";
					}else{
						$cond_st = "";
					}
					$whereUpdate = "
						AND tbl_faturamento_item.peca   = $peca
						AND tbl_faturamento_item.preco  = $preco
						AND tbl_faturamento_item.aliq_icms   = $icms
						AND tbl_faturamento_item.aliq_ipi    =  $ipi
						$cond_st
					";
				}

				$sql = "UPDATE tbl_faturamento_item SET
							qtde_inspecionada = 0
						FROM tbl_faturamento
						WHERE tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
							AND tbl_faturamento.fabrica     = $login_fabrica
							AND tbl_faturamento.faturamento = $faturamento
							{$whereUpdate}";
				$res = pg_query ($con,$sql);
				$msg_erro .= pg_last_error($con);
			}
		}


		if( in_array($login_fabrica, array(11,172)) ) {  # HD 284618
			$update_obs = ",obs         = '$obs' ";
		}
		# Marca como conferida toda nota que o usuario clicar em Gravar
		$sql = "UPDATE tbl_faturamento
				SET conferencia = CURRENT_TIMESTAMP
				$update_obs
				WHERE faturamento = $faturamento
				AND fabrica = $login_fabrica";
		$res = pg_query ($con,$sql);
		$msg_erro .= pg_last_error($con);

					# Verifica se falta alguma peça para devolver desta nota
			$sql = "SELECT SUM( tbl_faturamento_item.qtde - CASE WHEN tbl_faturamento_item.qtde_inspecionada IS NULL THEN 0 ELSE tbl_faturamento_item.qtde_inspecionada END )
				FROM tbl_faturamento
				JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
				WHERE tbl_faturamento.fabrica   = $login_fabrica
				AND tbl_faturamento.faturamento = $faturamento
				AND (tbl_faturamento_item.qtde - CASE WHEN tbl_faturamento_item.qtde_inspecionada IS NULL THEN 0 ELSE tbl_faturamento_item.qtde_inspecionada END)>0";
			$res = pg_query ($con,$sql);

			$qtde_faltante = trim(pg_fetch_result($res,0,0));


		# Se não tiver mais peças para devolver, grava como concluída
		if ($qtde_faltante == 0 OR strlen($qtde_faltante)==0){

			$cond_conclui = " AND faturamento = $faturamento";

			$sql = "UPDATE tbl_faturamento
					SET devolucao_concluida = 't'
					WHERE fabrica = $login_fabrica
					{$cond_conclui}";
			$res = pg_query ($con,$sql);
			$msg_erro .= pg_last_error($con);
		}else if (!in_array($login_fabrica, array(161))) {

			$mensagem_comunicado = traduz("Foi acusado o recebimento parcial de sua NF. n° $nota_fiscal, favor entrar em contato urgente para sua regularização");

			if ( in_array($login_fabrica, array(11,172)) ){
				$mensagem_comunicado = traduz("O Fabricante acusou o recebimento parcial de sua NF. n° $nota_fiscal, favor entrar em contato urgente c/ a Taiz TEL:071 3379-1997, para sua regularização");
			}

			# Envia um comunicado para o PA
			$sql = "INSERT INTO tbl_comunicado (
						descricao              ,
						mensagem               ,
						tipo                   ,
						fabrica                ,
						obrigatorio_os_produto ,
						obrigatorio_site       ,
						posto                  ,
						ativo
					) VALUES (
						'Nota Fiscal de Devolução - LGR',
						'$mensagem_comunicado',
						'LGR',
						$login_fabrica,
						'f',
						't',
						$posto,
						't'
					);";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
		}

		if ($login_fabrica == 158 and $garantia != "t") {
			$sql = "
				SELECT e.extrato
				FROM tbl_faturamento_item fi
				INNER JOIN tbl_faturamento f ON f.faturamento = fi.faturamento AND f.fabrica = {$login_fabrica}
				INNER JOIN tbl_extrato e ON e.extrato = fi.extrato_devolucao AND e.fabrica = {$login_fabrica}
				WHERE fi.faturamento = {$faturamento}
				AND e.protocolo = 'Fora de Garantia'
				LIMIT 1
			";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$sql = "
					SELECT 
					    p.referencia AS peca,
					    p.unidade AS peca_unidade,
					    /*SUM(nfi.qtde) AS qtde,*/
					    SUM(nfi.qtde_inspecionada) AS qtde,
					    nfi.preco AS preco,
					    nf.nota_fiscal,
					    TO_CHAR(nf.emissao, 'YYYYMMDD') AS data_nota_fiscal,
					    nfo.nota_fiscal AS nota_fiscal_origem,
					    nf.chave_nfe AS chave,
					    nf.status_nfe AS log,
					    ea.codigo AS unidade_negocio,
					    pf.centro_custo
					FROM tbl_faturamento nf
					INNER JOIN tbl_faturamento_item nfi ON nfi.faturamento = nf.faturamento
					INNER JOIN tbl_peca p ON p.peca = nfi.peca AND p.fabrica = {$login_fabrica}
					INNER JOIN tbl_faturamento nfo ON nfo.faturamento = nfi.devolucao_origem AND nfo.fabrica = {$login_fabrica}
					INNER JOIN tbl_posto_fabrica pf ON pf.posto = nf.distribuidor AND pf.fabrica = {$login_fabrica}
					INNER JOIN tbl_posto_distribuidor_sla_default pdsd ON pdsd.posto = pf.posto AND pdsd.fabrica = {$login_fabrica}
					INNER JOIN tbl_distribuidor_sla ds ON ds.distribuidor_sla = pdsd.distribuidor_sla
					INNER JOIN tbl_extrato_agrupado ea ON ea.extrato = nfi.extrato_devolucao
					WHERE nf.fabrica = {$login_fabrica}
					AND nf.faturamento = {$faturamento}
					/*AND nfi.qtde > 0*/
					AND nfi.qtde_inspecionada > 0
					GROUP BY p.referencia, p.unidade, nfi.preco, nf.nota_fiscal, nf.emissao, nfo.nota_fiscal, nf.chave_nfe, nf.status_nfe, ea.codigo, pf.centro_custo
				";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res) > 0) {
					$xml_pecas = "";

					$unidadesMinasGerais = \Posvenda\Regras::getUnidades("unidadesMinasGerais", $login_fabrica);

					while ($row = pg_fetch_object($res)) {
						if (in_array($row->unidade_negocio, $unidadesMinasGerais)) {
							$row->unidade_negocio = 6101;
						}
						$xml_pecas .= "
							<T_ENTRADA>
								<CENTRO>{$row->unidade_negocio}</CENTRO>
								<CLIENTE>7310</CLIENTE>
								<DATA>{$row->data_nota_fiscal}</DATA>
								<DATANFP>{$row->data_nota_fiscal}</DATANFP>
								<TECNICO>{$row->centro_custo}</TECNICO>
								<MATERIAL>{$row->peca}</MATERIAL>
								<CANTIDAD>{$row->qtde}</CANTIDAD>
								<UM>".utf8_encode($row->peca_unidade)."</UM>
								<NFP>{$row->nota_fiscal}</NFP>
								<PRECIO>{$row->preco}</PRECIO>
								<NFO>{$row->nota_fiscal_origem}</NFO>
								<CHAVE>{$row->chave}</CHAVE>
								<NODOLOG>{$row->log}</NODOLOG>
								<P_TELEC>0</P_TELEC>
								<O_TELEC>0</O_TELEC>
								<TIPO_O>10</TIPO_O>
							</T_ENTRADA>
						";

					}

			if ($_serverEnvironment == 'development') {

			    $url = "https://fiori.efemsa.com:8443/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_DevSas_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

			    $authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

			} else {

			    $url = "https://fiori.efemsa.com:8425/XISOAPAdapter/MessageServlet?senderParty=TELECONTROL_BR&senderService=TELCONTROL&receiverParty=&receiverService=&interface=SI_DevSas_Receiver&interfaceNamespace=http://imbera.com/telecontrol";

			    $authorization = "Authorization: Basic ".base64_encode("PIAPPLTC:3lT#L&C0ntr01");

			}

			$xml = '
                            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tel="http://imbera.com/telecontrol">
                                <soapenv:Header/>
				<soapenv:Body>
				    <tel:MT_DevSas_Req>
				        '.$xml_pecas.'
                                    </tel:MT_DevSas_Req>
                                </soapenv:Body>
			    </soapenv:Envelope>
			';

			$headers = array(
			    "Content-type: text/xml;charset=\"utf-8\"",
			    "Accept: text/xml",
			    "Cache-Control: no-cache",
			    "Pragma: no-cache",
			    "Content-length: ".strlen($xml),
			    $authorization
			);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);	
	                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	                curl_setopt($ch, CURLOPT_URL, $url);
	                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	                curl_setopt($ch, CURLOPT_POST, 1);
	                curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
	                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$retornoCurl = curl_exec($ch);
			$erroCurl = curl_error($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
	                
	                if ($_serverEnvironment == "development") {
                            $file = fopen('/tmp/imbera-ws.log','a');
			} else {
			    $file = fopen('/mnt/webuploads/imbera/logs/imbera-ws.log','a');
			}

			fwrite($file, 'Resquest \n\r');
                        fwrite($file, 'URL: '.$url.'\n\r');
		        fwrite($file, $xml);

			fwrite($file, 'Response \n\r');
			fwrite($file, 'Error Curl: '.$erroCurl.'\n\r');
			fwrite($file, 'Http Code: '.$httpcode.'\n\r');
			fwrite($file, utf8_decode($retornoCurl));
			fclose($file);

			$retornoCurl = preg_replace(("/(<\/?)(\w+):([^>]*>)/"),"$1$2$3",$retornoCurl);
			$retornoXML = new \SimpleXMLElement(utf8_encode($retornoCurl));
                        $retornoXML = $retornoXML->xpath('//T_MENSAGEM');
			$retornoSoap = json_decode(json_encode((array) $retornoXML), true);
			$retornoSoap = $retornoSoap[0];

	                switch ($retornoSoap['ID']) {
					    case '001':
					        $msg_erro = traduz("Técnico não tem material em estoque - Retorno SAP: ").$retornoSoap['MENSAGEM'];
					        break;

					    case '002':
					        $msg_erro = traduz("Dados recebidos corretamente - Retorno SAP: ").$retornoSoap['MENSAGEM'];
					        break;

					    case '003':
					    	$msg_erro = traduz("Estoque do técnico abaixo da quantidade consumida no Telecontrol - Retorno SAP: ").$retornoSoap['MENSAGEM'];
					        break;

					    case '004':
					        $msg_erro = traduz("Pedido e ordem já estão no SAP - Retorno SAP: ").$retornoSoap['MENSAGEM'];
					        break;

					    case '005':
					        $msg_erro = traduz("Nenhum Centro equivalente na tabela centros SAP - Retorno SAP: ").$retornoSoap['MENSAGEM'];
					        break;

					    case '006':
					        $msg_erro = traduz("Unidade de medida errada, não existe no SAP - Retorno SAP: ").$retornoSoap['MENSAGEM'];
					        break;

					    case '007':
					        $msg_erro = traduz("NF ou dados fiscais não devem estar vazios - Retorno SAP: ").$retornoSoap['MENSAGEM'];
					        break;

					    case '008':
					        $msg_erro = traduz("NF errada, a nota recebida não está pendente no SAP - Retorno SAP: ").$retornoSoap['MENSAGEM'];
					        break;

					    case '009':
					        $msg_erro = traduz("Preço errado, não corresponde com a Nota Origem - Retorno SAP: ").$retornoSoap['MENSAGEM'];
					        break;
					    
					    case '010':
					        $msg_erro = traduz("Chave deve ter 44 dígitos - Retorno SAP: ").$retornoSoap['MENSAGEM'];
					        break;

					    case '011':
					        $msg_erro = traduz("Nº do Log deve ter 8 digitos - Retorno SAP: ").$retornoSoap['MENSAGEM'];
					        break;

					    case '012':
					        $msg_erro = traduz("Centro/Depósito não está correto - Retorno SAP: ").$retornoSoap['MENSAGEM'];
					        break;

					    case '013':
					        $msg_erro = traduz("Fechamento é só para técnicos próprios - Retorno SAP: ").$retornoSoap['MENSAGEM'];
					        break;

					}
				}
			}
		}

		//retirado da parte de empty $login_fabrica <> 158 and 
		if (in_array($retornoSoap['ID'], array('002','004')) or (empty($msg_erro))) {
			$resX = pg_query ($con,"COMMIT TRANSACTION");

			if ($login_fabrica == 50){ // HD 62366 03/03/2006
				echo "<script language='javascript'>window.opener = window;window.close('#');</script>";
			} elseif($login_fabrica <> 153) {
				echo "<script language='javascript'>opener.window.location.reload()</script>";
			}
		} else {
			$resX = pg_query ($con,"ROLLBACK TRANSACTION");
		}
		
	}

	if (strlen($msg_erro) == 0) {
		if ($btn_acao=="liberar_provisorio"){
			$aux_extrato = trim($_POST['extrato']);
			if (strlen($aux_extrato)>0){
				$sql = "UPDATE tbl_extrato
						SET admin_lgr = $login_admin
						WHERE extrato = $aux_extrato
						AND fabrica = $login_fabrica";
				$res = pg_query ($con,$sql);
				$msg_erro .= pg_last_error($con);
			}
		}

		if ($btn_acao=="bloquear_provisorio"){
			$aux_extrato = trim($_POST['extrato']);
			if (strlen($aux_extrato)>0){
				$sql = "UPDATE tbl_extrato
						SET admin_lgr = NULL
						WHERE extrato = $aux_extrato
						AND fabrica = $login_fabrica";
				$res = pg_query ($con,$sql);
				$msg_erro .= pg_last_error($con);
			}
		}


		$faturamento         = trim(pg_fetch_result($resD,0,'faturamento'));
		$distribuidor        = trim(pg_fetch_result($resD,0,'distribuidor'));
		$posto               = trim(pg_fetch_result($resD,0,'posto'));
		$emissao             = trim(pg_fetch_result($resD,0,'emissao'));
		$nota_fiscal         = trim(pg_fetch_result($resD,0,'nota_fiscal'));
		$extrato_devolucao	 = trim(pg_fetch_result($resD,0,'extrato_devolucao'));
		$cfop                = trim(pg_fetch_result($resD,0,'cfop'));
		$conferencia         = trim(pg_fetch_result($resD,0,'conferencia'));
		$devolucao_concluida = trim(pg_fetch_result($resD,0,'devolucao_concluida'));
		$movimento           = trim(pg_fetch_result($resD,0,'movimento'));
		$cancelada           = trim(pg_fetch_result($resD,0,'cancelada'));


		if($login_fabrica == 177){
			$transportadora = pg_fetch_result($resD, 0, 'transportadora');
			$transp  		= pg_fetch_result($resD, 0, 'transp');
			$qtde_volume 	= pg_fetch_result($resD, 0, 'qtde_volume');

			if(strlen(trim($transportadora)) > 0){
				$sqlT = "SELECT  
                        tbl_transportadora.cnpj, 
                        tbl_transportadora.nome,
                        tbl_transportadora.transportadora
                    FROM tbl_transportadora 
                    JOIN tbl_transportadora_fabrica ON tbl_transportadora.transportadora = tbl_transportadora_fabrica.transportadora
                    WHERE tbl_transportadora.transportadora = $transportadora 
                    AND fabrica = $login_fabrica";
                $resT = pg_query($con, $sqlT);
                if(pg_num_rows($resT)>0){
                	$nome_transportadora = pg_fetch_result($resT, 0, 'nome');
                }
			}elseif($transp == "conta"){
				$nome_transportadora = "Frete por Conta";
			}
		}


		if ($movimento=="RETORNAVEL" or (in_array($login_fabrica,array(106,6)))){
			$devolucao = "RETORNÁVEL";
		}else{
			$devolucao = "NÃO RETORNÁVEL";
		}

		if (strlen ($posto) > 0) {
			$sql = "SELECT  PO.cnpj      ,
					PO.ie        ,
					PO.fone      ,
					PO.nome      ,
					PF.contato_endereco    AS endereco,
					PF.contato_numero      AS numero,
					PF.contato_complemento AS complemento,
					PF.contato_bairro      AS bairro,
					PF.contato_cidade      AS cidade,
					PF.contato_estado      AS estado,
					PF.contato_cep         AS cep,
					PF.contato_email       AS email
				FROM tbl_posto         PO
				JOIN tbl_posto_fabrica PF ON PO.posto = PF.posto
				WHERE PO.posto   = $posto
				AND   PF.fabrica = $login_fabrica";
			//$sql  = "SELECT * FROM tbl_posto WHERE posto = $posto";

			if($login_fabrica == 43) {
				$sql = "SELECT cnpj  ,
						ie           ,
						razao_social ,
						fone         ,
						nome         ,
						endereco     ,
						cidade       ,
						estado       ,
						cep
					FROM tbl_fabrica
					WHERE fabrica   = $login_fabrica ";
			}
			$resX = pg_query ($con,$sql);

			if(pg_num_rows($resX) > 0) {

				if($login_fabrica <> 43) {
					$endereco = trim (pg_fetch_result ($resX,0,'endereco')) . " " . trim (pg_fetch_result ($resX,0,'numero'));
					$razao    = pg_fetch_result ($resX,0,'nome');
				}
				else{
					$endereco = trim (pg_fetch_result ($resX,0,'endereco'));
					$razao    = pg_fetch_result ($resX,0,razao_social);
				}
				$cidade = pg_fetch_result($resX,0,'cidade');
				$estado = pg_fetch_result($resX,0,'estado');
				$cep    = pg_fetch_result($resX,0,'cep');
				$fone   = pg_fetch_result($resX,0,'fone');
				$cnpj   = pg_fetch_result($resX,0,'cnpj');
				$ie     = pg_fetch_result($resX,0,'ie');
			}
		}
	}
	header('Content-Type: text/html; charset=iso-8859-1');

?>
	<!-- HD 348716 - INICIO -->
	<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
	<script src="js/jquery-1.1.2.pack.js" type="text/javascript"></script>

	<script type='text/javascript'>
		function gravaObs(nota_fiscal){
			var obs = document.getElementById("obs").value;
			$.post("<? echo $PHP_SELF;?>",
					{pop_up:'sim',
					nota_fiscal: nota_fiscal,
					up_obs: 1,
					obs:obs},
					function(data){
						if(data == "ok"){
							$("#resp_grava_obs").html(traduz("Observação Gravada com Sucesso"));
						}
						else{
							$("#resp_grava_obs").html(data);
						}
					});
		}
	</script>
	<!-- HD 348716 - FIM -->
<?

	if (strlen($msg_erro) > 0) {
		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
		echo "<tr bgcolor='#FF0000' style='font:bold 16px Arial; color:#FFFFFF;'>";
		echo "<td align='center'>$msg_erro</td>";
		echo "</tr>";
		echo "</table>";
   }

	$cabecalho  = "";

	if ($login_fabrica == 175) {
		$alinhar = "style = 'margin-left: 15px !important; margin-top: 9px !important;'";
	}

	$cabecalho  = "<style>font-size:14px;font-family:'Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif';</style>";
	$cabecalho  .= "<form name='conferencia' $alinhar action='$PHP_SELF?pop_up=sim&nota_fiscal=$xNotaFiscal' method='POST'>";

	$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";

	$cabecalho .= "<caption style='font-size:14px;padding:3px;color:#FFF;background-color:#596D9B'>\n";
	$cabecalho .= "<b>".traduz("CONFERÊNCIA")."</b>\n";
	$cabecalho .= "</caption>\n";

	$cabecalho .= "<tr align='left'  height='16'>\n";
	if ($login_fabrica == 158) {
		$cabecalho .= "<td bgcolor='#E3E4E6' colspan='4' style='font-size:18px'>\n";
	} else {
		$cabecalho .= "<td bgcolor='#E3E4E6' colspan='3' style='font-size:18px'>\n";
	}
	$cabecalho .= "<b>&nbsp;$produto_acabado<br>$devolucao </b><br>\n";
	$cabecalho .= "</td>\n";
	$cabecalho .= "</tr>\n";

	$cabecalho .= "<tr>\n";
	$cabecalho .= "<td>".traduz("Natureza")."<br>" . "<b>". traduz("Devolução de Garantia")."</b> </td>\n";
	$cabecalho .= "<td>".traduz("CFOP")."<br> <b>$cfop</b> </td>\n";
	$cabecalho .= "<td>".traduz("Emissao")."<br> <b>$emissao</b> </td>\n";
	if ($login_fabrica == 158) {
		$cabecalho .= "<td>".traduz("Número do Log")."<br> <b>$n_log</b> </td>\n";
	}
	$cabecalho .= "</tr>\n";
	$cabecalho .= "</table>\n";

	if ($login_fabrica == 158) {
		$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
		$cabecalho .= "<tr>\n";
		$cabecalho .= "<td>".traduz("Chave")."<br> <b>$chave</b> </td>\n";
		$cabecalho .= "</tr>\n";
		$cabecalho .= "</table>\n";
	}

	$cnpj = substr ($cnpj,0,2) . "." . substr ($cnpj,2,3) . "." . substr ($cnpj,5,3) . "/" . substr ($cnpj,8,4) . "-" . substr ($cnpj,12,2);
	$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
	$cabecalho .= "<tr>\n";
	$cabecalho .= "<td>".traduz("Razão Social")."<br> <b>$razao</b> </td>\n";
	$cabecalho .= "<td>".traduz("CNPJ")."<br> <b>$cnpj</b> </td>\n";
	$cabecalho .= "<td>".traduz("Inscrição Estadual")."<br> <b>$ie</b> </td>\n";
	$cabecalho .= "</tr>\n";
	$cabecalho .= "</table>\n";

	$cep = substr ($cep,0,2) . "." . substr ($cep,2,3) . "-" . substr ($cep,5,3) ;
	$cabecalho .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
	$cabecalho .= "<tr>\n";
	$cabecalho .= "<td>".traduz("Endereço")."<br> <b>$endereco </b> </td>\n";
	$cabecalho .= "<td>".traduz("Cidade")."<br> <b>$cidade</b> </td>\n";
	$cabecalho .= "<td>".traduz("Estado")." <br> <b>$estado</b> </td>\n";
	$cabecalho .= "<td>".traduz("CEP")."<br> <b>$cep</b> </td>\n";
	$cabecalho .= "</tr>\n";
	$cabecalho .= "</table>\n";

	$topo ="";
	$topo .= "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' id='tbl_pecas_$i'>\n";

	$topo .=  "<thead>\n";
	$topo .=  "<tr align='center'>\n";
	$topo .=  "<td><b>".traduz("Código")."</b></td>\n";
	$topo .=  "<td><b>".traduz("Descrição")."</b></td>\n";
	if($login_fabrica == 177){
		$topo .=  "<td><b>".traduz("Peso")."</b></td>\n";
	}
	$topo .=  "<td><b>".traduz("Qtde.")."</b></td>\n";
	$topo .=  "<td><b>".traduz("Preço")."</b></td>\n";
	$topo .=  "<td><b>".traduz("Total")."</b></td>\n";
	$topo .=  "<td><b>% ICMS</b></td>\n";
	$topo .=  "<td><b>% IPI</b></td>\n";
	if ($login_fabrica == 175) {
		$topo .=  "<td><b>ST</b></td>\n";
	}
	$topo .=  "<td><b>".traduz("Qtde. Insp.")."</b></td>\n";
	$topo .=  "</tr>\n";
	$topo .=  "</thead>\n";

	if (in_array($login_fabrica, array(158,177))) {
		$colunaFaturamentoItem = ", tbl_faturamento_item.faturamento_item";
	}

	if($login_fabrica == 175){
    	$campo_st = " COALESCE(tbl_faturamento_item.valor_subs_trib, 0) AS valor_subs_trib, COALESCE(tbl_faturamento_item.base_subs_trib, 0) AS base_subs_trib, ";
    	$group_by = " , tbl_faturamento_item.valor_subs_trib, tbl_faturamento_item.base_subs_trib ";
    }

	$sql = "SELECT 
			{$campo_st}
			tbl_peca.peca,
			tbl_peca.referencia,
			tbl_peca.descricao,
			tbl_peca.peso,
			tbl_peca.ipi,
			CASE WHEN tbl_peca.produto_acabado IS TRUE THEN 'TRUE' ELSE 'NOT TRUE' END AS produto_acabado,
			tbl_peca.devolucao_obrigatoria,
			tbl_faturamento.qtde_volume, 
			tbl_faturamento_item.aliq_icms,
			tbl_faturamento_item.aliq_ipi,
			tbl_faturamento_item.preco,
			SUM (tbl_faturamento_item.qtde) as qtde,
			SUM (tbl_faturamento_item.qtde_inspecionada) as qtde_inspecionada,
			SUM (tbl_faturamento_item.qtde * tbl_faturamento_item.preco) as total,
			SUM (tbl_faturamento_item.base_icms) AS base_icms,
			SUM (tbl_faturamento_item.valor_icms) AS valor_icms,
			SUM (tbl_faturamento_item.base_ipi) AS base_ipi,
			SUM (tbl_faturamento_item.valor_ipi) AS valor_ipi
			{$colunaFaturamentoItem}
			FROM tbl_faturamento
			JOIN tbl_faturamento_item USING (faturamento)
			JOIN tbl_peca             USING (peca)
			WHERE tbl_faturamento.fabrica = $login_fabrica
				AND   tbl_faturamento.faturamento  = $faturamento
			GROUP BY
				tbl_peca.peca,
				tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_peca.devolucao_obrigatoria,
				tbl_faturamento.transportadora, 
				tbl_faturamento.transp, 
				tbl_faturamento.qtde_volume, 
				tbl_peca.produto_acabado,
				tbl_peca.ipi,
				tbl_faturamento_item.aliq_icms,
				tbl_faturamento_item.aliq_ipi,
				tbl_faturamento_item.preco
				{$colunaFaturamentoItem}
				{$group_by}
			ORDER BY tbl_peca.referencia";

	$resX = pg_query ($con,$sql);

	$notas_fiscais=array();
	$qtde_peca=0;


	echo $cabecalho;
	echo $topo;

	$total_base_icms  = 0;
	$total_valor_icms = 0;
	$total_base_ipi   = 0;
	$total_valor_ipi  = 0;
	$total_nota       = 0;
	$aliq_final       = 0;

	for ($x = 0 ; $x < pg_num_rows ($resX) ; $x++) {

		$peca                = pg_fetch_result ($resX,$x,'peca');
		$peca_referencia     = pg_fetch_result ($resX,$x,'referencia');
		$peca_descricao      = pg_fetch_result ($resX,$x,'descricao');
		$ipi                 = pg_fetch_result ($resX,$x,'ipi');
		$peca_produto_acabado= pg_fetch_result ($resX,$x,'produto_acabado');
		$peca_devolucao_obrigatoria = pg_fetch_result ($resX,$x,'devolucao_obrigatoria');
		$aliq_icms           = pg_fetch_result ($resX,$x,'aliq_icms');
		$aliq_ipi            = pg_fetch_result ($resX,$x,'aliq_ipi');
		$peca_preco          = pg_fetch_result ($resX,$x,'preco');

		if (in_array($login_fabrica, array(158,177))) {
			$faturamento_item = pg_fetch_result($resX, $x, "faturamento_item");
		}

		$base_icms           = pg_fetch_result ($resX,$x,'base_icms');
		$valor_icms          = pg_fetch_result ($resX,$x,'valor_icms');
		$base_ipi            = pg_fetch_result ($resX,$x,'base_ipi');
		$valor_ipi           = pg_fetch_result ($resX,$x,'valor_ipi');

		$total               = pg_fetch_result ($resX,$x,'total');
		$peso                = pg_fetch_result ($resX,$x,'peso');
		$qtde                = pg_fetch_result ($resX,$x,'qtde');
		$qtde_inspecionada   = pg_fetch_result ($resX,$x,'qtde_inspecionada');


		if ($qtde==0)
			$peca_preco       =  $peca_preco;
		else
			$peca_preco       =  $total / $qtde;

		$total_item  = $peca_preco * $qtde;

		if (strlen ($aliq_icms)  == 0) $aliq_icms = 0;

		if ($aliq_icms==0){
			$base_icms=0;
			$valor_icms=0;
		}
		else{
			$base_icms  = $total_item;
			$valor_icms = $total_item * $aliq_icms / 100;
		}

		if (strlen($aliq_ipi)==0) $aliq_ipi=0;

		if ($aliq_ipi==0) 	{
			$base_ipi=0;
			$valor_ipi=0;
		}
		else {
			$base_ipi=$total_item;
			$valor_ipi = $total_item*$aliq_ipi/100;
		}

		if ($login_fabrica == 175) {
			$valor_subs_trib = pg_fetch_result($resX, $x, 'valor_subs_trib');
			$base_subs_trib  = pg_fetch_result($resX, $x, 'base_subs_trib');
			$total_valor_st      += $valor_subs_trib;
            $total_valor_st_base += $base_subs_trib;
		}

		$total_base_icms  += $base_icms;
		$total_valor_icms += $valor_icms;
		$total_base_ipi   += $base_ipi;
		$total_valor_ipi  += $valor_ipi;
		$total_nota       += $total_item;


		echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >\n";
		echo "<td align='left'>";
		echo "$peca_referencia";
		echo "</td>\n";
		echo "<td align='left'>$peca_descricao</td>\n";
		if($login_fabrica == 177){
			echo "<td align='center'>$peso</td>\n";
		}
		echo "<td align='center' bgcolor='#E2E7E4'>$qtde</td>\n";
		echo "<td align='right' nowrap>" . number_format ($peca_preco,2,",",".") . "</td>\n";
		echo "<td align='right' nowrap>" . number_format ($total_item,2,",",".") . "</td>\n";
		echo "<td align='right'>$aliq_icms</td>\n";
		echo "<td align='right'>$aliq_ipi</td>\n";
		if ($login_fabrica == 175) {
			echo "<td align='right'>$valor_subs_trib</td>\n";
		}
		if ($qtde==$qtde_inspecionada){
			$cor_qtde = "#CDFED0";
		}else{
			$cor_qtde = "#E2E7E4";
		}
		echo "<td align='center' bgcolor='$cor_qtde'>
				<input type='text'   name='qtde_$x'      id='qtde_$x'      value='$qtde_inspecionada' size='2' maxlength='4'>
				<input type='hidden' name='peca_$x'      value='$peca'>
				<input type='hidden' name='preco_$x'     value='$peca_preco'>
				<input type='hidden' name='icms_$x'      value='$aliq_icms'>
				<input type='hidden' name='ipi_$x'       value='$aliq_ipi'>
				<input type='hidden' name='st_$x'        value='$valor_subs_trib'>
				<input type='hidden' name='qtde_insp_$x' id='qtde_insp_$x' value='$qtde'>";

		if (in_array($login_fabrica, array(158,177))) {
			echo "<input type='hidden' name='faturamento_item_$x' value='$faturamento_item'>";
		}

		echo "</td></tr>\n";
		flush();
	}

	if ($login_fabrica == 142) {
        $notaFiscalSemDevolucao = array();

        $sql_nf = "SELECT tbl_faturamento_item.nota_fiscal_origem
					 FROM tbl_faturamento_item
					 JOIN tbl_faturamento USING(faturamento)
					WHERE tbl_faturamento.fabrica = $login_fabrica
					  AND tbl_faturamento.posto   = $posto
					  AND tbl_faturamento.faturamento=$faturamento
					ORDER BY tbl_faturamento.nota_fiscal";
		$resNF = pg_query ($con,$sql_nf);

		for ($y = 0 ; $y < pg_num_rows ($resNF) ; $y++) {
			array_push($notas_fiscais,pg_fetch_result ($resNF,$y,'nota_fiscal_origem'));
		}

		$notas_fiscais = array_unique($notas_fiscais);

        foreach ($notas_fiscais as $nota_fiscal_sd) {
            $notaFiscalSemDevolucao[] = "'{$nota_fiscal_sd}'";
        }

        $sqlPecaSemDevolucao = "
            SELECT
                tbl_peca.referencia,
                tbl_peca.descricao,
                tbl_faturamento_item.qtde,
                tbl_faturamento_item.preco,
                tbl_faturamento.transp, 
                tbl_faturamento.transportadora,
                COALESCE(tbl_faturamento_item.aliq_icms, 0) AS aliq_icms,
                COALESCE(tbl_faturamento_item.aliq_ipi, 0) AS aliq_ipi,
                COALESCE(tbl_faturamento_item.base_icms, 0) AS base_icms,
                COALESCE(tbl_faturamento_item.valor_icms, 0) AS valor_icms,
                COALESCE(tbl_faturamento_item.base_ipi, 0) AS base_ipi,
                COALESCE(tbl_faturamento_item.valor_ipi, 0) AS valor_ipi
            FROM tbl_os_item
            INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
            INNER JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
            INNER JOIN tbl_faturamento_item ON tbl_faturamento_item.os_item = tbl_os_item.os_item
            INNER JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento AND tbl_faturamento.fabrica = {$login_fabrica}
            INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
            WHERE tbl_faturamento_item.extrato_devolucao IS NULL
            AND tbl_faturamento.nota_fiscal IN (".implode(", ", $notaFiscalSemDevolucao).")
        ";
        $resPecaSemDevolucao = pg_query($con, $sqlPecaSemDevolucao);

        if (pg_num_rows($resPecaSemDevolucao) > 0) {
            while ($pecaSemDevolucao = pg_fetch_object($resPecaSemDevolucao)) {
            	$total_base_icms  += $pecaSemDevolucao->base_icms;
				$total_valor_icms += $pecaSemDevolucao->valor_icms;
				$total_base_ipi   += $pecaSemDevolucao->base_ipi;
				$total_nota       += ($pecaSemDevolucao->preco * $pecaSemDevolucao->qtde);

				if ($pecaSemDevolucao->valor_ipi > 0) {
					$valor_ipi = ($pecaSemDevolucao->preco * $pecaSemDevolucao->qtde) * ($aliq_ipi / 100);
					$total_valor_ipi += $valor_ipi;
				}
               
                echo "
                    <tr style='background-color: #FFF; color: #000; text-align: left; font-size: 10px;' >
                        <td>{$pecaSemDevolucao->referencia}</td>
                        <td>{$pecaSemDevolucao->descricao}</td>
                        <td style='text-align: center;' >{$pecaSemDevolucao->qtde}</td>
                        <td style='text-align: right;' >".number_format($pecaSemDevolucao->preco, 2, ",", ".")."</td>
                        <td style='text-align: right;' >".number_format(($pecaSemDevolucao->preco * $pecaSemDevolucao->qtde), 2, ",", ".")."</td>
                        <td style='text-align: right;' >{$pecaSemDevolucao->aliq_icms}</td>
                        <td style='text-align: right;' >{$pecaSemDevolucao->aliq_ipi}</td>
                        <td style='color: #FF0000; text-align: center; font-weight: bold;' >devolução não obrigatória</td>
                    </tr>
                ";
            }
        }
    }

	echo "</table>\n";

	if ($login_fabrica == 158) {
		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
		echo '<tr>';
		echo "<td>".traduz("Observação")."<br><b>$obs</b></td>";
		echo '</tr>';
		echo '</table>';		
	}

	echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >";
	echo '<tr>';
	echo '<td>Base ICMS<br><b>'      . number_format($total_base_icms,             2, ', ', '.') . '</b></td>';
	echo '<td>Valor ICMS<br><b>'     . number_format($total_valor_icms,            2, ', ', '.') . '</b></td>';
	echo '<td>Base IPI<br><b>'       . number_format($total_base_ipi,              2, ', ', '.') . '</b></td>';
	echo '<td>Valor IPI<br><b>'      . number_format($total_valor_ipi,             2, ', ', '.') . '</b></td>';
	if ($login_fabrica == 175) {
		echo '<td>Base ST<br><b>'       . number_format($total_valor_st_base,      2, ', ', '.') . '</b></td>';
		echo '<td>Valor ST<br><b>'      . number_format($total_valor_st,           2, ', ', '.') . '</b></td>';
	}
	
	if ($login_fabrica == 175) {
		echo '<td>Total da Nota<br><b> ' . number_format($total_nota+$total_valor_ipi+$total_valor_st, 2, ', ', '.') . '</b></td>';
	} else {
		echo '<td>Total da Nota<br><b> ' . number_format($total_nota+$total_valor_ipi, 2, ', ', '.') . '</b></td>';
	}

	if($login_fabrica == 177){
		echo '<td>'.traduz("Volume").'<br><b>'      . $qtde_volume . '</b></td>';
		echo '<td>'.traduz("Transportadora").'<br><b>'      . $nome_transportadora . '</b></td>';
	}

	echo '</tr>';
	echo '</table>';

	echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='650' >\n";
	echo "<tr>\n";
	echo "<td><h4><center>".traduz("Nº da NF de Devolução:")."$nota_fiscal</center></h4></td>\n";
	echo "</tr>";

	if ($login_fabrica == 50 or $login_fabrica == 6) { // HD 62342

		$sql_nf = "SELECT tbl_faturamento_item.nota_fiscal_origem
					 FROM tbl_faturamento_item
					 JOIN tbl_faturamento USING(faturamento)
					WHERE tbl_faturamento.fabrica = $login_fabrica
					  AND tbl_faturamento.posto   = $posto
					  AND tbl_faturamento.faturamento=$faturamento
					ORDER BY tbl_faturamento.nota_fiscal";
		$resNF = pg_query ($con,$sql_nf);
		for ($y = 0 ; $y < pg_num_rows ($resNF) ; $y++) {
			array_push($notas_fiscais,pg_fetch_result ($resNF,$y,'nota_fiscal_origem'));
		}
		$notas_fiscais = array_unique($notas_fiscais);
		asort($notas_fiscais);
		if (count($notas_fiscais)>0){
			echo "<tr>";
			echo "<td><h4><center>".traduz("Referente as NFs. "). implode(", ",$notas_fiscais) . "</center></h4></td>\n";
			echo "</tr>";
		}

	}
		$devolucao_inspecionada = false;
		if ($login_fabrica == 161) {
			$sqlInspecao = "SELECT SUM( tbl_faturamento_item.qtde - CASE WHEN tbl_faturamento_item.qtde_inspecionada IS NULL THEN 0 ELSE tbl_faturamento_item.qtde_inspecionada END )
				FROM tbl_faturamento
				JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
				WHERE tbl_faturamento.fabrica   = $login_fabrica
				AND tbl_faturamento.faturamento = $faturamento
				AND (tbl_faturamento_item.qtde - CASE WHEN tbl_faturamento_item.qtde_inspecionada IS NULL THEN 0 ELSE tbl_faturamento_item.qtde_inspecionada END)>0";
			$resInspecao = pg_query ($con,$sqlInspecao);

			$qtde_faltante_inspecao = trim(pg_fetch_result($resInspecao,0,0));

			if ($qtde_faltante_inspecao == 0 || empty($qtde_faltante_inspecao)) {
				$devolucao_inspecionada = true;
			}

		}


	if (strlen($conferencia)>0 AND strlen($cancelada)==0){
		echo "<tr>\n";
		echo "<td><center>";
		echo "<h5 style='color:#0000FF'>".traduz("Nota Fiscal conferida em $conferencia")."</h5>";

		if ($devolucao_concluida != 't' && !$devolucao_inspecionada){
			echo "<h5 style='color:#7A7C85'>".traduz("Devolução Parcial")."</h5>";
		}else{
			echo "<h5 style='color:#007900'>".traduz("Devolução Completa")."</h5>";
		}
		echo "</center></td>";
		echo "</tr>";
	}elseif(strlen($cancelada)>0){
		echo "<tr>\n";
		echo "<td><center><h5 style='color:#FF0000'>".traduz("Nota Fiscal cancelada em %",null,null,[$cancelada])."</h5></center></td>";
		echo "</tr>";
	}

	echo "	<input type='hidden' name='qtde_linhas' value='$x'>
			<input type='hidden' name='faturamento' value='$faturamento'>
			<input type='hidden' name='btn_acao'    value=''>";
	if ($login_admin != 861) {
	if(strlen($cancelada)==0 AND $devolucao_concluida!='t'){
		if ($login_fabrica==50){ // HD 62366 03/03/2006
			echo "<tr>\n";
			echo "<td><h4><center>";
			echo "<input type='button' name='preencher_qtde' value='Preenchimento Automático' onClick=\"javascript:
				for (i=0;i<$x;i++){
					document.getElementById('qtde_'+i).value = document.getElementById('qtde_insp_'+i).value;
				}
			\"></center></h4></td>\n";
			echo "</tr>";
		}
		echo "<tr>\n";
		echo "<td><h4><center>";
		echo "<input type='button' name='gravar' value='Gravar/Conferência' onClick=\"javascript:
		if (this.form.btn_acao.value == ''){
			this.form.btn_acao.value = 'gravar_conferencia';
			this.form.submit();
		}else{
			alert('Aguarde submissão');
		}
		\"></center></h4></td>\n";
		echo "</tr>";
	}
	} else {
		echo "";
	}

	if( in_array($login_fabrica, array(11,172)) ) { # HD  284618
		$sql = "SELECT obs
				FROM tbl_faturamento
				WHERE faturamento = $faturamento
				AND   fabrica = $login_fabrica
				AND   obs <> 'Devolução de peças do posto para à Fábrica'
				AND   length(trim(obs)) > 0";
		$res = pg_query($con,$sql);
		echo "<tr><td colspan='100%' align='center'>";
		if(pg_num_rows($res) > 0){
			echo "<h5 style='font-size: 15px'>Observação:</h5>";
			if(strlen($cancelada)==0){
				echo "<textarea name='obs' cols='60' rows='6'>".pg_fetch_result($res,0,'obs')."</textarea>";
			}else{
				echo "<div>".pg_fetch_result($res,0,'obs')."</div>";
			}
		}else{
			if(strlen($cancelada)==0){
				echo "<h5 style='font-size: 15px'>Observação:</h5>";
				echo "<div id='resp_grava_obs'></div>"; //HD 348716
				echo "<textarea name='obs' id='obs' cols='60' rows='6'></textarea> <br />";
				echo "<input type='button' value='Gravar Observação' onclick='gravaObs($faturamento)'>";
			}
		}
		if(strlen($cancelada)==0  and $devolucao_concluida =='t' ){
			echo "<br/><input type='button' name='gravar' value='Gravar/Conferência' onClick=\"javascript:
				if (this.form.btn_acao.value == ''){
					this.form.btn_acao.value = 'gravar_obs';
					this.form.submit();
				}else{
					alert('Aguarde submissão');
				}
				\">";
		}
		echo "</td></tr>";
	}


	if ($login_fabrica <> 50){
		if ($devolucao_concluida!='t' AND strlen($cancelada)==0){
			$table = (in_array($login_fabrica, array(6,151,153)) || isset($usaLGR)) ? "tbl_faturamento_item" : "tbl_faturamento";
			$sql = "SELECT tbl_extrato.extrato, tbl_extrato.admin_lgr
					FROM $table
					JOIN tbl_extrato ON tbl_extrato.extrato = $table.extrato_devolucao
					WHERE $table.faturamento = $faturamento
					AND tbl_extrato.fabrica = $login_fabrica";

			$resX = pg_query ($con,$sql);
			if (pg_num_rows ($resX)>0){
				$extrato    = pg_fetch_result ($resX,0,'extrato');
				$admin_lgr  = pg_fetch_result ($resX,0,'admin_lgr');
				if ($login_admin != 861) {
					if (strlen($admin_lgr)==0){
						echo "<tr><td>&nbsp;</td></tr>\n";
						echo "<tr style='background-color:#D9E8FF'>\n";
						echo "<td><h5 style='color:#39842F'><center>
						<input type='hidden' name='extrato' value='$extrato'>
						<input type='button' name='gravar' value='".traduz("Liberar Provisoriamente")."' onClick=\"javascript:
						if (this.form.btn_acao.value == ''){
							this.form.btn_acao.value = 'liberar_provisorio';
							this.form.submit();
						}else{
							alert('Aguarde submissão');
						}
						\"></center></h5><b>*Liberando provisoriamente, este Posto Autorizado poderá visualizar o próximo extrato mesmo sem a confirmação de recebimento das Notas de Devolução do mês anterior.<br>** Não é necessário liberar em todas as Notas de	Devolução.</b></td>\n";
						echo "</tr>";
					}else{
						echo "<tr><td>&nbsp;</td></tr>\n";
						echo "<tr style='background-color:#E4FFD9'>\n";
						echo "<td><center>
						<h3>Extrato $extrato liberado provisoriamente</h3>
						<h5 style='color:#39842F'>
						<input type='hidden' name='extrato' value='$extrato'>
						<input type='button' name='gravar' value='Bloquear Visualização do Extrato'			onClick=\"javascript:
						if (this.form.btn_acao.value == ''){
						this.form.btn_acao.value = 'bloquear_provisorio';
						this.form.submit();
						}else{
						alert('Aguarde submissão');
						}
						\"></center></h45><b>* Bloqueando, o Posto Autorizado não poderá visualizar o próximo extrato sem a confirmação do recebimento das Notas de Devolução		referente a este extrato.</b></td>\n";
						echo "</tr>";
					}
				}
			}
		}
	}

	echo "</table>";
	echo "</form>";

	exit;
}
########################################################################################################


//include "cabecalho.php";

include "cabecalho_new.php";

$plugins = array(
	"multiselect",
	"datepicker",
	"shadowbox",
	"alphanumeric",
	"autocomplete",
	"mask",
	"dataTable"
);

include "plugin_loader.php";

?>
<style type="text/css">

.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
	}
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10PX	;
	font-weight: bold;
	border: 1px solid;
	background-color: #D9E2EF
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #FFFFFF
}
.quadro{
	border: 1px solid #596D9B;
	width:450px;
	height:50px;
	padding:10px;

}
.botao {
		border-top: 1px solid #333;
	        border-left: 1px solid #333;
	        border-bottom: 1px solid #333;
	        border-right: 1px solid #333;
	        font-size: 13px;
	        margin-bottom: 10px;
	        color: #0E0659;
		font-weight: bolder;
}
.Titulo {
	text-align: center;
	font-family: Arial, Verdana, Tahoma, Geneva, Helvetica, sans-serif;
	font-size: 14px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.inpu{
	border:1px solid #666;
	font-size:12px;
}
.butt{
	border:1px solid #666;
	background-color:#ccc;
	font-size:12px;
}

.mensagem {
    width: 600px;
    margin: 0 auto;
    margin-top: 20px;
    margin-bottom: 20px;
    text-align: center;
    padding: 10px 5px;
    font-size: 10pt;
}

.msg-info {
    border: 1px solid #596D9B;
    background-color: #E6EEF7;
}

#sb-body {
	background: white url() !important;
}

</style>






<? include "javascript_pesquisas.php"; ?>

<script type="text/javascript">

	function solicitaPostagemPosto(extrato) {
    
	    Shadowbox.open({
	            content :   "solicitacao_postagem_positron.php?extrato="+ extrato,
	            player  :   "iframe",
	            title   :   "Autorização de Postagem",
	            width   :   900,
	            height  :   500
	    }); 
	}

function retorna_posto(retorno){
	    $("#posto_codigo").val(retorno.codigo);
		$("#descricao_posto").val(retorno.nome);
	}

	$(function() {
		
		//$(".date").datepick();
		Shadowbox.init();
		$.datepickerLoad(Array("data_final", "data_inicial"));

		$("#nota_devolucao").numeric();

		$("span[rel=lupa]").click(function () {
			$.lupa($(this));
		});

		$("#estado").multiselect({
	        selectedText: "selecionados # de #"
	    });

	    $("#inspetor").multiselect({
	        selectedText: "selecionados # de #"
	    });


		//$(".date").maskedinput("99/99/9999");
	

		/*$("a[rel='ajuda'], span[rel='ajuda']").Tooltip({
			track: true,
			delay: 0,
			showURL: false,
			opacity: 0.85,
			showBody: " - ",
			extraClass: "ajuda"
		});*/

		function formatItem(row) {
			return row[2] + " - " + row[1];
		}

		function formatResult(row) {
			return row[0];
		}

		/*$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[2];}
		});

		$("#posto_codigo").result(function(event, data, formatted) {
			$("#posto_nome").val(data[1]) ;
		});

		$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[1];}
		});

		$("#posto_nome").result(function(event, data, formatted) {
			$("#posto_codigo").val(data[2]) ;
		});
*/
		$('#inverter_chk_email').change(function() {
			var chk_status = $(this).attr('checked');
			$('.checkable').attr('checked',chk_status);
		});
	});


	var checkflag = "false";
	function check(field) {
	    if (checkflag == "false") {
	        for (i = 0; i < field.length; i++) {
	            field[i].checked = true;
	        }
	        checkflag = "true";
	        return true;
	    }
	    else {
	        for (i = 0; i < field.length; i++) {
	            field[i].checked = false;
	        }
	        checkflag = "false";
	        return true;
	    }
	}

	function AbrirJanelaObs (extrato) {
		var largura  = 500;
		var tamanho  = 300;
		var lar      = largura / 2;
		var tam      = tamanho / 2;
		var esquerda = (screen.width / 2) - lar;
		var topo     = (screen.height / 2) - tam;
		var link = "extrato_status.php?extrato=" + extrato;
		window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=no, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
	}

	function verNota(nota_fiscal){

		Shadowbox.open({
			content:"<?=$PHP_SELF?>?pop_up=sim&nota_fiscal=" + nota_fiscal,
            player: "iframe",
            width:  680,
            height: 500
        });
	}
</script>


<?php



if (count($msg_erro["msg"]) > 0) {
?>
    <div class="alert alert-error">
		<h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}
?>

<div class="row">
	<b class="obrigatorio pull-right">  * <?=traduz("Campos obrigatórios")?></b>
</div>


<?
if(strlen($msg)>0){
	echo "<center><b style='font-size:12px;background-color:#dfdfdf'>eeee$msg</b></center><br>";
}

echo "<form method='POST' name='frm_extrato' class='form-search form-inline tc_formulario'>";

?>
	<div class='titulo_tabela '><?=traduz("Parâmetros de Pesquisa")?></div><br>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='codigo_posto'><?=traduz("Código Posto")?></label>
				<div class='controls controls-row'>
					<div class='span7 input-append'>
							<input class="span12" type="text" name="posto_codigo" id="posto_codigo" value="<? echo $posto_codigo ?>">
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="codigo" />
					</div>
				</div>
			</div>
		</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("posto", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='descricao_posto'><?traduz("Nome Posto")?></label>
				<div class='controls controls-row'>
					<div class='span12 input-append'>
							<input class="span12" type="text" name="descricao_posto" id="descricao_posto" value="<? echo $descricao_posto ?>">
						<span class='add-on' rel="lupa"><i class='icon-search' ></i></span>
						<input type="hidden" name="lupa_config" tipo="posto" parametro="nome" />
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
			<div class='span4'>
				<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
					<label class='control-label' for='data_inicial'><?=traduz("Data Inicial")?></label>
					<div class='controls controls-row'>
						<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_inicial; ?>" class="span12">
						</div>
					</div>
				</div>
			</div>
		<div class='span4'>
			<div class='control-group <?=(in_array("data", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='data_final'><?=traduz("Data Final")?></label>
				<div class='controls controls-row'>
					<div class='span4'>
							<h5 class='asteristico'>*</h5>
							<input type="text" name="data_final" id="data_final"  size="12" maxlength="10" value="<? if (strlen($data_final) > 0) echo $data_final; ?>" class="span12">
					</div>
				</div>
			</div>
		</div>
		<div class='span2'></div>
	</div>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("nota_devolucao", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='nota_devolucao'><?=traduz("Nota Devolucao")?></label>
				<div class='controls controls-row'>
					<div class='span10'>
						<input type="text" name="nota_devolucao" id="nota_devolucao"  value="<?=$nota_devolucao?>" class="span12">
					</div>
				</div>
			</div>
		</div>
		<?php if($login_fabrica == 3){ ?>
		<div class='span4'>
			<div class='control-group <?=(in_array("nota_devolucao", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='nota_devolucao'><?=traduz("Estado")?></label>
				<div class='controls controls-row'>
					<div class='span10'>
						<select name="estado[]" id="estado" multiple="multiple" class='span12'>
							<?php 
								foreach ($array_estado as $k => $v) {

									if(in_array($k, $estado)){
										$selected = " selected ";
									}else{
										$selected = " ";
									}

									echo '<option value="'.$k.'"'.($posto_estado == $k ? ' selected="selected"' : '')." $selected >".$v."</option>\n";
								} 
							?>
						</select>
					</div>
				</div>
			</div>
		</div>
		<?php } ?>
	</div>
	<?php if($login_fabrica == 3){ ?>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<div class='control-group <?=(in_array("nota_devolucao", $msg_erro["campos"])) ? "error" : ""?>'>
				<label class='control-label' for='nota_devolucao'><?=traduz("Inspetor Responsável")?></label>
				<div class='controls controls-row'>
					<div class='span10'>
						<select name="inspetor[]" id="inspetor" multiple="multiple" class='span12'>
							<?php 
								$sql = "SELECT admin, login, nome_completo 
										FROM tbl_admin 
										WHERE admin_sap = 't' 
										AND ativo = 't' 
										AND fabrica = $login_fabrica 
										ORDER BY nome_completo ";
								$res = pg_query($con, $sql);
								for($i=0; $i<pg_num_rows($res); $i++){
									$admin 			= pg_fetch_result($res, $i, 'admin');
									$login 			= pg_fetch_result($res, $i, 'login');
									$nome_completo  = substr(pg_fetch_result($res, $i, 'nome_completo'), 0, 25);

									if(in_array($admin, $inspetor)){
										$selected = " selected ";
									}else{
										$selected = " ";
									}

									echo "<option value='$admin' $selected >$nome_completo</option>";

								}


							?>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php } ?>
	<div class='row-fluid'>
		<div class='span2'></div>
		<div class='span4'>
			<label class="checkbox">
				<input type="checkbox" name='filtroObrigatorio' value='checked' <? echo $filtroObrigatorio; ?>> <?=traduz("Notas de Retorno Obrigatório")?>
			</label>
			<br>
			<label class="checkbox">
				<input type="checkbox" name='filtroNaoObrigatorio' value='checked' <? echo $filtroNaoObrigatorio; ?> > <?=traduz("Notas de Retorno Não Obrigatório")?>
			</label>	
			<br>
			</div>
			<div class='span4'>
			<label class="checkbox">
				<input type="checkbox"  name='filtroTodos' value='checked' <? echo $filtroTodos; ?>> <?=traduz("Notas Com Recebimento Pendente")?>
			</label>	
			<br>
			<label class="checkbox">
				<input type="checkbox"  name='filtroRecebidos' value='checked' <? echo $filtroRecebidos; ?> > <?=traduz("Notas Recebidas")?>
			</label>			
		</div>
	</div>
<p><br/>
<button type='button' class='btn' onclick="javascript: document.frm_extrato.btnacao.value='filtrar' ; document.frm_extrato.submit() "><?=traduz("Filtrar")?></button><br></td></tr>

	<input type='hidden' name='btnacao'>
	</p><br/>

</form>


<?php 
	if(count($msg_erro)==0 and $btn_acao == 'filtrar'){
		if ($btn_acao=='filtrar' and ($filtroRecebidos or $filtroTodos or $posto_codigo or $nota_devolucao)) {

			$qtde_notas= @pg_num_rows($res_notas);
			?>

			<?php if ($login_fabrica == 50): // HD 107532 ?>
				<div class="mensagem msg-info">
					<p> <?=traduz("Para enviar e-mail cobrando uma Nota Fiscal a um Posto, selecione a nota fiscal desejada e clique no botão")?><em class='btn'><?=traduz("Enviar E-mails")?></em>. </p>
				</div>
			<?php endif; // fim HD 107532
			##### LEGENDAS - INÍCIO #####
			 ?>
			<br>
				<center>
					<div>
						<table cellspacing='1' width='100%' cellpadding='3' class='Tabela' style='color:white;font-size:10px;font-weight:bold;text-align: left'>
							<tr height='18'>
								<td width='18' ><img src='imagens/status_verde.gif' width='10' valign='absmiddle'/></td>
								<td width='380'>&nbsp;<?=traduz("Nota conferida")?></td>
								<td width='18'><img src='imagens/status_vermelho.gif' width='10' valign='absmiddle'/></td>
								<td width='380'>&nbsp;<?=traduz("Nota cancelada")?></td>
							</tr>
							<tr height='18'>
								<td width='18'><img src='imagens/status_amarelo.gif' width='10' valign='absmiddle'/></td>
								<td width='150'>&nbsp;<?=traduz("Conferência parcial da NF")?></td>
								<td width='18'><img src='imagens/status_cinza.gif' width='10' valign='absmiddle'/></td>
								<td width='150'>&nbsp;<?=traduz("Aguardando Conferência")?></td>
							</tr>
						</table>
					</div>
				</center>
			<br>
			<?php

			if($login_fabrica == 3){
				$colspan = "16";
			}elseif($login_fabrica == 153){
				$colspan = "10";
			}else{
				$colspan = "9";
			}

			$lista  = "";

			$lista .= "<form method='post' name='frm_notas' action='$PHP_SELF'>";

			$lista .=  "<center><table class='table table-striped table-bordered table-fixed'>";
			echo "<thead>";
			$lista .=  "<tr class='titulo_coluna'><th colspan='$colspan'>".traduz("Notas Fiscais de Devolução")."</th></tr>";

			$lista .=  "<tr  class='titulo_coluna'>";
			// HD 107532
			if ( $login_fabrica == 50 ) {
				$lista .= '<td><input type="checkbox" id="inverter_chk_email" /></td>';
			}
			// fim HD 107532
			$lista .=  '<th class="tac"></th>';
			if ( in_array($login_fabrica, array(11,172)) ) {
				$lista .=  '<th class="tac"></th>';
			}
			$lista .=  '<th class="tac">'.traduz("CÓDIGO").'</th>';
			$lista .=  '<th class="tac">'.traduz("RAZÃO SOCIAL").'</th>';

			if($login_fabrica == 3){
				$lista .=  '<th class="tac">'.traduz("ESTADO").'</th>';
				$lista .=  '<th class="tac">'.traduz("INSP RESPONSÁVEL").'</th>';
			}

			$lista .=  '<th class="tac">'.traduz("EXTRATO").'</th>';
			$lista .=  '<th class="tac">'.traduz("DATA").'<br>'.traduz("EMISSÃO").'</th>';
			
			if($login_fabrica == 3){
				$lista .=  '<th class="tac" nowrap >'.traduz("PEÇA REFERÊNCIA").'</th>';
				$lista .=  '<th class="tac">'.traduz("PEÇA DESCRIÇÃO").'</th>';
				$lista .=  '<th class="tac">'.traduz("PEÇA QTDE").'</th>';
			}

			$lista .=  '<th class="tac">'.traduz("NOTA").'<br>'.traduz("FISCAL").'</th>';
			if($login_fabrica == 3){
				$lista .=  '<th class="tac" nowrap >'.traduz("DATA ÚLTIMO COMPROVANTE ENVIO").'</th>';
				$lista .=  '<th class="tac" nowrap >'.traduz("ANEXO").'</th>';
				$lista .=  '<th class="tac" nowrap >'.traduz("COMPROVANTE LGR").'</th>';				
			}		
			$lista .=  '<th class="tac">'.traduz("DATA").'<br>'.traduz("CONFERENCIA").'</th>';
			$lista .=  '<th class="tac">'.traduz("VALOR").'<br>'.traduz("NOTA").'</th>';

			if($login_fabrica != 3){
				$lista .=  '<th class="tac">'.traduz("CANCELADA").'</th>';
			}

			if($login_fabrica == 153){
				$lista .= '<th class="tac">'.traduz("SOLICITAÇÃO DE POSTAGEM").'</th>';
				$lista .= '<th class="tac">'.traduz("Rastreio").'</th>';
			}			

			$lista .=  '</tr>';
			echo "</thead>";

			if ($login_fabrica == 151) {
				unset($arr_nf);
			}

			for ($i=0;$i<$qtde_notas;$i++){
				$faturamento			= pg_fetch_result($res_notas,$i,'faturamento');
				$emissao				= pg_fetch_result($res_notas,$i,'emissao');
				$conferencia			= pg_fetch_result($res_notas,$i,'conferencia');
				$cancelada				= pg_fetch_result($res_notas,$i,'cancelada');
				$devolucao_concluida	= pg_fetch_result($res_notas,$i,'devolucao_concluida');
				$nota_fiscal			= pg_fetch_result($res_notas,$i,'nota_fiscal');
				$nf_total_nota			= pg_fetch_result($res_notas,$i,'total_nota');
				$nf_valor_ipi			= pg_fetch_result($res_notas,$i,'valor_ipi');
	//			$nf_total_nota			= $nf_total_nota + $nf_valor_ipi;
				$nf_total_nota			= number_format($nf_total_nota,2,',','.');
				$extrato_devolucao		= pg_fetch_result($res_notas,$i,'extrato_devolucao');
				if ($login_fabrica == 151) {
					if (isset($arr_nf)) {
						if (in_array($nota_fiscal, $arr_nf)) {
							continue;
						} else {
							$arr_nf[] = $nota_fiscal;
						}
					} else {
						$arr_nf[] = $nota_fiscal;
					}
				}
				$nome_posto_1			= pg_fetch_result($res_notas,$i,'nome_posto');
				$cod_posto				= pg_fetch_result($res_notas,$i,'posto');
				$codigo_posto_1			= pg_fetch_result($res_notas,$i,'codigo_posto');
				$admin_lgr				= pg_fetch_result($res_notas,$i,'admin_lgr');
				if($login_fabrica == 3){
					$contato_estado   = pg_fetch_result($res_notas,$i,'contato_estado');
					$nome_completo 	  = pg_fetch_result($res_notas,$i,'nome_completo');

					
					$temComprovante = $tDocs->getDocumentsByRef($extrato_devolucao,'comprovantelgr')->attachListInfo;

					$tDocs->setContext('lgr');
					if ($tDocs->getDocumentsByRef($faturamento)->attachListInfo) {
						//$anexo = $tDocs->url;
						$anexo = $tDocs->getDocumentsByRef($faturamento)->attachListInfo;
					}else{
						$anexo = null;
					}

					$pecas = "";
					$sql_pecas = " SELECT
									tbl_peca.referencia,
									tbl_peca.descricao,
									tbl_faturamento_item.qtde
									FROM tbl_faturamento
									JOIN tbl_faturamento_item USING (faturamento)
									JOIN tbl_peca USING (peca)
									WHERE tbl_faturamento.fabrica = $login_fabrica
									AND tbl_faturamento.faturamento = $faturamento
									GROUP BY
									tbl_peca.referencia,
									tbl_peca.descricao,
									tbl_faturamento_item.qtde
									ORDER BY tbl_peca.referencia";
					$res_pecas = pg_query($con, $sql_pecas);
					$pecas_referencia = "";
					$pecas_descricao = "";
					$pecas_qtde = "";
					$pecas = "";
					for($a= 0; $a<pg_num_rows($res_pecas); $a++){
						$referencia = pg_fetch_result($res_pecas, $a, referencia);
						$descricao 	= pg_fetch_result($res_pecas, $a, descricao);
						$qtde 	= pg_fetch_result($res_pecas, $a, qtde);

						$pecas_referencia .= "$referencia <br>";
						$pecas_descricao .= "$descricao <br>";
						$pecas_qtde .= "$qtde <br>";
						

						//$pecas .= $referencia . " &nbsp&nbsp - &nbsp&nbsp ". substr($descricao, 0, 30). " &nbsp&nbsp - &nbsp&nbsp ". $qtde . "<Br>";
					}

					$pecas .= "<td nowrap><center>$pecas_referencia</center></td>";
					$pecas .= "<td nowrap><center>$pecas_descricao</center></td>";
					$pecas .= "<td nowrap><center>$pecas_qtde</center></td>";

				}


				if($login_fabrica == 153){
				$subselect = ",
							(select conhecimento from tbl_faturamento_correio where tbl_faturamento_correio.numero_postagem = tbl_extrato.protocolo and conhecimento <> ''  limit 1) as conhecimento";
			}

				if ( empty( $extrato_devolucao ) ) { // HD 738953

					$sql = "SELECT 
							DISTINCT extrato_devolucao
							$subselect
							FROM tbl_faturamento_item
							INNER JOIN tbl_extrato on tbl_extrato.extrato = tbl_faturamento_item.extrato_devolucao
							
							WHERE tbl_faturamento_item.faturamento = $faturamento";

					$res2 = pg_query($con,$sql);

					if ( pg_num_rows($res2) ) {
						$extrato_devolucao = pg_result($res2,0,0);

						if($login_fabrica == 153){
							$conhecimento = pg_fetch_result($res2, 0, conhecimento);
						}
					}
				}

				$cor = ($i%2==0) ? '#e9e9e9' : '#ffffff';

				if (strlen($admin_lgr)>0){
					$admin_lgr = "*";
				}

				$lista .= "<tr class='Conteudo' height='20' bgcolor='$cor' align='left'  >";
				if( in_array($login_fabrica, array(11,172)) ) {
					$lista .= "<td align='center'><input type='checkbox' name='faturamento_$i' value='$faturamento'></td>";
				}

				/**
				 * Exibir checkbox para marcar as NFs que o usuário deseja cobrar do posto.
				 * Somente para Colormaq
				 * HD 107532
				 *
				 * @author Augusto Pascutti <augusto.pascutti@telecontrol.com.br>
				 */
				if ( $login_fabrica == 50 ) {
					$lista .= '<td> <input type="checkbox" name="nf_enviar_emails[]" class="checkable" value="'.$nota_fiscal.'" /> </td>';
				}

				if ($login_fabrica == 161) {
					$sqlInspecao = "SELECT SUM( tbl_faturamento_item.qtde - CASE WHEN tbl_faturamento_item.qtde_inspecionada IS NULL THEN 0 ELSE tbl_faturamento_item.qtde_inspecionada END )
						FROM tbl_faturamento
						JOIN tbl_faturamento_item ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
						WHERE tbl_faturamento.fabrica   = $login_fabrica
						AND tbl_faturamento.faturamento = $faturamento
						AND (tbl_faturamento_item.qtde - CASE WHEN tbl_faturamento_item.qtde_inspecionada IS NULL THEN 0 ELSE tbl_faturamento_item.qtde_inspecionada END)>0";
					$resInspecao = pg_query ($con,$sqlInspecao);

					$qtde_faltante_inspecao = trim(pg_fetch_result($resInspecao,0,0));

				}

				// fim HD 107532
				if (strlen($conferencia)>0 AND ($devolucao_concluida=='t' || ($login_fabrica == 161 && ($qtde_faltante_inspecao == 0 || empty($qtde_faltante_inspecao)))) AND strlen($cancelada)==0){
					$lista .= "<td nowrap  align='center'><img src='imagens/status_verde.gif' alt='NF conferida em $conferencia'></td>";
				}elseif (strlen($cancelada)>0){
					$lista .= "<td nowrap  align='center'><img src='imagens/status_vermelho.gif' alt='NF cancelada em $cancelada'></td>";
				}elseif (strlen($conferencia)>0){
						$lista .= "<td nowrap  align='center'><img src='imagens_admin/status_amarelo.gif' alt='Conferência parcial da NF'></td>";
				}else{
						$lista .= "<td nowrap  align='center'><img src='imagens/status_cinza.gif' alt='Aguardando conferência'></td>";
				}

				//$lista .= "<td nowrap  align='left' title='$codigo_posto_1 - $nome_posto_1'><a href='$PHP_SELF?btnacao=filtrar&posto_codigo=$codigo_posto_1&posto_nome=$nome_posto_1&filtroTodos=$filtroTodos&filtroRecebidos=$filtroRecebidos'>$codigo_posto_1 - $nome_posto_1</a></td>";
				$lista .= "<td nowrap  align='left' title='$codigo_posto_1 - $nome_posto_1'>$codigo_posto_1 </td>";
				$lista .= "<td nowrap align='left'>$nome_posto_1</td>";

				if($login_fabrica == 3){
					$lista .= "<td nowrap><center>$contato_estado</center></td>";
					$lista .= "<td nowrap align='left'>$nome_completo</td>";					
				}

				if (strlen($admin_lgr)>0){
					$lista .= "<td nowrap align='center'><span rel='ajuda' title=".traduz('Extrato liberado provisoriamente. O PA poderá visualizar o próximo extrato sem a confirmação de recebimento das notas de devolução desse mês.').">$extrato_devolucao*</span></td>";
				}else{
					$lista .= "<td nowrap  align='center' title=".traduz('Esta nota é referente ao extrato')."$extrato_devolucao'>$extrato_devolucao </td>";
				}
				$lista .= "<td nowrap  align='center' title=".traduz('Nota emitida em')."$emissao>$emissao</td>";

				if($login_fabrica == 3){
					$lista .= $pecas;
				}

				$lista .= "<td nowrap align='center'><center><a href='javascript:verNota($faturamento)'>$nota_fiscal</a></center></td>";
				if($login_fabrica == 3){

					/*$ultimo_arr = $anexo;
					if(count($ultimo_arr)>0){
						$ultimo_arr = array_pop($ultimo_arr);
						$data_anexo = $ultimo_arr['extra']['date'];
					}else{
						$data_anexo = "";
					}*/
					$temComprovante_ultimo = $temComprovante;
					if(count($temComprovante_ultimo)>0){
						$ultimo_arr = array_pop($temComprovante_ultimo);
						$data_comprovante = mostra_data($ultimo_arr['extra']['date']);
					}else{
						$data_comprovante = "";
					}

					$lista .= "<td nowrap ><center>".$data_comprovante."</center></td>";
					
					$lista .= "<td nowrap  align='center'>";
					if (count($anexo) > 0) {
						foreach ($anexo as $k => $vanexo) {
							$linkanexo   = $vanexo["link"];
							$ext = substr($linkanexo,strlen($linkanexo) - 3, 3);
							$ultimo_acesso = $vanexo["extra"]['date'];
							$lk = ($ext == "pdf") ? "<img src='../imagens/pdf_icone.png' width='100' height='100'>" : "<img src='$linkanexo' width='100' height='100'>";
						    $lista .= "<a href='$linkanexo' target='_blank'>$lk</a> <br><br> ";
						}
					    }

							/*if(count($anexo)>0) {
								$lista .= "<a href='$anexo' target='_blank'><img src='$anexo' width='100' height='100'></a>";
							}*/
							$lista .= "</td>";	
							$lista .= "<td nowrap  align='center'><center>";
							if (count($temComprovante) > 0) {
						foreach ($temComprovante as $k => $vComprovante) {
							$linkComprovante   = $vComprovante["link"];
							$ext = substr($linkComprovante,strlen($linkComprovante) - 3, 3);
							$lk = ($ext == "pdf") ? "<img src='../imagens/pdf_icone.png' width='100' height='100'>" : "<img src='$linkComprovante' width='100' height='100'>";
						    $lista .= "<a href='$linkComprovante' target='_blank'>$lk</a> <br><br> ";
						}
					    } 
					$lista .= "</center></td>";
						
				}
				
				$lista .= "<td nowrap  align='center' title='Conferida em $conferencia'>$conferencia</td>";
				$lista .= "<td nowrap  align='right'  title='Valor da Nota: $real . $nf_total_nota'>$nf_total_nota</td>";
				if($login_fabrica != 3){
					$lista .= "<td nowrap  align='center' title='Se estiver data, esta nota foi cancelada'>$cancelada</td>";
				}			

				if($login_fabrica == 153){
					$lista .= "<td nowrap  align='center' title=''><a href='#' onclick='solicitaPostagemPosto($extrato_devolucao)'>Solicitar Autorização de Postagem </a></td>";
					$lista .= "<td nowrap  align='center' title='' ><A HREF='./relatorio_faturamento_correios.php?conhecimento=$conhecimento' rel='shadowbox'>$conhecimento</a></td>";
				}
				$lista .= "</tr>";
			}

			if ($login_fabrica <> 115 and $login_fabrica <> 116){
				$lista .=  "<tr align='left' >";
					$lista .=  "<td bgColor='#6B7EAB' colspan='$colspan' align='left'>&nbsp;";
						if ($login_fabrica <> 50){
							$lista .=  "<font style='font-size:12px;color:#000000'><img  rel='ajuda' src='imagens/help1.gif' width='24' height='16' align='absmiddle' border='0' > <span rel='ajuda' title='Se o extrato estiver como liberado provisoriamente, o PA poderá visualizar o próximo extrato mesmo sem a Fábrica ter confirmado o recebimento da Nota Fiscal de Devolução'>(*) Extrato liberado provisoriamente</acronym></span> </font>";

							if( in_array($login_fabrica, array(11,172)) ) { # HD 284618
								$lista .=  "<select name='acao' style='font-weight:bold;font-size:12px'>
										<option value='confirmar' selected>CONFIRMAR RECEBIMENTO</option>
										<option value='nao_recebida'>NÃO RECEBIDA (CANCELAR)</option>
									</select>
									<input type='hidden' name='qtde_notas' value='$i'>
									<input type='hidden' name='btn_acao' value=''>
									<input type='button' name='btn' value='Gravar' onClick=\"this.form.btn_acao.value='gravar';this.form.submit()\">
									";
							}
						}
					$lista .=  "</td>";
				$lista .=  "</tr>";
			}

			$lista .=  "</table>";
			$lista .= "</form>";

			if ($qtde_notas==0){
				echo "<div class='alert alert-warning'><h4>".traduz("Nenhum registro encontrado")."</h4></div>";
			}else {
				//echo "<hr><br><b style='font-size:14px'>Postos Que Enviaram Peças Sem Confirmação de Recebimento da Fábrica</b><br>";
				echo "<br><br>";
				echo "</div>";
				echo $lista;
				echo "<br>";

				$jsonPOST = excelPostToJson($_POST);

			?>

			<div id='gerar_excel' class="btn_excel">
				<input type="hidden" id="jsonPOST" value='<?=$jsonPOST?>' />
				<span><img src='imagens/excel.png' /></span>
				<span class="txt"><?=traduz("Gerar Arquivo Excel")?></span>
			</div>
			<?php
			}
		} else {
			echo "<div class='alert alert-warning'><h4>".traduz("Nenhum registro encontrado")."</h4></div>";
			$msg_erro['msg'][] = traduz('Defina os parâmetros obrigatórios para a pesquisa!');
		}
	}

	// HD 107532
	if ( $login_fabrica == 50 && $qtde_notas > 0 ) {
		?>
		<input type="submit" name="enviar_emails" id="enviar_emails" value="Enviar E-mails" />
		<?php
	} else if ( isset($sucesso_envio_email) || isset($erros_envio_email) ) {
		?>
		<div class="mensagem msg-info">
			<?php if ( isset($sucesso_envio_email) ): ?>
			<p>
				<?=traduz("Foram enviados")?> <?php echo $sucesso_envio_email; ?> e-mails.
			</p>
			<?php endif; ?>
			<?php if ( isset($erros_envio_email) && count($erros_envio_email) > 0 ): ?>
			<p>
				<?=traduz("As seguintes notas fiscais não tiveram o e-mail enviado ao posto:")?>
				<ul>
					<?php foreach ($erros_envio_email as $nf): ?>
						<li> <?php echo $nf; ?> </li>
					<?php endforeach; ?>
				</ul>
			</p>
			<?php endif; ?>
		</div>
		<?php
	}
	// fim 107532

######### FIM ##################################################

echo "<br>";
/*if (strlen($msg_erro) > 0) {
	echo "<div style='font:bold 16px Arial; color:#FFFFFF;background-color:red'>";
	echo "$msg_erro";
	echo "</div>";
}*/
?>

<br>

<? include "rodape.php"; ?>
