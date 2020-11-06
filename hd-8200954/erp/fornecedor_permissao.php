<?php

include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include '../funcoes.php';
$msg_erro="";
$sql_adicional = "";
$incluir=$_GET['incluir'];
$btn_acao=$_POST['pesquisar'];
$busca_por        = trim($_POST['busca_por']);
$campo_pesquisa   = trim($_POST['campo_pesquisa']);
$btn_confirma=$_GET['confirma'];
if(strlen($btn_confirma) == 0) 	$btn_confirma=$_POST['confirma'];

if(strlen($btn_acao) >0) {
	
	if(strlen($busca_por)==0) $msg_erro = "Preenche o campo para fazer pesquisa";

	if ($campo_pesquisa=='codigo')
		$sql_adicional .= "AND UPPER(tbl_pessoa.pessoa) like UPPER('%$busca_por%')";

	if ($campo_pesquisa=='nome')
		$sql_adicional .= "AND UPPER(tbl_pessoa.nome) like UPPER('%$busca_por%')";

	if ($campo_pesquisa=='cpf')
		$sql_adicional .= "AND tbl_pessoa.cnpj like UPPER('%$busca_por%')";

	if ($campo_pesquisa=='endereco')
		$sql_adicional .= "AND UPPER(tbl_pessoa.endereco) like UPPER('%$busca_por%')";

	if ($campo_pesquisa=='telefone')
		$sql_adicional .= "AND ( tbl_pessoa.fone_residencial like '%$busca_por%' OR tbl_pessoa.fone_comercial like '%$busca_por%' OR tbl_pessoa.cel like '%$busca_por%' OR tbl_pessoa.fax like '%$busca_por%' )";

	if ($campo_pesquisa=='email')
		$sql_adicional .= "AND UPPER(tbl_pessoa.email) like '%$busca_por%'";
}


		$linha	 = $_POST['linha'];
		$familia = $_POST['familia'];
		$modelo	 = $_POST['modelo'];
		$marca	 = $_POST['marca'];

