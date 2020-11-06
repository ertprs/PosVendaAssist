<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';



$btn_acao=$_POST['btn_acao'];
if(strlen($btn_acao) == 0) 
	$btn_acao=$_GET['btn_acao'];

$loja_carta=$_POST['loja_carta'];
if(strlen($loja_carta) == 0) 
	$loja_carta=$_GET['loja_carta'];

$msg_erro='';

$conteudo  = $_POST['conteudo'];

if(strlen($btn_acao) > 0) {

	if(strlen($conteudo) == 0) {
		$msg_erro="Por favor, colocar o conteúdo da carta<BR>";
	}

	if(strlen($msg_erro) == 0) {
		$sql="UPDATE tbl_loja_carta set
				conteudo='$conteudo'
				WHERE loja_carta=$loja_carta";
		$res=pg_exec($con,$sql);
		$msg_erro = pg_errormessage($con);
	}
}

if(strlen($msg_erro) > 0) {
	echo "<div class='Erro'><center>$msg_erro</center></div><BR>";
	}
?>

<style>

.Label{
	font-family: Verdana;
	font-size: 10px;
}


.Titulo_Tabela{
	font-family: Verdana;
	font-size: 12px;
	font-weight: bold;
}

.titulo {
	font-family: Arial;
	font-size: 10pt;
	color: #000000;
	background: #ced7e9;
}

.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid; background-color: #990000;
}

.Botao{
	FONT: 10pt Arial ;
	BORDER-RIGHT:     #000000 1px solid;
	BACKGROUND-COLOR: #C0C0C0;
	padding:3px;
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color: #000000;
	border: 0px;
}

</style>

<script type="text/javascript">
	$(function() {
		$('#container-1').tabs( {fxAutoHeight: true, fxSpeed: 'fast'} );
		$('#container-Principal').tabs( {fxAutoHeight: true} );
	});
	$(document).ready(
);

function limpar_form(formu){
	for( var i = 0 ; i < formu.length; i++ ){
		if (formu.elements[i].type !='button' && formu.elements[i].type !='submit'){
			if(formu.elements[i].type=='checkbox'){
				formu.elements[i].checked=false;
			}else{
				formu.elements[i].value='';
			}
		}
	}
}

</script>

<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='600' border='0' >
		<tr height='20' bgcolor='#7392BF'>
			<td class='Titulo_Tabela' align='center' colspan='6'>Carta </td>
		</tr>
		<tr height='10'>
			<td  align='center' colspan='6'></td>
		</tr>
		<tr>
			<td class='Label'>
				<div id="container-Principal">
					<ul>
						<li><a href="#tab0Busca"><span><img src='imagens/lupa.png' align=absmiddle> Busca</span></a></li>
						<li><a href="#tab1Cadastro"><span><img src='imagens/document-txt-blue-new.png' align=absmiddle> Cadastro</span></a></li>
					</ul>
					<div id="tab0Busca">

				<FORM name='frm_consultar' action='<? echo $PHP_SELF ?>' METHOD='POST'>
				<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='600' border='0' class='tabela'>
					<tr bgcolor='#596D9B'>
						<td width='100%' colspan='2' class='titulo' align='left'>Selecionar os parâmetros para fazer pesquisa</td>
					</tr>
					<tr>
						<td align='left'><BR><font size=2>Número carta:</font> &nbsp; <input class='Caixa' type='text' size='5' maxlength='5' name='loja_carta' value='<? echo $loja_carta; ?>'><BR><BR></td>
						<td>
						<BR><center><font size=2>Tipo:</font><select class='frm' style='width: 200px;' name='tipo'>
							<option value=''>TODAS</option>
							<option value='assistencia' <? if ($tipo=='assistencia') echo "SELECTED"; ?>>ASSISTÊNCIA</option>
							<option value='cobranca'    <? if ($tipo=='cobranca')    echo "SELECTED"; ?>>COBRANÇA</option>
							<option value='venda'       <? if ($tipo=='venda')       echo "SELECTED"; ?>>VENDA</option>
							</select></center><BR>
						</td>
					</tr><BR>
					<tr>
						<td align='center' colspan=3>
							<center><input class='Botao' type='submit' name='btn_consulta' value='PESQUISAR'</center>
						</td>
					</tr>
				<BR>
