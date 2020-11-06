<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "call_center";
include "autentica_admin.php";

include "../class/email/PHPMailer/PHPMailerAutoload.php";

$msg_erro = array();
$msg = "";

if(isset($_POST["acao"])){

    $posto 		= $_POST["posto"];
    $nf_pecas   = $_POST["nf_pecas"];
    $os   		= $_POST["os"];

    pg_query($con, "BEGIN TRANSACTION");

    foreach ($nf_pecas as $key => $value) {

        $os_item                   = $value["os_item"];
        $nota_fiscal               = $value["nota_fiscal"];
        $data_nota_fiscal          = $value["data_nota_fiscal"];
        $pedido                    = $value["pedido"];
        $peca                      = $value["peca"];
        $referencia_descricao_peca = $value["referencia_descricao_peca"];

        if(strlen($nota_fiscal) == 0 AND strlen($data_nota_fiscal) == 0){
//             $msg_erro["msg"][] = "Preencha os dados";
            continue;
        }

        if(strlen($nota_fiscal) == 0 AND strlen($data_nota_fiscal) > 0){
            $msg_erro["msg"][] = "Preencha a Nota Fiscal para a peça {$referencia_descricao_peca}";
        }

        if(strlen($data_nota_fiscal) == 0 AND strlen($nota_fiscal) > 0){
            $msg_erro["msg"][] = "Preencha a Data da Nota Fiscal para a peça {$referencia_descricao_peca}";
        }else{

            list($dia, $mes, $ano) = explode("/", $data_nota_fiscal);

            if(!checkdate($mes, $dia, $ano)){
                $msg_erro["msg"][] = "A Data da Nota Fiscal {$data_nota_fiscal} é inválida para a peça {$referencia_descricao_peca}";
            }else{
                $data_nf = $ano."-".$mes."-".$dia;
            }

        }

        if(count($msg_erro) == 0){

            $sql = "INSERT INTO tbl_os_item_nf (
                        os_item,
                        qtde_nf,
                        nota_fiscal,
                        data_nf
                    ) VALUES (
                        {$os_item},
                        1,
                        '{$nota_fiscal}',
                        '{$data_nf}'
					) ; ";
			$sql .= " select fn_atualiza_pedido_item(peca, pedido, tbl_pedido_item.pedido_item, 1) from tbl_os_item join tbl_pedido_item using(pedido, peca)  where os_item = $os_item and tbl_pedido_item.qtde > tbl_pedido_item.qtde_faturada;  select fn_atualiza_status_pedido($login_fabrica, pedido) from tbl_os_item where os_item = $os_item ;"; 
            $sql .= "UPDATE tbl_os SET status_checkpoint=fn_os_status_checkpoint_os(tbl_os.os) FROM tbl_os_produto JOIN tbl_os_item USING(os_produto) WHERE os_item = $os_item AND tbl_os_produto.os = tbl_os.os" ;
            $res = pg_query($con, $sql);

            if(strlen(pg_last_error()) > 0){
                $msg_erro["msg"][] = "Não foi possível faturar a peça {$referencia_descricao_peca}";
            }

            // HD-6927134
            $sql_usou_estoque = "  SELECT tbl_os.os, tbl_os.posto, tbl_pedido_item.qtde_faturada
                                   FROM tbl_os_item
                                   JOIN tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                                   JOIN tbl_os ON tbl_os_produto.os = tbl_os.os
                                   JOIN tbl_pedido_item ON tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
                                   WHERE tbl_os.fabrica = {$login_fabrica}
                                   AND tbl_os.conferido_saida IS TRUE 
                                   AND tbl_os_item.peca_reposicao_estoque IS TRUE 
                                   AND tbl_os_item.os_item = {$os_item}
                                   AND tbl_os_item.peca = {$peca}";
            $res_usou_estoque = pg_query($con, $sql_usou_estoque);
            if (pg_num_rows($res_usou_estoque) > 0) {
                $posto_id      = pg_fetch_result($res_usou_estoque, 0, 'posto');
                $os_id         = pg_fetch_result($res_usou_estoque, 0, 'os');
                $qtde_faturada = pg_fetch_result($res_usou_estoque, 0, 'qtde_faturada');

                $sql_3 = "INSERT INTO tbl_estoque_posto_movimento (fabrica, 
                                                                    posto, 
                                                                    os, 
                                                                    peca, 
                                                                    data, 
                                                                    qtde_entrada
                                                                ) VALUES (
                                                                    $login_fabrica, 
                                                                    $posto_id, 
                                                                    $os_id, 
                                                                    $peca,
                                                                    now(),
                                                                    $qtde_faturada
                                                                )";
                $res_3 = pg_query($con, $sql_3);
                
                $sql_4 = "UPDATE tbl_estoque_posto SET qtde = qtde + $qtde_faturada WHERE peca = $peca AND posto = $posto_id AND fabrica = $login_fabrica";
                $res_4 = pg_query($con, $sql_4);
            }
        }

    }

    if(count($msg_erro) > 0){
        pg_query($con, "ROLLBACK TRANSACTION");
    }else{
        pg_query($con, "COMMIT TRANSACTION");
        $msg = "Pedido Faturado com Sucesso!";



        if ($login_fabrica == 1) {

            $sql = "SELECT tbl_os.sua_os,
                           tbl_posto_fabrica.codigo_posto,
                           tbl_admin.email
                    FROM tbl_os_troca
                    JOIN tbl_os ON tbl_os.os = tbl_os_troca.os
                    LEFT JOIN tbl_admin ON tbl_os_troca.admin = tbl_admin.admin 
                    OR tbl_os.admin = tbl_admin.admin AND tbl_admin.fabrica = $login_fabrica 
                    JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
                    WHERE tbl_os.os = $os
                    AND tbl_admin.fale_conosco IS TRUE
                    AND tbl_os.fabrica = $login_fabrica";

            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $sua_os       = pg_fetch_result($res, 0, 'sua_os');
                $codigo_posto = pg_fetch_result($res, 0, 'codigo_posto');
                $email_admin  = pg_fetch_result($res, 0, 'email');
       

                foreach ($nf_pecas as $key => $value) {

                    $nota_fiscal               = $value["nota_fiscal"];
                    $data_nota_fiscal          = $value["data_nota_fiscal"];

                    $msg_email_troca_msg = "A informação da NF {$nota_fiscal} data de emissão {$data_nota_fiscal} foi carregada na ordem de serviço ".$codigo_posto.$sua_os.".";

                    $msg_email_troca_subject = "NF da OS ".$codigo_posto.$sua_os.".";

                    $mailer_troca = new PHPMailer();
                    //$mailer->IsSMTP();
                    $mailer_troca->IsHTML();
                    $mailer_troca->AddReplyTo("suporte@telecontrol.com.br", "Suporte Telecontrol");

                    $mailer_troca->AddAddress($email_admin);

                    $mailer_troca->Subject = $msg_email_troca_subject;

                    $mensagem = $msg_email_troca_msg;

                    $mailer_troca->Body = $mensagem;
                    $mailer_troca->Send();
                }
            }
        }
    }

}else{
    $os = trim($_GET["os"]);
}

