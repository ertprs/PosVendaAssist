<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include "funcoes.php";

$sedex = $_GET['sedex'];

if (strlen ($sedex) > 0 AND $login_admin="232") {
	$sql = "UPDATE tbl_pedido SET
			pedido_sedex = 't',
			admin = 232       ,
			WHERE pedido=$sedex";
	$transforma =  pg_exec ($con,$sql);

	if ($transforma) {
		Header("Location: $PHP_SELF");
	}
}


if (strlen(trim($_GET["msg"])) > 0) $msg = trim($_GET["msg"]);

$abrir = fopen("/www/assist/www/bloqueia_pedido_black.txt", "r");
$ler = fread($abrir, filesize("/www/assist/www/bloqueia_pedido_black.txt"));
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
$abrir = fopen("/www/assist/www/libera_promocao_black.txt", "r");
$ler = fread($abrir, filesize("/www/assist/www/libera_promocao_black.txt"));
fclose($abrir);

$conteudo_p = explode(";;", $ler);

$data_inicio_p = $conteudo_p[0];
$data_fim_p    = $conteudo_p[1];
$comentario_p  = $conteudo_p[2];
$promocao = "f";

if (strval(strtotime(date("Y-m-d H:i:s")))  < strval(strtotime("$data_fim_p"))) { // DATA DA VOLTA
	if (strval(strtotime(date("Y-m-d H:i:s")))  >= strval(strtotime("$data_inicio_p"))) { // DATA DO BLOQUEIO
		$promocao = "t";
	}
}
?>
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


.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: justify;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial" !important;
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.espaco{
	padding: 0 0 0 140px
}

table.tabela tr td{
	
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}




</style>

