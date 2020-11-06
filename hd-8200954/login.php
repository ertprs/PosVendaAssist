<?php
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

session_start();

if(isset($_SESSION['cliente_loja']) && !empty($_SESSION['cliente_loja'])){
    unset($_SESSION['cliente_loja']);
    header("Location: externos/loja");
    exit;
}

if($login_fabrica == 177) {

    $query = "SELECT parametros_adicionais, 
                    (SELECT status 
                    FROM tbl_credenciamento 
                    WHERE posto = $login_posto 
                    AND   fabrica = $login_fabrica
                    ORDER BY credenciamento DESC
                    LIMIT 1) AS status_credenciamento 
              FROM tbl_posto_fabrica
              WHERE fabrica = $login_fabrica 
              AND   posto   = $login_posto";

    $res = pg_query($con, $query);

    $parametros            = pg_fetch_result($res, 0, 'parametros_adicionais');
    $status_credenciamento = pg_fetch_result($res, 0, 'status_credenciamento');
    $parametros            = json_decode($parametros, 1);
    #print_r($status_credenciamento); echo "<br>"; print_r($parametros); exit;
    if ( ($parametros["contrato"] == 'f' || !isset($parametros['contrato'])) && $status_credenciamento != 'EM DESCREDENCIAMENTO' ) {

        include 'termo_uso/template_termo_uso.php';
        exit;
    }
}

include 'token_cookie.php';
include_once "funcoes.php";

if (!empty($_GET["ajax_comunicado"])) {

    $comunicado_lido = $_GET['comunicado_lido'];

    if (in_array($login_fabrica, [85])) {

        $jsonLeitor = json_encode([
            "cpf_comunicado"  => str_replace([".","/","-"], "", trim($_GET["cpf_comunicado"])),
            "nome_comunicado" => utf8_encode(trim(pg_escape_string($_GET["nome_comunicado"])))
        ]);

        $campoLeitor = ", leitor";
        $valueLeitor = ", '{$jsonLeitor}'";

    }

    $sql = "SELECT comunicado
            FROM tbl_comunicado_posto_blackedecker
            WHERE comunicado = $comunicado_lido
            AND   posto      = $login_posto";
    $res = pg_query ($con,$sql);
    
    if (pg_num_rows($res) == 0){
        $sql = "INSERT INTO tbl_comunicado_posto_blackedecker (comunicado, posto, data_confirmacao {$campoLeitor}) 
                VALUES ($comunicado_lido, $login_posto, CURRENT_TIMESTAMP {$valueLeitor})";
    }else{
        $sql = "UPDATE tbl_comunicado_posto_blackedecker SET
                       data_confirmacao = CURRENT_TIMESTAMP
                WHERE  comunicado = $comunicado_lido
                AND    posto      = $login_posto";
    }
    $res = pg_query ($con,$sql);

    if(!in_array($login_fabrica, array(91,169,170,175,176,177,186,194,200))) {
		//funÁao envia e-mail
		$sql = "SELECT remetente_email, tbl_posto.nome , descricao
				FROM tbl_comunicado
				JOIN tbl_posto USING (posto)
				WHERE tbl_comunicado.comunicado = $comunicado_lido
				AND tbl_comunicado.posto IS NOT NULL
				AND tbl_comunicado.remetente_email IS NOT NULL";
		$res = pg_query($con,$sql);

		if (pg_num_rows($res) == 1 ) {
			$remetente_email = pg_fetch_result($res, 0, 'remetente_email');
			$posto_nome      = pg_fetch_result($res, 0, 'nome');
			$descricao       = pg_fetch_result($res, 0, 'descricao');
			$assunto      = "Leitura de Comunicado";
			$corpo        = "O Posto $posto_nome leu o comunicado $descricao.";

			include_once 'class/communicator.class.php';
			$mail = new TcComm($externalId, $externalEmail);

			$mail->sendMail($remetente_email, stripslashes($assunto), $corpo);

		}
	}
    exit(json_encode(["success" => true]));
    
}

$xxpesquisaSatisfacao = False;

if (in_array($login_fabrica, [35])) {

    $sqlX = "SELECT pesquisa
               FROM tbl_pesquisa
              WHERE fabrica    = $login_fabrica
                AND categoria  = 'posto_autorizado'
                AND ativo     IS TRUE
              ORDER BY pesquisa DESC LIMIT 1 ";

    $res = pg_query($con, $sqlX);

    if (pg_num_rows($res) > 0) {

        $pesquisa = pg_fetch_result($res, 0, 0) ;

        $sqlX = "SELECT  COUNT(resposta) as total
                 FROM    tbl_resposta
                 WHERE   pesquisa = $pesquisa
                 AND     posto    = $login_posto";

        $resi = pg_query($con, $sqlX);

        $resi = pg_fetch_result($resi, 0, total);
        
        if ($resi == 0) {

            $xxpesquisaSatisfacao = True;
            $pesquisaToken = strval($login_fabrica).strval($login_posto).strval($pesquisa);
            $pesquisaToken = sha1($pesquisaToken);
        }
    }
}
?>
<script src="js/jquery-1.8.3.min.js"></script>
<?
$PHP_SELF = $_SERVER['PHP_SELF'];