$layout_menu = "cadastro";

$title = "FATURAR PEDIDO DA OS";

include "cabecalho_new.php";

$plugins = array(
    "datepicker",
    "alphanumeric",
    "shadowbox",
    "mask"
);

include("plugin_loader.php");

if(strlen($os) > 0){

    $sql = "
        SELECT  tbl_posto.nome,
                tbl_posto_fabrica.codigo_posto,
                tbl_posto.posto,
                tbl_os.sua_os
        FROM    tbl_os
        JOIN    tbl_posto ON tbl_posto.posto = tbl_os.posto
        JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = {$login_fabrica}
        WHERE   tbl_os.os = {$os}
        AND     tbl_os.fabrica = {$login_fabrica}";
    $res = pg_query($con, $sql);

    $nome   = pg_fetch_result($res, 0, "nome");
    $codigo = pg_fetch_result($res, 0, "codigo_posto");
    $posto  = pg_fetch_result($res, 0, "posto");
    $sua_os = pg_fetch_result($res, 0, "sua_os");

}

?>

<script type="text/javascript">

$(function(){

    Shadowbox.init();

    $("button[id^=cancelar_]").click(function(e){
        e.preventDefault();
        var aux         = $(this).attr("id");
        var aux2        = aux.split("_");
        var pedido_item = aux2[1];
        var os_item     = aux2[2];
        var qtde        = aux2[3];

        Shadowbox.open({
            content:"cancelar_item_pedido.php?pedido_item="+pedido_item+"&os_item="+os_item+"&qtde="+qtde,
            player:"iframe",
            title:"Cancelar Item de Pedido",
            width:  800,
            height: 500
        });

    });
	$.datepickerLoad(Array(".data"));

	$(".data").mask("99/99/9999");
	$(".numeric").numeric();

});

</script>

<?php
if ((count($msg_erro["msg"]) > 0) ) {
?>
    <div class="alert alert-error">
        <h4><?=implode("<br />", $msg_erro["msg"])?></h4>
    </div>
<?php
}

if(strlen($msg) > 0){
	echo "<div class='alert alert-success'><h4>{$msg}</h4></div>";
}

?>

<div class="row">
	<b class="obrigatorio pull-right">  * Campos obrigatórios </b>
</div>

<form name="frm_credenciamento" method="POST" class="" action="<? echo $PHP_SELF; ?> ">

	<div class="tc_formulario">

		<div class="titulo_tabela">Informações da OS</div>

		<br />

		<input type="hidden" name="posto" id="posto" value="<? echo $posto?>">
		<input type="hidden" name="os" id="posto" value="<? echo $os?>">

		<div class="row-fluid">
			<div class="span1"></div>

			<div class="span10">
				Posto: <strong><?php echo $codigo; ?> - <?php echo $nome; ?></strong> <br />
				OS: <a href="os_press.php?os=<?php echo $os; ?>" target="_blank"><strong><?php echo $codigo . $sua_os; ?></strong></a>
			</div>

		</div>

	</div>

	<table class="table table-bordered table-large">

		<thead>
            <tr class='titulo_tabela'>
                <th colspan="8">Relação de Peças x Pedido</th>
            </tr>
            <tr class='titulo_coluna' >
                <th>Peça</th>
                <th>Pedido</th>
                <th>Nota Fiscal</th>
                <th>Data da Nota Fiscal</th>
                <th>Ações</th>
            </tr>
        </thead>

        <tbody>
