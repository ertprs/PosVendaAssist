<?php
/* Relatório solicitado pela Black&Decker conforme hd-1097906 */
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_admin.php";
include "funcoes.php";
include "../helpdesk/mlg_funciones.php";

$msg = "";
$msg_erro = "";
// pega os valores das variaveis dadas como parametros de pesquisa e coloca em um cookie
setcookie("cookredirect", $_SERVER["REQUEST_URI"]); // expira qdo fecha o browser

$os             = $_GET["excluir"];
$posto_exclusao = $_GET["posto_exclusao"];
$codigo_posto_exclusao = $_GET["codigo_posto_exclusao"];

$regioes = array('NORTE'  => 'Região Norte',
				 'NORDESTE' => 'Região Nordeste',
				 'CENTRO-OESTE' => 'Região Centro-Oeste',
				 'SUDESTE' => 'Região Sudeste',
				 'SUL'  => 'Região Sul');

if($_GET['buscaCidade']){
	$uf = $_GET['estado'];

	$estado = "'$uf'";
	
	$sql = "SELECT DISTINCT contato_estado,contato_cidade FROM tbl_posto_fabrica WHERE fabrica = $login_fabrica and contato_estado in($estado) ORDER BY contato_estado,contato_cidade";
	$res = pg_query($con,$sql);
	if(pg_numrows($res) > 0){
		$retorno = "<option value=''>Todos</option>";
		for($i = 0; $i < pg_numrows($res); $i++){
			$cidade = pg_result($res,$i,'contato_cidade');
			$estado = pg_result($res,$i,'contato_estado');

			$nome_cidade = in_array($uf,array('BR-CO','BR-NE','BR-N')) ? "$cidade - $estado" : $cidade;

			$retorno .= "<option value='$cidade'>$nome_cidade</option>";
		}
	} else {
		$retorno .= "<option value=''>Cidade não encontrada</option>";
	}

	echo $retorno;
	exit;
}

if($_GET['buscaRegiao']){
	$reg = $_GET['regiao'];
	if($reg == "CENTRO-OESTE"){
		$estados = array('GO'=>'Goi&aacute;s',
						'MS'=>'Mato Grosso do Sul',
						'MT'=>'Mato Grosso',
						'DF'=>'Distrito Federal');
	} else if($reg == "NORDESTE"){

		$estados = array('SE'=>'Sergipe',
						'AL'=>'Alagoas',
						'RN'=>'Rio Grande do Norte',
						'MA'=>'Maranh&atilde;o',
						'PE'=>'Pernambuco',
						'PB'=>'Para&iacute;ba',
						'CE'=>'Cear&aacute;',
						'PI'=>'Piau&iacute;',
						'BA'=>'Bahia');

	} else if($reg == "NORTE"){
		$estados = array('TO'=>'Tocantins',
						'PA'=>'Par&aacute;',
						'AP'=>'Amapa',
						'RR'=>'Roraima',
						'AM'=>'Amazonas',
						'AC'=>'Acre',
						'RO'=>'Rond&ocirc;nia');
	} else if($reg == "SUDESTE"){
		$estados = array('ES'=>'Esp&iacute;rito Santos',
						'MG'=>'Minas Gerais',
						'RJ'=>'Rio de Janeiro',
						'SP'=>'S&atilde;o Paulo');
	} else if($reg == "SUL"){
		$estados = array('PR'=>'Paran&aacute;',
						'RS'=>'Rio Grande do Sul',
						'SC'=>'Santa Catarina');
	}
	
		$retorno = "<option value=''>Selecione um Estado</option>";
		foreach ($estados as $sigla_estado=>$nome_estado) {
			$nome_estado = utf8_encode($nome_estado);
			$retorno .= "<option value='$sigla_estado'>$nome_estado</option>";
		}

	echo $retorno;
	exit;
}

if (strlen($_POST["acao"]) > 0) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0)  $acao = strtoupper($_GET["acao"]);

