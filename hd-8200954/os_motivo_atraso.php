<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if (strlen($_GET['os']) > 0)   $os = trim ($_GET['os']);
if (strlen($_POST['os']) > 0)  $os = trim ($_POST['os']);

if ($btn_acao == "gravar") {

	if (strlen ($os) > 0) {

		$motivo_atraso = trim($_POST['motivo_atraso']);
		$justificativa = $_POST['justificativa'];

		if ($login_fabrica == 42) {
			$tipo_atendimento = $_POST["tipo_atendimento"];
			$sql = "SELECT entrega_tecnica FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento";
			$res = pg_query($con, $sql);

			$entrega_tecnica = pg_result($res, 0, "entrega_tecnica");
		}
		
		if ($login_fabrica == 52 && isset($_POST['motivo_reincidencia'])) {#HD 409402 INICIO
			$motivo_reincidencia = $_POST['motivo_reincidencia'];
		} else {
			$motivo_reincidencia = "null";

		}
		
		if (strlen($motivo_reincidencia) == 0 and $login_fabrica == 52) {
			$msg_erro = traduz('escolha.um.motivo.para.a.reincidencia');
		}#HD 409402 FIM
		
		if (strlen($justificativa) > 0 AND strlen($motivo_atraso) > 0 AND strlen($msg_erro) == 0) {//HD 1583
		
			$sql = "UPDATE tbl_os SET 
						obs_reincidencia = '$motivo_atraso',
						motivo_reincidencia = $motivo_reincidencia 
					WHERE  tbl_os.os    = $os
					AND    tbl_os.fabrica = $login_fabrica
					AND    os_reincidente IS TRUE;";
			
			$res      = @pg_query($con,$sql);
			$foi      = 'SIM';
			$msg_erro = pg_errormessage($con);

		} elseif(strlen($motivo_reincidencia) > 0) {

			$sql = "UPDATE tbl_os SET motivo_atraso = '$motivo_atraso', motivo_reincidencia=$motivo_reincidencia 
					WHERE  tbl_os.os    = $os
					AND    tbl_os.fabrica = $login_fabrica;";

			$res      = @pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);
			$foi      = 'SIM';

		}

	}

	if (strlen($msg_erro) == 0) {

		if ($foi == 'SIM') {

			if (isset($novaTelaOs) || $login_fabrica == 52) {
				header("Location: os_press.php?os=$os");
			}

			if ($login_fabrica == 42 and ($cook_tipo_posto == "t" or $entrega_tecnica == "t")) {
                  header("Location: os_press.php?os=$os");
            } else {
                if ($justificativa <> 'ok') {

					echo "<script>window.location = 'os_item.php?os=$os'</script>";
				}

				if($login_fabrica == 117){
					$sql = "SELECT  tbl_os.sua_os       
								FROM   tbl_os
								JOIN tbl_os_status ON tbl_os.os=tbl_os_status.os
								AND tbl_os_status.os_status=(
								SELECT MAX(os_status)
								FROM tbl_os_status
								WHERE tbl_os_status.os=tbl_os.os
								AND tbl_os_status.status_os IN (19,62,64,67,102,103,148,149,150,151,163,98,161,162, 164,165,166,167)
								)
								WHERE   tbl_os.os = $os
								AND     tbl_os.validada    IS NOT NULL
								AND     tbl_os.excluida    IS NOT TRUE
								AND     tbl_os.fabrica    = $login_fabrica
								AND (tbl_os_status.status_os IN(62,102,148,150,98,161,162,165,167))";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res) > 0){
						echo "<script>window.location = 'os_press.php?os=$os'</script>";
					}
				}

				if($login_fabrica == 74){
					echo "<script>window.location = 'os_finalizada.php?os=$os'</script>";
				}else{
                    if($login_fabrica == 24){
                           echo "<script>window.location = 'os_press.php?os=$os'</script>";
                    }else{
                        echo "<script>window.location = 'os_item.php?os=$os'</script>";
                    }
				}
            }

		} else {

			$msg_erro = traduz("esta.errado");

		}

	}

}