<?php

$sql = "
            SELECT  tbl_os_item.pedido,
                    tbl_os_item.os_item,
                    tbl_pedido.seu_pedido,
                    tbl_pedido_item.pedido_item,
                    tbl_os_item.qtde  AS qtde,
                    tbl_os_item.peca,
                    tbl_peca.referencia,
                    tbl_peca.descricao
            FROM    tbl_os_item
            JOIN    tbl_os_produto          ON  tbl_os_produto.os_produto   = tbl_os_item.os_produto
                                            AND tbl_os_produto.os           = $os
            JOIN    tbl_os                  ON  tbl_os.os                   = tbl_os_produto.os
                                            AND tbl_os.os                   = $os
            JOIN    tbl_pedido              ON  tbl_pedido.pedido           = tbl_os_item.pedido
            JOIN    tbl_pedido_item         ON  tbl_pedido_item.pedido      = tbl_pedido.pedido
                                            AND ((tbl_pedido_item.peca        = tbl_os_item.peca  and tbl_os_item.pedido_item isnull) or tbl_pedido_item.pedido_item = tbl_os_item.pedido_item) 
            JOIN    tbl_peca                ON  tbl_peca.peca               = tbl_os_item.peca
            WHERE   tbl_os_item.fabrica_i = {$login_fabrica}
            AND     (
                        SELECT  COUNT(tbl_os_item_nf.os_item)
                        FROM    tbl_os_item_nf
                        WHERE   tbl_os_item_nf.os_item = tbl_os_item.os_item
                    ) = 0
            AND     (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada) > 0
            AND     tbl_pedido.pedido NOT IN (
                        SELECT  tbl_pedido_cancelado.pedido
                        FROM    tbl_pedido_cancelado
                        WHERE   tbl_pedido_cancelado.pedido         = tbl_pedido.pedido
                        AND     tbl_pedido_cancelado.pedido_item    = tbl_pedido_item.pedido_item
                        AND     tbl_pedido_cancelado.qtde           = tbl_pedido_item.qtde
                    )";
$res = pg_query($con, $sql);

if(pg_num_rows($res) > 0){

    $count = pg_num_rows($res);

    for ($i = 0; $i < $count; $i++) {

        $pedido         = pg_fetch_result($res, $i, "pedido");
        $pedido_item    = pg_fetch_result($res, $i, "pedido_item");
        $qtde           = pg_fetch_result($res, $i, "qtde");
        $os_item        = pg_fetch_result($res, $i, "os_item");
        $seu_pedido     = str_replace("SPG", "", pg_fetch_result($res, $i, "seu_pedido"));
        $peca           = pg_fetch_result($res, $i, "peca");
        $referencia     = pg_fetch_result($res, $i, "referencia");
        $descricao      = pg_fetch_result($res, $i, "descricao");

?>

            <tr>

                <input type="hidden" name="nf_pecas[<?=$i?>][os_item]" value="<?=$os_item?>" />
                <input type="hidden" name="nf_pecas[<?=$i?>][pedido]" value="<?=$pedido?>" />
                <input type="hidden" name="nf_pecas[<?=$i?>][qtde]" value="<?=$qtde?>" />
                <input type="hidden" name="nf_pecas[<?=$i?>][peca]" value="<?=$peca?>" />
                <input type="hidden" name="nf_pecas[<?=$i?>][referencia_descricao_peca]" value="<?=$referencia." - ".$referencia; ?>" />

                <td><strong><?=$referencia?></strong> - <?=$descricao?></td>
                <td class="tac"><a href="pedido_admin_consulta.php?pedido=<?=$pedido?>" target="_blank"><strong><?=$seu_pedido?></strong></a></td>
                            <td class="tac">
                    <input type="text" name="nf_pecas[<?=$i?>][nota_fiscal]" id="nf_pecas_nota_fiscal_<?=$i?>" class="span2 numeric" maxlength="15" value="<?=$nf_pecas[$i]['nota_fiscal']?>" />
                </td>
                <td class="tac">
                    <input type="text" name="nf_pecas[<?=$i?>][data_nota_fiscal]" id="nf_pecas_data_nota_fiscal_<?=$i?>" class="span2 data" value="<?=$nf_pecas[$i]['data_nota_fiscal']?>" />
                </td>

                <td>
                    <button class="btn btn-danger" name="cancelar" id="cancelar_<?=$pedido_item?>_<?=$os_item?>_<?=$qtde?>">Cancelar</button>
                </td>
            </tr>

<?php

    }
}
?>

        </tbody>

	</table>

	<input type="hidden" name="acao" value="gravar" />

	<p class="tac">
		<input type="submit" class="btn" value="Gravar" />
	</p>

</form>

<?php include "rodape.php"; ?>
