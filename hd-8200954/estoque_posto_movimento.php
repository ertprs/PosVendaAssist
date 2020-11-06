<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

?>

<style type='text/css'>
.menu_top {
	
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 0px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line1 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.titulo_tabela{
background-color:#596d9b;
font: bold 14px 'Arial';
color:#FFFFFF;
text-align:center;
}


.titulo_coluna{
background-color:#596d9b;
font: bold 11px 'Arial';
color:#FFFFFF;
text-align:center;
}

.msg_erro{
background-color:#FF0000;
font: bold 16px 'Arial';
color:#FFFFFF;
text-align:center;
margin: 0 auto;
}
.texto_avulso{
       font: 14px Arial; color: rgb(89, 109, 155);
       background-color: #d9e2ef;
       text-align: center;
       width:700px;
       margin: 0 auto;
}
.formulario{
background-color:#D9E2EF;
font:11px Arial;
}

.subtitulo{
background-color: #7092BE;
font:bold 11px Arial;
color: #FFFFFF;
}

table.tabela tr td{
font-family: verdana;
font-size: 11px;
border-collapse: collapse;
border:1px solid #596d9b;
}

.msg_sucesso{
background-color: green;
font: bold 16px 'Arial';
color: #FFFFFF;
text-align:center;
margin: 0 auto;
}
.frm {background-color:#F0F0F0;border:1px solid #888888;font-family:Verdana;font-size:8pt;font-weight:bold;}
</style>

<?
$ajax_acerto = $_GET['ajax_acerto'];
if(strlen($ajax_acerto)==0){$ajax_acerto = $_POST['ajax_acerto'];}
if(strlen($ajax_acerto)>0){
	$peca  = $_GET['peca'];
	$posto = $_GET['posto'];
	$btn_acao = trim($_POST['btn_acao']);
	$hoje = date("d/m/Y");
	if(strlen($btn_acao)>0){
		$data_acerto = $_POST['data_acerto'];
		$qtde_acerto = $_POST['qtde_acerto'];
		$nf_acerto   = $_POST['nf_acerto'];
		$obs_acerto  = $_POST['obs_acerto'];
		$peca        = $_POST['peca'];
		$posto       = $_POST['posto'];
		$tipo        = $_POST['tipo'];
		if ($login_fabrica == 30){
			$tipo_estoque= $_POST['tipo_estoque'];
		}

		if(strlen($tipo)==0){
			$tipo = "qtde_entrada";
			$operador = " + ";
			$msg_erro = "Por favor, selecione o tipo de movimentação(Entrada ou Saída)";
		}else{
			if($tipo == "E"){$tipo = "qtde_entrada"; $operador = " + ";}
			if($tipo == "S"){$tipo = "qtde_saida"; $operador = " - ";}
		}
		
		if ($login_fabrica == 30 && !$tipo_estoque){
			$msg_erro = "Por favor, selecione o tipo de estoque(Venda ou Antecipação)";
		}
		

		$data_acerto = fnc_formata_data_pg($data_acerto);
		if(strlen(trim($obs_acerto))==0){
			$msg_erro = "Por favor, informar a observação";
		}else{
			$obs_acerto = "'". $obs_acerto . "'";
		}
		
		$nf_acerto = (strlen($nf_acerto)==0) ? "null" : "'". $nf_acerto . "'";

		if(strlen($qtde_acerto)==0) $msg_erro = "Favor informar quantidade";

		if(strlen($msg_erro)==0){
			$sql = "INSERT INTO tbl_estoque_posto_movimento(
								fabrica      , 
								posto        , 
								peca         , 
								$tipo        , 
								data         , 
								obs          ,
								nf           , ";
			if ($login_fabrica==30){
			$sql .="
								tipo         , 
			";
			}
			$sql .= "
								admin
								)values(
								$login_fabrica,
								$posto        ,
								$peca         ,
								$qtde_acerto  ,
								$data_acerto  ,
								$obs_acerto   ,
								$nf_acerto    ,";
			if ($login_fabrica==30){
				$sql .="
								'$tipo_estoque',
				";
			}
			
			$sql .= "
								$login_posto
						)";
					
			$res = pg_query($con,$sql);
			$msg_erro .= pg_errormessage($con);
			if(strlen($msg_erro)==0){
				$sql = "SELECT peca 
						FROM tbl_estoque_posto 
						WHERE peca = $peca 
						AND posto = $posto 
				";
				if ($login_fabrica==30){
					$sql .= "AND tipo = '$tipo_estoque'";
				}
				
				$sql .= "
						AND fabrica = $login_fabrica;";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res)>0){
					$sql = "UPDATE tbl_estoque_posto set 
							qtde = qtde $operador $qtde_acerto
							WHERE peca  = $peca
							AND posto   = $posto";
				if ($login_fabrica==30){
					$sql .= "AND tipo = '$tipo_estoque'";
				}
				
				$sql .= "
							AND fabrica = $login_fabrica;";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}else{
					$sql = "INSERT into tbl_estoque_posto(fabrica, posto, peca, qtde,tipo)
							values($login_fabrica,$posto,$peca,$qtde_acerto,'$tipo_estoque')";
					$res = pg_query($con,$sql);
					$msg_erro .= pg_errormessage($con);
				}
			}
		}
		echo (strlen($msg_erro) > 0) ? "<div class='msg_erro'>$msg_erro</div><br>" : "<div class='msg_sucesso'>Atualizado com sucesso!</div>";
	}

	if(strlen($peca)>0 and strlen($posto)>0 ){
		$sql = "
			SELECT tbl_peca.referencia as peca_referencia,
				tbl_peca.descricao  as peca_descricao    ,
				tbl_posto.nome as nome_posto             ,
				tbl_posto_fabrica.codigo_posto           ,
				tbl_estoque_posto.qtde
			FROM tbl_estoque_posto
			JOIN tbl_posto on tbl_estoque_posto.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto
			AND  tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_peca on tbl_estoque_posto.peca = tbl_peca.peca
			WHERE tbl_estoque_posto.fabrica = $login_fabrica
			AND   tbl_estoque_posto.posto = $posto
			AND   tbl_estoque_posto.peca = $peca
			";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0 ){
			$peca_referencia = pg_fetch_result($res,0,peca_referencia);
			$peca_descricao  = pg_fetch_result($res,0,peca_descricao);
			$nome_posto      = pg_fetch_result($res,0,nome_posto);
			$codigo_posto    = pg_fetch_result($res,0,codigo_posto);
			$qtde            = pg_fetch_result($res,0,qtde);
			if($qtde<0){
				$xqtde = $qtde * -1;
			}else{
				$xqtde = $qtde;
			}
		}
		else {
			$sql = "SELECT tbl_peca.referencia,
			               tbl_peca.descricao ,
						   tbl_posto.nome     ,     
						   tbl_posto_fabrica.codigo_posto
						 FROM  tbl_peca
						 JOIN  tbl_posto_fabrica USING(fabrica)
						 JOIN  tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
						 WHERE tbl_posto.posto = $posto
						 AND   tbl_posto_fabrica.fabrica = $login_fabrica
						 AND   tbl_peca.peca = $peca
						 AND   tbl_peca.fabrica = $login_fabrica
						   ";
			$res = pg_query($con,$sql);
			//echo nl2br($sql); 
			$peca_referencia = pg_fetch_result($res,0,referencia);
			$peca_descricao  = pg_fetch_result($res,0,descricao);
			$nome_posto      = pg_fetch_result($res,0,nome);
			$codigo_posto    = pg_fetch_result($res,0,codigo_posto);
			$xqtde           = 0;
		}

		if(strlen($msg_erro)>0){
			$xqtde = $_POST['qtde_acerto'];
			$tipo = $_POST['tipo'];
		}
		
		
		?>
		<script language='javascript' src='ajax.js'></script>
		<script type='text/javascript' src='js/jquery.js'></script>
		<script type='text/javascript' src='js/jquery.alphanumeric.js'></script>
		<script type='text/javascript'>
			$(function(){
				$("#qtde_acerto").numeric();
			});
		</script>
		<?
		echo "<table cellpadding='3' cellspacing='1' width='700px%' align='center' class='formulario'>";
			echo "<tr class='titulo_tabela'>";
			echo "<td>Posto: <B>$codigo_posto</B></td><td><b> $nome_posto</B> </td>";
			echo "<td>Peça: <B>$peca_referencia</B></td><td>$peca_descricao</B> </td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td class='subtitulo' colspan='4'>Qtde Estoque: <B>$qtde</b></td>";
			echo "</tr>";
			echo "</table>";
			echo "<form name='frm_acerto' method='post' action='$PHP_SELF'>";
			echo "<table cellpadding='3' cellspacing='1' class='formulario' width='100%' align='center' border='0'>";
			echo "<tr>";
			echo "<td colspan='3' class='subtitulo' align='center'>Para acertar o estoque do posto basta inserir uma nova movimentação com os valores abaixo:</td>";
			echo "</tr>";
			echo "<tr><td width='10px'>&nbsp;</td>";
			echo "<td><B>Peça: </B>$peca_referencia - $peca_descricao </td>";
			echo "<td><B>Data: </B>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='text' name='data_acerto' size='10' maxlength='10' value='$hoje' class='frm'></td>";
			echo "</tr>";
			echo "<tr><td width='10px'>&nbsp;</td>";
			echo "<td><B>Qtde Estoque: </B> <input type='text' name='qtde_acerto' id='qtde_acerto' size='4' maxlength='4' value='$xqtde' class='frm'></td>";
			echo "<td><B>Nota Fiscal: </B> <iinput type=\"button\" style=\"background:url(imagens_admin/btn_gravar.gif); width:75px; cursor:pointer;\" value=\"&nbsp;\" name='nf_acerto' size='10' maxlength='20' value='$qtde_acerto' class='frm'></td>";
			echo "</tr>";
			#HD 159888
			
			if ($login_fabrica <> 30 ){
				echo "<tr>";
					echo "<td width='10px'>&nbsp;</td>";
					echo "<td colspan='2'>";
						echo "<fieldset style='width:120px'>";
							echo "<legend>Tipo</legend>";
							echo "<input type='radio' name='tipo' value='E'";
							if($tipo=="E") echo "checked";
							echo "> Entrada";
							
							echo "<input type='radio' name='tipo' value='S'";
							if($tipo=="S") echo "checked";
							echo "> Saída";
						echo "</fieldset>";
					echo "</td>";
				echo "</tr>";
				
			} else {
			echo "</table>";
			echo "<table cellpadding='3' cellspacing='1' class='formulario' width='100%' align='center' border='0'>";
				echo "<tr><td width='10px'>&nbsp;</td>";
				
					echo "<td colspan='2'>";
						
						echo "<fieldset style='width:150px'>";
							echo "<legend>Tipo de Estoque</legend>";
							echo "<input type='radio' name='tipo_estoque' id='tipo_estoque_venda' value='venda' />
									<label for='tipo_estoque_venda' style='cursor:pointer;'>Venda</label>";
								
							echo "<input type='radio' name='tipo_estoque' id='tipo_estoque_antecipacao' value='antecipada' />
									<label for='tipo_estoque_antecipacao' style='cursor:pointer;'>Antecipação</label>";
						echo "</fieldset>";					
					echo "</td>";
					
					echo "<td>";
						
						echo "<fieldset style='width:110px'>";
							echo "<legend>Tipo de Movimentação</legend>";

							echo "<input type='radio' name='tipo' id='tipo_entrada' value='E'";
							if($tipo=="E") echo "checked";echo ">";
							echo "<label for='tipo_entrada' style='cursor:pointer;'>Entrada</label>";
						
							echo "<input type='radio' name='tipo' id='tipo_saida' value='S'";
							if($tipo=="S") echo "checked";echo ">";
							echo "<label for='tipo_saida' style='cursor:pointer'>Saída</label>";

						echo"</fieldset>";
						
					echo "</td>";
					
				echo "</tr>";
				
				echo "</table>";
				
				
				echo "<table cellpadding='3' cellspacing='1' class='formulario' width='100%' align='center' border='0'>";
			}

			echo "<tr><td width='10px'>&nbsp;</td>";
			echo "<td colspan='3' align='center'><B>Observação: </B><BR><TEXTAREA NAME='obs_acerto' ROWS='5' COLS='50'  class='frm'>$obs_acerto</TEXTAREA>";
			echo "<input type='hidden' name='posto' value='$posto'>";
			echo "<input type='hidden' name='peca' value='$peca'>";
			echo "<input type='hidden' name='btn_acao' value=''>";
			echo "<input type='hidden' name='ajax_acerto' value='true'>";
			echo "<BR><BR><input type='button' value='Gravar' onclick=\"javascript: if (document.frm_acerto.btn_acao.value == '' ) { document.frm_acerto.btn_acao.value='gravar' ; document.frm_acerto.submit() } else { alert ('Aguarde ') }\" ALT=\"Gravar itens da Ordem de Serviço\" border='0' style=\"cursor:pointer;\">";
			echo "</td>";
			echo "</tr>";
		echo "</table>";
		echo "</form>";
	}
	exit;
}

