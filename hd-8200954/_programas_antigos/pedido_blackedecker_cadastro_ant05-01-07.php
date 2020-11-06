<?php
//raphael giovanini - inclusao de pedido garantia atraves de funcao

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

if (strlen(trim($_GET["msg"])) > 0) $msg = trim($_GET["msg"]);

// ALTERAÇÃO DE TABELA DE PREÇOS
if ( 1 == 1 AND $login_posto <> 6359 ) {
	if (strval(strtotime(date("Y-m-d H:i:s")))  < strval(strtotime("2007-01-04 07:00:00"))) { // DATA DA VOLTA
		if (strval(strtotime(date("Y-m-d H:i:s")))  >= strval(strtotime("2006-12-26 16:00:00"))) { // DATA DO BLOQUEIO
			$title     = "Pedido de Peças";
			$cabecalho = "Pedido de Peças";
			$layout_menu = 'pedido';
			
			include "cabecalho.php";

			echo "<br><br>\n";
			$sql2 = "SELECT * FROM tbl_posto_fabrica WHERE fabrica = 1 AND posto = $login_posto AND codigo_posto IN ('10068', '10086', '10097', '10120', '10177', '10240', '10345', '10358', '10378', '10678', '10844', '11147', '11228', '11290', '12004', '12008', '12009', '12010', '12011', '12012', '12012', '12016', '12017', '12019', '12027', '12030', '12058', '12059', '12115', '12120', '12124', '12129', '12138', '13008', '13014', '13031', '13035', '13053', '13072', '13074', '13076', '13077', '13088', '13109', '13114', '13128', '13136', '13150', '13155', '13161', '13201', '13270', '13515', '13516', '13632', '13635', '13695', '13715', '13786', '13812', '14048', '14049', '14055', '14135', '14162', '14166', '14187', '14189', '14228', '14246', '14426', '14675', '14975', '15007', '15024', '15026', '15031', '15034', '15036', '15037', '15046', '15047', '15097', '15111', '15113', '15199', '16001', '20034', '20165', '20223', '20274', '20285', '20312', '20322', '20333', '20336', '20403', '20439', '20621', '20653', '20670', '20763', '20920', '20998', '21061', '21139', '21150', '21163', '21215', '21217', '21272', '21292', '21350', '21362', '21436', '21464', '21661', '21815', '21870', '21914', '21957', '22002', '22010', '22086', '22116', '22426', '22439', '22457', '22612', '22624', '22625', '22626', '22632', '22689', '22720', '22941', '23019', '23098', '23111', '23135', '23150', '23155', '23158', '23160', '23163', '23163', '23193', '23194', '23195', '23215', '23225', '23227', '23241', '23284', '23290', '23292', '23293', '23331', '23340', '23345', '23350', '23354', '23355', '23358', '23359', '23360', '23361', '23364', '23368', '23369', '23373', '23510', '23511', '23513', '23642', '23915', '23921', '23925', '24142', '24212', '24327', '24367', '24394', '24567', '26934', '26942', '26943', '26946', '26948', '26949', '26954', '26955', '26956', '26957', '26958', '29067', '29078', '29079', '30003', '30048', '30052', '31057', '31351', '32007', '32022', '32027', '32057', '32058', '32074', '32085', '32855', '33009', '33018', '33027', '33029', '33034', '34022', '34087', '35021', '35053', '35066', '35143', '36033', '36039', '36086', '36107', '36307', '36778', '36888', '37040', '37043', '38021', '38034', '38036', '38039', '38774', '39075', '39512', '39600', '39730', '39870', '39874', '40031', '40077', '40092', '40113', '40305', '40399', '40563', '40979', '41086', '41272', '41665', '41683', '42076', '42124', '42308', '42362', '42375', '42376', '42408', '43380', '43743', '45007', '45127', '45889', '48838', '50061', '50087', '50139', '51092', '51097', '51167', '51168', '51199', '51734', '51735', '51738', '51771', '51774', '51782', '51787', '52006', '52021', '52028', '52043', '52047', '52051', '52055', '52200', '52208', '53129', '53130', '53155', '53175', '53463', '54006', '54020', '54025', '54029', '54032', '54037', '54038', '54049', '54050', '54089', '55036', '55122', '55142', '55143', '55153', '55155', '55159', '55170', '55200', '55241', '55270', '56016', '56089', '56153', '56176', '56198', '56267', '56305', '56368', '56463', '56875', '56876', '56977', '57215', '57432', '57582', '57719', '57873', '57874', '57972', '58022', '58036', '58142', '58219', '58329', '58558', '58774', '10121', '10341', '10454', '10665', '58414', '10674', '10843', '11399', '12137', '43745', '43244', '10698', '11529', '12015', '12031', '58876', '42450', '12045', '12124', '12127', '23154', '12129', '14017', '14300', '14717', '14975', '15022', '15035', '15040', '15045', '34069', '15048', '15101', '20072', '20331', '20341', '43144', '42300', '20370', '26966', '54062', '13513', '41689', '40142', '42307', '20398', '20417', '20741', '21149', '21302', '21351', '21480', '21551', '21565', '21972', '22007', '22497', '22530', '22585', '22588', '20490', '22627', '22631', '42385', '22670', '22710', '22893', '23156', '23167', '23183', '23184', '58589', '23413', '23189', '23197', '23283', '23298', '23316', '23371', '23372', '23374', '23380', '23381', '23382', '23387', '23388', '23440', '23554', '23980', '24348', '26950', '26953', '26955', '26960', '26961', '26962', '26963', '27907', '27908', '27909', '29060', '29062', '29083', '30002', '31021', '31129', '31141', '31290', '31310', '31395', '32008', '32024', '32029', '34135', '36788', '37583', '38038', '39545', '39600', '39692', '39791', '39874', '40007', '40049', '40052', '40053', '40082', '40092', '40097', '40143', '40444', '40598', '40885', '40907', '40921', '41066', '41176', '41221', '41280', '41309', '41472', '41694', '41979', '42031', '42125', '42270', '42280', '42297', '42404', '42409', '43027', '43051', '43201', '43779', '43818', '44003', '45010', '45126', '50004', '51782', '52198', '53440', '53441', '54024', '54056', '55026', '55049', '55146', '55874', '56047', '56097', '56146', '56376', '56480', '57299', '57602', '58108', '58423', '58879', '84481', '99065')";
			$res2 = pg_exec ($con,$sql2);
			if (pg_numrows ($res2) > 0) {
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
			echo "<table width='650' border='0' cellpadding='2' cellspacing='2' align='center'>\n";
			echo "<tr>\n";

			echo "<td width='100%' align='center'>\n";
			echo "<h4><b>";
			echo "De acordo com o comunicado sobre o nosso inventário enviado dia 28/11, o site ficará bloqueado para a digitação de peças e acessórios de 14/12 (após as 13h30) até 25/12/06. <br>
Neste período a Black & Decker não receberá pedidos e não emitirá nota fiscal, ou seja, não haverá nenhuma movimentação no estoque, por se tratar do nosso período de inventário na fábrica.<br>
 O faturamento retornará no dia 26/12/06 . Neste dia o site estará liberado para a digitação a partir das 08h. 
<br><br>
FECHAMENTO FISCAL DEZEMBRO 2006
<br><br>
O nosso fechamento para o mês de Dezembro/2006 será logo após o retorno do inventário, ou seja, nos dia 26, 27 e 28/12/06 (até às 13h30), serão os últimos dias do mês que receberemos pedidos de peças e acessórios. 
<br>
No dia 28/12 após às 13h30, o site será bloqueado para a digitação de pedidos, retornarndo somente no dia 04/01/2007, devido a implementação do novo sistema operacional de faturamento da Black & Decker.
<br>
Por isso é de suma importância que seja feita uma programação de peças com base neste período que a fábrica ficará sem atender, principalmente para as peças de maior giro.
<br><br> 

Atenciosamente.
<br>
Faturamento de peças";
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
				tbl_posto.cnpj                      ,
				tbl_posto.ie                        ,
				tbl_posto.nome                      ,
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

if ($pedido_faturado == 'f') {
	$title     = "Pedido de Peças";
	$cabecalho = "Pedido de Peças";
	$layout_menu = "pedido";

	include "cabecalho.php";

	echo "
	<style type=\"text/css\">
	.menu_top { text-align: center; font-family: Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 10px; font-weight: bold; border: 0px solid; color:'#ffffff'; background-color: '#596D9B'; }
	.table_line1 { font-family: Verdana, Geneva, Arial, Helvetica, sans-serif; font-size: 11px; font-weight: normal; border: 0px solid; }
	</style>";

	echo "<table width='700' border='0' cellpadding='3' cellspacing='1' align='center'>\n";
	echo "<tr>\n";

	echo "<td width='100%' align='left' class='table_line1'>\n";
	echo "<b>Caro $nome_posto</b>, seu pedido de peças deve ser efetuado através de um distribuidor de sua região.\n";
	echo "<br><br>\n";
	echo "Abaixo relação de distribuidores por região:\n";
	echo "</td>\n";

	echo "</tr>\n";
	echo "</table>\n";

	$sql = "SELECT  tbl_posto.nome                                                                                 ,
					tbl_posto_fabrica.codigo_posto                                                                 ,
					tbl_posto.fone                                                                                 ,
					tbl_posto.contato                                                                              ,
					tbl_posto.email                                                                                ,
					(tbl_posto.endereco || ', ' || tbl_posto.numero || ' ' || tbl_posto.complemento) AS endereco   ,
					tbl_posto.bairro                                                                               ,
					tbl_posto.cidade                                                                               ,
					tbl_posto.estado                                                                               ,
					(substr(tbl_posto.cep,1,5) || '-' || substr(tbl_posto.cep,6,3))                     AS cep     ,
					(case tbl_posto.estado when '$estado' then '1' else '2' end )                       AS ordem
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = 1
			WHERE   tbl_posto_fabrica.codigo_posto not in ('1122', '23513', '20741', '21957')
			AND     ( tbl_posto_fabrica.tipo_posto IN (39, 79, 80, 81) OR tbl_posto_fabrica.tipo_posto = 40 )
			ORDER BY ordem, tbl_posto.cidade";
	$res = @pg_exec ($con,$sql);

	for ($x=0; $x < pg_numrows($res); $x++) {
		$nome     = trim(pg_result($res,$x,nome));
		$posto    = trim(pg_result($res,$x,codigo_posto));
		$estado   = trim(pg_result($res,$x,estado));
		$fone     = trim(pg_result($res,$x,fone));
		$contato  = trim(pg_result($res,$x,contato));
		$email    = trim(pg_result($res,$x,email));
		$endereco = trim(pg_result($res,$x,endereco));
		$bairro   = trim(pg_result($res,$x,bairro));
		$cidade   = trim(pg_result($res,$x,cidade));
		$cep      = trim(pg_result($res,$x,cep));

		if ($codigo_posto <> "21957" and $codigo_posto <> "20741") {
			echo "<table width='700' border='0' cellpadding='3' cellspacing='1' align='center'>\n";
			echo "<tr>\n";
			echo "<td align='left'   class='menu_top'><b>DISTRIBUIDOR</b></td>\n";
			echo "<td align='center' class='menu_top'><b>CÓDIGO</b></td>\n";
			echo "<td align='center' class='menu_top'><b>UF</b></td>\n";
			echo "<td align='center' class='menu_top'><b>TELEFONE</b></td>\n";
			echo "<td align='left'   class='menu_top'><b>CONTATO</b></td>\n";
			echo "<td align='center' class='menu_top'><b>eMail</b></td>\n";
			echo "</tr>\n";
			echo "<tr>\n";
			echo "<td align='left'   class='table_line1'>$posto - $nome</td>\n";
			echo "<td align='center' class='table_line1'>$posto</td>\n";
			echo "<td align='center' class='table_line1'>$estado</td>\n";
			echo "<td align='center' class='table_line1'>$fone</td>\n";
			echo "<td align='left'   class='table_line1'>$contato</td>\n";
			echo "<td align='left'   class='table_line1'>$email</td>\n";
			echo "</tr>\n";
			echo "</table>\n";

			echo "<table width='700' border='1' cellpadding='2' cellspacing='0' align='center'>\n";
			echo "<tr>\n";
			echo "<td align='left'   class='menu_top'><b>ENDEREÇO</b></td>\n";
			echo "<td align='center' class='menu_top'><b>BAIRRO</b></td>\n";
			echo "<td align='center' class='menu_top'><b>CIDADE</b></td>\n";
			echo "<td align='center' class='menu_top'><b>CEP</b></td>\n";
			echo "</tr>\n";
			echo "<tr>\n";
			echo "<td align='left'   class='table_line1'>$endereco</td>\n";
			echo "<td align='center' class='table_line1'>$bairro</td>\n";
			echo "<td align='center' class='table_line1'>$cidade</td>\n";
			echo "<td align='center' class='table_line1'>$cep</td>\n";
			echo "</tr>\n";
			echo "</table>\n";

			echo "<br>\n";
		}
	}

	echo "<br><br>\n";

	include "rodape.php";
	exit;
}

setcookie ("cook_pedido");
$cook_pedido = "";

$sql = "SELECT  tbl_pedido.pedido                                              ,
		lpad(tbl_pedido.pedido_blackedecker,5,0) AS pedido_blackedecker
		FROM    tbl_pedido
		WHERE   tbl_pedido.exportado           ISNULL
		AND     tbl_pedido.controle_exportacao ISNULL
		AND     tbl_pedido.admin               ISNULL
		AND     (
			tbl_pedido.natureza_operacao ISNULL        OR
			tbl_pedido.natureza_operacao <> 'SN-GART' AND
			tbl_pedido.natureza_operacao <> 'VN-REV'
			)
		AND     tbl_pedido.pedido_os         IS NOT TRUE
		AND     tbl_pedido.pedido_acessorio  IS NOT TRUE
		AND     tbl_pedido.pedido_sedex      IS NOT TRUE
		 ";
		 //AND     tbl_pedido.condicao          NOT IN (62)

if (strval(strtotime(date("Y-m-d H:i:s"))) < strval(strtotime("2006-01-01 00:00:00"))) {
	$sql .= "AND     tbl_pedido.tabela = 31 ";
}else{
	$sql .= "AND     tbl_pedido.tabela = 108 ";
}
$sql .= "AND     tbl_pedido.posto             = $login_posto
		AND     tbl_pedido.fabrica           = $login_fabrica;";
//if ($ip == '201.42.112.110') echo $sql;exit;
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$cook_pedido         = trim(pg_result($res,0,pedido));
	$pedido_blackedecker = trim(pg_result($res,0,pedido_blackedecker));
	
	$pedido_suframa      = "";
	$sql_suframa = "SELECT pedido_suframa FROM tbl_pedido WHERE pedido_suframa = $cook_pedido";
	$res_suframa = pg_exec ($con,$sql_suframa);
	if (pg_numrows ($res_suframa) > 0) {
	    $pedido_suframa = pg_result ($res_suframa,0,0);
	}
	
	setcookie ("cook_pedido",$cook_pedido,time()+(3600*48));
}

if (strlen($cook_pedido) > 0) {
	$sql = "SELECT  tbl_condicao.condicao,
					tbl_pedido.bloco_os
			FROM    tbl_pedido
			JOIN    tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
			WHERE   tbl_pedido.pedido = $cook_pedido";
	$res = @pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$condicao = trim(pg_result($res,0,condicao));
		$bloco_os = trim(pg_result($res,0,bloco_os));;
	}
}


if (strlen($cook_pedido) > 0 and strlen($btngravar) == 0 and strlen($finalizar) == 0) {
	$res = pg_exec($con, "BEGIN TRANSACTION");

	if (strlen($pedido_suframa) > 0) {
		$sql = "INSERT INTO tbl_pedido_item (
					pedido,
					peca  ,
					qtde  ,
					preco
			)
			SELECT  $cook_pedido          ,
					tbl_pedido_item.peca  ,
					tbl_pedido_item.qtde  ,
					tbl_pedido_item.preco
			FROM    tbl_pedido_item
			JOIN    tbl_pedido using (pedido)
			WHERE   tbl_pedido.pedido_suframa = $cook_pedido;";
		$res = pg_exec ($con,$sql);
		
		if (strlen ( pg_errormessage ($con) ) > 0) {
			$msg_erro .= pg_errormessage ($con) ;
		}

		if (strlen($msg_erro) == 0) {
			$sql = "SELECT fnc_pedido_delete ($cook_pedido, $login_fabrica, null)";
			$res = @pg_exec ($con,$sql);
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro .= pg_errormessage ($con);
			}
		}
	}


	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}