if (strlen($os) > 0) {

	if ($login_fabrica == 19) {
		pg_query($con,"BEGIN TRANSACTION");
		$sqlReincidente = "	SELECT tbl_os_extra.os_reincidente, os_sequencia, sua_os
							FROM tbl_os_extra
							JOIN tbl_os ON tbl_os.os = tbl_os_extra.os
							WHERE   tbl_os.os = $os";
		$resReincidente = pg_query($con,$sqlReincidente);		
		$os_reincidente = pg_fetch_result($resReincidente, 0, 'os_reincidente');
		$sequencia_atual = pg_fetch_result($resReincidente, 0, 'os_sequencia');
		$sua_os = pg_fetch_result($resReincidente, 0, 'sua_os');
		if(strpos($sua_os, '-') === false){
			$sqlAnterior = "SELECT sua_os, os_sequencia, os_numero
						FROM tbl_os
						WHERE os = $os_reincidente";
			$resAnterior = pg_query($con,$sqlAnterior);

			$sequencia_anterior = pg_fetch_result($resAnterior, 0, 'os_sequencia');
			$sua_os_anterior = pg_fetch_result($resAnterior, 0, 'sua_os');
			$os_numero = pg_fetch_result($resAnterior, 0, 'os_numero');
			$sequencia_anterior = ($sequencia_anterior) ? $sequencia_anterior : 1 ;
			$sql_sequencia = "SELECT os_sequencia FROM tbl_os LEFT JOIN tbl_os_excluida ON tbl_os.os = tbl_os_excluida.os WHERE os_numero = {$os_numero} and (tbl_os.fabrica = {$login_fabrica} or (tbl_os.fabrica =  0 and tbl_os_excluida.fabrica = {$login_fabrica})) ORDER BY os_sequencia DESC limit 1;";
			$res_sequencia = pg_query($con, $sql_sequencia);
			$osSequencia = pg_fetch_result($res_sequencia, 0, 'os_sequencia');
			$sequencia_anterior++;
			$osSequencia++;
			if ($osSequencia == 1) {
				$osSequencia++;
			}
			if(isset($_GET['justificativa'])){
				$updateAtual = "UPDATE tbl_os 
							SET os_sequencia = {$osSequencia},
							sua_os = '{$os_numero}-{$osSequencia}',
							os_numero = '{$os_numero}'  
							WHERE os = $os";
				pg_query($con,$updateAtual);
			}
		}
		
		if (pg_last_error()) {
	        pg_query($con,"ROLLBACK TRANSACTION");
	    } else {
	        pg_query($con,"COMMIT TRANSACTION");
	    }
	}

	#----------------- Le dados da OS --------------
	if (!isset($novaTelaOs)) {
		$sql  = "SELECT fn_valida_os_reincidente($os, $login_fabrica)";
		$res2 = @pg_query($con,$sql);	
	}	

	if(in_array($login_fabrica, array(145))){
		$cond_produto = "LEFT JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os LEFT JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto ";
	}else{
		$cond_produto = " LEFT JOIN tbl_produto USING (produto) ";
	}	

	$sql = "SELECT  tbl_os.*                                                 ,
			to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura2   ,
			tbl_produto.produto                                              ,
			tbl_produto.referencia                                           ,
			tbl_produto.descricao                                            ,
			tbl_produto.linha                                                ,
			tbl_linha.nome AS linha_nome                                     ,
			tbl_posto_fabrica.codigo_posto                                   ,
			tbl_defeito_constatado.descricao AS defeito_constatado_descricao ,
			tbl_causa_defeito.descricao      AS causa_defeito_descricao      ,
			tbl_os.motivo_atraso                                             ,
			tbl_os.obs_reincidencia                                          ,
			tbl_os_extra.os_reincidente      AS reincidente_os,
			tbl_os.tipo_atendimento
		FROM    tbl_os
		JOIN    tbl_os_extra USING (os)
		JOIN    tbl_posto USING (posto)
		JOIN    tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
							  AND tbl_posto_fabrica.fabrica = $login_fabrica
		$cond_produto
		LEFT JOIN    tbl_linha   ON tbl_produto.linha = tbl_linha.linha
		LEFT JOIN    tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
		LEFT JOIN    tbl_causa_defeito      ON tbl_causa_defeito.causa_defeito           = tbl_os.causa_defeito
		WHERE   tbl_os.os = $os";

	$res = pg_query($con,$sql);

    if (pg_num_rows($res)) {

    	$defeito_constatado           = pg_fetch_result($res, 0, 'defeito_constatado');
    	$data_abertura                = pg_fetch_result($res, 0, 'data_abertura2');
    	$nota_fiscal                  = pg_fetch_result($res, 0, 'nota_fiscal');
    	$causa_defeito                = pg_fetch_result($res, 0, 'causa_defeito');
    	$linha                        = pg_fetch_result($res, 0, 'linha');
    	$linha_nome                   = pg_fetch_result($res, 0, 'linha_nome');
    	$consumidor_nome              = pg_fetch_result($res, 0, 'consumidor_nome');
    	$consumidor_fone              = pg_fetch_result($res, 0, 'consumidor_fone');
    	$sua_os                       = pg_fetch_result($res, 0, 'sua_os');
    	$produto_os                   = pg_fetch_result($res, 0, 'produto');
    	$produto_referencia           = pg_fetch_result($res, 0, 'referencia');
    	$produto_descricao            = pg_fetch_result($res, 0, 'descricao');
    	$produto_serie                = pg_fetch_result($res, 0, 'serie');
    	$motivo_atraso                = pg_fetch_result($res, 0, 'motivo_atraso');
    	$obs                          = pg_fetch_result($res, 0, 'obs');
    	$codigo_posto                 = pg_fetch_result($res, 0, 'codigo_posto');
    	$duplicada                    = pg_fetch_result($res, 0, 'os_reincidente');
    	$os_reincidente               = pg_fetch_result($res, 0, 'reincidente_os');
    	$obs_reincidencia             = pg_fetch_result($res, 0, 'obs_reincidencia');
    	$defeito_constatado_descricao = pg_fetch_result($res, 0, 'defeito_constatado_descricao');
    	$causa_defeito_descricao      = pg_fetch_result($res, 0, 'causa_defeito_descricao');
    	$tipo_atendimento             = pg_fetch_result($res, 0, "tipo_atendimento");

    	if ($login_fabrica == 52) {
			$motivo_reincidencia = pg_fetch_result($res, 0, 'motivo_reincidencia');
		}

    	//--=== Tradução para outras linguas ============================= Raphael HD:1212
    	$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto_os AND upper(idioma) = '$sistema_lingua'";
    	$res_idioma = @pg_query($con,$sql_idioma);

    	if (@pg_num_rows($res_idioma) > 0) {
    		$produto_descricao  = trim(@pg_fetch_result($res_idioma, 0, 'descricao'));
    	}

    	if ($sistema_lingua == 'ES' and strlen(trim($defeito_constatado)) > 0) {

    		$sql = "SELECT descricao FROM tbl_defeito_constatado_idioma WHERE idioma = 'ES' AND defeito_constatado = $defeito_constatado";
    		$res = @pg_query($con, $sql);

    		if (pg_num_rows($res) > 0) $defeito_constatado_descricao = trim(pg_fetch_result($res, 0, 0));

    	}//--=== Tradução para outras linguas ================================================

    	$justificativa = $_GET["justificativa"];

    	if (strlen($os_reincidente) > 0) {

    		$sql = "SELECT tbl_os.sua_os
    				FROM   tbl_os
    				WHERE  tbl_os.os      = $os_reincidente
    				AND    tbl_os.fabrica = $login_fabrica
    				AND    tbl_os.posto   = $login_posto;";

    		$res = @pg_query($con,$sql) ;

    		if (pg_num_rows($res) > 0) $sua_os_reincidente = trim(pg_fetch_result($res, 0, 'sua_os'));

        }

	} else {

        $msg_erro = traduz("os.nao.encontrada.confira.o.n.da.os");

    }

}

