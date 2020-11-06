<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

//  Funções próprias...
function iif($condition, $val_true, $val_false = "") {
	if (is_numeric($val_true) and is_null($val_false))
		$val_false = 0;
	if (is_null($val_true) or is_null($val_false) or !is_bool($condition)) 
		return null;
	return ($condition) ? $val_true : $val_false;
}

function anti_injection($string) {
	$a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
	return strtr(strip_tags(trim($string)), $a_limpa);
}

function getPost($param,$get_first = false) {
//  Procura o valor do parâmetro $param no $_POST e no $_GET (ou no $_GET e no $_POST, se o segundo parâmetro for 'true')
	if ($get_first) {
		if (isset($_GET[$param]))
			return anti_injection($_GET[$param]);
		if (isset($_POST[$param])) 
			return anti_injection($_POST[$param]);
	} else {
		if (isset($_POST[$param]))
			return anti_injection($_POST[$param]);
		if (isset($_GET[$param]))
			return anti_injection($_GET[$param]);
	}
	return null;
}

$admin_privilegios = "gerencia,call_center";
include "autentica_admin.php";

#HD 14830
#HD 13618
// De volta ao formato tradicional à pedido popular :)
// HD 211825: Liberar intervenção de OS para todas as fábricas >=81
if ($login_fabrica==2  OR $login_fabrica==3  OR $login_fabrica==6  OR $login_fabrica==11 OR
	$login_fabrica==25 OR $login_fabrica==45 OR $login_fabrica==51 OR $login_fabrica==35 OR
	$login_fabrica==14 OR $login_fabrica==52 OR $login_fabrica==19 OR $login_fabrica ==43 OR
	$login_fabrica==72 OR $login_fabrica >= 80) {
} else {
	header("Location: menu_callcenter.php");
	exit();
}

/*	29/12/2009 MLG - Define as fábricas que usam a os_intervencao, e assina o serviço realizado e o ajuste. A fábrica é uma 'key' do array, e o serviço_realizado e o ajuste estão nessa ordem, separados por vírgula */

$a_usam_intervencao = array(2 => "7,67", 3 => "20,96", 6 => "1,35", 11 => "61,498", 14 => "",19 => "",25 => "", 35 => "", 43=>"722,631",45 => "640,639", 51 => "673,671", 52 => "",85 => "8430,8431");
$a_usa_filtro_linha = array(3); // Fábricas que tem abilitado o filtro por linha

/*  29/12/2009 MLG - HD 179837 - Resposta AJAX consulta linhas do Posto */
if($_GET['ajax'] == 'linhas_posto') {
	$info_posto = getPost("info_posto");
	$tipo_info  = getPost("tipo_info");

	// Sem código ou razão social, mostrar todas as linhas
	if ($info_posto == "" or is_null($info_posto)){
		$sql = "SELECT linha,codigo_linha,nome FROM tbl_linha WHERE fabrica=$login_fabrica AND ativo IS TRUE";
	} else {
		if ($info_posto == null or $info_posto == ""){
			echo "ko - NO INFO"; exit;
		}
		if ($tipo_info =="codigo"){
			$cond	= "codigo_posto = '$info_posto'";
		}else{
			$cond	= "UPPER(nome) LIKE UPPER('%$info_posto%')";
		}
		$sql= "SELECT posto
				FROM tbl_posto_fabrica
					JOIN tbl_posto USING (posto)
				WHERE fabrica = $login_fabrica AND $cond";
		$res = @pg_query($con,$sql);
		if (!is_resource($res)){
			echo "ko - POSTO QUERY ERROR";exit;
		}
		$id_posto = @pg_fetch_result($res, 0, posto);
		if (!is_numeric($id_posto)){
			echo "ko - NENHUM POSTO";exit;
		}
		$sql = "SELECT tbl_posto_linha.linha,tbl_linha.codigo_linha,nome
					FROM tbl_posto_linha JOIN tbl_linha USING (linha)
				WHERE posto = $id_posto AND
					tbl_linha.ativo IS TRUE AND
					tbl_linha.fabrica = $login_fabrica";
	}
	$res_linhas = pg_query($con, $sql);
	if (($num_linhas = pg_num_rows($res_linhas)) > 0) {
		echo "<legend>Selecione a(s) linha(s)</legend>\n";
		for ($i = 0; $i < $num_linhas; $i++) {
			list ($linha_id, $codigo_linha, $linha_desc) = pg_fetch_row($res_linhas, $i);
			echo "\t\t\t\t<input type='checkbox' name='linhas[]' value='$linha_id' title='$linha_desc'>\n".
				"\t\t\t\t<label class='table_line' title='$linha_des'>$codigo_linha</label>\n";
		}
	}
exit;
}// FIM AJAX atualiza linhas por posto

//  Define o serviço realizado e o ajuste, segundo o fabricante. Se o fabricante não definiu, segue o padrão da Britânia
if ($a_usam_intervencao[$login_fabrica] != "") {
	list($id_servico_realizado,$id_servico_realizado_ajuste) = explode(",", $a_usam_intervencao[$login_fabrica]);
} else { # padrao BRITANIA
	$id_servico_realizado=20;
	$id_servico_realizado_ajuste = 96;
}

$msg = "";
$meses = array(1 => "Janeiro",	"Fevereiro","Março",	"Abril",	"Maio",		"Junho",
					"Julho",	"Agosto",	"Setembro",	"Outubro",	"Novembro",	"Dezembro");

function converte_data($date){
	$date = explode("-", str_replace('/', '-', $date));
	$date2 = ''.$date[2].'/'.$date[1].'/'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else 
		return false;
}

$os = getPost("os",true);

if (strlen(trim($_GET['retirar_intervencao']))>0){
	$retirar_intervencao = trim($_GET['retirar_intervencao']);
}

if (isset($_GET['msg_erro']) && strlen(trim($_GET['msg_erro']))>0)
	$msg_erro=trim($_GET['msg_erro']);

if (isset($_GET['msg']) && strlen(trim($_GET['msg']))>0)
	$msg=trim($_GET['msg']);

$str_filtro = "&btnacao=filtrar";
$ordem = "nome";

if (trim($_GET['janela'])=="sim") {
	$os   = trim($_GET['os']);
	$tipo = trim($_GET['tipo']);
	// OS não excluída
	$sql =  "SELECT tbl_os.os                                                     ,
				tbl_os.sua_os                                                     ,
				LPAD(tbl_os.sua_os,20,'0')                   AS ordem             ,
				TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao         ,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
				TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf           ,
				current_date - tbl_os.data_abertura          AS dias_aberto       ,
				tbl_os.data_abertura                         AS abertura_os       ,
				tbl_os.serie                                                      ,
				tbl_os.consumidor_nome                                            ,
				tbl_posto_fabrica.codigo_posto                                    ,
				tbl_posto.nome                              AS posto_nome         ,
				tbl_posto_fabrica.contato_fone_comercial    AS posto_fone         ,
				tbl_produto.referencia                      AS produto_referencia ,
				tbl_produto.descricao                       AS produto_descricao  ,
				tbl_produto.troca_obrigatoria               AS troca_obrigatoria  ,
				(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (67,68,70) ORDER BY data DESC LIMIT 1) AS reincindente,
				(SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65) ORDER BY data DESC LIMIT 1) AS status_descricao,
				(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65) ORDER BY data DESC LIMIT 1) AS status_os,
				(SELECT data FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65) ORDER BY data DESC LIMIT 1) AS status_pedido,
				(SELECT TO_CHAR(data,'DD/MM/YYYY') AS data FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65,127,147) ORDER BY data DESC LIMIT 1) AS status_data2
			FROM tbl_os
				JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
			WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_os.os=$os";

	$res = pg_query($con,$sql);
	$total=pg_num_rows($res);

	if ($total>0){
		$os                 = trim(pg_fetch_result($res,0,os));
		$sua_os             = trim(pg_fetch_result($res,0,sua_os));
		$data_nf            = trim(pg_fetch_result($res,0,data_nf));
		$digitacao          = trim(pg_fetch_result($res,0,digitacao));
		$abertura           = trim(pg_fetch_result($res,0,abertura));
		$serie              = trim(pg_fetch_result($res,0,serie));
		$consumidor_nome    = trim(pg_fetch_result($res,0,consumidor_nome));
		$codigo_posto       = trim(pg_fetch_result($res,0,codigo_posto));
		$posto_nome         = trim(pg_fetch_result($res,0,posto_nome));
		$posto_fone         = trim(pg_fetch_result($res,0,posto_fone));
		$produto_referencia = trim(pg_fetch_result($res,0,produto_referencia));
		$produto_descricao  = trim(pg_fetch_result($res,0,produto_descricao));
		$posto_fone         = substr(trim(pg_fetch_result($res,0,posto_fone)),0,17);
		$status_os          = trim(pg_fetch_result($res,0,status_os));
		$status_descricao   = trim(pg_fetch_result($res,0,status_descricao));
		$dias_abertura      = trim(pg_fetch_result($res,0,dias_aberto));
		echo "<html><head><title>";
		echo "Intervenção";
		echo "</title></head><body>";
		echo "<style>body{padding:2px;margin:0px;font-size:10px;font-family:Verdana,Tahoma,Arial}.frm {BORDER: '#888888 1px solid';FONT-WEIGHT: 'bold'; FONT-SIZE: '8pt'; BACKGROUND-COLOR: '#f0f0f0';}</style>";
		echo "<form name='frm_form' method='post' action='$PHP_SELF'>";
		echo "<input name='os' value='$os' type='hidden'>";
		echo "<input type='hidden' name='btn_tipo' value='$tipo'>";
		echo "<div>";
		echo "<h4 style='width:100%;background-color:#596D9B;color:white;text-align:center;padding:5px;margin:0px'>INTERVENÇÃO TÉCNICA</h4>\n";
		if($tipo=='cancelar'){
			echo "<h4 style='font-size:12px;width:100%;background-color:#EF4B4B;color:white;text-align:center;padding:3px;margin:0px'>CANCELAR PEDIDO</h4>\n";
		}
		if($tipo=='reparar' and $login_fabrica == 11){
			echo "<h4 style='font-size:12px;width:100%;background-color:#F7D909;color:white;text-align:center;padding:3px;margin:0px'>REPARAR</h4>\n";
		}else{
			if($tipo=='autorizar'){
				echo "<h4 style='font-size:12px;width:100%;background-color:#34BC3F;color:white;text-align:center;padding:0px;'>AUTORIZAR PEDIDO</h4>\n";
			}
		}
		echo "<h4 style='font-size:12px;width:100%;background-color:#E1E1E1;margin:0px;'>Posto</h4>";
		echo "<br>Código: $codigo_posto - $posto_nome\n";
		echo "<br>Telefone: $posto_fone\n";
		echo "<br>";
		echo "<br><h4 style='font-size:12px;width:100%;background-color:#E1E1E1;margin:0px;'>Ordem de Serviço</h4>";
		echo "<br>Número OS <b>$sua_os</b>\n";
		echo "<br>Data Abertura: <b>$abertura</b>\n";
		echo "<br>";
		echo "Data da Nota Fiscal: <b>$data_nf</b> \n";
		echo "<br>";
		echo "<br><h4 style='font-size:12px;width:100%;background-color:#E1E1E1;margin:0px;'>Produto</h4>";
		echo "<br>";
		echo "Referência: $produto_referencia - $produto_descricao \n";
		$sql_peca = "SELECT tbl_os_item.os_item,
							tbl_peca.troca_obrigatoria AS troca_obrigatoria,
							tbl_peca.retorna_conserto AS retorna_conserto,
							tbl_peca.bloqueada_garantia AS bloqueada_garantia,
							tbl_peca.referencia AS referencia,
							tbl_peca.descricao AS descricao,
							tbl_peca.peca AS peca,
							tbl_os_item.servico_realizado AS servico_realizado
					FROM tbl_os_produto
						JOIN tbl_os_item USING(os_produto)
						JOIN tbl_peca USING(peca)
					WHERE tbl_os_produto.os=$os";
		$res_peca = pg_query($con,$sql_peca);
		$resultado = pg_num_rows($res_peca);
		if ($resultado>0){
			echo "<br>";
			echo "<br><h4 style='font-size:12px;width:100%;background-color:#E1E1E1;margin:0px;'>Peças</h4>";
			for($j=0;$j<$resultado;$j++){
				$peca_referencia         = trim(pg_fetch_result($res_peca,$j,referencia));
				$peca_descricao          = trim(pg_fetch_result($res_peca,$j,descricao));
				$bloqueada_garantia      = trim(pg_fetch_result($res_peca,$j,bloqueada_garantia));
				$retorna_conserto        = trim(pg_fetch_result($res_peca,$j,retorna_conserto));
				$servico_realizado       = trim(pg_fetch_result($res_peca,$j,servico_realizado));
				if ($bloqueada_garantia=='t') 
					$bloqueada_garantia="(bloqueada p/ garantia)";
				else 
					$bloqueada_garantia="";
				if ($retorna_conserto=='t') 
					$retorna_conserto=" <b>*</b> ";
				else 
					$retorna_conserto="";
				if ($servico_realizado==$id_servico_realizado){
						$servico_realizado="<b style='color:gray;font-size:9px;font-weight:normal'>(Troca de Peça)</b>";
				}else{
					$servico_realizado="<b style='color:gray;font-size:9px;font-weight:normal'>(não gera pedido)</b>";
				}
				echo "<br>$retorna_conserto $peca_referencia - $peca_descricao $servico_realizado $bloqueada_garantia \n";
			}
			echo "<br><b style='color:gray;font-size:9px;font-weight:normal'>* Peças com intervenção da fábrica</b>";
		}
		$sql = "SELECT status_os,to_char(data,'DD/MM/YYYY') as data,observacao,admin FROM tbl_os_status WHERE os=$os AND status_os IN (62,64,65) AND tbl_os_status.fabrica_status=$login_fabrica ORDER BY data DESC LIMIT 1";
		$res = pg_query($con,$sql);
		$total=pg_num_rows($res);
		if ($total>0){
			$st_os   = trim(pg_fetch_result($res,0,status_os));
			$st_data = trim(pg_fetch_result($res,0,data));
			$st_obs  = trim(pg_fetch_result($res,0,observacao));
			$st_admin= trim(pg_fetch_result($res,0,admin));
		}
		#Para Tectoy Mostra Histórico
		if ($login_fabrica==6){
			$sql = "SELECT	tbl_os_status.status_os,
							to_char(tbl_os_status.data,'DD/MM/YYYY HH24:MI') as data,
							tbl_os_status.observacao,
							tbl_admin.admin
					FROM tbl_os_status
						LEFT JOIN tbl_admin USING(admin)
					WHERE os=$os
						AND status_os IN (62,64,65)
						AND tbl_os_status.fabrica_status=$login_fabrica
					ORDER BY data ASC";
			$res = pg_query($con,$sql);
			if (pg_num_rows($res)>0){
				echo "<br><br>";
				echo "<h4 style='font-size:12px;width:100%;background-color:#E1E1E1;margin:0px;'>Histórico</h4>";
				for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {
					$hist_stat = trim(pg_fetch_result($res,$i,status_os));
					$hist_data = trim(pg_fetch_result($res,$i,data));
					$hist_obs  = trim(pg_fetch_result($res,$i,observacao));
					$hist_admin= trim(pg_fetch_result($res,$i,admin));
					if ($hist_stat == 62 OR $hist_stat == 65){
						$origem = "<span style='color:green'>Posto</span>";
					}else{
						$origem = "<span style='color:blue'>Fábrica</span>";
					}
					$hist_obs = str_replace("Peça da O.S. com intervenção da fábrica","",$hist_obs);
					echo "<br><b>$hist_data</b> ($hist_admin) - $origem: $hist_obs\n";
					echo "<br>\n";
				}
				echo "<br>\n";
			}
		}
		
		if ($tipo=="cancelar"){
			$msg_titulo="Justificativa do Cancelamento";
		}
		if ($tipo=="reparar" and $login_fabrica == 11){
			$msg_titulo="Será feito o reparo deste produto na fabrica, informe abaixo a justificativa:";
		}elseif ($tipo == "autorizar"){
			$msg_titulo="Justificativa da Autorização";
		}
		echo "<br>";
		echo "<br><b style='width:100%;background-color:#A9C1E0;text-align:center'>$msg_titulo</b>";
		echo "<br>";
		echo "<textarea NAME='justificativa' style='width:100%'rows='5' class='frm' maxlength='40'></textarea>";
		echo "<br>";
		echo "<input type='hidden' name='btn_acao' value=''>";
		echo "<center><img src='imagens/btn_gravar.gif' onclick=\"javascript:
			if (document.frm_form.justificativa.value != '' ){
				if (document.frm_form.btn_acao.value == '' ) {
					if (confirm('Deseja continuar?')) {
						document.frm_form.btn_acao.value='gravar' ;
						document.frm_form.submit();
					}
				}else {
					alert ('Aguarde submissão');
				}
			}
			else{
				alert('Digite a justificativa!');
			}
			\" ALT='Gravar' border='0' style='cursor:pointer;'></center>";
		echo "</div>";
		echo "</form>";
		echo "</body></html>";
		exit();
	}
}