$sql = "SELECT posto FROM tbl_posto_mapa WHERE posto = {$login_posto}";
$res = pg_query($con, $sql);

// if (!pg_num_rows($res) && !strlen($cookie_login["cook_admin"]) && strtoupper($login_pais) == "BR" && $login_fabrica != 87) {
//     header("Location: confirma_localizacao.php");
//     exit;
// }

$plugins = array(
    "shadowbox"   
);
include("plugin_loader.php");

if ($login_fabrica <> 87 && !in_array($login_posto,[6359,4311]) && empty($cookie_login['cook_admin'])) {
	$sql = "SELECT pesquisa FROM tbl_pesquisa WHERE pesquisa = 677 AND ativo";
	$res = pg_query($con,$sql);

	if(pg_num_rows($res) > 0){
		$sql = "SELECT resposta FROM tbl_resposta WHERE pesquisa = 677 AND posto = $login_posto AND data_input::date = CURRENT_DATE";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) == 0){
		?>
			<script type="text/javascript">
				
				window.onload = function(){
					Shadowbox.init();

					Shadowbox.open({
					    content : "pesquisa_situacao_atendimento_posto.php?pesquisa=677&posto=" + <?=$login_posto?>,
					    player: 'iframe',
					    title : "Pesquisa",
					    width   :   900,
					    height  :   400,
					    options: {
						modal: true,
						enableKeys: false,
						displayNav: false
					    }
					});
				}
			</script>
		<?php
		exit;
		}
	}
}

?>
<script type="text/javascript">

    <?php

     if (isset($xxpesquisaSatisfacao) && $xxpesquisaSatisfacao) { ?> 
        
        window.onload = function() {   

            Shadowbox.init({
                skipSetup: true
            });

            pesquisaSatisfacaoPosto();
        }
        
    <?php } ?>


    function pesquisaSatisfacaoPosto(url = "") {

        var pesquisa = '<?= $pesquisa?>';
        var posto    = '<?= $login_posto?>';
        var fabrica  = '<?= $login_fabrica?>';
        var token    = '<?= $pesquisaToken ?>';

        Shadowbox.init({
            skipSetup: true
        });

        Shadowbox.open({
            content : "externos/pesquisa_satisfacao_posto.php?token=" + token + "&pesquisa_posto=" + true + "&pesquisa=" + pesquisa + "&posto=" + posto + "&fabrica="+ fabrica,
            player: 'iframe',
            title : "Pesquisa de Satisfacao",
            width   :   900,
            height  :   500,
            options: {
                modal: true,
                enableKeys: false,
                displayNav: false
            }
        });
    }
    
</script>

<?php
if(in_array($login_fabrica, [167, 203])){
	// EM CREDENCIAMENTO - Aceite do contrato
	$sqlCredenciamento = "SELECT
				tbl_posto_fabrica.credenciamento
				FROM tbl_posto_fabrica
                JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica =  {$login_fabrica}
				WHERE tbl_posto_fabrica.fabrica = {$login_fabrica}
                AND tbl_tipo_posto.descricao != 'Revenda'
				AND tbl_posto_fabrica.posto = {$login_posto}";

	$resCredenciamento = pg_query($con, $sqlCredenciamento);

	$credenciamento = pg_fetch_result($resCredenciamento, 0, 'credenciamento');

	$credenciamento = strtoupper($credenciamento);

	/*if(($login_fabrica == 167 && $credenciamento == "EM CREDENCIAMENTO") || $login_fabrica == 203 && in_array($credenciamento, ["EM CREDENCIAMENTO", "CREDENCIADO"]) ){     */
    if($credenciamento == "EM CREDENCIAMENTO"){   
	    ?>
	    <script>
		window.onload = function(){        
		    Shadowbox.init();  
		    var fabrica = "<?=$login_fabrica?>";
		    var posto   = "<?=$login_posto?>";
		    var linhas  = "<?=$linhas_contrato?>";
		    
		    Shadowbox.open({
            /*content :   "aceite_contrato.php?status=<?=$credenciamento?>",*/
            content :  "aceite_contrato.php",
			player  :   "iframe",
			title   :   "",
			width   :   2000,
			height  :   1500,
			options : {
			    modal: true,
			    enableKeys: false,
			    displayNav: false,
			    onFinish: function(){
				$("#sb-nav-close").hide();
			    },
			    overlayColor:'#fcfcfc'
			}
		    });  
		}        

		function fechar(){
		    Shadowbox.close();
		    window.location.reload();
		}
	    </script>            
	    <?
	    exit;
	} else {
	    ?>
	    <script>
		window.onload = function(){
		    Shadowbox.close();
		}
	    </script>
	    <?
	}   
}

if (($login_fabrica == 35 && $xxpesquisaSatisfacao == False) and $login_pais !== 'BR')  {
    header("Location: menu_inicial.php");
    exit;
}

