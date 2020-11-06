<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "call_center,gerencia";

include 'autentica_admin.php';

include 'funcoes.php';

$erro_msg = $_GET['erro_msg'];

$posto_codigo	= $_POST['posto_codigo'];

if(strlen($posto_codigo)==0)
	$posto_codigo	= $_GET['posto_codigo'];

$id_condicao= $_POST['id_condicao'];
$btn_acao	= $_POST['btn_acao'];

//CONDICAO DE PAGAMENTO (NAO USA DA TBL_CONDICAO)
$array_condicao[55] ="30/60/90DD (financeiro de 3%)";
$array_condicao[53] ="30/60DD (financeiro de 1,5%)";
$array_condicao[51] ="30DD (sem financeiro)";
$array_condicao[52] ="45DD (financeiro 1,5%)";
$array_condicao[57] ="60/90/120DD (financeiro 6,1%)";
$array_condicao[73] ="60/90DD (financeiro 4,5%)";
$array_condicao[54] ="60DD (financeiro 3%)";
$array_condicao[56] ="90DD (financeiro 6,1%)";

$array_id_condicao = array(55,53,51,52,57,73,54,56);

//GRAVAR A CONDIÇÃO DE PAGAMENTO PARA O POSTO
if($btn_acao== "Gravar") {
	if((strlen($posto_codigo)>0) and(strlen($id_condicao)>0)){
		//BUSCA A PK DO POSTO NA TABELA POSTO_FABRICA
 		$sql= "SELECT posto 
			FROM tbl_posto_fabrica 
			WHERE fabrica = 1 AND codigo_posto='$posto_codigo';";

		$res	= pg_exec ($con,$sql);

		if(pg_numrows($res)>0){
			$posto	= pg_result ($res,0,posto);
			
		}else{
			header ("Location: condicao_pagamento_alterar-igor.php?erro_msg=Não foi encontrado o posto: $posto_codigo!");
		}		
		$res = pg_exec ($con,"BEGIN TRANSACTION");
		
		//VERIFICA SE JA FOI CADASTRADO UMA CONDIÇÃO PARA O POSTO
		$sql= "select * 
			from tbl_black_posto_condicao 
			where posto = $posto;";

		$res	= pg_exec ($con,$sql);

		$condicao = $array_condicao[$id_condicao];
		if(pg_numrows($res)){
			//FAZ UPDATE		

			$sql= "UPDATE tbl_black_posto_condicao 
				SET  data		= current_timestamp,  
					 condicao	= '$condicao',  
					 id_condicao= $id_condicao 
				WHERE posto		= $posto;";
			
			$res = pg_exec ($con,$sql);

			if(pg_result_error($res)){
				$res = pg_exec ($con,"ROLLBACK;");
				$erro_msg.= "Falha na Alteração!";
			}else{
				$res = pg_exec ($con,"COMMIT;");
				$ok_msg= "Alterado com sucesso!";
			}
		
		}else{
			//FAZ INSERT

			$sql= "INSERT INTO 
					tbl_black_posto_condicao(
							posto, 
							data, 
							condicao, 
							id_condicao) 
					values(
							$posto, 
							current_timestamp, 
							'$condicao', 
							$id_condicao);";

			$res = pg_exec ($con,$sql);

			if(pg_result_error($res)){
				$res = pg_exec ($con,"ROLLBACK;;");
				$erro_msg.= "Falha no Cadastro!";
			}else{
				$res = pg_exec ($con,"COMMIT;");
				$ok_msg= "Cadastrado com sucesso!";

			}
		}
	}else{
		$erro_msg.= "É necessário selecionar o Posto e a Condição de Pagamento!";
	}
}

$sql= "SELECT tbl_posto.nome,
			tbl_posto.posto
	FROM tbl_posto_fabrica 
	JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
	WHERE tbl_posto_fabrica.fabrica = 1 AND codigo_posto='$posto_codigo';";
$res= pg_exec ($con,$sql);

if(pg_numrows($res)>0){
	$posto_nome= pg_result ($res,0,nome);
	$posto= pg_result ($res,0,posto);

	if(strlen($id_condicao)==0){
		$sql= "SELECT  id_condicao 
			   FROM tbl_black_posto_condicao 
			   WHERE posto = $posto;";
			   //echo "sql : $sql";
		$res= pg_exec ($con,$sql);

		if(pg_numrows($res)>0){
			$id_condicao	= pg_result ($res,0,id_condicao);
		}
	}

}

include "cabecalho.php";
?>

<!--=============== <FUNÇÕES> ================================!-->

<? include "javascript_pesquisas.php" ?>

<script language="JavaScript">

function VerificaSuaOS (sua_os){
	if (sua_os.value != "") {
		janela = window.open("pesquisa_sua_os.php?sua_os=" + sua_os.value,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=500,height=250,top=50,left=10");
		janela.focus();
	}
}

// ========= Função PESQUISA DE POSTO POR CÓDIGO OU NOME ========= //

function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_3.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=f";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		if ("<? echo $pedir_sua_os; ?>" == "t") {
			janela.proximo = document.frm_os.sua_os;
		}else{
			janela.proximo = document.frm_os.data_abertura;
		}
		janela.focus();
	}
}

