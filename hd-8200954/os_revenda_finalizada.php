<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if($login_fabrica == 1){
	include("os_revenda_finalizada_blackedecker.php");
	exit;
}

include 'funcoes.php';
include_once('anexaNF_inc.php');    // Dentro do include estão definidas as fábricas que anexam imagem da NF e os parâmetros.


include_once 'class/communicator.class.php';

if (in_array($login_fabrica, array(153,157,165))) {
	$anexaNotaFiscal = true;
}

$msg_erro = "";
if ($login_fabrica == 6) {
	if (strlen($_GET['os_revenda']) == 0 ) {
		$msg_erro = "Sem número de OS....";
	}
}

$btn_acao = trim (strtolower ($_POST['btn_acao']));

if (strlen($_GET['os_revenda']) > 0)
	$os_revenda = trim($_GET['os_revenda']);
if (strlen($_POST['os_revenda']) > 0)
	$os_revenda = trim($_POST['os_revenda']);

/**
 * Rotina para a exclusão de anexo da OS
 **/
if ($_POST['ajax'] == 'excluir_nf') {
	$img_nf = anti_injection($_POST['excluir_nf']);
	//$img_nf = basename($img_nf);

	$excluiu = (excluirNF($img_nf));
	$nome_anexo = preg_replace("/.*\/([rexs]_)?(\d+)([_-]\d)?\..*/", "$1$2", $img_nf);

	if ($excluiu)  $ret = "ok|" . temNF($nome_anexo, 'linkEx') . "|$img_nf|$nome_anexo";
	if (!$excluiu) $ret = 'ko|Não foi possível excluir o arquivo solicitado.';

	exit($ret);
}//	FIM	Excluir	imagem

function ativa_produto($produto, $os, $produto_serie, $posto) {
  global $con, $login_fabrica, $externalId;

  $sql_ativacao = " SELECT referencia, descricao 
                    FROM tbl_produto 
                    WHERE fabrica_i = $login_fabrica 
                    AND ativo IS NOT TRUE 
                    AND produto = $produto
                    AND parametros_adicionais::jsonb->>'ativacao_automatica' = 't'";

  $res_ativacao = pg_query($con, $sql_ativacao);

  if (pg_num_rows($res_ativacao) > 0) {
    $prod_ref  = pg_fetch_result($res_ativacao, 0, 'referencia');
    $prod_desc = pg_fetch_result($res_ativacao, 0, 'descricao');
    $valores_add  = json_encode(array("ativacao_automatica" => "f", "os_ativacao" => "$os"));
    
    $sql_update_ativacao = " UPDATE tbl_produto SET ativo = TRUE, parametros_adicionais = '$valores_add' WHERE produto =  $produto";
    $res_update_ativacao = pg_query($con, $sql_update_ativacao);

    if (strlen(pg_last_error()) > 0) {
      return false;
    } else {
      $data_hj = date("d/m/Y H:i");

      $sql_p = "SELECT tbl_os.sua_os,tbl_posto.nome, tbl_posto_fabrica.codigo_posto 
		FROM tbl_os
		JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
                JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto 
                AND tbl_posto_fabrica.fabrica = $login_fabrica  
		WHERE tbl_os.os = $os 
		AND tbl_posto.posto = $posto 
                AND tbl_posto_fabrica.fabrica = $login_fabrica";
      $res_p = pg_query($con, $sql_p);
      $nome_p = pg_fetch_result($res_p, 0, 'nome');
      $cod_p  = pg_fetch_result($res_p, 0, 'codigo_posto'); 
      $sua_os  = pg_fetch_result($res_p, 0, 'sua_os');

      $assunto = " Ativação do produto $prod_ref - $prod_desc"; 
      $mensagem = "O Produto $prod_ref - $prod_desc, foi ativado na OS $sua_os lançada pelo posto $cod_p - $nome_p com o número de série $produto_serie na data $data_hj. ";
      $email = array('caio.nagorski@britania.com.br', 'jose.pedrini@britania.com.br', 'ricardo.roque@britania.com.br');
  
      $mailTc = new TcComm($externalId);

      $res = $mailTc->sendMail(
          $email,
          utf8_encode($assunto),
          utf8_encode($mensagem),
          'noreply@telecontrol.com.br'
      );
    }
    return true;
  } else {
    return true;
  }  
} 

