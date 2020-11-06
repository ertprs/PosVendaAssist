<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

echo "desativado temporariamente"; exit;
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';
include "monitora.php";
$layout_menu = "gerencia";
$title = "Relatório de Peças Pendentes Críticas para Troca";

include 'cabecalho.php';

if (strlen($_GET['ate']) > 0) $ate = $_GET['ate'];
else                          $ate = $_POST['ate'];

if (strlen($_GET['peca']) > 0) $peca = $_GET['peca'];
else                           $peca = $_POST['peca'];

if (strlen($_GET['referencia']) > 0) $referencia = $_GET['referencia'];
else                                 $referencia = $_POST['referencia'];

if (strlen($_GET['btn_acao']) > 0) $btn_acao = $_GET['btn_acao'];
else                                 $btn_acao = $_POST['btn_acao'];

?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 11px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}

.Erro {
	text-align: center;
	font-family: Arial;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #FF0000;
}

.Conteudo {
	text-align: left;
	font-family: Arial;
	font-size: 11px;
	font-weight: normal;
}

.Conteudo2 {
	text-align: center;
	font-family: Arial;
	font-size: 11px;
	font-weight: normal;
}

.Caixa{
	BORDER-RIGHT: #6699CC 1px solid;
	BORDER-TOP: #6699CC 1px solid;
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid;
	BORDER-BOTTOM: #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF
}
</style>
<? include "javascript_pesquisas.php" ?>

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<script language="JavaScript">
var checkflag = "false";
function SelecionaTodos(field) {
    if (checkflag == "false") {
        for (i = 0; i < field.length; i++) {
            field[i].checked = true;
        }
        checkflag = "true";
        return true;
    }
    else {
        for (i = 0; i < field.length; i++) {
            field[i].checked = false;
        }
        checkflag = "false";
        return true;
    }
}
</script>

<?
flush();

