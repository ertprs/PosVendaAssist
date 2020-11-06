<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if(strlen($_GET['extrato']) == 0){
	echo "<script>";
	echo "close();";
	echo "</script>";
	exit;
}

$extrato = trim($_POST['extrato']);
if (strlen($_GET['extrato']) > 0) {
	$extrato = trim($_GET['extrato']);
}

$title = "EXTRATO - DETALHADO";
if($sistema_lingua == "ES") $title = "EXTRACTO - DETALLADO";?>

<style type="text/css">

.Titulo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;

	color:#000000;
}
.Titulo2 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#000000;
}
.Conteudo {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: normal;
	color:#000000;
}

.Conteudo2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color:#000000;
}

</style><?php
if ($login_fabrica == 20) {?>
	<style type='text/css' media='print'>
	.noPrint {display:none;}
	</style> 

	<div class='noPrint' align='center'>
	<input align='center' type=button name='fbBtPrint' value='Agrupar por Peças'
	onclick="window.location='os_extrato_detalhe_print_pecas.php?extrato=<? echo $extrato; ?>&posto=<? echo $login_posto; ?>'">
	<br>
	<hr class='noPrint'>
	</div><?php
} ?>

<p><?php

# ----------------------------------------- #
# -- VERIFICA SE É POSTO OU DISTRIBUIDOR -- #
# ----------------------------------------- #
$sql = "SELECT  DISTINCT
				tbl_tipo_posto.tipo_posto     ,
				tbl_posto.estado
		FROM    tbl_tipo_posto
		JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
									AND tbl_posto_fabrica.posto      = $login_posto
									AND tbl_posto_fabrica.fabrica    = $login_fabrica
		JOIN    tbl_posto            ON tbl_posto.posto = tbl_posto_fabrica.posto
		WHERE   tbl_tipo_posto.distribuidor IS TRUE
		AND     tbl_posto_fabrica.fabrica = $login_fabrica
		AND     tbl_tipo_posto.fabrica    = $login_fabrica
		AND     tbl_posto_fabrica.posto   = $login_posto ";

$res = pg_exec ($con,$sql);
if (pg_numrows($res) == 0) $tipo_posto = "P"; else $tipo_posto = "D";

$sql = "SELECT  tbl_os.sua_os                                                        ,
				tbl_os.os                                                            ,
				tbl_os.mao_de_obra                                                   ,
				tbl_os.mao_de_obra_distribuidor                                      ,
				tbl_os.pecas                                                         ,
				tbl_os.consumidor_nome                                               ,
				to_char(tbl_os.data_abertura, 'DD/MM/YYYY')     AS data_abertura     ,
				tbl_os.data_fechamento                                               ,
				to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data_geracao      ,
				to_char(tbl_extrato_extra.baixado,'DD/MM/YYYY') AS baixado           ,
				to_char(tbl_extrato.liberado,'DD/MM/YYYY')      AS liberado          ,
				lpad (tbl_extrato.protocolo,5,'0')              AS protocolo         ,
				tbl_extrato_extra.obs                                                ,
				tbl_os_extra.mao_de_obra                        AS extra_mo          ,
				tbl_os_extra.custo_pecas                        AS extra_pecas       ,
				tbl_os_extra.taxa_visita                        AS extra_instalacao  ,
				tbl_os_extra.deslocamento_km                    AS extra_deslocamento,
				tbl_os.qtde_km_calculada                        AS qtde_km_calculada
		FROM	tbl_os_extra
		JOIN	tbl_os            ON tbl_os.os           = tbl_os_extra.os
		JOIN	tbl_extrato       ON tbl_extrato.extrato = tbl_os_extra.extrato
		JOIN	tbl_extrato_extra ON tbl_extrato.extrato = tbl_extrato_extra.extrato
		WHERE	tbl_os_extra.extrato = $extrato
		AND		tbl_os.posto         = $login_posto ";
$sql .= "ORDER BY    lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0')               ASC,
					replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC";

$res = pg_exec ($con,$sql);

$totalRegistros = pg_numrows($res);

