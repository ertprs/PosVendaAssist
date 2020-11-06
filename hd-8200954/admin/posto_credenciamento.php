<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';
$title = "POSTOS EM CREDENCIAMENTO";
include 'cabecalho.php';

$btn_acao = $_POST['btn_acao'];

if(strlen($btn_acao) > 0){
	$fabrica_cod = $_POST['fabrica'];
	$posto = $btn_acao;

	$sql = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = $fabrica_cod AND posto = $posto;";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res) > 0){
		$msg_erro = "Posto já cadastrado para este fabricante";
	}

	$sql = "SELECT tipo_posto FROM tbl_tipo_posto WHERE fabrica = $fabrica_cod;";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res) == 0){
		$msg_erro = "Não tem tipo do posto cadastrado para este fabricante.";
	}

	if(strlen($msg_erro) == 0 AND strlen($posto) > 0 AND strlen($fabrica_cod) > 0){
		if(strlen($msg_erro) == 0){
			$sql = "SELECT *
					FROM tbl_posto 
					LEFT JOIN tbl_posto_extra using(posto)
					WHERE posto = '$posto'
					ORDER BY posto DESC limit 1";
			$res = pg_exec($con,$sql);
//echo "$sql<br>";
		if(pg_numrows($res) > 0){
			$posto            = pg_result($res,0,posto);
			$nome             = pg_result($res,0,nome);
			$nome_fantasia    = pg_result($res,0,nome_fantasia);
			$cnpj             = pg_result($res,0,cnpj);
			$endereco         = pg_result($res,0,endereco);
			$numero           = pg_result($res,0,numero);
			$complemento      = pg_result($res,0,complemento);
			$bairro           = pg_result($res,0,bairro);
			$cidade           = pg_result($res,0,cidade);
			$estado           = pg_result($res,0,estado);
			$cep              = pg_result($res,0,cep);
			$email            = pg_result($res,0,email);
			$telefone         = pg_result($res,0,fone);
			$fax              = pg_result($res,0,fax);
			$contato          = pg_result($res,0,contato);
			$ie               = pg_result($res,0,ie);

		//echo " Posto: $posto";
		}

		$sql = "INSERT INTO tbl_posto_fabrica ( 
										posto           ,
										fabrica         ,
										codigo_posto    ,
										senha           ,
										login_provisorio,
										data_alteracao  ,
										credenciamento  ,
										contato_endereco,
										contato_numero  ,
										contato_complemento,
										contato_bairro  ,
										contato_cidade  ,
										contato_cep     ,
										contato_estado  ,
										nome_fantasia   ,
										contato_email   ,
										tipo_posto
								) VALUES (
										$posto            ,
										$fabrica_cod      ,
										'$cnpj'           ,
										'*'               ,
										't'               ,
										current_timestamp ,
										'DESCREDENCIADO'  ,
										'$endereco'       ,
										'$numero'         ,
										'$complemento'    ,
										'$bairro'         ,
										'$cidade'         ,
										'$cep'            ,
										'$estado'         ,
										'$nome_fantasia'  ,
										'$email'          ,
										(SELECT tipo_posto 
												FROM tbl_tipo_posto 
											WHERE fabrica = $fabrica_cod 
											ORDER BY tipo_posto LIMIT 1)
								); ";
			$res = pg_exec($con, $sql);
			//echo "$sql<br>";
			$msg_erro = "Posto $posto foi credenciado para o fabricante corretamente!";
		}
	}
}

?>


<style type="text/css">

input.botao2 {
	background:#ffffff;
	color:#000000;
	border:1px solid #d2e4fc;
}

.Tabela2{
	border:1px solid #C3C3C3;
}

.Tabela22{
	border:1px dotted #C3C3C3;
}

a.conteudo{
	color: #FFFFFF;
	font-family: Arial;
	FONT-SIZE: 8pt; 
	font-weight: bold;
	text-decoration: none;
	text-align: center;
	border: none;
}
a.conteudo:visited {
	color: #FFFFFF;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-decoration: none;
	text-align: center;
	border: none;
}

a.conteudo:hover {
	color: #FFFFCC;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-decoration: none;
	text-align: center;
	border: none;
}

a.conteudo:active {
	color: #FFFFFF;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-decoration: none;
	text-align: center;
	border: none;
}


.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
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
</style>

<script type="text/javascript">
function MostraEsconde(dados)
{
	if (document.getElementById)
	{
		var style2 = document.getElementById(dados);
		if (style2==false) return;
		if (style2.style.display=="block"){
			style2.style.display = "none";
			}
		else{
			style2.style.display = "block";
		}
	}
}

function informacoes(posto) {
    var url = "";
        url = "posto_informacoes.php?posto=" + posto;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=650,height=600,top=18,left=0");
        janela.focus();
}

