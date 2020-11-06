<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include "funcoes.php";

//verifica se tem comunicado de acessorio, caso tenha não terá acesso a este programa.

 $sql = "SELECT comunicado FROM tbl_comunicado WHERE fabrica = {$login_fabrica} AND tipo = 'Acessório' AND ativo ORDER BY comunicado DESC;";
$res = pg_query($con, $sql);
if(pg_num_rows($res)){
	if(!in_array($login_posto,array(6359,829,17766,5150))){
		header("Location: menu_pedido.php");
	}
}
$sqlCat = "SELECT categoria FROM tbl_posto_fabrica where fabrica = $login_fabrica and posto = $login_posto ";
$resCat = pg_query($con, $sqlCat); 
if(pg_num_rows($resCat)>0){
	$categoria = pg_fetch_result($resCat, 0, 'categoria');
}

if($_GET['valida_multiplo'] == 'sim'){ //hd_chamado=2543280
	$peca = $_GET['peca'];
	$qtde_antiga = $_GET['qtde'];

	$sqlMultiplo = "SELECT tbl_peca.multiplo
						FROM tbl_peca
						WHERE fabrica = $login_fabrica
						AND referencia = '$peca'";
	$resMultiplo = pg_query($con, $sqlMultiplo);

	if(pg_num_rows($resMultiplo) > 0){
		$qtde_multiplo = pg_fetch_result($resMultiplo, 0, 'multiplo');

		$var1 = $qtde_antiga % $qtde_multiplo;
		$var1 = floor($var1);

		if($var1 > 0){
			$peca_qtde_mult = $qtde_antiga-$var1+$qtde_multiplo;

			if($qtde_antiga <> $peca_qtde_mult){
				$peca_qtde = $peca_qtde_mult;
			}
		}else{
			$peca_qtde = $qtde_antiga;
		}
	}
	echo "ok|".$peca_qtde;
	exit;
}

if (strlen(trim($_GET["msg"])) > 0) $msg = trim($_GET["msg"]);

$abrir = fopen("bloqueio_pedidos/bloqueia_pedido_black.txt", "r");
$ler = fread($abrir, filesize("bloqueio_pedidos/bloqueia_pedido_black.txt"));
fclose($abrir);

$conteudo = explode(";;", $ler);

$data_inicio = $conteudo[0];
$data_fim    = $conteudo[1];
$comentario  = $conteudo[3];

//PROMOÇÃO
# Obs.: no HD 59222 foi coloca uma validação na fn_finaliza_pedido_blackedecker
#   para não aceitar pedidos promocionais, uma vez que a Black não aceitará
#   este tipo de pedido em 2009. A razão de ter colocado na função é que no
#   envio de pedido via arquivo (pedido_upload.php) permitia-se o cadastro de
#   pedido promocional mesmo não estando de fato liberado

$sql_cond = "
        SELECT  tbl_posto_fabrica.pedido_em_garantia
        FROM    tbl_posto_fabrica
        WHERE   tbl_posto_fabrica.fabrica   = $login_fabrica
        AND     tbl_posto_fabrica.posto     = $login_posto
";
$res_cond = pg_query($con,$sql_cond);
$cond_manual = "\n";
if(pg_fetch_result($res_cond,0,pedido_em_garantia) != 't'){
    $cond_manual = "AND tbl_condicao.garantia_manual IS NOT TRUE\n";

    $abrir = fopen("bloqueio_pedidos/libera_promocao_black.txt", "r");
    $ler_p = fread($abrir, filesize("bloqueio_pedidos/libera_promocao_black.txt"));
    fclose($abrir);

    $conteudo_p = explode(";;", $ler_p);

    $data_inicio_p = $conteudo_p[0];
    $data_fim_p    = $conteudo_p[1];
    $comentario_p  = $conteudo_p[2];
    $promocao = "f";

    if (strval(strtotime(date("Y-m-d H:i:s")))  < strval(strtotime("$data_fim_p"))) { // DATA DA VOLTA
        if (strval(strtotime(date("Y-m-d H:i:s")))  >= strval(strtotime("$data_inicio_p"))) { // DATA DO BLOQUEIO
            $promocao = "t";
        }
    }
}

if (strval(strtotime(date("Y-m-d H:i:s")))  < strval(strtotime("$data_fim"))) { // DATA DA VOLTA
	if (strval(strtotime(date("Y-m-d H:i:s")))  >= strval(strtotime("$data_inicio"))) { // DATA DO BLOQUEIO
		$title     = "Pedido de Peças";
		$cabecalho = "Pedido de Peças";
		$layout_menu = 'pedido';


			include "cabecalho.php";

			//			echo "$ler<br><br>";

			echo "<br><br>\n";
			echo "<table width='500' border='0' align='center' cellpadding='5' cellspacing='2' style='border-collapse: collapse' bordercolor='000000'>";
			echo "<TR align='center' bgcolor='#336699'>";
			echo "<form method='post' name='frm_condicao' action='$PHP_SELF'>";
			echo "	<TD colspan='3' height='40' style='font-family: verdana, arial ; font-size: 16px; font-size: 14px; color:#FFFFFF;'><B>COMUNICADO BLACK & DECKER</B></TD>";
			echo "<TR align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000' bgcolor='#C1E0FF' onmouseout=\"this.bgColor='#C1E0FF'\">";
			echo "<TD><p align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000'>$comentario</p></TD>";

			/*			Devido  ao feriado de Carnaval e ao nosso fechamento mensal  de Feveriro/2007, o site ficará travado para a digitação de pedidos  de peças e acessórios apartir do dia 16/02 após ás 13h30 e retornará no dia 23/02 às 08 horas.
					Salientamos que os pedidos finalizados no dia 23/02,  serão enviados para a fábrica somente  na segunda-feira dia 26/02/2007.
					Agradecemos a compreensão.<br><br>
					Rúbia Fernandes<br>
					Faturamento de Peças</TD>";
			*/
			echo "</form>";
			echo "</TR>";
			echo "</table>";
			include "rodape.php";
			exit;
	}
}