if ($login_fabrica == 24) {

    $sql = "SELECT auditoria_online
            FROM tbl_auditoria_online
            WHERE fabrica = $login_fabrica
            AND pesquisa = 22
            AND posto = $login_posto
            AND concorda_relatorio IS NULL";
    $res = @pg_query($con,$sql);

    $auditoria_online   = trim(pg_fetch_result($res,0,auditoria_online));

    if (pg_num_rows($res) > 0) {

        header("Location: visualiza_relatorio_visita_tecnico.php?auditoria_online=$auditoria_online");
    }
}

if (in_array($login_fabrica, array(152,180,181,182))) {
    if ($_POST["btn_acao"] == "Enviar") {
        $nome_contrato      = $_POST['nome_contrato'];
        $cpf_contrato       = $_POST['cpf_contrato'];
        $aceita_contrato    = $_POST['aceita_contrato'];
        $id_contrato        = $_POST['id_contrato'];

        if (strlen($cpf_contrato) > 0) {
            $res_cpf = @pg_query($con,"SELECT fn_valida_cnpj_cpf('$cpf_contrato')");
            if ($res_cpf === false) {
                $msg_erro["msg"][]    = "CPF inv·lido.";
                $msg_erro["campos"][] = "cpf_contrato";
            }
        } else {
            $msg_erro["msg"][]    = "O campo CPF È obrigatÛrio.";
            $msg_erro["campos"][] = "cpf_contrato";
        }

        if (strlen(trim($nome_contrato) == '')) {
            $msg_erro["msg"][]    = "O campo Nome È obrigatÛrio.";
            $msg_erro["campos"][] = "nome_contrato";
        }

        if (!count($msg_erro["msg"])) {

            $nome_contrato = retira_acentos($nome_contrato);

            $json_campos_adicionais = array(
              "nome_contrato"   => $nome_contrato,
              "cpf_contrato"    => $cpf_contrato,
              "aceito"          => $aceita_contrato
            );

            $dados_json = json_encode($json_campos_adicionais, true);

            if ($aceita_contrato == 'sim') {
                $sql = "INSERT INTO tbl_comunicado_posto_blackedecker (
                            comunicado,
                            posto,
                            data_confirmacao,
                            fabrica,
                            leitor
                        ) VALUES (
                            $id_contrato,
                            $login_posto,
                            CURRENT_TIMESTAMP,
                            $login_fabrica,
                            '$dados_json'
                        )";
                $res = pg_query($con, $sql);
                $error_sql = pg_last_error($con);
                
                header("Location: $PHP_SELF?comunicado_lido=$id_contrato");
            } else {
                $res = pg_exec ($con,"BEGIN TRANSACTION");

                $sql = "INSERT INTO tbl_credenciamento (
                            posto             ,
                            fabrica           ,
                            data              ,
                            status            ,
                            confirmacao
                        ) VALUES (
                            $login_posto      ,
                            $login_fabrica    ,
                            current_timestamp ,
                            'EM DESCREDENCIAMENTO'  ,
                            current_timestamp
                        )";
                $res = pg_query ($con,$sql);
                $error_sql = pg_last_error($con);

                if (strlen($error_sql) == 0 ) {
                    $sql = "UPDATE  tbl_posto_fabrica SET
                                    credenciamento = 'EM DESCREDENCIAMENTO',
                                    digita_os = 'f',
                                    pedido_faturado = 'f'
                            WHERE   fabrica = $login_fabrica
                            AND     posto   = $login_posto;";
                    $res = pg_query($con,$sql);
                    $error_sql = pg_last_error($con);

                    $sql = "INSERT INTO tbl_comunicado_posto_blackedecker(
                                comunicado,
                                posto,
                                data_confirmacao,
                                fabrica,
                                leitor
                            )VALUES(
                                $id_contrato,
                                $login_posto,
                                current_timestamp,
                                $login_fabrica,
                                '$dados_json'
                            )";
                    $res = pg_query($con, $sql);
                    $error_sql = pg_last_error($con);
                }

                if (strlen ($error_sql) == 0) {
                    $res = pg_exec ($con,"COMMIT TRANSACTION");
                    echo '<script type="text/javascript">
                            alert("Seu credenciamento n√£o foi realizado, em caso de d√∫vidas entrar em contato com a f√°brica");
                            window.location="'.$PHP_SELF.'?comunicado_lido='.$id_contrato.'";
                        </script>
                    ';
                    #header ("Location: http://www.telecontrol.com.br/");
                    exit;
                } else {
                    $msg_erro["msg"][] = "Erro ao descredenciar o Posto";
                }
            }
        }
    }
}