function janela(a , b , c , d) {
	var arquivo = a;
	var janela = b;
	var largura = c;
	var altura = d;
	posx = (screen.width/2)-(largura/2);
	posy = (screen.height/2)-(altura/2);
	features="width=" + largura + " height=" + altura + " status=yes scrollbars=yes";
	newin = window.open(arquivo,janela,features);  
	newin.focus();
}
</script>

<?
if(strlen($msg_erro) > 0){
	echo "<br>";
	echo "<center><div style='background-color:#FCDB8F;width:300px;margin:0 auto;text-align:center;padding:3px'><p align='center' style='font-size: 12px'><b>$msg_erro</b></p></div></center>";
}
?>

<br><br>
<TABLE align='center' width='700' style='fon-size: 14px' border='0' class='Tabela22' bgcolor='#D9E2EF' cellspacing='0' cellpadding='0'>
<TR>
	<TD style='font-size: 14px; font-family: verdana' align='center'>POSTOS EM CREDENCIAMENTO</TD>
</TR>
<TR>
	<TD align='center'>Telecontrol Networking</TD>
</TR>
</TABLE>

<br>

<table width='700' border = '0' cellspacing='0' cellpadding='0' class='formulario' style='font-size: 10px; font-family: verdana;' align='center'>
<tr class='titulo_tabela'>
	<td height='20'>Parâmetros de Pesquisa</td>
</tr>
<tr>
	<td style='font-size: 14px' height='5'></td>
</tr>
<tr>
	<td align='center' style='font-size: 12px;'>
		<a href="<?echo $PHP_SELF?>?estado=AC">AC</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=AL">AL</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=AP">AP</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=AM">AM</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=BA">BA</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=CE">CE</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=DF">DF</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=ES">ES</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=GO">GO</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=MA">MA</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=SP">MG</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=MT">MT</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=MS">MS</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=PA">PA</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=PB">PB</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=PR">PR</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=PE">PE</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=PI">PI</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=RJ">RJ</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=RN">RN</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=RS">RS</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=RO">RO</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=RR">RR</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=SC">SC</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=SE">SE</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=SP">SP</a>&nbsp;&nbsp;
		<a href="<?echo $PHP_SELF?>?estado=TO">TO</a><br><br>
		<input type="button" onclick="javascript: window.location='<?echo $PHP_SELF?>?estado=todos'" value="Mostrar Todos os Postos" >
	</td>
</tr>
<tr>
	<td style='font-size: 12px' height='2'></td>
</tr>
</TABLE>


<?
// pega o endereço do diretório
$diretorio = '/var/www/assist/www/credenciamento/fotos'; 
//echo $diretorio;
// abre o diretório
$ponteiro  = opendir($diretorio);

// monta os vetores com os itens encontrados na pasta
while ($nome_itens = readdir($ponteiro)) {
	if($nome_itens <> "index_.php" AND $nome_itens <> "posto_cadastro_hbtec.php" AND $nome_itens <> "index2.php")
		$itens[] = $nome_itens;
}

// ordena o vetor de itens
sort($itens);

// percorre o vetor para fazer a separacao entre arquivos e pastas 
foreach ($itens as $listar) {

// retira "./" e "../" para que retorne apenas pastas e arquivos
	if ($listar!="." && $listar!=".."){ 

// checa se o tipo de arquivo encontrado é uma pasta
		if (is_dir($listar)) { 

// caso VERDADEIRO adiciona o item à variável de pastas
			$pastas[]=$listar; 
		} else{ 

// caso FALSO adiciona o item à variável de arquivos
			$arquivos[]=$listar;
		}
	}
}

if(strlen($_GET['estado']) > 0)  $estado = strtoupper(trim($_GET['estado']));