if ($btn_acao == "explodir" and strlen ($msg_erro) == 0) {
		// executa funcao de explosao

	/**
	* @author William Castro
	*
	* hd-6419065
	*
	* Alteração de status para resolvido e interação automática 
	* do sistema no atendimento informando os números das O.S's.
	*/

	$mensagem = "Nº da Os: " . $preos;
	$numeroOs = $_POST['os_revenda'];
	$status = "Resolvido";

	$sqlVerificaAnexos = "SELECT tdocs, count(*) FROM tbl_tdocs WHERE referencia_id = '" . $_POST['os_revenda'] . "' GROUP BY tdocs;";
	$resAnexos = pg_query($con, $sqlVerificaAnexos);

	$tdocsId = pg_fetch_result($resAnexos, 0, 'tdocs');

	$sqlAtivos = "SELECT situacao FROM tbl_tdocs WHERE tdocs = " . $tdocsId . ";";
	$resAtivos = pg_query($con, $sqlAtivos);

	if (pg_fetch_result($resAtivos, 0, 'situacao') == 'inativo') {
		$msg_erro = 'O anexo da Nota Fiscal é obrigatório';
	}

	if(!empty($preos)) {
		$sqlUpdateInteracao =  "INSERT INTO tbl_hd_chamado_item (posto, comentario, hd_chamado, os, status_item) 
			VALUES ('{$login_posto}', '{$mensagem}', '{$preos}', '{$numeroOs}', '{$status}');";

		$resInteracao = pg_query($con, $sqlUpdateInteracao);

		$sqlUpdateStatus = "UPDATE tbl_hd_chamado 
			SET status = 'Resolvido'
			WHERE fabrica = {$login_fabrica} 
			AND hd_chamado = {$preos}";

		$sqlUpdateStatus = pg_query($con, $sqlUpdateStatus);
	}
	# DESCOBRIR PQ O INSERT N TA PEGANDO, MAS TA PEGANDO. MAS N TA SALVANDO DO JEITO QUE TEM QUE SALVAR.

	if (in_array($login_fabrica, array(153,157,165))) {

		include_once "class/aws/s3_config.php";
		include_once S3CLASS;
	}

    $res = pg_exec ($con,"BEGIN TRANSACTION");
	$sql = "SELECT fn_explode_os_revenda($os_revenda,$login_fabrica)";
	//if ($ip=='201.76.85.4') {echo $sql; exit;}
	$res      = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);


    /**
	 * @author William Castro <william.castro@telecontrol.com.br>
	 *
	 * hd- 6737630
	 *
	 * ativa_produto quando cadastra a OS revenda
	 */

	if (strlen($msg_erro) == 0 && $login_fabrica == 3) {
		
		$get_sua_os = "SELECT sua_os FROM tbl_os_revenda WHERE os_revenda = {$os_revenda}";
		
		$res_sua_os = pg_query($con, $get_sua_os);
		
		$suaOs = pg_fetch_result($res_sua_os, 0, sua_os);

		$query_ativar = "SELECT o.os, o.posto, o.produto, o.serie, o.sua_os
						 FROM tbl_os AS o 
						 WHERE o.sua_os LIKE '{$suaOs}%'
						 AND o.fabrica = {$login_fabrica}";
		
		$res_ativar = pg_query($con, $query_ativar);
 	
		if (strlen(pg_last_error($con)) == 0 && pg_num_rows($res_ativar) > 0) {
		
			for ($i = 0; $i < pg_num_rows($res_ativar); $i++) {

				$res_os      = pg_fetch_result($res_ativar, $i, os);
				$res_posto   = pg_fetch_result($res_ativar, $i, posto);
				$res_produto = pg_fetch_result($res_ativar, $i, produto);
				$res_produto_serie 	 = pg_fetch_result($res_ativar, $i, serie);	

				ativa_produto($res_produto, $res_os, $res_produto_serie, $res_posto);

			    if (!ativa_produto($res_produto, $res_os, $res_produto_serie, $res_posto)) {
					
	                $res = @pg_query ($con,"ROLLBACK TRANSACTION");
	          	}
          	} 

		} else {
			print_r("roll back moro");
			$res = @pg_query ($con,"ROLLBACK TRANSACTION");
		}
	}

	if (strpos($msg_erro,"ERROR:") !== false) {
		$x        = explode('ERROR:',$msg_erro);
		$msg_erro = $x[1];
	}

	if (strpos($msg_erro,"CONTEXT:") !== false) {
		$x        = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}

	if(strpos($msg_erro,"data_nf_superior_data_abertura")){
		$msg_erro = " Data de abertura anterior à data da nota fiscal";
	}

	if (strlen($msg_erro) == 0 and in_array($login_fabrica, array(157,165)) ) {

		$amazonTC = new AmazonTC("os", $login_fabrica);

		$sql = "SELECT data_abertura,sua_os from tbl_os_revenda where os_revenda = $os_revenda";
		$res = pg_query($con,$sql);
		$data_abertura = pg_fetch_result($res,0,"data_abertura");
		$sua_os = pg_fetch_result($res,0,"sua_os");

		if(strlen($sua_os)>0 ){
			$sua_os_pesquisa = $sua_os;
		}else{
			$sua_os_pesquisa = $os_revenda;
		}

		list($ano, $mes, $dia) = explode("-", $data_abertura) ;

		$imagem = $amazonTC->getObjectList("r_{$os_revenda}",false, $ano, $mes);
		foreach ($imagem as $file ) {
			$ext = preg_replace("/.+\./", "", basename($file));

			$sql ="SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND sua_os LIKE '{$sua_os_pesquisa}-%' ";
			$res = pg_query($con,$sql);
			$i = 0;
			while ($os = pg_fetch_row($res)){
				$os = $os[0];
				$files = array(
						array(
							"file_orig" => basename($file),
				    			"file_new" => "{$os}_{$i}.{$ext}"
			    			),

				);
				$amazonTC->copyObject($files, $ano, $mes);
			}
			++$i;
		}
	}

	if (strlen ($msg_erro) == 0 AND $login_fabrica == 153) {
		$sql = "SELECT sua_os
					FROM tbl_os_revenda
				WHERE os_revenda = $os_revenda
					AND fabrica  = $login_fabrica";
		$res = pg_exec($con, $sql);
		$sua_os = pg_result($res,0,0);

		//move anexo para bucket


        if (count($arrFilesUpload) > 0) {
            if ($amazonTC->moveTempToBucket($arrFilesUpload, $year, $month) === false) {
                $msg_erro = "Erro ao salvar arquivos, por favor tente novamente <br />";
                $erro_upload = "true";
            }
        }
	}

	// $msg_erro = "teste";

	if (!empty($_POST['preos'])) {

		$hd_chamado = $_POST['preos']; 

		$sql = "SELECT sua_os
				FROM tbl_os_revenda
				WHERE os_revenda = {$os_revenda}";
		$res = pg_query($con,$sql);

		$sua_os = pg_fetch_result($res,0,"sua_os");

		if(strlen($sua_os)>0){
			$sua_os_pesquisa = $sua_os;
		}else{
			$sua_os_pesquisa = $os_revenda;
		}
		$sqlUpdate = "UPDATE tbl_os_revenda 
					  SET hd_chamado = {$hd_chamado} 
					  WHERE fabrica = {$login_fabrica} 
					  AND os_revenda = {$os_revenda}";

		$sqlUpdate = pg_query($con, $sqlUpdate);

		if ($login_fabrica == 141) {

			$sqlEmail = "SELECT email
						 FROM tbl_hd_chamado_extra
						 WHERE hd_chamado = {$hd_chamado}";

			$resEmail = pg_query($con, $sqlEmail);

			$email_atendimento = pg_fetch_result($resEmail, 0, 'email');

			$assunto = "Serviço de Atendimento UNICOBA";
	        $mensagem = "Ordem de serviço {$sua_os_pesquisa} de revenda aberta e aguardando análise da fábrica";

	        if(strlen(trim($email_atendimento))>0){
	            $mailTc = new TcComm('smtp@posvenda');
	            
	            $mailTc->sendMail(
		            $email_atendimento,
		            $assunto,
		            $mensagem,
		            'noreply@telecontrol.com.br'
	            );
	        }

    	}

	}

	if (strlen ($msg_erro) == 0) {

		$res = pg_exec ($con,"COMMIT TRANSACTION");

		$sql = "SELECT sua_os
					FROM tbl_os_revenda
				WHERE os_revenda = $os_revenda
					AND fabrica  = $login_fabrica";
		$res = pg_exec($con, $sql);
		$sua_os = pg_result($res,0,0);

		$sql_tem_anexo = "SELECT tdocs FROM tbl_tdocs WHERE referencia_id = '$os_revenda' AND fabrica = $login_fabrica";
		$res_tem_anexo = pg_query($con, $sql_tem_anexo);
		if (pg_num_rows($res_tem_anexo) > 0) {
			$oss = [];
			$sql_oss = "SELECT os FROM tbl_os WHERE sua_os LIKE '".$sua_os."-%'	AND	fabrica = $login_fabrica AND posto = $login_posto";
			$res_oss = pg_query($con, $sql_oss);

			for ($r = 0; $r < pg_num_rows($res_oss); $r++) {
				$oss[] = pg_fetch_result($res_oss, $r, 'os');
			}

			for ($a = 0; $a < pg_num_rows($res_tem_anexo); $a++) {
				$xtdocs = pg_fetch_result($res_tem_anexo, $a, 'tdocs');
				foreach ($oss as $key => $value) {
					$sql_insert_anexo = "INSERT INTO tbl_tdocs 
										 (
										 	tdocs_id,
										 	fabrica,
										 	contexto,
										 	situacao,
										 	obs,
										 	referencia,
										 	referencia_id,
										 	hash_temp
										 )

										 SELECT tdocs_id,
											 	$login_fabrica,
											 	contexto,
											 	situacao,
											 	obs,
											 	referencia,
											 	$value,
											 	hash_temp
										 FROM tbl_tdocs
										 WHERE tdocs = $xtdocs
										 AND fabrica = $login_fabrica";
					$res_insert_anexo = pg_query($con, $sql_insert_anexo);
				}
				$sql_exclui = "DELETE FROM tbl_tdocs WHERE tdocs = $xtdocs AND fabrica = $login_fabrica";
				$res_exclui = pg_query($con, $sql_exclui);
			}
		}

		// redireciona para os_revenda_explodida.php
		header("Location: os_revenda_explodida.php?sua_os=$sua_os");
		exit;
	}else{

		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if(strlen($os_revenda) > 0){
	if(in_array($login_fabrica, [3,15,117])){
		$campos = " tbl_revenda_fabrica.contato_razao_social AS revenda_nome , tbl_revenda_fabrica.cnpj AS revenda_cnpj , tbl_revenda_fabrica.contato_fone AS revenda_fone, tbl_revenda_fabrica.contato_email AS revenda_email, ";
		$join_revenda = " JOIN tbl_revenda_fabrica ON  tbl_os_revenda.revenda = tbl_revenda_fabrica.revenda AND tbl_revenda_fabrica.fabrica = $login_fabrica";
	}else{

		if (in_array($login_fabrica, [141]) && isset($_REQUEST['preos'])) {
			$campoRevenda = ",(
				SELECT tbl_hd_chamado_extra.email
				FROM tbl_hd_chamado_extra
				WHERE tbl_hd_chamado_extra.hd_chamado = ".$_REQUEST['preos']."
				LIMIT 1
			) as revenda_email,";
		} else {
			$campoRevenda = ",tbl_revenda.email AS revenda_email,";
		}

		$campos = " tbl_revenda.nome  AS revenda_nome, tbl_revenda.cnpj  AS revenda_cnpj, tbl_revenda.fone  AS revenda_fone {$campoRevenda} ";
		$join_revenda = " JOIN tbl_revenda ON  tbl_os_revenda.revenda = tbl_revenda.revenda ";
	}
	// seleciona do banco de dados
	$sql = "SELECT  tbl_os_revenda.sua_os                                                ,
					tbl_os_revenda.obs                                                   ,
					to_char(tbl_os_revenda.data_abertura,'DD/MM/YYYY') AS data_abertura  ,
					to_char(tbl_os_revenda.digitacao,'DD/MM/YYYY')     AS data_digitacao ,
					tbl_os_revenda.tipo_atendimento                                      ,
					$campos
					tbl_os_revenda.nota_fiscal                                           ,
					tbl_posto_fabrica.codigo_posto                                       ,
					tbl_os_revenda.consumidor_nome                                       ,
					tbl_os_revenda.consumidor_cnpj                                       ,
					tbl_os_revenda.tipo_os,
					tbl_os_revenda.explodida,
					tbl_os_revenda.classificacao_os
			FROM tbl_os_revenda
				$join_revenda
				JOIN tbl_fabrica ON tbl_os_revenda.fabrica = tbl_fabrica.fabrica
				LEFT JOIN tbl_posto USING (posto)
				LEFT JOIN tbl_posto_fabrica
				ON        tbl_posto_fabrica.posto   = tbl_posto.posto
				AND       tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
			WHERE tbl_os_revenda.os_revenda = $os_revenda
				AND tbl_os_revenda.posto    = $login_posto
				AND tbl_os_revenda.fabrica  = $login_fabrica ";
	$res = pg_exec($con, $sql);
	if (pg_numrows($res) > 0){
		$sua_os           = pg_result($res,0,sua_os);
		$data_abertura    = pg_result($res,0,data_abertura);
		$data_digitacao   = pg_result($res,0,data_digitacao);
		$tipo_atendimento = pg_result($res,0,tipo_atendimento);
		$revenda_nome     = pg_result($res,0,revenda_nome);
		$revenda_cnpj     = pg_result($res,0,revenda_cnpj);
		$revenda_fone     = pg_result($res,0,revenda_fone);
		$revenda_email    = pg_result($res,0,revenda_email);
		$obs              = pg_result($res,0,obs);
		$codigo_posto     = pg_result($res,0,codigo_posto);
		$nota_fiscal      = pg_result($res,0,nota_fiscal);
		$consumidor_nome  = pg_result($res,0,consumidor_nome);
		$consumidor_cnpj  = pg_result($res,0,consumidor_cnpj);
		$tipo_os          = pg_result($res,0,tipo_os);
		$dt_explodida     = pg_result($res,0,explodida);

		if (in_array($login_fabrica, array(141,144))) {
			$os_remanufatura = (pg_fetch_result($res, 0, "classificacao_os") == 1) ? "Sim" : "Não";
		}
	}else{
		if($login_fabrica == 15){
			header('Location: os_revenda_latina.php');
		}else{
			header('Location: os_revenda.php');
		}

		exit;
	}
}

