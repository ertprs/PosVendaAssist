<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';
include __DIR__ . DIRECTORY_SEPARATOR . "class/communicator.class.php";
$mailer = new TcComm($externalId);

$title = traduz("consulta.de.pecas.para.compra",$con);
$layout_menu = 'shop_pecas';

if($login_fabrica != 20){
    header("location: menu_inicial.php");
    exit;
}

if($_POST['ajax']){
    include 'class/email/PHPMailer/class.phpmailer.php';
    require 'class/email/PHPMailer/PHPMailerAutoload.php';

    $vitrine        = $_POST['vitrine'];
    $qtde_pedido    = $_POST['qtde_pedido'];
    $posto_compra   = $_POST['posto_compra'];

   /**
    * - Primeiro, seleciona o vendedor e a peça a
    * ser vendida ao comprador
    */

    $sqlP = "
        SELECT  tbl_vitrine.posto                                   ,
                tbl_vitrine.peca                                    ,
                tbl_posto_fabrica.contato_email AS email_vendedor   ,
                tbl_posto_fabrica.nome_fantasia AS nome_vendedor    ,
                tbl_peca.referencia                                 ,
                tbl_peca.descricao
        FROM    tbl_vitrine
        JOIN    tbl_posto_fabrica   ON  tbl_posto_fabrica.posto     = tbl_vitrine.posto
                                    AND tbl_posto_fabrica.fabrica   = $login_fabrica
        JOIN    tbl_posto           ON  tbl_posto.posto             = tbl_posto_fabrica.posto
                                    AND tbl_posto.pais = '$login_pais'
        JOIN    tbl_peca            USING(peca)
        WHERE   tbl_vitrine.vitrine         = $vitrine
        ;
    ";
    $resP = pg_query($con,$sqlP);
    $posto_venda        = pg_fetch_result($resP,0,posto);
    $email_vendedor     = pg_fetch_result($resP,0,email_vendedor);
    $nome_vendedor      = pg_fetch_result($resP,0,nome_vendedor);
    $peca_compra        = pg_fetch_result($resP,0,peca);
    $peca_referencia    = pg_fetch_result($resP,0,referencia);
    $peca_nome          = pg_fetch_result($resP,0,descricao);

    $res = pg_query($con,"BEGIN TRANSACTION");

   /**
    * - Depois, realiza a gravação
    * dos pedidos de peças
    * @param status_pedido
    * @example 1 = Aguardando Finalização
    * @example 2 = Finalizado
    * @example 3 = Cancelado
    * @return vitrine_pedido Para ser gravado na tbl_vitrine_pedido_item
    */

    $sqlS = "
        INSERT INTO tbl_vitrine_pedido (
            posto_venda,
            posto_compra,
            total       ,
            status_pedido
        ) VALUES (
            $posto_venda    ,
            $posto_compra   ,
            0.00            ,
            1
        )RETURNING vitrine_pedido
    ";
    $resS = pg_query($con,$sqlS);
    $vitrine_pedido = pg_fetch_result($resS,0,vitrine_pedido);

    if(!pg_last_error($con)){
       /**
        * - Por último, grava o item do pedido
        * contendo os dados da peça e quantidade
        */
        $sqlT = "
            INSERT INTO tbl_vitrine_pedido_item (
                vitrine_pedido  ,
                peca            ,
                qtde            ,
                preco
            ) VALUES (
                $vitrine_pedido ,
                $peca_compra    ,
                $qtde_pedido    ,
                0.00
            )
        ";
        $resT = pg_query($con,$sqlT);

        if(!pg_last_error($con)){
            $res = pg_query($con,"COMMIT TRANSACTION");
           /**
            * - Se tudo gravou perfeitamente, será
            * enviado email para o vendedor e o
            * comprador, informando do pedido realizado
            *
            * Primeiro, o vendedor
            * @todo mudar o email
            */

            $sqlCV = "
                SELECT  tbl_posto_fabrica.contato_email AS email_comprador,
                        tbl_posto_fabrica.nome_fantasia AS nome_comprador
                FROM    tbl_posto_fabrica
                WHERE   tbl_posto_fabrica.fabrica   = $login_fabrica
                AND     tbl_posto_fabrica.posto     = $login_posto
            ";
            $resCV = pg_query($con,$sqlCV);

            $email_comprador    = pg_fetch_result($resCV,0,email_comprador);
            $nome_comprador     = pg_fetch_result($resCV,0,nome_comprador);

            $mailVendedor   = new PHPMailer;
            $mailComprador  = new PHPMailer;

            $mailVendedor->isSMTP();
            $mailVendedor->From        = $email_comprador;
            $mailVendedor->FromName    = $nome_comprador;
            $mailVendedor->addAddress($email_vendedor,$nome_vendedor);
//             $mailVendedor->addAddress('william.brandino@telecontrol.com.br',$nome_vendedor);

            $mailVendedor->isHTML(true);
            if($login_pais == 'BR'){
                $mailVendedor->Subject  = "Pedido de compra de peças";
                $body = "
                <p>
                    Posto $nome_comprador fez um pedido de $qtde_pedido unidade(s)
                    <br />
                    da peca <b>$peca_referencia - $peca_nome</b>
                    <br />
                    Favor, acessar no sistema a área de pedidos de peças em Shop Peças e
                    <br />
                    Aceitar ou rejeitar o pedido.
                    <br />
                    <em> Obs.: Negociação de valores, devem ser tratados entre os postos</em>
                </p>
                ";
            }else{
                $mailVendedor->Subject = "Pedido de compra de repuestos";
                $body = "
                    <p>
                        Autorizada $nome_comprador hizo un pedido de $qtde_pedido unidade(s)
                        <br />
                        del repuesto <b>$peca_referencia - $peca_nome</b>
                        <br />
                        Por favor, entrar en el área de pedidos (Intercambio de Repuestos) del sistema para
                        <br />
                        Aceptar o rechazar el pedido.
                        <br />
                        <em> Obs.: La negociación de valores queda a cargo de las autorizadas.</em>
                    </p>
                ";
            }
            $mailVendedor->Body     = $body;

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
			if($login_pais == 'BR'){
				$assunto = "Pedido de compra de peças";
                $mailComprador->Subject  = $assunto; 
                $bodyC = "
                <p>
                    Seu posto fez um pedido de $qtde_pedido unidade(s)
                    <br />
                    da peca <b>$peca_referencia - $peca_nome</b>
                    <br />
                    Favor, aguardar resposta do posto vendedor a respeito do pedido
                    <br />
                    <em> Obs.: Negociação de valores, devem ser tratados entre os postos</em>
                </p>
                ";
            } else {
				$assunto = "Pedido de compra de repuestos";
                $mailComprador->Subject  = $assunto; 
                $bodyC = "
                    <p>
                        Su Autorizada ha realizado un pedido de $qtde_pedido unidade(s)
                        <br />
                        del repuesto <b>$peca_referencia - $peca_nome</b>
                        <br />
                        Por favor, aguarde la respuesta sobre el pedido de la Autorizada vendedora.
                        <br />
                        <em> Obs.: La negociación de valores queda a cargo de las autorizadas.</em>
                    </p>
                ";
            }
            $mailComprador->Body     = $bodyC;

			if(strlen($email_comprador) > 5 and strlen($email_vendedor) > 5) {
				if(!$mailVendedor->send() || !$mailComprador->send()){
					$externalId    =  'smtp@posvenda';
			        $externalEmail =  'noreply@telecontrol.com.br';

					$res = $mailer->sendMail(
							$email_vendedor,
							$assunto,
							$body,
							$externalEmail
					);
					$res = $mailer->sendMail(
							$email_comprador,
							$assunto,
							$bodyC,
							$externalEmail
					);

					$resp = array("resultado"=>"ok");
					$resp = json_encode($resp);
					echo $resp;

				}else{
					$resp = array("resultado"=>"ok");
					$resp = json_encode($resp);
					echo $resp;
				}
			}else{
				$resp = array("resultado"=>"ok");
				$resp = json_encode($resp);
				echo $resp;
			}
        }else{
            $res = pg_query($con,"ROLLBACK TRANSACTION");
            echo "Nao";
        }
    }else{
        echo pg_last_error($con);
    }
    exit;
}