if (getPost('btnacao')  == 'filtrar') {
	$ordem = getPost('ordem');
	if (strlen($ordem)>0){
		$sql_ordem = " ORDER BY ";
		switch ($ordem) {
			case "nome": $sql_ordem.= "tbl_posto.$ordem ASC";
				break;
			case "data_abertura": $sql_ordem.= "tbl_os.$ordem ASC";
				break;
			case "data_pedido": $sql_ordem.= "status_pedido ASC ";
				break;
		}
		$str_filtro .= "&ordem=$ordem";
	}
	$posto_codigo         = getPost("posto_codigo");
	$posto_nome           = getPost("posto_nome");
	$referencia           = getPost("referencia");
	$descricao            = getPost("descricao");
	$produto_referencia   = getPost("produto_referencia");
	$produto_descricao    = getPost("produto_descricao");
	$peca_referencia      = getPost("peca_referencia");
	$peca_descricao       = getPost("peca_descricao");
	if (strlen($peca_referencia)>0 OR strlen($peca_descricao)>0){
		if (strlen($peca_referencia)>0)
			$sql_adicional_2 = " AND tbl_peca.referencia = '$peca_referencia' ";
		else
			$sql_adicional_2 = " AND tbl_peca.descricao like '%$peca_descricao%' ";
		$sql = "SELECT  tbl_peca.referencia as ref, tbl_peca.descricao as desc, tbl_peca.peca as peca
					FROM tbl_peca
				WHERE tbl_peca.fabrica=$login_fabrica
					$sql_adicional_2";
		$res = pg_query ($con,$sql);
		if (pg_num_rows ($res)>0){
			$peca_referencia = pg_fetch_result ($res,0,ref);
			$peca_descricao  = pg_fetch_result ($res,0,desc);
			$peca  = pg_fetch_result ($res,0,peca);
			$sql_adicional_2 = " AND tbl_peca.peca = $peca";
			$str_filtro .= "&peca_referencia=$peca_referencia&peca_descricao=$peca_descricao";
		}
	}
	if (strlen($produto_referencia)>0 OR strlen($produto_descricao)>0){
		if (strlen($produto_referencia)>0){
				$sql_adicional_3 = " AND tbl_produto.referencia = '$produto_referencia' ";
		}else {
				$sql_adicional_3 = " AND tbl_produto.descricao like '%$produto_descricao%' ";
		}
		$sql = "SELECT  tbl_produto.referencia as ref, tbl_produto.descricao as desc, tbl_produto.produto as produto
			FROM tbl_produto
			JOIN tbl_familia USING(familia)
			WHERE tbl_familia.fabrica=$login_fabrica
			$sql_adicional_3";
		$res = pg_query ($con,$sql);
		if (pg_num_rows ($res)>0){
			$produto_referencia = pg_fetch_result ($res,0,ref);
			$produto_descricao  = pg_fetch_result ($res,0,desc);
			$produto  = pg_fetch_result ($res,0,produto);
			$sql_adicional_3 = " AND tbl_produto.produto = $produto";
			$str_filtro .= "&produto_referencia=$produto_referencia&produto_descricao=$produto_descricao";
		}
	}
	// HD 79395 NKS
	$posto_estado = getPost("posto_estado");
	if (strlen($posto_estado)>0){
		$sql_adicional_4 = " AND tbl_posto.estado = '$posto_estado'";
		$str_filtro .= "&posto_estado=$posto_estado";
	}
	if (strlen($posto_codigo)>0 OR strlen($posto_nome)>0){
		if (strlen($posto_codigo)>0 OR strlen($posto_nome)>0){
			if (strlen($posto_codigo)>0)
				$sql_adicional = " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
			else
				$sql_adicional = " AND tbl_posto.nome like '%$posto_nome%' ";
		}
		$sql = "SELECT  tbl_posto_fabrica.codigo_posto as cod, tbl_posto.nome as nome, tbl_posto.posto as posto
			FROM tbl_posto
			JOIN tbl_posto_fabrica USING(posto)
			WHERE tbl_posto_fabrica.fabrica=$login_fabrica
			$sql_adicional";
		$res = pg_query ($con,$sql);
		if (pg_num_rows ($res)>0){
			$posto_codigo = pg_fetch_result ($res,0,cod);
			$posto_nome  = pg_fetch_result ($res,0,nome);
			$posto  = pg_fetch_result ($res,0,posto);
			$sql_adicional = " AND tbl_posto.posto = $posto";
			$str_filtro .= "&posto_codigo=$posto_codigo&posto_nome=$posto_nome";
		}
	}
	// 29/12/2009 MLG - HD 179837 - Adicionar filtro por linha(s)
	if (count($_POST['linhas'])>0) {
		$linhas = implode(",",$_POST['linhas']);
		$sql_adicional	.= " AND tbl_posto_linha.linha IN (".$linhas.")";
		$str_filtro		.= "&linhas=$linhas";
	}
}

#Chamado 18406 - Serve para retirar da intervenção quando a OS está em intervenção - reparo na fábrica
if (trim($retirar_intervencao)=="1" && strlen($os) > 0  ) {
	$sql = "SELECT sua_os
			FROM tbl_os
			WHERE os    = $os
			AND fabrica = $login_fabrica";
	$res = pg_query($con,$sql);
	if (pg_num_rows($res) > 0){
		$sua_os = trim(pg_fetch_result($res,0,sua_os));
		$sql = "SELECT os_status,status_os
				FROM tbl_os_status
				WHERE os = $os
				AND status_os IN (62,64,65)
				AND tbl_os_status.fabrica_status=$login_fabrica
				ORDER BY tbl_os_status.data
				DESC LIMIT 1";
		$res = pg_query($con,$sql);
		if (pg_num_rows($res) > 0){
			$os_status = trim(pg_fetch_result($res,0,os_status));
			$st_os     = trim(pg_fetch_result($res,0,status_os));
			if ($st_os=='65'){
				$msg_erro = "";
				$res = @pg_query($con,"BEGIN TRANSACTION");
				$sql = "INSERT INTO tbl_os_status
						(os,status_os,data,observacao,admin)
						VALUES ($os,64,current_timestamp,'OS Liberada da Intervenção',$login_admin)";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
				if (strlen($msg_erro)>0){
					$msg = " Não foi possível retirar a OS de intervenção. Tente novamente.";
					$res = @pg_query ($con,"ROLLBACK TRANSACTION");
				}else {
					$msg = " OS ".$sua_os." retirada da Intervenção.";
					$res = @pg_query ($con,"COMMIT TRANSACTION");
				}
			}
			if ($st_os == 62 and $login_fabrica == 51) {
				$msg_erro = "";
				$res = @pg_query($con,"BEGIN TRANSACTION");
				$sql = "INSERT INTO tbl_os_status
						(os,status_os,data,observacao,admin)
						VALUES ($os,64,current_timestamp,'OS Liberada da Intervenção',$login_admin)";
				$res = pg_query($con,$sql);
				$msg_erro .= pg_errormessage($con);
				$sql = "UPDATE tbl_os_item SET servico_realizado = 671 FROM tbl_os_produto
						WHERE tbl_os_produto.os_produto = tbl_os_item.os_produto
						AND   tbl_os_produto.os = $os";
				$res = pg_query($con,$sql);
				$erro .= pg_errormessage($con);
				$sql = "UPDATE tbl_os SET defeito_constatado = 10536,solucao_os = 491
						WHERE tbl_os.os = $os";
				$res = pg_query($con,$sql);
				$erro .= pg_errormessage($con);
				if (strlen($msg_erro)>0){
					$msg = " Não foi possível retirar a OS de intervenção. Tente novamente.";
					$res = @pg_query ($con,"ROLLBACK TRANSACTION");
				}else {
					$msg = " OS ".$sua_os." retirada da Intervenção.";
					$res = @pg_query ($con,"COMMIT TRANSACTION");
				}
			}
		}
	}
}