if ($acao == "PESQUISAR") {

/* hd-1097906 - Validação dos campos */

	if( (!empty($_POST["data_inicial"]) && !empty($_POST["data_fim"])) ){
		$data_inicial=$_POST["data_inicial"];
		$data_fim=$_POST["data_fim"];
		if(strlen($msg_erro)==0){
			$dat = explode ("/", $data_inicial );//tira a barra
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
		}
		if(strlen($msg_erro)==0){
			$dat = explode ("/", $data_fim );//tira a barra
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
		}
		if(strlen($msg_erro)==0){
			$d_ini = explode ("/", $data_inicial);//tira a barra
			$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


			$d_fim = explode ("/", $data_fim);//tira a barra
			$nova_data_fim = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

			if($nova_data_fim < $nova_data_inicial){
				$msg_erro = "A Data Fim deve ser maior do que a Data Início.";
			}

			//verifica se intervalo de dadas é maior que 1 ano
			$time_dt_inicial = strtotime($nova_data_inicial.'+1 year');
			$time_dt_fim = strtotime($nova_data_fim);

			if ($time_dt_inicial < $time_dt_fim ) {
    			$msg_erro .= "O intervalo entre as datas não pode ser maior que 1 ano <br />";
   			}

   			//verifica se intervalo de datas é maior que 6 meses e se não é preenchido mais nenhum campo para filtro
   			$time_dt_inicial = strtotime($nova_data_inicial.'+6 months');

   			if ( ($time_dt_inicial < $time_dt_fim) )  {

   				if ( strlen($_POST["linha_produto"]) == 0){
   					$msg_erro .= "Preencha a Linha de Produtos e mais um parâmetro para filtro <br />";

   				}else if( (strlen($_POST["posto_codigo"]) == 0) && (strlen($_POST["regiao"]) == 0) && (strlen($_POST["estado"]) == 0) && (strlen($_POST["cidades"]) == 0) ){
					$msg_erro .= "Preencha mais um parâmetro para filtro <br />";
   				}
   				  
   			}
			//Fim Validação de Datas
		}
	}else{
		$msg_erro = "Por favor, preencha as datas";
	}

	if (strlen($_POST["posto_codigo"]) > 0) {
			$posto_codigo = trim($_POST["posto_codigo"]);
			$sqlPosto = " and tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";

	} else {
		$sqlPosto = "";
	}

    if (strlen($_POST["linha_produto"]) > 0) {
        $linha_produto = trim($_POST["linha_produto"]);
        $sqlLinha = " and tbl_linha.linha = $linha_produto ";
    } else {

        $sqlLinha = "";

    }

	if (strlen($_POST["marca"]) > 0) {
		$marca = trim($_POST["marca"]);
		$sqlM = " and tbl_produto.marca = $marca ";
	} else {

		$sqlM = "";
		
	}
	
	if (strlen($_POST["regiao"]) > 0)  {
		$regiao = trim($_POST["regiao"]);
		$sqlRegiao = " and tbl_estado.regiao = '$regiao' ";
		
	} else {
		$sqlRegiao = "";
	}

	if (strlen($_POST["estado"]) > 0)  {
		$estado = trim($_POST["estado"]);
		$sqlEstado = " and tbl_cidade.estado = '$estado' ";
		
	} else {
		$sqlEstado = "";
	}


	if (strlen($_POST["cidades"]) > 0){
		$cidade = trim($_POST["cidades"]);
		$sqlCidade = " and tbl_cidade.nome = '$cidade' ";
		
	} else {
		$sqlCidade = "";
	} 
}

$layout_menu = "callcenter";
$title       = "RELAÇÃO DE ORDENS DE SERVIÇO DE REVENDA LANÇADAS";

include "cabecalho.php";
?>

<link type="text/css" href="../plugins/jquery/datepick/telecontrol.datepick.css" rel="stylesheet" />


<script type="text/javascript" src="js/jquery-1.6.2.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>
<script src="../plugins/jquery/datepick/jquery.datepick.js" ></script>
<script src="../plugins/jquery/datepick/jquery.datepick-pt-BR.js" ></script>
<!-- PAGINAÇÃO -->
<link type="text/css" href="../plugins/jquery/jpaginate/jquery-ui.css" rel="stylesheet" />
<link type="text/css" href="../plugins/jquery/jpaginate/css/style.css" rel="stylesheet" />
<script src="../plugins/jquery/jpaginate/jquery-ui.min.js" ></script>
<script src="../plugins/jquery/jpaginate/jquery.paginate.js" ></script>
<!-- PAGINAÇÃO -->
<script>