/**
 * - Listagem de todas as peças
 * colocadas na vitrine
 * já com as quantidades alteradas, caso haja algum pedido pendente ou confirmado
 */

$sqlV = "
        SELECT  tbl_vitrine.vitrine ,
                tbl_vitrine.peca    ,
                tbl_vitrine.valor,
                tbl_peca.referencia ,
                CASE WHEN '$login_pais' = 'BR'
                     THEN tbl_peca.descricao
                     ELSE tbl_peca_idioma.descricao
                END AS descricao    ,
                CASE WHEN x.qtde_venda IS NOT NULL 
                     THEN tbl_vitrine.qtde - x.qtde_venda
                     ELSE tbl_vitrine.qtde
                END  AS qtde,
                tbl_vitrine.ativo   ,
                tbl_posto.nome      ,
                tbl_posto.email     ,
                tbl_posto.cidade     AS contato_cidade,
                tbl_posto.estado     AS contato_estado
        FROM    tbl_vitrine
        
        JOIN    tbl_posto               USING(posto)
        JOIN    tbl_posto_fabrica               USING(posto)
        JOIN    tbl_peca                USING(peca)
   LEFT JOIN    tbl_peca_idioma         ON  tbl_peca_idioma.peca                    = tbl_peca.peca
                                        AND tbl_peca_idioma.idioma                  = 'ES'
   LEFT JOIN    (
                    SELECT  tbl_vitrine_pedido.posto_venda                  ,
                            tbl_vitrine_pedido_item.peca                    ,
                            SUM(tbl_vitrine_pedido_item.qtde) AS qtde_venda
                    FROM    tbl_vitrine_pedido_item
                    JOIN    tbl_vitrine_pedido USING (vitrine_pedido)
                    WHERE   tbl_vitrine_pedido.status_pedido <> 3
              GROUP BY      tbl_vitrine_pedido.posto_venda      ,
                            tbl_vitrine_pedido_item.peca
                ) x ON  x.posto_venda          = tbl_posto.posto
                    AND x.peca                 = tbl_peca.peca
                    AND tbl_posto.posto        <> $login_posto
        WHERE   tbl_vitrine.posto   <> $login_posto
		AND     tbl_peca.fabrica  = $login_fabrica
		AND		tbl_posto_fabrica.fabrica = $login_fabrica
		AND		tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
        AND     tbl_vitrine.ativo   IS TRUE
        AND     tbl_posto.pais = '$login_pais'
  ORDER BY      tbl_posto.nome      ,
                tbl_peca.descricao ";

