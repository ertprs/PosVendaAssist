<? 
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
/*
if ($login_fabrica==24 and $login_posto <> 5044 
	and $login_posto <> 1838 and $login_posto <> 11591 
	and $login_posto <> 5551 and $login_posto <> 10745 
	and $login_posto <> 1317 and $login_posto <> 11722 
	and $login_posto <> 2252 and $login_posto <> 12114 
	and $login_posto <> 19386 and $login_posto <> 6359){
echo "<center><br><br><br><br><br><font face=VERDANA SIZE=4 COLOR=#FF000>ATENÇÃO POSTOS ATENDIMENTO DA FÁBRICA SUGGAR</font><br><br><br>
<font face=VERDANA size=2>Estaremos fazendo alguns ajustes em nossos cadastros <br>
para melhor atendê-los. A previsão de liberação do sistema <br>
para os Postos Autorizados é de 15 dias. <br>
Contamos com a costumeira compreensão.<br><br>
	Gerencia de Atendimento<br>
	Assistência Técnica </font>";
exit;
};*/

if($login_fabrica==1){
	$sql = "SELECT codigo_posto from black_tbl_posto where codigo_posto='$login_posto'";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)==0){
		include "posto_cadastro_atualiza.php";
		exit;
	}

}

##### VERIFICAÇÃO SE O POSTO JÁ LEU O COMUNICADO - INÍCIO #####
/*
$sql =	"SELECT tbl_comunicado.comunicado                                       ,
				tbl_comunicado.descricao                                        ,
				tbl_comunicado.mensagem                                         ,
				tbl_comunicado.extensao                                         ,
				TO_CHAR(tbl_comunicado.data,'DD/MM/YYYY') AS data               ,
				tbl_comunicado.produto                                          ,
				tbl_produto.referencia                    AS produto_referencia ,
				tbl_produto.descricao                     AS produto_descricao
		FROM tbl_comunicado
		LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado.produto
		LEFT JOIN tbl_comunicado_posto_blackedecker ON  tbl_comunicado_posto_blackedecker.comunicado = tbl_comunicado.comunicado
													AND tbl_comunicado_posto_blackedecker.fabrica    = $login_fabrica
													AND tbl_comunicado_posto_blackedecker.posto      = $login_posto
		WHERE tbl_comunicado.fabrica = $login_fabrica
		AND   tbl_comunicado.obrigatorio_site IS TRUE
		AND   tbl_comunicado_posto_blackedecker.posto IS NULL
		ORDER BY tbl_comunicado.data DESC;";
$res = pg_exec($con,$sql);
if (pg_numrows($res) > 0) {
	header("Location: comunicado_site.php");
	exit;
}
*/
##### VERIFICAÇÃO SE O POSTO JÁ LEU O COMUNICADO - FIM #####

##### Comunicados Mondial #####
# RETIRADO DIA 02/12/2005 12H - PEDIDO PELA FERNANDA
/*
if (strlen($_COOKIE["ComunicadoMondial20050929"]) == 0 AND $login_fabrica == 5) {
	header("Location: comunicado_mondial_20050929.php");
	exit;
}
*/

// 	$leio_depois = $_GET["leio_depois"];
// 	if($leio_depois=="1"){
// 		setcookie("leio_depois","1");
// /*		echo $_COOKIE["leio_depois"];*/
// 	}
##### Comunicados Britânia #####
setcookie("CookieNavegador", "Aceita");
if (strlen($_COOKIE["CookieNavegador"]) > 0) {
/*
if (strlen($_COOKIE["ComunicadoBritania"]) == 0 AND $login_fabrica == 3) {
	header("Location: comunicado_britania_20050719.php");
	exit;
}

if (strlen($_COOKIE["ComunicadoBritania20050923"]) == 0 AND $login_fabrica == 3) {
	header("Location: comunicado_britania_20050923.php");
	exit;
}
if (strlen($_COOKIE["ComunicadoBritania20060102"]) == 0 AND $login_fabrica == 3) {
	header("Location: comunicado_britania_20060102.php");
	exit;
}
*/
}




// Apaga cookie Acessa Extrato e a Tabela de Preços (intelbrass)
// Adicionado por Fabio 06/11/2006
setcookie("acessa_extrato", "NAO", time()-3600);
unset($_COOKIE["acessa_extrato"]);

setcookie("acessa_tabela_preco", "NAO", time()-3600);
//unset($_COOKIE['acessa_tabela_preco']);



/* PASSA PARÂMETRO PARA O CABEÇALHO (não esquecer ===========*/

/* $title = Aparece no sub-menu e no título do Browser ===== */
$title = "Telecontrol ASSIST - Gerenciamento de Assistência Técnica";

