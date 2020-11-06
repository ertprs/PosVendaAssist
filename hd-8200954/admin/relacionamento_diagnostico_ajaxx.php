<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
?>

<style type="text/css">
.treeview, .treeview ul { 
	padding: 0;
	margin: 0;
	list-style: none;
}	
.treeview li { 
	margin: 0;
	padding: 3px 0pt 3px 16px;
}
ul.dir li { padding: 2px 0 0 16px; }

.treeview li { background: url(imagens/treeview/tv-item.gif) 0 0 no-repeat; }
.treeview .collapsable { background-image: url(imagens/treeview/tv-collapsable.gif); }
.treeview .expandable { background-image: url(imagens/treeview/tv-expandable.gif); }
.treeview .last { background-image: url(imagens/treeview/tv-item-last.gif); }
.treeview .lastCollapsable { background-image: url(imagens/treeview/tv-collapsable-last.gif); }
.treeview .lastExpandable { background-image: url(imagens/treeview/tv-expandable-last.gif); }

#red.treeview li { background: url(imagens/treeview/red/tv-item.gif) 0 0 no-repeat; }
#red.treeview .collapsable { background-image: url(imagens/treeview/red/tv-collapsable.gif); }
#red.treeview .expandable { background-image: url(imagens/treeview/red/tv-expandable.gif); }
#red.treeview .last { background-image: url(imagens/treeview/red/tv-item-last.gif); }
#red.treeview .lastCollapsable { background-image: url(imagens/treeview/red/tv-collapsable-last.gif); }
#red.treeview .lastExpandable { background-image: url(imagens/treeview/red/tv-expandable-last.gif); }

#black.treeview li { background: url(imagens/treeview/black/tv-item.gif) 0 0 no-repeat; }
#black.treeview .collapsable { background-image: url(imagens/treeview/black/tv-collapsable.gif); }
#black.treeview .expandable { background-image: url(imagens/treeview/black/tv-expandable.gif); }
#black.treeview .last { background-image: url(imagens/treeview/black/tv-item-last.gif); }
#black.treeview .lastCollapsable { background-image: url(imagens/treeview/black/tv-collapsable-last.gif); }
#black.treeview .lastExpandable { background-image: url(imagens/treeview/black/tv-expandable-last.gif); }

#gray.treeview li { background: url(imagens/treeview/gray/tv-item.gif) 0 0 no-repeat; }
#gray.treeview .collapsable { background-image: url(imagens/treeview/gray/tv-collapsable.gif); }
#gray.treeview .expandable { background-image: url(imagens/treeview/gray/tv-expandable.gif); }
#gray.treeview .last { background-image: url(imagens/treeview/gray/tv-item-last.gif); }
#gray.treeview .lastCollapsable { background-image: url(imagens/treeview/gray/tv-collapsable-last.gif); }
#gray.treeview .lastExpandable { background-image: url(imagens/treeview/gray/tv-expandable-last.gif); }
#treecontrol { margin: 1em 0; }
.espaco{padding-left:80px;}
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.frm {
	background-color:#F0F0F0;
	border:1px solid #888888;
	font-family:Verdana;
	font-size:8pt;
	font-weight:bold;
}
.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
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
	font: bold 16px "Arial";
	color: #FFFFFF;
	text-align:center;
	width: 700px;
}
body {
	background: #fff;
}
</style>