$resV = pg_query($con,$sqlV);

include "cabecalho.php";
?>

<script type="text/javascript" src="js/jquery-1.8.3.min.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type="text/javascript" src="js/jquery.bgiframe.js"></script>
<script type="text/javascript" src="js/jquery.blockUI_2.39.js"></script>
<script type="text/javascript" src="js/plugin_verifica_servidor.js"></script>
<script type="text/javascript" src="script.js"></script>

<script>
    function pesquisa(valor)
    {
        var opcao = document.getElementById('opcao').value;
        //if(valor.length > 3 ){
            url="busca_nome.php?valor="+valor+"&opcao="+opcao;
            ajax(url);
        //}
    }
</script>



<script type="text/javascript">
$(function() {

    $("#busca_peca").change(function(){
        if($(this).val().length > 0){
            $("#result tbody tr[class!=vitrine_peca_"+$(this).val()+"]").css("display","none");
            $("#result tbody tr[class=vitrine_peca_"+$(this).val()+"]").css("display","table-row");
        }else{
            $("#result tbody tr[class^=vitrine_peca_]").css("display","table-row");
        }
    });

});

function cancelarCompra(vitrine){
    $("#"+vitrine+" td").detach();
}

function fecharCompra(vitrine){
    if(confirm("<?=traduz("tem.certeza.que.deseja.fechar.o.pedido.da.peca",$con)?>")){
        var qtde        = $("#vitrine_"+vitrine).children("td[id^=qtde_]").text();
        var qtde_pedido = $("#qtde_compra_"+vitrine).val();

        qtde            = parseInt(qtde);
        qtde_pedido     = parseInt(qtde_pedido);

        if(qtde_pedido > qtde){
            alert("<?=traduz("pedido.acima.do.oferecido.pelo.posto",$con)?>");
            cancelarCompra(vitrine);
        }else{
            $.ajax({
                url:"<?=$PHP_SELF?>",
                type:"POST",
                dataType: "JSON",
                data:{
                    ajax:true,
                    vitrine:vitrine,
                    posto_compra:<?=$login_posto?>,
                    qtde_pedido:qtde_pedido
                },
                beforeSend:function(){
                    $("#"+vitrine).html("<td colspan='5'>"
                    +"<?=traduz("aguarde.enquanto.enviamos.seu.pedido",$con)?>"
                    +"</td>"
                    ).css({
                        "background-color":"#D3D3D3",
                        "font-size":"12"
                    });
                }
            })
            .done(function(data){

                if(data['resultado'] == "ok"){
					alert("<?=traduz("pedido.confirmado.com.sucesso",$con)?>");
                    window.location.reload();
                }

            })
            .fail(function(){
				alert("<?=traduz("nao.foi.possivel.realizar.a.gravacao",$con)?>");
                cancelarCompra(vitrine);
            });
        }
    }
}

