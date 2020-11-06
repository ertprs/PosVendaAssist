</div>
<?

if($login_fabrica != 1) {
	header("Location: menu_callcenter.php");
	exit;
}

##### F I N A L I Z A R   P E D I D O #####
if ($_GET["finalizar"] == 1 AND $_GET["unificar"] == "t") {

	$pedido    = $_GET["pedido"];

	$sql =	"UPDATE tbl_pedido SET
				unificar_pedido = 't'
			WHERE tbl_pedido.pedido = $pedido
			AND   tbl_pedido.unificar_pedido IS NULL;";
	$res = pg_exec ($con,$sql);

	if (strlen(pg_errormessage($con)) > 0) {
		$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		$sql = "INSERT INTO tbl_pedido_alteracao (
					pedido
				)VALUES(
					$pedido
				);";
		$res = pg_exec($con,$sql);

		if (strlen(pg_errormessage($con)) > 0) {
			$msg_erro = pg_errormessage($con) ;
		}
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT fn_pedido_finaliza ($pedido,$login_fabrica)";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT fn_pedido_suframa($pedido,$login_fabrica);";
		$res = @pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
	if (strlen($msg_erro) == 0) {
		header ("Location: ".$_COOKIE["CookieLink"]);
		exit;
	}
}

setcookie("CookieLink","http://posvenda.telecontrol.com.br".$_SERVER["REQUEST_URI"]);

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

$msg = "";

if($_POST['chk_opt'])    $chk        = $_POST['chk_opt'];

if($_POST['estado_posto_autorizado'])   $estado_posto_autorizado       = $_POST['estado_posto_autorizado'];
if($_POST['regiao'])   $regiao       = $_POST['regiao'];
if($_POST['arquivo_pedido'])   $arquivo_pedido       = $_POST['arquivo_pedido'];
if($_POST['chk_opt10'])   $chk10       = $_POST['chk_opt10'];
if($_POST['chk_opt11'])   $chk11       = $_POST['chk_opt11'];
if($_POST['chk_opt12'])   $chk12       = $_POST['chk_opt12'];
if($_POST['tipo_pedido']) $tipo_pedido = $_POST['tipo_pedido'];
if($_POST['pedido_status']) $pedido_status = $_POST['pedido_status'];
if($_POST['tipo'])        $tipo        = $_POST['tipo'];

if($_POST["valor_pedido"])	$valor_pedido = trim($_POST["valor_pedido"]);
if($_POST["data_inicial_01"])	$data_inicial_01 = trim($_POST["data_inicial_01"]);
if($_POST["data_final_01"])		$data_final_01   = trim($_POST["data_final_01"]);
if($_POST['codigo_posto'])		$codigo_posto    = trim($_POST['codigo_posto']);
if($_POST["peca_referencia"])	$peca_referencia = trim($_POST["peca_referencia"]);
if($_POST["peca_descricao"])	$peca_descricao  = trim($_POST["peca_descricao"]);
if($_POST["numero_os"])			$numero_os       = trim($_POST["numero_os"]);
if($_POST["numero_nf"])			$numero_nf       = trim($_POST["numero_nf"]);
if($_POST["nome_revenda"])		$nome_revenda    = trim($_POST["nome_revenda"]);
if($_POST["cnpj_revenda"])		$cnpj_revenda    = trim($_POST["cnpj_revenda"]);
if($_POST["numero_pedido"])		$numero_pedido    = trim($_POST["numero_pedido"]);
if($_POST["categoria_pedido"])		$categoria_pedido    = trim($_POST["categoria_pedido"]);

if($_POST["id_representante"])				$representante   		= trim($_POST["id_representante"]);
if($_POST["nome"])				$nome_representante    			= trim($_POST["nome"]);
if($_POST["representante"])		$representante_representante   	= trim($_POST["representante"]);
if($_POST["pdd_rpst"])       $pedido_representante           = trim($_POST["pdd_rpst"]);

	$join_representante = "left join tbl_representante 		ON tbl_pedido.representante 		= tbl_representante.representante
								and tbl_pedido.fabrica = $login_fabrica ";
	$campos_representante = "tbl_representante.codigo, tbl_representante.nome AS nome_representante,";


if(!empty($_POST['pdd_rpst'])){
	$where_representante .= " and tbl_pedido.representante is not null";
	$where_representante .= " AND tbl_pedido.data BETWEEN current_date - interval '6 months' AND current_date";
}

if(!empty($_POST["id_representante"])){
	$where_representante .= " and tbl_pedido.representante = ".$representante;
}


# Desabilitado Alterado por Sono 18/08/2006
# Reabilitado por Tulio 18/08/2006

$data_padrao = "data";
if (strlen($chk10) > 0) $data_padrao = "data";
else                    $data_padrao = "exportado";

$pedidos_nao_exportados = '';
if (!empty($_REQUEST['pedidos_nao_exportados'])) {
    $pedidos_nao_exportados = ' AND tbl_pedido.status_pedido = 1 ';
    $data_padrao = "data";
	$where_representante .= " AND tbl_pedido.data BETWEEN current_date - interval '6 months' AND current_date";
}

$jsonPOST = excelPostToJson($_POST);
?>