// ALTERAÇÃO DE TABELA DE PREÇOS
if (1 == 1 and $login_posto <> 6359 ) {
	if (strval(strtotime(date("Y-m-d H:i:s")))  < strval(strtotime("2007-02-23 07:00:00"))) { // DATA DA VOLTA
		if (strval(strtotime(date("Y-m-d H:i:s")))  >= strval(strtotime("2007-02-16 12:31:00"))) { // DATA DO BLOQUEIO
			$title     = "Pedido de Peças Acessórios";
			$cabecalho = "Pedido de Peças Acessórios";
			$layout_menu = 'pedido';

			include "cabecalho.php";

			echo "<br><br>\n";
//			echo $login_posto;
			$sql2 = "SELECT * FROM tbl_posto_fabrica WHERE fabrica = 1 AND posto = $login_posto AND codigo_posto IN ('10068', '10086', '10097', '10120', '10177', '10240', '10345', '10358', '10378', '10678', '10844', '11147', '11228', '11290', '12004', '12008', '12009', '12010', '12011', '12012', '12012', '12016', '12017', '12019', '12027', '12030', '12058', '12059', '12115', '12120', '12124', '12129', '12138', '13008', '13014', '13031', '13035', '13053', '13072', '13074', '13076', '13077', '13088', '13109', '13114', '13128', '13136', '13150', '13155', '13161', '13201', '13270', '13515', '13516', '13632', '13635', '13695', '13715', '13786', '13812', '14048', '14049', '14055', '14135', '14162', '14166', '14187', '14189', '14228', '14246', '14426', '14675', '14975', '15007', '15024', '15026', '15031', '15034', '15036', '15037', '15046', '15047', '15097', '15111', '15113', '15199', '16001', '20034', '20165', '20223', '20274', '20285', '20312', '20322', '20333', '20336', '20403', '20439', '20621', '20653', '20670', '20763', '20920', '20998', '21061', '21139', '21150', '21163', '21215', '21217', '21272', '21292', '21350', '21362', '21436', '21464', '21661', '21815', '21870', '21914', '21957', '22002', '22010', '22086', '22116', '22426', '22439', '22457', '22612', '22624', '22625', '22626', '22632', '22689', '22720', '22941', '23019', '23098', '23111', '23135', '23150', '23155', '23158', '23160', '23163', '23163', '23193', '23194', '23195', '23215', '23225', '23227', '23241', '23284', '23290', '23292', '23293', '23331', '23340', '23345', '23350', '23354', '23355', '23358', '23359', '23360', '23361', '23364', '23368', '23369', '23373', '23510', '23511', '23513', '23642', '23915', '23921', '23925', '24142', '24212', '24327', '24367', '24394', '24567', '26934', '26942', '26943', '26946', '26948', '26949', '26954', '26955', '26956', '26957', '26958', '29067', '29078', '29079', '30003', '30048', '30052', '31057', '31351', '32007', '32022', '32027', '32057', '32058', '32074', '32085', '32855', '33009', '33018', '33027', '33029', '33034', '34022', '34087', '35021', '35053', '35066', '35143', '36033', '36039', '36086', '36107', '36307', '36778', '36888', '37040', '37043', '38021', '38034', '38036', '38039', '38774', '39075', '39512', '39600', '39730', '39870', '39874', '40031', '40077', '40092', '40113', '40305', '40399', '40563', '40979', '41086', '41272', '41665', '41683', '42076', '42124', '42308', '42362', '42375', '42376', '42408', '43380', '43743', '45007', '45127', '45889', '48838', '50061', '50087', '50139', '51092', '51097', '51167', '51168', '51199', '51734', '51735', '51738', '51771', '51774', '51782', '51787', '52006', '52021', '52028', '52043', '52047', '52051', '52055', '52200', '52208', '53129', '53130', '53155', '53175', '53463', '54006', '54020', '54025', '54029', '54032', '54037', '54038', '54049', '54050', '54089', '55036', '55122', '55142', '55143', '55153', '55155', '55159', '55170', '55200', '55241', '55270', '56016', '56089', '56153', '56176', '56198', '56267', '56305', '56368', '56463', '56875', '56876', '56977', '57215', '57432', '57582', '57719', '57873', '57874', '57972', '58022', '58036', '58142', '58219', '58329', '58558', '58774', '10121', '10341', '10454', '10665', '58414', '10674', '10843', '11399', '12137', '43745', '43244', '10698', '11529', '12015', '12031', '58876', '42450', '12045', '12124', '12127', '23154', '12129', '14017', '14300', '14717', '14975', '15022', '15035', '15040', '15045', '34069', '15048', '15101', '20072', '20331', '20341', '43144', '42300', '20370', '26966', '54062', '13513', '41689', '40142', '42307', '20398', '20417', '20741', '21149', '21302', '21351', '21480', '21551', '21565', '21972', '22007', '22497', '22530', '22585', '22588', '20490', '22627', '22631', '42385', '22670', '22710', '22893', '23156', '23167', '23183', '23184', '58589', '23413', '23189', '23197', '23283', '23298', '23316', '23371', '23372', '23374', '23380', '23381', '23382', '23387', '23388', '23440', '23554', '23980', '24348', '26950', '26953', '26955', '26960', '26961', '26962', '26963', '27907', '27908', '27909', '29060', '29062', '29083', '30002', '31021', '31129', '31141', '31290', '31310', '31395', '32008', '32024', '32029', '34135', '36788', '37583', '38038', '39545', '39600', '39692', '39791', '39874', '40007', '40049', '40052', '40053', '40082', '40092', '40097', '40143', '40444', '40598', '40885', '40907', '40921', '41066', '41176', '41221', '41280', '41309', '41472', '41694', '41979', '42031', '42125', '42270', '42280', '42297', '42404', '42409', '43027', '43051', '43201', '43779', '43818', '44003', '45010', '45126', '50004', '51782', '52198', '53440', '53441', '54024', '54056', '55026', '55049', '55146', '55874', '56047', '56097', '56146', '56376', '56480', '57299', '57602', '58108', '58423', '58879', '84481', '99065')";
			$res2 = pg_query ($con,$sql2);
			if (pg_num_rows ($res2) > 0 AND 1==2) {
				echo "<table width='500' border='0' align='center' cellpadding='5' cellspacing='2' style='border-collapse: collapse' bordercolor='000000'>";
				echo "<TR align='center' bgcolor='#336699'>";
				echo "	<TD colspan='3' height='40' style='font-family: verdana, arial ; font-size: 16px; font-size: 14px; color:#FFFFFF;'><B>COMUNICADOS BLACK & DECKER</B></TD>";
				echo "<TR align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000' bgcolor='#C1E0FF' onmouseover=\"this.bgColor='#3399FF'\" onmouseout=\"this.bgColor='#C1E0FF'\">";
				echo "	<TD>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<u>IMPLEMENTAÇÃO DA NOVA SISTEMÁTICA DE FATURAMENTO</u><BR><BR>



Conforme informado anteriormente, devido a implementação do novo sistema operacional de faturamento da Black & Decker, o site ficará bloqueado do dia 26/12 após às 17hs, retornarndo somente no dia 04/01/2007.<BR><BR>



Atenciosamente.<BR>

Faturamento de peças</TD>";
			echo "</TR>";
			echo "</table>";
			}else{
				echo "<table width='500' border='0' align='center' cellpadding='5' cellspacing='2' style='border-collapse: collapse' bordercolor='000000'>";
				echo "<TR align='center' bgcolor='#336699'>";
				echo "<form method='post' name='frm_condicao' action='$PHP_SELF'>";
				echo "	<TD colspan='3' height='40' style='font-family: verdana, arial ; font-size: 16px; font-size: 14px; color:#FFFFFF;'><B>COMUNICADO BLACK & DECKER</B></TD>";
				echo "<TR align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000' bgcolor='#C1E0FF' onmouseout=\"this.bgColor='#C1E0FF'\">";
				echo "<TD><p align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000'>$comentario</p></TD>";

/*				Devido  ao feriado de Carnaval e ao nosso fechamento mensal  de Feveriro/2007, o site ficará travado para a digitação de pedidos  de peças e acessórios apartir do dia 16/02 após ás 13h30 e retornará no dia 23/02 às 08 horas.
				Salientamos que os pedidos finalizados no dia 23/02,  serão enviados para a fábrica somente  na segunda-feira dia 26/02/2007.
				Agradecemos a compreensão.<br><br>
				Rúbia Fernandes<br>
				Faturamento de Peças</TD>";
*/
				echo "</b></h3>\n";
				echo "</td>\n";

				echo "</tr>\n";
				echo "</table>\n";
			}
			include "rodape.php";
			exit;
		}
	}
}

$sql = "SELECT  tbl_posto_fabrica.codigo_posto      ,
				tbl_posto_fabrica.tipo_posto        ,
				tbl_posto_fabrica.pedido_faturado   ,
				tbl_posto_fabrica.pedido_em_garantia,
				tbl_posto.cnpj,
				tbl_posto.ie,
				tbl_posto.nome,
				tbl_posto.estado
		FROM    tbl_posto_fabrica
		JOIN    tbl_posto USING(posto)
		WHERE   tbl_posto_fabrica.posto   = $login_posto
		AND     tbl_posto_fabrica.fabrica = $login_fabrica";
$res_posto = pg_query ($con,$sql);

if (pg_num_rows ($res_posto) == 0 OR strlen (trim (pg_last_error($con))) > 0 ) {
	header ("Location: index.php");
	exit;
}

$codigo_posto       = trim(pg_fetch_result ($res_posto,0,codigo_posto));
$tipo_posto         = trim(pg_fetch_result ($res_posto,0,tipo_posto));
$nome_posto         = trim(pg_fetch_result ($res_posto,0,nome));
$cnpj               = trim(pg_fetch_result ($res_posto,0,cnpj));
$ie                 = trim(pg_fetch_result ($res_posto,0,ie));
$estado             = trim(pg_fetch_result ($res_posto,0,estado));
$pedido_faturado    = trim(pg_fetch_result ($res_posto,0,pedido_faturado));
$pedido_em_garantia = trim(pg_fetch_result ($res_posto,0,pedido_em_garantia));

//if ($tipo_posto <> 31 and $tipo_posto <> 32 and $tipo_posto <> 33 and $tipo_posto <> 34 and $tipo_posto <> 38 and $tipo_posto <> 85 and $tipo_posto <> 86 and $tipo_posto <> 87 and $tipo_posto <> 39 and $tipo_posto <> 79 and $tipo_posto <> 80 and $tipo_posto <> 81) {
if ($pedido_faturado == 'f'){
	header ("Location: menu_pedido.php");
	exit;
}

if (!empty($_POST['btn_recalcula'])) {
    $pedido_recalcula = $_POST['pedido_recalcula'];
    $condicao_pagamento_trocar = $_POST['condicao_pagamento_trocar'];

    if (!empty($pedido_recalcula) and !empty($condicao_pagamento_trocar)) {
        $begin = pg_query($con, "BEGIN");

        $sql_pedido_cond = "UPDATE tbl_pedido SET condicao = $condicao_pagamento_trocar, seu_pedido = null WHERE pedido = $pedido_recalcula AND fabrica = $login_fabrica";
        $qry_pedido_cond = pg_query($con, $sql_pedido_cond);

        $sql_pedido_recalc = "SELECT fn_finaliza_pedido_blackedecker($pedido_recalcula, $login_fabrica)";
        $qry_pedido_recalc = pg_query($con, $sql_pedido_recalc);

        if (strlen(pg_last_error($con)) > 0) {
            $msg_erro = pg_last_error($con);
            $rollback = pg_query($con, "ROLLBACK");
        } else {
            $commit = pg_query($con, "COMMIT");
        }
    }
}

$cook_pedido_acessorio = "";
setcookie ("cook_pedido_acessorio");

$sql = "SELECT  tbl_pedido.pedido,
				case when tbl_pedido.pedido_blackedecker > 99999 then
					lpad((tbl_pedido.pedido_blackedecker - 100000)::text,5,'0')
				else
					lpad(tbl_pedido.pedido_blackedecker::text,5,'0')
				end AS pedido_blackedecker,
				tbl_pedido.condicao,
				tbl_pedido.representante,
				tbl_representante.codigo AS representante_codigo,
				tbl_representante.nome AS representante_nome,
				tbl_pedido.seu_pedido
		FROM    tbl_pedido
		JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.posto = tbl_pedido.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
		left join tbl_representante ON tbl_pedido.representante = tbl_representante.representante AND tbl_pedido.fabrica = $login_fabrica

		WHERE   tbl_pedido.exportado        is null
		AND     tbl_pedido.finalizado       is null
		AND     tbl_pedido.pedido_acessorio is true
		AND     tbl_pedido.posto   = $login_posto
		AND     tbl_pedido.admin   ISNULL
		AND     tbl_pedido.fabrica = $login_fabrica

		ORDER BY tbl_pedido.pedido DESC
		LIMIT 1;";

$res = pg_query ($con,$sql);

if (pg_num_rows($res) > 0) {
	$aux_cook_pedido_acessorio = trim(pg_fetch_result($res,0,pedido_blackedecker));
	$cook_pedido_acessorio     = trim(pg_fetch_result($res,0,pedido));
	$condicao                  = trim(pg_fetch_result($res,0,condicao));
	$seu_pedido                = trim(pg_fetch_result($res,0,seu_pedido));
	$representante_id 		   = trim(pg_fetch_result($res,0,representante));
	$representante_nome		   = trim(pg_fetch_result($res,0,representante_nome));
	$representante_codigo	   = trim(pg_fetch_result($res,0,representante_codigo));

	setcookie ("cook_pedido_acessorio",$cook_pedido_acessorio,time()+(3600*48));

	#HD 34403
	if (strlen($seu_pedido)>0){
		$aux_cook_pedido_acessorio = fnc_so_numeros($seu_pedido);
	}
}