if (getPost("btn_tipo") == "cancelar" && strlen($os) > 0) {
	$os				= getPost("os");
	$justificativa	= getPost("justificativa");
	if (strlen($justificativa)>0){
		$justificativa = "Justificativa: $justificativa";
	}
	$sql = "SELECT sua_os,
					posto
				FROM tbl_os
				WHERE os=$os";
	$res = pg_query($con,$sql);
	if (pg_num_rows($res)>0){
		$sua_os = trim(pg_fetch_result($res,0,sua_os));
		$posto = trim(pg_fetch_result($res,0,posto));
	}
	$sql = "SELECT status_os,
				to_char(data,'DD/MM/YYYY') as data,
				observacao,admin
				FROM tbl_os_status
				WHERE os=$os
				AND status_os IN (62,64,65)
				AND tbl_os_status.fabrica_status=$login_fabrica
				ORDER BY tbl_os_status.data
				DESC LIMIT 1";
	$res = pg_query($con,$sql);
	$total=pg_num_rows($res);
	if ($total>0){
		$st_os   = trim(pg_fetch_result($res,0,status_os));
		$st_data = trim(pg_fetch_result($res,0,data));
		$st_obs  = trim(pg_fetch_result($res,0,observacao));
		$st_admin= trim(pg_fetch_result($res,0,admin));
		if ($st_os=='64'){
			echo "<html><head><title='Intervenção'></head><body>";
			echo "OS LIBERADA!!!<br><br>id OS: $os<br>EM: $st_data<br>Observacao: $st_obs<br>Admin: $st_admin";
			echo "<script language='javascript'>";
			echo "opener.document.location = '$PHP_SELF';";
			echo "setTimeout('window.close()',5000);";
			echo "</script>";
			echo "</body>";
			echo "</html>";
			exit();
		}
	}
	$res = @pg_query($con,"BEGIN TRANSACTION");
	$sql = "INSERT INTO tbl_os_status
			(os,status_os,data,observacao,admin)
			VALUES ($os,64,current_timestamp,'Pedido de Peças Cancelado. $justificativa',$login_admin)";
	$res = pg_query($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strlen($msg_erro)==0){
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
					'Pedido de Peças CANCELADO'           ,
					'O pedido das peças referente a OS $sua_os foi <b>cancelado</b> pela fábrica. <br><br>$justificativa',
					'Pedido de Peças',
					$login_fabrica,
					'f' ,
					't',
					$posto,
					't'
				);";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}
	if (strlen($msg_erro)==0){
		//HD 255530: Alterei pois não faz sentido para a Nova, ninguém pediu esta condição e sem ela as peças vão ser pedidas
		if ($login_fabrica <> 43) {
			$retorno_conserto_sql = "AND   tbl_peca.retorna_conserto IS TRUE";
		}
		//HD 255530 FIM

		$sql =  "UPDATE tbl_os_item SET
				servico_realizado          = $id_servico_realizado_ajuste,
				admin                      = $login_admin                ,
				liberacao_pedido           = FALSE                       ,
				liberacao_pedido_analisado = FALSE                       ,
				data_liberacao_pedido      = NULL
			 WHERE os_item IN (
				SELECT os_item
				FROM tbl_os
				JOIN tbl_os_produto USING(os)
				JOIN tbl_os_item    USING(os_produto)
				JOIN tbl_peca       USING(peca)
				WHERE tbl_os.os                     = $os
				AND   tbl_os.fabrica                = $login_fabrica
				AND   tbl_os_item.servico_realizado = $id_servico_realizado
				AND   tbl_os_item.pedido IS NULL
				/* HD 255530 - Somente a linha $retorna_conserto_sql */
				$retorna_conserto_sql
			)";
		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (strlen($msg_erro)>0){
			$res = @pg_query ($con,"ROLLBACK TRANSACTION");
		}else {
			$res = @pg_query ($con,"COMMIT TRANSACTION");
			$msg = "Pedido de peças da OS $sua_os foi cancelado! A OS foi liberada para o posto";
		}
	}
	echo "<html><head><title='Intervenção'></head><body>";
	echo "<script language='javascript'>";
	echo "opener.document.frm_consulta.btnacao.value='filtrar';";
	echo "opener.document.frm_consulta.submit();";
	if (strlen($msg_erro)>0){
		echo "Erro: $msg_erro<br>Faça o processo novamente.";
	}else{
		echo "this.close();";
	}
	echo "</script>";
	echo "</body>";
	echo "</html>";
	exit();
}

if (getPost("reparar",true) && strlen($os) > 0) {
	$sua_os = trim(getPost("reparar",true));
	$res = @pg_query($con,"BEGIN TRANSACTION");
	$justificativa	= getPost("justificativa");
	if (strlen($justificativa)>0){
		$justificativa = "Justificativa: $justificativa";
	}
	$sql = "INSERT INTO tbl_os_status
			(os,status_os,data,observacao,admin)
			VALUES ($os,65,current_timestamp,'Reparo do produto deve ser feito pela fábrica $justificativa',$login_admin)";
	$res = pg_query($con,$sql);
	$msg_erro = pg_errormessage($con);
	$sql = "INSERT INTO tbl_os_retorno (os) VALUES ($os)";
	$res = pg_query($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strlen($msg_erro)==0){
		$sql =  "UPDATE tbl_os_item
				SET servico_realizado=$id_servico_realizado_ajuste,
				liberacao_pedido = FALSE,
				liberacao_pedido_analisado = FALSE,
				data_liberacao_pedido = null,
				admin=$login_admin
				WHERE os_item IN (
				SELECT os_item
				FROM tbl_os
				JOIN tbl_os_produto USING(os)
				JOIN tbl_os_item USING(os_produto)
				JOIN tbl_peca USING(peca)
				WHERE tbl_os.os=$os
				AND tbl_os.fabrica=$login_fabrica
				AND tbl_os_item.servico_realizado=$id_servico_realizado
				AND tbl_os_item.pedido IS NULL)";
		$res = pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
		if (strlen($msg_erro)>0){
			$res = @pg_query ($con,"ROLLBACK TRANSACTION");
		}
		else {
			$res = @pg_query ($con,"COMMIT TRANSACTION");
		}
	}
	if($login_fabrica == 11){?>
		<html>
			<head>
				<title='Intervenção'>
			</head>
			<body>
				<script language='javascript'>
					opener.document.location = '<?=$PHP_SELF?>';
					setTimeout('window.close()',5000);
				</script>
			</body>
		</html><?
		exit();
	}else{
		header("Location: $PHP_SELF?msg=$msg$str_filtro");
	}
}

# antigo
if (trim($_POST['btn_tipo'])=="autorizar" && strlen($os) > 0) {
	$os  			=trim($_POST['os']);
	$justificativa	=trim($_POST['justificativa']);
	if (strlen($justificativa)>0){
		$justificativa = "Justificativa: $justificativa";
	}else{
		$msg_erro = "Informe a justificativa!";
	}
	$sql = "SELECT status_os,to_char(data,'DD/MM/YYYY') as data,observacao,admin FROM tbl_os_status WHERE os=$os AND status_os IN (62,64,65) AND tbl_os_status.fabrica_status=$login_fabrica ORDER BY tbl_os_status.data DESC LIMIT 1";
	$res = pg_query($con,$sql);
	$total=pg_num_rows($res);
	if ($total>0){
		$st_os   = trim(pg_fetch_result($res,0,status_os));
		$st_data = trim(pg_fetch_result($res,0,data));
		$st_obs  = trim(pg_fetch_result($res,0,observacao));
		$st_admin= trim(pg_fetch_result($res,0,admin));
		if ($st_os=='64'){
			echo "<html><head><title='Intervenção'></head><body>";
			echo "OS LIBERADA!!!<br><br>id OS: $os<br>EM: $st_data<br>Observacao: $st_obs<br>Admin: $st_admin";
			echo "<script language='javascript'>";
			echo "opener.document.location = '$PHP_SELF';";
			echo "setTimeout('window.close()',3000);";
			echo "</script>";
			echo "</body>";
			echo "</html>";
			exit();
		}
	}
	$res = @pg_query($con,"BEGIN TRANSACTION");
	if($login_fabrica==19) {
		$obs_lib = "OSs Aprovada da Intervensão Pela Fábrica";
	} else {
		$obs_lib = "Pedido de Peças Autorizado Pela Fábrica ".$justificativa;
	}

	$sql = "INSERT INTO tbl_os_status
			(os,status_os,data,observacao,admin)
			VALUES ($os,64,current_timestamp,'".$obs_lib."',$login_admin)";
	$res = pg_query($con,$sql);
	$msg_erro = pg_errormessage($con);

	if (strlen($msg_erro)>0){
		$res = @pg_query ($con,"ROLLBACK TRANSACTION");
	}else {
		$res = @pg_query ($con,"COMMIT TRANSACTION");
		$msg = "Pedido de peças da OS $sua_os foi autorizado. A OS foi liberada para o posto";
	}?>
	<html>
		<head>
			<title='Intervenção'>
		</head>
		<body>
			<script language='javascript'>
				opener.document.frm_consulta.btnacao.value='filtrar';
				opener.document.frm_consulta.submit();
				this.close();
			</script>
			<br>
		</body>
	</html><?
	exit();
}

// HD 21341
$justific     = $_POST['justific'];

if(strlen($justific)==0){
	$justific="Pedido de Peças Autorizado Pela Fábrica";
}
$autorizar		= $_POST['autorizar'];
$autorizar_os	= $_POST['autorizar_os'];

if(strlen($os) == 0) {
	$autorizar_os = $_GET['autorizar_os'];
}

if(($login_fabrica==3 or $login_fabrica == 14) and strlen($justific) >0 and strlen($autorizar) >0) {
	$res = @pg_query($con,"BEGIN TRANSACTION");
	$sql = "INSERT INTO tbl_os_status
			(os,status_os,data,observacao,admin)
			VALUES ($autorizar_os,64,current_timestamp,'$justific',$login_admin)";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);

	if (strlen($msg_erro)>0){
		$res = @pg_query ($con,"ROLLBACK TRANSACTION");
	}else {
		$res = @pg_query ($con,"COMMIT TRANSACTION");
		$msg = "Pedido de peças da OS $autorizar foi autorizado. A OS foi liberada para o posto";
	}
	header("Location: $PHP_SELF?msg=$msg$str_filtro");
	exit();
}

if ($login_fabrica == 35 and trim($_GET['cancelar']) == 'sim' and strlen($os) > 0) {
	$sua_os=trim($_GET['autorizar']);
	$res = @pg_query($con,"BEGIN TRANSACTION");
	$sql = "INSERT INTO tbl_os_status
			(os,status_os,data,observacao,admin)
			VALUES ($os,13,current_timestamp,'Os Recusada Pela Fabrica',$login_admin)";
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	$sqlx = "UPDATE tbl_os SET excluida = 't' WHERE os = $os";
	$resx = pg_query($con, $sqlx);
	$msg_erro .= pg_errormessage($con);
	#158147 Paulo/Waldir desmarcar se for reincidente
	$sql = "SELECT fn_os_excluida_reincidente($os,$login_fabrica)";
	$res = pg_query($con, $sql);
	if (strlen($msg_erro)>0) {
		$res = @pg_query ($con,"ROLLBACK TRANSACTION");
	}else {
		$res = @pg_query ($con,"COMMIT TRANSACTION");
		$msg = "Os Cancelada com sucesso";
	}
	header("Location: $PHP_SELF?msg=$msg");
	exit();
}