/* $layout_menu = Determina a aba em destaque do MENU ===== */
$layout_menu = 'os';


?>
<?


############# Leitura Obrigatória de Comunicados #############

$comunicado_lido = $_GET['comunicado_lido'];
if (strlen ($comunicado_lido) > 0) {
	$sql = "SELECT comunicado 
			FROM tbl_comunicado_posto_blackedecker 
			WHERE comunicado = $comunicado_lido
			AND   posto      = $login_posto";
	$res = @pg_exec ($con,$sql);

	if (pg_numrows($res) == 0){
		$sql = "INSERT INTO tbl_comunicado_posto_blackedecker (comunicado, posto, data_confirmacao) VALUES ($comunicado_lido, $login_posto, CURRENT_TIMESTAMP)";
	}else{
		$sql = "UPDATE tbl_comunicado_posto_blackedecker SET 
					data_confirmacao = CURRENT_TIMESTAMP 
				WHERE  comunicado = $comunicado_lido
				AND    posto      = $login_posto";
	}
	$res = @pg_exec ($con,$sql);

//busca o nome do posto e tipo do posto ================================================
	$sql = "SELECT nome
			FROM tbl_posto
			WHERE tbl_posto.posto =  $login_posto  ;";
			
	$res = @pg_exec ($con,$sql);
	$nome_posto  = pg_result ($res,0,nome);
//busca o nome do posto e o tipo do posto ==============================================


//funçao envia e-mail====================================================================

	$sql = "SELECT remetente_email, tbl_posto.nome , descricao 
			FROM tbl_comunicado JOIN tbl_posto USING (posto) 
			WHERE tbl_comunicado.comunicado = $comunicado_lido 
			AND tbl_comunicado.posto IS NOT NULL 
			AND tbl_comunicado.remetente_email IS NOT NULL";
	$res = pg_exec ($con,$sql);
	//quando é escolhido um unico posto será enviado o e-mail de confirmacao.
	if (pg_numrows ($res) == 1) {

		$remetente_email = pg_result ($res,0,remetente_email);
		$posto_nome      = pg_result ($res,0,nome);
		$descricao       = pg_result ($res,0,descricao);
		#----------- Enviar email de Confirmação de Leitura -----------#
		$assunto      = "Leitura de Comunicado";
		$corpo        = "O Posto $posto_nome leu o comunicado $descricao.";

		$email_origem = "Telecontrol Assist <suporte@telecontrol.com.br>";

		$body_top = "--Message-Boundary\n";
		$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
		$body_top .= "Content-transfer-encoding: 7BIT\n";
		$body_top .= "Content-description: Mail message body\n\n";

		if ( @mail($remetente_email, stripslashes($assunto), $corpo, "From: ".$email_origem." \n $body_top " ) ){
		}else{
			$msg_erro = "Não foi possível enviar o email. Por favor entre em contato com a TELECONTROL.";
		}
	}
}

//funçao envia e-mail====================================================================




$sql2 = "SELECT tbl_posto_fabrica.codigo_posto        ,
					tbl_posto_fabrica.tipo_posto       ,
					tbl_posto.estado
			FROM	tbl_posto
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
			AND     tbl_posto.posto   = $login_posto ";

	$res2 = pg_exec ($con,$sql2);

	if (pg_numrows ($res2) > 0) {
		$tipo_posto            = trim(pg_result($res2,0,tipo_posto));
		$estado                = trim(pg_result($res2,0,estado));//adicionado por raphael
		$estado = strtoupper($estado);
	}
	
	