if ($login_fabrica == 3) {

    $os = $_GET['excluir'];

    if (strlen ($os) > 0) {
        $sql = "SELECT fn_os_excluida($os,$login_fabrica,null);";
        $res = @pg_query ($con,$sql);
        $msg_erro = pg_errormessage($con);
    }

    $os = $_GET['consertado'];
    if (strlen ($os) > 0) {
        $msg_erro = "";

        // if ($login_fabrica == 11) {
        //     $sqlD = "SELECT os
        //             FROM tbl_os
        //             WHERE os = $os
        //             AND fabrica  = $login_fabrica
        //             AND defeito_constatado IS NOT NULL
        //             AND solucao_os IS NOT NULL";
        //     $resD = @pg_query($con,$sqlD);
        //     $msg_erro = pg_errormessage($con);
        //     if (pg_num_rows($resD)==0) {
        //         $msg_erro = traduz("por.favor.verifique.os.dados.digitados.defeito.constatado.e.solucao.na.tela.de.lancamento.de.itens",$con,$cook_idioma);
        //     }
        // }

        if (strlen($msg_erro)==0){
            $sql = "UPDATE tbl_os SET data_conserto = CURRENT_TIMESTAMP WHERE os=$os";
            $res = @pg_query($con,$sql);
            $msg_erro = pg_errormessage($con);
        }

        if (strlen($msg_erro)==0){
            echo "ok|ok";
        } else {
            echo "erro|$msg_erro";
        }
        exit;
    }

    # ---- fechar ---- #
    $os = $_GET['fechar'];
    if (strlen ($os) > 0) {
        $msg_erro = "";
        $res = pg_query ($con,"BEGIN TRANSACTION");
        if ($login_fabrica == 3) {
            $sql = "SELECT tbl_os_item.os_item , tbl_os_extra.obs_fechamento
                    FROM tbl_os_produto
                    JOIN tbl_os_item           ON tbl_os_produto.os_produto     = tbl_os_item.os_produto
                    JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
                    JOIN tbl_os_extra          ON tbl_os_produto.os             = tbl_os_extra.os
                    LEFT JOIN tbl_faturamento_item on tbl_os_item.peca = tbl_faturamento_item.peca and tbl_os_item.pedido = tbl_faturamento_item.pedido
                    WHERE tbl_os_produto.os = $os
                    AND tbl_servico_realizado.gera_pedido IS TRUE
                    AND tbl_faturamento_item.faturamento_item IS NULL
                    LIMIT 1";
            $res = @pg_query($con,$sql);
            if (pg_num_rows($res)>0) {
                $os_item = trim(pg_fetch_result($res,0,os_item));
                $obs_fechamento = trim(pg_fetch_result($res,0,obs_fechamento));
                if (strlen($os_item)>0 and strlen($obs_fechamento)==0) {
                    $msg_erro .= traduz("os.com.pecas.pendentes,.favor.informar.o.motivo.do.fechamento",$con,$cook_idioma);
                }
            }

            $sql = "SELECT tbl_os.os FROM tbl_os WHERE tbl_os.os = $os AND tbl_os.defeito_constatado IS NULL";
            $res = pg_query ($con,$sql);
            if (pg_num_rows ($res) > 0) {
                $sql = "UPDATE tbl_os SET defeito_constatado = 0 WHERE tbl_os.os = $os";
                $res = pg_query ($con,$sql);
            }

            $sql = "SELECT tbl_os.os FROM tbl_os WHERE tbl_os.os = $os AND tbl_os.solucao_os IS NULL";
            $res = pg_query ($con,$sql);
            if (pg_num_rows ($res) > 0) {
                $sql = "UPDATE tbl_os SET solucao_os = 0 WHERE tbl_os.os = $os";
                $res = pg_query ($con,$sql);
            }

            $sql = "SELECT tbl_os.os FROM tbl_os JOIN tbl_os_produto USING (os) JOIN tbl_os_item USING (os_produto) WHERE tbl_os.os = $os AND tbl_os_item.peca_serie_trocada IS NULL";
            $res = pg_query ($con,$sql);
            if (pg_num_rows ($res) > 0) {
                $sql = "UPDATE tbl_os_item SET peca_serie_trocada = '0000000000000' FROM tbl_os_produto JOIN tbl_os USING (os) WHERE tbl_os_item.os_produto = tbl_os_produto.os_produto AND tbl_os.os = $os";
                $res = pg_query ($con,$sql);
            }
        }

        $sql = "SELECT status_os
                FROM tbl_os_status
                WHERE os = $os
                AND status_os IN (62,64,65,72,73,87,88,116,117)
                ORDER BY data DESC
                LIMIT 1";
        $res = pg_query ($con,$sql);
        if (pg_num_rows($res)>0){
            $status_os = trim(pg_fetch_result($res,0,status_os));
            if ($status_os=="72" || $status_os=="62" || $status_os=="87" || $status_os=="116"){
                // if ($login_fabrica ==51) { // HD 59408
                //     $sql = " INSERT INTO tbl_os_status
                //             (os,status_os,data,observacao)
                //             VALUES ($os,64,current_timestamp,'OS Fechada pelo posto')";
                //     $res = pg_query($con,$sql);
                //     $msg_erro .= pg_errormessage($con);

                //     $sql = "UPDATE tbl_os_item SET servico_realizado = 671 FROM tbl_os_produto
                //             WHERE tbl_os_produto.os_produto = tbl_os_item.os_produto
                //             AND   tbl_os_produto.os = $os";
                //     $res = pg_query($con,$sql);
                //     $msg_erro .= pg_errormessage($con);

                //     $sql = "UPDATE tbl_os SET defeito_constatado = 10536,solucao_os = 491
                //             WHERE tbl_os.os = $os";
                //     $res = pg_query($con,$sql);
                //     $msg_erro .= pg_errormessage($con);
                // } else {
                    $msg_erro .= traduz("os.com.intervencao,.nao.pode.ser.fechada.",$con,$cook_idioma);
                //}
            }
        }

        $sql = "UPDATE tbl_os SET data_fechamento = CURRENT_TIMESTAMP WHERE os = $os AND fabrica = $login_fabrica";
        $res = pg_query ($con,$sql);
        $msg_erro .= pg_errormessage($con) ;

        // if (strlen ($msg_erro) == 0 AND $login_fabrica == 1) {
        //     $sql = "SELECT fn_valida_os_item($os, $login_fabrica)";
        //     $res = @pg_query ($con,$sql);
        //     $msg_erro = pg_errormessage($con);
        // }

        if (strlen ($msg_erro) == 0) {
            $sql = "SELECT fn_finaliza_os($os, $login_fabrica)";
            $res = pg_query ($con,$sql);
            $msg_erro = pg_errormessage($con) ;
            if (strlen ($msg_erro) == 0 and ($login_fabrica==1 or $login_fabrica==24)) {
                $sql = "SELECT fn_estoque_os($os, $login_fabrica)";
                $res = @pg_query ($con,$sql);
                $msg_erro = pg_errormessage($con);
            }
        }
        // if (strlen ($msg_erro) == 0 and $login_fabrica==24) { //HD 3426
        //     $sql = "SELECT fn_estoque_os($os, $login_fabrica)";
        //     $res = @pg_query ($con,$sql);
        // }
            //HD 11082 17347

        // if (strlen($msg_erro) ==0 and $login_fabrica==11 and $login_posto==14301) {
        //     $sqlm="SELECT tbl_os.sua_os          ,
        //                  tbl_os.consumidor_email,
        //                  tbl_os.serie           ,
        //                  tbl_posto.nome         ,
        //                  tbl_produto.descricao  ,
        //                  to_char(tbl_os.data_fechamento,'DD/MM/YYYY') as data_fechamento
        //             from tbl_os
        //             join tbl_produto using(produto)
        //             join tbl_posto on tbl_os.posto = tbl_posto.posto
        //             where os=$os";
        //     $resm=pg_query($con,$sqlm);
        //     $msg_erro .= pg_errormessage($con) ;

        //     $sua_osm           = trim(pg_fetch_result($resm,0,sua_os));
        //     $consumidor_emailm = trim(pg_fetch_result($resm,0,consumidor_email));
        //     $seriem            = trim(pg_fetch_result($resm,0,serie));
        //     $data_fechamentom  = trim(pg_fetch_result($resm,0,data_fechamento));
        //     $nomem             = trim(pg_fetch_result($resm,0,nome));
        //     $descricaom        = trim(pg_fetch_result($resm,0,descricao));

        //     if (strlen($consumidor_emailm) > 0) {

        //         $nome         = "TELECONTROL";
        //         $email_from   = "helpdesk@telecontrol.com.br";
        //         $assunto      = traduz("ordem.de.servico.fechada",$con,$cook_idioma);
        //         $destinatario = $consumidor_emailm;
        //         $boundary = "XYZ-" . date("dmYis") . "-ZYX";

        //         $mensagem = traduz("a.ordem.de.servi√ßo.%.referente.ao.produto.%.com.n√∫mero.de.s√©rie.%.foi.fechada.pelo.posto.%.no.dia.%",$con,$cook_idioma,array($sua_osm,$descricaom,$seriem,$nomem,$data_fechamentom));


        //         $body_top = "--Message-Boundary\n";
        //         $body_top .= "Content-type: text/html; charset=iso-8859-1\n";
        //         $body_top .= "Content-transfer-encoding: 7BIT\n";
        //         $body_top .= "Content-description: Mail message body\n\n";
        //         @mail($destinatario, utf8_encode($assunto), utf8_encode($mensagem), "From: ".$email_from." \n $body_top ");
        //     }
        // }

        if (strlen ($msg_erro) == 0) {
            $res = pg_query ($con,"COMMIT TRANSACTION");

            //Envia e-mail para o consumidor, avisando da abertura da OS
            //HD 150972
            if (($login_fabrica == 14) || ($login_fabrica == 43) || ($login_fabrica == 66))
            {
                $novo_status_os = "FECHADA";
                include('os_email_consumidor.php');
            }

            echo "ok;XX$os";
        } else {
            $res = @pg_query ($con,"ROLLBACK TRANSACTION");
            echo "erro;$sql ==== $msg_erro ";
        }


        flush();
        exit;
    }
}

