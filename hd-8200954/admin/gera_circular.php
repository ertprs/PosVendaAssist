<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="financeiro";
include "autentica_admin.php";


$msg_erro = "";


$layout_menu = "financeiro";
$title = "Circular Interna";

include "cabecalho.php";



	if (strlen($_POST["btn_acao"]) > 0) $btn_acao = trim(strtolower($_POST["btn_acao"]));
	if (strlen($_GET["btn_acao"])  > 0) $btn_acao = trim(strtolower($_GET["btn_acao"]));

	if($btn_acao == "imprimir"){

		$qtde_itens = trim($_POST["qtde_itens"]);

		if (strlen($_POST["numero_ci"]) > 0) $numero_ci = $_POST["numero_ci"];
		
		$de      = $_POST["de"];
		$assunto = $_POST["assunto"];
		$para_cc = $_POST["para_cc"];
		$para_cp = $_POST["para_cp"];
		$para_cc_obs   = $_POST["para_cc_obs"];
		$para_cp_obs   = $_POST["para_cp_obs"];

		//Parametros fixos
		$data    = date('dmY');
		$data2   = date('d/m/Y');

		$conteudo = "<TABLE WIDTH='700' border='0' CELLPADDING='2' CELLSPACING='2'>";
			$conteudo .= "<TR>";
				$conteudo .= "<TD class='conteudo2'><IMG SRC='../logos/esmaltec.gif' BORDER='0' ALT=''></TD>";
				$conteudo .= "<TD align='center' colspan='2'>";
				$conteudo .= "<P ALIGN='CENTER'>GERÊNCIA DE ASSISTÊNCIA TÉCNICA<BR>";
				$conteudo .= "LIBERAÇÃO DE PAGAMENTO</P></TD>";
				$conteudo .= "<TD>MÊS REFERÊNCIA:</TD>";
			$conteudo .= "</TR>";
			$conteudo .= "<TR>";
				$conteudo .= "<TD colspan='4'>&nbsp;</TD>";
			$conteudo .= "</TR>";
			$conteudo .= "<TR>";
				$conteudo .= "<TD colspan='2'>CI Nº $numero_ci</TD>";
				$conteudo .= "<TD colspan='2'>DATA: $data2</TD>";
			$conteudo .= "</TR>";
			$conteudo .= "<TR>";
				$conteudo .= "<TD colspan='4'><B>DE:</B> $de</TD>";
			$conteudo .= "</TR>";
			$conteudo .= "<TR>";
				$conteudo .= "<TD colspan='4'><B>ASSUNTO:</B> $assunto</TD>";
			$conteudo .= "</TR>";
			$conteudo .= "<TR>";
				$conteudo .= "<TD colspan='4'><HR COLOR='#000000'></TD>";
			$conteudo .= "</TR>";
			$conteudo .= "<TR>";
				$conteudo .= "<TD colspan='4'><B>PARA:</B> $para_cc</TD>";
			$conteudo .= "</TR>";
			$conteudo .= "<TR>";
				$conteudo .= "<TD colspan='4'>";
					$conteudo .= "<P>$para_cc_obs</P>";
				$conteudo .= "</TD>";
			$conteudo .= "</TR>";
			$conteudo .= "<TR>";
				$conteudo .= "<TD colspan='4'><hr size='2' style='border:2px dashed black;'></TD>";
			$conteudo .= "</TR>";
			$conteudo .= "<TR>";
				$conteudo .= "<TD colspan='4'><B>PARA:</B> $para_cp</TD>";
			$conteudo .= "</TR>";
			$conteudo .= "<TR>";
				$conteudo .= "<TD colspan='4'>";
					$conteudo .= "<P>$para_cp_obs</P>";
				$conteudo .= "</TD>";
			$conteudo .= "</TR>";
			$conteudo .= "<TR>";
				$conteudo .= "<TD colspan='4'><hr size='2' style='border:2px dashed black;'></TD>";
			$conteudo .= "</TR>";
			
				for($i=0; $i<$qtde_itens; $i++){
					$check_extrato     = $_POST['check_extrato_'.$i];
					$posto_nome        = $_POST['posto_nome_'.$i];
					$cnpj              = $_POST['cnpj_'.$i];
					$cidade            = $_POST['cidade_'.$i];
					$estado            = $_POST['estado_'.$i];
					$valor_total       = $_POST['valor_total_'.$i];
					$nf_autorizacao    = $_POST['nf_autorizacao_'.$i];
					$autorizacao_pagto = $_POST['autorizacao_pagto_'.$i];
					$banco             = $_POST['banco_'.$i];
					$nomebanco         = $_POST['nomebanco_'.$i];
					$agencia           = $_POST['agencia_'.$i];
					$conta             = $_POST['conta_'.$i];
					$data_recebimento_nf = $_POST['data_recebimento_nf_'.$i];
					
					if($check_extrato =='t'){
						$soma_total = $soma_total + $valor_total;

						$conteudo .= "<TR>";
							$conteudo .= "<TD colspan='4'><B>SAE:</B> $cnpj - $posto_nome</TD>";
						$conteudo .= "</TR>";
						$conteudo .= "<TR>";
							$conteudo .= "<TD colspan='2'><B>CIDADE:</B> $cidade</TD>";
							$conteudo .= "<TD colspan='2'><B>ESTADO:</B> $estado</TD>";
						$conteudo .= "</TR>";
						$conteudo .= "<TR>";
							$conteudo .= "<TD nowrap><B>NF DE SERVIÇO:</B> $nf_autorizacao</TD>";
							$conteudo .= "<TD nowrap><B>ORDEM DE SERVIÇO:</B> $autorizacao_pagto</TD>";
							$conteudo .= "<TD nowrap><B>VALOR R$:</B> $valor_total</TD>";
							$conteudo .= "<TD nowrap><B>DATA:</B> $data_recebimento_nf </TD>";
						$conteudo .= "</TR>";
						$conteudo .= "<TR>";
							$conteudo .= "<TD colspan='2'><B>TIPO PAG. BANCO:</B> $banco - $nomebanco</TD>";
							$conteudo .= "<TD><B>AG:</B> $agencia</TD>";
							$conteudo .= "<TD><B>C.C:</B> $conta</TD>";
						$conteudo .= "</TR>";
						$conteudo .= "<TR>";
							$conteudo .= "<TD colspan='4'><hr size='2' style='border:2px dashed black;'></TD>";
						$conteudo .= "</TR>";
					}
				}
			//total
			$conteudo .= "<TR>";
				$conteudo .= "<TD>&nbsp;</TD>";
				$conteudo .= "<TD colspan='2'>Total R$: $soma_total</TD>";
				$conteudo .= "<TD>&nbsp;</TD>";
			$conteudo .= "</TR>";

			//espaço
			$conteudo .= "<TR>";
				$conteudo .= "<TD colspan='4'><BR><BR><BR><BR></TD>";
			$conteudo .= "</TR>";

			//assinatura
			$conteudo .= "<TR>";
				$conteudo .= "<TD class='conteudo2'><FONT SIZE='4' COLOR='#000000'>ESMALTEC S/A</FONT></TD>";
				$conteudo .= "<TD colspan='3'>&nbsp;</TD>";
			$conteudo .= "</TR>";
			$conteudo .= "<TR>";
				$conteudo .= "<TD colspan='4'><BR><BR></TD>";
			$conteudo .= "</TR>";
			$conteudo .= "<TR>";
				$conteudo .= "<TD class='conteudo2'>";
				$conteudo .= "<HR COLOR='#000000'><BR>";
				$conteudo .= "Carlos Eduardo Salles<BR>";
				$conteudo .= "Gerente de Assistência Técnica";
				$conteudo .= "</TD>";
				$conteudo .= "<TD colspan='3'>&nbsp;</TD>";
			$conteudo .= "</TR>";
		$conteudo .= "</TABLE>";

		//echo $conteudo;
		$numero_ci = str_replace("/","_",$numero_ci);

		$dir = opendir('tmp');
		while(false !== ($arq = readdir($dir))){
			$arq_nomes[] = $arq;
		}
		
		foreach($arq_nomes as $listar){
			$lista = explode(".",  $listar);
			if($lista[0]==$numero_ci){
				$msg_erro = "Número de CI já cadastrado";
			}
		}

		if(1==1){
		if(strlen($msg_erro) == 0){
		echo `mkdir /tmp/esmaltec`;
		echo `chmod 777 /tmp/esmaltec`;
		echo `rm /tmp/esmaltec/$numero_ci.htm`;
		echo `rm /tmp/esmaltec/$numero_ci.pdf`;
		echo `rm /var/www/assist/www/admin/tmp/$numero_ci.pdf`;

			$abrir = fopen("/tmp/esmaltec/$numero_ci.htm", "w");
			if (!fwrite($abrir, $conteudo)) {
				$msg_erro = "Erro escrevendo no arquivo ($filename)";
			}
			fclose($abrir); 

			echo "<P>
			<BR>
			<IMG SRC='imagens/img_pdf.jpg' BORDER='0' ALT=''><BR>
			<A HREF='tmp/$numero_ci.pdf' target='_blank'>Liberação de Pagamento</A>
			</P>";
		//gera o pdf
		echo `htmldoc --webpage --no-duplex --no-embedfonts --header ... --permissions no-modify,no-copy --fontsize 8.5 --no-title -f /tmp/esmaltec/$numero_ci.pdf /tmp/esmaltec/$numero_ci.htm`;
		echo `mv  /tmp/esmaltec/$numero_ci.pdf /var/www/assist/www/admin/tmp/$numero_ci.pdf`;
		}
		}
	}