$title        = "Cadastro de Ordem de Serviço - Revenda";
$layout_menu  = 'os';

include "cabecalho.php";

?>
<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
<script type="text/javascript" src="js/anexaNF_excluiAnexo.js"></script>

<style type="text/css">
	.menu_top {
		text-align: center;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: x-small;
		font-weight: bold;
		border: 1px solid;
		color:#ffffff;
		background-color: #596D9B
	}
	.table {
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: x-small;
		text-align: center;
		border: 1px solid #d9e2ef;
	}
	.table_line {
		text-align: left;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
		border: 0px solid;
	}
	.table_line2 {
		text-align: left;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
		background-color: #CED7e7;
	}
</style>

<? if (strlen ($msg_erro) > 0) { ?>
<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ff0000" width='700'>
	<tr>
		<td height="27" valign="middle" align="center">
			<b>
				<font face="Arial, Helvetica, sans-serif" style="font-size: 12pt;" color="#FFFFFF">
					<?
						if (strpos($msg_erro,'Favor informar o telefone do consumidor ou da revenda') > 0) {
							$sqlr = "SELECT revenda,tbl_revenda.cnpj from  tbl_os_revenda join tbl_revenda using(revenda) where os_revenda = $os_revenda";
							$resr = pg_exec($con, $sqlr);
							$revendax = ($login_fabrica == 3)  ? pg_fetch_result($resr,0,1): pg_result($resr,0,0);
							$stringRevenda = ($login_fabrica == 19) ? "revenda/atacado" : "revenda";
							$msg_erro = "Digite o telefone da ". $stringRevenda ." ou atualize os dados da ". $stringRevenda ." antes de efetuar a explosão da OS.<BR>
										Para cadastrar o telefone clique <a href = 'revenda_cadastro.php?revenda=$revendax' target='blank_'>aqui</a>.";
						}
						echo $msg_erro
					?>
				</font>
			</b>
		</td>
	</tr>
</table>
<? } ?>