###########################################
### AVISO E BLOQUEIO DE PEDIDO FATURADO ###
### HD 306762 - Recolocado no login     ###
###########################################
// $login_bloqueio_pedido = $_COOKIE["cook_bloqueio_pedido"];

if ($telecontrol_distrib) {
    $login_bloqueio_pedido = '';

    $sql = "SELECT posto
            FROM tbl_telecontrol_inadimplencia
            WHERE posto = $login_posto
            AND recebimento IS NULL";
    $res = pg_query($con,$sql);

    $login_bloqueio_pedido = (pg_num_rows($res) > 0) ? "t" : "";

    $cookie_login = add_cookie($cookie_login,'cook_bloqueio_pedido',$login_bloqueio_pedido);
    set_cookie_login($token_cookie,$cookie_login);
    //setcookie ('cook_bloqueio_pedido',$login_bloqueio_pedido);
}

if (strlen($_COOKIE['cook_plv'])>0) {
    $pedido = $_COOKIE['cook_plv'];
    $sql = "SELECT pedido_loja_virtual FROM tbl_pedido WHERE pedido =$pedido";
    $res = pg_exec($con,$sql);
    if (pg_result($res,0,0)=='t')  header("Location: lv_carrinho.php");
    else                         header("Location: pedido_cadastro.php?pedido=$pedido");
    exit;
}