// lista os arquivos se houverem
if ($arquivos != "" AND strlen($estado) > 0) {
	$xarquivos = $arquivos;
	
	$sql = "SELECT distinct tbl_posto.posto , tbl_posto.nome, cidade, estado, fabricantes, descricao, email, linhas, funcionario_qtde, os_qtde
					FROM tbl_posto_extra 
					JOIN tbl_posto using(posto) 
				WHERE  tbl_posto_extra.data_modificado IS NOT NULL";
	if(strlen($estado) > 0 AND $estado <> 'TODOS'){
		$sql .=" AND UPPER(tbl_posto.estado) = '$estado' ";
	}

	$sql .= " ORDER BY tbl_posto.nome; ";

	$res = pg_exec($con, $sql);
	if(pg_numrows($res) > 0){
		for($i = 0; $i < pg_numrows($res); $i++){
			$nome           = pg_result($res,$i,nome);
			$cidade         = pg_result($res,$i,cidade);
			$estado         = pg_result($res,$i,estado);
			$posto          = pg_result($res,$i,posto);
			$fabricantes    = pg_result($res,$i,fabricantes);
			$descricao      = pg_result($res,$i,descricao);
			$codigo_posto   = @pg_result($res,$i,codigo_posto);
			$credenciamento = @pg_result($res,$i,credenciamento);
			$linhas             = pg_result($res,$i,linhas);
			$funcionario_qtde   = pg_result($res,$i,funcionario_qtde);
			$os_qtde            = pg_result($res,$i,os_qtde);

			$email   = pg_result($res,$i,email);
			echo "<FORM METHOD=POST NAME='frm_posto_$i' ACTION='$PHP_SELF'>";
			echo "<INPUT TYPE='hidden' NAME='btn_acao' value=''>";
			echo "<br><table  border = '0' cellspacing='1' cellpadding='0' class='formulario' align='center' width='700'>";
			echo "<tr>";
				echo "<td style='font-size: 14px' nowrap><b onClick=\"MostraEsconde('conteudo$i')\" style='cursor:pointer; cursor:hand;'>". strtoupper($nome) ."</b></td>";
				echo "<td align='left' width='100%'>&nbsp;&nbsp;";
				$foto = '0';
				foreach($xarquivos as $xlistar){
					//validações para não imprimir fotos de outros postos com nome final =
					$testa_1 = "$posto" . "_1.jpg";
					$testa_2 = "$posto" . "_2.jpg";
					$testa_3 = "$posto" . "_3.jpg";

					if(substr_count($xlistar, $posto) > 0 AND $foto == '0' AND ($testa_1 == $xlistar OR $testa_2 == $xlistar OR $testa_3 == $xlistar)) {
						echo "<a href= \"javascript: informacoes($posto)\"><img border='0' src='../imagens/camera_foto.gif' ALT='Imagens do Posto em Credenciamento' title='Imagens do Posto em Credenciamento'></a>&nbsp;&nbsp;";
						$z++;
						$foto = '1';
					}
				}
				echo "<a href= \"javascript: informacoes($posto)\"><img border='0' src='../imagens/informacao.png' ALT='Informações do Posto em Credenciamento' title='Informações do Posto em Credenciamento' width='16' height='16'></a></td>";
			echo "</td>";
			echo "<tr>";
				echo "<td align='right' colspan='2'>". strtoupper($cidade) . " - " . strtoupper($estado) ."</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td colspan='2'>";
			echo "<div id='conteudo$i' style='display: none;'>";
				echo "<table border='0' align='left' cellspacing='0' cellpadding='0' style='font-size: 10px'>";
				echo "<tr>";
					echo "<td align='left'><b>Fabricantes</b>: $fabricantes</td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td align='left'><b>Descrição:</b> $descricao</td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td align='left'><b>Linhas:</b> $linhas</td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td align='left'><b>Qtde de funcionários:</b> $funcionario_qtde</td>";
				echo "</tr>";
				echo "<tr>";
					echo "<td align='left'><b>Qtde de OS mês:</b> $os_qtde</td>";
				echo "</tr>";
				echo "</table>";
			echo "</div>";
			echo "</td>";
			echo "</tr>";
			echo "<tr>";
				echo "<td colspan='2' align='center'>CADASTRAR PARA &nbsp;";
					$sql2 = "SELECT fabrica, nome FROM tbl_fabrica WHERE fabrica not in(select fabrica from tbl_posto_fabrica where posto = $posto);";
					$res2 = pg_exec($con,$sql2);
					echo "<select name='fabrica' id='estado' size='1' class='frm'>";
					echo "<option value=''></option>";
					for($x=0;$x<pg_numrows($res2);$x++){
						$fabrica_cod  = pg_result($res2,$x,fabrica);
						$fabrica_nome = pg_result($res2,$x,nome);
						echo "<option value='$fabrica_cod'>$fabrica_nome</option>";
					}
				echo "</select>";
				echo "<img src='imagens/btn_autorizar.gif' ALT='Autorizar Troca de Peça' border='0' style='cursor:pointer; height:15px;' onclick=\"javascript:
					if (document.frm_posto_$i.btn_acao.value == '' ) {
						document.frm_posto_$i.btn_acao.value='$posto' ; 
						document.frm_posto_$i.submit() } else {alert ('Não clique no botão voltar do navegador, utilize somente os botões da tela')}\" >";
				echo "</td>";
			echo "</tr>";
			echo "</table>";
			echo "</FORM>";
		}
	}else{
		echo "<br><br>";
		echo "<p style='font-size: 12px; font-family: verdana;' align='center'>Nenhum resultado encontrado para o estado de $estado.</p>";
	}
	echo "Total: " . pg_numrows($res);
	$z = 1;
	$xposto = $posto;

}
include 'rodape.php';
?>