$ajax = $_GET['ajax'];
if(strlen($ajax)>0){

	$peca         = $_GET['peca'];
	$posto        = $_GET['posto'];
	$tipo         = $_GET['tipo'];
	$data_inicial = $_GET['data_inicial'];
	$data_final   = $_GET['data_final'];

	if($login_fabrica == 3){
        $sqlTipo = "AND tbl_estoque_posto_movimento.tipo IS NULL";
	}else{
        $sqlTipo = "AND tbl_estoque_posto_movimento.tipo = '$tipo'";
	}

	if(strlen($peca)>0){
		$sql = "SELECT 	tbl_estoque_posto_movimento.peca                              , 
						tbl_os.sua_os                                                 ,
						tbl_os_excluida.sua_os as sua_os_excluida                     ,
						tbl_estoque_posto_movimento.os                                , 
						to_char(tbl_estoque_posto_movimento.data,'DD/MM/YYYY') as data,
						tbl_estoque_posto_movimento.qtde_entrada                      , 
						tbl_estoque_posto_movimento.qtde_saida                        , 
						tbl_estoque_posto_movimento.admin                             ,
						tbl_estoque_posto_movimento.pedido                            , 
						tbl_estoque_posto_movimento.obs                               ,
						tbl_estoque_posto_movimento.tipo							  ,
						tbl_estoque_posto_movimento.nf
				FROM  tbl_estoque_posto_movimento 
				LEFT  JOIN tbl_os ON tbl_estoque_posto_movimento.os = tbl_os.os and tbl_os.fabrica = $login_fabrica
				LEFT  JOIN tbl_os_excluida ON tbl_os_excluida.os = tbl_estoque_posto_movimento.os
				WHERE tbl_estoque_posto_movimento.posto   = $posto 
				AND   tbl_estoque_posto_movimento.peca    = $peca
				AND   tbl_estoque_posto_movimento.fabrica = $login_fabrica 
				$sqlTipo
				AND   (tbl_estoque_posto_movimento.qtde_entrada > 0 OR tbl_estoque_posto_movimento.qtde_entrada IS NULL)
				ORDER BY tbl_estoque_posto_movimento.data /*,
				tbl_estoque_posto_movimento.data,
				tbl_estoque_posto_movimento.qtde_saida,
				tbl_estoque_posto_movimento.os HD 151164 */";
				/* hd 151164 tirei a ordenação da movimentação por data, e deixei para mostrar na sequencia que foi inserida a movimentação no sistema. Então dá para você ver que está correta, ou seja, saiu uma vez na baixa, entrou uma vez na recusa, e saiu novamente na outra baixa. ass Samuel*/
// 		echo nl2br($sql);
		$res = pg_query($con,$sql);
		# HD 5630 -> AND   (tbl_estoque_posto_movimento.qtde_entrada > 0 OR tbl_estoque_posto_movimento.qtde_entrada IS NULL)
		//	AND   tbl_estoque_posto_movimento.data between '$data_inicial' and '$data_final' 
		if(pg_num_rows($res)>0){
			echo "<table border='0' cellpadding='3' cellspacing='1' width='700px' class='tabela' align='center'>";
			echo "<tr class='titulo_coluna'>";
			echo "<td>Movimentação</td>";
			if ($login_fabrica==30) {
				echo	"<td>Tipo de Estoque</td>";
			} 
			echo "<td>Data</td>";
			echo "<td>Entrada</td>";
			echo "<td>Saída</td>";
			echo ($login_fabrica == 3) ? "<td>Nota Fiscal</td>":"<td>Pedido</td>";
			echo "<td>OS</td>";
			echo "<td>Observação</td>";
			echo "</tr>";
			
			for($i=0; pg_num_rows($res)>$i;$i++){
			
				$os              = pg_fetch_result ($res,$i,os);
				$sua_os          = pg_fetch_result ($res,$i,sua_os);
				$sua_os_excluida = pg_fetch_result ($res,$i,sua_os_excluida);
				$data            = pg_fetch_result ($res,$i,data);
				$qtde_entrada    = pg_fetch_result ($res,$i,qtde_entrada);
				$qtde_saida      = pg_fetch_result ($res,$i,qtde_saida);
				$admin           = pg_fetch_result ($res,$i,admin);
				$obs             = pg_fetch_result ($res,$i,obs);
				$nf              = pg_fetch_result ($res,$i,nf);
				$pedido          = pg_fetch_result ($res,$i,pedido);
				$tipo            = pg_fetch_result ($res,$i,tipo);
				$saida_total  = $saida_total + $qtde_saida;
				$entrada_total = $entrada_total + $qtde_entrada;

				$movimentacao = ($qtde_entrada>0) ? "<font color='#35532f'>Entrada</font>" : "<font color='#f31f1f'>Saída</font>";

				$cor = ($i % 2 == 0) ? '#F7F5F0' : "#F1F4FA"; 

				echo "<tr bgcolor='$cor'>";
				echo "<td align='center'>$movimentacao</td>";
				if ($login_fabrica==30) {
					echo "<td align='center'>".ucfirst($tipo)."</td>";
				} 
				echo "<td align='center'>$data</td>";
				echo "<td align='center'>$qtde_entrada &nbsp;</td>";
				echo "<td align='center'>$qtde_saida &nbsp;</td>";
				echo "<td align='center'>";
				echo ($login_fabrica==3) ? $nf :"<a href='pedido_finalizado.php?pedido=$pedido' target='_blank'>$pedido &nbsp;</a>";
				echo "</td>";
				if(strlen($sua_os)==0 and strlen($os)>0){
					echo "<td>$sua_os_excluida &nbsp;</td>";
					echo "<td align='left'>Esta OS foi excluida pelo posto.</td>";
				}else{
					echo "<td><a href='os_press.php?os=$os' target='_blank'>$sua_os &nbsp;</a></td>";
					echo "<td align='left'>$obs &nbsp;</td>";
				}
				echo "</td>";
				echo "</tr>";
				
			}
			#HD 159888 INICIO
			if ($login_fabrica==30){
				
				$cond_os = ($login_fabrica==30) ? "tbl_servico_realizado.troca_de_peca IS TRUE" : "tbl_servico_realizado.peca_estoque IS TRUE";
	
				$sqlOS = "SELECT tbl_os.os                                                     ,
								 tbl_os.sua_os                                                 ,
								 to_char(tbl_os.data_digitacao,'DD/MM/YYYY') as data		   ,
								 tbl_os_item.qtde					                           
						FROM tbl_os
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
						JOIN tbl_os_produto USING(os)
						JOIN tbl_os_item USING(os_produto) 
						JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_os.fabrica = tbl_servico_realizado.fabrica
						WHERE tbl_os.fabrica = $login_fabrica
						AND tbl_os.posto     = $login_posto
						AND tbl_os.data_fechamento IS NULL
						AND tbl_os.excluida IS NOT TRUE
						AND tbl_os_item.servico_realizado IS NOT NULL
						AND $cond_os
						AND tbl_os_item.peca = $peca;";
				#echo nl2br($sqlOS); #exit;
				$resOS = pg_exec($con, $sqlOS);
				if(pg_numrows($resOS)>0){
					$total = $qtde_total;
					$qtde_os_nao_finalizada_total = 0;
					for($y=0; $y<pg_numrows($resOS); $y++){
					
						
						$os                          = pg_result($resOS,$y,os);
						$sua_os                      = pg_result($resOS,$y,sua_os);
						$qtde_os_nao_finalizada      = pg_result($resOS,$y,qtde);
						$data_os_nao_finalizada      = pg_result($resOS,$y,data);
						
						
						$movimentacao = "<font color='#f31f1f'>Saída</font>";
						
						$cor_2 = ($y % 2 == 0) ? '#F1F4FA' : "#F7F5F0";   
				
						echo "<tr bgcolor='$cor_2'>";
							echo "<td align='center'>$movimentacao&nbsp;</td>";
							echo "<td align='center'>Venda</td>";
							echo "<td align='center'>$data_os_nao_finalizada &nbsp;</td>";
							echo "<td> &nbsp;</td>";
							echo "<td align='center'>$qtde_os_nao_finalizada &nbsp;</td>";
							echo "<td>&nbsp;</td>";
							echo "<td><a href='os_press.php?os=$os' target='_blank'>$sua_os</a>&nbsp;</td>";
							echo "<td><font color='#f31f1f'><b>Os Não Finalizada<b></font></td>";
						echo "</tr>";
						
						$qtde_os_nao_finalizada_total += $qtde_os_nao_finalizada;
					}
					
				}
			
			}
			
			if ($login_fabrica==30){
				$total = ($entrada_total - $saida_total)-$qtde_os_nao_finalizada_total;
			}else{
				$total = $entrada_total - $saida_total;
			}
			echo "<tr class='titulo_coluna'>";
			echo "<td colspan='3' align='center'>TOTAL DE PEÇAS USADAS EM OS</td>";
			echo "<td colspan='2' align='center'>";
			echo $total;
			echo "</td>";
			echo "<td  colspan='3' >&nbsp;</td>";
			echo "</tr>";
			echo "</table><BR>";
		}else{
			echo "<BR><center>Nenhum resultado encontrado</center><BR>";
		}	
	}
	exit;
}



