<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "financeiro";

include 'autentica_admin.php';

if(filter_input(INPUT_POST,'liberar_os')){
	$os 					= filter_input(INPUT_POST,'os');
	$mao_de_obra_desconto	= filter_input(INPUT_POST,'mao_de_obra_desconto');
	$posto 					= filter_input(INPUT_POST,'posto');

	$sql = "
        INSERT INTO tbl_extrato_lancamento (
            valor,
            os,
            historico,
            admin,
            lancamento,
            fabrica,
            posto
        ) VALUES (
            '$mao_de_obra_desconto',
            $os,
            'Liberação de O.S com carência de 90 dias - $os',
            $login_admin,
            262,
            $login_fabrica,
            $posto
        )
    ";

	$res = pg_query($con, $sql);
	if(!pg_last_error($con)){
		echo json_encode(array("msg"=>utf8_encode("Liberação de pagamento realizada com sucesso.")));
	}else{
		echo "Falha ao liberar pagamento.";
	}

	exit;
}

if(filter_input(INPUT_POST,'btnacao')){
	$codigo    = filter_input(INPUT_POST,'codigo',FILTER_VALIDATE_INT);
	$os        = filter_input(INPUT_POST,'os',FILTER_SANITIZE_NUMBER_INT);
	$cnpj      = filter_input(INPUT_POST,'cnpj');
	$nome      = filter_input(INPUT_POST,'nome');
	$situacao  = filter_input(INPUT_POST,'situacao');

	if($situacao == 1){
		$join = "join tbl_extrato_lancamento on tbl_extrato_lancamento.os = tbl_os.os and tbl_extrato_lancamento.fabrica = $login_fabrica";
		$where_extrato = " and tbl_extrato_lancamento.extrato_lancamento is not null ";
	}elseif($situacao == 2){
		$join = " left join tbl_extrato_lancamento on tbl_extrato_lancamento.os = tbl_os.os and tbl_extrato_lancamento.fabrica = $login_fabrica ";
		$where_extrato = " and tbl_extrato_lancamento.extrato_lancamento is null  ";
	}else{
		$join = " left join tbl_extrato_lancamento on tbl_extrato_lancamento.os = tbl_os.os and tbl_extrato_lancamento.fabrica = $login_fabrica ";
	}
	if(!empty($os)){
		$where_os = " and tbl_os.os = $os ";
		//$where_extrato = " and tbl_extrato_lancamento.extrato_lancamento is not null";
	}

	if(!empty($codigo) && !empty($cnpj) && !empty($nome)){
		$where_posto = " and tbl_posto.posto = $codigo ";
	}

			$sql_os = "SELECT 
						tbl_os.os, 
						tbl_os.data_abertura, 
						tbl_os.data_fechamento, 
						tbl_posto.nome AS nome_posto, 
						tbl_posto_fabrica.codigo_posto, 
						tbl_os_extra.mao_de_obra_desconto,
						tbl_posto.posto,
						tbl_extrato_lancamento.extrato_lancamento
						FROM tbl_os 
						INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
						INNER JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
						INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						
						$join
						WHERE tbl_os.fabrica = $login_fabrica 
						AND tbl_os.data_digitacao > '2015-07-01 00:00:00'
						AND tbl_os.mao_de_obra = 0
						AND tbl_os.excluida IS NOT TRUE
						AND tbl_os.finalizada IS NOT NULL
						AND tbl_os.data_fechamento IS NOT NULL
						AND tbl_os_extra.mao_de_obra_desconto > 0 
						$where_extrato
						$where_posto
						AND tbl_os_extra.extrato IS NOT NULL
						$where_os ";
	$res_os = pg_query($con, $sql_os);
}


if(filter_input(INPUT_POST,'liberar')){
    $check_os = filter_input(INPUT_POST,'check_os',FILTER_UNSAFE_RAW,FILTER_REQUIRE_ARRAY);

    foreach($check_os as $linha){
        $mao_de_obra_desconto   = filter_input(INPUT_POST,'mao_de_obra_desconto_'.$linha);
        $posto                  = filter_input(INPUT_POST,'posto_'.$linha);

        $res = pg_query($con,"BEGIN TRANSACTION");
        $sql = "
                INSERT INTO tbl_extrato_lancamento (
                    valor,
                    os,
                    descricao,
                    historico,
                    admin,
                    lancamento,
                    fabrica,
                    posto
                ) VALUES (
                    '$mao_de_obra_desconto',
                    $linha,
                    'Liberação de O.S com carência de 90 dias - $linha',
                    'Liberação de O.S com carência de 90 dias - $linha',
                    $login_admin,
                    262,
                    $login_fabrica,
                    $posto
                )
            ";
        $res = pg_query($con, $sql);
        if(!pg_last_error($con)){
            $res = pg_query($con,"COMMIT TRANSACTION");
            $ok = "Liberação de pagamento realizada com sucesso. <br>";
        }else{
            $res = pg_query($con,"ROLLBACK TRANSACTION");
            $msg_erro = "Falha ao liberar pagamento. <br>";
        }
    }
}