if(strlen($btn_confirma) >0 and strlen($pessoa) > 0){
	if (strlen ($msg_erro) == 0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		if(count($_POST['linha'])>0){
			$array_linha  ="";
			for($i=0; $i<count($_POST['linha']); $i++){
				if(strlen($_POST['linha'][$i])>0){
					$aux_linha		= $_POST['linha'][$i];
					
					$sql= " SELECT linha, nome
							FROM tbl_fornecedor_linha 
							JOIN tbl_linha  using(linha) 
							WHERE tbl_fornecedor_linha.empresa= $login_empresa
								and pessoa_fornecedor=$pessoa
								and linha = $aux_linha;";
					$res= pg_exec($con, $sql);
					$msg_erro = pg_errormessage($con);

					if(pg_numrows($res)==0){
						$sql= " INSERT INTO tbl_fornecedor_linha (empresa, pessoa_fornecedor, linha)
								VALUES($login_empresa, $pessoa, $aux_linha);";
								//echo "sql: $sql";

						$res= pg_exec($con, $sql);
						$msg_erro = pg_errormessage($con);
					}
				}
			}
		}


		if(count($_POST['familia'])>0){
			$array_familia="";
			for($i=0; $i<count($_POST['familia']); $i++){
				if(strlen($_POST['familia'][$i])>0){
					$aux_familia	= $_POST['familia'][$i];				

					$sql= " SELECT familia, descricao
							FROM tbl_fornecedor_familia 
							JOIN tbl_familia  using(familia) 
							WHERE tbl_fornecedor_familia.empresa = $login_empresa
								and pessoa_fornecedor=$pessoa
								and familia = $aux_familia;";
					$res= pg_exec($con, $sql);

					$msg_erro = pg_errormessage($con);

					if(pg_numrows($res)==0){
						$sql= " INSERT INTO tbl_fornecedor_familia (empresa, pessoa_fornecedor, familia)
								VALUES($login_empresa, $pessoa, $aux_familia);";
						$res= pg_exec($con, $sql);
						$msg_erro = pg_errormessage($con);
					}
				}

			}
			
		}

		if(count($_POST['modelo'])>0){
			$array_modelo="";
			for($i=0; $i<count($_POST['modelo']); $i++){
				if(strlen($_POST['modelo'][$i])>0){
					$aux_modelo		= $_POST['modelo'][$i];
						$sql= " SELECT modelo, nome
								FROM tbl_fornecedor_modelo 
								JOIN tbl_modelo  using(modelo) 
								WHERE tbl_fornecedor_modelo.empresa = $login_empresa
									AND pessoa_fornecedor=$pessoa
									AND modelo = $aux_modelo;";
						$res= pg_exec($con, $sql);
						$msg_erro = pg_errormessage($con);
						
						if(pg_numrows($res) == 0){
							$sql= " INSERT INTO tbl_fornecedor_modelo (empresa, pessoa_fornecedor, modelo)
									VALUES($login_empresa, $pessoa, $aux_modelo);";
							$res= pg_exec($con, $sql);
							$msg_erro = pg_errormessage($con);
						}
					}
				}
			}
		

		if(count($_POST['marca'])>0){
			$array_marca="";
			for($i=0; $i<count($_POST['marca']); $i++){
				if(strlen($_POST['marca'][$i])>0){
					$aux_marca	=$_POST['marca'][$i];
					$sql= " SELECT marca, nome
							FROM tbl_fornecedor_marca 
							JOIN tbl_marca  using(marca) 
							WHERE tbl_fornecedor_marca.empresa = $login_empresa
								and pessoa_fornecedor=$pessoa
								and marca = $aux_marca;";

					$res= pg_exec($con, $sql);
					$msg_erro = pg_errormessage($con);

					if(pg_numrows($res)==0){
						$sql= " INSERT INTO tbl_fornecedor_marca (empresa, pessoa_fornecedor, marca)
								VALUES($login_empresa, $pessoa, $aux_marca);";
						$res= pg_exec($con, $sql);
						$msg_erro = pg_errormessage($con);
					}
				}
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
	?>
		<script language='javascript'>
			window.opener.location.reload();
			this.close();
		</script>
	<?
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}
?>

<script language='javascript' src='../ajax.js'></script>
<script language='javascript' src='../ajax_cep.js'></script>

<style>

.tabela{
	font-family: Verdana;
	font-size: 12px;
}

.Label{
	font-family: Verdana;
	font-size: 10px;
}
.Erro{
	font-family: Verdana;
	font-size: 12px;
	color:#FFF;
	border:#485989 1px solid; background-color: #990000;
}

</style>
<?
if (strlen($msg_erro)>0)      {
	echo "<div class='Erro'><center>$msg_erro</center></div><BR>";
	echo "<a href ='fornecedor_permissao.php?cotacao=$cotacao&incluir=incluir' rel='ajuda' title='Clique aqui para fazer nova busca'>Voltar</a>";
}
?>

<? if(strlen($cotacao) > 0 and strlen($incluir) > 0) { ?>
<table style=' bORDER:#485989 1px solid; background-color: #e6eef7' align='center' width='460' bORDER='0' class='tabela'>
	<tr height='20' bgcolor='#7392BF'>
		<td class='Titulo_Tabela' align='center' colspan='6'>Selecione o fornecedor pela pesquisa</td>
	</tr>
	<tr height='10'>
		<td  align='center' colspan='6'></td>
	</tr>
	<tr>
		<td class='Label'>
<?
		echo "<form name='frm_procura_simples' method='post' action='$PHP_SELF'>";
?>

			<table align='left' width='100%' border='0' class='tabela'>
				<input type='hidden' name='cotacao' value='<? echo $cotacao; ?>'>
				<tr><td>&nbsp;</td></tr>
				<tr>
					<td class='Label'>Buscar por: </td>
					<td align='left' ><input class="Caixa" type="text" name="busca_por" size="50" maxlength="80" value="<? echo $busca_por ?>" ></td>
				</tr>
				<tr>
					<td class='Label'>Campo</td>
					<td colspan='4'>
					<SELECT class='Caixa' name='campo_pesquisa'>
						<option value='nome' <? if ($campo_pesquisa=='nome') echo "SELECTED";?>>Nome</option>
						<option value='codigo' <? if ($campo_pesquisa=='codigo') echo "SELECTED"; ?>>Código</option>
						<option value='cpf' <? if ($campo_pesquisa=='cpf') echo "SELECTED";?>>CNPJ</option>
						<option value='telefone' <? if ($campo_pesquisa=='telefone') echo "SELECTED";?>>Telefone</option>
						<option value='endereco' <? if ($campo_pesquisa=='endereco') echo "SELECTED";?>>Endereço</option>
						<option value='email' <? if ($campo_pesquisa=='email') echo "SELECTED";?>>E-Mail</option>
					</SELECT>
					</td>
				</tr>
				<tr>
					<td colspan='6' align='center'>
						<br>
<?
						echo "<input name='pesquisar' type='submit' class='botao' value='Pesquisar' onClick=\" if (this.value!='Pesquisar'){
		alert('Aguarde');
	}else {
		this.value='Pesquisando...'; 
	}\">";
?>
					</td>
				</tr>
			</table>
		</form>
</table><BR><BR>
<?
}
if(strlen($msg_erro) == 0 and strlen($btn_acao) > 0){
	$sql = "SELECT
				tbl_pessoa.pessoa       ,
				tbl_pessoa.nome         ,
				tbl_pessoa.cnpj         ,
				tbl_pessoa.email        
			FROM tbl_pessoa
			JOIN tbl_pessoa_fornecedor USING(pessoa)
			WHERE tbl_pessoa.empresa = $login_empresa
			$sql_adicional
			ORDER BY nome ASC";
	$res= pg_exec ($con,$sql) ;
	$msg_erro = pg_errormessage($con);

	if (pg_numrows($res) > 0) {
		echo "<br>";
		echo "<a href ='fornecedor_permissao.php?cotacao=$cotacao&incluir=incluir' rel='ajuda' title='Clique aqui para fazer nova busca'>Fazer nova busca</a>";
		echo "<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='650' border='0'>";
		echo "<input type='hidden' name='cotacao' value='$cotacao'>";
		echo "<caption>";
		echo "Selecione o fornecedor desejado para incluir na cotação";
		echo "</caption>";
		echo "<tr height='20' bgcolor='#7392BF'>";

		
		echo "<td align='center' class='Titulo_Tabela'><b>Código</b></td>";
		echo "<td align='left'   class='Titulo_Tabela'><b>Descrição</b></td>";
		echo "<td align='left'   class='Titulo_Tabela'><b>CNPJ</b></td>";
		echo "<td align='left'   class='Titulo_Tabela'><b>Email</b></td>";
		echo "</tr>";	

		for ($k = 0; $k <pg_numrows($res) ; $k++) {
			$nome_fornecedor     = trim(pg_result($res,$k,nome));
			$pessoa              = trim(pg_result($res,$k,pessoa));
			$cnpj                = trim(pg_result($res,$k,cnpj));
			$email               = trim(pg_result($res,$k,email));

			if($k%2==0)$cor = '#D9E8FF';
			else               $cor = '#FFFFFF';

			echo "<tr bgcolor='$cor' class='linha'>";
			echo "<td align='center'>$pessoa</td>";
			echo "<td align='left'  ><a href='fornecedor_permissao.php?cotacao=$cotacao&pessoa=$pessoa&nome=$nome_fornecedor&escolha=1'>$nome_fornecedor</td>";
			echo "<td align='left'  >$cnpj</td>";
			echo "<td align='left'  >$email</td>";

			echo "</tr>";
		}
			echo "</table>";
	}else{
			echo "<br><br><p>Nenhum fornecedor encontrado.</p>";
			echo "<a href ='fornecedor_permissao.php?cotacao=$cotacao&incluir=incluir' rel='ajuda' title='Clique aqui para fazer nova busca'>Fazer nova busca</a>";
	}
}

if(strlen($_GET["escolha"])>0)        $escolha = $_GET["escolha"];


if(strlen($pessoa) > 0 and $escolha==1 ){
	$nome_fornecedor=$_POST['nome'];
	if(strlen($nome_fornecedor) == 0) {	$nome_fornecedor=$_GET['nome'];}

	$sql= "	SELECT 
				tbl_linha.nome as descricao1, 
				tbl_linha.linha as produto1,
				count(peca)
			FROM tbl_cotacao
			JOIN tbl_cotacao_item using(cotacao)
			JOIN tbl_peca using(peca)
			JOIN tbl_peca_item using(peca)
			JOIN tbl_linha on(tbl_peca_item.linha = tbl_linha.linha)
			WHERE cotacao=$cotacao
			GROUP BY tbl_linha.linha, tbl_linha.nome
			ORDER BY tbl_linha.nome";
	//echo "sql:$sql";
	$res_produto1= pg_exec($con, $sql);
	$msg_erro = pg_errormessage($con);

	//ENCONTRAR AS LINHAS QUE O FORNECEDOR TRABALHA

	$sql= "	SELECT distinct tbl_fornecedor_linha.linha as produto1
			FROM tbl_cotacao
			JOIN tbl_cotacao_item using(cotacao)
			JOIN tbl_peca_item using(peca)
			JOIN tbl_fornecedor_linha on(tbl_peca_item.linha = tbl_fornecedor_linha.linha)
			WHERE cotacao=$cotacao
				AND tbl_fornecedor_linha.pessoa_fornecedor = $pessoa";
//	echo "sql:$sql";
	$res_forn_produto1= pg_exec($con, $sql);
	$msg_erro = pg_errormessage($con);


	$array_forn_linha  ="";
	if(pg_numrows($res_forn_produto1)>0){
		$array_forn_linha  ="";
		for($i=0; $i<pg_numrows($res_forn_produto1); $i++){
			$produto1 = trim(pg_result($res_forn_produto1, $i, produto1));
			$array_forn_linha[$produto1]= $produto1;
		}
	}
	//FIM -----------------ENCONTRAR AS LINHAS QUE O FORNECEDOR TRABALHA


	$sql= "	SELECT 
				tbl_familia.descricao as descricao2, 
				tbl_familia.familia as produto2,
				count(peca)
			FROM tbl_cotacao
			JOIN tbl_cotacao_item using(cotacao)
			JOIN tbl_peca using(peca)
			JOIN tbl_peca_item using(peca)
			JOIN tbl_familia on(tbl_peca_item.familia= tbl_familia.familia)
			WHERE cotacao=$cotacao
			GROUP BY tbl_familia.familia, tbl_familia.descricao
			ORDER BY tbl_familia.descricao";

	$res_produto2= pg_exec($con, $sql);
	$msg_erro = pg_errormessage($con);



	//ENCONTRAR AS FAMILIA QUE O FORNECEDOR TRABALHA

	$sql= "	SELECT distinct tbl_fornecedor_familia.familia as produto2
			FROM tbl_cotacao
			JOIN tbl_cotacao_item using(cotacao)
			JOIN tbl_peca_item using(peca)
			JOIN tbl_fornecedor_familia on(tbl_peca_item.familia = tbl_fornecedor_familia.familia)
			WHERE cotacao=$cotacao
				AND tbl_fornecedor_familia.pessoa_fornecedor = $pessoa";
	//echo "sql:$sql";
	$res_forn_produto2= pg_exec($con, $sql);
	$msg_erro = pg_errormessage($con);

	$array_forn_familia  ="";
	if(pg_numrows($res_forn_produto2)>0){
		$array_forn_familia  ="";
		for($i=0; $i<pg_numrows($res_forn_produto2); $i++){
			$produto2 = trim(pg_result($res_forn_produto2, $i, produto2));
			$array_forn_familia[$produto2]= $produto2;
		}
	}
	//FIM -----------------ENCONTRAR AS FAMILIA QUE O FORNECEDOR TRABALHA

	
	
	$sql= "	SELECT 
				tbl_modelo.nome as descricao3, 
				tbl_modelo.modelo as produto3,
				count(peca)
			FROM tbl_cotacao
			JOIN tbl_cotacao_item using(cotacao)
			JOIN tbl_peca using(peca)
			JOIN tbl_peca_item using(peca)
			JOIN tbl_modelo on(tbl_peca_item.modelo = tbl_modelo.modelo)
			WHERE cotacao=$cotacao
			GROUP BY tbl_modelo.modelo, tbl_modelo.nome
			ORDER BY tbl_modelo.nome";

	$res_produto3= pg_exec($con, $sql);
	$msg_erro = pg_errormessage($con);

	//ENCONTRAR AS MODELO QUE O FORNECEDOR TRABALHA

	$sql= "	SELECT distinct tbl_fornecedor_modelo.modelo as produto3
			FROM tbl_cotacao
			JOIN tbl_cotacao_item using(cotacao)
			JOIN tbl_peca_item using(peca)
			JOIN tbl_fornecedor_modelo on(tbl_peca_item.modelo = tbl_fornecedor_modelo.modelo)
			WHERE cotacao=$cotacao
				AND tbl_fornecedor_modelo.pessoa_fornecedor = $pessoa";
	//echo "sql:$sql";
	$res_forn_produto3= pg_exec($con, $sql);
	$msg_erro = pg_errormessage($con);

	$array_forn_modelo  ="";
	if(pg_numrows($res_forn_produto3)>0){
		$array_forn_modelo  ="";
		for($i=0; $i<pg_numrows($res_forn_produto3); $i++){
			$produto3 = trim(pg_result($res_forn_produto3, $i, produto3));
			$array_forn_modelo[$produto3]= $produto3;
		}
	}
	//FIM -----------------ENCONTRAR AS LINHAS QUE O FORNECEDOR TRABALHA


	$sql= "	SELECT 
				tbl_marca.nome as descricao4, 
				tbl_marca.marca as produto4,
				count(peca)
			FROM tbl_cotacao
			JOIN tbl_cotacao_item using(cotacao)
			JOIN tbl_peca using(peca)
			JOIN tbl_peca_item using(peca)
			JOIN tbl_marca on(tbl_peca_item.marca = tbl_marca.marca)
			WHERE cotacao=$cotacao
			GROUP BY tbl_marca.marca, tbl_marca.nome
			ORDER BY tbl_marca.nome";

	$res_produto4= pg_exec($con,$sql);
	$msg_erro = pg_errormessage($con);


	//ENCONTRAR AS MODELO QUE O FORNECEDOR TRABALHA

	$sql= "	SELECT distinct tbl_fornecedor_marca.marca as produto4
			FROM tbl_cotacao
			JOIN tbl_cotacao_item using(cotacao)
			JOIN tbl_peca_item using(peca)
			JOIN tbl_fornecedor_marca on(tbl_peca_item.marca = tbl_fornecedor_marca.marca)
			WHERE cotacao=$cotacao
				AND tbl_fornecedor_marca.pessoa_fornecedor = $pessoa";
	//echo "sql:$sql";
	$res_forn_produto4= pg_exec($con, $sql);
	$msg_erro = pg_errormessage($con);

	$array_forn_marca ="";
	if(pg_numrows($res_forn_produto4)>0){
		$array_forn_marca ="";
		for($i=0; $i<pg_numrows($res_forn_produto4); $i++){
			$produto4 = trim(pg_result($res_forn_produto4, $i, produto4));
			$array_forn_marca[$produto4]= $produto4;
		}
	}
	//FIM -----------------ENCONTRAR AS LINHAS QUE O FORNECEDOR TRABALHA




	if(@pg_numrows($res_produto1) >0)
		$c_p1= @pg_numrows($res_produto1) ;
	if(@pg_numrows($res_produto2) >0)
		$c_p2= @pg_numrows($res_produto2) ;
	if(@pg_numrows($res_produto3) >0)
		$c_p3= @pg_numrows($res_produto3) ;
	if(@pg_numrows($res_produto4) >0)
		$c_p4= @pg_numrows($res_produto4) ;

	$maior_c_p=0;

	if($c_p1 > $maior_c_p)
		$maior_c_p= $c_p1;

	if($c_p2 > $maior_c_p)
		$maior_c_p= $c_p2;

	if($c_p3 > $maior_c_p)
		$maior_c_p= $c_p3;

	if($c_p4 > $maior_c_p)
		$maior_c_p= $c_p4;
	echo "<BR><BR>";
	echo "<center><font size=3><b>Selecione os itens que o fornecedor trabalha</b></font></center>";
	echo "<BR><BR>";
	echo "<center>$pessoa - $nome_fornecedor</center>";
	echo "<BR><table align='center' width='460' border='1' bordercolor='#ccccdd' bgcolor='#ddddee' cellspacing='0' cellpadding='0'>";		
	echo "<form name='frm_confirma' method='post' action='$PHP_SELF'>";
	echo "<input type='hidden' name='cotacao' value='$cotacao'>";
	echo "<input type='hidden' name='pessoa' value='$pessoa'>";
	echo "<tr class='menu_top'>";
	echo "<td bgcolor='#ddddff' width='100' align='center'><font color='#666699' ><b>Linha</b></font></td>";
	echo "<td bgcolor='#ddddff' width='100' align='center'> <font color='#666699' ><b>Família</b></font></td>";
	echo "<td bgcolor='#ddddff' width='100' align='center'><font color='#666699'><b>Marca</b></font></td>";
	echo "<td bgcolor='#ddddff' width='100' align='center'><font color='red'><b>Modelo</b></font></td>";
	echo "</tr>";

	for ( $i = 0 ; $i < $maior_c_p ; $i++ ){
		$produto1 ="";
		$produto2 ="";
		$produto3 ="";
		$produto4 ="";

		$descricao1="";
		$descricao2="";
		$descricao3="";
		$descricao4="";
		
		$linha ="";
		$familia="";
		$modelo="";
		$marca	="";

		$checked_1="";
		$checked_2="";
		$checked_3="";
		$checked_4="";

		if($i<$c_p1){
			$produto1	=trim(pg_result($res_produto1, $i, produto1));
			$descricao1	=trim(utf8_decode(pg_result($res_produto1, $i, descricao1)));
			if($array_forn_linha[$produto1] == $produto1)	$checked_1= "checked"." disabled";
			$linha	= "<input type='checkbox' name='linha[]' value='$produto1' $checked_1 ".$array_linha[$produto1].">$descricao1";
		}else $linha	= "&nbsp;";
		if($i<$c_p2){
			$produto2	=trim(pg_result($res_produto2, $i, produto2));
			$descricao2	=trim(utf8_decode(pg_result($res_produto2, $i, descricao2)));
			if($array_forn_familia[$produto2] == $produto2)	$checked_2= "checked"." disabled";
			$familia		= "<input type='checkbox' name='familia[]' value='$produto2' $checked_2 ".$array_familia[$produto2].">$descricao2";
		}else $familia	= "&nbsp;";
		if($i<$c_p3){
			$produto3	=trim(pg_result($res_produto3, $i, produto3));
			$descricao3	=trim(utf8_decode(pg_result($res_produto3, $i, descricao3)));
			if($array_forn_modelo[$produto3] == $produto3)	$checked_3= "checked"." disabled";
			$modelo	= "<input type='checkbox' name='modelo[]' value='$produto3' $checked_3 ".$array_modelo[$produto3].">$descricao3";
		}else $modelo	= "&nbsp;";
		if($i<$c_p4){
			$produto4	=trim(pg_result($res_produto4, $i, produto4));
			$descricao4	=trim(utf8_decode(pg_result($res_produto4, $i, descricao4)));
			if($array_forn_marca[$produto4] == $produto4)	$checked_4= "checked"." disabled";
			$marca		= "<input type='checkbox' name='marca[]' value='$produto4' $checked_4 ".$array_marca[$produto4].">$descricao4";
		}else $marca	= "&nbsp;";

		echo "<tr class='table_line' >";
		echo "<td align='left'>$linha</td>";
		echo "<td align='left'>$familia</td>";
		echo "<td align='left'>$modelo</td>";
		echo "<td align='left'>$marca</td>";
		echo "</tr>";
	}

	echo "</table><BR>";

	echo "<center><a href ='fornecedor_permissao.php?cotacao=$cotacao&incluir=incluir' rel='ajuda' title='Clique aqui para fazer nova busca'>Fazer nova busca</a>&nbsp;&nbsp;&nbsp;<input type=submit name=confirma value=confirmar>";
	echo "</form>";
}

 ?>