$ajax_autorizacao = $_GET['ajax_autorizacao'];
if(strlen($ajax_autorizacao)>0){
	$xpecas_negativas = $_GET['xpecas_negativas'];
	$observacao = $_GET['observacao'];
	$xposto     = $_GET['xposto'];
	$xpecas_negativas = "(".$xpecas_negativas.")";

	$sql = "BEGIN TRANSACTION";
	$res = pg_query($con,$sql);

	if(strlen(trim($observacao))==0) {
		$msg_erro = "Por favor, colocar observação";
		echo "Por favor, colocar observação";
	}
	if(strlen($msg_erro)==0) {
		$sql = "SELECT	peca, 
						posto, 
						(qtde*-1)  as qtde
				from tbl_estoque_posto 
				where peca in $xpecas_negativas
				and posto = $xposto 
				and fabrica = $login_fabrica";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			for($i=0;pg_num_rows($res)>$i;$i++){
				$posto = pg_fetch_result($res,$i,posto);
				$qtde = pg_fetch_result($res,$i,qtde);
				$peca = pg_fetch_result($res,$i,peca);

				$ysql = "INSERT INTO tbl_estoque_posto_movimento(
							fabrica      , 
							posto        , 
							peca         , 
							qtde_entrada   ,
							data, 
							obs,
							admin
							)values(
							$login_fabrica,
							$posto        ,
							$peca         ,
							$qtde         ,
							current_date  ,
							'Automático: $observacao',
							$login_admin
					)";
				$yres = pg_query($con,$ysql);
				$msg_erro .= pg_errormessage($con);
				if(strlen($msg_erro)==0){
					$ysql = "SELECT peca 
							FROM tbl_estoque_posto 
							WHERE peca = $peca 
							AND posto = $posto 
							AND fabrica = $login_fabrica;";
					$yres = pg_query($con,$ysql);
					if(pg_num_rows($res)>0){
						$ysql = "UPDATE tbl_estoque_posto set 
								qtde = qtde + $qtde
								WHERE peca  = $peca
								AND posto   = $posto
								AND fabrica = $login_fabrica;";
						$yres = pg_query($con,$ysql);
						$msg_erro .= pg_errormessage($con);
					}else{
						$ysql = "INSERT into tbl_estoque_posto(fabrica, posto, peca, qtde)
								values($login_fabrica,$posto,$peca,$qtde)";
						$yres = pg_query($con,$ysql);
						$msg_erro .= pg_errormessage($con);
					}
				}
			}
		}
		if (strlen($msg_erro) == 0) {
			$res = pg_query($con,"COMMIT TRANSACTION");
			echo "<span style='background-color: #FF3300;'>Peça(s) aceita(s) com sucesso!</span>";
		}else{
			$res = pg_query($con,"ROLLBACK TRANSACTION");
			echo "<span style='background-color: #FF3300;'>Erro no processo: $msg_erro</span>";
		}
	}
	exit;
}


