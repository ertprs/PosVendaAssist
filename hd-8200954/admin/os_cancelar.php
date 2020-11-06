<?
//conforme chamado 474 (fabricio -  britania) na hr em que eram buscada as informacoes da OS, estava buscando na forma antiga, ou seja, estava buscando informacoes do cliente na tbl_cliente, com o novo metodo as info do consumidor sao gravados direto na tbl_os, com isso hr que estava buscando info do cliente estava buscando no local errado -  Takashi 31/09/2006
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center,gerencia";

include 'autentica_admin.php';

include 'funcoes.php';


$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = pg_exec ($con,$sql);
$pedir_sua_os = pg_result ($res,0,pedir_sua_os);


if (strlen($_POST['os']) > 0){
	$os = trim($_POST['os']);
}

if (strlen($_GET['os']) > 0){
	$os = trim($_GET['os']);
}

if (strlen($_POST['sua_os']) > 0){
	$sua_os = trim($_POST['sua_os']);
}

if (strlen($_GET['sua_os']) > 0){
	$sua_os = trim($_GET['sua_os']);
}
$nosso_ip = include("../nosso_ip.php");
if(($ip=='201.43.245.148' OR ($ip==$nosso_ip) OR $login_fabrica == 15) AND $login_fabrica == 15){
	if($_GET["os"]) header("Location: os_cadastro_latina.php?os=$os");
	else            header("Location: os_cadastro_raphael_ajax.php");
	exit;
}

$sql = "SELECT * FROM tbl_fabrica WHERE fabrica = $login_fabrica";
$res = @pg_exec($con,$sql);
$pedir_sua_os = pg_result ($res,0,pedir_sua_os);
$pedir_defeito_reclamado_descricao = pg_result ($res,0,pedir_defeito_reclamado_descricao);