$leio_depois = $_GET["leio_depois"];
if($leio_depois=="1"){
	setcookie("leio_depois","1");
/*		echo $_COOKIE["leio_depois"];*/
$leio_depois = $_COOKIE["leio_depois"];
header ("Location: login.php");
}
if($login_pais<>'BR' AND $login_fabrica == 20) setcookie("leio_depois","1");
if(strlen($_COOKIE["leio_depois"])==0  ){


	$sql = "SELECT  tbl_comunicado.comunicado   ,
					tbl_comunicado.descricao    ,
					tbl_comunicado.extensao     ,
					tbl_comunicado.mensagem     ,
					tbl_comunicado.tipo_posto   ,
					TO_CHAR (tbl_comunicado.data, 'DD/MM/YYYY')
			FROM   tbl_comunicado
			LEFT JOIN tbl_comunicado_posto_blackedecker ON tbl_comunicado.comunicado = tbl_comunicado_posto_blackedecker.comunicado AND tbl_comunicado_posto_blackedecker.posto = $login_posto
			WHERE  tbl_comunicado.fabrica = $login_fabrica
			AND    tbl_comunicado.obrigatorio_site 
			AND    (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
			AND    (tbl_comunicado.estado     = '$estado'    OR  tbl_comunicado.estado     IS NULL)
			AND    tbl_comunicado.data >= CURRENT_DATE - INTERVAL '30 days'
			AND    (tbl_comunicado.posto IS NULL OR tbl_comunicado.posto = $login_posto)
			AND    tbl_comunicado.ativo IS TRUE 
			AND    (tbl_comunicado_posto_blackedecker.data_confirmacao < CURRENT_DATE - INTERVAL '3 DAYS' 
				OR  tbl_comunicado_posto_blackedecker.data_confirmacao IS NULL )";
	
	$res = pg_exec ($con,$sql);
	
	#echo $_SERVER['REMOTE_ADDR'];
	
	if (pg_numrows ($res) > 0 ) {
		if($tipo_posto == null ){
		echo "tipo_posto";
		}
		echo "<br>
		<table style='font-family: verdana, arial ;  font-size: 16px; border-style: dotted; border-width: 2px; border-color: #330000; background-color: #FFFFFF;' width='600' border='0' align='center' cellpadding='0' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		echo "<TR class='Titulo'>";
		echo "	<TD colspan='2' align='center' nowrap height='30' ><FONT SIZE='3' COLOR='#FF0000'><B>Existem comunicados de leitura obrigatória!</B></FONT></TD>";
		echo "</TR>";
		echo "</table>";
		echo "<br>
			<table width='500' border='0' align='center' cellpadding='5' cellspacing='2' style='border-collapse: collapse' bordercolor='000000'>";
		echo "<TR align='center' bgcolor='#336699'>";
		echo "	<TD colspan='3' height='40' style='font-family: verdana, arial ; font-size: 16px; font-size: 14px; color:#FFFFFF;'><B>COMUNICADOS</B></TD>";
		echo "</TR>";
		echo "<TR align='center' style='font-family: verdana, arial ; font-size: 10px; color:#FFFFFF' bgcolor='#336699'>";
		echo "	<TD><B>Nr.</B></TD>";
		echo "	<TD><B>Descrição</B></TD>";
		echo "	<TD><B>Confirmação</B></TD>";
		echo "</TR>";
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$comunicado = pg_result ($res,$i,comunicado);
			$extensao   = pg_result ($res,$i,extensao);
			$descricao  = pg_result ($res,$i,descricao);
			$mensagem   = pg_result ($res,$i,mensagem);
			echo "<TR align='center' style='font-family: verdana, arial ; font-size: 10px; color:#000000' bgcolor='#C1E0FF' onmouseover=\"this.bgColor='#3399FF'\" onmouseout=\"this.bgColor='#C1E0FF'\">";
			echo "	<TD>$comunicado</TD>";
			
			//alterado por Wellington em 28/09/2006 - não tem arquivo nao linka
			if (strlen(trim($extensao)) > 0) {
				echo "	<TD><a href=\"javascript: window.open ('/assist/comunicados/$comunicado.$extensao','_blank', 'toolbar=no, status=no, scrollbars=yes, resizable=yes, width=700, height=500') ; window.location='$PHP_SELF?comunicado_lido=$comunicado' \" ><B>$descricao</B> <br></a> $mensagem</TD>";
			} else {
				echo "	<TD><B>$descricao</B> <br></a> $mensagem</TD>";
			}
			
			echo "	<TD nowrap><a href=\"javascript: window.location='$PHP_SELF?comunicado_lido=$comunicado' \" ><B>Já li e confirmo</b></TD>";
			echo "</TR>";
		}
		
		echo "</table>";
		echo "<br><table align='center'>";
		if(($login_fabrica==1) or ($login_fabrica==11)){
		echo "<TR>";
		echo "<TD align='center' colspan='3' nowrap><br><a href='$PHP_SELF?leio_depois=1'><FONT SIZE='4' COLOR='#880000'>Leio depois</FONT></a></TD>";
		echo "</TR>";
		}
		
	
		
		
		
		
		echo "<TR>";
		echo "<TD align='center' colspan='3' nowrap><br><FONT SIZE='2' COLOR='919597'>*Clique no(s) comunicado(s) para acessar o site.</FONT></TD>";
		echo "</TR>";
		echo "</table>";
	
		if($login_fabrica==1 and 1 == 2){
			$sql2 = "SELECT * FROM tbl_posto_fabrica WHERE fabrica = 1 AND posto = $login_posto AND codigo_posto IN ('56480' , '52198' , '23361' , '39075' , '12138' , '31351' , '22612' , '40598' , '55146' , '22631' , '32074' , '12137' , '40053' , '20341' , '23154' , '21972' , '26956' , '20920' , '23195' , '34135' , '56376' , '35021' , '38774' , '39874' , '39874' , '52047' , '56146' , '55142' , '14300' , '56016' , '10678' , '30052' , '23098' , '51738' , '21815' , '23183' , '51167' , '23350' , '12127' , '12045' , '26949' , '13076' , '10378' , '23111' , '36039' , '12031' , '43818' , '23155' , '42280' , '21139' , '35143' , '20165' , '23197' , '22588' , '32024' , '14426' , '23316' , '10341' , '43051' , '21350' , '23158' , '54024' , '30003' , '14017' , '54050' , '55122' , '37040' , '32022' , '50139' , '55143' , '21661' , '31057' , '23293' , '23544' , '31290' , '54056' , '21957' , '20398' , '26963' , '23225' , '14162' , '51735' , '12016' , '55241' , '31141' , '36888' , '21914' , '53130' , '40921' , '50087' , '40049' , '53441' , '40113' , '41066' , '41176' , '41683' , '23184' , '20312' , '23019' , '10698' , '38034' , '37583' , '13516' , '20653' , '12009' , '56047' , '36033' , '36307' , '20403' , '15111' , '10358' , '12129' , '12129' , '22439' , '40097' , '53175' , '52051' , '42125' , '14135' , '23387' , '51771' , '26962' , '15199' , '58108' , '42385' , '31395' , '20331' , '40052' , '15034' , '56463' , '57873' , '27907' , '12027' , '23167' , '20072' , '51782' , '51782' , '52043' , '33018' , '31129' , '40082' , '41694' , '10177' , '10240' , '12058' , '21149' , '29060' , '22710' , '22007' , '24348' , '24394' , '20370' , '23925' , '55036' , '30048' , '30002' , '38021' , '15097' , '15113' , '15101' , '14675' , '35053' , '56368' , '57972' , '56089' , '13812' , '13695' , '13786' , '13155' , '13150' , '13161' , '14166' , '13270' , '13632' , '13201' , '14228' , '13072' , '13072' , '13715' , '13610' , '13610' , '56305' , '56198' , '56876' , '56875' , '51097' , '23513' , '51168' , '23511' , '26946' , '23915' , '50061' , '23642' , '52028' , '23163' , '23163' , '24142' , '26942' , '12115' , '51734' , '57602' , '12124' , '12124' , '51774' , '52006' , '52200' , '52208' , '54004' , '53155' , '53446' , '54006' , '53463' , '53129' , '36788' , '39545' , '39692' , '39730' , '39870' , '32027' , '31310' , '32029' , '32007' , '31021' , '40444' , '40399' , '40979' , '40031' , '40143' , '40885' , '43027' , '10844' , '11228' , '11529' , '13114' , '12004' , '10345' , '10086' , '10120' , '11290' , '10843' , '10674' , '10068' , '10665' , '10454' , '11399' , '11245' , '12059' , '33009' , '33029' , '55170' , '55200' , '55155' , '55153' , '55159' , '36778' , '42308' , '43743' , '41086' , '41280' , '42031' , '41272' , '41472' , '41221' , '43779' , '41665' , '15045' , '16001' , '22625' , '22632' , '57215' , '20763' , '23980' , '58774' , '29057' , '29062' , '22627' , '24367' , '22585' , '22461' , '22049' , '23194' , '22457' , '29078' , '29067' , '29079' , '29083' , '20439' , '23193' , '21217' , '22720' , '21302' , '57432' , '21272' , '21292' , '21436' , '20621' , '20336' , '22624' , '21464' , '20285' , '99065' , '22530' , '21215' , '21565' , '20741' , '22670' , '21551' , '22626' , '21150' , '22116' , '29066' , '22497' , '21163' , '50004' , '53440' , '24327' , '20998' , '51787' , '48838' , '54089' , '52055' , '21870' , '26953' , '39791' , '40077' , '23135' , '22086' , '26943' , '21362' , '21351' , '23189' , '20274' , '40907' , '42124' , '43745' , '58036' , '14189' , '26934' , '12019' , '23227' , '23215' , '23241' , '23331' , '52021' , '41309' , '42076' , '51092' , '14717' , '13077' , '14187' , '26957' , '45889' , '23374' , '45126' , '45127' , '23156' , '11147' , '12008' , '12010' , '12011' , '12012' , '12012' , '12017' , '12120' , '13014' , '13088' , '13136' , '13635' , '14246' , '14315' , '15007' , '15022' , '15035' , '15046' , '20034' , '20104' , '20208' , '20322' , '20332' , '20333' , '20345' , '20376' , '21061' , '21801' , '22002' , '22010' , '23160' , '23283' , '23284' , '23290' , '23340' , '23345' , '23921' , '24212' , '24567' , '26948' , '29065' , '32008' , '32057' , '33034' , '34022' , '34087' , '35066' , '36086' , '39512' , '39600' , '39600' , '40092' , '40092' , '40305' , '42270' , '43380' , '45007' , '54020' , '54025' , '54029' , '54032' , '54037' , '54038' , '54049' , '56097' , '56267' , '57136' , '57719' , '57759' , '57779' , '57874' , '58022' , '58142' , '58219' , '58329' , '58558' , '45010' , '13053' , '13031' , '56153' , '20223' , '26954' , '58262' , '55270' , '15024' , '23298' , '32085' , '14049' , '15048' , '13128' , '55026' , '22689' , '20383' , '58274' , '23354' , '51199' , '23382' , '99243' , '23355' , '58267' , '26960' , '40007' , '32058' , '58876' , '13109' , '13074' , '13074' , '13515' , '23292' , '20346' , '15026' , '13008' , '23364' , '33027' , '22893' , '23358' , '15031' , '38036' , '57582' , '26950' , '57693' , '22426' , '23359' , '23360' , '26955' , '26955' , '12015' , '23150' , '38038' , '42362' , '58395' , '58815' , '57599' , '14975' , '14975' , '23510' , '31855' , '14055' , '56176' , '38039' , '10097' , '14048' , '58839' , '58841' , '58414' , '22514' , '36107' , '42375' , '15047' , '40563' , '42376' , '12030' , '13035' , '23368' , '23369' , '15037' , '22941' , '26958' , '42408' , '56977' , '37043' , '23371' , '23388' , '43201' , '15036' , '23380' , '42409' , '08481' , '42297' , '42297' , '44003' , '23372' , '23373' , '26961' , '57299' , '55874' , '20443' , '58879' , '10121' , '58423' , '41979' , '23381' , '42404' , '20417' , '21480' , '23440' , '20670' , '42450' , '15040' , '55049' , '58589' , '23413' , '52178' , '43244' , '01122')";
			$res2 = pg_exec ($con,$sql2);
			if (pg_numrows ($res2) > 0) {
				echo "<table width='500' border='0' align='center' cellpadding='5' cellspacing='2' style='border-collapse: collapse' bordercolor='000000'>";
				echo "<TR align='center' bgcolor='#336699'>";
				echo "	<TD colspan='3' height='40' style='font-family: verdana, arial ; font-size: 16px; font-size: 14px; color:#FFFFFF;'><B>COMUNICADOS BLACK & DECKER</B></TD>";
				echo "<TR align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000' bgcolor='#C1E0FF' onmouseover=\"this.bgColor='#3399FF'\" onmouseout=\"this.bgColor='#C1E0FF'\">";
				echo "	<TD>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Devido ao nosso inventário anual, informamos que o fechamento correspondente ao mês de DEZEMBRO será no dia 14/12/06. Receberemos pedidos de peças e acessórios até esta data e, os pedidos serão enviados para a fábrica no horário padrão, às 13h30.<br><br>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Desta forma, de 14/12 (após as 13h30) até 25/12/06, o site ficará travado para a digitação. Neste período a Black & Decker não receberá pedidos e não emitirá nota fiscal, ou seja, não haverá nenhuma movimentação no estoque, por se tratar do nosso período de inventário na fábrica. Por isso é de suma importância que seja feita uma programação de peças com base neste período que a fábrica ficará sem atender, principalmente para as peças de maior giro.<br><br>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;O faturamento retornará no dia 26/12/06, neste dia o site estará liberado para a digitação de peças à partir das 08hs.
			Qualquer dúvida estamos à disposição.<br><br>
			Atenciosamente.<br>
			Silvania Alves<br>
			Black & Decker do Brasil</TD>";
			echo "</TR>";
			echo "</table>";
			}
		}
	exit;
	}
}
#############################################