// ========= Função PESQUISA DE PRODUTO POR REFERÊNCIA OU DESCRIÇÃO ========= //

function fnc_pesquisa_produto2 (campo, campo2, tipo, voltagem) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=t";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;
		janela.proximo      = document.frm_os.produto_serie;
		if (voltagem != "") {
			janela.voltagem = voltagem;
		}
		janela.focus();
	}
}

// ========= Função PESQUISA DE CONSUMIDOR POR NOME OU CPF ========= //

function fnc_pesquisa_consumidor (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_consumidor.php?nome=" + campo.value + "&tipo=nome&proximo=t";
	}
	if (tipo == "cpf") {
		url = "pesquisa_consumidor.php?cpf=" + campo.value + "&tipo=cpf&proximo=t";
	}
	if (campo.value != "") {
		if (campo.value.length >= 3) {
			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
			janela.cliente		= document.frm_os.consumidor_cliente;
			janela.nome			= document.frm_os.consumidor_nome;
			janela.cpf			= document.frm_os.consumidor_cpf;
			janela.rg			= document.frm_os.consumidor_rg;
			janela.cidade		= document.frm_os.consumidor_cidade;
			janela.estado		= document.frm_os.consumidor_estado;
			janela.fone			= document.frm_os.consumidor_fone;
			janela.endereco		= document.frm_os.consumidor_endereco;
			janela.numero		= document.frm_os.consumidor_numero;
			janela.complemento	= document.frm_os.consumidor_complemento;
			janela.bairro		= document.frm_os.consumidor_bairro;
			janela.cep			= document.frm_os.consumidor_cep;
			janela.proximo		= document.frm_os.revenda_nome;
			janela.focus();
		}else{
			alert("Digite pelo menos 3 caracteres para efetuar a pesquisa");
		}
	}
}

// ========= Função PESQUISA DE REVENDA POR NOME OU CNPJ ========= //

function fnc_pesquisa_revenda (campo, tipo) {
	var url = "";
	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome&proximo=t";
	}
	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj&proximo=t";
	}
	if (campo.value != "") {
		if (campo.value.length >= 3) {
			janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
			janela.nome			= document.frm_os.revenda_nome;
			janela.cnpj			= document.frm_os.revenda_cnpj;
			janela.fone			= document.frm_os.revenda_fone;
			janela.cidade		= document.frm_os.revenda_cidade;
			janela.estado		= document.frm_os.revenda_estado;
			janela.endereco		= document.frm_os.revenda_endereco;
			janela.numero		= document.frm_os.revenda_numero;
			janela.complemento	= document.frm_os.revenda_complemento;
			janela.bairro		= document.frm_os.revenda_bairro;
			janela.cep			= document.frm_os.revenda_cep;
			janela.email		= document.frm_os.revenda_email;
			janela.proximo		= document.frm_os.nota_fiscal;
			janela.focus();
		}else{
			alert("Digite pelo menos 3 caracteres para efetuar a pesquisa");
		}
	}
}

