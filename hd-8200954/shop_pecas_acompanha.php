<?php
/**
 *  LEMBRETE DE STATUS PEDIDO
 * 1 - EM AGUARDO
 * 2 - APROVADO
 * 3 - REPROVADO
 *
 * @author William Ap. Brandino
 */

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';
include "/www/assist/www/class/communicator.class.php";
$mailer = new TcComm($externalId);

if($login_fabrica != 20){
    header("location: menu_inicial.php");
    exit;
}

$title = traduz("acompanhamento.de.compra.e.venda",$con);
$layout_menu = 'shop_pecas';

if($_POST['ajax']){
    include 'class/email/PHPMailer/class.phpmailer.php';
    require 'class/email/PHPMailer/PHPMailerAutoload.php';

    $acao   = $_POST['acao'];
    $pedido = $_POST['pedido'];

    $res = pg_query($con,"BEGIN TRANSACTION");

    $apagouVitrine = false;
    if($acao == "confirmar"){
       /**
        * - Ação para CONFIRMAR o pedido pelo posto
        *
        * @params string acao | int pedido
        * @uses Para CONFIRMAR, gravar status_pedido como 2
        */

        $sqlCon = "
            UPDATE  tbl_vitrine_pedido
            SET     status_pedido   = 2,
                    finalizado      = CURRENT_TIMESTAMP
            WHERE   vitrine_pedido  = $pedido
            AND     status_pedido   = 1
        ";
        $resCon = pg_query($con,$sqlCon);

        if(!pg_last_error($con)){
            /**
             * - Verifica se TODAS AS QUANTIDADES
             * da peça envolvida no pedido foi vendida.
             *
             * Se sim, será DELETADA a peça da vitrine
             */
            $sqlCompara = "
                SELECT  x.peca,
                        tbl_vitrine.qtde - x.qtde_venda AS total_qtde
                FROM    tbl_vitrine
                JOIN    tbl_posto               USING(posto)
                JOIN    tbl_peca                USING(peca)
                JOIN    (
                            SELECT  tbl_vitrine_pedido.posto_venda                  ,
                                    tbl_vitrine_pedido_item.peca                    ,
                                    SUM(tbl_vitrine_pedido_item.qtde) AS qtde_venda
                            FROM    tbl_vitrine_pedido_item
                            JOIN    tbl_vitrine_pedido USING (vitrine_pedido)
                            WHERE   tbl_vitrine_pedido.status_pedido = 2
                      GROUP BY      tbl_vitrine_pedido.posto_venda      ,
                                    tbl_vitrine_pedido_item.peca
                        ) x ON  x.posto_venda   = tbl_posto.posto
                            AND x.peca          = tbl_peca.peca
                            AND tbl_posto.posto = $login_posto
                JOIN    (
                            SELECT  tbl_vitrine_pedido_item.peca
                            FROM    tbl_vitrine_pedido_item
                            WHERE   tbl_vitrine_pedido_item.vitrine_pedido = $pedido
                        ) y ON y.peca           = x.peca
                WHERE   tbl_vitrine.posto   = $login_posto
                AND     tbl_peca.fabrica    = $login_fabrica
                AND     tbl_vitrine.ativo   IS TRUE
            ";

            $resCompara = pg_query($con,$sqlCompara);

            if(pg_numrows($resCompara) > 0){
                $peca_elimina   = pg_fetch_result($resCompara,0,peca);
                $qtde_pecas     = pg_fetch_result($resCompara,0,total_qtde);

                if($qtde_pecas == 0){
                    $sqlD = "
                        DELETE  FROM tbl_vitrine
                        WHERE   posto = $login_posto
                        AND     peca  = $peca_elimina
                    ";
                    $resD = pg_query($con,$sqlD);
                    $apagouVitrine = true;
                }
            }
        }
    }else if($acao == "cancelar"){
       /**
        * - Ação para CANCELAR o pedido pelo posto
        *
        * @params string acao | int pedido
        * @uses Para CANCELAR, gravar status_pedido como 3
        */

        $res = pg_query($con,"BEGIN TRANSACTION");

        $sqlCan = "
            UPDATE  tbl_vitrine_pedido
            SET     status_pedido   = 3,
                    finalizado      = CURRENT_TIMESTAMP
            WHERE   vitrine_pedido  = $pedido
            AND     status_pedido   = 1
        ";
        $resCan = pg_query($con,$sqlCan);
    }

    if(!pg_last_error($con)){
        $sqlCV = "
            SELECT  posto_vendedor.nome_fantasia    AS nome_vendedor    ,
                    posto_vendedor.contato_email    AS email_vendedor   ,
                    posto_comprador.nome_fantasia   AS nome_comprador   ,
                    posto_comprador.contato_email   AS email_comprador
            FROM    tbl_vitrine_pedido
            JOIN    tbl_posto_fabrica posto_vendedor    ON  posto_vendedor.posto    = tbl_vitrine_pedido.posto_venda
                                                        AND posto_vendedor.fabrica  = $login_fabrica
            JOIN    tbl_posto_fabrica posto_comprador   ON  posto_comprador.posto   = tbl_vitrine_pedido.posto_compra
                                                        AND posto_comprador.fabrica = $login_fabrica
            WHERE   tbl_vitrine_pedido.vitrine_pedido = $pedido
        ";

        $resCV = pg_query($con,$sqlCV);
        $nome_vendedor      = pg_fetch_result($resCV,0,nome_vendedor);
        $email_vendedor     = pg_fetch_result($resCV,0,email_vendedor);
        $nome_comprador     = pg_fetch_result($resCV,0,nome_comprador);
        $email_comprador    = pg_fetch_result($resCV,0,email_comprador);

        if(pg_numrows($resCV) > 0){
            $mailVendedor   = new PHPMailer;
            $mailComprador  = new PHPMailer;

            $subject = ($acao == "confirmar") ? "Pedido de compra $pedido CONFIRMADO" : "Pedido de compra $pedido CANCELADO" ;


            if($acao == "confirmar"){
                if($login_pais == "BR"){
                    $bodyVendedor = "
                        <p>
                            Pedido nº $pedido foi <b>CONFIRMADO</b>
                            <br />
                            <em> Obs.: Negociação de valores, devem ser tratados entre os postos</em>
                        </p>
                    ";
                    $bodyComprador = "
                        <p>
                            Pedido nº $pedido foi <b>CONFIRMADO</b>
                            <br />
                            <em> Obs.: Negociação de valores, devem ser tratados entre os postos</em>
                        </p>
                    ";
                } else {
                    $bodyVendedor = "
                        <p>
                            El pedido nº $pedido ha sido <b>CONFIRMADO</b>
                            <br />
                            <em> Obs.:  La negociación de valores queda a cargo de las autorizadas.</em>
                        </p>
                    ";
                    $bodyComprador = "
                        <p>
                            El pedido nº $pedido ha sido <b>CONFIRMADO</b>
                            <br />
                            <em> Obs.:  La negociación de valores queda a cargo de las autorizadas.</em>
                        </p>
                    ";
                }
            }else{
                if($login_pais == "BR"){
                    $bodyVendedor = "
                        <p>
                            Pedido nº $pedido foi <b>CANCELADO</b>
                            <br />
                            <em> Obs.: Poderão ser normalmente feitos outros pedidos da mesma peça, até mesmo o posto requerente.</em>
                        </p>
                    ";
                    $bodyComprador = "
                        <p>
                            Pedido nº $pedido foi <b>CANCELADO</b>
                            <br />
                            <em> Obs.: Poderá ser normalmente feito outro pedido desta peça, pelo mesmo posto, caso esteja disponível a possibilidade de venda </em>
                        </p>
                    ";
                } else {
                    $bodyVendedor = "
                        <p>
                            El pedido nº $pedido ha sido <b>CANCELADO</b>
                            <br />
                            <em> Obs.: Podrán realizarse nuevos pedidos de estos repuestos, inclusive por la Autorizada que hizo este pedido.</em>
                        </p>
                    ";
                    $bodyComprador = "
                        <p>
                            El pedido nº $pedido ha sido <b>CANCELADO</b>
                            <br />
                            <em> Obs.: Podrá realizar otro pedido de este repuesto, de la misma Autorizada, siempre que esté disponible para venta.</em>
                        </p>
                    ";
                }
            }

            $mailVendedor->isSMTP();
            $mailVendedor->From        = "no-reply@telecontrol.com.br";
            $mailVendedor->FromName    = "Telecontrol";
            $mailVendedor->addAddress($email_vendedor,$nome_vendedor);
//             $mailVendedor->addAddress('william.brandino@telecontrol.com.br',$nome_vendedor);

            $mailVendedor->isHTML(true);
            $mailVendedor->Subject  = $subject;
            $mailVendedor->Body     = $bodyVendedor;

           /**
            * - Agora, o email a enviar será para o comprador
            * @todo mudar o email
            */

            $mailComprador->isSMTP();
            $mailComprador->From        = "no-reply@telecontrol.com.br";
            $mailComprador->FromName    = "Telecontrol";
            $mailComprador->addAddress($email_comprador,$nome_comprador);
//             $mailComprador->addAddress('joao.junior@telecontrol.com.br',$nome_vendedor);

            $mailComprador->isHTML(true);
            $mailComprador->Subject  = $subject;

            $mailComprador->Body     = $bodyComprador;

			if(strlen($email_vendedor) > 5 and strlen($email_comprador) > 5) {
				if(!$mailVendedor->send() || !$mailComprador->send()){
					$externalId    =  'smtp@posvenda';
			        $externalEmail =  'noreply@telecontrol.com.br';

					$res = $mailer->sendMail(
							$email_vendedor,
							$subject,
							$bodyVendedor,
							$externalEmail
					);
					$res = $mailer->sendMail(
							$email_comprador,
							$subject,
							$bodyComprador,
							$externalEmail
					);
					$res = pg_query($con,"COMMIT TRANSACTION");
					if($apagouVitrine === true){
						$resp = array("resultado"=>"ok","vitrine_apagado"=>"ok");
					}else{
						$resp = array("resultado"=>"ok","vitrine_apagado"=>"nao");
					}
					$resp = json_encode($resp);
					echo $resp;
				}else{
					$res = pg_query($con,"COMMIT TRANSACTION");
					if($apagouVitrine === true){
						$resp = array("resultado"=>"ok","vitrine_apagado"=>"ok");
					}else{
						$resp = array("resultado"=>"ok","vitrine_apagado"=>"nao");
					}
					$resp = json_encode($resp);
					echo $resp;
				}
			}else{
				$res = pg_query($con,"COMMIT TRANSACTION");
				if($apagouVitrine === true){
					$resp = array("resultado"=>"ok","vitrine_apagado"=>"ok");
				}else{
					$resp = array("resultado"=>"ok","vitrine_apagado"=>"nao");
				}
				$resp = json_encode($resp);
				echo $resp;
			}
        }
    }else{
        $res = pg_query($con,"ROLLBACK TRANSACTION");
        echo "nao";
    }
    exit;
}