if (strlen(trim($_GET['autorizar'])) > 0 && strlen($os) > 0  ) {
	$sua_os=trim($_GET['autorizar']);
	$res = @pg_query($con,"BEGIN TRANSACTION");
	$sql = "INSERT INTO tbl_os_status
			(os,status_os,data,observacao,admin)
				VALUES ($os,64,current_timestamp,'Pedido de Peças Autorizado Pela Fábrica',$login_admin)";
	//mesmo status, mas com observação diferente
	if ($login_fabrica == 52 ) {
		$sql = "INSERT INTO tbl_os_status
				(os,status_os,data,observacao,admin)
				VALUES ($os,19,current_timestamp,'OSs Aprovada da Intervensão Pela Fábrica',$login_admin)";
	}
	if ($login_fabrica == 35) {
		$sql = "INSERT INTO tbl_os_status
				(os,status_os,data,observacao,admin)
				VALUES ($os,19,current_timestamp,'Pedido de Peças Autorizado Pela Fábrica',$login_admin)";
	}
	$res = pg_query($con,$sql);
	$msg_erro .= pg_errormessage($con);
	if ($login_fabrica==6){
		# Peças com serviço ENVIO PARA A FABRICA, QUANDO AUTORIZA TROCA O SERVIÇO PARA TROCA DE PEÇA
		$sql = "UPDATE tbl_os_item
				SET servico_realizado = 1
				WHERE servico_realizado = 485
				AND os_produto IN (
					SELECT os_produto FROM tbl_os_produto WHERE os=$os
				)";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	if($login_fabrica == 85) {
		$sql = " UPDATE tbl_os_item SET liberacao_pedido = 't'
				 FROM tbl_os_produto 
				 WHERE tbl_os_produto.os_produto = tbl_os_item.os_produto
				 AND   os = $os";
		$res = @pg_query($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
	
	if (strlen($msg_erro)>0){
		$res = @pg_query ($con,"ROLLBACK TRANSACTION");
	}else {
		$res = @pg_query ($con,"COMMIT TRANSACTION");
		$msg = "Pedido de peças da OS $sua_os foi autorizado. A OS foi liberada para o posto";
		header("Location: $PHP_SELF?msg=$msg$str_filtro");
		exit();
	}
}

if (strlen($_GET['trocar']) > 0 && strlen($os) > 0  ) {
	$sua_os=trim($_GET['trocar']);
	if (strlen($sua_os)>0){
		header("Location: os_cadastro.php?os=$os$str_filtro&osacao=trocar");
		exit();
	}
	## ao inves de colocar o status novo, redireciona e o status novo vai ser colocado na os_cadastro.php
	$res = @pg_query($con,"BEGIN TRANSACTION");
	$sql = "INSERT INTO tbl_os_status
			(os,status_os,data,observacao,admin)
			VALUES ($os,64,current_timestamp,'Troca do Produto',$login_admin)";
	$res = pg_query($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strlen($msg_erro)>0){
		$res = @pg_query ($con,"ROLLBACK TRANSACTION");
		header("Location: $PHP_SELF?msg_erro=$msg_erro");
		exit();
	}
	else {
		$res = @pg_query ($con,"ROLLBACK TRANSACTION");
		header("Location: os_cadastro.php?os=$os$str_filtro");
		exit();
	}
}

if (strlen($_POST['confirmar_chegada']) > 0 && strlen(trim($_POST['txt_data_envio_chegada']))>0) {
	$os = trim($_GET['autorizar_os']);
	$data_envio_chegada=trim($_POST['txt_data_envio_chegada']);
	$data_envio_chegada= @converte_data($data_envio_chegada);
	if ($data_envio_chegada==false)
		$msg_erro.="Data de chegada a fábrica inválida!";
	$data_envio_chegada_x = $data_envio_chegada." ".date("H:i:s");
	if (strlen($msg_erro)==0){
		$res = @pg_query($con,"BEGIN TRANSACTION");
		$sql =  "UPDATE tbl_os_retorno
				SET envio_chegada='$data_envio_chegada_x',
					admin_recebeu=$login_admin
				WHERE os=$os";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);
		if (strlen($msg_erro)>0){
			$res = @pg_query ($con,"ROLLBACK TRANSACTION");
		}
		else {
			$res = @pg_query ($con,"COMMIT TRANSACTION");
		}
	}
	header("Location: $PHP_SELF?msg_erro=$msg_erro$str_filtro");
}

if (strlen($_POST['confirmar_retorno']) > 0 && strlen(trim($_POST['txt_nota_fiscal_retorno']))>0) {
	$os = trim($_GET['autorizar_os']);
	$nota_fiscal_retorno	= trim($_POST['txt_nota_fiscal_retorno']);
	$rastreio_retorno		= trim($_POST['txt_rastreio_retorno']);
	$data_envio_retorno		= trim($_POST['txt_data_envio_retorno']);
	if (strlen($nota_fiscal_retorno)==0 OR strlen($nota_fiscal_retorno)>6 OR (strlen($rastreio_retorno)==0 AND $login_fabrica<>6) OR strlen($data_envio_retorno)!=10){
		$msg_erro.="Dados do Envio à Fábrica incorretos";
	}
	else {
		echo $data_envio_retorno= @converte_data($data_envio_retorno);
		if ($data_envio_retorno==false) $msg_erro .="Data de envio do produto ao posto inválido!";
	}
	if (strlen($msg_erro)==0){
		$res = @pg_query($con,"BEGIN TRANSACTION");
		$sql =  "UPDATE tbl_os_retorno
				SET nota_fiscal_retorno='$nota_fiscal_retorno',
					data_nf_retorno='$data_envio_retorno',
					numero_rastreamento_retorno='$rastreio_retorno',
					admin_enviou=$login_admin
				WHERE os=$os";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);
		if (strlen($msg_erro)>0){
			$res = @pg_query ($con,"ROLLBACK TRANSACTION");
		}
		else {
			$res = @pg_query ($con,"COMMIT TRANSACTION");
		}
	}
	header("Location: $PHP_SELF?msg_erro=$msg_erro$str_filtro");
}

$layout_menu = "callcenter";
$title = "OS's com intervenção da Fábrica";
include "cabecalho.php";
if (in_array($login_fabrica,$a_usa_filtro_linha)) {?>
	<script src="js/jquery-1.3.2.js" type="text/javascript" language="JavaScript"></script>
	<script type="text/javascript" language="JavaScript">
		$().ready(function() {
			$('input[name^=posto_]').change(function () {
				var info_posto = $(this).val();
				var tipo_info  = $(this).attr('name').substr(6);
				var caixa_linhas=$('fieldset#linhas');
				var fs_content = caixa_linhas.html();
				caixa_linhas.html("<p>Atualizando...</p>");
				$.get('<?=$PHP_SELF?>',{
					'ajax':'linhas_posto',
					 'info_posto':info_posto,
					 'tipo_info':tipo_info,
				},function(data){
					if (data.substr(0,2) != 'ko' && data.indexOf("label>") > 0) {
						caixa_linhas.html(data);
					} else {
						caixa_linhas.html(fs_content);
					}
				});
			});
			$('input[name=posto_codigo]').change();
		});
	</script>
<?}?>
<style type="text/css">
	.Tabela{
		border:1px solid #596D9B;
		background-color:#596D9B;
	}
	.Erro{
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 12px;
		color:#CC3300;
		font-weight: bold;
		background-color:#FFFFFF;
	}
	.Titulo {
		text-align: center;
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
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
		font-size:9px;
		height:12px;
	}
	.botao2{
		border:1px solid #666;
		font-size:9px;
	}
	.butt{
		border:1px solid #666;
		background-color:#ccc;
		font-size:9px;
		height:16px;
	}
	.menu_top {
		text-align: center;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: x-small;
		font-weight: bold;
		border: 1px solid;
		color:#ffffff;
		background-color: #596D9B
	}
	.table_line {
		text-align: left;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
		border: 0px solid;
		background-color: #D9E2EF
	}
	label {padding-top:0;position:relative;top:-3px}
	.table_line2 {
		text-align: left;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
	}
	.justificativa{
		font-size: 10px;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	}
</style>

<?php include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>
<script language="javascript">
	function fnc_pesquisa_peca_lista(peca_referencia, peca_descricao, tipo) {
		var url = "";
		if (tipo == "referencia") {
			url = "peca_pesquisa_lista.php?peca=" + peca_referencia.value + "&tipo=" + tipo + "&exibe=/assist/admin/os_intervencao_fabio.php";
		}
		if (tipo == "descricao") {
			url = "peca_pesquisa_lista.php?descricao=" + peca_descricao.value + "&tipo=" + tipo + "&exibe=/assist/admin/os_intervencao_fabio.php";
		}
		if (peca_referencia.value.length >= 3 || peca_descricao.value.length >= 3) {
			janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=501, height=400, top=18, left=0");
			janela.referencia	= peca_referencia;
			janela.descricao	= peca_descricao;
			janela.preco		= document.frm_consulta.preco_null;
			janela.focus();
		}else{
			alert("Digite pelo menos 3 caracteres!");
		}
	}
	function fnc_pesquisa_peca_2 (campo, campo2, tipo) {
		if (tipo == "referencia" ) {
			var xcampo = campo;
		}
		if (tipo == "descricao" ) {
			var xcampo = campo2;
		}
		if (xcampo.value != "") {
			var url = "";
			url = "peca_pesquisa.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
			janela.retorno = "<?php echo $PHP_SELF ?>";
			janela.referencia= campo;
			janela.descricao= campo2;
			janela.focus();
		}
	}
	function MostraEsconde(dados){
		if (document.getElementById){
			var style2 = document.getElementById(dados);
			if (style2==false) 
				return;
			if (style2.style.display=="block"){
				style2.style.display = "none";
			}else{
				style2.style.display = "block";
			}
		}
	}
	function fnc_pesquisa_posto(campo, campo2, tipo) {
		if (tipo == "codigo" ) {
			var xcampo = campo;
		}
		if (tipo == "nome" ) {
			var xcampo = campo2;
		}
		if (xcampo.value != "") {
			var url = "";
			url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
			janela.codigo  = campo;
			janela.nome    = campo2;
			janela.focus();
		}
	}
	function fnc_pesquisa_produto2 (campo, campo2, tipo) {
		if (tipo == "referencia" ) {
			var xcampo = campo;
		}
		if (tipo == "descricao" ) {
			var xcampo = campo2;
		}
		if (xcampo.value != "") {
			var url = "";
			url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&lbm=1" ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
			janela.referencia	= campo;
			janela.descricao	= campo2;
			janela.produto		= document.frm_consulta.produto;
			janela.focus();
		}
	}
	function fnc_pesquisa_produto (campo, campo2, tipo) {
		if (tipo == "referencia" ) {
			var xcampo = campo;
		}
		if (tipo == "descricao" ) {
			var xcampo = campo2;
		}
		if (xcampo.value != "") {
			var url = "";
			url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
			janela.referencia	= campo;
			janela.descricao	= campo2;
			janela.focus();
		}
	}
	function fnc_reparar(os) {
		var url = "<?php echo $PHP_SELF ?>?janela=sim&tipo=reparar&os="+os;
		janela_aut = window.open(url, "_blank", "toolbar=no, location=no, status=no, scrollbars=yes, directories=no, width=300, height=500, top=18, left=0");
		janela_aut.focus();
	}
	function fnc_cancelar(os) {
		var url = "<?php echo $PHP_SELF ?>?janela=sim&tipo=cancelar&os="+os;
		janela_aut = window.open(url, "_blank", "toolbar=no, location=no, status=no, scrollbars=yes, directories=no, width=300, height=500, top=18, left=0");
		janela_aut.focus();
	}
	function fnc_autorizar(os) {
		var url = "<? echo $PHP_SELF ?>?janela=sim&tipo=autorizar&os="+os;
		janela_aut = window.open(url, "_blank", "toolbar=no, location=no, status=no, scrollbars=yes, directories=no, width=300, height=500, top=18, left=0");
		janela_aut.focus();
	}
	function autorizar_os(os,formulario,sua_os){
		eval("var form = document."+formulario);
		var just = prompt('Informe a justificativa da autorização (opcional)','');
		if ( just==null){
			return false;
		}
		form.autorizar.value=sua_os;
		form.justific.value=just;
		if (confirm('Deseja continuar?\n\nOS: '+sua_os)){
			form.submit();
		}
	}
</script>
<br>

<?php
$dias = 5;

if ($login_fabrica == 3) {
	$dias = 1;
}
// HD 204735 não haverá mais tempo para a OS sair automaticamente de INTERVENÇÃO... só saira quando a fabrica confirmar
//if ($login_fabrica == 14) {
//	$dias = 30;
//}

if ($login_fabrica == 19) {
	$dias = 2;
}

if($login_fabrica <> 51){
	#HD 14331
	echo "<div style='background-color:#FCDB8F;width:600px;margin:0 auto;text-align:center;padding:2px 10px 2px 10px;'>";
	echo "<p style='text-align:left;padding:0px;'><b>ATENÇÃO: </b>As OSs em intervenção serão desconsideradas da INTERVENÇÃO automaticamente pelo sistema se não forem analisadas no prazo de $dias dias! O objetivo desta rotina é que o fabricante ajude o posto autorizado, e se isto não acontecer a OS sai da intervenção.</p>";
	echo "<p style='text-align:left'>TELECONTROL</p>";
	echo "</div>";
	echo "<br>";
}?>

<?php
if(strlen($msg_erro)>0){
	echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td bgcolor='FFFFFF' width='60'class='Erro'><img src='imagens/proibido2.jpg' align='middle'></td><td  class='Erro' bgcolor='FFFFFF' align='left'> $msg_erro</td>";
	echo "</tr>";
	echo "</table><br>";
}

if(strlen($msg)>0){
	echo "<center><b style='font-size:12px;border:1px solid #999;padding:10px;background-color:#dfdfdf'>$msg</b></center><br>";
}

echo "<FORM METHOD='POST' NAME='frm_consulta' ACTION=\"$PHP_SELF\">";?>
<input type="hidden" name="preco_null" value="">
<input type='hidden' name='btnacao'>

<table width="500" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr>
		<td colspan="5" class="menu_top">
			<div align="center">
				<b>Pesquisa</b>
			</div>
		</td>
	</tr>
	<tr>
		<?if (!in_array($login_fabrica,$a_usa_filtro_linha))
			echo "<td class='table_line' rowspan='2'>&nbsp;</td>\n";  ?>
		<td width="80" rowspan="2" style='padding-left:1em;' class="table_line">
			Posto
		</td>
		<td class="table_line">
			Código do Posto
		</td>
		<td class="table_line">
			Nome do Posto
		</td>
		<td width="19" class="table_line" style="text-align: left;">
			&nbsp;
		</td>
	</tr>
	<tr>
		<td class="table_line" align="left" nowrap>
			<input type="text" name="posto_codigo" size="10" maxlength="20" value="<? echo $posto_codigo ?>" class="frm">
			<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome, 'codigo')">
		</td>
		<td class="table_line" style="text-align: left;" nowrap>
			<input type="text" name="posto_nome" size="25" maxlength="50"  value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor:pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.posto_codigo, document.frm_consulta.posto_nome, 'nome')">
		</td>
		<td width="19" class="table_line">
			&nbsp;
		</td>
	</tr>
	<? if($login_fabrica <> 51){ ?>
		<tr>
			<td class="table_line" style="text-align: center;color:gray" colspan='5'>
				* Busca por produto e peça desabilitado temporariamente
			</td>
		</tr>
	<? }
	//  29/12/2009 MLG - HD 179837 - Adicionar filtro por linha(s)
	if (in_array($login_fabrica,$a_usa_filtro_linha)) {
		$sql_linhas ="SELECT linha,codigo_linha,nome FROM tbl_linha WHERE fabrica=$login_fabrica AND ativo IS TRUE";
		$res_linhas = pg_query($con, $sql_linhas);
		if (($num_linhas = pg_num_rows($res_linhas)) > 0) { ?>
			<tr>
				<td class="table_line" title="Selecione a(s) linha(s)" colspan='5'>
					<fieldset size="4" style='text-align:center' id='linhas'>
						<legend>
							Selecione a(s) linha(s) (<?=$num_linhas?>)
						</legend>
						<? for ($i = 0; $i < $num_linhas; $i++) {
							list ($linha_id, $codigo_linha, $linha_desc) = pg_fetch_row($res_linhas, $i);
							if (isset($_POST['linhas'])) {
								$checked = iif(in_array($linha_id,$_POST['linhas'])," CHECKED");
							} else {    //  HD 195612 - Por padrão, deixar todas as linhas selecionadas.
								$checked = " CHECKED";
							}
							echo "<input type='checkbox' name='linhas[]' value='$linha_id' title='$linha_desc'$checked>\n".
							 "<label class='table_line' title='$linha_des'>$codigo_linha</label>\n";
						}?>
					</fieldset>
				</td>
			</tr><?
		}
	}
	//INCLUIDO BUSCA POR ESTADO PARA INTELBRAS HD176677
	$fabricas_busca_estado = array(14,45);
	if(in_array($login_fabrica,$fabricas_busca_estado)) { ?>
		<tr>
			<td colspan="5" class="table_line">
				<hr color='#eeeeee'>
			</td>
		</tr>
		<tr>
			<td colspan="5" class="table_line" style="text-align: center;">
				Busca por Estado
				<select name="posto_estado" id='posto_estado' size="1" class="frm">
					<option value="" selected></option>
					<option value="AC">AC</option>
					<option value="AL">AL</option>
					<option value="AM">AM</option>
					<option value="AP">AP</option>
					<option value="BA">BA</option>
					<option value="CE">CE</option>
					<option value="DF">DF</option>
					<option value="ES">ES</option>
					<option value="GO">GO</option>
					<option value="MA">MA</option>
					<option value="MG">MG</option>
					<option value="MS">MS</option>
					<option value="MT">MT</option>
					<option value="PA">PA</option>
					<option value="PB">PB</option>
					<option value="PE">PE</option>
					<option value="PI">PI</option>
					<option value="PR">PR</option>
					<option value="RJ">RJ</option>
					<option value="RN">RN</option>
					<option value="RO">RO</option>
					<option value="RR">RR</option>
					<option value="RS">RS</option>
					<option value="SC">SC</option>
					<option value="SE">SE</option>
					<option value="SP">SP</option>
					<option value="TO">TO</option>
				</select>
			</td>
	<?php } ?>
	<tr>
		<td colspan="5" class="table_line">
			<hr color='#eeeeee'>
		</td>
	</tr>
	<?php if($login_fabrica == 51){ ?>
		<tr>
			<td width="19" class="table_line" style="text-align: left;">
				&nbsp;
			</td>
			<td rowspan="2" class="table_line">
				Produto
			</td>
			<td class="table_line">
				Referência
			</td>
			<td class="table_line">
				Descrição
			</td>
			<td width="19" class="table_line" style="text-align: left;">
				&nbsp;
			</td>
		</tr>
		<tr>
			<td class="table_line" style="text-align: center;">
				&nbsp;
			</td>
			<td class="table_line" align="left">
				<input type="text" name="produto_referencia" value="<? echo $produto_referencia ?>" size="10" maxlength="20" class='frm'>
					<img src='imagens_admin/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia,document.frm_consulta.produto_descricao,'referencia')">
			</td>
			<td class="table_line" style="text-align: left;">
				<input type="text" name="produto_descricao" value="<? echo $produto_descricao ?>" size="25" maxlength="50" class='frm'>
					<img src='imagens_admin/btn_buscar5.gif' border='0' style="cursor:pointer" align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_consulta.produto_referencia,document.frm_consulta.produto_descricao,'descricao')">
			</td>
			<td class="table_line" style="text-align: center;">
				&nbsp;
			</td>
		</tr>
		<tr>
			<td colspan="5" class="table_line">
				<hr color='#eeeeee'>
			</td>
		</tr>
		<tr>
			<td width="19" class="table_line" style="text-align: left;">&nbsp;</td>
			<td rowspan="2" class="table_line">Peça</td>
			<td class="table_line">Referência</td>
			<td class="table_line">Descrição</td>
			<td width="19" class="table_line" style="text-align: left;">&nbsp;</td>
		</tr>
		<tr>
			<td class="table_line" style="text-align: center;">
				&nbsp;
			</td>
			<td class="table_line" align="left">
				<input class='frm' type="text" name="peca_referencia" value="<? echo $peca_referencia ?>"size="10" maxlength="20">
					<a href="javascript: fnc_pesquisa_peca_lista (this.form.peca_referencia,this.form.peca_descricao,'referencia')">
						<img SRC="imagens_admin/btn_buscar5.gif" align="absmiddle">
					</a>
			</td>
			<td class="table_line" style="text-align: left;">
				<input class='frm' type="text" name="peca_descricao" value="<? echo $peca_descricao ?>" size="25" maxlength="50">
					<a href="javascript: fnc_pesquisa_peca_lista (this.form.peca_referencia,this.form.peca_descricao,'descricao')">
						<img SRC="imagens_admin/btn_buscar5.gif" align="absmiddle" >
					</a>
			</td>
			<td class="table_line" style="text-align: center;">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="5" class="table_line">
				<hr color='#eeeeee'>
			</td>
		</tr>
	<? } ?>

	<tr>
		<td colspan='5' style='padding-left:2em;' class="table_line">
			Ordenar por:
		</td>
	</tr>
	<tr>
		<td class="table_line" colspan='5' style='text-align:center;vertical-align:middle'>
			<input type="radio" name="ordem" value="data_abertura" <? if ($ordem=='data_abertura') echo 'checked'; ?> > 
				<label>
					Data da Abertura&nbsp;&nbsp;&nbsp;
				</label>
			<input TYPE="radio" NAME="ordem" value="nome" <?php if ($ordem=='nome') echo 'checked'; ?> >
				<label>
					Nome do Posto&nbsp;&nbsp;&nbsp;
				</label>
			<input TYPE="radio" NAME="ordem" value="data_pedido" <?php if ($ordem=='data_pedido') echo 'checked'; ?> > 
				<label>
					Data Pedido
				</label>
		</td>
	</tr>

	<tr>
		<td colspan="5" class="table_line" style="text-align: center;">
			<br>
			<img src="imagens_admin/btn_filtrar.gif" onclick="javascript: document.frm_consulta.btnacao.value='filtrar' ; document.frm_consulta.submit() " ALT="Filtrar extratos" border='0' style="cursor:pointer;">
		</td>
	</tr>