$layout_menu = "callcenter";
$titulo = "MOVIMENTAÇÃO DE PEÇAS DO POSTO";
$title = "MOVIMENTAÇÃO DE PEÇAS DO POSTO";
echo '<center>';
include 'cabecalho.php';
include "javascript_pesquisas.php"; 
?>
<script language="javascript" src="js/assist.js"></script>
<script language='javascript' src='ajax.js'></script>
<script type="text/javascript" src="js/jquery-1.3.2.js"></script>
<script type="text/javascript" src="js/jquery.corner.js"></script>
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script language="JavaScript">

function fechar(peca){
	if (document.getElementById('dados_'+ n)){
		var style2 = document.getElementById('dados_'+ n); 
		if (style2==false) return; 
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
		}
	}
}
function fnc_pesquisa_peca(campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}
	
	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= campo;
		janela.descricao= campo2;
		janela.focus();
	}
	else
		alert('Preencha toda ou parte da informação para realizar a pesquisa!');
}

function createRequestObject(){
	var request_;
	var browser = navigator.appName;
	if(browser == "Microsoft Internet Explorer"){
		 request_ = new ActiveXObject("Microsoft.XMLHTTP");
	}else{
		 request_ = new XMLHttpRequest();
	}
	return request_;
}
	
function mostraMovimentacao(n,peca,posto,tipo,data_inicial,data_final){
	if (document.getElementById('dados_' + n)){

		if ($('#dados_'+n).is(':visible')){
			$('#dados_'+n).hide();
			$('#linha_'+n).hide();
		}else{
			$('#linha_'+n).show();
			$('#dados_'+n).css({'display':'block'});
			if ($('#linha_'+n).attr('rel') != '1'){
				retornaMovimentacao(n,peca,posto,tipo,data_inicial,data_final);
			}
			$('#linha_'+n).attr('rel','1');
		}

	}
}

