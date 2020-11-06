<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";
include "funcoes.php";
include "helpdesk.inc.php";
include_once __DIR__ . '/class/AuditorLog.php';
include_once './class/communicator.class.php';
include_once './email_pedido.php';

if($login_fabrica <> 1){
	header("Location: pedido_cadastro.php");
}

if (isset($_GET['verifica_peca_lista_basica'])) {

	$peca    = $_GET['peca_referencia'];
	$produto = $_GET['produto'];

	$query = "SELECT lb.peca
			  FROM tbl_lista_basica lb
			  JOIN tbl_peca p ON (p.peca = lb.peca)
			  WHERE lb.fabrica = $login_fabrica
			  AND p.referencia = '{$peca}'
			  AND lb.produto   = {$produto}";

	$res = pg_query($con, $query);

	$pecaRes = pg_fetch_result($res, 0, 'peca');

	if (strlen($pecaRes) > 0) {

		exit(json_encode(['msg' => "success"]));	
	}
	
	exit(json_encode(['msg' => 'error']));
}

if(isset($_GET["verificaDemanda"])){

	$pedido = $_GET["pedido"];
	$sql_qtde_pecas = "
        SELECT tbl_pedido_item.peca,
	           tbl_peca.parametros_adicionais,
	           tbl_pedido_item.qtde,
	           tbl_pedido_item.data_item,
	           tbl_pedido_item.pedido_item
	    FROM tbl_pedido_item
	    INNER JOIN tbl_pedido ON tbl_pedido.pedido = tbl_pedido_item.pedido
	    INNER JOIN tbl_peca   ON tbl_peca.peca     = tbl_pedido_item.peca
	         WHERE tbl_pedido_item.pedido = $pedido
	           AND JSON_FIELD('qtde_demanda', tbl_peca.parametros_adicionais) IS NOT NULL
	           AND JSON_FIELD('qtde_demanda', tbl_peca.parametros_adicionais) ~'\\d'
	           AND JSON_FIELD('qtde_demanda', tbl_peca.parametros_adicionais)::NUMERIC < tbl_pedido_item.qtde
	      ORDER BY data_item";
	    $res_qtde_pecas = pg_query($con, $sql_qtde_pecas);

	    $rQtPc = pg_num_rows($res_qtde_pecas);
	    $motivo_demanda = false;
	    $pedidos_itens = [];

	    for ($i = 0; $i < $rQtPc; $i++) {
	        $parametros_adicionais = pg_fetch_result($res_qtde_pecas, $i, 'parametros_adicionais');
	        $qtde                  = pg_fetch_result($res_qtde_pecas, $i, 'qtde');
	        $data_item             = pg_fetch_result($res_qtde_pecas, $i, 'data_item');
	        $parametros_adicionais = json_decode($parametros_adicionais, true);
	        $qtde_demanda          = $parametros_adicionais['qtde_demanda'];

	        if($qtde <= 3){
	        	continue;
	        }else{
	        	$motivo_demanda = true;
	        	$pedidos_itens[] = pg_fetch_result($res_qtde_pecas, $i, 'pedido_item');
	        }
	    }
	    echo json_encode(array("motivo_demanda" => $motivo_demanda, "pedidos_itens" => $pedidos_itens));	
	exit;
}


if($login_fabrica == 1){
	$reabrir 	= $_GET["reabrir"];
	$pedido 	= (int)$_GET["pedido"];
	if($reabrir == "sim"){
		$sql_reabrir = "UPDATE tbl_pedido SET finalizado = null WHERE PEDIDO = $pedido";
		$res_reabrir = pg_query($con, $sql_reabrir);
	}
}

if($_POST['btn_recalcula'] == 'Recalcular') {
}

if($_GET['valida_multiplo'] == 'sim'){ //hd_chamado=2543280
	$peca = $_GET['peca'];
	$qtde_antiga = $_GET['qtde'];

	$sqlMultiplo = "SELECT tbl_peca.multiplo
						FROM tbl_peca
						WHERE fabrica = $login_fabrica
						AND (referencia = '$peca' or referencia_pesquisa='$peca') ";
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

if (file_exists("bloqueio_pedidos/bloqueia_pedido_black.txt")) {
	$abrir = fopen("bloqueio_pedidos/bloqueia_pedido_black.txt", "r");
	$ler = fread($abrir, filesize("bloqueio_pedidos/bloqueia_pedido_black.txt"));

	fclose($abrir);
	$conteudo = explode(";;", $ler);

	$data_inicio = $conteudo[0];
	$data_fim    = $conteudo[1];
	$comentario  = $conteudo[3];
}

$res_bloqueio = pg_query(
	$con,
	"SELECT desbloqueio
	   FROM tbl_posto_bloqueio
	  WHERE posto   = $login_posto
	    AND fabrica = $login_fabrica
	    AND pedido_faturado
	  ORDER BY data_input DESC LIMIT 1"
);

if (pg_num_rows($res_bloqueio)>0) {
	$bloqueio_pedido = $desbloqueio = pg_fetch_result($res_bloqueio, 0, 'desbloqueio');
}
$posto_desbloqueado = ($desbloqueio != 'f');
$uploadExcelData = FALSE;

if(isset($_POST['upload_excel'])){

	$arquivo = $_FILES['arquivo_excel'];

	$produto_id         = $_POST['produto_excel'];      	
	$produto_serie      = $_POST['serie_excel'];     
	$produto_referencia = $_POST['referencia_excel'];   
	$produto_descricao  = $_POST['descricao_excel'];    
	$produto_voltagem   = $_POST['voltagem_excel'];    
	$produto_tipo       = $_POST['tipo_excel'];    
	$nota_fiscal        = $_POST['nota_fiscal_excel'] ;      
	$data_emissao       = $_POST['data_emissao_excel'];

	$uploadExcelData = TRUE;

	$queryCatPosto = "SELECT categoria, parametros_adicionais
				      FROM tbl_posto_fabrica
				      WHERE posto = $login_posto
				      AND fabrica = $login_fabrica";

	$resCatPosto = pg_query($con, $queryCatPosto);

	$categoriaPosto = pg_fetch_result($resCatPosto, 0, 'categoria');
	$parametros_adicionais = pg_fetch_result($resCatPosto, 0, 'parametros_adicionais');
	$parametros_adicionais = json_decode($parametros_adicionais);
	
	$pedido_faturado_locadora = True;

	if (isset($parametros_adicionais->pedido_faturado_locadora) && $parametros_adicionais->pedido_faturado_locadora == 't') {
			
		$pedido_faturado_locadora = False;
	}  

	if (in_array($login_fabrica, [1]) && $categoriaPosto == 'Locadora' && strlen($produto_serie) == 0 && $pedido_faturado_locadora) {
		
		$msg_erro = "É preciso específicar o produto para realizar o Upload do aquivo";

	} else { 

		if($arquivo['size'] > 0){
		$file_ext = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
			if (strpos($file_ext, 'xls') !== false) {
				if($arquivo['size'] < 2000000){

					system ("mkdir /tmp/blackedecker/pedido-excel/ 2> /dev/null ; chmod 777 /tmp/blackedecker/pedido-excel/" );

					$origem = $arquivo['tmp_name'];
					$destino = "/tmp/blackedecker/pedido-excel/".date("dmYHis").$arquivo['name'];
					if(move_uploaded_file($origem, $destino)){

						require_once './admin/xls_reader.php';
						$data = new Spreadsheet_Excel_Reader();
						$data->setOutputEncoding('CP1251');
						$data->read($destino);

						$pecas_pedido_excel = "";

						for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) {
							$peca  = "";
							$qtd   = "";

							for ($j = 1; $j <= $data->sheets[0]['numCols']; $j++) {

								if($data->sheets[0]['numCols'] <> 2) {
									$msg_erro = "Por favor, verificar o conteúdo de Excel, está faltando/sobrando algumas colunas";
								}

								if (in_array($login_fabrica, [1]) && $categoriaPosto == 'Locadora') {

									$referenciaPeca = $data->sheets[0]['cells'][$i];

									$query = "SELECT lb.peca, lb.qtde
											  FROM tbl_lista_basica lb
											  JOIN tbl_peca p ON (p.peca = lb.peca)
											  WHERE lb.fabrica = $login_fabrica
											  AND p.referencia = '{$referenciaPeca[1]}'
											  AND lb.produto = {$produto_id}";

									$res = pg_query($con, $query);

									if (pg_num_rows($res) == 0) {
											
										$naoAdicionado[$referenciaPeca[1]] = $referenciaPeca[2];
									}
								}

								switch($j) {
									case 1: $peca = $data->sheets[0]['cells'][$i][$j]; break;
									case 2: $qtd = $data->sheets[0]['cells'][$i][$j]; break;
								}

							}

							if (!isset($naoAdicionado[$data->sheets[0]['cells'][$i][1]])) {
								$pecas_pedido_excel .= $peca." / ".$qtd." |";
							}

						}

						$pecas_pedido_excel = substr($pecas_pedido_excel, 0, strlen($pecas_pedido_excel)-1);
						$rows_pecas = $data->sheets[0]['numRows'] - count($naoAdicionado);
					}else {
						$msg_erro = "Não foi possível realizar o Upload do aquivo";
					}

				}else{
					$msg_erro = "Arquivo com mais de 2 Mb";
				}

			}else{
				$msg_erro = "Arquivo Inválido";
			}

		}else{
			$msg_erro = "Por favor envie um arquivo XLS";
		}
	}

}

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
    $cond2 = $_POST['condicao'];
} 

if($login_fabrica == 3) setcookie ("cook_pedido",$_GET["pedido"],time()+(3600*48));

$sql = "SELECT current_timestamp as agora" ; 
$res = pg_query($con, $sql); 
$agora = pg_fetch_result($res, 0, 'agora'); 

if (strval(strtotime("$agora")) < strval(strtotime("$data_fim"))) { // DATA DA VOLTA
	if (strval(strtotime("$agora"))  >= strval(strtotime("$data_inicio"))) { // DATA DO BLOQUEIO
		$title     = "Pedido de Peças";
		$cabecalho = "Pedido de Peças";
		$layout_menu = 'pedido';


			if($login_posto <>6359 ) {
				include "cabecalho.php";

				echo "<br><br>\n";
				echo "<table width='700' border='0' align='center' cellpadding='5' cellspacing='2' style='border-collapse: collapse' bordercolor='000000'>";
				echo "<TR align='center' bgcolor='#336699'>";
				echo "<form method='post' name='frm_condicao' action='$PHP_SELF'>";
				echo "	<TD colspan='3' height='40' style='font-family: verdana, arial ; font-size: 16px; font-size: 14px; color:#FFFFFF;'><B>COMUNICADO BLACK & DECKER</B></TD>";
				echo "<TR align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000' bgcolor='#C1E0FF' onmouseout=\"this.bgColor='#C1E0FF'\">";
				echo "<TD><p align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000'>$comentario</p></TD>";

				echo "</form>";
				echo "</TR>";
				echo "</table>";
				include "rodape.php";
				exit;
			}
	}
}

$sql = "SELECT  tbl_posto_fabrica.codigo_posto      ,
				tbl_posto_fabrica.tipo_posto        ,
				tbl_posto_fabrica.pedido_faturado   ,
				tbl_posto_fabrica.pedido_em_garantia,
				tbl_posto_fabrica.escolhe_condicao  ,
				tbl_posto_fabrica.condicao_escolhida,
				tbl_posto.cnpj                      ,
				tbl_posto.ie                        ,
				tbl_posto.nome                      ,
				tbl_posto.estado
		FROM    tbl_posto_fabrica
		JOIN    tbl_posto USING(posto)
		WHERE   tbl_posto_fabrica.posto   = $login_posto
		AND     tbl_posto_fabrica.fabrica = $login_fabrica";
$res_posto = @pg_query ($con,$sql);