if ($totalRegistros == 0) {
	echo "<script>";
	echo "close();";
	echo "</script>";
	exit;
} else if ($totalRegistros > 0) {
	$ja_baixado = false ;
	$protocolo  = pg_result($res,0,protocolo) ;

	//HD 6204 PA pediu colocar logo da fabrica para identificar
	$login_fabrica_nome=strtolower($login_fabrica_nome);

	echo "<TABLE width='650' align='center' border='1' cellspacing='0' cellpadding='1' style='border-collapse: collapse' bordercolor='#000000'>\n";
	echo "<tr>";
	echo "	<td class='Titulo' colspan='3' align='center'>";
	echo "<IMG SRC=\"logos/cabecalho_print_";
	echo "$login_fabrica_nome.gif\" width='120' height='40' ALT='EXTRATO' align=left >";
	echo "	<BR><b>$title<br>";
	if($sistema_lingua == "ES") echo "Extracto ";
	else                        echo "Extrato ";
	if ($login_fabrica == 1) echo $posto_codigo.$protocolo;
	else                     echo $extrato;
	if($sistema_lingua == "ES") echo " generado en ";
	else                        echo " gerado em ";
	echo pg_result($res,0,data_geracao) ;
	echo "	</b><BR><BR></td>";
	echo "</tr>";
	echo "</TABLE>\n";


//--=== DADOS DO POSTO============================================================================================--\\
	$sql2 = "SELECT  tbl_posto_fabrica.codigo_posto                          ,
					tbl_posto.posto                                         ,
					tbl_posto.nome                                          ,
					tbl_posto.endereco                                      ,
					tbl_posto.cidade                                        ,
					tbl_posto.estado                                        ,
					tbl_posto.cep                                           ,
					tbl_posto.fone                                          ,
					tbl_posto.fax                                           ,
					tbl_posto.contato                                       ,
					tbl_posto.email                                         ,
					tbl_posto.cnpj                                          ,
					tbl_posto.ie                                            ,
					tbl_posto_fabrica.banco                                 ,
					tbl_posto_fabrica.agencia                               ,
					tbl_posto_fabrica.conta                                 ,
					tbl_extrato.protocolo                                   ,
					to_char(tbl_extrato.data_geracao, 'DD/MM/YYYY') AS data 
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
									  AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN    tbl_extrato ON tbl_extrato.posto = tbl_posto.posto
			WHERE   tbl_extrato.extrato = $extrato;";
	$res2 = pg_exec ($con,$sql2);

	if (pg_numrows($res2) > 0) {
		$codigo        = trim(pg_result($res2,0,codigo_posto));
		$posto         = trim(pg_result($res2,0,posto));
		$nome          = trim(pg_result($res2,0,nome));
		$endereco      = trim(pg_result($res2,0,endereco));
		$cidade        = trim(pg_result($res2,0,cidade));
		$estado        = trim(pg_result($res2,0,estado));
		$cep           = substr(pg_result($res2,0,cep),0,2) .".". substr(pg_result($res2,0,cep),2,3) ."-". substr(pg_result($res2,0,cep),5,3);
		$fone          = trim(pg_result($res2,0,fone));
		$fax           = trim(pg_result($res2,0,fax));
		$contato       = trim(pg_result($res2,0,contato));
		$email         = trim(pg_result($res2,0,email));
		$cnpj          = trim(pg_result($res2,0,cnpj));
		$ie            = trim(pg_result($res2,0,ie));
		$banco         = trim(pg_result($res2,0,banco));
		$agencia       = trim(pg_result($res2,0,agencia));
		$conta         = trim(pg_result($res2,0,conta));
		$data_extrato  = trim(pg_result($res2,0,data));
		$protocolo     = trim(pg_result($res2,0,protocolo));

		echo "<table border='0' cellpadding='0' cellspacing='0' width='600' align='center'>\n";
			echo "<tr>\n";
				echo "<td bgcolor='#FFFFFF' align='left'>\n";
					echo "<img src='imagens/pixel.gif' width='70' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>";
					if ($sistema_lingua == "ES") echo "Período: ";
					else                         echo "Período: ";
					echo "</b></font>\n";
				echo "</td>\n";
				echo "<td bgcolor='#FFFFFF' align='left'>\n";
					echo "<img src='imagens/pixel.gif' width='100' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$inicio_extrato</font>\n";
				echo "</td>\n";
				echo "<td bgcolor='#FFFFFF' align='left'>\n";
					echo "<img src='imagens/pixel.gif' width='40' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>";
					if ($sistema_lingua == "ES") echo "Hasta";
					else                         echo "Até:";
					echo "</b></font>\n";
				echo "</td>\n";
				echo "<td bgcolor='#FFFFFF' align='left'>\n";
					echo "<img src='imagens/pixel.gif' width='120' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$final_extrato</font>\n";
				echo "</td>\n";
				echo "<td bgcolor='#FFFFFF' align='left'>\n";
					echo "<img src='imagens/pixel.gif' width='40' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'><b>";
					if($sistema_lingua == "ES") echo "Fecha:";
					else                        echo "Data:";
					echo "</b></font>\n";
				echo "</td>\n";
				echo "<td bgcolor='#FFFFFF' align='left'>\n";
					echo "<img src='imagens/pixel.gif' width='230' height='1'><br><font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>$data_atual</font>\n";
				echo "</td>\n";
			echo "</tr>\n";
		echo "</table>\n";

		echo "<TABLE width='650' align='center' border='1' cellspacing='0' cellpadding='0' style='border-collapse: collapse' bordercolor='#000000'>\n";
			echo "<TR class='Conteudo2'>\n";
				echo "<TD>";
				echo "<table border='0' >";
					echo "<tr>";
						echo "<td class='Titulo2'>";
						if ($sistema_lingua == "ES") echo "SERVICIO";
						else                         echo "POSTO";
						echo "</td>";
						echo "<td class='Conteudo2'>$nome</td>";
						echo "</tr>";
						echo "<td class='Titulo2'>";
						if ($sistema_lingua == "ES") echo "DIRECCIÓN";
						else                         echo "ENDEREÇO";
						echo "</td>";
						echo "<td class='Conteudo2' width='200'>$endereco,$numero</td>";
					echo "</tr>";
					echo "<tr>";
						echo "<td class='Titulo2'>";
						if ($sistema_lingua == "ES") echo "CIUDAD";
						else                         echo "CIDADE";
						echo "</td>";
						echo "<td class='Conteudo2'>$cidade - $estado</td>";
						echo "<td class='Titulo2'>";
						if ($sistema_lingua == "ES") echo "APARATO POSTAL";
						else                         echo "CEP";
						echo "</td>";
						echo "<td class='Conteudo2'>$cep</td>";
					echo "</tr>";
						echo "<tr>";
							echo "<td class='Titulo2'>";
							if ($sistema_lingua == "ES") echo "TELÉFONO";
							else                         echo "TELEFONE";
							echo "</td>";
							echo "<td class='Conteudo2'>$fone</td>";
							echo "<td class='Titulo2'>";
							if ($sistema_lingua == "ES") echo "FAX";
							else                         echo "FAX";
							echo "</td>";
							echo "<td class='Conteudo2'>$fax</td>";
						echo "</tr>";
						echo "<tr>";
							echo "<td class='Titulo2'>";
							if ($sistema_lingua == "ES") echo "IDENTIFICACIÓN";
							else                         echo "CNPJ";
							echo "</td>";
							echo "<td class='Conteudo2'>$cnpj</td>";
							echo "<td class='Titulo2'>";
							if ($sistema_lingua == "ES") echo "IDENTIFICACIÓN 2";
							else                         echo "IE/RG";
							echo "</td>";
							echo "<td class='Conteudo2'>$ie</td>";
						echo "</tr>";
						echo "<tr>";
							echo "<td class='Titulo2'>";
							if ($sistema_lingua == "ES") echo "E-MAIL";
							else                         echo "EMAIL";
							echo "</td>";
							echo "<td class='Conteudo2'>$email</td>";
						echo "</tr>";
					echo "</table>";
				echo "</TD>";
			echo "</TR>";
		echo "</TABLE>";

	}
	//--=== DADOS DO POSTO============================================================================================--\\

	echo "<br />";

	//--=== OS DENTRO DO EXTRATO =====================================================================================--\\
	echo "<TABLE width='650' align='center' border='1' cellspacing='0' cellpadding='1' style='border-collapse: collapse' bordercolor='#000000'>\n";
		echo "<TR class='Titulo2'>\n";
			echo "<TD align='center' width='17%'>OS</TD>\n";

			if ($login_fabrica == 2) {//HD 245084
				echo "<TD align='center'>DATA ABERTURA</TD>\n";
			}

			if ($login_fabrica == 20 || $login_fabrica == 2) {

				echo "<TD align='center' width='35%'>";
				if ($sistema_lingua == "ES") echo "PRODUCTO";
				else                         echo "PRODUTO";
				echo "</TD>\n";

			} else {

				echo "<TD align='center' width='35%'>";
				if($sistema_lingua == "ES") echo "CONSUMIDOR";
				else                        echo "CLIENTE";
				echo "</TD>\n";

			}

			if ($login_fabrica == 2) {

				echo "<TD align='center' width='35%'>";
				if($sistema_lingua == "ES") echo "CONSUMIDOR";
				else                        echo "CLIENTE";
				echo "</TD>\n";

			}

			if ($login_fabrica == 6) {

				if (strlen($liberado) > 0) {

					echo "<td align='center'>MO</td>\n";
					echo "<td align='center'>MO REVENDA</td>\n";
					echo "<td align='center'>PEÇAS</td>\n";
					echo "<td align='center'>PEÇAS REVENDA</td>\n";

				}

			} else if ($login_fabrica == 19) {

				echo "<td align='center'>MO</td>\n";
				echo "<td align='center'>PEÇAS</td>\n";
				echo "<td align='center'>INST</td>\n";
				echo "<td align='center'>DESL</td>\n";
				echo "<td align='center'>TOTAL</td>\n";

			} else if ($login_fabrica == 50 or $login_fabrica == 52 or ($login_fabrica == 24 and $login_tipo_posto == 256) or $login_fabrica == 85) {

				echo "<td align='center' colspan=2>MO</td>\n";
				echo "<td align='center' colspan=2>KM</td>\n";

			} else {

				echo "<td align='center' colspan=2>";

				if ($sistema_lingua == "ES") echo "MO";
				else                         echo "MO";

				echo "</td>\n";
				echo "<td align='center' colspan=2>";

				if ($sistema_lingua == "ES") echo "PIEZAS";
				else                         echo "PEÇAS";

				echo "</td>\n";

			}

		echo " </TR>\n";

		$total             = 0;
		$total_mao_de_obra = 0;
		$total_pecas       = 0;
		$total_km          = 0;

		$total_mao_de_obra_revenda = 0;
		$total_pecas_revenda       = 0;

		$total_extra_mo            = 0;
		$total_extra_pecas         = 0;
		$total_extra_instalacao    = 0;
		$total_extra_deslocamento  = 0;
		$total_extra_total         = 0;

		for ($i = 0 ; $i < $totalRegistros; $i++) {

			$os                       = trim(pg_result($res, $i,'os'));
			$sua_os                   = trim(pg_result($res, $i,'sua_os'));
			$mao_de_obra              = trim(pg_result($res, $i,'mao_de_obra'));
			$mao_de_obra_distribuidor = trim(pg_result($res, $i,'mao_de_obra_distribuidor'));
			$pecas                    = trim(pg_result($res, $i,'pecas'));
			$qtde_km_calculada        = trim(pg_result($res, $i,'qtde_km_calculada'));
			$consumidor_nome          = strtoupper(trim(pg_result($res, $i,'consumidor_nome')));
			$consumidor_str           = substr($consumidor_nome, 0, 40);
			$data_abertura            = trim(pg_result($res, $i ,'data_abertura'));
			$data_fechamento          = trim(pg_result($res, $i ,'data_fechamento'));
			$baixado                  = pg_result($res, 0, 'baixado') ;
			$liberado                 = pg_result($res, 0, 'liberado') ;
			$obs                      = pg_result($res, 0, 'obs') ;
			
			if ($login_fabrica == 40) {
				$pecas = 0;
			}

			if ($login_fabrica == 19) {

				$extra_mo           = trim(pg_result($res,$i,'extra_mo'));
				$extra_pecas        = trim(pg_result($res,$i,'extra_pecas'));
				$extra_instalacao   = trim(pg_result($res,$i,'extra_instalacao'));
				$extra_deslocamento = trim(pg_result($res,$i,'extra_deslocamento'));

				$extra_total               = $extra_mo + $extra_pecas + $extra_instalacao + $extra_deslocamento;
				$total_extra_mo           += $extra_mo;
				$total_extra_pecas        += $extra_pecas;
				$total_extra_instalacao   += $extra_instalacao;
				$total_extra_deslocamento += $extra_deslocamento;
				$total_extra_total        += $extra_total;

			}
			
			$total_km += $qtde_km_calculada;

			if (strlen($baixado) > 0) $ja_baixado = true ;
			
			if ($login_fabrica == 6) {

				if ($consumidor_revenda == 'R') {

					$mao_de_obra         = '0,00';
					$mao_de_obra_revenda = $mao_de_obra;
					$pecas_posto         = '0,00';
					$pecas_revenda       = $pecas;
					
					if ($tipo_posto == "P") $total_mao_de_obra_revenda += $mao_de_obra_revenda;
					else					$total_mao_de_obra_revenda += $mao_de_obra_distribuidor;

				} else {

					$mao_de_obra         = $mao_de_obra;
					$mao_de_obra_revenda = '0,00';
					$pecas_posto         = $pecas;
					$pecas_revenda       = '0,00';
					
					if ($tipo_posto == "P") $total_mao_de_obra += $mao_de_obra;
					else                    $total_mao_de_obra += $mao_de_obra_distribuidor;

				}
				
				$total_pecas         += $pecas_posto;
				$total_pecas_revenda += $pecas_revenda;

			} else {

				//if ($tipo_posto == "P") {
					$total_mao_de_obra += $mao_de_obra;
				//}else{
				//	$total_mao_de_obra += $mao_de_obra_distribuidor ;
				//}
				$mao_de_obra         = $mao_de_obra;
				$pecas_posto         = $pecas;
				$total_pecas        += $pecas ;

			}

			# soma valores
	/*
			if ($tipo_posto == "P") {
				$total_mao_de_obra += $mao_de_obra ;
			}else{
				$total_mao_de_obra += $mao_de_obra_distribuidor ;
			}
			$total_pecas       += $pecas ;
	*/		
			echo "<TR class='Conteudo2' align='center'>\n";
				echo "<TD>";
				if ($login_fabrica == 1) echo $posto_codigo;
				echo "$sua_os</TD>\n";

				if ($login_fabrica == 2) {//HD 245084
					echo "<TD>$data_abertura</TD>\n";
				}

				if ($login_fabrica == 20 || $login_fabrica == 2) {
			
					$sql3 = "SELECT referencia,descricao FROM tbl_produto JOIN tbl_os ON tbl_os.produto = tbl_produto.produto WHERE tbl_os.os = $os";

					$res_produto = pg_exec($con,$sql3);

					$produto_referencia = trim(pg_result($res_produto,0,'referencia'));
					$produto_descricao  = trim(pg_result($res_produto,0,'descricao'));

					echo "<TD class='Conteudo' align='left' nowrap>$produto_referencia - $produto_descricao &nbsp;</TD>\n";

				} else {

					echo "<TD class='Conteudo' align='left' nowrap>$consumidor_str &nbsp;</TD>\n";

				}

				if ($login_fabrica == 2) {//HD 245084
					echo "<TD class='Conteudo' align='left' nowrap>$consumidor_str &nbsp;</TD>\n";
				}

				/*
				if ($tipo_posto == "P") {
					echo "		<TD class='Conteudo' align='right' style='padding-right:5px'>" . number_format($mao_de_obra,2,",",".") . "</TD>\n";
				}else{
					echo "		<TD class='Conteudo' align='right' style='padding-right:5px'>" . number_format($mao_de_obra_distribuidor,2,",",".") . "</TD>\n";
				}
				echo "		<TD class='Conteudo' align='right' style='padding-right:5px'>" . number_format($pecas,2,",",".") . "&nbsp;</TD>\n";
				*/

				if ($login_fabrica == 6) {

					if (strlen($liberado) > 0) {

						if ($tipo_posto == "P") {

							echo "<td class='Conteudo' align='right' style='padding-right:2px'>  " . number_format($mao_de_obra,2,",",".") . "</td>\n";
							echo "<td class='Conteudo' align='right' style='padding-right:2px'>  " . number_format($mao_de_obra_revenda,2,",",".") . "</td>\n";

						} else {

							echo "<td class='Conteudo' align='right' style='padding-right:2px'>  " . number_format($mao_de_obra_distribuidor,2,",",".") . "</td>\n";
							echo "<td class='Conteudo' align='right' style='padding-right:2px'>  " . number_format($mao_de_obra_revenda,2,",",".") . "</td>\n";

						}
						
						echo "<td class='Conteudo' align='right' style='padding-right:2px'>  " . number_format($pecas_posto,2,",",".") . "</td>\n";
						echo "<td class='Conteudo' align='right' style='padding-right:2px'>  " . number_format($pecas_revenda,2,",",".") . "</td>\n";

					}

				} else if ($login_fabrica == 19) {

					echo "<td class='Conteudo' align='right' style='padding-right:2px'> " . number_format($extra_mo,2,",",".") . "</td>\n";
					echo "<td class='Conteudo' align='right' style='padding-right:2px'> " . number_format($extra_pecas,2,",",".") . "</td>\n";
					echo "<td class='Conteudo' align='right' style='padding-right:2px'> " . number_format($extra_instalacao,2,",",".") . "</td>\n";
					echo "<td class='Conteudo' align='right' style='padding-right:2px'> " . number_format($extra_deslocamento,2,",",".") . "</td>\n";
					echo "<td class='Conteudo' align='right' style='padding-right:2px'> " . number_format($extra_total,2,",",".") . "</td>\n";

				} else if ($login_fabrica == 50 or $login_fabrica == 52 or ($login_fabrica == 24 and $login_tipo_posto == 256) or $login_fabrica == 85) {

					echo "<td class='Conteudo' colspan=2 align='right' style='padding-right:2px'>  " . number_format($mao_de_obra,2,",",".") . "</td>\n";
					echo "<td class='Conteudo' colspan=2 align='right' style='padding-right:2px'>  " . number_format($qtde_km_calculada,2,",",".") . "</td>\n";

				} else {

					//if ($tipo_posto == "P") {
						echo "<td class='Conteudo' colspan=2 align='right' style='padding-right:2px'>  " . number_format($mao_de_obra,2,",",".") . "</td>\n";
					//}else{
					//	echo "<td colspan=2 align='right' style='padding-right:5px'>  " . number_format($mao_de_obra_distribuidor,2,",",".") . "</td>\n";
					//}
					
					echo "<td class='Conteudo' colspan=2 align='right' style='padding-right:2px'>  " . number_format($pecas_posto,2,",",".") . "</td>\n";

				}

				echo "	</TR>\n";

			}

		echo "<tr class='Conteudo'>\n";
			echo "<td colspan=\"4\"></td>\n";

			if ($login_fabrica == 6) {

				if (strlen($liberado) > 0) {

					echo "<td align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b>  " . number_format($total_mao_de_obra,2,",",".") . "</b></td>\n";
					echo "<td align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b>  " . number_format($total_mao_de_obra_revenda,2,",",".") . "</b></td>\n";
					echo "<td align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b>  " . number_format($total_pecas,2,",",".") . "</b></td>\n";
					echo "<td align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b>  " . number_format($total_pecas_revenda,2,",",".") . "</b></td>\n";

				}

			} else if ($login_fabrica == 19) {

				echo "<td align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b>  " . number_format($total_extra_mo,2,",",".") . "</b></td>\n";
				echo "<td align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b>  " . number_format($total_extra_pecas,2,",",".") . "</b></td>\n";
				echo "<td align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b>  " . number_format($total_extra_instalacao,2,",",".") . "</b></td>\n";
				echo "<td align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b>  " . number_format($total_extra_deslocamento,2,",",".") . "</b></td>\n";
				echo "<td align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b>  " . number_format($total_extra_total,2,",",".") . "</b></td>\n";

			} else if ($login_fabrica == 50 or $login_fabrica == 52) {

				echo "<td colspan=2 align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b>  " . number_format($total_mao_de_obra,2,",",".") . "</b></td>\n";
				echo "<td colspan=2 align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b>  " . number_format($total_km,2,",",".") . "</b></td>\n";

			} else {

				echo "<td colspan=2 align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b>  " . number_format($total_mao_de_obra,2,",",".") . "</b></td>\n";
				echo "<td colspan=2 align='right' bgcolor='$cor' style='padding-right:2px' nowrap><b>  " . number_format($total_pecas,2,",",".") . "</b></td>\n";

			}

		echo "</tr>\n";

		if ($login_fabrica == 19) {

			echo "<tr class='Conteudo'>\n";
				echo "<td colspan=\"2\" align=\"center\" style='padding-right:2px'><b>TOTAL (MO + Peças)</b></td>\n";
				echo "<td colspan=\"5\" bgcolor='$cor' align='center'><b>  " . number_format($total_extra_total,2,",",".") . "</b></td>\n";
			echo "</tr>\n";

		} else {

			if ($login_fabrica == 6) {

				if (strlen($liberado) > 0) {

					echo "<tr class='Conteudo'>\n";
						echo "<td colspan=\"2\" align=\"center\" style='padding-right:2px'><b>";
						if($sistema_lingua == "ES") echo "TOTAL (MO + PIEZAS)";
						else                        echo "TOTAL (MO + Peças)";
						echo "</b></td>\n";
						echo "<td colspan=\"4\" bgcolor='$cor' align='center'><b>  " . number_format($total_mao_de_obra + $total_mao_de_obra_revenda + $total_pecas + $total_pecas_revenda,2,",",".") . "</b></td>\n";
					echo "</tr>\n";

				}

			} else {

				echo "<tr class='Conteudo'>\n";
					echo "<td colspan=\"2\" align=\"center\" style='padding-right:2px'><b>";
					if ($sistema_lingua == "ES") echo "TOTAL (MO + PIEZAS)";
					else                         echo "TOTAL (MO + Peças)";
					echo "</b></td>\n";
					echo "<td colspan=\"6\" bgcolor='$cor' align='center'><b>  " . number_format($total_mao_de_obra + $total_mao_de_obra_revenda + $total_pecas + $total_pecas_revenda +$total_km,2,",",".") . "</b></td>\n";
				echo "</tr>\n";

			}

		}

		if ($login_fabrica == 51) {

			echo "<tr class='Conteudo'>\n";
				echo "<td colspan=\"2\" align=\"center\" style='padding-right:2px'><b>";
				echo "TOTAL + AVULSO";
				echo "</b></td>\n";
				$sql    = "SELECT avulso FROM tbl_extrato WHERE extrato = $extrato";
				$res    = pg_exec($con,$sql);
				$avulso = pg_result($res,0,'avulso');
				echo "<td colspan=\"4\" bgcolor='$cor' align='center'><b>  " . number_format($total_mao_de_obra + $total_mao_de_obra_revenda + $total_pecas + $total_pecas_revenda +$total_km+$avulso,2,",",".") . "</b></td>\n";
			echo "</tr>\n";

		}

	echo "</table>\n";

	//--=== PEÇAS DA OS ============================================================--\\
	if ($login_fabrica == 20) {

		echo "<tr>";
		echo "<td>";

		$sql = "SELECT  tbl_os.sua_os,
						tbl_produto.referencia AS ref_equipamento ,
						tbl_produto.descricao  AS nome_equipamento,
						tbl_peca.peca                             ,
						tbl_produto.produto    AS produto_id      ,
						tbl_peca.referencia    AS ref_peca        ,
						tbl_peca.descricao     AS nome_peca       ,
						tbl_os_item.preco                         ,
						tbl_os_item.qtde                          
				FROM    tbl_os_item
				JOIN    tbl_os_produto        ON tbl_os_item.os_produto                  = tbl_os_produto.os_produto
				JOIN    tbl_os                ON tbl_os_produto.os                       = tbl_os.os
				JOIN    tbl_os_extra          ON tbl_os.os                               = tbl_os_extra.os
				JOIN    tbl_produto           ON tbl_os.produto                          = tbl_produto.produto
				JOIN    tbl_peca              ON tbl_os_item.peca                        = tbl_peca.peca
				JOIN    tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
				WHERE   tbl_os_extra.extrato = $extrato
				AND     tbl_os.fabrica       = $login_fabrica
				ORDER BY substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')) ASC,
						lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')+1,length(tbl_os.sua_os)),5,'0') ASC,
						tbl_os.sua_os;";

		$res = pg_exec($con,$sql);
	
		if (pg_numrows($res) > 0) {
			
			echo "<br><table border='1' cellpadding='2' cellspacing='0' width='650' align='center' style='border-collapse: collapse' bordercolor='#000000'>\n";
			echo "<tr class='Titulo2'>\n";

				echo "<td align='center'><b>";
				if ($sistema_lingua == "ES") echo "OS";
				else                         echo "OS";
				echo "</b></td>\n";

				echo "<td align='center'><b>";
				if ($sistema_lingua == "ES") echo "EQUIPAMIENTO";
				else                         echo "EQUIPAMENTO";
				echo "</b></td>\n";

				echo "<td align='center'><b>";
				if ($sistema_lingua == "ES") echo "PIEZAS";
				else                         echo "PEÇA";
				echo "</b></td>\n";

				echo "<td align='center'><b>";
				if ($sistema_lingua == "ES") echo "CTD";
				else                         echo "QTDE";
				echo "</b></td>\n";

				echo "<td align='center'><b>";
				if ($sistema_lingua == "ES") echo "PRECIO";
				else                         echo "PREÇO";
				echo "</b></td>\n";
			
			echo "</tr>\n";
			
			for ($x = 0; $x < pg_numrows($res); $x++) {

				$desconto      = 0;
				$valor_liquido = 0;
				$peca          = 0;
				$qtde_calculo  = 0;
				$qtde          = 1;
				
				$peca    = trim(pg_result($res,$x,'peca'));
				$produto = trim(pg_result($res,$x,'produto_id'));

				$valor_liquido       = number_format(pg_result($res,$x,'preco') ,2,",",".");
				$valor_liquido_total = pg_result($res,$x,'preco')  + $valor_liquido_total;

				$nome_equipamento = trim(pg_result($res,$x,'nome_equipamento'));
				$nome_peca        = trim(pg_result($res,$x,'nome_peca'));

				//--=== Tradução para outras linguas ============================= Raphael HD:1212
				$sql_idioma = "SELECT * FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma) = '$sistema_lingua'";

				$res_idioma = @pg_exec($con,$sql_idioma);
				if (@pg_numrows($res_idioma) >0) {
					$nome_peca  = trim(@pg_result($res_idioma,0,'descricao'));
				}
				//--=== Tradução para outras linguas ===================================================================

				echo "<tr>\n";
				
					echo "<td align='center' nowrap>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". trim(pg_result($res,$x,sua_os)) ."</a></font>\n";
					echo "</td>\n";
					
					echo "<td align='left' nowrap>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". trim(pg_result($res,$x,ref_equipamento)) ." - ". substr($nome_equipamento,0,20) ."</font>\n";
					echo "</td>\n";
					
					echo "<td align='left' nowrap>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". trim(pg_result($res,$x,ref_peca)) ." - ". $nome_peca ."</font>\n";
					echo "</td>\n";

					echo "<td align='center' nowrap>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'>". trim(pg_result($res,$x,qtde)) ."</font>\n";
					echo "</td>\n";

					echo "<td align='center' nowrap>\n";
					echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='-2'> ". $valor_liquido ."</font>\n";
					echo "</td>\n";

				echo "</tr>\n";
			}

			$valor_liquido_total = number_format($valor_liquido_total ,2,",",".");
			echo "<tr class='Titulo2'><td colspan='6' align='right'>";
			if ($sistema_lingua == "ES") echo "TOTAL PIEZAS: ";
			else                         echo "TOTAL PEÇAS: ";
			echo " $valor_liquido_total</font></td></tr>\n";
			echo "</table>\n";

		}

		echo "</td>";
		echo "</tr>";

	}

	echo "<tr>";
	echo "<td>";
	##### LANÇAMENTO DE EXTRATO AVULSO - INÍCIO #####
	$sql =	"SELECT tbl_lancamento.descricao         ,
					tbl_extrato_lancamento.historico ,
					tbl_extrato_lancamento.valor     
			FROM tbl_extrato_lancamento
			JOIN tbl_lancamento USING (lancamento)
			WHERE tbl_extrato_lancamento.extrato = $extrato
			AND   tbl_lancamento.fabrica = $login_fabrica";

	$res_avulso = pg_exec($con,$sql);

	if (pg_numrows($res_avulso) > 0) {

		if (($login_fabrica == 6 and strlen($liberado) > 0) or $login_fabrica <> 6) {
		
		echo "<br /><br />";
		echo "<table width='650' align='center' border='1' cellspacing='0' cellpadding='1' style='border-collapse: collapse' bordercolor='#000000'>\n";
			echo "<tr class='Titulo'>\n";
				echo "<td class='Conteudo' nowrap colspan='3'><B>LANÇAMENTO DE EXTRATO AVULSO<B></td>\n";
			echo "</tr>\n";
			echo "<tr class='Titulo'>\n";
				echo "<td class='Titulo' nowrap nowrap><B>DESCRIÇÃO</B></td>\n";
				echo "<td class='Titulo' align='center' nowrap><B>HISTÓRICO</B></td>\n";
				echo "<td class='Titulo' align='center' nowrap><B>VALOR</B></td>\n";
			echo "</tr>\n";

		for ($j = 0; $j < pg_numrows($res_avulso); $j++) {
			$cor = ($i % 2 == 0) ? "#F7F5F0" : "#F1F4FA";

			echo "<tr class='Conteudo'>\n";
				echo "<td class='Conteudo' width='45%'>" . pg_result($res_avulso, $j, descricao) . "&nbsp;</td>";
				echo "<td class='Conteudo' width='45%'>" . pg_result($res_avulso, $j, historico) . "&nbsp;</td>";
				echo "<td class='Conteudo' width='10%' align='right'> " . number_format( pg_result($res_avulso, $j, valor), 2, ',', '.') . "&nbsp;</td>";
			echo "</tr>";
		}

		echo "</table>\n";
	}##### LANÇAMENTO DE EXTRATO AVULSO - FIM #####
	echo "</td>";
	echo "</tr>";

	}

	##### RESUMO DO EXTRATO - INÍCIO #####
	$sql =	"SELECT tbl_extrato.mao_de_obra ,
					tbl_extrato.pecas ,
					tbl_extrato.avulso,
					tbl_extrato.total
			FROM tbl_extrato
			WHERE tbl_extrato.extrato = $extrato
			AND   tbl_extrato.fabrica = $login_fabrica";

	$res_extrato = pg_exec($con,$sql);

	if ($login_fabrica == 11 OR $login_fabrica == 20 or $login_fabrica == 43) { // HD 118073
		if (pg_numrows($res_extrato) > 0) {
			echo "<br>\n";
			echo "<table width='650' align='center' border='1' cellspacing='0' cellpadding='0' style='border-collapse: collapse' bordercolor='#000000'>\n";
				echo "<tr>\n";
					echo "<td align='right' width='88%' nowrap class='Titulo'><B>";
					if($sistema_lingua == "ES") echo "TOTAL GENERAL";
					else                        echo "TOTAL GERAL";
					echo "</B></td>\n";
					echo "<td align='right' width='12%'nowrap><B>" . number_format( pg_result($res_extrato, 0, total), 2, ',', '.') . "&nbsp;</B></td>\n";
				echo "</tr>\n";
			echo "</table>\n";
		}
		##### RESUMO DO EXTRATO - FIM #####
	}

}

echo "</TABLE>\n";?>

<br /><?php
if ($ja_baixado == true) { ?>
	<TABLE width='650' border='1' cellspacing='1' cellpadding='0' align='center' style='border-collapse: collapse' bordercolor='#000000'>
		<TR>
			<TD height='20' class="Conteudo" colspan='4'>PAGAMENTO</TD>
		</TR>
		<TR>
			<TD align='left' class="Conteudo" width='20%'>EXTRATO PAGO EM: </TD>
			<TD class="Conteudo" width='15%'><? echo $baixado; ?></TD>
			<TD align='left' class="Conteudo" width='15%'><center>OBSERVAÇÃO:</center></TD>
			<TD class="Conteudo" width='50%'><? echo $obs;?>
			</td>
		</TR>
	</TABLE><?php
}?>

<br />

<script>
	window.print();
</script>