<?
				$btn_consulta=$_GET['btn_consulta'];
				if(strlen($btn_consulta) == 0) {
					$btn_consulta=$_POST['btn_consulta'];
				}

				if($btn_consulta=="PESQUISAR") {
					$sql= "SELECT * 
							FROM tbl_loja_carta
							WHERE fabrica=$login_empresa ";
					if(strlen($tipo) > 0 and strlen($loja_carta) > 0) {
						$sql.=" AND tipo='$tipo'
								AND loja_carta=$loja_carta";
					} else {
						if(strlen($tipo) > 0) {
							$sql.=" AND tipo='$tipo' ";
						} else {
							if(strlen($loja_carta) > 0  and strlen($tipo) == 0) {
								$sql.=" AND loja_carta=$loja_carta";
							}
						}
					}
					$sql.=" ORDER by loja_carta asc";
					$res= pg_exec($con,$sql);
?>
					<table class='table_line' width='700' border='1' cellpadding="2" cellspacing="0" style='border-collapse: collapse' bordercolor='#D2E4FC'>
						<tr bgcolor='#596D9B'>	
							<td nowrap class='titulo' width='20%' align='center'>Nº Carta</td>
							<td nowrap class='titulo' width='20%' align='center'>Tipo</td>
							<td nowrap class='titulo' width='15%' align='center'>Email</td>
							<td nowrap class='titulo' width='25%' align='center'>Descrição</td>
							<td nowrap class='titulo' width='25%' align='center'>Ações</td>
						</tr>

	<?
					if(@pg_numrows($res)>0){
						for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
							$loja_carta   = trim(pg_result($res,$i,loja_carta));
							$tipo         = trim(pg_result($res,$i,tipo));
							$tipo         = strtoupper($tipo);
							$descricao    = trim(pg_result($res,$i,descricao));
							$email        = trim(pg_result($res,$i,email));
							$conteudo     = trim(pg_result($res,$i,conteudo));
							
							if ($cor=="#fafafa")	$cor= "#eeeeff";
							else					$cor= "#fafafa";
							
							echo "<tr bgcolor='$cor' class='table_line'>"; 
							echo "<td nowrap align='center'>$loja_carta</td>";
							echo "<td nowrap align='center'>$tipo</td>";
							echo "<td nowrap align='center' >$email</td>";
							echo "<td nowrap align='center' >$descricao</td>";
							echo "<td nowrap align='center' ><a href='$PHP_SELF?loja_carta=$loja_carta#tab1Cadastro'><font color='#0000ff' size=2><U>Alterar</U></font></a> | 		<a href=\"javascript:if (confirm('Deseja excluir?')) window.location='$PHP_SELF?excluir=excluir&loja_carta=$loja_carta'\"><font color='#0000ff' size=2><U>Excluir</U></font></a></td>";
						}
					}else{
						echo "<tr ><td colspan='5' align='center'><font color='#0000ff'><b>Nenhuma carta cadastrada!</font></b></td></tr>"; 
					}
				}

?>
				</td>
			</tr>
			</table>
			</form>
			</div>

			<div id="tab1Cadastro">
					<table>
					<FORM name='frm_carta' action='<? echo $PHP_SELF; ?>#tab1Cadastro' METHOD='POST'>
					<input type=hidden name=loja_carta value=<? echo $loja_carta; ?>>

						<tr>
							<td class='Label'>Tipo:   
<?
					if(strlen($loja_carta) > 0 ) {
						$sql= "SELECT * 
								FROM tbl_loja_carta
								WHERE fabrica=$login_empresa
								AND loja_carta=$loja_carta";

						$res= pg_exec($con, $sql);
						if(@pg_numrows($res)>0){
							$tipo         = trim(pg_result($res,0,tipo));
							$descricao    = trim(pg_result($res,0,descricao));
							$email        = trim(pg_result($res,0,email));
							$conteudo     = trim(pg_result($res,0,conteudo));
						}
}
							echo "&nbsp;&nbsp;<SELECT name='tipo' rel='ajuda' title='Selecione o tipo de carta a ser cadastrada' onChange=\"	
								if (this.value== 'assistencia' ) {
									document.getElementById('descricao').value='Carta Assistência';
								}else{
									if (this.value== 'cobranca' ) {
										document.getElementById('descricao').value='Carta Cobrança';
									}else{	
										if (this.value== 'venda' ) {
											document.getElementById('descricao').value='Carta Venda';
										}else{
											document.getElementById('descricao').value=' ';
										}
									}
								};\">";
?>
								<option value=''></option>
								<option value='assistencia' <? if ($tipo=='assistencia' ) echo "SELECTED"; ?>>ASSISTÊNCIA</option>
								<option value='cobranca' <? if ($tipo=='cobranca') echo "SELECTED"; ?>>COBRANÇA</option>
								<option value='venda' <? if($tipo=='venda') echo "SELECTED"; ?>>VENDA</option>
								</SELECT>
							</td>
						</tr>
						<tr>
							<td class='Label' width='40'>Email:
								<input class="Caixa" type="text" name="email" size="50" maxlength="80" value="<?echo $email;?>">
							</td>
						</tr>
						<tr>
							<td class='Label' width='40'>Descrição:
								<input class="Caixa" type="text" name="descricao"  id="descricao" size="50" maxlength="80" value="<? echo $descricao; ?>" readonly>
							</td>
						</tr>
						<tr>
							<td class='Label'>Carta :<br>
								<textarea class="Caixa" name="conteudo" rows='10' cols='100'><? echo $conteudo; ?></textarea></td>
						</tr>
						<tr>
<?							
							echo "<td align='center'>";
							echo "<BR><input type=submit class='Botao' rel='ajuda' title='Clique aqui para cadastrar ou alterar a carta' name=btn_acao value=Gravar onClick=\" if (this.value!='Cadastrar'){
									alert('Aguarde');
										} else {
									this.value='Cadastrando...';
										}\" >";

							echo "&nbsp;&nbsp;<input class='Botao' type='button' name='btn_limpar' onclick='limpar_form(this.form)'  value='Limpar' >";

							if(strlen($loja_carta) > 0) {
								echo "&nbsp;&nbsp;<a href='$PHP_SELF#tab1Cadastro' rel='ajuda' title='Clique aqui para fazer novo cadastro'><font size=2>&nbsp;&nbsp;Novo cadastro</font></a>";
								echo "</td>";
							}
?>
						</tr>
					</form>
				</table>
		</div>
			</td>
		</tr>
		<tr height='20'>
			<td  align='center' colspan='6'></td>
		</tr>
</table>

</table>


<?
 include "rodape.php";
 ?>