/**
 * - Listagem das peças
 * VENDIDAS
 */

$sqlV = "
        SELECT  tbl_vitrine_pedido.vitrine_pedido   ,
                tbl_vitrine_pedido.status_pedido    ,
                tbl_vitrine_pedido_item.qtde        ,
                tbl_vitrine_pedido_item.preco       ,
                tbl_posto_fabrica.nome_fantasia     ,
                tbl_peca.referencia                 ,
                CASE WHEN '$login_pais' = 'BR'
                     THEN tbl_peca.descricao
                     ELSE tbl_peca_idioma.descricao
                END AS descricao
        FROM    tbl_vitrine_pedido
        JOIN    tbl_vitrine_pedido_item ON  tbl_vitrine_pedido_item.vitrine_pedido  = tbl_vitrine_pedido.vitrine_pedido
                                        AND tbl_vitrine_pedido.posto_venda          = $login_posto
        JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto                 = tbl_vitrine_pedido.posto_compra
                                        AND tbl_posto_fabrica.fabrica               = $login_fabrica
        JOIN    tbl_peca                ON  tbl_peca.peca                           = tbl_vitrine_pedido_item.peca
   LEFT JOIN    tbl_peca_idioma         ON  tbl_peca_idioma.peca                    = tbl_peca.peca
                                        AND tbl_peca_idioma.idioma                  = 'ES'