if (strlen($cook_pedido_acessorio) > 0) {
	$sql = "SELECT  tbl_condicao.condicao,
					tbl_pedido.bloco_os
			FROM    tbl_pedido
			JOIN    tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
			WHERE   tbl_pedido.pedido = $cook_pedido_acessorio";
	$res = pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {
		$condicao = trim(pg_fetch_result($res,0,condicao));
		$bloco_os = trim(pg_fetch_result($res,0,bloco_os));;
	}
}


//HD 21009 - 27/5/2008
#----------------------- Deletar Item ------------------
if (strlen($_GET["delete"]) > 0) {
	$delete = trim($_GET["delete"]);
	$pedido = trim($_GET["pedido"]);

	$sql_verifica = "SELECT finalizado from tbl_pedido where tbl_pedido.pedido = $pedido";
	$res_verifica = pg_query($con,$sql);

	if (pg_num_rows($res_verifica)>0){

		$finalizado = pg_fetch_result($res_ve, 0, 0);

		if (!empty($finalizado)){
			$msg_erro = "Pedido já finalizado";
		}else{

			$res = pg_query($con,"BEGIN TRANSACTION");

			$sql = "UPDATE tbl_pedido_item set pedido = 0
					WHERE  tbl_pedido_item.pedido_item = $delete";
			$res = pg_query ($con,$sql);

			$sqlP = "SELECT pedido_item FROM tbl_pedido_item WHERE pedido = $pedido";
			$resP = pg_query ($con,$sqlP);
			if(pg_num_rows($resP)==0){
				$sql = "SELECT fnc_pedido_delete ($pedido, $login_fabrica, null)";
				$res = pg_query ($con,$sql);

				if (strlen ( pg_last_error ($con) ) > 0) {
					$msg_erro .= pg_last_error ($con);
				}else{
					$sql = "DELETE FROM tbl_pedido
					WHERE  tbl_pedido.pedido = $pedido";
					$res = pg_query ($con,$sql);
				}
			}

			if (strlen ( pg_last_error ($con) ) > 0) {
				$res = pg_query($con,"ROLLBACK TRANSACTION");
				$msg_erro = pg_last_error ($con) ;
			}else{
				$res = pg_query($con,"COMMIT TRANSACTION");
				header ("Location: $PHP_SELF");
				exit;
			}
		}
	}
}

#----------------------- Finalizar Pedido ------------------
if ($finalizar == 1) {

$res = pg_query($con, "BEGIN TRANSACTION");

	$retorno = DividePedidos($cook_pedido_acessorio);

	foreach ($retorno as $pedido) {
	
		$sql = "INSERT INTO tbl_pedido_alteracao (
					pedido
				)VALUES(
					$pedido
				)";
		$res = pg_query ($con,$sql);

		if (strlen ( pg_last_error ($con) ) > 0) {
			$msg_erro = pg_last_error ($con) ;
		}

		$sql = "SELECT fn_pedido_finaliza ($pedido,$login_fabrica)";
		$res = pg_query($con,$sql);
		if (strlen(pg_last_error($con)) > 0) {
			$msg_erro['erro'][] = pg_last_error($con) ;
		}
	
		$sql = "SELECT fn_pedido_suframa($pedido,$login_fabrica);";
		$res = pg_query($con,$sql);
		if (strlen(pg_last_error($con)) > 0) {
			$msg_erro['erro'][] = pg_last_error($con) ;
		}
	}
	

    $sqlBloqueio = "SELECT desbloqueio
        FROM tbl_posto_bloqueio
        WHERE posto = $login_posto and fabrica = $login_fabrica
        AND pedido_faturado
        ORDER BY data_input DESC LIMIT 1";
    $qryBloqueio = pg_query($con, $sqlBloqueio);

    if (pg_num_rows($qryBloqueio) > 0) {
        $bloqueio_pedido_faturado = pg_fetch_result($qryBloqueio, 0, 'desbloqueio');
        if ($bloqueio_pedido_faturado == 'f') {
                $condicao = 1905;
                $condicao_automatica = true;
        }
    }


    if(array_key_exists('erro', $retorno) or count($msg_erro['erro']) > 0 ){
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}else{
		$res = pg_query ($con,"COMMIT");
		if(!array_key_exists('erro', $retorno)){
			$qtdePedidos = count($retorno);
			$msg = $_GET['msg'];
			if($login_fabrica == 1){
				if($qtdePedidos > 1){
					header ("Location: pedido_finalizado_desmembrados.php?pedido=".implode(',', $retorno)."&msg=".$msg."&bloq=$bloqueio_pedido_faturado");
				}else{
					header ("Location: pedido_finalizado.php?pedido=$retorno[0]&msg=".$msg."&bloq=$bloqueio_pedido_faturado");
				}            
			}else{
				header ("Location: pedido_finalizado.php?pedido=$cook_pedido_acessorio&msg=".$msg);
			}
			exit;
		}	

	}


}