############# +++INICIO+++ AVISO DE OS COM INTERVENCAO DA FABRICA - fabio 24/01/2007
if(strlen($_GET["aviso_os"])==0 AND $login_fabrica==3){
	$sql =  "SELECT  DISTINCT tbl_os.os,
						tbl_os.sua_os,
						(SELECT status_os FROM tbl_os_status WHERE tbl_os.os = tbl_os_status.os ORDER BY data DESC LIMIT 1) AS status_os,
						tbl_os_retorno.nota_fiscal_envio AS envio,
						tbl_os_retorno.nota_fiscal_retorno AS retorno,
						tbl_os_retorno.retorno_chegada AS confirmacao_retorno
				FROM tbl_os
				JOIN tbl_os_status USING(os) 
				JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto AND tbl_posto.posto=$login_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_os_retorno USING(os)
				WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_os_status.status_os = 65
				";
				// 65 - Produto deve ser enviado a fábrica
	$res = pg_exec ($con,$sql);
	$qtde = pg_numrows($res);
	$msg_aviso ="";
	$os_com_inter ="";
	$os_com_inter_ass ="";
	if ($qtde>0){
		for ($i=0;$i<$qtde;$i++){
			$test_os       	= pg_result($res,$i,os);
			$test_sua_os     = pg_result($res,$i,sua_os);
			$test_status      	= pg_result($res,$i,status_os);
			$test_envio      	= trim(pg_result($res,$i,envio));
			$test_retorno   	= trim(pg_result($res,$i,retorno));
			$test_confirm   	= trim(pg_result($res,$i,confirmacao_retorno));
			if ($test_status=="65"){
				if (strlen($test_envio)==0){
					$tmp=" Este produto necessita de reparo na Assitência Técnica da Fábrica. Por favor, envie o produto desta OS à fábrica. Consulte esta OS para saber como proceder. Caso tenha sido enviado, preencha os dados do envio na consulta de OS's";
				}
				elseif (strlen($test_retorno)>0 AND strlen($test_confirm)==0){
						$tmp=" A fábrica retornou o produto desta OS para seu posto. Por favor, confirme seu recebimento na consulta de OS";
				}
				if (strlen($tmp)>0){
					$os_com_inter_ass .= "OS <b>$test_sua_os</b> : $tmp<br><br> ";
				}
			}
	//		$os_com_inter_ass = substr($os_com_inter_ass, 0, (strlen($os_com_inter_ass)-1));
		}
		if (strlen($os_com_inter)>0 OR strlen($os_com_inter_ass)>0){
			$msg_aviso .= "<br><br><center><div style='width:400px;font-family: verdana, arial;border:1px solid #cecece;padding:30px'>";
			$msg_aviso .= "<h3 style='background-color:#dfdfdf;color:#000;align:center;border:1px solid #666'>ATENÇÃO</h3>";
			if (strlen($os_com_inter)>0){
				$msg_aviso .= "<p style='font-size:12px;text-align:left'>A(s) OS's <b>$os_com_inter</b> está(ão) com intervenção da fábrica. No momento o seu posto não poderá lançar mais itens nem fecha-la.<br> <b>Aguarde a fábrica entrar em contato</b></p>";
			}
			if (strlen($os_com_inter_ass)>0){
				$msg_aviso .= "<p style='font-size:12px;text-align:left'><b style='color:red;text-align:center'>LEITURA OBRIGATÓRIA</b><BR><BR>$os_com_inter_ass </p>";
			}
			$msg_aviso .= "<br><br><a href='$PHP_SELF?aviso_os=1' style='font-size:12px'>Clique aqui para continuar</a></p>";
			$msg_aviso .= "</div></center>";
			echo $msg_aviso;
			exit;
		}
	}
}
#############  +++ FIM+++ AVISO DE OS COM INTERVENCAO DA FABRICA