$btn_cancelar = strtolower ($_POST['cancelar']);
if ($btn_cancelar == "cancelar") {
	$os                  = $_POST["os"];
	$motivo_cancelamento = trim($_POST["motivo_cancelamento"]);

	if(strlen($motivo_cancelamento)==0) $msg_erro = "Por favor digite o motivo do cancelamento da OS";
	if(strlen($msg_erro)==0){
		$sql = "SELECT DISTINCT pedido 
				FROM tbl_os
				JOIN tbl_os_produto USING(os)
				JOIN tbl_os_item    USING(os_produto)
				WHERE tbl_os.fabrica = $login_fabrica
				AND   tbl_os.os      = $os
				AND   tbl_os_item.pedido IS NOT NULL";
		$res1 = @pg_exec($con,$sql);
		if(pg_numrows($res1)>0){
			for($i=0;$i<pg_numrows($res1);$i++){
				$pedido = pg_result($res1,$i,0);

				if(in_array($login_fabrica, array(11,172))) {

		            if (strlen($pedido) > 0) {
	                    $aux_sql   = "SELECT pedido_item FROM tbl_pedido_item WHERE pedido = $pedido";
	                    $aux_res   = pg_query($con, $aux_sql);
	                    $aux_total = pg_num_rows($aux_res);

	                    for ($x = 0; $x < $aux_total; $x++) {
	                        $temp_pedido_item = pg_fetch_result($aux_res, $x, 'pedido_item');
	                        if (!in_array($pedido_itens, $temp_pedido_item)) {
	                            $pedido_itens[] = $temp_pedido_item;
	                        }
	                        unset($temp_pedido_item);
	                    }

		                if (count($pedido_itens) > 0) {
		                    foreach ($pedido_itens as $pedido_item) {
		                        $aux_sql = "
		                            SELECT pedido, qtde, qtde_faturada, qtde_cancelada
		                            FROM tbl_pedido_item
		                            WHERE pedido_item = $pedido_item
		                            LIMIT 1
		                        ";
		                        $aux_res        = pg_query($con, $aux_sql);
		                        $pedido         = (int) pg_fetch_result($aux_res, 0, 'pedido');
		                        $qtde           = (int) pg_fetch_result($aux_res, 0, 'qtde');
		                        $qtde_cancelada = (int) pg_fetch_result($aux_res, 0, 'qtde_cancelada');
		                        $qtde_faturada  = (int) pg_fetch_result($aux_res, 0, 'qtde_faturada');

		                        if($qtde_faturada == 0) {
		                            $sql_cancel = "
		                                UPDATE tbl_pedido_item SET
		                                qtde_cancelada = $qtde
		                                WHERE pedido_item = $pedido_item;

		                                SELECT fn_atualiza_status_pedido($login_fabrica, $pedido);
		                            ";
		                            $res_cancel = pg_query($con, $sql_cancel);

		                            if (pg_num_rows($res_cancel) <= 0) {
		                                $msg_erro = "Erro ao excluir o pedido pendente da OS";
		                            }
		                            
		                            unset($aux_sql, $aux_res, $aux_total, $pedido_itens);
		                        } else {
		                            $msg_erro = "O pedido da OS possui itens faturados, por isso não pode ser excluída.";
		                        }
		                    }
		                }
		            }					
				} else {
					$sql = "SELECT  PI.pedido_item,
							PI.qtde      ,
							PC.peca      ,
							PC.referencia,
							PC.descricao ,
							OP.os        ,
							PE.posto     ,
							PE.distribuidor
						FROM    tbl_pedido       PE
						JOIN    tbl_pedido_item  PI ON PI.pedido     = PE.pedido
						JOIN    tbl_peca         PC ON PC.peca       = PI.peca
						LEFT JOIN tbl_os_item    OI ON OI.peca       = PI.peca       AND OI.pedido = PE.pedido
						LEFT JOIN tbl_os_produto OP ON OP.os_produto = OI.os_produto
						WHERE   PI.pedido      = $pedido
						AND     PE.fabrica     = $login_fabrica
						AND     PE.exportado   IS NULL";
					$res2 = pg_exec($con,$sql);
					if(pg_numrows($res2)>0){
							$peca  = pg_result($res2,0,peca);
							$qtde  = pg_result($res2,0,qtde);
							$posto = pg_result($res2,0,posto);
							$sql = "UPDATE tbl_pedido_item SET qtde_cancelada = qtde WHERE pedido_item = $cancelar;";
							$res = pg_exec ($con,$sql);
							$sql = "INSERT INTO tbl_pedido_cancelado (
										pedido,
										posto,
										fabrica,
										os,
										peca,
										qtde,
										motivo,
										data
									)VALUES(
										$pedido,
										$posto,
										$login_fabrica,
										$os,
										$peca,
										$qtde,
										'$motivo_cancelamento',
										current_date
									);";
							$res = pg_exec ($con,$sql);
					}else{ 
						if($login_fabrica <> 45) $msg_erro= "OS não pode ser cancelada porque o pedido já foi exportado!";
					}
				}
			}
		}
		if(strlen($msg_erro)==0){
			$sql = "BEGIN TRANSACTION";
			$res = pg_exec($con,$sql);
			$sql = "INSERT INTO tbl_os_status (os,status_os,observacao) VALUES ($os,15,'$motivo_cancelamento');";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			$sql = "UPDATE tbl_os SET excluida = TRUE WHERE os = $os";
			$res = pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if(strlen($msg_erro)==0){
				$sql = "COMMIT TRANSACTION";
				$res = pg_exec($con,$sql);
				header("Location: os_press.php?os=$os");
				exit;
			}else{
				$res = pg_exec($con,"ROLLBACK TRANSACTION");
			}
		}
	}

}
$title = "Cancelar OS - ADMIN"; 

$layout_menu = 'callcenter';
include "cabecalho.php";
?>

<script language="JavaScript">
function cancelar_os(){
	if (confirm ('Cancelar esta OS?')) {
		document.frm_cancelar.cancelar.value='cancelar';
		document.frm_cancelar.submit(); 
	}
}
</script>
<style>
	.Cancelar td{
		font-family: Verdana,sans;
		font-size:12px;
		color: #000000;
		font-weight:none;
	}
	.Conteudo{
		font-family: Verdana,sans;
		font-size: 10px;
		color: #333333;
	}
	.erro{
		font-family: Verdana,sans;
		font-size: 12px;
		color: white;
		background-color: red;
		width: 700px;
	}