?>

<p>

<style type="text/css">
.Tabela{
	border:1px solid #d2e4fc;
	background-color:#d2e4fc;
	}

.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}

.titulo {
	font-family: Arial;
	font-size: 7pt;
	text-align: right;
	color: #000000;
	background: #ced7e7;
}

.conteudo {
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-align: left;

}

.conteudo2 {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	text-align: center;
}

div.Exibe{
	border: #D3BE96 1px solid; 
	background-color: #FCF0D8;
	width:200px;
}

body{
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: normal;
}
p{
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: normal;
}
td{
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: normal;
	/*text-align: left;*/
}

</style>

<?
	if($login_fabrica<>20)  $cond_1 = " AND EX.liberado IS NOT NULL ";

	$sql = "
		SELECT  EX.extrato                                            ,
				EX.posto                                              ,
				EX.fabrica                                            ,
				EX.liberado                                           ,
				EX.aprovado                                           ,
				TO_CHAR (EX.data_geracao,'dd/mm/yyyy') AS data_geracao,
				EX.total                                              ,
				EP.valor_total                                        ,
				EP.autorizacao_pagto                                  ,
				EP.nf_autorizacao                                     ,
				EP.data_recebimento_nf                                ,
				TO_CHAR (EP.data_pagamento,'dd/mm/yyyy') AS baixado
		INTO TEMP tmp_libera_$login_admin
		FROM      tbl_extrato           EX
		JOIN      tbl_extrato_extra     EE ON EX.extrato = EE.extrato
		LEFT JOIN tbl_extrato_pagamento EP ON EX.extrato = EP.extrato
		WHERE     EX.fabrica = $login_fabrica
		AND       EX.aprovado  IS NOT NULL
		AND       EE.exportado IS     NULL
		$cond_1;

	CREATE INDEX tmp_libera_extrato_$login_admin ON tmp_libera_$login_admin(extrato);
	CREATE INDEX tmp_libera_posto_$login_admin ON tmp_libera_$login_admin(posto);
	CREATE INDEX tmp_libera_fabrica_$login_admin ON tmp_libera_$login_admin(fabrica);
		SELECT distinct  PO.posto        ,
			PO.nome                      ,
			PO.cnpj                      ,
			PO.cidade                    ,
			PO.estado                    ,
			PF.codigo_posto              ,
			PF.banco                     ,
			PF.nomebanco                 ,
			PF.agencia                   ,
			PF.conta                     ,
			TE.extrato                   ,
			TE.liberado                  ,
			TE.aprovado                  ,
			TE.data_geracao              ,
			TE.total                     ,
			TE.valor_total               ,
			TE.autorizacao_pagto         ,
			TE.nf_autorizacao            ,
			to_char(TE.data_recebimento_nf, 'dd/mm/yyyy') AS data_recebimento_nf
		FROM      tmp_libera_$login_admin TE
		JOIN      tbl_posto                       PO ON TE.posto      = PO.posto
		JOIN      tbl_posto_fabrica               PF ON TE.posto      = PF.posto      AND PF.fabrica = $login_fabrica
		LEFT JOIN tbl_os_extra                    OE ON OE.extrato    = TE.extrato
		LEFT JOIN tbl_os                          OS ON OS.os         = OE.os         AND OS.posto   = TE.posto AND OS.fabrica = TE.fabrica		
		WHERE     PO.pais    = 'BR'
		AND       PF.distribuidor IS NULL 
		AND       TE.total >0
		ORDER BY PO.nome, TE.data_geracao";