";
$resV = pg_query($con,$sqlV);

/**
 * - Listagem das peças
 * COMPRADAS
 */

$sqlC = "
        SELECT  tbl_vitrine_pedido.vitrine_pedido   ,
                tbl_vitrine_pedido.status_pedido    ,
                tbl_vitrine_pedido_item.qtde        ,
                tbl_vitrine_pedido_item.preco       ,
                tbl_posto_fabrica.nome_fantasia     ,
                tbl_peca.referencia                 ,
                CASE WHEN '$login_pais' = 'BR'
                     THEN tbl_peca.descricao
                     ELSE tbl_peca_idioma.descricao
                END AS descricao
        FROM    tbl_vitrine_pedido
        JOIN    tbl_vitrine_pedido_item ON  tbl_vitrine_pedido_item.vitrine_pedido  = tbl_vitrine_pedido.vitrine_pedido
                                        AND tbl_vitrine_pedido.posto_compra         = $login_posto
        JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto                 = tbl_vitrine_pedido.posto_venda
                                        AND tbl_posto_fabrica.fabrica               = $login_fabrica
        JOIN    tbl_peca                ON  tbl_peca.peca                           = tbl_vitrine_pedido_item.peca
   LEFT JOIN    tbl_peca_idioma         ON  tbl_peca_idioma.peca                    = tbl_peca.peca
                                        AND tbl_peca_idioma.idioma                  = 'ES'