if ($login_fabrica==24) {
    $sql = "SELECT posto
            FROM tbl_pesquisa_suggar
            WHERE posto = $login_posto";
    $res = pg_exec($con,$sql);
    if (pg_numrows($res)==0) {
        include "suggar_questionario.php";
        exit;
    }
}
if ($login_fabrica==157 && $_GET["loginAcacia"] == 1) {
    header("Location: loja_new.php");
}
setcookie("CookieNavegador", "Aceita");
remove_cookie($cookie_login,'acessa_extrato');
remove_cookie($cookie_login,'acessa_tabela_preco');
set_cookie_login($token_cookie,$cookie_login);
$title = "Telecontrol ASSIST - Gerenciamento de AssistÍncia TÈcnica";
$layout_menu = 'os'; ?>

<style type="text/css">
    #redirect-comunicado {
        text-decoration: none;
        color: #FFF;
        margin: 0 auto;
        display: block;
        width: 150px;
        background-color: #596d9b;
        width: 150px;
        text-align: center;
        border-radius: 3px;
        transition: box-shadow 1s;
    }
    #redirect-comunicado:hover {
        box-shadow: 0 0 5px #666;
    }
</style>

<div class="container">
<?php
#############################################
#   PendÍncia Telecontrol                   #
#############################################

    if ($login_bloqueio_pedido == 't' and $telecontrol_distrib =='t' and empty($comunicado_lido)) { ?>
        <div class="row-fluid">
            <div class="span12">
                <h3 align="center">
                    AÁ„o Necess·ria
                </h3>
                <p align="center">
                    Qualquer d˙vida, gentileza entrarem  em contato.
                </p>

                <p align="center">E-mail - contabil@acaciaeletro.com.br</p>

                <p align="center">Telefone: (011) 4063-0036</p>

                <a id="redirect-comunicado" href="comunicado_inicial.php">
                    <p style="padding:5px">Acessar sistema</p>
                </a>
            </div>
        </div>
    <?}

############# +++INICIO+++ AVISO DE OS COM INTERVENCAO DA FABRICA - fabio 24/01/2007


if ($login_bloqueio_pedido=='t' and !isset($_COOKIE['cook_bloqueio_pedido']) and empty($comunicado_lido)) exit('</body></html>');

#############  +++ FIM+++ AVISO DE OS COM INTERVENCAO DA FABRICA

if ($login_fabrica == 1) {

    $sqlPostoPesquisa = "SELECT categoria 
                           FROM tbl_posto_fabrica 
                          WHERE fabrica = {$login_fabrica} 
                            AND posto = {$login_posto};";
    $resPostoPesquisa = pg_query($con, $sqlPostoPesquisa);

    $categoriaPostoPesquisa = pg_fetch_result($resPostoPesquisa, 0, categoria);

    if (in_array($categoriaPostoPesquisa, array('Autorizada', 'Locadora Autorizada'))) {

        $pesquisa  = 117;
       
        $sqlVerificaJaRespondido  = "SELECT * FROM tbl_resposta WHERE posto={$login_posto} AND pesquisa = $pesquisa";

        $resVerificaJaRespondido  = pg_query($con, $sqlVerificaJaRespondido);
        
        if (pg_num_rows($resVerificaJaRespondido) == 0) {
            include_once 'pesquisa_posto_black_telecontrol.php';
            exit;  
        }

    }
    
    if (!isset($_GET["menu_inicial"]) && (empty($cookie_login['cook_admin']) || $_serverEnvironment == "development")) {

        $sqlPesquisaCadastral = "SELECT pesquisa
                                 FROM tbl_pesquisa
                                 WHERE fabrica = {$login_fabrica}
                                 AND categoria = 'atualizacao_cadastral'
                                 AND ativo
                                 AND (
                                    SELECT resposta FROM tbl_resposta
                                    WHERE posto = {$login_posto}
                                    AND tbl_resposta.pesquisa = tbl_pesquisa.pesquisa
                                    LIMIT 1
                                 ) IS NULL
                                 AND (
                                    SELECT categoria FROM tbl_posto_fabrica
                                    WHERE posto = {$login_posto}
                                    AND fabrica = {$login_fabrica}
                                    LIMIT 1
                                 ) IN ('Autorizada', 'Locadora Autorizada')";
        $resPesquisaCadastral = pg_query($con, $sqlPesquisaCadastral);

        if (pg_num_rows($resPesquisaCadastral) > 0) {

            $pesquisa     = pg_fetch_result($resPesquisaCadastral, 0, "pesquisa");

            require_once "formulario_telecontrol_easyfb.php";
            exit;

        }

    }
    
}