if (@pg_num_rows ($res_posto) == 0 OR strlen (trim (pg_errormessage($con))) > 0 ) {
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
$escolhe_condicao   = trim(pg_fetch_result ($res_posto,0,escolhe_condicao));
$condicao_escolhida = trim(pg_fetch_result ($res_posto,0,condicao_escolhida));

/* HD 23738 */
if ($escolhe_condicao == 't' AND $condicao_escolhida == "") {
	header ("Location: pedido_condicao.php");
	exit;
}

/* HD 23738 */
if ($escolhe_condicao == 't' AND $condicao_escolhida == "f") {
		$title     = "Pedido de Peças";
		$cabecalho = "Pedido de Peças";
		$layout_menu = 'pedido';

		include "cabecalho.php";

		echo "<br><br>\n";
		echo "<table width='520' border='0' align='center' cellpadding='5' cellspacing='2' style='border-collapse: collapse' bordercolor='000000'>";
		echo "<TR align='center' bgcolor='#336699'>";
		echo "	<TD colspan='3' height='40' style='font-family: verdana, arial ; font-size: 16px; font-size: 14px; color:#FFFFFF;'><B>CONDIÇÃO DE PAGAMENTO</B></TD>";
		echo "<TR align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000' bgcolor='#C1E0FF' onmouseout=\"this.bgColor='#C1E0FF'\">";
		echo "<TD><p align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000'>A condição de pagamento foi enviada para cadastro. Assim que finalizado, você receberá um e-mail automático de confirmação e a tela para digitação de pedidos será liberada.<BR><BR><BR> Stanley Black&Decker</p></TD>";
		echo "</TR>";
		echo "</table>";
		include "rodape.php";
		exit;
}

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
	echo "<b>Caro $nome_posto</b>, O seu posto não está habilitado a realizar compra de peças direto com a fábrica.<br/> Em caso de dúvida, gentileza entrar em contato com o suporte da sua região.\n";
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
			WHERE   tbl_posto_fabrica.codigo_posto in ('22530','51167','40979','40399','56198','42308','10674','43743')
			ORDER BY ordem, tbl_posto.cidade";
	$res = @pg_query ($con,$sql);

/*
##Antes de 09/10/2007 eram essas condições##

FROM    tbl_posto
JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = 1
WHERE   tbl_posto_fabrica.codigo_posto not in ('1122', '23513', '20741', '21957')
AND     ( tbl_posto_fabrica.tipo_posto IN (39, 79, 80, 81) OR tbl_posto_fabrica.tipo_posto = 40 )
ORDER BY ordem, tbl_posto.cidade";


*/


	for ($x=0; $x < pg_num_rows($res); $x++) {
		$nome     = trim(pg_fetch_result($res,$x,nome));
		$posto    = trim(pg_fetch_result($res,$x,codigo_posto));
		$estado   = trim(pg_fetch_result($res,$x,estado));
		$fone     = trim(pg_fetch_result($res,$x,fone));
		$contato  = trim(pg_fetch_result($res,$x,contato));
		$email    = trim(pg_fetch_result($res,$x,email));
		$endereco = trim(pg_fetch_result($res,$x,endereco));
		$bairro   = trim(pg_fetch_result($res,$x,bairro));
		$cidade   = trim(pg_fetch_result($res,$x,cidade));
		$cep      = trim(pg_fetch_result($res,$x,cep));

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




//alterado HD 7325
//lpad(tbl_pedido.pedido_blackedecker,5,0) AS pedido_blackedecker trocado por tbl_pedido.pedido_blackedecker

$sql = "SELECT  tbl_pedido.pedido                                              ,
				tbl_pedido.pedido_blackedecker                                 ,
				tbl_pedido.status_pedido,
				tbl_pedido.condicao,
				tbl_pedido.seu_pedido
		FROM    tbl_pedido
		WHERE   tbl_pedido.exportado           ISNULL

		AND tbl_pedido.finalizado is null

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
		AND     tbl_pedido.exportado         IS NULL
		AND     tbl_pedido.status_pedido     = 1
		 ";
		// AND     tbl_pedido.finalizado        IS NULL  - HD 911437 - Waldir solicitou para retirar > By gabriel Silveira
		 //AND     tbl_pedido.condicao          NOT IN (62)

if (strval(strtotime(date("Y-m-d H:i:s"))) < strval(strtotime("2006-01-01 00:00:00"))) {
	$sql .= "AND     tbl_pedido.tabela = 31 ";
}else{
	$sql .= "AND     tbl_pedido.tipo_pedido = 86 ";
}
// HD 22895
$sql .= "AND     tbl_pedido.posto             = $login_posto
		 AND     tbl_pedido.fabrica           = $login_fabrica
		 AND (tbl_pedido.status_pedido is null or tbl_pedido.status_pedido <>14);";
//if ($ip == '201.13.179.89') echo $sql;
//if ($ip == '201.71.54.144') echo $sql;
$res = pg_query ($con,$sql);


if (pg_num_rows($res)==0){
	$sql = "SELECT  tbl_pedido.pedido                                              ,
					tbl_pedido.pedido_blackedecker                                 ,
					tbl_pedido.status_pedido,
					tbl_pedido.seu_pedido
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
			AND     tbl_pedido.finalizado         IS NULL
			 ";
			// AND     tbl_pedido.finalizado        IS NULL  - HD 911437 - Waldir solicitou para retirar > By gabriel Silveira
			 //AND     tbl_pedido.condicao          NOT IN (62)

	if (strval(strtotime(date("Y-m-d H:i:s"))) < strval(strtotime("2006-01-01 00:00:00"))) {
		$sql .= "AND     tbl_pedido.tabela = 31 ";
	}else{
		$sql .= "AND     tbl_pedido.tipo_pedido =86  ";
	}
	// HD 22895
	$sql .= "AND     tbl_pedido.posto             = $login_posto
			 AND     tbl_pedido.fabrica           = $login_fabrica
			 AND (tbl_pedido.status_pedido is null or tbl_pedido.status_pedido not in(14))";
	//if ($ip == '201.13.179.89') echo $sql;
	//if ($ip == '201.71.54.144') echo $sql;
	$res = pg_query ($con,$sql);

}

if (pg_num_rows($res) > 0) {
	$cook_pedido         = trim(pg_fetch_result($res,0,pedido));
	$pedido_blackedecker = trim(pg_fetch_result($res,0,pedido_blackedecker));
	$seu_pedido          = trim(pg_fetch_result($res,0,seu_pedido));
	$condicao            = trim(pg_fetch_result($res,0,condicao));
	$status_pedido       = trim(pg_fetch_result($res,0,status_pedido));
	//alterado HD 7325
	$pedido_blackedecker = "00000" . $pedido_blackedecker;
	$pedido_blackedecker = substr($pedido_blackedecker, strlen($pedido_blackedecker)-5, strlen($pedido_blackedecker));
	//---------------

	#HD 34403
	if (strlen($seu_pedido)>0){
		$pedido_blackedecker = fnc_so_numeros($seu_pedido);
	}

	$pedido_suframa      = "";
	$sql_suframa = "SELECT pedido_suframa FROM tbl_pedido WHERE pedido_suframa = $cook_pedido";
	$res_suframa = pg_query ($con,$sql_suframa);
	if (pg_num_rows ($res_suframa) > 0) {
	    $pedido_suframa = pg_fetch_result ($res_suframa,0,0);
	}

	setcookie ("cook_pedido",$cook_pedido,time()+(3600*48));
}


if (strlen($cook_pedido) > 0) {
	$sql = "SELECT  tbl_condicao.condicao,
					tbl_pedido.bloco_os
			FROM    tbl_pedido
			JOIN    tbl_condicao ON tbl_condicao.condicao = tbl_pedido.condicao
			WHERE   tbl_pedido.pedido = $cook_pedido";
	$res = @pg_query ($con,$sql);

	if (pg_num_rows($res) > 0) {
		$condicao = trim(pg_fetch_result($res,0,condicao));
		$bloco_os = trim(pg_fetch_result($res,0,bloco_os));;
	}
}

if (strlen($cook_pedido) > 0 and strlen($btngravar) == 0 and strlen($finalizar) == 0) {

	$res = pg_query($con, "BEGIN TRANSACTION");

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
		$res = pg_query ($con,$sql);

		if (strlen ( pg_errormessage ($con) ) > 0) {
			$msg_erro .= pg_errormessage ($con) ;
		}

		if (strlen($msg_erro) == 0) {
			$sql = "SELECT fnc_pedido_delete ($cook_pedido, $login_fabrica, null)";
			$res = @pg_query ($con,$sql);

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro .= pg_errormessage ($con);
			}
		}

		// HD 32120
		if (strlen($msg_erro) == 0) {
			$sql = "SELECT fn_finaliza_pedido_blackedecker ($cook_pedido, $login_fabrica)";
			$res = @pg_query ($con,$sql);

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro .= pg_errormessage ($con);
			}
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
	}else{
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}

}

if (strlen($_GET["ignorar"]) > 0) {

	$ignorar = trim($_GET["ignorar"]);

	$sql_verifica = "SELECT finalizado from tbl_pedido where tbl_pedido.pedido = $pedido";
	$res_verifica = pg_query($con,$sql);

	if (pg_num_rows($res_verifica)>0){

		$finalizado = pg_fetch_result($res_ve, 0, 0);

		if (!empty($finalizado)){

			$msg_erro = "Pedido já finalizado";


		}else{

			$res = pg_query($con,"BEGIN TRANSACTION");

			$sql = "SELECT fnc_pedido_delete ($ignorar, $login_fabrica, null)";
			$res = pg_query ($con,$sql);

			setcookie ("cook_pedido");
			$cook_pedido = "";

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$res = pg_query($con,"ROLLBACK TRANSACTION");
				$msg_erro = pg_errormessage ($con) ;
			}else{
				$res = pg_query($con,"COMMIT TRANSACTION");
				header ("Location: $PHP_SELF");
				exit;
			}

		}

	}

}