if(1==1) {
header ("Location: menu_inicial.php");
}
include 'cabecalho_login.php';





?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #000000;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
</style>

<hr>
<h1><? echo $login_nome ?></h1>
<?
	echo "<table width='600' align='center' border='0' align='center'>";
	echo "<tr>";
	echo "<td align='center'>"

?>


<?

//alterado por takashi 05/07/2006 segundo chamado 133 
//insere qtdade de dias para descredenciamento
	if (trim($login_credenciamento) == "EM DESCREDENCIAMENTO"){
	$sql = "SELECT	tbl_credenciamento.status,
					tbl_credenciamento.dias  ,
					tbl_credenciamento.texto ,
					to_char(tbl_credenciamento.data,'YYYY-MM-DD') AS data,
					tbl_posto.nome
			FROM	tbl_credenciamento
			JOIN    tbl_posto ON tbl_posto.posto = tbl_credenciamento.posto
			WHERE	tbl_credenciamento.fabrica = $login_fabrica
			AND		tbl_credenciamento.posto   = $login_posto
			ORDER BY tbl_credenciamento.credenciamento DESC LIMIT 1";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0){
		$status      = pg_result($res,0,status);
		$xdias        = pg_result($res,0,dias);
		$data_geracao= pg_result($res,0,data);
		$xtexto       = pg_result($res,0,texto);
		$posto_nome  = pg_result($res,0,nome);

		if ($status == 'EM CREDENCIAMENTO' OR $status == 'EM DESCREDENCIAMENTO'){
			
			$sqlX = "SELECT '$data_geracao':: date + interval '$xdias days';";
			$resX = pg_exec ($con,$sqlX);
			$dt_expira = pg_result ($resX,0,0);

			$sqlX = "SELECT '$dt_expira'::date - current_date;";
			$resX = pg_exec ($con,$sqlX);

			$dt_expira = substr ($dt_expira,8,2) . "-" . substr ($dt_expira,5,2) . "-" . substr ($dt_expira,0,4);
			$dia_hoje= pg_result ($resX,0,0);

			echo "<div class='error'></div>";

			echo "<table width='100%' align='center' border='0' cellpadding='3' cellspacing='3'>";
			echo "<tr><td bgcolor='#ff0000' align='center'><font color='#FFFFFF'><B>$login_credenciamento</B> - ATÉ O DIA $dt_expira (RESTAM $dia_hoje DIAS)</font></td></tr>";
			echo "</table>";
		}
	}