function retornaMovimentacao(n,peca,posto,tipo,data_inicial,data_final){

	var curDateTime = new Date();
	$.ajax({
		type: "GET",
		url: "<?=$PHP_SELF?>",
		data: 'ajax=true&peca='+ peca +"&posto=" + posto + "&tipo=" + tipo + "&data_inicial=" + data_inicial + "&data_final="+ data_final+"&data="+curDateTime ,
		beforeSend: function(){
			$('#dados_'+n).html("&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='js/loadingAnimation.gif'> ");
		},
		error: function (){
			$('#dados_'+n).html("erro");
		},
		complete: function(http) {
			results = http.responseText;
			$('#dados_'+n).html(results).css({'z-index':'2'});
		}
	});
}

function acertaEstoque(peca,posto){
	var div = document.getElementById('div_acertaEstoque');
	div.style.display = (div.style.display=="") ? "none" : "";
	acertaEstoque_pop(peca,posto);
}
var http4 = new Array();
function acertaEstoque_pop(peca,posto){

	var curDateTime = new Date();
	http4[curDateTime] = createRequestObject();

	url = "<? $PHP_SELF; ?>?ajax_acerto=true";
	http4[curDateTime].open('get',url);
	var campo = document.getElementById('div_acertaEstoque');
	Page.getPageCenterX();
	campo.style.top = (Page.top + Page.height/2)-160;
	campo.style.left = Page.width/2-220;

	http4[curDateTime].onreadystatechange = function(){
		if(http4[curDateTime].readyState == 1) {
			campo.innerHTML = "<font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http4[curDateTime].readyState == 4){
			if (http4[curDateTime].status == 200 || http4[curDateTime].status == 304){

				var results = http4[curDateTime].responseText;
				$( campo ).html( "<div class='msg_sucesso'>" + results + "</div>" );
				
			}else {
				campo.innerHTML = "Erro";
			}
			//$( campo ).text( results );
		}
	}
	http4[curDateTime].send(null);

}