";
$resC = pg_query($con,$sqlC);

include "cabecalho.php";
?>

<script type="text/javascript" src="js/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type="text/javascript" src="js/jquery.bgiframe.js"></script>
<script type="text/javascript" src="js/jquery.blockUI_2.39.js"></script>
<script type="text/javascript" src="js/plugin_verifica_servidor.js"></script>

<script type="text/javascript">

function finalizarVenda(pedido,modo,acao){
    if(confirm("<?=traduz("deseja.continuar",$con)?>")){
        var guarda = $("#"+modo+"_"+pedido).children().clone();
        var lingua = "<?=$login_pais?>";
        var frase;
        $.ajax({
            url:"<?=$PHP_SELF?>",
            type:"POST",
            dataType:"JSON",
            data:{
                ajax:true,
                acao:acao,
                pedido:pedido
            },
            beforeSend:function(){
                var verbo;
                if(acao == "cancelar"){
                    if(lingua == "BR"){
                        frase = "Aguarde enquanto cancelamos o pedido";
                    }else{
                        frase = "Aguarde mientras cancelamos la orden";
                    }
                }else{
                    if(lingua == "BR"){
                        frase = "Aguarde enquanto confirmamos o pedido";
                    }else{
                        frase = "Aguarde mientras confirmamos la orden";
                    }
                }
                $("#"+modo+"_"+pedido).html("<td colspan='5'>"
                    +frase
                    +"</td>"
                    ).css({
                        "background-color":"#D3D3D3",
                        "font-size":"12"
                });
            }
        })
        .done(function(data){
            if(data['resultado'] == "ok"){
                if(acao == "cancelar"){
                    alert("<?=traduz("pedido.cancelado.com.sucesso",$con)?>");
                }else{
                    if(data['vitrine_apagado'] == "nao"){
                        alert("<?=traduz("pedido.confirmado.com.sucesso",$con)?>");
                    }else{
                        alert("<?=traduz("pedido.confirmado.com.sucesso",$con)." ".traduz("pecas.na.vitrine.foram.negociadas.em.toda.sua.totalidade.e.sera.retirada.o.anuncio",$con)?>");
                    }
                }
                window.location.reload();
            }
        })
        .fail(function(){
            alert("<?=traduz("nao.foi.possivel.realizar.a.gravacao",$con)?>");
            $("#"+modo+"_"+pedido).html(guarda);
        });
    }
}
</script>

<style type="text/css">
a.lnk{
    color:#247BF0;
    font-weight:bold;
}

a.lnk:hover{
    cursor:pointer;
}
.menu_top {
    text-align: center;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 9px;
    font-weight: bold;
    border: 1px solid;
    color:#596d9b;
    background-color: #d9e2ef
}

.border {
    border: 1px solid #ced7e7;
}

.mensagem{
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 18px;
    font-weight: bold;
    color:#FF0000;
}

.mensagem#msg_ajax{
    color:#0F0;
    display:none;
}

#compras td{
    font-size:14px;
}
#vendas td{
    font-size:14px;
}

th{
    background-color:#CCC;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 14px;
}
</style>

<!-- INICIO TABELA MINHAS VENDAS -->
<table id="vendas" border="0" cellpadding="0" cellspacing="0" style=" text-align:center; background-color:#FFF;width:750px;">
    <thead>
        <tr>
            <th colspan="6"><?=strtoupper(traduz("minhas.vendas",$con))?></th>
        </tr>
        <tr>
            <th>Pedido</th>
            <th><?=traduz("descricao",$con)?></th>
            <th><?=traduz("posto",$con)?></th>
            <th><?=traduz("qtde",$con)?></th>
            <th width="120">Valor</th>
            <th>&nbsp;</th>
        </tr>
    </thead>
    <tbody>