$layout_menu = "financeiro";
$title='O.S Carência 90 dias';
include 'cabecalho.php';
?>

<script type="text/javascript">
function fnc_pesquisa_posto (campo, campo2, campo3, tipo) {
    if (tipo == "nome" ) {
        var xcampo = campo;
    }
    if (tipo == "cnpj" ) {
        var xcampo = campo2;
    }
    if (tipo == "codigo" ) {
        var xcampo = campo3;
    }
    if (xcampo.value != "") {
        var url = "";
        url = "posto_pesquisa.php?forma=nao&campo=" + xcampo.value + "&tipo=" + tipo ;
        janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=650, height=400, top=0, left=0");
        janela.retorno = "<? echo $_SERVER['PHP_SELF']; ?>";
        janela.nome 	= campo;
        janela.cnpj 	= campo2;
        janela.codigo	= campo3;
        janela.focus();
    }
    else{
        alert("Informe toda ou parte da informação para realizar a pesquisa");
    }
}

$(function(){
    $("button[id^=liberar_]").click(function(event){
        event.preventDefault();
        var guarda = $(this);
        var aux = guarda.attr("id").split("_");
        var os = aux[1];
        var mao_de_obra_desconto = $("#mao_de_obra_desconto_"+os).val();
        var posto = $("#posto_"+os).val();

        $.ajax({
            url: 'relatorio_os_carencia_90_dias.php',
            type: 'POST',
            dataType: 'JSON',
            data: {
                liberar_os : true,
                os: os,
                mao_de_obra_desconto : mao_de_obra_desconto,
                posto : posto
            },
            beforeSend:function(){
                $("#liberar_"+os).detach();
                $("#check_"+os).hide();
            }
        })
        .done(function(data){
            alert(data['msg']);
            $("#td_"+os).html("Liberado");
        })
        .fail(function(){
            alert("Falha ao liberar pagamento");
            $("#td_"+os).html(guarda);
            $("#check_"+os).show();
        });
    });
});

</script>


<style>
	
	.linha{
		font-size: 12px;
	}

</style>

<form name="frm_carencia_90" method="POST" action="<? $PHP_SELF ?>">
<input type="hidden" name="motivo_recusa" value="<? echo $motivo_recusa ?>">

<? if (strlen($msg_erro) > 0) { ?>
<table width="700px" border="0" cellpadding="2" cellspacing="1" class="error" align='center'>
	<tr>
		<td><? echo $msg_erro; ?></td>
	</tr>
</table>
<br>
<? } ?>
<? if (strlen($ok) > 0) { ?>
<table width="700px" border="0" cellpadding="2" cellspacing="1" align='center'>
	<tr>
		<td style="color:blue"><? echo $ok; ?></td>
	</tr>
</table>
<br>
<? } ?>

<table width='700px' class='formulario' border='0px' cellpadding='5' cellspacing='1' align='center'>
	<tr  style="margin-left:70px;">
 		<td class='titulo_tabela'>Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td bgcolor='#DBE5F5' align="left">
			<table width="500" border="0" cellpadding="2" cellspacing="1" class="titulo" align='center' >
				<tr>
					<td>O.S</td>
					<td>Situação</td>
				</tr>
				<tr>
					<td><input type="text" class='frm' name="os" size="14" value="<?php echo $os ?>"></td>
					<td>
						<select name="situacao" class='frm'>
							<option value="">Situação</option>
							<option value="1" <?=($situacao == 1) ? "selected" : ""?>>Liberadas p/ Pagamento</option>
							<option value="2" <?=($situacao == 2) ? "selected" : ""?>>Não Liberadas p/ Pagamento</option>
						</select>
					</td>
				</tr>
				<tr>
					<td>CNPJ</td>
					<td>Razão Social</td>
				</tr>
				<tr>
					<td align='left'>
			            <input class='frm' type="text" name="cnpj" size="14" style="float: left;" maxlength="14" value="<? echo $cnpj ?>" style="width:150px"<?if(strlen($posto) > 0 and $login_fabrica == 45 AND strlen(trim($codigo)) > 0)  echo " readonly='readonly' ";?>><a href="#"><img src='imagens/lupa.png' style="cursor:pointer" border='0' align='left' onclick="javascript: fnc_pesquisa_posto (document.frm_carencia_90.nome,document.frm_carencia_90.cnpj,document.frm_carencia_90.codigo,'codigo')"></a>
			        </td>
			        <td colspan="2" align='left'>
			            <input class='frm' type="text" name="nome" style="float: left; width:300px;" maxlength="60" value="<? if ($login_fabrica == 50) { echo strtoupper($nome); } else { echo $nome; } ?>"><a href="#"><img src='imagens/lupa.png' style="cursor:pointer" border='0' align='left' onclick="javascript: fnc_pesquisa_posto (document.frm_carencia_90.nome,document.frm_carencia_90.cnpj,document.frm_carencia_90.codigo,'nome')"></a>
			            <input class='frm' type="hidden" name="codigo" value="<?=$codigo?>">
			        </td>
				</tr>
			</table>
		</td>
		</tr>
		<tr>
			<td  bgcolor='#DBE5F5' align="center">
				<input type='submit'  name="btnacao" value='Pesquisar' ALT="Gravar" border='0' style="cursor:pointer;">
			</td>
		</tr>
	</table>
	<input type='hidden' name='btnacao' value='Pesquisar'>

