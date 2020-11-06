<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

include 'funcoes.php';

if (strlen($_GET['os']) > 0)   $os = trim ($_GET['os']);
if (strlen($_POST['os']) > 0)  $os = trim ($_POST['os']);

/* HD 35521 */
$justificativa_obrigatoria = 1;

$justificativa = "";

if ($login_fabrica == 11) {

	$email_origem  = "helpdesk@telecontrol.com.br";
	$email_destino = "fabio@telecontrol.com.br";
	$assunto       = "JUSTIFICATIVA LENOXX OS=$os";
	$corpo.="MENSAGEM AUTOMÁTICA. NÃO RESPONDA A ESTE E-MAIL \n\n OS: $y_sua_os \nData: $y_data\nPosto: $y_nome\nProduto: $xproduto\nPeca: $xpeca";
	$body_top = "--Message-Boundary\n";
	$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
	$body_top .= "Content-transfer-encoding: 7BIT\n";
	$body_top .= "Content-description: Mail message body\n\n";
	#@mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem);

}

if ($btn_acao == "gravar") {

	if (strlen ($os) > 0) {

		$justificativa = trim($_POST ['justificativa']);
		$tipo = trim($_POST ['tipo']);

		/* HD 35521 */
		if ($tipo == '116') {
			$justificativa_obrigatoria = 0;
		}

		$res = pg_exec($con,"BEGIN TRANSACTION");

		//8209 Paulo colocou para justificativa minima de 3 caracteres
		if ($justificativa_obrigatoria == 1 AND strlen($justificativa) < 3) {
			$msg_erro = "Justificativa muito curta!";
		}

		if ($justificativa_obrigatoria == 1 AND strlen($justificativa) == 0) {
			$msg_erro = "Por favor, justifique a utilização da peça!";
		}

		if (strlen($msg_erro) == 0) {

			if ($tipo == '72') { // SAP

				if ($login_fabrica == 11) {

					$sql = "UPDATE tbl_os_status
								SET observacao = COALESCE(observacao,NULL,'') || ' Justificativa: $justificativa'
							WHERE os = $os
							AND os_status = (SELECT os_status FROM tbl_os_status WHERE os = $os AND status_os = 72 ORDER BY data DESC LIMIT 1)";

					$res = @pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);

				} else if ($login_fabrica == 3) {

					$sql = "UPDATE tbl_os_status
								SET observacao = COALESCE(observacao,NULL,'') || ' Justificativa: $justificativa'
							WHERE os = $os
							AND os_status = (SELECT os_status FROM tbl_os_status WHERE os = $os AND status_os = 72 ORDER BY data DESC LIMIT 1)";

					$res = @pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);

					if (strlen($msg_erro) == 0) {

						$sql = "SELECT qtde_dias_intervencao_sap
								FROM tbl_fabrica
								WHERE fabrica = $login_fabrica";

						$res = pg_exec($con, $sql);
						$qtde_dias_intervencao_sap = pg_result($res, 0, 'qtde_dias_intervencao_sap');

						//HD 284057: Estava liberando TODAS as OS automaticamente, mesmo as abertas a menos de dias que está configurado para a intervenção SAP. Coloquei a validação.
						$sql = "SELECT CURRENT_DATE - data_abertura AS dias FROM tbl_os WHERE os = $os";
						$res = pg_exec($con, $sql);
						$dias = pg_result($res, 0, 'dias');

						if ($dias >= $qtde_dias_intervencao_sap) {
							$sql = "INSERT INTO tbl_os_status (os, status_os, data,observacao) values ($os, 73, current_timestamp, 'Favor tomar o devido cuidado com o prazo de 30 dias para fechamento de OS. Pedido de peça efetuado após $qtde_dias_intervencao_sap dias da data de abertura da OS.')";

							$res = @pg_exec($con, $sql);
							$msg_erro .= pg_errormessage($con);
						}
						//HD 284057: FIM

					}

				}

			}

			if ($tipo == '62') { // TECNICA

				$sql = "UPDATE tbl_os_status
							SET observacao = 'Peça da O.S. com intervenção da fábrica. Justificativa: $justificativa'
						WHERE os = $os
					AND os_status = (SELECT os_status FROM tbl_os_status WHERE os=$os AND status_os=62 ORDER BY data DESC LIMIT 1)";

				$res = @pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

			}

			if ($tipo == '116') { // CARTEIRA

				if (strlen($justificativa) > 0) {
					$justificativa = "Justificativa: $justificativa";
				}

				$sql = "UPDATE tbl_os_status SET
							observacao = 'Peça da O.S. com intervenção de carteira. $justificativa'
						WHERE os=$os
						AND os_status = (SELECT os_status FROM tbl_os_status WHERE os=$os AND status_os=116 ORDER BY data DESC LIMIT 1)";

				$res = @pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

			}

			if($tipo == '175'){

				if (strlen($justificativa) > 0) {
					$justificativa = "Justificativa: $justificativa";
				}

				$sql = "UPDATE tbl_os_status SET
							observacao = 'Peça da O.S. com intervenção de display. $justificativa'
						WHERE os=$os
						AND os_status = (SELECT os_status FROM tbl_os_status WHERE os=$os AND status_os=175 ORDER BY data DESC LIMIT 1)";

				$res = @pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

			}

		}

		if (strlen ($msg_erro) == 0) {

			$res = pg_exec($con,"COMMIT TRANSACTION");
			header("Location: os_finalizada.php?os=$os");
			exit;

		} else {

			$res = pg_exec($con,"ROLLBACK TRANSACTION");

		}

	}
}