#--------------- Gravar Item ----------------------
if ($btngravar == "Gravar") {

    $condicao = trim($_POST['condicao']);

    $sqlBloqueio = "SELECT desbloqueio
        FROM tbl_posto_bloqueio
		WHERE posto = $login_posto and fabrica = $login_fabrica
		AND pedido_faturado
        ORDER BY data_input DESC LIMIT 1";
    $qryBloqueio = pg_query($con, $sqlBloqueio);

    if (pg_num_rows($qryBloqueio) > 0) {
        $bloqueio_pedido_faturado = pg_fetch_result($qryBloqueio, 0, 'desbloqueio');
        if ($bloqueio_pedido_faturado == 'f') {
                $condicao = 1905;
                $condicao_automatica = true;
        }
    }

    if($condicao_automatica == false){

        $condicoes = array();

        $sql_categoria_posto = "SELECT categoria FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
        $qry_categoria_posto = pg_query($con, $sql_categoria_posto);
        $categoria_posto = pg_fetch_result($qry_categoria_posto, 0, 'categoria');

        $join_posto_fabrica = "JOIN tbl_posto_fabrica ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto_condicao.tipo_posto ";

        if (empty($categoria_posto)) {
            $join_posto_fabrica .= "AND tbl_tipo_posto_condicao.categoria IS NULL";
        } else {
            $join_posto_fabrica .= "AND tbl_tipo_posto_condicao.categoria = '{$categoria_posto}'";
        }

        $sql_tipo_posto_condicao = "SELECT DISTINCT tbl_tipo_posto_condicao.condicao
            FROM tbl_tipo_posto_condicao
            JOIN tbl_condicao USING(condicao)
            $join_posto_fabrica
            WHERE tbl_condicao.fabrica    = $login_fabrica
            AND     tbl_condicao.visivel    IS TRUE
            AND     (
                        EXTRACT(DAY FROM CURRENT_DATE) BETWEEN tbl_condicao.dia_inicio AND tbl_condicao.dia_fim
                    OR  (
                        tbl_condicao.dia_inicio IS NULL
                        AND tbl_condicao.dia_fim IS NULL
                )
            )
            AND tbl_posto_fabrica.fabrica = $login_fabrica
            AND tbl_posto_fabrica.posto = $login_posto";
        $qry_tipo_posto_condicao = pg_query($con, $sql_tipo_posto_condicao);

        while ($fetch = pg_fetch_assoc($qry_tipo_posto_condicao)) {
            $condicoes[] = $fetch['condicao'];
        }

        $cond_condicoes = '';

        if (!empty($condicoes)) {
            $cond_condicoes = ' AND condicao IN (' . implode(', ', $condicoes) . ') ';
        }

        $sql = "SELECT  tbl_posto_condicao.condicao AS condicao, tbl_posto_condicao.posto 
                FROM    tbl_posto_condicao
                JOIN    tbl_posto    USING (posto)
                JOIN    tbl_tabela  ON tbl_tabela.tabela   = tbl_posto_condicao.tabela AND tbl_tabela.fabrica = $login_fabrica
                WHERE   tbl_posto_condicao.posto    = $login_posto
                AND     tbl_posto_condicao.condicao = $condicao
                UNION
                SELECT tbl_black_posto_condicao.id_condicao AS condicao, tbl_black_posto_condicao.posto
                FROM   tbl_black_posto_condicao
                JOIN   tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_black_posto_condicao.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                LEFT JOIN tbl_condicao   ON tbl_condicao.condicao   = tbl_black_posto_condicao.id_condicao
                WHERE tbl_black_posto_condicao.posto = $login_posto
				AND   tbl_condicao.condicao = $condicao";
			if($promocao == 't' or !empty($cond_condicoes)){
				$sql .= "
            UNION
				SELECT  tbl_condicao.condicao   condicao,
						$login_posto as posto
				FROM    tbl_condicao
				WHERE   tbl_condicao.fabrica    = $login_fabrica
				AND     tbl_condicao.visivel    IS TRUE";

            if ($promocao == 't') {
                $sql .= " AND     tbl_condicao.promocao   IS TRUE ";
            } else {
                $sql .= " AND     tbl_condicao.promocao   IS NOT TRUE ";
            }

            $sql .= "
                AND     (
                            to_char(CURRENT_DATE,'dd')::INTEGER BETWEEN tbl_condicao.dia_inicio AND tbl_condicao.dia_fim
                        OR  (
                                tbl_condicao.dia_inicio IS NULL
                            AND tbl_condicao.dia_fim    IS NULL
                            )
                        )
				";
			};

        $res = pg_query($con, $sql);
        if (pg_num_rows($res) == 0){
            $msg_erro .= "Condição de pagamento informada não encontrada.";
        }
    }
   
	if(!empty($_POST["representante"])){
		$representante_id = (int)$_POST["representante"];

		$campo_insert = ", representante ";
		$value_insert = ", {$representante_id}";

		$update = ", representante = {$representante} ";

	}


	
#	$bloco_os = intval(trim($_POST['bloco_os']));
	$bloco_os = 0;

	if (strlen($bloco_os) == 0) {
		$aux_bloco_os = 0;
	}else{
		if (is_int($bloco_os) == false) {
			$aux_bloco_os = 0;
		}else{
			$fnc          = pg_query($con,"SELECT fnc_so_numeros('$bloco_os')");
			$aux_bloco_os = pg_fetch_result ($fnc,0,0);
		}
	}

	if (strlen($msg_erro) == 0) {
		if (strlen ($condicao) == 0 AND strlen ($cook_pedido_acessorio)== 0) {
			$msg_erro = "Escolha a condição de pagamento";
		}
	}

	$sql = "SELECT tbl_tabela.tabela
			FROM   tbl_tabela
			WHERE  tbl_tabela.sigla_tabela = 'ACESS'";
	$res = pg_query ($con,$sql);
	if (pg_num_rows ($res) > 0) $tabela = pg_fetch_result ($res,0,0);

	if (strlen($msg_erro) == 0){
		$res = pg_query($con, "BEGIN TRANSACTION");

		if (strlen ($cook_pedido_acessorio) == 0) {
			$sql = "INSERT INTO tbl_pedido (
						posto          ,
						condicao       ,
						tabela         ,
						bloco_os       ,
						fabrica        ,
						tipo_pedido    ,
						pedido_acessorio
						$campo_insert
					)VALUES(
						$login_posto   ,
						'$condicao'    ,
						'$tabela'      ,
						$aux_bloco_os  ,
						$login_fabrica ,
						(SELECT tipo_pedido FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND UPPER(trim(descricao)) = 'ACESSORIOS'),
						't'
						$value_insert
					)";

			$res = pg_query ($con,$sql);

			if (strlen ( pg_last_error ($con) ) > 0) {
				$msg_erro = pg_last_error($con);
			}else{
				$res = pg_query ($con,"SELECT currval ('seq_pedido')");
				$cook_pedido_acessorio = pg_fetch_result($res,0,0);

				# cookie expira em 48 horas
				setcookie ("cook_pedido_acessorio",$cook_pedido_acessorio,time()+(3600*48));
			}
		}else{
			$sql = "UPDATE tbl_pedido SET
						tabela     = '$tabela'       ,
						condicao   = '$condicao'     ,
						bloco_os   = '$aux_bloco_os' ,
						finalizado = null
						$update
					WHERE tbl_pedido.pedido = $cook_pedido_acessorio;";
			$res = pg_query ($con,$sql);

			if (strlen ( pg_last_error ($con) ) > 0) {
				$msg_erro = pg_last_error ($con) ;
			}

			if (strlen (trim ($msg_erro)) == 0) {
				$sql = "INSERT INTO tbl_pedido_alteracao (
							pedido
						)VALUES(
							$cook_pedido_acessorio
						)";
				$res = pg_query ($con,$sql);

				if (strlen ( pg_last_error ($con) ) > 0) {
					$msg_erro = pg_last_error ($con) ;
				}
			}
		}

		if (strlen (trim ($msg_erro)) == 0) {
			for ($i = 0 ; $i < 15 ; $i++) {
				$referencia = trim($_POST["referencia" . $i]);
				$qtde       = trim($_POST["qtde"       . $i]);

				$xreferencia = str_replace(" ","",$referencia);
				$xreferencia = str_replace(".","",$xreferencia);
				$xreferencia = str_replace("-","",$xreferencia);
				$xreferencia = str_replace("/","",$xreferencia);
				$xreferencia = strtoupper($xreferencia);

				$sql = "SELECT tbl_peca.peca
						FROM   tbl_peca
						WHERE  tbl_peca.referencia_pesquisa = '$xreferencia'
						AND fabrica = $login_fabrica
						and tbl_peca.acessorio is true 
						AND tbl_peca.ativo";
				$resX = pg_query ($con,$sql);

				if (pg_num_rows($resX) > 0 AND strlen (trim ($qtde)) > 0) {
					$peca = pg_fetch_result($resX,0,0);
					//Chamado 2770
					$sql3 = "SELECT peca FROM tbl_pedido_item WHERE pedido = $cook_pedido_acessorio AND peca = $peca; ";
					$res3 = pg_query($con,$sql3);
					//echo $sql3;
					if(pg_num_rows($res3) > 0){
						$msg_erro = "Peça $referencia em destaque em duplicidade, favor retirar!";
					}

					if(strlen($msg_erro) == 0){
						$sql = "INSERT INTO tbl_pedido_item (
								pedido,
								peca  ,
								qtde
							)VALUES(
								$cook_pedido_acessorio,
								$peca                 ,
								$qtde
							)";
						$res = pg_query ($con,$sql);
						$msg_erro = pg_last_error($con);
					}

					if (strlen($msg_erro) == 0) {
						$res         = pg_query ($con,"SELECT CURRVAL ('seq_pedido_item')");
						$pedido_item = pg_fetch_result ($res,0,0);
						$msg_erro = pg_last_error($con);
					}

					if (strlen($msg_erro) == 0) {
						$sql = "SELECT fn_valida_pedido_item ($cook_pedido_acessorio,$peca,$login_fabrica)";
						$res = pg_query ($con,$sql);
						$msg_erro = pg_last_error($con);
					}

					if (strlen ($msg_erro) > 0) {
						$erro_linha  = "erro_linha" . $i;
						$$erro_linha = 1 ;
						break ;
					}
				}

				##### VERIFICA A QTDE DA PEÇA NO DO MÊS ANTERIOR #####
/*				if (strlen($msg_erro) == 0 && strlen($peca) > 0 && strlen($xreferencia) > 0) {
					$data_i = date("Y-m-d", mktime(0, 0, 0, date("m")-1, 1, date("Y")));
					$data_f = date("Y-m-t", mktime(0, 0, 0, date("m")-1, 1, date("Y")));
					$sql =	"SELECT SUM(tbl_pedido_item.qtde)
							FROM tbl_pedido_item
							JOIN tbl_pedido      ON tbl_pedido.pedido           = tbl_pedido_item.pedido
							JOIN tbl_tipo_pedido ON tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido
							JOIN tbl_peca        ON tbl_peca.peca               = tbl_pedido_item.peca
							WHERE tbl_pedido_item.peca = $peca
							AND   tbl_pedido.data BETWEEN '$data_i 00:00:00' AND '$data_f 23:59:59'
							AND   tbl_pedido.pedido NOT IN ($cook_pedido_acessorio)
							AND   UPPER(TRIM(tbl_tipo_pedido.descricao)) = 'ACESSORIOS';";
					$res = pg_query($con,$sql);
					if (pg_num_rows($res) > 0) {
						$qtde_total_mes = pg_fetch_result($res,0,0);
						if (strlen($qtde_total_mes) > 0 && $qtde > $qtde_total_mes) {
							$msg .= " A quantidade $qtde pedida para a peça $referencia está acima da média de $qtde_total_mes pedida normalmente. Você tem certeza que a quantidade pedida está correta? ";
						}
					}
				}*/
			}
		}

		if (strlen($msg_erro) == 0) {
			$sql = "SELECT fn_finaliza_pedido_blackedecker($cook_pedido_acessorio,$login_fabrica)";
			$res = pg_query ($con,$sql);
			$msg_erro = pg_last_error($con);
		}

		if (strlen($msg_erro) == 0) {
			$res = pg_query ($con,"COMMIT TRANSACTION");
			header ("Location: $PHP_SELF?msg=".$msg);
			exit;
		}else{
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
		}
	}
}

$title     = "Pedido de Peças";
$cabecalho = "Pedido de Peças";