$title = traduz("telecontrol.assistencia.tecnica.motivo.atraso.da.ordem.de.servico");

if (in_array($login_fabrica,array(14,24))) {
	$title = "Telecontrol - Assistência Técnica - Motivo Reincidência da Ordem de Serviço";
}

if ($sistema_lingua == 'ES')  $title = "Servicio Técnico - Razón del retraso de la Orden de Servicio";

$layout_menu = 'os';
include "cabecalho.php";

if (strlen($msg_erro) > 0) {?>

	<table width="600" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffCCCC">
		<tr>
			<td height="27" valign="middle" align="center">
				<b><font face="Arial, Helvetica, sans-serif" color="#FF3333"><?php
				// retira palavra ERROR:
				if (strpos($msg_erro,"ERROR: ") !== false) {
					$erro = traduz("foi.detectado.o.seguinte.erro").":<br>";
					$msg_erro = substr($msg_erro, 6);
				}

				// retira CONTEXT:
				if (strpos($msg_erro, "CONTEXT:")) {
					$x = explode('CONTEXT:', $msg_erro);
					$msg_erro = $x[0];
				}
				echo $erro . $msg_erro;?>
				</font></b>
			</td>
		</tr>
	</table><?php

}?>

<table width="750" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
<tr>
	<td><img height="1" width="20" src="imagens/spacer.gif"></td>
	<td valign="top" align="center">
		<!-- ------------- Formulário ----------------- -->
		<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
		<input type="hidden" name="os" value="<?echo $os?>">
		<input type="hidden" name="justificativa" value="<?echo $justificativa?>">
		<input type="hidden" name="tipo_atendimento" value="<?echo $tipo_atendimento?>">
		<p>
		
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
			<tr>
				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif"> <?php echo traduz("os");?></font>
					<br>
					<font size="2" face="Geneva, Arial, Helvetica, san-serif">
					<b><? if ($login_fabrica == 1) echo $codigo_posto; echo $sua_os; ?></b>
					</font>
				</td>
				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?php echo traduz("abertura");?>
					</font>
					<br>
					<font size="2" face="Geneva, Arial, Helvetica, san-serif">
					<b><? echo $data_abertura?></b>
					</font>
				</td>
				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?if ($sistema_lingua=='ES') echo "Usuario"; else echo traduz("consumidor");?></font>
					<br>
					<font size="2" face="Geneva, Arial, Helvetica, san-serif">
					<b><? echo $consumidor_nome ?></b>
					</font>
				</td>
				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?if ($sistema_lingua == 'ES') echo "Teléfono"; else echo traduz("telefone");?></font>
					<br>
					<font size="2" face="Geneva, Arial, Helvetica, san-serif">
					<b><? echo $consumidor_fone ?></b>
					</font>
				</td>
				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?if ($sistema_lingua == 'ES') echo "Factura"; else echo traduz("nota.fiscal");?></font>
					<br>
					<font size="2" face="Geneva, Arial, Helvetica, san-serif">
					<B><? echo $nota_fiscal; ?></B>
					</font>
				</td>
			</tr>
		</table>
		
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
			<tr>
				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?if ($sistema_lingua == 'ES') echo "Producto"; else echo traduz("produto");?></font>
					<br>
					<font size="2" face="Geneva, Arial, Helvetica, san-serif">
					<b><? echo $produto_referencia . " - " . $produto_descricao?></b>
					</font>
				</td>
				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?if ($sistema_lingua == 'ES') echo "N. serie"; else echo traduz("n.de.serie");?></font>
					<br>
					<font size="2" face="Geneva, Arial, Helvetica, san-serif">
					<b><? echo $produto_serie ?></b>
					</font>
				</td><?php
				if ($login_fabrica <> 5) {?>
					<td nowrap>
						<font size="1" face="Geneva, Arial, Helvetica, san-serif"><?if ($sistema_lingua=='ES') echo "Defecto constatado"; else echo traduz("defeito.constatado");?></font>
						<br>
						<font size="2" face="Geneva, Arial, Helvetica, san-serif">
						<B><? echo $defeito_constatado_descricao; ?></B>
						</font>
					</td><?php
				}?>
			</tr>
		</table>
	</td>