if (strlen($os) > 0) {

    #----------------- Le dados da OS --------------
    $sql = "SELECT  tbl_os.defeito_constatado                                                   ,
                    tbl_os.nota_fiscal                                                          ,
                    tbl_os.consumidor_nome                                                      ,
                    tbl_os.consumidor_fone                                                      ,
                    tbl_os.sua_os                                                               ,
                    tbl_os.serie                                                                ,
                    tbl_os.obs                                                                  ,
                    tbl_os.os_reincidente                                                       ,
                    tbl_os.motivo_atraso                                                        ,
                    to_char(tbl_os.data_abertura,'DD/MM/YYYY')  AS data_abertura2               ,
                    tbl_produto.produto                                                         ,
                    tbl_produto.referencia                                                      ,
                    tbl_produto.descricao                                                       ,
                    tbl_produto.linha                                                           ,
                    tbl_linha.nome                              AS linha_nome                   ,
                    tbl_posto_fabrica.codigo_posto                                              ,
                    tbl_defeito_constatado.descricao            AS defeito_constatado_descricao ,
                    tbl_causa_defeito.descricao                 AS causa_defeito_descricao      ,
                    tbl_os_extra.os_reincidente                 AS reincidente_os
            FROM    tbl_os
            JOIN    tbl_os_extra            USING   (os)
       LEFT JOIN    tbl_produto             USING   (produto)
            JOIN    tbl_posto               USING   (posto)
            JOIN    tbl_posto_fabrica       ON      tbl_posto.posto                             = tbl_posto_fabrica.posto
                                            AND     tbl_posto_fabrica.fabrica                   = $login_fabrica
       LEFT JOIN    tbl_linha               ON      tbl_produto.linha                           = tbl_linha.linha
       LEFT JOIN    tbl_defeito_constatado  ON      tbl_defeito_constatado.defeito_constatado   = tbl_os.defeito_constatado
       LEFT JOIN    tbl_causa_defeito       ON      tbl_causa_defeito.causa_defeito             = tbl_os.causa_defeito
            WHERE   tbl_os.os = $os";

    $res = pg_exec($con,$sql) ;

    $defeito_constatado           = pg_result($res, 0, 'defeito_constatado');
    $nota_fiscal                  = pg_result($res, 0, 'nota_fiscal');
    $consumidor_nome              = pg_result($res, 0, 'consumidor_nome');
    $consumidor_fone              = pg_result($res, 0, 'consumidor_fone');
    $sua_os                       = pg_result($res, 0, 'sua_os');
    $obs                          = pg_result($res, 0, 'obs');
    $duplicada                    = pg_result($res, 0, 'os_reincidente');
    $produto_serie                = pg_result($res, 0, 'serie');
    $motivo_atraso                = pg_result($res, 0, 'motivo_atraso');
    $data_abertura                = pg_result($res, 0, 'data_abertura2');
    $produto_os                   = pg_result($res, 0, 'produto');
    $produto_referencia           = pg_result($res, 0, 'referencia');
    $produto_descricao            = pg_result($res, 0, 'descricao');
    $linha                        = pg_result($res, 0, 'linha');
    $linha_nome                   = pg_result($res, 0, 'linha_nome');
    $codigo_posto                 = pg_result($res, 0, 'codigo_posto');
    $defeito_constatado_descricao = pg_result($res, 0, 'defeito_constatado_descricao');
    $causa_defeito_descricao      = pg_result($res, 0, 'causa_defeito_descricao');
    $os_reincidente               = pg_result($res, 0, 'reincidente_os');

    if(in_array($login_fabrica, array(3))){
    	$cond_display = ", 175";
    }

	$sql_status = "SELECT	status_os,
							observacao
					FROM tbl_os_status
					WHERE os=$os
					AND status_os IN (62,72,116 $cond_display)
					ORDER BY status_os
					DESC LIMIT 1";
	$res_status = pg_exec($con,$sql_status) ;
	if (pg_numrows($res_status) > 0){
		$status_os			= pg_result($res_status,0,status_os);
		$status_observacao	= pg_result($res_status,0,observacao);
	}

	if ($status_os == '116') {
		$justificativa_obrigatoria = 0;
	}

}