//alterado por takashi 05/07/2006 segundo chamado 133 ---FIM
//insere qtdade de dias para descredenciamento  ---FIM
}
?>


<?
/* utilizado ate 05/07/2006 alterado por takashi
	if (trim($login_credenciamento) == "EM DESCREDENCIAMENTO"){echo "<div class='error'>$login_credenciamento</div>";}
*/
?>

<!-- AQUI VAI INSERIDO OS RELATÓRIOS E OS FORMS -->



<!--
<br>
<center>
<img src='imagens/embratel_logo.gif' valign='absmiddle'>
<br>
<font color='#330066'><b>Concluída migração para EMBRATEL</b>.</font>
<br>
<font size='-1'>
A <b>Telecontrol</b> agradece sua compreensão.
<br>Agora com a migração para o iDC EMBRATEL teremos
<br>um site mais veloz, robusto e confiável.
</font>
<p>
</center>
-->

<?


$sql = "SELECT estado FROM tbl_posto WHERE posto = $login_posto";
$res = pg_exec($con,$sql);
$estado = trim(pg_result($res,0,estado));
	
echo "<table border='1'>";
echo "<tr>";
if ($login_fabrica == 3){
	if ($estado == 'RS' OR $estado == 'SC' OR $estado == 'PR') {
		echo "<td bgcolor='#FF0000'>";
		echo "<b><font size='-1'>Pagamento Direto de Mão-de-Obra</font></b>";
		echo "<p>";
		echo "<font size='-1'>O pagamento da mão-de-obra <br> será feito diretamente pela BRITÂNIA. <BR> <A HREF='/assist/comunicados/britania-mobra-direta.html' target='_blank'><font color='#660000'>Clique aqui para saber mais.</font></a></font>";
		echo "</td>";
	}

	if ($estado == 'SP' OR $estado == 'MA' OR $estado == 'PA' OR $estado == 'AC' OR $estado == 'AM' OR $estado == 'TO') {
		echo "<td bgcolor='#FF0000'>";
		echo "<b><font size='-1'>Pagamento Direto de Mão-de-Obra</font></b>";
		echo "<p>";
		echo "<font size='-1'>O pagamento da mão-de-obra <br> será feito diretamente pela BRITÂNIA. <BR> <A HREF='/assist/comunicados/britania-mobra-direta-eletro.html' target='_blank'><font color='#660000'>Clique aqui para saber mais.</font></a></font>";
		echo "</td>";
	}
}
echo "<td>";
#------------------------ Média de Peças por OS   e  Custo Médio por OS --------------
include "custo_medio_include.php";
echo "</td>";