<?php
$ajax_acerto = $_GET['ajax_acerto'];
if(strlen($ajax_acerto)==0){$ajax_acerto = $_POST['ajax_acerto'];}
if(strlen($ajax_acerto)>0){
	$tipo = $_GET['tipo'];	
	if(strlen($tipo)==0){$tipo = $_POST['tipo'];}
	
	$grupo_hd = $_GET['grupo'];
	$multiplos = $_GET["multiplos"];
	$todosProdutos = $_GET["todos_produtos"];
	$familiaSelecionada = $_GET["familia_selecionada"];

	if(strlen($grupo_hd)==0){$grupo_hd = $_POST['grupo'];}

	if ($login_fabrica == 3 AND $grupo_hd == 'HD') {
		$produto = $_GET['produto'];
		if (strlen($produto) == 0) {
			$produto = $_POST['produto'];		
		}

		$defeito_constatado = $_GET['defeito_constatado'];
		if (strlen($defeito_constatado) == 0) {
			$defeito_constatado = $_POST['defeito_constatado'];		
		}
	}	

	echo "<div id='container'  style='overflow-y:auto; height: 100%'>";
	switch ($tipo) {
		case "linha":
			$codigo_linha = $_POST['codigo_linha'];
			$nome_linha   = $_POST['nome_linha'];
			$btn_acao     = $_POST['btn_acao'];
			$linha        = $_GET['linha'];
			if(strlen($linha)>0){
				$sql = "SELECT tbl_linha.linha, tbl_linha.codigo_linha, tbl_linha.nome 
						from tbl_linha 
						where fabrica = $login_fabrica
						and linha = $linha 
						and ativo = 't'
						order by nome";
				$res = pg_exec($con,$sql);
				$linha = pg_result($res,0,linha);
				$codigo_linha = pg_result($res,0,codigo_linha);
				$nome = pg_result($res,0,nome);
			}

			if($btn_acao == "gravar"){
				$linha        = $_POST['linha'];
				if(strlen($linha)==0){
					//	echo "gravando $codigo_linha, $nome_linha";
					if(strlen($codigo_linha)==0){
						if($login_fabrica == 35){
							$msg_erro = "Por favor insira o código da linha";
						}else{
							$codigo_linha = "null";
						}
					}else{
						$codigo_linha = "'"."$codigo_linha"."'";
					}
					if(strlen($nome_linha)==0){
						$msg_erro = "Por favor insira o nome da linha";
					}
					if(strlen($msg_erro)==0){
						$sql = "INSERT INTO tbl_linha(codigo_linha,nome, fabrica)values($codigo_linha,'$nome_linha',$login_fabrica)";
						$res = @pg_exec($con,$sql);
						$msg_erro = pg_errormessage($con);
						if(strlen($msg_erro)==0){
							$msg_erro2 = "Cadastrado com sucesso!";
							$linha = "";
							$codigo_linha = "";
							$nome = "";
						}
					}
				}else{ //atualizando
					if(strlen($codigo_linha)==0){
						$codigo_linha = "null";
					}else{
						$codigo_linha = "'"."$codigo_linha"."'";
					}
					if(strlen($nome_linha)==0){
						$msg_erro = "Por favor insira o nome da linha";
					}
					if(strlen($msg_erro)==0){
						$sql = "UPDATE tbl_linha set codigo_linha = $codigo_linha,
											nome = '$nome_linha'
								where fabrica = $login_fabrica
								and linha = $linha";
						$res = @pg_exec($con,$sql);
						//						echo $sql;
						$msg_erro = pg_errormessage($con);
						if(strlen($msg_erro)==0){
							$msg_erro2 = "Atualizado com sucesso!";
							$linha = "";
							$codigo_linha = "";
							$nome = "";
						}
					}

				}
			} 
				if(strlen($msg_erro)>0){
					if (strpos ($msg_erro,"duplicate key violates unique constraint \"tbl_linha_unico\"") > 0)
					$msg_erro = "Código da linha já cadastrado";
	

					echo "<table cellspacing='1' width='100%' align='center' class='foormulario'>
							<tr>
								<td align='center' class='msg_erro'>$msg_erro</td>
							</tr>
						</table>";
				}
				if(strlen($msg_erro2)>0){
					echo "<table cellspacing='1' width='100%' align='center' class='formulario'>
							<tr>
								<td align='center' class='msg_erro'>$msg_erro2</td>
							</tr>
						</table>";
				}

				echo "<form name='frm_linha' method='POST' action='$PHP_SELF'>";
				echo "<table cellspacing='1' width='100%' class='formulario' align='center'>";
				echo "<tr>";
				echo "<td align='center' colspan='2' class='titulo_tabela'>Efetue o cadastro de uma nova linha inserindo o código e o nome</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td class='espaco'>Código</td>";
				echo "<td>Nome</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td class='espaco'><input type='text' name='codigo_linha' size='5' maxlength='3' value='$codigo_linha' class='frm'></td>";
				echo "<td><input type='text' name='nome_linha' size='40' maxlength='50' value='$nome' class='frm'></td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td colspan='2' align='center'>";
				echo "<input type='button' value='Gravar' onclick=\"if (document.frm_linha.btn_acao.value == '' ) { 
					document.frm_linha.btn_acao.value='gravar' ; document.frm_linha.submit() 
					} else { alert ('Aguarde ') } 
					\" alt=\"Gravando Linha\" border='0' style=\"cursor:pointer;\">
					<br><br>
					</td>";
				echo "</tr>";
				echo "</table>";
				echo "<input type='hidden' name='btn_acao' value=''>";
				echo "<input type='hidden' name='linha' value='$linha'>";
				echo "<input type='hidden' name='ajax_acerto' value='true'>";
				echo "<input type='hidden' name='tipo' value='linha'>";
				echo "</form>";
				echo "<BR>";

				echo "<table cellspacing='1' width='100%' align='center' class='tabela'>";
				echo "<tr>";
				echo "<td align='center' class='titulo_coluna'>Todas as linhas cadastradas</td>";
				echo "</tr>";
				echo "</table>";
				echo "<BR>";

				echo "<table cellspacing='1' width='100%' align='center' class='tabela'>";
				echo "<tr class='titulo_coluna'>";
				echo "<td align='center'>Código</td>";
				echo "<td align='center'>Nome</td>";
				echo "<td align='center'>Ações</td>";
				echo "</tr>";
				
				$sql = "SELECT tbl_linha.linha, tbl_linha.codigo_linha, tbl_linha.nome from tbl_linha where fabrica = $login_fabrica order by nome";
				$res = pg_exec($con,$sql);
				
				if(pg_numrows($res)>0){
					for($i=0;pg_numrows($res)>$i;$i++){
						$linha = pg_result($res,$i,linha);
						$codigo_linha = pg_result($res,$i,codigo_linha);
						$nome = pg_result($res,$i,nome);
						
						$cor = ($i%2) ? '#F7F5F0' : '#F1F4FA';
						
						echo "<tr bgcolor='$cor'>";
						echo "<td align='center'>$codigo_linha</td>";
						echo "<td>$nome</td>";
						echo "<td align='center'><input type='button' value='Alterar' style='cursor:pointer' onclick=\"window.location.href='$PHP_SELF?ajax_acerto=true&tipo=linha&acao=alterar&linha=$linha';\"/></td>";
						echo "</tr>";
					}
				
				}

				echo "</table>";

			break;
		case "familia":
			$codigo_familia = $_POST['codigo_familia'];
			$nome_familia   = $_POST['nome_familia'];
			$btn_acao       = $_POST['btn_acao'];
			$familia        = $_GET['familia'];
			if(strlen($familia)>0){
				$sql = "SELECT tbl_familia.familia, tbl_familia.codigo_familia, tbl_familia.descricao 
						from tbl_familia 
						where fabrica = $login_fabrica
						and familia = $familia 
						order by descricao";
					//	echo $sql;
				$res = pg_exec($con,$sql);
				$familia        = pg_result($res,0,familia);
				$codigo_familia = pg_result($res,0,codigo_familia);
				$nome_familia   = pg_result($res,0,descricao);
			}

			if($btn_acao == "gravar"){
				$familia        = $_POST['familia'];
				if(strlen($familia)==0){
					//	echo "gravando $codigo_linha, $nome_linha";
					if(strlen($codigo_familia)==0){
						if($login_fabrica == 35){
							$msg_erro = "Por favor insira o código da família";
						}else{
							$codigo_familia = "null";
						}
					}else{
						$codigo_familia = "'"."$codigo_familia"."'";
					}
					if(strlen($nome_familia)==0){
						$msg_erro = "Por favor insira o nome da familia";
					}
					if(strlen($msg_erro)==0){
						$sql = "INSERT INTO tbl_familia(codigo_familia,descricao, fabrica)
						values($codigo_familia,'$nome_familia',$login_fabrica)";
						$res      = @pg_exec($con,$sql);
						//echo $sql;
						$msg_erro = pg_errormessage($con);
						if(strlen($msg_erro)==0){
							$msg_erro2 = "Cadastrado com sucesso!";
							$familia        = "";
							$codigo_familia = "";
							$nome_familia   = "";
						}
					}
				}else{ //atualizando
					if(strlen($codigo_familia)==0){
						$codigo_familia ="null";
					}else{
						$codigo_familia = "'"."$codigo_familia"."'";
					}
					if(strlen($nome_familia)==0){
						$msg_erro = "Por favor insira o nome da familia";
					}
					if(strlen($msg_erro)==0){
						$sql = "UPDATE tbl_familia set codigo_familia = $codigo_familia,
											descricao = '$nome_familia'
								where fabrica = $login_fabrica
								and familia = $familia";
						$res = @pg_exec($con,$sql);
						//echo $sql;
						$msg_erro = pg_errormessage($con);
						if(strlen($msg_erro)==0){
							$msg_erro2 = "Atualizado com sucesso!";
							$familia        = "";
							$codigo_familia = "";
							$nome_familia   = "";
						}
					}

				}
			} 

			if(strlen($msg_erro)>0){
				if (strpos ($msg_erro,"duplicate key violates unique constraint \"tbl_familia_unico\"") > 0)
				$msg_erro = "Código da familia já cadastrado";


				echo "<table cellspacing='1' width='100%' align='center' class='formulario'>
						<tr>
							<td align='center' class='msg_erro'>$msg_erro</td>
						</tr>
					</table>";
			}
			if(strlen($msg_erro2)>0){
				echo "<table cellspacing='1' width='100%' align='center'>
						<tr>
							<td align='center' class='msg_erro'>$msg_erro2</td>
						</tr>
					</table>";
			}

				echo "<form name='frm_familia' method='POST' action='$PHP_SELF'>";
				echo "<table cellspacing='1' width='100%' align='center' class='formulario'>";
				echo "<tr>";
				echo "<td align='center' colspan='2' class='titulo_tabela'>Efetue o cadastro de uma nova <B>familia</b> inserindo o código e o nome</td>";
				echo "</tr>";
				echo "<td class='espaco'>Código</td>";
				echo "<td>Nome</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td class='espaco'><input type='text' name='codigo_familia' size='5' maxlength='3' value='$codigo_familia' class='frm'></td>";
				echo "<td><input type='text' name='nome_familia' size='40' maxlength='50' value='$nome_familia' class='frm'></td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td colspan='2' align='center'>";
				echo "<input type='button' value='Gravar' onclick=\"if (document.frm_familia.btn_acao.value == '' ) { 
					document.frm_familia.btn_acao.value='gravar' ; document.frm_familia.submit() 
					} else { alert ('Aguarde ') } 
					\" alt=\"Gravando Linha\" border='0' style=\"cursor:pointer;\"></td>";
				echo "</tr>";
				echo "</table>";
				echo "<input type='hidden' name='btn_acao' value=''>";
				echo "<input type='hidden' name='familia' value='$familia'>";
				echo "<input type='hidden' name='ajax_acerto' value='true'>";
				echo "<input type='hidden' name='tipo' value='familia'>";
				echo "</form>";
				echo "<BR>";

				echo "<table cellspacing='1' width='100%' align='center' class='tabela'>";
				echo "<tr>";
				echo "<td align='center' class='titulo_coluna'>Todas as familias cadastradas</td>";
				echo "</tr>";
				echo "</table>";

				echo "<table cellspacing='1' width='100%' align='center' class='tabela'>";
				echo "<tr class='titulo_coluna'>";
				echo "<td align='center'>Código</td>";
				echo "<td align='center'>Nome</td>";
				echo "<td align='center'>Ações</td>";
				echo "</tr>";
				
				$sql = "SELECT tbl_familia.familia, tbl_familia.codigo_familia, tbl_familia.descricao 
						from tbl_familia 
						where fabrica = $login_fabrica and ativo = 't'
						order by descricao";
				$res = pg_exec($con,$sql);
				
				if(pg_numrows($res)>0){
					for($i=0;pg_numrows($res)>$i;$i++){
						$familia        = pg_result($res,$i,familia);
						$codigo_familia = pg_result($res,$i,codigo_familia);
						$nome_familia   = pg_result($res,$i,descricao);
						
						if(!trim($codigo_familia)) $codigo_familia = '&nbsp;';
						
						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
						
						echo "<tr bgcolor='$cor'>";
						echo "<td align='center'>".$codigo_familia."</td>";
						echo "<td>$nome_familia</td>";
						echo "<td align='center'><input type='button' value='Alterar' style='cursor:pointer' onclick=\"window.location.href='$PHP_SELF?ajax_acerto=true&tipo=familia&acao=alterar&familia=$familia';\" /></td>";
						echo "</tr>";
					}
				
				}

				echo "</table>";
			break;
		case "defeito_reclamado":
			$codigo_defeito_reclamado = $_POST['codigo_defeito_reclamado'];
			$nome_defeito_reclamado   = $_POST['nome_defeito_reclamado'];
			$btn_acao                 = $_POST['btn_acao'];
			$defeito_reclamado        = $_GET['defeito_reclamado'];
			if(strlen($defeito_reclamado)>0){
				$sql = "SELECT tbl_defeito_reclamado.defeito_reclamado, 
								tbl_defeito_reclamado.codigo,
								tbl_defeito_reclamado.descricao , ativo
						from tbl_defeito_reclamado 
						where fabrica = $login_fabrica
						and defeito_reclamado = $defeito_reclamado 
						 AND tbl_defeito_reclamado.duvida_reclamacao <> 'CC' 
						order by descricao";
					//	echo $sql;
				$res = pg_exec($con,$sql);
				$defeito_reclamado        = pg_result($res,0,defeito_reclamado);
				$codigo_defeito_reclamado = pg_result($res,0,codigo);
				$nome_defeito_reclamado   = pg_result($res,0,descricao);
				$ativo_defeito_reclamado   = pg_result($res,0,ativo);

			}

			if($btn_acao == "gravar"){
				$defeito_reclamado        = $_POST['defeito_reclamado'];
				$ativo_defeito_reclamado  = $_POST['ativo_defeito_reclamado'];
				if(strlen($defeito_reclamado)==0){
					//	echo "gravando $codigo_linha, $nome_linha";
					if(strlen($ativo_defeito_reclamado)==0){
						$ativo_defeito_reclamado = "f";
					}
					if(strlen($codigo_defeito_reclamado)==0){
						$codigo_defeito_reclamado = "null";
					}else{
						$codigo_defeito_reclamado = "'"."$codigo_defeito_reclamado"."'";
					}
					if(strlen($nome_defeito_reclamado)==0){
						$msg_erro = "Por favor insira o nome do defeito reclamado";
					}
					if(strlen($msg_erro)==0){
						$sql = "INSERT INTO tbl_defeito_reclamado(codigo,descricao, fabrica, ativo, duvida_reclamacao)
						values($codigo_defeito_reclamado,'$nome_defeito_reclamado',$login_fabrica, '$ativo_defeito_reclamado','RC')";
						$res      = pg_query($con,$sql);

						$msg_erro = pg_last_error($con);
						if(strlen($msg_erro)==0){
							$msg_erro2 = "Cadastrado com sucesso!";
							$defeito_reclamado        = "";
							$codigo_defeito_reclamado = "";
							$nome_defeito_reclamado   = "";
							$ativo_defeito_reclamado  = "";
						}
					}
				}else{ //atualizando
					if(strlen($ativo_defeito_reclamado)==0){
						$ativo_defeito_reclamado = "f";
					}
					if(strlen($codigo_defeito_reclamado)==0){
						$codigo_defeito_reclamado = "null";
					}else{
						$codigo_defeito_reclamado = "'"."$codigo_defeito_reclamado"."'";
					}
					if(strlen($nome_defeito_reclamado)==0){
						$msg_erro = "Por favor insira o nome do defeito reclamado";
					}
					if(strlen($msg_erro)==0){
						$sql = "UPDATE tbl_defeito_reclamado set codigo = $codigo_defeito_reclamado,
											descricao = '$nome_defeito_reclamado',
												ativo = '$ativo_defeito_reclamado'
								where fabrica = $login_fabrica
								and defeito_reclamado = $defeito_reclamado";
						$res = @pg_exec($con,$sql);
						//echo $sql;
						$msg_erro = pg_errormessage($con);
						if(strlen($msg_erro)==0){
							$msg_erro2 = "Atualizado com sucesso!";
							$defeito_reclamado        = "";
							$codigo_defeito_reclamado = "";
							$nome_defeito_reclamado   = "";
							$ativo_defeito_reclamado  = "";
						}
					}

				}
			} 
				if(strlen($msg_erro)>0){
					if (strpos ($msg_erro,"duplicate key violates unique constraint \"tbl_familia_unico\"") > 0)
					$msg_erro = "Código do defeito reclamado já cadastrado";
	

					echo "<table cellspacing='1' width='100%' align='center'>
							<tr>
								<td align='center'>$msg_erro</td>
							</tr>
						</table>";
				}
				if(strlen($msg_erro2)>0){
					echo "<table cellspacing='1' width='100%' align='center'>
							<tr><td align='center'>$msg_erro2</td></tr>
						</table>";
				}

				echo "<form name='frm_defeito_reclamado' method='POST' action='$PHP_SELF'>";
				echo "<table cellspacing='1' width='100%' align='center' class='formulario'>";
				echo "<tr>";
				echo "<td align='center' colspan='3' class='titulo_coluna'>Efetue o cadastro de uma novo <B>defeito reclamado</b> inserindo o código e o nome</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td class='espaco'>Código</td>";
				echo "<td>Nome</td>";
				echo "<td>Ativo</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td class='espaco'><input type='text' name='codigo_defeito_reclamado' size='5' maxlength='3' value='$codigo_defeito_reclamado' class='frm'></td>";
				echo "<td><input type='text' name='nome_defeito_reclamado' size='40' maxlength='50' value='$nome_defeito_reclamado' class='frm'></td>";
				echo "<td><input type='checkbox' name='ativo_defeito_reclamado'"; if ($ativo_defeito_reclamado == 't' ) echo " checked "; echo " value='t'></td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td colspan='3' align='center'>";
				echo "<input type='button' value='Gravar' onclick=\"if (document.frm_defeito_reclamado.btn_acao.value == '' ) { 
					document.frm_defeito_reclamado.btn_acao.value='gravar' ; document.frm_defeito_reclamado.submit() 
					} else { alert ('Aguarde ') } 
					\" alt=\"Gravando Linha\" border='0' style=\"cursor:pointer;\"></td>";
				echo "</tr>";
				echo "</table>";
				echo "<input type='hidden' name='btn_acao' value=''>";
				echo "<input type='hidden' name='defeito_reclamado' value='$defeito_reclamado'>";
				echo "<input type='hidden' name='ajax_acerto' value='true'>";
				echo "<input type='hidden' name='tipo' value='defeito_reclamado'>";
				echo "</form>";
				echo "<BR>";

				echo "<table cellspacing='1' width='100%' align='center' class='tabela'>";
				echo "<tr>";
				echo "<td align='center' class='titulo_coluna'>Todos os defeitos reclamados cadastradas</td>";
				echo "</tr>";
				echo "</table>";

				echo "<table cellspacing='1' width='100%' align='center' class='tabela' >";
				echo "<tr class='titulo_coluna'>";
				echo "<td align='center'>Status</td>";
				echo "<td align='center'>Código</td>";
				echo "<td align='center'>Nome</td>";
				echo "<td align='center'>Ações</td>";
				echo "</tr>";
				
				$sql = "SELECT tbl_defeito_reclamado.defeito_reclamado,
								tbl_defeito_reclamado.codigo,
								tbl_defeito_reclamado.descricao ,
								tbl_defeito_reclamado.ativo
						from tbl_defeito_reclamado
						where fabrica = $login_fabrica
						 AND tbl_defeito_reclamado.duvida_reclamacao <> 'CC' 
						order by descricao";
				$res = pg_exec($con,$sql);
				
				if(pg_numrows($res)>0){
					for($i=0;pg_numrows($res)>$i;$i++){
						$defeito_reclamado        = pg_result($res,$i,defeito_reclamado);
						$codigo_defeito_reclamado = pg_result($res,$i,codigo);
						$nome_defeito_reclamado   = pg_result($res,$i,descricao);
						$ativo_defeito_reclamado  = pg_result($res,$i,ativo);
						if($ativo_defeito_reclamado=="t"){
							$ativo_defeito_reclamado = "<font color='#336633'>Ativo</font>";
						}else{
							$ativo_defeito_reclamado = "<font color='#CC0033'>Inativo</font>";
						}
						
						if(!trim($codigo_defeito_reclamado))
							$codigo_defeito_reclamado = '&nbsp;';
						
						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
						
						echo "<tr bgcolor='$cor'>";
						echo "<td align='center'>$ativo_defeito_reclamado</td>";
						echo "<td align='center'>$codigo_defeito_reclamado</td>";
						echo "<td>$nome_defeito_reclamado</td>";
						echo "<td align='center'><input type='button' value='Alterar' style='cursor:pointer' onclick=\"window.location.href='$PHP_SELF?ajax_acerto=true&tipo=defeito_reclamado&acao=alterar&defeito_reclamado=$defeito_reclamado';\" /></td>";
						echo "</tr>";
					}
				
				}

				echo "</table>";

			break;
		case "defeito_constatado":
			$codigo_defeito_constatado = $_POST['codigo_defeito_constatado'];
			$nome_defeito_constatado   = $_POST['nome_defeito_constatado'];
			$btn_acao                  = $_POST['btn_acao'];
			$defeito_constatado        = $_GET['defeito_constatado'];

			if(strlen($defeito_constatado)>0){

				$sql = "SELECT tbl_defeito_constatado.defeito_constatado, 
								tbl_defeito_constatado.codigo,
								tbl_defeito_constatado.descricao , ativo
						from tbl_defeito_constatado
						where fabrica = $login_fabrica						
						and defeito_constatado = $defeito_constatado					
						order by descricao";
						//echo $sql;
				$res = pg_exec($con,$sql);
				$defeito_constatado        = pg_result($res,0,defeito_constatado);
				$codigo_defeito_constatado = pg_result($res,0,codigo);
				$nome_defeito_constatado   = pg_result($res,0,descricao);
				$ativo_defeito_constatado  = pg_result($res,0,ativo);

			}

			if($btn_acao == "gravar"){
				$defeito_constatado        = $_POST['defeito_constatado'];
				$ativo_defeito_constatado  = $_POST['ativo_defeito_constatado'];
				$multiplos                 = $_POST['multiplos'];
				$todosProdutos             = $_POST['todos_produtos'];
				$familiaSelecionada        = $_POST['familia_selecionada'];
				if(strlen($defeito_constatado)==0){
					//	echo "gravando $codigo_linha, $nome_linha";
					if(strlen($ativo_defeito_constatado)==0){
						$ativo_defeito_constatado = "f";
					}
					if(strlen($codigo_defeito_constatado)==0){
						if($login_fabrica == 35){
							$msg_erro = "Por favor insira o código do defeito constatado";
						}else{
							$codigo_defeito_constatado = "null";
						}
					}else{
						$codigo_defeito_constatado = "'"."$codigo_defeito_constatado"."'";
					}
					if(strlen($nome_defeito_constatado)==0){
						$msg_erro = "Por favor insira o nome do defeito constatado";
					}
					if(strlen($msg_erro)==0){

						if ($grupo_hd == 'HD') {
							if ($todosProdutos != "sim") {
								if (strlen($produto)==0) {
									$msg_erro = "Por favor selecione um produto";
								}else{
									$insert_grupo = " ,defeito_constatado_grupo ";
									$sql_grupo = "SELECT defeito_constatado_grupo
													FROM tbl_defeito_constatado_grupo 
													WHERE fabrica = $login_fabrica
														AND descricao = 'HD'
														AND ativo = 't';";
									$res_grupo = pg_query($con,$sql_grupo);
									if (pg_num_rows($res_grupo) > 0) {
										$insert_grupo_d = pg_fetch_result($res_grupo, 0, defeito_constatado_grupo);
										$insert_grupo_d = ", ".$insert_grupo_d." ";//código do do grupo do defeito na tabela tbl_defeito_constatado_grupo	
									}else{
										$insert_grupo = "";
										$insert_grupo_d = "";
									}

									$sql = "INSERT INTO tbl_defeito_constatado
													(codigo,descricao, fabrica, ativo $insert_grupo)
													VALUES
													($codigo_defeito_constatado,'$nome_defeito_constatado',$login_fabrica, '$ativo_defeito_constatado' $insert_grupo_d)
													RETURNING defeito_constatado ";
									$res = pg_query($con,$sql);
									
									if (pg_num_rows($res) > 0) {
										$id_defeito_constatado = pg_fetch_result($res, 0, defeito_constatado);

										if ($multiplos == "true") {
											$arrayProdutos = explode(",",$produto);
										} else {
											$arrayProdutos = [$produto];
										}

										foreach ($arrayProdutos as $referenciaProd) {
											if (!empty($referenciaProd)) {
												$sqlProd = "SELECT produto
															FROM tbl_produto
															WHERE fabrica_i = {$login_fabrica}
															AND referencia = '{$referenciaProd}'";
												$resProd = pg_query($con, $sqlProd);

												$idProd = pg_fetch_result($resProd, 0, "produto");

												if (!empty($idProd)) {

													$sql = "INSERT INTO tbl_produto_defeito_constatado
																(produto,defeito_constatado,mao_de_obra)
																VALUES
																({$idProd},{$id_defeito_constatado},0);";
													$res = pg_query($con,$sql);
												}
											}
										}

									//echo pg_last_error($con);exit;
									} else {
										$msg_erro = "Não foi possível gravar o Defeito Constatado!";
									}
								}
							} else {

								$sql_grupo = "SELECT defeito_constatado_grupo
									FROM tbl_defeito_constatado_grupo 
									WHERE fabrica = $login_fabrica
										AND descricao = 'HD'
										AND ativo = 't';";
								$res_grupo = pg_query($con,$sql_grupo);

								$insert_grupo_d = pg_fetch_result($res_grupo, 0, defeito_constatado_grupo);


								$sql = "INSERT INTO tbl_defeito_constatado
										(codigo,descricao, fabrica, ativo, defeito_constatado_grupo)
										VALUES
										($codigo_defeito_constatado,'$nome_defeito_constatado',$login_fabrica, '$ativo_defeito_constatado', $insert_grupo_d)
										RETURNING defeito_constatado ";
								$res = pg_query($con,$sql);

								$id_defeito_constatado = pg_fetch_result($res, 0, defeito_constatado);

								$sql = "INSERT INTO tbl_produto_defeito_constatado
										(produto,defeito_constatado,mao_de_obra)
										VALUES
										((SELECT produto
										  FROM tbl_produto
										  WHERE familia = {$familiaSelecionada}
										  LIMIT 1),{$id_defeito_constatado},0);";
								$res = pg_query($con,$sql);

							}
						} else {
							$sql = "INSERT INTO tbl_defeito_constatado(codigo,descricao, fabrica, ativo $insert_grupo)
						values($codigo_defeito_constatado,'$nome_defeito_constatado',$login_fabrica, '$ativo_defeito_constatado' $insert_grupo_d)";
							$res      = @pg_exec($con,$sql);
						}

						//echo $sql;
						if (strlen($msg_erro)==0) {
							$msg_erro = pg_errormessage($con);
						}
						if(strlen($msg_erro)==0){
							$msg_erro2 = "Cadastrado com sucesso!";
							$defeito_constatado        = "";
							$codigo_defeito_constatado = "";
							$nome_defeito_constatado   = "";
							$ativo_defeito_constatado  = "";
						}
					}
				}else{ //atualizando
					if(strlen($ativo_defeito_constatado)==0){
						$ativo_defeito_constatado = "f";
					}
					if(strlen($codigo_defeito_constatado)==0){
						$codigo_defeito_constatado = "null";
					}else{
						$codigo_defeito_constatado = "'"."$codigo_defeito_constatado"."'";
					}
					if(strlen($nome_defeito_constatado)==0){
						$msg_erro = "Por favor insira o nome do defeito constatado";
					}
					if(strlen($msg_erro)==0){
						$sql = "UPDATE tbl_defeito_constatado set codigo = $codigo_defeito_constatado,
											descricao = '$nome_defeito_constatado',
												ativo = '$ativo_defeito_constatado'
								where fabrica = $login_fabrica
								and defeito_constatado = $defeito_constatado";
						$res = @pg_exec($con,$sql);
						//echo $sql;
						$msg_erro = pg_errormessage($con);
						if(strlen($msg_erro)==0){
							$msg_erro2 = "Atualizado com sucesso!";
							$defeito_constatado        = "";
							$codigo_defeito_constatado = "";
							$nome_defeito_constatado   = "";
							$ativo_defeito_constatado  = "";
						}
					}

				}
			} 
				if(strlen($msg_erro)>0){
					if (strpos ($msg_erro,"duplicate key violates unique constraint \"tbl_familia_unico\"") > 0)
					$msg_erro = "Código do defeito constatado já cadastrado";
	

					echo "<table cellspacing='1' width='100%' align='center' class='formulario'>
							<tr>
								<td align='center' class='msg_erro'>$msg_erro</td>
							</tr>
						</table>";
				}
				if(strlen($msg_erro2)>0){
					echo "<table cellspacing='1' width='100%' align='center'>
							<tr>
								<td align='center'>$msg_erro2</td>
							</tr>
						</table>";
					if ($grupo_hd == 'HD') { ?>
						<script type="text/javascript">
							// window.parent.atualizaCombo('defeito');
							window.parent.atualiza_combos();
						</script>
					<?
					}				
				}

				echo "<form name='frm_defeito_constatado' method='POST' action='$PHP_SELF'>";
				echo "<table cellspacing='1' width='100%' align='center' class='formulario'>";
				echo "<tr>";
				if ($grupo_hd == 'HD') {
					echo "<td align='center' colspan='3' class='titulo_coluna'>Efetue o cadastro de um novo <b>defeito </b> inserindo o código e o nome</td>";
				}else{
					echo "<td align='center' colspan='3' class='titulo_coluna'>Efetue o cadastro de um novo <B>defeito constatado</b> inserindo o código e o nome</td>";
				}
				
				echo "</tr>";
				echo "<tr>";
				echo "<td class='espaco'>Código</td>";
				echo "<td>Nome</td>";
				echo "<td>Ativo</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td class='espaco'><input type='text' name='codigo_defeito_constatado' size='5' maxlength='3' value='$codigo_defeito_constatado' class='frm'></td>";
				echo "<td><input type='text' name='nome_defeito_constatado' size='40' maxlength='100' value='$nome_defeito_constatado' class='frm'></td>";
				echo "<td><input type='checkbox' name='ativo_defeito_constatado'"; if ($ativo_defeito_constatado == 't' || empty($ativo_defeito_constatado)) echo " checked "; echo " value='t'></td>";
				echo "</tr>";
				echo "<tr>";
				echo "<input type='hidden' name='btn_acao' value=''>";
				echo "<input type='hidden' name='defeito_constatado' value='$defeito_constatado'>";
				echo "<input type='hidden' name='ajax_acerto' value='true'>";
				echo "<input type='hidden' name='tipo' value='defeito_constatado'>";
				echo "<input type='hidden' name='grupo' value='$grupo' >";
				echo "<input type='hidden' name='produto' value='$produto' >";
				echo "<input type='hidden' name='multiplos' value='$multiplos' >";
				echo "<input type='hidden' name='todos_produtos' value='$todosProdutos'>";
				echo "<input type='hidden' name='familia_selecionada' value='$familiaSelecionada'>";
				echo "<td colspan='3' align='center'>";
				echo "<input type='button' value='Gravar' onclick=\"if (document.frm_defeito_constatado.btn_acao.value == '' ) { 
					document.frm_defeito_constatado.btn_acao.value='gravar' ; document.frm_defeito_constatado.submit() 
					} else { alert ('Aguarde ') } 
					\" alt=\"Gravando Linha\" border='0' style=\"cursor:pointer;\"></td>";
				echo "</tr>";
				echo "</table>";
				echo "</form>";
				echo "<BR>";

				if (!empty($familiaSelecionada)) {
					$label = "Defeitos cadastrados para a <strong>Família</strong>";
				} else {
					$label = "Defeitos cadastrados para o <strong>Produto</strong>";
				}

				echo "<table cellspacing='1' width='100%' align='center' class='tabela'>";
				echo "<tr class='titulo_coluna'>";
				echo "<td align='center' class='tabela'>{$label}</td>";
				echo "</tr>";
				echo "</table>";

				echo "<table cellspacing='1' width='100%' align='center' class='tabela'>";
				echo "<tr class='titulo_coluna'>";
				echo "<td align='center'>Status</td>";
				echo "<td align='center'>Código</td>";
				echo "<td align='center'>Nome</td>";
				echo "<td align='center'>Ações</td>";
				echo "</tr>";
				if ($grupo == 'HD') {

					if (!empty($familiaSelecionada)) {
						$condProd = "AND tbl_produto.familia = {$familiaSelecionada}";
					} else {
						$condProd = "AND tbl_produto_defeito_constatado.produto = $produto";
					}

					$sql = "SELECT DISTINCT ON (tbl_defeito_constatado.descricao) 
								tbl_defeito_constatado.defeito_constatado,
								tbl_defeito_constatado.codigo,
								tbl_defeito_constatado.descricao,
								tbl_defeito_constatado.ativo
						FROM tbl_defeito_constatado
							JOIN tbl_defeito_constatado_grupo USING(defeito_constatado_grupo)
							JOIN tbl_produto_defeito_constatado USING(defeito_constatado)
							JOIN tbl_produto ON tbl_produto.produto = tbl_produto_defeito_constatado.produto
						WHERE tbl_defeito_constatado.fabrica = $login_fabrica
						AND tbl_defeito_constatado_grupo.descricao = '{$grupo}'
						{$condProd}
						ORDER BY descricao";

				} else {
					$sql = "SELECT tbl_defeito_constatado.defeito_constatado,
								tbl_defeito_constatado.codigo,
								tbl_defeito_constatado.descricao ,
								tbl_defeito_constatado.ativo
						from tbl_defeito_constatado
						$join_grupo
						where tbl_defeito_constatado.fabrica = $login_fabrica
						$where_grupo 
						order by descricao";
				}
				//echo nl2br($sql);
				$res = pg_exec($con,$sql);
				
				if(pg_numrows($res)>0){
					for($i=0;pg_numrows($res)>$i;$i++){
						$defeito_constatado        = pg_result($res,$i,defeito_constatado);
						$codigo_defeito_constatado = pg_result($res,$i,codigo);
						$nome_defeito_constatado   = pg_result($res,$i,descricao);
						$ativo_defeito_constatado  = pg_result($res,$i,ativo);
						if($ativo_defeito_constatado=="t"){
							$ativo_defeito_constatado = "<font color='#336633'>Ativo</font>";
						}else{
							$ativo_defeito_constatado = "<font color='#CC0033'>Inativo</font>";
						}
						
						if(!trim($codigo_defeito_constatado))
							$codigo_defeito_constatado = '&nbsp;';

						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
						
						echo "<tr bgcolor='$cor'>";
						echo "<td align='center'>$ativo_defeito_constatado</td>";
						echo "<td align='center'>$codigo_defeito_constatado</td>";
						echo "<td>$nome_defeito_constatado</td>";
						echo "<td align='center'><input type='button' value='Alterar' style='cursor:pointer' onclick=\"window.location.href='$PHP_SELF?ajax_acerto=true&tipo=defeito_constatado&acao=alterar&grupo=$grupo&defeito_constatado=$defeito_constatado&produto=$produto';\" /></td>";
						echo "</tr>";
					}
				
				}

				echo "</table>";

			break;
		case "solucao":
			$codigo_solucao = $_POST['codigo_solucao'];
			$nome_solucao   = $_POST['nome_solucao'];
			$btn_acao       = $_POST['btn_acao'];
			$solucao        = $_GET['solucao'];
			$multiplos      = $_REQUEST["multiplos"];
			$produto        = $_REQUEST["produto"];
			$todosProdutos  = $_REQUEST["todos_produtos"];
			$familiaSelecionada = $_REQUEST["familia_selecionada"];

			if ($multiplos == "true") {
				$arrayProdutos = explode(",", $produto);
			} else {
				$arrayProdutos = [$produto];
			}

			foreach ($arrayProdutos as $referenciaProd) {

				if (empty($referenciaProd)) {
					continue;
				}

				$sqlProd = "SELECT produto
							FROM tbl_produto
							WHERE fabrica_i = {$login_fabrica}
							AND referencia = '{$referenciaProd}'";
				$resProd = pg_query($con, $sqlProd);

				$produtoId = pg_fetch_result($resProd, 0, "produto");

				if(strlen($solucao)>0){
					if ($grupo_hd == 'HD') {
						$sql = "SELECT tbl_solucao.solucao,
										tbl_solucao.codigo,
										tbl_solucao.descricao,
										tbl_solucao.ativo,
										tbl_solucao.troca_peca
									FROM tbl_solucao
									JOIN tbl_defeito_constatado_solucao USING (solucao)
									WHERE tbl_solucao.fabrica = $login_fabrica
										AND tbl_solucao.codigo = 'HD'
										AND tbl_solucao.solucao = $solucao 
										AND tbl_defeito_constatado_solucao.produto = $produtoId
									ORDER BY descricao";
					} else {
						$sql = "SELECT tbl_solucao.solucao, 
									tbl_solucao.codigo,
									tbl_solucao.descricao , ativo, troca_peca
							from tbl_solucao
							where fabrica = $login_fabrica
							$where_cod
							and solucao = $solucao 
							order by descricao";
					}					
					$res = pg_exec($con,$sql);

					$solucao        = pg_result($res,0,solucao);
					$codigo_solucao = pg_result($res,0,codigo);
					$nome_solucao   = pg_result($res,0,descricao);
					$ativo_solucao  = pg_result($res,0,ativo);
					$troca_peca_solucao= pg_result($res,0,troca_peca);

				}
			}

				if($btn_acao == "gravar"){

					$solucao        = $_POST['solucao'];
					$ativo_solucao  = $_POST['ativo_solucao'];
					$troca_peca_solucao= $_POST['troca_peca_solucao'];
					$produto = $_REQUEST["produto"];
					
					if(strlen($solucao)==0){
						//	echo "gravando $codigo_linha, $nome_linha";
						if(strlen($ativo_solucao)==0){
							$ativo_solucao = "f";
						}
						if(strlen($troca_peca_solucao)==0){
							$troca_peca_solucao = "f";
						}
						if(strlen($codigo_solucao)==0){
							$codigo_solucao = "null";
						}else{
							$codigo_solucao = "'"."$codigo_solucao"."'";
						}
						if(strlen($nome_solucao)==0){
							$msg_erro = "Por favor insira o nome da solução";
						}
						if(strlen($msg_erro)==0){
							if ($todosProdutos != "sim") {
								if ($grupo_hd == 'HD') {
									if (strlen($produto)==0) {
										$msg_erro = "Por favor selecione um produto";
									}else{
										$sql = "INSERT INTO tbl_solucao(codigo,descricao, fabrica, ativo, troca_peca)
												VALUES('HD','$nome_solucao',$login_fabrica, '$ativo_solucao', '$troca_peca_solucao') 
												RETURNING solucao";
										$res = pg_query($con,$sql);
										
										if (pg_num_rows($res) > 0) {
											$id_solucao = pg_fetch_result($res, 0, solucao);

											if ($multiplos == "true") {
												$arrayProdutos = explode(",", $produto);
											} else {
												$arrayProdutos = [$produto];
											}

											foreach ($arrayProdutos as $referenciaProd) {

												if (empty($referenciaProd)) {
													continue;
												}

												$sqlProd = "SELECT produto
															FROM tbl_produto
															WHERE fabrica_i = {$login_fabrica}
															AND referencia = '{$referenciaProd}'";
												$resProd = pg_query($con, $sqlProd);

												$produtoId = pg_fetch_result($resProd, 0, 'produto');

												$sql = "INSERT INTO tbl_defeito_constatado_solucao(solucao,fabrica,produto)
													VALUES ($id_solucao,$login_fabrica,$produtoId);";
												$res = pg_query($con,$sql);

											}

										} else {
											$msg_erro = "Não foi possível gravar a Solução!";
										}
									}
								} else {
									$sql = "INSERT INTO tbl_solucao(codigo,descricao, fabrica, ativo, troca_peca)
											VALUES($codigo_solucao,'$nome_solucao',$login_fabrica, '$ativo_solucao', '$troca_peca_solucao')";
									$res = pg_query($con,$sql);
								}
							} else {

								$sql = "INSERT INTO tbl_solucao(codigo,descricao, fabrica, ativo, troca_peca)
												VALUES('HD','$nome_solucao',$login_fabrica, '$ativo_solucao', '$troca_peca_solucao') 
												RETURNING solucao";
								$res = pg_query($con,$sql);

								$id_solucao = pg_fetch_result($res, 0, solucao);

								$sql = "INSERT INTO tbl_defeito_constatado_solucao(solucao,fabrica,produto)
										VALUES ($id_solucao,$login_fabrica,(SELECT produto
										  FROM tbl_produto
										  WHERE familia = {$familiaSelecionada}
										  LIMIT 1));";
								$res = pg_query($con,$sql);

							}

							if (strlen($msg_erro)==0) {
								$msg_erro = pg_errormessage($con);
							}

							if(strlen($msg_erro)==0){
								$msg_erro2 = "Cadastrado com sucesso!";
								$solucao        = "";
								$codigo_solucao = "";
								$nome_solucao   = "";
								$ativo_solucao  = "";
								$troca_peca_solucao= "";
							}
						}
					}else{ //atualizando
						if(strlen($ativo_solucao)==0){
							$ativo_solucao = "f";
						}
						if(strlen($troca_peca_solucao)==0){
							$troca_peca_solucao = "f";
						}
						if(strlen($codigo_solucao)==0){
							$codigo_solucao = "null";
						}else{
							$codigo_solucao = "'"."$codigo_solucao"."'";
						}
						if(strlen($nome_solucao)==0){
							$msg_erro = "Por favor insira o nome da solução";
						}
						if(strlen($msg_erro)==0){
							if ($grupo_hd == 'HD') {
								$codigo_solucao = "'HD'";
								$sql = "UPDATE tbl_solucao set codigo = $codigo_solucao,
												descricao = '$nome_solucao',
													ativo = '$ativo_solucao',
													troca_peca = '$troca_peca_solucao'
									where fabrica = $login_fabrica
									and solucao = $solucao";
							} else {
								$sql = "UPDATE tbl_solucao set codigo = $codigo_solucao,
												descricao = '$nome_solucao',
													ativo = '$ativo_solucao',
													troca_peca = '$troca_peca_solucao'
									where fabrica = $login_fabrica
									and solucao = $solucao";
							}						
							$res = @pg_exec($con,$sql);
							//echo $sql;
							$msg_erro = pg_errormessage($con);
							if(strlen($msg_erro)==0){
								$msg_erro2 = "Atualizado com sucesso!";
								$solucao        = "";
								$codigo_solucao = "";
								$nome_solucao   = "";
								$ativo_solucao  = "";
								$troca_peca_solucao= "";
							}
						}

					}
				} 

				if(strlen($msg_erro)>0){
					echo "<table cellspacing='1' width='100%' align='center' class='tabela'>
							<tr>
								<td align='center'>$msg_erro</td>
							</tr>
						</table>";
				}
				if(strlen($msg_erro2)>0){
					echo "<table cellspacing='1' width='100%' align='center' class='tabela'>
							<tr>
								<td align='center'>$msg_erro2</td>
							</tr>
						</table>";
					if ($grupo_hd == 'HD') {
					?>
						<script type="text/javascript">
						//window.parent.atualizaCombo('solucao');
						window.parent.atualiza_combos();
						</script>
					<?
					}
				}
				
				$colspan = ($login_fabrica <> 35) ? 4 : 3;
				if ($grupo_hd == 'HD' AND $login_fabrica == 3) {
					$colspan = 3;
				}
				
				echo "<form name='frm_solucao' method='POST' action='$PHP_SELF'>";
				echo "<table cellspacing='1' width='100%' align='center' class='formulario'>";
				echo "<tr>";
				echo "<td align='center' colspan='$colspan' class='titulo_coluna'>Efetue o cadastro da uma nova <B>solução</b> inserindo o código e o nome</td>";
				echo "</tr>";
				echo "<tr>";
				if($login_fabrica <> 35){
					if ($grupo_hd != 'HD') {
						echo "<td class='espaco'>Código</td>";
					}else{
						echo "<td class='espaco'></td>";
					}					
					echo "<td>Nome</td>";
				}else{
					echo "<td class='espaco'>Nome</td>";
				}
				echo "<td>Ativo</td>";
				if ($grupo_hd != 'HD') {
					echo "<td>Troca Peça</td>";	
				}				
				echo "</tr>";
				echo "<tr>";
				if($login_fabrica <> 35){
					if ($grupo_hd != 'HD') {
						echo "<td class='espaco'><input type='text' name='codigo_solucao' size='5' maxlength='3' value='$codigo_solucao' class='frm'></td>";
					}else{
						echo "<td class='espaco'></td>";
					}
					echo "<td><input type='text' name='nome_solucao' size='40' maxlength='100' value='$nome_solucao' class='frm'></td>";
				}else{
					echo "<td class='espaco'>";
					echo "<input type='text' name='nome_solucao' size='40' maxlength='50' value='$nome_solucao' class='frm'>";
					echo "<input type='hidden' name='codigo_solucao' size='5' maxlength='3' value='$codigo_solucao' class='frm'>";
					echo "</td>";
				}
				echo "<td><input type='checkbox' name='ativo_solucao'"; if ($ativo_solucao == 't' || empty($ativo_solucao)) echo " checked "; echo " value='t'></td>";
				if ($grupo_hd != 'HD') {
					echo "<td><input type='checkbox' name='troca_peca_solucao'"; if ($troca_peca_solucao == 't' ) echo " checked "; echo " value='t'></td>";
				}	
				
				echo "</tr>";
				echo "<tr>";
				echo "<input type='hidden' name='btn_acao' value=''>";
				echo "<input type='hidden' name='solucao' value='$solucao'>";
				echo "<input type='hidden' name='ajax_acerto' value='true'>";
				echo "<input type='hidden' name='tipo' value='solucao'>";
				echo "<input type='hidden' name='grupo' value='$grupo' >";
				echo "<input type='hidden' name='produto' value='$produto' >";
				echo "<input type='hidden' name='multiplos' value='$multiplos' >";
				echo "<input type='hidden' name='todos_produtos' value='$todosProdutos'>";
				echo "<input type='hidden' name='familia_selecionada' value='$familiaSelecionada'>";
				echo "<input type='hidden' name='defeito_constatado' value='$defeito_constatado' >";
				echo "<td colspan='$colspan' align='center'>";
				echo "<input type='button' value='Gravar' onclick=\"if (document.frm_solucao.btn_acao.value == '' ) { 
					document.frm_solucao.btn_acao.value='gravar' ; document.frm_solucao.submit() 
					} else { alert ('Aguarde ') } 
					\" alt=\"Gravando Linha\" border='0' style=\"cursor:pointer;\"><br><br></td>";
				echo "</tr>";
				echo "</table>";
				echo "</form>";
				echo "<BR>";

				if (!empty($familiaSelecionada)) {
					$label = "Soluções cadastradas para a <strong>Família</strong>";
				} else {
					$label = "Soluções cadastradas para o <strong>Produto</strong>";
				}

				echo "<table cellspacing='1' width='100%' align='center' class='tabela'>";
				echo "<tr>";
				echo "<td align='center' class='titulo_tabela'>{$label}</td>";
				echo "</tr>";
				echo "</table>";
				echo "<BR>";

				echo "<table border='0' cellspacing='1' width='100%' align='center' class='tabela'>";
				echo "<tr class='titulo_coluna'>";
				echo "<td align='center'>Status</td>";				
				if ($grupo_hd != 'HD') {
					echo "<td align='center'>Troca Peça?</td>";
					echo "<td align='center'>Código</td>";
				}				
				echo "<td align='center'>Nome</td>";
				echo "<td align='center'>Ações</td>";
				echo "</tr>";

				if ($grupo_hd == 'HD') {

					if (!empty($familiaSelecionada)) {
						$condProd = "AND tbl_produto.familia = {$familiaSelecionada}";
					} else {
						$condProd = "AND tbl_defeito_constatado_solucao.produto = $produto";
					}


					$sql = "SELECT DISTINCT ON (tbl_solucao.descricao)	
									tbl_solucao.solucao, 
									tbl_solucao.codigo,
									tbl_solucao.descricao,
									tbl_solucao.ativo,
									tbl_solucao.troca_peca
								FROM tbl_solucao
								JOIN tbl_defeito_constatado_solucao USING (solucao)
								JOIN tbl_produto ON tbl_defeito_constatado_solucao.produto = tbl_produto.produto
								WHERE tbl_solucao.fabrica = $login_fabrica
									AND tbl_solucao.codigo = 'HD'
									{$condProd}
									AND tbl_defeito_constatado_solucao.defeito_constatado is null
								ORDER BY descricao";
				} else {
					$sql = "SELECT tbl_solucao.solucao,
								tbl_solucao.codigo,
								tbl_solucao.descricao ,
								tbl_solucao.ativo,
								tbl_solucao.troca_peca
						from tbl_solucao
						where fabrica = $login_fabrica
						$where_cod
						order by descricao";
				}
				
				$res = pg_exec($con,$sql);
				
				if(pg_numrows($res)>0){
					for($i=0;pg_numrows($res)>$i;$i++){
						$solucao        = pg_result($res,$i,solucao);
						$codigo_solucao = pg_result($res,$i,codigo);
						$nome_solucao   = pg_result($res,$i,descricao);
						$troca_peca_solucao = pg_result($res,$i,troca_peca);
						$ativo_solucao  = pg_result($res,$i,ativo);
						if($ativo_solucao=="t"){
							$ativo_solucao = "<font color='#336633'>Ativo</font>";
						}else{
							$ativo_solucao = "<font color='#CC0033'>Inativo</font>";
						}
						if($troca_peca_solucao=="t"){
							$troca_peca_solucao = "<font color='#336633'>Sim</font>";
						}else{
							$troca_peca_solucao = "<font color='#CC0033'>Não</font>";
						}
						
						if(!trim($codigo_solucao)){
							$codigo_solucao = '&nbsp;';
						}
						
						$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
						
						echo "<tr bgcolor='$cor'>";
						echo "<td align='center'>$ativo_solucao</td>";						
						if($login_fabrica == 35){
							echo "<td align='center'>$troca_peca_solucao</td>";
							echo "<td align='center'>$solucao</td>";
						}else{
							if ($grupo_hd != 'HD') {
								echo "<td align='center'>$troca_peca_solucao</td>";
								echo "<td align='center'>$codigo_solucao</td>";
							}							
						}
						echo "<td>$nome_solucao</td>";
						echo "<td align='center'><input type='button' value='Alterar' style='cursor:pointer' onclick=\"window.location.href='$PHP_SELF?ajax_acerto=true&tipo=solucao&grupo=$grupo_hd&acao=alterar&solucao=$solucao&produto=$produto';\" /></td>";
						echo "</tr>";
					}
				
				}

				echo "</table>";

			break;
	}
	echo "</div>";
	exit;
}
?>