if($btn_acao == "Efetivar Troca"){
	$qtde_os         = $_POST['qtde_os'];
	for ($i = 0 ; $i < $qtde_os ; $i++) {
		$ativo             = trim($_POST['ativo_'. $i]);
		if($ativo =='t'){
			$distribuidor      = trim($_POST['distribuidor_'. $i]);
			$pedido            = trim($_POST['pedido_'. $i]);
			$peca              = trim($_POST['peca_'. $i]);
			$os                = trim($_POST['os_'. $i]);
			$os_item           = trim($_POST['os_item_'. $i]);
			$motivo            = "Peça cancelada pelo relatorio de PECAS PENDENTES CRÍTICAS.";
			$distribuidor      = 4311; //Distribuidor Telecontrol
			// ATENCAO - A rotina abaixo pede como parametro o distribuidor, mas não utiliza, mesmo assim estamos enviando 4311 porque e o distribuidor Telecontrol
			$fabrica           = 51; //Gama Italy
			
			$sql_os = "SELECT DISTINCT tbl_os.os ,
					tbl_os.posto,
					tbl_os_item.os_item,
					tbl_os_item.peca,
					tbl_os_item.pedido
					FROM tbl_os
					JOIN tbl_posto_fabrica         ON tbl_posto_fabrica.fabrica = tbl_os.fabrica and tbl_posto_fabrica.posto = tbl_os.posto
					JOIN tbl_os_produto USING (os)
					JOIN tbl_os_item    USING (os_produto)
					JOIN tbl_peca       USING (peca)
					LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_os_item.pedido AND tbl_faturamento_item.peca = tbl_os_item.peca AND tbl_faturamento_item.os_item = tbl_os_item.os_item
					LEFT JOIN tbl_pedido_item      ON tbl_pedido_item.pedido = tbl_os_item.pedido 
												  AND tbl_pedido_item.peca   = tbl_os_item.peca
												  AND tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
					LEFT JOIN tbl_pedido           ON tbl_pedido.pedido = tbl_pedido_item.pedido
					LEFT JOIN tbl_faturamento      ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
					JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
					WHERE tbl_os.fabrica = 51
					AND tbl_faturamento.nota_fiscal IS NULL
					AND tbl_os_item.pedido IS NOT NULL
					AND tbl_pedido_item.qtde_cancelada < tbl_pedido_item.qtde
					AND tbl_os.troca_garantia is not true
					AND tbl_os.os = $os";
			//echo $sql_os;
			$res_os = pg_exec($con,$sql_os);
			if(pg_numrows($res_os)>0){
				for($j=0; $j<pg_numrows($res_os); $j++){
					$os        = trim(pg_result($res_os,$j,os));
					$os_item   = trim(pg_result($res_os,$j,os_item));
					$posto     = trim(pg_result($res_os,$j,posto));
					$peca      = trim(pg_result($res_os,$j,peca));
					$pedido    = trim(pg_result($res_os,$j,pedido));

					$sql = "SELECT count(*) as ja
							FROM tbl_pedido_cancelado
							WHERE pedido = $pedido
							AND posto = $posto
							AND fabrica = 51
							AND os = $os
							AND peca = $peca";
					//echo $sql;
					$res = pg_exec($con, $sql);
					$ja = 0;
					if(pg_numrows($res)>0){
						$ja = pg_result($res,0,ja);

						$sql = "SELECT sum(tbl_os_item.qtde) as qtde_a_cancelar
								FROM tbl_os
								JOIN tbl_posto_fabrica         ON tbl_posto_fabrica.fabrica = tbl_os.fabrica and tbl_posto_fabrica.posto = tbl_os.posto
								JOIN tbl_os_produto USING (os)
								JOIN tbl_os_item    USING (os_produto)
								JOIN tbl_peca       USING (peca)
								LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_os_item.pedido AND tbl_faturamento_item.peca = tbl_os_item.peca AND tbl_faturamento_item.os_item = tbl_os_item.os_item
								LEFT JOIN tbl_pedido_item      ON tbl_pedido_item.pedido = tbl_os_item.pedido 
								AND tbl_pedido_item.peca   = tbl_os_item.peca
								AND tbl_pedido_item.pedido_item = tbl_os_item.pedido_item
								LEFT JOIN tbl_pedido           ON tbl_pedido.pedido = tbl_pedido_item.pedido
								LEFT JOIN tbl_faturamento      ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
								JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
								WHERE tbl_os.os = $os and tbl_os_item.peca = $peca";
								$res = pg_exec($con, $sql);
						if(pg_numrows($res)>0){
							$qtde_a_cancelar = pg_result($res,0,qtde_a_cancelar);
						}
						if($qtde_a_cancelar>$ja){
							$ja= 0;
						}
					}
					if($ja==0){
						$sql ="SELECT fn_pedido_cancela_garantia($distribuidor,51,$pedido,$peca,$os_item, '$motivo',$login_admin)";
						//echo $sql;
						$res = pg_exec ($con,$sql);
					}
				}
				$data_fechamento   = trim($_POST['data_fechamento_'. $i]);
				//echo $sql; echo $data_fechamento; echo strlen($data_fechamento); exit;
				if(strlen($data_fechamento)==0){
					$sql = "INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,62,current_timestamp,'Peça crítica cancelada pelo relatorio PECAS PENDENTES CRITICAS e necessita de troca.')";
					//echo $sql;
					$res = pg_exec ($con,$sql);
					// envia email teste para avisar
					$email_origem  = "helpdesk@telecontrol.com.br";
					$email_destino = "samuel@telecontrol.com.br, samueltelecontrol@gmail.com";
					$assunto       = "Peça crítica cancelada pelo relatorio PECAS PENDENTES CRITICAS e necessita de troca.";
					$corpo         ="OS: $os \n SELECT fn_pedido_cancela_garantia($distribuidor,51,$pedido,$peca,$os_item,$motivo,$login_admin) \n INSERT INTO tbl_os_status (os,status_os,data,observacao) values ($os,62,current_timestamp,'Peça crítica cancelada pelo relatorio PECAS PENDENTES CRITICAS e necessita de troca.')";
					@mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem);
					// fim
				}
			}
		}
	}
}