function confirmarCompra(vitrine){
    var procura = $("#"+vitrine).children("td").val();
    if(procura == undefined){
        $("#"+vitrine).append("<td colspan='5'>"
        +"Qtde:"
        +"<input type='text' name='qtde_compra' id='qtde_compra_"+vitrine+"' maxlength='2' size='3' />&nbsp;&nbsp;&nbsp;"
        +"<a href='#' onclick='javascript:fecharCompra("+vitrine+");'>Confirmar</a>&nbsp;&nbsp;&nbsp;"
        +"<a href='#' onclick='javascript:cancelarCompra("+vitrine+");'>Cancelar</a>"
        +"</td>"
        ).css({
            "display":"table-row",
            "background-color":"#D3D3D3"
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

#result td{
    font-size:14px;
}

th{
    background-color:#CCC;
    font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
    font-size: 14px;
}
</style>
<!-- ## Início da tabela de compra ## -->
<table id="form" border="0" cellpadding="0" cellspacing="0" style=" text-align:center; background-color:#FFF;">
    <tr>
        <td><img height="1" width="20" src="imagens/spacer.gif"></td>
        <td>
            <font size="1" face="Geneva, Arial, Helvetica, san-serif"><?=traduz("pesquisar",$con)?></font>
            <br />
            <select name="opcao" id="opcao">
                <option value="ref"><?=traduz("referencia",$con)?></option>
                <option value="peca"><?=traduz("peca",$con)?></option>
            </select>
            <input type="text" name="peca_pesquisa"  value="" onkeyup="pesquisa(this.value)">
        </td>
        <td><img height="1" width="16" src="imagens/spacer.gif"></td>
    </tr>
</table>
<!-- ## Fim da tabela de compra ## -->
<br />
<div id="pagina">
    <!-- ## Início da tabela de peças cadastradas ## -->
    <table id="result" border="0" cellpadding="0" cellspacing="0" style=" text-align:center; background-color:#FFF;width:750px;">
        <thead>
            <tr>
                <th>Ref.</th>
                <th><?=traduz("descricao",$con)?></th>
                <th><?=traduz("posto",$con)?></th>
                <th><?=traduz("cidade",$con)."/".traduz("estado",$con)?></th>
                <th><?=traduz("qtde",$con)?></th>
                <th width="110">Valor</th>            
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody>
    <?
        if(pg_numrows($resV) > 0){
            for($i=0;$i<pg_numrows($resV);$i++){
                $peca_qtde_vitrine          = pg_fetch_result($resV,$i,qtde);

                if($peca_qtde_vitrine > 0 ){//Solicitado pelo Paulo
                    $peca_vitrine               = pg_fetch_result($resV,$i,vitrine);
                    $peca                       = pg_fetch_result($resV,$i,peca);
                    $peca_referencia_vitrine    = pg_fetch_result($resV,$i,referencia);
                    $peca_descricao_vitrine     = pg_fetch_result($resV,$i,descricao);
                    $peca_ativo_vitrine         = pg_fetch_result($resV,$i,ativo);
                    $posto_email                = pg_fetch_result($resV,$i,email);
                    $posto_nome                 = pg_fetch_result($resV,$i,nome);
                    $cidade                     = pg_fetch_result($resV,$i,contato_cidade);
                    $estado                     = pg_fetch_result($resV,$i,contato_estado);
                    $valor                      = number_format(pg_fetch_result($resV,$i,valor), 2, '.', '');
                    $qtde_venda                 = pg_fetch_result($resV,$i,qtde_venda);

                    $cidade_estado              = $cidade."/".$estado;

                    $cor = ($i % 2 == 0) ? "background-color: #FFF" : "background-color: #FFC";
        ?>
                <tr class="vitrine_peca_<?=$peca?>" id="vitrine_<?=$peca_vitrine?>" style="<?=$cor?>">
                    <td><?=$peca_referencia_vitrine?></td>
                    <td><?=$peca_descricao_vitrine?></td>
                    <td nowrap><?=$posto_nome?></td>
                    <td nowrap><?=$cidade_estado?></td>
                    <td id="qtde_<?=$peca_qtde_vitrine?>"><?=$peca_qtde_vitrine?></td>
                    <td nowrap><?=(strlen($valor) > 0) ? "$valor" : "---"?></td>
                    <td style="margin-left:10px;">
                        <a class="lnk" onclick="javascript:confirmarCompra(<?=$peca_vitrine?>)" >comprar</a>
                    </td>
                </tr>
                <tr id="<?=$peca_vitrine?>" style="display:none">
                </tr>
    <?
                }
            }
        }else{
    ?>
            <tr>
                <td colspan="4"><h6><?=traduz("nenhuma.peca.colocada.na.vitrine",$con)?></h6></td>
            </tr>
    <?
        }
    ?>
        </tbody>
    </table>
</div>
<!-- ## Fim da tabela de peças cadastradas ## -->
<? include "rodape.php";?>