echo "</tr>";
echo "</table>";

?>


<div id="container"><h2><IMG SRC="imagens/bemVindo<? echo $login_fabrica_nome ?>.gif" ALT="Bem-Vindo!!!"></h2></div>
<?
/*
##### VERIFICAÇÃO PARA OS COM PEÇA PARA PREVISÃO DE ENTREGA #####
$sql =	"SELECT COUNT(tbl_os.sua_os) AS qtde_os
		FROM   tbl_os
		JOIN   tbl_os_produto    ON  tbl_os_produto.os       = tbl_os.os
		JOIN   tbl_os_item       ON  tbl_os_item.os_produto  = tbl_os_produto.os_produto
		JOIN   tbl_peca          ON  tbl_peca.peca           = tbl_os_item.peca
		JOIN   tbl_produto       ON  tbl_produto.produto     = tbl_os.produto
		WHERE  tbl_os.fabrica = $login_fabrica
		AND    tbl_os.posto           = $login_posto
		AND    tbl_peca.previsao_entrega > date(current_date + INTERVAL '20 days')
		AND    tbl_os.finalizada ISNULL ;";
$res = pg_exec($con,$sql);
if (pg_numrows($res) > 0) {
	if (pg_result($res,0,0) > 0) {
		echo "<div id='mainCol'><div class='contentBlockLeft' style='background-color: #FFE1E1;'>";
		echo "<img src='imagens/esclamachion1.gif'><a href='os_peca_previsao_entrega.php'>Confira as OS com peça para previsão de entrega maior que 20 dias.<br> Foram encontradas ".pg_result($res,0,0)." OS.</a>";
		echo "</div></div>";
	}
}
*/