<p>
<script language="JavaScript">
var checkflag = "false";
function AbrirJanelaObs (pedido) {
	var largura  = 650;
	var tamanho  = 450;
	var lar      = largura / 2;
	var tam      = tamanho / 2;
	var esquerda = (screen.width / 2) - lar;
	var topo     = (screen.height / 2) - tam;
	var link = "obs_pedido_consulta_blackedecker.php?pedido=" + pedido;
	window.open(link, "janela", "toolbar=no, location=no, status=yes, menubar=no, scrollbars=yes, directories=no, resizable=no, width=" + largura + ", height=" + tamanho + ", top=" + topo + ", left=" + esquerda + "");
}
</script>
<?
	$dt = 0;

	if($tipo_pedido == 93){
		$pedido_acessorio = "AND   tbl_pedido.pedido_acessorio IS TRUE";
	}elseif($tipo_pedido != '' and $tipo_pedido != 93){
		$pedido_acessorio = "AND   tbl_pedido.pedido_acessorio IS FALSE";
	}



	$sql =	"SELECT DISTINCT tbl_pedido.pedido                                          ,
					tbl_pedido.pedido_blackedecker                                      ,
					tbl_pedido.seu_pedido                                               ,
					tbl_pedido.unificar_pedido                                          ,
					tbl_pedido.fabrica                                                  ,
					JSON_FIELD('categoria_pedido',tbl_pedido.valores_adicionais)     AS categoria_pedido,
					to_char(tbl_pedido.exportado,'DD/MM/YYYY HH24:MI:SS')  AS exportado ,
					to_char(tbl_pedido.data,'DD/MM/YYYY HH24:MI:SS')       AS data      ,
					to_char(tbl_pedido.finalizado,'DD/MM/YYYY HH24:MI:SS') AS finalizado,
					tbl_pedido.finalizado                                               ,
					tbl_pedido.pedido_suframa                                           ,
					tbl_pedido.status_pedido                                            ,
					tbl_status_pedido.descricao                AS status_descricao      ,
					tbl_posto_fabrica.codigo_posto                                      ,
					substr(tbl_posto.nome,0,25)                AS nome_posto            ,
					tbl_posto.estado                           AS estado_posto          ,
					tbl_tipo_posto.descricao                   AS tipo_posto            ,
					pedido_tipo_posto.descricao                AS pedido_tipo_posto     ,
					tbl_tipo_pedido.descricao                  AS descricao_tipo_pedido ,
					tbl_tabela.sigla_tabela                                             ,
					tbl_condicao.descricao                     AS condicao_descricao    ,
					tbl_admin.nome_completo                                                     ,
					$campos_representante
					(
						SELECT SUM (tbl_pedido_item.qtde * tbl_pedido_item.preco) AS total
						FROM  tbl_pedido_item
						WHERE tbl_pedido_item.pedido = tbl_pedido.pedido
						GROUP BY tbl_pedido_item.pedido
					)                                          AS total,
					(
						SELECT SUM ((tbl_pedido_item.qtde * tbl_pedido_item.preco)+((tbl_pedido_item.qtde * tbl_pedido_item.preco) * tbl_peca.ipi / 100)) AS total
						FROM tbl_pedido_item
						join tbl_peca using(peca)
						WHERE tbl_pedido_item.pedido = tbl_pedido.pedido
						GROUP BY tbl_pedido_item.pedido
					) AS total_com_ipi
			FROM      tbl_posto
			JOIN      tbl_pedido        ON  tbl_pedido.posto                = tbl_posto.posto
			LEFT JOIN      tbl_pedido_item   ON  tbl_pedido_item.pedido          = tbl_pedido.pedido
			LEFT JOIN      tbl_peca          ON  tbl_peca.peca                   = tbl_pedido_item.peca
			JOIN      tbl_tipo_pedido   ON  tbl_tipo_pedido.tipo_pedido     = tbl_pedido.tipo_pedido
			JOIN      tbl_posto_fabrica ON  tbl_posto_fabrica.posto         = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica       = tbl_pedido.fabrica
			JOIN      tbl_tipo_posto    ON  tbl_tipo_posto.tipo_posto       = tbl_posto_fabrica.tipo_posto
			JOIN      tbl_tabela        ON  tbl_tabela.tabela               = tbl_pedido.tabela
			JOIN      tbl_condicao      ON  tbl_condicao.condicao           = tbl_pedido.condicao
			LEFT JOIN tbl_admin         ON  tbl_pedido.admin                = tbl_admin.admin

			$join_representante

			LEFT JOIN tbl_produto       ON  tbl_produto.produto             = tbl_pedido.produto
			LEFT JOIN tbl_status_pedido ON  tbl_status_pedido.status_pedido = tbl_pedido.status_pedido
			LEFT JOIN tbl_tipo_posto AS pedido_tipo_posto ON pedido_tipo_posto.tipo_posto = tbl_pedido.tipo_posto
			WHERE tbl_pedido.fabrica = $login_fabrica
			$where_representante
			$pedido_acessorio
            $pedidos_nao_exportados
			AND (1=2 ";



# Data do dia
if ($chk == '1') {
	$resX = pg_exec ($con,"SELECT TO_CHAR(current_date,'YYYY-MM-DD')");
	$dia_hoje_inicio = pg_result ($resX,0,0) . " 00:00:00";
	$dia_hoje_final  = pg_result ($resX,0,0) . " 23:59:59";

	$monta_sql .=" OR (tbl_pedido.$data_padrao BETWEEN '$dia_hoje_inicio' AND '$dia_hoje_final') ";
	$dt = 1;

	$msg .= "Pedidos lançados hoje";
}
//	if($ip == '201.42.44.145') echo $monta_sql;

# Dia anterior
if ($chk == '2') {
	$resX = pg_exec ($con,"SELECT TO_CHAR(current_date - INTERVAL '1 day','YYYY-MM-DD')");
	$dia_ontem_inicial = pg_result ($resX,0,0) . " 00:00:00";
	$dia_ontem_final   = pg_result ($resX,0,0) . " 23:59:59";

	$monta_sql .=" OR (tbl_pedido.$data_padrao BETWEEN '$dia_ontem_inicial' AND '$dia_ontem_final') ";
	$dt = 1;

	$msg .= "Pedidos lançados ontem";
}

# Nesta Semana
if ($chk == '3') {
	$resX = pg_exec($con,"SELECT TO_CHAR(current_date,'D')");
	$dia_semana_hoje = pg_result($resX,0,0) - 1 ;

	$resX = pg_exec ($con,"SELECT TO_CHAR(current_date - INTERVAL '$dia_semana_hoje days','YYYY-MM-DD')");
	$dia_semana_inicial = pg_result($resX,0,0) . " 00:00:00";

	$resX = pg_exec($con,"SELECT TO_CHAR('$dia_semana_inicial'::date + INTERVAL '6 days','YYYY-MM-DD')");
	$dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

	$monta_sql .=" OR (tbl_pedido.$data_padrao BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
	$dt = 1;

	$msg .= " Pedidos lançados nesta semana";
}

# Semana anterior
if ($chk == '4') {
	$resX = pg_exec ($con,"SELECT TO_CHAR(current_date,'D')");
	$dia_semana_hoje = pg_result($resX,0,0) - 1 + 7 ;

	$resX = pg_exec ($con,"SELECT TO_CHAR(current_date - INTERVAL '$dia_semana_hoje days','YYYY-MM-DD')");
	$dia_semana_inicial = pg_result ($resX,0,0) . " 00:00:00";

	$resX = pg_exec ($con,"SELECT TO_CHAR('$dia_semana_inicial'::date + INTERVAL '6 days','YYYY-MM-DD')");
	$dia_semana_final = pg_result ($resX,0,0) . " 23:59:59";

	$monta_sql .=" OR (tbl_pedido.$data_padrao BETWEEN '$dia_semana_inicial' AND '$dia_semana_final') ";
	$dt = 1;

	$msg .= "Pedidos lançados na semana anterior";
}

# Neste mês
if ($chk == '5') {
	$mes_inicial = trim(date("Y")."-".date("m")."-01");
	$mes_final   = trim(date("Y")."-".date("m")."-".date("d"));
	$monta_sql .= " OR (tbl_pedido.$data_padrao BETWEEN '$mes_inicial 00:00:00' AND '$mes_final 23:59:59') ";
	$dt = 1;

	$msg .= "Pedidos lançados neste mês";
}

# Entre datas
if (!empty($data_inicial_01) && !empty($data_final_01)) {

	list($di, $mi, $yi) = explode("/", $data_inicial_01);
	list($df, $mf, $yf) = explode("/", $data_final_01);

    if(strlen($msg_erro)==0){
        list($di, $mi, $yi) = explode("/", $data_inicial_01);
        if(!checkdate($mi,$di,$yi))
            $msg_erro = "Data Inicial Inválida";
    }

    if(strlen($msg_erro)==0){
        list($df, $mf, $yf) = explode("/", $data_final_01);
        if(!checkdate($mf,$df,$yf))
            $msg_erro = "Data Final Inválida";
    }

    if(strlen($msg_erro)==0){
        $aux_data_inicial = "$yi-$mi-$di";
        $aux_data_final = "$yf-$mf-$df";
    }

    if(strlen($msg_erro)==0){
        if(strtotime($aux_data_final) < strtotime($aux_data_inicial) or strtotime($aux_data_final) > strtotime('today')){
            $msg_erro = "Data final não pode ser menor que data inicial ou maior que a data atual.";
        }
    }

    /*O trecho abaixo, colocar apenas se o relatório não permitir pesquisa em um
	    intervalo maios que 60 dias.
	===================INICIO=======================*/
	if(strlen($msg_erro)==0){
	    if (strtotime($aux_data_inicial.'+6 months') < strtotime($aux_data_final) ) {
            $msg_erro = 'O intervalo entre as datas não pode ser maior que 6 meses';
        }
    }
	if(strlen($msg_erro)==0){
        $aux_data_inicial = "$yi-$mi-$di 00:00:00";
        $aux_data_final = "$yf-$mf-$df 23:59:59";
    }

	if (!$msg_erro) {
		$monta_sql .=" OR (tbl_pedido.$data_padrao::date BETWEEN '$aux_data_inicial' AND '$aux_data_final') ";
		$dt = 1;
	}

	$msg .= "Pedidos lançados entre os dias $data_inicial_01 e $data_final_01";
}

# Posto
if (!empty($codigo_posto)) {
	if ($dt == 1) $xsql = " AND ";
	else          $xsql = " OR ";
	$monta_sql .= " $xsql tbl_posto_fabrica.codigo_posto ='$codigo_posto' ";
	$dt = 1;

	$msg .= " e Pedidos lançados pelo posto $nome_posto";
}

# Peça
if (!empty($peca_referencia)) {
	$peca_referencia = str_replace("-","",$peca_referencia);

	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	$monta_sql .= "$xsql tbl_peca.referencia_pesquisa = '".$peca_referencia."' ";
	$dt = 1;

	$msg .= " e Pedidos lançados pela peça $peca_descricao";

}

#estado pedido
if (count($estado_posto_autorizado) > 0) {
	$novoUF = array();
	foreach ($estado_posto_autorizado as $key => $value) {
		$novoUF[] = "'$value'";
	}
	$monta_sql .= " AND tbl_posto.estado IN (".implode(",", $novoUF).")";
}

#Arquivo pedido
if (strlen($arquivo_pedido) > 11) {
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	$arquivo_pedido = trim($arquivo_pedido);
	$dia     = substr($arquivo_pedido, 3, 2);
	$mes     = substr($arquivo_pedido, 5, 2);
	$hora    = substr($arquivo_pedido, 7, 2);
	$minuto  = substr($arquivo_pedido, 9, 2);
	if($hora == '09' and strpos($arquivo_pedido,'ped')) $hora = "21";
	if ($mes > date("m")) {
		$ano = (date("Y")-1);
	} else {
		$ano = date("Y");
	}

	$dataPedidoI = "$ano-$mes-$dia $hora:$minuto:00";
	$dataPedidoF = "$ano-$mes-$dia $hora:$minuto:59";
	$monta_sql .= " $xsql (tbl_pedido.exportado BETWEEN '$dataPedidoI'::timestamp - interval '5 minutes' AND '$dataPedidoF'::timestamp + interval '5 minutes' and  tbl_pedido.arquivo_pedido = '$arquivo_pedido' ) ";
	$dt = 1;
}

#regiao
if (strlen($regiao) > 0) {
    if ($regiao == 1) {
        $monta_sql .= " AND tbl_posto_fabrica.contato_estado = 'SP'";
    }

	//Região Sudeste exceto sp
    if ($regiao == 2) {
        $monta_sql .= " AND tbl_posto_fabrica.contato_estado IN ('RJ', 'ES', 'MG')";
    }

    //Região Centro-Oeste
    if ($regiao == 3) {
        $monta_sql .= " AND tbl_posto_fabrica.contato_estado IN ('GO', 'MS', 'MT', 'DF')";
    }

    //Região Nordeste
    if ($regiao == 4) {
        $monta_sql .= " AND tbl_posto_fabrica.contato_estado IN ('AL', 'BA', 'CE', 'MA', 'PB', 'PE', 'PI', 'RN', 'SE')";
    }

    //Região Norte
    if ($regiao == 5) {
        $monta_sql .= " AND tbl_posto_fabrica.contato_estado IN ('AC', 'AP', 'AM', 'PA', 'RO', 'RR',  'TO')";
    }

    //Região Sul
    if ($regiao == 6) {
        $monta_sql .= " AND tbl_posto_fabrica.contato_estado IN ('PR', 'SC', 'RS')";
    }
}

if (!empty($categoria_pedido)) {
    $monta_sql .= " AND JSON_FIELD('categoria_pedido',tbl_pedido.valores_adicionais) = '$categoria_pedido'";
}

if (!empty($numero_pedido))
{
	// cliente
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	$numero_pedido = trim ($numero_pedido);
#Utilizando o campo seu_pedido para a black HD32341
#	$monta_sql .= "$xsql (tbl_pedido.pedido_cliente='".$numero_pedido."' OR tbl_pedido.pedido_blackedecker = $numero_pedido OR tbl_pedido.pedido_blackedecker = ($numero_pedido+100000) OR tbl_pedido.pedido_blackedecker = ($numero_pedido+200000) OR tbl_pedido.pedido_blackedecker = ($numero_pedido+200000) OR tbl_pedido.pedido_blackedecker = ($numero_pedido+300000) OR tbl_pedido.pedido_blackedecker = ($numero_pedido+400000)) ";
	$monta_sql .= "$xsql (tbl_pedido.pedido_blackedecker='".$numero_pedido."' OR substr(tbl_pedido.seu_pedido,4) = '$numero_pedido' OR tbl_pedido.seu_pedido = '$numero_pedido')";
	$dt = 1;

	$msg .= " e Pedidos lançados pelo cliente $numero_pedido";
}

// Finalizado?
if (strlen($chk10) > 0) {
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	$monta_sql .= "$xsql ( tbl_pedido.finalizado IS NULL AND tbl_pedido.exportado IS NULL )";
	$dt = 1;

	$msg .= " e Pedidos não finalizados";
}

// Promocional
if (strlen($chk11) > 0) {
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	$monta_sql .= "$xsql tbl_condicao.promocao IS TRUE ";
	$dt = 1;

	$msg .= " e Pedidos promocionais";
}
//Sedex
if (strlen($chk12) > 0) {
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";
	$monta_sql .= "$xsql tbl_pedido.pedido_sedex IS TRUE ";
	$dt = 1;

	$msg .= " e Pedidos sedex";
}

if (strlen($tipo_pedido) > 0) {
	// Tipo do Pedido
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";

	/* HD 21725 */
	$tipo_pedido_aux = explode('|',trim($tipo_pedido));
	if (count($tipo_pedido_aux)==2){
		if ($tipo_pedido_aux[1]=="produto"){
			$monta_sql .= " AND tbl_pedido.troca IS TRUE ";
		}
		if ($tipo_pedido_aux[1]=="peca"){
			$monta_sql .= " AND tbl_pedido.troca IS NOT TRUE ";
		}
		$tipo_pedido = $tipo_pedido_aux[0];
	}
	$monta_sql .= "$xsql tbl_pedido.tipo_pedido=" . $tipo_pedido;
	$dt = 1;

	$msg .= " e Pedidos lançados pelo cliente $numero_pedido";
}

if (strlen($tipo) > 0) {
	// tipo de pedido
	if ($dt == 1) $xsql = "AND ";
	else          $xsql = "OR ";

	$monta_sql .= "$xsql tbl_pedido.tipo_pedido = $tipo ";
}

if (strlen($pedido_status) > 0) {
	$monta_sql .= "AND tbl_pedido.status_pedido = $pedido_status ";
}

// ordena sql padrao
$sql .= $monta_sql;
$sql .= ") ORDER BY tbl_pedido.pedido ASC";
// echo nl2br($sql);exit;
$res = pg_exec($con,$sql);
$sqlCount  = "SELECT count(*) FROM (";
$sqlCount .= $sql;
$sqlCount .= ") AS count";

echo "<TABLE align='center'>\n";

if (@pg_numrows($res) != 0) {
	echo "<TR class='menu_top'>\n";
	echo "	<TD colspan='100%' class='tac' align='center'><h4>$msg</h4></TD>\n";
	echo "</TR>\n";
	echo "</table>\n";
}

if ($msg_erro){
	echo "<TABLE width='1000' align='center' border='0' class='msg_erro' cellspacing='1' cellpadding='1'>\n";

	echo "<TR><TR>\n";
	echo "	<TD colspan='5'>$msg_erro</TD>\n";
	echo "</TR>\n";
	echo "</table>\n";
}

if (@pg_numrows($res) == 0) {
	echo "<div class='alert alert-warning' style='width: 80%;position: relative;left: 7.5%;'><h4>Não existem pedidos com estes parâmetros</h4></div>";
}

if (@pg_numrows($res) > 0) {
	echo "<TABLE class'table table-striped table-bordered'>\n";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$pedido              = trim(pg_result ($res,$i,pedido));
		$pedido_suframa      = trim(pg_result ($res,$i,pedido_suframa));
		$pedido_blackedecker = trim(pg_result ($res,$i,pedido_blackedecker));
		$seu_pedido          = trim(pg_result ($res,$i,seu_pedido));
		$categoria_pedido    = trim(pg_result ($res,$i,categoria_pedido));
		$unificar_pedido     = trim(pg_result ($res,$i,unificar_pedido));
		$descricao_tipo      = trim(pg_result ($res,$i,descricao_tipo_pedido));
		$data                = trim(pg_result ($res,$i,data));
		$finalizado          = trim(pg_result ($res,$i,finalizado));
		$exportado           = trim(pg_result ($res,$i,exportado));
		$nome_completo       = trim(pg_result ($res,$i,nome_completo));
		$codigo_posto        = trim(pg_result ($res,$i,codigo_posto));
		$nome_posto          = trim(pg_result ($res,$i,nome_posto));
		$estado_posto        = trim(pg_result ($res,$i,estado_posto));
		$tipo_posto          = trim(pg_result ($res,$i,tipo_posto));
		$pedido_tipo_posto   = trim(pg_result ($res,$i,pedido_tipo_posto));
		$condicao_descricao  = trim(pg_result ($res,$i,condicao_descricao));
		$sigla_tabela        = trim(pg_result ($res,$i,sigla_tabela));
		$total               = trim(pg_result ($res,$i,total));
		$total_com_ipi       = trim(pg_result ($res,$i,total_com_ipi));
		$total_geral         = $total_geral + $total;
		$total_geral_com_ipi = $total_geral_com_ipi + $total_com_ipi;
		$total               = number_format($total,2,",",".");
		$total_com_ipi       = number_format($total_com_ipi,2,",",".");
		$status_pedido       = trim(pg_result ($res,$i,status_pedido));
		$status_descricao    = trim(pg_result ($res,$i,status_descricao));

		$codigo    			 = trim(pg_result ($res,$i,codigo));
		$nome_representante  = trim(pg_result ($res,$i,nome_representante));


		$pedido_blackedecker = "00000" . $pedido_blackedecker;
		$pedido_blackedecker = substr($pedido_blackedecker, strlen($pedido_blackedecker)-5, strlen($pedido_blackedecker));

		if (strlen($seu_pedido)>0){
			$pedido_blackedecker = fnc_so_numeros($seu_pedido);
		}

		if ($unificar_pedido == 't') $unificar_pedido = "S";
		else                         $unificar_pedido = "N";

        switch ($categoria_pedido) {
            case "cortesia":
                $categoria_pedido_descricao = "CORTESIA";
                break;
            case "credito_bloqueado":
                $categoria_pedido_descricao = "CRÉDITO BLOQUEADO";
                break;
            case "erro_pedido":
                $categoria_pedido_descricao = "ERRO DE PEDIDO";
                break;
            case "kit":
                $categoria_pedido_descricao = "KIT DE REPARO";
                break;
            case "midias":
                $categoria_pedido_descricao = "MÍDIAS";
                break;
            case "outros":
                $categoria_pedido_descricao = "OUTROS";
                break;
            case "valor_minimo":
                $categoria_pedido_descricao = "VALOR MÍNIMO";
				break;
			case "divergencia":
                $categoria_pedido_descricao = "DIVERGÊNCIAS LOGÍSTICA/ESTOQUE";
                break;
            case "problema_distribuidor":
                $categoria_pedido_descricao = "PROBLEMAS COM DISTRIBUIDOR";
				break;
			case "acessorios":
                $categoria_pedido_descricao = "ACESSÓRIOS";
				break;
			case "item_similar":
                $categoria_pedido_descricao = "ITEM SIMILAR";
                break;
            case "vsg":
                $categoria_pedido_descricao = "VSG";
                break;
            default:
                $categoria_pedido_descricao = "";
                break;
        }
        $sqlOrigem = "
            SELECT * FROM tbl_pedido_item
            JOIN tbl_peca USING(peca)
            WHERE pedido = $pedido
            AND (origem = 'FAB/SA' OR origem = 'IMP/SA')
            LIMIT 1
            ";
        $qryOrigem = pg_query($con, $sqlOrigem);

        if (pg_num_rows($qryOrigem) == 1) {

            $sqlSemIPI = "
                SELECT
                    SUM (
                        CASE WHEN (tbl_peca.origem = 'FAB/SA' OR tbl_peca.origem = 'IMP/SA') THEN
                            ((tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * 0.6) * tbl_pedido_item.qtde)
                        ELSE
                            (tbl_pedido_item.qtde * tbl_pedido_item.preco)
                        END
                    ) AS sem_ipi
                FROM tbl_pedido_item
                JOIN tbl_pedido USING(pedido)
                JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca
                LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca= tbl_peca.peca AND tbl_tabela_item.tabela = tbl_pedido.tabela
                WHERE tbl_pedido_item.pedido = $pedido
                GROUP BY tbl_pedido_item.pedido
                ";

            $qrySemIPI = pg_query($con, $sqlSemIPI);
            $total_sem_ipi = 0;

            if (pg_num_rows($qryOrigem) > 0) {
                while($fetch = pg_fetch_assoc($qrySemIPI)) {
                    $total_sem_ipi += floatval($fetch['sem_ipi']);
                }
            }

            if (!empty($total_sem_ipi)) {
                $total_com_ipi = $total;
                $total = number_format($total_sem_ipi, 2, ",", ".");
            }

        }

		if (strlen($valor_pedido) == 0) {

			echo "<thead><TR class='titulo_coluna'>\n";
			echo "<TH>UP</TH>\n";
			echo "<TH>TIPO</TH>\n";
			echo "<TH>ADMIN</TH>\n";
			echo "<TH>PEDIDO</TH>\n";
			echo "<TH>CATEGORIA</TH>\n";
			echo "<TH>ABERTURA</TH>\n";
			echo "<TH>FINALIZADO</TH>\n";
			echo "<TH>POSTO</TH>\n";
			echo "<TH>REGIÃO</TH>\n";
			if(!empty($_POST['pdd_rpst'])){
				echo "<TH>REPRESENTANTE</TH>\n";
			}
			echo "<TH>TIPO ATUAL</TH>\n";
			echo "<TH>TIPO ANTERIOR</TH>\n";
			echo "<TH>CONDIÇÃO</TH>\n";
			echo "<TH>TABELA</TH>\n";
			echo "<TH>TOTAL</TH>\n";
			echo "<TH>TOTAL+IPI</TH>\n";
			echo "<TH colspan='2'>AÇÕES</TH>\n";
			echo "<TH>OBS</TH>\n";
			echo "</TR></thead>\n";

			echo "<TR class='table_line' bgcolor='#F1F4FA'>\n";
			echo "<TD align='center'>$unificar_pedido</TD>\n";
			echo "<TD align='center'>";
			if (strlen($pedido_suframa) > 0) {
				echo "IMP";
			}else{
				echo "&nbsp;";
			}
			echo "</TD>\n";
			echo "<TD>$nome_completo</TD>\n";
			echo "<TD><a href='pedido_admin_consulta.php?pedido=$pedido' target ='_blank'><font color='#000000'>$pedido_blackedecker</font></a></TD>\n";
			echo "<TD align='center'>$categoria_pedido_descricao</TD>\n";
			echo "<TD align='center'>$data</TD>\n";
			echo "<TD align='center'>$finalizado</TD>\n";
			echo "<TD nowrap><ACRONYM TITLE='$codigo_posto - $nome_posto'>$codigo_posto - "
			.substr($nome_posto,0,20)."</ACRONYM></TD>\n";
			echo "<TD align='center'>$estado_posto</TD>\n";
			if(!empty($_POST['pdd_rpst'])){
				echo "<TD align='center'>$codigo - $nome_representante</TD>\n";
			}
			echo "<TD align='center'>$tipo_posto</TD>\n";
			echo "<TD align='center'>$pedido_tipo_posto</TD>\n";
			echo "<TD align='center'>$condicao_descricao</TD>\n";
			echo "<TD align='center'>$sigla_tabela</TD>\n";
			echo "<TD align='center'>$total</TD>\n";
			echo "<TD align='center'>$total_com_ipi</TD>\n";
			echo "<TD align='center' nowrap colspan='2'>&nbsp;";
			if($status_pedido <> 14) {
				if (in_array($login_admin,array(112,232,245))) {  // duas usuarias da Black&Decker
					if (strlen($exportado) == 0) {
					echo "<a class='btn btn-primary' href='pedido_cadastro_blackedecker.php?pedido=$pedido' target='_blank'>Alterar</a>";
					}
					if (strlen($exportado) == 0) {
						echo "&nbsp;<a class='btn btn-success' href='pedido_parametros.php?pedido=$pedido&finalizar=1&unificar=t'>Finalizar</a>";
					}
				}else{
					if (strlen($exportado) == 0) {
					echo "<img class='btn btn-primary' alt='Alterar'>";
					}
					if (strlen($exportado) == 0) {
						echo "&nbsp;<img alt='Finalizar' class='btn btn-success' border='0' >";
					}
				}
			}
			echo "&nbsp;</TD>\n";
			echo "<td><a href=\"javascript:AbrirJanelaObs('$pedido')\">Inserir Obs</a></td>";
			echo "</TR>\n";

			if (strlen($exportado) > 0) {
				echo "<TR class='table_line'>\n";
				echo "<TD align='left' colspan='100%'>Enviado para fábrica em $exportado";
				if($status_pedido == 14) { echo "<br><b>Pedido: $status_descricao</b>";}
				echo "<br></TD>\n";
				echo "</TR>\n";
				echo "<tr><td colspan='100%'>&nbsp;</td></tr>";
			}
		}
	}
	if (strlen($valor_pedido) == 0) {

		echo "</TABLE>\n";
		echo "<br>\n";

		echo "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='2'>\n";
		echo "<TR class='table_line'>\n";
		echo "<TD align='center'><b>TOTAL GERAL</b></TD>\n";
		echo "<TD align='right'><b>". number_format($total_geral,2,",",".") ."</b></TD>\n";
		echo "</TR>\n";

		echo "<TR class='table_line'>\n";
		echo "<TD align='center'><b>TOTAL GERAL COM IPI</b></TD>\n";
		echo "<TD align='right'><b>". number_format($total_geral_com_ipi,2,",",".") ."</b></TD>\n";
		echo "</TR>\n";

		echo "</TABLE>\n";
		echo "<br>\n";
	}


	if (strlen($valor_pedido) > 0) {
		echo "</TABLE>\n";
		echo "<br>\n";
		echo "<TABLE width='700' align='center' border='1' cellspacing='1' cellpadding='2'>\n";
		echo "<TR class='table_line titulo_coluna'>\n";
		echo "<TD align='center'><b>TOTAL GERAL</b></TD>\n";
		echo "<TD align='center'><b>TOTAL GERAL COM IPI</b></TD>\n";
		echo "</TR>\n";

		echo "<TR class='table_line'>\n";
		echo "<TD align='center'><b>". number_format($total_geral,2,",",".") ."</b></TD>\n";
		echo "<TD align='center'><b>". number_format($total_geral_com_ipi,2,",",".") ."</b></TD>\n";
		echo "</TR>\n";

		echo "</TABLE>\n";
		echo "<br>\n";
	}

	//gera excel
	$dataGeracaoExcel           = date("d-m-Y-H-i");
	$arquivo_nome_c = "pedidos-$login_fabrica-$dataGeracaoExcel.xls";
	$path           = "xls/";
	$path_tmp       = "/tmp/";

	if (!is_dir($path_tmp)) {
		mkdir($path_tmp);
		chmod($path_tmp, 0777);
	}

	$arquivo_completo     = $path.$arquivo_nome_c;
	$arquivo_completo_tmp = $path_tmp.$arquivo_nome_c;

	$fp = fopen ($arquivo_completo_tmp,"w+");
	$tabela_conteudo     =  "<table width='100%' align='left' border='1' cellpadding='2' cellspacing='1'>";
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$excel_pedido              = trim(pg_result ($res,$i,pedido));
		$excel_pedido_suframa      = trim(pg_result ($res,$i,pedido_suframa));
		$excel_pedido_blackedecker = trim(pg_result ($res,$i,pedido_blackedecker));
		$excel_seu_pedido          = trim(pg_result ($res,$i,seu_pedido));
		$excel_unificar_pedido     = trim(pg_result ($res,$i,unificar_pedido));
		$excel_descricao_tipo      = trim(pg_result ($res,$i,descricao_tipo_pedido));
        $categoria_pedido          = trim(pg_result ($res,$i,categoria_pedido));
		$excel_data                = trim(pg_result ($res,$i,data));
		$excel_finalizado          = trim(pg_result ($res,$i,finalizado));
		$excel_exportado           = trim(pg_result ($res,$i,exportado));
		$excel_nome_completo       = trim(pg_result ($res,$i,nome_completo));
		$excel_codigo_posto        = trim(pg_result ($res,$i,codigo_posto));
		$excel_nome_posto          = trim(pg_result ($res,$i,nome_posto));
		$excel_estado_posto        = trim(pg_result ($res,$i,estado_posto));
		$excel_tipo_posto          = trim(pg_result ($res,$i,tipo_posto));
		$excel_pedido_tipo_posto   = trim(pg_result ($res,$i,pedido_tipo_posto));
		$excel_condicao_descricao  = trim(pg_result ($res,$i,condicao_descricao));
		$excel_sigla_tabela        = trim(pg_result ($res,$i,sigla_tabela));
		$excel_total               = trim(pg_result ($res,$i,total));
		$excel_total_com_ipi       = trim(pg_result ($res,$i,total_com_ipi));

		$excel_total_geral         = $excel_total_geral + $excel_total;
		$excel_total_geral_com_ipi = $excel_total_geral_com_ipi + $excel_total_com_ipi;
		$excel_total               = number_format($excel_total,2,",",".");
		$excel_total_com_ipi       = number_format($excel_total_com_ipi,2,",",".");
		$excel_status_pedido       = trim(pg_result ($res,$i,status_pedido));
		$excel_status_descricao    = trim(pg_result ($res,$i,status_descricao));
		$excel_codigo    		   = trim(pg_result ($res,$i,codigo));
		$excel_nome_representante  = trim(pg_result ($res,$i,nome_representante));
		$excel_pedido_blackedecker = "00000" . $excel_pedido_blackedecker;
		$excel_pedido_blackedecker = substr($excel_pedido_blackedecker, strlen($excel_pedido_blackedecker)-5, strlen($excel_pedido_blackedecker));

		if (strlen($excel_seu_pedido) > 0) {
			$excel_pedido_blackedecker = fnc_so_numeros($excel_seu_pedido);
		}

		if ($excel_unificar_pedido == 't') {
			$excel_unificar_pedido = "S";
		} else {
			$excel_unificar_pedido = "N";
		}

        $sqlOrigemExcel = "SELECT *
		                FROM tbl_pedido_item
		                JOIN tbl_peca USING(peca)
		               WHERE pedido = $pedido
		                 AND (origem = 'FAB/SA' OR origem = 'IMP/SA')
		               LIMIT 1";
        $qryOrigemExcel = pg_query($con, $sqlOrigemExcel);

        if (pg_num_rows($qryOrigemExcel) == 1) {
            $sqlSemIPIExcel = "
                SELECT
                    SUM (
                        CASE WHEN (tbl_peca.origem = 'FAB/SA' OR tbl_peca.origem = 'IMP/SA') THEN
                            ((tbl_tabela_item.preco / (1 - (1.65 + 7.6 + 4) / 100) / 0.9 / 0.7 / 0.7 * 0.6) * tbl_pedido_item.qtde)
                        ELSE
                            (tbl_pedido_item.qtde * tbl_pedido_item.preco)
                        END
                    ) AS sem_ipi
                FROM tbl_pedido_item
                JOIN tbl_pedido USING(pedido)
                JOIN tbl_peca ON tbl_pedido_item.peca = tbl_peca.peca
                LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca= tbl_peca.peca AND tbl_tabela_item.tabela = tbl_pedido.tabela
                WHERE tbl_pedido_item.pedido = $excel_pedido
                GROUP BY tbl_pedido_item.pedido
                ";
            $qrySemIPIExcel = pg_query($con, $sqlSemIPIExcel);
            $excel_total_sem_ipi = 0;

            if (pg_num_rows($qryOrigemExcel) > 0) {
                while($fetchExcel = pg_fetch_assoc($qrySemIPIExcel)) {
                    $excel_total_sem_ipi += floatval($fetchExcel['sem_ipi']);
                }
            }

            if (!empty($excel_total_sem_ipi)) {
                $excel_total_com_ipi = $excel_total;
                $excel_total = number_format($excel_total_sem_ipi, 2, ",", ".");
            }
        }

        if  ($i == 0) {

			if(!empty($pedido_representante)){
				$th_respre = "<TH>REPRESENTANTE</TH>";
			}
			$tabela_conteudo .= "<thead>
				              	<TR class='titulo_coluna'>
									<TH>UP</TH>
									<TH>TIPO</TH>
									<TH>ADMIN</TH>
									<TH>PEDIDO</TH>
									<TH>CATEGORIA</TH>
									<TH>ABERTURA</TH>
									<TH>FINALIZADO</TH>
									<TH>POSTO</TH>
									<TH>REGIÃO</TH>
									{$th_respre}
									<TH>TIPO ATUAL</TH>
									<TH>TIPO ANTERIOR</TH>
									<TH>CONDIÇÃO</TH>
									<TH>TABELA</TH>
									<TH>TOTAL</TH>
									<TH>TOTAL+IPI</TH>
								</TR>
							</thead>";

			if (strlen($pedido_suframa) > 0) {
				$campoSuframa =  "<TD align='center'>IMP</TD>";
			} else {
				$campoSuframa =  "<TD align='center'>&nbsp;</TD>";
			}

			if(!empty($pedido_representante)){
				$campoRepre = "<TD align='center'>$codigo - $nome_representante</TD>";
			}
        }

    	/*HD-4126894*/
    	$aux_sql = "SELECT valores_adicionais FROM tbl_pedido WHERE pedido = $excel_pedido LIMIT 1";
    	$aux_res = pg_query($con, $aux_sql);
    	$aux_val = json_decode(pg_fetch_result($aux_res, 0, 'valores_adicionais'));
    	$ver_cat = false;

    	if (count($aux_val) > 0) {
    		foreach ($aux_val as $key => $object) {
    			if ($key == "categoria_pedido") {
    				$ver_cat = true;
    				switch ($object) {
			            case "cortesia":
			                $categoria_pedido_descricao = "CORTESIA";
			                break;
			            case "credito_bloqueado":
			                $categoria_pedido_descricao = "CRÉDITO BLOQUEADO";
			                break;
			            case "erro_pedido":
			                $categoria_pedido_descricao = "ERRO DE PEDIDO";
			                break;
			            case "kit":
			                $categoria_pedido_descricao = "KIT DE REPARO";
			                break;
			            case "midias":
			                $categoria_pedido_descricao = "MÍDIAS";
			                break;
			            case "outros":
			                $categoria_pedido_descricao = "OUTROS";
			                break;
			            case "valor_minimo":
			                $categoria_pedido_descricao = "VALOR MÍNIMO";
			                break;
			            case "vsg":
			                $categoria_pedido_descricao = "VSG";
							break;
						case "divergencia":
							$categoria_pedido_descricao = "DIVERGÊNCIAS LOGÍSTICA/ESTOQUE";
							break;
						case "problema_distribuidor":
							$categoria_pedido_descricao = "PROBLEMAS COM DISTRIBUIDOR";
							break;
						case "acessorios":
							$categoria_pedido_descricao = "ACESSÓRIOS";
							break;
						case "item_similar":
							$categoria_pedido_descricao = "ITEM SIMILAR";
							break;
			            default:
			                $categoria_pedido_descricao = "";
			                break;
			        }
    			}
    		}
    	}

    	if ($ver_cat === false) {
    		$categoria_pedido_descricao = "";
    	}

    	unset($aux_sql, $aux_res, $aux_val, $ver_cat);

		$tabela_conteudo .= "<TR class='table_line' bgcolor='#F1F4FA'>
								<TD align='center'>$unificar_pedido</TD>
								{$campoSuframa}
								<TD>$excel_nome_completo</TD>
								<TD><font color='#000000'>$excel_seu_pedido</font></TD>
								<TD><font color='#000000'>$categoria_pedido_descricao</font></TD>
								<TD align='center'>$excel_data</TD>
								<TD align='center'>$excel_finalizado</TD>
								<TD nowrap><ACRONYM TITLE='$excel_codigo_posto - $excel_nome_posto'>$excel_codigo_posto - " . substr($excel_nome_posto,0,20)."</ACRONYM></TD>
								<TD align='center'>$excel_estado_posto</TD>
								{$campoRepre }
								<TD align='center'>$excel_tipo_posto</TD>
								<TD align='center'>$excel_pedido_tipo_posto</TD>
								<TD align='center'>$excel_condicao_descricao</TD>
								<TD align='center'>$excel_sigla_tabela</TD>
								<TD align='center'>$excel_total</TD>
								<TD align='center'>$excel_total_com_ipi</TD>
							</TR>";
		$msn_pedido = "";
		if ($excel_status_pedido == 14) {
			$msn_pedido =  "<br><b>Pedido: $excel_status_descricao</b>";
		}
		//if (strlen($exportado) > 0) {
		//	$tabela_conteudo .= "<TR class='table_line'>
		//							<TD align='left' colspan='15'>Enviado para fábrica em $//excel_exportado {$msn_pedido}</TD>
		//						</TR>
		//						<TR>
		//							<TD colspan='15'>&nbsp;</TD>
		//						</TR>";
	    //
		//}
	}//fecha for
		$tabela_conteudo .= "</TABLE>\n";

	$tabela_conteudo_total =  "<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='2'>
							<TR class='table_line'>
								<TD></TD>
							</TR>
						</TABLE>\n
						<TABLE width='700' align='center' border='0' cellspacing='1' cellpadding='2'>
							<TR class='table_line'>
								<TD colspan='6'></TD>
								<TD align='center'><b>TOTAL GERAL</b></TD>
								<TD align='right'><b>". number_format($excel_total_geral,2,",",".") ."</b></TD>
								<TD colspan='7'></TD>
							</TR>
							<TR class='table_line'>
								<TD colspan='6'></TD>
								<TD align='center'><b>TOTAL GERAL COM IPI</b></TD>
								<TD align='right'><b>". number_format($excel_total_geral_com_ipi,2,",",".") ."</b></TD>
								<TD colspan='7'></TD>
							</TR>
						</TABLE>\n";

	fputs ($fp, $tabela_conteudo);
	fputs ($fp, $tabela_conteudo_total);
	fclose ($fp);
	echo `cp $arquivo_completo_tmp $arquivo_completo`;

    echo "
    <br />
    <div id='gerar_excel' onclick='javascript: window.location=\"xls/{$arquivo_nome_c}\";' class='btn_excel'>
          <span><img src='imagens/excel.png' /></span>
          <span class='txt'>Gerar Arquivo Excel</span>
    </div>";

}
?>

<br>
