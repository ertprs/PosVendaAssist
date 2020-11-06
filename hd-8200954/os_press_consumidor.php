<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include_once "includes/traducao.php";
?>

<style>

.vermelho {color: #f00!important}

body {
    margin: 0px;
}
.titulo {
    font-family: Arial;
    font-size: 7pt;
    text-align: right;
    color: #000000;
    background: #ced7e7;
    padding-right: 1ex;
    text-transform: uppercase;
}
.titulo2 {
    font-family: Arial;
    font-size: 7pt;
    text-align: center;
    color: #000000;
    background: #ced7e7;
    text-transform: uppercase;
}
.titulo3 {
    font-family: Arial;
    font-size: 10px;
    text-align: right;
    color: #000000;
    background: #ced7e7;
    height:16px;
    padding-left:5px;
    padding-right: 1ex;
    text-transform: uppercase;
}

.titulo4 {
    font-family: Arial;
    font-size: 10px;
    text-align: left;
    color: #000000;
    background: #ced7e7;
    height:16px;
    padding-left:0px;
}

.inicio {
    font-family: Arial;
    FONT-SIZE: 8pt;
    font-weight: bold;
    text-align: left;
    color: #FFFFFF;
    padding-left: 5px;
    text-transform: uppercase;
}

.conteudo {
    font-family: Arial;
    FONT-SIZE: 8pt;
    font-weight: bold;
    background: #F4F7FB;
	padding-left: 5px;
}

.justificativa{
    font-family: Arial;
    FONT-SIZE: 10px;
    background: #F4F7FB;
}

.Tabela{
    border:1px solid #d2e4fc;
    background-color:#485989;
    }

.subtitulo {
    font-family: Verdana;
    FONT-SIZE: 9px;
    text-align: left;
    background: #F4F7FB;
    padding-left:5px
}
.caixa{
    border:1px solid #666;
	font-family: courier;
}

.conteudo2 {
	font-family: Arial;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-align: left;
	background: #FFDCDC;
}

.conteudo_sac {
    font-family: Arial;
    FONT-SIZE: 10pt;
    text-align: left;
    background: #F4F7FB;
}

</style>

<?php
$cook_idioma = "pt-br";
$os = $_GET["os"];
$md5_os = $_GET["chave"];

if (($os == "") && ($md5_os == ""))
{
	echo "
	<table class='Tabela' align=center>
		<form method=get>
		<tr>
			<td class='titulo' style='text-align:center'>
				Para visualizar a OS, digite o número e a chave
			</td>
		</tr>
		<tr>
			<td class='conteudo'>
				Número da OS: <input type=text name=os size=10 class=caixa> Chave: <input type=text name=chave size=33 class=caixa>
			</td>
		</tr>
		<tr>
			<td align=center>
				<input type=submit value='Consultar OS'>
			</td>
		</tr>
		</form>
	</table>
	";

	die;
}

if(md5($os[1] . $os[3] . $os[5]) !== $md5_os) 
{
	echo "OS não localizada";
	die;
}

/*
Função extraída de /assist/www/autentica_usuario.php
Caso presice de modificações, modificar a funçao original e depois copiar

SUGESTÃO: colocar a função em um outro arquivo. Ex: funcoes.php
*/
if (!function_exists("traduz")) {
	function traduz($inputText,$con,$cook_idioma_pesquisa,$x_parametros = null){

		global $msg_traducao;
		global $PHP_SELF;

		$cook_idioma_pesquisa = strtolower($cook_idioma_pesquisa);

		if (strlen($cook_idioma_pesquisa)==0){
			$cook_idioma_pesquisa = 'pt-br';
		}

		$mensagem = $msg_traducao[$cook_idioma_pesquisa][$inputText];

		if (strlen($mensagem)==0){
			$mensagem = $msg_traducao['pt-br'][$inputText];
		}

		if (strlen($mensagem)==0){
			$mensagem = $msg_traducao['es'][$inputText];
		}

		if (strlen($mensagem)==0){
			$mensagem = $msg_traducao['en-us'][$inputText];
		}

		if (strlen($mensagem)==0){
			$mensagem = $inputText;

			$sql = "INSERT INTO tmp_traducao_falha (msg_id,idioma,programa)
					VALUES ('$inputText', '$cook_idioma_pesquisa','$PHP_SELF')";
			$x_res = @pg_exec($con,$sql);
		}

		if ($x_parametros){
			if (!is_array($x_parametros)){
				$x_parametros = explode(",",$x_parametros);
			}
			while ( list($x_variavel,$x_valor) = each($x_parametros)){
				$mensagem = preg_replace('/%/',$x_valor,$mensagem,1);
			}
		}

		return $mensagem;
	}
}

function formata_data($data)
{
	return(implode("/", array_reverse(explode("-", $data))));
}


$sql = "
SELECT
*,
tbl_produto.referencia AS produto_referencia,
tbl_produto.descricao AS produto_descricao,
tbl_defeito_reclamado.descricao AS defeito_reclamado_descricao,
tbl_defeito_constatado.descricao AS defeito_constatado_descricao,
tbl_causa_defeito.descricao AS causa_defeito_descricao

FROM
tbl_os
JOIN tbl_produto ON tbl_os.produto=tbl_produto.produto
LEFT JOIN tbl_defeito_reclamado ON tbl_os.defeito_reclamado=tbl_defeito_reclamado.defeito_reclamado
LEFT JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado=tbl_defeito_constatado.defeito_constatado
LEFT JOIN tbl_causa_defeito ON tbl_os.causa_defeito=tbl_causa_defeito.causa_defeito

WHERE
os=$os
";

$res = pg_exec($sql);

//Bloco de código que gera variáveis com nomes dos campos contendo o conteúdo dos mesmos
//Coloque o campo na SQL e o código irá gerar uma variável com o mesmo nome do banco de dados
//Observe que se tiver dois campos em tabelas diferentes com o mesmo nome (ex: qtde), deverá
//renomear o campo (ex: tbl_os_item.qtde AS os_item_qtde)
for($i = 0 ; $i < pg_num_fields($res); $i++)
{
	$campo = pg_field_name($res, $i);
	$tipo = pg_field_type($res, $i);		//recuperando o tipo de dado armazenado no campo no banco de dados
	
	//tratando cada tipo de daods de forma específica. ex: data => formata para o padrão dd/mm/yyyy
	switch($tipo)
	{
		case "date":
			$$campo = formata_data(pg_result($res, 0, $i));
		break;

		case "timestamptz":
			$temp = explode(" ", pg_result($res, 0, $i));
			$$campo = formata_data($temp[0]);
		break;

		case "timestamp":
			$temp = explode(" ", pg_result($res, 0, $i));
			$$campo = formata_data($temp[0]);
		break;

		default:
			$$campo = pg_result($res, 0, $i);
	}
}

if ($consumidor_revenda == "C") $consumidor_revenda = "CONSUMIDOR";
else $consumidor_revenda = "REVENDA";

echo "

<table width='700' border='0' cellspacing='1' cellpadding='0' class='Tabela' align='center'>
    <tr>
        <td rowspan='5' class='conteudo' width='300' align=center>
			" . traduz("os.fabricante",$con,$cook_idioma) . "<br>
			<b><FONT SIZE='6' COLOR='#C67700'>" . $os . "</FONT><br>
			[" . $consumidor_revenda . "]</b>
        </td>
        <td class='inicio' height='15' colspan='4'>" . traduz("datas.da.os",$con,$cook_idioma) . "</td>
    </tr>
    <tr>
        <td class='titulo' width='100' height='15'>" . traduz("abertura",$con,$cook_idioma) . "</td>
        <td class='conteudo' width='100' height='15'>" . $data_abertura . "</td>
        <td class='titulo' width='100' height='15'>" . traduz("digitacao",$con,$cook_idioma) . "</td>
        <td class='conteudo' width='100' height='15'>" . $data_digitacao . "</td>
    </tr>
    <tr>
        <td class='titulo' width='100' height='15'>" . traduz("fechamento",$con,$cook_idioma) . "</td>
        <td class='conteudo' width='100' height='15' id='data_fechamento'>" . $data_fechamento . "</td>
        <td class='titulo' width='100' height='15'>" . traduz("finalizada",$con,$cook_idioma) . "</td>
        <td class='conteudo' width='100' height='15' id='finalizada'>" . $data_finalizada . "</td>
    </tr>
    <tr>
        <td class='titulo'  height='15'>" . traduz("data.da.nf",$con,$cook_idioma) . "</td>
        <td class='conteudo'  height='15'>" . $data_nf . "</td>
        <td class='titulo' width='100' height='15'>" . traduz("fechado.em",$con,$cook_idioma) . "</td>
        <td class='conteudo' width='100' height='15'>" . $data_fechamento . "</td>
    </tr>
	<tr>
		<td class='titulo' width='100' height='15'>" . traduz("consertado",$con,$cook_idioma) . "</td>
		<td class='conteudo' width='100' height='15' id='consertado'>" . $data_consertado . "</td>
		<td class='titulo' width='100'height='15'></td>
		<td class='conteudo' width='100' height='15'></td>
	</tr>
</table>

<table width='700' border='0' cellspacing='1' cellpadding='0' class='Tabela' align='center'>
    <tr>
        <td class='inicio' height='15' colspan='4'>" . traduz("informacoes.do.produto",$con,$cook_idioma) . "</td>
    </tr>
    <tr >
        <td class='titulo' height='15' width='90'>" . traduz("referencia",$con,$cook_idioma) . "</td>
        <td class='conteudo' height='15'>" . $produto_referencia . "</td>
        <td class='titulo' height='15' width='90'>" . traduz("descricao",$con,$cook_idioma) . "</td>
        <td class='conteudo' height='15'>" . $produto_descricao . "</td>
        <td class='titulo' height='15' width='90'>" . traduz("n.de.serie",$con,$cook_idioma) . "</td>
        <td class='conteudo' height='15'>" . $serie . "</td>
        <td class='titulo' height='15' width='90'>" . traduz("numero.controle",$con,$cook_idioma) . "</td>
        <td class='conteudo' height='15'>" . $serie_reoperado . "</td>
    </tr>
</table>

<table width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>
<tr>
    <td class='titulo' height='15' width='300'>" . traduz("aparencia.geral.do.aparelho.produto",$con,$cook_idioma) . "</td>
    <td class='conteudo'>" . $aparencia_produto . "</td>
</tr>
</table>

<table width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>
<tr>
    <td class='titulo' height='15' width='300'>" . traduz("acessorios.deixados.junto.com.o.aparelho",$con,$cook_idioma) . "</td>
    <td class='conteudo'>" . $acessorios . "</td>
</tr>
</table>

<table width='700px' border='0' cellspacing='1' cellpadding='0' align='center'class='Tabela'>
    <tr>
        <td class='titulo' height='15'width='300'>" . traduz("informacoes.sobre.o.defeito",$con,$cook_idioma) . "</td>
        <td class='conteudo' >" . $defeito_reclamado_descricao . "</td>
    </tr>
</table>


<table width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>
    <tr>
        <td  height='15' class='inicio' colspan='4'>" . traduz("defeitos",$con,$cook_idioma) . "</td>
    </tr>
    <tr>
        <td class='titulo' height='15' width='90'>" . traduz("reclamado",$con,$cook_idioma) . "</td>
		<td class='conteudo' height='15' width='140'>" . $defeito_reclamado_descricao . "</td>
		<td class='titulo' height='15' width='90'>" . traduz("constatado",$con,$cook_idioma) . "</td>
        <td class='conteudo' height='15'>" . $defeito_constatado_descricao . "</td>
    </tr>

    <tr>
        <td class='titulo' height='15' width='90'>" . traduz("causa",$con,$cook_idioma) . "</td>
        <td class='conteudo' colspan='3' height='15'>" . $causa_defeito_descrucao . "</td>
    </tr>
</table>

<table width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>
    <tr>
        <td class='inicio' colspan='4' height='15'>" . traduz("informacoes.sobre.o.consumidor",$con,$cook_idioma) . "</td>
    </tr>
    <tr>
        <td class='titulo' height='15'>" . traduz("nome",$con,$cook_idioma) . "</td>
        <td class='conteudo' height='15' width='300'>" . $consumidor_nome . "</td>
        <td class='titulo'>" . traduz("fone1",$con,$cook_idioma) . "</td>
        <td class='conteudo' height='15'>" . consumidor_fone . "</td>
    </tr>
    <tr>
        <td class='titulo' height='15'>" . traduz("cpf.consumidor",$con,$cook_idioma) . "</td>
        <td class='conteudo' height='15'>" . $consumidor_cpf . "</td>
        <td class='titulo' height='15'>" . traduz("cep",$con,$cook_idioma) . "</td>
        <td class='conteudo' height='15'>" . $consumidor_cep . "</td>
    </tr>
    <tr>
        <td class='titulo' height='15'>" . traduz("endereco",$con,$cook_idioma) . "</td>
        <td class='conteudo' height='15'>" . $consumidor_endereco . "</td>
        <td class='titulo' height='15'>" . traduz("numero",$con,$cook_idioma) . "</td>
        <td class='conteudo' height='15'>" . $consumidor_numero . "</td>

    </tr>
    <tr>
        <td class='titulo' height='15'>" . traduz("complemento",$con,$cook_idioma) . "</td>
        <td class='conteudo' height='15'>" . $consumidor_complemento . "</td>
        <td class='titulo' height='15'>" . traduz("bairro",$con,$cook_idioma) . "</td>
        <td class='conteudo' height='15'>" . $consumidor_bairro . "</td>
    </tr>

    <tr>
        <td class='titulo'>" . traduz("cidade",$con,$cook_idioma) . "</td>
        <td class='conteudo'>" . $consumidor_cidade . "</td>
        <td class='titulo'>" . traduz("estado",$con,$cook_idioma) . "</td>
        <td class='conteudo'>" . $consumidor_estado . "</td>
    </tr>
   <tr>
        <td class='titulo'>" . traduz("email",$con,$cook_idioma) . "</td>
        <td class='conteudo'>" . $consumidor_email . "</td>
		<td class='titulo'>" . traduz("",$con,$cook_idioma) . "</td>
		<td class='conteudo'></td>
	</tr>
</table>

<table width='700px' border='0' cellspacing='1' cellpadding='0' align='center' class='Tabela'>
    <tr>
        <td class='inicio' colspan='4' height='15'>" . traduz("informacoes.da.revenda",$con,$cook_idioma) . "</td>
    </tr>
    <tr>

        <td class='titulo'  height='15' >" . traduz("nome",$con,$cook_idioma) . "</td>
        <td class='conteudo'  height='15' width='300'>" . $revenda_nome . "</td>
        <td class='titulo'  height='15' width='80'>" . traduz("cnpj.revenda",$con,$cook_idioma) . "</td>
        <td class='conteudo'  height='15'>" . $revenda_cnpj . "</td>
    </tr>
    <tr>
        <td class='titulo'  height='15'>" . traduz("nf.numero",$con,$cook_idioma) . "</td>
        <td class='conteudo vermelho'  height='15'>" . $nota_fiscal . "</FONT></td>
        <td class='titulo'  height='15'>" . traduz("data.da.nf",$con,$cook_idioma) . "</td>
        <td class='conteudo'  height='15'>" . $data_nf . "</td>
    </tr>
</table>

<table width='700px' border='0' cellspacing='1' cellpadding='0' align='center'  class='Tabela'>
	<tr>
		<td colspan='7' class='inicio'>
			" . traduz("diagnosticos.componentes.manutencoes.executadas",$con,$cook_idioma) . "
		</td>
	</tr>
	<tr>
		<td class='titulo2' width=300>" . traduz("componente",$con,$cook_idioma) . "</td>
		<td class='titulo2'>" . traduz("qtd",$con,$cook_idioma) . "</td>
		<td class='titulo2'>" . traduz("defeito",$con,$cook_idioma) . "</td>
		<td class='titulo2'>" . traduz("servico",$con,$cook_idioma) . "</td>
	</tr>";

$sql = "
SELECT
tbl_peca.descricao AS peca_descricao,
tbl_os_item.qtde,
	CASE WHEN tbl_os_item.defeito_descricao IS NULL THEN
		tbl_defeito.descricao
	ELSE
		tbl_os_item.defeito_descricao
	END AS defeito_descricao,
tbl_servico_realizado.descricao AS servico_realizado_descricao

FROM
tbl_os_item
JOIN tbl_os_produto ON tbl_os_item.os_produto=tbl_os_produto.os_produto
JOIN tbl_os ON tbl_os_produto.os=tbl_os.os
JOIN tbl_peca ON tbl_os_item.peca=tbl_peca.peca
JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado=tbl_servico_realizado.servico_realizado
JOIN tbl_defeito ON tbl_os_item.defeito=tbl_defeito.defeito

WHERE
tbl_os.os=" . $_GET["os"] . "
";
$res = pg_exec($con, $sql);


for($i = 0; $i < pg_num_rows($res); $i++)
{
	//Bloco de código que gera variáveis com nomes dos campos contendo o conteúdo dos mesmos
	//Coloque o campo na SQL e o código irá gerar uma variável com o mesmo nome do banco de dados
	//Observe que se tiver dois campos em tabelas diferentes com o mesmo nome (ex: qtde), deverá
	//renomear o campo (ex: tbl_os_item.qtde AS os_item_qtde)
	for($j = 0 ; $j < pg_num_fields($res); $j++)
	{
		$campo = pg_field_name($res, $j);
		$$campo = pg_result($res, $i, $j);
	}

	echo "
	<tr>
		<td class='conteudo'>" . $peca_descricao . "</td>
		<td class='conteudo'>" . $qtde . "</td>
		<td class='conteudo'>" . $defeito_descricao . "</td>
		<td class='conteudo'>" . $servico_realizado_descricao . "</td>
	</tr>";
}

echo "
</table>";

?>