#-------------- Validação Periódica de EMAIL -------------------

$sql = "SELECT tbl_posto.email, nome, tbl_posto.email_validado
		FROM tbl_posto
		WHERE tbl_posto.posto =  $login_posto
		AND ( email_enviado IS NULL  OR email_enviado  < CURRENT_DATE - INTERVAL '1 days' )
		AND ( email_validado IS NULL OR email_validado < CURRENT_DATE - INTERVAL '30 days')";
$res = @pg_exec ($con,$sql);
if (@pg_numrows($res) > 0) {
	$nome  = pg_result ($res,0,nome);
	$email = trim(pg_result ($res,0,email));

	echo "<form name='frm_email' method='post' action='email_altera_envia.php' target='_blank'>";
	echo "<input type='hidden' name='btn_acao'>";
	echo "<fieldset style='border-color: 00CCFF;'>";
	echo "<legend align='center' style='background-color:#3399FF ; border:1px solid #036; ' width='90%' align='center'><font face='Arial, Helvetica, sans-serif' size='+1' color='#ffffff'> Verificação Obrigatória de Email </font> </legend>";
	echo "<br>";
	echo "<center>";
	echo "<font color='#000000' size='2'>Por favor confirme seu endereço de EMAIL no campo abaixo, e <b><i>clique em CONTINUAR</i></b>.<br>Em seguida <b><i>será enviado um email</i></b> para sua caixa de mensagens vindo<b><i> de verificacao@telecontrol.com.br</i></b>, com o <b><i>assunto Verificação de Email</i></b>, e dentro dele <b><i>existe um link que você deve clicar</i></b> para efetuar a operação de atualização e verificação do email.</font><br><br>";
	echo "Email: <input type='text' name='email' size='50' maxlength='50' value='$email'>";
	echo "&nbsp;&nbsp;";
	echo "<img border='0' src='imagens/btn_continuar.gif' align='absmiddle' onclick='document.frm_email.submit(); window.location.reload( true ); ' style='cursor: hand' alt='Atualiar email'>";
	echo "<br><br>";
	echo "</center>";
	echo "</fieldset>";
	echo "</form>";
	echo "<p>";
}





#----------------- Página de informativos ----------------

switch (trim ($login_fabrica_nome)) {

	case "Dynacom":
		include "news_dynacom.php";
	break;

	case "Britania":
	    /*
		$sql = "SELECT COUNT(*) FROM tbl_posto JOIN tbl_posto_linha ON tbl_posto.posto = tbl_posto_linha.posto AND tbl_posto_linha.linha IN (2,4) WHERE tbl_posto.estado = 'SP' AND tbl_posto.posto = $login_posto";
		$res = pg_exec ($con,$sql);
		$qtde = pg_result ($res,0,0);
		if ($qtde > 0) {
			echo "<font face='arial' size='+1'>A <b>TELECONTROL</b> é seu novo Distribuidor de Peças BRITÂNIA <br>para as linhas de Eletro Portáteis e Linha Branca</font>";
			echo "<p>";
			echo "<font face='arial' size='-1'>Entre em contato conosco pelo email <a href='mailto:distribuidor@telecontrol.com.br'>distribuidor@telecontrol.com.br</a> <br>ou pelo MSN, usando este mesmo endereço de email. <br> Telefone (14) 3433-9009 </font>";

			echo "<p>";
		}
	    */
		include "news_britania.php";
#		echo "<script language='javascript'>window.open ('britania_informativo_2.html','popup2','toolbar=no, location=no, status=nos, scrollbars=no, directories=no, width=300, height=300, top=50, left=100') ; </script>";
	break;

	case "Meteor":
		include "news_meteor.php";
	break;

	case "Mondial":
		include "news_mondial.php";
	break;

	case "Tectoy":
		include "news_tectoy.php";
	break;

	case "Ibratele":
		include "news_ibratele.php";
	break;

	case "Filizola":
		include "news_filizola.php";
	break;

	case "Telecontrol":
		include "news_telecontrol.php";
	break;

	case "Lenoxx":
		include "news_lenoxx.php";
	break;

	case "Intelbras":
		include "news_intelbras.php";
	break;

	case "BlackeDecker":
		include "news_blackdecker.php";
	break;

	case "Latina":
		include "news_latina.php";
	break;
	
	case "Bosch":
		include "news_bosch.php";
	break;

	case "Lorenzetti":
		include "news_lorenzetti.php";
	break;
}

	echo "</td>";
	echo "</tr>";
	echo "</table>";

include "rodape.php";
?>