//HD 21009 - 27/5/2008
#----------------------- Deletar Item ------------------
if (strlen($_GET["delete"]) > 0) {
	$delete = trim($_GET["delete"]);
	$pedido = trim($_GET["pedido"]);


	$sql_verifica = "SELECT exportado from tbl_pedido where tbl_pedido.pedido = $pedido";
	$res_verifica = pg_query($con,$sql_verifica);

	if (pg_num_rows($res_verifica)>0){

		$exportado = pg_fetch_result($res_verifica, 0, 0);

		if (!empty($exportado)){
			$msg_erro = "Pedido já exportado";
		}else{

			if (empty($pedido)) {
		        /*Inicia o AuditorLog Pedido */

		        $objLog = new AuditorLog('insert');
		        $objItem = new AuditorLog('insert');
		        $tpAuditor = "insert";
		    } else {
		        /*Inicia o AuditorLog Pedido */
		        $objLog = new AuditorLog();
		        $objItem = new AuditorLog();
		        $objLog->retornaDadosTabela('tbl_pedido', array('pedido'=>$pedido, 'fabrica'=>$login_fabrica) );
		        
		        $objItem->retornaDadosSelect("  SELECT  pi.pedido,
		        										pi.pedido_item,
														pi.peca,
														pi.qtde AS qtde_peca_pedida,
														pi.valores_adicionais->>'qtde_demanda' AS qtde_demanda_pedida,
														p.referencia,
														p.descricao,
														JSON_FIELD('qtde_demanda', p.parametros_adicionais) AS qtde_demanda_peca
												FROM tbl_pedido_item pi
												JOIN tbl_peca p ON pi.peca = p.peca
												WHERE p.fabrica = $login_fabrica 
												AND pi.pedido_item = $delete");
		        $tpAuditor = "update";
		    }

			$res = pg_query($con,"BEGIN TRANSACTION");


			// HD-7424309 Comentei pra poder excluir o registro para pegar no log
			/*$sql = "UPDATE tbl_pedido_item SET pedido = 0 WHERE pedido_item = $delete ";
			$res = pg_query ($con,$sql);*/

			$sql = "DELETE FROM tbl_pedido_item
					WHERE  tbl_pedido_item.pedido_item = $delete";
			$res = pg_query($con,$sql);

			$sql = "UPDATE tbl_pedido set finalizado = null, status_pedido = case when status_pedido = 18 then 18 else  null end where pedido = $pedido";
			$res = pg_query($con,$sql);

			$sqlP = "SELECT pedido_item FROM tbl_pedido_item WHERE pedido = $pedido";
			$resP = pg_query ($con,$sqlP);
				
			if(pg_num_rows($resP)==0){

				if($login_fabrica == 1){				
					$sql = "UPDATE Tbl_pedido set fabrica = 0 where pedido = $pedido ";
					$res = pg_query($con, $sql); 
				}
				// $sql = "SELECT fnc_pedido_delete ($pedido, $login_fabrica, null)";
				// $res = pg_query ($con,$sql);

				// if (strlen ( pg_errormessage ($con) ) > 0) {
				// 	$msg_erro .= pg_errormessage ($con);
				// }else{
				// 	$sql = "DELETE FROM tbl_pedido
				// 	WHERE  tbl_pedido.pedido = $pedido";
				// 	$res = @pg_query ($con,$sql);
				// }
			}

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$res = pg_query($con,"ROLLBACK TRANSACTION");
				$msg_erro = pg_errormessage ($con) ;
			}else{
				$res = pg_query($con,"COMMIT TRANSACTION");

				if ($tpAuditor == 'insert') {
		            $objLog->retornaDadosTabela('tbl_pedido', array('pedido'=>$pedido, 'fabrica'=>$login_fabrica))
		                    ->enviarLog($tpAuditor, 'tbl_pedido', $login_fabrica.'*'.$pedido);
		            $objItem->retornaDadosSelect("  SELECT  pi.pedido,
		            										pi.pedido_item,
															pi.peca,
															pi.qtde AS qtde_peca_pedida,
															pi.valores_adicionais->>'qtde_demanda' AS qtde_demanda_pedida,
															p.referencia,
															p.descricao,
															JSON_FIELD('qtde_demanda', p.parametros_adicionais) AS qtde_demanda_peca
													FROM tbl_pedido_item pi
													JOIN tbl_peca p ON pi.peca = p.peca
													WHERE p.fabrica = $login_fabrica 
													AND pi.pedido_item = $delete")
		                    ->enviarLog($tpAuditor, "tbl_pedido_item", $login_fabrica."*".$pedido);
		        } else {
		            $objLog->retornaDadosTabela()->enviarLog($tpAuditor, "tbl_pedido", $login_fabrica."*".$pedido);
		            $objItem->retornaDadosSelect(" SELECT   pi.pedido,
		            										pi.pedido_item,
															pi.peca,
															pi.qtde AS qtde_peca_pedida,
															pi.valores_adicionais->>'qtde_demanda' AS qtde_demanda_pedida,
															p.referencia,
															p.descricao,
															JSON_FIELD('qtde_demanda', p.parametros_adicionais) AS qtde_demanda_peca
													FROM tbl_pedido_item pi
													JOIN tbl_peca p ON pi.peca = p.peca
													WHERE p.fabrica = $login_fabrica 
													AND pi.pedido_item = $delete")->enviarLog($tpAuditor, "tbl_pedido_item", $login_fabrica."*".$pedido);
		        }

				header ("Location: $PHP_SELF");
				exit;
			}

		}

	}
}

//PROMOÇÂO BLACK
/*IGOR - HD 15890 - 18/03/08 - estava com errno pois o cook pedido está em branco quando vai gravar um pedido novo
então adicionei: strlen($cook_pedido) > 0
*/
if($promocao == 'f' AND ($btngravar == "Gravar" OR $finalizar == 1) AND strlen($cook_pedido) > 0 ) {
	$sql = "SELECT condicao FROM tbl_pedido WHERE pedido = $cook_pedido AND condicao in(957,958); ";
	$sql = "SELECT tbl_pedido.condicao
			FROM tbl_pedido
			JOIN tbl_condicao using(condicao)
			WHERE tbl_pedido.pedido = $cook_pedido AND tbl_condicao.promocao is true ";

	$res = @pg_query ($con,$sql);
	if(pg_num_rows($res) > 0){
		if(strlen($cond) == 0){
			$sql2 = "UPDATE tbl_pedido set condicao = null WHERE pedido = $cook_pedido AND fabrica = $login_fabrica; ";
			$res2 = pg_query($con,$sql2);
			$msg_erro = "O prazo para cadastro de promoção acabou. Por favor escolha outra condição de pagamento e clique em Gravar." ;
		}
	}
}


#----------------------- Finalizar Pedido ------------------
if ($finalizar == 1) {
	$motivo = trim($_GET["motivo"]);
	$demanda = $_GET['demanda'];
	$obs_motivo = pg_escape_string(trim($_GET["obs_motivo"]));

	if (!empty($motivo) || !empty($obs_motivo)) {
		$obs_motivo = "$motivo | $obs_motivo";
	}


	//$obs_motivo = empty($obs_motivo) ? "$motivo" : "$obs_motivo"; 

	/*if(empty($obs_motivo) and $demanda == true){
		$msg_erro['erro'][] = "Peça acima da demanda, por favor informar o motivo. ";
	}*/

	$res = pg_query($con, "BEGIN TRANSACTION");

	$retorno = DividePedidos($cook_pedido);
	

	#se o posto tem na tbl_black_posto_condicao a id_condicao = 1905, não vai
	$sql_condicao = "SELECT tbl_black_posto_condicao.id_condicao,
						tbl_black_posto_condicao.posto
						FROM tbl_black_posto_condicao
						JOIN tbl_posto ON tbl_posto.posto = tbl_black_posto_condicao.posto
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
						WHERE tbl_black_posto_condicao.posto = $login_posto
						AND tbl_posto_fabrica.fabrica = $login_fabrica
						AND tbl_black_posto_condicao.id_condicao = 1905";
	$res_condicao = pg_query($con, $sql_condicao);

	if(pg_num_rows($res_condicao) > 0){
		$condicao_id = pg_fetch_result($res_condicao, 0, 'id_condicao');
	}

	if ($condicao_id != 1905 and !$posto_desbloqueado) {
		$condicao = 1905;
		$campo_condicao = " , condicao = $condicao "; 
	}


if(!array_key_exists('erro', $retorno)){

	foreach ($retorno as $pedido) {

		$sql = "SELECT tbl_pedido.pedido
				FROM   tbl_pedido
				WHERE  tbl_pedido.exportado isnull
				AND    tbl_pedido.pedido = $pedido ";
		$res = @pg_query ($con,$sql);

		if (pg_num_rows($res) == 0) {
			$msg_erro['erro'][] = "Pedido não pode ser mais alterado pois já foi exportado.";
			//setcookie ("cook_pedido");
			//$cook_pedido = "";
		}

		if(!array_key_exists('erro', $msg_erro)){
			$sql = "UPDATE tbl_pedido SET
						unificar_pedido = '$unificar'
						$campo_condicao
					WHERE  tbl_pedido.pedido = $pedido
					AND    tbl_pedido.unificar_pedido isnull;";
			$res = @pg_query ($con,$sql);
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro['erro'][] = pg_errormessage ($con) ;
			}
		}

		$sql = "INSERT INTO tbl_pedido_alteracao (
						pedido
				)VALUES(
					$pedido
				)";
		$res = @pg_query ($con,$sql);
		if (strlen ( pg_errormessage ($con) ) > 0) {
			$msg_erro['erro'][] = pg_errormessage ($con) ;
		}


		$sql2 = "SELECT total, condicao FROM tbl_pedido WHERE pedido = $pedido AND fabrica = $login_fabrica; ";
		$res2 = pg_query($con,$sql2);
		if(pg_num_rows($res2) > 0){
			$condicao_pag = pg_fetch_result($res2,0,condicao);
		}

		if($promocao == 't' && !array_key_exists('erro', $retorno)){
			$sql = "SELECT tbl_black_posto_condicao.id_condicao  ,
							tbl_black_posto_condicao.condicao    ,
							tbl_condicao.limite_minimo           ,
							tbl_condicao.promocao
					FROM tbl_black_posto_condicao
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_black_posto_condicao.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					LEFT JOIN tbl_condicao      ON tbl_condicao.condicao   = tbl_black_posto_condicao.id_condicao
					WHERE tbl_black_posto_condicao.posto = $login_posto
					AND   tbl_condicao.condicao = $condicao_pag ";
			if($promocao == 't'){
				$sql .= "UNION SELECT tbl_condicao.condicao as id_condicao, tbl_condicao.descricao as condicao, tbl_condicao.limite_minimo, tbl_condicao.promocao
				FROM tbl_condicao
				JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = $login_posto and tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_condicao.fabrica = $login_fabrica
				AND tbl_condicao.promocao is true
				AND   tbl_condicao.condicao = $condicao_pag";
			}
			$res = pg_query ($con,$sql);
			if(pg_num_rows($res) > 0){
				$limite_minimo = pg_fetch_result($res,0,limite_minimo);
				$promocao2 = pg_fetch_result($res,0,promocao);

				$sql2 = "SELECT case when total_original > 0 then total_original else total end as total
							FROM tbl_pedido
							WHERE pedido = $pedido; ";
				$res2 = pg_query ($con,$sql2);

				$total_pag = pg_fetch_result($res2,0,0);
				if($total_pag < $limite_minimo AND $promocao2 == 't'){
					$msg_erro['erro'][] = "O valor limite para esta condição de pagamento é de R$ ". number_format($limite_minimo, 2, ',', '.') .", por gentileza acrescente mais peças neste pedido, grave e finalize.";
					$sql3 = "UPDATE tbl_pedido set finalizado = null where pedido = $pedido AND fabrica = $login_fabrica ; ";
					$res3 = pg_query($con,$sql3);

					# HD 225737
					if($login_fabrica == 1 and $login_posto == 5252 and $condicao_pag == 972) {
						$msg_erro = "";
						$sql3 = "SELECT fn_pedido_finaliza ($pedido,$login_fabrica);";
						$res3 = pg_query($con,$sql3);
						$msg_erro .= pg_errormessage($con);
					}
				}
			}
		}
	}

	if(!array_key_exists('erro', $msg_erro)){
		$sql = "UPDATE tbl_pedido set seu_pedido = null
				FROM tbl_pedido ped
				WHERE ped.fabrica= tbl_pedido.fabrica
				AND tbl_pedido.seu_pedido = ped.seu_pedido
				AND ped.posto = tbl_pedido.posto
				AND tbl_pedido.pedido = $pedido
				AND ped.pedido <> $pedido
				AND tbl_pedido.fabrica = $login_fabrica
				AND tbl_pedido.seu_pedido notnull
				AND (ped.status_pedido <> 14 or ped.status_pedido isnull) ";
		$res = pg_query($con, $sql);

		foreach ($retorno as $pedido) {

	        $sql = "SELECT fn_finaliza_pedido_blackedecker($pedido,$login_fabrica)";
	        $res = pg_query($con,$sql);
	        if(strlen(pg_last_error($con))>0){
	            $msg_erro['erro'][] = pg_last_error($con);
	        }

	        $sql = "SELECT fn_pedido_finaliza($pedido,$login_fabrica)";
	        $res = pg_query($con,$sql);
	        if(strlen(pg_last_error($con))>0){
	            $msg_erro['erro'][] = pg_last_error($con);
	        }

	        VerificaDemanda($pedido, $obs_motivo);
	    }

	    if(array_key_exists('erro', $msg_erro)){
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
		}else{
			$res = pg_query ($con,"COMMIT");
		}

	}
}else{

	$res = pg_query ($con,"ROLLBACK TRANSACTION");
	$msg_erro = "Erro a Finalizar pedido. " .  implode(",", $msg_erro['erro']);
}

	if(!array_key_exists('erro', $msg_erro)){

		$qtdePedidos = count($retorno);

		if($qtdePedidos > 1){
			$msg = $msg_erro;
			header ("Location: pedido_finalizado_desmembrados.php?msg=$msg&pedido=".implode(",", $retorno)."&bloq=$bloqueio_pedido");
		}else{
			if($login_fabrica == 1 && strlen($msg_erro) == 0){

				$posto              = $login_posto;
				
				$sql_condicao = "SELECT condicao FROM tbl_pedido WHERE pedido = $pedido";
				$res_condicao = pg_query($con, $sql_condicao);

				$condicao_pagamento = pg_fetch_result($res_condicao, 0, 'condicao');

				if($condicao_pagamento == 1905){

					$atendente = hdBuscarAtendentePorPosto($posto);

					$sql_protocolo = "SELECT COUNT(hd_chamado) AS qtde_sac FROM tbl_hd_chamado WHERE fabrica = {$login_fabrica} AND categoria = 'servico_atendimeto_sac'";
			        $res_protocolo = pg_query($con, $sql_protocolo);

			        $qtde_sac = pg_fetch_result($res_protocolo, 0, "qtde_sac");

			        $protocolo_cliente = "SAC".str_pad($qtde_sac, 7, "0", STR_PAD_LEFT);

					$sqlp = "SELECT seu_pedido FROM tbl_pedido WHERE pedido = {$cook_pedido} AND fabrica = {$login_fabrica}";
					$resp = pg_query($con, $sqlp);
					if(pg_num_rows($resp) > 0){
						$seu_pedido = pg_fetch_result($resp, 0, "seu_pedido");
					}

					$sqlh = "SELECT hd_chamado FROM tbl_hd_chamado join tbl_hd_chamado_item using(hd_chamado) where tbl_hd_chamado.posto = $login_posto and fabrica = $login_fabrica and categoria = 'pagamento_antecipado'  and comentario ~'Chamado aberto referente ao pedido {$seu_pedido}'";
					$resh = pg_query($con, $sqlh); 
					if(pg_num_rows($resh) == 0) {
						$titulo = "Help-Desk Posto";

						$sql_hd = "INSERT INTO tbl_hd_chamado 
									(posto, titulo, status, atendente, fabrica_responsavel, fabrica, categoria, protocolo_cliente) 
									VALUES 
									({$posto}, '{$titulo}', 'Ag. Fábrica', {$atendente}, {$login_fabrica}, {$login_fabrica}, 'pagamento_antecipado', '{$protocolo_cliente}') 
									RETURNING hd_chamado";
						$res_hd = pg_query($con, $sql_hd);

						$hd_chamado = pg_fetch_result($res_hd, 0, "hd_chamado");

						if(strlen($hd_chamado) > 0){

							$sql_hd_extra = "INSERT INTO tbl_hd_chamado_extra (hd_chamado) VALUES ({$hd_chamado})";
							$res_hd_extra = pg_query($con, $sql_hd_extra);



							$comentario = "Chamado aberto referente ao pedido {$seu_pedido} com Condição de Pagamento Antecipado";

							$sql_hd_item = "INSERT INTO tbl_hd_chamado_item 
											(hd_chamado, comentario, status_item) 
											VALUES 
											({$hd_chamado}, '{$comentario}', 'Aberto')";
							$res_hd_item = pg_query($con, $sql_hd_item);

						}
					}

				}

			}

			if(!empty($pedido)){
				$sql_posto = "SELECT 
								tbl_posto_fabrica.contato_email as contato_email,
								tbl_fabrica.nome as fabrica_nome,
								tbl_posto.nome as posto_nome 
							FROM tbl_posto_fabrica 
							JOIN tbl_fabrica on (tbl_fabrica.fabrica = tbl_posto_fabrica.fabrica) 
							JOIN tbl_posto on (tbl_posto.posto = tbl_posto_fabrica.posto) 
							where tbl_posto_fabrica.fabrica = $login_fabrica and tbl_posto_fabrica.posto = $login_posto";
	
	
				$res_posto = pg_query($con, $sql_posto);
	
				$contato_email = pg_fetch_result($res_posto, 0, 'contato_email');
				$fabrica_nome = pg_fetch_result($res_posto, 0, 'fabrica_nome');
				$posto_nome = pg_fetch_result($res_posto, 0, 'posto_nome');

				$assunto       = "Pedido nº ".$pedido_blackedecker. " - ". $fabrica_nome;
				$corpo         = email_pedido_blackeredecker($posto_nome, $fabrica_nome, $pedido, $pedido_blackedecker, $cook_login);
	
				$mailTc = new TcComm($externalId);
				$res = $mailTc->sendMail(
					$contato_email,
					$assunto,
					utf8_encode($corpo),
					$externalEmail
				);
			}

			header ("Location: pedido_blackedecker_finalizado_new.php?msg=$msg&pedido=".$retorno[0]."&bloq=$bloqueio_pedido&hd_chamado=$hd_chamado");
		}
		exit;
	}else{
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
		$msg_erro = "Erro a Finalizar pedido. " .  implode(",", $msg_erro['erro']);
	}
}
#--------------- Gravar Item ----------------------
if ($btngravar == "Gravar" or $_POST['btn_recalcula'] == 'Recalcular') {

    $queryCatPosto = "SELECT categoria, parametros_adicionais
				      FROM tbl_posto_fabrica
				      WHERE posto = $login_posto
				      AND fabrica = $login_fabrica";

	$resCatPosto = pg_query($con, $queryCatPosto);

	$categoriaPosto = pg_fetch_result($resCatPosto, 0, 'categoria');
	$parametros_adicionais = pg_fetch_result($resCatPosto, 0, 'parametros_adicionais');
	$parametros_adicionais = json_decode($parametros_adicionais);
	
	$pedido_faturado_locadora = True;

	if (isset($parametros_adicionais->pedido_faturado_locadora) && $parametros_adicionais->pedido_faturado_locadora == 't') {
			
		$pedido_faturado_locadora = False;
	}  

	if (in_array($login_fabrica, [1]) && $categoriaPosto == 'Locadora' && $pedido_faturado_locadora) {
		
		$query = "SELECT p.referencia
				  FROM tbl_lista_basica lb
				  JOIN tbl_peca p ON (p.peca = lb.peca)
				  WHERE lb.produto = $produto
				  AND lb.fabrica = $login_fabrica";

		$res = pg_query($con, $query);
		
		$arrayPecas = pg_fetch_all($res);

		foreach ($arrayPecas as $arrayPeca) {
			
			$pecas[] = $arrayPeca['referencia'];
		}

		$i = 0;

		$referencia = 'referencia' . $i;

		while (isset($_POST[$referencia])) {
			
			$pecaReferencia = $_POST[$referencia];

			if (strlen($pecaReferencia) > 0) {
				
				if (!in_array($pecaReferencia, $pecas)) {

					$msg_erro .= ' Peça ' . $pecaReferencia . ' não cadastrada na lista básica. ';
				}
			}

			$i++;

			$referencia = 'referencia' . $i;

		}
	}

	$numero_pedido = (int)$_POST["numero_pedido"];

	$sql_verifica_nao_exportado = "SELECT pedido FROM tbl_pedido WHERE fabrica = $login_fabrica and posto = $login_posto and exportado is null and status_pedido not in(14) and admin is null and troca is not true and pedido_acessorio is not true and pedido_os is not true and tipo_pedido = 86 ORDER BY data desc limit 1 ";
	$res_verifica_nao_exportado = pg_query($con, $sql_verifica_nao_exportado);
	if(pg_num_rows($res_verifica_nao_exportado)>0){
		$cook_pedido = pg_fetch_result($res_verifica_nao_exportado, 0, pedido);

		$sql_reabrir = "UPDATE tbl_pedido SET finalizado = null WHERE PEDIDO = $cook_pedido AND fabrica = $login_fabrica ";
		//echo $sql_reabrir . '<br><br>'; exit;
		$res_reabrir = pg_query($con, $sql_reabrir);
	}

#se o posto tem na tbl_black_posto_condicao a id_condicao = 1905, não vai
	$condicao = trim($_POST['condicao']);
	$condicao_pagamento_trocar = trim($_POST['condicao_pagamento_trocar']);

	if(!empty($condicao_pagamento_trocar)) $condicao = $condicao_pagamento_trocar; 

	$condicao_automatica = false;

	$sql_condicao = "SELECT tbl_black_posto_condicao.id_condicao, tbl_condicao.promocao,
						tbl_black_posto_condicao.posto
						FROM tbl_black_posto_condicao
						JOIN tbl_posto ON tbl_posto.posto = tbl_black_posto_condicao.posto
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
						JOIN tbl_condicao on tbl_condicao.condicao = tbl_black_posto_condicao.id_condicao
						WHERE tbl_black_posto_condicao.posto = $login_posto
						AND tbl_posto_fabrica.fabrica = $login_fabrica
						AND tbl_black_posto_condicao.id_condicao = 1905";
	$res_condicao = pg_query($con, $sql_condicao);

	if(pg_num_rows($res_condicao) > 0){
		$condicao_id = pg_fetch_result($res_condicao, 0, 'id_condicao');
		$promocao    = pg_fetch_result($res_condicao, 0, 'promocao') == 't';
	}

	if ($condicao_id != 1905 and !$posto_desbloqueado) {
		$condicao = 1905;
		$condicao_automatica = true;
	}

	if ($condicao_automatica == false and $promocao == 'f') {

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
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) == 0){
#            $msg_erro .= "Condição de pagamento informada não encontrada.";
        }

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
				WHERE  tbl_tabela.sigla_tabela = 'BASE4'";
	}
	$res = @pg_query ($con,$sql);
	if (pg_num_rows ($res) > 0) $tabela = pg_fetch_result ($res,0,0);
	if (strlen($msg_erro) == 0){

		if (empty($cook_pedido)) {
	        /*Inicia o AuditorLog Pedido */

	        $objLog = new AuditorLog('insert');
	        $objItem = new AuditorLog('insert');
	        $tpAuditor = "insert";
	    } else {
	        /*Inicia o AuditorLog Pedido */
	        $objLog = new AuditorLog();
	        $objItem = new AuditorLog();
	        $objLog->retornaDadosTabela('tbl_pedido', array('pedido'=>$cook_pedido, 'fabrica'=>$login_fabrica) );
	        
	        $objItem->retornaDadosSelect("  SELECT  pi.pedido_item,
													pi.peca,
													pi.qtde AS qtde_peca_pedida,
													pi.valores_adicionais->>'qtde_demanda' AS qtde_demanda_pedida,
													p.referencia,
													p.descricao,
													JSON_FIELD('qtde_demanda', p.parametros_adicionais) AS qtde_demanda_peca
											FROM tbl_pedido_item pi
											JOIN tbl_peca p ON pi.peca = p.peca
											WHERE p.fabrica = $login_fabrica 
											AND pi.pedido = $cook_pedido");
	        $tpAuditor = "update";
	    }

		$res = pg_query($con, "BEGIN TRANSACTION");

		if (strlen ($cook_pedido) == 0) {

			// IGOR - ATENÇÃO: FOI ADICIONADO PARA OS POSTOS QUE TEM PEDIDO EM GARANTIA
			if($condicao=="62"){
				$des_tipo_pedido = "GARANTIA";
			}else{
				$des_tipo_pedido = "FATURADO";
			}

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
						(SELECT tipo_pedido
						 FROM tbl_tipo_pedido
						 WHERE fabrica = $login_fabrica
							AND UPPER(trim(descricao)) = '$des_tipo_pedido' ),
						't'
					)";
			//echo $sql . '<br><br>';

			$res = @pg_query ($con,$sql);

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro .= pg_errormessage ($con) ;
			}else{
				$res = pg_query ($con,"SELECT currval ('seq_pedido')");
				$cook_pedido = pg_fetch_result ($res,0,0);

				# cookie expira em 48 horas
				setcookie ("cook_pedido",$cook_pedido,time()+(3600*48));

				if(empty($msg_erro) and !empty($cook_pedido)){
					$sql = "SELECT fn_valida_pedido($cook_pedido,$login_fabrica);";
					$res = pg_query($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			}
		}else{
			$sql = "SELECT tbl_pedido.pedido
					FROM   tbl_pedido
					WHERE  tbl_pedido.exportado isnull
					AND    tbl_pedido.pedido = $cook_pedido;";
			$res = @pg_query ($con,$sql);

			if (pg_num_rows($res) == 0) {
				$msg_erro .= "Pedido não pode ser mais alterado pois já foi exportado.";
				setcookie ("cook_pedido");
				$cook_pedido = "";
			}

			if (strlen($msg_erro) == 0) {
				// IGOR - ATENÇÃO: FOI ADICIONADO PARA OS POSTOS QUE TEM PEDIDO EM GARANTIA
				if($condicao=="62"){
					$des_tipo_pedido = "GARANTIA";
				}else{
					$des_tipo_pedido = "FATURADO";
				}

				$sql = "UPDATE tbl_pedido SET
							tabela     = '$tabela'       ,
							condicao   = '$condicao'     ,
							seu_pedido = case when condicao <> $condicao then null else seu_pedido end,
							bloco_os   = '$aux_bloco_os' ,
							total      = null            ,
							finalizado = null
						WHERE tbl_pedido.pedido = $cook_pedido;";

				//echo 'Primeira Passagem<br>' . $sql . '<br><br>';

				$res = @pg_query ($con,$sql);

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

				//echo 'Segunda Passagem<br>' . $sql . '<br><br>';

				$res = @pg_query ($con,$sql);

				if (strlen ( pg_errormessage ($con) ) > 0) {
					$msg_erro .= pg_errormessage ($con) ;
				}
			}
		}

		/* hd 19525 - retirei em 08/05/2008, se ocorrer algum problema voltar e retirar a vlaidação de itens da função
		if (strlen($cook_pedido) > 0) {
			$sql = "SELECT fn_finaliza_pedido_blackedecker ($cook_pedido,$login_fabrica)";
			$res = @pg_query ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
		*/

		if (strlen (trim ($msg_erro)) == 0) {
			$msg = "";
			$rows_pecas = $_POST["rows_pecas"];
			
			for ($xx=0; $xx<$rows_pecas; $xx++) {
				$referencia = trim($_POST["referencia" . $xx]);

                if (!empty($_POST["qtde_multi{$xx}"])) {
                    $qtde = (int) $_POST["qtde_multi{$xx}"];
                } else {
                    $qtde = trim($_POST["qtde" . $xx]);
                }

				$xreferencia = str_replace(" ","",$referencia);
				$xreferencia = str_replace(".","",$xreferencia);
				$xreferencia = str_replace("-","",$xreferencia);
				$xreferencia = str_replace("/","",$xreferencia);

				$xreferencia = strtoupper($xreferencia);
		//takashi 26-01 HD1115
		/*				if($xreferencia=="56224401"){
							$msg_erro .= "ITEM 562244-01 EM ANÁLISE, ENTRE EM CONTATO COM A FÁBRICA.<BR>";
						}
						if($xreferencia=="64789600"){
							$msg_erro .= "ITEM 647896-00 EM ANÁLISE, ENTRE EM CONTATO COM A FÁBRICA.<BR>";
						}
						if($xreferencia=="18544100"){
							$msg_erro .= "ITEM 185441-00 EM ANÁLISE, ENTRE EM CONTATO COM A FÁBRICA.<BR>";
						}
		*/
				if ((strlen($referencia) > 0) AND(strlen($msg_erro)==0)) {
					$sql = "SELECT tbl_peca.peca, tbl_peca.origem
							FROM   tbl_peca
							WHERE  tbl_peca.referencia_pesquisa = '$xreferencia'
							AND    tbl_peca.fabrica             = $login_fabrica";

					$resX = pg_query ($con,$sql);

					if (pg_num_rows($resX) > 0 AND strlen (trim ($qtde)) > 0 AND $qtde > 0) {
						$peca = pg_fetch_result($resX,0,0);
						$peca_origem = pg_fetch_result($resX,0,1);

						$sqlY = "SELECT	a.oid    ,
										a.*      ,
										tbl_peca.peca,
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

						$resY = pg_query ($con,$sqlY);
						if(pg_num_rows($resY) > 0){
							for ($j = 0 ; $j < @pg_num_rows ($resY) ; $j++) {
								$aux_peca = @pg_fetch_result ($resY,$j,peca);
								if($aux_peca == $peca){
									$msg_erro = "Peça $referencia em destaque em duplicidade, favor retirar!";
									$linha_erro = $xx;
								}
							}
						}

						if(in_array($peca_origem,array('FAB/SUB','TER/SUB','IMP/SUB')) AND strlen($msg_erro) == 0){
							if($qtde > 1){
								$msg_erro = "A peça $referencia é fornecida para uso do posto onde é limitada a compra de uma unidade para cada posto autorizado. Qualquer dúvida entre em contato com o seu suporte.";
							}else{
								$sqlPedido = "SELECT seu_pedido FROM tbl_pedido JOIN tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido AND tbl_pedido_item.peca = $peca
								WHERE tbl_pedido.fabrica = $login_fabrica
								AND tbl_pedido.posto = $login_posto";

								//die(nl2br($sqlPedido));
								$resPedido = pg_query($con,$sqlPedido);

								if(pg_num_rows($resPedido) > 0){
									$seu_pedido = fnc_so_numeros(pg_fetch_result($resPedido,0,'seu_pedido'));
									$msg_erro = "A peça $referencia é fornecida para uso do posto onde é limitada a compra de uma unidade para cada posto autorizado. Registramos que já houve compra desse item no pedido $seu_pedido. Qualquer dúvida entre em contato com o seu suporte.";
								}
							}
						}

						if (in_array($login_fabrica, [1]) && $categoriaPosto == 'Locadora' && $pedido_faturado_locadora) {

							if (!isset($_REQUEST['produto_serie'])) {

								$msg_erro .= "Número de série do produto é obrigatório.";

							} else  {

								$inserts = "produto_locador, nota_fiscal_locador, data_nf_locador, serie_locador,";

								$data_locador = str_replace('/', '-', $_REQUEST['data_emissao']);
								$data_locador = date("Y-m-d", strtotime($data_locador));

								$nota_fiscal = "";
								
								for ($yy=0; $yy<6; $yy++) {
									
									$nota_fiscal .= $_REQUEST['nota_fiscal'][$yy];
								}

								$variaveis = "{$_REQUEST['produto']}, $nota_fiscal, '$data_locador', {$_REQUEST['produto_serie']},";
							}

						}

						if (strlen($msg_erro) == 0) {

							$sql = "INSERT INTO tbl_pedido_item (
									pedido,
									$inserts
									peca  ,
									qtde
								)VALUES(
								 	$cook_pedido,
									$variaveis
									$peca       ,
									$qtde
								)";

							//echo 'Terceira Passagem<br>' . $sql . '<br><br>'; 

							$res = @pg_query ($con,$sql);
							$msg_erro .= @pg_errormessage($con);



							if (strlen($msg_erro) == 0) {
								$res         = @pg_query ($con,"SELECT CURRVAL ('seq_pedido_item')");
								$pedido_item = @pg_fetch_result ($res,0,0);
								$msg_erro .= pg_errormessage($con);
							}

							if (strlen($msg_erro) == 0) {
								$sql = "SELECT fn_valida_pedido_item ($cook_pedido,$peca,$login_fabrica)";
								$res = @pg_query ($con,$sql);
								$msg_erro .= pg_errormessage($con);
							}

							if (strlen ($msg_erro) > 0) {
								$linha_erro = $xx;
								$erro_linha = "erro_linha" . $xx;
								$$erro_linha = 1 ;
								break ;
							}
						}
						// HD 32120
						// if (strlen($msg_erro) == 0) {
						// 	$sql = "SELECT fn_finaliza_pedido_blackedecker ($cook_pedido, $login_fabrica)";
						// 	$res = @pg_query ($con,$sql);

						// 	if (strlen ( pg_errormessage ($con) ) > 0) {
						// 		$msg_erro .= pg_errormessage ($con);
						// 	}
						// }
					}else{
						if (strlen (trim ($qtde)) > 0 AND $qtde > 0) {
							$msg_erro = "Item $referencia não existe, Consulte a vista explodida atualizada e verifique o código correto.";
						}else{
							$msg_erro = "Favor informar a quantidade para o item $referencia.";
						}

						if (strlen ($msg_erro) > 0) {
							$erro_linha = "erro_linha" . $xx;
							$$erro_linha = 1 ;
							$linha_erro = $xx;
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
					$res = pg_query($con,$sql);
					if (pg_num_rows($res) > 0) {
						$qtde_total_mes = pg_fetch_result($res,0,0);
						if (strlen($qtde_total_mes) > 0 && $qtde > $qtde_total_mes) {
							$msg .= " A quantidade $qtde pedida para a peça $referencia está acima da média de $qtde_total_mes pedida normalmente. Você tem certeza que a quantidade pedida está correta? ";
						}
					}
				}*/
				//echo $xx . ' - ' . trim($_POST["referencia" . $xx]) . ' - ' . $xreferencia . ' - ' . $qtde . '<br>';
			} //exit;
		}


		if (strlen($msg_erro) == 0) {
			$sql = "SELECT fn_finaliza_pedido_blackedecker ($cook_pedido,$login_fabrica)";
			$res = @pg_query ($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}

		if (strlen($msg_erro) == 0) {

			$res = pg_query ($con,"COMMIT TRANSACTION");

			if ($tpAuditor == 'insert') {
	            $objLog->retornaDadosTabela('tbl_pedido', array('pedido'=>$cook_pedido, 'fabrica'=>$login_fabrica))
	                    ->enviarLog($tpAuditor, 'tbl_pedido', $login_fabrica.'*'.$cook_pedido);
	            $objItem->retornaDadosSelect("  SELECT  pi.pedido_item,
														pi.peca,
														pi.qtde AS qtde_peca_pedida,
														pi.valores_adicionais->>'qtde_demanda' AS qtde_demanda_pedida,
														p.referencia,
														p.descricao,
														JSON_FIELD('qtde_demanda', p.parametros_adicionais) AS qtde_demanda_peca
												FROM tbl_pedido_item pi
												JOIN tbl_peca p ON pi.peca = p.peca
												WHERE p.fabrica = $login_fabrica 
												AND pi.pedido = $cook_pedido")
	                    ->enviarLog($tpAuditor, "tbl_pedido_item", $login_fabrica."*".$pedido);
	        } else {
	            $objLog->retornaDadosTabela()->enviarLog($tpAuditor, "tbl_pedido", $login_fabrica."*".$cook_pedido);
	            $objItem->retornaDadosSelect(" SELECT   pi.pedido_item,
														pi.peca,
														pi.qtde AS qtde_peca_pedida,
														pi.valores_adicionais->>'qtde_demanda' AS qtde_demanda_pedida,
														p.referencia,
														p.descricao,
														JSON_FIELD('qtde_demanda', p.parametros_adicionais) AS qtde_demanda_peca
												FROM tbl_pedido_item pi
												JOIN tbl_peca p ON pi.peca = p.peca
												WHERE p.fabrica = $login_fabrica 
												AND pi.pedido = $cook_pedido")->enviarLog($tpAuditor, "tbl_pedido_item", $login_fabrica."*".$cook_pedido);
	        }

			header("Location: $PHP_SELF?msg=".$msg);
			exit;
		}else{
			$res = pg_query ($con,"ROLLBACK TRANSACTION");
		}
	}
}

$title     = "Pedido de Peças";
$cabecalho = "Pedido de Peças";

$layout_menu = 'pedido';

if(!empty($cook_pedido)) {
	$sql = "SELECT pedido
			FROM tbl_pedido
			WHERE pedido = $cook_pedido
			AND   fabrica = $login_fabrica";
	$res = pg_query($con,$sql);
	if(pg_num_rows($res) == 0){
		unset( $cook_pedido );
		setcookie ('cook_pedido');
		$cook_pedido = "";
	}
}

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

		@mail($remetente_email, stripslashes(utf8_encode($assunto)), utf8_encode($corpo), "From: ".$email_origem." \n $body_top " );
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
//	if($ip=="200.232.184.76") echo "$sql2";
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



<style type="text/css">

#msg_aguarde_gravar, #msg_aguarde{
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 14px;
	font-weight: bold;
	border: 0px solid;
	color:#FF0000;
}

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

<script type="text/javascript" src="js/jquery-1.2.1.pack.js"></script>
<script src="plugins/shadowbox/shadowbox.js"	type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">

<script LANGUAGE="JavaScript">


function adicionarLinha(){

	qtde = parseInt($("#qtde_campos").val());

	//posicao += 1;
	var conteudo = "";

	for(posicao = qtde; posicao<qtde+15; posicao++){

		conteudo += "<tr> <input type='hidden' name='peca_qtde_antiga_"+posicao+"' value=''> <td align='center' ><input type='text' name='referencia"+posicao+"' id='referencia"+posicao+"' size='15' maxlength='15' value='' onFocus='nextfield=qtde"+posicao+"' onChange='javascript: FuncPesquisaPeca(window.document.frmpedido.referencia"+posicao+".value,window.document.frmpedido.descricao"+posicao+".value,referencia,"+posicao+", false);'>  <img src='imagens/btn_buscar5.gif' style='cursor:pointer' alt='Clique para pesquisar por referência do componente' border='0' hspace='5' align='absmiddle' onclick='javascript: FuncPesquisaPeca(window.document.frmpedido.referencia"+posicao+".value,window.document.frmpedido.descricao"+posicao+".value,'referencia',"+posicao+");'></td> </td>";

		conteudo +=  "<td align='center'><input type='text' name='descricao"+posicao+"' id='descricao"+posicao+"' size='30' maxlength='30' value='' onFocus='nextfield =qtde"+posicao+"'> </td>";

		conteudo +=  "<td align='center' ><input type='hidden' id='qtde_multi"+posicao+"' name='qtde_multi"+posicao+"' value='' /> <input type='text' name='qtde"+posicao+"' id='qtde"+posicao+"' size='6' maxlength='4' value='' onKeyUp='getValidaMultiplo(window.document.frmpedido.qtde"+posicao+".value,window.document.frmpedido.referencia"+posicao+".value,window.document.frmpedido.descricao"+posicao+".value,window.document.frmpedido.peca_qtde_antiga_"+posicao+".value,"+posicao+");'> </td>";

		//conteudo += "<td> <input type='text' name='valor_unitario"+posicao+"' size='10' value=''></td>";
	 	//conteudo += "<td> <input type='text' name='total"+posicao+"' size='10' value=''></td>"

	 	conteudo += "</tr>";
    }

	$(".linha_pedido_black").append(conteudo);
	$("#qtde_campos").val(posicao);
	$("#rows_pecas").val(posicao);

}

function gravarObs(obs, motivo, observacao){
	if(obs == true){
		window.location.href ="pedido_blackedecker_cadastro.php?finalizar=1&linha=$linha&unificar=t&demanda=true&motivo= "+motivo+ "&obs_motivo="+observacao ;
	}else{
		window.location.href ="pedido_blackedecker_cadastro.php?finalizar=1&linha=$linha&unificar=t";
	}
}

window.onload = function () { //hd_chamado=2543280
	Shadowbox.init();
	$("#msg_aguarde_gravar").hide();

	$("#visualiza_log_item").click(function () {
		
		let pedido_id = $(this).attr("data-pedido")
		let url_log = "relatorio_log_alteracao_new.php?parametro=tbl_pedido_item&id="+pedido_id

        Shadowbox.open({
            content: url_log,
            player: "iframe",
        });
	})

	$("#finalizar").click(function(){
		var num_pedido = $("input[name='numero_pedido']").val();
		$.ajax({ //hd_chamado=2543280
			url: "pedido_blackedecker_cadastro.php",
			dataType: "GET",
			async: false,
			data: {verificaDemanda:true, pedido:num_pedido},
			success: function(retorno){
				var retorno = JSON.parse(retorno);
				if(retorno.motivo_demanda == true){
					let pedidos_itens = retorno.pedidos_itens.join(",")
					Shadowbox.open({
						content: 'motivo_demanda.php?pedido='+num_pedido+'&pedidos_itens='+pedidos_itens,
						player:	"iframe",
						title: 	"Motivo Demanda",
						width:	1200,
						height:	600
					});
				}else{
					gravarObs(obs=false);
				}
			}
		});
	});


	$("#uploda_arquivo_black").click(function(){		
		if($('input[name=arquivo_excel]').val().length > 0){
			$("#msg_aguarde").show();	
		}
	});

	<? if (strlen($pecas_pedido_excel) > 0) { ?>

		var $inputs = $('input[name^=qtde]');

		$("#btn_img").hide();

		var cont = 1;
		var cont2 = $("#rows_pecas").val();


			Shadowbox.open({
				content:"<div style='background:#FFFFFF;height:100%;text-align:center;'>\
								<br><p style='font-size:14px;font-weight:bold'>Processando Aguarde...</p>\
							</div>",
				player:	"html",
				title:	"",
				width:	200,
				height:	65,
				options: {onFinish: function(){

					$("#sb-nav-close").hide();
				},
					modal:true,
					overlayColor:'#000000' }
			});

		$inputs.each(function(){

			var peca = $(this).parent("td").parent("tr").find("input[name^=referencia]").val();
			var descricao = $(this).parent("td").parent("tr").find("input[name^=descricao]").val();
			var qtde = $(this).val();
			var qtde_new = $(this);
			var qtde_peca_antiga = $(this).parent("td").parent("tr").find("input[name^=peca_qtde_antiga_]");

			$.ajax({ //hd_chamado=2543280
				url: "pedido_blackedecker_cadastro.php",
				dataType: "GET",
				data: "valida_multiplo=sim&peca="+peca+"&qtde="+qtde,
				success: function(retorno){
					var resposta = retorno.responseText;
					resposta = retorno.split("|");
					if(resposta[0] == "ok"){
						qtde_peca_antiga.val(peca+"|"+descricao+"|"+qtde+"|"+resposta[1]);
						qtde_new.val(resposta[1]);
						//setTimeout(fnc_calcula_total(linha),1000);

						if(cont == cont2){
							$("#btn_img").show();
							Shadowbox.close();
						}
						cont++;
					}
				}
			});
		});

	<?}else{?>


		// $("input[name^=qtde]").blur(function(){

		// 	var peca = $(this).parent("td").parent("tr").find("input[name^=referencia]").val();
		// 	var descricao = $(this).parent("td").parent("tr").find("input[name^=descricao]").val();
		// 	var qtde = $(this).val();
		// 	var qtde_new = $(this);
		// 	var qtde_peca_antiga = $(this).parent("td").parent("tr").find("input[name^=peca_qtde_antiga_]");

		// 	$.ajax({ //hd_chamado=2543280
		// 		url: "pedido_blackedecker_cadastro.php",
		// 		dataType: "GET",
		// 		data: "valida_multiplo=sim&peca="+peca+"&qtde="+qtde,
		// 		success: function(retorno){
		// 			var resposta = retorno.responseText;
		// 			resposta = retorno.split("|");
		// 			if(resposta[0] == "ok"){
		// 				qtde_peca_antiga.val(peca+"|"+descricao+"|"+qtde+"|"+resposta[1]);
		// 				qtde_new.val(resposta[1]);
		// 				//setTimeout(fnc_calcula_total(linha),1000);
		// 			}
		// 		}
		// 	});

		// });

	<?php }?>
};

function verificaDePara()
{

    var de_para = [];

    $("input[name^='referencia']").each(function() {
      var ref = $(this).val();

      if (ref) {
        var id = $(this)[0].id;
        $.ajax({
          url: "verifica_de_para.php",
          data: {ref: ref},
          async: false,
          success: function(response) {
			var pos = id.replace('referencia', '');
			if(response.length > 0) {
				var resp = JSON.parse(response);

				var referencia = resp.referencia_para;
				var descricao = resp.descricao_para;
				var qtde = $("#qtde" + pos).val()
				var qtde_antiga = $("input[name=peca_qtde_antiga_" + pos + "]").val();

				getValidaMultiplo(qtde, referencia, descricao, qtde_antiga, pos);

				de_para.push({id: id, response: resp});
			}
          }
        });
      }
    });

    var td = '';

    de_para.forEach(function(data, idx) {
      td += '<tr>';
      td += '<td>' + data.response.referencia_de + ' - ' + data.response.descricao_de + '</td>';
      td += '<td>' + data.response.referencia_para + ' - ' + data.response.descricao_para + '</td>';
      td += '<td>';
      td += '<input type="hidden" name="referencia_para_' + data.id + '" value="' + data.response.referencia_para + '">';
      td += '<input type="hidden" name="descricao_para_' + data.id + '" value="' + data.response.descricao_para + '">';
      td += '<input type="radio" name="confirma_peca_' + data.id + '" value="t" checked> SIM';
      td += '<input type="radio" name="confirma_peca_' + data.id + '" value="f"> NÃO</td>';
      td += '</tr>';
    });

    if (td) {
        Shadowbox.open({
            content:"<div style='background:#FFFFFF;height:100%;text-align:center;'>\
                        <br><p style='font-size:14px;font-weight:bold'>Peças que possuem DE-PARA</p>\
                        <p style='font-weight:bold;'>\
                        <table border='1' width='800' id='resultado' cellspacing='1' cellpadding='0' align='center'>\
                        <tr height='20' class='menu_top'>\
                            <td>DE</td><td>PARA</td><td>Deseja Gravar o pedido?</td>\
                        </tr>\
                            "+td+"\
                        </table>\
                        </p>\
                        <p>\
                            <input type='button' value='OK' onclick=\"javascript:confirmaDePara();\">\
                        </p>\
                    </div>",
            player: "html",
            title:  "Confirmar DE-PARA",
            width:  1000,
            height: 600,
            options: {
                onFinish: function(){
                    $("input[name=btngravar]").val("");
                    $("#sb-nav-close").hide();
                },
                modal: true,
                overlayColor:'#000000'
            }
        });
    } else {
        validaMultiplo();
    }
}

function confirmaDePara(){
    var ok = false;

    $("input[name^='confirma_peca_']").each(function() {
      var conf = $(this)[0];

      if (conf.checked) {
        var value = conf.value;
        var referencia = undefined;
        var descricao = undefined;
        var arr = conf.name.split('_');
        var id = arr[arr.length - 1];

        if (value == 't') {
          ok = true;
          referencia = $("input[name='referencia_para_" + id + "']").val();
          descricao = $("input[name='descricao_para_" + id + "']").val();
          $("#" + id).val(referencia);
          $("#" + id.replace('referencia', 'descricao')).val(descricao);
        }
      }
    });

    Shadowbox.close();

    if (ok) {
      $("input[name=btngravar]").val("Gravar");
      setTimeout(function() { validaMultiplo(); }, 500);
    }

}

function getValidaMultiplo(qtde, peca, descricao, qtde_antiga, posicao){

	peca = $.trim(peca);
	descricao = $.trim(descricao);
	qtde = $.trim(qtde);
	qtde_new = $("input[name=qtde_multi"+posicao+"]");
	qtde_peca_antiga = $("input[name=peca_qtde_antiga_"+posicao+"]");

    if (!qtde.length) {
        return;
    }

	$.ajax({ //hd_chamado=2543280
		url: "pedido_blackedecker_cadastro.php",
		dataType: "GET",
		data: "valida_multiplo=sim&peca="+peca+"&qtde="+qtde,
		success: function(retorno){
			var resposta = retorno.responseText;
			resposta = retorno.split("|");
			if(resposta[0] == "ok"){
				$(qtde_peca_antiga).val(peca+"|"+descricao+"|"+qtde+"|"+resposta[1]);
 				$(qtde_new).val(resposta[1]);
				//setTimeout(fnc_calcula_total(linha),1000);
			}
		}
	});
}

function validaMultiplo(){//hd_chamado=2543280

	var submite = true;
	var valores = "";
	var cond_pagamento = $("#cond_pagamento").val();

	if(cond_pagamento == '' || cond_pagamento == undefined){
		alert("Escolha a condição de pagamento");
		$("input[name=btngravar]").val("");
	}else{

		$("input[name^='peca_qtde_antiga_']").each(function(){
			var qtde_antiga = $(this).val();
			if(qtde_antiga != '' && qtde_antiga != undefined){
				valores = qtde_antiga.split("|");

				// if(valores[1] != valores[2]){
				// 	alert('1');
				// 	submite = false;
				// 	confirmaMultiplo();
				// 	return;
				// }else{
				// 	submite = true;
				// 	alert('2');
				// }

				if(valores[2] != valores[3]){
					submite = false;
					confirmaMultiplo();
					return;
				}
			}
		});
		if(submite != false || valores.length == 0  ){
			document.frmpedido.submit();
		}
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
			if(msg[2] != msg[3]){
				td +='<tr height="20"><td>'+msg[0]+'</td><td>'+msg[1]+'</td><td>'+msg[2]+'</td><td>'+msg[3]+'</td></tr>';
			}
		}
	});

	Shadowbox.open({
		content:"<div style='background:#FFFFFF;height:100%;text-align:center;'>\
						<br><p style='font-size:14px;font-weight:bold'>Peças com Quantidade Multiplas</p>\
						<p style='font-weight:bold;'>\
						<table border='1' width='800' id='resultado' cellspacing='1' cellpadding='0' align='center'>\
						<tr height='20' class='menu_top'>\
							<td>Referência</td><td>Descrição</td><td>Qdte Digitada</td><td>Qtde Alterada</td>\
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
        height: 600,
        options: {
            onFinish: function(){
                $("#sb-nav-close").hide();
            },
            modal: true,
            overlayColor:'#000000'
        }
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

function retorna_peca(peca_referencia,peca_descricao,posicao){
	gravaDados("referencia"+posicao,peca_referencia);
	gravaDados("descricao"+posicao,peca_descricao);

    var qtde = $("#qtde" + posicao).val();

    if (qtde) {
        var referencia = $("#referencia" + posicao).val();
        var descricao = $("#descricao" + posicao).val();
        var qtde_antiga = $("input[name=peca_qtde_antiga_"+posicao+"]").val();

        getValidaMultiplo(qtde, referencia, descricao, qtde_antiga, posicao);
    }

	$("input[name=qtde"+posicao+"]").focus();
}

function gravaDados(name, valor){
	try {
		$("input[name="+name+"]").val(valor);
	} catch(err){
		return false;
	}
}

function FuncPesquisaPeca (peca_referencia, peca_descricao, tipo, posicao, alerta) {
	peca_referencia = $.trim(peca_referencia);
    peca_descricao  = $.trim(peca_descricao);

    if (alerta === undefined) {
        alerta = true;
    }

    <?php 

    $queryCatPosto = "SELECT categoria, parametros_adicionais
				      FROM tbl_posto_fabrica
				      WHERE posto = $login_posto
				      AND fabrica = $login_fabrica";

	$resCatPosto = pg_query($con, $queryCatPosto);

	$categoriaPosto = pg_fetch_result($resCatPosto, 0, 'categoria');
	$parametros_adicionais = pg_fetch_result($resCatPosto, 0, 'parametros_adicionais');
	$parametros_adicionais = json_decode($parametros_adicionais);
	
	$pedido_faturado_locadora = True;

	if (isset($parametros_adicionais->pedido_faturado_locadora) && $parametros_adicionais->pedido_faturado_locadora == 't') {
			
		$pedido_faturado_locadora = False;
	}  

	if (in_array($login_fabrica, [1]) && $categoriaPosto == 'Locadora' && $pedido_faturado_locadora) { ?> 
		
		var produto = document.frmpedido.produto.value;

		$.ajax({
			url: "pedido_blackedecker_cadastro.php",
			dataType: "GET",
			data: { 
				verifica_peca_lista_basica : true, 
				peca_referencia : peca_referencia,
				produto : produto
			},
			success: function(retorno) {
				
				var retorno = JSON.parse(retorno);
				
				if (retorno['msg'] == 'error') {

					alert("Esta peça precisa estar na lista básica do produto para ser adicionada");
					$('#referencia' + posicao).val("");

				} else {

					$("input[name=peca_qtde_antiga_"+posicao+"]").val('');

					url = "peca_pesquisa_lista_blackedecker_nv_semdepara.php?peca="+peca_referencia+"&descricao="+peca_descricao+"&posicao="+posicao;

					if ($.trim(peca_referencia).length > 2 || $.trim(peca_descricao).length > 2) {
						Shadowbox.open({
							content:	url,
							player:	"iframe",
							title:		"Pesquisa Peça",
							width:	800,
							height:	500
						});
		    		} else {
				        if (alerta) {
				            alert("Preencha toda ou parte da informação para realizar a pesquisa!");
				        }
					}
				}
			},
			fail : function(retorno) {

				alert("Erro ao validar peça");
			}
		});

	<?php } else { ?> 
		$("input[name=peca_qtde_antiga_"+posicao+"]").val('');

		url = "peca_pesquisa_lista_blackedecker_nv_semdepara.php?peca="+peca_referencia+"&descricao="+peca_descricao+"&posicao="+posicao;

		if ($.trim(peca_referencia).length > 2 || $.trim(peca_descricao).length > 2) {
			Shadowbox.open({
				content:	url,
				player:	"iframe",
				title: "Pesquisa Peça",
				width:	800,
				height:	500
			});
		} else {
	        if (alerta) {
	            alert("Preencha toda ou parte da informação para realizar a pesquisa!");
	        }
		}

	<?php } ?>
}

/*function FuncPesquisaPeca (peca_referencia, peca_descricao, peca_qtde) {
	var url = "";
	if (peca_referencia.value != "") {
		url = "peca_pesquisa_lista_blackedecker.php?peca=" + peca_referencia.value;
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=501,height=400,top=50,left=100");
		janela.referencia	= peca_referencia;
		janela.descricao	= peca_descricao;
		janela.qtde			= peca_qtde;
		janela.focus();
	}
}*/

function fnc_fora_linha (nome, seq) {
	var url = "";
	if (nome != "") {
		url = "pesquisa_fora_linha.php?nome=" + nome + "&seq=" + seq;
		janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=400,top=18,left=0");
		janela.focus();
	}
}

function uploadExcel(){
	$('#uploadExcel').show();
}




<!--
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
// -->

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
	<!--<tr>-->
		<? //hd 21119
		//<td align='center' bgcolor='#f4f4f4'>
		//<p align='justify'><font size='1'><b> Para que o pedido <? echo $pedido_blackedecker ?>
		<?//seja enviado para a fábrica às 11:45h, grave e finalize o pedido novamente, antes de sair da tela //de digitação de pedidos.</b></font></p>
		//</td>
		?>
	<!--	<td align='center' bgcolor='#f4f4f4'>
		<p align='justify'><font size='1'><b> Para que o pedido <? echo $pedido_blackedecker ?> seja enviado para a fábrica às 11h45, grave e finalize o pedido novamente, antes de sair da tela de digitação de pedidos.</b></font></p>
		</td>
	</tr>
	<tr>
		<td align='center' bgcolor='#f4f4f4'>
			<p align='justify'><font size='1' color='#FF0000'><b>  Caso seja necessário incluir ou cancelar algum item, após a manutenção, grave e finalize novamente.</b></font></p>
		</td>
	</tr>-->
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
	<td valign="middle" align="center" class='error' style="padding: 10px;">
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

	echo $erro ;
	echo (mb_detect_encoding($msg_erro, 'UTF-8', true)) ? utf8_decode($msg_erro) : ($msg_erro);
?>
	</td>
</tr>
</table>
<p>
<? } ?>

<table width="700" border="0" cellpadding="2" cellspacing="1" align="center">
	<tr>
		<td class='menu_top' colspan='2'>INFORMAÇÕES IMPORTANTES</td>
	</tr>

	<?
	// Não haverá mais pedido unificado para blackedecker
		if (($tipo_posto == 39 or $tipo_posto == 79 or $tipo_posto == 80 or $tipo_posto == 81) and 1==2) { ?>
	<tr class='table_line1' bgcolor='#F1F4FA'>
		<td>*** PROJETO DE UNIFICAÇÃO DOS PEDIDOS (DISTRIBUIDOR)</td>
		<td align='center'><b><a href='http://www.blackdecker.com.br/xls/procedimento_distribuidor.doc' target='_blank'>Clique aqui</a></b></td>
	</tr>
	<? } ?>

	<? if ($pedido_em_garantia == "t" and 1==2) { ?>
	<tr class='table_line1' bgcolor='#F1F4FA'>
		<td>*** PROJETO DE UNIFICAÇÃO DOS PEDIDOS (GARANTIA)</td>
		<td align='center'><b><a href='http://www.blackdecker.com.br/xls/procedimento_garantia.doc' target='_blank'>Clique aqui</a></b></td>
	</tr>
	<? } ?>

	<?
	/*
	<tr class='table_line1' bgcolor='#F1F4FA'>
		<td>*** CALENDÁRIO FISCAL</td>
		<td align='center'><b><a href='http://www.blackdecker.com.br/xls/calendario_fechamento.xls' target='_blank'>Clique aqui</a></b></td>
	</tr>
	*/
	?>
	<tr class='table_line1' bgcolor='#F1F4FA'>
		<td>*** ENVIAR PEDIDOS VIA ARQUIVO</td>
		<td align='center'><b><a href='pedido_upload.php'>Clique aqui</a></b></td>
	</tr>
	<tr class='table_line1' bgcolor='#F1F4FA'>
		<td>*** ENVIAR PEDIDOS VIA ARQUIVO <strong>XLS</strong> - Layout: peça e quantidade </td>
		<td align='center'><b><a href='javascript: uploadExcel();'>Clique aqui</a></b></td>
	</tr>
</table>

<br />

<form name="upload_excel" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype='multipart/form-data'>
	<div id="uploadExcel" style="display: none;">
		<?php 
			if (!$uploadExcelData) {
				$produto_id         = isset($_POST['produto'])            ? $_POST['produto'] : '';
				$produto_serie      = isset($_POST['produto_serie'])      ? $_POST['produto_serie'] : '';
				$produto_referencia = isset($_POST['produto_referencia']) ? $_POST['produto_referencia'] : '';
				$produto_descricao  = isset($_POST['produto_descricao'])  ? $_POST['produto_descricao'] : '';
				$produto_voltagem   = isset($_POST['produto_voltagem'])   ? $_POST['produto_voltagem'] : '';
				$produto_tipo       = isset($_POST['produto_tipo'])       ? $_POST['produto_tipo'] : '';
				$nota_fiscal        = isset($_POST['nota_fiscal'])        ? $_POST['nota_fiscal'] : '';
				$data_emissao       = isset($_POST['data_emissao'])       ? $_POST['data_emissao'] : '';
			}
		?>
		<input type="hidden" name="upload_excel"/>
		<input type="hidden" name="id_produto"         value="<?=$produto_id?>"/>
		<input type="hidden" name="produto_excel"      value="<?=$produto_id?>"/>     
		<input type="hidden" name="referencia_excel"   value="<?=$produto_referencia?>"/>  
		<input type="hidden" name="descricao_excel"    value="<?=$produto_descricao?>"/>  
		<input type="hidden" name="voltagem_excel"     value="<?=$produto_voltagem?>"/>     
		<input type="hidden" name="tipo_excel"         value="<?=$produto_tipo?>"/>     
		<input type="hidden" name="nota_fiscal_excel"  value="<?=$nota_fiscal?>"/> 
		<input type="hidden" name="data_emissao_excel" value="<?=$data_emissao?>"/> 
		<input type="hidden" name="serie_excel"        value="<?=$produto_serie?>"/>
		<input type="file"   name="arquivo_excel"/>

		<input type="submit" id="uploda_arquivo_black" value="Enviar Arquivo" />
		<br /> <br />
		<div id="msg_aguarde" style="display: none;">Aguarde o processamento do arquivo, pode demorar alguns minutos. </div>
	</div>

</form>


<form name="frmpedido" method="post" action="<? echo $PHP_SELF ?>">
<input type="hidden" name="linha" value="<?echo $linha?>">

<?/*
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
*/
if (count($naoAdicionado) > 0) { ?>
	<table width="700" border="0" cellpadding="2" cellspacing="1" align="center">
		<tr>
			<td class='menu_top' colspan='2'>PEÇAS NÃO ADICIONADAS</td>
		</tr>
		<tr>
			<td bgcolor='#B0C4DE' align='center' colspan='7'>
				<font face='arial,verdana' size='-1'><strong>
					NÃO FOI POSSÍVEL REALIZAR O PEDIDO DA(S) PEÇA(S)
				</strong></font>
				<table>
					<thead>
						<th>Peça</th>
						<th>Quantidade</th>
					</thead>
					<tbody>
						<?php foreach ($naoAdicionado as $referenciaPeca => $qtdePeca) {
							echo "<tr>";
							echo "<td align='center'>" . $referenciaPeca . "</td>";
							echo "<td align='center'>" . $qtdePeca . "</td>";
							echo "</tr>";
						} ?>
					</tbody>
				</table>
				<font face='arial,verdana' size='-1'><strong>
					VERIFIQUE A LISTA BÁSICA DO PRODUTO PARA ADICIONAR PEÇAS
				</strong></font>
			</td>
		</tr>
	</table>
	<br>
<?php }
//Chamado: 1757
if ( file_exists("bloqueio_pedidos/periodo_fiscal.txt") ) {
	$fiscal = fopen("bloqueio_pedidos/periodo_fiscal.txt", "r");
	$ler_fiscal = fread($fiscal, filesize("bloqueio_pedidos/periodo_fiscal.txt"));
	fclose($fiscal);
}
if($login_fabrica <>3){
?>

<table width="600" border="1" cellpadding="2" cellspacing="1" align="center">
<tr>
	<td colspan='2' align='center'><font face='verdana' size='2' color='#0000ff'><b>Data limite para colocação de pedidos neste mês:<br><font color='#ff0000'><? echo $ler_fiscal; ?></font></font></td>
</tr>
</table>

<?}?>

<?/*<!--
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
-->*/?>

<br>
<br>
<?
if($promocao=="t"){
		echo "<table width='500' border='0' align='center' cellpadding='5' cellspacing='2' style='border-collapse: collapse' bordercolor='000000'>";
		echo "<TR align='center' bgcolor='#336699'>";
		//echo "<form method='post' name='frm_condicao' action='$PHP_SELF'>";
		echo "	<TD colspan='3' height='40' style='font-family: verdana, arial ; font-size: 16px; font-size: 14px; color:#FFFFFF;'><B>COMUNICADO PROMOÇÃO</B></TD>";
		echo "<TR align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000' bgcolor='#C1E0FF' onmouseout=\"this.bgColor='#C1E0FF'\">";
		echo "<TD><p align='justify' style='font-family: verdana, arial ; font-size: 10px; color:#000000'>$comentario_p</p></TD>";

	//	echo "</form>";
		echo "</TR>";
		echo "</table>";
		echo "<br><br>\n";
}

?>


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
<br><br>
*/?>



<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
<tr>
	<td align='left' class="menu_top">
		<b>Posto</b>
	</td>

	<td align='left' class="menu_top">
		<b>Razão Social</b>
	</td>

	<td align='left' class="menu_top">
		<b>Condição de Pagamento</b>
	</td>
<?/*<!--<td align='left' class="menu_top">
		<b>Bloco de Os's</b>
	</td>-->*/?>
</tr>
<tr>
	<td align='center' class="table_line1" valign='top'>
		<b><? echo $codigo_posto; ?></b>
	</td>

	<td align='left' class="table_line1" valign='top'>
		<b><? echo $nome_posto; ?></b>
	</td>

	<td align='center' nowrap class="table_line1" valign='top'>


	<select name="condicao" id='cond_pagamento' class="frm">
<?php
	//nao deixar mostrar EM GARANTIA, pois existe uma tela para pedido em garantia que é habilitada no menu.
   	$sql = "
		SELECT tipo_posto, categoria
		FROM tbl_posto_fabrica
		WHERE posto = $login_posto
		AND fabrica = $login_fabrica;
	";
	
	$res = pg_query($con, $sql);
	$tipo_posto = pg_fetch_result($res, 0, "tipo_posto");
	$categoria = pg_fetch_result($res, 0, "categoria");
	$aux_data = date("j");
	
	//HD 100300 - Pedido de promoção automatica
	$abrir = fopen("bloqueio_pedidos/libera_promocao_black.txt", "r");
	$ler = fread($abrir, filesize("bloqueio_pedidos/libera_promocao_black.txt"));
	fclose($abrir);
	$conteudo_p = explode(";;", $ler);
	$data_inicio_p = $conteudo_p[0];
	$data_fim_p    = $conteudo_p[1];
	$comentario_p  = $conteudo_p[2];
	$promocao = "f";
	if (strval(strtotime(date("Y-m-d H:i:s")))  < strval(strtotime("$data_fim_p"))) {
		if (strval(strtotime(date("Y-m-d H:i:s")))  >= strval(strtotime("$data_inicio_p"))) {
			$promocao = "t";
		}
	}
	
	$condicoes = array();

	$sql_black_posto_condicao = "
		SELECT DISTINCT(id_condicao) as condicao
		FROM tbl_black_posto_condicao
		JOIN tbl_condicao ON tbl_condicao.condicao = tbl_black_posto_condicao.id_condicao
		WHERE posto = $login_posto
		AND tbl_condicao.fabrica = $login_fabrica
		AND id_condicao <> 62
	"; /*Não mostra "Garantia" */

	$res_black_posto_condicao = pg_query($con, $sql_black_posto_condicao);

	if (pg_num_rows($res_black_posto_condicao) > 0) {
		while ($fetch = pg_fetch_assoc($res_black_posto_condicao)) {
		    $condicoes[] = $fetch['condicao'];
		}
	}  else {
		$condicoes[] = "51";
	}

	$sql_tipo_posto_condicao = "
   		SELECT DISTINCT( tbl_condicao.condicao) AS condicao,
				tbl_condicao.promocao AS promocao,
				tbl_condicao.dia_inicio,
				tbl_condicao.dia_fim
		FROM tbl_condicao
			JOIN tbl_tipo_posto_condicao ON tbl_tipo_posto_condicao.condicao = tbl_condicao.condicao
		WHERE ((tbl_condicao.dia_inicio <= $aux_data AND tbl_condicao.dia_fim >= $aux_data) OR (tbl_condicao.dia_inicio IS NULL AND tbl_condicao.dia_fim IS NULL) OR (tbl_condicao.promocao IS NOT TRUE))
		AND (tbl_tipo_posto_condicao.tipo_posto = $tipo_posto OR tbl_tipo_posto_condicao.categoria = '$categoria')
		AND tbl_condicao.visivel IS TRUE
   	";

   	if ($posto_desbloqueado && $promocao == 't') {
		$sql_tipo_posto_condicao .= "
          UNION
            SELECT  DISTINCT( tbl_condicao.condicao) AS condicao,
				tbl_condicao.promocao AS promocao,
				tbl_condicao.dia_inicio,
				tbl_condicao.dia_fim
            FROM    tbl_condicao
			JOIN tbl_tipo_posto_condicao ON tbl_tipo_posto_condicao.condicao = tbl_condicao.condicao
            WHERE   tbl_condicao.fabrica    = $login_fabrica
            AND     tbl_condicao.promocao   IS TRUE
            AND     tbl_condicao.visivel    IS TRUE
            AND     (
                        EXTRACT(DAY FROM CURRENT_DATE) BETWEEN tbl_condicao.dia_inicio AND tbl_condicao.dia_fim
                    OR  (
                            tbl_condicao.dia_inicio IS NULL
                        AND tbl_condicao.dia_fim    IS NULL
                        )
                    )
			AND (tbl_tipo_posto_condicao.tipo_posto = $tipo_posto OR tbl_tipo_posto_condicao.categoria = '$categoria')
	      ORDER BY      condicao
	    ";
	}

    $res_tipo_posto_condicao = pg_query($con, $sql_tipo_posto_condicao);
    $aux_total = pg_num_rows($res_tipo_posto_condicao);

    for ($y = 0; $y < $aux_total; $y++) { 
    	$tbl_promocao = pg_fetch_result($res_tipo_posto_condicao, $y, 'promocao');
    	$dia_inicio   = pg_fetch_result($res_tipo_posto_condicao, $y, 'dia_inicio');
    	$dia_fim      = pg_fetch_result($res_tipo_posto_condicao, $y, 'dia_fim');
    	$aux_promocao = $promocao;

    	if ($tbl_promocao == 't' && strlen($dia_inicio) == 0 && strlen($dia_fim) == 0) {
			$aux_promocao = 't';
		}
    	
    	if ($posto_desbloqueado) {
	    	if ($tbl_promocao == 't' && $aux_promocao == 'f') {
	    		continue;
	    	} else {
	    		$aux_condicao = pg_fetch_result($res_tipo_posto_condicao, $y, 'condicao');
	    		$condicoes[] = $aux_condicao;
	    	} 
		}else{
			if($tbl_promocao == 't') {
				continue;
			}

		}
    }

	$condicoes = array_unique ($condicoes);
	foreach ($condicoes as $xcondicao) {
		$sql = "
			SELECT descricao, visivel, promocao
			FROM tbl_condicao
			WHERE condicao = $xcondicao
			LIMIT 1
		";
		
		$res       = pg_query($con, $sql);
		$descricao = pg_fetch_result($res, 0, 'descricao');
		$visivel   = pg_fetch_result($res, 0, 'visivel');
		$promocao  = pg_fetch_result($res, 0, 'promocao');
		if (!$posto_desbloqueado and $promocao =='t') {
			continue;
		}
		if (strlen($descricao) > 0 && $visivel == 't') {
			?> <option value="<?=$xcondicao;?>" <?if($condicao == $xcondicao) echo " selected " ?>><?=$descricao;?></option> <?php
		} else {
			continue;
		}
	} ?>
	</select>
	<br>
	</td>
</tr>
</table>

<br>
<?
if(strlen($rows_pecas)==0){
	$rows_pecas = 15;
}
?>

<?php 

$queryCatPosto = "SELECT categoria, parametros_adicionais
			      FROM tbl_posto_fabrica
			      WHERE posto = $login_posto
			      AND fabrica = $login_fabrica";

$resCatPosto = pg_query($con, $queryCatPosto);

$categoriaPosto = pg_fetch_result($resCatPosto, 0, 'categoria');
$parametros_adicionais = pg_fetch_result($resCatPosto, 0, 'parametros_adicionais');
$parametros_adicionais = json_decode($parametros_adicionais);

$pedido_faturado_locadora = True;

if (isset($parametros_adicionais->pedido_faturado_locadora) && $parametros_adicionais->pedido_faturado_locadora == 't') {
		
	$pedido_faturado_locadora = False;
}  

if (in_array($login_fabrica, [1]) && $categoriaPosto == 'Locadora' && $pedido_faturado_locadora) {
 	
 	if (!$uploadExcelData) {
		$produto_id         = isset($_POST['produto'])            ? $_POST['produto'] : '';
		$produto_serie      = isset($_POST['produto_serie'])      ? $_POST['produto_serie'] : '';
		$produto_referencia = isset($_POST['produto_referencia']) ? $_POST['produto_referencia'] : '';
		$produto_descricao  = isset($_POST['produto_descricao'])  ? $_POST['produto_descricao'] : '';
		$produto_voltagem   = isset($_POST['produto_voltagem'])   ? $_POST['produto_voltagem'] : '';
		$produto_tipo       = isset($_POST['produto_tipo'])       ? $_POST['produto_tipo'] : '';
		$nota_fiscal        = isset($_POST['nota_fiscal'])        ? $_POST['nota_fiscal'] : '';
		$data_emissao       = isset($_POST['data_emissao'])       ? $_POST['data_emissao'] : '';
	}

?>
	<div class="produto_info">
		<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
			<tbody>
				<tr>
					<td align="left" class="menu_top">
						<b>N. Série do Produto</b>
					</td>

					<td align="left" class="menu_top">
						<b>Referência</b>
					</td>

					<td align="left" class="menu_top">
						<b>Descrição</b>
					</td>

					<td align="left" class="menu_top">
						<b>Voltagem</b>
					</td>

					<td align="left" class="menu_top">
						<b>Tipo</b>
					</td>

					<td align="left" class="menu_top">
						<b>N. Fiscal</b>
					</td>

					<td align="left" class="menu_top">
						<b>Emissão</b>
					</td>
				</tr>
				<tr>
					<td align="center" class="table_line1" valign="top" nowrap="">
						<input class="frm" type="text" name="produto_serie" size="15" maxlength="30" value="<?=$produto_serie?>">&nbsp;<img src="imagens/btn_lupa_novo.gif" style="cursor:pointer" border="0" alt="Clique para pesquisar pelo número de série do produto" align="absmiddle" onclick="javascript: document.frmpedido.produto.value = ''; document.frmpedido.produto_referencia.value = ''; document.frmpedido.produto_descricao.value = ''; document.frmpedido.produto_voltagem.value = ''; document.frmpedido.nota_fiscal.value = ''; document.frmpedido.data_emissao.value = ''; document.frmpedido.value = '';fnc_pesquisa_locacao (document.frmpedido.produto_serie,'t',0)">
					</td>
					<td align="center" class="table_line1" valign="top" nowrap="">
						<input id="produto_id" class="frm" type="hidden" name="produto" value="<?=$produto_id?>">
						<input class="frm" type="text" name="produto_referencia" size="13" value="<?=$produto_referencia?>">&nbsp;<img src="imagens/btn_lupa_novo.gif" style="cursor:pointer" border="0" alt="Clique para pesquisar pela referência do produto" align="absmiddle" onclick="javascript: document.frmpedido.produto.value = ''; document.frmpedido.produto_serie.value = ''; document.frmpedido.produto_descricao.value = ''; document.frmpedido.produto_voltagem.value = ''; document.frmpedido.nota_fiscal.value = ''; document.frmpedido.data_emissao.value = ''; document.frmpedido.value = '';fnc_pesquisa_locacao (0,'t',document.frmpedido.produto_referencia)">
					</td>					
					<td align="left" class="table_line1" valign="top">
						<input class="frm" type="text" name="produto_descricao" size="40%" value="<?=$produto_descricao?>" readonly="">
					</td>
					<td align="left" class="table_line1" valign="top">
						<input class="frm" type="text" name="produto_voltagem" size="10" value="<?=$produto_voltagem?>" readonly="">
					</td>
					<td align="left" class="table_line1" valign="top">
						<input id="produto_tipo" class="frm" type="text" name="produto_tipo" size="10" value="<?=$produto_tipo?>" readonly="">
					</td>
					<td align="left" class="table_line1" valign="top">
						<input class="frm" type="text" name="nota_fiscal" size="7" value="<?=$nota_fiscal?>" readonly="">
					</td>
					<td align="left" class="table_line1" valign="top">
						<input class="frm" type="text" name="data_emissao" size="13" value="<?=$data_emissao?>" readonly="">
					</td>
				</tr>
			</tbody>
		</table>
	</div>
	<br>
	<div class="btn_lista_basica">
		<button type="button" class="btn btn-primary" onclick="abrir_lista_basica()">Lista Básica
		</button>
	</div>
	<script type="text/javascript">

		function abrir_lista_basica() {
			
			var width = 800;
			var height = 600;
			var left = 99;
			var top = 99;

			var produto_tipo = $("#produto_tipo").val();
			var produto_id   = $("#produto_id").val();

			var url = 'peca_consulta_por_produto.php?produto=' + produto_id + '&tipo=' + produto_tipo;
			//console.log(url);

			janela = window.open(url,'janela', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no');
		}

		function fnc_pesquisa_locacao (produto_serie, faturado, produto_referencia) {
			var url = "";		

			if(produto_serie != 0){
				var t_serie = produto_serie.value.length;
				url = "pesquisa_locacao.php?serie=" + produto_serie.value +"&faturado="+faturado;	
			}

			if (produto_referencia != 0){
				var t_referencia = produto_referencia.value.length;
				url = "pesquisa_locacao.php?referencia=" + produto_referencia.value +"&faturado="+faturado;	
			}

			if (t_serie >= 3 || t_referencia >= 3) {

				janela = window.open(url, "janela", "toolbar=no, location=yes, status=yes, scrollbars=yes, directories=no, width=701, height=500, top=18, left=0");
				janela.num_serie 	= document.frmpedido.produto_serie;
				janela.produto      = document.frmpedido.produto;
				janela.descricao    = document.frmpedido.produto_descricao;
				janela.referencia   = document.frmpedido.produto_referencia;
				janela.voltagem     = document.frmpedido.produto_voltagem;
				janela.tipo         = document.frmpedido.produto_tipo;
				janela.nota_fiscal  = document.frmpedido.nota_fiscal;
				janela.data_emissao = document.frmpedido.data_emissao;

			} else {
				alert("Digite pelo menos 3 caracteres!");
			}
		}

	</script>
<br>
<?php } ?>

<input type="hidden" id="rows_pecas" name="rows_pecas" value="<?=$rows_pecas?>"/>
<table width="700" border="0" cellpadding="2" cellspacing="2" align="center" class="tabela_pedido">
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
	<!--<td width="15%" align='center' class="menu_top">
		<b>Valor Unitário</b>
	</td>
	<td width="15%" align='center' class="menu_top">
		<b>Total</b>
	</td>-->
</tr>


<?

if(strlen($pecas_pedido_excel) > 0){
	$listaPeca = explode("|", $pecas_pedido_excel);
	$qtdePeca = count($listaPeca);
}


echo "<tbody class='linha_pedido_black'>";

for ($i = 0 ; $i < $rows_pecas ; $i ++) {

	if(strlen($pecas_pedido_excel) > 0){

		$pecaPedido = "";
		$qtdePedido = "";

		if($i < $qtdePeca){

			$pedido = explode("/", $listaPeca[$i]);

			$pecaPedido = trim($pedido[0]);

			$qtdePedido = trim($pedido[1]);


			$xreferencia = str_replace(".","",$pecaPedido);
			$xreferencia = str_replace(",","",$xreferencia);
			$xreferencia = str_replace("-","",$xreferencia);
			$xreferencia = str_replace("/","",$xreferencia);
			$xreferencia = str_replace(" ","",$xreferencia);


			//2543280
				$sql =	"SELECT z.peca                                ,
								z.referencia       AS peca_referencia ,
								z.descricao        AS peca_descricao  ,
								z.peca_fora_linha                     ,
								z.de                                  ,
								z.para                                ,
								z.peca_para                           ,
								tbl_peca.descricao AS para_descricao
						FROM (
								SELECT  y.peca               ,
										y.referencia         ,
										y.descricao          ,
										y.peca_fora_linha    ,
										tbl_depara.de        ,
										tbl_depara.para      ,
										tbl_depara.peca_para
								FROM (
										SELECT  x.peca                                      ,
												x.referencia                                ,
												x.descricao                                 ,
												tbl_peca_fora_linha.peca AS peca_fora_linha
										FROM (
												SELECT  tbl_peca.peca       ,
														tbl_peca.referencia ,
														tbl_peca.descricao
												FROM tbl_peca
												WHERE tbl_peca.fabrica = $login_fabrica
												AND   tbl_peca.ativo IS TRUE
												AND   tbl_peca.produto_acabado IS NOT TRUE
												AND   tbl_peca.acessorio IS NOT TRUE";
				if (strlen($xreferencia) > 0) $sql .= " AND UPPER(TRIM(tbl_peca.referencia_pesquisa)) = UPPER(TRIM('$xreferencia'))";
				$sql .= "					) AS x
										LEFT JOIN tbl_peca_fora_linha ON tbl_peca_fora_linha.peca = x.peca
									) AS y
								LEFT JOIN tbl_depara ON tbl_depara.peca_de = y.peca
							) AS z
						LEFT JOIN tbl_peca ON tbl_peca.peca = z.peca_para
						ORDER BY z.descricao";
			$res = pg_exec ($con,$sql);

			$peca_referencia = trim(@pg_result($res,0,peca_referencia));
			$peca_descricao  = trim(@pg_result($res,0,peca_descricao));
			$peca_para       = trim(@pg_result($res,0,peca_para));
			$para            = trim(@pg_result($res,0,para));
			$para_descricao  = trim(@pg_result($res,0,para_descricao));

			if(!empty($para)) {
				$descricao_peca = $para_descricao;
				$pecaPedido = $para;
			} else {
				$descricao_peca = $peca_descricao;
			}

		}

	}

	$referencia = "referencia" . $i;
	$descricao  = "descricao" . $i;
    $qtde_multi = "qtde_multi" . $i;
	$qtde       = "qtde" . $i;
	$erro_linha = "erro_linha" . $i;

	$referencia = $$referencia;
	$descricao  = $$descricao;
	$qtde       = $$qtde;
    if (!empty($$qtde_multi)) {
        $qtde = $$qtde_multi;
    }
	$erro_linha .= $$erro_linha;

	if(strlen($pecas_pedido_excel) > 0){
		if(strlen($referencia) ==  0){
			$referencia = $pecaPedido;
			$qtde = $qtdePedido;
			$descricao = $descricao_peca;
		}
	}

	$prox = $i + 1;
	$done = 14;

	$cor_erro = "#ffffff";
	if ($erro_linha == 1) $cor_erro = "#FFCCCC";

	if ($linha_erro == $i and strlen ($msg_erro) > 0) $cor_erro = "#FFCCCC";

?>

<?/*<!--<tr bgcolor="<?echo $cor_erro?>">
	<td align='center'>
		<input type="text" name="referencia<? echo $i ?>" onblur="javascript:fnc_fora_linha(this.value, <?echo $i?>)" size="15" maxlength="15" value="<? echo $referencia ?>" class="textbox" style="width:100px" onFocus="nextfield ='qtde<?echo $i?>'">
	</td>
	<td align='center'>
		<input type="text" name="qtde<? echo $i ?>" size="4" maxlength="4" value="<? echo $qtde ?>" class="textbox" style="width:40px " <? if ($prox <= $done) { echo "onFocus=\"nextfield ='referencia$prox'\""; }else{ echo "onFocus=\"nextfield ='done'\"";}?>>
	</td>
</tr>-->*/?>

<tr bgcolor="<?echo $cor_erro?>">
	<input type='hidden' name='peca_qtde_antiga_<? echo $i ?>' value=''> <!--hd_chamado=2543280-->
	<td align='center' bgcolor="<?echo $cor_erro?>">
		<input type="text" name="referencia<? echo $i ?>" id="referencia<? echo $i ?>" size="15" maxlength="15" value="<? echo $referencia ?>"  onFocus="nextfield ='qtde<?echo $i?>'" onChange="javascript: FuncPesquisaPeca(window.document.frmpedido.referencia<? echo $i ?>.value,window.document.frmpedido.descricao<? echo $i ?>.value,'referencia',<? echo $i ?>, false);">
		<img src='imagens/btn_buscar5.gif' style="cursor:pointer" alt="Clique para pesquisar por referência do componente" border='0' hspace='5' align='absmiddle' onclick="javascript: FuncPesquisaPeca(window.document.frmpedido.referencia<? echo $i ?>.value,window.document.frmpedido.descricao<? echo $i ?>.value,'referencia',<? echo $i ?>);">
	</td>
	<td align='center' bgcolor="<?echo $cor_erro?>">
		<input type="text" name="descricao<? echo $i ?>" id="descricao<? echo $i ?>" size="30" maxlength="30" value="<? echo $descricao ?>"  onFocus="nextfield ='qtde<?echo $i?>'">
		<?/*<!--<img src='imagens/btn_buscar5.gif' style="cursor:pointer" alt="Clique para pesquisar por descrição do componente" border='0' hspace='5' align='absmiddle' onclick="javascript: fnc_pesquisa_peca_lista ('',window.document.frmpedido.referencia<? echo $i ?>,window.document.frmpedido.descricao<? echo $i ?>,'','descricao')">-->*/?>
	</td>
	<td align='center' bgcolor="<?echo $cor_erro?>">
        <input type="hidden" id="qtde_multi<?php echo $i ?>" name="qtde_multi<?php echo $i ?>" value="" />
		<input type="text" name="qtde<? echo $i ?>" id="qtde<? echo $i ?>" size="6" maxlength="4" value="<? echo $qtde ?>" onKeyUp="getValidaMultiplo(window.document.frmpedido.qtde<? echo $i ?>.value,window.document.frmpedido.referencia<? echo $i ?>.value,window.document.frmpedido.descricao<? echo $i ?>.value,window.document.frmpedido.peca_qtde_antiga_<? echo $i ?>.value,<? echo $i ?>);"  <? if ($prox <= $done) { echo "onFocus=\"nextfield ='referencia$prox'\""; }else{ echo "onFocus=\"nextfield ='done'\"";}?>>

 	<!-- 	<input type="text" name="qtde<? echo $i ?>" id="qtde<? echo $i ?>" size="4" maxlength="4" value="<? echo $qtde ?>" <? if ($prox <= $done) { echo "onFocus=\"nextfield ='referencia$prox'\""; }else{ echo "onFocus=\"nextfield ='done'\"";}?>>
 -->
 </td>
 <!--<td>
 	<input type="text" name="valor_unitario<?=$i?>" size="10" value="<?=$valor_unitario?>">
 </td>
 <td>
 	<input type="text" name="total_linha<?=$i?>" size="10" value="<?=$total?>">
 </td>
</tr>-->

<? } ?>
</tbody>

<tr>
	<td align="center">
		<button type="button" onclick="adicionarLinha()">Adicionar Linha</button>
		<span title="Será adicionado mais 15 linhas ao pedido" style="color:red;font-weight:bold"><img src="imagens/help.png"></span>
		<input type="hidden" id="qtde_campos" name="qtde_campos" value="<?=$i?>">
	 </td>
	<td colspan="2"></td>
	<!--<td align="right"><b> Total: </b></td>
	<td> <input type="text" size="10" name="total_geral" value="<?= $total_geral?>"> </td>-->
</tr>


<tr>
	<td align='center' colspan='5'>
		<br>
		<input type="hidden" id='bntgravar' name="btngravar" value="">
		<?/*<!--
		<img src="imagens/gravar.gif" onclick="window.document.frmpedido.btngravar.value='1' ; frmpedido.submit() " >
		-->*/?>
<? /*HD 1102*/  ?>

		<!-- <img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frmpedido.btngravar.value == '' ) { document.frmpedido.btngravar.value='Gravar' ; document.frmpedido.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar pedido" border='0' style='cursor: pointer'> -->
		<img id='btn_img' src='imagens/btn_gravar.gif' onclick="javascript: if (document.frmpedido.btngravar.value == '' ) { $('#msg_aguarde_gravar').show(400, function(){
			document.frmpedido.btngravar.value='Gravar' ; verificaDePara();

		});   } else { alert ('Aguarde submissão') }" ALT="Gravar pedido" border='0' style='cursor: pointer'>

	</td>
</tr>
	
</table>
<br>

	<div align='center' id="msg_aguarde_gravar" colspan='5'>Aguarde...</div>
	

<table width="700" border="0" cellpadding="5" cellspacing="2" align="center">
<!--<tr>
	<td align='center' bgcolor='#f4f4f4'>
		<p align='justify'><font size='1'><b>PARA CONTINUAR A DIGITAR ITENS NESTE PEDIDO, BASTA GRAVAR E EM SEGUIDA CONTINUAR DIGITANDO.</b></font></p>
	</td>
</tr>
<tr>
	<td align='center' bgcolor='#f4f4f4'>
		<p align='justify'><font size='1' color='#FF0000'><b>AVISO: APÓS GRAVAR O SEU PEDIDO, IRÁ APARECER O RESUMO DOS ITENS LANÇADOS E ABAIXO DESTE RESUMO, TERÁ O BOTÃO DE FINALIZAÇÃO QUE SOMENTE SERÁ USADO QUANDO NÃO EXISTIREM MAIS ITENS A SEREM LANÇADOS NESTE PEDIDO.</b></font></p>
	</td>
</tr>-->
	<input type="hidden" name="numero_pedido" value="<?=$cook_pedido?>">
</form>
</table>

<?

if(strlen(trim($cook_pedido))==0){

	$sql = "select pedido, seu_pedido, finalizado from tbl_pedido
		where finalizado is not null
		and exportado is null
		and fabrica = $login_fabrica
		and posto = $login_posto
	   	and admin is null
		AND     tbl_pedido.pedido_os         IS NOT TRUE
		AND     tbl_pedido.pedido_acessorio  IS NOT TRUE
		AND     tbl_pedido.pedido_sedex      IS NOT TRUE
		and		tbl_pedido.tipo_pedido = 86
		AND     tbl_pedido.status_pedido <> 14
		order by data desc limit 1 ";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){
		$mostrar_msg_pedido_finalizado = true;
		$cook_pedido 			= pg_fetch_result($res, 0, pedido);
		$pedido_blackedecker	= substr(pg_fetch_result($res, 0, seu_pedido), 3);
		$data_finalizou 	= substr(pg_fetch_result($res, 0, finalizado), 11, 5);
		$status_pedido = pg_fetch_result($res, 0, status_pedido);

	}

}else{
	$sql_qtde_pecas = "SELECT  count(pedido_item) as qtde_pecas,finalizado from tbl_pedido_item join tbl_pedido using(pedido) where pedido = $cook_pedido group by finalizado";
	$res_qtde_pecas = pg_query($con, $sql_qtde_pecas);
	$qtde_pecas = pg_fetch_result($res_qtde_pecas, 0, qtde_pecas);
	$finalizado = pg_fetch_result($res_qtde_pecas, 0, finalizado);

	if(empty($finalizado)) {$mostrar_msg_pedido_finalizado = false;}
}

if ( (strlen ($cook_pedido) > 0 OR strlen($pedido) > 0) and $mostrar_msg_pedido_finalizado == false and $qtde_pecas > 0 ) {
?>
<br>

<form name="pedido_recalcula" method="POST">
    <table width="700" border="0" cellpadding="3" cellspacing="1" align="center">
        <tr class="table_line1" bgcolor="#F1F4FA">
            <td align="center">
                Trocar Condição de Pagamento
                <select name="condicao_pagamento_trocar">
                    <?php foreach ($condicoes as $xcondicao) {
							$sql = "
								SELECT descricao, visivel
								FROM tbl_condicao
								WHERE condicao = $xcondicao
								LIMIT 1
							";
							$res       = pg_query($con, $sql);
							$descricao = pg_fetch_result($res, 0, 'descricao');
							$visivel   = pg_fetch_result($res, 0, 'visivel');

							if (strlen($descricao) > 0 && $visivel == 't') {
								?> <option value="<?=$xcondicao;?>" <?php if ($condicao == $xcondicao) echo 'selected="SELECTED"' ?>><?=$descricao;?></option> <?php
							} else {
								continue;
							}
						} ?>
                </select>
                <input type="hidden" name="pedido_recalcula" value="<?= $cook_pedido ?>">
                <input type="submit" name="btn_recalcula" value="Recalcular">
            </td>
        </tr>
    </table>
</form>

<table width="700" border="0" cellpadding="3" cellspacing="1" align="center">
<tr>
	<td colspan="6" align="center" class='menu_top'>
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
	<td width="10%" align='center' class="menu_top">
		<b>Estoque</b>
	</td>
	<td width="10%" align='center' class="menu_top">
		<b>Previsão</b>
	</td>
</tr>

<?

	$pedido = $cook_pedido;
	if($login_fabrica == 3) $pedido = $_GET["pedido"];
	$sql = "SELECT	a.oid    ,
					a.*      ,
					referencia,
					descricao,
					tbl_peca.parametros_adicionais
			FROM	tbl_peca
			JOIN	(
						SELECT	tbl_pedido_item.oid,tbl_pedido_item.*
						FROM	tbl_pedido_item
						JOIN    tbl_pedido USING(pedido)
						WHERE	pedido = $pedido
						AND     fabrica = $login_fabrica
					)
					a ON tbl_peca.peca = a.peca
					ORDER BY a.pedido_item";
	$res = pg_query ($con,$sql);
	$total = 0;

	for ($i = 0 ; $i < @pg_num_rows ($res) ; $i++) {


		$parametros_adicionais = json_decode(pg_fetch_result($res, $i, "parametros_adicionais"), true);

		$estoque 	= ucfirst($parametros_adicionais["estoque"]);
		$previsao 	= mostra_data($parametros_adicionais["previsao"]);

		if($estoque == "Disponivel" or $estoque == "Disponível"){
			$previsao = " - ";
		}


		$cor = ($i % 2 == 0) ? '#F1F4FA' : "#F7F5F0";

		echo "<tr bgcolor='$cor'>";
		echo "<td width='25%' align='left' class='table_line1' nowrap>";

		echo "<a href='$PHP_SELF?delete=" . pg_fetch_result ($res,$i,pedido_item) . "&pedido=$pedido'>";

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

		echo "<td width='10%' align='right' class='table_line1'>";
		echo $estoque;
		echo "</td>";

		echo "<td width='10%' align='center' class='table_line1'>";
		echo $previsao;
		echo "</td>";

		echo "</tr>";

		$total = $total + (pg_fetch_result ($res,$i,preco) * pg_fetch_result ($res,$i,qtde));
	}
?>

<tr>
	<td align="center" colspan="5" class="menu_top">
		<b>T O T A L</b>
	</td>
	<td align='right' class="menu_top" style='text-align:right'>
		<b>
		<? echo number_format ($total,2,",",".") ?>
		</b>
	</td>
	<!--<td class="menu_top"></td>
	<td class="menu_top"></td>-->
</tr>
</table>


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
$res = @pg_query ($con,$sql);

if (@pg_num_rows($res) > 0){
	$link  = "javascript:PedidoPendente();";
	echo "
			<script>
			function PedidoPendente(){
				if(confirm('UNIFICAÇÃO DOS PEDIDOS.\\n\\nDeseja somar as pendências do pedido ".trim(pg_fetch_result($res,0,pedido_mfg))." neste novo pedido ?\\n\\nPara confirmar clique em \"OK\",\\ncaso contrário, clique em \"Cancelar\".') == true){
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
		<!--<br><a href="<? echo $link; ?>"><img src='imagens/btn_finalizar.gif' border='0'></a><br><br>-->
		<br><a href="#" id="finalizar"><img src='imagens/btn_finalizar.gif' border='0'></a><br>
		<?if($_GET['ver_log'] == true){?>
		<button class="btn btn-warning" type="button" id="visualiza_log_item" data-pedido="<?=$pedido?>" >Log Itens Pedido</button><br><br>
	<?php } ?>
	</td>
</tr>

<!--<tr>
	<td align='center' bgcolor='#f4f4f4'>
		<p align='justify'><font size='1'><b>CASO JÁ TENHA TERMINADO DE DIGITAR OS ITENS E QUEIRA PASSAR PARA A PRÓXIMA TELA, CLIQUE EM FINALIZAR ACIMA.</b></font></p>
	</td>
</tr>-->

<tr >
	<td align='center' bgcolor='#f4f4f4'><p align='center'><font size='1'><b> A previsão informada refere-se a disponibilidade da peça na fábrica. Para entrega é necessário considerar o prazo de envio de acordo com sua região. <br> Previsão sujeita a alteração.</b></font></p>
	</td>
</tr>


</table>

<?
}

if(strlen(trim($cook_pedido))>0){

	$sql_status_pedido = "SELECT status_pedido, finalizado ,current_timestamp as agora FROM tbl_pedido WHERE pedido = $cook_pedido and fabrica = $login_fabrica ";
	$res_status_pedido = pg_query($con, $sql_status_pedido);
	$status_pedido = pg_fetch_result($res_status_pedido, 0, status_pedido);
	$data_finalizou = substr(pg_fetch_result($res_status_pedido, 0, finalizado), 11, 5);
	$hora_agora = substr(pg_fetch_result($res_status_pedido, 0, agora), 11, 5);
}


if($data_finalizou > "11:30" and $data_finalizou < "20:59" and $hora_agora > "11:45"){
	$data_mostrar = "21H00";
}else{
	$data_mostrar = "11H30";
}

if($mostrar_msg_pedido_finalizado == true and $status_pedido <> 18 and $status_pedido <> 14){ ?>

	<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
		<tr>
			<td align='center' bgcolor='#f4f4f4'>O PEDIDO <?=$pedido_blackedecker?> SERÁ ENVIADO PARA A FÁBRICA ÀS <?=$data_mostrar?>, CASO SEJA NECESSÁRIO CANCELAR ALGUM ITEM <a href='pedido_finalizado_desmembrados.php?exportado=nao'>CLIQUE AQUI.</a> SE DESEJA INCLUIR, GRAVE O ITEM E FINALIZE NOVAMENTE. </td>
		</tr>
	</table>
<?php }elseif($status_pedido == 18 AND !empty($data_finalizou)){ ?>
	<table width="700" border="0" cellpadding="2" cellspacing="2" align="center">
		<tr>
			<td align='center' bgcolor='#f4f4f4'>O PEDIDO <?=$pedido_blackedecker?> ESTÁ AGUARDANDO APROVAÇÃO DO FABRICANTE. CASO SEJA NECESSÁRIO ALTERAR OU EXCLUIR ALGUM ITEM, FAVOR <a href='pedido_finalizado_desmembrados.php?exportado=nao'>CLIQUE AQUI.</a> PARA REABRIR O PEDIDO.</td>
		</tr>
	</table>
<?php
	}
	echo "<br>";


?>


<? } # Final do IF do CNPJ e IE ?>

<p>

<?include "rodape.php";?>