$layout_menu = 'pedido';
include "cabecalho.php";
/*Entra so se for posto atendido pela black*/
$sql2 = "SELECT * FROM tbl_posto_fabrica WHERE fabrica = 1 AND posto = $login_posto AND codigo_posto IN ('10068', '10086', '10097', '10120', '10177', '10240', '10345', '10358', '10378', '10678', '10844', '11147', '11228', '11290', '12004', '12008', '12009', '12010', '12011', '12012', '12012', '12016', '12017', '12019', '12027', '12030', '12058', '12059', '12115', '12120', '12124', '12129', '12138', '13008', '13014', '13031', '13035', '13053', '13072', '13074', '13076', '13077', '13088', '13109', '13114', '13128', '13136', '13150', '13155', '13161', '13201', '13270', '13515', '13516', '13632', '13635', '13695', '13715', '13786', '13812', '14048', '14049', '14055', '14135', '14162', '14166', '14187', '14189', '14228', '14246', '14426', '14675', '14975', '15007', '15024', '15026', '15031', '15034', '15036', '15037', '15046', '15047', '15097', '15111', '15113', '15199', '16001', '20034', '20165', '20223', '20274', '20285', '20312', '20322', '20333', '20336', '20403', '20439', '20621', '20653', '20670', '20763', '20920', '20998', '21061', '21139', '21150', '21163', '21215', '21217', '21272', '21292', '21350', '21362', '21436', '21464', '21661', '21815', '21870', '21914', '21957', '22002', '22010', '22086', '22116', '22426', '22439', '22457', '22612', '22624', '22625', '22626', '22632', '22689', '22720', '22941', '23019', '23098', '23111', '23135', '23150', '23155', '23158', '23160', '23163', '23163', '23193', '23194', '23195', '23215', '23225', '23227', '23241', '23284', '23290', '23292', '23293', '23331', '23340', '23345', '23350', '23354', '23355', '23358', '23359', '23360', '23361', '23364', '23368', '23369', '23373', '23510', '23511', '23513', '23642', '23915', '23921', '23925', '24142', '24212', '24327', '24367', '24394', '24567', '26934', '26942', '26943', '26946', '26948', '26949', '26954', '26955', '26956', '26957', '26958', '29067', '29078', '29079', '30003', '30048', '30052', '31057', '31351', '32007', '32022', '32027', '32057', '32058', '32074', '32085', '32855', '33009', '33018', '33027', '33029', '33034', '34022', '34087', '35021', '35053', '35066', '35143', '36033', '36039', '36086', '36107', '36307', '36778', '36888', '37040', '37043', '38021', '38034', '38036', '38039', '38774', '39075', '39512', '39600', '39730', '39870', '39874', '40031', '40077', '40092', '40113', '40305', '40399', '40563', '40979', '41086', '41272', '41665', '41683', '42076', '42124', '42308', '42362', '42375', '42376', '42408', '43380', '43743', '45007', '45127', '45889', '48838', '50061', '50087', '50139', '51092', '51097', '51167', '51168', '51199', '51734', '51735', '51738', '51771', '51774', '51782', '51787', '52006', '52021', '52028', '52043', '52047', '52051', '52055', '52200', '52208', '53129', '53130', '53155', '53175', '53463', '54006', '54020', '54025', '54029', '54032', '54037', '54038', '54049', '54050', '54089', '55036', '55122', '55142', '55143', '55153', '55155', '55159', '55170', '55200', '55241', '55270', '56016', '56089', '56153', '56176', '56198', '56267', '56305', '56368', '56463', '56875', '56876', '56977', '57215', '57432', '57582', '57719', '57873', '57874', '57972', '58022', '58036', '58142', '58219', '58329', '58558', '58774', '10121', '10341', '10454', '10665', '58414', '10674', '10843', '11399', '12137', '43745', '43244', '10698', '11529', '12015', '12031', '58876', '42450', '12045', '12124', '12127', '23154', '12129', '14017', '14300', '14717', '14975', '15022', '15035', '15040', '15045', '34069', '15048', '15101', '20072', '20331', '20341', '43144', '42300', '20370', '26966', '54062', '13513', '41689', '40142', '42307', '20398', '20417', '20741', '21149', '21302', '21351', '21480', '21551', '21565', '21972', '22007', '22497', '22530', '22585', '22588', '20490', '22627', '22631', '42385', '22670', '22710', '22893', '23156', '23167', '23183', '23184', '58589', '23413', '23189', '23197', '23283', '23298', '23316', '23371', '23372', '23374', '23380', '23381', '23382', '23387', '23388', '23440', '23554', '23980', '24348', '26950', '26953', '26955', '26960', '26961', '26962', '26963', '27907', '27908', '27909', '29060', '29062', '29083', '30002', '31021', '31129', '31141', '31290', '31310', '31395', '32008', '32024', '32029', '34135', '36788', '37583', '38038', '39545', '39600', '39692', '39791', '39874', '40007', '40049', '40052', '40053', '40082', '40092', '40097', '40143', '40444', '40598', '40885', '40907', '40921', '41066', '41176', '41221', '41280', '41309', '41472', '41694', '41979', '42031', '42125', '42270', '42280', '42297', '42404', '42409', '43027', '43051', '43201', '43779', '43818', '44003', '45010', '45126', '50004', '51782', '52198', '53440', '53441', '54024', '54056', '55026', '55049', '55146', '55874', '56047', '56097', '56146', '56376', '56480', '57299', '57602', '58108', '58423', '58879', '84481', '99065', '01122')";
$res2 = pg_query ($con,$sql2);

if (pg_num_rows ($res2) > 0) {
$btn_condicao = $_POST['btn_condicao'];
	if ($btn_condicao == 'Confirmar') {
		$condicao = $_POST['condicao'];
/*takashi -  nao estava gravando id_condicao de pagamento - 11-01-07*/
		if($condicao == "30/60/90DD (financeiro de 3%)"){ $id_condicao = "55"; }
		if($condicao == "30/60DD (financeiro de 1,5%)"){  $id_condicao = "53"; }
		if($condicao == "30DD (sem financeiro)"){         $id_condicao = "51"; }
		if($condicao == "45DD (financeiro 1,5%)"){        $id_condicao = "52"; }
		if($condicao == "60/90/120DD (financeiro 6,1%)"){ $id_condicao = "57"; }
		if($condicao == "60/90DD (financeiro 4,5%)"){     $id_condicao = "73"; }
		if($condicao == "60DD (financeiro 3%)"){          $id_condicao = "54"; }
		if($condicao == "90DD (financeiro 6,1%)"){        $id_condicao = "56"; }
/*takashi -  nao estava gravando id_condicao de pagamento - 11-01-07*/

		$sql = "INSERT INTO tbl_black_posto_condicao (posto, condicao, id_condicao) VALUES ($login_posto, '$condicao', $id_condicao)";
			$resX = pg_query ($con,$sql);
#----------- Enviar email de Confirmação de Leitura -----------#
		$sql = "SELECT codigo_posto from tbl_posto_fabrica where fabrica=$login_fabrica and posto=$login_posto";
		$res = pg_query($con, $sql);
		$codigo_postoo = pg_fetch_result($res,0, codigo_posto);

		$remetente_email = "takashi@telecontrol.com.br";
		$assunto      = "Condição de Pagamento de pedido Telecontrol";
		$corpo        = "O Posto <B>$codigo_postoo</b> escolheu uma condição de pagamento <B>( $condicao )</B> do pedido TELECONTROL";

		$email_origem = "Telecontrol Assist <helpdesk@telecontrol.com.br>";

		$body_top = "--Message-Boundary\n";
		$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
		$body_top .= "Content-transfer-encoding: 7BIT\n";
		$body_top .= "Content-description: Mail message body\n\n";

		@mail($remetente_email, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " );
		/*envia email pra rubia*/
			echo "<script language='javascript'> location.href=\"$PHP_SELF\" ; </script>";
			exit;
	}
	$sql2 = "SELECT id_condicao
				FROM tbl_black_posto_condicao
				JOIN tbl_posto_fabrica on tbl_posto_fabrica.fabrica = $login_fabrica
				AND tbl_posto_fabrica.posto = tbl_black_posto_condicao.posto
			WHERE tbl_black_posto_condicao.posto = $login_posto
			AND tbl_posto_fabrica.pedido_faturado = 't'";
	$res2 = pg_query ($con,$sql2);
//if($ip=="200.232.184.76") echo "$sql2";
	if (pg_num_rows ($res2) == 0) {
		echo "<table width='500' border='0' align='center' cellpadding='5' cellspacing='2' style='border-collapse: collapse' bordercolor='000000'>";
		echo "<TR align='center' bgcolor='#336699'>";
		echo "<form method='post' name='frm_condicao' action='$PHP_SELF'>";
		echo "	<TD colspan='3' height='40' style='font-family: verdana, arial ; font-size: 16px; font-size: 14px; color:#FFFFFF;'><B>COMUNICADO BLACK & DECKER</B></TD>";
		echo "<TR align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000' bgcolor='#C1E0FF' onmouseover=\"this.bgColor='#3399FF'\" onmouseout=\"this.bgColor='#C1E0FF'\">";
		echo "	<TD>Para dar início a digitação de pedidos de peça,  por gentileza leia o comunicado abaixo relativo ao faturamento de peças.<BR><BR>
		Por exigência da Corporação a Black & Decker do Brasil estará mudando seu software operacional e essa alteração implicará em nossa sistemática de faturamento. No entanto, a única mudança que irá interferir para o posto de serviço é relativa às condições de pagamento dos pedidos. Explicando melhor, com o novo sistema, o posto de serviço poderá determinar sua condição de pagamento, porém, após essa escolha a condição será padronizada e fixa para todos seus pedidos sem opção de alterá-la.<BR><BR>
		IMPORTANTE: A condição escolhida agora será permanente e única para todos os pedidos.<BR><BR>Condições a escolher:<BR><BR>";

		echo "<select name='condicao' size='1'>";
		echo "<option value='30DD (sem financeiro)'        >30DD (sem financeiro)        </option>";
		echo "<option value='30/60DD (financeiro de 1,5%)' >30/60DD (financeiro de 1,5%) </option>";
		echo "<option value='30/60/90DD (financeiro de 3%)'>30/60/90DD (financeiro de 3%)</option>";
		echo "<option value='45DD (financeiro 1,5%)'       >45DD (financeiro 1,5%)       </option>";
		echo "<option value='60DD (financeiro 3%)'         >60DD (financeiro 3%)         </option>";
		echo "<option value='60/90DD (financeiro 4,5%)'    >60/90DD (financeiro 4,5%)    </option>";
		echo "<option value='60/90/120DD (financeiro 6,1%)'>60/90/120DD (financeiro 6,1%)</option>";
		echo "<option value='90DD (financeiro 6,1%)'       >90DD (financeiro 6,1%)       </option>";
		echo "</select><BR>";
		echo "Desde já agradecemos à compreensão.<BR><BR>
				Departamento de Assistência Técnica<BR><BR>
				Black & Decker do Brasil<BR>
	<center><input type='submit' name='btn_condicao' value='Confirmar'></center></TD>";
		echo "</form>";
		echo "</TR>";
		echo "</table>";
		include "rodape.php";
		exit;
	}
/*VERIFICA SE ELE JA ESCOLHEU A CONDICAO DE PAGAMENTO*/
}
?>

<!-- ---------------------- Inicio do HTML -------------------- -->
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">
<script src="plugins/shadowbox/shadowbox.js"	type="text/javascript"></script>
<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 0px solid;
	color:#ffffff;
	background-color: #596D9B
}
.table_line1 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}
table tr td{
	font-family: verdana;
	font-size: 11px;
}
</style>