if ($usaEasyBuilder) {

    try {

        $easyBuilderMirror = new \Mirrors\EasyBuilderMirror($login_fabrica, "tela_inicial_posto", $login_admin);

        $pesquisaPendente = $easyBuilderMirror->getPesquisaPendentePosto($login_posto);

        if (!empty($pesquisaPendente)) {

            $pesquisa = $pesquisaPendente;

            require_once "formulario_telecontrol_easyfb.php";
            exit;

        }

    } catch(\Exception $e){
        $erroEasyfb = $e->getMessage();
    }

}

if (!$xxpesquisaSatisfacao) {

    include_once 'comunicado_inicial.php';
}

if (1==1) { 
?>
<script language='javascript'>
    <?php if (!$xxpesquisaSatisfacao) { ?>
        window.location = 'menu_inicial.php';
    <?php } ?>
</script>
</body>
<?
exit;
}

// include 'cabecalho_login.php';

?>

<!-- <hr>
<div class="row-fluid">
    <div class="span12"> -->
        <!-- <h3><? //echo 'Prezado '.$login_nome ?></h3> -->
<!--     </div>
</div> -->
<?
    // echo "<table class='table table-bordered table-hover table-fixed''>";
    // echo "<tr>";
    // echo "<td align='center'>";

//alterado por takashi 05/07/2006 segundo chamado 133
//insere qtdade de dias para descredenciamento
// if (trim($login_credenciamento) == "EM DESCREDENCIAMENTO"){
//     $sql = "SELECT  tbl_credenciamento.status,
//                     tbl_credenciamento.dias  ,
//                     tbl_credenciamento.texto ,
//                     to_char(tbl_credenciamento.data,'YYYY-MM-DD') AS data,
//                     tbl_posto.nome
//             FROM    tbl_credenciamento
//             JOIN    tbl_posto ON tbl_posto.posto = tbl_credenciamento.posto
//             WHERE   tbl_credenciamento.fabrica = $login_fabrica
//             AND     tbl_credenciamento.posto   = $login_posto
//             ORDER BY tbl_credenciamento.credenciamento DESC LIMIT 1";
//     $res = pg_exec($con,$sql);

//     if (pg_numrows($res) > 0){

//         $status       = pg_fetch_result($res, 0, 'status');
//         $xdias        = pg_fetch_result($res, 0, 'dias');
//         $data_geracao = pg_fetch_result($res, 0, 'data');
//         $xtexto       = pg_fetch_result($res, 0, 'texto');
//         $posto_nome   = pg_fetch_result($res, 0, 'nome');

//         if ($status == 'EM CREDENCIAMENTO' OR $status == 'EM DESCREDENCIAMENTO'){

//             $sqlX = "SELECT '$data_geracao':: date + interval '$xdias days';";
//             $resX = pg_exec ($con,$sqlX);
//             $dt_expira = pg_result ($resX,0,0);

//             $sqlX = "SELECT '$dt_expira'::date - current_date;";
//             $resX = pg_exec ($con,$sqlX);

//             $dt_expira = substr ($dt_expira,8,2) . "-" . substr ($dt_expira,5,2) . "-" . substr ($dt_expira,0,4);
//             $dia_hoje= pg_result ($resX,0,0);

// 			$msg_erro[] = strtoupper(traduz('ate.o.dia.%.restam.%dias', $con, $cook_idioma, array($dt_expira, $dia_hoje)));
//         }
//     }
// }

// $sql = "SELECT estado FROM tbl_posto WHERE posto = $login_posto";
// $res = pg_exec($con,$sql);
// $estado = trim(pg_fetch_result($res, 0, 'estado'));

// echo "<table border='1'>";
// echo "<tr>";
// if ($login_fabrica == 3){
//     if ($estado == 'RS' OR $estado == 'SC' OR $estado == 'PR') {
//         echo "<td bgcolor='#FF0000'>";
//         echo "<b><font size='-1'>".traduz("pagamento.direto.de.mao.de.obra",$con,$cook_idioma)."</font></b>";
//         echo "<p>";
//         echo "<font size='-1'>".traduz("o.pagamento.da.mao.de.obra",$con,$cook_idioma)."<br> ".traduz("sera.feito.diretamente.pela.britania",$con,$cook_idioma)." <BR> <A HREF='/assist/comunicados/britania-mobra-direta.html' target='_blank'><font color='#660000'>".traduz("clique.aqui.para.saber.mais",$con,$cook_idioma)."</font></a></font>";
//         echo "</td>";
//     }