$(function() {

	// o campo rows é onde grava quantas linhas a consulta retornou no total
	var rows = $('#rows').val();
	// divide o numero de linhas por 30 para saber quantas paginas irei ter
	var pages = parseInt(rows / 30);
	// resto da divisao do numero de linhas divido por 30 para ver quantas linhas ficou para tras
	var pages2 = parseInt(rows % 30);
	// verifica se o numero de linhas é maior que 0 e menor ou igual a 29 se entrar na condição acrescenta uma pagina
	if (pages2 <= 29 && pages2 > 0)
	{
		var pages2 = 1
	}
	// soma as duas variaveis para definir quantas paginas irá ter ao total
	var pages_total = parseInt(pages + pages2);

	// pages é a div que ira aparecer a paginação, não pode ser a principal e nem a de resultados
	$("#pages").paginate({
		// count é o numero de paginas
		count 		: pages_total,
		// start define qual sera a pagina inicial
		start 		: 1,
		// display é define quantas paginas podem aparecer na paginação
		display     : 10,
		border					: false,
		text_color  			: '#495677',
		background_color    	: 'transparent',
		text_hover_color  		: '#FFB70F',
		background_hover_color	: 'transparent',
		// rotate define se tera setas para voce navegar facilmetne ate outras paginas
		rotate      			: true,
		// images define se voce quer usar imagens ou caracteres
		images					: true,
		mouse					: 'press',
		// onchange é onde muda a pagina e p é o nome da minha div que estão os resultados da comsulta
		onChange     			: function(page){
											// esta primeira linha esconde a pagina atual
											$('._current').removeClass('_current').hide("drop", { direction: "left" }, 400);
											// esta segunda linha mostra a pagina clicada
											$('#p'+page).addClass('_current').delay(400).show("drop", { direction: "right" }, 400);
										  }
	});

});

</script>
<script language="JavaScript">

$(document).ready(function(){

	$('input[name=extrato]').numeric();
	
	$("#img_help_extrato").click(function(){
		alert("Disponibiliza a opção de imprimir os produtos das OSs que entraram no extrato consultado");
	});

	$("input[rel=data]").datepick();
});

function montaComboEstado(){

	var regiao = $('#regiao').val();

	$.ajax({
			url: "<?php echo $_SERVER['PHP_SELF']; ?>?buscaRegiao=1&regiao="+regiao,
			cache: false,
			success: function(data) {
				$('#estado').html(data);
			}

		});

}

function montaComboCidade2(){

	var estado = $('#estado').val();

	$.ajax({
			url: "<?php echo $_SERVER['PHP_SELF']; ?>?buscaCidade=1&estado="+estado,
			cache: false,
			success: function(data) {
				$('#cidades').html(data);
			}

		});

}

function fnc_pesquisa_revenda (campo, tipo) {

	var url = "";

	if (tipo == "nome") {
		url = "pesquisa_revenda.php?nome=" + campo.value + "&tipo=nome";
	}

	if (tipo == "cnpj") {
		url = "pesquisa_revenda.php?cnpj=" + campo.value + "&tipo=cnpj";
	}

	if (campo.value!="") {

		janela = window.open(url,"janela","toolbar=no,location=yes,status=yes,scrollbars=yes,directories=no,width=501,height=400,top=18,left=0");
		janela.nome			= document.frm_pesquisa.revenda_nome;
		janela.cnpj			= document.frm_pesquisa.revenda_cnpj;
		janela.fone			= document.frm_pesquisa.revenda_fone;
		janela.cidade		= document.frm_pesquisa.revenda_cidade;
		janela.estado		= document.frm_pesquisa.revenda_estado;
		janela.endereco		= document.frm_pesquisa.revenda_endereco;
		janela.numero		= document.frm_pesquisa.revenda_numero;
		janela.complemento	= document.frm_pesquisa.revenda_complemento;
		janela.bairro		= document.frm_pesquisa.revenda_bairro;
		janela.cep			= document.frm_pesquisa.revenda_cep;
		janela.email		= document.frm_pesquisa.revenda_email;
		janela.focus();

	} else{

		alert("Informe toda ou parte da informação para realizar a pesquisa!");

	}

}

</script>