var Page = new Object();
Page.width;
Page.height;
Page.top;

Page.loadOut = function (){
	document.getElementById('div_acertaEstoque').innerHTML ='';	
}
Page.getPageCenterX = function (){
	var fWidth;
	var fHeight;		
	//For old IE browsers 
	if(document.all) { 
		fWidth = document.body.clientWidth; 
		fHeight = document.body.clientHeight; 
	} 
	//For DOM1 browsers 
	else if(document.getElementById &&!document.all){ 
			fWidth = innerWidth; 
			fHeight = innerHeight; 
		} 
		else if(document.getElementById) { 
				fWidth = innerWidth; 
				fHeight = innerHeight; 		
			} 
			//For Opera 
			else if (is.op) { 
					fWidth = innerWidth; 
					fHeight = innerHeight; 		
				} 
				//For old Netscape 
				else if (document.layers) { 
						fWidth = window.innerWidth; 
						fHeight = window.innerHeight; 		
					}
	Page.width = fWidth;
	Page.height = fHeight;
	Page.top = window.document.body.scrollTop;
}

var http13 = new Array();
function gravaAutorizao(){
	var xpecas_negativas = document.getElementById('xpecas_negativas').value;
	xpecas_negativas = xpecas_negativas.split(",");
	/*for (i=0; i<5;i++){
		alert(xpecas_negativas[i]);
	}*/
	var xposto = document.getElementById('xposto');
	var autorizacao_texto = document.getElementById('autorizacao_texto');
	var curDateTime = new Date();
	http13[curDateTime] = createRequestObject();
//alert(xpecas_negativas.value);
	url = "<? echo $PHP_SELF;?>?ajax_autorizacao=gravar&xpecas_negativas="+xpecas_negativas+"&observacao="+autorizacao_texto.value + "&xposto="+xposto.value;
	http13[curDateTime].open('get',url);

	var campo = document.getElementById('mensagem');

	http13[curDateTime].onreadystatechange = function(){
		if(http13[curDateTime].readyState == 1) {
			campo.innerHTML = "<img src='imagens_admin/carregando_callcenter.gif' border='0'><font size='1' face='verdana'>Aguarde..</font>";
		}
		if (http13[curDateTime].readyState == 4){
			if (http13[curDateTime].status == 200 || http13[curDateTime].status == 304){


				var results = http13[curDateTime].responseText;
				
				var procurar = "Peça";
				var posicao = results.search(procurar);


				if(posicao != -1)
					$( campo ).html( "<div class='msg_sucesso' style='width: 700px'>Peça(s) aceita(s) com sucesso!</div>" );
				else
					$( campo ).html( "<div class='msg_erro' style='width: 700px'>" + results + "</div>" );

				//campo.innerHTML = results;
					
				
			}else {
				campo.innerHTML = "Erro";
			}
		}
	}
	http13[curDateTime].send(null);
}
</script>