if (strlen($_GET["ignorar"]) > 0) {
	$ignorar = trim($_GET["ignorar"]);

	$sql = "SELECT fnc_pedido_delete ($ignorar, $login_fabrica, null)";
	$res = pg_exec ($con,$sql);

	setcookie ("cook_pedido");
	$cook_pedido = "";

	header ("Location: $PHP_SELF");
	exit;
}

#----------------------- Deletar Item ------------------
if (strlen($_GET["delete"]) > 0) {
	$delete = trim($_GET["delete"]);

	$sql = "DELETE FROM tbl_pedido_item
			WHERE  tbl_pedido_item.pedido_item = $delete";
	$res = @pg_exec ($con,$sql);

	if (strlen ( pg_errormessage ($con) ) > 0) {
		$msg_erro .= pg_errormessage ($con) ;
	}else{
		header ("Location: $PHP_SELF");
		exit;
	}
}


#----------------------- Finalizar Pedido ------------------
if ($finalizar == 1) {
	if (strlen($msg_erro) == 0) {
		$sql = "SELECT tbl_pedido.pedido
				FROM   tbl_pedido
				WHERE  tbl_pedido.exportado isnull
				AND    tbl_pedido.pedido = $cook_pedido;";
		$res = @pg_exec ($con,$sql);

		if (pg_numrows($res) == 0) {
			$msg_erro .= "Pedido não pode ser mais alterado pois já foi exportado.";
			setcookie ("cook_pedido");
			$cook_pedido = "";
		}

		if (strlen($msg_erro) == 0) {
			$sql = "UPDATE tbl_pedido SET
						unificar_pedido = '$unificar'
					WHERE  tbl_pedido.pedido = $cook_pedido
					AND    tbl_pedido.unificar_pedido isnull;";
			$res = @pg_exec ($con,$sql);

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro .= pg_errormessage ($con) ;
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		if (strlen (trim ($msg_erro)) == 0) {
			$sql = "INSERT INTO tbl_pedido_alteracao (
						pedido
					)VALUES(
						$cook_pedido
					)";
			$res = @pg_exec ($con,$sql);

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro .= pg_errormessage ($con) ;
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT fn_pedido_finaliza ($cook_pedido,$login_fabrica)";
		$res = @pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}

	if (strlen($msg_erro) == 0) {
		$sql = "SELECT fn_pedido_suframa($cook_pedido,$login_fabrica);";
		$res = @pg_exec ($con,$sql);
		$msg_erro .= pg_errormessage($con);
	}
/*		if (strlen (trim ($msg_erro)) == 0 AND strlen($pedido_em_garantia)>0) {
			$sql = "SELECT fn_black_pedido_garantia($cook_pedido)";
#			echo $sql.'<br>';
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);

	}
*/	
	if(strlen($pedido_em_garantia)==0){
		$sql = "SELECT tbl_pedido.pedido
				FROM   tbl_pedido
				JOIN   tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
				WHERE  tbl_pedido.pedido = $cook_pedido
				AND    tbl_pedido.total <= 200
				AND    trim(tbl_condicao.codigo_condicao) <> '15'
				AND    trim(tbl_condicao.codigo_condicao) <> '30'
				AND    trim(tbl_condicao.codigo_condicao) <> '60'
				AND    trim(tbl_condicao.codigo_condicao) <> '90';";
		$res = @pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) $msg_erro = "
		<table width='650' border='0' cellpadding='2' cellspacing='2' align='center'>
		<tr>
		<td align='left'>
		<font face='Verdana, Arial' size='2' color='#FFFFFF'>
		<b>Pedidos de valor até R$ 200,00 gerarão parcela única, sendo disponível estas opções</b>:<br>
		<UL>
			<LI>À VISTA ou 30 dias direto (sem taxa financeira);
			<LI>60 dias direto (3%);
			<LI>90 dias direto (6,10%)
		</UL>
		<br><center>Favor alterar a condição de pagamento e clicar em gravar.</center><br><br>
		</font>
		</td>
		</tr>
		</table>";

		if (strlen($msg_erro) == 0) {
			$sql = "SELECT tbl_pedido.pedido
					FROM   tbl_pedido
					JOIN   tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
					WHERE  tbl_pedido.pedido = $cook_pedido
					AND    tbl_pedido.total >  200
					AND    tbl_pedido.total <= 400
					AND    trim(tbl_condicao.codigo_condicao) <> '15'
					AND    trim(tbl_condicao.codigo_condicao) <> '30'
					AND    trim(tbl_condicao.codigo_condicao) <> '47'
					AND    trim(tbl_condicao.codigo_condicao) <> '60'
					AND    trim(tbl_condicao.codigo_condicao) <> '76'
					AND    trim(tbl_condicao.codigo_condicao) <> '90';";
			$res = @pg_exec ($con,$sql);

			if (pg_numrows($res) > 0) $msg_erro .= "
			<table width='650' border='0' cellpadding='2' cellspacing='2' align='center'>
			<tr>
			<td align='left'>
			<font face='Verdana, Arial' size='2' color='#FFFFFF'>
			<b>Pedidos acima de R$ 200,00 e até R$ 400,00 gerarão duas parcelas, sendo disponível estas opções</b>:<br>
			<UL>
				<LI>30/60 dias (1,5%);
				<LI>60/90 dias (4,5%);
			</UL>
			e/ou
			<br>
			<UL>
				<LI>À VISTA ou 30 dias direto (sem taxa financeira);
				<LI>60 dias direto (3%);
				<LI>90 dias direto (6,10%)
			</UL>
			<br><center>Favor alterar a condição de pagamento e clicar em gravar.</center><br><br>
			</font>
			</td>
			</tr>
			</table>";
		}

		if (strlen($msg_erro) == 0) {
			$sql = "SELECT tbl_pedido.pedido
					FROM   tbl_pedido
					JOIN   tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
					WHERE  tbl_pedido.pedido = $cook_pedido
					AND    tbl_pedido.total > 400
					AND    trim(tbl_condicao.codigo_condicao) <> '15'
					AND    trim(tbl_condicao.codigo_condicao) <> '30'
					AND    trim(tbl_condicao.codigo_condicao) <> '47'
					AND    trim(tbl_condicao.codigo_condicao) <> '60'
					
					AND    trim(tbl_condicao.codigo_condicao) <> '76'
					AND    trim(tbl_condicao.codigo_condicao) <> '90'
					AND    trim(tbl_condicao.codigo_condicao) <> '191';";
					//AND    trim(tbl_condicao.codigo_condicao) <> '62'
			$res = @pg_exec ($con,$sql);

			if (pg_numrows($res) > 0) $msg_erro .= "Pedidos acima de R$ 400,00 gerarão três parcelas, sendo disponível estas opções:<br>
			<UL>
				<LI>30/60/90 dias (3%);
				<LI> 60/90/120 dias (6,10%);
			</UL>
			e/ou
			<br>
			<UL>
				<LI>À VISTA ou 30 dias direto (sem taxa financeira);
				<LI>60 dias direto (3%);
				<LI>90 dias direto (6,10%)
			</UL>
			e/ou
			<UL>
				<LI>30/60 dias (1,5%);
				<LI>60/90 dias (4,5%);
			</UL>
			<br>
			<br>Favor alterar a condição de pagamento e clicar em gravar.<br><br>";
		}

	}

	if (strlen($msg_erro) == 0) {
		$msg = $_GET['msg'];
		header ("Location: pedido_finalizado.php?msg=".$msg);
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
		if (strlen ($condicao) == 0 AND strlen ($cook_pedido)== 0) {
			$msg_erro .= "Escolha a condição de pagamento";
		}
	}
	
	if (strval(strtotime(date("Y-m-d H:i:s"))) < strval(strtotime("2006-01-01 00:00:00"))) {
		$sql = "SELECT tbl_tabela.tabela
				FROM   tbl_tabela
				WHERE  tbl_tabela.sigla_tabela = 'BASE1'";
	}else{
		$sql = "SELECT tbl_tabela.tabela
				FROM   tbl_tabela
				WHERE  tbl_tabela.sigla_tabela = 'BASE2'";
	}
	$res = @pg_exec ($con,$sql);
	if (pg_numrows ($res) > 0) $tabela = pg_result ($res,0,0);
	
	if (strlen($msg_erro) == 0){
		$res = pg_exec($con, "BEGIN TRANSACTION");
		
		if (strlen ($cook_pedido) == 0) {
			$sql = "INSERT INTO tbl_pedido (
						posto          ,
						condicao       ,
						tabela         ,
						bloco_os       ,
						fabrica        ,
						tipo_pedido    ,
						unificar_pedido
					)VALUES(
						$login_posto  ,
						'$condicao'   ,
						'$tabela'     ,
						$aux_bloco_os ,
						$login_fabrica,
						(SELECT tipo_pedido FROM tbl_tipo_pedido WHERE fabrica = $login_fabrica AND UPPER(trim(descricao)) = 'FATURADO' ),
						't'
					)";
			$res = @pg_exec ($con,$sql);
//if ($ip == "201.0.9.216") echo $sql;
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro .= pg_errormessage ($con) ;
			}else{
				$res = pg_exec ($con,"SELECT currval ('seq_pedido')");
				$cook_pedido = pg_result ($res,0,0);

				# cookie expira em 48 horas
				setcookie ("cook_pedido",$cook_pedido,time()+(3600*48));
			}
		}else{
			$sql = "SELECT tbl_pedido.pedido
					FROM   tbl_pedido
					WHERE  tbl_pedido.exportado isnull
					AND    tbl_pedido.pedido = $cook_pedido;";
			$res = @pg_exec ($con,$sql);

			if (pg_numrows($res) == 0) {
				$msg_erro .= "Pedido não pode ser mais alterado pois já foi exportado.";
				setcookie ("cook_pedido");
				$cook_pedido = "";
			}
			
			if (strlen($msg_erro) == 0) {
				$sql = "UPDATE tbl_pedido SET
							tabela     = '$tabela'       ,
							condicao   = '$condicao'     ,
							bloco_os   = '$aux_bloco_os' ,
							total      = null            ,
							finalizado = null
						WHERE tbl_pedido.pedido = $cook_pedido;";
				$res = @pg_exec ($con,$sql);

				if (strlen ( pg_errormessage ($con) ) > 0) {
					$msg_erro .= pg_errormessage ($con) ;
				}
			}

			if (strlen (trim ($msg_erro)) == 0) {
				$sql = "INSERT INTO tbl_pedido_alteracao (
							pedido
						)VALUES(
							$cook_pedido
						)";
				$res = @pg_exec ($con,$sql);

				if (strlen ( pg_errormessage ($con) ) > 0) {
					$msg_erro .= pg_errormessage ($con) ;
				}
			}
		}
		
		if (strlen($cook_pedido) > 0) {
			$sql = "SELECT fn_finaliza_pedido_blackedecker ($cook_pedido,$login_fabrica)";
			$res = @pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
		
		if (strlen (trim ($msg_erro)) == 0) {
			$msg = "";
			for ($i = 0 ; $i < 15 ; $i++) {
				$referencia = trim($_POST["referencia" . $i]);
				$qtde       = trim($_POST["qtde"       . $i]);

				$xreferencia = str_replace(" ","",$referencia);
				$xreferencia = str_replace(".","",$xreferencia);
				$xreferencia = str_replace("-","",$xreferencia);
				$xreferencia = str_replace("/","",$xreferencia);

				$xreferencia = strtoupper($xreferencia);

				if (strlen($referencia) > 0) {
					$sql = "SELECT tbl_peca.peca
							FROM   tbl_peca
							WHERE  tbl_peca.referencia_pesquisa = '$xreferencia'";
					$resX = pg_exec ($con,$sql);

					if (pg_numrows($resX) > 0 AND strlen (trim ($qtde)) > 0 AND $qtde > 0) {
						$peca = pg_result($resX,0,0);
						
						$sql = "INSERT INTO tbl_pedido_item (
								pedido,
								peca  ,
								qtde
							)VALUES(
								$cook_pedido,
								$peca       ,
								$qtde
							)";
						$res = @pg_exec ($con,$sql);
						$msg_erro .= @pg_errormessage($con);



						if (strlen($msg_erro) == 0) {
							$res         = @pg_exec ($con,"SELECT CURRVAL ('seq_pedido_item')");
							$pedido_item = @pg_result ($res,0,0);
							$msg_erro .= pg_errormessage($con);
						}

						if (strlen($msg_erro) == 0) {
							$sql = "SELECT fn_valida_pedido_item ($cook_pedido,$peca,$login_fabrica)";
							$res = @pg_exec ($con,$sql);
							$msg_erro .= pg_errormessage($con);
						}

						if (strlen ($msg_erro) > 0) {
							$erro_linha = "erro_linha" . $i;
							$$erro_linha = 1 ;
							break ;
						}


					}else{
						if (strlen (trim ($qtde)) > 0 AND $qtde > 0) {
							$msg_erro = "Item $referencia não existe, Consulte a vista explodida atualizada e verifique o código correto.";
						}else{
							$msg_erro = "Favor informar a quantidade para o item $referencia.";
						}

						if (strlen ($msg_erro) > 0) {
							$erro_linha = "erro_linha" . $i;
							$$erro_linha = 1 ;
							break ;
						}
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
							AND   tbl_pedido.pedido NOT IN ($cook_pedido)
							AND   UPPER(TRIM(tbl_tipo_pedido.descricao)) = 'FATURADO';";
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
			$sql = "SELECT fn_finaliza_pedido_blackedecker ($cook_pedido,$login_fabrica)";
			$res = @pg_exec ($con,$sql);
			$msg_erro .= pg_errormessage($con);
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

?>

<!-- ---------------------- Inicio do HTML -------------------- -->

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

</style>

<?
if ($alterar == 1) {
?>



<table width="400" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align="center" class="table_line1">
		<b>Antes de lançar Pedido ou OS´s, por favor, <a href='cad_posto.php'>clique aqui</a> <br>e complete seu CNPJ e Inscrição Estadual</b>
	</td>
</tr>
</table>

<?}else{?>

<script LANGUAGE="JavaScript">
function FuncPesquisaPeca (peca_referencia, peca_descricao, peca_qtde) {
	var url = "";
	if (peca_referencia.value != "") {
		url = "peca_pesquisa_lista_blackedecker.php?peca=" + peca_referencia.value;
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=501,height=400,top=50,left=100");
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

<?
//if (strlen($cook_pedido) > 0) {
//	echo "<br>";
//	echo "<div class='contentBlockMiddle' style='width: 600px'>";
//	echo "<table border='0' cellpadding='0' cellspacing='0'>";
//	echo "<tr>";
//	echo "<td><img border='0' src='imagens/esclamachion1.gif'></td>";
//	echo "<td align='center'><b>Para que o pedido $pedido_blackedecker seja enviado para a fábrica, grave e finalize o pedido novamente, antes de sair da tela de digitação de pedidos.</b></td>";
//Para que o pedido 62301 seja enviado para a fábrica às 13h30, grave e finalize o pedido novamente, antes de sair da tela de digitação de pedidos. 
//Caso seja necessário incluir ou cancelar algum item, após a manutenção, grave e finalize novamente.
//	echo "<td align='center'><b>Para que o pedido $pedido_blackedecker seja enviado para a fábrica às 13h30, grave e finalize o pedido novamente, antes de sair da tela de digitação de pedidos.  Caso seja necessário incluir ou cancelar algum item, após a manutenção, grave e finalize novamente.</b></td>";
//	echo "<td align='center'><b>Caso seja necessário incluir ou cancelar algum item, após a manutenção, grave e finalize novamente. </b></td>";
//	echo "</tr>";
//	echo "</table>";
//	echo "</div>";
//}
if (strlen($cook_pedido) > 0) {
?>
	<table width="700" border="0" cellpadding="5" cellspacing="2" align="center">
	<tr>
		<td align='center' bgcolor='#f4f4f4'>
			<p align='justify'><font size='1'><b> Para que o pedido <? echo $pedido_blackedecker ?> seja enviado para a fábrica às 13h30, grave e finalize o pedido novamente, antes de sair da tela de digitação de pedidos.</b></font></p>
		</td>
	</tr>
	<tr>
		<td align='center' bgcolor='#f4f4f4'>
			<p align='justify'><font size='1' color='#FF0000'><b>  Caso seja necessário incluir ou cancelar algum item, após a manutenção, grave e finalize novamente.</b></font></p>
		</td>
	</tr>
<?	
}

if (strlen($msg) > 0) $msg_erro .= $msg;

if (strlen ($msg_erro) > 0) {
	echo "<br>";
	if (strpos ($msg_erro,"Fail to add null value in not null attribute peca") > 0)
		$msg_erro .= "Peça não existe";

	if (strpos ($msg_erro,'update or delete on "tbl_pedido" violates foreign key constraint "$3" on "tbl_pedido') > 0)
		$msg_erro .= "Não foi possível gerar pedido SUFRAMA. Por favor, entre em contato com o administrador.";
?>
<table width="700" border="0" cellpadding="0" cellspacing="0" align="center">
<tr>
	<td valign="middle" align="center" class='error'>
<? 
	// retira palavra ERROR:
	if (strpos($msg_erro,"ERROR: ") !== false) {
		$erro = "Foi detectado o seguinte erro:<br>";
		$msg_erro = substr($msg_erro, 6);
	}

	// retira CONTEXT:
	if (strpos($msg_erro,"CONTEXT:")) {
		$x = explode('CONTEXT:',$msg_erro);
		$msg_erro = $x[0];
	}

	echo $erro . $msg_erro;
?>
	</td>
</tr>
</table>
<p>
<? } ?>

<form name="frmpedido" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="linha" value="<?echo $linha?>">

<!--
<table width="500" border="0" cellpadding="2" cellspacing="1" align="center">
<tr>
	<td colspan='2' align='center'><font face='verdana' size='2' color='#000000'><b>IMPORTANTE: <br> A PARTIR DE 01/01/06, O HORÁRIO DO ENVIO DE PEDIDOS PARA <br> A FÁBRICA SERÁ ALTERADO PARA ÀS 13h30</b></font></td>
</tr>
</table>

<p>

<table width="550" border="0" cellpadding="2" cellspacing="1" align="center">
<tr>
	<td colspan='2' align='center'><font face='verdana' size='2' color='#FF0000'><b>FOI FINALIZADA A SEGUNDA FASE DA ANALISE NA TABELA DE PREÇOS.
	<br><br> NESSA ETAPA HOUVE UM EQUILÍBRIO DE ALGUNS ITENS IMPORTADOS QUE ESTAVAM COM PREÇO FORA DE MERCADO.
	<br><br> DESTA MANEIRA, SOLICITAMOS QUE BAIXEM UMA NOVA TABELA E FAÇAM SUAS ATUALIZAÇÕES.
	<br><br>QUALQUER DÚVIDA, POR GENTILEZA ENTRAR EM CONTATO.
	<br><br>
	SILVÂNIA ALVES
	<br>Black & Decker do Brasil
	<br>(34) 3318-3025
	</b></font></td>
</tr>
</table>
-->

<table width="500" border="0" cellpadding="2" cellspacing="1" align="center">
<tr>
	<td colspan='2' align='center'><font face='verdana' size='2' color='#0000ff'><b>Data limite para colocação de pedidos neste mês:<br><font color='#ff0000'> 24/11   à   14/12/06 , até às 13h30.</font></font></td>
</tr>
</table>

<p>

<table width="500" border="0" cellpadding="2" cellspacing="1" align="center">
<tr>
	<td class='menu_top' colspan='2'>INFORMAÇÕES IMPORTANTES</td>
</tr>
<? if ($tipo_posto == 39 or $tipo_posto == 79 or $tipo_posto == 80 or $tipo_posto == 81) { ?>
<tr class='table_line1' bgcolor='#F1F4FA'>
	<td>*** PROJETO DE UNIFICAÇÃO DOS PEDIDOS (DISTRIBUIDOR)</td>
	<td align='center'><b><a href='http://www.blackdecker.com.br/xls/procedimento_distribuidor.doc' target='_blank'>Clique aqui</a></b></td>
</tr>
<? } ?>

<? if ($pedido_em_garantia == "t") { ?>
<tr class='table_line1' bgcolor='#F1F4FA'>
	<td>*** PROJETO DE UNIFICAÇÃO DOS PEDIDOS (GARANTIA)</td>
	<td align='center'><b><a href='http://www.blackdecker.com.br/xls/procedimento_garantia.doc' target='_blank'>Clique aqui</a></b></td>
</tr>
<? } ?>

<tr class='table_line1' bgcolor='#F1F4FA'>
	<td>*** CALENDÁRIO FISCAL</td>
	<td align='center'><b><a href='http://www.blackdecker.com.br/xls/calendario_fechamento.xls' target='_blank'>Clique aqui</a></b></td>
</tr>
<tr class='table_line1' bgcolor='#F1F4FA'>
	<td>*** ENVIAR PEDIDOS VIA ARQUIVO</td>
	<td align='center'><b><a href='pedido_upload.php'>Clique aqui</a></b></td>
</tr>
</table>

<br>

<!--
<? if ($cook_pedido > 0) { ?>
<table width="700" border="0" cellpadding="5" cellspacing="2" align="center">
<tr>
	<td align="center" width="100%" class="table_line1" bgcolor='#f4f4f4'>
		<p align='justify'><font size=1>
		<font color='#FF0000'><b>O SEU PEDIDO NÚMERO</b>: <b><? echo $pedido_blackedecker ?> SERÁ EXPORTADO ÀS 13h30</font>, SE NECESSÁRIO, INCLUA OS ITENS FALTANTES E FINALIZE NOVAMENTE. SE O PEDIDO NÃO FOR FINALIZADO APÓS A INCLUSÃO DE NOVOS ITENS, SERÁ EXPORTADO PARA A BLACK & DECKER APENAS O PEDIDO FINALIZADO INICIALMENTE</b>.<br>
		</font></p>
	</td>
</tr>
</table>
<? } ?>
-->

<br>


<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align='left' class="menu_top">
		<b>Posto</b>
	</td>

	<td align='left' class="menu_top">
		<b>Razão Social</b>
	</td>

	<td align='left' class="menu_top">
		<b>Cond. Pgto.</b>
	</td>
<!--<td align='left' class="menu_top">
		<b>Bloco de Os's</b>
	</td>-->
</tr>
<tr>
	<td align='center' class="table_line1" valign='top'>
		<b><? echo $codigo_posto; ?></b>
	</td>

	<td align='left' class="table_line1" valign='top'>
		<b><? echo $nome_posto; ?></b>
	</td>

	<td align='center' nowrap class="table_line1" valign='top'>

		<select name="condicao" class="frm">
		<option value=''></option>
<?
//RAPHAEL
			$sql = "SELECT   tbl_condicao.*
					FROM     tbl_condicao
					JOIN     tbl_posto_condicao USING (condicao)
					WHERE    tbl_posto_condicao.posto = $login_posto
					AND      tbl_condicao.fabrica     = $login_fabrica
					AND      (
					       tbl_posto_condicao.visivel IS TRUE)
					ORDER BY lpad(trim(tbl_condicao.codigo_condicao), 10, 0) ";
			//echo $sql;
			$res = pg_exec ($con,$sql);

			for ($x=0; $x < pg_numrows($res); $x++) {
				echo "<option "; if ($condicao == pg_result($res,$x,condicao)) echo " SELECTED "; echo " value='" . pg_result($res,$x,condicao) . "'>" . pg_result($res,$x,descricao) . "</option>\n";
			}
?>
		</select>
		<br>
		<font face='arial' size='-2' color='#336633'><b>Favor escolher a condição de pagamento</b></font>
	</td>
<!--<td align='center' class="table_line1" valign='top'>
		<b><input type='text' name='bloco_os' value='<? echo $bloco_os; ?>' size=3 maxlength=3></b>
	</td>-->
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
	$erro_linha .= $$erro_linha;

	$prox = $i + 1;
	$done = 14;

	$cor_erro = "#ffffff";
	if ($erro_linha == 1) $cor_erro = "#AA6666";

?>

<!--<tr bgcolor="<?echo $cor_erro?>">
	<td align='center'>
		<input type="text" name="referencia<? echo $i ?>" onblur="javascript:fnc_fora_linha(this.value, <?echo $i?>)" size="15" maxlength="15" value="<? echo $referencia ?>" class="textbox" style="width:100px" onFocus="nextfield ='qtde<?echo $i?>'">
	</td>
	<td align='center'>
		<input type="text" name="qtde<? echo $i ?>" size="4" maxlength="4" value="<? echo $qtde ?>" class="textbox" style="width:40px " <? if ($prox <= $done) { echo "onFocus=\"nextfield ='referencia$prox'\""; }else{ echo "onFocus=\"nextfield ='done'\"";}?>>
	</td>
</tr>-->

<tr bgcolor="<?echo $cor_erro?>">
	<td align='center'>
		<input type="text" name="referencia<? echo $i ?>" size="15" maxlength="15" value="<? echo $referencia ?>" class="textbox" onFocus="nextfield ='qtde<?echo $i?>'" onblur="javascript: FuncPesquisaPeca(window.document.frmpedido.referencia<? echo $i ?>,window.document.frmpedido.descricao<? echo $i ?>,window.document.frmpedido.qtde<? echo $i ?>);">
		<img src='imagens/btn_buscar5.gif' style="cursor:pointer" alt="Clique para pesquisar por referência do componente" border='0' hspace='5' align='absmiddle' onclick="javascript: FuncPesquisaPeca(window.document.frmpedido.referencia<? echo $i ?>,window.document.frmpedido.descricao<? echo $i ?>,window.document.frmpedido.qtde<? echo $i ?>);">
	</td>
	<td align='center'>
		<input type="text" name="descricao<? echo $i ?>" size="30" maxlength="30" value="<? echo $descricao ?>" class="textbox" onFocus="nextfield ='qtde<?echo $i?>'">
		<!--<img src='imagens/btn_buscar5.gif' style="cursor:pointer" alt="Clique para pesquisar por descrição do componente" border='0' hspace='5' align='absmiddle' onclick="javascript: fnc_pesquisa_peca_lista ('',window.document.frmpedido.referencia<? echo $i ?>,window.document.frmpedido.descricao<? echo $i ?>,'','descricao')">-->
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
		<!--
		<img src="imagens/gravar.gif" onclick="window.document.frmpedido.btngravar.value='1' ; frmpedido.submit() " >
		-->
		<a href="javascript: document.frmpedido.btngravar.value='Gravar' ; document.frmpedido.submit() ; "><img src='imagens/btn_gravar.gif' border='0'></a>
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
if (strlen ($cook_pedido) > 0) {
?>
<br>
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
	$sql = "SELECT	a.oid    ,
					a.*      ,
					referencia,
					descricao
			FROM	tbl_peca
			JOIN	(
						SELECT	oid,*
						FROM	tbl_pedido_item
						WHERE	pedido = $cook_pedido
					)
					a ON tbl_peca.peca = a.peca
					ORDER BY a.pedido_item";
	$res = pg_exec ($con,$sql);
	$total = 0;
	for ($i = 0 ; $i < @pg_numrows ($res) ; $i++) {

		$cor = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";

		echo "<tr bgcolor='$cor'>";
		echo "<td width='25%' align='left' class='table_line1' nowrap>";

		echo "<a href='$PHP_SELF?delete=" . pg_result ($res,$i,pedido_item) . "'>";

		echo "<img src='imagens/btn_excluir.gif' align='absmiddle' hspace='5' border='0'>";
		echo "</a>";
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
</tr>
</table>



<!-- ============================ Botoes de Acao ========================= -->


<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align='center'>
<?
/*
$sql = "SELECT	tbl_posicao_pedido.pedido_mfg ,
				to_char(tbl_posicao_pedido.data_pedido, 'DD/MM/YYYY') AS data_pedido,
				(
					SELECT	tbl_posicao_status.status
					FROM	tbl_posicao_status
					WHERE	tbl_posicao_pedido.pedido_mfg = tbl_posicao_status.pedido_mfg
					ORDER BY tbl_posicao_status.data_status DESC LIMIT 1
				) AS status
		FROM tbl_posicao_pedido
		WHERE	trim(tbl_posicao_pedido.codigo_posto) = '$posto'
		ORDER BY tbl_posicao_pedido.data_pedido DESC LIMIT 1";
*/
$sql = "SELECT	*
		FROM	tbl_status_pedido
		LEFT JOIN tbl_pedido USING (status_pedido)
		LEFT JOIN tbl_faturamento USING(pedido)
		WHERE	tbl_pedido.posto = $login_posto
		AND		tbl_pedido.fabrica = $login_fabrica
		AND		tbl_pedido.pedido = $cook_pedido
		AND		tbl_status_pedido.status_pedido IN (4,5)";
$res = @pg_exec ($con,$sql);

if (@pg_numrows($res) > 0){
	$link  = "javascript:PedidoPendente();";
	echo "
			<script>
			function PedidoPendente(){
				if(confirm('UNIFICAÇÃO DOS PEDIDOS.\\n\\nDeseja somar as pendências do pedido ".trim(pg_result($res,0,pedido_mfg))." neste novo pedido ?\\n\\nPara confirmar clique em \"OK\",\\ncaso contrário, clique em \"Cancelar\".') == true){
					window.location = '$PHP_SELF?finalizar=1&unificar=t&msg=1';
				}else{
					if(confirm('A pendência, após a finalização do seu novo pedido, será cancelada.\\n\\nConfirma a exclusão da pendência ?\\n\\nPara confirmar clique em \"OK\",\\ncaso contrário, clique em \"Cancelar\".') == true){
						window.location = '$PHP_SELF?finalizar=1&unificar=f&msg=2';
					}
				}
			}
			</script>\n";
}else{
	$link = "$PHP_SELF?finalizar=1&linha=$linha&unificar=t";
}

?>
		<br><a href="<? echo $link; ?>"><img src='imagens/btn_finalizar.gif' border='0'></a><br><br>
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