<style>
a.botao {
	background-color:ButtonFace;
	color:ButtonText;
	font-size: 11px;
	padding: 2px 5px;
	border-width: 1px;
	border-radius: 3px;
	-o-border-radius: 3px;
	-moz-border-radius: 3px;
	-webkit-border-radius: 3px;
	border-style: outset;
	border-bottom-color: ButtonHighlight;
	border-right-color: ButtonHighlight;
	border-top-color: ButtonShadow;
	border-left-color: ButtonShadow;
}
a.botao:hover {
	background-color:ButtonFace;
	color:ButtonText;
	border-style: inset;
	border-bottom-color:ButtonShadow;
	border-right-color:	ButtonShadow;
	border-top-color:	ButtonHighlight;
	border-left-color:	ButtonHighlight;
}
.pagedemo{
        margin: 2px;
        padding: 10px 10px;
        text-align: center;
}

.demo{
        padding: 10px;
        margin: 0 auto;
}

.pages{
        width: 320px;
        position: relative;
        margin: 0 auto;
}

</style>


<style type="text/css">

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
	text-align:left;
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
#mapa_cidades { width: 147px !important; }
</style>

<? include "javascript_pesquisas.php"; ?>

<?
if(strlen($msg_erro)>0){
	echo "<div class='msg_erro'>$msg_erro</div>";
}
?>


<br>

<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">

<table width="700" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
	<? if (strlen($msg) > 0) { ?>
		<tr class="msg_erro">
			<td colspan="6"><?echo $msg?></td>
		</tr>

	<? } ?>
	<tr class="titulo_tabela">
		<td colspan="6">Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr>
		<td width="50">&nbsp;</td>
		<td align="left">Data Fechamento </td>
		<td>Data Início</td>
		<td colspan="2">Data Fim</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>
			<input type='text' size='12' maxlength='10' name='data_inicial' id='data_inicial' rel='data' value='<?=$data_inicial?>' class='frm date' />
		</td>
		<td colspan="2">
			<input type='text' size='12' maxlength='10' name='data_fim' id='data_fim' rel='data' value='<?=$data_fim?>' class='frm date' />
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align="left">Posto</td>
		<td>Código do Posto</td>
		<td colspan="2">Razão Social</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>
			<input type="text" name="posto_codigo" size="12" value="<?echo $posto_codigo?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo, document.frm_pesquisa.posto_nome, 'codigo')">
		</td>
		<td colspan="2">
			<input type="text" name="posto_nome" size="30" value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.posto_codigo, document.frm_pesquisa.posto_nome, 'nome')">
		</td>
		<td>&nbsp;</td>
	</tr>
	
	<tr>
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align="left" >Linha de Produtos</td>
		<td <?=($login_fabrica != 1) ? "colspan='2'" : ""?>>
				<select name='linha_produto' class='frm' >
					<option value=''></option>
					<?PHP
							//traz as linhas da fabrica ativa
							$sqlx = "SELECT linha,
											nome
									FROM tbl_linha
									WHERE fabrica=$login_fabrica and
										  ativo
									ORDER BY nome";

							$resx = pg_exec($con,$sqlx);
							
								if(pg_numrows($resx)>0){
									for($y=0;pg_numrows($resx)>$y;$y++){
										
										$linha     = trim(pg_result($resx,$y,'linha'));
										$nomeLinha = trim(pg_result($resx,$y,'nome'));
										
										echo "<option value='$linha'";
											if($linha == $linha_produto){
												print "selected";
											}
										echo ">$nomeLinha</option>";
									}
									
								}
								
						?>
				</select>
		</td>