<br>

<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>?os_revenda=<?=$os_revenda?>">
	<input type='hidden' name='os_revenda' value='<? echo $os_revenda; ?>'>
	<input type="hidden" name="preos" 	   value="<?= $_REQUEST['preos'] ?>" />
	<table width="650" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff" class="table">
		<tr>
			<td>
				<img height="1" width="20" src="imagens/spacer.gif">
			</td>
			<td valign="top" align="left">
				<table width="100%" border="0" cellspacing="3" cellpadding="2">
					<?
						if ($login_fabrica == 19) {
							$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = 19 and tipo_atendimento=$tipo_atendimento ORDER BY tipo_atendimento";
							$res = pg_exec ($con,$sql) ;
								if(strlen($tipo_os)>0){
									$sqll = "SELECT descricao from tbl_tipo_os where tipo_os = $tipo_os";
									$ress = pg_exec ($con,$sqll) ;
								}?>
								<tr class="menu_top">
									<td nowrap colspan='2'>
										<font size="1" face="Geneva, Arial, Helvetica, san-serif">
											Tipo Atendimento
										</font>
									</td>
									<td nowrap colspan='2'>
										<font size="1" face="Geneva, Arial, Helvetica, san-serif">
											Motivo
										</font>
									</td>
								</tr>
								<tr>
									<td nowrap align='center' colspan='2'>
										<font size="1" face="Geneva, Arial, Helvetica, san-serif">
											<? echo pg_result ($res,0,tipo_atendimento) . " - " . pg_result ($res,0,descricao) ;?>
										</font>
									</td>
									<td nowrap align='center' colspan='2'>
										<font size="1" face="Geneva, Arial, Helvetica, san-serif">
											<? if(strlen($tipo_os)>0){echo pg_result ($ress,0,descricao);}?>
										</font>
									</td>
								</tr>
						<? }
					?>
					<tr class="menu_top">
						<td nowrap>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">
								OS Fabricante
							</font>
						</td>
						<td nowrap>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">
								Data Abertura
							</font>
						</td>
						<td nowrap>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">
								Data Digitação
							</font>
						</td>
						<td nowrap>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">
								Nota Fiscal
							</font>
						</td>
					</tr>
					<tr>
						<td nowrap align='center'>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">
								<? if ($login_fabrica == 1) echo $codigo_posto; echo $sua_os; ?>
							</font>
						</td>
						<td nowrap align='center'>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">
								<? echo $data_abertura ?>
							</font>
						</td>
						<td nowrap align='center'>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">
								<? echo $data_digitacao ?>
							</font>
						</td>
						<td nowrap align='center'>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">
								<? echo $nota_fiscal ?>
							</font>
						</td>
					</tr>
					<tr>
						<td colspan='4' class="table_line2" height='20'>
						</td>
					</tr>
				</table>
				<?
					if($login_fabrica== 19) {
						$aux_revenda = "do atacado";
					} else {
						$aux_revenda = "da revenda";
					}
				?>
				<table width="100%" border="0" cellspacing="3" cellpadding="2">
					<tr class="menu_top">
						<td>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">
								Nome <?=$aux_revenda;?>
							</font>
						</td>
						<td>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">
								<?php if ($login_fabrica == 15): ?>
									CNPJ Raiz <?=$aux_revenda;?>
								<?php else: ?>
									CNPJ <?=$aux_revenda;?>
								<?php endif ?>
							</font>
						</td>
					<? if($login_fabrica != 15){ ?>
						<td>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">
								Fone <?=$aux_revenda;?>
							</font>
						</td>
						<td>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">
								e-Mail <?=$aux_revenda;?>
							</font>
						</td>
					<? } ?>
					</tr>
					<tr>
						<td align='center'>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">
								<? echo $revenda_nome ?>
							</font>
						</td>
						<td align='center'>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">
								<? echo ($login_fabrica == 15) ? substr($revenda_cnpj,0,8) : $revenda_cnpj; ?>
							</font>
						</td>
					<? if($login_fabrica != 15){ ?>
						<td align='center'>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">
								<? echo $revenda_fone ?>
							</font>
						</td>
						<td align='center'>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">
							<? echo $revenda_email ?>
							</font>
						</td>
					<? } ?>
					</tr>
				</table>
				<?
					if($login_fabrica ==19) { ?>
						<table width="100%" border="0" cellspacing="3" cellpadding="2">
							<tr class="menu_top">
								<td>
									<font size="1" face="Geneva, Arial, Helvetica, san-serif">
										Nome da Revenda
									</font>
								</td>
								<td>
									<font size="1" face="Geneva, Arial, Helvetica, san-serif">
										CNPJ da Revenda
									</font>
								</td>
							</tr>
							<tr>
								<td align='center'>
									<font size="1" face="Geneva, Arial, Helvetica, san-serif">
										<? echo $consumidor_nome ?>
									</font>
								</td>
								<td align='center'>
									<font size="1" face="Geneva, Arial, Helvetica, san-serif">
										<? echo $consumidor_cnpj ?>
									</font>
								</td>
							</tr>
						</table>
					<? }
				?>
				<table width="100%" border="0" cellspacing="3" cellpadding="2">
					<tr class="menu_top">
						<td>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">
								Observações
							</font>
						</td>
					</tr>
					<tr>
						<td align='center'>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">
								<? echo $obs ?>
							</font>
						</td>
					</tr>
				</table>

				<?php
				if (in_array($login_fabrica, array(141,144)) && in_array($login_tipo_posto, array(450,449))) {
				?>
					<table width="100%" border="0" cellspacing="3" cellpadding="2">
						<tr class="menu_top">
							<td>
								<font size="1" face="Geneva, Arial, Helvetica, san-serif">
									Remanufatura
								</font>
							</td>
						</tr>
						<tr>
							<td align='center'>
								<font size="1" face="Geneva, Arial, Helvetica, san-serif">
									<? echo $os_remanufatura ?>
								</font>
							</td>
						</tr>
					</table>
				<?php
				}
				?>
			</td>
			<td>
				<img height="1" width="16" src="imagens/spacer.gif">
			</td>
		</tr>
	</table>
	<table width="550" border="0" cellpadding="2" cellspacing="3" align="center" bgcolor="#ffffff">
		<tr>
			<td colspan="4">
				<br>
			</td>
		</tr>
		<tr class="menu_top">
			<td align="center">
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
					Produto
				</font>
			</td>
			<td align="center" nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">
					Descrição do produto
				</font>
			</td>
			<?
				if($login_fabrica<>19 && $login_fabrica != 145 && $login_fabrica != 151) { ?>
					<td align="center" nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">
							Número de série
						</font>
					</td>
				<? }
				if($login_fabrica == 160 or $replica_einhell){
				?>
				<td align="center" nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">
						Versão Produto
					</font>
				</td>
			<?
				}
			?>

			<?
				if ($login_fabrica == 1) { ?>
					<td align="center">
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">
							Type
						</font>
					</td>
					<td align="center">
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">
							Embalagem Original
						</font>
					</td>
					<td align="center">
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">
							Sinal de Uso
						</font>
					</td>
				<? }
			?>
			<? if ($login_fabrica == 7) { ?>
				<td align="center">
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">
						Capacidade
					</font>
				</td>
			<? } else {
					if($login_fabrica <> 19) {
						if ($login_fabrica <> 11) { ?>
							<td align="center">
								<font size="1" face="Geneva, Arial, Helvetica, san-serif">
									Número da NF
								</font>
								<br>
								<img src="imagens/selecione_todas.gif" border=0 onclick="javascript:TodosNF()" ALT="Selecionar todas" style="cursor:pointer;">
							</td>
						<? } else { ?>
							<td align="center">
								<font size="1" face="Geneva, Arial, Helvetica, san-serif">
									RG DO PRODUTO
								</font>
							</td>
						<? }
						if($login_fabrica == 162){ ?>
							<td align="center">
								<font size="1" face="Geneva, Arial, Helvetica, san-serif">
									IMEI
								</font>
							</td>
						<?php }
					}

					if($login_fabrica == 94){ //hd_chamado=2705567
					?>
						<td align="center">
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">
								Defeito Reclamado
							</font>
						</td>
					<?php
					}

					if ($login_fabrica == 151) {
					?>
						<td>
							<font size="1" face="Geneva, Arial, Helvetica, san-serif">Data da NF</font>
						</td>
					<?php
					}
				} ?>
			<? if (in_array($login_fabrica, array(19,121,136,137,139,141,145,151))) { ?>
					<td align="center">
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">
							Qtde
						</font>
					</td>
			<? } ?>




			<?php
				if ($login_fabrica==91) { ?>
					<td align="center">
						<font size="1" face="Geneva, Arial, Helvetica, san-serif">
							Data de Fabricação
						</font>
					</td>
			<?php
				}
			?>
		</tr>
		<?php
			// monta o FOR
			$qtde_item = 20;
			if ($os_revenda) {
				// seleciona do banco de dados
				$sql = "SELECT  tbl_os_revenda_item.os_revenda_item    ,
								tbl_os_revenda_item.produto            ,
								tbl_os_revenda_item.serie              ,
								tbl_os_revenda_item.nota_fiscal        ,
								tbl_os_revenda_item.rg_produto         ,
								tbl_os_revenda_item.capacidade         ,
								tbl_os_revenda_item.type               ,
								tbl_os_revenda_item.embalagem_original ,
								tbl_os_revenda_item.sinal_de_uso       ,
								tbl_os_revenda_item.qtde               ,
								tbl_produto.referencia                 ,
								tbl_produto.descricao				   ,
								to_char(tbl_os_revenda_item.data_fabricacao,'DD/MM/YYYY') as data_fabricacao,
								to_char(tbl_os_revenda_item.data_nf, 'DD/MM/YYYY') as data_nf
						FROM tbl_os_revenda
							JOIN  tbl_os_revenda_item
							ON    tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
							JOIN  tbl_produto
							ON    tbl_produto.produto       = tbl_os_revenda_item.produto
						WHERE tbl_os_revenda.os_revenda = $os_revenda
							AND tbl_os_revenda.posto    = $login_posto
							AND tbl_os_revenda.fabrica  = $login_fabrica ";
				$res = pg_exec($con, $sql);
				for ($i=0; $i<pg_numrows($res); $i++) {
					$produto = pg_fetch_result($res, $i, 'produto');
					$referencia_produto = pg_result($res,$i,referencia);
					$produto_descricao  = pg_result($res,$i,descricao);
					$produto_serie      = pg_result($res,$i,serie);
					$nota_fiscal        = pg_result($res,$i,nota_fiscal);
					$rg_produto         = pg_result($res,$i,rg_produto);
					$capacidade         = pg_result($res,$i,capacidade);
					$type               = pg_result($res,$i,type);
					$embalagem_original = pg_result($res,$i,embalagem_original);
					$sinal_de_uso       = pg_result($res,$i,sinal_de_uso);
					$qtde               = pg_result($res,$i,qtde);
					$data_fabricacao    = pg_result($res,$i,data_fabricacao);
					$xdata_fabricacao	= preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$1/$2/$3', $data_fabricacao);
					$data_nf = pg_fetch_result($res, $i, "data_nf");

					if($login_fabrica == 94){ //hd_chamado=2705567
						$sql_defeito = "SELECT tbl_os_revenda_item.defeito_constatado_descricao
										FROM tbl_os_revenda
										JOIN tbl_os_revenda_item ON tbl_os_revenda.os_revenda = tbl_os_revenda_item.os_revenda
										WHERE tbl_os_revenda.os_revenda = $os_revenda
										AND tbl_os_revenda_item.produto = $produto
										AND tbl_os_revenda.posto    = $login_posto
										AND tbl_os_revenda.fabrica  = $login_fabrica";
						$res_defeito = pg_query($con, $sql_defeito);
						if(pg_last_error($con) > 0){ $msg_erro.= "Erro na consulta de defeito"; }

						if(pg_num_rows($res_defeito) > 0){
							$defeito_reclamado = pg_fetch_result($res_defeito, 0, 'defeito_constatado_descricao');
						}
					}

					?>
						<tr>
							<td align="center">
								<font size="1" face="Geneva, Arial, Helvetica, san-serif">
									<? echo $referencia_produto ?>
								</font>
							</td>
							<td align="left" nowrap>
								<font size="1" face="Geneva, Arial, Helvetica, san-serif">
									<? echo $produto_descricao ?>
								</font>
							</td>
							<? if($login_fabrica<>19 && $login_fabrica != 145 && $login_fabrica != 151) { ?>
								<td align="center">
									<font size="1" face="Geneva, Arial, Helvetica, san-serif">
										<? echo $produto_serie ?>
									</font>
								</td>
							<?}
							if($login_fabrica == 160 or $replica_einhell){
							?>
								<td align="center">
									<font size="1" face="Geneva, Arial, Helvetica, san-serif">
										<? echo $type ?>
									</font>
								</td>
							<?} if ($login_fabrica == 1) { ?>
								<td align='center' nowrap>
									<font size="1" face="Geneva, Arial, Helvetica, san-serif">
										<? echo $type ?>
									</font>
								</td>
								<td align='center' nowrap>
									<font size="1" face="Geneva, Arial, Helvetica, san-serif">
										<?
											if ($embalagem_original == 't')
												echo "Sim";
											else
												echo "Não";
										?>
									</font>
								</td>
								<td align='center' nowrap>
									<font size="1" face="Geneva, Arial, Helvetica, san-serif">
										<?
											if ($sinal_de_uso == 't')
												echo "Sim";
											else
												echo "Não";
										?>
									</font>
								</td>
							<? }
							if($login_fabrica<>19) { ?>
								<td>
									<? if ($login_fabrica == 7) { ?>
										<font size="1" face="Geneva, Arial, Helvetica, san-serif">
											<? echo $capacidade ?>
										</font>
									<? }else{
										if ($login_fabrica <> 11 ) { ?>
											<font size="1" face="Geneva, Arial, Helvetica, san-serif">
												<? echo $nota_fiscal ?>
											</font>
									<? } else {
											echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>$rg_produto</font>";
										}
									}?>
								</td>
							<? }
							if($login_fabrica == 162){ ?>
								<td>
									<?php echo "<font size='1' face='Geneva, Arial, Helvetica, san-serif'>$rg_produto</font>"; ?>
								</td>

							<? }


							if ($login_fabrica == 151) {
							?>
								<td>
									<font size="1" ><?=$data_nf?></font>
								</td>
							<?php
							}
							if (in_array($login_fabrica, array(19,121,136,137,139,141,145,151))) { ?>
								<td align="center">
									<font size="1" face="Geneva, Arial, Helvetica, san-serif">
										<?=$qtde?>
									</font>
								</td>
							<? } ?>

							<?php if($login_fabrica == 94){ //hd_chamado=2705567?>
								<td align="center">
									<font size="1" face="Geneva, Arial, Helvetica, san-serif">
										<?php echo $defeito_reclamado;?>
									</font>
								</td>

							<?php } ?>
							<?php
								if ($login_fabrica==91) { ?>
									<td align="center">
										<font size="1" face="Geneva, Arial, Helvetica, san-serif">
											<?php echo $xdata_fabricacao;?>
										</font>
									</td>
							<?php
								}
							?>

						</tr>
					<?
				}
			}
		?>
	</table>