if(strlen($ate) == 0) $ate = 99999;
if(strlen($msg_erro)>0){
	echo "<table width='700' border='0' cellpadding='5' cellspacing='1' align='center'>";
		echo "<tr>";
			echo "<td class='Erro'>$msg_erro</td>";
		echo "</tr>";
	echo "</table>";
}

?>
<FORM name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center'>
<BR>
<?
if(strlen($referencia) == 0 and strlen($peca) == 0) { ?>
<table width='500' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'>Relatório de Peças Pendentes Críticas para Troca</td>
	</tr>

	<tr>
		<td bgcolor='#DBE5F5'>
			<?if($btn_acao!="Consultar"){
				?>
				<table width='90%' border='0' cellspacing='1' cellpadding='2' class='Conteudo' align='center'>
					<tr class="Conteudo" bgcolor="#D9E2EF">
						<td align='center'>Quantidade de peças&nbsp;</td>
					</tr>
					<tr bgcolor="#D9E2EF">
						<td align='center'>
							<input type="text" name="ate" id="ate" size="5" maxlength="5" class='Caixa' value="<? echo $ate?>">

						</td>
					</tr>
				</table>
				<center><br><input type='submit' name='btn_acao' value='Consultar'></center>
				<?}?>
		</td>
	</tr>
</table>
<?}
if($btn_acao=="Consultar" AND strlen($msg_erro) == 0){
	$sql = "
			/*SELECT tbl_os_item.peca, tbl_os_item.digitacao_item
			into temp table tmp_os_fechada_pedido_pendente
			FROM tbl_os_item 
			JOIN tbl_os_produto on tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN tbl_os         on tbl_os.os = tbl_os_produto.os
			LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_os_item.pedido AND tbl_faturamento_item.peca = tbl_os_item.peca and tbl_faturamento_item.os_item = tbl_os_item.os_item
			LEFT JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_os_item.pedido AND tbl_pedido_item.peca = tbl_os_item.peca
			LEFT JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
			WHERE tbl_os_item.fabrica_i = 51
			AND tbl_faturamento.nota_fiscal IS NULL
			AND tbl_os_item.pedido IS NOT NULL
			AND tbl_os.troca_garantia is not true
			AND tbl_pedido_item.qtde_cancelada < tbl_pedido_item.qtde;
			*/

			SELECT tbl_os_item.peca, tbl_os_item.digitacao_item
			into temp table tmp_os_fechada_pedido_pendente
			FROM tbl_os
			JOIN tbl_os_produto USING (os)
			JOIN tbl_os_item USING (os_produto)
			JOIN tbl_peca USING (peca)
			LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_os_item.pedido AND tbl_faturamento_item.peca = tbl_os_item.peca and tbl_faturamento_item.os_item = tbl_os_item.os_item
			LEFT JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_os_item.pedido AND tbl_pedido_item.peca = tbl_os_item.peca and tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
			LEFT JOIN tbl_pedido      ON tbl_pedido.pedido      = tbl_pedido_item.pedido
			LEFT JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
			WHERE tbl_os.fabrica = 51
			AND tbl_faturamento.nota_fiscal IS NULL
			AND tbl_os_item.pedido IS NOT NULL
			AND tbl_pedido_item.qtde_cancelada < tbl_pedido_item.qtde
			AND tbl_os.troca_garantia is not true;
			
			select peca, digitacao_item, referencia 
			from tmp_os_fechada_pedido_pendente join tbl_peca using(peca)
			order by digitacao_item, peca";
	$res = pg_exec($con , $sql);
	// ##### PAGINACAO ##### //

	if (pg_numrows($res) > 0) {
		$array_peca[] = "";
		if (pg_numrows($res)<11){
			$ate_x = pg_numrows($res);
		}else{
			$ate_x = $ate;
		}
		echo "<br><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' >";
		echo "<tr class='Titulo' height='25' background='imagens_admin/azul.gif'>";
		echo "<td >peca</td>";
		echo "</tr>";
		$cont = 0;
		for ($n=0; $n<pg_numrows($res); $n++){
			$peca        = trim(pg_result($res,$n,peca));
			$referencia  = trim(pg_result($res,$n,referencia));
			if($array_peca[$peca] == $peca){
				continue;
			}
			$array_peca[$peca]  = $peca;
			$cont = $cont + 1;
			if($cont > $ate){
				break;
			}
			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			echo "<tr class='Conteudo2'>";
			echo "<input type='hidden' name='peca' value=$peca>";
			echo "<td bgcolor='$cor' ><A HREF='relatorio_peca_pendente_gama_troca.php?peca=$peca&referencia=$referencia&mostrar=mostrar' target='_blank'>$referencia</A></td>";
			echo "</tr>";
		}
	}
}