<?
if(pg_numrows($resV) > 0){
    for($v=0;$v<pg_numrows($resV);$v++){
        $pedido_venda           = pg_fetch_result($resV,$v,vitrine_pedido);
        $peca_referencia_venda  = pg_fetch_result($resV,$v,referencia);
        $peca_descricao_venda   = pg_fetch_result($resV,$v,descricao);
        $posto_venda            = pg_fetch_result($resV,$v,nome_fantasia);
        $qtde_venda             = pg_fetch_result($resV,$v,qtde);
        $status_venda           = pg_fetch_result($resV,$v,status_pedido);
        $preco_venda            = number_format(pg_fetch_result($resV,$v,preco), 2, '.', '');

        $total_qtde += $qtde_venda;
        $total_valor += $preco_venda;

        $corV = ($v % 2 == 0) ? "background-color: #FFF" : "background-color: #FFC";
?>
        <tr id="venda_<?=$pedido_venda?>" style="<?=$corV?>">
            <td><?=$pedido_venda?></td>
            <td><?=$peca_referencia_venda." - ".$peca_descricao_venda?></td>
            <td><?=$posto_venda?></td>
            <td><?=$qtde_venda?></td>
            <td><?=$preco_venda?></td>
            <td>
<?
        if($status_venda == 1){
?>
                <a class="lnk" onclick="javascript:finalizarVenda(<?=$pedido_venda?>,'venda','confirmar');">Confirmar</a>
                &nbsp;
                <a class="lnk" onclick="javascript:finalizarVenda(<?=$pedido_venda?>,'venda','cancelar');">Cancelar</a>
<?
        }else{
            if($status_venda == 2){
                echo "CONFIRMADO";
            }else{
                echo "CANCELADO";
            }
        }
?>
            </td>
        </tr>
<?
    }
}else{
?>
        <tr>
            <td colspan="5"><?=traduz("nenhuma.venda.realizada",$con)?></td>
        </tr>
<?
}
?>
        <? if(pg_numrows($resV) > 0){ ?>
        <tr>
            <th colspan="3"></th>
            <th><?=$total_qtde?></th>
            <th><?=number_format($total_valor, 2, '.', '')?></th>
            <th></th>
        </tr>
        <?} ?>
    </tbody>
</table>
<!-- FIM TABELA MINHAS VENDAS -->

<br />

<!-- INICIO TABELA MINHAS COMPRAS -->
<table id="compras" border="0" cellpadding="0" cellspacing="0" style=" text-align:center; background-color:#FFF;width:750px;">
    <thead>
        <tr>
            <th colspan="6"><?=strtoupper(traduz("minhas.compras",$con))?></th>
        </tr>
        <tr>
            <th>Pedido</th>
            <th><?=traduz("descricao",$con)?></th>
            <th><?=traduz("posto",$con)?></th>
            <th><?=traduz("qtde",$con)?></th>
            <th width="120">Valor</th>
            <th>&nbsp;</th>
        </tr>
    </thead>
    <tbody>
<?
if(pg_numrows($resC) > 0){
    for($c=0;$c<pg_numrows($resC);$c++){
        $pedido_compra           = pg_fetch_result($resC,$c,vitrine_pedido);
        $peca_referencia_compra  = pg_fetch_result($resC,$c,referencia);
        $peca_descricao_compra   = pg_fetch_result($resC,$c,descricao);
        $posto_compra            = pg_fetch_result($resC,$c,nome_fantasia);
        $qtde_compra             = pg_fetch_result($resC,$c,qtde);
        $status_compra           = pg_fetch_result($resC,$c,status_pedido);
        $preco_compra            = number_format(pg_fetch_result($resC,$c,preco), 2, '.', '');


        $total_qtde += $qtde_compra;
        $total_valor += $preco_compra;
        $corC = ($c % 2 == 0) ? "background-color: #FFF" : "background-color: #FFC";
?>
        <tr id="compra_<?=$pedido_compra?>" style="<?=$corC?>">
            <td><?=$pedido_compra?></td>
            <td><?=$peca_referencia_compra." - ".$peca_descricao_compra?></td>
            <td><?=$posto_compra?></td>
            <td><?=$qtde_compra?></td>
            <td><?=$preco_compra?></td>
            <td>
<?
        if($status_compra == 1){
?>
                <a href="#" onclick="javascript:finalizarVenda(<?=$pedido_compra?>,'compra','cancelar');">Cancelar</a>
<?
        }else{
            if($status_compra == 2){
                echo "CONFIRMADO";
            }else{
                echo "CANCELADO";
            }
        }
?>
            </td>
        </tr>
        
<?
    }
}else{
?>
        <tr>
            <td colspan="5"><?=traduz("nenhuma.compra.realizada",$con)?></td>
        </tr>
<?
}
?>
        <? if(pg_numrows($resC) > 0){ ?>
        <tr>
            <th colspan="3"></th>
            <th><?=$total_qtde?></th>
            <th><?=number_format($total_valor, 2, '.', '')?></th>
            <th></th>
        </tr>
        <? } ?>
    </tbody>
</table>
<!-- FIM TABELA MINHAS COMPRAS -->

<? include "rodape.php";?>