<?	

if ($anexaNotaFiscal and temNF('r_' . $os_revenda, 'bool')) {
		echo "<div>" . temNF('r_' . $os_revenda, 'linkEx') . "</div>\n" . $include_imgZoom;
	} // HD 321132 - FIM
?>
	<br>
	<input type='hidden' name='btn_acao' value=''>
	<center>
		<?
			$link = ($login_fabrica == 15) ? "os_revenda_latina.php" : "os_revenda.php";
		?>

		<img src='imagens/btn_alterarcinza.gif'  onclick="javascript: document.location='<?=$link?>?os_revenda=<? echo $os_revenda; ?>'" ALT="Alterar" border='0' style="cursor:pointer;">
		<img src='imagens/btn_explodir<?if($login_fabrica==19){echo "_2";}?>.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='explodir' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Explodir" border='0' style="cursor:pointer;">
		<img src='imagens/btn_imprimir.gif' onclick="javascript: window.open('os_revenda_print.php?os_revenda=<? echo $os_revenda; ?>','osrevenda');" ALT="Imprimir" border='0' style="cursor:pointer;">
	</center>
	<br>
	<center>
		<a href="os_revenda_consulta.php?<?echo $_COOKIE['cookget']; ?>">
			<img src="imagens/btn_voltarparaconsulta.gif">
		</a>
	</center>
	<br>
	<br>
</form>

<br>

<? include 'rodape.php'; ?>