<script src="js/jquery-latest.pack.js" type="text/javascript"></script>
<!--<script src="js/jquery.cookie.js" type="text/javascript"></script>-->
<script src="js/jquery.treeview.pack.js" type="text/javascript"></script>
<script type="text/javascript" src="js/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
<script type="text/javascript" src="js/jquery.flydom-3.0.6.js"></script>
<script type="text/javascript">
$(document).ready(function(){
	//$("#browser").Treeview();
	//$("#browser").Treeview();
	//$("#black, #gray").Treeview({ control: "#treecontrol" });
});


	function adicionaIntegridade() {

		if(document.getElementById('linha').value=="0")             { alert('Selecione a linha');             return false}
		if(document.getElementById('familia').value=="0")           { alert('Seleciona a família');           return false}
		if(document.getElementById('defeito_reclamado').value=="0") { alert('Selecione o defeito reclamado'); return false}
		if(document.getElementById('defeito_constatado').value=="0"){ alert('Selecione o defeito constatado');return false}
		if(document.getElementById('solucao').value=="0")           { alert('Selecione a solução');           return false}

		var tbl = document.getElementById('tbl_integridade');
		var lastRow = tbl.rows.length;
		var iteration = lastRow;

		if (iteration>0){
			document.getElementById('tbl_integridade').style.display = "inline";
		}


		var linha = document.createElement('tr');
		linha.style.cssText = 'color: #000000; text-align: left; font-size:10px';

		// COLUNA 1 - LINHA
		var celula = criaCelula(document.getElementById('linha').options[document.getElementById('linha').selectedIndex].text);
		celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'integridade_linha_' + iteration);
		el.setAttribute('id', 'integridade_linha_' + iteration);
		el.setAttribute('value',document.getElementById('linha').value);
		celula.appendChild(el);

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'integridade_familia_' + iteration);
		el.setAttribute('id', 'integridade_familia_' + iteration);
		el.setAttribute('value',document.getElementById('familia').value);
		celula.appendChild(el);

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'integridade_defeito_reclamado_' + iteration);
		el.setAttribute('id', 'integridade_defeito_reclamado_' + iteration);
		el.setAttribute('value',document.getElementById('defeito_reclamado').value);
		celula.appendChild(el);

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'integridade_defeito_constatado_' + iteration);
		el.setAttribute('id', 'integridade_defeito_constatado_' + iteration);
		el.setAttribute('value',document.getElementById('defeito_constatado').value);
		celula.appendChild(el);

		var el = document.createElement('input');
		el.setAttribute('type', 'hidden');
		el.setAttribute('name', 'integridade_solucao_' + iteration);
		el.setAttribute('id', 'integridade_solucao_' + iteration);
		el.setAttribute('value',document.getElementById('solucao').value);
		celula.appendChild(el);

		linha.appendChild(celula);