</style>
<?
if(strlen($os) > 0) {
	$sql = "SELECT  OS.sua_os    ,
					PR.referencia,
					PR.descricao ,
					TO_CHAR(data_digitacao,'DD/MM/YYYY')  AS data_digitacao ,
					TO_CHAR(data_abertura,'DD/MM/YYYY')   AS data_abertura  ,
					TO_CHAR(data_fechamento,'DD/MM/YYYY') AS data_fechamento,
					TO_CHAR(finalizada,'DD/MM/YYYY')      AS finalizada     ,
					consumidor_nome
			FROM tbl_os      OS
			JOIN tbl_produto PR USING(produto)
			WHERE OS.fabrica = $login_fabrica
			AND   OS.os      = $os
			";
	$resTroca = pg_exec ($con,$sql);
	if(pg_numrows($resTroca)>0) {
		echo "<br><table align='center' border='0' cellspacing='0' width='700'>";
		echo "<tr bgcolor='#FFFFFF' class='Conteudo'>";
		echo "<td align='left' colspan='6'><b><font size='2'>Informações da OS</font></td>";
		echo "</tr>";
		echo "<tr bgcolor='#668CFF' class='Conteudo'>";
		echo "<td align='left'> <b>OS</td>";
		echo "<td align='left'> <b>Referência</td>";
		echo "<td align='left'> <b>Produto</td>";
		echo "<td align='left'> <b>Digitada</td>";
		echo "<td align='left'> <b>Abertura</td>";
		echo "<td align='left'> <b>Finalizado</td>";
//		echo "<td align='left'> <b>Dt. Fechamento</td>";

		echo "</tr>";
			$sua_os          = pg_result ($resTroca,0,sua_os)        ;
			$referencia      = pg_result ($resTroca,0,referencia)    ;
			$descricao       = pg_result ($resTroca,0,descricao)     ;
			$data_digitacao  = pg_result ($resTroca,0,data_digitacao);
			$data_abertura   = pg_result ($resTroca,0,data_abertura);
			$data_fechamento = pg_result ($resTroca,0,data_fechamento);
			$finalizada      = pg_result ($resTroca,0,finalizada);


			if($cor == "#D7E1FF") $cor = '#F0F4FF';
			else                  $cor = '#D7E1FF';
			echo "<tr bgcolor='$cor' class='Conteudo'>";
			echo "<td align='left'> $sua_os</td>";
			echo "<td align='left'> $referencia</td>";
			echo "<td align='left'> $descricao</td>";
			echo "<td align='left'> $data_digitacao</td>";
			echo "<td align='left'> $data_abertura</td>";
			echo "<td align='left'> $finalizada</td>";
//			echo "<td align='left'> $data_fechamento</td>";
			echo "</tr>";
		echo "</table>";
	}

	$sql = "SELECT tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_os_item.qtde
			FROM tbl_os_item
			JOIN tbl_os_produto USING (os_produto)
			JOIN tbl_peca       USING (peca)
			WHERE os = $os";
	$resTroca = pg_exec ($con,$sql);
	if(pg_numrows($resTroca)>0) {
		echo "<tr bgcolor='#668CFF' class='Conteudo'>";
		echo "<td align='left'> <b>Referência</td>";
		echo "<td align='left'> <b>Peça</td>";
		echo "<td align='left'> <b>Qtde</td>";
		echo "</tr>";
		for ($i = 0 ; $i < pg_numrows($resTroca) ; $i++) {
			$peca_referencia = pg_result ($resTroca,$i,referencia) ;
			$peca_descricao  = pg_result ($resTroca,$i,descricao) ;
			$peca_qtde       = pg_result ($resTroca,$i,qtde)      ;
			if($cor == "#D7E1FF") $cor = '#F0F4FF';
			else                  $cor = '#D7E1FF';
			echo "<tr bgcolor='$cor' class='Conteudo'>";
			echo "<td align='left'> $peca_referencia</td>";
			echo "<td align='left'> $peca_descricao</td>";
			echo "<td align='left'> $peca_qtde</td>";
			echo "</tr>";
		}
		echo "<tr bgcolor='#FF8083' class='Conteudo'>";
		echo "<td align='left' colspan='3'><u> Em caso de troca as peças acima serão canceladas</td>";
		echo "</tr>";
		echo "</table>";
	}else echo "<b><font size='1'>Nenhum peça lançada nesta OS</font></b>";


	echo "<form method='post' name='frm_cancelar' action='$PHP_SELF?os=$os'>";
	echo "<table width='700' align='center' border='2' cellspacing='0' bgcolor='#F7D7D7'  class='Cancelar'>";
	echo "<input type='hidden' name='os' value='$os'>";
	echo "<input type='hidden' name='cancelar' value=''>";
	echo "<tr>";
	echo "<td align='center' style='color: #F7D7D7'> ";
	echo "<font color='#3300CC' size='+1'> <b>Cancelar OS?</b> </font> ";
		echo "<table border='0' cellspacing='0' width='600'>";
		echo "<tr bgcolor='#F7D7D7' class='Conteudo'>";
		echo "<td align='left'><b>Motivo:</b></td>";
		echo "<td align='left'><textarea name='motivo_cancelamento' cols='50' rows='3' class='Caixa'>$motivo_cancelamento</textarea></td>";
		echo "</tr>";
		echo "</table>";
	echo "<input type='button' value='Cancelar' name='btn_cancelar' id='btn_cancelar' onclick=\"javascript: cancelar_os();\">";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</form>";

}
?>