/* ============= Função FORMATA CNPJ =============================
Nome da Função : formata_cnpj (cnpj, form)
		Formata o Campo de CNPJ a medida que ocorre a digitação
		Parâm.: cnpj (numero), form (nome do form)
=================================================================*/
function formata_cnpj(cnpj, form){
	var mycnpj = '';
		mycnpj = mycnpj + cnpj;
		myrecord = "revenda_cnpj";
		myform = form;
		
		if (mycnpj.length == 2){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 6){
			mycnpj = mycnpj + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 10){
			mycnpj = mycnpj + '/';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
		if (mycnpj.length == 15){
			mycnpj = mycnpj + '-';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycnpj;
		}
}


/* ============= Função FORMATA CPF =============================
Nome da Função : formata_cpf (cpf, form)
		Formata o Campo de CPF a medida que ocorre a digitação
		Parâm.: cpf (numero), form (nome do form)
=================================================================*/
function formata_cpf(cpf, form){
	var mycpf = '';
		mycpf = mycpf + cpf;
		myrecord = "consumidor_cpf";
		myform = form;
		
		if (mycpf.length == 3){
			mycpf = mycpf + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycpf;
		}
		if (mycpf.length == 7){
			mycpf = mycpf + '.';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycpf;
		}
		if (mycpf.length == 11){
			mycpf = mycpf + '-';
			window.document.forms["" + myform + ""].elements[myrecord].value = mycpf;
		}
}



/* ========== Função AJUSTA CAMPO DE DATAS =========================
Nome da Função : ajustar_data (input, evento)
		Ajusta a formatação da Máscara de DATAS a medida que ocorre
		a digitação do texto.
=================================================================*/
function ajustar_data(input , evento)
{
	var BACKSPACE=  8; 
	var DEL=  46; 
	var FRENTE=  39; 
	var TRAS=  37; 
	var key; 
	var tecla; 
	var strValidos = "0123456789" ;
	var temp;
	tecla= (evento.keyCode ? evento.keyCode: evento.which ? evento.which : evento.charCode)

	if (( tecla == BACKSPACE )||(tecla == DEL)||(tecla == FRENTE)||(tecla == TRAS)) {
		return true; 
			}
		if ( tecla == 13) return false; 
		if ((tecla<48)||(tecla>57)){
			return false;
			}
		key = String.fromCharCode(tecla); 
		input.value = input.value+key;
		temp="";
		for (var i = 0; i<input.value.length;i++ )
			{
				if (temp.length==2) temp=temp+"/";
				if (temp.length==5) temp=temp+"/";
				if ( strValidos.indexOf( input.value.substr(i,1) ) != -1 ) {
					temp=temp+input.value.substr(i,1);
			}
			}
					input.value = temp.substr(0,10);
				return false;
}
</script>

<table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#ffffff">
	<tr>
		<td><img height="1" width="20" src="imagens/spacer.gif"></td>
	</tr>
<?	
	if (strlen($erro_msg)>0){
		echo "<tr>
				<td><font color='red'>$erro_msg</font></td>
			  </tr>";
	}else{
		if (strlen($ok_msg)>0){
			echo "<tr>
					<td><font color='blue'>$ok_msg</font></td>
				  </tr>";
		}
	}
?>

	<tr>	
		<td valign="top" align="left">
		<!-- ------------- Formulário ----------------- -->
		<form style="MARGIN: 0px; WORD-SPACING: 0px" name="frm_os" method="post" action="<? echo $PHP_SELF ?>">

		<table width="100%" border="0" cellspacing="5" cellpadding="0">
		<tr>
			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Código do Posto</font>
				<br>
					<input class="frm" type="text" name="posto_codigo" size="15" value="<? echo $posto_codigo ?>">&nbsp;<img src='imagens/btn_buscar5.gif'
border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2
(document.frm_os.posto_codigo,document.frm_os.posto_nome,'codigo')"></A>
			</td>

			<td nowrap>
				<font size="1" face="Geneva, Arial, Helvetica, san-serif">Nome do Posto</font>
				<br>
				<input class="frm" type="text" name="posto_nome" size="50" value="<? echo $posto_nome ?>">&nbsp;<img src='imagens/btn_buscar5.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_os.posto_codigo,document.frm_os.posto_nome,'nome')" style="cursor:pointer;"></A>
			</td>

		</tr>
		<tr>
			<td colspan ='2'>

			<font size="1" face="Geneva, Arial, Helvetica, san-serif">Condição de Pagamento</font>

			<SELECT NAME="id_condicao">
			<?
			echo "<option value=''>selecionar</option>";
			for ($i=0; $i < count($array_id_condicao); $i++) {
				$cond		= $array_condicao[$array_id_condicao[$i]];
				$id_cond	= $array_id_condicao[$i];

				if($id_cond == $id_condicao)
					echo "<option value='$id_cond' selected>$cond</option>\n";
				else
					echo "<option value='$id_cond'>$cond</option>\n";
			}
			?>
			</SELECT>
			&nbsp;
			<input type="submit" name="btn_acao" size="50" value="Gravar">

			</td>
		</tr>
		</table>
		<hr>
			<input type="submit" name="relatorio" size="220" align="center" value="Clique aqui para ver a relação">
	  </form>
	</td>
</tr>
</table>
<?
if($relatorio == "Clique aqui para ver a relação") {
	$sql1= "Select codigo_posto, condicao from tbl_black_posto_condicao join tbl_posto_fabrica using(posto) order by codigo_posto;";
	$res1 = pg_exec ($con,$sql1);
	if (@pg_numrows($res1) > 0) {
		echo "<table width='400' border='0' class='titulo' cellpadding='2' cellspacing='1' align='center'>\n";
		echo "<tr align='center'>\n";
		echo "<td colspan='1' bgcolor='#D9E2EF' nowrap>Posto</td>\n";
		echo "<td bgcolor='#D9E2EF' nowrap>Condição de Pagamento</td>\n";
		echo "</tr>\n";

		for ($y = 0 ; $y < @pg_numrows($res1) ; $y++){
			$codigo_posto  = trim(pg_result($res1,$y,codigo_posto));
			$condicao      = trim(pg_result($res1,$y,condicao));

			$cor = ($y % 2 == 0) ? "#F1F4FA" : "#F7F5F0";

			echo "<tr bgcolor='$cor'>\n";
			echo "<td width='15%' align='center' nowrap>$codigo_posto</a></td>";
			echo "<td width='85%' align='left' nowrap>$condicao</a></td>\n";
			echo "</tr>\n";
		}
		echo "</table>\n\n";
		echo "<br>\n\n";
	}
}
include "rodape.php";?>