</tr>
<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF"><?php
		if ($duplicada == 't') { 
			echo "<table style=' border: #D3BE96 1px solid; background-color: #FCF0D8 ' width='700'><tr><td align='center'><b>".strtoupper(traduz("os.reincidente"))."</b>";
			if($login_fabrica == 1) echo "<br><font size='2'>". traduz("gentileza.justificar.abaixo.se.esse.atendimento.tem.procedencia.pois.foi.localizado.num.periodo.menor.ou.igual.a.90.dias.outras.oss.concluidas.pelo.seu.posto.com.os.mesmos.dados.de.nota.fiscal.e.produto.se.o.lancamento.estiver.incorreto.solicitamos.nao.fazer.a.justificativa.nesse.caso.entre.em.consulta.de.ordem.de.servico.de.consumidor.e.faca.a.exclusao.da.os")."</font>";
			echo "</td></tr></table>";

			if (strlen($os_reincidente) > 0 OR $reincidencia =='t') {
				
				$sql = "SELECT  tbl_os_status.status_os,tbl_os_status.observacao 
					FROM tbl_os_extra JOIN tbl_os_status USING(os)
					WHERE tbl_os_extra.os = $os
					AND tbl_os_status.status_os IN (67,68,70)";
				$res1 = pg_query($con,$sql);
				
				if (pg_num_rows ($res1) > 0) {
					$status_os  = trim(pg_fetch_result($res1, 0, 'status_os'));
					$observacao = trim(pg_fetch_result($res1, 0, 'observacao'));
				}
			
				echo "<br><table style=' border: #D3BE96 1px solid; background-color: #FCF0D8 ' align='center' width='700'>";
				echo "<tr><td align='center'><b><font size='1'>";
				echo ($sistema_lingua=='ES') ? "ATENCIÓN" : strtoupper(traduz("atencao"));
				echo "</font></b></td></tr>";
				echo "<tr>";
				echo "<td align='center'><font size='1'>";

				if (strlen($os_reincidente) > 0) {

					$sql  = "SELECT tbl_os.sua_os, tbl_os.serie FROM tbl_os WHERE tbl_os.os = $os_reincidente;";
					$res1 = pg_query($con,$sql);
					
					$sos     = trim(pg_fetch_result($res1, 0, 'sua_os'));
					$serie_r = trim(pg_fetch_result($res1, 0, 'serie'));

					if ($login_fabrica == 1) $sos=$codigo_posto.$sos;

				} else {

					//CASO NÃO TENHA A REINCIDENCIA NÃO TENHA SIDO APONTADA, PROCURA PELA REINCIDENCIA NA SERIE
					$sql = "SELECT os,sua_os,posto
								FROM tbl_os
								JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
								WHERE   serie   = '$produto_serie'
								AND     os     <> $os
								AND     fabrica = $login_fabrica
								AND posto = $login_posto
								AND     tbl_produto.numero_serie_obrigatorio IS TRUE ";

					if ($login_fabrica == 3) $sql .= " AND tbl_produto.linha = 3";

					$res2 = pg_query($con,$sql);

                    if ($sistema_lingua == 'ES') {
                        echo "ORDEN DE SERVICO CON NÚMERO DE SERIE: <u>$produto_serie</u> REINCIDENTE. ORDEN DE SERVICIO ANTERIOR:<BR>";
					} else {
						echo strtoupper(traduz("Ordem de serviço com número de série")).": <u>$produto_serie</u> ".strtoupper(traduz("reincidente.ordem.de.servico.anterior")).":<br>";
                    }

					if (pg_num_rows ($res2) > 0) {

						for ($i = 0 ; $i < pg_num_rows ($res2) ; $i++) {

							$sos_reinc   = trim(pg_fetch_result($res2, $i, 'sua_os'));
							$os_reinc    = trim(pg_fetch_result($res2, $i, 'os'));
							$posto_reinc = trim(pg_fetch_result($res2, $i, 'posto'));

							if ($posto_reinc == $login_posto) {
								echo " <a href='os_press.php?os=$os_reinc' target='_blank'>» $sos_reinc</a><br>";
							} else {
								echo "» $sos_reinc<br>";
							}
							
						}

					}
			
				}

				if ($status_os == 67) {

                    if ($sistema_lingua == 'ES') {
                        echo "ORDEN DE SERVICO CON NÚMERO DE SERIE: <u>$produto_serie</u> REINCIDENTE. ORDEN DE SERVICIO ANTERIOR:<BR>";
					} else {
						echo strtoupper(traduz("Ordem de serviço com número de série")).": <u>$produto_serie</u> ".strtoupper(traduz("reincidente.ordem.de.servico.anterior")).":<br>";
                    }

					if ( in_array($login_fabrica, array(11,172)) ) {

						$sql    = "SELECT os_reincidente FROM tbl_os_extra WHERE os = $os";
						$res2   = pg_query($con, $sql);
						$osrein = pg_fetch_result($res2, 0, 'os_reincidente');

						if (pg_num_rows($res2) > 0) {

							$sql = "SELECT os,sua_os FROM tbl_os WHERE serie = '$produto_serie' AND os = $osrein AND fabrica = $login_fabrica";
						}

						$res2 = pg_query($con, $sql);

						if (pg_num_rows($res2) > 0) {
							$sua_osrein = pg_fetch_result($res2, 0, 'sua_os');
							echo "<a href='os_press.php?os=$osrein' target='_blank'>» $sua_osrein</a>";
						}

					} else if (isset($novaTelaOs) || $login_fabrica == 52) {
						$sql = "SELECT osr.os, osr.sua_os FROM tbl_os_extra INNER JOIN tbl_os AS osr ON osr.os = tbl_os_extra.os_reincidente WHERE tbl_os_extra.os = {$os}";
						$res = pg_query($con, $sql);

						if (pg_num_rows($res) > 0) {
							$osr = pg_fetch_result($res, 0, "os");
							$osr_sua_os = pg_fetch_result($res, 0, "sua_os");

							echo "<a href='os_press.php?os=$osr' target='_blank'>» $osr_sua_os</a>";
						}
					} else {

						if ($login_fabrica != 122) {
							$reincidencia_ns_obrigatoria = " AND     tbl_produto.numero_serie_obrigatorio IS TRUE ";
						}

						$sql = "SELECT os,sua_os,posto
									FROM tbl_os
									JOIN    tbl_produto ON tbl_produto.produto = tbl_os.produto
									WHERE   serie   = '$produto_serie'
									AND     os     <> $os
									AND     fabrica = $login_fabrica
									AND posto = $login_posto
									$reincidencia_ns_obrigatoria ";

						if ($login_fabrica == 3) $sql .= " AND tbl_produto.linha = 3";
				
						$res2 = pg_query($con,$sql);
				
						if (pg_num_rows ($res2) > 0) {

							for ($i = 0; $i < pg_num_rows($res2) ; $i++) {

								$sos_reinc   = trim(pg_fetch_result($res2, $i, 'sua_os'));
								$os_reinc    = trim(pg_fetch_result($res2, $i, 'os'));
								$posto_reinc = trim(pg_fetch_result($res2, $i, 'posto'));

								if ($posto_reinc == $login_posto) {
									echo " <a href='os_press.php?os=$os_reinc' target='_blank'>» $sos_reinc</a><br>";
								} else {
									echo "» $sos_reinc<br>";
								}

							}

						}

					}

				} else if ($status_os == 68) {

					if ($sistema_lingua == 'ES') echo "ORDEN DE SERVICIO COM  MISMO DISTRIBUIDOR Y FACTURA REINCIDENTE. ORDEN DE SERVICIO ANTERIOR: ";
					else echo strtoupper(traduz("ordem.de.servico.com.mesma.revenda.e.nota.fiscal.reincidente.ordem.de.servico.anterior"));

					echo "<a href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a>";

				} else if ($status_os == 70) {

					if($login_fabrica == 20 AND in_array($login_posto,array(6359,17673))){
						echo strtoupper(traduz("ordem.de.servico.com.a.mesma.nota.fiscal.produto.e.serie.reincidente.ordem.de.servico.anterior"));
					
					} elseif ($login_fabrica == 194) {
						echo strtoupper(traduz("ordem.de.servico.com.a.mesma.nota.fiscal.e.cpf.reincidente.ordem.de.servico.anterior"));

					}else{

						if ($sistema_lingua == 'ES') echo "ORDEN DE SERVICIO CON MISMO DISTRIBUIDOR, FACTURA Y PRODUCTO REINCIDENTE. ORDEN DE SERVICIO ANTERIOR: ";
						else                         echo strtoupper(traduz("ordem.de.servico.com.mesma.revenda.nota.fiscal.e.produto.reincidente.ordem.de.servico.anterior"));
					}

					echo " <a href='os_press.php?os=$os_reincidente' target='_blank'>$sos</a>";

				} else {

					echo "OS Reincidente:<a href='os_press.php?os=$os_reincidente' target = '_blank'>$sos</a>";
				
				}

				echo "";
				echo "</font></td>";
				echo "</tr>";
				echo "</table>";

			}

		}

		echo '<br />';

		if ($justificativa == 'ok') {
		
			$teste = explode('Motivo atraso: ',$motivo_atraso);
			if (strlen(trim($teste[1]))>0) echo "<font color='FF0000'>".traduz("motivo.do.atraso").": ".$teste[1].'</font><br>';
			$motivo_atraso = "";

		}

		if ($login_fabrica == 52) {?>
			<FONT SIZE="2"><B><? if($justificativa=='ok')echo "Justificativa:";else 
			if ($sistema_lingua == 'ES') echo "DESCRIPCIÓN DEL DEFECTO CONSTATADO EN EL EQUIPO:"; else echo strtoupper(traduz("descricao.do.defeito.constatado.no.equipamento"));?></B></FONT>
			<br /><?php
		} else {?>
		<FONT SIZE="2"><B><? 
			if($justificativa=='ok'){
				if($login_fabrica == 24){
					echo traduz("motivo.da.reincidencia");
				}else{
					echo traduz("justificativa");
				}
			}else{ 

				if ($sistema_lingua == 'ES'){
					echo "Razón del retraso:"; 
				} else if($login_fabrica == 24){
					echo traduz("motivo.da.reincidencia");
				}else{
					echo traduz("motivo.do.atraso");
				}
			}
			?></B></FONT>
			<br /><?php
		}?>
		<textarea NAME="motivo_atraso" cols="70" rows="5" class="frm" ><? if($justificativa=='ok') echo "$obs_reincidencia"; else echo $motivo_atraso; ?></textarea>
		
	</td>