<?
if($login_fabrica == 1){
?>
    <td align="left" >Marca</td>
    <td>
        <select name="marca" class="frm">
            <option value=''>Todas</option>
<?
    $sqlMarca = "
        SELECT  marca,
                nome
        FROM    tbl_marca
        WHERE   fabrica = $login_fabrica;
    ";
    $resMarca = pg_query($con,$sqlMarca);
    $marcas = pg_fetch_all($resMarca);

    foreach($marcas as $chave => $valor){
?>
            <option value="<?=$valor['marca']?>" <?=($valor['marca'] == $marca) ? "selected='selected'" : "" ?>><?=$valor['nome']?></option>
<?
    }
?>
        </select>
    </td>
<?
}
?>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="6">&nbsp;</td>
	</tr>

	<tr>
		<td>&nbsp;</td>
		<td align="left" >Região</td>
		<td colspan="2">
			<select title='Selecione a Região' style='width:200px;' name='regiao' id='regiao' onchange="montaComboEstado();" >
				<option></option>
				<? foreach ($regioes as $sigla=>$regiao_nome) {// a variavel $estados esta definida em ../helpdesk/mlg_funciones
						echo "<option value='$sigla'";
								if($sigla == $regiao){
									print "selected";
								}
						echo ">$regiao_nome</option>\n";
					}
				?>				
			</select>
		</td>
		<td>&nbsp;</td>
	</tr>

	<tr>
		<td>&nbsp;</td>
		<td align="left" >Estado</td>
		<td colspan="2">
			<select title='Selecione o Estado' style='width:200px;' name='estado' id='estado' onchange="montaComboCidade2();">
				<option></option>
				<? foreach ($estados as $sigla=>$estado_nome) {// a variavel $estados esta definida em ../helpdesk/mlg_funciones
						echo "<option value='$sigla'";
								if($sigla == $estado){
									print "selected";
								}
						echo ">$estado_nome</option>\n";
					}
				?>				
			</select>
		</td>
		<td>&nbsp;</td>
	</tr>
	
	<tr>
		<td >&nbsp;</td>
		<td align="left" > Cidade</td>
		<td colspan="2">
			<select title='Selecione uma cidade' name='cidades' id='cidades' style='width:200px;'>
				<option></option>
				<?php
				echo "<option value='$cidades'";		
						print "selected";
				echo ">$cidades</option>\n";
				?>
			</select>
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan="6" align="center">
			<input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px; cursor:pointer;" value="&nbsp;" onclick="document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" alt="Preencha as opções e clique aqui para pesquisar">
			
		</td>
	</tr>
</table>

</form>

<br>

<?
if (strlen($acao) > 0 && strlen($msg) == 0 && strlen($msg_erro) == 0) {
	
	if(strlen ($data_inicial) > 0 AND strlen ($data_fim) > 0 ){
			$x_data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2);

			$x_data_fim = substr ($data_fim,6,4) . "-" . substr ($data_fim,3,2) . "-" . substr ($data_fim,0,2);

			$sqlDataFechamento = " and tbl_os.finalizada BETWEEN '$x_data_inicial 00:00:00' and '$x_data_fim 23:59:59' ";
	}

	$sql = "SELECT 	distinct
				tbl_os.os || tbl_os.sua_os as os_sua_os,
				tbl_posto.posto,
				tbl_os.os,
				tbl_os.sua_os,
				tbl_posto_fabrica.codigo_posto,
				tbl_os.data_abertura,
				tbl_os.data_fechamento,
				tbl_produto.referencia_fabrica,
				tbl_produto.referencia,
				tbl_produto.descricao,
				tbl_posto.nome as nome_posto,
				tbl_revenda.nome as nome_revenda,
				tbl_revenda.cnpj,
				tbl_cidade.nome as nome_cidade,
				tbl_cidade.estado,
				tbl_estado.regiao,
				tbl_os.tipo_atendimento, /* para saber se é troca*/
				tbl_tipo_atendimento.descricao as descricao_tipo_atendimento, /*para saber se é troca*/
				tbl_os.cortesia, /*para saber se é cortesia*/
				tbl_produto.mao_de_obra,
				tbl_os.pecas, /*valor pecas*/
				tbl_produto.valor_troca,
				tbl_tipo_posto.tx_administrativa * tbl_produto.mao_de_obra as tx_admin,
				tbl_linha.nome as nome_linha						
			FROM tbl_os			
			JOIN tbl_posto 			ON tbl_posto.posto = tbl_os.posto
			JOIN tbl_posto_fabrica 		ON tbl_posto_fabrica.fabrica = $login_fabrica AND tbl_posto_fabrica.posto = tbl_os.posto
			JOIN tbl_tipo_posto 		ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = $login_fabrica
			JOIN tbl_linha 			ON tbl_linha.fabrica = $login_fabrica AND tbl_linha.ativo
			JOIN tbl_produto 		ON tbl_produto.produto = tbl_os.produto AND tbl_produto.linha = tbl_linha.linha	AND tbl_produto.fabrica_i = $login_fabrica
			LEFT JOIN tbl_tipo_atendimento 	ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = $login_fabrica
			JOIN tbl_revenda 		ON tbl_revenda.revenda = tbl_os.revenda
			JOIN tbl_cidade 		ON tbl_cidade.cidade  = tbl_revenda.cidade		
			JOIN tbl_estado 		ON tbl_estado.estado = tbl_cidade.estado
			WHERE tbl_os.fabrica = $login_fabrica 
				AND tbl_os.consumidor_revenda = 'R' 
				AND tbl_os.excluida IS NOT TRUE
				$sqlDataFechamento
				$sqlPosto
				$sqlLinha
				$sqlM
				$sqlEstado 
				$sqlRegiao
				$sqlCidade
			ORDER BY nome_posto,  os_sua_os ASC";