<p>

<? 

if(isset($limpa_tudo)){
	$sql = "SELECT distinct os FROM bkp_britania_os WHERE nota_fiscal is  null;";
	$resxx = @pg_exec($con,$sql);
	if(pg_numrows($resxx)>0){

		for($a=0;$a<pg_numrows($resxx);$a++){

			$os = pg_result($resxx,$a,os);

			$sql = "SELECT DISTINCT pedido 
					FROM tbl_os
					JOIN tbl_os_produto USING(os)
					JOIN tbl_os_item    USING(os_produto)
					WHERE tbl_os.fabrica = $login_fabrica
					AND   tbl_os.os      = $os
					AND   tbl_os_item.pedido IS NOT NULL";
			$res1 = @pg_exec($con,$sql);

			if(pg_numrows($res1)>0){
				for($i=0;$i<pg_numrows($res1);$i++){
					$pedido = pg_result($res1,$i,0);
					$sql = "SELECT  PI.pedido_item,
							PI.qtde      ,
							PC.peca      ,
							PC.referencia,
							PC.descricao ,
							OP.os        ,
							PE.posto     ,
							PE.distribuidor
						FROM    tbl_pedido       PE
						JOIN    tbl_pedido_item  PI ON PI.pedido     = PE.pedido
						JOIN    tbl_peca         PC ON PC.peca       = PI.peca
						LEFT JOIN tbl_os_item    OI ON OI.peca       = PI.peca       AND OI.pedido = PE.pedido
						LEFT JOIN tbl_os_produto OP ON OP.os_produto = OI.os_produto
						WHERE   PI.pedido      = $pedido
						AND     PE.fabrica     = $login_fabrica
						AND     PE.exportado   IS NULL";
					$res2 = pg_exec($con,$sql);
					if(pg_numrows($res2)>0){
							$peca  = pg_result($res2,0,peca);
							$qtde  = pg_result($res2,0,qtde);
							$posto = pg_result($res2,0,posto);
							$sql = "UPDATE tbl_pedido_item SET qtde_cancelada = qtde WHERE pedido_item = $cancelar;";
							$res = pg_exec ($con,$sql);
							$sql = "INSERT INTO tbl_pedido_cancelado (
										pedido,
										posto,
										fabrica,
										os,
										peca,
										qtde,
										motivo,
										data
									)VALUES(
										$pedido,
										$posto,
										$login_fabrica,
										$os,
										$peca,
										$qtde,
										'$motivo_cancelamento',
										current_date
									);";
							$res = pg_exec ($con,$sql);
					}
				}
			}
			if(strlen($msg_erro)==0){
				$sql = "BEGIN TRANSACTION";
				$res = pg_exec($con,$sql);
				$sql = "INSERT INTO tbl_os_status (os,status_os,observacao) VALUES ($os,15,'OS ABERTA HÁ MAIS DE 90 DIAS');";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
				$sql = "UPDATE tbl_os SET excluida = TRUE WHERE os = $os AND tbl_os.finalizada IS NULL";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

				if(strlen($msg_erro)==0){
					$sql = "COMMIT TRANSACTION";
					$res = pg_exec($con,$sql);
					echo "os $os  -  CANCELADA<br> ";
				}else{
					$res = pg_exec($con,"ROLLBACK TRANSACTION");
				}
			}
		}
	}
}

# HD 65873 - não estava mostrando mensagem de erro.
if (strlen($msg_erro) > 0){
	echo "<center><div class='erro'><strong>$msg_erro</strong></div></center>";
}

include "rodape.php";

?>
