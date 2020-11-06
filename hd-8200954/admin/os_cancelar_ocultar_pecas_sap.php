<?php
#echo "<h1> Programa em Manutencao </h1>";
#exit;

include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "funcoes.php";

// if ($ip<> include('../nosso_ip.php')) {
// 	echo "<h2> Em manutenção. Aguarde alguns minutos.</h2>";exit();
// }

$admin_privilegios = "gerencia,call_center";
include "autentica_admin.php";

if ($login_fabrica!=3 AND $login_fabrica!=11 AND $login_fabrica!=10 and $login_fabrica != 172){
	header("Location: menu_callcenter.php");
	exit();
}

$os = $_GET['os'];
$id_servico_realizado = $_GET['id_servico_realizado'];

?>
<script type="text/javascript" src="js/jquery-1.8.3.min.js"></script>
<script type="text/javascript">

function enviar_cancelar(os){	
	var justificativa = $('.justificativa').val();	
	var chkpecas = $('.chk_pecas');
	
	if( (justificativa=='') || (justificativa==null) || justificativa.length==0){
		alert('Justificativa não informada!');
		return false;
	}

	for(var i=0;i<chkpecas.length;i++){
		if(chkpecas[i].checked == true){
			var itens = $('.item').val();
		}
	}

	$.ajax({
		url: "<?echo $PHP_SELF;?>",
		type: "get", 
		data:{
            ajax_cancelar_ocultar:'ok' ,
            os:os,
            itens: itens,
            justificativa: justificativa                                         
        },
        complete: function(data){
        	var retorno = data.responseText.split("|");
			if(retorno[0] == "ok"){
        		//alert(retorno[1]);        		        		
        		window.parent.Shadowbox.close();         			
        	} else {
        		//ert(retorno[1]);        		
        		window.parent.Shadowbox.close();
        	}
        }
	})
}

</script>

<style type="text/css">
	.Titulo {
		text-align: center;
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 12px;
		font-weight: bold;
		color: #FFFFFF;
		background-color: #596D9B;
	}

	.titulo_tabela{
	    background-color:#596d9b;
	    font: bold 16px "Arial";
	    color:#FFFFFF;
	    text-align:center;
	}

	.titulo_justificar {
		text-align: center;
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 14px;
		font-weight: bold;
		color: #000000;	
	}
</style>

<?
if ($login_fabrica==3) {
	$id_servico_realizado = 20;
	$id_servico_realizado_ajuste = 96;
}

// Cancelar/Ocultar peças Britania

if($_REQUEST['ajax_cancelar_ocultar'] == "ok"){

	$os = $_REQUEST['os'];
	$justificativa = $_REQUEST['justificativa'];

	$itens = explode(",",$_REQUEST['itens']); //OBS: o caracter para separação dos itens pode ser outro, não precisa ser a Vírgula.

	if(!empty($os)){

		$sql = "SELECT posto,sua_os,finalizada FROM tbl_os where os={$os}";
		$res = pg_query($con,$sql);

		$posto = trim(pg_fetch_result($res,0,posto));
		$sua_os = trim(pg_fetch_result($res,0,sua_os));
		$finalizada = trim(pg_fetch_result($res,0,finalizada));


		pg_query($con, "BEGIN TRANSACTION");

		if(strlen($finalizada) > 0 ){

			$sql = "UPDATE tbl_os SET finalizada = NULL WHERE os = {$os}";
			$res = pg_query($con,$sql);
			$msg_erro = pg_errormessage($con);

		}

		if(strlen($msg_erro) == 0){

			for($j = 0; $j < count($itens); $j++){

				$sql_peca = " SELECT tbl_peca.referencia AS referencia ,
				tbl_peca.descricao AS descricao
				FROM tbl_peca
				JOIN tbl_os_item USING(peca)
				WHERE tbl_os_item.os_item = {$itens[$j]}"; 
				$res_peca = pg_query($con,$sql_peca);
				$peca_referencia = trim(pg_result($res_peca,0,referencia));
				$peca_descricao = trim(pg_result($res_peca,0,descricao));

				$sql = "UPDATE tbl_os_item
				SET servico_realizado = {$id_servico_realizado_ajuste} ,
				admin = {$login_admin} ,
				liberacao_pedido = FALSE,
				liberacao_pedido_analisado = FALSE,
				data_liberacao_pedido = null
				WHERE os_item = {$itens[$j]}";
				$res = pg_query($con,$sql);
				$msg_erro = pg_errormessage($con);

				if(strlen($msg_erro) == 0){

					$sql = "INSERT INTO tbl_comunicado (
					descricao ,
					mensagem ,
					tipo ,
					fabrica ,
					obrigatorio_os_produto ,
					obrigatorio_site ,
					posto ,
					ativo
					) VALUES (
					'Pedido de Peças CANCELADO' ,
					'Seu pedido da peça $peca_referencia - $peca_descricao referente a OS $sua_os foi <b>cancelado</b> pela fábrica. <br><br>Justificativa da Fábrica: $justificativa',
					'Pedido de Peças',
					$login_fabrica,
					'f' ,
					't',
					$posto,
					't'
					);";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);

					if(strlen($msg_erro) == 0){
						$msg_posto= "Pedido de Peças Cancelado Pela Fábrica. Justificativa: ".utf8_decode($justificativa);
						$sql = "INSERT INTO tbl_os_status 
						(os,status_os,data,observacao,admin)
						VALUES ({$os},73,current_timestamp,'{$msg_posto}',{$login_admin})";
						$res = pg_query($con,$sql);
						$msg_erro = pg_errormessage($con);

					}
				}
			}
		}

		if (strlen($msg_erro) == 0 AND strlen($finalizada) > 0){

			$sql = "UPDATE tbl_os SET finalizada = '$finalizada' WHERE os = {$os}";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		if(strlen($msg_erro)>0){
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			echo "erro|$msg_erro";
		}else{
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			$msg = "Os itens da OS foram cancelados"; 
			echo "ok|$msg";
		}
	}
	exit;
}
// FIM