//  exit(nl2br($sql));
	$res = pg_exec($con,$sql);
	$rowsT = pg_num_rows($res);

	if($rowsT > 500){
		$rows = 500;
	}else{
		$rows = $rowsT;
	}

	if($rows>0){
		echo "<input id='rows' type='hidden' value='$rows'>";
		echo "<div id='pagination' class='demo'>";

		for ($i = 0; $i < $rows ; $i++)
		{
			// pega registros
			$idOs						= trim(pg_result($res,$i,'os'));
			$sua_os						= trim(pg_result($res,$i,'sua_os'));
			$codigo_posto				= trim(pg_result($res,$i,'codigo_posto'));
			$data_abertura 				= substr(trim(pg_result($res,$i,'data_abertura')),-2) . "/" . substr(trim(pg_result($res,$i,'data_abertura')),5,2) . "/" . substr(trim(pg_result($res,$i,'data_abertura')),0,4);
			$data_fechamento 			= substr(trim(pg_result($res,$i,'data_fechamento')),-2) . "/" . substr(trim(pg_result($res,$i,'data_fechamento')),5,2) . "/" . substr(trim(pg_result($res,$i,'data_fechamento')),0,4);
			$referencia_fabrica     	= trim(pg_result($res,$i,'referencia_fabrica'));
			$referencia 				= trim(pg_result($res,$i,'referencia'));
			$descricao_produto			= trim(pg_result($res,$i,'descricao'));
			$nome_posto 				= trim(pg_result($res,$i,'nome_posto'));
			$nome_revenda 				= trim(pg_result($res,$i,'nome_revenda'));
			$cnpj_revenda				= trim(pg_result($res,$i,'cnpj'));
			$nome_cidade 				= trim(pg_result($res,$i,'nome_cidade'));
			$estado 					= trim(pg_result($res,$i,'estado'));
			$regiao 					= trim(pg_result($res,$i,'regiao'));
			$tipo_atendimento 			= trim(pg_result($res,$i,'tipo_atendimento'));
			$descricao_tipo_atendimento	= trim(pg_result($res,$i,'descricao_tipo_atendimento'));
			$cortesia 					= trim(pg_result($res,$i,'cortesia'));
			$mao_de_obra 				= trim(pg_result($res,$i,'mao_de_obra'));
			$pecas 						= trim(pg_result($res,$i,'pecas'));
			$valor_troca 				= trim(pg_result($res,$i,'valor_troca'));
			$tx_admin 					= trim(pg_result($res,$i,'tx_admin'));
			$nome_linha 				= trim(pg_result($res,$i,'nome_linha'));			
			
			$os = $codigo_posto.$sua_os;
			// pega registros

			$cor = ($i % 2) ?"#F7F5F0":'#F1F4FA';

			if ($i == $z)
			{
				### aqui monta o cabeçalho das paginas
				if (empty($z))
				{
					### ### é obrigatorio colocar o _current na class da pagina inicial
					$class = "class='pagedemo _current'";
				}
				else
				{
					$class   = "class='pagedemo'";
					$display = "display: none;'";
				}
				### ### $p ira ser o numerador de cada div e sempre incrementara 1, no final ficara p1, p2, p3, p4, p5
				$p = $p + 1;
				### ### $z define quantos resultados quer por pagina, mudando aqui também tem que mudar na função e nas condições de fechamento
				$z = $z + 30;
				echo "<div id='p".$p."' $class style='$display'>"; 
					echo "<table class='tabela' width='' align=''>";
						echo "<tr class='titulo_coluna'>";
							echo 		"<td>OS</td>";
							echo 		"<td>Data Abertura</td>";
							echo 		"<td>Data Fechamento</td>";
							echo 		"<td>Referência Interna</td>";
							echo 		"<td>Referência Telecontrol</td>";
							echo 		"<td>Descrição Produto</td>";
							echo 		"<td>Código Posto</td>";
							echo 		"<td>Posto</td>";
							echo 		"<td>Revenda</td>";
							echo 		"<td>CNPJ REVENDA</td>";
							echo 		"<td>Cidade</td>";
							echo 		"<td>Estado</td>";
							echo 		"<td>Região</td>";
							echo 		"<td>Tipo da OS</td>";
							echo 		"<td>Valor M.O.</td>";
							echo 		"<td>Valor Peça em Garantia</td>";
							echo 		"<td>Valor Peça Faturada</td>";
							echo 		"<td>Valor Produto</td>";
							echo 		"<td>Valor Tx Adm</td>";
							echo 		"<td>Linha</td>";
						echo "</tr>";
			}

			### resultados
				echo "<tr bgcolor='$cor'>";
				echo "<td nowrap> <a href='os_press.php?os=$idOs' target='_blank'>$os</a> </td>";
				echo "<td nowrap> $data_abertura		 </td>";
				echo "<td nowrap> $data_fechamento		 </td>";
				echo "<td nowrap> $referencia_fabrica	 </td>";
				echo "<td nowrap> $referencia			 </td>";
				echo "<td nowrap> $descricao_produto	 </td>";
				echo "<td nowrap> $codigo_posto			 </td>";
				echo "<td nowrap> $nome_posto			 </td>";
				echo "<td nowrap> $nome_revenda			 </td>";
				echo "<td nowrap> $cnpj_revenda			 </td>";
				echo "<td nowrap> $nome_cidade			 </td>";
				echo "<td nowrap> $estado			 	 </td>";
				echo "<td nowrap> $regiao			 	 </td>";
				if($tipo_atendimento)
					echo "<td nowrap> $descricao_tipo_atendimento </td>";
				else if($cortesia != 'f'){
					echo "<td nowrap> Cortesia </td>";
				}else{
					echo "<td nowrap> Normal </td>";
				}
				echo "<td nowrap> $mao_de_obra	 </td>";
				echo "<td nowrap> $pecas		 </td>";
				echo "<td nowrap> $pecas		 </td>";
				echo "<td nowrap> $valor_troca	 </td>";
				echo "<td nowrap> $tx_admin		 </td>";
				echo "<td nowrap> $nome_linha	 </td>";
			echo "</tr>";

			### aqui define quando fechara a tabela e a div das paginas
			if ($i == ($z - 1))
			{
				echo "</table>";
				echo "</div>";
			}
			### aqui define quando fechara a tabela e a div da ultima pagina
			if ($i == ($rows - 1))
			{
				echo "</table>";
				echo "</div>";
			}
		}
		### div onde aparecera a paginação
		echo "<div id='pages' class='pages'></div>";
		echo "</div>";
		echo "<div class='msg_erro'>Nesta consulta, serão mostrados até 500 registros. Para visualizar o restante dos registros, faça o download do arquivo. </div>";
	
		/*botao para fazer download*/
		

		/*
			GERAR arquivo DOS RESULTADOS
		*/
		$date = date('Ymd-Hi');
		$arquivoTmp 	 = "/tmp/relatorio_somente_os_revenda_$login_fabrica-$login_admin-".$date.'.csv';
		$arquivoDownload = "xls/relatorio_somente_os_revenda_$login_fabrica-$login_admin-".$date.".csv";
		
		$xls = fopen($arquivoTmp, 'a');
		$thead  = 	"OS;";
		$thead .= 	"Data Abertura;";
		$thead .=	"Data Fechamento;";
		$thead .=	"Referência Interna;";
		$thead .=	"Referência telecontrol;";
		$thead .=	"Descrição Produto;";
		$thead .=	"Código Posto;";
		$thead .=	"Posto;";
		$thead .=	"Revenda;";
		$thead .=	"CNPJ REVENDA;";
		$thead .=	"Cidade;";
		$thead .=	"Estado;";
		$thead .=	"Região;";
		$thead .=	"Tipo da OS;";
		$thead .=	"Valor M.O.;";
		$thead .=	"Valor Peça em Garantia;";
		$thead .=	"Valor Peça Faturada;";
		$thead .=	"Valor Produto;";
		$thead .=	"Valor Tx Adm;";
		$thead .=	"Linha\n";
		fwrite($xls, $thead);
		fclose($xls);
		$tRow="";
		$z=0;
		for ($i = 0; $i < $rowsT ; $i++){
			 $cor = ($i % 2) ?"#F7F5F0":'#F1F4FA';
			 
			// pega registros
			$sua_os						= trim(pg_result($res,$i,'sua_os'));
			$codigo_posto				= trim(pg_result($res,$i,'codigo_posto'));
			$data_abertura 				= substr(trim(pg_result($res,$i,'data_abertura')),-2) . "/" . substr(trim(pg_result($res,$i,'data_abertura')),5,2) . "/" . substr(trim(pg_result($res,$i,'data_abertura')),0,4);
			$data_fechamento 			= substr(trim(pg_result($res,$i,'data_fechamento')),-2) . "/" . substr(trim(pg_result($res,$i,'data_fechamento')),5,2) . "/" . substr(trim(pg_result($res,$i,'data_fechamento')),0,4);
			$referencia_fabrica     	= trim(pg_result($res,$i,'referencia_fabrica'));
			$referencia 				= trim(pg_result($res,$i,'referencia'));
			$descricao_produto			= trim(pg_result($res,$i,'descricao'));
			$nome_posto 				= trim(pg_result($res,$i,'nome_posto'));
			$nome_revenda 				= trim(pg_result($res,$i,'nome_revenda'));
			$cnpj_revenda 				= trim(pg_result($res,$i,'cnpj'));
			$nome_cidade 				= trim(pg_result($res,$i,'nome_cidade'));
			$estado 					= trim(pg_result($res,$i,'estado'));
			$regiao 					= trim(pg_result($res,$i,'regiao'));
			$tipo_atendimento 			= trim(pg_result($res,$i,'tipo_atendimento'));
			$descricao_tipo_atendimento	= trim(pg_result($res,$i,'descricao_tipo_atendimento'));
			$cortesia 					= trim(pg_result($res,$i,'cortesia'));
			$mao_de_obra 				= trim(pg_result($res,$i,'mao_de_obra'));
			$pecas 						= trim(pg_result($res,$i,'pecas'));
			$valor_troca 				= trim(pg_result($res,$i,'valor_troca'));
			$tx_admin 					= trim(pg_result($res,$i,'tx_admin'));
			$nome_linha 				= trim(pg_result($res,$i,'nome_linha'));
			$os = $codigo_posto.$sua_os;
			$tRow .= "$os;";
			$tRow .= "$data_abertura;";
			$tRow .= "$data_fechamento;";
			$tRow .= "$referencia_fabrica;";
			$tRow .= "$referencia;";
			$tRow .= "$descricao_produto;";
			$tRow .= "$codigo_posto;";
			$tRow .= "$nome_posto;";
			$tRow .= "$nome_revenda;";
			$tRow .= "$cnpj_revenda;";
			$tRow .= "$nome_cidade;";
			$tRow .= "$estado;";
			$tRow .= "$regiao;";
				if($tipo_atendimento)
					$tRow .= "$descricao_tipo_atendimento;";
				else if($cortesia != 'f'){
					$tRow .= "Cortesia;";
				}else{
					$tRow .= "Normal;"; /*VERIFICAR COM LIN*/
				}
				$tRow .= "$mao_de_obra;";
				$tRow .= "$pecas;";
				$tRow .= "$pecas;";
				$tRow .= "$valor_troca;";
				$tRow .= "$tx_admin;";
				$tRow .= "$nome_linha\n";

				### escreve n arquivo a quantidade especificada em $z;
				if($i==$z){
					
					$z=$z+30;
					$xls = fopen($arquivoTmp, 'a');
					
					fwrite($xls, $tRow);
					fclose($xls);
					unset($xls);
					unset($tRow);
				}else if($i == ($rowsT - 1)){

					$xls = fopen($arquivoTmp, 'a');
					fwrite($xls, $tRow);
					fclose($xls);
					unset($xls);
					unset($tRow);
				}

		}

		$xls = fopen($arquivoTmp, 'a');
		/*$fecha .= "Total de Registros: $rowsT\n";
		fwrite($xls, $fecha);*/

		fclose($xls);

		if(file_exists($arquivoTmp)){
			system("mv $arquivoTmp $arquivoDownload");
		}
		if(file_exists($arquivoDownload)){

			echo "<div style='margin:1em auto;text-lign:center;font-size:0.9em' id='download_div'>
				<a href=\"$arquivoDownload\" class='botao' target='_blank' id='download_link' class='botao'>Arquivo</button></a>
			</div>";
		}
	} else {
		
		echo "Nenhum registro foi encontrado.";
	}


}
?>

<br>

<? include "rodape.php" ?>