//     if ($estado == 'SP' OR $estado == 'MA' OR $estado == 'PA' OR $estado == 'AC' OR $estado == 'AM' OR $estado == 'TO') {
//         echo "<td bgcolor='#FF0000'>";
//         echo "<b><font size='-1'>".traduz("pagamento.direto.de.mao.de.obra",$con,$cook_idioma)."</font></b>";
//         echo "<p>";
//         echo "<font size='-1'>".traduz("o.pagamento.da.mao.de.obra",$con,$cook_idioma)."<br> ".traduz("sera.feito.diretamente.pela.britania",$con,$cook_idioma)." <BR> <A HREF='/assist/comunicados/britania-mobra-direta-eletro.html' target='_blank'><font color='#660000'>".traduz("clique.aqui.para.saber.mais",$con,$cook_idioma)."</font></a></font>";
//         echo "</td>";
//     }
// }
// echo "<td>";
// #------------------------ M√©dia de Pe√ßas por OS   e  Custo M√©dio por OS --------------
// include "custo_medio_include.php";
// echo "</td>";

// echo "</tr>";
// echo "</table>";

?>

<!-- </div>
<div id="container"><h2><IMG SRC="imagens/bemVindo<? //echo $login_fabrica_nome ?>.gif" ALT="Bem-Vindo!!!"></h2></div> -->
<?


#-------------- Valida√ß√£o Peri√≥dica de EMAIL -------------------
// $sql = "SELECT tbl_posto.email, nome, tbl_posto.email_validado
//         FROM tbl_posto
//         WHERE tbl_posto.posto =  $login_posto
//         AND (email_enviado  IS NULL OR email_enviado  < CURRENT_DATE - INTERVAL '1 days' )
//         AND (email_validado IS NULL OR email_validado < CURRENT_DATE - INTERVAL '30 days')";
// $res = pg_query($con, $sql);
// if (pg_num_rows($res) > 0 AND strlen($cookie_login['cook_login_unico'])==0) {
//     $nome  = pg_fetch_result($res, 0, 'nome');
//     $email = trim(pg_fetch_result($res, 0, 'email'));
?>
 <!--    <form name='frm_email' method='post' action='email_altera_envia.php' target='_blank'>
      <input type='hidden' name='btn_acao'>
      <fieldset style='border-color: 00CCFF;color:black'>
        <legend align='center' style='background-color:#3399FF;border:1px solid #036;color:white;width:90%;text-align:center'>
          <?/*=traduz("verificacao.obrigatoria.de.email",$con,$cook_idioma)*/?>
        </legend>
        <br>
        <center>
          <font color='#000000' size='2'>
            <?/*=traduz("por.favor.confirme.seu.endereco.de.email.no.campo.abaixo.e.clique.em.continuar.em.seguida.sera.enviado.um.email.para.sua.caixa.de.mensagens.vindo.de.verificacao@telecontrol.com.br.com.o.assunto.verificacao.de.email.e.dentro.dele.existe.um.link.que.voce.deve.clicar.para.efetuar.a.operacao.de.atualizacao.e.verificacao.do.email",$con,$cook_idioma)*/?>
          </font><br><br>
          <?/*=traduz(email)*/?>: <input type='text' name='email' size='50' maxlength='50' value='<?=$email?>'>
          &nbsp;&nbsp;
          <img border='0' src='imagens/btn_continuar.gif' align='absmiddle' onclick='document.frm_email.submit(); window.location.reload( true ); ' style='cursor: hand' alt='Atualiar email'>
          <br><br>
        </center>
      </fieldset>
    </form>
	<p> -->
<?php
//}

//include_once 'comunicado_inicial.php';

#----------------- P√°gina de informativos ----------------
/*  8/4/2009 MLG - C√≥digo atualizado  */
    $a_news_fabrica = Array(
        "Dynacom", "Britania",  "Meteor",   "Mondial",
        "Tectoy",  "Ibratele",  "Filizola", "Telecontrol",
        "Lenoxx",  "Intelbras", "Latina",   "BlackeDecker",
        "Bosch",   "Lorenzetti"
    );
    if (in_array(trim ($login_fabrica_nome), $a_news_fabrica)) {
        $k_news_fabrica    = array_search(trim ($login_fabrica_nome), $a_news_fabrica);
        $tela_info_fabrica = "news_".strtolower($a_news_fabrica[$k_news_fabrica]).".php";
        if (is_readable($tela_info_fabrica))
            include $tela_info_fabrica;
    }
    // echo "</td>";
    // echo "</tr>";
    // echo "</table>";
// include "rodape.php";
?>