</tr><?php

if ($login_fabrica == 52) {?>

	<tr>
		<td>&nbsp;</td>
	</tr><?php

	$sqla = "SELECT * FROM tbl_os WHERE os = $os_reincidente";
	$resa = @pg_query ($con,$sqla);
	if (@pg_num_rows ($resa) > 0) {
		$posto_os_reincidente = pg_result($resa, '0', 'posto');
	}

	if ($posto_os_reincidente == $login_posto) {?>
		<tr>
			<td colspan='3' align='center'>
				<font size='2'><b><?php echo traduz("motivo.da.reincidencia");?></b></font>
				<br /> 
				<select name="motivo_reincidencia" id="motivo_reincidencia" class='frm'>
					<option value=""></option><?php
					$sql = "SELECT motivo_reincidencia,
									descricao
								FROM tbl_motivo_reincidencia 
								WHERE fabrica = $login_fabrica 
								AND ativo is true";
					
					$res = pg_query($con,$sql);
					
					for ($i = 0; $i < pg_num_rows($res); $i++) {
						
						$motivo_reincidencia_sel	   = pg_result($res, $i, 'motivo_reincidencia');
						$motivo_reincidencia_descricao = pg_result($res, $i, 'descricao');
						
						$selected_motivo_reincidencia = ($motivo_reincidencia_sel == $motivo_reincidencia) ? "SELECTED" : null;
						
						echo "<option value='$motivo_reincidencia_sel' $selected_motivo_reincidencia>$motivo_reincidencia_descricao</option>";
						
					} ?>
				</select>
			</td>
		</tr><?php
	}

}?>