<?

echo "<div id='div_acertaEstoque' style='display:none;width:700px; class='formulario'>&nbsp;</div>";
echo "<div id='mensagem' style='width: 700px'></div>";
$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
?>
<br>
<form name='frm_tabela' method='post' action='<?= $PHP_SELF ?>'>
<table cellspacing='1' cellpadding='3'  align='center' width='700px' class='formulario'>
	<tr>
		<td colspan='3' class='titulo_tabela'>Parâmetros de Pesquisa</td>
	</tr>

	<tr><td>&nbsp;</td></tr>

	<tr>
		
		<td width='10%'>&nbsp;</td>
		
		<td style='padding:10px 0 0 0;'>
			
			Referência
			
			<br />
			
			<input class='frm' type='text' name='referencia_peca' value='<?=$referencia?>' size='12' maxlength='20'>
			
			<a href="javascript: fnc_pesquisa_peca (document.frm_tabela.referencia_peca,document.frm_tabela.descricao_peca,'referencia')">
				
				<IMG SRC='imagens/lupa.png' style='cursor : pointer'>
			
			</a>
			
		</td>

		<td style='padding:10px 0 0 0;'>
		
			Descrição 
			
			<br />
			
			<input class='frm' type='text' name='descricao_peca' value='<?=$descricao?>' size='50' maxlength='50'>
			
			<a href="javascript: fnc_pesquisa_peca(document.frm_tabela.referencia_peca,document.frm_tabela.descricao_peca,'descricao')">
				<IMG SRC='imagens/lupa.png' style='cursor : pointer'>
			</a>
		
		</td>
		
	</tr>
	
	<tr><td>&nbsp;</td></tr>

	<tr>
		<td colspan='3' align='center'>
			<input type='submit' name='btn_acao' value='Pesquisar'>
		</td>
	</tr>
	
	<tr><td>&nbsp;</td></tr>
	
</table>
</form>


<?