<?
if ($alterar == 1) {
?>

<table width="400" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align="center" class="table_line1">
		<b>Antes de lançar Pedido ou OS´s, por favor, <a href='lista_representante.php'>clique aqui</a> <br>e complete seu CNPJ e Inscrição Estadual</b>
	</td>
</tr>
</table>

<?}else{?>


<script language="JavaScript">

$(document).ready(function(){ //hd_chamado=2543280
	$("input[name^=qtde]").blur(function(){

		var peca = $(this).parent("td").parent("tr").find("input[name^=referencia]").val();
		var descricao = $(this).parent("td").parent("tr").find("input[name^=descricao]").val();
		var qtde = $(this).val();
		var qtde_new = $(this);
		var qtde_peca_antiga = $(this).parent("td").parent("tr").find("input[name^=peca_qtde_antiga_]");

		$.ajax({ //hd_chamado=2543280
			url: "pedido_blackedecker_cadastro_acessorio.php",
			dataType: "GET",
			data: "valida_multiplo=sim&peca="+peca+"&qtde="+qtde,
			success: function(retorno){
				var resposta = retorno.responseText;
				resposta = retorno.split("|");
				if(resposta[0] == "ok"){
					qtde_peca_antiga.val(descricao+"|"+qtde+"|"+resposta[1]);
					qtde_new.val(resposta[1]);
					//setTimeout(fnc_calcula_total(linha),1000);
				}
			}
		});

		//setTimeout(fnc_calcula_total(linha),1000);
	});
});

$(function() {//hd_chamado=2543280
	Shadowbox.init({
		skipSetup	: true,
		enableKeys	: false,
		modal 		: true
	});
});


function validaMultiplo(){//hd_chamado=2543280

	var submit = false;
	$("input[name^='peca_qtde_antiga_']").each(function(){
		var valores = '';
		var qtde_antiga = $(this).val();
		if(qtde_antiga != '' && qtde_antiga != undefined){
			valores = qtde_antiga.split("|");
			if(valores[1] != valores[2]){
				submit = false;
				confirmaMultiplo();
				return;
			}else{
				submit = true;
			}
		}
	});
	if(submit == true){
		document.frmpedido.submit();
	}
}


function confirmaMultiplo(){ //hd_chamado=2543280
	var msg = "";
	var result = "";
	var td = "";
	$("input[name^='peca_qtde_antiga_']").each(function(){
		var msg_valor = $(this).val();
		if(msg_valor != ''){
			result = $(this).val();
			msg = result.split("|");
			if(msg[1] != msg[2]){
				td +='<tr height="20"><td>'+msg[0]+'</td><td>'+msg[1]+'</td><td>'+msg[2]+'</td></tr>';
			}
		}
	});

	Shadowbox.open({
		content:"<div style='background:#FFFFFF;height:100%;text-align:center;'>\
						<br><p style='font-size:14px;font-weight:bold'>Peças com Quantidade Multiplas</p>\
						<p style='font-weight:bold;'>\
						<table border='1' width='800' id='resultado' cellspacing='1' cellpadding='0' align='center'>\
						<tr height='20' class='menu_top'>\
							<td>Descrição</td><td>Qdte Digitada</td><td>Qtde Alterada</td>\
						</tr>\
							"+td+"\
						</table>\
							Deseja gravar o pedido com estas quantidades?\
							<input type='radio' name='confirma_pedido' value='t' checked> SIM\
							<input type='radio' name='confirma_pedido' value='f'> NÃO\
						</p>\
						<p>\
							<input type='button' value='Prosseguir' onclick=\"javascript:finalizarPedido();\">\
						</p>\
					</div>",
		player:	"html",
		title:	"Confirmar multiplo",
		width:	1000,
		height:	600,
		options: {onFinish: function(){
			$("#sb-nav-close").hide();
		},
				overlayColor:'#000000' }
	});
}
function finalizarPedido(){ //hd_chamado=2543280
	var confirmar = $("input[name=confirma_pedido]:checked").val();

	if(confirmar == "t"){
		document.frmpedido.submit();
	}else{
		Shadowbox.close();
		$("input[name=btngravar]").val("");
	}
}

function PesquisaPeca (peca_referencia, peca_descricao, peca_qtde) {
	var url = "";
	if (peca_referencia.value != "") {
		url = "peca_pesquisa_lista_blackedecker_acessorio.php?peca=" + peca_referencia.value;
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=501,height=300,top=100,left=100");
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.qtde			= peca_qtde;
		janela.focus();
	}
}

function fnc_fora_linha (nome, seq) {
	var url = "";
	if (nome != "") {
		url = "pesquisa_fora_linha.php?nome=" + nome + "&seq=" + seq;
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.focus();
	}
}

function fnc_pesquisa_representante(tipo) {
	var fabrica = <?php echo $login_fabrica ?>;
	var url = "";
	if (tipo == "codigo" ) {
		var xcampo = document.getElementById('codigo').value;
	}

	if (tipo == "nome" ) {
		var xcampo = document.getElementById('nome').value;
	}
		var campo  = document.getElementById('codigo');
		var campo2 = document.getElementById('nome');
		var campo3 = document.getElementById('representante');

	if (xcampo) {
		var url = "";
		url               = "pesquisa_representante.php?campo=" + xcampo + "&tipo=" + tipo + "&fabrica=" + fabrica ;
		janela            = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.codigo = campo;
		janela.nome  = campo2;
		janela.representante = campo3;
		janela.focus();
	}else{
		alert("Informe toda ou parte da informação para a pesquisa");
	}
}


<!-- Início
nextfield = "condpgto"; // coloque o nome do primeiro campo do form
netscape = "";
ver = navigator.appVersion; len = ver.length;
for(iln = 0; iln < len; iln++) if (ver.charAt(iln) == "(") break;
netscape = (ver.charAt(iln+1).toUpperCase() != "C");

function keyDown(DnEvents) {
	// ve quando e o netscape ou IE
	k = (netscape) ? DnEvents.which : window.event.keyCode;
	if (k == 13) { // preciona tecla enter
		if (nextfield == 'done') {
			return true; // envia quando termina os campos
		} else {
			// se existem mais campos vai para o proximo
			eval('document.frmpedido.' + nextfield + '.focus()');
			return false;
		}
	}
}

document.onkeydown = keyDown; // work together to analyze keystrokes
if (netscape) document.captureEvents(Event.KEYDOWN|Event.KEYUP);
// Fim -->

</script>

<? include "javascript_pesquisas.php" ?>

<? if ($tipo_posto == 4) { ?>

<!--
<table width="450" border="1" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td bgcolor="#FFCCCC" align='left' nowrap>
		<font face='arial' size='-1' color='#FF0000'><b>PROJETO DE UNIIFICAÇÃO DE PEDIDOS em vigor apartir de 27/09/2004</b>.</font>
	</td>
</tr>
<tr>
	<td bgcolor="#FFFFFF" align='center'>
		<font face='arial' size='-1' color='#000000'>Objetivando melhor atendê-lo, foi desenvolvido o <b>Projeto de Unificação de Pedidos</b>, que possui como principal objetivo eliminar problemas relativos ao controle de pendências, melhor controle no faturamento e embarque de pedidos.&nbsp;&nbsp;&nbsp;<? if ($posto <> "23513" and $posto <> "20741" and $posto <> "21957") { ?><a href='http://cebolinha.telecontrol.com.br/bd/xls/procedimento_distribuidor.doc' target='_blank'>Ler Procedimentos</a>&nbsp;&nbsp;&nbsp;<a href='http://cebolinha.telecontrol.com.br/bd/xls/calendario_2004.xls' target='_blank'>Ver Calendário Fiscal</a><? } ?></font>
	</td>
</tr>
</table>

<p><p>
-->

<? } ?>

<? if ($tipo_posto == 5) { ?>

<!--
<table width="450" border="1" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td bgcolor="#FFCCCC" align='left' nowrap>
		<font face='arial' size='-1' color='#FF0000'><b>PROJETO DE UNIIFICAÇÃO DE PEDIDOS EM VIGOR A PARTIR DE 27/09/2004</b>.</font>
	</td>
</tr>
<tr>
	<td bgcolor="#FFFFFF" align='center'>
		<font face='arial' size='-1' color='#000000'>Objetivando melhor atendê-lo, foi desenvolvido o <b>Projeto de Unificação de Pedidos</b>, que possui como principal objetivo eliminar problemas relativos ao controle de pendências, melhor controle no faturamento e embarque de pedidos.&nbsp;&nbsp;&nbsp;<a href='http://cebolinha.telecontrol.com.br/bd/xls/procedimento_vip.doc' target='_blank'>Ler Procedimentos</a></font>
	</td>
</tr>
</table>

<p><p>
-->

<? } ?>



<?
if (strlen($aux_cook_pedido_acessorio) > 0) {

	echo "<br>";
	echo "<div class='contentBlockMiddle' style='width: 600px'>";
	echo "<table border='0' cellpadding='0' cellspacing='0'>";
	echo "<tr>";
	echo "<td><img border='0' src='imagens/esclamachion1.gif'></td>";
	echo "<td align='center'><b>Para que o pedido $aux_cook_pedido_acessorio seja enviado para a fábrica, grave e finalize o pedido novamente, antes de sair da tela de digitação de pedidos.</b></td>";
	echo "</tr>";
	echo "</table>";
	echo "</div>";
}

if (strlen($msg) > 0) $msg_erro = $msg;

if (strlen ($msg_erro) > 0 or count($msg_erro['erro']) > 0) {
	if (strpos ($msg_erro,"Fail to add null value in not null attribute peca") > 0)
		$msg_erro = "Peça não existe";
?>
<br>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
<tr>
	<td valign="middle" align="center" style='background-color:red; color:black'>
		<? echo is_array($msg_erro) ? $msg_erro['erro'][0] : $msg_erro ; ?>
	</td>
</tr>
</table>
<p>
<? } ?>

<form name="frmpedido" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="linha" value="<?echo $linha?>">

<?
//Chamado: 1757
$fiscal = fopen("bloqueio_pedidos/periodo_fiscal.txt", "r");
$ler_fiscal = fread($fiscal, filesize("bloqueio_pedidos/periodo_fiscal.txt"));
fclose($fiscal);

if($categoria == 'mega projeto' OR $categoria == 'Compra Peca'){ ?>

	<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
	<tr>
		<td valign="middle" align="center" style='background-color:red; color:white; font-size: 18px; font-weight: bold;'>
			Sem permissão de acesso
		</td>
	</tr>
	</table>
	<?php exit;
} ?>



<table width="500" border="0" cellpadding="2" cellspacing="1" align="center">
<tr>
	<td colspan='2' align='center'><font face='verdana' size='2' color='#0000ff'><b>Data limite para colocação de pedidos neste mês:<br><font color='#ff0000'><? echo $ler_fiscal; ?></font></font></td>
</tr>
</table>
<p>
<table width="500" border="0" cellpadding="2" cellspacing="1" align="center">
<tr>
	<td class='menu_top' colspan='2'>INFORMAÇÕES IMPORTANTES</td>
</tr>
<tr class='table_line1' bgcolor='#F1F4FA'>
	<td>*** PROCEDIMENTO PARA PEDIDOS DE ACESSÓRIOS</td>
	<td align='center'><b><a href='http://www.blackdecker.com.br/comunicado_visualiza.php?comunicado=72' target='_blank'>Clique aqui</a></b></td>
</tr>
<tr class='table_line1' bgcolor='#F1F4FA'>
	<td>*** TABELA DE ACESSÓRIOS</td>
	<td align='center'><b><a href='xls/acessorios_bd.xls' target='_blank'>Clique aqui</a></b></td>
</tr>
</table>

<br>

<? //hd 50052 59207
/*<table width='500' border='0' align='center' cellpadding='5' cellspacing='2' style='border-collapse: collapse' bordercolor='000000'>
<TR align='center' bgcolor='#336699'>
<TD colspan='3' height='40' style='font-family: verdana, arial ; font-size: 16px; font-size: 14px; color:#FFFFFF;'><B>COMUNICADO</B></TD>
<TR align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000' bgcolor='#C1E0FF' onmouseout=\"this.bgColor='#C1E0FF'\">
<TD><p align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000'>
	<CENTER><B><U>"ATENÇÃO:  INVENTÁRIO 2008</U></B></CENTER>
	<BR><BR>
	Devido ao nosso inventário anual, informamos que o fechamento correspondente ao <B>mês de DEZEMBRO/2008 será no dia 12/12/08.</B> Receberemos pedidos de peças e acessórios somente até esta data, dentro do horário padrão às 11h45.
	<BR><BR>
	Retornaremos com o faturamento somente dia 05/01/2009. Desta forma, de 12/12/08 (após as 11h45) até 05/01/09, não disponibilizaremos o site para a digitação de pedidos.
	<BR><BR>
	Neste período a Black & Decker não receberá pedidos e não emitirá nota fiscal, ou seja, não haverá nenhuma movimentação no estoque, por se tratar do nosso período de inventário na fábrica. Por isso é de suma importância  que seja feita uma programação de peças com base neste período que a fábrica ficará sem atender, principalmente para as peças de maior giro, pois não podemos abrir exceções.
	<BR><BR>
	Qualquer dúvida, por gentileza entre em contato com Rúbia pelo telefone 34-3318-3024 ou com o suporte da sua região.
	<BR><BR>
	<U>Obs: DISTRIBUIDORES, FAVOR INFORMAR OS SEUS CLIENTES.</U>
	<BR><BR>
	Obrigada
	<BR><BR>
	Rúbia Lane Fernandes<BR>
	Analista de Faturamento<BR>
	Black & Decker do Brasil"<BR>
</p></TD>
</TR>
</table>
<br><br>*/
?>

<table width="500" border="0" cellpadding="2" cellspacing="1" align="center">
<tr>
	<td class='menu_top' colspan='2'>CATÁLOGO DOS PRODUTOS</td>
</tr>
<tr class='table_line1' bgcolor='#F1F4FA'>
	<td align='center'><a href='http://www.dewalt.com.br/products/Aces/listTipoCate.asp' target='_blank'>DEWALT</a></td>
	<td align='center'><a href='http://www.bdhobby.com.br/prod/aces/ListTipoCate.asp' target='_blank'>HOBBY</a></td>
</tr>
</table>

<br>

<table width="500" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align='left' class="menu_top">
		<b>Condição de Pagamento</b>
	</td>
</tr>
<tr>
	<td align='center' nowrap class="table_line1">

<? if(1==1) { ?>

<select name="condicao" class="frm" onFocus="nextfield ='bloco_os'">
		<option value=''></option>
<?

        $condicoes = array();

        $sql_categoria_posto = "SELECT categoria FROM tbl_posto_fabrica WHERE posto = $login_posto AND fabrica = $login_fabrica";
        $qry_categoria_posto = pg_query($con, $sql_categoria_posto);
        $categoria_posto = pg_fetch_result($qry_categoria_posto, 0, 'categoria');

        $join_posto_fabrica = "JOIN tbl_posto_fabrica ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto_condicao.tipo_posto ";

        if (empty($categoria_posto)) {
            $join_posto_fabrica .= "AND tbl_tipo_posto_condicao.categoria IS NULL";
        } else {
            $join_posto_fabrica .= "AND tbl_tipo_posto_condicao.categoria = '{$categoria_posto}'";
        }

        $sql_tipo_posto_condicao = "SELECT DISTINCT tbl_tipo_posto_condicao.condicao
            FROM tbl_tipo_posto_condicao
            JOIN tbl_condicao USING(condicao)
            $join_posto_fabrica
            WHERE tbl_condicao.fabrica    = $login_fabrica
            AND     tbl_condicao.visivel    IS TRUE
            AND     (
                        EXTRACT(DAY FROM CURRENT_DATE) BETWEEN tbl_condicao.dia_inicio AND tbl_condicao.dia_fim
                    OR  (
                        tbl_condicao.dia_inicio IS NULL
                        AND tbl_condicao.dia_fim IS NULL
                )
            )
            AND tbl_posto_fabrica.fabrica = $login_fabrica
            AND tbl_posto_fabrica.posto = $login_posto";
        $qry_tipo_posto_condicao = pg_query($con, $sql_tipo_posto_condicao);

        while ($fetch = pg_fetch_assoc($qry_tipo_posto_condicao)) {
            $condicoes[] = $fetch['condicao'];
        }

        $cond_condicoes = '';

        if (!empty($condicoes)) {
            $cond_condicoes = ' AND condicao IN (' . implode(', ', $condicoes) . ') ';
        }

        $sql = "SELECT	tbl_black_posto_condicao.id_condicao,
                        tbl_black_posto_condicao.condicao
                FROM    tbl_black_posto_condicao
                JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto     = tbl_black_posto_condicao.posto
                                            AND tbl_posto_fabrica.fabrica   = $login_fabrica
                JOIN    tbl_condicao        ON  tbl_condicao.condicao       = tbl_black_posto_condicao.id_condicao
                                            AND tbl_condicao.fabrica        = $login_fabrica
                                            AND tbl_condicao.visivel        IS TRUE
                                            $cond_manual
                WHERE   tbl_black_posto_condicao.posto      = $login_posto
                AND     (
                            to_char(CURRENT_DATE,'dd')::INTEGER BETWEEN tbl_condicao.dia_inicio AND tbl_condicao.dia_fim
                        OR  (
                                tbl_condicao.dia_inicio IS NULL
                            AND tbl_condicao.dia_fim    IS NULL
                            )
                        )
        ";
			if($promocao == 'f'){
				$sql .="
                AND     tbl_condicao.promocao               IS NOT TRUE ";
			}else{
				$sql .= "
                AND     tbl_posto_fabrica.pedido_faturado   IS TRUE ";
			}
			$sql .= "
                AND     tbl_posto_fabrica.fabrica           = $login_fabrica ";
			if($promocao == 't' or !empty($cond_condicoes)){
				$sql .= "
            UNION
                SELECT  tbl_condicao.condicao   AS id_condicao,
                        tbl_condicao.descricao  AS condicao
				FROM    tbl_condicao
				WHERE   tbl_condicao.fabrica    = $login_fabrica
				AND     tbl_condicao.visivel    IS TRUE";

                if ($promocao == 't') {
                    $sql .= " AND     tbl_condicao.promocao   IS TRUE ";
                } else {
                    $sql .= " AND     tbl_condicao.promocao   IS NOT TRUE ";
                }

				$sql .= "
                AND     (
                            to_char(CURRENT_DATE,'dd')::INTEGER BETWEEN tbl_condicao.dia_inicio AND tbl_condicao.dia_fim
                        OR  (
                                tbl_condicao.dia_inicio IS NULL
                            AND tbl_condicao.dia_fim    IS NULL
                            )
                        )
                $cond_manual
                $cond_condicoes
				";
			}

			$res = pg_query ($con,$sql);
			$condicoes_pagamento = array();

			if (pg_num_rows($res) > 0) {
				for ($x=0; $x < pg_num_rows($res); $x++) {
                    $condicoes_pagamento[pg_fetch_result($res, $x, 'id_condicao')] = pg_fetch_result($res, $x, 'condicao');
					echo "<option "; if ($condicao == pg_fetch_result($res,$x,id_condicao)) echo " SELECTED "; echo " value='" . pg_fetch_result($res,$x,id_condicao) . "'>" . pg_fetch_result($res,$x,condicao) . "</option>\n";
				}
			}
			else {
				echo "<option value='51' ";
				if($condicao==51){
					echo " SELECTED ";
				}
				echo ">30DD (sem financeiro)</option>";
			}
?>
		</select>
<? } ?>
		<br>
		<font face='arial' size='-2' color='#336633'><b>Favor escolher a condição de pagamento</b></font>
	</td>
</tr>
</table>
<br>
<table width="500" border="0" cellpadding="2" cellspacing="2" align="center">
	<tr>
		<td align='left' colspan="4" class="menu_top">
			<b>Representante</b>
		</td>
	</tr>
	<tr>
		<td align="right" width='25%'></td>
		<td align="center" width='25%'>Código</td>
		<td align="center" width='25%'>Representação</td>
		<td align="left" width='25%'></td>
	</tr>
	<TD align="right" colspan="2" width='50%'>
			<INPUT TYPE="text" class="frm" name="codigo" id="codigo" size="10" value="<?php echo $representante_codigo?>">
			<IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="fnc_pesquisa_representante ( 'codigo')">
	</TD>
	<TD class="table_line"  colspan="2" align="left"  width='50%'>
		<INPUT TYPE="text" class="frm" name="nome" id="nome" size="15" value='<?php echo $representante_nome ?>'>
		<INPUT TYPE="hidden" class="frm" name="representante" id="representante" value='<?php echo $representante_id?>'>
		<IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="fnc_pesquisa_representante ( 'nome')">
	</TD>
</tr>
<?
/* Comentado a pedido da Fábrica, talvez posteriormente essa mensagem poderá voltar (NÃO EXCLUIR) HD-2570722
 * $mensagem = " <a href='lista_representante.php?todos=ok' target="_blank">CLIQUE AQUI</a> para visualizar a tabela.";
*/ ?>
<tr>
	<td colspan='100%' align='center'><font face='verdana' size='2' color='#000000'><b>Caso o representante da sua região realize visitas em seu posto autorizado, pedimos que nos informe o código antes de finalizar os pedidos de acessórios.</font></td>
</tr>


</table>
<br>
<table width="500" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td width="35%" align='center' class="menu_top">
		<b>Referência</b>
	</td>
	<td width="50%" align='center' class="menu_top">
		<b>Descrição</b>
	</td>
	<td width="15%" align='center' class="menu_top">
		<b>Quantidade</b>
	</td>
</tr>


<?
for ($i = 0 ; $i < 15 ; $i ++) {
	$referencia = "referencia" . $i;
	$descricao  = "descricao" . $i;
	$qtde       = "qtde" . $i;
	$erro_linha = "erro_linha" . $i;

	$referencia = $$referencia;
	$descricao  = $$descricao;
	$qtde       = $$qtde;
	$erro_linha = $$erro_linha;

	$prox = $i + 1;
	$done = 14;

	$cor_erro = "#ffffff";
	if ($erro_linha == 1) $cor_erro = "#AA6666";

?>

<tr bgcolor="<?echo $cor_erro?>">
	<input type='hidden' name='peca_qtde_antiga_<? echo $i ?>' value=''> <!--hd_chamado=2543280-->
	<td align='center'>
		<input type="text" name="referencia<? echo $i ?>" size="15" maxlength="15" value="<? echo $referencia ?>" class="textbox" onFocus="nextfield ='qtde<?echo $i?>'" onblur="PesquisaPeca(window.document.frmpedido.referencia<? echo $i ?>,window.document.frmpedido.descricao<? echo $i ?>,window.document.frmpedido.qtde<? echo $i ?>);">
		<img src='imagens/btn_buscar5.gif' style="cursor:pointer" alt="Clique para pesquisar por referência do componente" border='0' hspace='5' align='absmiddle' onclick="PesquisaPeca(window.document.frmpedido.referencia<? echo $i ?>,window.document.frmpedido.descricao<? echo $i ?>,window.document.frmpedido.qtde<? echo $i ?>);">
	</td>
	<td align='center'>
		<input type="text" name="descricao<? echo $i ?>" size="30" maxlength="30" value="<? echo $descricao ?>" class="textbox" onFocus="nextfield ='qtde<?echo $i?>'">
	</td>
	<td align='center'>
		<input type="text" name="qtde<? echo $i ?>" size="4" maxlength="4" value="<? echo $qtde ?>" class="textbox" <? if ($prox <= $done) { echo "onFocus=\"nextfield ='referencia$prox'\""; }else{ echo "onFocus=\"nextfield ='done'\"";}?>>
	</td>
</tr>

<? } ?>

<tr>
	<td align='center' colspan='2'>
		<br>
		<input type="hidden" name="btngravar" value="x">
		<a href="javascript: document.frmpedido.btngravar.value='Gravar' ; validaMultiplo() ; "><img src='imagens/btn_gravar.gif' border='0'></a>

	</td>
</tr>
</table>
<br>

<table width="700" border="0" cellpadding="5" cellspacing="2" align="center">
<tr>
	<td align='center' bgcolor='#f4f4f4'>
		<p align='justify'><font size='1'><b>PARA CONTINUAR A DIGITAR ITENS NESTE PEDIDO, BASTA GRAVAR E EM SEGUIDA CONTINUAR DIGITANDO.</b></font></p>
	</td>
</tr>
<tr>
	<td align='center' bgcolor='#f4f4f4'>
		<p align='justify'><font size='1' color='#FF0000'><b>AVISO: APÓS GRAVAR O SEU PEDIDO, IRÁ APARECER O RESUMO DOS ITENS LANÇADOS E ABAIXO DESTE RESUMO, TERÁ O BOTÃO DE FINALIZAÇÃO QUE SOMENTE SERÁ USADO QUANDO NÃO EXISTIREM MAIS ITENS A SEREM LANÇADOS NESTE PEDIDO.</b></font></p>
	</td>
</tr>

</form>
</table>


<?
if (strlen ($aux_cook_pedido_acessorio) > 0) {
?>
<br>
<form name="pedido_recalcula" method="POST">
    <table width="700" border="0" cellpadding="3" cellspacing="1" align="center">
        <tr class="table_line1" bgcolor="#F1F4FA">
            <td align="center">
                Trocar Condição de Pagamento
                <select name="condicao_pagamento_trocar">
                    <option value=""></option>
                    <?php
                    foreach ($condicoes_pagamento as $idx => $val) {
                        echo '<option value="' . $idx . '"';

                        if ($condicao == $idx) {
                            echo ' selected="selected"';
                        }

                        echo '>' . $val . '</option>';
                    }
                    ?>
                </select>
                <input type="hidden" name="pedido_recalcula" value="<?= $cook_pedido_acessorio ?>">
                <input type="submit" name="btn_recalcula" value="Recalcular">
            </td>
        </tr>
    </table>
</form>

<table width="700" border="0" cellpadding="3" cellspacing="1" align="center">
<tr>
	<td colspan="4" align="center" class='menu_top'>
		<font face="arial" color="#ffffff" size="+2"><b>Resumo do Pedido</b></font>
	</td>
</tr>

<tr>
	<td width="25%" align='center' class="menu_top">
		<b>Referência</b>
	</td>
	<td width="50%" align='center' class="menu_top">
		<b>Descrição</b>
	</td>
	<td width="15%" align='center' class="menu_top">
		<b>Quantidade</b>
	</td>
	<td width="10%" align='center' class="menu_top">
		<b>Preço</b>
	</td>
</tr>

<?
//HD 11606 paulo
	$sql = "SELECT a.oid    ,
					a.*      ,
					referencia,
					descricao
			FROM	tbl_peca
			JOIN	(
						SELECT oid,*
						FROM tbl_pedido_item
						WHERE pedido = $cook_pedido_acessorio
					)
					a ON tbl_peca.peca = a.peca
					ORDER BY a.pedido_item";
	$res = pg_query ($con,$sql);
	$total = 0;
	for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {

		$cor = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";



		echo "<tr bgcolor='$cor'>";

		echo "<td width='25%' align='left' class='table_line1' nowrap>";

		echo "<a href='$PHP_SELF?delete=" . pg_fetch_result ($res,$i,pedido_item) . "&pedido=$cook_pedido_acessorio'>";

		echo "<img src='imagens/btn_excluir.gif' align='absmiddle' hspace='5' border='0'>";
		echo "</a>";
		echo pg_fetch_result ($res,$i,referencia);
		echo "</td>";

		echo "<td width='50%' align='left' class='table_line1'>";
		echo pg_fetch_result ($res,$i,descricao);
		echo "</td>";

		echo "<td width='15%' align='center' class='table_line1'>";
		echo pg_fetch_result ($res,$i,qtde);
		echo "</td>";

		echo "<td width='10%' align='right' class='table_line1'>";
		echo number_format (pg_fetch_result ($res,$i,preco),2,",",".");
		echo "</td>";

		echo "</tr>";

		$total = $total + (pg_fetch_result ($res,$i,preco) * pg_fetch_result ($res,$i,qtde));
	}
?>

<tr>
	<td align="center" colspan="3" class="menu_top">
		<b>T O T A L</b>
	</td>
	<td align='right' class="menu_top" style='text-align:right'>
		<b>
		<? echo number_format ($total,2,",",".") ?>
		</b>
	</td>
</tr>
</table>



<!-- ============================ Botoes de Acao ========================= -->


<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align='center'>
		<br><a href="<?echo $PHP_SELF?>?finalizar=1&linha=<?echo $linha?>&unificar=t"><img src='imagens/btn_finalizar.gif' border='0'></a><br><br>
	</td>
</tr>
<tr>
	<td align='center' bgcolor='#f4f4f4'>
		<p align='justify'><font size='1'><b>CASO JÁ TENHA TERMINADO DE DIGITAR OS ITENS E QUEIRA PASSAR PARA A PRÓXIMA TELA, CLIQUE EM FINALIZAR ACIMA.</b></font></p>
	</td>
</tr>
</table>

<?
}
?>


<? } # Final do IF do CNPJ e IE ?>

<p>

<?include "rodape.php";?>