;

		// coluna 2 - FAMÍLIA
		celula = criaCelula(document.getElementById('familia').options[document.getElementById('familia').selectedIndex].text);
		celula.style.cssText = 'text-align: left; color: #000000;font-size:10px';
		linha.appendChild(celula);

		// coluna 3 - DEFEITO RECLAMADO
		var celula = criaCelula(document.getElementById('defeito_reclamado').options[document.getElementById('defeito_reclamado').selectedIndex].text);
		celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
		linha.appendChild(celula);

		// coluna 4 - DEFEITO CONSTATADO
		var celula = criaCelula(document.getElementById('defeito_constatado').options[document.getElementById('defeito_constatado').selectedIndex].text);
		celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
		linha.appendChild(celula);

		// coluna 5 - SOLUCAO
		var celula = criaCelula(document.getElementById('solucao').options[document.getElementById('solucao').selectedIndex].text);
		celula.style.cssText = 'text-align: center; color: #000000;font-size:10px';
		linha.appendChild(celula);

		// coluna 6 - botacao
		var celula = document.createElement('td');
		celula.style.cssText = 'text-align: right; color: #000000;font-size:10px';

		var el = document.createElement('input');
		el.setAttribute('type', 'button');
		el.setAttribute('value','Excluir');
		el.onclick=function(){removerIntegridade(this);};
		celula.appendChild(el);
		linha.appendChild(celula);

		// finaliza linha da tabela
		var tbody = document.createElement('TBODY');
		tbody.appendChild(linha);
		/*linha.style.cssText = 'color: #404e2a;';*/
		tbl.appendChild(tbody);

		//document.getElementById('solucao').selectedIndex=0;
	}

	function removerIntegridade(iidd){
		var tbl = document.getElementById('tbl_integridade');
		var oRow = iidd.parentElement.parentElement;
		tbl.deleteRow(oRow.rowIndex);
	}

	function criaCelula(texto) {
		var celula = document.createElement('td');
		var textoNode = document.createTextNode(texto);
		celula.appendChild(textoNode);
		return celula;
	}
</script>
