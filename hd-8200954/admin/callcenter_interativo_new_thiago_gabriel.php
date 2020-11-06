<?php

//as tabs definem a categoria do chamado
/* OBSERVACAO HBTECH
	* O produto Hibeats possui uma garantia estendida, ou seja, 1 ano de garantia normal e se ele entrar no site do hibeats ou solicitar via SAC a extenso o cliente ganha mais 6 meses de garantia ficando com 18 meses.
	* Para verificar os produtos que tem garantia estendida acessamos o bd do hibeats (conexao_hbflex.php) e verificamos o nmero de srie.
		* Todos numeros de series vendidos estao no bd do hibeats, caso nao esteja l no foi vendido ou a AKabuki no deu carga no bd.
		* AKabuki  a agencia que toma conta do site da hbflex, responsavel pelo bd e atualizacao do bd. Contato:
			Allan Rodrigues
			Programador
			AGNCIA KABUKI
			* allan@akabuki.com.br
			* www.akabuki.com.br
			( 55 11 3871-9976
	** Acompanhar os lancamentos destas garantias, liberado no ultimo dia do ano e ainda estamos acompanhando

*/
# socinter = 59
$debug = false;
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';

//HD 235203: Alterar assuntos Fale Conosco e CallCenter
//			 É no arquivo abaixo que é definido o array $assuntos
include_once("callcenter_suggar_assuntos_thiago_gabriel.php");

if (isset($_GET['ajax']) && isset($_GET['hdanterior']) && isset($_GET['resp'])) {//HD 277105

	if ($_GET['ajax'] != 'sim' || empty($_GET['hdanterior']) || empty($_GET['resp']) ) 
		return 'Erro com os parâmetros. Verifique os dados na url.';

	require_once '../helpdesk.inc.php';
	$aRespostas = hdBuscarRespostas($_GET['hdanterior']);

	if (!empty($aRespostas)) {

		$sql = 'SELECT categoria FROM tbl_hd_chamado 
				WHERE hd_chamado = ' . $_GET['hdanterior'] . ' AND fabrica = ' . $login_fabrica . 'LIMIT 1';
		$res = pg_query($con,$sql);
		$assunto_cat = pg_result($res,0,categoria);

		foreach($assuntos as $categoria_assunto => $itens_categoria_assunto) {
			foreach($itens_categoria_assunto as $label_assunto => $bd_assunto) {
				if ($bd_assunto == $assunto_cat) {
					$categoria = $label_assunto;
					$achou_categoria_assunto = true;
				}
			}
		}

		echo '<tr class="resp'.$_GET['resp'].'"  bgcolor="#A0BFE0"><td colspan="4"><b>Assunto: ' . $categoria . '</b></td></tr>';
		foreach ($aRespostas as $iResposta=>$aResposta): ?>
			<tr class="resp<?=$_GET['resp']?>" bgcolor="#A0BFE0">
				<td colspan="4">
								Resposta <strong><?php echo $iResposta + 1; ?></strong>
								Por <strong><?php echo ( ! empty($aResposta['atendente']) ) ? $aResposta['atendente'] : $aResposta['posto_nome'] ; ?></strong>
								em <?php echo $aResposta['data']; ?> </td>
				</td>
			</tr>
			
			<?php if ( $aResposta['interno'] == 't' ): ?>
			<tr class="resp<?=$_GET['resp']?>">
				<td align="center" bgcolor="#EFEBCF" colspan="4"> Chamado Interno </td>
			</tr>
			<?php endif; ?>
			<?php if ( in_array($aResposta['status_item'],array('Cancelado','Resolvido')) ): ?>
			<tr class="resp<?=$_GET['resp']?>">
				<td align="center" colspan="4" bgcolor="#EFEBCF" > <?php echo $aResposta['status_item']; ?> </td>
			</tr>
			<?php endif; ?>
			<tr class="resp<?=$_GET['resp']?>">
				<td align="left" colspan="4" bgcolor="#FFFFFF" style="border-bottom:1px solid black;"> <?php echo nl2br($aResposta['comentario']); ?> </td>
			</tr><?php 
		endforeach;
	}
	else
		echo '<tr class="resp'.$_GET['resp'].' "><td colspan="4">Não foram feitas Interações nesse Chamado<td></td></tr>';
	return;

}

if (!empty($_COOKIE['debug'])) $debug = ($_COOKIE['debug']=='true');

if ($login_fabrica == 3) {
	if (strlen($callcenter) > 0) header ("Location:callcenter_interativo_new_britania.php?callcenter=$callcenter");
	else                      	 header ('Location:callcenter_interativo_new_britania.php');
	exit;
}else if($login_fabrica == 5){
	if(strlen($callcenter)>0) header ("Location:callcenter_interativo_new_mondial.php?callcenter=$callcenter");
	else                      header ('Location:callcenter_interativo_new_mondial.php');
	exit;
}

$fab_usa_tipo_cons = array(51); // HD 317864

if ($_GET["continuar_chamado"] && $_GET["Id"]) {
	$hd_chamado = $_GET["Id"];
	
	$sql = "SELECT hd_chamado FROM tbl_hd_chamado WHERE hd_chamado=$hd_chamado AND fabrica=$login_fabrica AND fabrica_responsavel=$login_fabrica";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res)) {

		$sql = "BEGIN TRANSACTION";
		$res = pg_query($con, $sql);
		$msg_erro[] = pg_errormessage($con);

		$sql = "
		INSERT INTO tbl_hd_chamado (
		admin,
		posto,
		titulo,
		status,
		atendente,
		fabrica_responsavel,
		categoria,
		duracao,
		exigir_resposta,
		fabrica,
		empregado,
		orcamento,
		pessoa,
		sequencia_atendimento,
		tipo_chamado,
		cliente,
		cliente_admin,
		hd_chamado_anterior
		)

		SELECT
		admin,
		posto,
		titulo,
		'Aberto',
		$login_admin,
		fabrica_responsavel,
		categoria,
		duracao,
		exigir_resposta,
		fabrica,
		empregado,
		orcamento,
		pessoa,
		sequencia_atendimento,
		tipo_chamado,
		cliente,
		cliente_admin,
		$hd_chamado

		FROM
		tbl_hd_chamado

		WHERE
		hd_chamado=$hd_chamado
		";
		$res = pg_query($con, $sql);
		$msg_erro[] = pg_errormessage($con);

		$res = pg_query($con, "SELECT CURRVAL('seq_hd_chamado')");
		$hd_chamado_novo = pg_result($res, 0, 0);
		$msg_erro[] = pg_errormessage($con);

		$sql = "
		INSERT INTO tbl_hd_chamado_extra (
		hd_chamado,
		reclamado,
		defeito_reclamado,
		serie,
		hora_ligacao,
		produto,
		posto,
		os,
		receber_info_fabrica,
		consumidor_revenda,
		origem,
		revenda,
		revenda_nome,
		data_nf,
		nota_fiscal,
		nome,
		endereco,
		numero,
		complemento,
		bairro,
		cep,
		fone,
		fone2,
		email,
		cpf,
		rg,
		cidade,
		qtde_km,
		abre_os,
		defeito_reclamado_descricao,
		numero_processo,
		tipo_registro,
		celular,
		revenda_cnpj,
		atendimento_callcenter
		)

		SELECT
		$hd_chamado_novo,
		reclamado,
		defeito_reclamado,
		serie,
		hora_ligacao,
		produto,
		posto,
		os,
		receber_info_fabrica,
		consumidor_revenda,
		origem,
		revenda,
		revenda_nome,
		data_nf,
		nota_fiscal,
		nome,
		endereco,
		numero,
		complemento,
		bairro,
		cep,
		fone,
		fone2,
		email,
		cpf,
		rg,
		cidade,
		qtde_km,
		'f',
		defeito_reclamado_descricao,
		numero_processo,
		tipo_registro,
		celular,
		revenda_cnpj,
		atendimento_callcenter

		FROM
		tbl_hd_chamado_extra

		WHERE
		hd_chamado=$hd_chamado
		";
		$res = pg_query($con, $sql);
		$msg_erro[] = pg_errormessage($con);
	if($login_fabrica != 24) {
		$sql = "
		INSERT INTO
		tbl_hd_chamado_item (
		hd_chamado,
		data,
		comentario,
		admin,
		posto,
		interno,
		status_item,
		empregado,
		pessoa,
		termino,
		tempo_interacao,
		enviar_email,
		atendimento_telefone,
		produto,
		serie,
		defeito_reclamado,
		os
		)

		SELECT
		$hd_chamado_novo,
		data,
		comentario,
		admin,
		posto,
		interno,
		'Aberto',
		empregado,
		pessoa,
		termino,
		tempo_interacao,
		enviar_email,
		atendimento_telefone,
		produto,
		serie,
		defeito_reclamado,
		os

		FROM
		tbl_hd_chamado_item

		WHERE
		hd_chamado=$hd_chamado
		";
		$res = pg_query($con, $sql);
		$msg_erro[] = pg_errormessage($con);
	}

		$msg_erro = implode("", $msg_erro);

		if (strlen($msg_erro)) {
			$sql = "ROLLBACK TRANSACTION";
			$res = pg_query($con, $sql);
			header("location:" . $PHP_SELF);
			die;
		}
		else {
			$sql = "COMMIT TRANSACTION";
			$res = pg_query($con, $sql);
			header("location:" . $PHP_SELF . "?callcenter=$hd_chamado_novo");
			die;
		}
	}
	else {
		header("location:" . $PHP_SELF);
		die;
	}
}

// !129655
// HD 129655 - Gravar faq para dúvida de produtos
/**
 * Insere as dúvidas do produto pesquisadas.
 *
 * @return boolean Se true a função gravou as Dúvidas
 * @author Augusto Pascutti <augusto.pascutti@telecontrol.com.br>
 */
function gravarFaq() {
	global $con,$hd_chamado,$msg_erro;

	if ( empty($hd_chamado) || $hd_chamado <= 0 ) {
		$msg_erro .= "<p>Não foi possível gravar dúvidas do produto, número do chamado não informado. $hd_chamado</p>";
		return false;
	}

	if ( isset($_POST['faq']) && count($_POST['faq']) > 0 && is_array($_POST['faq']) ) {
		$aFaqs = array();
		foreach ( $_POST['faq'] as $xfaq ) {
			$xfaq = (int) $xfaq;
			$aFaqs[] = "({$hd_chamado},{$xfaq})";
		}
		@pg_query($con,"DELETE FROM tbl_hd_chamado_faq WHERE hd_chamado = {$hd_chamado}");
		$sql = "INSERT INTO tbl_hd_chamado_faq (hd_chamado,faq) VALUES " . implode(',',$aFaqs);
		$res = @pg_query($con,$sql);
		if ( is_resource($res) && pg_affected_rows($res) > 0 ) {
			return true;
		}
		$msg_erro .= "<p>Erro ao inserir as dúvidas.</p>";
		return false;
	}
}
// fim HD 129655

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];

	if (strlen($q)>2){

		//HD 204082: Busca de revenda para fábricas >= 81 e Telecontrol Net
		if ($tipo_busca=="revenda"){
			$sql = "SELECT tbl_revenda.revenda, tbl_revenda.cnpj, tbl_revenda.nome
					FROM tbl_revenda
					JOIN tbl_revenda_compra USING(revenda)
					WHERE tbl_revenda_compra.fabrica = $login_fabrica ";

			if ($busca == "codigo"){
				$sql .= " AND tbl_revenda.cnpj like '%$q%' ";
			}else{
				$sql .= " AND UPPER(tbl_revenda.nome) ilike UPPER('%$q%') ";
			}
			

			$res = pg_query($con,$sql);
			if (pg_num_rows ($res) > 0) {
				for ($i=0; $i<pg_num_rows ($res); $i++ ){
					$revenda = trim(pg_fetch_result($res,$i,revenda));
					$cnpj    = trim(pg_fetch_result($res,$i,cnpj));
					$nome    = trim(pg_fetch_result($res,$i,nome));
					echo "$revenda|$cnpj|$nome";
					echo "\n";
				}
			}
		}

		if ($tipo_busca=="revenda_geral"){
			$sql = "SELECT tbl_revenda.revenda, tbl_revenda.cnpj, tbl_revenda.nome, tbl_cidade.nome AS cidade_nome
					FROM tbl_revenda
					JOIN tbl_cidade USING(cidade)
					WHERE 1=1
					";

			if ($busca == "codigo"){
				$sql .= " AND tbl_revenda.cnpj like '%$q%' ";
			}else{
				$sql .= " AND tbl_revenda.nome ilike '%$q%' ";
				$sql .= " OR tbl_cidade.nome ilike '%$q%' ";
				$sql .= " OR tbl_revenda.nome || ' - ' || tbl_cidade.nome ilike '%$q%' ";
			}

			$sql .= " LIMIT 10 ";

			$res = pg_query($con,$sql);
			if (pg_num_rows ($res) > 0) {
				for ($i=0; $i<pg_num_rows ($res); $i++ ){
					$revenda = trim(pg_fetch_result($res,$i,revenda));
					$cnpj    = trim(pg_fetch_result($res,$i,cnpj));
					$nome    = trim(pg_fetch_result($res,$i,nome));
					$cidade_nome = trim(pg_fetch_result($res,$i,cidade_nome));
					echo "$revenda|$cnpj|$nome|$cidade_nome";
					echo "\n";
				}
			}
		}

		if ($tipo_busca=="revenda_os"){
			$sql = "SELECT tbl_revenda.revenda, tbl_revenda.cnpj, tbl_revenda.nome
					FROM tbl_revenda
					JOIN tbl_revenda_compra USING(revenda)
					WHERE UPPER(tbl_revenda.nome) ilike UPPER('%$q%') ";

			$res = pg_query($con,$sql);
			if (pg_num_rows ($res) > 0) {
				for ($i=0; $i<pg_num_rows ($res); $i++ ){
					$revenda = trim(pg_fetch_result($res,$i,revenda));
					$cnpj    = trim(pg_fetch_result($res,$i,cnpj));
					$nome    = trim(pg_fetch_result($res,$i,nome));
					echo "$revenda|$cnpj|$nome";
					echo "\n";
				}
			}
		}


		if ($tipo_busca=="posto"){
			$sql = "SELECT tbl_posto.posto, tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto, tbl_posto_fabrica.nome_fantasia,						   tbl_posto.fone, tbl_posto_fabrica.contato_email as email
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					AND tbl_posto_fabrica.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')";

			if ($busca == "codigo"){
				$sql .= " AND tbl_posto_fabrica.codigo_posto ilike '%$q%' ";
			}else{
				$sql .= "  AND (
								UPPER(tbl_posto.nome) like UPPER('%$q%')
							OR  UPPER(tbl_posto_fabrica.nome_fantasia) like UPPER('%$q%')
								)";
			}
//var_dump( $sql );
			$res = pg_query($con,$sql);
			if (pg_num_rows ($res) > 0) {
				for ($i=0; $i<pg_num_rows ($res); $i++ ){
					$posto         = trim(pg_fetch_result($res,$i,posto));
					$cnpj          = trim(pg_fetch_result($res,$i,cnpj));
					$nome          = trim(pg_fetch_result($res,$i,nome));
					$codigo_posto  = trim(pg_fetch_result($res,$i,codigo_posto));
					$nome_fantasia = trim(pg_fetch_result($res,$i,nome_fantasia));
					$fone = trim(pg_fetch_result($res,$i,fone));
					$email = trim(pg_fetch_result($res,$i,email));
					echo "$posto|$cnpj|$codigo_posto|$nome|$nome_fantasia|$fone|$email";
					echo "\n";
				}
			}
		}
			if ($tipo_busca=="mapa_cidade"){

			$sql = "SELECT      DISTINCT tbl_posto.cidade
					FROM        tbl_posto_fabrica
					JOIN tbl_posto using(posto)
					WHERE       tbl_posto_fabrica.fabrica = $login_fabrica
					AND         tbl_posto.cidade ILIKE UPPER('%$q%')
					ORDER BY    tbl_posto.cidade";

			$res = pg_query($con,$sql);
			if (pg_num_rows ($res) > 0) {
				for ($i=0; $i<pg_num_rows ($res); $i++ ){
					$mapa_cidade        = trim(pg_fetch_result($res,$i,cidade));
					echo "$mapa_cidade";
					echo "\n";
				}
			}
		}
	}


	if ($tipo_busca=="cliente_admin"){
			$y = trim (strtoupper ($q));
			$palavras = explode(' ',$y);
			$count = count($palavras);
			$sql_and = "";
			for($i=0 ; $i < $count ; $i++){
				if(strlen(trim($palavras[$i]))>0){
					$cnpj_pesquisa = trim($palavras[$i]);
					$cnpj_pesquisa = str_replace (' ','',$cnpj_pesquisa);
					$cnpj_pesquisa = str_replace ('-','',$cnpj_pesquisa);
					$cnpj_pesquisa = str_replace ('\'','',$cnpj_pesquisa);
					$cnpj_pesquisa = str_replace ('.','',$cnpj_pesquisa);
					$cnpj_pesquisa = str_replace ('/','',$cnpj_pesquisa);
					$cnpj_pesquisa = str_replace ('\\','',$cnpj_pesquisa);
					$sql_and .= " AND (tbl_cliente_admin.nome ILIKE '%".trim($palavras[$i])."%'
								 	  OR  tbl_cliente_admin.cnpj ILIKE '%$cnpj_pesquisa%' OR tbl_cliente_admin.cidade ILIKE '%".trim($palavras[$i])."%')";
					if (strlen($cidade)>0) {
						$sql_and .= " AND tbl_cliente_admin.cidade ILIKE '%".trim($cidade)."%'";
					}
				}
			}

			$sql = "SELECT      tbl_cliente_admin.cliente_admin,
								tbl_cliente_admin.nome,
								tbl_cliente_admin.codigo,
								tbl_cliente_admin.cnpj,
								tbl_cliente_admin.cidade
					FROM        tbl_cliente_admin
					WHERE       tbl_cliente_admin.fabrica = $login_fabrica
					$sql_and limit 30";

			$res = pg_query($con,$sql);
			if (pg_num_rows ($res) > 0) {
				for ($i=0; $i<pg_num_rows ($res); $i++ ){
					$cliente_admin      = trim(pg_fetch_result($res,$i,cliente_admin));
					$nome               = trim(pg_fetch_result($res,$i,nome));
					$codigo             = trim(pg_fetch_result($res,$i,codigo));
					$cnpj               = trim(pg_fetch_result($res,$i,cnpj));
					$cidade             = trim(pg_fetch_result($res,$i,cidade));

					echo "$cliente_admin|$cnpj|$codigo|$nome|$cidade";
					echo "\n";
				}
			}
		}

		if ($tipo_busca=="localizar"){
				$y = trim (strtoupper ($q));
				$palavras = explode(' ',$y);
				$count = count($palavras);
				$sql_and = "";
				for($i=0 ; $i < $count ; $i++){
					if(strlen(trim($palavras[$i]))>0){
						$cnpj_pesquisa = trim($palavras[$i]);
						$cnpj_pesquisa = str_replace (' ','',$cnpj_pesquisa);
						$cnpj_pesquisa = str_replace ('-','',$cnpj_pesquisa);
						$cnpj_pesquisa = str_replace ('\'','',$cnpj_pesquisa);
						$cnpj_pesquisa = str_replace ('.','',$cnpj_pesquisa);
						$cnpj_pesquisa = str_replace ('/','',$cnpj_pesquisa);
						$cnpj_pesquisa = str_replace ('\\','',$cnpj_pesquisa);
						$sql_and .= " AND (tbl_hd_chamado_extra.nome ILIKE '%".trim($palavras[$i])."%'
										  OR  tbl_hd_chamado_extra.cpf ILIKE '%".trim($palavras[$i])."%' OR tbl_hd_chamado_extra.fone ILIKE '%".trim($palavras[$i])."%' OR tbl_hd_chamado_extra.nota_fiscal ILIKE '%".trim($palavras[$i])."%' OR tbl_hd_chamado_extra.serie ILIKE '%".trim($palavras[$i])."%' OR tbl_os.sua_os ILIKE'%".trim($palavras[$i])."%' OR tbl_hd_chamado_extra.cep ILIKE '%".$cnpj_pesquisa."%')";
					}
				}

				$sql = "SELECT      tbl_hd_chamado.hd_chamado,
									tbl_hd_chamado_extra.serie,
									tbl_hd_chamado_extra.nota_fiscal,
									tbl_hd_chamado_extra.nome,
									tbl_hd_chamado_extra.cpf,
									tbl_hd_chamado_extra.rg,
									tbl_hd_chamado_extra.endereco,
									tbl_hd_chamado_extra.email,
									tbl_hd_chamado_extra.numero,
									tbl_hd_chamado_extra.complemento,
									tbl_hd_chamado_extra.bairro,
									tbl_cidade.nome as nome_cidade,
									tbl_cidade.estado,
									tbl_os.sua_os,
									tbl_hd_chamado_extra.cep,
									tbl_hd_chamado_extra.fone
						FROM        tbl_hd_chamado JOIN tbl_hd_chamado_extra using(hd_chamado)
						LEFT JOIN tbl_os USING(os)
						LEFT JOIN tbl_cidade USING (cidade)
						WHERE       tbl_hd_chamado.fabrica = $login_fabrica
						$sql_and limit 30";

				$res = pg_query($con,$sql);
				if (pg_num_rows ($res) > 0) {
					for ($i=0; $i<pg_num_rows ($res); $i++ ){
						$hd_chamado        = trim(pg_fetch_result($res,$i,hd_chamado));
						$nome              = trim(pg_fetch_result($res,$i,nome));
						$serie             = trim(pg_fetch_result($res,$i,serie));
						$cpf               = trim(pg_fetch_result($res,$i,cpf));
						$rg               = trim(pg_fetch_result($res,$i,rg));
						$email               = trim(pg_fetch_result($res,$i,email));
						$nota_fiscal       = trim(pg_fetch_result($res,$i,nota_fiscal));
						$fone              = trim(pg_fetch_result($res,$i,fone));
						$endereco          = trim(pg_fetch_result($res,$i,endereco));
						$numero            = trim(pg_fetch_result($res,$i,numero));
						$complemento       = trim(pg_fetch_result($res,$i,complemento));
						$cep               = trim(pg_fetch_result($res,$i,cep));
						$sua_os            = trim(pg_fetch_result($res,$i,sua_os));
						$bairro            = trim(pg_fetch_result($res,$i,bairro));
						$cidade            = trim(pg_fetch_result($res,$i,nome_cidade));
						$estado            = trim(pg_fetch_result($res,$i,estado));


						echo "$hd_chamado|$cpf|$nome|$serie|$nota_fiscal|$fone|$sua_os|$cep|$endereco|$numero|$bairro|$complemento|$cidade|$estado|$rg|$email";
						echo "\n";
					}
				}
		}

	exit;
}

$title = "Atendimento Call-Center";
$layout_menu = 'callcenter';

include 'funcoes.php';
function converte_data($date)
{
	$date = explode("-", str_replace('/', '-', $date));
	$date2 = ''.$date[2].'-'.$date[1].'-'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}
function acentos1( $texto ){
	 $array1 = array("", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "" , "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "","","" );
	$array2 = array("", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "" ,"", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "","","");
	return str_replace( $array1, $array2, $texto );
}
function acentos3( $texto ){
 $array1 = array("", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "" , "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "","","" );
 $array2 = array("A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","N","N" );
 return str_replace( $array1, $array2, $texto );
}

if (!function_exists('date_to_timestamp')) {
	function date_to_timestamp($fecha='hoje') { // $fecha formato YYYY-MM-DD H24:MI:SS ou DD-MM-YYYY H24:MI:SS
	    if ($fecha=="hoje") $fecha= date('Y-m-d H:i:s');
		list($date, $time)		  = explode(' ', $fecha);
		list($year, $month, $day) = preg_split('/[\/|\.|-]/', $date);
		if (strlen($year)==2 and strlen($day)==4) list($day,$year) = array($year,$day); // Troca a ordem de dia e ano, se precisar
		if ($time=="") $time = "00:00:00";
		list($hour, $minute, $second) = explode(':', $time);
		return mktime((int) $hour, (int) $minute, (int) $second, (int) $month, (int) $day, (int) $year);
	}
}

/* MLG HD 175044    */
/*  14/12/2009 - Alteração direta, colquei conferência de 'funcion exists', porque mesmo que o include
				 e 'exit' esteja antes da declaração da função, ela é declarada na primeira passagem
				 do interpretador. */
if (!function_exists('checaCPF')) {
	function checaCPF  ($cpf,$return_str = true, $use_savepoint = false){
	   global $con, $login_fabrica;	// Para conectar com o banco...
			$cpf = preg_replace("/\D/","",$cpf);   // Limpa o CPF
	//  23/12/2009 HD 186382 - a função pula as pré-OS anteriores à hoje...
			if ((($login_fabrica==52  and strlen($_REQUEST['pre_os'])>0) or
				$login_fabrica==11) and
				date_to_timestamp($_REQUEST['data_abertura'])<date_to_timestamp('24/12/2009')) return $cpf;
			if (!$cpf or $cpf == '' or (strlen($cpf) != 11 and strlen($cpf) != 14)) return false;

			if ($use_savepoint) $n = @pg_query($con,"SAVEPOINT checa_CPF");
			$res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf')");
			if ($res_cpf === false) {
				$cpf_erro = pg_last_error($con);
				if ($use_savepoint) $n = @pg_query($con,"ROLLBACK TO SAVEPOINT checa_CPF");
				return ($return_str) ? $cpf_erro : false;
			}
			return $cpf;

}
}
include '../helpdesk/mlg_funciones.php';

$indicacao_posto=$_GET['indicacao_posto'];
if(strlen($indicacao_posto)==0) {
	$indicacao_posto=$_POST['indicacao_posto'];
}
if(strlen($indicacao_posto)==0) {
	$indicacao_posto='f';
}


$atendimento_callcenter    =(strlen($_GET['atendimento_callcenter']) > 0 ) ? trim($_GET['atendimento_callcenter']) : trim($_POST['atendimento_callcenter']);

$btn_acao = $_POST['btn_acao'];

if(strlen($btn_acao)>0){

			$callcenter         = $_POST['callcenter'];
			$hd_chamado         = $callcenter;
			$tab_atual          = $_POST['tab_atual'];
			$status_interacao   = $_POST['status_interacao'];
			$transferir         = $_POST['transferir'];
			$intervensor         = $_POST['intervensor'];
			$chamado_interno    = $_POST['chamado_interno'];
			$envia_email        = $_POST['envia_email'];

			if(strlen($envia_email)==0){
				$xenvia_email = "'f'";
			}else{
				$xenvia_email = "'t'";
			}

			if ($login_fabrica == 24) {
				$pedir_produto = false;

				if($indicacao_posto == 't') {
					$callcenter_assunto = "produto_local_de_assistencia";
				}
				elseif (isset($_POST['callcenter_assunto'])) {
					$callcenter_assunto = trim($_POST["callcenter_assunto"]["$tab_atual"]);
				}

				if (strlen($callcenter_assunto) == 0) {
					$categoria = $_POST['tab_atual'];
				}
				else {
					$categoria = $callcenter_assunto;
				}

				foreach($assuntos as $categoria_assunto => $itens_categoria_assunto) {
					foreach($itens_categoria_assunto as $label_assunto => $bd_assunto) {
						if ($bd_assunto == $categoria) {
							$categoria_assunto_seleciona = $categoria_assunto;
						}
					}
				}

				switch($categoria_assunto_seleciona) {
					case "PRODUTOS":
						if ($tab_atual == "reclamacao_produto") {
							$pedir_produto = true;
						}
						else {
							$categoria = "";
						}
					break;

					case "MANUAL":
						if ($tab_atual == "reclamacao_produto") {
							$pedir_produto = true;
						}
						else {
							$categoria = "";
						}
					break;

					case "EMPRESA":
						if ($tab_atual == "reclamacao_empresa") {
						}
						else {
							$categoria = "";
						}
					break;

					case "ASSISTÊNCIA TÉCNICA":
						if ($tab_atual == "reclamacao_at") {
						}
						else {
							$categoria = "";
						}
					break;

					case "REVENDA":
						if ($tab_atual == "onde_comprar") {
						}
						else {
							$categoria = "";
						}
					break;

					default:
						$categoria = "";
				}
			}
			if($login_fabrica==11){//HD 53881 27/11/2008
				$tipo_reclamacao = $_POST['tipo_reclamacao'];
				if($tab_atual=="reclamacao_at" AND strlen($tipo_reclamacao)==0){
					$msg_erro = "Escolha o Tipo da Reclamação";
				}

				$sub_tipo_reclamacao = array("mau_atendimento","posto_nao_contribui","demonstra_desorg","possui_bom_atend","demonstra_org","reclamacao_at_info");
				if(in_array($tipo_reclamacao, $sub_tipo_reclamacao)){
					$tab_atual       = $tipo_reclamacao;
				}

				$reclamado       = $_POST['reclamado_at'];
				if(strlen($reclamado)>0){
					$xreclamado = "'" . $reclamado . "'";
				}else{
					$xreclamado = "null";
				}
			}

			if(strlen($chamado_interno)>0){$xchamado_interno = "'t'";}else{$xchamado_interno="'f'";}
			if(strlen($transferir)==0){$xtransferir = $login_admin;}else{$xtransferir = $transferir;}
			if(strlen($status_interacao)>0){ $xstatus_interacao = "'".$status_interacao."'";}
			if(strlen($tab_atual)==0 and $login_fabrica==25)      { $tab_atual = "extensao"; }
			if(strlen($tab_atual)==0 and $login_fabrica<>25)      { $tab_atual = "reclamacao_produto"; }
			$xconsumidor_revenda        = "'C'";
			if(strlen(trim($_POST['consumidor_revenda']))>0) {
				$xconsumidor_revenda    = "'".trim($_POST['consumidor_revenda'])."'";
			}

			$xorigem                    = "'".trim($_POST['origem'])."'";

			$receber_informacoes       = $_POST['receber_informacoes'];
			$hora_ligacao              = $_POST['hora_ligacao'];
			if(strlen($hora_ligacao)==0){$xhora_ligacao = "null";}else{$xhora_ligacao = "'$hora_ligacao".":00'";}
			$defeito_reclamado         = $_POST['defeito_reclamado'];
			$consumidor_nome           = trim($_POST['consumidor_nome']);
			$cliente                   = trim($_POST['cliente']);
			$consumidor_cpf            = checaCPF($_POST['consumidor_cpf'],false);
			$atendimento_callcenter    = trim($_POST['atendimento_callcenter']);

			if (is_numeric($consumidor_cpf)) {
			    $mask = (strlen($consumidor_cpf) == 14) ? '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/':'/(\d{3})(\d{3})(\d{3})(\d{2})/';
			    $fmt  = (strlen($consumidor_cpf) == 14) ? '$1.$2.$3/$4-$5':'$1.$2.$3-$4';
				$consumidor_cpf = preg_replace($mask, $fmt, $consumidor_cpf);
			} else {
				$consumidor_cpf  = "null";
	//  28/12/2009  Lembrando que só deve dar erro se o usuário digitou um CPF/CNPJ...
				if (strlen($_POST['consumidor_cpf']) != 0) $msg_erro = "CPF do consumidor inválido";
			}


			$consumidor_rg             = trim($_POST['consumidor_rg']);
			$consumidor_rg             = preg_replace("/\W/","",$consumidor_rg);
			$consumidor_email          = trim($_POST['consumidor_email']);
			$consumidor_fone           = trim($_POST['consumidor_fone']);
			$consumidor_fone           = str_replace("'","",$consumidor_fone);
			$consumidor_fone2          = trim($_POST['consumidor_fone2']);
			$consumidor_fone2          = str_replace("'","",$consumidor_fone2);
			$consumidor_fone3          = trim($_POST['consumidor_fone3']);
			$consumidor_fone3          = str_replace("'","",$consumidor_fone3);
			$consumidor_cep            = trim($_POST['consumidor_cep']);
			$consumidor_cep		   = substr(preg_replace( '/[^0-9]+/', '', $consumidor_cep), 0, 8);
			$consumidor_endereco       = trim($_POST['consumidor_endereco']);
			$consumidor_numero         = trim($_POST['consumidor_numero']);
			$consumidor_numero         = str_replace("'","",$consumidor_numero);
			$consumidor_complemento    = trim($_POST['consumidor_complemento']);
			$consumidor_bairro         = trim($_POST['consumidor_bairro']);
			$consumidor_cidade         = trim(strtoupper($_POST['consumidor_cidade']));
			$consumidor_estado         = trim(strtoupper($_POST['consumidor_estado']));
			$origem                    = $_POST['origem'];
			$consumidor_revenda        = $_POST['consumidor_revenda'];
			$consumidor_cpf_cnpj		= $_POST['consumidor_cpf_cnpj'];
			$cnpj_revenda				= $_POST['cnpj_revenda'];
			$nome_revenda				= substr($_POST['nome_revenda'], 0, 50);
			
			if (strlen($cnpj_revenda)) {
				@$res_cnpj_revenda = pg_query($con, "SELECT fn_valida_cnpj_cpf('$cnpj_revenda')");
				if (pg_errormessage($con)) {
					$msg_erro = "Erro na validação do CNPJ da Revenda: " . substr(pg_errormessage($con), 6);
				}
			}

			if($indicacao_posto=='t' and $login_fabrica <> 24){
				$consumidor_nome='Indicação de Posto';
				$consumidor_fone='00000000000';
				$consumidor_estado='00';
				$consumidor_cidade='Indicação de Posto';
				$consumidor_revenda='Indicação de Posto';
				$origem='Indicação de Posto';
				$consumidor_cpf='00000000000';
				$consumidor_cep='00000000';
				$produto_referencia='Indicação de Posto';
				$hora_ligacao='00:00';
			}elseif($indicacao_posto=='t' and $login_fabrica == 24){
				if(strlen($_POST['produto_referencia']) == 0 or strlen($_POST['produto_nome']) == 0) {
					$msg_erro .= "Por favor, informe a referência e a descrição do Produto ";
				}
			}

			if ($login_fabrica == 24) {
				if ($pedir_produto) {
					if(strlen($_POST['produto_referencia']) == 0 or strlen($_POST['produto_nome']) == 0) {
						$msg_erro .= "Por favor, informe a referência e a descrição do Produto";
					}
				}				
			}

			$xconsumidor_nome        = (strlen($consumidor_nome)==0)  ? "null" : "'".$consumidor_nome."'";
			$xconsumidor_cpf         = (!is_numeric(checaCPF($consumidor_cpf)) and strlen(trim($consumidor_cpf)) != 0) ? "null" : "'".checaCPF($consumidor_cpf)."'";
			$xconsumidor_rg          = (strlen($consumidor_rg)==0)    ? "null" : "'".$consumidor_rg."'";
			$xconsumidor_email       = (strlen($consumidor_email)==0) ? "null" : "'".$consumidor_email."'";
			$xconsumidor_fone        = (strlen($consumidor_fone)==0)  ? "null" : "'".$consumidor_fone."'";
			$xconsumidor_fone2       = (strlen($consumidor_fone2)==0) ? "null" : "'".$consumidor_fone2."'";
			$xconsumidor_fone3       = (strlen($consumidor_fone3)==0) ? "null" : "'".$consumidor_fone3."'";
			$xconsumidor_cep         = (strlen($consumidor_cep)==0)   ? "null" : "'".$consumidor_cep."'";
			$xconsumidor_endereco    = (strlen($consumidor_endereco)== 0) ? "null" : "'".$consumidor_endereco."'";
			$xconsumidor_numero      = (strlen($consumidor_numero)==0) ? "null" : "'".$consumidor_numero."'";
			$xconsumidor_complemento = (strlen($consumidor_complemento)==0) ? "null" :"'".$consumidor_complemento."'";
			$xconsumidor_bairro      = (strlen($consumidor_bairro)==0) ? "null" :"'".$consumidor_bairro."'";
			$xconsumidor_cidade      = (strlen($consumidor_cidade)==0) ? "null" :"'".$consumidor_cidade."'";
			$xconsumidor_estado      = (strlen($consumidor_estado)==0) ? "null" : "'".$consumidor_estado."'";

			if($login_fabrica== 3 or $login_fabrica == 24 or ($login_fabrica==5 and $indicacao_posto=='f') or $login_fabrica == 85){ // HD 48900 58796
				if(strlen($consumidor_nome)==0){
					$msg_erro .= "Por favor inserir o nome do consumidor ";
				}
				else {
					$consumidor_nome = substr($consumidor_nome, 0, 50);
				}
				if(strlen($consumidor_cep)==0 and ($login_fabrica <> 24 and $login_fabrica <> 85)){
					$msg_erro .= "Por favor inserir o cep do consumidor ";
				}
				if(strlen($consumidor_bairro)==0){
					$msg_erro .= "Por favor inserir o bairro do consumidor ";
				}
				if(strlen($consumidor_endereco)==0){
					$msg_erro .= "Por favor inserir o endereco do consumidor ";
				}
				if(strlen($consumidor_fone)==0){
					$msg_erro .= "Por favor inserir o telefone do consumidor ";
				}
				if(strlen($consumidor_estado)==0){
					$msg_erro .= "Por favor selecione o estado ";
				}
				if(strlen($consumidor_cidade)==0){
					$msg_erro .= "Por favor inserir a cidade ";
				}
				if ($login_fabrica == 3) {
					if(strlen(trim($_POST['consumidor_revenda'])) ==0) {
						$msg_erro .= "Por favor selecione o tipo (Consumidor ou Revenda) ";
					}
					if(strlen(trim($_POST['origem'])) ==0) {
						$msg_erro .= "Por favor selecione a origem ";
					}
				}

				if ($login_fabrica == 5) { // HD 59786
					if(checaCPF($_POST['consumidor_cpf'],false)===false) {
						$msg_erro .= "Por favor inserir o CPF do consumidor ";
					}
					if(strlen(trim($_POST['consumidor_cep'])) ==0) {
						$msg_erro .= "Por favor inserir CEP do consumidor ";
					}

					if (strlen($_POST["produto_referencia"]) == 0) {
						$msg_erro .= "Por favor, insira a referência do produto ";
					}
				}
			}elseif($indicacao_posto=='f') {
				if(strlen($consumidor_nome)>0 and strlen($consumidor_estado)==0){
					$msg_erro .= "Por favor selecione o estado";
				}
				if(strlen($consumidor_nome)>0 and strlen($consumidor_cidade)==0){
					$msg_erro .= "Por favor inserir a cidade";
				}
			}

			$abre_os                   = trim($_POST['abre_os']);
			//HD 205933: Habilitar abertura de ORDEM DE SERVIÇO para a Esmaltec
			$abre_ordem_servico        = trim($_POST['abre_ordem_servico']);
			$imprimir_os               = trim($_POST['imprimir_os']);
			$resposta                  = trim($_POST['resposta']);
			$posto_tab                 = trim(strtoupper($_POST['posto_tab']));
			$codigo_posto_tab          = trim(strtoupper($_POST['codigo_posto_tab']));
			$posto_nome_tab            = trim(strtoupper($_POST['posto_nome_tab']));
			$posto_endereco_tab        = trim(strtoupper($_POST['posto_endereco_tab']));
			$posto_cidade_tab          = trim(strtoupper($_POST['posto_cidade_tab']));
			$posto_estado_tab          = trim(strtoupper($_POST['posto_estado_tab']));
			$posto_fone_tab            = trim(strtoupper($_POST['posto_fone_tab']));
			$posto_email_tab           = trim(strtoupper($_POST['posto_email_tab']));
			$posto_km_tab              = trim(strtoupper($_POST['posto_km_tab']));
			$revenda_nome              = substr(trim($_POST['revenda_nome']), 0, 50);
			$revenda_endereco          = trim($_POST['revenda_endereco']);
			$revenda_nro               = trim($_POST['revenda_nro']);
			$revenda_cmpto             = trim($_POST['revenda_cmpto']);
			$revenda_bairro            = trim($_POST['revenda_bairro']);
			$revenda_city              = trim($_POST['revenda_city']);
			$revenda_uf                = trim($_POST['revenda_uf']);
			$revenda_fone              = trim($_POST['revenda_fone']);

			$hd_extra_defeito          = trim($_POST['hd_extra_defeito']);
			$faq_situacao              = trim($_POST['faq_situacao']);

			$reclama_posto             = trim($_POST['tipo_reclamacao']);

			$xresposta = (strlen($resposta)==0) ? "null" : "'".$resposta."'";
			$xreceber_informacoes = (strlen($receber_informacoes)>0) ? "'$receber_informacoes'" : "'f'";

			if ($abre_os <> 't') {
				$abre_os = 'f';
			}
			
			if( $login_fabrica == 91 || $login_fabrica == 90 ){

				$hd_extra_defeito = $_POST[ 'hd_extra_defeito' ] ?  $_POST[ 'hd_extra_defeito' ] : $_GET[ 'hd_extra_defeito' ]; 
				$reclamado_produto = $_POST[ 'reclamado_produto' ] ?  $_POST[ 'reclamado_produto' ] : $_GET[ 'reclamado_produto' ];
			
				if(  (strlen( $hd_extra_defeito ) == 0 || strlen( $reclamado_produto ) == 0) and  $tab_atual == "reclamacao_produto" ){
					$msg_erro = "Digite o defeito do produto";
				}
			}
			//HD 205933: Habilitar abertura de ORDEM DE SERVIÇO para a Esmaltec
			if ($abre_ordem_servico <> 't') {
				$abre_ordem_servico = 'f';
			}

			if ($login_fabrica == 24) {
				if($tab_atual == "outros_assuntos"){
					$reclamado          = trim($_POST['outros_assuntos_descricao']);
					if (strlen($reclamado)==0) {
						$msg_erro = 'Digite a descrição de outros assuntos';
					} else {
						$xreclamado = "'".$reclamado."'";
					}
				}
			}


			if($tab_atual == "extensao"){
				$produto_referencia = $_POST['produto_referencia_es'];
				$produto_nome       = $_POST['produto_nome_es'];
				$reclamado          = trim($_POST['reclamado_es']);
				if(strlen($reclamado)==0) {
					$xreclamado = "null";
				}else{
					$xreclamado = "'".$reclamado."'";
				}


				$xserie = $_POST['serie'];
				if(strlen($_POST["serie_es"])>0) $xserie = $_POST['serie_es'];

				//HD 12749
				if(strlen($produto_referencia) == 0){
					$msg_erro.=" Insira a referência do produto\n ";
				}
				if(strlen($produto_nome) == 0){
					$msg_erro.=" Insira nome do produto\n ";
				}
				if(strlen($xserie) == 0){
					$msg_erro.=" Insira o número de série do produto\n ";
				}

				$es_id_numeroserie        = $_POST['es_id_numeroserie'];
				$es_revenda_cnpj          = $_POST['es_revenda_cnpj'];

				$es_revenda               = $_POST['es_revenda'];
				if(strlen($es_revenda)==0){
					$xes_revenda = "NULL";
				}else{
					$xes_revenda = "'".$es_revenda."'";
				}

				$es_nota_fiscal           = $_POST['es_nota_fiscal'];
				if(strlen($es_nota_fiscal)==0){
					$xes_nota_fiscal = "NULL";
				}else{
					$xes_nota_fiscal = "'".$es_nota_fiscal."'";
				}

				$es_data_compra           = $_POST['es_data_compra'];
				if(strlen($es_data_compra)==0){
					$xes_data_compra = "NULL";
				}else{
					$xes_data_compra = "'".converte_data($es_data_compra)."'";
				}

				$es_municipiocompra       = $_POST['es_municipiocompra'];
				if(strlen($es_municipiocompra)==0){
					$xes_municipiocompra = "NULL";
				}else{
					$xes_municipiocompra = "'".$es_municipiocompra."'";
				}

				$es_estadocompra          = $_POST['es_estadocompra'];
				if(strlen($es_estadocompra)==0){
					$xes_estadocompra = "NULL";
				}else{
					$xes_estadocompra = "'".$es_estadocompra."'";
				}

				$es_data_nascimento       = $_POST['es_data_nascimento'];
				if(strlen($es_data_nascimento)==0){
					$xes_data_nascimento = "NULL";
				}else{
					$xes_data_nascimento = "'".converte_data($es_data_nascimento)."'";
				}

				$es_estadocivil           = $_POST['es_estadocivil'];
				if(strlen($es_estadocivil)==0){
					$xes_estadocivil = "NULL";
				}else{
					$xes_estadocivil = "'".$es_estadocivil."'";
				}

				$es_sexo                  = $_POST['es_sexo'];
				if(strlen($es_sexo)==0){
					$xes_sexo = "NULL";
				}else{
					$xes_sexo = "'".$es_sexo."'";
				}

				$es_filhos                = $_POST['es_filhos'];
				if(strlen($es_filhos)==0){
					$xes_filhos = "NULL";
				}else{
					$xes_filhos = "'".$es_filhos."'";
				}

				$es_fonecomercial         = $_POST['es_fonecomercial'];
				if(strlen($es_fonecomercial)==0){
					$xes_dddcomercial = " NULL ";
					$xes_fonecomercial = "NULL";
				}else{
					$xes_dddcomercial = "'".substr($es_fonecomercial,1,2)."'";
					$xes_fonecomercial = "'".substr($es_fonecomercial,5,9)."'";
				}

				$es_celular               = $_POST['es_celular'];
				if(strlen($es_celular)==0){
					$xes_dddcelular = " NULL ";
					$xes_celular    = "NULL";
				}else{
					$xes_dddcelular = "'".substr($es_celular,1,2)."'";
					$xes_celular = "'".substr($es_celular,5,9)."'";
				}

				$es_preferenciamusical    = $_POST['es_preferenciamusical'];
				if(strlen($es_preferenciamusical)==0){
					$xes_preferenciamusical = "NULL";
				}else{
					$xes_preferenciamusical = "'".$es_preferenciamusical."'";
				}
			}

			if($tab_atual == "reclamacao_produto"){
				$produto_referencia = $_POST['produto_referencia'];
				$produto_nome       = $_POST['produto_nome'];
				$voltagem           = $_POST['voltagem'];
				$reclamado          = trim($_POST['reclamado_produto']);
				$xserie             = $_POST['serie'];
				
				if($login_fabrica == 43 or $login_fabrica == 14) {
					$ordem_montagem     = $_POST['ordem_montagem'];
					$codigo_postagem    = $_POST['codigo_postagem'];
				}
				
				
				if($login_fabrica == 85) { // HD 237892
					if(strlen($produto_referencia) == 0){
						$msg_erro.=" Insira a referência do produto\n ";
					}
					if(strlen($produto_nome) == 0){
						$msg_erro.=" Insira nome do produto\n ";
					}

					if (strlen($_POST['voltagem'])== 0){
						$msg_erro .= "Informe voltagem do produto.";
					}
					//289254 retirado a pedido do tulio reuniao gelopar 04/08
					/*
					if (strlen($_POST['data_nf'])== 0){
						$msg_erro .= "Informe a data da Nota fiscal.";
					}

					if (strlen($_POST['nota_fiscal'])== 0){
						$msg_erro .= "Informe a nota fiscal.";
					}
					*/
					if (strlen($_POST['nome_revenda'])== 0){
						$msg_erro .= "Informe a revenda.";
					}
				}
				if(strlen($reclamado)==0){
					$xreclamado = "null";
				}else{
					$xreclamado = "'".$reclamado."'";
				}
			}

			if($tab_atual == "reclamacao_at"){
				$reclamado          = trim($_POST['reclamado_at']);
				$xserie             = $_POST['serie'];
				if(strlen($reclamado)==0){
					$msg_erro = "Insira a reclamação";
				}else{
					$xreclamado = "'".$reclamado."'";
				}
			}

			$posto_nome           = $_POST['posto_nome'];
			$codigo_posto         = $_POST['codigo_posto'];
			$procon_posto_nome    = $_POST['procon_posto_nome'];
			$procon_codigo_posto  = $_POST['procon_codigo_posto'];
			$reclamacao_procon    = $_POST['reclamacao_procon'];

			if ($login_fabrica == 2 AND $reclama_posto <> 'reclamacao_at'){
				$codigo_posto = "";
			}

			/*
			if ($login_fabrica == 2 AND $reclama_posto == 'reclamacao_at'){
				if(strlen($codigo_posto) == 0){
					$msg_erro .= "Ao selecionar Reclamação da Assitência Técnica  <br/>
						obrigatório informar qual foi a assistência que gerou a reclamação.";
				}
			}*/

			if(strlen($codigo_posto_tab)>0){
				$sql = "SELECT posto
						from tbl_posto_fabrica
						where codigo_posto='$codigo_posto_tab'
						and fabrica = $login_fabrica";
				$res = pg_query($con,$sql);

				if(pg_num_rows($res)>0){
					$mr_codigo_posto = pg_fetch_result($res,0,0);
					$sqlMr = "SELECT endereco, numero, cidade, estado
							FROM tbl_posto
							WHERE posto = $mr_codigo_posto";
					$resMr = pg_query($con,$sqlMr);

					if (pg_num_rows($resMr)>0){
						$endereco_posto_tab = pg_fetch_result($resMr,0,endereco);
						$numero_posto_tab = pg_fetch_result($resMr,0,numero);
						$posto_endereco_tab = "$endereco_posto_tab, $numero_posto_tab";
						$posto_cidade_tab = pg_fetch_result($resMr,0,cidade);
						$posto_estado_tab = pg_fetch_result($resMr,0,estado);
					}
				}
			}

			if(strlen($codigo_posto)==0){
					$xcodigo_posto = "null";
			}else{
				$sql = "SELECT posto
						from tbl_posto_fabrica
						where codigo_posto='$codigo_posto'
						and fabrica = $login_fabrica";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res)>0){
					$xcodigo_posto = pg_fetch_result($res,0,0);
				}else{
					$xcodigo_posto = "null";
				}
			}

			if($login_fabrica ==11) {
				if(strlen($procon_codigo_posto)==0){ // HD 55995
						$xcodigo_posto = "null";
				}else{
					$sql = "SELECT posto
							from tbl_posto_fabrica
							where codigo_posto='$procon_codigo_posto'
							and fabrica = $login_fabrica";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res)>0){
						$xcodigo_posto = pg_fetch_result($res,0,0);
					}else{
						$xcodigo_posto = "null";
					}
				}
			}

			$os               = trim($_POST['os']);
			if(strlen($os)==0){
				$xos = "null";
			}else{
				$sql = "SELECT os from tbl_os where sua_os='$os' and fabrica=$login_fabrica";
				//echo $sql;
				$res = pg_query($con,$sql);
				if(pg_num_rows($res)>0){
					$xos = pg_fetch_result($res,0,0);
				}else{
					$msg_erro .= "OS informada não encontrada no sistema";
				}
			}

			if ((strlen($xos)==0 or ($xos=='null')) and $_POST['os_ressarcimento'] != '') {
				$xos = $_POST['os_ressarcimento'];
			}

			if($tab_atual == "reclamacao_empresa"){
				$reclamado                 = trim($_POST['reclamado_empresa']);
				if(strlen($reclamado)==0){
					$msg_erro = "Insira a reclamação";
				}else{
					$xreclamado = "'".$reclamado."'";
				}
			}

			if($tab_atual == "reclamacoes"){
				$reclamado                 = trim($_POST['reclamado']);
				$tipo_reclamado            = trim($_POST['tipo_reclamacao']);
				if(strlen($reclamado)==0){
					$msg_erro = "Insira a reclamação";
				}else{
					$xreclamado = "'".$reclamado."'";
				}
			}
			
			if ($login_fabrica == 2) {
				if($tab_atual == "reclamacao_produto"){
					$reclamado                 = trim($_POST['reclamado_produto']);
					$tipo_reclamado            = trim($_POST['tipo_reclamacao']);
					if(strlen($reclamado)==0){
						$msg_erro = "Insira a reclamação";
					}else{
						$xreclamado = "'".$reclamado."'";
					}
				}

				if($tab_atual == "reclamacoes"){
					$reclamado                 = trim($_POST['reclamado']);
					$tipo_reclamado            = trim($_POST['tipo_reclamacao']);
					if(strlen($reclamado)==0){
						$msg_erro = "Insira a reclamação";
					}else{
						$xreclamado = "'".$reclamado."'";
					}
				}

				if($tab_atual == "duvida_produto"){
					$reclamado                 = trim($_POST['faq_duvida_duvida']);
					$tipo_reclamado            = trim($_POST['tipo_reclamacao']);
					if(strlen($reclamado)==0){
						$msg_erro = "Insira a reclamação";
					}else{
						$xreclamado = "'".$reclamado."'";
					}
				}
			}

			if($tab_atual == "sugestao"){
				$reclamado                 = trim($_POST['reclamado_sugestao']);
				if(strlen($reclamado)==0){
					$msg_erro .= "Insira a sugestão";
				}else{
					$xreclamado = "'".$reclamado."'";
				}
			}

			if($tab_atual == "assistencia"){
				$produto_referencia = $_POST['produto_referencia_pa'];
				$produto_nome       = $_POST['produto_nome_pa'];
				$xserie             = $_POST['serie_pa'];
				$reclamado     = trim($_POST['reclamado_pa']);
				if(strlen($reclamado)==0){
					$xreclamado = "null";
				}else{
					$xreclamado = "'".$reclamado."'";
				}
			}

			if($tab_atual == "procon"){
				$reclamado     = trim($_POST['reclamado_procon']);
				if(strlen($reclamado)==0){
					$xreclamado = "null";
				}else{
					$xreclamado = "'".$reclamado."'";
				}
				if(strlen($reclamacao_procon) > 0) {
					$sub_reclamacao_procon = array("pr_reclamacao_at","pr_info_at","pr_mau_atend","pr_posto_n_contrib","pr_demonstra_desorg","pr_bom_atend","pr_demonstra_org");
					if(in_array($reclamacao_procon, $sub_reclamacao_procon)){
						$tab_atual       = $reclamacao_procon;
					}
				}
			}

			if($tab_atual == "garantia"){
				$produto_referencia = $_POST['produto_referencia_garantia'];
				$produto_nome       = $_POST['produto_nome_garantia'];
				$xserie             = $_POST['serie_garantia'];
				$reclamado     = trim($_POST['reclamado_produto_garantia']);
				if(strlen($reclamado)==0){
					$xreclamado = "null";
				}else{
					$xreclamado = "'".$reclamado."'";
				}
			}

			if($tab_atual == "troca_produto"){
				$produto_referencia = $_POST['troca_produto_referencia'];
				$produto_nome       = $_POST['troca_produto_nome'];
				$reclamado          = trim($_POST['troca_produto_descricao']);
				$xserie             = $_POST['troca_serie'];
				if(strlen($reclamado)==0){
					$xreclamado = "null";
				}else{
					$xreclamado = "'".$reclamado."'";
				}
				if(strlen($produto_referencia) == 0 AND strlen($produto_nome) == 0){
					$msg_erro = "Por favor escolha o produto.";
				}
			}
			$xrevenda      = 'null';
			$xrevenda_nome = "''";

			if($tab_atual == "onde_comprar"){
				$revenda          = $_POST['revenda'];
				$revenda_cnpj     = $_POST['revenda_cnpj'];
				$revenda_nome     = substr(trim($_POST['revenda_nome']), 0, 50);
				$revenda_endereco = trim($_POST['revenda_endereco']);
				$revenda_nro      = trim($_POST['revenda_nro']);
				$revenda_cmpto    = trim($_POST['revenda_cmpto']);
				$revenda_bairro   = trim($_POST['revenda_bairro']);
				$revenda_city     = trim($_POST['revenda_city']);
				$revenda_uf       = trim($_POST['revenda_uf']);
				$revenda_fone     = trim($_POST['revenda_fone']);
				$reclamado        = trim($_POST['reclamado_onde_comprar']);

				$xrevenda      = ($revenda != '') ? $revenda : 'null';
				$xrevenda_nome = "'$xrevenda_nome'";
			}
			else {
				if (strlen($nome_revenda)) {
					$xrevenda_nome = "'$nome_revenda'";
				}
			}

			if($tab_atual == "ressarcimento"){
				$banco             = trim($_POST['banco']);
				$agencia           = trim($_POST['agencia']);
				$contay            = trim($_POST['contay']);
				$nomebanco         = trim($_POST['nomebanco']);
				$tipo_conta        = trim($_POST['tipo_conta']);
				$favorecido_conta  = trim($_POST['favorecido_conta']);
				$cpf_conta         = trim($_POST['cpf_conta']);
				$reclamado         = trim($_POST['obs_ressarcimento']);

				$valor_produto     = trim($_POST['valor_produto']);
				$valor_inpc        = trim($_POST['valor_inpc']);
				$valor_corrigido   = trim($_POST['valor_corrigido']);

				$reclamado          = trim($_POST['troca_produto_descricao']);

				$data_pagamento    = trim($_POST['data_pagamento']);
				$procon            = trim($_POST['procon']);
				$numero_processo   = trim($_POST['numero_processo']);

				$valor_produto     = str_replace(",",".",$valor_produto);
				$valor_inpc        = str_replace(",",".",$valor_inpc);
				$valor_corrigido   = str_replace(",",".",$valor_corrigido);

				if (strlen($xos)==0 or $xos == 'null') {
					$msg_erro .= "Para fazer o ressarcimento é necesário ter uma Ordem de Serviço Aberta";
				}
				
				if(strlen($banco)==0){
					$xbanco = "null";
				}else{
					$xbanco = "'".$banco."'";
				}
				if(strlen($agencia)==0){
					$xagencia = "null";
				}else{
					$xagencia = "'".$agencia."'";
				}
				if(strlen($contay)==0){
					$xcontay = "null";
				}else{
					$xcontay = "'".$contay."'";
				}
				if(strlen($nomebanco)==0){
					$xnomebanco = "null";
				}else{
					$xnomebanco = "'".$nomebanco."'";
				}
				if(strlen($tipo_conta)==0){
					$xtipo_conta = "null";
				}else{
					$xtipo_conta = "'".$tipo_conta."'";
				}
				if(strlen($favorecido_conta)==0){
					$xfavorecido_conta = "null";
				}else{
					$xfavorecido_conta = "'".$favorecido_conta."'";
				}
				if(strlen($cpf_conta)==0){
					$xcpf_conta = "null";
				}else{
					$xcpf_conta = "'".$cpf_conta."'";
				}
				if(strlen($obs_conta)==0){
					$xobs_conta = "null";
				}else{
					$xobs_conta = "'".$obs_conta."'";
				}

				if(strlen($data_pagamento)==0){
					$xdata_pagamento = "null";
				}else{
					$xdata_pagamento = "'".$data_pagamento."'";
				}
			}

			if($tab_atual == "sedex_reverso"){
				$troca_produto_referencia = trim($_POST['troca_produto_referencia']);
				$troca_produto_nome       = trim($_POST['troca_produto_nome']);
				$reclamado                = trim($_POST['troca_observacao']);

				$numero_objeto        = trim($_POST['numero_objeto']);
				$nota_fiscal_saida    = trim($_POST['nota_fiscal_saida']);
				$data_nf_saida        = trim($_POST['data_nf_saida']);
				$data_retorno_produto = trim($_POST['data_retorno_produto']);

				$procon            = trim($_POST['procon2']);
				$numero_processo   = trim($_POST['numero_processo2']);

				if(strlen($nota_fiscal_saida)==0){
					$xnota_fiscal_saida = "null";
				}else{
					$xnota_fiscal_saida = "'".$nota_fiscal_saida."'";
				}

				if(strlen($data_nf_saida)==0){
					$xdata_nf_saida = "null";
				}else{
					$xdata_nf_saida = "'".converte_data($data_nf_saida)."'";
				}

				if(strlen($data_retorno_produto)==0){
					$xdata_retorno_produto = "null";
				}else{
					$xdata_retorno_produto = "'".converte_data($data_retorno_produto)."'";
				}

				if(strlen($numero_objeto)==0){
					$xnumero_objeto = "null";
				}else{
					$xnumero_objeto = "'".$numero_objeto."'";
				}

				if(strlen($produto_referencia) == 0 AND strlen($produto_nome) == 0){
					$msg_erro = "Por favor escolha o produto.";
				}
			}

			if(strlen($valor_produto)==0){
				$xvalor_produto = "null";
			}else{
				$xvalor_produto = $valor_produto;
			}
			if(strlen($valor_inpc)==0){
				$xvalor_inpc = "null";
			}else{
				$xvalor_inpc = $valor_inpc;
			}
			if(strlen($valor_corrigido)==0){
				$xvalor_corrigido = "null";
			}else{
				$xvalor_corrigido = $valor_corrigido;
			}

			if(strlen($numero_processo)==0){
				$xnumero_processo = "null";
			}else{
				$xnumero_processo = "'".$numero_processo."'";
			}

			if (strlen($cliente)==0){
				$cliente = "null";
			}

			if(strlen($faq_situacao) > 0){
				$produto_referencia = $_POST['produto_referencia'];
			}
			if(strlen($_POST['produto_referencia']) > 0 && $login_fabrica == 30){ //hd 311031
				$produto_referencia = $_POST['produto_referencia'];
			}

			if(strlen($defeito_reclamado)==0) {
				$xdefeito_reclamado  = "null";
			} else {
				$xdefeito_reclamado = $defeito_reclamado;
			}

			if ($login_fabrica <> 2) {
				if(strlen($reclamado)==0){
					$xreclamado          = "null";
				}else {
					$xreclamado = "'".$reclamado."'";
				}
			}

			if(strlen($produto_referencia)>0){
				if($login_fabrica == 96)
					$cond_produto = "tbl_produto.referencia_fabrica = '$produto_referencia'";
				else
					$cond_produto = "tbl_produto.referencia = '$produto_referencia'";
				$sql = "SELECT tbl_produto.produto
							FROM  tbl_produto
							join  tbl_linha on tbl_produto.linha = tbl_linha.linha
							WHERE $cond_produto
							and tbl_linha.fabrica = $login_fabrica
							limit 1";
				//echo $sql;
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
				if(pg_num_rows($res)>0){
					$xproduto = pg_fetch_result($res,0,0);
				}else{
					if ($tab_atual == "reclamacao_produto"){
						$msg_erro = "Produto $produto_referencia informado não encontrado no sistema";
					}
					$xproduto = "null";
				}
			}else{
					$xproduto = "null";
			}

			if(strlen($troca_produto_referencia)>0){
				$sql = "SELECT tbl_produto.produto
							FROM  tbl_produto
							join  tbl_linha on tbl_produto.linha = tbl_linha.linha
							WHERE tbl_produto.referencia = '$troca_produto_referencia'
							and tbl_linha.fabrica = $login_fabrica
							limit 1";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
				if(pg_num_rows($res)>0){
					$xproduto_troca = pg_fetch_result($res,0,0);
				}else{
					$xproduto_troca = "null";
				}
			}else{
					$xproduto_troca = "null";
			}

			if(strlen($faq_situacao) > 0){ // HD 45991
				$sql = "INSERT INTO tbl_faq (
					situacao,
					produto
				) VALUES (
					'$faq_situacao',
					$xproduto
				);";
				$res = @pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
				if(strlen($msg_erro) ==0 ){
					$sql = "SELECT email_cadastros FROM tbl_fabrica WHERE fabrica = $login_fabrica";
					$res=pg_query($con,$sql);
					if(pg_num_rows($res) > 0){
						$email_cadastros = pg_fetch_result($res,0,email_cadastros);
						$admin_email = "helpdesk@telecontrol.com.br";
						$remetente    = $admin_email;
						$destinatario = $email_cadastros ;
						$assunto      = "Nova dúvida cadastrada";
						$mensagem     = "Prezado, <br> Foi cadastrada uma nova dúvida no sistema para o produto $produto_referencia:<br>  - $faq_situacao <br><br>Por favor, entre na aba <b>Cadastro - Perguntas Frequentes</b> para cadastrar causa e solução da mesma. <br>Att <br>Equipe Telecontrol";
						$headers="Return-Path: <$admin_email>\nFrom:".$remetente."\nContent-type: text/html\n";
						mail($destinatario,$assunto,$mensagem,$headers);
					}
				}
			}

			#HD Chamado 13106 Bloqueia
			#HD Chamado 21419 DESBloqueia
			if ( $login_fabrica==25 AND strlen($xserie)>0 AND 1==2){
				$sql = "SELECT tbl_hd_chamado_extra.hd_chamado
						FROM tbl_hd_chamado_extra
						JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado=tbl_hd_chamado_extra.hd_chamado
						WHERE tbl_hd_chamado.fabrica        = $login_fabrica
						AND   tbl_hd_chamado_extra.serie    = '$xserie' ";
						//AND   tbl_hd_chamado_extra.produto  = $xproduto
				if (strlen($callcenter)>0){
					$sql .= " AND tbl_hd_chamado_extra.hd_chamado <> $callcenter ";
				}
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
				if(pg_num_rows($res)>0){
					$hd_chamado_serie = pg_fetch_result($res,0,0);
					$msg_erro .= "Número de série $xserie já cadastrado anteriormente. Número do chamado: <a href='$PHP_SELF?callcenter=$hd_chamado_serie' target='_blank'>$hd_chamado_serie</a> ";
				}
			}

			if(strlen($xserie)==0){$xserie="null";}else{$xserie = "'".$xserie."'";}

			if($login_fabrica ==11) { // HD 45078
				$xconsumidor_nome        = acentos1($xconsumidor_nome);
				$xconsumidor_nome        = acentos3($xconsumidor_nome);
				$xconsumidor_endereco    = acentos1($xconsumidor_endereco);
				$xconsumidor_endereco    = acentos3($xconsumidor_endereco);
				$xconsumidor_numero      = acentos1($xconsumidor_numero);
				$xconsumidor_numero      = acentos3($xconsumidor_numero);
				$xconsumidor_complemento = acentos1($xconsumidor_complemento);
				$xconsumidor_complemento = acentos3($xconsumidor_complemento);
				$xconsumidor_bairro      = acentos1($xconsumidor_bairro);
				$xconsumidor_bairro      = acentos3($xconsumidor_bairro);
				$xconsumidor_cidade      = acentos1($xconsumidor_cidade);
				$xconsumidor_cidade      = acentos3($xconsumidor_cidade);
				$xconsumidor_email       = acentos1($xconsumidor_email);
				$xconsumidor_email       = acentos3($xconsumidor_email);
			}

	//HD 205933: Habilitar abertura de ORDEM DE SERVIÇO para a Esmaltec
	/************************************************************************************************/
	/********************************* VALIDACOES DA ABERTURA DE OS *********************************/
	/************************************************************************************************/
	if(strlen($msg_erro)==0 && $abre_ordem_servico == 't'){

		if(strlen($mr_codigo_posto) == 0) {
			$msg_erro = "Para que a ORDEM DE SERVIÇO seja aberta é necessário escolher um posto";
		}

		$consumidor_cpf = checaCPF($consumidor_cpf,false,true);
		if ($consumidor_cpf===false) {
			$consumidor_cpf = 'null';
			if ($consumidor_cpf == "nul" and strlen($_POST['consumidor_cpf']) != 0){
				$msg_erro = "CPF do consumidor inválido";
			}
		}

		if(strlen($msg_erro)==0){
			if(strlen($data_nf)==0) $xdata_nf = "NULL";
			else                    $xdata_nf = "'".converte_data($data_nf)."'";
		}
	}

	//HD 211895: Separando rotina de PRÉ-OS das rotinas de inserção e atualização
	//			 As validações estão no começo do código, a a inserção depois das rotinas de inserir/atualizar
	/************************************************************************************************/
	/************************************* VALIDACOES DA PRE-OS *************************************/
	/************************************************************************************************/
	if ($login_fabrica == 52 ){
		$cliente_admin           = $_POST['cliente_admin'];
		$cliente_nome_admin      = $_POST['cliente_nome_admin'];

		if (strlen($cliente_admin)==0) {
			$cliente_admin = 'null';
		}

		if(strlen($msg_erro)==0 and strlen($callcenter)==0 and $abre_os=='t'){
			if (strlen($posto_km_tab)==0) {
				$msg_erro .= "É necessario digitar a Qtde de Km, clique em Mapa da Rede<br>";
			}
			if ($xserie<>'null') {
				$sql = "SELECT serie from tbl_numero_serie where serie = $xserie and fabrica = 52";
				$res = pg_query($con,$sql);
				if (pg_num_rows($res)==0) {
					$msg_erro .="Numero de Série Inválido, preencha corretamente ou deixe em branco o campo série";
				}
			}
		}
	}

	if(strlen($msg_erro)==0 and $abre_os=='t'){

		if (($login_fabrica == 2 or $login_fabrica == 52 or $login_fabrica == 80 or $login_fabrica == 81) and (strlen($mr_codigo_posto) == 0)){
			$msg_erro = "Para que a PRÉ-OS seja aberta é necessário escolher um posto";
		}

		if (($login_fabrica == 2 or $login_fabrica == 52 or $login_fabrica == 80 or $login_fabrica == 81) and (strlen($mr_codigo_posto) > 0)){
			$rat_codigo_posto = $xcodigo_posto;
			$xcodigo_posto = $mr_codigo_posto;
		}

		if(strlen($mr_codigo_posto) == 0) {
			if($login_fabrica == 30){
				$msg_erro = "Para que a OS seja aberta é necessário escolher um posto";
			}else{
				$msg_erro = "Para que a PRÉ-OS seja aberta é necessário escolher um posto";
			}
		}
		$xnota_fiscal = "'".$_POST["nota_fiscal"]."'";

		$consumidor_cpf = checaCPF($consumidor_cpf,false,true);
		if ($consumidor_cpf===false) {
			$consumidor_cpf = 'null';
			if ($consumidor_cpf == "nul" and strlen($_POST['consumidor_cpf']) != 0){
				$msg_erro = "CPF do consumidor inválido";
			}
		}

		if(strlen($msg_erro)==0){
			if(strlen($data_nf)==0) $xdata_nf = "NULL";
			else                    $xdata_nf = "'".converte_data($data_nf)."'";
		}
	}

	/************************************************************************************************/
	/******************************************* INSERÇÃO *******************************************/
	/************************************************************************************************/
	if(strlen($callcenter)==0){
	
			if(strlen($msg_erro)==0){
				$res = pg_query ($con,"BEGIN TRANSACTION");
				if(strlen($consumidor_nome)>0 and strlen($consumidor_estado)>0 and strlen($consumidor_cidade)>0){
					$sql = "SELECT tbl_cidade.cidade
								FROM tbl_cidade
								where tbl_cidade.nome = $xconsumidor_cidade
								AND tbl_cidade.estado = $xconsumidor_estado
								limit 1";
						$res = pg_query($con,$sql);
						if(pg_num_rows($res)>0){
							$cidade = pg_fetch_result($res,0,0);
						}else{
							$sql = "INSERT INTO tbl_cidade(nome, estado)values(upper($xconsumidor_cidade),upper($xconsumidor_estado))";
							$res = pg_query($con,$sql);
							$msg_erro .= pg_errormessage($con);
							$res    = pg_query ($con,"SELECT CURRVAL ('seq_cidade')");
							$cidade = pg_fetch_result ($res,0,0);
						}
				}elseif($indicacao_posto=='f') {
					$msg_erro .= "Informe a cidade do consumidor";
				}
			}

			if ($tab_atual == 'reclamacoes') {
				$tab_atual = $tipo_reclamado;
			}
		if($login_fabrica == 2){
			if ($tab_atual == "reclamacao_produto"){
				$up_tipo_reclamacao = array("aquisicao_mat","aquisicao_prod","indicacao_posto","solicitacao_manual");
				if(in_array($tipo_reclamacao, $up_tipo_reclamacao)){
					$tab_atual       = $tipo_reclamacao;
				}
				if ($tab_atual == 'reclamacao_produto'){
					$tab_atual = $tipo_reclamado;
				}
			}
			if ($tab_atual == "reclamacoes"){
				$up_tipo_reclamacao = array("reclamacao_revenda","reclamacao_at","reclamacao_enderecos","reclamacao_produto",
					"reclamacao_conserto", "reclamacao_posto_aut", "reclamacao_orgao_ser", "repeticao_chamado", "reclamacao_outro");
				if(in_array($tipo_reclamacao, $up_tipo_reclamacao)){
					$tab_atual       = $tipo_reclamacao;
				}
				if ($tab_atual == 'reclamacoes'){
					$tab_atual = $tipo_reclamado;
				}
			}
			if ($tab_atual == "duvida_produto"){
				$up_tipo_reclamacao = array("especificacao_manuseio","informacao_manuseio","informacao_tecnica","orientacao_instalacao");
				if(in_array($tipo_reclamacao, $up_tipo_reclamacao)){
					$tab_atual       = $tipo_reclamacao;
				}
				if ($tab_atual == 'duvida_produto'){
					$tab_atual = $tipo_reclamado;
				}
			}
		}

			if (strlen($cliente_admin)==0) {
				$cliente_admin = 'null';
			}

			$protocolo = $_POST['protocolo_id'];

			if(strlen($callcenter_assunto)>0 && is_array($callcenter_assunto)){ #HD 251241
				$callcenter_assunto = trim(implode("", $callcenter_assunto));
			}

			if ($login_fabrica == 24 and $tab_atual <> 'outros_assuntos') {
				if (strlen($callcenter_assunto)) {
					$achou_categoria_assunto = false;
					
					//Localiza o assunto dentro do array $assuntos (definido em callcenter_suggar_assuntos)
					foreach($assuntos as $categoria_assunto => $itens_categoria_assunto) {
						foreach($itens_categoria_assunto as $label_assunto => $bd_assunto) {
							if ($bd_assunto == $callcenter_assunto) {
								$categoria_banco = $callcenter_assunto;
								$achou_categoria_assunto = true;
							}
						}
					}

					if ($achou_categoria_assunto == false) {
						$msg_erro .= "<br>Assunto não encontrado";
					}
				}
				else {
					if ($tab_atual <> 'outros_assuntos' AND $indicacao_posto == 'f') {
							$msg_erro .= "Escolha um assunto";
					}
				}
			}
			else {
				$categoria_banco = $tab_atual;
			}

			if(strlen($msg_erro)==0 and strlen($callcenter)==0 and strlen($protocolo)==0) {
				$titulo = 'Atendimento interativo';
				if($indicacao_posto=='t') $titulo = 'Indicação de Posto';
				$sql = "INSERT INTO tbl_hd_chamado (
							admin                 ,
							cliente_admin         ,
							data                  ,
							status                ,
							atendente             ,
							fabrica_responsavel   ,
							titulo                ,
							categoria             ,
							fabrica
						)values(
							$login_admin            ,
							$cliente_admin           ,
							current_timestamp       ,
							$xstatus_interacao      ,
							$login_admin            ,
							$login_fabrica          ,
							'$titulo'               ,
							'$categoria_banco'            ,
							$login_fabrica
					)";

				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
				$res    = pg_query ($con,"SELECT CURRVAL ('seq_hd_chamado')");
				$hd_chamado = pg_fetch_result ($res,0,0);
			} else {
				if(strlen($msg_erro)==0 and strlen($protocolo)>0) {
					$sql = "UPDATE tbl_hd_chamado set
							admin                 = $login_admin,
							cliente_admin         = $cliente_admin,
							data                  = current_timestamp,
							status                = $xstatus_interacao,
							atendente             = $login_admin,
							fabrica_responsavel   = $login_fabrica,
							titulo                = '$titulo',
							categoria             = '$categoria_banco',
							fabrica               = $login_fabrica
							WHERE hd_chamado = $protocolo";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);
					if (strlen($msg_erro)==0) {
						$hd_chamado = $protocolo;
					}
				}
			}		

			//HD 196942: Controle de postagem
			//o checkbox para solicitar postagem para o chamado só aparece quando não tem solicitação para aquele chamado, portanto só virá alguma coisa por POST em hd_chamado_postagem se não estiver cadastrado e se estiver com o checkbox marcado
			if ($_POST["hd_chamado_postagem"] == "sim" && strlen($hd_chamado) > 0 && strlen($msg_erro) == 0) {
				$sql = "SELECT hd_chamado FROM tbl_hd_chamado_postagem WHERE hd_chamado=$hd_chamado";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res)) {
					$msg_erro = "Já existe solicitação de postagem cadastrada para este chamado. Não é permitido o cadastramento de mais de uma postagem para um mesmo chamado";
				}
				else {
					$sql = "INSERT INTO tbl_hd_chamado_postagem(hd_chamado) VALUES($hd_chamado)";
					@$res = pg_query($con, $sql);
					if (pg_errormessage($con)) {
						$msg_erro = "Houve um erro ao cadastrar a sua solicitação de postagem. Contate o HelpDesk";
					}
				}
			}

				if(strlen($msg_erro)==0 and strlen($callcenter)==0) {

					if (isset($rat_codigo_posto)){
						$xcodigo_posto = $rat_codigo_posto;
					}

					$xnota_fiscal = "'".$_POST["nota_fiscal"]."'";

					if(strlen($abre_os)==0){ $abre_os = 'f';}
					$xabre_os = "'".$abre_os."'";

					//HD 205933: Habilitar abertura de ORDEM DE SERVIÇO para a Esmaltec
					if(strlen($abre_ordem_servico)==0){ $abre_ordem_servico = 'f';}
					$xabre_ordem_servico = "'".$abre_ordem_servico."'";

					$data_nf = $_POST["data_nf"] ;
					if(strlen($data_nf)==0) $xdata_nf = "NULL";
					else                    $xdata_nf = "'".converte_data($data_nf)."'";

					if($login_fabrica==3){
						if($status_interacao=='Resolvido' OR $status_interacao=='Cancelado') {
							$tipo_registro ="Contato";
						}elseif($status_interacao=='Aberto'){
							$tipo_registro ="Processo";
						}
					}else{
						$tipo_registro="";
					}
					if (strlen($mr_codigo_posto) > 0) {
						$xcodigo_posto=$mr_codigo_posto;
					}

					if (strlen($posto_km_tab)==0) {
						$posto_km_tab = 'null';
					}
					else {
						$posto_km_tab = str_replace(',','.',$posto_km_tab);
					}

					if(strlen($atendimento_callcenter) > 0) {
						$condicao = ", atendimento_callcenter = '$atendimento_callcenter' ";
					}
					
					if (strlen($protocolo)==0) {

						$sql = "INSERT INTO tbl_hd_chamado_extra(
									hd_chamado           ,
									reclamado            ,
									defeito_reclamado    ,
									serie                ,
									hora_ligacao         ,
									produto              ,
									posto                ,
									os                   ,
									receber_info_fabrica ,
									consumidor_revenda   ,
									origem               ,
									revenda              ,
									revenda_nome         ,
									data_nf              ,
									nota_fiscal          ,
									nome                 ,
									endereco             ,
									numero               ,
									complemento          ,
									bairro               ,
									cep                  ,
									fone                 ,
									fone2                ,
									email                ,
									cpf                  ,
									rg                   ,
									cidade               ,
									qtde_km              ,
									abre_os              ,
									defeito_reclamado_descricao,
									numero_processo      ,
									tipo_registro		 ,
									celular				 ,
									revenda_cnpj         ,";
									if($login_fabrica == 43 or $login_fabrica == 14) {
										if(strlen($ordem_montagem)>0) $sql.= " ordem_montagem,  ";
										$sql.= " codigo_postagem, ";
									}


									$sql .= " atendimento_callcenter ";

						$sql .="	)values(
								$hd_chamado                    ,
								$xreclamado                    ,
								$xdefeito_reclamado            ,
								substr($xserie,0,20)           ,
								$xhora_ligacao                 ,
								$xproduto                      ,
								$xcodigo_posto                 ,
								$xos                           ,
								$xreceber_informacoes          ,
								$xconsumidor_revenda           ,
								$xorigem                       ,
								$xrevenda                      ,
								substr($xrevenda_nome,0,51)    ,
								$xdata_nf                      ,
								$xnota_fiscal                  ,
								upper($xconsumidor_nome)       ,
								upper($xconsumidor_endereco)   ,
								upper($xconsumidor_numero)     ,
								upper($xconsumidor_complemento),
								upper($xconsumidor_bairro)     ,
								$xconsumidor_cep               ,
								$xconsumidor_fone              ,
								$xconsumidor_fone2             ,
								$xconsumidor_email             ,
								$xconsumidor_cpf               ,
								upper($xconsumidor_rg)         ,
								$cidade                        ,
								$posto_km_tab                  ,
								$xabre_os                      ,
								'$hd_extra_defeito'            ,
								$xnumero_processo              ,
								'$tipo_registro'			   ,
								$xconsumidor_fone3			   ,
								'$cnpj_revenda'                ,";
								 if($login_fabrica == 43 or $login_fabrica == 14) { #HD 251241
										 if(strlen($ordem_montagem)>0) $sql.= " '$ordem_montagem' , ";
										 $sql.= " '$codigo_postagem', ";
								 }

								$sql .= "'$atendimento_callcenter'       ";

					$sql .=");";
					} else {
						$sql = "UPDATE tbl_hd_chamado_extra SET
									reclamado            = $xreclamado                    ,
									defeito_reclamado    = $xdefeito_reclamado            ,
									serie                = substr($xserie,0,21)           ,
									hora_ligacao         = $xhora_ligacao                 ,
									produto              = $xproduto                      ,
									posto                = $xcodigo_posto                 ,
									os                   = $xos                           ,
									receber_info_fabrica = $xreceber_informacoes          ,
									consumidor_revenda   = $xconsumidor_revenda           ,
									origem               = $xorigem                       ,
									revenda              = $xrevenda                      ,
									revenda_nome         = substr($xrevenda_nome,0,51)    ,
									data_nf              = $xdata_nf                      ,
									nota_fiscal          = substr($xnota_fiscal,0,11)     ,
									nome                 = upper($xconsumidor_nome)       ,
									endereco             = upper($xconsumidor_endereco)   ,
									numero               = upper($xconsumidor_numero)     ,
									complemento          = upper($xconsumidor_complemento),
									bairro               = upper($xconsumidor_bairro),
									cep                  = $xconsumidor_cep               ,
									fone                 = substr($xconsumidor_fone,0,21) ,
									fone2                = substr($xconsumidor_fone2,0,21) ,
									email                = $xconsumidor_email             ,
									cpf                  = $xconsumidor_cpf               ,
									rg                   = upper($xconsumidor_rg)         ,
									cidade               = $cidade                        ,
									qtde_km              = $posto_km_tab                  ,
									abre_os              = $xabre_os                      ,
									defeito_reclamado_descricao= '$hd_extra_defeito'      ,
									numero_processo      = $xnumero_processo              ,
									tipo_registro        = '$tipo_registro'				  ,
									celular				 = $xconsumidor_fone3			  ,
									revenda_cnpj		 = '$cnpj_revenda'
									$condicao
									";
							$sql .= " WHERE tbl_hd_chamado_extra.hd_chamado = $hd_chamado";
					}
					
	if ($debug) pre_echo(pg_last_error($con), "Antes da query");
	//echo nl2br($sql); exit;
					$res = pg_query($con,$sql);
	if ($debug) pre_echo(pg_last_error($con)."Erro:\n'$msg_erro'\n\n$sql\n\n".pg_last_error($con));
					$msg_erro .= pg_errormessage($con);
	// $n = pg_query($con,"ROLLBACK");

					if($xstatus_interacao == "'Resolvido'" AND $login_fabrica <> 6){
						$sql = "INSERT INTO tbl_hd_chamado_item(
								hd_chamado   ,
								data         ,
								comentario   ,
								admin        ,
								interno      ,
								status_item  ,
								enviar_email
								)values(
								$hd_chamado       ,
								current_timestamp ,
								'Resolvido'       ,
								$login_admin      ,
								$xchamado_interno ,
								$xstatus_interacao,
								$xenvia_email
								)";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);

						if (strlen($msg_erro) == 0) {
							$sql = "SELECT fn_callcenter_dias_interacao($hd_chamado,$login_fabrica)";
							$res = pg_query($con,$sql);
							$msg_erro .= pg_errormessage($con);

							$sql = "SELECT fn_callcenter_dias_aberto($hd_chamado,$login_fabrica);";
							$res = pg_query($con,$sql);
							$msg_erro .= pg_errormessage($con);

							$sql = "SELECT fn_callcenter_intervalo_interacao($hd_chamado,$login_fabrica);";
							$res = pg_query($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}
					}
					
					if ( isset($_POST['tipo_consumidor']) && !empty($hd_chamado) ) { // HD 317864
	
						$sql = "UPDATE tbl_hd_chamado_extra SET tipo_consumidor = '" . $_POST['tipo_consumidor'] . "' WHERE hd_chamado = $hd_chamado";
						$res = pg_query($con,$sql);
						
					}
					else if ( in_array($login_fabrica,$fab_usa_tipo_cons) ) {
					
						$msg_erro = "Selecione o Tipo do Consumidor";
					
					}

					//IGOR - HD: 10441 QUANDO FOR INDICADO UM POSTO AUTORIZADO, DEVE-SE INSERIR UMA INTERAO NO CHAMADO
					if(strlen($posto_tab)>0){

						$comentario = "Indicação do posto mais próximo do consumidor: <br>
									Código: $codigo_posto_tab <br>
									Nome: $posto_nome_tab<br>
									Endereço: $posto_endereco_tab <br>
									Cidade: $posto_cidade_tab <br>
									Estado: $posto_estado_tab<br>
									Telefone: $posto_fone_tab<br>
									E-mail: $posto_email_tab";

						//HD 211895: Retirado da rotina abaixo para buscar OS no caso de PRÉ-OS, uma vez que não será
						//			 mais aberta OS no caso de PRÉ-OS
						if($abre_os=='t'){
							if(login_fabrica == 30){
								$comentario .= "<Br><br> Foi disponibilizado para o posto a Ordem de Serviço.";
							}else{
								$comentario .= "<Br><br> Foi disponibilizado para o posto a Pré-Ordem de Serviço.";
							}
						}

						$sql = "INSERT INTO tbl_hd_chamado_item(
								hd_chamado   ,
								data         ,
								comentario   ,
								admin        ,
								interno      ,
								status_item
								)values(
								$hd_chamado       ,
								current_timestamp ,
								'$comentario'       ,
								$login_admin      ,
								'f',
								$xstatus_interacao
								)";

						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}
				}
					//94971
					$herdar_x = $_GET['herdar'];
					$hd_chamado_herdar_x = $_GET['Id'];
					if($login_fabrica==59 AND strlen($herdar_x)>0 AND strlen($hd_chamado_herdar_x)>0 AND strlen($callcenter)<=0)
					{
						$interacao = $_POST['reclamado_produto_x'];
						$reclamado_x = "Histórico do HD $hd_chamado_herdar_x: $interacao ";
						$sql = "INSERT INTO tbl_hd_chamado_item(
								hd_chamado   ,
								data         ,
								comentario   ,
								admin        ,
								interno      ,
								status_item
								)values(
								$hd_chamado       ,
								current_timestamp ,
								'$reclamado_x'       ,
								$login_admin      ,
								'f',
								$xstatus_interacao
								)";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);

						$sql = "SELECT hd_chamado, data,comentario,admin,interno,status_item FROM tbl_hd_chamado_item WHERE hd_chamado = $hd_chamado_herdar_x";

						$res = @pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);
						$linhas = pg_num_rows($res);
						if(strlen($linhas)>0){
							for($y = 0;$y < $linhas;$y++){
								$data_hd_hist = pg_fetch_result($res,$y,data);
								$comentario_hd_hist = pg_fetch_result($res,$y,comentario);
								$admin_hd_hist = pg_fetch_result($res,$y,admin);
								$interno_hd_hist = pg_fetch_result($res,$y,interno);
								$status_item_hd_hist = pg_fetch_result($res,$y,status_item);
								$hd_chamado_hd_hist = pg_fetch_result($res,$y,hd_chamado);

								$sql = "INSERT INTO tbl_hd_chamado_item(
										hd_chamado   ,
										data         ,
										comentario   ,
										admin        ,
										interno      ,
										status_item
										)values(
										'$hd_chamado'       ,
										'$data_hd_hist' ,
										'Histórico do HD $hd_chamado_herdar_x: $comentario_hd_hist'       ,
										'$admin_hd_hist'      ,
										'$interno_hd_hist',
										'$status_item_hd_hist'
										)";

								$res2 = pg_query($con,$sql);
								$msg_erro .= pg_errormessage($con);

								$finaliza = $y + 1;
							}
							if(strlen($finaliza)<=0){
								$finaliza = 0;
							}
						}
					}
				if ($login_fabrica == 52) {
					$qtde_produto = $_POST['qtde_produto'];

					if ($qtde_produto>0) {
						for ($w=1;$w<=$qtde_produto;$w++) {
							$produto_referencia = $_POST['produto_referencia_'.$w];
							$serie              = $_POST['serie_'.$w]; echo "<br>";
							$defeito_reclamado  = $_POST['defeito_reclamado_'.$w]; echo "<br>";

							if (strlen($produto_referencia)>0) {
								$sql_ref = "SELECT tbl_produto.produto
								FROM  tbl_produto
								join  tbl_linha on tbl_produto.linha = tbl_linha.linha
								WHERE tbl_produto.referencia = '$produto_referencia'
								and tbl_linha.fabrica = $login_fabrica
								limit 1";
								$res_ref = pg_query($con,$sql_ref);
								$msg_erro .= pg_errormessage($con);
								//echo nl2br($sql)."<BR>";
								if(pg_num_rows($res_ref)>0){
								$xproduto = pg_fetch_result($res_ref,0,0);
								}else{
								$xproduto = "null";
								}
								}else{
								$xproduto = "null";
								}

								if (strlen($produto_referencia)==0 and strlen($serie)==0 and strlen($defeito_reclamado)==0) {
									//NAO FAZ NADA
								}
								else {

									if (strlen($defeito_reclamado)==0) {
										$msg_erro = "Favor escolha um defeito reclamado para o produto";
									}
									
									if (strlen($defeito_reclamado)>0 and (strlen($xproduto)==0 or $xproduto=='null')) {
										$msg_erro = "Favor escolha o produto";
									}

									if (strlen($msg_erro)==0) {
										if($login_fabrica == 30){
											$comentario_x = 'Insercao de Produto para OS';
										}else{
											$comentario_x = 'Insercao de Produto para pré-os';
										}
										$sql = "INSERT INTO tbl_hd_chamado_item(
										hd_chamado   ,
										data         ,
										comentario   ,
										admin        ,
										interno      ,
										produto      ,
										serie        ,
										defeito_reclamado,
										status_item
										)values(
										$hd_chamado                       ,
										current_timestamp                 ,
										'Insercao de Produto para pré-os' ,
										$login_admin                      ,
										't'                               ,
										'$xproduto'                       ,
										'$serie'                          ,
										$defeito_reclamado                ,
										'Aberto'
										)";
										$res = pg_query($con,$sql);
									}
								}
						}
					}
				}
				/* HD 37805 */
				if ($tab_atual == "ressarcimento" and strlen($msg_erro)==0){

					if (strlen($xdata_nf)== 0 OR $xdata_nf == 'NULL'){
						$msg_erro .= "Informe a data da Nota fiscal.";
					}

					$sql = "SELECT hd_chamado
							FROM tbl_hd_chamado_extra_banco
							WHERE hd_chamado = $hd_chamado ";
					$resx = @pg_query($con,$sql);
					if(@pg_num_rows($resx) == 0){
						$sql = "INSERT INTO tbl_hd_chamado_extra_banco ( hd_chamado ) values ( $hd_chamado )";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}

					$sql = "UPDATE tbl_hd_chamado_extra_banco SET
											banco            = $xbanco,
											agencia          = $xagencia,
											contay           = $xcontay,
											nomebanco        = $xnomebanco,
											favorecido_conta = $xfavorecido_conta,
											cpf_conta        = $xcpf_conta,
											tipo_conta       = $xtipo_conta

							WHERE hd_chamado = $hd_chamado";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);

					$sql = "SELECT hd_chamado
							FROM tbl_hd_chamado_troca
							WHERE hd_chamado = $hd_chamado ";
					$resx = @pg_query($con,$sql);
					if(@pg_num_rows($resx) == 0){
						$sql = "INSERT INTO tbl_hd_chamado_troca ( hd_chamado ) values ( $hd_chamado )";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}

					$sql = "UPDATE tbl_hd_chamado_troca SET
									data_pagamento    = $xdata_pagamento,
									ressarcimento     = 't',
									numero_objeto     = NULL,
									nota_fiscal_saida = NULL,
									data_nf_saida     = NULL,
									produto           = NULL,
									valor_produto     = $xvalor_produto,
									valor_inpc        = $xvalor_inpc,
									valor_corrigido   = $xvalor_corrigido
							WHERE hd_chamado = $hd_chamado";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);

					if (strlen($valor_produto)>0 AND strlen($valor_inpc)>0 AND strlen($msg_erro)==0){
						$sql = "SELECT CURRENT_DATE - data_nf AS qtde_dias
								FROM tbl_hd_chamado_extra
								WHERE hd_chamado = $hd_chamado ";
						$resx = @pg_query($con,$sql);
						if(@pg_num_rows($resx) > 0){
							#echo "<hr>";
							$qtde_dias = pg_fetch_result($resx,0,qtde_dias);
							if ($qtde_dias>0){
								$valor_corrigido = $valor_produto + ($valor_produto * $qtde_dias / 100);
								 $sql = "UPDATE tbl_hd_chamado_troca SET valor_corrigido = $valor_corrigido WHERE hd_chamado = $hd_chamado";
								$res = pg_query($con,$sql);
								$msg_erro .= pg_errormessage($con);
							}
						}
					}
				}

				/* HD 37805 */
				if ($tab_atual == "sedex_reverso" and strlen($msg_erro)==0){
					$sql = "SELECT hd_chamado
							FROM tbl_hd_chamado_troca
							WHERE hd_chamado = $hd_chamado ";
					$resx = @pg_query($con,$sql);
					if(@pg_num_rows($resx) == 0){
						$sql = "INSERT INTO tbl_hd_chamado_troca ( hd_chamado ) values ( $hd_chamado )";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}

					$sql = "UPDATE tbl_hd_chamado_troca SET
									data_pagamento       =  NULL,
									ressarcimento        = 'f',
									numero_objeto        = $xnumero_objeto,
									nota_fiscal_saida    = $xnota_fiscal_saida,
									data_nf_saida        = $xdata_nf_saida,
									produto              = $xproduto_troca,
									data_retorno_produto = $xdata_retorno_produto
							WHERE hd_chamado = $hd_chamado";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

				if($tab_atual == "extensao" and strlen($msg_erro) == 0 and $login_fabrica==25){
					if (strlen($es_data_compra)==0){
						$msg_erro .= "Informe a data da Compra do produto.";
					}
				}

				/* ########################################################################### */
				/* ##################  grava no banco de dados da hbtech ##################### */
				/* ########################################################################### */
				if($tab_atual == "extensao" and strlen($msg_erro) == 0 and $login_fabrica==25){
					if(strlen($consumidor_fone)==15){
						 $xddd_consumidor       = "'".substr($consumidor_fone,2,2)."'";
						 $xfone_consumidor      = "'".substr($consumidor_fone,6,9)."'";
					}elseif(strlen($consumidor_fone)==9 or strlen($consumidor_fone)==8){
						 $xddd_consumidor       = "null";
						 $xfone_consumidor      = "'".$consumidor_fone."'";
					}elseif(strlen($consumidor_fone)==11 or strlen($consumidor_fone)==10){
						 $xddd_consumidor       = "'".substr($consumidor_fone,0,2)."'";
						 $xfone_consumidor      = "'".substr($consumidor_fone,2,9)."'";
					}elseif(strlen($consumidor_fone)==0){
						 $xddd_consumidor       = "NULL";
						 $xfone_consumidor      = "NULL";
					}else{
						 $xddd_consumidor       = "NULL";
						 $xfone_consumidor      = "'".$consumidor_fone."'";
					}

					$xxes_data_compra = converte_data($es_data_compra);
					$sql = "SELECT garantia from tbl_produto where produto = $xproduto";
					$res = pg_query($con,$sql);
					$garantia = pg_fetch_result($res,0,0);

					$sql = "SELECT to_char(('$xxes_data_compra'::date + interval '$garantia month') + interval '6 month','YYYY-MM-DD') ";
					$res = pg_query($con,$sql);
					$es_garantia = "'".pg_fetch_result($res,0,0)."'";

					if(strlen($es_id_numeroserie)>0){
						include "conexao_hbtech.php";

						/*INSERINDO NO SITE DO HIBEATS, VERIFICAMOS ANTES SE EXISTE ESSE NUMERO DE SRIE E INSERIMOS OS DADOS DO CLIENTE*/
						$sql = "INSERT INTO garantia(
									produto           ,
									numeroSerie       ,
									nome              ,
									endereco          ,
									numero            ,
									complemento       ,
									cep               ,
									bairro            ,
									cidade            ,
									estado            ,
									sexo              ,
									dataNascimento    ,
									cpf               ,
									dddComercial      ,
									foneComercial     ,
									dddResidencial    ,
									foneResidencial   ,
									dddCelular        ,
									foneCelular       ,
									email             ,
									estadoCivil       ,
									filhos            ,
									prefMusical       ,
									dataCompra        ,
									nf                ,
									lojaAdquirida     ,
									estadoCompra      ,
									municipioCompra   ,
									dataGarantia
								)values(
									'$produto_referencia||$produto_nome',
									$xserie  ,
									$xconsumidor_nome       ,
									$xconsumidor_endereco   ,
									$xconsumidor_numero     ,
									$xconsumidor_complemento,
									$xconsumidor_cep        ,
									$xconsumidor_bairro     ,
									$xconsumidor_cidade     ,
									$xconsumidor_estado     ,
									$xes_sexo               ,
									$xes_data_nascimento    ,
									$xconsumidor_cpf        ,
									$xes_dddcomercial       ,
									$xes_fonecomercial      ,
									$xddd_consumidor        ,
									$xfone_consumidor       ,
									$xes_dddcelular         ,
									$xes_celular            ,
									$xconsumidor_email      ,
									$xes_estadocivil        ,
									$xes_filhos             ,
									$xes_preferenciamusical ,
									$xes_data_compra        ,
									$xes_nota_fiscal        ,
									$xes_revenda            ,
									$xes_estadocompra       ,
									$xes_municipiocompra    ,
									$es_garantia
								);";
						$res = mysql_query($sql) or die("Erro no Sql1: ".mysql_error());

						if (strlen(mysql_error())>0){
							$mensagem   = $enviar_erro."<br><br><br> $sql <br><br>".mysql_error();
							$cabecalho .= "MIME-Version: 1.0\n";
							$cabecalho .= "Content-type: text/html; charset=iso-8859-1\n";
							$cabecalho .= "From: Telecontrol <helpdesk@telecontrol.com.br>\n";
							$cabecalho .= 'To: Fabio<fabio@telecontrol.com.br>'."\n";
							$cabecalho .= "Subject: LOG HBTECH GARANTIA\n";
							$cabecalho .= "Return-Path: Suporte <helpdesk@telecontrol.com.br>\n";
							$cabecalho .= "X-Priority: 1\n";
							$cabecalho .= "X-MSMail-Priority: High\n";
							$cabecalho .= "X-Mailer: PHP/" . phpversion();
							if ( !mail("", $assunto, $mensagem, $cabecalho) ) {
							}
						}

						if ($xconsumidor_cpf == 'null' or strlen($xconsumidor_cpf)==0 ){
							$pesquisa_xconsumidor_cpf = " AND cpf  IS NULL ";
						}else{
							$pesquisa_xconsumidor_cpf = " AND cpf  = $xconsumidor_cpf";
						}
						$sql = "SELECT idGarantia FROM garantia WHERE numeroSerie = $xserie $pesquisa_xconsumidor_cpf";

						$res = mysql_query($sql) or die("Erro no Sql2:".mysql_error());

						if(mysql_num_rows($res)>0){
							$idGarantia = mysql_result($res,0,idGarantia);
							$sql = "UPDATE numero_serie SET idGarantia = $idGarantia WHERE numero = $xserie";
							$res = mysql_query($sql) or die("Erro no Sql:".mysql_error());
						}
					}
				}

				if (strlen($msg_erro) == 0) {
					$sql = "SELECT fn_callcenter_dias_interacao($hd_chamado,$login_fabrica)";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);

					$sql = "SELECT fn_callcenter_dias_aberto($hd_chamado,$login_fabrica);";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);

					$sql = "SELECT fn_callcenter_intervalo_interacao($hd_chamado,$login_fabrica);";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

				if ($abre_os == 't' AND $imprimir_os == 't'){
					$imprimir_os = "&imprimir_os=t";
				}else{
					$imprimir_os = "";
				}

				
				// HD 26968
				if(strlen($xtransferir) >0 AND strlen($hd_chamado) >0 AND ($login_admin <> $xtransferir)){
					$sql = "UPDATE tbl_hd_chamado set atendente = $xtransferir
					WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
					and tbl_hd_chamado.hd_chamado = $hd_chamado	";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);

					$sql = "SELECT login from tbl_admin where admin = $login_admin";
					$res = pg_query($con,$sql);
					$nome_ultimo_atendente = pg_fetch_result($res,0,login);

					$sql = "SELECT login from tbl_admin where admin = $xtransferir";
					$res = pg_query($con,$sql);
					$nome_atendente = pg_fetch_result($res,0,login);

					$sql = "INSERT INTO tbl_hd_chamado_item(
							hd_chamado   ,
							data         ,
							comentario   ,
							admin        ,
							interno      ,
							status_item
							)values(
							$hd_chamado       ,
							current_timestamp ,
							'Atendimento transferido por <b>$login_login</b> de <b>$nome_ultimo_atendente</b> para <b>$nome_atendente</b>',
							$login_admin      ,
							't'  ,
							$xstatus_interacao
							)";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

				// HD 129655 - Gravar dúvidas selecionadas [augusto]
				if (strlen($msg_erro) == 0) {
					gravarFaq();
				}
				
				//HD 211895: Rotina de PRÉ-OS movida para fora das rotinas de inserção e atualização
				//ATENÇÃO: COMMIT E ROLLBACK DESTA TRANSAÇÃO FOI MOVIDO PARA DEPOIS DA ROTINA DE INSERÇÃO
				//DE PRÉ-OS, QUE ESTÁ LOGO ABAIXO DA ROTINA DE ATUALIZAÇÃO (QUE COMEÇA AI EMABAIXO)
	}/*INSERINDO*/

	/************************************************************************************************/
	/****************************************** ATUALIZAÇÃO *****************************************/
	/************************************************************************************************/

	if(strlen($callcenter)>0){
		if($xresposta=="null"){ $msg_erro = "Por favor insira a resposta";}
		$sql = "SELECT atendente,login
				from tbl_hd_chamado
				JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente
				where fabrica_responsavel= $login_fabrica
				and hd_chamado = $callcenter";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			$ultimo_atendente       = pg_fetch_result($res,0,'atendente');
			$ultimo_atendente_login = pg_fetch_result($res,0,'login');
		}
		// ! Gravar alterações de dados
		// HD 122446 (augusto) - Criar interação quando o endereço do cliente for modificado (Lenoxx)
		// HD 124579 (augusto) - Implementar isso em outros campos que podem ser modificados para todas as fábricas
		$msg_interacao = '';
		if ( ( strlen($msg_erro) <= 0 && $login_fabrica == 11 && $hd_chamado > 0 && $xstatus_interacao == "'Aberto'" ) ||
			 ( strlen($msg_erro) <= 0 && $login_fabrica != 11 && $hd_chamado > 0 )                                       ) {
			$array_campos_consumidor_verificar 					= array('consumidor_nome','consumidor_cpf','consumidor_rg','consumidor_email','consumidor_fone',
																		'consumidor_fone2','consumidor_fone3','consumidor_cep','consumidor_endereco','consumidor_numero',
																		'consumidor_complemento','consumidor_bairro','consumidor_cidade','consumidor_estado',
																		'produto_referencia','produto_nome','voltagem','serie','nota_fiscal','data_nf');
			$array_consumidor_label            					= array_flip($array_campos_consumidor_verificar);
			$array_consumidor_label['consumidor_nome']	 		= 'Nome';
			$array_consumidor_label['consumidor_cpf']	 		= 'CPF';
			$array_consumidor_label['consumidor_rg']	 		= 'RG';
			$array_consumidor_label['consumidor_email'] 		= 'E-mail';
			$array_consumidor_label['consumidor_fone'] 			= 'Telefone';
			$array_consumidor_label['consumidor_fone2']			= 'Telefone Comercial';
			$array_consumidor_label['consumidor_fone3'] 		= 'Telefone Celular';
			$array_consumidor_label['consumidor_cep'] 			= 'CEP';
			$array_consumidor_label['consumidor_endereco'] 		= 'Endereço';
			$array_consumidor_label['consumidor_numero'] 		= 'Número';
			$array_consumidor_label['consumidor_complemento'] 	= 'Complem.';
			$array_consumidor_label['consumidor_bairro'] 		= 'Bairro';
			$array_consumidor_label['consumidor_cidade']		= 'Cidade';
			$array_consumidor_label['consumidor_estado'] 		= 'Estado';
			$array_consumidor_label['produto_referencia'] 		= 'Referência (do Produto)';
			$array_consumidor_label['produto_nome'] 			= 'Descrição (do Produto)';
			$array_consumidor_label['voltagem'] 				= 'Voltagem';
			$array_consumidor_label['serie'] 					= 'Série';
			$array_consumidor_label['nota_fiscal'] 				= 'NF Compra';
			$array_consumidor_label['data_nf'] 					= 'Data NF';
			$interacao_campos_consumidor_msgs  					= array();
			foreach ($array_campos_consumidor_verificar as $campo_consumidor) {
				$valor_anterior = $campo_consumidor.'_anterior';
				if ( ! isset($_POST[$campo_consumidor]) ) { continue; }
	//  30/12/2009 MLG HD 188091 - "Null" e "nada" deveriam ser a mesma coisa...
				if (empty($_POST[$valor_anterior]) and $$campo_consumidor == "null") { continue; }
	//  22/01/2010 MLG HD 198090 - O campo CPF é re-formatado, mas isso significa que volta a ser alterado.
				$valor_atual    = $$campo_consumidor;
// 				if ($campo_consumidor=="consumidor_cpf") $valor_atual = preg_replace("/\D/","",$valor_atual);

				if ( $_POST[$valor_anterior] != $valor_atual ) {
					$msg_valor_anterior = ( empty($_POST[$valor_anterior]) ) ? 'Em branco' : $_POST[$valor_anterior] ;
					$msg_alteracao      = "<li>Campo <strong>{$array_consumidor_label[$campo_consumidor]}</strong> alterado de '<em>{$msg_valor_anterior}</em>' para '<em>{$$campo_consumidor}</em>'</li>";
					$interacao_campos_consumidor_msgs[] = $msg_alteracao;
				}
			}
			if ( count($interacao_campos_consumidor_msgs) > 0 ) {
				$msg_interacao  = "<p>As seguintes informações do chamado foram alteradas nesta interação:</p><p>&nbsp;</p>";
				$msg_interacao .= "<ul>".implode('',$interacao_campos_consumidor_msgs)."</ul>";
				$sql = "INSERT INTO tbl_hd_chamado_item(
									hd_chamado   ,
									data         ,
									comentario   ,
									admin        ,
									interno      ,
									status_item
									)values(
									$hd_chamado       ,
									current_timestamp ,
									'$msg_interacao'       ,
									$login_admin      ,
									't',
									$xstatus_interacao
									)";
	/*
				// A inserçnao agora é feita na SQL de inserção de resposta inserida pelo usuário, e não numa resposta nova !
				$res = pg_query($con,$sql);
				if ( ! is_resource($res) ) {
					$msg_erro .= "<p> Erro ao inserir interação informando modificação das informações do chamado: ".pg_errormessage($con)."</p>";
				}
	*/
			}
			unset($array_campos_consumidor_verificar,$interacao_campos_consumidor_msgs,$msg_alteracao,$valor_anterior,$campo_consumidor,$sql,$res);
		}
		// fim HD 122446

		# HD 45756
		if($login_fabrica == 3) {
			if($ultimo_atendente <> $login_admin) {
				$msg_erro = "Sem permissão de alteração. Admin responsável: $ultimo_atendente_login";
			}
		}
		
		if ( isset($_POST['tipo_consumidor']) && !empty($hd_chamado) ) { // HD 317864
	
			$sql = "UPDATE tbl_hd_chamado_extra SET tipo_consumidor = '" . $_POST['tipo_consumidor'] . "' WHERE hd_chamado = $hd_chamado";
			$res = pg_query($con,$sql);
			
		}
		else if ( in_array($login_fabrica,$fab_usa_tipo_cons) ) {
		
			$msg_erro = "Selecione o Tipo do Consumidor";
		
		}
		
		if(strlen($msg_erro)==0){
			$res = pg_query ($con,"BEGIN TRANSACTION");
			$_xresposta = pg_escape_string("{$resposta}<p>&nbsp;</p> {$msg_interacao}");
			$sql = "INSERT INTO tbl_hd_chamado_item(
							hd_chamado   ,
							data         ,
							comentario   ,
							admin        ,
							interno      ,
							status_item  ,
							enviar_email
							)values(
							$callcenter       ,
							current_timestamp ,
							'$_xresposta'        ,
							$login_admin      ,
							$xchamado_interno  ,
							$xstatus_interacao ,
							$xenvia_email
							)";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				if($login_fabrica == 1 and $xchamado_interno <> "'t'") {

					$sql = "Select email from tbl_admin where admin = $login_admin and fabrica = $login_fabrica";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);
					if(pg_num_rows($res)>0){
						$admin_email = pg_fetch_result($res,0,email);
					}
					$sql = "SELECT email from tbl_hd_chamado_extra where hd_chamado = $hd_chamado";
					$res = pg_query($con,$sql);
					if(@pg_num_rows($res)  > 0 and strlen(pg_fetch_result($res,0,email)) >0){
						$email_posto = strtolower(pg_fetch_result($res,0,email));
						$subject  = "Help-Desk $hd_chamado respondido pelo fabricante";
						$message="<b>Prezado Posto</b><br><br>";
						$message .= "<b> O Help-Desk $hd_chamado foi respondido pelo fabrincate.<br><br>";
						$message .= "<b> Atenciosamente<br><br>";

						$headers = "From: Call-center <$admin_email>\n";

						$headers .= "MIME-Version: 1.0\n";
						$headers .= "Content-type: text/html; charset=iso-8859-1\n";

						mail("$email_posto",$subject,$message,$headers);
					}
				}
		}

		//HD 196942: Controle de postagem
		//o checkbox para solicitar postagem para o chamado só aparece quando não tem solicitação para aquele chamado, portanto só virá alguma coisa por POST em hd_chamado_postagem se não estiver cadastrado e se estiver com o checkbox marcado
		if ($_POST["hd_chamado_postagem"] == "sim" && strlen($hd_chamado) > 0 && strlen($msg_erro) == 0) {
			$sql = "SELECT hd_chamado FROM tbl_hd_chamado_postagem WHERE hd_chamado=$hd_chamado";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res)) {
				$msg_erro = "Já existe solicitação de postagem cadastrada para este chamado. Não é permitido o cadastramento de mais de uma postagem para um mesmo chamado";
			}
			else {
				$sql = "INSERT INTO tbl_hd_chamado_postagem(hd_chamado) VALUES($hd_chamado)";
				@$res = pg_query($con, $sql);
				if (pg_errormessage($con)) {
					$msg_erro = "Houve um erro ao cadastrar a sua solicitação de postagem. Contate o HelpDesk";
				}
			}
		}

		if (strlen($_POST["hd_chamado_codigo_postagem"]) > 0 && strlen($hd_chamado) && strlen($msg_erro) == 0) {
			$hd_chamado_codigo_postagem = $_POST["hd_chamado_codigo_postagem"];

			$sql = "SELECT hd_chamado FROM tbl_hd_chamado_postagem WHERE hd_chamado=$hd_chamado AND aprovado IS TRUE";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res)) {
				$sql = "UPDATE tbl_hd_chamado_postagem SET codigo_postagem='$hd_chamado_codigo_postagem' WHERE hd_chamado=$hd_chamado";
				@$res = pg_query($con, $sql);
				
				if (pg_errormessage($con)) {
					$msg_erro = "Ocorreu um erro no sistema, contate o HelpDesk";
				}
			}
			else {
				$msg_erro = "Foi informado Código de Postagem, mas não existe postagem autorizada para este chamado";
			}
		}


		if(strlen($posto_tab)>0){

			$comentario = "Indicação do posto mais próximo do consumidor: <br>
						Código: $codigo_posto_tab <br>
						Nome: $posto_nome_tab<br>
						Endereço: $posto_endereco_tab <br>
						Cidade: $posto_cidade_tab <br>
						Estado: $posto_estado_tab
						Telefone: $posto_fone_tab<br>
						E-mail: $posto_email_tab";

			$sql = "INSERT INTO tbl_hd_chamado_item(
					hd_chamado   ,
					data         ,
					comentario   ,
					admin        ,
					interno      ,
					status_item
					)values(
					$hd_chamado       ,
					current_timestamp ,
					'$comentario'       ,
					$login_admin      ,
					'f',
					$xstatus_interacao
					)";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);
			
			//HD 211895: Atualizando posto
			$sql = "UPDATE tbl_hd_chamado_extra SET posto=$posto_tab WHERE hd_chamado=$callcenter";
			$res_atualiza_posto = pg_query($con, $sql);
			$msg_erro .= pg_errormessage($con);
		}
		
		//se  para enviar email para consumidor
		if(strlen($msg_erro)==0 and $xenvia_email == "'t'"){
			if($_POST["consumidor_email"]){
				if ($login_fabrica == 24) {
					$admin_email = "Suggar <resposta_automatica@suggar.com.br>";
				}
				else {
					$sql = "Select email from tbl_admin where admin = $login_admin and fabrica = $login_fabrica";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);

					if(pg_num_rows($res)>0){
						$admin_email = pg_fetch_result($res,0,email);
					}else{
						$admin_email = "telecontrol@telecontrol.com.br";
					}
				}

				$xxresposta = str_replace("'","",$xresposta);
				$remetente    = $admin_email;
				$destinatario = $_POST["consumidor_email"];
				$assunto      = "Protocolo $callcenter - Resposta atendimento Call Center";
				$mensagem     = nl2br($xxresposta);
				$headers="Return-Path: $admin_email\nFrom:".$remetente."\nContent-type: text/html\n";
				
				//HD 234227: Mensagem da Suggar
				if ($login_fabrica == 24) {
					$remetente = "resposta_automatica@suggar.com.br";
					$mensagem = "
					<font size='-1'>Esta é uma mensagem automática. Por favor, não responda este e-mail. Estamos sempre prontos para atendê-lo.  Caso queira entrar em contato novamente, acesse www.suggar.com.br no link Fale conosco</font><br>
					<br>
					$mensagem<br>
					<br>
					Atenciosamente,<br>
					<br>
					Central de Relacionamento com o cliente<br>
					<br>
					SUGGAR<br>
					<br>
					";
				}

				mail($destinatario,$assunto,$mensagem,$headers);
			}
		}

		if(strlen($msg_erro)==0){
			$sql = "UPDATE tbl_hd_chamado set status = $xstatus_interacao
					WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
					and tbl_hd_chamado.hd_chamado = $callcenter	";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if (strlen($intervensor)>0 AND strlen($hd_chamado) >0 AND ($login_admin <> $intervensor) AND ($xtransferir <> $intervensor)) {
				
				$sql = "UPDATE tbl_hd_chamado set sequencia_atendimento = $intervensor
							WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
							and tbl_hd_chamado.hd_chamado = $hd_chamado	";
			
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
		
				$sql = "SELECT login,email from tbl_admin where admin = $ultimo_atendente";
				$res = pg_query($con,$sql);
				$nome_ultimo_atendente  = pg_fetch_result($res,0,login);
				$email_ultimo_atendente = pg_fetch_result($res,0,email);

				$sql = "SELECT login,email from tbl_admin where admin = $intervensor";
				$res = pg_query($con,$sql);
				$nome_intervensor  = pg_fetch_result($res,0,login);
				$email_intervensor = pg_fetch_result($res,0,email);

				$sql = "INSERT INTO tbl_hd_chamado_item(
								hd_chamado   ,
								data         ,
								comentario   ,
								admin        ,
								interno      ,
								status_item
								)values(
								$callcenter       ,
								current_timestamp ,
								'O Atendente <b>$login_login</b> precisou da intenvencão do <b>$nome_intervensor</b> para resolver este atendimento',
								$login_admin      ,
								't'  ,
								$xstatus_interacao
								)";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				if(strlen($email_ultimo_atendente) >0 AND strlen($email_intervensor) >0){

					$assunto       = "O Atendente $login_login, precisou de sua intervensao no chamado $callcenter";

					if($login_fabrica == 24) {
						$sql = " SELECT  tbl_hd_chamado_extra.nome       ,
										 endereco   ,
										 numero     ,
										 complemento,
										 bairro     ,
										 cep        ,
										 fone       ,
										 email      ,
										 cpf        ,
										 rg         ,
										 categoria  ,
										 reclamado  ,
										 tbl_cidade.nome as cidade,
										 tbl_cidade.estado         ,
										 tbl_produto.referencia    ,
										 tbl_produto.descricao
								FROM tbl_hd_chamado
								JOIN tbl_hd_chamado_extra USING(hd_chamado)
								JOIN tbl_cidade           USING(cidade)
								LEFT JOIN tbl_produto     USING(produto)
								WHERE tbl_hd_chamado.hd_chamado = $callcenter";
						$res = pg_query($con,$sql);
						if(pg_num_rows($res) > 0){
							$nome        = pg_fetch_result($res,0,'nome');
							$endereco    = pg_fetch_result($res,0,'endereco');
							$numero      = pg_fetch_result($res,0,'numero');
							$bairro      = pg_fetch_result($res,0,'bairro');
							$cep         = pg_fetch_result($res,0,'cep');
							$fone        = pg_fetch_result($res,0,'fone');
							$email       = pg_fetch_result($res,0,'email');
							$categoria   = pg_fetch_result($res,0,'categoria');
							$cidade      = pg_fetch_result($res,0,'cidade');
							$estado      = pg_fetch_result($res,0,'estado');
							$reclamado   = pg_fetch_result($res,0,'reclamado');
							$referencia  = @pg_fetch_result($res,0,'referencia');
							$descricao   = @pg_fetch_result($res,0,'descricao');
							
							//HD 235203: Colocar vários assuntos para a Suggar
							$achou_categoria_assunto = false;

							foreach($assuntos as $categoria_assunto => $itens_categoria_assunto) {
								foreach($itens_categoria_assunto as $label_assunto => $bd_assunto) {
									if ($bd_assunto == $categoria) {
										$categoria = $label_assunto;
										$achou_categoria_assunto = true;
									}
								}
							}

							if ($achou_categoria_assunto == false) {
								if($categoria == 'reclamacao_produto') $categoria = "Reclamação do Produto";
								if($categoria =="duvida_produto") $categoria= "Dúvida do Produto";
								if($categoria =="reclamacao_at") $categoria= "Reclamação da Assistência Técnica";
								if($categoria =="sugestao") $categoria= "Sugestão";
								if($categoria =="reclamacao_empresa") $categoria= "Reclamação da Empresa";
								if($categoria =="procon") $categoria= "Procon";
								if($categoria =="onde_comprar") $categoria= "Onde comprar";
							}
						}
					}

					if ($status_interacao == 'Resolvido') {//HD 226230
						$corpo = "<P align=left><STRONG>Chamado finalizado</STRONG> </P>
						<P align=left>".ucwords($nome_atendente).",</P>
						<P align=justify>
						O atendimento $callcenter foi concluído por <b>$nome_ultimo_atendente</b>
						</P>";
					} else {
						$corpo = "<P align=left><STRONG>Nota: Este e-mail gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
						<P align=left>$nome_atendente,</P>
						<P align=justify>
						O atendimento $callcenter foi transferido por <b>$login_login</b> de <b>$nome_ultimo_atendente</b> para você
						</P>";
					}

					if($login_fabrica == 24) {
						$corpo .= "<p align=justify>Informação do atendimento:</p>";
						$corpo .= "<p align=justify>Nome do consumidor: $nome&nbsp;&nbsp;Telefone: $fone</p>";
						$corpo .= "<p align=justify>E-mail: $email</p>";
						$corpo .= "<p align=justify>Endereço:$endereco&nbsp;$numero - $bairro - $cidade - $estado CEP: $cep</p>";
						$corpo .= "<p align=justify>Tipo de atendimento: $categoria</p>";
						if(strlen($referencia) > 0) {
							$corpo .="<p align=justify>Produto: $referencia - $descricao</p>";
						}
						$corpo .= "<p align=justify>Descrição: $reclamado</p>";
					}
					//HD 190736 - Link para chamado no corpo do e-mail
					$corpo .= "<p>Segue abaixo link para acesso ao chamado:</p><p align=justify><a href='http://www.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=$callcenter'>http://www.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=$callcenter</a>";
					// HD 112313 (augusto) - Problema no cabeçalho do email, removidas partes com problema;
					$body_top  = "Content-type: text/html; charset=iso-8859-1 \n";
					$body_top .= "From: {$email_ultimo_atendente} \n";

					if ( @mail($email_intervensor, stripslashes($assunto), $corpo, $body_top ) ){
						$msg = "<br>Foi enviado um email para: ".$email_atendente."<br>";
					}else{
						$msg_erro = "Não foi possível enviar o email. ";
					}
				}
			}

				if ($login_fabrica == 24) {
					$sql = "SELECT atendente,sequencia_atendimento from tbl_hd_chamado where hd_chamado = $callcenter";
				$res = pg_query($con,$sql);

					if (pg_num_rows($res)>0) {
						$atendente_chamado = pg_result($res,0,0); 
						$intervensor_chamado = pg_result($res,0,1); 
					}
				} else {
					$intervensor_chamado = '0';
				}

			if(($ultimo_atendente <> $xtransferir) or ($login_admin == $intervensor_chamado)){

				if ($login_fabrica == 24) {
					if (($intervensor_chamado == $login_admin) AND ($atendente_chamado <> $xtransferir)) {
						$msg_erro = "O intervensor pode transferir o chamado apenas para o responsável do atendimento";
					}

					if (strlen($msg_erro)==0) {
						$sql = "UPDATE tbl_hd_chamado set atendente = $xtransferir
							WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
							and tbl_hd_chamado.hd_chamado = $callcenter";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);
	
						if ($intervensor_chamado == $login_admin) {
							$sql = "UPDATE tbl_hd_chamado set sequencia_atendimento = null
							WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
							and tbl_hd_chamado.hd_chamado = $callcenter";
							$res = pg_query($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}
					}
				} else {

					$sql = "UPDATE tbl_hd_chamado set atendente = $xtransferir
						WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
						and tbl_hd_chamado.hd_chamado = $callcenter	";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

				# HD 35488
				# Marca HD como pendente
				if ($login_fabrica == 51){
					$sql = "UPDATE tbl_hd_chamado_extra set leitura_pendente = 't'
							WHERE hd_chamado = $callcenter	";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}

				$sql = "SELECT login,email from tbl_admin where admin = $ultimo_atendente";
				//echo nl2br( $sql );

				$res = pg_query($con,$sql);
				$nome_ultimo_atendente  = pg_fetch_result($res,0,login);
				$email_ultimo_atendente = pg_fetch_result($res,0,email);

				$sql = "SELECT login,email from tbl_admin where admin = $xtransferir";
				$res = pg_query($con,$sql);
				$nome_atendente  = pg_fetch_result($res,0,login);
				$email_atendente = pg_fetch_result($res,0,email);

				$sql = "INSERT INTO tbl_hd_chamado_item(
								hd_chamado   ,
								data         ,
								comentario   ,
								admin        ,
								interno      ,
								status_item
								)values(
								$callcenter       ,
								current_timestamp ,
								'Atendimento transferido por <b>$login_login</b> de <b>$nome_ultimo_atendente</b> para <b>$nome_atendente</b>',
								$login_admin      ,
								't'  ,
								$xstatus_interacao
								)";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				if(strlen($email_ultimo_atendente) >0 AND strlen($email_atendente) >0){

					$assunto       = "O atendimento $callcenter foi transferido por $login_login para você";

					if($login_fabrica == 24) {
						$sql = " SELECT  tbl_hd_chamado_extra.nome       ,
										 endereco   ,
										 numero     ,
										 complemento,
										 bairro     ,
										 cep        ,
										 fone       ,
										 email      ,
										 cpf        ,
										 rg         ,
										 categoria  ,
										 reclamado  ,
										 tbl_cidade.nome as cidade,
										 tbl_cidade.estado         ,
										 tbl_produto.referencia    ,
										 tbl_produto.descricao
								FROM tbl_hd_chamado
								JOIN tbl_hd_chamado_extra USING(hd_chamado)
								JOIN tbl_cidade           USING(cidade)
								LEFT JOIN tbl_produto     USING(produto)
								WHERE tbl_hd_chamado.hd_chamado = $callcenter";
						$res = pg_query($con,$sql);
						if(pg_num_rows($res) > 0){
							$nome        = pg_fetch_result($res,0,'nome');
							$endereco    = pg_fetch_result($res,0,'endereco');
							$numero      = pg_fetch_result($res,0,'numero');
							$bairro      = pg_fetch_result($res,0,'bairro');
							$cep         = pg_fetch_result($res,0,'cep');
							$fone        = pg_fetch_result($res,0,'fone');
							$email       = pg_fetch_result($res,0,'email');
							$categoria   = pg_fetch_result($res,0,'categoria');
							$cidade      = pg_fetch_result($res,0,'cidade');
							$estado      = pg_fetch_result($res,0,'estado');
							$reclamado   = pg_fetch_result($res,0,'reclamado');
							$referencia  = @pg_fetch_result($res,0,'referencia');
							$descricao   = @pg_fetch_result($res,0,'descricao');
							
							//HD 235203: Colocar vários assuntos para a Suggar
							$achou_categoria_assunto = false;

							foreach($assuntos as $categoria_assunto => $itens_categoria_assunto) {
								foreach($itens_categoria_assunto as $label_assunto => $bd_assunto) {
									if ($bd_assunto == $categoria) {
										$categoria = $label_assunto;
										$achou_categoria_assunto = true;
									}
								}
							}

							if ($achou_categoria_assunto == false) {
								if($categoria == 'reclamacao_produto') $categoria = "Reclamação do Produto";
								if($categoria =="duvida_produto") $categoria= "Dúvida do Produto";
								if($categoria =="reclamacao_at") $categoria= "Reclamação da Assistência Técnica";
								if($categoria =="sugestao") $categoria= "Sugestão";
								if($categoria =="reclamacao_empresa") $categoria= "Reclamação da Empresa";
								if($categoria =="procon") $categoria= "Procon";
								if($categoria =="onde_comprar") $categoria= "Onde comprar";
							}
						}
					}

					if ($status_interacao == 'Resolvido') {//HD 226230
						$corpo = "<P align=left><STRONG>Chamado finalizado</STRONG> </P>
						<P align=left>".ucwords($nome_atendente).",</P>
						<P align=justify>
						O atendimento $callcenter foi concluído por <b>$nome_ultimo_atendente</b>
						</P>";
					} else {
						$corpo = "<P align=left><STRONG>Nota: Este e-mail gerado automaticamente. **** POR FAVOR NÃO RESPONDA ESTA MENSAGEM ****.</STRONG> </P>
						<P align=left>$nome_atendente,</P>
						<P align=justify>
						O atendimento $callcenter foi transferido por <b>$login_login</b> de <b>$nome_ultimo_atendente</b> para você
						</P>";
					}

					if($login_fabrica == 24) {
						$corpo .= "<p align=justify>Informação do atendimento:</p>";
						$corpo .= "<p align=justify>Nome do consumidor: $nome&nbsp;&nbsp;Telefone: $fone</p>";
						$corpo .= "<p align=justify>E-mail: $email</p>";
						$corpo .= "<p align=justify>Endereço:$endereco&nbsp;$numero - $bairro - $cidade - $estado CEP: $cep</p>";
						$corpo .= "<p align=justify>Tipo de atendimento: $categoria</p>";
						if(strlen($referencia) > 0) {
							$corpo .="<p align=justify>Produto: $referencia - $descricao</p>";
						}
						$corpo .= "<p align=justify>Descrição: $reclamado</p>";
					}
					//HD 190736 - Link para chamado no corpo do e-mail
					$corpo .= "<p>Segue abaixo link para acesso ao chamado:</p><p align=justify><a href='http://www.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=$callcenter'>http://www.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=$callcenter</a>";
					// HD 112313 (augusto) - Problema no cabeçalho do email, removidas partes com problema;
					$body_top  = "Content-type: text/html; charset=iso-8859-1 \n";
					$body_top .= "From: {$email_ultimo_atendente} \n";

					if (strlen($msg_erro)==0){
						if ( @mail($email_atendente, stripslashes($assunto), $corpo, $body_top ) ){
							$msg = "<br>Foi enviado um email para: ".$email_atendente."<br>";
						}else{
							$msg_erro = "Não foi possível enviar o email. ";
						}
					}
				}
			}
		}
		//hd 14231 22/2/2008
		if(strlen($msg_erro)==0){
			if(strlen($consumidor_nome)>0 and strlen($xconsumidor_estado)>0 and strlen($xconsumidor_cidade)>0){
				$sql = "SELECT tbl_cidade.cidade
							FROM tbl_cidade
							where tbl_cidade.nome = $xconsumidor_cidade
							AND tbl_cidade.estado = $xconsumidor_estado
							limit 1";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res)>0){
						$cidade = pg_fetch_result($res,0,0);
					}else{
						$cidade = 'null';
					}
			}
			 // HD 122446 (augusto) - Lenoxx (11) - Salvar informações do consumidor se elas forem modificadas
			 // Para Lenox só é possível modificar as informações de cliente se o chamado ainda estiver aberto
			 // HD 124579 (augusto) - Todas as fábricas: acrescentar update das informações do Produto
			if( strlen($hd_chamado)>0 && ( $login_fabrica != 11 || ( $login_fabrica == 11 && $xstatus_interacao == "'Aberto'" or strlen($protocolo)>0) ) or strlen($protocolo)>0) {//*ja tem cadastro no telecontrol/
				$_serie				= (empty($_POST['serie'])) ? 'null' : "'".pg_escape_string($_POST['serie'])."'" ;
				$_nota_fiscal		= (empty($_POST['nota_fiscal'])) ? 'null' : pg_escape_string($_POST['nota_fiscal']) ;
				$_data_nf			= (empty($_POST['data_nf'])) ? 'null' : "'".pg_escape_string(converte_data($_POST['data_nf']))."'" ;
				$sql = "SELECT  tbl_hd_chamado.hd_chamado,
								tbl_hd_chamado.status
						FROM tbl_hd_chamado
						JOIN tbl_hd_chamado_extra USING(hd_chamado)
						where tbl_hd_chamado.hd_chamado = $hd_chamado";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res)>0){
					$xhd_chamado = pg_fetch_result($res,0,'hd_chamado');
					$xstatus     = pg_fetch_result($res,0,'status');
					// HD 124579 (augusto) - Permitir alterar produto para todas as fábricas
					//if(($login_fabrica == 59 OR $login_fabrica == 30) AND $xstatus == 'Aberto'){
					$sql_produto = " , produto = $xproduto "; // HD 76545 - HD 108048
					//}

					$sql = "UPDATE tbl_hd_chamado_extra SET
								nome        = upper($xconsumidor_nome)       ,
								endereco    = upper($xconsumidor_endereco)   ,
								numero      = upper($xconsumidor_numero)     ,
								complemento = upper($xconsumidor_complemento),
								bairro      = upper($xconsumidor_bairro)     ,
								cep         = upper($xconsumidor_cep)        ,
								fone        = $xconsumidor_fone				 ,
								fone2       = $xconsumidor_fone2			 ,
								celular     = $xconsumidor_fone3			 ,
								email       = upper($xconsumidor_email)      ,
								cpf         = $xconsumidor_cpf				 ,
								rg          = upper($xconsumidor_rg)         ,
								nota_fiscal = '$_nota_fiscal',
								data_nf     = {$_data_nf},
								serie       = {$_serie},
								cidade      = $cidade                        ,
								defeito_reclamado_descricao = '$hd_extra_defeito'
								$sql_produto,
								revenda_cnpj		 = '$cnpj_revenda',
								revenda_nome		 = $xrevenda_nome
							WHERE tbl_hd_chamado_extra.hd_chamado = $xhd_chamado";
							
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}

			if($login_fabrica == 52) {
				if (strlen($cliente_admin)>0) {
					$sql = "UPDATE tbl_hd_chamado set cliente_admin = $cliente_admin where hd_chamado = $xhd_chamado;";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}
		}
		/* HD 37805 */
		if ($tab_atual == "ressarcimento" and strlen($msg_erro)==0){

			$sql = "SELECT hd_chamado
					FROM tbl_hd_chamado_extra_banco
					WHERE hd_chamado = $hd_chamado ";
			$resx = @pg_query($con,$sql);
			if(@pg_num_rows($resx) == 0){
				$sql = "INSERT INTO tbl_hd_chamado_extra_banco ( hd_chamado ) values ( $hd_chamado )";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}

			$sql = "UPDATE tbl_hd_chamado_extra_banco SET
									banco            = $xbanco,
									agencia          = $xagencia,
									contay           = $xcontay,
									nomebanco        = $xnomebanco,
									favorecido_conta = $xfavorecido_conta,
									cpf_conta        = $xcpf_conta,
									tipo_conta       = $xtipo_conta
					WHERE hd_chamado = $hd_chamado";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

			$sql = "SELECT hd_chamado
					FROM tbl_hd_chamado_troca
					WHERE hd_chamado = $hd_chamado ";
			$resx = @pg_query($con,$sql);
			if(@pg_num_rows($resx) == 0){
				$sql = "INSERT INTO tbl_hd_chamado_troca ( hd_chamado ) values ( $hd_chamado )";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}

			$sql = "UPDATE tbl_hd_chamado_troca SET
							data_pagamento    = $xdata_pagamento,
							ressarcimento     = 't',
							numero_objeto     = NULL,
							nota_fiscal_saida = NULL,
							data_nf_saida     = NULL,
							produto           = NULL,
							valor_produto     = $xvalor_produto,
							valor_inpc        = $xvalor_inpc,
							valor_corrigido   = $xvalor_corrigido
					WHERE hd_chamado = $hd_chamado";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if (strlen($valor_produto)>0 AND strlen($valor_inpc)>0 AND strlen($msg_erro)==0){
				$sql = "SELECT CURRENT_DATE - data_nf AS qtde_dias
						FROM tbl_hd_chamado_extra
						WHERE hd_chamado = $hd_chamado ";
				$resx = @pg_query($con,$sql);
				if(@pg_num_rows($resx) > 0){
					#echo "<hr>";
					$qtde_dias = pg_fetch_result($resx,0,qtde_dias);
					if ($qtde_dias>0){
						$valor_corrigido = $valor_produto + ($valor_produto * $qtde_dias / 100);
						 $sql = "UPDATE tbl_hd_chamado_troca SET valor_corrigido = $valor_corrigido WHERE hd_chamado = $hd_chamado";
						$res = pg_query($con,$sql);
						$msg_erro .= pg_errormessage($con);
					}
				}
			}
		}

		/* HD 37805 */
		if ($tab_atual == "sedex_reverso" and strlen($msg_erro)==0){
			$sql = "SELECT hd_chamado
					FROM tbl_hd_chamado_troca
					WHERE hd_chamado = $hd_chamado ";
			$resx = @pg_query($con,$sql);
			if(@pg_num_rows($resx) == 0){
				$sql = "INSERT INTO tbl_hd_chamado_troca ( hd_chamado ) values ( $hd_chamado )";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}

			$sql = "UPDATE tbl_hd_chamado_troca SET
							data_pagamento       =  NULL
							ressarcimento        = 'f',
							numero_objeto        = $xnumero_objeto,
							nota_fiscal_saida    = $xnota_fiscal_saida,
							data_nf_saida        = $xdata_nf_saida,
							produto              = $xproduto_troca,
							data_retorno_produto = $xdata_retorno_produto
					WHERE hd_chamado = $hd_chamado";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

		if($tab_atual == "extensao" and strlen($msg_erro) == 0 and $login_fabrica==25){
			if (strlen($es_data_compra)==0){
				$msg_erro .= "Informe a data da Compra do produto.";
			}
		}

		$sql = "SELECT fn_callcenter_dias_interacao($hd_chamado,$login_fabrica)";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$sql = "SELECT fn_callcenter_dias_aberto($hd_chamado,$login_fabrica);";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

		$sql = "SELECT fn_callcenter_intervalo_interacao($hd_chamado,$login_fabrica);";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);

		// !129655
		// HD 129655 - Gravar dúvidas selecionadas [augusto]
		if (strlen($msg_erro) == 0) {
			gravarFaq();
		}
		
	}
	
	// HD 674943
		if ( $login_fabrica == 85 && strlen($msg_erro) == 0 ) {

			$sql = "SELECT pergunta
					FROM tbl_pergunta
					JOIN tbl_tipo_pergunta ON tbl_pergunta.tipo_pergunta = tbl_tipo_pergunta.tipo_pergunta AND tbl_tipo_pergunta.ativo
					WHERE fabrica = $login_fabrica
					AND tbl_pergunta.ativo";
					
			$res = pg_query($con,$sql);
			
			for($i = 0; $i < pg_num_rows($res); $i++) {

				$pergunta = pg_result($res,$i,'pergunta');
				$nota = $_POST['nota_'.$pergunta];

				if (!isset($_POST['nota_'.$pergunta]) || empty($nota) ) {
					continue;
				}
				
				if (strlen($hd_chamado) > 0) {
					$campo_gravar = $hd_chamado;
					$cond = ' hd_chamado = ' . $hd_chamado;
				}
				else {
					$campo_gravar = $callcenter;
					$cond = ' hd_chamado = ' . $callcenter;
				}
				
				$sql = "SELECT resposta FROM tbl_resposta
						JOIN tbl_pergunta USING(pergunta)
						JOIN tbl_tipo_pergunta USING(tipo_pergunta)
						WHERE $cond AND fabrica = $login_fabrica AND tbl_resposta.pergunta = $pergunta";
				$res2 = pg_query($con,$sql);
				
				if( pg_num_rows($res2) ) {
				
					$sql = "UPDATE tbl_resposta SET nota = $nota WHERE pergunta = $pergunta AND $cond";
				
				}
				else {
					$sql = "INSERT INTO tbl_resposta(hd_chamado, pergunta, nota) VALUES($campo_gravar,$pergunta,$nota)";
				}
				$res2 = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		
		}
	// HD 674943

	/************************************************************************************************/
	/********************************************* PRE-OS *******************************************/
	/************************************************************************************************/
	// HD 120306 - envia e-mail para o posto informando pre-OS cadastrada
	if (strlen($msg_erro)==0) {
		if($abre_os=='t'){
			//HD 211895: Caso o chamado já esteja aberto anteriormente, atualiza abre_os='t'
			if (strlen($callcenter)) {
				$sql = "UPDATE tbl_hd_chamado_extra SET abre_os=true WHERE hd_chamado=$callcenter";
				$res = pg_query($con, $sql);
				$msg_erro .= pg_errormessage($con);
			}

			switch($login_fabrica) {
				case 2: $sac_email = "sac@dynacom.com.br";
					break;
				case 52: $sac_email = "sac@fricon.com.br";
					break;
				case 96:
					$sac_email = "andre.dias@br.bosch.com; renato.lima2@br.bosch.com";
					break;
			}

			$sql = "Select email from tbl_admin where admin = $login_admin and fabrica = $login_fabrica";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);
			if(pg_num_rows($res)>0){
				$admin_email = pg_fetch_result($res,0,email);
			}else{
				$admin_email = $sac_email;
			}

			$sql = "SELECT contato_email from tbl_posto_fabrica where posto = $xcodigo_posto and fabrica=$login_fabrica";
			$res = pg_query($con,$sql);
			if(@pg_num_rows($res) > 0){
				$email_posto = pg_fetch_result($res,0,contato_email);
			}else{
				$sql = "SELECT email from tbl_posto where posto = $xcodigo_posto";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res)>0)
					$email_posto = pg_fetch_result($res,0,email);
			}
			if($login_fabrica == 30){
				$subject  = "Nova OS :: Call-center $clogin_fabrica_nome;";
			}else{
				$subject  = "Nova Pré-OS :: Call-center $clogin_fabrica_nome;";
			}
			
			//HD 199901 - Alterar mensagem de abertura de pré-OS
			$sql = "
			SELECT
			tbl_posto_fabrica.codigo_posto AS codigo_posto,
			tbl_posto.nome AS posto_nome,
			tbl_fabrica.nome AS fabrica_nome,
			TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YY HH24:MI') AS data_atendimento,
			tbl_hd_chamado_extra.nome,
			tbl_hd_chamado_extra.endereco,
			tbl_hd_chamado_extra.numero,
			tbl_hd_chamado_extra.complemento,
			tbl_hd_chamado_extra.bairro,
			tbl_cidade.nome AS cidade_nome,
			tbl_cidade.estado,
			tbl_hd_chamado_extra.revenda_nome,
			tbl_revenda.cnpj AS revenda_cnpj,
			tbl_admin.nome_completo AS admin_nome,
			tbl_admin.email AS admin_email,
			tbl_hd_chamado_extra.posto

			FROM
			tbl_hd_chamado
			JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado=tbl_hd_chamado_extra.hd_chamado
			JOIN tbl_posto ON tbl_hd_chamado_extra.posto=tbl_posto.posto
			JOIN tbl_posto_fabrica ON tbl_posto.posto=tbl_posto_fabrica.posto
			JOIN tbl_fabrica ON tbl_posto_fabrica.fabrica=tbl_fabrica.fabrica
			LEFT JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade=tbl_cidade.cidade
			LEFT JOIN tbl_revenda ON tbl_hd_chamado_extra.revenda=tbl_revenda.revenda
			JOIN tbl_admin ON tbl_hd_chamado.admin=tbl_admin.admin

			WHERE
			tbl_hd_chamado.hd_chamado=$hd_chamado
			AND tbl_hd_chamado.fabrica=$login_fabrica
			AND tbl_posto_fabrica.fabrica=$login_fabrica
			";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res)) {
				$email_preos_codigo_posto = pg_fetch_result($res, 0, codigo_posto);
				$email_preos_posto_nome = pg_fetch_result($res, 0, posto_nome);
				$email_preos_fabrica_nome = pg_fetch_result($res, 0, fabrica_nome);
				$email_preos_data_atendimento = pg_fetch_result($res, 0, data_atendimento);
				$email_preos_nome = pg_fetch_result($res, 0, nome);
				$email_preos_endereco = pg_fetch_result($res, 0, endereco);
				$email_preos_numero = pg_fetch_result($res, 0, numero);
				$email_preos_complemento = pg_fetch_result($res, 0, complemento);
				$email_preos_bairro = pg_fetch_result($res, 0, bairro);
				$email_preos_cidade_nome = pg_fetch_result($res, 0, cidade_nome);
				$email_preos_estado = pg_fetch_result($res, 0, estado);
				$email_preos_revenda_nome = pg_fetch_result($res, 0, revenda_nome);
				$email_preos_revenda_cnpj = pg_fetch_result($res, 0, revenda_cnpj);
				$email_preos_admin_nome = pg_fetch_result($res, 0, admin_nome);
				$email_preos_admin_email = pg_fetch_result($res, 0, admin_email);
				$comunicado_posto       = pg_fetch_result($res,0,'posto');
				if ($login_fabrica == 52) {
					$sql = "
					SELECT
					tbl_produto.referencia,
					tbl_produto.descricao

					FROM
					tbl_hd_chamado_item
					JOIN tbl_produto ON tbl_hd_chamado_item.produto=tbl_produto.produto

					WHERE
					tbl_hd_chamado_item.hd_chamado=$hd_chamado
					";
					$res = pg_query($con, $sql);

					$produtos = array();
					for ($p = 0; $p < pg_num_rows($res); $p++) {
						$produtos[] = "[" . pg_fetch_result($res, $p, referencia) . "] " . pg_fetch_result($res, $p, descricao);
					}
					$produtos = implode(", ", $produtos);
				}
				else {
					$sql = "
					SELECT
					tbl_produto.referencia,
					tbl_produto.descricao

					FROM
					tbl_hd_chamado_extra
					JOIN tbl_produto ON tbl_hd_chamado_extra.produto=tbl_produto.produto

					WHERE
					tbl_hd_chamado_extra.hd_chamado=$hd_chamado
					";
					
					$res = pg_query($con, $sql);

					$produtos = "[" . pg_fetch_result($res, 0, 'referencia') . "] " . pg_fetch_result($res, 0, 'descricao');
				}
				
				$email_endereco = "";
				if ($email_preos_endereco) $email_endereco .= $email_preos_endereco;
				if ($email_preos_numero) $email_endereco .= ", " . $email_preos_numero;
				if ($email_preos_complemento) $email_endereco .= " " . $email_preos_complemento;
				if ($email_preos_bairro) $email_endereco .= " - " . $email_preos_bairro;
				if ($email_preos_cidade_nome) $email_endereco .= " - " . $email_preos_cidade_nome;
				if ($email_preos_estado) $email_endereco .= " - " . $email_preos_estado;

				if ($email_preos_revenda_cnpj) $email_preos_revenda_cnpj = " - " . $email_preos_revenda_cnpj;

$message="Autorizada $email_preos_codigo_posto - $email_preos_posto_nome

O Callcenter da Fábrica $email_preos_fabrica_nome, abriu um atendimento que se tornou uma ";
if($login_fabrica == 30){
	$message.="OS";
}else{
	$message.="pré-OS";
}
$message.= " para ser atendido pelo seu posto autorizado.
Segue as informações da ";
if($login_fabrica == 30){
	$message.="OS";
}else{
	$message.="pré-OS";
}
$message.= " :

Atendimento do Call-Center nº $hd_chamado - Aberto por $email_preos_admin_nome em $email_preos_data_atendimento
Produto: $produtos
Consumidor: $email_preos_nome ($email_endereco)
Revenda: $email_preos_revenda_cnpj $email_preos_revenda_nome";

if ($login_fabrica <> 59) {
	$message .= " Favor completar este atendimento o mais rápido possível, e qualquer dúvida, entrar em contato com ";
}


$message .= "$email_preos_admin_nome
Callcenter";

				$message = str_replace("\n", "<br>", $message);
				//$headers = "From: Call-center <$admin_email>\n";
				$headers = "From: Call-center <$email_preos_admin_email>\n";

				$headers .= "MIME-Version: 1.0\n";
				$headers .= "Content-type: text/html; charset=iso-8859-1\n";
				
				mail("$email_preos_admin_email",$subject,$message,$headers);
				mail("$email_posto",$subject,$message,$headers);
			}
			if($login_fabrica == 96)
				$admin_email = "null";
			$peca = "null";
			$produto = "null";
			$aux_familia = "null";
			$aux_linha = "null";
			$aux_extensao = "null";
			$aux_descricao = substr($subject, 0, 50);
			$aux_mensagem  = $message;
			$aux_tipo      = "Comunicado";
			$posto	       = ($xcodigo_posto =='null' or empty($xcodigo_posto)) ?$comunicado_posto : $xcodigo_posto;
			$aux_obrigatorio_os_produto = "'f'";
			$aux_obrigatorio_site = "'t'";
			$aux_tipo_posto = "null";
			$aux_ativo = "'t'";
			$aux_estado = "null";
			$aux_pais = "'BR'";
			$remetente_email = "$admin_email";
			$pedido_faturado="'f'";
			$pedido_em_garantia="'f'";
			$digita_os="'f'";
			$reembolso_peca_estoque="'f'";


			$sql = "INSERT INTO tbl_comunicado (
				peca                   ,
				produto                ,
				familia                ,
				linha                  ,
				extensao               ,
				descricao              ,
				mensagem               ,
				tipo                   ,
				fabrica                ,
				obrigatorio_os_produto ,
				obrigatorio_site       ,
				posto                  ,
				tipo_posto             ,
				ativo                  ,
				estado                 ,
				pais                   ,
				remetente_email        ,
				pedido_faturado        ,
				pedido_em_garantia     ,
				digita_os              ,
				reembolso_peca_estoque
				) VALUES (
				$peca                       ,
				$produto                    ,
				$aux_familia                ,
				$aux_linha                  ,
				$aux_extensao               ,
				'$aux_descricao'            ,
				'$aux_mensagem'             ,
				'$aux_tipo'                 ,
				$login_fabrica              ,
				$aux_obrigatorio_os_produto ,
				$aux_obrigatorio_site       ,
				$posto                      ,
				$aux_tipo_posto             ,
				$aux_ativo                  ,
				$aux_estado                 ,
				$aux_pais                   ,
				'$remetente_email'          ,
				$pedido_faturado            ,
				$pedido_em_garantia         ,
				$digita_os                  ,
				$reembolso_peca_estoque
			);";
			$res = @pg_query ($con,$sql);
			$msg_erro .= pg_errormessage($con);

		}	//IF PARA CONTROLAR ABERTURA DE PRE-OS
	}	//IF QUE VERIFICA SE TEM ERROS

	//HD 205933: Habilitar abertura de ORDEM DE SERVIÇO para a Esmaltec
	if ($abre_ordem_servico == 't' && strlen($msg_erro) == 0) {
		/************************************************************************************/
		/******************************* VALIDAÇÕES DOS CAMPOS *******************************/
		/************************************************************************************/
		$sql = "
		SELECT
		hd_chamado,
		tbl_hd_chamado_extra.posto,
		tbl_hd_chamado_extra.nome,
		tbl_cidade.nome AS cidade,
		tbl_cidade.estado,
		tbl_hd_chamado_extra.fone,
		tbl_hd_chamado_extra.cpf,
		tbl_hd_chamado_extra.endereco,
		tbl_hd_chamado_extra.bairro,
		tbl_hd_chamado_extra.celular,
		tbl_hd_chamado_extra.fone2,
		tbl_hd_chamado_extra.consumidor_revenda,
		tbl_hd_chamado_extra.produto,
		tbl_hd_chamado_extra.defeito_reclamado,
		tbl_hd_chamado_extra.defeito_reclamado_descricao,
		tbl_hd_chamado_extra.nota_fiscal,
		tbl_hd_chamado_extra.data_nf

		FROM
		tbl_hd_chamado_extra
		LEFT JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade=tbl_cidade.cidade

		WHERE
		tbl_hd_chamado_extra.hd_chamado=$hd_chamado
		";
		$res = pg_query($con, $sql);

		if (trim(pg_result($res, 0, posto)) == "") { $msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informar o posto autorizado<br>"; }
		if (trim(pg_result($res, 0, nome)) == "") { $msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informe o nome do consumidor<br>"; }
		if (trim(pg_result($res, 0, cidade)) == "") { $msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informe a cidade o consumidor<br>"; }
		if (trim(pg_result($res, 0, estado)) == "") { $msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informe o estado do consumidor<br>"; }
		if (trim(pg_result($res, 0, fone)) == "" && trim(pg_result($res, 0, celular)) == "" && trim(pg_result($res, 0, fone2)) == "") { $msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informe pelo menos um dos telefones do cliente<br>"; }
		if (trim(pg_result($res, 0, cpf)) == "" and $login_fabrica <> 30) { $msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informe o CPF do consumidor<br>"; }
		if (trim(pg_result($res, 0, endereco)) == "") { $msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informe o endereço do consumidor<br>"; }
		if (trim(pg_result($res, 0, bairro)) == "") { $msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informe o bairro do consumidor<br>"; }
		if (trim(pg_result($res, 0, produto)) == "") { $msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informe o produto<br>"; }
		if (trim(pg_result($res, 0, defeito_reclamado)) == "" && trim(pg_result($res, 0, defeito_reclamado_descricao)) == "") { $msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informe o defeito reclamado no produto<br>"; }
		if (trim(pg_result($res, 0, nota_fiscal)) == "") { $msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informe o número da nota fiscal<br>"; }
		if (trim(pg_result($res, 0, data_nf)) == "") { $msg_erro .= "Para abertura de ORDEM DE SERVIÇO, informe a data da nota fiscal<br>"; }
//		if (trim(pg_result($res, 0, )) == "") { $msg_erro .= "Para abertura de ORDEM DE SERVIÇO,<br>"; }
		
		if (strlen($msg_erro) == 0) {
			/************************************************************************************/
			/********************************** INSERÇÃO DA OS **********************************/
			/************************************************************************************/
			$xcodigo_posto = trim(pg_result($res, 0, posto));

			$sql = "
			INSERT INTO tbl_os (
			fabrica,
			posto,
			data_abertura,
			consumidor_nome,
			consumidor_cidade,
			consumidor_estado,
			consumidor_fone,
			consumidor_cpf,
			consumidor_endereco,
			consumidor_numero,
			consumidor_cep,
			consumidor_complemento,
			consumidor_bairro,
			consumidor_email,
			consumidor_celular,
			consumidor_fone_comercial,
			consumidor_revenda,
			revenda,
			revenda_cnpj,
			revenda_nome,
			revenda_fone,
			produto,
			serie,
			nota_fiscal,
			data_nf,
			defeito_reclamado,
			defeito_reclamado_descricao,
			admin,
			hd_chamado,
			cliente_admin,
			obs,
			observacao
			)

			SELECT
			$login_fabrica,
			tbl_hd_chamado_extra.posto,
			tbl_hd_chamado.data,
			tbl_hd_chamado_extra.nome,
			tbl_cidade.nome,
			tbl_cidade.estado,
			tbl_hd_chamado_extra.fone,
			tbl_hd_chamado_extra.cpf,
			tbl_hd_chamado_extra.endereco,
			tbl_hd_chamado_extra.numero,
			tbl_hd_chamado_extra.cep,
			tbl_hd_chamado_extra.complemento,
			tbl_hd_chamado_extra.bairro,
			tbl_hd_chamado_extra.email,
			tbl_hd_chamado_extra.celular,
			tbl_hd_chamado_extra.fone2,
			tbl_hd_chamado_extra.consumidor_revenda,
			tbl_hd_chamado_extra.revenda,
			tbl_hd_chamado_extra.revenda_cnpj,
			tbl_hd_chamado_extra.revenda_nome,
			tbl_revenda.fone,
			tbl_hd_chamado_extra.produto,
			tbl_hd_chamado_extra.serie,
			tbl_hd_chamado_extra.nota_fiscal,
			tbl_hd_chamado_extra.data_nf,
			tbl_hd_chamado_extra.defeito_reclamado,
			tbl_hd_chamado_extra.defeito_reclamado_descricao,
			tbl_hd_chamado.admin,
			$hd_chamado,
			tbl_hd_chamado.cliente_admin,
			'Ordem de Serviço aberta pelo CallCenter, atendimento $hd_chamado',
			'Ordem de Serviço aberta pelo CallCenter, atendimento $hd_chamado'

			FROM
			tbl_hd_chamado
			JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado=tbl_hd_chamado_extra.hd_chamado
			LEFT JOIN tbl_revenda ON tbl_hd_chamado_extra.revenda=tbl_revenda.revenda
			LEFT JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade=tbl_cidade.cidade

			WHERE
			tbl_hd_chamado.hd_chamado=$hd_chamado
			";
			@$res = pg_query($con, $sql);
			$msg_erro .= pg_errormessage($con);
			
			if (strlen($msg_erro)==0) {
				$sql = "SELECT CURRVAL('seq_os')";
				@$res = pg_query($con, $sql);
				$os_aberta = pg_result($res, 0, 0);

				$sql = "SELECT fn_valida_os($os_aberta, $login_fabrica)";
				@$res = pg_query($con, $sql);
				$erro_valida = pg_errormessage($con);

				if (strlen($erro_valida)==0) {
					$sql = "SELECT sua_os FROM tbl_os WHERE os=$os_aberta";
					@$res = pg_query($con, $sql);
					$msg_erro .= pg_errormessage($con);
					$sua_os_aberta = pg_result($res, 0, sua_os);

					$sql = "UPDATE tbl_hd_chamado_extra SET os=$os_aberta WHERE hd_chamado=$hd_chamado";
					@$res = pg_query($con, $sql);
					$msg_erro .= pg_errormessage($con);
					if($login_fabrica == 30){
						///insere o intervenção de KM para esmaltec - hd 311031
						$sql = "INSERT into tbl_os_status (os,status_os,observacao) values ($os_aberta,98,'Os aberto pelo Callcenter');";
						@$res = pg_query($con, $sql);
						$msg_erro .= pg_errormessage($con);
						////
					}
				}
				else {
					$erro_valida = explode("CONTEXT", $erro_valida);
					$erro_valida = explode("ERROR:", $erro_valida[0]);
					$erro_valida = trim($erro_valida[1]);
					$msg_erro .= $erro_valida . "<br>";
				}
			}
			//Envia e-mail para o posto informando OS cadastrada
			if (strlen($msg_erro)==0) {
				$sql = "SELECT email FROM tbl_admin WHERE admin = $login_admin AND fabrica = $login_fabrica";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);

				switch($login_fabrica) {
					case 2:
						$sac_email = "sac@dynacom.com.br";
					break;

					case 52:
						$sac_email = "sac@fricon.com.br";
					break;
					case 96:
						//$sac_email = "brayan@telecontrol.com.br; ronald@telecontrol.com.br";
					break;
				}

				if(pg_num_rows($res)>0){
					$admin_email = pg_fetch_result($res,0,email);
				}
				else {
					$admin_email = $sac_email;
				}

				$sql = "SELECT contato_email FROM tbl_posto_fabrica WHERE posto = $xcodigo_posto AND fabrica=$login_fabrica";
				$res = pg_query($con,$sql);

				if(@pg_num_rows($res) == 0){
					$email_posto = pg_fetch_result($res, 0, contato_email);
				}else{
					$sql = "SELECT email from tbl_posto where posto = $xcodigo_posto";
					$res = pg_query($con, $sql);
					$email_posto = pg_fetch_result($res, 0, email);
				}

				$subject  = "Nova ORDEM DE SERVIÇO nº$sua_os_aberta :: $clogin_fabrica_nome";
				
				//Mensagem de abertura de ORDEM DE SERVICO
				$sql = "
				SELECT
				tbl_posto_fabrica.codigo_posto AS codigo_posto,
				tbl_posto.nome AS posto_nome,
				tbl_fabrica.nome AS fabrica_nome,
				TO_CHAR(tbl_hd_chamado.data, 'DD/MM/YY HH24:MI') AS data_atendimento,
				tbl_hd_chamado_extra.nome,
				tbl_hd_chamado_extra.endereco,
				tbl_hd_chamado_extra.numero,
				tbl_hd_chamado_extra.complemento,
				tbl_hd_chamado_extra.bairro,
				tbl_cidade.nome AS cidade_nome,
				tbl_cidade.estado,
				tbl_hd_chamado_extra.revenda_nome,
				tbl_revenda.cnpj AS revenda_cnpj,
				tbl_admin.nome_completo AS admin_nome

				FROM
				tbl_hd_chamado
				JOIN tbl_hd_chamado_extra ON tbl_hd_chamado.hd_chamado=tbl_hd_chamado_extra.hd_chamado
				JOIN tbl_posto ON tbl_hd_chamado_extra.posto=tbl_posto.posto
				JOIN tbl_posto_fabrica ON tbl_posto.posto=tbl_posto_fabrica.posto
				JOIN tbl_fabrica ON tbl_posto_fabrica.fabrica=tbl_fabrica.fabrica
				LEFT JOIN tbl_cidade ON tbl_hd_chamado_extra.cidade=tbl_cidade.cidade
				LEFT JOIN tbl_revenda ON tbl_hd_chamado_extra.revenda=tbl_revenda.revenda
				JOIN tbl_admin ON tbl_hd_chamado.admin=tbl_admin.admin

				WHERE
				tbl_hd_chamado.hd_chamado=$hd_chamado
				AND tbl_hd_chamado.fabrica=$login_fabrica
				AND tbl_posto_fabrica.fabrica=$login_fabrica
				";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res)) {
					$email_os_codigo_posto = pg_fetch_result($res, 0, codigo_posto);
					$email_os_posto_nome = pg_fetch_result($res, 0, posto_nome);
					$email_os_fabrica_nome = pg_fetch_result($res, 0, fabrica_nome);
					$email_os_data_atendimento = pg_fetch_result($res, 0, data_atendimento);
					$email_os_nome = pg_fetch_result($res, 0, nome);
					$email_os_endereco = pg_fetch_result($res, 0, endereco);
					$email_os_numero = pg_fetch_result($res, 0, numero);
					$email_os_complemento = pg_fetch_result($res, 0, complemento);
					$email_os_bairro = pg_fetch_result($res, 0, bairro);
					$email_os_cidade_nome = pg_fetch_result($res, 0, cidade_nome);
					$email_os_estado = pg_fetch_result($res, 0, estado);
					$email_os_revenda_nome = pg_fetch_result($res, 0, revenda_nome);
					$email_os_revenda_cnpj = pg_fetch_result($res, 0, revenda_cnpj);
					$email_os_admin_nome = pg_fetch_result($res, 0, admin_nome);
					
					if ($login_fabrica == 52) {
						$sql = "
						SELECT
						tbl_produto.referencia,
						tbl_produto.descricao

						FROM
						tbl_hd_chamado_item
						JOIN tbl_produto ON tbl_hd_chamado_item.produto=tbl_produto.produto

						WHERE
						tbl_hd_chamado_item.hd_chamado=$hd_chamado
						";
						$res = pg_query($con, $sql);

						$produtos = array();
						for ($p = 0; $p < pg_num_rows($res); $p++) {
							$produtos[] = "[" . pg_fetch_result($res, $p, referencia) . "] " . pg_fetch_result($res, $p, descricao);
						}
						$produtos = implode(", ", $produtos);
					}
					else {
						$sql = "
						SELECT
						tbl_produto.referencia,
						tbl_produto.descricao

						FROM
						tbl_hd_chamado_extra
						JOIN tbl_produto ON tbl_hd_chamado_extra.produto=tbl_produto.produto

						WHERE
						tbl_hd_chamado_extra.hd_chamado=$hd_chamado
						";
						$res = pg_query($con, $sql);

						$produtos = "[" . pg_fetch_result($res, 0, referencia) . "] " . pg_fetch_result($res, 0, descricao);
					}
					
					$email_endereco = "";
					if ($email_os_endereco) $email_endereco .= $email_os_endereco;
					if ($email_os_numero) $email_endereco .= ", " . $email_os_numero;
					if ($email_os_complemento) $email_endereco .= " " . $email_os_complemento;
					if ($email_os_bairro) $email_endereco .= " - " . $email_os_bairro;
					if ($email_os_cidade_nome) $email_endereco .= " - " . $email_os_cidade_nome;
					if ($email_os_estado) $email_endereco .= " - " . $email_os_estado;

					if ($email_os_revenda_cnpj) $email_os_revenda_cnpj = " - " . $email_os_revenda_cnpj;

					$message="Autorizada $email_os_codigo_posto - $email_os_posto_nome

	O Callcenter da Fábrica $email_os_fabrica_nome, abriu um atendimento que se tornou uma ORDEM DE SERVIÇO para ser atendido pelo seu posto autorizado.

	Segue as informações da ORDEM DE SERVIÇO nº $sua_os_aberta:

	Atendimento do Call-Center nº $hd_chamado - Aberto por $email_os_admin_nome em $email_os_data_atendimento
	Produto: $produtos
	Consumidor: $email_os_nome ($email_endereco)
	Revenda: $email_os_revenda_cnpj $email_os_revenda_nome

	Favor completar este atendimento o mais rápido possível, e qualquer dúvida, entrar em contato.

	$email_os_admin_nome

	Callcenter $login_fabrica_nome";
					$message = str_replace("\n", "<br>", $message);
					$headers = "From: Call-center <$admin_email>\n";

					$headers .= "MIME-Version: 1.0\n";
					$headers .= "Content-type: text/html; charset=iso-8859-1\n";
					
					if ($admin_email) {
						mail("$admin_email",$subject,$message,$headers);
					}

					if ($email_posto) {
						mail("$email_posto",$subject,$message,$headers);
					}
				}
				if($login_fabrica == 96)
					$admin_email = "null";
				$peca = "null";
				$produto = "null";
				$aux_familia = "null";
				$aux_linha = "null";
				$aux_extensao = "null";
				$aux_descricao = substr($subject, 0, 50);
				$aux_mensagem  = $message;
				$aux_tipo      = "Comunicado";
				$posto	       = $xcodigo_posto;
				$aux_obrigatorio_os_produto = "'f'";
				$aux_obrigatorio_site = "'t'";
				$aux_tipo_posto = "null";
				$aux_ativo = "'t'";
				$aux_estado = "null";
				$aux_pais = "'BR'";
				$remetente_email = "$admin_email";
				$pedido_faturado="'f'";
				$pedido_em_garantia="'f'";
				$digita_os="'f'";
				$reembolso_peca_estoque="'f'";


				$sql = "INSERT INTO tbl_comunicado (
					peca                   ,
					produto                ,
					familia                ,
					linha                  ,
					extensao               ,
					descricao              ,
					mensagem               ,
					tipo                   ,
					fabrica                ,
					obrigatorio_os_produto ,
					obrigatorio_site       ,
					posto                  ,
					tipo_posto             ,
					ativo                  ,
					estado                 ,
					pais                   ,
					remetente_email        ,
					pedido_faturado        ,
					pedido_em_garantia     ,
					digita_os              ,
					reembolso_peca_estoque
					) VALUES (
					$peca                       ,
					$produto                    ,
					$aux_familia                ,
					$aux_linha                  ,
					$aux_extensao               ,
					'$aux_descricao'            ,
					'$aux_mensagem'             ,
					'$aux_tipo'                 ,
					$login_fabrica              ,
					$aux_obrigatorio_os_produto ,
					$aux_obrigatorio_site       ,
					$posto                      ,
					$aux_tipo_posto             ,
					$aux_ativo                  ,
					$aux_estado                 ,
					$aux_pais                   ,
					'$remetente_email'          ,
					$pedido_faturado            ,
					$pedido_em_garantia         ,
					$digita_os                  ,
					$reembolso_peca_estoque
				);";
				$res = @pg_query ($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}	//IF QUE VERIFICA SE TEM ERROS - INSERÇÃO DA OS
		}	//IF QUE VERIFICA SE TEM ERROS - VALIDAÇÃO
	}

	//Se veio conteúdo na variável $callcenter é atualização
	if (strlen($callcenter)) {
		if (strlen($msg_erro) == 0){
			$res = pg_query($con,"COMMIT TRANSACTION");
			$msg_erro .= pg_errormessage($con);
			header ("Location: $PHP_SELF?callcenter=$hd_chamado");
			exit;
		}else{
			$res = pg_query($con,"ROLLBACK TRANSACTION");
		}
	}
	//Se não veio conteúdo na variável $callcenter é inserção
	else {
		if (strlen($msg_erro) == 0){
			$res = pg_query($con,"COMMIT TRANSACTION");


			#94971
			if($login_fabrica==59 AND ($finaliza==$linhas)){
				header ("Location: $PHP_SELF?indicacao_posto=$indicacao_posto&callcenter=$hd_chamado&$imprimir_os#$tab_atual");
				exit;
			}elseif($login_fabrica<>59){
				header ("Location: $PHP_SELF?indicacao_posto=$indicacao_posto&callcenter=$hd_chamado&$imprimir_os#$tab_atual");
				exit;
			}
		}else{
			$res = pg_query($con,"ROLLBACK TRANSACTION");
		}
	}

}	//IF DO BTN_ACAO

function saudacao(){
	$hora = date("H");
	echo ($hora >= 7 and $hora <= 11) ? "bom dia" : (($hora>=18) ? "boa noite" : "boa tarde");
}

$callcenter  = $_GET['callcenter'];
$imprimir_os = trim($_GET['imprimir_os']);


if(strlen($callcenter)>0){
	if(in_array($login_fabrica, array(96)))
		$produto_referencia_s = "tbl_produto.referencia_fabrica as produto_referencia,";
	else
		$produto_referencia_s = "tbl_produto.referencia as produto_referencia,";
	$sql = "SELECT	tbl_hd_chamado_extra.hd_chamado as callcenter,
					tbl_hd_chamado.admin as usuario_abriu,
					to_char(tbl_hd_chamado_extra.data_abertura,'DD/MM/YYYY') as abertura_callcenter,
					tbl_hd_chamado_extra.nome,
					tbl_hd_chamado.protocolo_cliente,
					tbl_hd_chamado_extra.endereco ,
					tbl_hd_chamado_extra.numero ,
					tbl_hd_chamado_extra.complemento ,
					tbl_hd_chamado_extra.bairro ,
					tbl_hd_chamado_extra.cep ,
					tbl_hd_chamado_extra.fone ,
					tbl_hd_chamado_extra.fone2 ,
					tbl_hd_chamado_extra.celular ,
					tbl_hd_chamado_extra.email ,
					tbl_hd_chamado_extra.cpf ,
					tbl_hd_chamado_extra.rg ,
					tbl_hd_chamado_extra.cliente ,
					tbl_hd_chamado_extra.consumidor_revenda,
					tbl_hd_chamado_extra.qtde_km,
					tbl_cidade.nome as cidade_nome,
					tbl_cidade.estado,
					tbl_hd_chamado_extra.origem,
					tbl_hd_chamado.admin AS admin_abriu,
					tbl_admin.login as atendente,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY HH24:MI') as data,
					tbl_hd_chamado.status,
					tbl_hd_chamado.categoria as natureza_operacao,
					tbl_posto.posto,
					tbl_posto_fabrica.contato_email as posto_email,
					tbl_posto.fone as posto_fone,
					tbl_hd_chamado.titulo as assunto,
					tbl_hd_chamado.categoria,
					tbl_produto.produto,
					tbl_produto.linha,
					$produto_referencia_s
					tbl_produto.descricao as produto_nome,
					tbl_produto.voltagem,
					tbl_defeito_reclamado.defeito_reclamado,
					tbl_defeito_reclamado.descricao as defeito_reclamado_descricao,
					tbl_hd_chamado_extra.reclamado,
					tbl_hd_chamado_extra.os,
					tbl_hd_chamado_extra.serie,
					to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') as data_nf,
					tbl_hd_chamado_extra.nota_fiscal,
					tbl_hd_chamado_extra.revenda,
					tbl_hd_chamado_extra.revenda_nome,
					tbl_hd_chamado_extra.ordem_montagem,
					tbl_hd_chamado_extra.codigo_postagem,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome as posto_nome,
					to_char(tbl_hd_chamado_extra.data_abertura_os,'DD/MM/YYYY') as data_abertura,
					tbl_hd_chamado_extra.receber_info_fabrica,
					tbl_os.sua_os as sua_os,
					tbl_hd_chamado_extra.abre_os,
					tbl_hd_chamado_extra.leitura_pendente,
					tbl_hd_chamado.atendente as atendente_pendente,
					tbl_hd_chamado_extra.defeito_reclamado_descricao as hd_extra_defeito,
					tbl_hd_chamado_extra.numero_processo,
					tbl_hd_chamado_extra.tipo_registro  ,
					tbl_hd_chamado_extra.atendimento_callcenter  ,
					tbl_hd_chamado_extra.familia ,
					tbl_admin.login            AS admin_login ,
					tbl_admin.nome_completo    AS admin_nome_completo,
					tbl_cliente_admin.nome     as nome_cliente_admin,
					tbl_cliente_admin.cliente_admin,
					tbl_hd_chamado_extra.revenda_cnpj
		FROM tbl_hd_chamado
		JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
		JOIN tbl_admin  on tbl_hd_chamado.admin = tbl_admin.admin
		LEFT JOIN tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_hd_chamado.cliente_admin
		LEFT JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
		LEFT JOIN tbl_posto on tbl_hd_chamado_extra.posto = tbl_posto.posto
		LEFT JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto  and tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto
		LEFT JOIN tbl_revenda on tbl_revenda.revenda = tbl_hd_chamado_extra.revenda
		LEFT JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
		LEFT JOIN tbl_os on tbl_os.os = tbl_hd_chamado_extra.os
		WHERE tbl_hd_chamado.fabrica_responsavel = $login_fabrica
		AND tbl_hd_chamado.hd_chamado = $callcenter";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			$callcenter               = pg_fetch_result($res,0,'callcenter');
			$usuario_abriu			  = pg_fetch_result($res,0,'usuario_abriu');
			$abertura_callcenter      = pg_fetch_result($res,0,'abertura_callcenter');
			$data_abertura_callcenter = pg_fetch_result($res,0,'data');
			$natureza_chamado         = pg_fetch_result($res,0,'natureza_operacao');
			$consumidor_nome          = pg_fetch_result($res,0,'nome');
			$cliente                  = pg_fetch_result($res,0,'cliente');
			$consumidor_cpf           = pg_fetch_result($res,0,'cpf');
			$consumidor_rg            = pg_fetch_result($res,0,'rg');
			$consumidor_email         = pg_fetch_result($res,0,'email');
			$consumidor_fone          = pg_fetch_result($res,0,'fone');
			$consumidor_fone2         = pg_fetch_result($res,0,'fone2');
			$consumidor_fone3         = pg_fetch_result($res,0,'celular');
			$consumidor_cep           = pg_fetch_result($res,0,'cep');
			$consumidor_endereco      = pg_fetch_result($res,0,'endereco');
			$consumidor_numero        = pg_fetch_result($res,0,'numero');
			$consumidor_complemento   = pg_fetch_result($res,0,'complemento');
			$consumidor_bairro        = pg_fetch_result($res,0,'bairro');
			$consumidor_cidade        = pg_fetch_result($res,0,'cidade_nome');
			$consumidor_estado        = pg_fetch_result($res,0,'estado');
			$consumidor_revenda       = pg_fetch_result($res,0,'consumidor_revenda');
			$origem                   = pg_fetch_result($res,0,'origem');
			$assunto                  = pg_fetch_result($res,0,'assunto');
			$sua_os                   = pg_fetch_result($res,0,'sua_os');
			$os                       = pg_fetch_result($res,0,'os');
			$data_abertura            = pg_fetch_result($res,0,'data_abertura');
			$produto                  = pg_fetch_result($res,0,'produto');
			$produto_referencia       = pg_fetch_result($res,0,'produto_referencia');
			$produto_nome             = pg_fetch_result($res,0,'produto_nome');
			$voltagem                 = pg_fetch_result($res,0,'voltagem');
			$serie                    = pg_fetch_result($res,0,'serie');
			$data_nf                  = pg_fetch_result($res,0,'data_nf');
			$nota_fiscal              = pg_fetch_result($res,0,'nota_fiscal');
			$revenda                  = pg_fetch_result($res,0,'revenda');
			$revenda_nome             = pg_fetch_result($res,0,'revenda_nome');
			$ordem_montagem 		  = pg_fetch_result ($res,0,ordem_montagem);
			$codigo_postagem		  = pg_fetch_result ($res,0,codigo_postagem);
			$posto                    = pg_fetch_result($res,0,'posto');
			$posto_nome               = pg_fetch_result($res,0,'posto_nome');
			$defeito_reclamado        = pg_fetch_result($res,0,'defeito_reclamado');
			$reclamado                = pg_fetch_result($res,0,'reclamado');
			$status_interacao         = pg_fetch_result($res,0,'status');
			$atendente                = pg_fetch_result($res,0,'atendente');
			$receber_informacoes	  = pg_fetch_result($res,0,'receber_info_fabrica');
			$codigo_posto	          = pg_fetch_result($res,0,'codigo_posto');
			$linha         	          = pg_fetch_result($res,0,'linha');
			$abre_os                  = pg_fetch_result($res,0,'abre_os');
			$leitura_pendente         = pg_fetch_result($res,0,'leitura_pendente');
			$atendente_pendente       = pg_fetch_result($res,0,'atendente_pendente');
			$categoria                = pg_fetch_result($res,0,'categoria');
			$hd_extra_defeito         = pg_fetch_result($res,0,'hd_extra_defeito');
			$numero_processo          = pg_fetch_result($res,0,'numero_processo');
			$tipo_registro            = pg_fetch_result($res,0,'tipo_registro');
			$admin_abriu              = pg_fetch_result($res,0,'admin_abriu');
			$familia                  = pg_fetch_result($res,0,'familia');
			$admin_login              = pg_fetch_result($res,0,'admin_login');
			$admin_nome_completo      = pg_fetch_result($res,0,'admin_nome_completo');
			$cliente_admin            = pg_fetch_result($res,0,'cliente_admin');
			$cliente_nome_admin       = pg_fetch_result($res,0,'nome_cliente_admin');
			$posto_km_tab             = pg_fetch_result($res,0,'qtde_km');
			$posto_email_tab          = pg_fetch_result($res,0,'posto_email');
			$posto_fone_tab           = pg_fetch_result($res,0,'posto_fone');
			$cnpj_revenda             = pg_fetch_result($res,0,'revenda_cnpj');
			$atendimento_callcenter   = pg_fetch_result($res,0,'atendimento_callcenter');
			$protocolo_cliente		  = pg_fetch_result($res,0,'protocolo_cliente');

			//HD 205933: Habilitar abertura de ORDEM DE SERVIÇO para a Esmaltec
			if ($os) {
				$abre_ordem_servico = 't';
			}
			else {
				$abre_ordem_servico = 'f';
			}

			if (is_numeric($consumidor_cpf)) {
			    $mask = (strlen($consumidor_cpf) == 14) ? '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/':'/(\d{3})(\d{3})(\d{3})(\d{2})/';
			    $fmt  = (strlen($consumidor_cpf) == 14) ? '$1.$2.$3/$4-$5':'$1.$2.$3-$4';
				$consumidor_cpf = preg_replace($mask, $fmt, $consumidor_cpf);
			}

			if ($login_fabrica == 51 and $leitura_pendente == "t"){
				if ($atendente_pendente == $login_admin){
					$sql = "UPDATE tbl_hd_chamado_extra set leitura_pendente = null
							WHERE hd_chamado = $callcenter	";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}
			if(strlen($codigo_posto)>0) {
				$procon_codigo_posto = $codigo_posto;
				$procon_posto_nome   = $posto_nome;
				$codigo_posto_tab   = $codigo_posto;
				$posto_nome_tab     = $posto_nome;
			}

			$sql ="SELECT	tbl_hd_chamado_troca.valor_corrigido   ,
							tbl_hd_chamado_troca.hd_chamado        ,
							to_char(tbl_hd_chamado_troca.data_pagamento,'DD/MM/YYYY') as data_pagamento,
							tbl_hd_chamado_troca.ressarcimento     ,
							tbl_hd_chamado_troca.numero_objeto     ,
							tbl_hd_chamado_troca.nota_fiscal_saida ,
							TO_CHAR(tbl_hd_chamado_troca.data_nf_saida,'DD/MM/YYYY')        AS data_nf_saida,
							TO_CHAR(tbl_hd_chamado_troca.data_retorno_produto,'DD/MM/YYYY') AS data_retorno_produto,
							tbl_hd_chamado_troca.valor_produto     ,
							tbl_hd_chamado_troca.valor_inpc        ,
							tbl_hd_chamado_troca.valor_corrigido   ,
							tbl_produto.referencia                 AS troca_produto_referencia,
							tbl_produto.referencia                 AS troca_produto_descricao
				FROM tbl_hd_chamado_troca
				LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_troca.produto
				WHERE tbl_hd_chamado_troca.hd_chamado = $callcenter";
			$res = pg_query($con,$sql);

			if(pg_num_rows($res)>0){
				$valor_corrigido           = pg_fetch_result($res,0,valor_corrigido);
				$hd_chamado                = pg_fetch_result($res,0,hd_chamado);
				$data_pagamento            = pg_fetch_result($res,0,data_pagamento);
				$ressarcimento             = pg_fetch_result($res,0,ressarcimento);
				$numero_objeto             = pg_fetch_result($res,0,numero_objeto);
				$nota_fiscal_saida         = pg_fetch_result($res,0,nota_fiscal_saida);
				$nota_fiscal_saida         = pg_fetch_result($res,0,nota_fiscal_saida);
				$data_nf_saida             = pg_fetch_result($res,0,data_nf_saida);
				$data_retorno_produto      = pg_fetch_result($res,0,data_retorno_produto);
				$valor_produto             = pg_fetch_result($res,0,valor_produto);
				$valor_inpc                = pg_fetch_result($res,0,valor_inpc);
				$valor_corrigido           = pg_fetch_result($res,0,valor_corrigido);
				$troca_produto_referencia  = pg_fetch_result($res,0,troca_produto_referencia);
				$troca_produto_descricao   = pg_fetch_result($res,0,troca_produto_descricao);
			}

			/* HD 37805 - Adicionei 59 - Arrumei esta parte de baixo*/
			if ($login_fabrica==59){
				$tipo_atendimento = array(	1 => 'reclamacao_produto',
											2 => 'reclamacao_empresa',
											3 => 'reclamacao_at',
											4 => 'duvida_produto',
											5 => 'sugestao',
											6 => 'onde_comprar',
											7 => 'ressarcimento',
											8 => 'sedex_reverso');
			}elseif ($login_fabrica == 2) {
				if ( $natureza_chamado == 'aquisicao_mat' or $natureza_chamado == 'aquisicao_prod' or $natureza_chamado == 'indicacao_posto' or $natureza_chamado == 'solicitacao_manual') {
				$natureza_chamado2 = $natureza_chamado;
				$natureza_chamado = "reclamacao_produto";

				}
				if ( $natureza_chamado == 'reclamacao_revenda' or $natureza_chamado == 'reclamacao_at' or $natureza_chamado == 'reclamacao_enderecos') {
					$natureza_chamado2 = $natureza_chamado;
					$natureza_chamado = "reclamacoes";
				}
					$tipo_atendimento = array(	1 => 'reclamacao_produto',
												2 => 'reclamacoes',
												3 => 'duvida_produto',
												4 => 'sugestao',
												5 => 'procon' ,
												6 => 'onde_comprar');
			} elseif($login_fabrica == 11) {

					$sub_tipo_reclamacao = array("mau_atendimento","posto_nao_contribui","demonstra_desorg","possui_bom_atend","demonstra_org","reclamacao_at_info");
					if(in_array($natureza_chamado, $sub_tipo_reclamacao) or $natureza_chamado == 'reclamacao_at'){
						$natureza_chamado2 = $natureza_chamado;
						$natureza_chamado = "reclamacao_at";
					}
					$sub_reclamacao_procon = array("pr_reclamacao_at","pr_info_at","pr_mau_atend","pr_posto_n_contrib","pr_demonstra_desorg","pr_bom_atend","pr_demonstra_org");

					if($natureza_chamado == 'procon' or in_array($natureza_chamado, $sub_reclamacao_procon) ) {
						$natureza_chamado2 = $natureza_chamado;
						$natureza_chamado  = "procon";
					}

					$tipo_atendimento = array(
							1 => 'reclamacao_produto',
							2 => 'reclamacao_empresa',
							3 => 'reclamacao_at',
							4 => 'duvida_produto',
							5 => 'sugestao',
							6 => 'procon' ,
							7 => 'onde_comprar');
			}elseif($login_fabrica ==1){

				$hd_posto = array("digitacao_fechamento_de_os","utilizacao_do_site","falha_no_site","pendencias_de_pecas","pedido_de_pecas","duvida_tecnica_sobre_produto","outros");
				if(in_array($natureza_chamado, $hd_posto) ){
					$natureza_chamado = "hd_posto";
				}
				$tipo_atendimento = array(	1 => 'extensao',
											2 => 'reclamacao_produto',
											3 => 'reclamacao_empresa',
											4 => 'reclamacao_at',
											5 => 'duvida_produto',
											6 => 'sugestao',
											7 => 'assistencia',
											8 => 'garantia',
											9 => 'troca_produto',
											10 => 'procon' ,
											11 => 'onde_comprar',
											12 => 'hd_posto');
			} else {
				$tipo_atendimento = array(	1 => 'extensao',
											2 => 'reclamacao_produto',
											3 => 'reclamacao_empresa',
											4 => 'reclamacao_at',
											5 => 'duvida_produto',
											6 => 'sugestao',
											7 => 'assistencia',
											8 => 'garantia',
											9 => 'troca_produto',
											10 => 'procon' ,
											11 => 'onde_comprar',
											12 => 'ressarcimento',
											13 => 'outros_assuntos');
			}
			$posicao = array_search($natureza_chamado, $tipo_atendimento); // $key = 2;
			if ($imprimir_os == 't' AND strlen ($os) > 0 ) {
				echo "<script language='javascript'>";
				echo "window.open ('os_print.php?os=$os&qtde_etiquetas=$qtde_etiquetas','os_print','resizable=yes,resize=yes,toolbar=no,location=yes,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0')";
				echo "</script>";
			}
		}
		if($assunto=='Indicação de Posto' and ($login_fabrica==5 or $login_fabrica == 24)) {
			$indicacao_posto='t';
		}
}

$Id = $_GET['Id'];
if(strlen($Id)>0){
	$sql = "SELECT	tbl_hd_chamado_extra.hd_chamado as callcenter,
					to_char(tbl_hd_chamado_extra.data_abertura,'DD/MM/YYYY') as abertura_callcenter,
					tbl_hd_chamado_extra.nome,
					tbl_hd_chamado_extra.endereco ,
					tbl_hd_chamado_extra.numero ,
					tbl_hd_chamado_extra.complemento ,
					tbl_hd_chamado_extra.bairro ,
					tbl_hd_chamado_extra.cep ,
					tbl_hd_chamado_extra.fone ,
					tbl_hd_chamado_extra.fone2 ,
					tbl_hd_chamado_extra.celular ,
					tbl_hd_chamado_extra.email ,
					tbl_hd_chamado_extra.cpf ,
					tbl_hd_chamado_extra.rg ,
					tbl_hd_chamado_extra.cliente ,
					tbl_hd_chamado_extra.ordem_montagem,
					tbl_hd_chamado_extra.codigo_postagem,
					tbl_hd_chamado_extra.consumidor_revenda ,
					tbl_cidade.nome as cidade_nome,
					tbl_cidade.estado,
					tbl_produto.produto,
					tbl_produto.linha,
					tbl_produto.referencia as produto_referencia,
					tbl_produto.descricao as produto_nome,
					tbl_produto.voltagem,
					tbl_hd_chamado_extra.serie,
					to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') as data_nf,
					tbl_hd_chamado_extra.nota_fiscal,
					tbl_hd_chamado_extra.abre_os,
					tbl_hd_chamado_extra.leitura_pendente,
					tbl_hd_chamado.atendente as atendente_pendente,
					tbl_hd_chamado_extra.defeito_reclamado_descricao as hd_extra_defeito,
					tbl_hd_chamado_extra.tipo_registro ,
					tbl_admin.login            AS admin_login ,
					tbl_admin.nome_completo    AS admin_nome_completo,
					tbl_cliente_admin.nome     as nome_cliente_admin,
					tbl_cliente_admin.cliente_admin,
					tbl_hd_chamado_extra.os
		FROM      tbl_hd_chamado
		JOIN      tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
		LEFT JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
		LEFT JOIN tbl_cliente_admin ON tbl_cliente_admin.cliente_admin = tbl_hd_chamado.cliente_admin
		LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto
		LEFT JOIN tbl_admin ON tbl_hd_chamado.admin = tbl_admin.admin
		WHERE tbl_hd_chamado.hd_chamado = $Id";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res)>0){
		$consumidor_nome           = pg_fetch_result($res,0,nome);
		$cliente                   = pg_fetch_result($res,0,cliente);
		$consumidor_cpf            = pg_fetch_result($res,0,cpf);
		$consumidor_rg             = pg_fetch_result($res,0,rg);
		$consumidor_email          = pg_fetch_result($res,0,email);
		$consumidor_fone           = pg_fetch_result($res,0,fone);
		$consumidor_fone2          = pg_fetch_result($res,0,fone2);
		$consumidor_fone3          = pg_fetch_result($res,0,celular);
		$consumidor_cep            = pg_fetch_result($res,0,cep);
		$consumidor_endereco      = pg_fetch_result($res,0,endereco);
		$consumidor_numero        = pg_fetch_result($res,0,numero);
		$consumidor_complemento   = pg_fetch_result($res,0,complemento);
		$consumidor_bairro        = pg_fetch_result($res,0,bairro);
		$consumidor_cidade        = pg_fetch_result($res,0,cidade_nome);
		$consumidor_estado        = pg_fetch_result($res,0,estado);
		$produto                  = pg_fetch_result($res,0,produto);
		$produto_referencia       = pg_fetch_result($res,0,produto_referencia);
		$produto_nome             = pg_fetch_result($res,0,produto_nome);
		$voltagem                 = pg_fetch_result($res,0,voltagem);
		$serie                    = pg_fetch_result($res,0,serie);
		$data_nf                  = pg_fetch_result($res,0,data_nf);
		$nota_fiscal              = pg_fetch_result($res,0,nota_fiscal);
		$revenda                  = pg_fetch_result($res,0,consumidor_revenda);
		$abre_os                  = pg_fetch_result($res,0,abre_os);
		$leitura_pendente         = pg_fetch_result($res,0,leitura_pendente);
		$atendente_pendente       = pg_fetch_result($res,0,atendente_pendente);
		$hd_extra_defeito         = pg_fetch_result($res,0,hd_extra_defeito);
		$tipo_registro            = pg_fetch_result($res,0,tipo_registro);
		$admin_login              = pg_fetch_result($res,0,admin_login);
		$admin_nome_completo      = pg_fetch_result($res,0,admin_nome_completo);
		$cliente_admin            = pg_fetch_result($res,0,cliente_admin);
		$cliente_nome_admin       = pg_fetch_result($res,0,nome_cliente_admin);

		if (is_numeric($consumidor_cpf)) {
		    $mask = (strlen($consumidor_cpf) == 14) ? '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/':'/(\d{3})(\d{3})(\d{3})(\d{2})/';
		    $fmt  = (strlen($consumidor_cpf) == 14) ? '$1.$2.$3/$4-$5':'$1.$2.$3-$4';
			$consumidor_cpf = preg_replace($mask, $fmt, $consumidor_cpf);
		}
		if ($login_fabrica == 51 and $leitura_pendente == "t"){
			if ($atendente_pendente == $login_admin){
				$sql = "UPDATE tbl_hd_chamado_extra set leitura_pendente = null
						WHERE hd_chamado = $Id";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}
		}

		//HD 205933: Habilitar abertura de ORDEM DE SERVIÇO para a Esmaltec
		$abre_ordem_servico = pg_result($res, 0, os);

		if ($abre_ordem_servico) {
			$abre_ordem_servico = 't';
		}
		else {
			$abre_ordem_servico = 'f';
		}
	}
}

//HD 235203: Colocar vários assuntos para a Suggar
if ($login_fabrica == 24) {
	if (strlen($tab_atual) == 0) {
		foreach($assuntos as $categoria_assunto => $itens_categoria_assunto) {
			foreach($itens_categoria_assunto as $label_assunto => $bd_assunto) {
				if ($bd_assunto == $categoria) {
					$callcenter_assunto = $categoria;

					switch($categoria_assunto) {
						case "PRODUTOS":
							$natureza_chamado = "reclamacao_produto";
						break;

						case "MANUAL":
							$natureza_chamado = "reclamacao_produto";
						break;

						case "EMPRESA":
							$natureza_chamado = "reclamacao_empresa";
						break;

						case "ASSISTÊNCIA TÉCNICA":
							$natureza_chamado = "reclamacao_at";
						break;

						case "REVENDA":
							$natureza_chamado = "onde_comprar";
						break;
					}
				}
			}
		}
	} else {
		$natureza_chamado = $tab_atual;
	}

	$tipo_atendimento = array(	1 => 'extensao',
								2 => 'reclamacao_produto',
								3 => 'reclamacao_empresa',
								4 => 'reclamacao_at',
								5 => 'duvida_produto',
								6 => 'sugestao',
								7 => 'assistencia',
								8 => 'garantia',
								9 => 'troca_produto',
								10 => 'procon' ,
								11 => 'onde_comprar',
								12 => 'ressarcimento',
								13 => 'outros_assuntos');
	$posicao = array_search($natureza_chamado, $tipo_atendimento); // $key = 2;

	if (!$posicao) {
		$posicao = 2;
	}
}

if (strlen($_GET['Id']) > 0) {//HD 94971
	$id_x = $_GET['Id'];
} else {
	$id_x = "";
}

if(strlen($callcenter)>0 OR strlen($id_x)>0){
	require_once '../helpdesk.inc.php';
}

include "cabecalho.php"; ?>

<style>

.input {
	font-size: 10px;
	font-family: verdana;
	BORDER-RIGHT: #666666 1px double;
	BORDER-TOP: #666666 1px double;
	BORDER-LEFT: #666666 1px double;
	BORDER-BOTTOM: #666666 1px double;
	BACKGROUND-COLOR: #ffffff
}

.respondido {
	font-size: 10px;
	color: #4D4D4D;
	font-family: verdana;
	BORDER-RIGHT: #666666 1px double;
	BORDER-TOP: #666666 1px double;
	BORDER-LEFT: #666666 1px double;
	BORDER-BOTTOM: #666666 1px double;
	BACKGROUND-COLOR: #ffffff;
}

.inicio{
	border:#485989 1px solid;
	background-color: #e6eef7;
	font-size:10px;
	font-family:verdana;
	text-align:center;
	margin: 0 auto;
	width:200px;
	padding-left: 2px;
	padding-right: 2px;
	padding-top: 2px;
	padding-bottom: 2px;
}

.tab_content{
	border:#485989 1px solid;
	font-size:10px;
	font-family:verdana;
	margin: 0 auto;
	float:center;
/*	width:680px;*/
	padding-left: 2px;
	padding-right: 2px;
	padding-top: 2px;
	padding-bottom: 2px;
}

.padding {
	padding-left: 150px;
}

.input_req {
	font-size: 10px;
	font-family: verdana;
	BORDER-RIGHT: #666666 1px double;
	BORDER-TOP: #666666 1px double;
	BORDER-LEFT: #666666 1px double;
	BORDER-BOTTOM: #666666 1px double;
	BACKGROUND-COLOR: #ffffff;
}

.input_req2 {
	font-size: 10px;
	font-family: verdana;
	BORDER-RIGHT: #666666 1px double;
	BORDER-TOP: #666666 1px double;
	BORDER-LEFT: #666666 1px double;
	BORDER-BOTTOM: #666666 1px double;
	BACKGROUND-COLOR: #ffffff;
}

.box {
	border-width: 1px;
	border-style: solid;
}
.box {
	display: block;
	margin: 0 auto;
	width: 100%;
}
.azul {
	border-color: #1937D9;
	background-color: #D9E2EF;
}
.label {
	width: 20%;
}
.border{
	text-align:left;
	font-weight:bold;
}
.dados {
	text-align:left;
}
body {
	text-align: center !important;
}

</style>

<!--=============== <FUNES> ================================!-->
<? include "javascript_pesquisas.php" ?>
<script language='javascript' src='ajax.js'></script>
<script language='javascript' src='js/bibliotecaAJAX.js'></script>
<script language='javascript' src='ajax_cep.js'></script><?php

include 'javascript_calendario.php'?>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script src="js/jquery.tabs.pack.js" type="text/javascript"></script>
<link rel="stylesheet" href="js/jquery.tabs.css" type="text/css" media="print, projection, screen">
<!-- Additional IE/Win specific style sheet (Conditional Comments) -->
<!--[if lte IE 7]>
<link rel="stylesheet" href="js/jquery.tabs-ie.css" type="text/css" media="projection, screen">
<![endif]-->

<script language="javascript">
function retiraAcentos1(obj) {
	re = /[^a-z^A-Z^0-9\s]/g;		//Expressão regular que localiza tudo que for diferente de caracteres a-Z ou 0-9 ou espaços
	obj.value = obj.value.replace(re, "");
}

//HD 201434 - Retirar acentos da digitação do nome do cliente
function retiraAcentos(obj) {
	com_acento = 'áàãâäéèêëíìîïóòõôöúùûüçÁÀÃÂÄÉÈÊËÍÌÎÏÓÒÕÖÔÚÙÛÜÇ';
	sem_acento = 'aaaaaeeeeiiiiooooouuuucAAAAAEEEEIIIIOOOOOUUUUC';

	resultado='';

	for(i=0; i<obj.value.length; i++) {  
		if (com_acento.search(obj.value.substr(i,1))>=0) {  
			resultado += sem_acento.substr(com_acento.search(obj.value.substr(i,1)),1);  
		}  
		else {  
			resultado += obj.value.substr(i,1);  
		}  
	}  

	obj.value = resultado;
}

</script>

<?
if ($login_fabrica == 52 and strlen($msg_erro)>0) {

	?>
		<script language='javascript'>

		$(document).ready(function() {
		var tipo = "<?echo $consumidor_cpf_cnpj;?>";
		if (tipo == 'C') {
		$('#cpf').attr('maxLength', 14);
		$('#cpf').attr('size', 18);
		$('#label_cpf').html('CPF:');
		$('#cpf').keypress (function(e){
			return txtBoxFormat(document.frm_callcenter, this.name, '999.999.999-99', e);
		});
		} else {
			if (tipo == 'R') {
				$('#consumidor_cnpj').attr('checked', true);
				$('#cpf').attr('maxLength', 18);
				$('#cpf').attr('size', 23);
				$('#label_cpf').html('CNPJ:');
				$('#cpf').keypress(function(e){
					return txtBoxFormat(document.frm_callcenter, this.name, '99.999.999/9999-99', e);
				});
			}
		}
	})
		</script>
<?php
}
?>


<script type="text/javascript">


function abrir_anexo(anexo){
	window.open ("callcenter_interativo_anexo.php?anexo="+anexo, "Anexo", "status = yes, scrollbars=yes");
	abrir() ;
}


function function1(linha2) {

	var linha = document.getElementById('qtde_produto').value;
	linha = parseInt(linha) + 1;
	if (!document.getElementById('item'+linha)) {
	var tbl = document.getElementById('tabela_itens');
		//var lastRow = tbl.rows.length;
		//var iteration = lastRow;

		//Atualiza a qtde de linhas
		$('#qtde_produto').val(linha);

	/*Criar TR - Linha*/
		var nova_linha = document.createElement('tr');
		nova_linha.setAttribute('rel', linha);

		/********************* COLUNA 1 ****************************/

		/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
		var el = document.createElement('label');
		el.innerHTML = '<strong>Série:</strong>';
		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';

		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'serie_' +linha);
		el.setAttribute('id', 'serie_' + linha);
		el.setAttribute('class','input');
		celula.appendChild(el);
		var el = document.createElement('img');
		el.setAttribute('src', 'imagens/lupa.png');
		el.setAttribute('border', '0');
		el.setAttribute('align', 'absmiddle');
		el.setAttribute('style', 'cursor: pointer');
		el.onclick = function(){
			var nome       = document.getElementById('produto_nome_'+linha);
			var produto    = document.getElementById('produto_referencia_'+linha);
			var mapa_linha = $('#mapa_linha'+linha);
			var serie      = document.getElementById('serie_'+linha);
			fnc_pesquisa_serie(produto,nome,'serie',mapa_linha,serie);
		}
		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/********************* COLUNA 2 ****************************/
		/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
		var el = document.createElement('label');
		el.innerHTML = '<strong>Referência:</strong>';
		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';

		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'produto_referencia_' +linha);
		el.setAttribute('id', 'produto_referencia_' + linha);
		el.setAttribute('size','15');
		el.setAttribute('class','input');
		celula.appendChild(el);
		var el = document.createElement('img');
		el.setAttribute('src', 'imagens/lupa.png');
		el.setAttribute('border', '0');
		el.setAttribute('align', 'absmiddle');
		el.setAttribute('style', 'cursor: pointer');
		el.onclick = function(){
			var nome       = document.getElementById('produto_nome_'+linha);
			var produto    = document.getElementById('produto_referencia_'+linha);
			var mapa_linha = $('#mapa_linha'+linha);
			fnc_pesquisa_produto2(produto,nome,'referencia',mapa_linha);
		}

		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/********************* COLUNA 3 ****************************/
		/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
		var el = document.createElement('label');
		el.innerHTML = '<strong>Descrição:</strong>';
		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/*Cria TD */
		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';

		var el = document.createElement('input');
		el.setAttribute('type', 'text');
		el.setAttribute('name', 'produto_nome_' +linha);
		el.setAttribute('id', 'produto_nome_' + linha);
		el.setAttribute('size','20');
		el.setAttribute('class','input');
		celula.appendChild(el);
		var el = document.createElement('img');
		el.setAttribute('src', 'imagens/lupa.png');
		el.setAttribute('border', '0');
		el.setAttribute('align', 'absmiddle');
		el.setAttribute('style', 'cursor: pointer');
		el.onclick = function(){
			var nome       = document.getElementById('produto_nome_'+linha);
			var produto    = document.getElementById('produto_referencia_'+linha);
			var mapa_linha = $('#mapa_linha'+linha);
			fnc_pesquisa_produto2(produto,nome,'descricao',mapa_linha);
		}
		celula.appendChild(el);
		nova_linha.appendChild(celula);

		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
		var el = document.createElement('label');
		el.innerHTML = '<strong>Defeito Reclamado  </strong>';
		celula.appendChild(el);
		nova_linha.appendChild(celula);

		var celula = criaCelula('');
		celula.style.cssText = 'text-align: left;';
		var teste_array = '<?	if ($login_fabrica == 52 ) { $sql = "SELECT distinct tbl_defeito_reclamado.descricao, tbl_defeito_reclamado.defeito_reclamado FROM tbl_diagnostico JOIN tbl_defeito_reclamado on tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado JOIN tbl_produto on tbl_diagnostico.linha = tbl_produto.linha and tbl_diagnostico.familia = tbl_produto.familia WHERE tbl_diagnostico.fabrica = $login_fabrica AND tbl_diagnostico.ativo is true"; $res1 = pg_query ($con,$sql); if (pg_num_rows($res1) > 0) { for ($x = 0 ; $x < pg_num_rows($res1) ; $x++){$defeito_reclamado = trim(pg_fetch_result($res1,$x,defeito_reclamado)); $descricao  = trim(pg_fetch_result($res1,$x,descricao)); $descricao = substr($descricao,0,30); echo $defeito_reclamado;echo'/';echo $descricao;echo '|'; }	 }	}?>';

		teste_array = teste_array.split('|');
		var qtd = teste_array.length;
		var el = document.createElement("select");
		el.setAttribute('name', 'defeito_reclamado_' + linha);
		el.setAttribute('id', 'defeito_reclamado_' + linha);
		el.setAttribute('class','input');
		elop=document.createElement("OPTION");
		elop.setAttribute('value','');
		texto1=document.createTextNode(" ");
		elop.appendChild(texto1);
		el.appendChild(elop);

		for ($i=0;$i<qtd;$i++) {
			var array = teste_array[$i].split('/');
			var codigo = array[0];
			var nome = array[1];

			if (codigo != '') {
				elop=document.createElement("OPTION");
				elop.setAttribute('value',codigo);
				texto1=document.createTextNode(nome);
				elop.appendChild(texto1);
				el.appendChild(elop);
			}
		}

		celula.appendChild(el);
		nova_linha.appendChild(celula);

		/************ FINALIZA LINHA DA TABELA ***********/
		var tbody = document.createElement('TBODY');
		tbody.appendChild(nova_linha);
		tbl.appendChild(tbody);

	}
}

function criaCelula(texto) {
	var celula = document.createElement('td');
	var textoNode = document.createTextNode(texto);
	celula.appendChild(textoNode);
	return celula;
}


$(function() {
	// !129655 - Carregar dúvidas já cadastradas para este chamado [augusto]
	// !133157 - Carregar dúvidas quando o usuário não selecionou a aba dúvida, ou seja, a dúvida de produto [augusto]
	<?php if ( isset($callcenter) && ! empty($callcenter) && $login_fabrica <> 52 ): ?>
			localizarFaq(document.frm_callcenter.produto_referencia.value,'faq_duvida_duvida','carregar');
			localizarFaq(document.frm_callcenter.produto_referencia.value,'faq_duvida_produto','carregar');
	<?php endif; ?>
});

//inutilizado por waldir em 11/10/2009 vereficar com o augusto o porque do erro, mas este ready estava dando erro na busca por nome ao clicar na lupa no IE 8.0
/*
$(document).ready(function() {
	$('#label_nome').click( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'consumidor'); } );
	$('#label_cnpj').click( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'consumidor'); } );
});
*/
<?
if($login_fabrica==25 OR $login_fabrica==59){
	$w=1;
}else if($login_fabrica == 45){
	$w=1;
	$posicao = $posicao-1;
}else if($login_fabrica == 46 ){
	$w=1;
	$posicao = $posicao-1;
}else if ($login_fabrica == 2 OR $login_fabrica == 11){
	$w=1;
	$posicao = $posicao;
}else{
	$w=1;
	if($posicao>=10) $posicao = $posicao-4;
	else             $posicao = $posicao-1;
}

?>
	$().ready(function() {
		<?
		if ($login_fabrica == 24) {
		?>
			$('#container-Principal').tabs( <? echo "$posicao,"; ?>{fxSpeed: 'fast'} );
		<?
		}
		else { 
		?>
			$('#container-Principal').tabs( <? if(strlen($callcenter)>0){ echo "$posicao,"; }?>{fxSpeed: 'fast'} );
		<?
		}
		?>
	<? if(strlen($callcenter)>0){for($x=$w;$x<12;$x++){
		if($x<>$posicao) {?>
		<? if (strlen($protocolo)==0) { ?>
			$('#container-Principal').disableTab(<?echo $x;?>);
	<? }
		} }}?>
//		$('#container').disableTab(3);
		//fxAutoHeight: true,
		$("#consumidor_cpf").maskedinput("999.999.999-99");
		$("#consumidor_fone").maskedinput("(999) 9999-9999");
		$("#consumidor_cep").maskedinput("99999-999");
		$("#hora_ligacao").maskedinput("99:99");
		$("input[@rel='data']").maskedinput("99/99/9999");
		$("#data_abertura").maskedinput("99/99/9999");
		$("#expedicao").maskedinput("99/99/9999");
// 		$('#cpf').keypress();

	});


function formatItem(row) {
	return row[1] + " - " + row[2];
}

function formatItemNomeRevenda(row) {
	return row[1] + " - " + row[2] + " - " + row[3];
}

function formatItemPosto(row) {
	return row[2] + " - " + row[3] + " (Fantasia:" + row[4] + ")";
}


function formatCliente(row) {
	return row[2] + " - " + row[3] + " - Cidade: " + row[4];
}

function formatLocalizar(row) {
	var data = "";
	var nota_fiscal = "";
	var serie = "";
	var cep = "";

	if (row[0] == "erro") {
		return "Erro: " + row[1];
	}

	switch(row[24]) {
		case "O":
			sua_os = row[16];
			atendimento = row[22];
		break;

		case "C":
			sua_os = row[16];
			atendimento = row[0];
		break;

		case "R":
			sua_os = 0;
			atendimento = 0;
		break;

		case "A":
			sua_os = 0;
			atendimento = 0;
		break;

		default:
			sua_os = 0;
			atendimento = 0;
	}

	if (atendimento) {
		atendimento = "Chamado: " + atendimento;
	}
	else {
		atendimento = "<font style='font-size: 7pt; font-weight: bold; background-color: #CC5555; color: #FFFFFF;'>SEM ATENDIMENTO</font>";
	}

	if (sua_os) {
		sua_os = "OS: " + sua_os;
	}
	else {
		sua_os = "<font style='font-size: 7pt; font-weight: bold; background-color: #CC5555; color: #FFFFFF;'>SEM OS</font>";
	}
	
	if (row[18]) {
		nota_fiscal = " Nota Fiscal: " + row[18];
	}
	
	if (row[17]) {
		serie = " Série: " + row[17];
	}
	
	if (row[6]) {
		cep = " Cep: " + row[6];
	}

	return atendimento + " - Cliente: " + row[1] + " - " + sua_os + nota_fiscal + serie + cep;
}
/**
 * Adiciona o evento do autocomplete para o campo "Cliente Fricon"
 * Esta função existe porque é necessário executar ela depois de algumas requisições AJAX.
 *
 * @param Object reference É a referencia da onde o INPUT do autocomplete deve ser buscado, se nenhum for informado, busca no BODY inteiro
 */
function bindEventClienteNomeAdmin(reference) {
	if ( reference == undefined ) {
		reference = $('BODY').get(0);
	}
	$("#cliente_nome_admin",reference).autocomplete("<?php echo $PHP_SELF.'?tipo_busca=cliente_admin&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		max: 30,
		matchContains: true,
		formatItem: formatCliente,
		formatResult: function(row) {
		return row[3];
		}
	});
}

$().ready(function() {
	/* Busca pelo Cdigo */
	$("#revenda_cnpj").autocomplete("<?echo $PHP_SELF.'?tipo_busca=revenda&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {
			return row[1];
		}
	});

	$("#revenda_cnpj").result(function(event, data, formatted) {
		$("#revenda").val(data[0]) ;
		$("#revenda_cnpj").val(data[1]) ;
		$("#revenda_nome").val(data[2]) ;
	});
	
	/* Busca pelo CNPJ da Revenda no quadro Informações do Produto */
	$("#cnpj_revenda").autocomplete("<?echo $PHP_SELF.'?tipo_busca=revenda_geral&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {
			return row[1];
		}
	});

	$("#cnpj_revenda").result(function(event, data, formatted) {
		$("#revenda").val(data[0]) ;
		$("#cnpj_revenda").val(data[1]) ;
		$("#nome_revenda").val(data[2]) ;
	});

	/* Busca pelo Nome */
	$("#revenda_nome").autocomplete("<?echo $PHP_SELF.'?tipo_busca=revenda&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {
			return row[2];
		}
	});

	$("#revenda_nome").result(function(event, data, formatted) {
		$("#revenda").val(data[0]) ;
		$("#revenda_cnpj").val(data[1]) ;
		$("#revenda_nome").val(data[2]) ;
		//alert(data[2]);
	});

	/* Busca pelo Nome da Revenda no quadro Informações do Produto */
	$("#nome_revenda").autocomplete("<?echo $PHP_SELF.'?tipo_busca=revenda_geral&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItemNomeRevenda,
		formatResult: function(row) {
			return row[2];
		}
	});

	$("#nome_revenda").result(function(event, data, formatted) {
		$("#cnpj_revenda").val(data[1]) ;
		$("#nome_revenda").val(data[2]) ;
		//alert(data[2]);
	});

	$("#revenda_nome_os").autocomplete("<?echo $PHP_SELF.'?tipo_busca=revenda_os&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {
			return row[2];
		}
	});

	$("#revenda_nome_os").result(function(event, data, formatted) {
		$("#revenda_os").val(data[0]) ;
	});


	$("#mapa_cidade").autocomplete("<?echo $PHP_SELF.'?tipo_busca=mapa_cidade&busca=mapa_cidade'; ?>", {
		minChars: 3,
		delay: 150,
		width: 205,
		matchContains: true,
		formatItem: function(row) {
			return row[0];
		},
		formatResult: function(row) {
			return row[0];
		}
	});

	/* Busca pelo Código */
	$("#codigo_posto_tab").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItemPosto,
		formatResult: function(row) {
			return row[2];
		}
	});

	$("#codigo_posto_tab").result(function(event, data, formatted) {
		$("#posto_tab").val(data[0]) ;
		$("#codigo_posto_tab").val(data[2]) ;
		$("#posto_nome_tab").val(data[3]) ;
		$("#posto_fone_tab").val(data[5]) ;
		$("#posto_email_tab").val(data[6]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome_tab").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItemPosto,
		formatResult: function(row) {
			return row[3];
		}
	});

	$("#posto_nome_tab").result(function(event, data, formatted) {
		$("#posto_tab").val(data[0]) ;
		$("#codigo_posto_tab").val(data[2]) ;
		$("#posto_nome_tab").val(data[3]) ;
		$("#posto_fone_tab").val(data[5]) ;
		$("#posto_email_tab").val(data[6]) ;
		//alert(data[2]);
	});

	/* ! Busca pelo Nome do cliente  --------- */
	bindEventClienteNomeAdmin();
	/* Busca pelo Nome do cliente  FIM ----- */

	$("#cliente_nome_admin").result(function(event, data, formatted) {
	$("#cliente_admin").val(data[0]) ;
	$("#cliente_nome_admin").val(data[3]) ;
	});

	hora = new Date();
	engana = hora.getTime()
	
	//HD 201434 - Campo Localizar desmembrado em vários, linhas do autocomplete do antigo campo excluídas
});

function preencheConsumidorAutocomplete(event, data, formatted) {
	var formulario = document.frm_callcenter;
	var os = document.getElementById('os')

	formulario.consumidor_nome.value = data[1];
	formulario.consumidor_cpf.value = data[11];
	formulario.consumidor_rg.value = data[12];
	formulario.consumidor_email.value = data[13];
	formulario.consumidor_fone.value = data[8];
	formulario.consumidor_cep.value = data[6];
	formulario.consumidor_endereco.value = data[2];
	formulario.consumidor_numero.value = data[3];
	formulario.consumidor_complemento.value = data[4];
	formulario.consumidor_bairro.value = data[5];
	formulario.consumidor_cidade.value = data[14];
	formulario.consumidor_estado.value = data[15];
	os.value = data[16];
	formulario.consumidor_fone2.value = data[9];
	formulario.consumidor_fone3.value =	 data[10];

	if ((typeof formulario.consumidor_cpf != "undefined") && (typeof formulario.consumidor_cnpj != "undefined")) {
		if (data[11].length > 11) {
			formulario.consumidor_cnpj.checked = true;
		}
		else {
			formulario.consumidor_cpf.checked = true;
		}
	}

	if (typeof formulario.consumidor_revenda_c != "undefined") {
		switch (data[38]) {
			case "O":
				formulario.consumidor_revenda_c.checked = true;
			break;

			case "C":
				formulario.consumidor_revenda_c.checked = true;
			break

			case "R":
				formulario.consumidor_revenda_r.checked = true;
			break

			case "A":
				formulario.consumidor_revenda_a.checked = true;
			break
		}
	}
}


function verificarImpressao(check){
	if (check.checked){
		$('#imprimir_os').show();
	}else{
		$('#imprimir_os').hide();
	}
}

function fnc_pesquisa_serie (campo, campo2, tipo, mapa_linha,campo3) {
	if (tipo == "serie" ) {
		var xcampo = campo3;
	}
	if (xcampo.value != "") {
		var url = "";
		url = "produto_serie_pesquisa_new.php?campo=" + xcampo.value + "&tipo=" + tipo + "&mapa_linha=t&voltagem=t";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.serie   = campo3;
		janela.referencia   = campo;
		janela.descricao    = campo2;
		janela.mapa_linha   = mapa_linha;
		janela.voltagem     = document.frm_callcenter.voltagem;
		janela.focus();
	}else{
		alert( 'Favor inserir toda ou parte da informação para realizar a pesquisa' );
		return false;

	}
}

var janela = null;
var janela_descricao = null;

function fnc_pesquisa_produto2 (campo, campo2, tipo, mapa_linha) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
		//alert(xcampo.value);
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&mapa_linha=t&voltagem=t";

		if (janela != null && !janela.closed) {
			janela.focus();
		}
		else if (janela != null && janela.closed) {
			janela = null;
		}

		if (janela == null) {
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
			janela = window.janela;
			janela.referencia   = campo;
			janela.descricao    = campo2;
			janela.mapa_linha   = mapa_linha;
			janela.voltagem     = document.frm_callcenter.voltagem;
			janela.focus();
		}
	}
}



function MudaCampo(campo){
	if (campo.value == 'reclamacao_at') {
		document.getElementById('info_posto').style.display='inline';
	}else{
		document.getElementById('info_posto').style.display='none';
	}
}

function enviaEmail(callcenter){
	url = "envio_email_callcenter.php?callcenter=" + callcenter;
	janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=700, height=500, top=18, left=0");
}

function validaOsRessarcimento() {
	
	var os = $('#os').val();

	if (os.length == 0) {
		alert('Para fazer o ressarcimento é necessario escolher a Ordem de serviço no cabeçalho do programa');		
	} else {
		$('#os_ressarcimento').val(os);
	}
}


</script>

<script type="text/javascript" src="js/thickbox.js"></script>

<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script language="JavaScript">
function atualizaMapa(){
	var cidade = $('#consumidor_cidade').val();
	var estado = $('#consumidor_estado').val();
	$('#link').attr('href','callcenter_interativo_posto.php?fabrica=12<?echo $login_fabrica;?>&cidade='+cidade+'&estado='+estado+'&keepThis=trueTB_iframe=true&height=400&width=700')
	$('#link2').attr('href','callcenter_interativo_posto.php?fabrica=12<?echo $login_fabrica;?>&cidade='+cidade+'&estado='+estado+'&keepThis=trueTB_iframe=true&height=400&width=700')
}
function minimizar(arquivo){
	if (document.getElementById(arquivo)){
		var style2 = document.getElementById(arquivo);
		if (style2==false) return;
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
		}
	}
}

function formata_data(valor_campo, form, campo){
	var mydata = '';
	mydata = mydata + valor_campo;
	myrecord = campo;
	myform = form;

	if (mydata.length == 2){
		mydata = mydata + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mydata;
	}
	if (mydata.length == 5){
		mydata = mydata + '/';
		window.document.forms["" + myform + ""].elements[myrecord].value = mydata;
	}

}

//HD 163220 - Alterada a função para chamar pesquisa de todos os tipos pelo popup
/*************************************************************************************/
/*************************** Função PESQUISA DE CONSUMIDOR ***************************/
/*************************************************************************************/
function fnc_pesquisa_consumidor_callcenter(valor, tipo_pesquisa, busca_exata) {
	var url = "pesquisa_consumidor_callcenter_new.php?localizar=" + valor + "&tipo=" + tipo_pesquisa;

	if (typeof busca_exata == "undefined") {
		busca_exata = false;
	}

	if (busca_exata) {
		url += "&exata=sim";
	}

	if (valor.length > 5) {
		janela = window.open(url,"callcenter_interativo_new_pesquisa_consumidor","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=780,height=400,top=18,left=0");
		janela.formulario = document.frm_callcenter;
		janela.os = document.getElementById('os_ressarcimento');
		janela.focus();
	}
	else {
		alert("Digite pelo menos 6 caracteres para efetuar a busca");
	}
}

function fnc_pesquisa_revenda (campo, tipo,cidade) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda_callcenter.php?nome=" + campo.value + "&tipo=nome";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda_callcenter.php?cnpj=" + campo.value + "&tipo=cnpj";
	}
	if (tipo == "cidade") {
		url = "pesquisa_revenda_callcenter.php?cidade=" + campo.value + "&tipo=cidade";
	}
	if (tipo == "familia") {
		url = "pesquisa_revenda_callcenter.php?familia=" + campo.value + "&tipo=familia&consumidor_cidade=" + cidade.value;

	}

	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.nome         = document.frm_callcenter.revenda_nome;
	janela.endereco     = document.frm_callcenter.revenda_endereco;
	janela.numero       = document.frm_callcenter.revenda_nro;
	janela.complemento  = document.frm_callcenter.revenda_cmpto;
	janela.bairro       = document.frm_callcenter.revenda_bairro;
	janela.cidade       = document.frm_callcenter.revenda_city;
	janela.estado       = document.frm_callcenter.revenda_uf;
	janela.fone         = document.frm_callcenter.revenda_fone;
	janela.revenda      = document.frm_callcenter.revenda;

	janela.focus();
}

function zxxx (campo) {

	url = "pesquisa_os_callcenter.php?sua_os=" + campo;
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
	janela.sua_os			= document.frm_callcenter.sua_os;
	janela.data_abertura	= document.frm_callcenter.data_abertura;
	janela.data_nf	        = document.frm_callcenter.data_nf;
	janela.serie	        = document.frm_callcenter.serie;
	janela.nota_fiscal	    = document.frm_callcenter.nota_fiscal;
	janela.produto	        = document.frm_callcenter.produto;
	janela.produto_nome	    = document.frm_callcenter.produto_nome;
	janela.revenda_nome	    = document.frm_callcenter.revenda_nome;
	janela.revenda	        = document.frm_callcenter.revenda;
	//janela.posto        	= document.frm_callcenter.posto;
	janela.posto_nome     	= document.frm_callcenter.posto_nome;

	janela.focus();

}

/* ============= Função PESQUISA DE POSTO POR MAPA ====================
Nome da Função : fnc_pesquisa_at_proximo()
=================================================================*/
function fnc_pesquisa_at_proximo(fabrica) {
	url = "callcenter_interativo_posto.php?fabrica=12"+fabrica;
	janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=750,height=500,top=18,left=0");
	janela.posto_tab = document.frm_callcenter.posto_tab;
	janela.codigo_posto_tab = document.frm_callcenter.codigo_posto_tab;
	janela.posto_nome_tab = document.frm_callcenter.posto_nome_tab;
	janela.posto_cidade_tab = document.frm_callcenter.posto_cidade_tab;
	janela.posto_estado_tab= document.frm_callcenter.posto_estado_tab;
	janela.posto_endereco_tab = document.frm_callcenter.posto_endereco_tab;
	janela.posto_km_tab = document.frm_callcenter.posto_km_tab;
	janela.posto_fone_tab = document.frm_callcenter.posto_fone_tab;
	janela.posto_email_tab = document.frm_callcenter.posto_email_tab;
	janela.abas = $('#container-Principal');
	janela.focus();
}

/* ========== Função AJUSTA CAMPO DE DATAS =========================
Nome da Função : ajustar_data (input, evento)
		Ajusta a formatao da Máscara de DATAS a medida que ocorre
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

function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}

var http1 = new Array();
function mostraDefeitos(natureza,produto){
	var curDateTime = new Date();
	http1[curDateTime] = createRequestObject();
	url = "callcenter_interativo_defeitos.php?ajax=true&natureza="+ natureza +"&produto=" + produto;
	http1[curDateTime].open('get',url);

	var campo = document.getElementById('div_defeitos');
	http1[curDateTime].onreadystatechange = function(){
		if(http1[curDateTime].readyState == 1) {
			campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http1[curDateTime].readyState == 4){
			if (http1[curDateTime].status == 200 || http1[curDateTime].status == 304){
				var results = http1[curDateTime].responseText;
				campo.innerHTML   = results;
			}else {
				campo.innerHTML = "Erro";

			}
		}
	}
	http1[curDateTime].send(null);

}

var http2 = new Array();
function localizarFaq(produto,local,action){
	var action          = ( action == undefined ) ? 'pesquisa' : action ;
	var faq_duvida      = (action == 'pesquisa') ? document.getElementById(local).value : '    ';
	var campo           = '#div_'+local;
	var hd_chamado      = '<?php echo $callcenter; ?>';
	var buscar_marcados = ( action == 'pesquisa' ) ? '0' : '1' ;
	if ( hd_chamado.length <= 0 ) {
		hd_chamado = 0;
	}
	if(produto.length==0 && action == 'pesquisa'){
		alert('Por favor selecione o produto');
		return 0;
	} else if ( produto.length==0 ) {
		return 0;
	}

	if(faq_duvida.length==0){
		alert('Por favor inserir a dúvida');
		return 0;
	}

	//url    = "callcenter_interativo_ajax.php";
	//params = {'ajax':'true', 'faq_duvida':'true','produto':produto,'duvida':faq_duvida,'hd_chamado':hd_chamado,'buscar_marcados':buscar_marcados };
	url    = "callcenter_interativo_ajax.php?ajax=true&faq_duvida=true&produto="+produto+"&faq_duvida="+faq_duvida+"&hd_chamado="+hd_chamado+"&buscar_marcados="+buscar_marcados;
	params = '';
	$(campo).empty();
	$.get(url,params,function(resposta) {
		$(campo).html(resposta);
		$('.chk_faq').click(function() {
			$('.chk_faq').parent().css('background','none').css('cursor','pointer')
					     .end().filter(':checked').parent().css('background','#BCCACD');
		});
	},'html');
}
var http3 = new Array();
function localizarConsumidor(busca,tipo){
	if (tipo=='novo'){
		$('#tabela_consumidor input').each( function(){
			$(this).val('');
		});
		$('#consumidor_nome').focus();
		return false;
	}
	var campo = document.getElementById('div_consumidor');
	$(campo).empty();
	var busca = document.getElementById(busca).value;
	var curDateTime = new Date();
	http3[curDateTime] = createRequestObject();

	url = "callcenter_interativo_ajax.php?ajax=true&busca_cliente=tue&busca=" + busca + "&tipo=" + tipo;
	http3[curDateTime].open('get',url);

	http3[curDateTime].onreadystatechange = function(){
		if(http3[curDateTime].readyState == 1) {
			campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http3[curDateTime].readyState == 4){
			if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
				var results = http3[curDateTime].responseText;
				campo.innerHTML   = results;
				bindEventClienteNomeAdmin(campo);
			}else {
				campo.innerHTML = "Erro";
			}
		}

		$("#consumidor_fone").maskedinput("(999) 9999-9999");
		$("#consumidor_cep").maskedinput("99999-999");
		$("#hora_ligacao").maskedinput("99:99");
	}
	http3[curDateTime].send(null);
}

function mostraEsconde(){
	$("div[@rel=div_ajuda]").toggle();
}
var http4 = new Array();
function fn_verifica_garantia(){
	var produto_nome       = document.getElementById('produto_nome_es').value;
	var produto_referencia = document.getElementById('produto_referencia_es').value;
	var serie              = document.getElementById('serie_es').value;
	 var campo = document.getElementById('div_estendida');
	var curDateTime = new Date();
	http4[curDateTime] = createRequestObject();

	url = "callcenter_interativo_ajax.php?ajax=true&garantia=tue&produto_nome=" + produto_nome + "&produto_referencia=" + produto_referencia+"&serie="+serie+"&data="+curDateTime;
	http4[curDateTime].open('get',url);

	http4[curDateTime].onreadystatechange = function(){
		if(http4[curDateTime].readyState == 1) {
			campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http4[curDateTime].readyState == 4){
			if (http4[curDateTime].status == 200 || http4[curDateTime].status == 304){
				var results = http4[curDateTime].responseText;
				campo.innerHTML   = results;
			}else {
				campo.innerHTML = "Erro";
			}
		}
		$("#es_data_compra").maskedinput("99/99/9999");
		$("#es_data_nascimento").maskedinput("99/99/9999");
		$("#es_fonecomercial").maskedinput("(99) 9999-9999");
		$("#es_celular").maskedinput("(99) 9999-9999");
	}
	http4[curDateTime].send(null);
}

/*  Tirei o bairro e aumentei o tamanhodo pop-up    */
function mapa_rede(linha,estado,cidade,cep,endereco,numero,bairro,consumidor_cidade,consumidor_estado){
	//HD 289158: Retirado o bairro. Não acrescentar o bairro na variável consumidor, pois confunde o Google
	url = "mapa_rede.php?callcenter=true&pais=BR&estado="+estado.value+"&linha="+linha.value+"&cidade="+cidade.value+"&cep="+cep.value+"&consumidor="+endereco.value+","+numero.value+","+consumidor_cidade.value+" - "+consumidor_estado.value;
	janela = window.open(url,"janela","width=800,height=480,scrollbars=yes,resizable=yes");
	janela.posto_tab        = document.frm_callcenter.posto_tab;
	janela.codigo_posto_tab = document.frm_callcenter.codigo_posto_tab;
	janela.posto_nome_tab   = document.frm_callcenter.posto_nome_tab;
	janela.posto_email_tab  = document.frm_callcenter.posto_email_tab;
	janela.posto_fone_tab   = document.frm_callcenter.posto_fone_tab;
	janela.posto_km_tab     = document.frm_callcenter.posto_km_tab;

}

function fnc_pesquisa_os (campo, tipo) {
	var url = "";
	if (tipo == "os") {
		url = "pesquisa_os_callcenter.php?consumidor_cpf=" + campo.value + "&tipo=os";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor_callcenter.php?consumidor_cpf=" + campo.value + "&tipo=cpf";
	}

	if (tipo == "nota_fiscal") {
		url = "pesquisa_os_callcenter.php?nota_fiscal=" + campo.value + "&tipo=nota_fiscal";
	}
	if (campo.value != "") {
		janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0,resizable=yes");

		janela.produto_referencia      = document.frm_callcenter.produto_referencia;
		janela.produto_nome            = document.frm_callcenter.produto_nome;
		janela.produto_serie           = document.frm_callcenter.serie;
		janela.produto_nf              = document.frm_callcenter.nota_fiscal;
		janela.produto_nf_data         = document.frm_callcenter.data_nf;
		janela.sua_os                  = document.frm_callcenter.os;
		janela.posto_nome              = document.frm_callcenter.posto_nome;
		janela.posto_codigo            = document.frm_callcenter.codigo_posto;
		<? if($login_fabrica==11) { //HD 14549 ?>
			janela.consumidor_nome         = document.frm_callcenter.consumidor_nome;
			janela.consumidor_cpf          = document.frm_callcenter.consumidor_cpf;
			janela.consumidor_cep          = document.frm_callcenter.consumidor_cep;
			janela.consumidor_fone         = document.frm_callcenter.consumidor_fone;
			janela.consumidor_endereco     = document.frm_callcenter.consumidor_endereco;
			janela.consumidor_numero       = document.frm_callcenter.consumidor_numero;
			janela.consumidor_complemento  = document.frm_callcenter.consumidor_complemento;
			janela.consumidor_bairro       = document.frm_callcenter.consumidor_bairro;
			janela.consumidor_cidade       = document.frm_callcenter.consumidor_cidade;
			janela.consumidor_estado       = document.frm_callcenter.consumidor_estado;
			janela.abas = $('#container-Principal');
		<? } ?>
		janela.focus();
	}
}

function atualizaQuadroMapas(){

	/* Atualiza os dados do posto conforme cidade e estado do Consumidor */

	var estado_selecionado = $('#consumidor_estado').val();

	/* Centro Oeste */
	if (estado_selecionado == 'GO' || estado_selecionado == 'MT' || estado_selecionado == 'MS' || estado_selecionado == 'DF'){
		estado_selecionado = 'BR-CO';
	}

	/* Nordeste */
	if (estado_selecionado == 'AL' || estado_selecionado == 'BA' || estado_selecionado == 'CE' || estado_selecionado == 'MA' || estado_selecionado == 'PB' || estado_selecionado == 'PE' || estado_selecionado == 'PI' || estado_selecionado == 'RN' || estado_selecionado == 'SE'){
		estado_selecionado = 'BR-NE';
	}

	/* Norte */
	if (estado_selecionado == 'AC' || estado_selecionado == 'AP' || estado_selecionado == 'AM' || estado_selecionado == 'PA' || estado_selecionado == 'RR' || estado_selecionado == 'RO' || estado_selecionado == 'TO'){
		estado_selecionado = 'BR-N';
	}

	$('#mapa_cidade').val( $('#consumidor_cidade').val() );
	$('#mapa_estado').val( estado_selecionado );
}


function txtBoxFormat(objForm, strField, sMask, evtKeyPress) {
	var i, nCount, sValue, fldLen, mskLen,bolMask, sCod, nTecla;

	if(document.all) { // Internet Explorer
		nTecla = evtKeyPress.keyCode;
	} else if(document.layers) { // Nestcape
		nTecla = evtKeyPress.which;
	} else {
		nTecla = evtKeyPress.which;
		if (nTecla == 8) {
			return true;
		}
	}

	sValue = objForm[strField].value;

	sValue = sValue.toString().replace( "-", "" );
	sValue = sValue.toString().replace( "-", "" );
	sValue = sValue.toString().replace( ".", "" );
	sValue = sValue.toString().replace( ".", "" );
	sValue = sValue.toString().replace( "/", "" );
	sValue = sValue.toString().replace( "/", "" );
	sValue = sValue.toString().replace( "/", "" );
	sValue = sValue.toString().replace( "(", "" );
	sValue = sValue.toString().replace( "(", "" );
	sValue = sValue.toString().replace( ")", "" );
	sValue = sValue.toString().replace( ")", "" );
	sValue = sValue.toString().replace( " ", "" );
	sValue = sValue.toString().replace( " ", "" );
	fldLen = sValue.length;
	mskLen = sMask.length;

	i = 0;
	nCount = 0;
	sCod = "";
	mskLen = fldLen;

	while (i <= mskLen) {
	bolMask = ((sMask.charAt(i) == "-") || (sMask.charAt(i) == ":") || (sMask.charAt(i) == ".") || (sMask.charAt(i) == "/"))
	bolMask = bolMask || ((sMask.charAt(i) == "(") || (sMask.charAt(i) == ")") || (sMask.charAt(i) == " ") || (sMask.charAt(i) == "."))


	if (bolMask) {
		sCod += sMask.charAt(i);
		mskLen++;

	} else {
		sCod += sValue.charAt(nCount);
		nCount++;
	}
	i++;
	}

	objForm[strField].value = sCod;
	if (nTecla != 8) { // backspace
		if (sMask.charAt(i-1) == "9") { // apenas números...
		return ((nTecla > 47) && (nTecla < 58)); } // números de 0 a 9
	else { // qualquer caracter...
		return true;
	}
	} else {
		return true;
	}
}


<?PHP if ($login_fabrica == 3) { ?>
	window.onload = function foco(){
		var campo = document.getElementById("consumidor_nome");
		campo.focus();
	}
<? } ?>

<?PHP if ($login_fabrica == 2 || $login_fabrica == 10 || $login_fabrica == 91) { ?>
function fnc_tipo_atendimento(tipo) {
		$('#cpf').val('');
		$('#label_nome').show();
	if (tipo.value == 'C') {
		$('#cpf').attr('maxLength', 14);
		$('#cpf').attr('size', 18);
		$('#label_cpf').html('CPF:');
		$('#label_nome').unbind(function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'assistencia'); } );
		$('#label_cnpj').unbind( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'assistencia'); } );
		$('#label_nome').unbind(function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'revenda'); } );
		$('#label_cnpj').unbind( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'revenda'); } );
		$('#label_nome').click( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'consumidor'); } );
		$('#label_cnpj').click( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'consumidor'); } );
		$('#cpf').keypress (function(e){
			return txtBoxFormat(document.frm_callcenter, this.name, '999.999.999-99', e);
		});
	} else {
		if (tipo.value == 'R' || tipo.value=='E') {
			$('#cpf').attr('maxLength', 18);
			$('#cpf').attr('size', 23);
			$('#label_cpf').html('CNPJ:');
			$('#label_nome').unbind(function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'consumidor'); } );
			$('#label_cnpj').unbind( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'consumidor'); } );
			$('#label_nome').unbind(function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'assistencia'); } );
			$('#label_cnpj').unbind( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'assistencia'); } );
			$('#label_nome').click( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'revenda'); } );
			$('#label_cnpj').click( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'revenda'); } );
			$('#cpf').keypress(function(e){
				return txtBoxFormat(document.frm_callcenter, this.name, '99.999.999/9999-99', e);
			});
		} else {
			if (tipo.value == "F") {
				$('#label_nome').hide();
			}
			$('#cpf').attr('maxLength', 18);
			$('#cpf').attr('size', 23);
			$('#label_cpf').html('CNPJ:');
			$('#label_nome').unbind(function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'consumidor'); } );
			$('#label_cnpj').unbind( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'consumidor'); } );
			$('#label_nome').unbind(function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'revenda'); } );
			$('#label_cnpj').unbind( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'revenda'); } );
			$('#label_nome').click( function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_nome, 'nome', 'assistencia'); } );
			$('#label_cnpj').click(function() { javascript: fnc_pesquisa_consumidor_callcenter (document.frm_callcenter.consumidor_cpf, 'cpf', 'assistencia'); } );

			$('#cpf').keypress(function(e){
				return txtBoxFormat(document.frm_callcenter, this.name, '99.999.999/9999-99', e);
			});
		}
	}
}

<? }elseif($login_fabrica == 59 or $login_fabrica == 24 or $login_fabrica == 30  || $login_fabrica == 52){ // HD 75777 ?>
function fnc_tipo_atendimento(tipo) {
		$('#cpf').val('');
	if (tipo.value == 'C') {
		$('#cpf').attr('maxLength', 14);
		$('#cpf').attr('size', 18);
		$('#label_cpf').html('CPF:');
		$('#cpf').keypress (function(e){
			return txtBoxFormat(document.frm_callcenter, this.name, '999.999.999-99', e);
		});
	} else {
		if (tipo.value == 'R') {
			$('#cpf').attr('maxLength', 18);
			$('#cpf').attr('size', 23);
			$('#label_cpf').html('CNPJ:');
			$('#cpf').keypress(function(e){
				return txtBoxFormat(document.frm_callcenter, this.name, '99.999.999/9999-99', e);
			});
		}
	}
}
<?}?>

var http5 = new Array()
function listaFaq(produto){
	var campo = document.getElementById('div_faq_duvida_duvida');
	if(produto.length==0){
		alert('Por favor selecione o produto');
	}else{
		var curDateTime = new Date();
		http5[curDateTime] = createRequestObject();

		url = "callcenter_interativo_ajax.php?ajax=true&listar=sim&produto=" + produto;
		http5[curDateTime].open('get',url);

		http5[curDateTime].onreadystatechange = function(){
			if(http5[curDateTime].readyState == 1) {
				campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
			}
			if (http5[curDateTime].readyState == 4){
				if (http5[curDateTime].status == 200 || http5[curDateTime].status == 304){
					var results = http5[curDateTime].responseText;
					campo.innerHTML   = results;
				}else {
					campo.innerHTML = "Erro";

				}
			}
		}
		http5[curDateTime].send(null);
	}
}

function indicacao(check){
	if (check.checked){
		$('.input_req').val('Indicação de Posto');
		$('#telefone').val('(000) 0000-0000');
		$('#cpf').val('000.000.000-00');
		$('#cep').val('00000-000');
		$('#consumidor_numero').val('00');
		$('#hora_ligacao').val('00:00');
		$('#consumidor_estado').val('');
		$('#origem').val('');
		$('#consumidor_revenda').val('');
		$('#receber_informacoes').attr('checked', false);
		$('#status_interacao').val('Resolvido');


		$('.input_req').attr('readonly', true);
		$('.input_req').attr('disabled', true);

		$('#consumidor_estado').attr('disabled', true);
		$('#origem').attr('disabled', true);
		$('#consumidor_revenda').attr('disabled', true);
		$('#receber_informacoes').attr('disabled', true);
		$('#status_interacao').attr('disabled', true);


	} else {

		$('.input_req').val('');
		$('#consumidor_estado').val('');
		$('#origem').val('');
		$('#consumidor_revenda').val('');
		$('#receber_informacoes').attr('checked', false);

		input_req = $(".input_req").get();
		for(i = 0; i < input_req.length; i++){
			$(input_req[i]).removeAttr('readonly');
			$(input_req[i]).removeAttr('disabled');
		}

		$('#consumidor_estado').removeAttr('disabled');
		$('#origem').removeAttr('disabled');
		$('#consumidor_revenda').removeAttr('disabled');
		$('#receber_informacoes').removeAttr('disabled');
		$('#status_interacao').removeAttr('disabled');

	}
}

function indicacao_suggar(check, evento){
	if (check.checked){
		$('.input_req').val('Indicação de Posto');
		//$('#telefone').val('(000) 0000-0000');
		$('#cpf').val('');
		$('#cep').val('00000-000');
		$('#consumidor_numero').val('00');
		$('#hora_ligacao').val('00:00');
		//$('#consumidor_estado').val('');
		$('#origem').val('');
		$('#consumidor_revenda').val('');
		$('#receber_informacoes').attr('checked', false);
		$('#status_interacao').val('Resolvido');
		$('#data_nf').val('');
		$('#nota_fiscal').val('');
		$('#serie').val('');

		$('.input_req').attr('readonly', true);
		$('.input_req').attr('disabled', true);

		//$('#consumidor_estado').attr('disabled', true);
		$('#origem').attr('disabled', true);
		$('#consumidor_revenda').attr('disabled', true);
		$('#receber_informacoes').attr('disabled', true);
		$('#status_interacao').attr('disabled', true);

	//HD 235203: Coloquei o evento, pois quando carrega a página e não está marcado indicação de posto, não pode entrar aqui, senão apaga tudo o que o usuário digitou nos dados do consumidor
	} else if (evento == 'onchange') {

		$('.input_req').val('');
		$('#consumidor_estado').val('');
		$('#origem').val('');
		$('#consumidor_revenda').val('');
		$('#receber_informacoes').attr('checked', false);

		input_req = $(".input_req").get();
		for(i = 0; i < input_req.length; i++){
			$(input_req[i]).removeAttr('readonly');
			$(input_req[i]).removeAttr('disabled');
		}

		$('#consumidor_estado').removeAttr('disabled');
		$('#origem').removeAttr('disabled');
		$('#consumidor_revenda').removeAttr('disabled');
		$('#receber_informacoes').removeAttr('disabled');
		$('#status_interacao').removeAttr('disabled');

	}
}


function liberar_campos(){
	input_req = $(".input_req").get();
	for(i = 0; i < input_req.length; i++){
		$(input_req[i]).removeAttr('readonly');
		$(input_req[i]).removeAttr('disabled');
	}
	select_req = $("select:disabled").get();
	for(i = 0; i < select_req.length; i++){
		$(select_req[i]).removeAttr('disabled');
	}
}


function geraProtocolo() {
	var div = $('#protocolo');
	div.html("<img src='imagens/ajax-loader.gif' width='20' height='20'>");
	requisicaoHTTP('GET','gera_protocolo.php', true , 'mostraProtocolo');
}	

function mostraProtocolo(campos) {
	var campos_array = campos.split('|');
	if (campos_array[0]=='sim') {
		var div = $('#protocolo');
		var protocolo = $('#protocolo_id');
		div.html("n <font color='#CC0033'><b>"+campos_array[1]+"</font></b>");
		protocolo.val(campos_array[1]);

	} else {
		var div = $('#protocolo');
		div.html('Erro ao gerar Protocolo');
	}
}
</script>

<br><br>


<? 	if(strlen($msg_erro)>0){ ?>
<!-- Colocar aqui a função substr HD#311031 -->
<? //recarrega informacoes
	$callcenter                = trim($_POST['callcenter']);
	$data_abertura_callcenter  = trim($_POST['data_abertura_callcenter']);
	$natureza_chamado          = trim($_POST['natureza_chamado']);
	$consumidor_nome           = trim($_POST['consumidor_nome']);
	$cliente                   = trim($_POST['cliente']);
	$consumidor_cpf            = trim($_POST['consumidor_cpf']);
	$consumidor_rg             = trim($_POST['consumidor_rg']);
	$consumidor_rg             = preg_replace("/\W/","",$consumidor_rg);
	$consumidor_email          = trim($_POST['consumidor_email']);
	$consumidor_fone           = trim($_POST['consumidor_fone']);
	$consumidor_fone2          = trim($_POST['consumidor_fone2']);
	$consumidor_fone3          = trim($_POST['consumidor_fone3']);
	$consumidor_cep            = trim($_POST['consumidor_cep']);
	$consumidor_cep            = str_replace("-","",$consumidor_cep);
	$consumidor_cep            = str_replace("/","",$consumidor_cep);
	$consumidor_endereco       = trim($_POST['consumidor_endereco']);
	$consumidor_numero         = trim($_POST['consumidor_numero']);
	$consumidor_complemento    = trim($_POST['consumidor_complemento']);
	$consumidor_bairro         = trim($_POST['consumidor_bairro']);
	$consumidor_cidade         = trim(strtoupper($_POST['consumidor_cidade']));
	$consumidor_estado         = trim(strtoupper($_POST['consumidor_estado']));
	$assunto                   = trim($_POST['assunto']);
	$sua_os                    = trim($_POST['sua_os']);
	$data_abertura             = trim($_POST['data_abertura']);

	$produto                   = trim($_POST['produto']);
	$produto_referencia        = trim($_POST['produto_referencia']);
	$produto_nome              = trim($_POST['produto_nome']);
	$voltagem                  = trim($_POST['voltagem']);
	$serie                     = trim($_POST['serie']);
	$data_nf                   = trim($_POST['data_nf']);
	$mapa_linha                = trim($_POST['mapa_linha']);

	$nota_fiscal               = trim($_POST['nota_fiscal']);
	$revenda                   = trim($_POST['revenda']);
	$revenda_nome              = trim($_POST['revenda_nome']);
	$revenda_endereco          = trim($_POST['revenda_endereco']);
	$revenda_nro               = trim($_POST['revenda_nro']);
	$revenda_cmpto             = trim($_POST['revenda_cmpto']);
	$revenda_bairro            = trim($_POST['revenda_bairro']);
	$revenda_city              = trim($_POST['revenda_city']);
	$revenda_uf                = trim($_POST['revenda_uf']);
	$revenda_fone              = trim($_POST['revenda_fone']);
	$posto                     = trim($_POST['posto']);
	$posto_nome                = trim($_POST['posto_nome']);
	$defeito_reclamado         = trim($_POST['defeito_reclamado']);
//	$reclamado                 = trim($_POST['reclamado']);
	$status                    = trim($_POST['status']);

	$transferir                = trim($_POST['transferir']);
	$chamado_interno           = trim($_POST['chamado_interno']);
	$status_interacao          = trim($_POST['status_interacao']);
	$resposta                  = trim($_POST['resposta']);
	$abre_os                   = trim($_POST['abre_os']);
	$hd_extra_defeito          = trim($_POST['hd_extra_defeito']);
	//HD 205933: Habilitar abertura de ORDEM DE SERVIÇO para a Esmaltec
	$abre_ordem_servico        = trim($_POST['abre_ordem_servico']);

?>
<body <? if ($login_fabrica==24) { ?> onload="javascript: var check = document.getElementById('indicacao_posto'); indicacao_suggar(check, 'onload')"; <?}?>>
	<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F7503E;font-size:10px'>
		<tr>
			<td align='center'><? echo "<font color='#FFFFFF'>$msg_erro</font>"; ?></td>
		</tr>
	</table>

<?}

$sql = "SELECT nome from tbl_fabrica where fabrica = $login_fabrica";
$res = pg_query($con,$sql);
$nome_da_fabrica = pg_fetch_result($res,0,0);

?>

<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
	<tr>
		<td align='right' width='150'></td>
		<td align='right' width='55'>
			<img src='imagens/ajuda_call.png' align='absmiddle' onClick='javascript:mostraEsconde();'>
		</td>
		<td align='center'>
			<STRONG>APRESENTAÇÃO</STRONG><BR>
			<?
			$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='1' AND fabrica = $login_fabrica";
			$pe = pg_query($con,$sql);
			if(pg_num_rows($pe)>0){
				echo pg_fetch_result($pe,0,0);
			}else{
				if ($login_fabrica==25) echo "Hbflex"; else echo "$nome_da_fabrica";?>, <?echo ucfirst($login_login);?>, <?echo saudacao();?>.<BR> O Sr.(a) já fez algum contato com a <? if ($login_fabrica==25) echo "Hbflex"; else echo "$nome_da_fabrica ";?> <?if($login_fabrica==25){ ?> por telefone ou pelo Site<?}?> ?
			<?}?>
		</td>
		<td align='right' width='150'></td>
	</tr>
</table>

<BR /><?php

#94971
if ($login_fabrica == 59 AND strlen($_GET['herdar']) > 0) {
	$id = $_GET['Id'];
	$end_herda = "?herdar=sim&Id=$id";
}?>

<form name="frm_callcenter" method="post" action="<?$PHP_SELF?><?=$end_herda?>">
<input name="callcenter" class="input" type="hidden" value='<?echo $callcenter;?>' />
<input name="protocolo_id" id='protocolo_id' class="input" type="hidden" value='<?=$protocolo;?>' />
<table width="100%" border="0" align="center" cellpadding="2" cellspacing="2" style='font-size:12px'><?php
if ($login_fabrica == 5 or $login_fabrica == 24) { ?>
	<tr>
		<td align='right' style='font-size: 14px; font-weight: bold; font-family: arial; color:red'>INDICAÇÃO DE POSTO
			<input type="checkbox" name="indicacao_posto" id="indicacao_posto" <? if($indicacao_posto=="t") echo "checked";?> value="t" <?if ($login_fabrica == 24){ ?>onChange="indicacao_suggar(this, 'onchange');" <?} else {?> onChange="indicacao(this);" <?}?>>
		</td>
	</tr><?php
}

if (($login_fabrica == 11 || $login_fabrica == 80) and strlen($callcenter) > 0) { ?>
	<tr>
		<td align='right' style='font-size: 14px; font-weight: bold; font-family: arial; color:red'>

			<a href='callcenter_upload_imagens.php?callcenter=<?=$callcenter;?>'>ANEXAR IMAGEM</a><?php

			$limite_anexos = 10;

			$caminho = 'callcenter_digitalizados/';
			
			$contador = 1;

			for($i = 0; $i < $limite_anexos; $i++){
				if($i == 0)
					$arquivo = $callcenter . ".jpg";
				else
					$arquivo = $callcenter . "-$i.jpg";

				$arquivo_destino = $caminho.$arquivo;

				if(file_exists($arquivo_destino)){
					if($i == 0)
						echo "  <span style='font-size:11px;'><a href=\"javascript://\" OnClick=\"abrir_anexo('$arquivo')\">Anexo</a></span>";
					else
						echo "  - <span style='font-size:11px;'><a href=\"javascript://\" OnClick=\"abrir_anexo('$arquivo')\">Anexo ".($i)."</a></span>";
				}
			}
			?>
		</td>
	</tr>
<?}?>

<tr>
	<td align='left'>

		<table width="100%" border='0'>
			<tr>
				<td align='left'><strong>Cadastro de Atendimento</strong></td>
				<? if(strlen($callcenter)>0 AND $login_fabrica == 3) { ?>
				<td  nowrap>Tipo de registro: <strong><? echo $tipo_registro; ?></strong></td>
				<? } ?>
				<?
				if (strlen ($admin_login) > 0) {
					echo "<td><b>Aberto por: </b> $admin_login - $admin_nome_completo <b> Em: </b> $data_abertura_callcenter </td>";
				}
				?>
				<? if(strlen($atendimento_callcenter)>0 AND $login_fabrica == 35) { ?>
				<td  nowrap>Atendimento(Solutiva): <strong><? echo $atendimento_callcenter; ?></strong></td>
				<? } ?>
				<td align='right'><div id='protocolo'><strong><? if(strlen($callcenter)>0){ if( strlen( $protocolo_cliente && $login_fabrica == 90 ) > 0 ) echo "Número IBBL: <font color='#CC0033'>" . $protocolo_cliente . '</font> - ' ; echo "n <font color='#CC0033'>$callcenter</font>";} else {echo "<font color='#CC0033'><a href='#' onclick='geraProtocolo()'>GERAR PROTOCOLO</font></a>";}?></strong></div></td>
			</tr>
		</table>

		<?  if(strlen($callcenter)==0){ ?>

		<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<!-- HD 216395: Mudar todas as buscas de nome para LIKE com % apenas no final. A funcao function mostrarMensagemBuscaNomes() está definida no js/assist.js -->
			<tr>
				<td colspan="2" style='border: 1px solid #DD0000; background-color: #FFDDCC; padding: 5px;' id="aviso_localizar_nome">
				<b>BUSCA POR NOME:</b> utilizar sempre letras MAIUSCULAS E SEM ACENTOS. Clique no icone "?" a seguir para INSTRUÇÕES <img align="absmiddle" src='imagens/help.png' title='Clique aqui para ajuda na busca deste campo' onclick='mostrarMensagemBuscaNomes()'>
				</td>
			</tr>
			<tr>
				<td align='left' width='68'><strong>Localizar:</strong></td>
				<td>
					<table>
					<?
					//HD 163220 - Abrir popup para todos os tipos de pesquisas na fábrica
					//			- Retirei o if ($login_fabrica == 3), pois a Britania não utiliza esta tela e a busca
					//			  está sendo padronizada
					$localizar_opcoes = array("cpf", "nome", "atendimento", "os", "serie", "cep", "telefone");
					$localizar_labels = array("CPF/CNPJ", "Nome", "Atendimento", "OS", "nº de Série", "CEP", "Telefone");
					$localizar_posicao_retorno = array(11, 1, 0, 16, 17, 6, 8);
					$localizar_links = array();
					$localizar_autocomplete = array();
					//HD311031
					$cor_font = $login_fabrica == 30 ? 'font-size:10px;' : '';

					for ($l = 0; $l < count($localizar_opcoes); $l++) {
						if ($l % 3 == 0) {
							$localizar_links[$l] = "<tr>";
						}

						if ($localizar_opcoes[$l] == "nome") {
							$onkeyup = "somenteMaiusculaSemAcento(this);";
						}
						else {
							$onkeyup = "";
						}

						$localizar_links[$l] .= "<td align=right style='padding-left:10px;$cor_font'>" . $localizar_labels[$l] . ": </td><td><input type=text name='localizar_" . $localizar_opcoes[$l] . "' id='localizar_" . $localizar_opcoes[$l] . "' onkeyup='retiraAcentos(this); $onkeyup' class=input_req2> <img src='imagens/lupa.png' title='Buscar' onclick='fnc_pesquisa_consumidor_callcenter(document.getElementById(\"localizar_" . $localizar_opcoes[$l] . "\").value, \"" . $localizar_opcoes[$l] . "\")' style='cursor:pointer;'></td>";

						if ($l % 3 == 2) {
							$localizar_links[$l] .= "</tr>";
						}

						if ($localizar_opcoes[$l] != "nome") {
							$localizar_autocomplete[$l] = '
							$("#localizar_' . $localizar_opcoes[$l] . '").autocomplete("pesquisa_consumidor_callcenter_new.php?tipo=' . $localizar_opcoes[$l] . '&ajax=sim&engana="+engana, {
							minChars: 6,
							delay: 700,
							width: 300,
							max: 30,
							matchContains: true,
							formatItem: formatLocalizar,
							formatResult: function(row) {
							return $("#localizar_' . $localizar_opcoes[$l] . '").val();
							}
							});

							$("#localizar_' . $localizar_opcoes[$l] . '").result(function(event, data, formatted) {
								preencheConsumidorAutocomplete(event, data, formatted);
								fnc_pesquisa_consumidor_callcenter(data[' . $localizar_posicao_retorno[$l] . '], "' . $localizar_opcoes[$l] . '", true);
							});
							';
						}
					}

					$novo_consumidor = "<input type=button value='Novo Consumidor' onclick=\"javascript:localizarConsumidor('localizar','novo')\" class=input_req2 style='background-color:#DDDDDD'>";

					switch ($l % 3) {
						case 0:
							$localizar_links[$l] .= "<td colspan=2 align=right>$novo_consumidor</td><td></td><td></td></tr>";
						break;

						case 1:
							$localizar_links[$l] .= "<td colspan=2 align=right>$novo_consumidor</td><td></td></tr>";
						break;

						case 2:
							$localizar_links[$l] .= "<td colspan=2 align=right>$novo_consumidor</td></tr>";
						break;
					}

					$localizar_links = implode("", $localizar_links);
					echo $localizar_links;

					$localizar_autocomplete = implode("", $localizar_autocomplete);
					echo "
					<script language='javascript'>
					hora = new Date();
					engana = hora.getTime()
					$localizar_autocomplete
					</script>
					";
					?>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan=2>
					Digite pelo menos 6 caracteres em um dos campos acima para localizar e recuperar na tela os dados do consumidor. O sistema apresentará sempre 5 opções. Para uma busca completa utilize o botão <b>Buscar</b> ao lado do campo. Demais dados (informações da OS, produto e/ou Autorizada) podem ser recuperados através do pop-up aberto com o botão Buscar.
				</td>
			</tr>
		</table>
	<?  } ?>
	</td>
</tr>

<tr>
	<td>

	<div id='div_consumidor' style='display:inline; Position:relative;background-color: #e6eef7;width:100%'>
		<input type='hidden' name='atendimento_callcenter' value='<?=$atendimento_callcenter?>'>
		<table width='100%' border='0' align='center' cellpadding='2' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px' id='tabela_consumidor'>
		<!--HD36903-->
		<?PHP
			if ($login_fabrica == 2 || $login_fabrica == 10 || $login_fabrica == 91) {
		?>
		<tr>
			<td colspan='6'  align='left'>
				<table border='0' cellpadding='3' cellspacing='0' width="50%">
					<tr>
						<td align='left'>
							<b>Tipo de atendimento:</b>
						</td>
						<td align='left'>
							<label for="consumidor_revenda_c">Consumidor</label>
							<input type='radio' id="consumidor_revenda_c" name='consumidor_revenda' value='C' <?PHP if ($consumidor_revenda == 'C' or $consumidor_revenda == '') { echo "CHECKED";}?> onclick="fnc_tipo_atendimento(this)">
						</td>
						<td align='left'>
							<label for="consumidor_revenda_r">Revenda</label>
							<input type='radio' id="consumidor_revenda_r" name='consumidor_revenda' value='R' <?PHP if ($consumidor_revenda == 'R') { echo "CHECKED";}?> onclick="fnc_tipo_atendimento(this)">
						</td>
						<td align='left'>
							<label for="consumidor_revenda_a">Assistência Técnica</label>
							<input type='radio' id="consumidor_revenda_a" name='consumidor_revenda' value='A' <?PHP if ($consumidor_revenda == 'A') { echo "CHECKED";}?> onclick="fnc_tipo_atendimento(this)">
						</td>
						<?php if ( $login_fabrica == 10 ): ?>
						<td align='left'>
							<label for="consumidor_revenda_f">Fábrica</label>
							<input type='radio' id="consumidor_revenda_f" name='consumidor_revenda' value='F' <?PHP if ($consumidor_revenda == 'F') { echo "CHECKED";}?> onclick="fnc_tipo_atendimento(this)">
						</td>
						<?php endif; ?>
						<?php if ( $login_fabrica == 91 ){ ?>
						<td align='left'>
							<label for="consumidor_revenda_f">Equipe Comercial</label>
							<input type='radio' id="consumidor_revenda_e" name='consumidor_revenda' value='E' <?PHP if ($consumidor_revenda == 'E') { echo "CHECKED";}?> onclick="fnc_tipo_atendimento(this)">
						</td>
						<?php } ?>
					<tr>
				</table>
			</td>
		</tr>
		<?
			}elseif($login_fabrica == 59 or $login_fabrica == 24 or $login_fabrica == 30 or $login_fabrica == 52) {
				if($login_fabrica == 30){
		?>
		<tr>
			<td colspan="6" style='border: 1px solid #DD0000; background-color: #FFDDCC; padding: 5px;' id="aviso_localizar_nome">
			OS CAMPOS EM VERMELHO SÃO DE PREENCHIMENTO OBRIGATÓRIO
			</td>
		</tr>
		<?php   }  ?>
		<tr>
			<td colspan='6'  align='left'>
				<table border='0' cellpadding='3' cellspacing='0' width="50%">
					<tr>
						<td align='left'>
							<b>Tipo Consumidor:</b>
						</td>
						<td align='left'>
							CPF
							<input type='radio' name='consumidor_cpf_cnpj' id='consumidor_cfp' value='C' <?PHP if (strlen($consumidor_cpf) == 14 or strlen($consumidor_cpf) == 0) { echo "CHECKED";}
							if(strlen($callcenter) > 0) { echo " disabled"; }?> onclick="fnc_tipo_atendimento(this)">
						</td>
						<td align='left'>
							CNPJ
							<input type='radio' name='consumidor_cpf_cnpj'id='consumidor_cnpj' value='R' <?PHP if (strlen($consumidor_cpf) == 18) { echo "CHECKED";}
							if(strlen($callcenter) > 0) { echo " disabled"; }
							?> onclick="fnc_tipo_atendimento(this)">
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<? } ?>
<? if ($login_fabrica == 52) { ?>
		<tr>
			<td align='left'><strong>Cliente Fricon:</strong>
				<input type='hidden' name='cliente_admin' id='cliente_admin' value="<? echo $cliente_admin; ?>">
			</td>
			<td align='left'>
				<input name="cliente_nome_admin" id="cliente_nome_admin" value='<?echo $cliente_nome_admin ;?>' class='input_req' type="text" size="35" maxlength="50">
			</td>
		<tr>
	<?
		}
	?>
		<tr>
			<? if ($login_fabrica == 30) { ?>
				<td align='left'><strong><acronym title='Campo Obrigatório'><font color="#AA0000">Nome:</font></acronym></strong></td>
			<? } else { ?>
				<td align='left'><strong>Nome:</strong></td>
			<? } ?>

			<td align='left'>
			<? if ($login_fabrica == 30) { ?>
				<input type="hidden" name="consumidor_nome_anterior" value="<?php echo $consumidor_nome; ?>" />
				<acronym title='Campo Obrigatório'><input name="consumidor_nome" id="consumidor_nome" maxlength='50' value='<?php echo $consumidor_nome ;?>'
				 <?echo ($login_fabrica == 24) ? 'class="input_req2"':'class="input_req"';?> type="text" size="35" maxlength="50"
				 	onkeyup="somenteMaiusculaSemAcento(this);"
				 <? if($login_fabrica==11){?> onChange="javascript: this.value=this.value.toUpperCase();"<?}?>>
				 <img style="cursor: pointer;" onclick='fnc_pesquisa_consumidor_callcenter(document.getElementById("consumidor_nome").value, "nome")'
				 	  title="Buscar" src="imagens/lupa.png"/></acronym>
			<? }else{ ?>
				<input type="hidden" name="consumidor_nome_anterior" value="<?php echo $consumidor_nome; ?>" />
				<input name="consumidor_nome" id="consumidor_nome" maxlength='50' value='<?php echo $consumidor_nome ;?>'
				 <?echo ($login_fabrica == 24) ? 'class="input_req2"':'class="input_req"';?> type="text" size="35" maxlength="50"
				 	onkeyup="somenteMaiusculaSemAcento(this);"
				 <? if($login_fabrica==11){?> onChange="javascript: this.value=this.value.toUpperCase();"<?}?>>
				 <img style="cursor: pointer;" onclick='fnc_pesquisa_consumidor_callcenter(document.getElementById("consumidor_nome").value, "nome")'
				 	  title="Buscar" src="imagens/lupa.png"/>
			<? } ?>
			</td>
			
			<td align='left'><strong><span id='label_cpf'>
			<?
			if((strlen($consumidor_cpf) != 18 and strlen($callcenter) > 0) or strlen($callcenter) == 0) {
				echo "CPF:";
				$limite ='14';
			}elseif(strlen($consumidor_cpf) == 18 and strlen($callcenter) > 0){
				echo "CNPJ:";
				$limite = "18";
			}
			$campos_obrig = $login_fabrica == 30 ? '<acronym title="Campo Obrigatório"><font color="#AA0000">' : NULL;
			$fx_cmp_obg = !is_null($campos_obrig) ? '</font></acronym>' : '';
			?>
			</span></strong></td>
<?/* 08/04/2010 MLG - HD 209670 - Retirado código para bloquear esta parte do formulário na Lenoxx */?>
			<td align='left'>
				<input type="hidden" name="consumidor_cpf_anterior" value="<?php echo $consumidor_cpf; ?>" />
				<input type="text"	 name="consumidor_cpf" id="cpf" value='<? echo $consumidor_cpf ;?>'
					  class="input_req" size="18" maxlength="<?=$limite?>"
				 onkeypress="return txtBoxFormat(this.form, this.name, '999.999.999-99', event);">
				<img style="cursor: pointer;" onclick='fnc_pesquisa_consumidor_callcenter(document.getElementById("cpf").value, "cpf")' title="Buscar" src="imagens/lupa.png"/>
				<input name="cliente" id="cliente" value='<? echo $cliente ;?>' type="hidden">
			</td>
			<td align='left'><strong>RG:</strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_rg_anterior" value="<?php echo $consumidor_rg; ?>" />
				<input name="consumidor_rg" id="consumidor_rg" value='<? echo $consumidor_rg ;?>'  class="input_req" type="text" size="14" maxlength="14">
			</td>
		</tr>

		<tr>
			<?php $endereco_readonly = '' ; //08/04/2010 MLG - HD 209670 - ( $login_fabrica == 11 && isset($callcenter) && $callcenter > 0 && $status_interacao != 'Aberto' ) ? 'readonly' : ?>
			<td align='left'><strong>E-mail:</strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_email_anterior" value="<?php echo $consumidor_email; ?>" />
				<input name="consumidor_email" id="consumidor_email" value='<? echo $consumidor_email ?>' class="input_req" type="text" size="40" maxlength="50" <?php echo $endereco_readonly; ?> >
			</td>
			<td align='left'>
				<strong>
					<? echo ($login_fabrica==59) ? "Telefone Residêncial:" : "<strong>".$campos_obrig."Telefone:".$fx_cmp_obg."</strong>"; ?>
				</strong>
			</td>
			<td align='left'>
				<input type="hidden" name="consumidor_fone_anterior" value="<?php echo $consumidor_fone; ?>" />
				<input name="consumidor_fone" id="telefone" value='<? echo $consumidor_fone ;?>'  <? if ($login_fabrica == 24) { ?>class="input_req2"<? } else { ?> class="input_req" <? } ?>  type="text" size="18" maxlength="15" onkeypress="return txtBoxFormat(this.form, this.name, '(99) 9999-9999', event);" <?php echo $endereco_readonly; ?> >
			</td>
			<td align='left'><strong>CEP:</strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_cep_anterior" value="<?php echo $consumidor_cep; ?>" />
				<input name="consumidor_cep" id="cep" value="<? echo $consumidor_cep ;?>"  class="input_req" type="text" size="14" maxlength="9" onchange="buscaCEP(this.value, document.frm_callcenter.consumidor_endereco, document.frm_callcenter.consumidor_bairro, document.frm_callcenter.consumidor_cidade, document.frm_callcenter.consumidor_estado) ;" onkeypress="return txtBoxFormat(this.form, this.name, '99999-999', event);" <?php echo $endereco_readonly; ?> >
			</td>
		</tr>
		<tr>
			<td align='left'><strong><?=$campos_obrig;?>Endereço:<?=$fx_cmp_obg;?></strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_endereco_anterior" value="<?php echo $consumidor_endereco; ?>" />
				<input name="consumidor_endereco" id='consumidor_endereco' value='<? echo $consumidor_endereco ;?>' class="input_req" type="text" size="40" maxlength="60" <?php echo $endereco_readonly; ?> >
			</td>
			<td align='left'><strong><?=$campos_obrig;?>Número:</strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_numero_anterior" value="<?php echo $consumidor_numero; ?>" />
				<input name="consumidor_numero" id='consumidor_numero' value='<? echo $consumidor_numero ;?>' class="input_req" type="text" size="18" maxlength="15" <?php echo $endereco_readonly; ?> >
			</td>
			<td align='left'><strong>Complem.</strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_complemento_anterior" value="<?php echo $consumidor_complemento; ?>" />
				<input name="consumidor_complemento" id='consumidor_complemento' value='<? echo $consumidor_complemento ;?>' class="input_req" type="text" size="14" maxlength="20" <?php echo $endereco_readonly; ?> >
			</td>
		</tr>
		<tr>
			<td align='left'><strong><?=$campos_obrig;?>Bairro:</strong></td>
			<td align='left'>
				<input type="hidden" name="consumidor_bairro_anterior" value="<?php echo $consumidor_bairro; ?>" />
				<input name="consumidor_bairro" id='consumidor_bairro' value='<? echo $consumidor_bairro ;?>' <?if ($login_fabrica <> 24) { ?>class="input_req" <? }?>type="text" class="input_req" size="40" maxlength="30" <?php echo $endereco_readonly; ?> >
			</td>
			<? if ($login_fabrica == 30) { ?>
				<td align='left'><strong><acronym title='Campo Obrigatório'><font color="#AA0000">Cidade:</font></acronym></strong></td>
			<? } else { ?>
				<td align='left'><strong>Cidade:</strong></td>
			<? } ?>
			
			<td align='left'>
			<? if ($login_fabrica == 30) {?>
				<input type="hidden" name="consumidor_cidade_anterior" value="<?php echo $consumidor_cidade; ?>" />
				<acronym title='Campo Obrigatório'><input name="consumidor_cidade" id='consumidor_cidade' value='<? echo $consumidor_cidade ;?>'  <?if ($login_fabrica == 24) {?>class="input_req2"<?} else {?> class="input_req" <?}?> type="text" size="18" maxlength="16" <?php echo $endereco_readonly; ?> ></acronym>
				<input name="cidade"  class="input_req" value='<? echo $cidade ;?>' type="hidden">
			<? }else{ ?>
				<input type="hidden" name="consumidor_cidade_anterior" value="<?php echo $consumidor_cidade; ?>" />
				<input name="consumidor_cidade" id='consumidor_cidade' value='<? echo $consumidor_cidade ;?>'  <?if ($login_fabrica == 24) {?>class="input_req2"<?} else {?> class="input_req" <?}?> type="text" size="18" maxlength="50" <?php echo $endereco_readonly; ?> >
				<input name="cidade"  class="input_req" value='<? echo $cidade ;?>' type="hidden">
			<? } ?>
			</td>

			<? if ($login_fabrica == 30) { ?>
				<td align='left'><strong><acronym title='Campo Obrigatório'><font color="#AA0000" style="bold">Estado:</font><acronym></strong></td>
			<? } else { ?>
				<td align='left'><strong>Estado:</strong></td>
			<? } ?>
			<td align='left'>
			<? if ($login_fabrica == 30) { ?>
				<input type="hidden" name="consumidor_estado_anterior" value="<?php echo $consumidor_estado; ?>" />
				<acronym title='Campo Obrigatório'><select name="consumidor_estado" id='consumidor_estado' style='width:81px; font-size:9px'>
					<? $ArrayEstados = array('','AC','AL','AM','AP',
												'BA','CE','DF','ES',
												'GO','MA','MG','MS',
												'MT','PA','PB','PE',
												'PI','PR','RJ','RN',
												'RO','RR','RS','SC',
												'SE','SP','TO'
											);
					for ($i=0; $i<=27; $i++){
						echo"<option value='".$ArrayEstados[$i]."'";
						if ($consumidor_estado == $ArrayEstados[$i]) echo " selected";
						echo ">".$ArrayEstados[$i]."</option>\n";
					}?>
				</select></acronym>
			<? }else{ ?>
				<input type="hidden" name="consumidor_estado_anterior" value="<?php echo $consumidor_estado; ?>" />
				<select name="consumidor_estado" id='consumidor_estado' style='width:81px; font-size:9px'>
					<? $ArrayEstados = array('','AC','AL','AM','AP',
												'BA','CE','DF','ES',
												'GO','MA','MG','MS',
												'MT','PA','PB','PE',
												'PI','PR','RJ','RN',
												'RO','RR','RS','SC',
												'SE','SP','TO'
											);
					for ($i=0; $i<=27; $i++){
						echo"<option value='".$ArrayEstados[$i]."'";
						if ($consumidor_estado == $ArrayEstados[$i]) echo " selected";
						echo ">".$ArrayEstados[$i]."</option>\n";
					}?>
				</select>
			<? } ?>
			</td>
		</tr>
		<tr>
			<?if($login_fabrica <> 3) { // HD 48900 ?>
			<td colspan='2' align='left'>
				<strong>Melhor horário p/ contato: </strong>
				<input name="hora_ligacao" id='hora_ligacao' class="input_req" value='<?echo $hora_ligacao ;?>' type="text" maxlength='5' size='7'>
			</td>
			<? } ?>
			<td align='left'><strong>Origem:</strong></td>
			<td align='left'>
				<select name='origem' id='origem' style='width:102px;font-size:9px'>
				<? if($login_fabrica ==3) { // HD 48900?>
				<option value=''></option>
				<? } ?>
				<option value='Telefone' <?PHP if ($origem == 'Telefone') { echo "Selected";}?>>Telefone</option>
				<option value='Email' <?PHP if ($origem == 'Email') { echo "Selected";}?>>E-mail</option>
				<?if ($login_fabrica == 2){?>
					<option value='0800' <?PHP if ($origem == '0800'){ echo "Selected";}?>>Atendimento 0800</option>
					<option value='9166' <?PHP if ($origem == '9166') { echo "Selected";}?>>Atendimento 9166</option>
					<option value='Outros' <?PHP if ($origem == 'Outros') { echo "Selected";}?>>Outros</option>
				<?}?>
				</select>
			</td>
			<!--HD36903-->
			<?PHP if ($login_fabrica != 2 && $login_fabrica != 10 && $login_fabrica != 91) {?>
			<td align='left'><strong>Tipo:</strong></td>
			<td align='left'>
				<select name="consumidor_revenda" id='consumidor_revenda' style='width:81px; font-size:9px'>
				<? if($login_fabrica ==3) { // HD 48900?>
				<option value=''></option>
				<? } ?>
				<option value='C' <? if($consumidor_revenda == "C") echo "Selected" ;?>>Consumidor</option>
				<option value='R' <? if($consumidor_revenda == "R") echo "Selected" ;?>>Revenda</option>
				</select>
			</td>
			<?PHP }?>
		</tr>
		<?php if ( in_array( $login_fabrica, $fab_usa_tipo_cons) ) : //HD 317864 ?>
			<tr>
				<td colspan="2">
					<fieldset style="width:230px; text-align:left;">
						<legend style="font-weight:bold;">Tipo de Consumidor</legend>
						<?php
							
							if (!isset($_POST['tipo_consumidor']) && !empty($callcenter) ) {
								
								$sql = "SELECT tipo_consumidor FROM tbl_hd_chamado_extra WHERE hd_chamado = $callcenter ";
								$res = pg_query($con,$sql);
								if(pg_num_rows($res)) {
									$checked = pg_result ($res,0,0);
									$CA = $checked == 'CA' ? 'checked' : '';
									$CF = $checked == 'CF' ? 'checked' : '';
								}
							}
						
						?>
						<input type="radio" name="tipo_consumidor" <?=$CF?> value="CF" id="CF" <? echo $_POST['tipo_consumidor'] == 'CF' ? 'checked' : ''; ?> /><label for="CF">&nbsp;Consumidor Final</label>&nbsp;&nbsp;
						<input type="radio" name="tipo_consumidor" <?=$CA?> value="CA" id="CA"  <? echo $_POST['tipo_consumidor'] == 'CA' ? 'checked' : ''; ?> /><label for="CA">&nbsp;Cabelereiro</label>
					</fieldset>
				</td>
			</tr>
		<?php endif; ?>
		<tr>
			<?if($login_fabrica <> 3) { // HD 48900 ?>
			<td colspan='2' align='left'>
				<input type="checkbox" name="receber_informacoes" id="receber_informacoes" <? if($receber_informacoes=="t") echo "checked";?> value='t'>
				<strong>Aceita receber informações sobre nossos produtos? </strong> <br>
				<a  href="javascript:fnc_pesquisa_os (document.frm_callcenter.consumidor_cpf, 'os')">Clique aqui para ver todas as OSs cadastradas com CPF deste consumidor</a>
			</td>
			<? }
			//HD 201434 - Retirado o Telefone 2 para Gama Italy, pois é o mesmo campo de Telefone comercial
			?>

			<td align='left' colspan='1'><strong>Telefone Comercial:</strong></td>
			<td align='left' colspan='1'>
				<input type="hidden" name="consumidor_fone2_anterior" value="<?php echo $consumidor_fone2; ?>" />
				<input name="consumidor_fone2" id="telefone2" value='<?php echo $consumidor_fone2 ;?>'  class="input"  type="text" size="18" maxlength="14" onkeypress="return txtBoxFormat(this.form, this.name, '(99) 9999-9999', event);">
			</td>

			<td align='left' colspan='1'><strong>Telefone Celular:</strong></td>
			<td align='left' colspan='1'>
				<input type="hidden" name="consumidor_fone3_anterior" value="<?php echo $consumidor_fone3; ?>" />
				<input name="consumidor_fone3" id="telefone3" value='<?php echo $consumidor_fone3 ;?>'  class="input"  type="text" size="18" maxlength="14" onkeypress="return txtBoxFormat(this.form, this.name, '(99) 9999-9999', event);">
			</td>

			<? if ($login_fabrica == 11 || $login_fabrica == 24 || $login_fabrica == 81) { // HD 14549?>
			<td align='left' width=50><strong>OS:</strong></td>
			<td align='left' width=150>
			<input name="os" id="os" class="input"  value='<?echo $sua_os ;?>'> <img style="cursor: pointer;" align="absmiddle" onclick='fnc_pesquisa_consumidor_callcenter(document.getElementById("os").value, "os")' title="Buscar" src="imagens/lupa.png"/>
			</td>
			<? } ?>
			<?  if ($login_fabrica == 24 AND strlen($familia) > 0 AND strlen($callcenter) > 0) { // HD 98922?>
			<td align='right' colspan='1'><br /><a href="envio_email_callcenter.php?callcenter=<?=$callcenter?>&KeepThis=true&TB_iframe=true&height=500&width=700" class='thickbox' title='Enviar E-mail para consumidor'>Clique aqui para enviar E-mail para <?=$consumidor_email?></a>
			</td>
			<? } ?>
		</tr>
		</table>
	</div>
	<br>
	<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
		<tr>
			<td align='right' width='150'></td>
			<td align='right' width='55'>
				<img src='imagens/ajuda_call.png' align='absmiddle' >
			</td>
			<td align='center'>
				<?
				$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='2' AND fabrica = $login_fabrica";
				$pe = pg_query($con,$sql);
				echo (pg_num_rows($pe)>0) ? pg_fetch_result($pe,0,0) : "Qual o produto comprado?";
				?>
			</td>
			<td align='right' width='150'></td>
		</tr>
	</table>

	<table width="100%" border='0'>
		<tr>
			<td align='left'><strong>Informações do produto</strong></td>
		</tr>
	</table>
	<?php
		//alteracao para Fricon lancar varios item num mesmo callcenter hd 165524 waldir
	if ($login_fabrica <> 52) { ?>
	<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>

		<tr>
			<td align='left'><?=$campos_obrig;?><strong>Referência:</strong><?=$fx_cmp_obg;?></td>
			<td align='left'>
				<input type="hidden" name="produto_referencia_anterior" value="<?php echo $produto_referencia;  ?>" />
				<input name="produto_referencia"  class="input"  value='<? echo $produto_referencia ;?>'
				onblur="javascript: fnc_pesquisa_produto2 (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'referencia',document.frm_callcenter.mapa_linha); <?php  if ($login_fabrica <> 51){ # HD 41923 ?>
					mostraDefeitos('Reclamado',document.frm_callcenter.produto_referencia.value);
					<?php } ?>
					atualizaQuadroMapas();" type="text" size="15" maxlength="15">
				<img src='imagens/lupa.png' border='0' align='absmiddle'
				style='cursor: pointer'
				onclick="javascript: setTimeout(\"fnc_pesquisa_produto2 (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'referencia',document.frm_callcenter.mapa_linha)\", 300)">
			</td>
			<td align='left'><?=$campos_obrig;?><strong>Descrição:</strong><?=$fx_cmp_obg;?></td>
			<td align='left'>
				<input type="hidden" name="produto_nome_anterior" value="<?php echo $produto_nome; ?>" />
				<input type='hidden' name='produto' value="<? echo $produto; ?>">
				<input name="produto_nome"  class="input" value='<?php echo $produto_nome ;?>'
				<? if ($login_fabrica <> 52) { ?> onblur='janela_descricao = setTimeout("fnc_pesquisa_produto2 (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,\"descricao\",document.frm_callcenter.mapa_linha)", 300); <?php }if ($login_fabrica <> 51){ ?>
					mostraDefeitos("Reclamado",document.frm_callcenter.produto_referencia.value);
					<?php } ?>
					atualizaQuadroMapas();' type="text" size="35" maxlength="80">
				<img src='imagens/lupa.png' border='0' align='absmiddle'
				style='cursor: pointer'
				onclick='clearTimeout(janela_descricao); fnc_pesquisa_produto2 (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,"descricao",document.frm_callcenter.mapa_linha);'>
			</td>
			 <? if ($login_fabrica == 14 or $login_fabrica == 43) { ?>
				 <td align='left'><strong>Ordem de Montagem</strong></td>
				 <td align='left'>
		         <input type='text' class="input" name='ordem_montagem' size='20'  value='<?=$ordem_montagem;?>'>
				 </td>
				  <?}?>
			</tr>
			<tr>
				<td align='left'><?=$campos_obrig;?><strong>Voltagem:</strong><?=$fx_cmp_obg;?></td>
				<td align='left'>
					<input type="hidden" name="voltagem_anterior" value="<?php echo $voltagem; ?>" />
					<input name="voltagem" id="voltagem" class="input" value='<?php echo $voltagem;?>' maxlength="5" >
				</td>
				<td align='left'><strong>Série:</strong></td>
				<td align='left'>
					<input type="hidden" name="serie_anterior" value="<?php echo $serie; ?>" />
					<input name="serie" id="serie" maxlength="20" class="<?php echo ($login_fabrica==24) ? 'input_req' : 'input' ; ?>" value="<?php echo $serie;?>" /><img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer'
					onclick="javascript: fnc_pesquisa_serie (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'serie',document.frm_callcenter.mapa_linha,document.frm_callcenter.serie)">
				</td>
				<? if ($login_fabrica == 14 or $login_fabrica == 43) {?>
					<td align='left'><strong>
					Codigo de Postagem</strong>
					</td>
					<td align='left'>
					<input type='text' class="input" name='codigo_postagem' size='30' value='<?=$codigo_postagem;?>'>
					</td>
				<?}?>
		</tr>
		<tr>
			<? if($login_fabrica==30){?>
				<td align='left'><?=$campos_obrig;?><strong>NF compra:</strong><?=$fx_cmp_obg;?></td>
			<? } else{ ?>
				<td align='left'><strong>NF compra:</strong></td>
			<? } ?>
			
			<td align='left'>
					<input type="hidden" name="nota_fiscal_anterior" value="<?php echo $nota_fiscal; ?>"/>
				<input name="nota_fiscal" id="nota_fiscal" class="<?php echo ($login_fabrica==24) ? 'input_req' : 'input' ; ?>" value="<?php echo $nota_fiscal;?>" maxlength="10" />
			</td>
			<td align='left'><?=$campos_obrig;?><strong>Data NF:</strong><?=$fx_cmp_obg;?></td>
			<td align='left'>
				<input type="hidden" name="data_nf_anterior" value="<?php echo $data_nf; ?>" />
				<input name="data_nf" id="data_nf" class="<?php echo ($login_fabrica==24) ? 'input_req' : 'input' ; ?>" rel="data" value="<?php echo $data_nf ;?>">
			</td>
			<? if($login_fabrica==24 AND strlen($familia) > 0) {
				echo "<td align='left'><strong>Familia:</strong></td>";
				echo "<td align='left'>";
				$sql = " SELECT descricao FROM tbl_familia WHERE familia = $familia ";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0){
					echo "".pg_fetch_result($res,0,descricao);
				}
				echo "</td>";
			}?>
		</tr>
		<? 
		//HD 204082: Busca de revenda para fábricas >= 81 e Telecontrol Net
		if($login_fabrica==2 || $login_fabrica >= 81 || $login_fabrica == 46) {
			if ($nome_revenda == "" && strlen($revenda_nome)) {
				$nome_revenda = $revenda_nome;
			}
		?>
			<tr>
				<td align='left'><strong>CNPJ da Revenda:</strong></td>
				<td align='left'>
					<input name="cnpj_revenda" id="cnpj_revenda" class="<?php echo ($login_fabrica==24) ? 'input_req' : 'input' ; ?>" value="<?php echo $cnpj_revenda;?>"type="text" maxlength="14" />
					<input name="revenda" id="revenda" type="hidden" value="<?php echo $revenda;?>" />
				</td>
				<td align='left'><strong>Nome da Revenda:</strong></td>
				<td align='left'>
					<input name="nome_revenda" id="nome_revenda" class="<?php echo ($login_fabrica==24) ? 'input_req' : 'input' ; ?>" value="<?php echo $nome_revenda;?>" size=35 maxlength="50" />
				</td>
			</tr>
		<? 
		}
		if($login_fabrica==3) {?>
		<tr>
		<tr>
			<td colspan='2' align='left'>
				<a  href="javascript:fnc_pesquisa_os (document.frm_callcenter.nota_fiscal, 'nota_fiscal')">Clique aqui para ver todas as OSs cadastradas com esta nota fiscal</a>
			</td>
		</tr>
		<?}?>
	</table>

	<?
	}
	else {
		unset($defeito_reclamado);
	?>
	<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px' name='tabela_itens' id='tabela_itens'>
		<thead>
		<tr>
			<td align='left'><strong>NF compra:</strong></td>
			<td align='left'>
				<input type="hidden" name="nota_fiscal_anterior" value="<?php echo $nota_fiscal; ?>"/>
				<input name="nota_fiscal" id="nota_fiscal" class="<?php echo ($login_fabrica==24) ? 'input_req' : 'input' ; ?>" value="<?php echo $nota_fiscal;?>" maxlength="10" />
			</td>
			<td align='left'><strong>Data NF:</strong></td>
			<td align='left'>
				<input type="hidden" name="data_nf_anterior" value="<?php echo $data_nf; ?>" />
				<input name="data_nf" id="data_nf" class="<?php echo ($login_fabrica==24) ? 'input_req' : 'input' ; ?>" rel="data" value="<?php echo $data_nf ;?>">
			</td>
		</tr>
		</thead>
		<tbody>
		<?php
		if (strlen($callcenter)>0) {
			$sql_produto = "SELECT produto,descricao,referencia,serie,defeito_reclamado from tbl_hd_chamado_item join tbl_produto using(produto) where hd_chamado = $callcenter order by hd_chamado_item ";

			$res_produto = pg_query($con,$sql_produto);
			$qtde_produto = pg_num_rows($res_produto);
		}

		if (strlen($qtde_produto) == 0) {
			$qtde_produto = 1;
		}
			for ( $i = 1 ; $i <= $qtde_produto ; $i++ ) {

				if (strlen($msg_erro)>0) {
					$serie					= $_POST['serie_'.$i];
					$produto_referencia		= $_POST['produto_referencia_'.$i];
					$produto_nome			= $_POST['produto_nome_'.$i];
					$defeito_reclamado		= $_POST['defeito_reclamado_'.$i];
				}
				else {
					if (strlen($callcenter)>0) {
						$serie					= pg_fetch_result($res_produto,$i-1,serie);
						$produto_referencia		= pg_fetch_result($res_produto,$i-1,referencia);
						$produto_nome			= pg_fetch_result($res_produto,$i-1,descricao);
						$defeito_reclamado		= pg_fetch_result($res_produto,$i-1,defeito_reclamado);
					}
				}
		?>
		<tr>
			<td align='left'><strong>Série:</strong></td>
			<td align='left'>
				<input type="hidden" name="serie_anterior_<?=$i?>" value="<?php echo $serie; ?>" />
				<input name="serie_<?=$i;?>" id="serie_<?=$i;?>" maxlength="20" class="<?php echo ($login_fabrica==24) ? 'input_req' : 'input' ; ?>" value="<?php echo $serie;?>" /><img src='imagens/lupa.png' border='0' align='absmiddle'
				style='cursor: pointer'
				onclick="javascript: fnc_pesquisa_serie (document.frm_callcenter.produto_referencia_<?=$i;?>,document.frm_callcenter.produto_nome_<?=$i;?>,'serie',document.frm_callcenter.mapa_linha,document.frm_callcenter.serie_<?=$i;?>)">
			</td>
			<td align='left'><strong>Referência:</strong></td>
			<td align='left'>
				<input type="hidden" name="produto_referencia_anterior_<?=$i?>" value="<?php echo $produto_referencia;  ?>" />
				<input name="produto_referencia_<?=$i?>"  class="input"  value='<? echo $produto_referencia ;?>'
				onblur="javascript: fnc_pesquisa_produto2 (document.frm_callcenter.produto_referencia_<?=$i;?>,document.frm_callcenter.produto_nome_<?=$i;?>,'referencia',document.frm_callcenter.mapa_linha); <?php  if ($login_fabrica <> 51){ # HD 41923 ?>
					mostraDefeitos('Reclamado',document.frm_callcenter.produto_referencia_<?=$i;?>.value);
					<?php } ?>
					atualizaQuadroMapas();" type="text" size="15" maxlength="15"><img src='imagens/lupa.png' border='0' align='absmiddle'
				style='cursor: pointer'
				onclick="javascript: fnc_pesquisa_produto2 (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao',document.frm_callcenter.mapa_linha)">
			</td>
			<td align='left'><strong>Descrição:</strong></td>
			<td align='left'>
				<input type="hidden" name="produto_nome_anterior_<?=$i?>" value="<?php echo $produto_nome; ?>" />
				<input type='hidden' name='produto_<?=$i?>' value="<? echo $produto; ?>">
				<input name="produto_nome_<?=$i?>"  size='20' class="input" value='<?php echo $produto_nome ;?>'
				<? if ($login_fabrica <> 52) { ?> onblur="javascript: fnc_pesquisa_produto2 (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao',document.frm_callcenter.mapa_linha); <?php }if ($login_fabrica <> 51){ ?>
				mostraDefeitos('Reclamado',document.frm_callcenter.produto_referencia_<?=$i;?>.value);
				<?php } ?>
				atualizaQuadroMapas();" type="text" size="35" maxlength="500"><img src='imagens/lupa.png' border='0' align='absmiddle'
				style='cursor: pointer'
				onclick="javascript: fnc_pesquisa_produto2 (document.frm_callcenter.produto_referencia_<?=$i;?>,document.frm_callcenter.produto_nome_<?=$i;?>,'descricao',document.frm_callcenter.mapa_linha)">
			</td>
			<td align='left'>
				<strong>Defeito Reclamado</strong>
			</td>
			<td><? ;?>
				<select class='input' name='defeito_reclamado_<?=$i?>' id='defeito_reclamado_<?=$i?>'>
					<option> </option>
					<?php
					$sqldef = "SELECT distinct tbl_defeito_reclamado.descricao,
								tbl_defeito_reclamado.defeito_reclamado
								FROM tbl_diagnostico
								JOIN tbl_defeito_reclamado on tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
								JOIN tbl_produto on tbl_diagnostico.linha = tbl_produto.linha and tbl_diagnostico.familia = tbl_produto.familia
								WHERE tbl_diagnostico.fabrica = $login_fabrica
								AND tbl_diagnostico.ativo is true
								ORDER BY tbl_defeito_reclamado.descricao";

					$resdef = pg_query($con,$sqldef);
					if (pg_num_rows($resdef)>0) {
							for ($w=0;$w<pg_num_rows($resdef);$w++) {
							unset($selected);
							$xdefeito_reclamado = pg_fetch_result($resdef,$w,defeito_reclamado);
							$descricao         = pg_fetch_result($resdef,$w,descricao);
							$descricao = substr($descricao,0,30);

							if ($defeito_reclamado == $xdefeito_reclamado) {
								$selected = "SELECTED";
							}

							echo "<option value='$xdefeito_reclamado' $selected> $descricao</option>";
						}
					}
				?>
			</select>
			</td>
			<td>
				<input type='button' name='addlinha' value='+' onclick='function1(<?=$i?>)'>
			</td>
		</tr>
		<? }?>
		<INPUT TYPE='hidden' NAME='qtde_produto' value='<? echo $i= $i-1;?>' id='qtde_produto'>
		</tbody>
	</table>
	<?php
	}
	if($login_fabrica <> 3){ //HD 40086 ?>
	<table width="100%" border='0'>
		<tr>
			<td align='left'><strong>Mapa da Rede</strong></td>
		</tr>
	</table>
	<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
		<tr>
			<td align='left' width='50'><strong>Linha:</strong></td>
			<td align='left'>
			<?
			$sql = "SELECT  *
					FROM    tbl_linha
					WHERE   tbl_linha.fabrica = $login_fabrica
					  AND   tbl_linha.ativo 
					ORDER BY tbl_linha.nome;";
			$res = pg_query ($con,$sql);

			if (pg_num_rows($res) > 0) {
				echo "<select name='mapa_linha' id='mapa_linha' class='frm'>\n";
				echo "<option value=''>ESCOLHA</option>\n";
				for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
					$aux_linha = trim(pg_fetch_result($res,$x,linha));
					$aux_nome  = trim(pg_fetch_result($res,$x,nome));

					echo "<option value='$aux_linha'";
					if ($linha == $aux_linha){
						echo " SELECTED ";
						$mostraMsgLinha = "<br> da LINHA $aux_nome";
					}
					echo ">$aux_nome</option>\n";
				}
				echo "</select>\n&nbsp;";
			}
			?>
			</td>
			<td align='left' width='50'><strong>Estado:</strong></td>
			<td align='left'>
				<select name='mapa_estado' id='mapa_estado'>
					<option value='00' selected>Todos</option>
					<? if ($login_fabrica == 5) {?>
						<option value='SUL'        >Sul</option>
						<option value='SP-capital' >São Paulo - Capital</option>
						<option value='SP-interior'>São Paulo - Interior</option>
						<option value='RJ'         >Rio de Janeiro</option>
						<option value='MG'         >Minas Gerais</option>
						<option value='PE'         >Pernambuco</option>
						<option value='BA'         >Bahia</option>
						<option value='BR-NEES'    >Nordeste + E.S.</option>
						<option value='BR-NCO'     >Norte + C.O.</option>
					<? } else {?>
						<option value='AC'>Acre</option>
						<option value='AL'>Alagoas</option>
						<option value='AP'>Amapá</option>
						<option value='AM'>Amazonas</option>
						<option value='BA'>Bahia</option>
						<option value='CE'>Ceará</option>
						<option value='DF'>Distrito Federal</option>
						<option value='GO'>Goiás</option>
						<option value='ES'>Espírito Santo</option>
						<option value='MA'>Maranhão</option>
						<option value='MT'>Mato Grosso</option>
						<option value='MS'>Mato Grosso do Sul</option>
						<option value='MG'>Minas Gerais</option>
						<option value='PA'>Pará</option>
						<option value='PB'>Paraiba</option>
						<option value='PR'>Paraná</option>
						<option value='PE'>Pernambuco</option>
						<option value='PI'>Piauí</option>
						<option value='RJ'>Rio de Janeiro</option>
						<option value='RN'>Rio Grande do Norte</option>
						<option value='RS'>Rio Grande do Sul</option>
						<option value='RO'>Rondônia</option>
						<option value='RR'>Roraima</option>
						<option value='SP'>São Paulo</option>
						<option value='SC'>Santa Catarina</option>
						<option value='SE'>Sergipe</option>
						<option value='TO'>Tocantins</option>
						<option value='BR-N'>Região Norte</option>
						<option value='BR-NE'>Região Nordeste</option>
						<option value='BR-CO'>Região Centro-Oeste</option>
						<option value='BR-SE'>Região Sudeste</option>
						<option value='BR-S'>Região Sul</option>
					<? }?>
				</select>
			<td align='left' width='50'><strong>Cidade:</strong></td>
			<td align='left'><input type='text' id='mapa_cidade' name='mapa_cidade' value='<?=$mapa_cidade?>'>

				<input type='button' name='btn_mapa' value='mapa' onclick='javascript:mapa_rede(mapa_linha,mapa_estado,mapa_cidade,cep,consumidor_endereco,consumidor_numero,consumidor_bairro,consumidor_cidade,consumidor_estado)'>
				</font>
			</td>
		</tr>
			<tr>
				<td align='left'><strong>Código:</strong></td>
				<td align='left'>
					<input name="codigo_posto_tab" id="codigo_posto_tab"  class="input" value='<?echo $codigo_posto_tab;?>'  type="text" size="15" maxlength="15">
				</td>
				<td align='left'><strong>Nome:</strong></td>
				<td align='left'>
					<input type='hidden' name='posto_tab' id='posto_tab' value="<? echo $posto_tab; ?>">
					<input name="posto_nome_tab" id="posto_nome_tab"  class="input" value='<?echo $posto_nome_tab ;?>'  type="text" size="35" maxlength="150">
				</td>
				<?
				if ($login_fabrica == 52) {
				?>
				<td align='left'><strong>Distancia Km(ida/volta):</strong></td>
				<td align='left'><input type='text' name='posto_km_tab' class="input" value='<?echo $posto_km_tab?>' maxlength="5"></td>
				<? } ?>
			</tr>
		<tr>
			<tr>
				<td align='left'><strong>Telefone:</strong></td>
				<td align='left'>
					<input name="posto_fone_tab" id="posto_fone_tab"  class="input" value='<?echo $posto_fone_tab;?>'  type="text" size="15" maxlength="15">
				</td>
				<td align='left'><strong>E-mail:</strong></td>
				<td align='left'>
					<input name="posto_email_tab" id="posto_email_tab"  class="input" value='<?echo $posto_email_tab ;?>'  type="text" size="35" maxlength="50">
				</td>
				<?
				if ($login_fabrica == 52) {
				?>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<? } ?>
			</tr>
		<tr>
			<td colspan='6'>
			<?
			//HD 211895: Habilitar abertura de pré-os em um chamado após o mesmo já estar em aberto
			if (strlen($callcenter) > 0) {
				$sql = "SELECT abre_os FROM tbl_hd_chamado_extra WHERE hd_chamado=$callcenter";
				$res_abre_os = pg_query($con, $sql);
				$xabre_os = pg_result($res_abre_os, 0, abre_os);

				if ($xabre_os == 't') {
					$disabled = "disabled";
					$checked = "checked";
				}
				else {
					if (isset($abre_os_submeteu)) {
						if ($abre_os == 't') {
							$checked = "checked";
						}
						else {
							$abre_os = $xabre_os;
						}
					}
				}
			}
			else {
				if (isset($abre_os_submeteu)) {
					if ($abre_os == 't') {
						$checked = "checked";
					}
					else {
					}
				}
				elseif ($login_fabrica == 52) {
					$checked = "checked";
				}
			}

			//HD 205933: Habilitar abertura de ORDEM DE SERVIÇO para a Esmaltec
			if (strlen($callcenter) > 0 && $login_fabrica == 30) {
				$sql = "SELECT os FROM tbl_hd_chamado_extra WHERE hd_chamado=$callcenter";
				$res_abre_ordem_servico = pg_query($con, $sql);

				if (pg_num_rows($res_abre_ordem_servico)) {
					$xabre_ordem_servico = pg_result($res_abre_ordem_servico, 0, os);
				}
				else {
					$xabre_ordem_servico = false;
				}

				if ($xabre_ordem_servico) {
					$disabled = "disabled";
					$checked_abre_ordem_servico = "checked";
				}
				else {
					if (isset($abre_ordem_servico_submeteu)) {
						if ($abre_ordem_servico == 't') {
							$checked_abre_ordem_servico = "checked";
						}
						else {
							if ($xabre_ordem_servico) {
								$abre_ordem_servico = 't';
							}
							else {
								$abre_ordem_servico = "";
							}
						}
					}
				}
			}
			else {
				if (isset($abre_ordem_servico_submeteu)) {
					if ($abre_ordem_servico == 't') {
						$checked_abre_ordem_servico = "checked";
					}
					else {
					}
				}
				elseif ($login_fabrica == 52) {
					$checked_abre_ordem_servico = "checked";
				}
			}

			echo "<tr><td align='left' colspan='6'>";
			if($login_fabrica == 30){
				echo "";
			}else{
				echo "<strong><input type=hidden name='abre_os_submeteu' value='sim'><input $disabled type='checkbox' name='abre_os' id='abre_os' value='t' onClick='verificarImpressao(this)' $checked> Abrir PRE-OS para o esta Autorizada</strong>";
			}

			//HD 205933: Habilitar abertura de ORDEM DE SERVIÇO para a Esmaltec
			if ($login_fabrica == 30) {
				echo "<br><strong><input type=hidden name='abre_ordem_servico_submeteu' value='sim'><input $disabled type='checkbox' name='abre_ordem_servico' id='abre_ordem_servico' value='t' onClick='verificarImpressao(this)' $checked_abre_ordem_servico> Abrir ORDEM DE SERVIÇO para o esta Autorizada</strong>";
				if ($os) {
					$sql = "SELECT sua_os FROM tbl_os WHERE os=$os";
					$res = pg_query($con, $sql);
					$abre_os_sua_os = pg_result($res, 0, sua_os);

					echo " <a href='os_press.php?os=$os' target='_blank'>$abre_os_sua_os</a>";
				}
			}

			echo "<div id='imprimir_os' style='display:$display'><strong>&nbsp;&nbsp;&nbsp;&nbsp;<input type='checkbox' name='imprimir_os' value='t'> Imprimir OS</strong></div>";
			echo "</td></tr>";
			?>
			</td>
		</tr>
		</table>
	<? } ?>
	
	<br>

	<div rel='div_ajuda' style='display:inline; Position:relative;'>
		<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
		<tr>
			<td align='right' width='150'></td>
			<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
			<td align='center'><STRONG><?echo$consumidor_nome;?></STRONG><BR>
			em que posso ajudá-lo?
			</td>
			<td align='right' width='150'></td>
		</tr>
		</table>
	</div>
	</td>
</tr>
<tr>
	<td align='left'>
	    <br>
		<?
			if(strlen($callcenter)>0) {
				$tab_atual = $natureza_chamado;
			}
			elseif (strlen($tab_atual) == 0) {
				$tab_atual = "reclamacao_produto";
			}
		?>
		<input type='hidden' name='tab_atual' id='tab_atual' value='<? echo $tab_atual; ?>' >
	<div id="container-Principal">

	<ul>
		<?if($login_fabrica==25){ ?>
		<li>
			<a href="#extensao" onclick="javascript:$('#tab_atual').val('extensao')">
			<span><img src='imagens/garantia_estendida.png' width='10' align="absmiddle">Garantia</span>
			</a>
		</li>
		<?}?>
		<li>
			<a href="#reclamacao_produto" onclick="javascript:$('#tab_atual').val('reclamacao_produto');">
			<span>
			<!--<img src='imagens/rec_produto.png' width='10' align="absmiddle" alt='Reclamao Produto/Defeito'>-->Produto/Defeito</span>
			</a>
		</li>
		<?PHP if($login_fabrica != 2) {?>
		<li>
			<a href="#reclamacao_empresa" onclick="javascript:$('#tab_atual').val('reclamacao_empresa')">
			<span><!--<img src='imagens/rec_empresa.png' width='10' align="absmiddle" alt='Reclamao Empresa'>-->
			<?
				if ($login_fabrica == 24) {
					echo "Empresa";
				}
				else {
					echo "Recl. Empresa";
				}
			?>
			</span>
			</a>
		</li>
		<li>

		<?
			if ($login_fabrica == 11 and strlen($tipo_reclamacao) > 0){
				if(in_array($tipo_reclamacao, $sub_tipo_reclamacao) ) {
					$tab_atual = 'reclamacao_at';
				}
			}
		?>

			<a href="#reclamacao_at" onclick="javascript:$('#tab_atual').val('reclamacao_at')">
			<span><!--<img src='imagens/rec_empresa.png' width='10' align="absmiddle" alt='Reclamao Assistncia Tcnica'>-->
			<? echo ($login_fabrica==11) ? "A.T." : "Recl. A.T."; ?>
			</span>
			</a>
		</li>
		<?PHP }
		if ($login_fabrica == 2) {
			if ($tab_atual == 'reclamacao_at'){// or $tab_atual == 'reclamacao_produto') {
				$tab_atual = "reclamacoes";
			}
		?>
		<li>
			<a href="#reclamacoes" onclick="javascript:$('#tab_atual').val('reclamacoes')">
			<span><!--<img src='imagens/rec_empresa.png' width='10' align="absmiddle" alt='Reclamao Assistncia Tcnica'>-->Reclamações</span>
			</a>
		</li>
		<?PHP
		}
		?>
		<li>
			<a href="#duvida_produto" onclick="javascript:$('#tab_atual').val('duvida_produto')">
			<span><!--<img src='imagens/duv_produto.png' width='10' align=absmiddle>-->Dúvida Prod.</span>
			</a>
		</li>
		<li>
			<a href="#sugestao" onclick="javascript:$('#tab_atual').val('sugestao')">
			<span><!--<img src='imagens/sugestao_call.png' width='10' align=absmiddle>-->Sugestão</span>
			</a>
		</li>
		<?if($login_fabrica != 59 ){?>
			<?	if($login_fabrica==11) {
					if($natureza_chamado2 == 'reclamacao_at_procon') {
							$tab_atual = 'procon';
						}
				}
			?>
		<li>
			<a href="#procon" onclick="javascript:$('#tab_atual').val('procon');">
			<span><!--<img src='imagens/lupa.png' width='10' align=absmiddle>-->Procon/Jec.</span>
			</a>
		</li>
		<?}?>
		<li>
			<a href="#onde_comprar" onclick="javascript:$('#tab_atual').val('onde_comprar');">
			<span><!--<img src='imagens/lupa.png' width='10' align=absmiddle>-->Onde Comprar</span>
			</a>
		</li>
		<?if($login_fabrica==45 ){?>
		<br>
		<li>
			<a href="#garantia" onclick="javascript:$('#tab_atual').val('garantia')">
			<span><!--<img src='imagens/garantia_estendida.png' width='10' align="absmiddle">-->Garantia</span>
			</a>
		</li>
		<?}?>
		<?if($login_fabrica==1 and strlen($callcenter) > 0){?>
		<li>
			<a href="#hd_posto" onclick="javascript:$('#tab_atual').val('hd_posto')">
			<span><!--<img src='imagens/garantia_estendida.png' width='10' align="absmiddle">-->HD Posto</span>
			</a>
		</li>
		<?}?>
		<?if($login_fabrica==59 or $login_fabrica == 81){?>
		<li>
			<a href="#ressarcimento" onclick="javascript:$('#tab_atual').val('ressarcimento');validaOsRessarcimento();">
			<span><!--<img src='imagens/garantia_estendida.png' width='10' align="absmiddle">-->Ressarcimento</span>
			</a>
		</li>
			<?if($login_fabrica <> 81){?>
		<li>
			<a href="#sedex_reverso" onclick="javascript:$('#tab_atual').val('sedex_reverso')">
			<span><!--<img src='imagens/garantia_estendida.png' width='10' align="absmiddle">-->Sedex Reverso</span>
			</a>
		</li>

		<?	}
		}?>
		<?if(1 == 2 /* $login_fabrica==46 OR $login_fabrica == 11 Samuel Tirou esta aba, Troca de Produto  somente permitido na OS, no pode ser feita no call-center*/ ){?>
		<li>
			<a href="#troca_produto" onclick="javascript:$('#tab_atual').val('troca_produto')">
			<span><!--<img src='imagens/rec_empresa.png' width='10' align="absmiddle">-->Troca Prod.</span>
			</a>
		</li>
		<?}?>
		<?if($login_fabrica==24 ){?>
		<li>
			<a href="#outros_assuntos" onclick="javascript:$('#tab_atual').val('outros_assuntos')">
			<span><!--<img src='imagens/garantia_estendida.png' width='10' align="absmiddle">-->Outros Assuntos</span>
			</a>
		</li>
		<?}?>
	</ul>


	<?if($login_fabrica==25){?>
		<div id="extensao" class='tab_content'>

			<div rel='div_ajuda' style='display:inline; Position:relative;'>
			<? if(strlen($callcenter)==0){ ?>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>Oferecer Garantia Estendida.</STRONG><BR>
						O Sr.(a) gostaria de cadastrar a garantia estendida do seu produto?
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>
				<?} ?>
			Informações do Produto

			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left'><strong>Referência:</strong></td>
				<td align='left'>
					<input name="produto_referencia_es" id="produto_referencia_es"  class="input"  value='<?echo $produto_referencia ;?>'
					onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_es,document.frm_callcenter.produto_nome_es,'referencia')" type="text" size="10" maxlength="15">
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer'
					onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao')">
				</td>
				<td align='left'><strong>Descrição:</strong></td>
				<td align='left'>
					<input type='hidden' name='produto' value="<? echo $produto; ?>">
					<input name="produto_nome_es"  id="produto_nome_es"  class="input" value='<?echo $produto_nome ;?>'
					onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_es,document.frm_callcenter.produto_nome_es,'descricao')" type="text" size="30" maxlength="500">
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer'
					onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia,document.frm_callcenter.produto_nome,'descricao')">
				</td>
				<td align='left'><strong>Série:</strong></td>
				<td align='left'>
					<input name="serie_es" id='serie_es' maxlength="20" class="input"  value='<?echo $serie ;?>'>
				</td>
				<td align='left'> <?if(strlen($callcenter)==0){?>
				<INPUT TYPE="button" onClick='javascritp:fn_verifica_garantia();' name='Verificar' value='Verificar'>
				<?}?>
				</td>
			</tr>
			<tr>
				<td colspan='7'>
					<div id='div_estendida'>
					<? if(strlen($callcenter)>0){
							if(strlen($serie)>0){
								include "conexao_hbtech.php";

								$sql = "SELECT idNumeroSerie  ,
												idGarantia     ,
												revenda        ,
												cnpj
										FROM numero_serie
										WHERE numero = '$serie'";
								$res = mysql_query($sql) or die("Erro no Sql:".mysql_error());

								if(mysql_num_rows($res)>0){
									$idNumeroSerie = mysql_result($res,0,idNumeroSerie);
									$idGarantia    = mysql_result($res,0,idGarantia);
									$es_revenda    = mysql_result($res,0,revenda);
									$es_cnpj       = mysql_result($res,0,cnpj);

									if(strlen($idGarantia)>0){
										$sql = "SELECT	nf                ,
														dataCompra        ,
														municipioCompra   ,
														estadoCompra      ,
														dataNascimento    ,
														estadoCivil       ,
														filhos            ,
														sexo              ,
														dddComercial      ,
														foneComercial     ,
														dddCelular        ,
														foneCelular       ,
														prefMusical
												FROM garantia
												WHERE idGarantia = $idGarantia;
											";
										$res = mysql_query($sql) or die("Erro no Sql:".mysql_error());

										if(mysql_num_rows($res)>0){
											$es_nf                    = mysql_result($res,0,nf);
											$es_dataCompra            = mysql_result($res,0,dataCompra);
											$es_municipioCompra       = mysql_result($res,0,municipioCompra);
											$es_estadoCompra          = mysql_result($res,0,estadoCompra);
											$es_dataNascimento        = mysql_result($res,0,dataNascimento);
											$es_estadoCivil           = mysql_result($res,0,estadoCivil);
											$es_filhos                = mysql_result($res,0,filhos);
											$es_sexo                  = mysql_result($res,0,sexo);
											$es_dddComercial          = mysql_result($res,0,dddComercial);
											$es_foneComercial         = mysql_result($res,0,foneComercial);

											$es_telComercial  = "($es_dddComercial) $es_foneComercial";

											$es_dddCelular            = mysql_result($res,0,dddCelular);
											$es_foneCelular           = mysql_result($res,0,foneCelular);
											$es_prefMusical           = mysql_result($res,0,prefMusical);
											$es_telCelular  = "($es_dddCelular) $es_foneCelular";

											$es_dataCompra = converte_data($es_dataCompra);
											$es_dataCompra = str_replace("-","/",$es_dataCompra);

											$es_dataNascimento = converte_data($es_dataNascimento);
											$es_dataNascimento = str_replace("-","/",$es_dataNascimento);

										}

										echo "<input name='es_id_numeroserie' id='es_id_numeroserie' value='$idNumeroSerie' type='hidden'>";
										echo "<table width='100%' border='0' align='center' cellpadding='2' cellspacing='2' style=' font-size:10px'>";
										echo "<tr>";
											echo "<td><B>Cnpj Revenda:</B></td>";
											echo "<td><input name='es_revenda_cnpj' id='es_revenda_cnpj' class='input' value='$es_cnpj' type='text' maxlength='14' size='15' readonly></td>";
											echo "<td><B>Nome Revenda:</B></td>";
											echo "<td><input name='es_revenda' id='es_revenda' class='input' value='$es_revenda' type='text' maxlength='50' size='25' readonly></td>";
											echo "<td><B>Nota Fiscal:</B></td>";
											echo "<td><input name='es_nota_fiscal' id='es_nota_fiscal' class='input' value='$es_nf' type='text' maxlength='8' size='8'> </td>";
										echo "</tr>";

										echo "<tr>";
											echo "<td><B>Data Compra:</B></td>";
											echo "<td><input name='es_data_compra' id='es_data_compra' class='input' value='$es_dataCompra' type='text' maxlength='10' size='12'></td>";
											echo "<td><B>Municipio Compra:</B></td>";
											echo "<td><input name='es_municipiocompra' id='es_municipiocompra' class='input' value='$es_municipioCompra' type='text' maxlength='255' size='25'></td>";
											echo "<td><B>Estado Compra:</B></td>";
											echo "<td>";
											echo "<select name='es_estadocompra' id='es_estadocompra' style='width:52px; font-size:9px' >";
											 $ArrayEstados = array('AC','AL','AM','AP',
																		'BA','CE','DF','ES',
																		'GO','MA','MG','MS',
																		'MT','PA','PB','PE',
																		'PI','PR','RJ','RN',
																		'RO','RR','RS','SC',
																		'SE','SP','TO'
																	);
											for ($i=0; $i<=26; $i++){
												echo"<option value='".$ArrayEstados[$i]."'";
												if ($es_estadoCompra == $ArrayEstados[$i]) echo " selected";
												echo ">".$ArrayEstados[$i]."</option>\n";
											}
											echo "</select>";
											echo "</td>";
										echo "</tr>";

										echo "<tr>";
											echo "<td><B>Data Nascimento:</B></td>";
											echo "<td><input name='es_data_nascimento' id='es_data_nascimento' class='input' value='$es_dataNascimento' type='text' maxlength='10' size='12'></td>";
											echo "<td><B>Estado Civil:</B></td>";
											echo "<td>";
											echo "<select name='es_estadocivil' id='es_estadocivil' style='width:100px; font-size:9px' >";
											echo "<option value=''></option>";
											echo "<option value='0' ";
											if($es_estadoCivil=="0")echo "SELECTED";
											echo ">Solteiro(a)</option>";
											echo "<option value='1' ";
											if($es_estadoCivil=="1")echo "SELECTED";
											echo ">Casado(a)</option>";
											echo "<option value='2' ";
											if($es_estadoCivil=="2")echo "SELECTED";
											echo ">Divorciado(a)</option>";
											echo "<option value='3' ";
											if($es_estadoCivil=="3")echo "SELECTED";
											echo ">Viuvo(a)</option>";
											echo "</select>";
											echo "</td>";
											echo "<td><B>Sexo:</B></td>";
											echo "<td>";
											echo "<INPUT TYPE='radio' NAME='es_sexo' ";
											if($es_sexo == "0") echo "CHECKED ";
											echo "value='0'>M. ";
											echo "<INPUT TYPE='radio' NAME='es_sexo' ";
											if($es_sexo == "1") echo "CHECKED ";
											echo " value='1'>F. ";
											echo "</td>";
										echo "</tr>";

										echo "<tr>";
											echo "<td><B>Filhos:</B></td>";
											echo "<td>";
											echo "<INPUT TYPE='radio' NAME='es_filhos' ";
											if($es_filhos == "0") echo "CHECKED ";
											echo "value='0'>Sim ";
											echo "<INPUT TYPE='radio' NAME='es_filhos' ";
											if($es_filhos == "1") echo "CHECKED ";
											echo "value='1'>No ";
											echo "</td>";
											echo "<td><B>Fone Comercial:</B></td>";
											echo "<td><input name='es_fonecomercial' id='es_fonecomercial' class='input' value='$es_telComercial' type='text' maxlength='14' size='16'></td>";

											echo "<td><B>Celular:</B></td>";
											echo "<td>";
											echo "<input name='es_celular' id='es_celular' class='input' value='$es_telCelular' type='text' maxlength='14' size='16'>";
											echo "</td>";
										echo "</tr>";

										echo "<tr>";
											echo "<td colspan='6'><B>Preferência Musical:</B> ";
											echo "<input name='es_preferenciamusical' id='es_preferenciamusical' class='input' value='$es_prefMusical' type='text' maxlength='255' size='100'>";
											echo "</td>";
										echo "</tr>";

										echo "</table>";
									}
								}else{
									echo "Número de série não encontrado nas vendas";
								}
							}
						}
					?>
					</div>
				</td>
			</tr>
			<tr>
				<td align='left'><strong>Descrição:</strong></td>
				<td colspan='6'>
				<TEXTAREA NAME="reclamado_es" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
				</div>
				</td>
			</tr>
			</table>
			</div>
		</div>
	<? } ?>
	<? if ($login_fabrica == 5 and strlen($callcenter) > 0) { // hd 58796
			$read = " readonly='readonly' ";
	}?>

	<div id="reclamacao_produto" class='tab_content'>
			<div rel='div_ajuda' style='display:inline; Position:relative;'>
			<!-- Incluído solicitação de TIPO DE PRODUTO/DEFEITO HD 173649 -->
			<? if($login_fabrica==2){ ?>
				Tipo da Produro/Defeito
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
					<tr>
						<td align='left' class="padding" width='50%' nowrap>
							<input type="radio" name="tipo_reclamacao" value="aquisicao_mat" 
								<?PHP if ($natureza_chamado2 == 'aquisicao_mat') {echo "CHECKED";}?>> AQUISIÇÃO DE MATERIAIS
						</td>
						<td align='left' class="padding" width='50%' nowrap>
							<input type="radio" name="tipo_reclamacao" value="aquisicao_prod" 
								<?PHP if ($natureza_chamado2 == 'aquisicao_prod') { echo "CHECKED";}?>>AQUISIÇÃO DE PRODUTO
						</td>
					</tr>
					<tr>
						<td align='left' class="padding" width='50%' nowrap>
							<input type="radio" name="tipo_reclamacao" value="indicacao_posto" 
								<?PHP if ($natureza_chamado2 == 'indicacao_posto') { echo "CHECKED";}?>>INDICAÇÃO DE POSTO
						</td>
						<td align='left' class="padding" width='50%' nowrap>
							<input type="radio" name="tipo_reclamacao" value="solicitacao_manual" 
								<?PHP if ($natureza_chamado2 == 'solicitacao_manual') { echo "CHECKED";}?>>SOLICITAÇÕES DE MANUAIS / CATÁLOGOS
						</td>
					</tr>
				</table>
			<!-- FIM solicitação de TIPO DE PRODUTO/DEFEITO HD 173649 -->
			<? }else{?>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>Confirmar ou perguntar a reclamação.</STRONG><BR>
						<?
						$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='3' AND fabrica = $login_fabrica";
						$pe = pg_query($con,$sql);
						if(pg_num_rows($pe)>0) {
							echo pg_fetch_result($pe,0,0);
						}else{
							echo ($login_fabrica==11) ? "Qual a sua solicitação SR.(a)?<BR>" : "Qual a sua reclamação SR.(a)?<BR>";?> ou<BR> O Sr.(a) diz que...., correto?
						<?}?>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>
			<?}?>
			</div>

			Informações do Produto
			
			<?
			//HD 235203: Alterar assuntos Fale Conosco e CallCenter
			//			 O array $assuntos é definido dentro do arquivo callcenter_suggar_assuntos.php
			//			 que está sendo incluído no começo deste arquivo

			if ($login_fabrica == 24) {
			?>
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td>
				Assunto: <select name="callcenter_assunto[reclamacao_produto]" id="callcenter_assunto" class="input_req">
				<option value=''>>>> ESCOLHA <<<</option>

				<?php

				foreach($assuntos["PRODUTOS"] AS $label => $valor) {
					if ($valor == $callcenter_assunto) {
						$selected = "selected";
					}
					else {
						$selected = "";
					}

					echo "
					<option value='$valor' $selected>PRODUTOS >> $label</option>";
				}

				foreach($assuntos["MANUAL"] AS $label => $valor) {
					if ($valor == $callcenter_assunto) {
						$selected = "selected";
					}
					else {
						$selected = "";
					}

					echo "
					<option value='$valor' $selected>MANUAL >> $label</option>";
				}

				?>
				</select>
				</td>
			</tr>
			</table>
			<?
			}
			?>
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td>
				<?php
				//HD 201434 - Verifica como a fabrica trabalha com defeito reclamado na configuracao da tbl_fabrica e 
				//			  trata devidamente para mostrar o tipo de campo correto
				$sql_defeito = "
				SELECT
				pedir_defeito_reclamado_descricao
				
				FROM
				tbl_fabrica
				
				WHERE
				fabrica=$login_fabrica
				AND pedir_defeito_reclamado_descricao IS TRUE";
				$res_defeito_reclamado = pg_query($con, $sql_defeito);

				//HD 219939: Para a Cadence deverá selecionar o defeito reclamado no CallCenter e digitar na OS
				if (pg_num_rows($res_defeito_reclamado) && $login_fabrica <> 35){
					echo "<strong>Defeitos</strong>";
					echo "</td>";
					echo "<td align='left'><input name='hd_extra_defeito' id='hd_extra_defeito' size='50' class='input' value='$hd_extra_defeito'>";
					echo "</td>";
				}else{
					if ($login_fabrica <> 52) { ?>
					<a href="javascript:mostraDefeitos('Reclamado',document.frm_callcenter.produto_referencia.value)">Defeitos</a>
					</td>
					<td align='left' colspan='5' width='630' valign='top'>
						<div id='div_defeitos' style='display:inline; Position:relative;background-color: #e6eef7;width:100%'>
						<?   if(strlen($defeito_reclamado)>0){
								$sql = "SELECT defeito_reclamado,
												descricao
										FROM tbl_defeito_reclamado
										WHERE defeito_reclamado = $defeito_reclamado";
								$res = pg_query($con,$sql);
								if(pg_num_rows($res)>0){
									$defeito_reclamado_descricao = pg_fetch_result($res,0,descricao);
									echo "$defeito_reclamado<input type='radio' checked value='$defeito_reclamado'><font size='1'>$defeito_reclamado_descricao</font>";
								}
							}
						?>
						</div>
				<?php }
							} ?>
				</td>
			</tr>

			<tr>
				<td align='left' valign='top'><strong>Descrição:</strong></td>
				<td align='left' colspan='5'>
					<TEXTAREA NAME="reclamado_produto_x" ROWS="6" COLS="110"  class="input" style='display: none;font-size:10px' <? echo $read; ?>>
					<?
						#94971
						if($_GET['herdar']=='sim' AND $login_fabrica==59){
							$sql2 ="SELECT		tbl_hd_chamado_extra.reclamado
									FROM tbl_hd_chamado
									JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
									LEFT JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
									JOIN tbl_admin  on tbl_hd_chamado.atendente = tbl_admin.admin
									LEFT JOIN tbl_posto on tbl_hd_chamado_extra.posto = tbl_posto.posto
									LEFT JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto  and tbl_posto_fabrica.fabrica = 59
									LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto
									LEFT JOIN tbl_revenda on tbl_revenda.revenda = tbl_hd_chamado_extra.revenda
									LEFT JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
									LEFT JOIN tbl_os on tbl_os.os = tbl_hd_chamado_extra.os
									WHERE tbl_hd_chamado.fabrica_responsavel = 59
									AND tbl_hd_chamado.hd_chamado = $Id";
							$res2 = pg_query($con,$sql2);

							if(pg_num_rows($res2)>0){
								$reclamado2       = pg_fetch_result($res2,0,reclamado);
							}
							echo $reclamado2;
						}
					?>
					</TEXTAREA>
					<TEXTAREA NAME="reclamado_produto" ROWS="6" COLS="110"  class="input" style='font-size:10px' <? echo $read; ?>><?echo $reclamado ;?></TEXTAREA>
				</td>
			</tr>

			<!-- HD 196942: Controle de autorização de postagem -->
			<?
			if ($login_fabrica == 11) {
			?>
			<tr>
				<td align='left' valign='top'><strong>Postagem:</strong></td>
				<td align='left' colspan='5'>
				<?
				
				if (strlen($callcenter)) {
					$sql = "
					SELECT
					TO_CHAR(tbl_hd_chamado_postagem.data, 'DD/MM/YYYY HH24:MI:SS') AS data,
					TO_CHAR(tbl_hd_chamado_postagem.data_aprovacao, 'DD/MM/YYYY HH24:MI:SS') AS data_aprovacao,
					tbl_hd_chamado_postagem.aprovado,
					tbl_hd_chamado_postagem.admin,
					tbl_admin.nome_completo AS admin_nome_completo,
					tbl_admin.login AS admin_login,
					tbl_hd_chamado_postagem.motivo,
					tbl_hd_chamado_postagem.obs,
					tbl_hd_chamado_postagem.codigo_postagem
					
					FROM
					tbl_hd_chamado_postagem
					LEFT JOIN tbl_admin ON tbl_hd_chamado_postagem.admin=tbl_admin.admin
					
					WHERE
					hd_chamado=$callcenter
					";
					$res_postagem = pg_query($con, $sql);
				}
				
				//Verifica se já existe solicitação de postagem cadastrada
				if (strlen($callcenter) && pg_num_rows($res_postagem)) {
					$postagem_aprovado = pg_result($res_postagem, 0, aprovado);
					$postagem_data = pg_result($res_postagem, 0, data);
					$postagem_data_aprovacao = pg_result($res_postagem, 0, data_aprovacao);
					$postagem_admin_nome_completo = pg_result($res_postagem, 0, admin_nome_completo);
					$postagem_motivo = pg_result($res_postagem, 0, motivo);
					$postagem_obs = pg_result($res_postagem, 0, obs);
					$postagem_codigo_postagem = pg_result($res_postagem, 0, codigo_postagem);

					switch($postagem_aprovado) {
						case 't':
							if ($_POST["hd_chamado_codigo_postagem"]) {
								$hd_chamado_codigo_postagem = $_POST["hd_chamado_codigo_postagem"];
							}
							else {
								$hd_chamado_codigo_postagem = $postagem_codigo_postagem;
							}

							if ($postagem_obs) {
								$postagem_obs = "- <u>Observações:</u> $postagem_obs";
							}

							echo "<div style='display:inline; background: #44FF44; padding: 2px;'>Aprovado por $postagem_admin_nome_completo em $postagem_data_aprovacao - <u>Motivo:</u> $postagem_motivo $postagem_obs</div>";

							echo "</td></tr>";
							echo "<tr>
							<td align='left' valign='top'><strong>Código de Postagem:</strong></td>
							<td align='left' colspan='5'>
							<input class='input' type='text' name='hd_chamado_codigo_postagem' id='hd_chamado_codigo_postagem' value='$hd_chamado_codigo_postagem' size='30'>";
						break;

						case 'f':
							if ($postagem_obs) {
								$postagem_obs = "- <u>Observações:</u> $postagem_obs";
							}

							echo "<div style='display:inline; background: #FFAA99; padding: 2px;'>Reprovado por $postagem_admin_nome_completo em $postagem_data_aprovacao - <u>Motivo:</u> $postagem_motivo $postagem_obs</div>";
						break;

						default:
							echo "Em aprovação desde $postagem_data $postagem_aprovado";
					}
				}
				else {
					if ($_POST["hd_chamado_postagem"] == "sim") {
						$checked = "checked";
					}
					else {
						$checked = "";
					}

					echo "<input type='checkbox' name='hd_chamado_postagem' id='hd_chamado_postagem' value='sim' $checked onchange=''> este chamado precisa de postagem";
				}

				?>
				</td>
			</tr>
			<?
			}
			?>
			<!-- FIM - HD 196942: Controle de autorização de postagem -->

			</table>
			
			<?php if($login_fabrica == 85) : // HD 674943 ?>
				<br /><div id="questionario">
					<?php include 'pesquisa_satisfacao.php'; ?>
				</div> <br />
			<?php endif; // HD 674943 - FIM ?>
			
			<?php
				$aEsconderDuvidaProduto = array(2);
			?>
			<?php if ( in_array($login_fabrica, $aEsconderDuvidaProduto) ): ?>
				<div style="display:none">
			<?php endif; ?>
			Consultar FAQs sobre o Produto
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left' width='60'><strong>Dúvida:</strong></td>
				<td align='left'>
					<input name="faq_duvida_produto"  id='faq_duvida_produto' size='50' class="input" value='<?echo $faq_duvida ;?>'>
					<input  id="faq_duvida_produto_btn" class="input"  type="button" name="bt_localizar" value='Localizar' onclick="javascript:localizarFaq(document.frm_callcenter.produto_referencia.value,'faq_duvida_produto')">
				</td>
			</tr>
			<tr>
				<td colspan='2'>
					<div id='div_faq_duvida_produto' style='display:inline; Position:relative;background-color: #e6eef7;width:100%'>
					</div>
				</td>
			</tr>
			</table>
			<?php if ( in_array($login_fabrica, $aEsconderDuvidaProduto) ): ?>
				</div>
			<?php endif; ?>
			<?PHP
				if (1 ==2 /*$login_fabrica != 45 AND $login_fabrica != 3 Samuel retirou isto...a consulta do posto mais prximo  atravs do Mapa da Rede */ ) {
			?>
			Consultar Posto Autorizado
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left' colspan='6'><strong><a href="javascript: fnc_pesquisa_at_proximo('<?echo $login_fabrica?>')" title="Localize o Posto Autorizado" >Clique aqui para consultar o posto autorizado mais próximo do consumidor</a></strong></td>
			</tr>

			</table>
			<?PHP
				}
			?>
		</div>

	<? if($login_fabrica <> 2){ ?>
	
		<div id="reclamacao_empresa" class='tab_content'>
			<div rel='div_ajuda' style='display:inline; Position:relative;'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>Confirmar ou perguntar a reclamação.</STRONG><BR>
						<?
						$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='4' AND fabrica = $login_fabrica";
						$pe = pg_query($con,$sql);
						if(pg_num_rows($pe)>0) {
							echo pg_fetch_result($pe,0,0);
						}else{
							echo "Qual a sua reclamação SR.(a)?<BR>	ou<BR> O Sr.(a) diz que...., correto?";
						}?>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>
			</div>
			<?PHP
			if ($login_fabrica == 2) {
				if ($natureza_chamado2 == 'reclamacao_at') {
					$mostra_reclamacao = "Assitência Técnica";
				} else if ($natureza_chamado2 == 'reclamacao_produto') {
					$mostra_reclamacao = "o Produto";
				} else if ($natureza_chamado2 == 'reclamacao_revenda') {
					$mostra_reclamacao = "a Loja";
				} else if ($natureza_chamado2 == 'reclamacao_enderecos') {
					$mostra_reclamacao = "a Lista de Endereços Desatualizada";
				}
			}
			?>

			<?
				if ($login_fabrica == 24) {
					echo "Informações";
				}
				else {
					echo "Informações da Reclamação";
				}
				
				if ($login_fabrica == 2 and strlen($mostra_reclamacao) > 0) { echo "Sobre $mostra_reclamacao";}
			?>

			<?
			//HD 235203: Alterar assuntos Fale Conosco e CallCenter
			//			 O array $assuntos é definido dentro do arquivo callcenter_suggar_assuntos.php
			//			 que está sendo incluído no começo deste arquivo

			if ($login_fabrica == 24) {
			?>
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td>
				Assunto: <select name="callcenter_assunto[reclamacao_empresa]" id="callcenter_assunto" class="input_req">
				<option value=''>>>> ESCOLHA <<<</option>

				<?php

				foreach($assuntos["EMPRESA"] AS $label => $valor) {
					if ($valor == $callcenter_assunto) {
						$selected = "selected";
					}
					else {
						$selected = "";
					}

					echo "
					<option value='$valor' $selected>EMPRESA >> $label</option>";
				}

				?>
				</select>
				</td>
			</tr>
			</table>
			<?
			}
			?>

			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left' valign='top'><strong>Reclamação:</strong></td>
			    <td align='left' colspan='5'>
				  <TEXTAREA NAME="reclamado_empresa" ROWS="6" COLS="110"  class="input" style='font-size:10px' <? echo $read;?>><?echo $reclamado ;?></TEXTAREA>
				</td>
			</tr>
			</table>

			<BR>

			<div rel='div_ajuda' style='display:inline; Position:relative;'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
					<td align='center'><STRONG>Sr.(a) estou encaminhando a sua reclamação ao Depto. responsável, que responderá em 12 h.
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>
			</div>
		</div>

		<div id="reclamacao_at" class='tab_content'>
			<div rel='div_ajuda' style='display:inline; Position:relative;'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>Confirmar ou perguntar a reclamação.</STRONG><BR>
						<?
						$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='6' AND fabrica = $login_fabrica";
						$pe = pg_query($con,$sql);
						if(pg_num_rows($pe)>0) {
							echo pg_fetch_result($pe,0,0);
						}else{
							echo "Qual a sua reclamação SR.(a)?<BR> ou<BR> O Sr.(a) diz que...., correto?";
						}
					?>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>
			</div>

			Informações da Assistência
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left'><strong>Código:</strong></td>
				<td align='left'>
					<input name="codigo_posto"  class="input"  value='<?echo $codigo_posto ;?>'
					onblur="javascript: fnc_pesquisa_posto (document.frm_callcenter.codigo_posto,document.frm_callcenter.posto_nome,'codigo');" type="text" size="15" maxlength="15">
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer'
					onclick="javascript: fnc_pesquisa_posto (document.frm_callcenter.codigo_posto,document.frm_callcenter.produto_nome,'codigo');">
				</td>
				<td align='left'><strong>Nome:</strong></td>
				<td align='left'>
					<input name="posto_nome"  class="input" value='<?echo $posto_nome ;?>'
					onblur="javascript: fnc_pesquisa_posto (document.frm_callcenter.codigo_posto,document.frm_callcenter.posto_nome,'nome');" type="text" size="35" maxlength="500">
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer'
					onclick="javascript: fnc_pesquisa_posto (document.frm_callcenter.codigo_posto,document.frm_callcenter.posto_nome,'nome');">
				</td>
				<?if ($login_fabrica <> 11) { // HD 14549?>
				<td align='left'><strong>OS:</strong></td>
				<td align='left'>
					<input name="os"  class="input"  value='<?echo $sua_os ;?>'>
				</td>
				<? } ?>
			</tr>
			</table>

			<? if($login_fabrica==11){ ?>
				Tipo da Reclamação
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
				<tr>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="tipo_reclamacao" value="reclamacao_at" <?PHP if ($natureza_chamado2 == 'reclamacao_at' OR $natureza_chamado2 =='') { echo "CHECKED";}?>> RECLAMAÇÃO DA  ASSIST. TÉCN.</td>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="tipo_reclamacao" value="reclamacao_at_info" <?PHP if ($natureza_chamado2 == 'reclamacao_at_info') { echo "CHECKED";}?>>INFORMAÇÕES DE A.T</td>
				</tr>
				<tr>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="tipo_reclamacao" value="mau_atendimento" <?PHP if ($natureza_chamado2 == 'mau_atendimento') { echo "CHECKED";}?>>MAU ATENDIMENTO</td>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="tipo_reclamacao" value="posto_nao_contribui" <?PHP if ($natureza_chamado2 == 'posto_nao_contribui') { echo "CHECKED";}?>>POSTO NÃO CONTRIBUI COM INFORMAÇÕES</td>
				</tr>
				<tr>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="tipo_reclamacao" value="demonstra_desorg" <?PHP if ($natureza_chamado2 == 'demonstra_desorg') { echo "CHECKED";}?>>DEMONSTRA DESORGANIZAÇÃO</td>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="tipo_reclamacao" value="possui_bom_atend" <?PHP if ($natureza_chamado2 == 'possui_bom_atend') { echo "CHECKED";}?>>POSSUI BOM ATENDIMENTO</td>
				</tr>
				<tr>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="tipo_reclamacao" value="demonstra_org" <?PHP if ($natureza_chamado2 == 'demonstra_org') { echo "CHECKED";}?>>DEMONSTRA ORGANIZAÇÃO</td>
				</tr>
				</table>
			<? }
				echo ($login_fabrica==11 || $login_fabrica == 24) ? "Informações" : "Informações da Reclamação";
			?>

			<?
			//HD 235203: Alterar assuntos Fale Conosco e CallCenter
			//			 O array $assuntos é definido dentro do arquivo callcenter_suggar_assuntos.php
			//			 que está sendo incluído no começo deste arquivo

			if ($login_fabrica == 24) {
			?>
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td>
				Assunto: <select name="callcenter_assunto[reclamacao_at]" id="callcenter_assunto" class="input_req">
				<option value=''>>>> ESCOLHA <<<</option>

				<?php

				foreach($assuntos["ASSISTÊNCIA TÉCNICA"] AS $label => $valor) {
					if ($valor == $callcenter_assunto) {
						$selected = "selected";
					}
					else {
						$selected = "";
					}

					echo "
					<option value='$valor' $selected>ASSISTÊNCIA TÉCNICA >> $label</option>";
				}

				?>
				</select>
				</td>
			</tr>
			</table>
			<?
			}
			?>

			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left' valign='top'><strong>Reclamação:</strong></td>
				<td align='left' colspan='5'>
					<TEXTAREA NAME="reclamado_at" ROWS="6" COLS="110"  class="input" style='font-size:10px' <?echo $read;?>><?echo $reclamado ;?></TEXTAREA>
				</td>
			</tr>
			</table>
			<BR>

			<div rel='div_ajuda' style='display:inline; Position:relative;'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>Sr.(a) estou encaminhando a sua reclamação ao Depto. responsável, que responderá em 12 h.
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>
			</div>
		</div>
		<?}?>
		<?PHP
		if ($login_fabrica == 2) {
		?>

		<div id="reclamacoes" class='tab_content'>
			<div rel='div_ajuda' style='display:inline; Position:relative;'>
				Tipo da Reclamação
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
					<tr>
						<td align='left' class="padding" width='50%'><input type="radio" name="tipo_reclamacao" value="reclamacao_revenda" onclick="MudaCampo(this)" <?PHP if ($natureza_chamado2 == 'reclamacao_revenda' OR $natureza_chamado2 == '') { echo "CHECKED";}?>> RECLAMAÇÃO DA LOJA</td>
						<td align='left' class="padding" width='50%'><input type="radio" name="tipo_reclamacao" value="reclamacao_at" onclick="MudaCampo(this)" <?PHP if ($natureza_chamado2 == 'reclamacao_at') { echo "CHECKED";}?>> RECLAMAÇÃO DA ASSIST. TÉCN.</td>
					</tr>
					<tr>
						<td align='left' class="padding" width='50%'><input type="radio" name="tipo_reclamacao" value="reclamacao_enderecos" onclick="MudaCampo(this)"<?PHP if ($natureza_chamado2 == 'reclamacao_enderecos') { echo "CHECKED";}?>> RECL. LISTA ENDEREÇOS DESATUALIZADA </td>
						<td align='left' class="padding" width='50%'><input type="radio" name="tipo_reclamacao" value="reclamacao_produto" onclick="MudaCampo(this)" <?PHP if ($natureza_chamado2 == 'reclamacao_produto') { echo "CHECKED";}?>> RECLAMAÇÃO DO PRODUTO</td>
					</tr>
					<tr>
						<td align='left' class="padding" width='50%'><input type="radio" name="tipo_reclamacao" value="reclamacao_conserto" onclick="MudaCampo(this)"<?PHP if ($natureza_chamado2 == 'reclamacao_conserto') { echo "CHECKED";}?>> RECLAMAÇÃO DE CONSERTOS </td>
						<td align='left' class="padding" width='50%'><input type="radio" name="tipo_reclamacao" value="reclamacao_posto_aut" onclick="MudaCampo(this)" <?PHP if ($natureza_chamado2 == 'reclamacao_posto_aut') { echo "CHECKED";}?>> RECLAMAÇÃO DE POSTOS AUTORIZADOS</td>
					</tr>
					<tr>
						<td align='left' class="padding" width='50%'><input type="radio" name="tipo_reclamacao" value="reclamacao_orgao_ser" onclick="MudaCampo(this)"<?PHP if ($natureza_chamado2 == 'reclamacao_orgao_ser') { echo "CHECKED";}?>> RECLAMAÇÃO DE ÓRGÃO DE SERVIÇO</td>
						<td align='left' class="padding" width='50%'><input type="radio" name="tipo_reclamacao" value="repeticao_chamado" onclick="MudaCampo(this)"<?PHP if ($natureza_chamado2 == 'repeticao_chamado') { echo "CHECKED";}?>> REPETIÇÃO DE CHAMADO</td>
					</tr>
					<tr>
						<td align='left' class="padding" width='50%'><input type="radio" name="tipo_reclamacao" value="reclamacao_outro" onclick="MudaCampo(this)"<?PHP if ($natureza_chamado2 == 'reclamacao_outro') { echo "CHECKED";}?>> OUTRAS RECLAMAÇÕES</td>
					</tr>
				</table>

				<div id="info_posto" style="
				<?php
					echo ($natureza_chamado2 == 'reclamacao_at') ? "display:inline" : "display:none";
				 ?>
					;">
				<br/>
				Informações da Assistência
				<table width='100%' class="tab_content" border='0' align='center' cellpadding="2" cellspacing="2" style='border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
					<tr>
						<td>
							<strong>Código do Posto:&nbsp;</strong>
							<input name="codigo_posto" class="input" value='<?echo $codigo_posto ;?>'
							<?php
								if (strlen($codigo_posto)>0){
									echo " disabled";
								} ?>
								onblur="javascript: fnc_pesquisa_posto (document.frm_callcenter.codigo_posto,document.frm_callcenter.posto_nome,'codigo');" type="text" size="15" maxlength="15"> <img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer' onclick="javascript: fnc_pesquisa_posto (document.frm_callcenter.codigo_posto,document.frm_callcenter.produto_nome,'codigo');">
						</td>
						<td>
							<strong>Nome do Posto:&nbsp;</strong>
							<input name="posto_nome" class="input" value='<?echo $posto_nome ;?>'
							<?php
								if (strlen($posto_nome)>0){
									echo " disabled";
								} ?>
								onblur="javascript: fnc_pesquisa_posto (document.frm_callcenter.codigo_posto,document.frm_callcenter.posto_nome,'nome');" type="text" size="35" maxlength="500"> <img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor: pointer' onclick="javascript: fnc_pesquisa_posto (document.frm_callcenter.codigo_posto,document.frm_callcenter.posto_nome,'nome');">
						</td>
					</tr>
				</table>
				</div>

				<br>
				Informações da Reclamação
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
					<tr>
						<td align='right' width='35%'><strong>Reclamação:</strong></td>
						<td align='center' colspan='5'>
							<TEXTAREA NAME="reclamado" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<?PHP
		}
		?>

		<div id="duvida_produto" class='tab_content'>
			<div rel='div_ajuda' style='display:inline; Position:relative;'>
			<!-- Incluído solicitação de TIPO DE PRODUTO/DEFEITO HD 173649 -->
			<? if($login_fabrica==2){ ?>
				Dúvida dos produtos
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
					<tr>
						<td align='left' class="padding" width='50%' nowrap>
							<input type="radio" name="tipo_reclamacao" value="especificacao_manuseio" 
								<?PHP if ($natureza_chamado2 == 'especificacao_manuseio') {echo "CHECKED";}?>> ESPECIFICAÇÕES DE MANUSEIO
						</td>
						<td align='left' class="padding" width='50%' nowrap>
							<input type="radio" name="tipo_reclamacao" value="informacao_manuseio" 
								<?PHP if ($natureza_chamado2 == 'informacao_manuseio') { echo "CHECKED";}?>>INFORMAÇÃO DE MANUSEIO
						</td>
					</tr>
					<tr>
						<td align='left' class="padding" width='50%' nowrap>
							<input type="radio" name="tipo_reclamacao" value="informacao_tecnica" 
								<?PHP if ($natureza_chamado2 == 'informacao_tecnica') { echo "CHECKED";}?>>INFORMAÇÃO TÉCNICA
						</td>
						<td align='left' class="padding" width='50%' nowrap>
							<input type="radio" name="tipo_reclamacao" value="orientacao_instalacao" 
								<?PHP if ($natureza_chamado2 == 'orientacao_instalacao') { echo "CHECKED";}?>>ORIENTAÇÃO PARA INSTALAÇÃO
						</td>
					</tr>
				</table>
				<!-- FIM solicitação de TIPO DE PRODUTO/DEFEITO HD 173649 -->
			<?}else{?>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>Confirmar ou perguntar a dúvida.</STRONG><BR>
						<?
						$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='7' AND fabrica = $login_fabrica";
						$pe = pg_query($con,$sql);
						if(pg_num_rows($pe)>0) {
							echo pg_fetch_result($pe,0,0);
						}else{
							echo "Qual a sua dúvida SR.(a)?<BR>	ou<BR>A dúvida do Sr.(a) sobre como...., correto?";
						}?>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>
			<?}?>
			<br>
			<br>
			</div>
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td><strong>Dúvida :</strong></td>
				<td align='left' colspan='5'>
					<input name="faq_duvida_duvida"  id="faq_duvida_duvida" class="input" size="74" value="<? echo $faq_duvida ;?>">
					<input  class="input"  type="button" name="bt_localizar" value="Localizar" onclick="javascript:localizarFaq(document.frm_callcenter.produto_referencia.value,'faq_duvida_duvida')">
				</td>
				<? if($login_fabrica==2) {
						$coluna ="7";
						echo "<td align='left' nowrap>";
						echo "<a href=\"javascript:listaFaq(document.frm_callcenter.produto_referencia.value)\">Listar todas dvidas cadastradas ou cadastrar a nova</a>";
						echo "</td>";
					}else{
						$coluna ="6";
					}
				?>
			</tr>
			<tr>
				<td colspan='<? echo $coluna; ?>' id="div_faq_duvida_duvida" class="div_faq_duvida_duvida"> &nbsp; </td>
			</tr>
			</table>
		</div>

		<div id="sugestao" class='tab_content'>
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left' valign='top'><strong>Sugestão:</strong></td>
				<td align='left' colspan='5'>
					<TEXTAREA NAME="reclamado_sugestao" ROWS="6" COLS="110"  class="input" style='font-size:10px' <? echo $read;?>><?echo $reclamado ;?></TEXTAREA>
				</td>
			</tr>
			</table>

			<BR>

			<div rel='div_ajuda' style='display:inline; Position:relative;'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>
						<?
						$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='8' AND fabrica = $login_fabrica";
						$pe = pg_query($con,$sql);
						if(pg_num_rows($pe)>0) {
							echo pg_fetch_result($pe,0,0);
						}else{
							echo "Sr.(a) estou encaminhando a sua reclamação ao Depto. responsável, que responderá em 12 h.";
						}
						?>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>
			</div>
		</div>

		<?if($login_fabrica != 59 ){ # HD 37805 ?>
		<div id="procon" class='tab_content'>
			<div rel='div_ajuda' style='display:inline; Position:relative;'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>Confirmar ou perguntar a reclamação.</STRONG><BR>
						<?
						$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='11' AND fabrica = $login_fabrica";
						$pe = pg_query($con,$sql);
						if(pg_num_rows($pe)>0) {
							echo pg_fetch_result($pe,0,0);
						}else{
							echo "Qual a reclamação feita no Procon pelo SR.(a)?<BR>	ou<BR> O Sr.(a) diz que...., correto?";
						}
						?>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>
			</div>

			<? if($login_fabrica ==11) { // HD 55995?>
			Informações da Assistência
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
				<tr>
					<td align='left'><strong>Código:</strong></td>
					<td align='left'>
						<input name="procon_codigo_posto"  class="input"  value='<?echo $procon_codigo_posto ;?>'
						onblur="javascript: fnc_pesquisa_posto (document.frm_callcenter.procon_codigo_posto,document.frm_callcenter.procon_posto_nome,'codigo');" type="text" size="15" maxlength="15">
						<img src='imagens/lupa.png' border='0' align='absmiddle'
						style='cursor: pointer'
						onclick="javascript: fnc_pesquisa_posto (document.frm_callcenter.procon_codigo_posto,document.frm_callcenter.procon_posto_nome,'codigo');">
					</td>
					<td align='left'><strong>Nome:</strong></td>
					<td align='left'>
						<input name="procon_posto_nome"  class="input" value='<?echo $procon_posto_nome ;?>'
						onblur="javascript: fnc_pesquisa_posto (document.frm_callcenter.procon_codigo_posto,document.frm_callcenter.procon_posto_nome,'nome');" type="text" size="35" maxlength="500">
						<img src='imagens/lupa.png' border='0' align='absmiddle'
						style='cursor: pointer'
						onclick="javascript: fnc_pesquisa_posto (document.frm_callcenter.procon_codigo_posto,document.frm_callcenter.procon_posto_nome,'nome');">
					</td>
				</tr>
			</table>
				Tipo da Reclamação
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
				<tr>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="reclamacao_procon" value="pr_reclamacao_at" <?PHP if ($natureza_chamado2 == 'pr_reclamacao_at' OR $natureza_chamado2 =='') { echo "CHECKED";}?>> RECLAMAÇÃO DA ASSIST. TÉCN.</td>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="reclamacao_procon" value="pr_info_at" <?PHP if ($natureza_chamado2 == 'pr_info_at') { echo "CHECKED";}?>>INFORMAÇÕES DE A.T</td>
				</tr>
				<tr>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="reclamacao_procon" value="pr_mau_atend" <?PHP if ($natureza_chamado2 == 'pr_mau_atend') { echo "CHECKED";}?>>MAU ATENDIMENTO</td>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="reclamacao_procon" value="pr_posto_n_contrib" <?PHP if ($natureza_chamado2 == 'pr_posto_n_contrib') { echo "CHECKED";}?>>POSTO NÃO CONTRIBUI COM INFORMAÇÕES</td>
				</tr>
				<tr>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="reclamacao_procon" value="pr_demonstra_desorg" <?PHP if ($natureza_chamado2 == 'pr_demonstra_desorg') { echo "CHECKED";}?>>DEMONSTRA DESORGANIZAÇÃO</td>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="reclamacao_procon" value="pr_bom_atend" <?PHP if ($natureza_chamado2 == 'pr_bom_atend') { echo "CHECKED";}?>>POSSUI BOM ATENDIMENTO</td>
				</tr>
				<tr>
					<td align='left' class="padding" width='50%' nowrap><input type="radio" name="reclamacao_procon" value="pr_demonstra_org" <?PHP if ($natureza_chamado2 == 'pr_demonstra_org') { echo "CHECKED";}?>>DEMONSTRA ORGANIZAÇÃO</td>
				</tr>
				</table>
			<? } ?>
			Informações da Reclamação
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left' valign='top'><strong>Reclamação:</strong></td>
				<td align='left' colspan='5'>
					<TEXTAREA NAME="reclamado_procon" ROWS="6" COLS="110"  class="input" style='font-size:10px' <? echo $read; ?>><?echo $reclamado ;?></TEXTAREA>
				</td>
			</tr>
			</table>
			<BR>
		</div>
		<?}?>
		<div id="onde_comprar" class='tab_content'>
			<div rel='div_ajuda' style='display:inline; Position:relative;'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>Informar dados da Revenda.</STRONG><BR>
						<?
						$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='11' AND fabrica = $login_fabrica";
						$pe = pg_query($con,$sql);
						if(pg_num_rows($pe)>0) {
							echo pg_fetch_result($pe,0,0);
						}else{
							echo "Quais são os dados da Revenda?";
						}
				?>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>

			</div>

			<?
			# HD 31204 - Francisco Ambrozio
			#   Alterado campo onde comprar para a Dynacom
			if ($login_fabrica == 2 || $login_fabrica == 24){
				if (strlen($revenda) > 0){
				$sql = "SELECT tbl_revenda.nome,
							tbl_revenda.endereco,
							tbl_revenda.numero,
							tbl_revenda.complemento,
							tbl_revenda.bairro,
							tbl_revenda.fone,
							tbl_cidade.nome AS revenda_city,
							tbl_cidade.estado AS revenda_uf
							FROM tbl_revenda
							JOIN tbl_cidade USING (cidade)
							WHERE revenda = $revenda";
				$res = pg_query($con,$sql);

				if(pg_num_rows($res)>0){
					$revenda_nome             = pg_fetch_result($res,0,nome);
					$revenda_endereco         = pg_fetch_result($res,0,endereco);
					$revenda_nro              = pg_fetch_result($res,0,numero);
					$revenda_cmpto            = pg_fetch_result($res,0,complemento);
					$revenda_bairro           = pg_fetch_result($res,0,bairro);
					$revenda_city             = pg_fetch_result($res,0,revenda_city);
					$revenda_uf               = pg_fetch_result($res,0,revenda_uf);
					$revenda_fone             = pg_fetch_result($res,0,fone);
				}
			}
			?>
				Informações da Revenda

			<?
			//HD 235203: Alterar assuntos Fale Conosco e CallCenter
			//			 O array $assuntos é definido dentro do arquivo callcenter_suggar_assuntos.php
			//			 que está sendo incluído no começo deste arquivo

			if ($login_fabrica == 24) {
			?>
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td>
				Assunto: <select name="callcenter_assunto[onde_comprar]" id="callcenter_assunto" class="input_req">
				<option value=''>>>> ESCOLHA <<<</option>

				<?php

				foreach($assuntos["REVENDA"] AS $label => $valor) {
					if ($valor == $callcenter_assunto) {
						$selected = "selected";
					}
					else {
						$selected = "";
					}

					echo "
					<option value='$valor' $selected>REVENDA >> $label</option>";
				}

				?>
				</select>
				</td>
			</tr>
			</table>
			<?
			}
			?>

				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
				<tr>
					<td align='left' width='68'><strong>Localizar:</strong></td>
					<td align='left' nowrap colspan=5>
						<input name="localizarrevenda" id='localizarrevenda' value='<?echo $localizarrevenda ;?>' class="input" type="text" size="40" maxlength="500"> <a href='#onde_comprar' onclick='javascript: fnc_pesquisa_revenda (document.frm_callcenter.localizarrevenda, "nome","")'>Por Nome</a> | <a href='#onde_comprar' onclick='javascript:fnc_pesquisa_revenda (document.frm_callcenter.localizarrevenda, "cidade","")'>Por Cidade</a> | <a href='#onde_comprar' onclick='javascript:fnc_pesquisa_revenda (document.frm_callcenter.localizarrevenda, "cnpj","")'>Por CNPJ</a> | <a href='#onde_comprar' onclick='javascript:fnc_pesquisa_revenda (document.frm_callcenter.localizarrevenda, "familia",document.frm_callcenter.consumidor_cidade)'>Por Família do Produto</a>
					</td>
				</tr>
				<tr>
					<td align='left'><strong>Nome:</strong></td>
					<td align='left'><input type='hidden' name='revenda' id='revenda' value='<?=$revenda?>'><input type='text' name='revenda_nome' id='revenda_nome' value='<?=$revenda_nome?>'  size="40" maxlength="50">
					</td>
				</tr>
				<tr>
					<td align='left'><strong>Endereço:</strong></td>
					<td align='left'><input type='text' name='revenda_endereco' id='revenda_endereco' value='<?=$revenda_endereco?>'  size="40" maxlength="500">
					</td>
					<td align='left'><strong>Nro.:</strong></td>
					<td align='left'><input type='text' name='revenda_nro' id='revenda_nro' value='<?=$revenda_nro?>'>
					</td>
					<td align='left'><strong>Complemento:</strong></td>
					<td align='left'><input type='text' name='revenda_cmpto' id='revenda_cmpto' value='<?=$revenda_cmpto?>'>
					</td>
				</tr>
					<tr>
					<td align='left'><strong>Bairro:</strong></td>
					<td align='left'><input type='text' name='revenda_bairro' id='revenda_bairro' value='<?=$revenda_bairro?>'>
					</td>
					<td align='left' valign='top'><strong>Cidade:</strong></td>
					<td align='left'><input type='text' name='revenda_city' id='revenda_city' value='<?=$revenda_city?>'>
					</td>
					<td align='left'><strong>UF:</strong></td>
					<td align='left'><input type='text' name='revenda_uf' id='revenda_uf' value='<?=$revenda_uf?>'>
					</td>
				</tr>
				<tr>
					<td align='left'><strong>Telefone:</strong></td>
					<td align='left'><input type='text' name='revenda_fone' id='revenda_fone' value='<?=$revenda_fone?>'>
					</td>
				</tr><tr><td colspan='4'>Para cadastrar <a href='revenda_cadastro.php' target='_blank'>clique aqui</a></td>
			<?
			//HD 235203: Alterar assuntos Fale Conosco e CallCenter

			if ($login_fabrica == 24) {
			?>
				<tr><td colspan='7' height='10'></td></tr>
				<tr>
					<td align='left' valign='top'><strong>Informações:</strong></td>
					<td align='left' colspan='6'>
						<TEXTAREA NAME="reclamado_onde_comprar" ROWS="6" COLS="110"  class="input" style='font-size:10px' <?echo $read;?>><?echo $reclamado ;?></TEXTAREA>
					</td>
				</tr>
			<?
			}
			?>
				</table>


			<? }else{ ?>

			Informações da Reclamação
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left' valign='top'><strong>CNPJ:</strong></td>
				<td align='left' colspan='5'><input type='hidden' name='revenda' id='revenda' value='<?=$revenda?>'><input type='text' name='revenda_cnpj' id='revenda_cnpj' value='<?=$revenda_cnpj?>'>
				</td>
				<td align='left' valign='top'><strong>Nome:</strong></td>
				<td align='left' colspan='5'><input type='text' name='revenda_nome' id='revenda_nome' value='<?=$revenda_nome?>' maxlength='50'>
				</td>
			</tr>
			<tr><td colspan='4'>Para cadastrar <a href='revenda_cadastro.php' target='_blank'>clique aqui</a></td>
			</table>

			<? } ?>

			<BR>
		</div>

		<?if($login_fabrica == 1 and strlen($callcenter) > 0) { 

			$aDados = hdBuscarChamado($callcenter);

			switch($aDados['categoria']) {
				case ('digitacao_fechamento_de_os') :   $categoria = "Digitação e/ou fechamento de OS\'s"; break;
				case ('utilizacao_do_site') :           $categoria ="Utilização do site"; break;
				case ('falha_no_site') :                $categoria ="Falha no site"; break;
				case ('pendencias_de_pecas') :          $categoria ="Pendências de peças"; break;
				case ('pedido_de_pecas') :              $categoria ="Pedido de peças"; break;
				case ('duvida_tecnica_sobre_produto') : $categoria ="Dúvida técnica sobre o produto"; break;
				case ('outros') :                       $categoria ="Outros"; break;
			}
			
		?>

		<div id="hd_posto" class='tab_content'>
			<div rel='div_ajuda' style='display:inline; Position:relative;'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>Informações cadastrados pelo posto.</STRONG><BR>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>
				
				<br>
				<center>
				<table width='90%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:12px'>
					<tbody>
							<tr>
								<td class=" border "> Tipo de Solicitação: </td>
								<td class="dados"> <?php echo $categoria; ?> </td>
								
								<td class=" border "> Produto em Garantia: </td>
								<td class="dados"> <?php echo ($aDados['garantia'] =='t') ? "Sim" : "Não" ; ?> </td>
							</tr>
							<tr>
								<td class=" border "> Produto: </td>
								<td class="dados"> <?php echo $aDados['referencia']; ?> </td>
								
								<td class=" border "> OS: </td>
								<td class="dados"> <?php echo (!empty($aDados['sua_os'])) ? $aDados['codigo_posto']."".$aDados['sua_os'] : ""; ?> </td>
							</tr>
							<tr>
								<td class=" border "> Pedido: </td>
								<td class="dados"> <?php echo $aDados['pedido']; ?> </td>

								<td class=" border "> Posto recebe peça em garantia: </td>

								<td class="dados"> <?php echo ($aDados['pedido_em_garantia'] =='t') ? "Sim" : "Não" ; ?> </td>
							</tr>
						</tbody>
					</table>
				</center>
			</div>
			<BR>
		</div>
		<? } ?>

		<?if($login_fabrica==45 /*OR $login_fabrica == 46 OR $login_fabrica == 11 Retirado por Samuel */){?>
		<div id="garantia" class='tab_content'>
			<p style='font-size: 14px'><b>Garantia</b></p>
			Informações do Produto
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left'><strong>Referência:</strong></td>
				<td align='left'>
					<input name="produto_referencia_garantia"  class="input"  value='<?echo $produto_referencia ;?>'
					onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_garantia,document.frm_callcenter.produto_nome_garantia,'referencia');mostraDefeitos('Reclamado',document.frm_callcenter.produto_referencia_garantia.value)" type="text" size="15" maxlength="15">
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer'
					onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_garantia,document.frm_callcenter.produto_nome_garantia,'descricao')">
				</td>
				<td align='left'><strong>Descrição:</strong></td>
				<td align='left'>
					<input type='hidden' name='produto_garantia' value="<? echo $produto; ?>">
					<input name="produto_nome_garantia"  class="input" value='<?echo $produto_nome ;?>'
					onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_garantia,document.frm_callcenter.produto_nome_garantia,'descricao');mostraDefeitos('Reclamado',document.frm_callcenter.produto_referencia_garantia.value)" type="text" size="35" maxlength="500">
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer'
					onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.produto_referencia_garantia,document.frm_callcenter.produto_nome_garantia,'descricao')">
				</td>
				<td align='left'><strong>Série:</strong></td>
				<td align='left'>
					<input name="serie_garantia" maxlength="20" class="input"  value='<?echo $serie ;?>'>
				</td>
			</tr>

			<tr>
				<td align='left' valign='top'><strong>Descrição:</strong></td>
				<td align='left' colspan='5'>
					<TEXTAREA NAME="reclamado_produto_garantia" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
				</td>
			</tr>
			</table>
		</div>
		<? } ?>

		<?if($login_fabrica==59  or $login_fabrica == 81 /* HD 37805 */){?>
		<div id="ressarcimento" class='tab_content'>

		<!-- SEDEX REVERSO -->
		<!--
			<div rel='div_ajuda' style='display:inline; Position:relative;'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>Informar dados Bancários.</STRONG><BR>
						<?
						$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='11' AND fabrica = $login_fabrica";
						$pe = pg_query($con,$sql);
						if(pg_num_rows($pe)>0) {
							echo pg_fetch_result($pe,0,0);
						}else{
							echo "Quais são os dados da Revenda?";
						}
				?>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>
			</div>
-->
			<?
			if (strlen($callcenter) > 0){
				$sql = "SELECT 	banco            ,
								agencia          ,
								contay           ,
								nomebanco        ,
								favorecido_conta ,
								cpf_conta        ,
								tipo_conta
						FROM tbl_hd_chamado_extra_banco
						WHERE hd_chamado = $callcenter";
				$res = pg_query($con,$sql);

				if(pg_num_rows($res)>0){
					$banco            = pg_fetch_result($res,0,banco);
					$agencia          = pg_fetch_result($res,0,agencia);
					$contay           = pg_fetch_result($res,0,contay);
					$nomebanco        = pg_fetch_result($res,0,nomebanco);
					$favorecido_conta = pg_fetch_result($res,0,favorecido_conta);
					$cpf_conta        = pg_fetch_result($res,0,cpf_conta);
					$tipo_conta       = pg_fetch_result($res,0,tipo_conta);
				}
			}
			?>
			Dados Bancários
			<table width='100%' border='0' align='center' cellpadding="0" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<? if (strlen($xos)==0) { $xos = $os ;}?>
				<td align='left'><strong>OS:</strong></td>
				<td align='left'><input type='text' name='os_ressarcimento' id='os_ressarcimento' class="input" value='<?=$xos?>'  size="13" maxlength="13">
				</td>
			</tr>
			<tr>
				<td align='left'><strong>Banco:</strong></td>
				<?
				
				$sql = "SELECT banco,codigo,nome from tbl_banco order by nome";
				$res = pg_exec($con,$sql);
				?>
				<td align='left'>
					<select name='banco' id='banco' class='input'>
						<option>- escolha</option>
							<?
								for ($i=0;$i<pg_num_rows($res);$i++) {
									$xbanco = pg_result($res,$i,banco);
									$codigo = pg_result($res,$i,codigo);
									$nome = pg_result($res,$i,nome);

									if ($banco == $xbanco) {
										$selected = "SELECTED";
									}
									echo "<option value='$xbanco' $selected>$codigo-$nome</option>";
									$selected = '';
								}
							?>
						</select>
				</td>
			</tr>
			<tr>
				<td align='left'><strong>Agência:</strong></td>
				<td align='left'><input type='text' name='agencia' id='agencia' class="input" value='<?=$agencia?>' size="15" maxlength="10">
				</td>
			</tr>
			<tr>
				<td align='left'><strong>Conta:</strong></td>
				<td align='left'><input type='text' name='contay' id='contay' class="input" value='<?=$contay?>' size="15" maxlength="10">
				</td>
			</tr>
			<tr>
				<td align='left'><strong>Tipo de Conta:</strong></td>
				<td align='left'>
					<select name='tipo_conta' id='tipo_conta' class="input" style='width:150px; font-size:10px' >
						<option value='' <? if (strlen($tipo_conta)==0)echo "SELECTED";?> ></option>
						<option value='Conta conjunta' <? if ($tipo_conta == 'Conta conjunta')echo "SELECTED";?> >Conta conjunta</option>
						<option value='Conta corrente' <? if ($tipo_conta == 'Conta corrente')echo "SELECTED";?>>Conta corrente</option>
						<option value='Conta individual' <? if ($tipo_conta == 'Conta individual')echo "SELECTED";?>>Conta individual</option>
						<option value='Conta jurdica' <? if ($tipo_conta == 'Conta jurdica')echo "SELECTED";?>>Conta jurídica</option>
						<option value='Conta poupana' <? if ($tipo_conta == 'Conta poupana')echo "SELECTED";?>>Conta poupança</option>
					</select>
				</td>
			</tr>
			<tr>
				<td align='left'><strong>Nome do Favorecido:</strong></td>
				<td align='left' colspan='2'><input type='text' name='favorecido_conta' id='favorecido_conta' class="input" value='<?=$favorecido_conta?>'  size="40" maxlength="50" <?if(strlen($callcenter)>0) echo " READONLY "?>>
				</td>
				<td align='left'><strong>CPF:</strong></td>
				<td align='left'><input type='text' name='cpf_conta' id='cpf_conta' class="input" value='<?=$cpf_conta?>'  size="20" maxlength="14" <?if(strlen($callcenter)>0) echo " READONLY "?>>
				</td>
			</tr>
			<tr>
				<td align='left'><strong>Observações:</strong></td>
				<td align='left' colspan='5'><TEXTAREA NAME="obs_ressarcimento" ROWS="6" COLS="110"  class="input" style='font-size:10px' <?if(strlen($callcenter)>0) echo " READONLY "?>><?echo $defeito;?></TEXTAREA></td>
			</tr>
			<tr>
				<td align='left'><strong>Procon? <input type="checkbox" name="procon" value='t' <?if (strlen($numero_processo) > 0) echo "CHECKED ";?> onClick='if (this.checked) {this.form.numero_processo.disabled = false;} else {this.form.numero_processo.disabled = true;}'></strong></td>
				<td align='left'><strong>Número do Processo:</strong></td>
				<td align='left'><input type='text' name='numero_processo' id='numero_processo' class="input" value='<?=$numero_processo?>' <?if(strlen($callcenter)>0) echo " READONLY "?> size="40" maxlength="30">
				</td>
			</tr>
			</table>

			<br>
			Valores do Produto
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left'><strong>Valor do Produto:</strong></td>
				<td align='left'><input type='text' name='valor_produto' id='valor_produto' class="input" value='<?=$valor_produto?>'  size="20" maxlength="10" <?if(strlen($callcenter)>0) echo " READONLY "?>>
				</td>
				<td align='left'><!--<strong>Valor INPC.:</strong>--></td>
				<td align='left'><input type='hidden' name='valor_inpc' id='valor_inpc' class="input" value='<?=$valor_inpc?>' size="15" maxlength="10">
				</td>
				<td align='left'><strong>Valor Corrigido:</strong></td>
				<td align='left'><input type='text' name='valor_corrigido' id='valor_corrigido' readonly class="input" value='<?=$valor_corrigido?>' size="15" maxlength="10">
				</td>
			</tr>
			<tr>
				<td align='left'><strong>Data do Pagamento:</strong></td>
				<td align='left'><input type='text' name='data_pagamento' rel='data' id='data_pagamento' class="input" value='<?=$data_pagamento?>'  size="20" maxlength="10">
				</td>
			</tr>
			</table>
			<BR>

		</div>

		<!-- SEDEX REVERSO -->
		<div id="sedex_reverso" class='tab_content'>
		<!--
			<div rel='div_ajuda' style='display:inline; Position:relative;'>
				<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
				<tr>
					<td align='right' width='150'></td>
					<td align='right' width='55'>
						<img src='imagens/ajuda_call.png' align=absmiddle>
					</td>
					<td align='center'>
						<STRONG>Informar dados Bancários.</STRONG><BR>
						<?
						$sql = "SELECT pergunta FROM tbl_callcenter_pergunta WHERE codigo='11' AND fabrica = $login_fabrica";
						$pe = pg_query($con,$sql);
						if(pg_num_rows($pe)>0) {
							echo pg_fetch_result($pe,0,0);
						}else{
							echo "Quais são os dados da Revenda?";
						}
				?>
					</td>
					<td align='right' width='150'></td>
				</tr>
				</table>
			</div>
-->
			<?
			if (strlen($callcenter) > 0){
				$sql = "SELECT 	banco            ,
								agencia          ,
								contay           ,
								nomebanco        ,
								favorecido_conta ,
								cpf_conta        ,
								tipo_conta
						FROM tbl_hd_chamado_extra_banco
						WHERE hd_chamado = $callcenter";
				$res = pg_query($con,$sql);

				if(pg_num_rows($res)>0){
					$banco            = pg_fetch_result($res,0,banco);
					$agencia          = pg_fetch_result($res,0,agencia);
					$contay           = pg_fetch_result($res,0,contay);
					$nomebanco        = pg_fetch_result($res,0,nomebanco);
					$favorecido_conta = pg_fetch_result($res,0,favorecido_conta);
					$cpf_conta        = pg_fetch_result($res,0,cpf_conta);
					$tipo_conta       = pg_fetch_result($res,0,tipo_conta);
				}
			}
			?>
			Informações do Produto
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left'><strong>Referência:</strong></td>
				<td align='left'>
					<input name="troca_produto_referencia"  class="input"  value='<?echo $produto_referencia ;?>'
					onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.troca_produto_referencia,document.frm_callcenter.troca_produto_nome,'referencia');mostraDefeitos('Reclamado',document.frm_callcenter.troca_produto_referencia.value)" type="text" size="15" maxlength="15" <?if(strlen($callcenter)>0) echo " READONLY "?>>
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer'
					onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.troca_produto_referencia,document.frm_callcenter.troca_produto_nome,'descricao')">
				</td>
				<td align='left'><strong>Descrição:</strong></td>
				<td align='left'>
					<input type='hidden' name='produto' value="<? echo $produto; ?>">
					<input name="troca_produto_nome"  class="input" value='<?echo $produto_nome ;?>'
					onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.troca_produto_referencia,document.frm_callcenter.troca_produto_nome,'descricao');mostraDefeitos('Reclamado',document.frm_callcenter.troca_produto_referencia.value)" type="text" size="35" maxlength="500" <?if(strlen($callcenter)>0) echo " READONLY "?>>
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer'
					onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.troca_produto_referencia,document.frm_callcenter.troca_produto_nome,'descricao')">
				</td>
			</tr>
			<tr>
				<td align='left' valign='top'><strong>Observações:</strong></td>
				<td align='left' colspan='5'>
					<TEXTAREA NAME="troca_observacao" ROWS="6" COLS="110"  class="input" style='font-size:10px' <?if(strlen($callcenter)>0) echo " READONLY "?>><?echo $reclamado ;?></TEXTAREA>
				</td>
			</tr>
			</table>
			Informações de Envio
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left'><strong>Data do Retorno do Produto (Cliente):</strong></td>
				<td align='left'><input type='text' name='data_retorno_produto' id='data_retorno_produto' class="input" value='<?=$data_retorno_produto?>' size="12" maxlength="12" rel='data' <?if(strlen($callcenter)>0) echo " READONLY "?>>
				</td>
				<td align='left'><strong>Código de Postagem:</strong></td>
				<td align='left'><input type='text' name='numero_objeto' id='numero_objeto' class="input" value='<?=$numero_objeto?>'  size="25" maxlength="20" <?if(strlen($callcenter)>0) echo " READONLY "?>>
				</td>
			</tr>
			<tr>
				<td align='left' colspan='4'><strong>Procon? <input type="checkbox" name="procon2" value='t' <?if (strlen($numero_processo)>0) echo "CHECKED ";?> <?if(strlen($callcenter)>0) echo " READONLY "?> onClick='if (this.checked) {this.form.numero_processo2.disabled = false;} else {this.form.numero_processo2.disabled = true;}'></strong>
				&nbsp;&nbsp;&nbsp;
				<strong>Número do Processo:</strong>
				&nbsp;&nbsp;&nbsp;&nbsp;
				<input type='text' name='numero_processo2' id='numero_processo2' class="input" value='<?=$numero_processo?>'  size="25" maxlength="30" <?if(strlen($callcenter)>0) echo " READONLY "?>>
				</td>
			</tr>
			</table>
			<BR>
		</div>
		<? } ?>

		<?if($login_fabrica==46 OR $login_fabrica==11){?>
		<div id="troca_produto" class='tab_content'>
			<p style='font-size: 14px'><b>Troca de Produto</b></p>
			Informações do Produto
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left'><strong>Referência:</strong></td>
				<td align='left'>
					<input name="troca_produto_referencia"  class="input"  value='<?echo $produto_referencia ;?>'
					onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.troca_produto_referencia,document.frm_callcenter.troca_produto_nome,'referencia');mostraDefeitos('Reclamado',document.frm_callcenter.troca_produto_referencia.value)" type="text" size="15" maxlength="15">
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer'
					onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.troca_produto_referencia,document.frm_callcenter.troca_produto_nome,'descricao')">
				</td>
				<td align='left'><strong>Descrição:</strong></td>
				<td align='left'>
					<input type='hidden' name='produto' value="<? echo $produto; ?>">
					<input name="troca_produto_nome"  class="input" value='<?echo $produto_nome ;?>'
					onblur="javascript: fnc_pesquisa_produto (document.frm_callcenter.troca_produto_referencia,document.frm_callcenter.troca_produto_nome,'descricao');mostraDefeitos('Reclamado',document.frm_callcenter.troca_produto_referencia.value)" type="text" size="35" maxlength="500">
					<img src='imagens/lupa.png' border='0' align='absmiddle'
					style='cursor: pointer'
					onclick="javascript: fnc_pesquisa_produto (document.frm_callcenter.troca_produto_referencia,document.frm_callcenter.troca_produto_nome,'descricao')">
				</td>
				<td align='left'><strong>Série:</strong></td>
				<td align='left'>
					<input name="troca_serie" maxlength="20" class="input"  value='<?echo $serie ;?>'>
				</td>
			</tr>

<?/*		<tr>
			<td>
				<a href="javascript:mostraDefeitos('Reclamao',document.frm_callcenter.produto_referencia.value)">Defeitos</a>
				</td>
				<td align='left' colspan='5' width='630' valign='top'>
					<div id='div_defeitos' style='display:inline; Position:relative;background-color: #e6eef7;width:100%'>
					<?   if(strlen($defeito_reclamado)>0){
							$sql = "SELECT defeito_reclamado,
											descricao
									FROM tbl_defeito_reclamado
									WHERE defeito_reclamado = $defeito_reclamado";
							$res = pg_query($con,$sql);
							if(pg_num_rows($res)>0){
								$defeito_reclamado_descricao = pg_fetch_result($res,0,descricao);
								echo "<input type='radio' checked value='$defeito_reclamado'><font size='1'>$defeito_reclamado_descricao</font>";
							}
						}
*/					?>
<?/*					</div>
				</td>
			</tr>
*/?>
			<tr>
				<td align='left' valign='top'><strong>Descrição:</strong></td>
				<td align='left' colspan='5'>
					<TEXTAREA NAME="troca_produto_descricao" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
				</td>
			</tr>
			</table>
		</div>
		<? } ?>
		
	<?if($login_fabrica==24){?>
		<div id="outros_assuntos" class='tab_content'>
			<p style='font-size: 14px'><b>Outros Assuntos</b></p>
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left' valign='top'><strong>Descrição:</strong></td>
				<td align='left' colspan='5'>
					<TEXTAREA NAME="outros_assuntos_descricao" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $reclamado ;?></TEXTAREA>
				</td>
			</tr>
			</table>
		</div>
		<? } ?>

	</div>
	</td>
</tr>

<? if(strlen($callcenter)>0 && $login_fabrica == 24) {
		// HD 277105		
		$sql = 'SELECT hd_chamado_anterior
				FROM tbl_hd_chamado 
				WHERE hd_chamado = ' . $callcenter . '
				AND fabrica = '.$login_fabrica . '
				LIMIT 1';
		$res = pg_query($sql);

		if(pg_numrows($res) > 0)
			$hd_pesquisa_ini = pg_result ($res,0,hd_chamado_anterior);

		if(strlen($hd_pesquisa_ini) == 0) { // verifica se ele pode ser o chamado raiz
		
			$sql = 'SELECT hd_chamado
				FROM tbl_hd_chamado
				WHERE hd_chamado_anterior = ' . $callcenter . '
				AND fabrica = '.$login_fabrica . '
				LIMIT 1';
			$res = pg_query($sql);

			if(pg_numrows($res) > 0)
				$hd_pesquisa_ini = pg_result ($res,0,hd_chamado);

		}

		if(strlen($hd_pesquisa_ini) > 0) { //se tiver chamados anteriores

			function busca_chamados_filhos($hd_pai) { //busca os chamados vindo do passado

				global $con;
				$sql = "SELECT hd_chamado FROM tbl_hd_chamado WHERE hd_chamado_anterior=$hd_pai";
				//echo $sql;
				$res = pg_query($con, $sql);

				$hd_filhos	= array();

				for($i=0; $i<pg_num_rows($res); $i++) {
					$hd_atual = pg_result($res,$i,hd_chamado);
					$hd_filhos[$hd_atual] = busca_chamados_filhos($hd_atual);
				}

				return($hd_filhos);

			}

			function onlyOne($arr) { //deixa o array unidimensional
				$rpltext = print_r($arr,true);
				$chars = array("Array","(",")","[","]"," ","\n");
				$repls = array("","","","=>","","","");
				$text = str_replace($chars,$repls,$rpltext);
				$expl = explode('=>',$text);

				foreach ($expl AS $result) {
					if (!empty($result)) $arrOrg[] = $result;
				}

				$count = count($arrOrg);
				$retorno = array();

				for ($i=0; $i<$count; $i++) {
					if ($i%2==0) $retorno[$arrOrg[$i]] = $arrOrg[($i+1)]; 
				}

				return $retorno;

			}
/**********     busca o primeiro chamado a partir do chamado aberto      **********/
			$controle = false;	//variavel que finaliza o loop
			$ultimo_reg = 0;	//pega o ultimo registro, sem chamado anterior
			$i = 0;				//contadora p/ zebrado e ajax/jQuery 

			while($controle == false) {

				if(isset($hd_pesquisa_ini)) //inicia pesquisando o hd anterior do visualizado na tela
					$hd_pesquisa = $hd_pesquisa_ini;

				$i++;
				$sql = 'SELECT 
						hd_chamado_anterior, hd_chamado
						FROM tbl_hd_chamado 
						WHERE hd_chamado = ' . $hd_pesquisa . '
						AND fabrica = '.$login_fabrica . '
						LIMIT 1';
				$res = pg_query($sql);

				if(pg_numrows($res) > 0) {
					$hd_pesquisa	= pg_result ($res,0,hd_chamado_anterior);
					$hd_chamado_cp	= pg_result ($res,0,hd_chamado);

					if(strlen($hd_pesquisa) == 0 && $ultimo_reg == 0 ) {
						$hd_pesquisa = $hd_chamado_cp; // $hd_pesquisa = chamado raiz
						$ultimo_reg++;
					}
					if ($ultimo_reg == 1)
						$controle = true;
				}

				if($i == 1)
					unset($hd_pesquisa_ini); //usa essa variavel só na primeira iteração do loop
			}
/*******      fim da busca      @return $hd_pesquisa       *****/
			$vet = array();
			
			$vet = busca_chamados_filhos($hd_pesquisa); // pega todos os chamados vindo desse
			
			$res_chamados = onlyOne($vet); //tira o vetor multidimensional em um só

			$new_res_chamados = array();

			foreach ($res_chamados as $key => $value){ //tira as chaves pra ficar total unidimensional
				if(!in_array($key, $new_res_chamados))
					$new_res_chamados[] = $key;
				if(!in_array($value, $new_res_chamados))
					$new_res_chamados[] = $value;
			}

			$new_res_chamados[] = $hd_pesquisa; //adiciona no vetor chamado raiz

			asort($new_res_chamados);
			/*
			echo '<pre>';
			print_r($new_res_chamados);
			echo '</pre>';			
			*/
			echo "
					<table width='100%' border='0' align='center' cellpadding=\"1\" cellspacing=\"1\" style=' border:#5AA962 1px solid; background-color:#E6E6FA;font-size:10px'>
							<tr><th colspan='4' style='border-bottom:1px solid #ccc'>Chamados Anteriores</th></tr>
							<tr align='left'>
								<th>Nº do Chamado/Protocolo</th>
								<th>Data</th>
								<th>Status</th>
								<th>Ações</th>
							</tr>
				";
			// percorre o vetor e exibe os protocolos anteriores (finalmente :) )
			if(!empty($new_res_chamados))
			foreach ($new_res_chamados as $hd_pesquisa){
				if($hd_pesquisa == $callcenter || is_null($hd_pesquisa))
					continue;
				$i++;
				$sql = 'SELECT 
						TO_CHAR(data,\'DD/MM/YYY\') as data, status, hd_chamado
						FROM tbl_hd_chamado 
						WHERE hd_chamado = ' . $hd_pesquisa . '
						AND fabrica = '.$login_fabrica . '
						LIMIT 1';

				$res = pg_query($sql);

				if(pg_numrows($res) > 0) {

					$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

					$hd_chamado_cp	= pg_result ($res,0,hd_chamado);
					$data			= pg_result ($res,0,data);
					$status			= pg_result ($res,0,status);

					// entra no if se tiver hd e for até o ultimo registro
					if (strlen($hd_pesquisa)) {
						echo '<tr id="dados'.$i.'" style="cursor:pointer;" bgcolor="'.$cor.'" onclick="exibe_interacao_anterior('.$hd_chamado_cp.','.$i.')">
							<td>'.$hd_chamado_cp.'</td>
							<td>'.$data.'</td>
							<td>'.$status.'</td>
							<td><a href="#dados'.$hd_chamado_cp.'">Exibir <img src="imagens/mais.bmp" style="cursor:pointer" id="seta'.$i.'"></a></td>
						</tr>';
					}
				}
			}
			echo '</table><br />
				';
			
		}

	?>
		<script type="text/javascript">
			function exibe_interacao_anterior(id, linha) {

				if($("#seta"+linha).attr( "src" )=="imagens/menos.bmp"){
					$(".resp"+linha).fadeOut("slow");
					$("#seta"+linha).attr("src", "imagens/mais.bmp");
				}
				else {

					$("#seta"+linha).attr("src", "imagens/menos.bmp");

					url = '<?php echo $_SERVER[PHP_SELF]; ?>?ajax=sim&hdanterior='+id+'&resp='+linha;

					$.get(url, function(dados) { 
						$("#dados" + linha).after(dados); 
					});

				}

			}
		</script>
<?php } ?>

<tr>
	<td align='center' colspan='5'>
		<?
		#94971
		if(strlen($_GET['Id'])>0){
			$id_x = $_GET['Id'];
		}else{
			$id_x = "";
		}

		?>
		<? if(strlen($callcenter)>0 OR strlen($id_x)>0){
			// ! respostas do chamado
			$_hd_chamado = (strlen($id_x)>0 AND strlen($_GET['herdar'])>0 AND $login_fabrica==59) ? $id_x : $callcenter ;
			
			$aRespostas = hdBuscarRespostas($_hd_chamado); // funcao declarada em 'assist/www/heldesk.inc.php'
		?>
		<?php foreach ($aRespostas as $iResposta=>$aResposta): ?>
		<table width="100%" border="0" align="center" cellpadding="2" cellspacing="1" style="border:#485989 1px solid; background-color: #A0BFE0; font-size:10px; margin-bottom: 10px;">
			<tr>
				<td align="left" valign="top">
					<table style="font-size: 10px" border="0" width="100%">
						<tr>
							<td align="left" width="70%">
								Resposta <strong><?php echo $iResposta + 1; ?></strong>
								Por <strong><?php echo ( ! empty($aResposta['atendente']) ) ? $aResposta['atendente'] : $aResposta['posto_nome'] ; ?></strong>
							</td>
							<td align="right" nowrap="nowrap"> <?php echo $aResposta['data']; ?> </td>
						</tr>
					</table>
				</td>
			</tr>
			<?php if ( $aResposta['interno'] == 't' ): ?>
			<tr>

				<td align="center" valign="top" bgcolor="#EFEBCF" style="font-size: 10px;"> Chamado Interno </td>
			</tr>
			<?php endif; ?>
			<?php if ( in_array($aResposta['status_item'],array('Cancelado','Resolvido')) ): ?>
			<tr>
				<td align="center" valign="top" bgcolor="#EFEBCF" style="font-size: 10px;"> <?php echo $aResposta['status_item']; ?> </td>
			</tr>
			<?php endif; ?>
			<tr>
				<td align="left" valign="top" bgcolor="#FFFFFF"> <?php echo nl2br($aResposta['comentario']); ?> </td>
				<? if($login_fabrica == 1) { ?>
				<td align="center" valign="middle" bgcolor="#FFFFFF" width="50px">
					<?php
						$file = hdNomeArquivoUpload($aResposta['hd_chamado_item']);
						if ( empty($file) ) {
							echo '&nbsp';
						} else {
					?>
						<a href="<?php echo TC_HD_UPLOAD_URL.$file; ?>" target="_blank" >
							<img src="../helpdesk/imagem/clips.gif" alt="Baixar Anexo" />
							Baixar Anexo
						</a>
					<?php } ?>
				</td>
				<? } ?>
			</tr>
		</table>
		<?php endforeach; ?>
		<?php unset($aRespostas,$iResposta,$aResposta,$_hd_chamado); ?>
	<?php
			if($login_fabrica==59 AND strlen($admin_abriu)>0){ // HD 52082 14/11/2008
				$sqlAdm = " SELECT login
							FROM tbl_admin
							WHERE fabrica = $login_fabrica AND admin = $admin_abriu";
				$resAdm   = pg_query($con, $sqlAdm);

				if(pg_num_rows($resAdm )>0) $login_abriu = pg_fetch_result($resAdm, 0, $login);

				echo "<div style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>";
					echo "<b>CHAMADO ABERTO PELO ATENDENTE: " . $login_abriu."</b>";
				echo "</div>";
			}
		}

	?>
	</td>
</tr>
<tr>
	<td align='center' colspan='5'>
	<? if($login_fabrica == 3){ ?>
		<table width="100%" border='0'>
			<tr>
				<td align='left'><strong>Mapa da Rede</strong></td>
			</tr>
		</table>
		<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#485989 1px solid; background-color: #e6eef7;font-size:10px'>
			<tr>
				<td align='left' width='50'><strong>Linha:</strong></td>
				<td align='left'>
				<?
				$sql = "SELECT  *
						FROM    tbl_linha
						WHERE   tbl_linha.fabrica = $login_fabrica
						ORDER BY tbl_linha.nome;";
				$res = pg_query ($con,$sql);

				if (pg_num_rows($res) > 0) {
					echo "<select name='mapa_linha' id='mapa_linha' class='frm'>\n";
					echo "<option value=''>ESCOLHA</option>\n";
					for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
						$aux_linha = trim(pg_fetch_result($res,$x,linha));
						$aux_nome  = trim(pg_fetch_result($res,$x,nome));

						echo "<option value='$aux_linha'";
						if ($linha == $aux_linha){
							echo " SELECTED ";
							$mostraMsgLinha = "<br> da LINHA $aux_nome";
						}
						echo ">$aux_nome</option>\n";
					}
					echo "</select>\n&nbsp;";
				}
				?>
				</td>
				<td align='left' width='50'><strong>Estado:</strong></td>
				<td align='left'>
					<select name='mapa_estado' id='mapa_estado'>
						<option value='00' selected>Todos</option>
						<option value='SP'         >São Paulo</option>
						<option value='RJ'         >Rio de Janeiro</option>
						<option value='PR'         >Paraná</option>
						<option value='SC'         >Santa Catarina</option>
						<option value='RS'         >Rio Grande do Sul</option>
						<option value='MG'         >Minas Gerais</option>
						<option value='ES'         >Espírito Santo</option>
						<option value='BR-CO'      >Centro-Oeste</option>
						<option value='BR-NE'      >Nordeste</option>
						<option value='BR-N'       >Norte</option>
					</select>
				<td align='left' width='50'><strong>Cidade:</strong></td>
				<td align='left'><input type='text' id='mapa_cidade' name='mapa_cidade' value='<?=$mapa_cidade?>'>

					<input type='button' name='btn_mapa' value='mapa' onclick='javascript:mapa_rede(mapa_linha,mapa_estado,mapa_cidade)'>
					</font>
				</td>
			</tr>
			<tr>
				<td align='left'><strong>Código:</strong></td>
				<td align='left'>
					<input name="codigo_posto_tab" id="codigo_posto_tab"  class="input" value='<?echo $codigo_posto_tab;?>'  type="text" size="15" maxlength="15">
				</td>
				<td align='left'><strong>Nome:</strong></td>
				<td align='left'>
					<input type='hidden' name='posto_tab' value="<? echo $posto_tab; ?>">
					<input name="posto_nome_tab" id="posto_nome_tab"  class="input" value='<?echo $posto_nome_tab ;?>'  type="text" size="35" maxlength="500">
				</td>
			</tr>

			<tr>
				<td colspan='6'>
				<?
				if(strlen($callcenter)==0){
					echo "<tr><td align='left' colspan='6'>";
					if($login_fabrica == 30){
						echo "<strong><input type='checkbox' name='abre_os' id='abre_os' value='t' onClick='verificarImpressao(this)'> Abrir OS para esta Autorizada</strong>";
					}else{
						echo "<strong><input type='checkbox' name='abre_os' id='abre_os' value='t' onClick='verificarImpressao(this)'> Abrir PRE-OS para esta Autorizada</strong>";
					}
					echo "<div id='imprimir_os' style='display:none'><strong>&nbsp;&nbsp;&nbsp;&nbsp;<input type='checkbox' name='imprimir_os' value='t'> Imprimir OS</strong></div>";
					echo "</td></tr>";
				}
				?>
				</td>
			</tr>
			</table>
			<BR>
		<? } ?>
		
	<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#5AA962 1px solid; background-color:#D1E7D3;font-size:10px'>

		<? if(strlen($callcenter)>0){ ?>
			 <tr>
				<td></td>
			 </tr>
			 <tr>
			 <td align='left' valign='top'> <strong>Resposta:</strong></td>
			 <td colspan='6' align='left'><TEXTAREA NAME="resposta" ROWS="6" COLS="110"  class="input" style='font-size:10px'><?echo $resposta ;?></TEXTAREA></td>
			 </tr>
		<?}?>
	<tr>
		<td align='left' width='80'><strong>Transferir p/:</strong></td>
		<td align='left' width='90'>
			<select name="transferir" style='width:80px; font-size:9px' class="input" >
			 <option value=''></option>
			<?	
				if ($login_fabrica == 24) {
					$sql_fale = " AND atendente_callcenter = true ";
				}
				if($login_fabrica==30 and strlen($login_cliente_admin)>0) {
					$sql_marca = "SELECT marca FROM tbl_cliente_admin WHERE cliente_admin = $login_cliente_admin";
					$res_marca           = pg_exec($con,$sql_marca);
					$marca_cliente_admin = pg_result($res_img,0,marca);
					$sql_marca = " JOIN tbl_cliente_admin on tbl_cliente_admin.cliente_admin = tbl_admin.cliente_admin AND tbl_cliente_admin.marca = $marca_cliente_admin ";
				}
				else $sql_marca = '';
				$sql = "SELECT admin, login
						from tbl_admin 
						$sql_marca
						where fabrica = $login_fabrica
						and ativo is true
						and (privilegios like '%call_center%' or privilegios like '*') 
						$sql_fale
						order by login
						";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res)>0){
					for($i=0;pg_num_rows($res)>$i;$i++){
						$tranferir = pg_fetch_result($res,$i,admin);
						$tranferir_nome = pg_fetch_result($res,$i,login);
						echo "<option value='$tranferir'>$tranferir_nome</option>";
					}
				}
			?>
			</select>
		</td>
		<? if ($login_fabrica == 24) { ?>
		<td align='left' width='80'><strong>Intervensor p/:</strong></td>
		<td align='left' width='90'>
						<select name="intervensor" style='width:80px; font-size:9px' class="input" >
			 <option value=''></option>
			<?	
				if ($login_fabrica == 24) {
					$sql_fale = " AND intervensor = true ";
				}
				$sql = "SELECT admin, login
						from tbl_admin
						where fabrica = $login_fabrica
						and ativo is true
						and (privilegios like '%call_center%' or privilegios like '*') 
						$sql_fale
						order by login
						";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res)>0){
					for($i=0;pg_num_rows($res)>$i;$i++){
						$tranferir_intervensor = pg_fetch_result($res,$i,admin);
						$tranferir_nome_intervensor = pg_fetch_result($res,$i,login);
						echo "<option value='$tranferir_intervensor'>$tranferir_nome_intervensor</option>";
					}
				}
			?>
			</select>
		</td>
		<?}?>
		<td align='left' width='50'><strong>Situação:</strong></td>
		<td align='left' width='85'>
			<select name="status_interacao" id="status_interacao" style="width:80px; font-size:9px" class="input" >
			<?php
				/**
				 * HD 124579 (augusto)
				 * Modificado para impedir que após o chamado ser marcado como resolvido,
				 * não permitir mais mudanças de status
				 *
				 * HD 132345 - Permitir mudança de status para Gama Italy (51) [augusto]
				 * HD 136170 - Liberar reabertura para Cadence (35) [augusto]
				 */
				 $aLiberarMudancaStatus = array(35,51,90);
				 $aLiberarMudancaStatus = array_flip($aLiberarMudancaStatus);
			?>
			<?php if ( empty($callcenter) || $status_interacao != 'Resolvido' || ($status_interacao == 'Resolvido' && isset($aLiberarMudancaStatus[$login_fabrica])) ): ?>
				<option value="Aberto"   <? if ($status_interacao=="Aberto") echo "SELECTED";?> >Aberto</option>
				<!-- HD 234208: Acrescentar status Pendente -->
				<?php if ( $login_fabrica ==  24 ): ?>
				<option value="Pendente"   <? if ($status_interacao=="Pendente") echo "SELECTED";?> >Pendente</option>
				<?php endif; ?>

				<?php if ( $login_fabrica ==  11 ): ?>
				<option value="Analise"   <? if ($status_interacao=="Analise") echo "SELECTED";?> >Em análise</option>
				<?php endif; ?>
				<?php if ( $login_fabrica ==  1 ): ?>
				<option value="Atendido"   <? if ($status_interacao=="Atendido") echo "SELECTED";?> >Atendido</option>
				<?php endif; ?>

				<option value="Resolvido"  <? if ($status_interacao=="Resolvido") echo "SELECTED";?> >Resolvido</option>
				<option value="Cancelado" <? if ($status_interacao=="Cancelado") echo "SELECTED";?> >Cancelado</option>
			<?php else: ?>
				<option value="<?php echo $status_interacao; ?>"><?php echo $status_interacao; ?></option>
			<?php endif; ?>
			</select>
		</td>
		<td align="left" nowrap style="width: 130px;">
			<input type="checkbox" name="chamado_interno" id="chamado_interno" class="input" <?php echo (isset($_POST['chamado_interno'])) ? 'checked="checked"' : '' ; ?> />
			<label for="chamado_interno"><strong>Chamado Interno</strong></label>
			<?php
				// !110180 - Nome do atendente que abriu o chamado no rodapé
				$fabrica_exibir_nome_atentende = array(30);
				$fabrica_exibir_nome_atentende = array_flip($fabrica_exibir_nome_atentende);
			?>
			<?php if ( ! empty($callcenter) && isset($fabrica_exibir_nome_atentende[$login_fabrica])): ?>
				<?php
					/**
					 * Colocar nome de usuário que abriu o chamado no rodapé.
					 * HD 110180
					 *
					 * @author Augusto Pascutti <augusto.pascuti@telecontrol.com.br>
					 */
					$sql_abriu = "SELECT nome_completo
								  FROM tbl_admin
								  WHERE admin = %s";
					$sql_abriu = sprintf($sql_abriu,$usuario_abriu);
					$sql_abriu = pg_escape_string($sql_abriu);
					$res_abriu = @pg_query($con,$sql_abriu);
					if ( is_resource($res_abriu) ) {
						$row_abriu = pg_num_rows($res_abriu);
						if ( $row_abriu > 0 ) {
							$nome_abriu = pg_fetch_result($res_abriu,0,'nome_completo');
						}
					}
					if ( empty($nome_abriu) ) {
						$nome_abriu = "Erro";
					}
				?>
				&nbsp; Chamado aberto por <?php echo $nome_abriu; ?>
			<?php endif; ?>
		</td>

		<? if($login_fabrica==25){?>
			<td align='center' nowrap><a href='sedex_cadastro.php' target='blank'><strong>Abrir OS Sedex</strong></a></td>
		<? } ?>

		<?
		//HD 234227: Enviar e-mail para o consumidor
		if(($login_fabrica==35 || $login_fabrica == 24) and strlen($callcenter)>0){?>
			<td align='center' nowrap><INPUT TYPE="checkbox" <?php if ($envia_email) { echo "checked"; } ?> NAME="envia_email" class="input" > <strong>Envia e-mail para consumidor</strong></td>
		<?
		}?>

		<td align='left'>
			<input class="botao" type="hidden" name="btn_acao"  value=''>
			<input  class="input"  type="button" name="bt" value='Gravar Atendimento' style='width:120px' onclick="javascript:if (document.frm_callcenter.btn_acao.value!='') alert('Dados já gravados. Se você clicou em Voltar no seu browser ou clicou mais de uma vez no botão, acesse novamente a tela pelos Menus do sistema.'); else{
			<?if($login_fabrica ==3) { // HD 48680
			  echo "if(confirm('Deseja confirmar o atendimento?') == true){ document.frm_callcenter.btn_acao.value='final';document.frm_callcenter.submit();}else{ return; }";
			} else {
				echo "document.frm_callcenter.btn_acao.value='final';liberar_campos();document.frm_callcenter.submit();";
			 } ?>
			}
			">
		</td>
	</tr>
	</table>
</td>
</tr>

<? if(strlen($callcenter)>0){ ?>
<tr>
	<td align='center' colspan='5'>
		<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
		<tr>
			<td align='right' width='150'></td>
			<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
			<td align='center'><STRONG>Por favor, queira anotar o nº do protocolo de atendimento</STRONG><BR>
			Número <font color='#D1130E'><?echo $callcenter;?></font>
			</td>
			<td align='right' width='150'></td>
		</tr>
		</table><BR>
	</td>
</tr>
<tr>
	<td><a href='callcenter_interativo_print.php?callcenter=<?echo $callcenter;?>' target='_blank' style='font-size:10px;font-family:Verdana;'><img src='imagens/img_impressora.gif'>Imprimir</a></td>
</tr>
<tr>
	<td align='center' colspan='5'>
		<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
		<tr>
			<td align='right' width='150'></td>
			<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
			<td align='center'><STRONG>Posso ajudá-lo(a) em algo mais Sr.(a)?</STRONG><BR>
			</td>
			<td align='right' width='150'></td>
		</tr>
		<tr>
			<td align='right' width='150'></td>
			<td align='right' width='55'></td>
			<td align='center'>
				<input type="hidden" name="hd_chamado_anterior" value="<?=$_POST['hd_chamado_anterior']?>" id="chamado_anterior" />
				<? //HD 94971 acrescentei um botão que pergunta se quer enviar o históico ou não
					if($login_fabrica==59){
				?>
						<script>
							function questiona(){
								{
									var name=confirm("Võcê deseja herdar o histórico desse chamado?")
									if (name==true)
									{
										document.forms['frm_callcenter'].submit();
										window.location='<?=$PHP_SELF?>?Id=<?echo $callcenter;?>&herdar=sim';
									}
									else
									{
										window.location='<?=$PHP_SELF?>?Id=<?echo $callcenter;?>';
									}
								}
							}
						</script>
						<input  class="input"  type="button" name="bt" value='Sim' onclick="javascript:questiona();">

				<?
					}elseif($login_fabrica==24){
				?>
						<script>
							function questiona(){
								{
									var name=confirm("Dar continuidade ao chamado atual?")
									
									if (name==true)
									{
										document.forms['frm_callcenter'].submit();
										window.location='<?=$PHP_SELF?>?Id=<?echo $callcenter;?>&continuar_chamado=sim';
									}
									else
									{
										window.location='<?=$PHP_SELF?>?Id=<?echo $callcenter;?>';
									}
								}
							}
						</script>
						<input  class="input"  type="button" name="bt" value='Sim' onclick="javascript:questiona();">

				<?
					}else{
				?>
						<input  class="input"  type="button" name="bt" value='Sim' onclick="javascript:window.location='<?=$PHP_SELF?>?Id=<?echo $callcenter;?>';">
				<?
					}
				?>
				<input  class="input"  type="button" name="bt" value='No' onclick="javascript:window.location='<?=$PHP_SELF?>';">
			</td>
			<td align='right' width='150'></td>
		</tr>
		</table>
		<bR>
	</td>
</tr>
<tr>
	<td align='center' colspan='5'>
			<table width='100%' border='0' align='center' cellpadding="2" cellspacing="2" style=' border:#CC3300 1px solid; background-color: #F4E6D7;font-size:10px'>
			<tr>
				<td align='right' width='150'></td>
				<td align='right' width='55'><img src='imagens/ajuda_call.png' align=absmiddle></td>
				<td align='center'><STRONG>FINALIZAR LIGAÇÃO</STRONG><BR>
				A <?echo "$nome_da_fabrica";?> agradece a sua ligação, tenha um(a) <?echo saudacao();?>.
				</td>
				<td align='right' width='150'></td>
			</tr>
			</table>
	</td>
</tr>
 <? } ?>
</table>
</form>

<? include "rodape.php";?>