<?php
if (strval(strtotime(date("Y-m-d H:i:s")))  < strval(strtotime("$data_fim"))) { // DATA DA VOLTA
	if (strval(strtotime(date("Y-m-d H:i:s")))  >= strval(strtotime("$data_inicio"))) { // DATA DO BLOQUEIO
		$title     = "Pedido de Peças";
		$cabecalho = "Pedido de Peças";
		$layout_menu = 'pedido';
		
		include "cabecalho.php";

//			echo "$ler<br><br>";

		echo "<br>\n";
		echo "<form method='post' name='frm_condicao' action='$PHP_SELF'>";

		echo "<table width='700' border='0' align='center' cellpadding='5' cellspacing='0' class='tabela'>";
		echo "<tr class='titulo_tabela' >";
		echo "	<td align='center'>COMUNICADO BLACK & DECKER</TD>";
		echo "</tr>";
		echo "<TR align='justify'>";
		echo "<TD><p align='justify' >$comentario</p></TD>";

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
			$res2 = pg_exec ($con,$sql2);
			if (pg_numrows ($res2) > 0 AND 1==2) {
				echo "<table width='700' border='0' align='center' cellpadding='5' cellspacing='2' class='tabela'>";
				echo "<TR>";
				echo "	<TD colspan='3' height='40' class='titulo_tabela'>COMUNICADOS BLACK & DECKER</TD>";
				echo "</TR>";
				echo "<TR align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000' bgcolor='#C1E0FF' onmouseover=\"this.bgColor='#3399FF'\" onmouseout=\"this.bgColor='#C1E0FF'\">";
				echo "	<TD>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<u>IMPLEMENTAÇÃO DA NOVA SISTEMÁTICA DE FATURAMENTO</u><BR><BR>

 

Conforme informado anteriormente, devido a implementação do novo sistema operacional de faturamento da Black & Decker, o site ficará bloqueado do dia 26/12 após às 17hs, retornarndo somente no dia 04/01/2007.<BR><BR>

 

Atenciosamente.<BR>

Faturamento de peças</TD>";
			echo "</TR>";
			echo "</table>";
			}else{
				echo "<table width='700' border='0' align='center' cellpadding='1' cellspacing='2' class='titulo_tabela'>";
				echo "<TR align='center'>";
				echo "<form method='post' name='frm_condicao' action='$PHP_SELF'>";
				echo "	<TD colspan='3' height='40' class='titulo_tabela'>COMUNICADO BLACK & DECKER</B></TD>";
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
$res_posto = @pg_exec ($con,$sql);

if (@pg_numrows ($res_posto) == 0 OR strlen (trim (pg_errormessage($con))) > 0 ) {
	header ("Location: index.php");
	exit;
}

$codigo_posto       = trim(pg_result ($res_posto,0,codigo_posto));
$tipo_posto         = trim(pg_result ($res_posto,0,tipo_posto));
$nome_posto         = trim(pg_result ($res_posto,0,nome));
$cnpj               = trim(pg_result ($res_posto,0,cnpj));
$ie                 = trim(pg_result ($res_posto,0,ie));
$estado             = trim(pg_result ($res_posto,0,estado));
$pedido_faturado    = trim(pg_result ($res_posto,0,pedido_faturado));
$pedido_em_garantia = trim(pg_result ($res_posto,0,pedido_em_garantia));

//if ($tipo_posto <> 31 and $tipo_posto <> 32 and $tipo_posto <> 33 and $tipo_posto <> 34 and $tipo_posto <> 38 and $tipo_posto <> 85 and $tipo_posto <> 86 and $tipo_posto <> 87 and $tipo_posto <> 39 and $tipo_posto <> 79 and $tipo_posto <> 80 and $tipo_posto <> 81) {
if ($pedido_faturado == 'f'){
	header ("Location: menu_pedido.php");
	exit;
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
				tbl_pedido.seu_pedido
		FROM    tbl_pedido
		JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.posto = tbl_pedido.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
		WHERE   tbl_pedido.exportado        isnull
		AND     tbl_pedido.finalizado       isnull
		AND     tbl_pedido.pedido_acessorio is true
		AND     tbl_pedido.posto   = $login_posto
		AND     tbl_pedido.admin   ISNULL
		AND     tbl_pedido.fabrica = $login_fabrica
		ORDER BY tbl_pedido.pedido DESC
		LIMIT 1;";
$res = @pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$aux_cook_pedido_acessorio = trim(pg_result($res,0,pedido_blackedecker));
	$cook_pedido_acessorio     = trim(pg_result($res,0,pedido));
	$condicao                  = trim(pg_result($res,0,condicao));
	$seu_pedido                = trim(pg_result($res,0,seu_pedido));

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
	$res = @pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$condicao = trim(pg_result($res,0,condicao));
		$bloco_os = trim(pg_result($res,0,bloco_os));;
	}
}


//HD 21009 - 27/5/2008
#----------------------- Deletar Item ------------------
if (strlen($_GET["delete"]) > 0) {
	$delete = trim($_GET["delete"]);
	$pedido = trim($_GET["pedido"]);

	$sql = "DELETE FROM tbl_pedido_item
			WHERE  tbl_pedido_item.pedido_item = $delete";
	$res = @pg_exec ($con,$sql);

	$sqlP = "SELECT pedido_item FROM tbl_pedido_item WHERE pedido = $pedido";
	$resP = @pg_exec ($con,$sqlP);
	if(pg_numrows($resP)==0){
		$sql = "DELETE FROM tbl_pedido
		WHERE  tbl_pedido.pedido = $pedido";
		$res = @pg_exec ($con,$sql);
	}

	if (strlen ( pg_errormessage ($con) ) > 0) {
		$msg_erro = pg_errormessage ($con) ;
	}else{
		header ("Location: $PHP_SELF");
		exit;
	}
}

#----------------------- Finalizar Pedido ------------------
if ($finalizar == 1) {
	if (strlen($msg_erro) == 0) {
		if (strlen (trim ($msg_erro)) == 0) {
			$sql = "INSERT INTO tbl_pedido_alteracao (
						pedido
					)VALUES(
						$cook_pedido_acessorio
					)";
			$res = @pg_exec ($con,$sql);

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro = pg_errormessage ($con) ;
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT fn_pedido_finaliza ($cook_pedido_acessorio,$login_fabrica)";
		$res = @pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		$msg = $_GET['msg'];
		header ("Location: pedido_finalizado.php?pedido=$cook_pedido_acessorio&msg=".$msg);
		exit;
	}
}

#--------------- Gravar Item ----------------------
if ($btngravar == "Gravar") {
	$condicao = trim($_POST['condicao']);
#	$bloco_os = intval(trim($_POST['bloco_os']));
	$bloco_os = 0;

	if (strlen($bloco_os) == 0) {
		$aux_bloco_os = 0;
	}else{
		if (is_int($bloco_os) == false) {
			$aux_bloco_os = 0;
		}else{
			$fnc          = pg_exec($con,"SELECT fnc_so_numeros('$bloco_os')");
			$aux_bloco_os = pg_result ($fnc,0,0);
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
	$res = @pg_exec ($con,$sql);
	if (pg_numrows ($res) > 0) $tabela = pg_result ($res,0,0);

	if (strlen($msg_erro) == 0){
		$res = pg_exec($con, "BEGIN TRANSACTION");

		if (strlen ($cook_pedido_acessorio) == 0) {
			$sql = "INSERT INTO tbl_pedido (
						posto          ,
						condicao       ,
						tabela         ,
						bloco_os       ,
						fabrica        ,
						tipo_pedido    ,
						pedido_acessorio
					)VALUES(
						$login_posto   ,
						'$condicao'    ,
						'$tabela'      ,
						$aux_bloco_os  ,
						$login_fabrica ,
						(SELECT tipo_pedido FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND UPPER(trim(descricao)) = 'ACESSORIOS'),
						't'
					)";
			$res = @pg_exec ($con,$sql);

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro = pg_errormessage($con);
			}else{
				$res = pg_exec ($con,"SELECT currval ('seq_pedido')");
				$cook_pedido_acessorio = pg_result($res,0,0);

				# cookie expira em 48 horas
				setcookie ("cook_pedido_acessorio",$cook_pedido_acessorio,time()+(3600*48));
			}
		}else{
			$sql = "UPDATE tbl_pedido SET
						tabela     = '$tabela'       ,
						condicao   = '$condicao'     ,
						bloco_os   = '$aux_bloco_os' ,
						finalizado = null
					WHERE tbl_pedido.pedido = $cook_pedido_acessorio;";
			$res = @pg_exec ($con,$sql);

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro = pg_errormessage ($con) ;
			}

			if (strlen (trim ($msg_erro)) == 0) {
				$sql = "INSERT INTO tbl_pedido_alteracao (
							pedido
						)VALUES(
							$cook_pedido_acessorio
						)";
				$res = @pg_exec ($con,$sql);

				if (strlen ( pg_errormessage ($con) ) > 0) {
					$msg_erro = pg_errormessage ($con) ;
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

				$sql = "SELECT tbl_peca.peca
						FROM   tbl_peca
						WHERE  tbl_peca.referencia_pesquisa = '$xreferencia'
								and tbl_peca.acessorio is true";
				$resX = pg_exec ($con,$sql);

				if (pg_numrows($resX) > 0 AND strlen (trim ($qtde)) > 0) {
					$peca = pg_result($resX,0,0);
					//Chamado 2770
					$sql3 = "SELECT peca FROM tbl_pedido_item WHERE pedido = $cook_pedido_acessorio AND peca = $peca; ";
					$res3 = pg_exec($con,$sql3);
					//echo $sql3;
					if(pg_numrows($res3) > 0){
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
						$res = @pg_exec ($con,$sql);
						$msg_erro = pg_errormessage($con);
					}

					if (strlen($msg_erro) == 0) {
						$res         = @pg_exec ($con,"SELECT CURRVAL ('seq_pedido_item')");
						$pedido_item = @pg_result ($res,0,0);
						$msg_erro = pg_errormessage($con);
					}

					if (strlen($msg_erro) == 0) {
						$sql = "SELECT fn_valida_pedido_item ($cook_pedido_acessorio,$peca,$login_fabrica)";
						$res = @pg_exec ($con,$sql);
						$msg_erro = pg_errormessage($con);
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
					$res = pg_exec($con,$sql);
					if (pg_numrows($res) > 0) {
						$qtde_total_mes = pg_result($res,0,0);
						if (strlen($qtde_total_mes) > 0 && $qtde > $qtde_total_mes) {
							$msg .= " A quantidade $qtde pedida para a peça $referencia está acima da média de $qtde_total_mes pedida normalmente. Você tem certeza que a quantidade pedida está correta? ";
						}
					}
				}*/
			}
		}

		if (strlen($msg_erro) == 0) {
			$sql = "SELECT fn_finaliza_pedido_blackedecker_teste ($cook_pedido_acessorio,$login_fabrica)";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			header ("Location: $PHP_SELF?msg=".$msg);
			exit;
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		}
	}
}

$title     = "Pedido de Peças";
$cabecalho = "Pedido de Peças";

$layout_menu = 'pedido';
include "cabecalho.php";
/*Entra so se for posto atendido pela black*/
$sql2 = "SELECT * FROM tbl_posto_fabrica WHERE fabrica = 1 AND posto = $login_posto AND codigo_posto IN ('10068', '10086', '10097', '10120', '10177', '10240', '10345', '10358', '10378', '10678', '10844', '11147', '11228', '11290', '12004', '12008', '12009', '12010', '12011', '12012', '12012', '12016', '12017', '12019', '12027', '12030', '12058', '12059', '12115', '12120', '12124', '12129', '12138', '13008', '13014', '13031', '13035', '13053', '13072', '13074', '13076', '13077', '13088', '13109', '13114', '13128', '13136', '13150', '13155', '13161', '13201', '13270', '13515', '13516', '13632', '13635', '13695', '13715', '13786', '13812', '14048', '14049', '14055', '14135', '14162', '14166', '14187', '14189', '14228', '14246', '14426', '14675', '14975', '15007', '15024', '15026', '15031', '15034', '15036', '15037', '15046', '15047', '15097', '15111', '15113', '15199', '16001', '20034', '20165', '20223', '20274', '20285', '20312', '20322', '20333', '20336', '20403', '20439', '20621', '20653', '20670', '20763', '20920', '20998', '21061', '21139', '21150', '21163', '21215', '21217', '21272', '21292', '21350', '21362', '21436', '21464', '21661', '21815', '21870', '21914', '21957', '22002', '22010', '22086', '22116', '22426', '22439', '22457', '22612', '22624', '22625', '22626', '22632', '22689', '22720', '22941', '23019', '23098', '23111', '23135', '23150', '23155', '23158', '23160', '23163', '23163', '23193', '23194', '23195', '23215', '23225', '23227', '23241', '23284', '23290', '23292', '23293', '23331', '23340', '23345', '23350', '23354', '23355', '23358', '23359', '23360', '23361', '23364', '23368', '23369', '23373', '23510', '23511', '23513', '23642', '23915', '23921', '23925', '24142', '24212', '24327', '24367', '24394', '24567', '26934', '26942', '26943', '26946', '26948', '26949', '26954', '26955', '26956', '26957', '26958', '29067', '29078', '29079', '30003', '30048', '30052', '31057', '31351', '32007', '32022', '32027', '32057', '32058', '32074', '32085', '32855', '33009', '33018', '33027', '33029', '33034', '34022', '34087', '35021', '35053', '35066', '35143', '36033', '36039', '36086', '36107', '36307', '36778', '36888', '37040', '37043', '38021', '38034', '38036', '38039', '38774', '39075', '39512', '39600', '39730', '39870', '39874', '40031', '40077', '40092', '40113', '40305', '40399', '40563', '40979', '41086', '41272', '41665', '41683', '42076', '42124', '42308', '42362', '42375', '42376', '42408', '43380', '43743', '45007', '45127', '45889', '48838', '50061', '50087', '50139', '51092', '51097', '51167', '51168', '51199', '51734', '51735', '51738', '51771', '51774', '51782', '51787', '52006', '52021', '52028', '52043', '52047', '52051', '52055', '52200', '52208', '53129', '53130', '53155', '53175', '53463', '54006', '54020', '54025', '54029', '54032', '54037', '54038', '54049', '54050', '54089', '55036', '55122', '55142', '55143', '55153', '55155', '55159', '55170', '55200', '55241', '55270', '56016', '56089', '56153', '56176', '56198', '56267', '56305', '56368', '56463', '56875', '56876', '56977', '57215', '57432', '57582', '57719', '57873', '57874', '57972', '58022', '58036', '58142', '58219', '58329', '58558', '58774', '10121', '10341', '10454', '10665', '58414', '10674', '10843', '11399', '12137', '43745', '43244', '10698', '11529', '12015', '12031', '58876', '42450', '12045', '12124', '12127', '23154', '12129', '14017', '14300', '14717', '14975', '15022', '15035', '15040', '15045', '34069', '15048', '15101', '20072', '20331', '20341', '43144', '42300', '20370', '26966', '54062', '13513', '41689', '40142', '42307', '20398', '20417', '20741', '21149', '21302', '21351', '21480', '21551', '21565', '21972', '22007', '22497', '22530', '22585', '22588', '20490', '22627', '22631', '42385', '22670', '22710', '22893', '23156', '23167', '23183', '23184', '58589', '23413', '23189', '23197', '23283', '23298', '23316', '23371', '23372', '23374', '23380', '23381', '23382', '23387', '23388', '23440', '23554', '23980', '24348', '26950', '26953', '26955', '26960', '26961', '26962', '26963', '27907', '27908', '27909', '29060', '29062', '29083', '30002', '31021', '31129', '31141', '31290', '31310', '31395', '32008', '32024', '32029', '34135', '36788', '37583', '38038', '39545', '39600', '39692', '39791', '39874', '40007', '40049', '40052', '40053', '40082', '40092', '40097', '40143', '40444', '40598', '40885', '40907', '40921', '41066', '41176', '41221', '41280', '41309', '41472', '41694', '41979', '42031', '42125', '42270', '42280', '42297', '42404', '42409', '43027', '43051', '43201', '43779', '43818', '44003', '45010', '45126', '50004', '51782', '52198', '53440', '53441', '54024', '54056', '55026', '55049', '55146', '55874', '56047', '56097', '56146', '56376', '56480', '57299', '57602', '58108', '58423', '58879', '84481', '99065', '01122')";
$res2 = pg_exec ($con,$sql2);
			
if (pg_numrows ($res2) > 0) {
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
			$resX = pg_exec ($con,$sql);
#----------- Enviar email de Confirmação de Leitura -----------#
		$sql = "SELECT codigo_posto from tbl_posto_fabrica where fabrica=$login_fabrica and posto=$login_posto";
		$res = pg_exec($con, $sql);
		$codigo_postoo = pg_result($res,0, codigo_posto);

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
	$res2 = pg_exec ($con,$sql2);
//if($ip=="200.232.184.76") echo "$sql2";
	if (pg_numrows ($res2) == 0) {
		echo "<table width='700' border='0' align='center' cellpadding='3' cellspacing='1' class='tabela'>";
		echo "<TR align='center'>";
		echo "<form method='post' name='frm_condicao' action='$PHP_SELF'>";
		echo "	<TD colspan='3' class='titulo_tabela'>COMUNICADO BLACK & DECKER</B></TD>";
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



<?
if ($alterar == 1) {
?>

<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align="center" class="table_line1">
		<b>Antes de lançar Pedido ou OS´s, por favor, <a href='cad_posto.php'>clique aqui</a> <br>e complete seu CNPJ e Inscrição Estadual</b>
	</td>
</tr>
</table>

<?}else{?>
<? include "javascript_pesquisas.php"; ?>
<script language='javascript'>
<!-- 
function fnc_pesquisa_peca1 (campo, campo2, tipo) {
    if (tipo == "referencia" ) {
        var xcampo = campo;
    }

    if (tipo == "descricao" ) {
        var xcampo = campo2;
    }

    if (xcampo.value != "") {
        var url = "";
        url = "peca_pesquisa_lista_blackedecker_acessorio_341188.php?peca=" + xcampo.value + "&tipo=" + tipo ;
        janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes,directories=no, width=500, height=400, top=0, left=0");
        janela.retorno = "pedido_blackedecker_cadastro_acessorio_teste.php";
        janela.referencia= campo;
        janela.descricao= campo2;
        janela.focus();
    }

	
	// function PesquisaPeca (peca_referencia, peca_descricao, peca_qtde) {
	// var url = "";
	// if (peca_referencia.value != "") {
		// url = "peca_pesquisa_lista_blackedecker_acessorio.php?peca=" + peca_referencia.value;
		// janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=501,height=300,top=100,left=100");
		// janela.referencia	= peca_referencia;
		// janela.descricao	= peca_descricao;
		// janela.qtde			= peca_qtde;
		// janela.focus();
	// }
	// }
	
	else{
        window.alert("Informar toda ou parte da informação para realizar a pesquisa!");
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

function SomenteNumero(e){
var tecla=(window.event)?event.keyCode:e.which;

	if((tecla > 47 && tecla < 58)) return true;
	else{
	if (tecla != 8 && tecla != 0) return false;
	else return true;
	}
}


document.onkeydown = keyDown; // work together to analyze keystrokes
if (netscape) document.captureEvents(Event.KEYDOWN|Event.KEYUP);


//-->

</script>


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

if (strlen ($msg_erro) > 0) {
	if (strpos ($msg_erro,"Fail to add null value in not null attribute peca") > 0)
		$msg_erro = "Peça não existe";
?>

<? } ?>

<form name="frmpedido" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="linha" value="<?echo $linha?>">

<?
//Chamado: 1757
$fiscal = fopen("/www/assist/www/periodo_fiscal.txt", "r");
$ler_fiscal = fread($fiscal, filesize("/www/assist/www/periodo_fiscal.txt"));
fclose($fiscal); 
?>

<br>

<table width="700" border="0" cellpadding="2" cellspacing="1" class='texto_avulso'>
<tr>
	<td style='text-align:center'>Data limite para colocação de pedidos neste mês:<br><font color='#ff0000'><? echo $ler_fiscal; ?></font></td>
</tr>
</table>

<br>

<table width="700" border="0" cellpadding="2" cellspacing="2" align="center" class='texto_avulso'>
<tr>
	<td>
		PARA CONTINUAR A DIGITAR ITENS NESTE PEDIDO, BASTA GRAVAR E EM SEGUIDA CONTINUAR DIGITANDO.
		<br> -----
		<br>
	</td>
</tr>
<tr>
	<td>
		AVISO: APÓS GRAVAR O SEU PEDIDO, IRÁ APARECER O RESUMO DOS ITENS LANÇADOS E ABAIXO DESTE RESUMO, TERÁ O BOTÃO DE FINALIZAÇÃO QUE SOMENTE SERÁ USADO QUANDO NÃO EXISTIREM MAIS ITENS A SEREM LANÇADOS NESTE PEDIDO.
	</td>
</tr>

</form>
</table>

<br>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
<tr>
	<td valign="middle" align="center" class='error'>
		<? echo $msg_erro ?>
	</td>
</tr>
</table>

<!--
<table width="700" border="0" cellpadding="2" class='tabela' cellspacing="1" align="center">
	<tr >
		<td colspan='2'  class='titulo_tabela'>Visualizar</td>
	</tr>

	<tr class='titulo_coluna'>
		<td align='center'> Descrição </td>
		<td align='center'> Ações </td>
	</tr>


	<tr bgcolor='#F7F5F0'>
		<td>*** PROCEDIMENTO PARA PEDIDOS DE ACESSÓRIOS</td>
		<td align='center'><form action='http://www.blackanddecker.com.br/comunicado_visualiza.php' target='_blank' style='margin: 0'><input type='hidden' name='comunicado' value='72' /><input type='submit' value='Clique Aqui' /></form></td>
	</tr>
	
	<tr bgcolor='#F1F4FA'>
		<td>*** TABELA DE ACESSÓRIOS</td>
		<td align='center'><form action='http://www.telecontrol.com.br/assist/xls/acessorios_bd.xls' target='_blank'  style='margin: 0'><input type='submit' value='Clique Aqui' /></form></td>
	</tr>
</table>
-->
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

<table width="700" border="0" cellpadding="2" cellspacing="1" align="center">
<tr>
	<td class='titulo_tabela' colspan='2'>CATÁLOGO DOS PRODUTOS</td>
</tr>
<tr class='table_line1' bgcolor='#F1F4FA'>
	<td align='center'><a href='http://www.dewalt.com.br/products/Aces/listTipoCate.asp' target='_blank'>DEWALT</a></td>
	<td align='center'><a href='http://www.bdhobby.com.br/prod/aces/ListTipoCate.asp' target='_blank'>HOBBY</a></td>
</tr>
</table>

<br>

<table width="700" border="0" cellpadding="2" cellspacing="2" class='formulario' align="center">
	<tr>
		<td align='left' class="titulo_tabela">
			<b>Condição de Pagamento.</b>
		</td>
	</tr>

	<tr><td>&nbsp;</td></tr>

	<tr>
		<td align='center' nowrap class="table_line1">

	<? if(1==1) { ?>		

	<select name="condicao" class="frm" onFocus="nextfield ='bloco_os'">
			<option value=''></option>
	<?
				$sql = "SELECT   tbl_condicao.*
						FROM     tbl_condicao
						JOIN     tbl_posto_condicao USING (condicao)
						JOIN     tbl_black_posto_condicao on tbl_black_posto_condicao.posto = $login_posto
						WHERE    tbl_posto_condicao.posto = $login_posto
						AND      tbl_posto_condicao.condicao = tbl_black_posto_condicao.condicao
						AND      tbl_condicao.fabrica     = $login_fabrica
						AND      tbl_posto_condicao.visivel     IS TRUE
						AND      tbl_condicao.visivel_acessorio IS TRUE
						ORDER BY lpad(trim(tbl_condicao.codigo_condicao)::text, 10, '0') ";
	#Não foi comentado o por que deste SELECT, retirei, *Fernando HD7200
	/*			$sql = "SELECT id_condicao, condicao
						FROM tbl_black_posto_condicao
						WHERE posto = $login_posto LIMIT 1";

				$sql = "SELECT tbl_black_posto_condicao.id_condicao, tbl_condicao.descricao as condicao
						FROM tbl_black_posto_condicao
						JOIN tbl_condicao ON tbl_condicao.condicao = tbl_black_posto_condicao.id_condicao
						WHERE posto = $login_posto
						AND tbl_condicao.fabrica = $login_fabrica
						AND promocao IS NOT TRUE";
	*/
				$sql = "SELECT	tbl_black_posto_condicao.id_condicao,
								tbl_black_posto_condicao.condicao
						FROM tbl_black_posto_condicao
						JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_black_posto_condicao.posto and tbl_posto_fabrica.fabrica = $login_fabrica
						JOIN tbl_condicao on tbl_condicao.condicao = tbl_black_posto_condicao.id_condicao and tbl_condicao.fabrica = $login_fabrica
						WHERE tbl_black_posto_condicao.posto = $login_posto ";
				if($promocao == 'f'){
					$sql .=" AND  tbl_condicao.promocao is not true ";
				}else{
					$sql .= "AND  tbl_posto_fabrica.pedido_faturado is true ";
				}
				$sql .= " AND tbl_posto_fabrica.fabrica = $login_fabrica ";


				$res = pg_exec ($con,$sql);
				if (pg_numrows($res) > 0) {
					for ($x=0; $x < pg_numrows($res); $x++) {
						echo "<option "; if ($condicao == pg_result($res,$x,id_condicao)) echo " SELECTED "; echo " value='" . pg_result($res,$x,id_condicao) . "'>" . pg_result($res,$x,condicao) . "</option>\n";
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
	<tr><td>&nbsp;</td></tr>
</table>
<br>

<table width="700" border="0" cellpadding="1" cellspacing="1" class='tabela' align="center">
<tr class="titulo_coluna">
	<td width="25%" align='center' >
		<b>Referência</b>
	</td>
	<td width="60%" align='center'>
		<b>Descrição</b>
	</td>
	<td width="15%" align='center' >
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

        $cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

?>

<tr bgcolor="<?echo $cor?>">
	<td align='center'>
		<input type="text" name="referencia<? echo $i ?>" style='width: 100px;'maxlength="15" value="<? echo $referencia ?>" class="textbox" onFocus="nextfield ='qtde<?echo $i?>'" onblur="PesquisaPeca(window.document.frmpedido.referencia<? echo $i ?>,window.document.frmpedido.descricao<? echo $i ?>,window.document.frmpedido.qtde<? echo $i ?>);">
		<img src='imagens/lupa.png' style="cursor:pointer" alt="Clique para pesquisar por referência do componente" border='0' hspace='5' align='absmiddle' onclick="fnc_pesquisa_peca1(window.document.frmpedido.referencia<? echo $i ?>,window.document.frmpedido.descricao<? echo $i ?>,'referencia');">
	</td>
	<td align='center'>
		<input type="text" name="descricao<? echo $i ?>" style='width: 400px;'  maxlength="30" value="<? echo $descricao ?>" class="textbox" onFocus="nextfield ='qtde<?echo $i?>'">
		
	</td>
	<td align='center'>
		<input type="text" onkeypress='return SomenteNumero(event);' name="qtde<? echo $i ?>" size="6" maxlength="4" value="<? echo $qtde ?>" class="textbox" <? if ($prox <= $done) { echo "onFocus=\"nextfield ='referencia$prox'\""; }else{ echo "onFocus=\"nextfield ='done'\"";}?>>
	</td>
</tr>

<? } ?>

<tr>
	<td align='center' colspan='3'>
		<br>
		<input type="hidden" name="btngravar" value="x">
		<input type='button' value='Gravar' style="cursor:pointer;font:14px Arial;" class='teste' onclick="javascript: document.frmpedido.btngravar.value='Gravar' ; document.frmpedido.submit() ;">
	</td>
</tr>
</table>


<?
if (strlen ($aux_cook_pedido_acessorio) > 0) {
?>
<br>
<table width="700" border="0" cellpadding="3" cellspacing="1" align="center">
<tr>
	<td colspan="5" align="center" class='titulo_tabela'>Resumo do Pedido</td>
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
	<td width="10%" align='center' class="menu_top">
		<b>Ações</b>
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
	$res = pg_exec ($con,$sql);
	$total = 0;
	for ($i = 0 ; $i < @pg_numrows ($res) ; $i++) {

		$cor = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";

		echo "<tr bgcolor='$cor'>";
		echo "<td width='25%' align='left' class='table_line1' nowrap>";
		//echo "<input type='button' value=' Excluir ' onclick='location.href=\"$PHP_SELF?delete=" . pg_result ($res,$i,pedido_item) . "&pedido=$cook_pedido_acessorio\"' />";
		echo pg_result ($res,$i,referencia);
		echo "</td>";

		echo "<td width='50%' align='left' class='table_line1'>";
		echo pg_result ($res,$i,descricao);
		echo "</td>";

		echo "<td width='15%' align='center' class='table_line1'>";
		echo pg_result ($res,$i,qtde);
		echo "</td>";

		echo "<td width='10%' align='right' class='table_line1'>";
		echo number_format (pg_result ($res,$i,preco),2,",",".");
		echo "</td>";
		
		echo "<td width='15%' align='center'>";
	    	echo "<input type='button' value=' Excluir ' onclick='location.href=\"$PHP_SELF?delete=" . pg_result ($res,$i,pedido_item) . "&pedido=$cook_pedido_acessorio\"' />";	
		echo "</td>";

		echo "</tr>";

		$total = $total + (pg_result ($res,$i,preco) * pg_result ($res,$i,qtde));
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
	<td align='right' class="menu_top" style='text-align:right'>&nbsp;</td>
</tr>
</table>



<!-- ============================ Botoes de Acao ========================= -->


<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align='center'>
	    <br><input type='button' value=' Finalizar ' onclick='location.href="<?echo $PHP_SELF?>?finalizar=1&linha=<?echo $linha?>&unificar=t"' /><br><br>
		<!--<br><a href="<?echo $PHP_SELF?>?finalizar=1&linha=<?echo $linha?>&unificar=t"><img src='imagens/btn_finalizar.gif' border='0'></a><br><br>-->
	</td>
</tr>
<tr>
	<td align='center' class='texto_avulso'>
		CASO JÁ TENHA TERMINADO DE DIGITAR OS ITENS E QUEIRA PASSAR PARA A PRÓXIMA TELA, CLIQUE EM FINALIZAR ACIMA.
	</td>
</tr>
</table>

<?
}
?>


<? } # Final do IF do CNPJ e IE ?>

<p>

<?include "rodape.php";?>