</table>
</form>
<?php if ($btnacao=='filtrar'){
	##### LEGENDAS - INÍCIO #####
	echo "<div name='leg' align='left' style='padding-left:10px'>";
	echo "<br><b style='border:1px solid #666666;background-color:#F1F4FA;'>&nbsp; &nbsp;&nbsp;</b>&nbsp;<b> Intervenção da Assistência Técnica da Fábrica</b>";
	echo "<br><b style='border:1px solid #666666;background-color:#FFFF99'>&nbsp; &nbsp;&nbsp;</b>&nbsp; <b> Reparo na Assistência Técnica da Fábrica</b>";
	echo "<br><b style='border:1px solid #666666;background-color:#D7FFE1'>&nbsp; &nbsp;&nbsp;</b>&nbsp; <b> OS Reincidente</b>";
	echo "<br><b style='border:1px solid #666666;background-color:#91C8FF'>&nbsp; &nbsp;&nbsp;</b>&nbsp; <b> OS Aberta a mais de 25 dias</b>";
	echo "</div>";
	$cond_status = '62,64,65,127,147';
	if ($login_fabrica == 35 or $login_fabrica == 52) {
		$cond_status = '62,64,65,127,19';
	}
	/*HD 15731 - Desabilitado p/ Otimização*/
	$sql =  "SELECT interv.os
			FROM (
			SELECT
			ultima.os,
			(SELECT status_os FROM tbl_os_status WHERE status_os IN (62,64,65,127,147) AND tbl_os_status.os = ultima.os ORDER BY data DESC LIMIT 1) AS ultimo_status
			FROM (SELECT DISTINCT os FROM tbl_os_status WHERE status_os IN (62,64,65,127,147) AND tbl_os_status.fabrica_status=$login_fabrica ) ultima
			) interv
			WHERE interv.ultimo_status IN (62,65,127,147)";
	/*HD 15731 - Habilitado p/ Otimização*/
	$sql =  "
			SELECT DISTINCT os INTO TEMP tmp_interv_2_$login_admin
			FROM tbl_os_status WHERE status_os IN ($cond_status) AND tbl_os_status.fabrica_status=$login_fabrica;

			SELECT interv.os
			INTO TEMP tmp_interv_$login_admin
			FROM (
			SELECT
			ultima.os,
			(SELECT status_os FROM tbl_os_status WHERE status_os IN ($cond_status) AND tbl_os_status.os = ultima.os ORDER BY data DESC LIMIT 1) AS ultimo_status
			FROM ( SELECT os FROM tmp_interv_2_$login_admin) ultima
			) interv
			WHERE interv.ultimo_status IN (62,65,127,147);

			CREATE INDEX tmp_interv_OS_$login_admin ON tmp_interv_$login_admin(os);

			/* select os from  tmp_interv_$login_admin */;";
	if($ip =='201.76.83.17'){
		#echo nl2br($sql);
	}

	$res_status = pg_query($con,$sql);
	$total=pg_num_rows($res_status);
	if(strlen($produto_referencia)>0 AND $login_fabrica == 51){
		$sql_adicional_3 = " AND tbl_produto.referencia = '$produto_referencia' ";
	}else{
		$sql_adicional_3 = " AND 1=1 ";
	}
	if (strlen($peca_referencia)>0 AND $login_fabrica == 51) {
		$sql_adicional_2 = " AND tbl_peca.referencia = '$peca_referencia' ";
	}else{
		$sql_adicional_2 = " AND 1=1 ";
	}
	// OS não excluída
	/*HD 15731 - Desabilitado p/ Otimização*/
	$sql =  "SELECT DISTINCT tbl_os.os                                                        ,
				tbl_os.sua_os                                                     ,
				LPAD(tbl_os.sua_os,20,'0')                   AS ordem             ,
				TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao         ,
				TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
				current_date - tbl_os.data_abertura as dias_aberto,
				tbl_os.data_abertura   AS abertura_os       ,
				tbl_os.serie                                                      ,
				tbl_os.consumidor_nome                                            ,
				tbl_os.admin                                                      ,
				tbl_posto_fabrica.codigo_posto                                    ,
				tbl_posto.nome                              AS posto_nome         ,
				tbl_posto.fone                              AS posto_fone         ,
				tbl_produto.referencia                      AS produto_referencia ,
				tbl_produto.descricao                       AS produto_descricao  ,
				tbl_produto.troca_obrigatoria               AS troca_obrigatoria  ,
				tbl_os_retorno.nota_fiscal_envio,
				TO_CHAR(tbl_os_retorno.data_nf_envio,'DD/MM/YYYY')      AS data_nf_envio        ,
				tbl_os_retorno.numero_rastreamento_envio,
				TO_CHAR(tbl_os_retorno.envio_chegada,'DD/MM/YYYY hh:mm:ss')      AS envio_chegada      ,
				tbl_os_retorno.nota_fiscal_retorno,
				TO_CHAR(tbl_os_retorno.data_nf_retorno,'DD/MM/YYYY')      AS data_nf_retorno        ,
				tbl_os_retorno.numero_rastreamento_retorno,
				TO_CHAR(tbl_os_retorno.retorno_chegada,'DD/MM/YYYY hh:mm:ss')      AS retorno_chegada,
				tbl_os_retorno.admin_recebeu AS admin_recebeu,
				tbl_os_retorno.admin_enviou AS admin_enviou,
				(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (67,68,70) ORDER BY data DESC LIMIT 1) AS reincindente,
				(SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65,127,147) ORDER BY data DESC LIMIT 1) AS status_descricao,
				(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65,127,147) ORDER BY os_status DESC LIMIT 1) AS status_os,
				(SELECT data FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65,127,147) ORDER BY data DESC LIMIT 1) AS status_pedido
			FROM tbl_os
				JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
				LEFT JOIN tbl_os_retorno ON tbl_os_retorno.os=tbl_os.os
			WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_os.excluida IS NOT TRUE
				AND (SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65,127,147) ORDER BY data DESC LIMIT 1) IN (62,65,127,147)
				$os_array
				$sql_adicional
				$sql_ordem ";
		//HD 195242: Adicionando a consulta de OS na SQL. Acrescentei como uma subselect na cláusula FROM, pois para a Fricon é diferente
		if ($login_fabrica == 52) {
			$select_hd_chamado_os = "SELECT hd_chamado FROM tbl_hd_chamado_item WHERE tbl_hd_chamado_item.os=tbl_os.os";
		}
		else {
			$select_hd_chamado_os = " SELECT hd_chamado FROM tbl_hd_chamado_extra WHERE  tbl_hd_chamado_extra.os=tbl_os.os ORDER BY hd_chamado DESC LIMIT 1 ";
		}
		/*HD 15731 - Habilitado p/ Otimização*/
		// OS não excluída
		$sql =  "SELECT DISTINCT tbl_os.os                                                        ,
					tbl_os.sua_os                                                     ,
					LPAD(tbl_os.sua_os,20,'0')                   AS ordem             ,
					TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')  AS digitacao         ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
					current_date - tbl_os.data_abertura as dias_aberto,
					tbl_os.data_abertura   AS abertura_os       ,
					tbl_os.serie                                                      ,
					tbl_os.consumidor_nome                                            ,
					tbl_os.admin                                                      ,
					tbl_posto_fabrica.codigo_posto                                    ,
					tbl_posto.nome                              AS posto_nome         ,
					tbl_posto.fone                              AS posto_fone         ,
					tbl_produto.referencia                      AS produto_referencia ,
					tbl_produto.descricao                       AS produto_descricao  ,
					tbl_produto.troca_obrigatoria               AS troca_obrigatoria  ,
					tbl_produto.produto_critico               AS produto_critico  ,
					tbl_os_retorno.nota_fiscal_envio,
					TO_CHAR(tbl_os_retorno.data_nf_envio,'DD/MM/YYYY')      AS data_nf_envio        ,
					tbl_os_retorno.numero_rastreamento_envio,
					TO_CHAR(tbl_os_retorno.envio_chegada,'DD/MM/YYYY HH24:mm')      AS envio_chegada      ,
					tbl_os_retorno.nota_fiscal_retorno,
					TO_CHAR(tbl_os_retorno.data_nf_retorno,'DD/MM/YYYY')      AS data_nf_retorno        ,
					tbl_os_retorno.numero_rastreamento_retorno,
					TO_CHAR(tbl_os_retorno.retorno_chegada,'DD/MM/YYYY HH24:mm')      AS retorno_chegada,
					tbl_os_retorno.admin_recebeu AS admin_recebeu,
					tbl_os_retorno.admin_enviou AS admin_enviou,
					(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (67,68,70) ORDER BY data DESC LIMIT 1) AS reincindente,
					(SELECT observacao FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($cond_status) ORDER BY data DESC LIMIT 1) AS status_descricao,
					(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($cond_status) ORDER BY os_status DESC LIMIT 1) AS status_os,
					(SELECT data FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN ($cond_status) ORDER BY data DESC LIMIT 1) AS status_pedido,
					/* HD 195242: Colocar coluna do Chamado para Fricon, Salton e novas */
					($select_hd_chamado_os) AS hd_chamado
				FROM  tmp_interv_$login_admin X
					JOIN  tbl_os ON tbl_os.os = X.os
					JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
					LEFT JOIN tbl_os_retorno ON tbl_os_retorno.os=tbl_os.os ";
		if($login_fabrica == 51){
			$sql .= " LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
					LEFT JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
					LEFT JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica ";
		}
		if (isset($linhas)) 
			$sql.= "	JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto_fabrica.posto
				AND tbl_posto_linha.linha = tbl_produto.linha";
			$sql .= " WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_os.excluida IS NOT TRUE
				AND (SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os AND status_os IN (62,64,65,127,147) ORDER BY data DESC LIMIT 1) IN (62,65,127,147) ";
			if($login_fabrica == 51 or $login_fabrica == 81){ #HD 269024
				$sql.= " AND tbl_os.finalizada IS NULL
						 AND tbl_os.os NOT IN(SELECT tbl_os_troca.os FROM tbl_os_troca WHERE tbl_os_troca.os = tbl_os.os) ";
			}
			$sql .= " $sql_adicional_4
				$sql_adicional_3
				$sql_adicional_2
				$sql_adicional
				$sql_ordem ";
		if ($debug) pre_echo (
				$sql,"Consulta por linha");
			$res = pg_query($con,$sql);
			$total=pg_num_rows($res);
			$achou=0;
			echo "<br>";
			echo "<center>";
			echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#485989' width='98%'>";
			//INICIO DO XLS
			$data_xls = date('dmy');
			echo `rm /tmp/assist/relatorio-os-intervencao-$login_fabrica.xls`;
			$fp = fopen ("/tmp/assist/relatorio-os-intervencao-$login_fabrica.html","w");
			fputs ($fp,"<html>");
			fputs ($fp,"<head>");
			fputs ($fp,"<title>OS INTERVENCAO - $data_xls");
			fputs ($fp,"</title>");
			fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
			fputs ($fp,"</head>");
			fputs ($fp,"<body>");
			fputs($fp,"<TABLE align='center' border='0' cellspacing='1' cellpadding='1'>\n");
			echo "<tr class='Titulo' height='25' background='imagens_admin/azul.gif'>";
			fputs($fp,"<tr>\n");
			echo "<td width='70'>OS</td>";
			fputs($fp,"<td width='70'>OS</td>\n");
			//HD 195242: Colocar coluna do Chamado para Fricon, Salton e novas
			if ($login_fabrica == 52 || $login_fabrica >= 81) {
				echo "<td width='70'>Chamado</td>";
				fputs($fp,"<td width='70'>Chamado</td>\n");
			}
			//alteração HD 8112 3/12/2007 Gustavo
			if($login_fabrica == 6){
				echo "<td width='70'>Nº SÉRIE</td>";
			}
			echo "<td>AB</td>";
			fputs($fp,"<td>AB</td>\n");
			echo "<td>PEDIDO</td>";
			fputs($fp,"<td>PEDIDO</td>\n");
			echo "<td>POSTO</td>";
			fputs($fp,"<td>POSTO</td>\n");
			//alteração HD 8112 3/12/2007 Gustavo
			if($login_fabrica <> 6){
				echo "<td width='75'>FONE POSTO</td>";
			}
			//echo "<td>CONSUMIDOR</td>"; retirado a pedido do Fabio Britania
			echo "<td width='75'>PRODUTO</td>";
			fputs($fp,"<td width='75'>PRODUTO</td>\n");
			if($login_fabrica == 51){
				echo "<td>PEÇA CRÍTICA</td>";
			}else{
				echo "<td>PEÇA</td>";
				fputs($fp,"<td>PEÇA</td>\n");
			}
			echo "<td>QTDE<br>PEÇAS</td>";
			fputs($fp,"<td>QTDE PEÇAS</td>\n");
			echo "<td colspan='7'>AÇÕES</td>";
			fputs($fp,"<td>OBS</td>\n");
			fputs($fp,"<td>AÇÕES</td>\n");
			fputs($fp,"<td>Nota Fiscal Envio</td>\n");
			fputs($fp,"<td>Data Nota Fiscal Envio:</td>\n");
			fputs($fp,"<td>Número do Objeto/PAC Envio:</td>\n");
			fputs($fp,"<td>Envio Chegada</td>\n");
			fputs($fp,"<td>Admin recebeu:</td>\n");
			fputs($fp,"<td>Nota Fiscal retorno</td>\n");
			fputs($fp,"<td>Data Nota Fiscal Retorno:</td>\n");
			fputs($fp,"<td>Número do Objeto/PAC Retorno:</td>\n");
			fputs($fp,"<td>Admin enviou</td>\n");
			fputs($fp,"<td>Retorno Chegada</td>\n");
			fputs($fp,"<td>Justificativa</td></tr>\n");
			for ($i = 0 ; $i < $total ; $i++) {
				$os                 = trim(pg_fetch_result($res,$i,os));
				$sua_os             = trim(pg_fetch_result($res,$i,sua_os));
				$digitacao          = trim(pg_fetch_result($res,$i,digitacao));
				$abertura           = trim(pg_fetch_result($res,$i,abertura));
				$serie              = trim(pg_fetch_result($res,$i,serie));
				$consumidor_nome    = trim(pg_fetch_result($res,$i,consumidor_nome));
				$codigo_posto       = trim(pg_fetch_result($res,$i,codigo_posto));
				$posto_nome         = trim(pg_fetch_result($res,$i,posto_nome));
				$produto_referencia = trim(pg_fetch_result($res,$i,produto_referencia));
				$produto_descricao  = trim(pg_fetch_result($res,$i,produto_descricao));
				$produto_troca_obrigatoria   = trim(pg_fetch_result($res,$i,troca_obrigatoria));
				$produto_critico    = trim(pg_fetch_result($res,$i,produto_critico));
				$posto_fone         = substr(trim(pg_fetch_result($res,$i,posto_fone)),0,17);
				$status_os          = trim(pg_fetch_result($res,$i,status_os));
				$status_descricao   = trim(pg_fetch_result($res,$i,status_descricao));
				$admin_recebeu      = trim(pg_fetch_result($res,$i,admin_recebeu));
				$admin_enviou       = trim(pg_fetch_result($res,$i,admin_recebeu));
				$os_reincidente      = trim(pg_fetch_result($res,$i,reincindente));
				$dias_abertura       = trim(pg_fetch_result($res,$i,dias_aberto));
				$nota_fiscal_envio   = trim(pg_fetch_result($res,$i,nota_fiscal_envio));
				$data_nf_envio       = trim(pg_fetch_result($res,$i,data_nf_envio));
				$numero_rastreamento_envio = trim(pg_fetch_result($res,$i,numero_rastreamento_envio));
				$envio_chegada       = trim(pg_fetch_result($res,$i,envio_chegada));
				$nota_fiscal_retorno = trim(pg_fetch_result($res,$i,nota_fiscal_retorno));
				$data_nf_retorno     = trim(pg_fetch_result($res,$i,data_nf_retorno));
				$numero_rastreamento_retorno = trim(pg_fetch_result($res,$i,numero_rastreamento_retorno));
				$retorno_chegada     = trim(pg_fetch_result($res,$i,retorno_chegada));
				//HD 195242: Colocar coluna do Chamado para Fricon, Salton e novas
				if ($login_fabrica == 52 || $login_fabrica >= 81) {
					$hd_chamado          = trim(pg_fetch_result($res,$i,hd_chamado));
				}
				if ($login_fabrica==11){
					$produto_troca_obrigatoria='f';
				}
				$sql_status  = "SELECT TO_CHAR(data,'DD/MM/YYYY') AS data FROM tbl_os_status WHERE tbl_os_status.os= $os AND tbl_os_status.fabrica_status=$login_fabrica ORDER BY tbl_os_status.data DESC LIMIT 1";
				$res_status = pg_query($con,$sql_status);
				$data_pedido = trim(pg_fetch_result($res_status,0,0));
				//Para Gama Italy não pegar a data da intervenção como data do pedido, ela pode entrar em intervenção pelo Ditrib, ações do Ronaldo
				if($login_fabrica == 51){
					$sql_canc = "SELECT  TO_CHAR(tbl_os_item.digitacao_item,'DD/MM/YYYY') as data_pedido
							FROM tbl_os_produto
								JOIN tbl_os_item USING(os_produto)
								JOIN tbl_peca USING(peca)
							WHERE tbl_os_produto.os=$os;";
					$res_canc = pg_query($con, $sql_canc);
					if( pg_num_rows($res_canc) > 0){
						$data_pedido = trim(pg_fetch_result($res_canc,0,0));
					}
				}
				if ($status_os=="64")
					continue; //volta ao laço "for"
				$achou=1;
				if ($i % 2 == 0) 
					$cor   = "#F1F4FA";
				else
					$cor   = "#F7F5F0";
				if ($dias_abertura>24){
					$cor = "#91C8FF";
				}
				if ($status_os == "65") 
					$cor = "#FFFF99";
				if ($os_reincidente==67 || $os_reincidente==68 || $os_reincidente==70){
					$cor = "#D7FFE1";
				}
				if ($status_os == "64") 
					$cor = "#D7FFE1";
				$pecas = "";
				$peca  = "";
				$sql_peca = "SELECT  tbl_os_item.os_item,
								tbl_peca.troca_obrigatoria AS troca_obrigatoria,
								tbl_peca.retorna_conserto AS retorna_conserto,
								tbl_peca.referencia AS referencia,
								tbl_peca.descricao AS descricao,
								tbl_peca.peca AS peca
							FROM tbl_os_produto
								JOIN tbl_os_item USING(os_produto)
								JOIN tbl_peca USING(peca)
							WHERE tbl_os_produto.os=$os ";
				if ($login_fabrica == 6){
					$sql_peca .= " AND tbl_peca.retorna_conserto IS TRUE ";
				}
				if ($login_fabrica == 51){
					//HD 52047 - retirado, caso venha a dar problema retornar.
					//25/11/2008 - Ronaldo pediu para voltar como era antes..
					$sql_peca .= " AND tbl_peca.troca_obrigatoria IS TRUE ";
				}
				$res_peca = pg_query($con,$sql_peca);
				$resultado = pg_num_rows($res_peca);
				$quantas_pecas = $resultado;
				if ($resultado>0){
					$peca_troca_obrigatoria		= trim(pg_fetch_result($res_peca,0,troca_obrigatoria));
					$peca_intervencao_fabrica	= trim(pg_fetch_result($res_peca,0,retorna_conserto));
					$peca						= trim(pg_fetch_result($res_peca,0,peca));
					for($j=0;$j<$resultado;$j++){
						$peca_referencia       = trim(pg_fetch_result($res_peca,$j,referencia));
						$peca_descricao       = trim(pg_fetch_result($res_peca,$j,descricao));
						$pecas .= $peca_referencia." - ".$peca_descricao."\n";
					}
				}
				if (strlen($admin_recebeu)>0){
					$query = "SELECT login FROM tbl_admin WHERE admin=$admin_recebeu";
					$res_query = pg_query($con,$query);
					$resultado = pg_num_rows($res_query);
					if ($resultado>0){
						$admin_recebeu = trim(pg_fetch_result($res_query,0,login));
					}
				}
				if (strlen($admin_enviou)>0){
					$query = "SELECT login FROM tbl_admin WHERE admin=$admin_enviou";
					$res_query = pg_query($con,$query);
					$resultado = pg_num_rows($res_query);
					if ($resultado>0){
						$admin_enviou = trim(pg_fetch_result($res_query,0,login));
					}
				}
				if (strlen($sua_os) == 0)
					$sua_os = $os;
				if($login_fabrica==3 or $login_fabrica ==14){
					// HD 21341
					echo "<form name='frm_$os' method='post' ACTION=\"$PHP_SELF?autorizar_os=$os\">";
				}
				echo "<tr class='Conteudo' height='20' bgcolor='$cor' align='left'  >";
				fputs($fp,"<tr align='left'>\n");
				echo "<input type='hidden' name='justific' value=''>";
				echo "<input type='hidden' name='autorizar' value=''>";
				echo "<td nowrap><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
				fputs($fp,"<td nowrap>$sua_os</td>\n");
				//HD 195242: Colocar coluna do Chamado para Fricon, Salton e novas
				if ($login_fabrica == 52 || $login_fabrica >= 81) {
					echo "<td nowrap><a href='callcenter_interativo_new.php?callcenter=$hd_chamado' target='_blank'>$hd_chamado</a></td>";
					fputs($fp,"<td nowrap>$hd_chamado</td>\n");
				}
				//alteração HD 8112 3/12/2007 Gustavo
				if($login_fabrica == 6){
					echo "<td nowrap >$serie</td>";
				}
				echo "<td nowrap >$abertura</td>";
				fputs($fp,"<td nowrap >$abertura</td>\n");
				echo "<td nowrap >$data_pedido</td>";
				fputs($fp,"<td nowrap >$data_pedido</td>\n");
				echo "<td nowrap title='$codigo_posto $posto_nome'>$codigo_posto ".substr($posto_nome,0,15)."</td>";
				fputs($fp,"<td nowrap>$codigo_posto ".$posto_nome."</td>\n");
				//alteração HD 8112 3/12/2007 Gustavo
				if($login_fabrica <> 6){
					echo "<td nowrap>$posto_fone</td>";
				}
				$produto = $produto_referencia . " " . $produto_descricao;
				echo "<td nowrap title='Referência: $produto_referencia \nDescrição: $produto_descricao'>".substr($produto,0,20)."</td>";
				fputs($fp,"<td nowrap>".$produto."</td>\n");
				if (strlen($pecas)==0){
					echo "<td nowrap title='$pecas' align='left'>-</td>";
					fputs($fp,"<td nowrap align='left'>-</td>\n");
				}else{
					echo "<td nowrap title='$pecas' align='left'>"; if ($login_fabrica <> 35) { echo "<a href='peca_cadastro.php?peca=$peca' target='_blank'>"; } else { echo "<a href='os_item.php?os=$os' target='_blank'>"; } echo substr($pecas,0,16);   echo "</a></td>";
					fputs($fp,"<td nowrap align='left'>".$pecas."</td>\n");
				}
				echo "<td nowrap title='Quantidade de peças: $quantas_pecas' align='center'>$quantas_pecas</td>";
				fputs($fp,"<td nowrap align='center'>$quantas_pecas</td>\n");
				if ($status_os=="62" or $status_os=="127" or $status_os=="147"){
					$colspan="";
					if ($produto_troca_obrigatoria=='t' || $peca_troca_obrigatoria=='t') 
						$colspan="colspan='4'";
					if ($login_fabrica == 51) {
						$colspan="colspan='3'";
					}
					if ( $produto_troca_obrigatoria=='t' || $peca_troca_obrigatoria=='t' || 1==1){
						echo "<td align='center' $colspan style='font-size:9px' nowrap>";
						if (in_array($login_fabrica,array(14,25,45,51,19,81)) or ($login_fabrica==35 and $produto_troca_obrigatoria=='t')) {
							echo "<img src='imagens/btn_trocar.gif' ALT='Efetuar a troca do Produto' border='0' style='cursor:pointer;' onClick=\"javascript: if(confirm('Deseja realizar a troca deste produto pela Fábrica? Esta OS será liberada'))  document.location='$PHP_SELF?os=$os&trocar=$sua_os$str_filtro';\">";
						}
					echo "</td>\n";
					}
					echo "<td align='center' style='font-size:9px' nowrap>";
					if ($login_fabrica==11){
						echo "<img src='imagens/btn_autorizar.gif' ALT='Autorizar Troca de Peça' i border='0' style='cursor:pointer;'
						onClick=\"javascript: fnc_autorizar($os);\">";
					}elseif($login_fabrica==3 or $login_fabrica==14){
					echo "<img src='imagens/btn_autorizar.gif' ALT='Autorizar Troca de Peça Liberar a OS para o Posto' i border='0' style='cursor:pointer;' onClick=\"javascript:autorizar_os($os,'frm_$os',$sua_os);\" >";
					}else{
						if($login_fabrica ==51){
							$sql_canc = "SELECT motivo
											FROM tbl_pedido_cancelado
										WHERE os = $os AND peca in (SELECT  tbl_os_item.peca
											FROM tbl_os_produto
												JOIN tbl_os_item USING(os_produto)
												JOIN tbl_peca USING(peca)
										WHERE tbl_os_produto.os=$os);";
							$res_canc = pg_query($con, $sql_canc);
							if( pg_num_rows($res_canc) > 0){
								echo "Peça cancelada";
							}else{
								echo "<img src='imagens/btn_autorizar.gif' ALT='Autorizar Troca de Peça Liberar a OS para o Posto' i border='0' style='cursor:pointer;' onClick=\"javascript: if(confirm('Autorizar pedido de peça? Esta OS será liberada e a solicitação de peça para esta OS será autorizada'))  document.location='$PHP_SELF?os=$os&autorizar=$sua_os$str_filtro';\">";
							}
						}else if ($login_fabrica == 52) {
							echo "<img src='imagens/btn_autorizar.gif' ALT='Liberar Os de Intervenção Por falta da dados na OS' i border='0' style='cursor:pointer;' onClick=\"javascript: if(confirm('Autorizar liberação de OS? Esta OS será liberada e poderá entrar no proxímo extrato'))  document.location='$PHP_SELF?os=$os&autorizar=$sua_os$str_filtro';\">";
						}else{
							if ($login_fabrica <> 35 or $produto_troca_obrigatoria<>'t') {
								echo "<img src='imagens/btn_autorizar.gif' ALT='Autorizar Troca de Peça Liberar a OS para o Posto' i border='0' style='cursor:pointer;' onClick=\"javascript: if(confirm('Autorizar pedido de peça? Esta OS será liberada e a solicitação de peça para esta OS será autorizada'))  document.location='$PHP_SELF?os=$os&autorizar=$sua_os$str_filtro';\">";
							}
						}
					}
					echo "</td>\n";
					if (($produto_troca_obrigatoria!='t' && $peca_troca_obrigatoria!='t' && strlen($pecas)>0 && $login_fabrica <> 35 && $login_fabrica <> 52 ) or $login_fabrica == 14){
						echo "<td align='center' style='font-size:9px' nowrap>";
						echo "<img src='imagens/btn_cancelar.gif' ALT='Cancelar Troca de Peça' border='0' style='cursor:pointer;' onClick=\"javascript: fnc_cancelar($os);\">";
						echo "</td>\n";
						if ($login_fabrica<>25 && $login_fabrica <> 43){
							echo "<td align='center' style='font-size:9px' nowrap>";
							if($login_fabrica == 11){
								echo "<img src='imagens/btn_reparar.gif' ALT='Reparar Produto' border='0' style='cursor:pointer;' onClick=\"javascript: fnc_reparar($os);\">";
							}else{
								if ($login_fabrica <> 3 and $login_fabrica <> 35) {
								echo "<img src='imagens/btn_reparar.gif' ALT='Reparar Produto' border='0' style='cursor:pointer;'  onClick=\"javascript: if(confirm('Reparar este produto na fábrica? O pedido de peça será cancelado.'))  document.location='$PHP_SELF?os=$os&reparar=$sua_os$str_filtro';\">";
								}
							}
						}
						echo "</td>\n";
					}
					if ($login_fabrica == 52) {
						echo "<td align='center' style='font-size:9px' nowrap>";
						echo "<img src='imagens/btn_cancelar.gif' ALT='Reprovar OS' border='0' style='cursor:pointer;' onClick=\"javascript: fnc_cancelar($os);\">";
						echo "</td>\n";
					}
					if ($login_fabrica == 35 and $quantas_pecas >= 4) {
						echo "<td align='center' style='font-size:9px' nowrap>";
						echo "<img src='imagens/btn_cancelar.gif' ALT='Cancelar Troca de Peça' border='0' style='cursor:pointer;' onClick=\"javascript: fnc_cancelar($os);\">";
						echo "</td>\n";
					}else if ($login_fabrica == 35) {
						echo "<td align='center' style='font-size:9px' nowrap>";
						echo "<img src='imagens/btn_cancelar.gif' ALT='Autorizar Troca de Peça Liberar a OS para o Posto' i border='0' style='cursor:pointer;' onClick=\"javascript: if(confirm('Cancelar troca do produto? Esta OS será liberada'))  document.location='$PHP_SELF?os=$os&cancelar=sim';\">";
						echo "</td>\n";
						if ($produto_critico == 't') {
						echo "<td>
								<img src='imagens/btn_trocar.gif' ALT='Efetuar a troca do Produto' border='0' style='cursor:pointer;' onClick=\"javascript: if(confirm('Deseja realizar a troca deste produto pela Fábrica? Esta OS será liberada'))  document.location='$PHP_SELF?os=$os&trocar=$sua_os$str_filtro';\">
							</td>";
						}
					}
				}
				if ($status_os=="64" && strlen($nota_fiscal_envio)==0){
					echo "<td align='center' colspan='4' nowrap>";
					echo "OS LIBERADA";
					echo "</td>";
					fputs($fp,"<td align='center' nowrap>OS LIBERADA</td>\n");
				}else{
					fputs($fp,"<td></td>\n");
				}

				$mostrar="none";
				$btn_retirar_intervencao = "<br /><a href=\"javascript: if(confirm('Deseja retirar esta OS da intervenção?'))  document.location='$PHP_SELF?os=$os&retirar_intervencao=1';\" alt='Força retirada da OS da intervenção. Só utilize em casos exttremos.'>RETIRAR OS DA INTERVENÇÃO</a>";

				if ($status_os=="62" AND $login_fabrica == 51){ // HD 59408
					echo "<td align='center' style='font-size:9px' nowrap>";
					echo "<a href=\"javascript: if(confirm('Deseja retirar esta OS da intervenção por posto conseguiu consertar o produto?'))  document.location='$PHP_SELF?os=$os&retirar_intervencao=1';\"><img src='imagens/btn_consertado.gif' ALT='Produto Consertado pelo Posto' border='0' style='cursor:pointer;' ></a>";
					echo "</td>\n";
				}
				if ($status_os=="65" OR ($status_os=="64" && strlen($nota_fiscal_envio)>0)){
					echo "<td align='center' colspan='4' style='font-family:arial;font-size:9px' nowrap>";
					if ($nota_fiscal_envio==""){
						echo "POSTO NÃO ENVIOU O PRODUTO";
						$xls_acoes = "POSTO NÃO ENVIOU O PRODUTO";
						if ($login_fabrica==6 or $login_fabrica==11){
							echo $btn_retirar_intervencao;
						}
					}elseif ($envio_chegada==""){
						$mostrar="block";
						echo "PRODUTO ENVIADO PELO POSTO";
						$xls_acoes = "PRODUTO ENVIADO PELO POSTO";
						if ($login_fabrica==6 or $login_fabrica==11){
							echo $btn_retirar_intervencao;
						}
					}elseif ($nota_fiscal_retorno==""){
						$mostrar="block";
						echo "RETORNO DO PRODUTO AO POSTO PENDENTE";
						$xls_acoes = "RETORNO DO PRODUTO AO POSTO PENDENTE";
						if ($login_fabrica==6 or $login_fabrica==11){
							echo $btn_retirar_intervencao;
						}
					}elseif($retorno_chegada==""){
						$mostrar="block";
						echo "CONFIRMAÇÃO DO POSTO PENDENTE";
						$xls_acoes = "CONFIRMAÇÃO DO POSTO PENDENTE";
						if ($login_fabrica==6 or $login_fabrica==11){
							echo $btn_retirar_intervencao;}
					}else{
						if ($status_os=="65")
							echo "<a href=\"javascript:MostraEsconde('dados_$i');\" >REPARO CONCLUÍDO</a>";
						else
							echo "<a href=\"javascript:MostraEsconde('dados_$i');\" >OS LIBERADA COM REPARO</a>";
					}
					echo "</td>";
					fputs($fp,"<td>$xls_acoes</td>\n");

					// Se não for fabrica 14 não mostra o botão de CONFIRMAR
						if ($login_fabrica == 14 ){ //or ($nota_fiscal_envio && $data_nf_envio)){
							$acao=(strlen($envio_chegada)>0)?"confirmar_retorno":"confirmar_chegada";
							echo "</tr>";
							echo "<tr class='Conteudo' bgcolor='#FFFFCC' height='0px' align='right'>";
							echo "<form name='frm_confim' method='post' action='$PHP_SELF?$acao=$os' onSubmit='javascript:if (confirm(\"Deseja continuar?\")) return true; else return false;'>";
							echo "<td colspan='12'>";
							fputs($fp,"<td>$nota_fiscal_envio</td>\n");
							fputs($fp,"<td>$data_nf_envio</td>\n");
							echo "<div style='display:$mostrar' id='dados_$i'>";
							echo "<b style='color:#3366CC'>ENVIO À FÁBRICA:&nbsp;&nbsp;&nbsp;&nbsp;</b>";
							echo "<b style='padding-left:13px'>Nota Fiscal:</b> $nota_fiscal_envio ";
							echo "<b style='padding-left:13px'>Data Nota Fiscal:</b> $data_nf_envio ";
							if ($login_fabrica<>6){
								echo "<b style='padding-left:13px'>Número do Objeto/PAC:</b> <a href='http://websro.correios.com.br/sro_bin/txect01$.QueryList?P_LINGUA=001&P_TIPO=001&P_COD_UNI=$numero_rastreamento_envio' target='_blank'>$numero_rastreamento_envio</a>";
								fputs($fp,"<td>$numero_rastreamento_envio</td>\n");
							} else {
								fputs($fp,"<td></td>\n");
							}
//							if ($envio_chegada){
								echo "<b style='padding-left:13px'>Chegada: </b>$envio_chegada ";
								fputs($fp,"<td>$envio_chegada</td>\n");
								echo "<b style='padding-left:13px'>Admin: </b>$admin_recebeu ";
								fputs($fp,"<td>$admin_recebeu</td>\n");
//							}else {
								fputs($fp,"<td></td>\n");
								fputs($fp,"<td></td>\n");
								echo "<b style='padding-left:13px'>Data Chegada: </b><input type='text' value='' name='txt_data_envio_chegada' class='inpu' size='15' maxlength='10'><input name='confirmar_chegada' type='submit' value='Confirmar' class='butt'>";
//							}
							if ($envio_chegada){
								echo "<br><b style='color:#3366CC'>RETORNO AO POSTO: </b>";
								if ($nota_fiscal_retorno){
									echo "<b style='padding-left:13px'>Nota Fiscal: </b>$nota_fiscal_retorno ";
									fputs($fp,"<td>$nota_fiscal_retorno</td>\n");
									echo "<b style='padding-left:13px'>Data Nota Fiscal: </b> $data_nf_retorno ";
									fputs($fp,"<td>$data_nf_retorno </td>\n");
									if ($login_fabrica<>6){
										echo "<b style='padding-left:13px'>Número do Objeto/PAC: </b> <a href='http://websro.correios.com.br/sro_bin/txect01$.QueryList?P_LINGUA=001&P_TIPO=001&P_COD_UNI=$numero_rastreamento_retorno"."BR' target='_blank'>$numero_rastreamento_retorno</a>";
										fputs($fp,"<td>$numero_rastreamento_retorno</td>\n");
									} else {
										fputs($fp,"<td></td>\n");
									}
									echo "<b style='padding-left:13px'>Admin: </b>$admin_enviou";
									fputs($fp,"<td>$admin_enviou</td>\n");
									if (strlen($retorno_chegada)==0)
										$retorno_chegada = " NÃO CONSTA ";
									echo "<b style='padding-left:13px'> Chegada: </b>$retorno_chegada ";
									fputs($fp,"<td>$retorno_chegada</td>\n");
								}else {
									fputs($fp,"<td></td>\n");
									fputs($fp,"<td></td>\n");
									fputs($fp,"<td></td>\n");
									fputs($fp,"<td></td>\n");
									echo " <b style='padding-left:13px'>Nota Fiscal: </b><input type='text' value='' name='txt_nota_fiscal_retorno' class='inpu' size='10' maxlength='6'>";
									echo "<b style='padding-left:13px'>Data da Nota Fiscal: </b><input type='text' value='' name='txt_data_envio_retorno' class='inpu' size='15' maxlength='10'>";
									if ($login_fabrica<>6){
										echo "<b style='padding-left:13px'>Número do Objeto/PAC: </b><input type='text' value='' name='txt_rastreio_retorno' class='inpu' size='15' maxlength='13'>";
										fputs($fp,"<td></td>\n");
									}
									echo"<input type='submit' name='confirmar_retorno' value='Confirmar' class='butt'>";
								}
							} else {
								fputs($fp,"<td></td>\n");
								fputs($fp,"<td></td>\n");
								fputs($fp,"<td></td>\n");
								fputs($fp,"<td></td>\n");
								fputs($fp,"<td></td>\n");
							}
							echo "</div>";
							echo "</td>";
							echo "</form>";
						} else {
							fputs($fp,"<td></td>\n");
							fputs($fp,"<td></td>\n");
							fputs($fp,"<td></td>\n");
							fputs($fp,"<td></td>\n");
							fputs($fp,"<td></td>\n");
							fputs($fp,"<td></td>\n");
							fputs($fp,"<td></td>\n");
							fputs($fp,"<td></td>\n");
							fputs($fp,"<td></td>\n");
							fputs($fp,"<td></td>\n");
						}


				}
				echo "</tr>";
				if($login_fabrica==3 or $login_fabrica==14){
					// HD 21341
					echo "</form>";
				}
				$justificativa = trim(str_replace("Reparo do produto deve ser feito pela fábrica","",$status_descricao));
				if($login_fabrica == 51){
					$justificativa = $justificativa;
				}else{
					$justificativa = trim(str_replace("Peça da O.S. com intervenção da fábrica.","",$justificativa));
				}
				if (strlen($justificativa)>0){
					echo "<tr class='justificativa' bgcolor='$cor'>";
					echo "<td colspan='14' align='left'>";
					echo "<img src='imagens/setinha_linha4.gif'>&nbsp;&nbsp; <i  style='color:#5B5B5B'>$justificativa </i>";
					fputs($fp,"<td>$justificativa</td>\n");
					echo "</td>";
					echo "</tr>";
				} else {
					fputs($fp,"<td></td>\n");
				}
			}
			if ($achou==0){
				echo "<tr class='Conteudo' height='20' bgcolor='#FFFFCC' align='left'>
					<td colspan='13' style='padding:10px'>NENHUMA OS COM INTERVENÇÃO DA FÁBRICA OU COM REPARO</td>
					</tr>";
			}
			echo "</table></center>";
			fputs($fp,"</tr></table>");
			if ($achou>0 AND $i>0){
				echo "<p style='text-align:center'>$i OS(s) em Intervenção</p>";
				if ($login_fabrica == 14) {
					echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio-os-intervencao-$login_fabrica-$data_xls.xls /tmp/assist/relatorio-os-intervencao-$login_fabrica.html`;
					echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
					echo"<tr>";
					echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><BR>RELATÓRIO DE OS INTERVENÇÃO<BR>Clique aqui para fazer o </font><a href='xls/relatorio-os-intervencao-$login_fabrica-$data_xls.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
					echo "</tr>";
					echo "</table>";
				}
			}
}

?>
<br>
<br>
<br>

<?php include "rodape.php"?>