//echo nl2br($sql);
//exit;
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<center><h2>Nenhum extrato aprovado para ser liberado</h2></center>";
	}
// echo "$sql";
	if (pg_numrows ($res) > 0) {

		$data_relatorio = date('d/m/Y');

		if(strlen($msg_erro)>0){
			echo "<table width='700' height=16 border='0' cellspacing='2' cellpadding='2' align='center'>";
			echo "<tr>";
			echo "<td align='center' bgcolor='#FF0000'><font size=2 color='#FFFFFF'><b>";
			echo $msg_erro;
			echo "</b></font></td>";
			echo "</tr>";
			echo "</table>";
		}
		echo "<form name='frm_liberado' method='POST' action=\"$PHP_SELF\">\n";
		echo "<input type='hidden' name='btn_acao' value=''>";
		echo "<table width='300' height=16 border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo "<tr class='Titulo' height='25'>\n";
		echo "<td>Relatório de Pagamentos</td>\n";
		echo "<td nowrap>Data do Relatório</td>\n";
		echo "</tr>\n";
		echo "<tr height='25'>\n";
		echo "<td align='center'>Nº <INPUT TYPE='text' NAME='numero_ci' size='12'></td>\n";
		echo "<td align='center'>$data_relatorio</td>\n";
		echo "</tr>\n";
		echo "<tr height='25'>\n";
		echo "<td colspan='6'>&nbsp;</td>\n";
		echo "</tr>\n";
		echo "</table>";


		echo "<table width='600' height=16 border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo "<tr class='Titulo' height='25'>\n";
		echo "<td>De</td>\n";
		echo "<td nowrap>Assunto</td>\n";
		echo "<td>Para Crédito e Cobrança</td>\n";
		echo "<td>Para Contas a Pagar</td>\n";
		echo "</tr>\n";
		echo "<tr height='20'>\n";
		echo "<td><INPUT TYPE='text' NAME='de' value='Carlos Eduardo Salles'></td>\n";
		echo "<td><INPUT TYPE='text' NAME='assunto' value='Liberação de Pagamentos a Autorizados'></td>\n";
		echo "<td><INPUT TYPE='text' NAME='para_cc' value='Sra. Ailomar - Crédito e Cobrança'></td>\n";
		echo "<td><INPUT TYPE='text' NAME='para_cp' value='Sr. Almir - Contas a Pagar'></td>\n";
		echo "</tr>\n";
		echo "<tr class='Titulo' height='25'>\n";
		echo "<td colspan='4'>Obs Para Crédito e Cobrança</td>\n";
		echo "</tr>\n";
		echo "<tr height='20'>\n";
		echo "<td colspan='4' class='conteudo2'><TEXTAREA NAME='para_cc_obs' ROWS='5' COLS='70'>Estamos liberando para o pagamento as NF’S dos serviços autorizados abaixo relacionados, solicito fazer a analise e possível encontro de contas, após o qual, favor encaminhar a documentação anexada para o Sr. Almir em contas a pagar.</TEXTAREA></td>\n";
		echo "</tr>\n";
		echo "<tr class='Titulo' height='25'>\n";
		echo "<td colspan='4'>Obs Para Contas a Pagar</td>\n";
		echo "</tr>\n";
		echo "<tr height='20'>\n";
		echo "<td colspan='4' class='conteudo2'><TEXTAREA NAME='para_cp_obs' ROWS='5' COLS='70'>Estamos liberando para o pagamento as NF’S dos serviços autorizados abaixo relacionados, solicito providenciar pagamento.</TEXTAREA></td>\n";
		echo "</tr>\n";
		echo "</TABLE>";

		echo "<br><table border='1' width ='700'align='center' cellspacing='0' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>";
		echo "<tr class='Titulo' height='25'>\n";
		echo "<td>#</td>\n";
		echo "<td>Data Recebimento NF </td>\n";
		echo "<td nowrap>CNPJ do SAE</td>\n";
		echo "<td>Nº da NF</td>\n";
		echo "<td>Valor da NF</td>\n";
		echo "<td nowrap>OS Matriz</td>\n";
		echo "<td>Nº Extrato</td>\n";
		echo "</tr>\n";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$posto          = trim(pg_result($res,$i,posto));
			$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
			$posto_nome     = trim(pg_result($res,$i,nome));
			$cnpj           = trim(pg_result($res,$i,cnpj));
			$cidade         = trim(pg_result($res,$i,cidade));
			$estado         = trim(pg_result($res,$i,estado));
			$data_geracao   = trim(pg_result($res,$i,data_geracao));
			$total          = trim(pg_result($res,$i,total));
			$extrato        = trim(pg_result($res,$i,extrato));
			$total	        = number_format ($total,2,',','.');
			$liberado       = trim(pg_result($res,$i,liberado));
			$aprovado       = trim(pg_result($res,$i,aprovado));
			$nf_autorizacao    = trim(pg_result($res,$i,nf_autorizacao));
			$autorizacao_pagto = trim(pg_result($res,$i,autorizacao_pagto));
			$banco          = trim(pg_result($res,$i,banco));
			$nomebanco      = trim(pg_result($res,$i,nomebanco));
			$agencia        = trim(pg_result($res,$i,agencia));
			$conta          = trim(pg_result($res,$i,conta));
			$data_recebimento_nf = trim(pg_result($res,$i,data_recebimento_nf));

			if (trim(pg_result($res,$i,valor_total)) <> '') $valor_total = number_format (trim(pg_result($res,$i,valor_total)),2,',','.');
			else                                            $valor_total = number_format (trim(pg_result($res,$i,total)),2,',','.')        ;

			$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

		echo "<tr bgcolor='$cor' height='20'>\n";
			echo "<td align='left'><INPUT TYPE='checkbox' NAME='check_extrato_$i' value='t'";
			if($check_extrato=='t')  echo "checked"; 
			echo "size='11'></td>\n";
			echo "<td align='left'><INPUT TYPE='text' NAME='data_recebimento_nf_$i' value='$data_recebimento_nf' size='11'></td>\n";
			echo "<td align='left' nowrap><INPUT TYPE='text' NAME='cnpj_$i' value='$cnpj' size='15'></td>\n";
			echo "<td align='left' nowrap><INPUT TYPE='text' NAME='nf_autorizacao_$i' value='$nf_autorizacao' size='10'></td>\n";
			echo "<td align='left' nowrap><INPUT TYPE='text' NAME='valor_total_$i' value='$valor_total' size='10'></td>\n";
			echo "<td align='left' nowrap><INPUT TYPE='text' NAME='autorizacao_pagto_$i' value='$autorizacao_pagto' size='12'></td>\n";
			echo "<td align='center'><INPUT TYPE='text' NAME='extrato_$i' value='$extrato' size='10'></td>\n";
		echo "</tr>\n";

		//parametros hidden
		echo "<INPUT TYPE='hidden' NAME='posto_nome_$i' value='$posto_nome'>";
		echo "<INPUT TYPE='hidden' NAME='cidade_$i' value='$cidade'>";
		echo "<INPUT TYPE='hidden' NAME='estado_$i' value='$estado'>";
		echo "<INPUT TYPE='hidden' NAME='banco_$i' value='$banco'>";
		echo "<INPUT TYPE='hidden' NAME='nomebanco_$i' value='$nomebanco'>";
		echo "<INPUT TYPE='hidden' NAME='agencia_$i' value='$agencia'>";
		echo "<INPUT TYPE='hidden' NAME='conta_$i' value='$conta'>";

		}
		$extrato = trim(pg_result($res,0,extrato));
		
		echo "<tr bgcolor='$cor' height='20'>\n";
			echo "<td colspan='7' class='conteudo2'>
			<img src=\"imagens_admin/btn_imprimir.gif\" onclick=\"javascript: document.frm_liberado.btn_acao.value='imprimir' ; document.frm_liberado.submit() \" border='0' style=\"cursor:pointer;\">";
			echo "<INPUT TYPE='hidden' NAME='qtde_itens' value='$i'>";
			echo "</td>";
		echo "</tr>\n";
		echo "</table>\n";
		//input hidden
		echo "<center><br><div id='dados' class='Exibe' style='visibility:hidden'></div></center>";

	echo "</form>\n";
	}
	
?>

<br>

<? include "rodape.php"; ?>