</form>
<br>
<?

if(pg_num_rows($res_os)>0){
?>
<form name='frm_liberacao_carencia_90' method='POST' action='<? $PHP_SELF ?>'>
    <TABLE width='700px' border='1px' align='center' cellspacing='2' cellpadding='2' style='border-collapse: collapse' bordercolor='#d2e4fc'>
        <thead>
            <tr class='titulo_coluna'>
                <td colspan='6' height='20'><font size='2'>O.S com carência de 90 dias</font></td>
            </tr>
            <tr class='titulo_coluna'>
                <td>#</td>
                <td>O.S</td>
                <td>Posto</td>
                <td>Data Abertura</td>
                <td>Data Fechamento</td>
                <td>Liberação</td>
            </tr>
        </thead>
        <tbody>
<?php
	for($i=0; $i<pg_num_rows($res_os);$i++){
		$nome 					= pg_fetch_result($res_os, $i, "nome");
		$os 					= pg_fetch_result($res_os, $i, "os");
		$data_abertura 			= pg_fetch_result($res_os, $i, "data_abertura");
		$data_fechamento 		= pg_fetch_result($res_os, $i, "data_fechamento");
		$nome_posto	   			= pg_fetch_result($res_os, $i, "nome_posto");	
		$mao_de_obra_desconto   = pg_fetch_result($res_os, $i, "mao_de_obra_desconto");
		$posto   				= pg_fetch_result($res_os, $i, "posto");
		$extrato_lancamento     = pg_fetch_result($res_os, $i, "extrato_lancamento");

		list($yda, $mda, $dda) = explode("-", $data_abertura);
		$data_abertura_tela = $dda. "/". $mda ."/". $yda;
		
		list($ydf, $mdf, $ddf) = explode("-", $data_fechamento);
		$data_fechamento_tela = $ddf. "/". $mdf ."/". $ydf;
?>

            <tr class='linha'>
                <td id='check'>
<?php
        if(strlen(trim($extrato_lancamento))==0){
?>
                    <input type='checkbox' name='check_os[]' id="check_<?=$os?>" value='<?=$os?>'>
<?php
        }
?>
                </td>
                <td class='Conteudo' id='os'>
                    <a href='os_press.php?os=<?=$os?>' target='_blank'><?=$os?></a>
                </td>
                <td><?=$nome_posto?></td>
                <td align='center'><?=$data_abertura_tela?></td>
                <td align='center'><?=$data_fechamento_tela?></td>
<?php
        if(strlen(trim($extrato_lancamento))==0){
?>
                <td align='center' id="td_<?=$os?>">
                    <!--<a href='#' onclick='fnc_liberar($(this), <?=$os?>);' id='<?=$i?>'>Liberar Pagamento</a>-->
                    <button id="liberar_<?=$os?>" name="liberar_<?=$os?>">Liberar</button>
                    <input type='hidden' name='mao_de_obra_desconto_<?=$os?>' id='mao_de_obra_desconto_<?=$os?>' value='<?=$mao_de_obra_desconto?>'>
                    <input type='hidden' name='posto_<?=$os?>' id='posto_<?=$os?>' value='<?=$posto?>'>
                </td>
<?php
        }else{
?>
                <td align='center'>Liberado</td>
<?php
        }
?>
            </tr>
<?php
	}
?>
        </tbody>
    </table>

    <br><br>

	<table width='700px' align='center'>
        <tr>
            <td align='center'>
                <input type='submit' name='liberar' value='Liberar'>
                <input type='hidden' name='liberar' value='Liberar'>
            </td>
        </tr>
    </table>
</form>
<?php
}
include "rodape.php";
?>