if(strlen($referencia) > 0 and strlen($peca) > 0 and strlen($mostrar)>0) {
	$sql_peca = "SELECT DISTINCT tbl_os.os ,
						tbl_os.sua_os ,
						tbl_os.posto ,
						to_char (tbl_os_item.digitacao_item,'DD/MM/YY') AS digitacao_item,
						to_char (tbl_os.data_fechamento,'DD/MM/YY') AS data_fechamento,
						to_char (tbl_os.data_abertura,'DD/MM/YY') AS data_abertura,
						tbl_os_item.digitacao_item AS digitacao,
						tbl_faturamento.nota_fiscal,
						tbl_peca.referencia,
						tbl_pedido.distribuidor,
						tbl_peca.peca,
						tbl_peca.referencia,
						tbl_os_item.pedido,
						tbl_pedido_item.pedido_item,
						tbl_os_item.os_item
						FROM tbl_os
						JOIN tbl_os_produto USING (os)
						JOIN tbl_os_item USING (os_produto)
						JOIN tbl_peca USING (peca)
						LEFT JOIN tbl_faturamento_item ON tbl_faturamento_item.pedido = tbl_os_item.pedido AND tbl_faturamento_item.peca = tbl_os_item.peca and tbl_faturamento_item.os_item = tbl_os_item.os_item
						LEFT JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_os_item.pedido AND tbl_pedido_item.peca = tbl_os_item.peca and tbl_os_item.pedido_item = tbl_pedido_item.pedido_item
						LEFT JOIN tbl_pedido      ON tbl_pedido.pedido      = tbl_pedido_item.pedido
						LEFT JOIN tbl_faturamento ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
						WHERE tbl_os.fabrica = 51
						AND tbl_faturamento.nota_fiscal IS NULL
						AND tbl_os_item.pedido IS NOT NULL
						AND tbl_pedido_item.qtde_cancelada < tbl_pedido_item.qtde
						AND tbl_os_item.peca = $peca
						AND tbl_os.troca_garantia is not true
						ORDER BY digitacao ASC";
	//if ($ip=='201.76.66.123') echo nl2br($sql_peca);
	$res_peca = pg_exec($con, $sql_peca);

	echo "<br>
		<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center'>";
		echo "<tr class='Titulo' height='25' background='imagens_admin/azul.gif'>";
			echo "<td colspan='7' align='left'>";
			?>
			<input type='hidden' name='qtde_os' value='<? echo pg_numrows ($res_peca); ?>'>

			<input type='checkbox' class='frm' name='marcar' value='tudo' title='<? echo "Selecione ou desmarque todos"; ?>' onClick='SelecionaTodos(this.form.ativo);' style='cursor: hand;'>	Clique aqui para marcar todos, ou desmarcar todos.
			<?
			echo "</td>";
		echo "</tr>";
		echo "<tr class='Titulo' height='25' background='imagens_admin/azul.gif'>";
			echo "<td >Check</td>";
			echo "<td >peca</td>";
			echo "<td >Pedido</td>";
			echo "<td >OS</td>";
			echo "<td >Abertura</td>";
			echo "<td >Fechamento</td>";
			echo "<td >POSTO</td>";
		echo "</tr>";
		for ($i=0; $i<pg_numrows($res_peca) ; $i++){
			$os              = trim(pg_result($res_peca,$i,os));
			$posto           = trim(pg_result($res_peca,$i,posto));
			$sua_os          = trim(pg_result($res_peca,$i,sua_os));
			$digitacao_item  = trim(pg_result($res_peca,$i,digitacao_item));
			$referencia      = trim(pg_result($res_peca,$i,referencia));
			$distribuidor    = trim(pg_result($res_peca,$i,distribuidor));
			$pedido          = trim(pg_result($res_peca,$i,pedido));
			$peca            = trim(pg_result($res_peca,$i,peca));
			$os_item         = trim(pg_result($res_peca,$i,os_item));
			$data_abertura   = trim(pg_result($res_peca,$i,data_abertura));
			$data_fechamento = trim(pg_result($res_peca,$i,data_fechamento));

			if(strlen($posto)>0){
				$sqlP = "SELECT nome AS posto_nome ,
								codigo_posto
						 FROM  tbl_posto_fabrica
						 JOIN  tbl_posto USING(posto)
						 WHERE tbl_posto_fabrica.posto   = $posto
						 AND   tbl_posto_fabrica.fabrica = $login_fabrica ";
				$resP = pg_exec($con, $sqlP);

				if(pg_numrows($resP) > 0){
					$codigo_posto   = trim(pg_result($resP,0,codigo_posto));
					$posto_nome     = trim(pg_result($resP,0,posto_nome));
				}
			}

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			echo "<tr class='Conteudo2'>";
			echo "<td bgcolor='$cor'>";
			?>
			<input type="hidden" name="distribuidor_<?echo $i?>" value="<?echo $distribuidor?>">
			<input type="hidden" name="pedido_<?echo $i?>" value="<?echo $pedido?>">
			<input type="hidden" name="peca_<?echo $i?>" value="<?echo $peca?>">
			<input type="hidden" name="os_item_<?echo $i?>" value="<?echo $os_item?>">
			<input type="hidden" name="data_fechamento_<?echo $i?>" value="<?echo $data_fechamento?>">
			<input type="hidden" name="os_<?echo $i?>" value="<?echo $os?>">
			<input type="checkbox" class="frm" name="ativo_<?echo $i?>" id="ativo" value="t">	<?
			echo "</td>";
			echo "<td bgcolor='$cor' >$referencia</td>";
			echo "<td bgcolor='$cor' >$digitacao_item</td>";
			echo "<td bgcolor='$cor' ><A HREF='os_press.php?os=$os' target='_blank'>$sua_os</A></td>";
			echo "<td bgcolor='$cor' >$data_abertura</td>";
			echo "<td bgcolor='$cor' >$data_fechamento</td>";
			echo "<td bgcolor='$cor' align='left' nowrap>$codigo_posto - $posto_nome</td>";
			echo "</tr>";
		}
		echo "<tr class='Conteudo2'>";
			echo "<td bgcolor='$cor' colspan='7' align='center'><b><font color='red'>Esta ação irá cancelar os pedidos pendentes das Ordens de Serviços FECHADAS e SELECIONADAS,<br> e colocar em intervenção para TROCA as Ordens de Serviços em ABERTO</b></font>";
				echo "<center><br>
				<input type='submit' name='btn_acao' value='Efetivar Troca'>
				</center><br>";
			echo "</td>";
		echo "</tr>";
	echo "</table>";
}
include 'rodape.php';
?>