$title = "Telecontrol - Assistência Técnica - Justificativa de Pedido de Peça";

$layout_menu = 'os';
include "cabecalho.php";

if (strlen ($msg_erro) > 0) {?>
	<table width="600" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffCCCC">
		<tr>
			<td height="27" valign="middle" align="center">
				<b><font face="Arial, Helvetica, sans-serif" color="#FF3333"><?php
				// retira palavra ERROR:
				if (strpos($msg_erro,"ERROR: ") !== false) {
					$msg_erro = substr($msg_erro, 6);
				}
				// retira CONTEXT:
				if (strpos($msg_erro,"CONTEXT:")) {
					$x = explode('CONTEXT:',$msg_erro);
					$msg_erro = $x[0];
				}
				echo $msg_erro;?>
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
		<p>
		<table width="100%" border="0" cellspacing="5" cellpadding="0">
			<tr>
				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">OS</font>
					<br />
					<font size="2" face="Geneva, Arial, Helvetica, san-serif">
					<b><? echo $sua_os; ?></b>
					</font>
				</td>
				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Abertura</font>
					<br />
					<font size="2" face="Geneva, Arial, Helvetica, san-serif">
					<b><? echo $data_abertura?></b>
					</font>
				</td>
				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Consumidor</font>
					<br />
					<font size="2" face="Geneva, Arial, Helvetica, san-serif">
					<b><? echo $consumidor_nome ?></b>
					</font>
				</td>
				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Telefone</font>
					<br />
					<font size="2" face="Geneva, Arial, Helvetica, san-serif">
					<b><? echo $consumidor_fone ?></b>
					</font>
				</td>
				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nota Fiscal</font>
					<br />
					<font size="2" face="Geneva, Arial, Helvetica, san-serif">
					<B><? echo $nota_fiscal; ?></B>
					</font>
				</td>
			</tr>
		</table>

		<table width="100%" border="0" cellspacing="5" cellpadding="0">
			<tr>
				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Produto</font>
					<br />
					<font size="2" face="Geneva, Arial, Helvetica, san-serif">
					<b><? echo $produto_referencia . " - " . $produto_descricao?></b>
					</font>
				</td>
				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">N. Série</font>
					<br />
					<font size="2" face="Geneva, Arial, Helvetica, san-serif">
					<b><? echo $produto_serie ?></b>
					</font>
				</td>
				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Defeito Constatado</font>
					<br />
					<font size="2" face="Geneva, Arial, Helvetica, san-serif">
					<B><? echo $defeito_constatado_descricao; ?></B>
					</font>
				</td>
			</tr>
		</table>

		<br /><br />

		<table width="100%" border="0" cellspacing="5" cellpadding="0">
			<tr>
				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Peça</font>
				</td>
				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Descrição</font>
				</td>
				<td nowrap>
					<font size="1" face="Geneva, Arial, Helvetica, san-serif">Serviço Realizado</font>
				</td>
			</tr><?php

            $sql = "SELECT  tbl_peca.peca                                               ,
                            tbl_peca.referencia                                         ,
                            tbl_peca.descricao                                          ,
                            tbl_peca.bloqueada_garantia                                 ,
                            tbl_peca.retorna_conserto                                   ,
                            tbl_peca.intervencao_carteira                               ,
                            tbl_peca.peca_critica                                       ,
                            tbl_servico_realizado.gera_pedido       AS gera_pedido      ,
                            tbl_servico_realizado.descricao         AS descserv         ,
                            tbl_servico_realizado.servico_realizado AS servico_realizado
                    FROM    tbl_os_produto
                    JOIN    tbl_os_item             USING(os_produto)
                    JOIN    tbl_peca                USING(peca)
                    JOIN    tbl_servico_realizado   USING(servico_realizado)
                    WHERE   tbl_os_produto.os   = $os
                    AND     tbl_peca.fabrica    = $login_fabrica
                    AND     tbl_os_item.pedido  IS NULL
            ";

            $res = pg_exec($con,$sql);
            $contador = 0;

            for ($i = 0; $i < pg_numrows($res); $i++) {

                $xpeca                      = pg_result($res, $i, 'peca');
                $xpeca_referencia           = pg_result($res, $i, 'referencia');
                $xpeca_descricao            = pg_result($res, $i, 'descricao');
                $bloqueada_garantia_peca    = pg_result($res, $i, 'bloqueada_garantia');
                $peca_intervencao_tecnica   = pg_result($res, $i, 'retorna_conserto');
                $peca_intervencao_carteira  = pg_result($res, $i, 'intervencao_carteira');
                $peca_critica               = pg_result($res, $i, 'peca_critica');
                $servico_realizado          = pg_result($res, $i, 'servico_realizado');
                $servico                    = pg_result($res, $i, 'descserv');
                $gera_pedido                = pg_result($res, $i, 'gera_pedido');

                if (
                        (
                            $bloqueada_garantia_peca    == 't'  ||
                            $peca_intervencao_tecnica   == 't'  ||
                            $peca_intervencao_carteira  == 't'  ||
                            $peca_critica               == 't'
                        )
                    && $gera_pedido == 't'
                ) {
                    $xpeca_referencia   = "<u>$xpeca_referencia</u>";
                    $xpeca_descricao    = "<u>$xpeca_descricao</u>";
                    $servico            = "<u>$servico</u>";
                    $contador++;
                }
?>
                <tr>
                    <td nowrap>
                        <font size="2" face="Geneva, Arial, Helvetica, san-serif"><b><? echo $xpeca_referencia ?></b>
                        </font>
                    </td>
                    <td nowrap>
                        <font size="2" face="Geneva, Arial, Helvetica, san-serif"><b><? echo $xpeca_descricao ?></b></font>
                    </td>
                    <td nowrap>
                        <font size="2" face="Geneva, Arial, Helvetica, san-serif"><b><? echo $servico ?></b></font>
                    </td>
                </tr>
    <?php
            }
?>
			</table>
		</td>
	</tr>
	<tr>
		<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF"></td>
	</tr>
	<tr>
		<td height="27" valign="middle" align="center" colspan="3" bgcolor="#FFFFFF"><?php
			if ($contador == 0) {

				if ($status_os == 62) {

					echo "<FONT SIZE='2' color='red'><b>A peça <u>destacada</u> acima está sob intervenção da Assistência Técnica da Fábrica.<br />Informe a justificativa da solicitação desta peça</b></FONT>";

				} elseif ($status_os == 116) {

					echo "<FONT SIZE='2' color='red'><b>A peça <u>destacada</u> acima está sob intervenção da Assistência Técnica da Fábrica.<br />Informe a justificativa da solicitação desta peça</b></FONT>";

				} else {

					if ($login_fabrica == 3) {

						$sql= "SELECT qtde_dias_intervencao_sap
								FROM tbl_fabrica
								WHERE fabrica = $login_fabrica";

						$res = pg_exec($con,$sql);

						$qtde_dias_intervencao_sap = pg_result($res, 0, 'qtde_dias_intervencao_sap');

						echo "<FONT SIZE='2' color='red'><b>OSs abertas a mais de $qtde_dias_intervencao_sap dias necessitam de justificativa para pedidos de peça. Justifique a solicitação no campo abaixo.</b></FONT>";

					}

				}

			} else if ($contador > 1) {

				if ($status_os == 72 OR $status_os == 116) {

					echo "<FONT SIZE='2' color='red'><b>As peças <u>destacadas</u> acima acima necessitam de autorização da Fábrica para atendimento em garantia.<br />Para liberação dessas peças, favor informe a justificativa.</b></FONT>";

				} else {

					echo "<FONT SIZE='2' color='red'><b>As peças <u>destacadas</u> acima estão sob intervenção da Assistência Técnica da Fábrica.<br />Informe a justificativa da solicitação dessas peças.</b></FONT>";

				}

			} else {

				if ($status_os == 72 OR $status_os == 116) {

					echo "<FONT SIZE='2'  color='red'><b>A peça <u>destacada</u> acima necessita de autorização da Fábrica para atendimento em garantia.<br />Para liberação desta peça, favor informe a justificativa.</b></FONT>";

				} else {

					echo "<FONT SIZE='2' color='red'><b>A peça <u>destacada</u> acima está sob intervenção da Assistência Técnica da Fábrica.<br />Informe a justificativa da solicitação desta peça.</b></FONT>";

				}

			}?>
			<br />
			<br /><b>Justificativa</b><?php
			/* HD 35521 */
			if ($justificativa_obrigatoria == 0) {
				echo "<FONT SIZE='2' color='black'><b>(OPCIONAL)</b></FONT>";
			}?>
			<br />
			<form name="frm_os" method="post" action="<? echo $PHP_SELF ?>">
				<input type="hidden" name="os" value="<?echo $os?>">
				<input type='hidden' name='tipo' value='<? echo $status_os; ?>'>
				<textarea NAME="justificativa" cols="70" rows="5" class="frm" ><? echo $justificativa; ?></textarea>
				<input type="hidden" name="btn_acao" value=""><br />
				<img src='imagens/btn_gravar.gif' onclick="javascript: if (document.frm_os.btn_acao.value == '' ) { document.frm_os.btn_acao.value='gravar' ; document.frm_os.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar" border='0' style="cursor:pointer;">
			</form>
		</td>
	</tr>

</table><?php

include "rodape.php";?>