$btn_acao= $_POST['btn_acao'];
if (strlen($btn_acao)>0){
	$codigo_posto = $login_posto;
	
	if ($codigo_posto){	
		
		$sql = "SELECT posto 
				FROM tbl_posto_fabrica
				WHERE posto = $codigo_posto
				AND fabrica = $login_fabrica";
		// echo nl2br($sql);
		// exit;
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			$posto = pg_fetch_result($res,0,posto);
		}else{
			$msg_erro = "Posto não encontrado";
		}
	
	}
	
	$referencia  = $_POST['referencia'];
	$descricao   = $_POST['descricao'];

	if (strlen($referencia)>0 and strlen($msg_erro)==0){	
		$sql = "SELECT peca
				FROM tbl_peca
				WHERE tbl_peca.fabrica= $login_fabrica
				and tbl_peca.referencia='$referencia'
				AND tbl_peca.ativo = 't'";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			$peca = pg_fetch_result($res,0,peca);
		}else{
			$msg_erro = "Peça não encontrada";
		}

	}

	$cond_1 = (strlen($peca)>0) ? "  tbl_estoque_posto.peca = $peca " : " 1=1 ";
	

	if (strlen($msg_erro)==0){
		
		if ( $login_fabrica==30 )
		{
		
			$sql = "SELECT DISTINCT 
						
						tbl_peca.referencia  ,
						tbl_peca.peca		 ,
						tbl_peca.descricao   ,

						(SELECT tbl_estoque_posto.qtde FROM tbl_estoque_posto
						WHERE tbl_estoque_posto.peca = tbl_peca.peca
						AND tbl_estoque_posto.tipo = 'venda'
						AND tbl_estoque_posto.fabrica = $login_fabrica) AS qtde_venda,
						
						(SELECT tbl_estoque_posto.qtde FROM tbl_estoque_posto
						WHERE tbl_estoque_posto.peca = tbl_peca.peca
						AND tbl_estoque_posto.tipo = 'antecipada'
						AND tbl_estoque_posto.fabrica = $login_fabrica) AS qtde_antecipada
					
					FROM tbl_peca
					JOIN tbl_estoque_posto on tbl_estoque_posto.peca = tbl_peca.peca
					WHERE tbl_estoque_posto.posto = $login_posto
					AND $cond_1
					AND tbl_estoque_posto.fabrica = $login_fabrica
					ORDER BY tbl_peca.descricao;";
		
		}else{
			$sql = "SELECT 	DISTINCT 
						
						tbl_peca.referencia,tbl_peca.peca                   ,
						tbl_peca.descricao                                  ,
						tbl_estoque_posto.tipo                                  		,
						tbl_estoque_posto.qtde                              
					
					FROM tbl_estoque_posto
					JOIN tbl_peca on tbl_estoque_posto.peca = tbl_peca.peca 
					WHERE  tbl_estoque_posto.posto = $login_posto  
					AND $cond_1
					AND tbl_estoque_posto.fabrica = $login_fabrica
					ORDER BY tbl_peca.descricao";
		}
		
	$res = pg_query ($con,$sql);

	if(pg_num_rows($res)>0){
		
		if($login_fabrica==1){
			for($x=0;pg_num_rows($res)>$x;$x++){
				$peca            = pg_fetch_result($res,$x,peca);
				$pecas_negativas[] = $peca;
			}
			echo "<div id='div_estoque' style='margin 0 auto;width:700px;' >";
			echo "<table cellpadding='3' cellspacing='1' align='center' width='700px' class='formulario'>";
			echo "<tr>";
			echo "<td align='center' class='texto_avulso'><strong>Atenção</strong><BR>";
			echo "Para <strong>ACEITAR TODAS</strong> as peças que estão <font color='#FF3300'>negativas</font> do <br />estoque informe o motivo e clique em continuar.<br />";
			echo "<textarea name='autorizacao_texto' class='frm' id='autorizacao_texto' rows='5' cols='40'></textarea>";
			echo "<input type='hidden' name='xposto' id='xposto' value='$login_posto'>";
			echo "<input type='hidden' name='xpecas_negativas' id='xpecas_negativas' value='".implode(",",$pecas_negativas)."'>";
			echo "<br/><br/><input type=\"button\" value=\"Confirmar\" border='0' style='cursor:pointer;' onClick='gravaAutorizao();'></td>";
			echo "</tr>";	
			echo "</table><BR>";
			echo "</div>";
		}

		?><BR><BR>
		<table class='tabela' width="700px" cellspacing="1" cellpadding="3" align='center'>
		<thead>
			<tr class='titulo_coluna'>
				<td>Peça</td>
				<td>Descrição</td>
				<?php if($login_fabrica == 1 OR $login_fabrica == 74){ ?>
					<td>Tipo de Estoque</td>
				<?php } ?>
		<?
		if ($login_fabrica==30){
		?>
				<td>Estoque Faturado</td>
				<td>Estoque de Remessa para Garantia</td>
		<? }else{ ?>
				<td colspan='2'>Saldo</td>
		<? } ?>
			</tr>
		</thead>
		<tbody>
		<?
		for($x=0;pg_num_rows($res)>$x;$x++){
			$peca            	= pg_fetch_result($res,$x,peca);
			$peca_referencia 	= pg_fetch_result($res,$x,referencia);
			$peca_descricao  	= pg_fetch_result($res,$x,descricao);
			$tipo_estoque  		= pg_fetch_result($res,$x,tipo);
			
			if ($login_fabrica == 30){
				$qtde_venda            = pg_fetch_result($res,$x,qtde_venda);
				$qtde_antecipada       = pg_fetch_result($res,$x,qtde_antecipada);
			
				if ($qtde_venda and !$qtde_antecipada){
					$qtde_antecipada = 0;
				}else if (!$qtde_venda and $qtde_antecipada){
					$qtde_venda = 0;
				}
			
			}else{
				$qtde            = pg_fetch_result($res,$x,qtde);
			}
			
			
			$cor = ($x % 2 ==0) ? "#F7F5F0" : "#F1F4FA";
			if($qtde > -20 and $login_fabrica == 1)$cor = "#FF9933";
		?>
			<tr bgcolor='<? echo $cor;?>'>
				<td align='left'>
				
				<? echo 
						"<a href=\"javascript:mostraMovimentacao($x,$peca,$login_posto,'$tipo_estoque','$data_inicial','$data_final');\">$peca_referencia</a>
							
							<input type='hidden' id='peca_$x' name='peca_$x' value='$peca;'>
						</td>
						<td><a href=\"javascript:mostraMovimentacao($x,$peca,$login_posto,'$tipo_estoque','$data_inicial','$data_final');\"> $peca_descricao</a></td>";
				?>

				<?php if($login_fabrica == 1 OR $login_fabrica == 74){ 
					$tipo_estoque = ($tipo_estoque == "estoque") ? "Estoque" : "Pulmão";
					?>
					<td><?=$tipo_estoque?></td>
				<?php } ?>
				
				<?if ( $login_fabrica == 30 ){
				?>
					<td align='center'> <? echo $qtde_venda; ?>&nbsp; </td>
				<?
				}else {
				?>
					<td align='center' colspan='2'> <? echo $qtde; ?> &nbsp;</td>
					<input type='hidden' id='qtde_pendente_<? echo $x; ?>' name='qtde_pendente_<? echo $x; ?>' value='<? echo $qtde; ?>'>
				<?}?>
			
				<?
				if ($login_fabrica == 30){
				?>
					<td align='center'><?echo $qtde_antecipada?>&nbsp;</td>
				<?
				}
				?>
				
				
			</tr>
			<?
			echo "<tr id='linha_$x' rel='' style='display:none;'>";
				echo "<td colspan='100%'>";
					echo "<div id='dados_$x' style='display:none;border: 1px solid #949494;'></div>";
				echo "</td>";
			echo "</tr>";
			?>
		<? 
			}
		echo "</tbody>";
		echo "</table>";
	} else{ 
	?>	
		<table class="msg_erro" width="700px">
			<tr>
				<td>Não foi encontrado nenhum registro para a peça pesquisada</td>
			</tr>
		</table>
		
	<?
	}
}
 else {
?>
	<div class="msg_erro" id="msg_erro" style="display:none;"><?=$msg_erro?></div>
	<script type="text/javascript">
		$("#msg_erro").appendTo("#mensagem").fadeIn("slow");
	</script>
<?
 }
}
include "rodape.php";


?>