$sql_peca = "SELECT  tbl_os_item.os_item AS item,
			tbl_peca.troca_obrigatoria AS troca_obrigatoria,
			tbl_peca.retorna_conserto AS retorna_conserto,
			tbl_peca.referencia AS referencia,
			tbl_peca.descricao AS descricao,
			tbl_peca.peca AS peca,
			tbl_peca.bloqueada_garantia,
			tbl_os_item.digitacao_item
			FROM tbl_os_produto
			JOIN tbl_os_item USING(os_produto)
			JOIN tbl_peca USING(peca)
			WHERE tbl_os_produto.os=$os
			AND tbl_os_item.servico_realizado=$id_servico_realizado
			AND tbl_peca.bloqueada_garantia = 't'
			AND tbl_os_item.pedido IS NULL";

$res_peca = pg_exec($con,$sql_peca);
$resultado = pg_numrows($res_peca);

echo "
	<div>
		<div class='titulo_tabela'>
			Selecione as peças para cancelamento
		</div>
		<div align='center'><br>
			<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#E6E8FA' width='98%'>
				<tbody>
					<tr class='Titulo' height='25'>
						<td>&nbsp;</td>
						<td>Referência Peça</td>
						<td>Descrição Peça</td>
					</tr>";

for($j=0;$j<$resultado;$j++){

	$item               = trim(pg_result($res_peca,$j,item));
	$peca_referencia    = trim(pg_result($res_peca,$j,referencia));
	$peca_descricao     = trim(pg_result($res_peca,$j,descricao));
	$bloqueada_garantia = trim(pg_result($res_peca,$j,bloqueada_garantia));
	$digitacao_item     = trim(pg_result($res_peca,$j,digitacao_item));
	$peca               = trim(pg_result($res_peca,$j,peca));

	echo "
					<tr>
						<td align='center' style='font-size:12px' nowrap><input type='checkbox' name='chk_pecas' id='chk_pecas' class='chk_pecas'></td>
						<td align='center' style='font-size:12px' nowrap>" . $peca_referencia . "</td>
						<td align='center' style='font-size:12px' nowrap>" . $peca_descricao . "</td>					
						<input type='hidden' value=" . $item . " id='item' class='item'/>
					</tr>";

}
echo "<br>

					<tr bgcolor='#596D9B' height='3'><td colspan='3'></td></tr>
				</tbody>
			</table>
		</div><br>
		<div align='center'>
			<label for='cancelar_ocultar' class='titulo_justificativar'>Justifique o Cancelamento das peças selecionadas</label><br>
			<textarea name='justificativa' class='justificativa' id='justificativa' rows=20 cols=80 style='resize:none; outline:none;'></textarea>
		</div><br>
		<div align='center'>
			<input type='button' name='btn_gravar_cancelar_ocultar' class='btn_gravar_cancelar_ocultar' value='Gravar'  onClick='enviar_cancelar($os)' />			
		</div>
	</div>";
?>