<tr>
	<td>&nbsp;</td>
</tr>

<tr>
	<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF">
		<input type="hidden" name="btn_acao" value=""><?php
        $btn_gravar_text = ($sistema_lingua == 'ES') ? 'Guardar' : traduz('gravar');
		$btn_aguarde_txt = ($sistema_lingua == 'ES') ? 'Espere mientras se envían los datos...' : traduz('aguarde.submissao');?>
		<img src='imagens/btn_<?=strtolower($btn_gravar_text)?>.gif' onclick="if (document.frm_os.btn_acao.value == '') {document.frm_os.btn_acao.value = 'gravar'; document.frm_os.submit()} else {alert('<?=$btn_aguarde_txt?>')}" alt="<?=$btn_gravar_text?>" border='0' style="cursor:pointer;" /><?php
		if ($login_fabrica == 52) {
			echo '&nbsp;';
			echo '<a href="os_print.php?os='.$os.'" target="_blank">';
				echo '<img border="0" src="imagens/btn_imprime.gif" />';
			echo '</a>';
		}?>
	</td>
</tr>

</table>

</form>

<?php // HD - 6678829 - JP
	$j_justificativa = filter_input(INPUT_GET, 'justificativa', FILTER_SANITIZE_STRING);
	if( $login_fabrica == 157 AND  $j_justificativa == 'ok') echo "<style> .titulo{ display: none; } </style>";
	
include "rodape.php";
?>
