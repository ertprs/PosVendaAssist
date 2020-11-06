<?


//as tabs definem a categoria do chamado
/* OBSERVACAO HBTECH
	* O produto Hibeats possui uma garantia estendida, ou seja, 1 ano de garantia normal e se ele entrar no site do hibeats ou solicitar via SAC a extensão o cliente ganha mais 6 meses de garantia ficando com 18 meses.
	* Para verificar os produtos que tem garantia estendida acessamos o bd do hibeats (conexao_hbflex.php) e verificamos o número de série.
		* Todos numeros de series vendidos estao no bd do hibeats, caso nao esteja lá não foi vendido ou a AKabuki não deu carga no bd.
		* AKabuki é a agencia que toma conta do site da hbflex, responsavel pelo bd e atualizacao do bd. Contato:
			Allan Rodrigues
			Programador
			AGÊNCIA KABUKI
			* allan@akabuki.com.br
			* www.akabuki.com.br
			( 55 11 3871-9976
	** Acompanhar os lancamentos destas garantias, liberado no ultimo dia do ano e ainda estamos acompanhando

*/
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';

require_once("../classes/mpdf/src/Mpdf.php");
echo "teste";
//$mpdf = new mPDF();
$mpdf = new \Mpdf\Mpdf;


$title = "Atendimento Call-Center";
$layout_menu = 'callcenter';

include 'funcoes.php';
function converte_data($date)
{
	//$date = explode("-", ereg_replace('/', '-', $date));
	$date = explode("-", preg_replace('/\//', '-', $date));
	$date2 = ''.$date[2].'-'.$date[1].'-'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}


function saudacao(){
	$hora = date("H");
	if($hora >= 7 and $hora <= 11){
		echo "bom dia";
	}
	if($hora>=12 and $hora <= 17){
		echo "boa tarde";
	}
	if($hora>=18){
		echo "boa noite";
	}
}

$callcenter = $_GET['callcenter'];
if(strlen($callcenter)>0){

	$sql = "SELECT	tbl_hd_chamado_extra.hd_chamado as callcenter,
					to_char(tbl_hd_chamado_extra.data_abertura,'DD/MM/YYYY') as abertura_callcenter,
					tbl_hd_chamado_extra.nome,
					tbl_hd_chamado_extra.endereco ,
					tbl_hd_chamado_extra.numero ,
					tbl_hd_chamado_extra.complemento ,
					tbl_hd_chamado_extra.bairro ,
					tbl_hd_chamado_extra.cep ,
                    tbl_hd_chamado_extra.fone ,
                    tbl_hd_chamado_extra.fone2 ,
					tbl_hd_chamado_extra.celular ,
					tbl_hd_chamado_extra.email ,
					tbl_hd_chamado_extra.cpf ,
					tbl_hd_chamado_extra.rg ,
					tbl_hd_chamado_extra.cliente ,
					tbl_cidade.nome as cidade_nome,
					tbl_cidade.estado,
					tbl_admin.login as atendente,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY') as data,
					tbl_hd_chamado.status,
					tbl_hd_chamado.categoria as natureza_operacao,
					tbl_posto.posto,
					tbl_posto_fabrica.contato_endereco,
					tbl_posto_fabrica.contato_numero,
					tbl_posto_fabrica.contato_bairro,
					tbl_posto_fabrica.contato_cidade,
					tbl_posto_fabrica.contato_cep,
					tbl_posto_fabrica.contato_estado,
					tbl_posto_fabrica.contato_email as posto_email,
					tbl_posto_fabrica.contato_fone_comercial as posto_fone,
					tbl_hd_chamado.titulo as assunto,
					tbl_hd_chamado_extra.revenda_nome,
					tbl_produto.produto,
					tbl_produto.referencia as produto_referencia,
					tbl_produto.descricao as produto_nome,
					tbl_produto.voltagem,
					tbl_produto.familia,
					tbl_defeito_reclamado.defeito_reclamado,
					tbl_defeito_reclamado.descricao as defeito_reclamado_descricao,
					tbl_hd_chamado_extra.reclamado,
					tbl_hd_chamado_extra.os,
					tbl_hd_chamado_extra.serie,
					to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') as data_nf,
					tbl_hd_chamado_extra.nota_fiscal,
					tbl_hd_chamado_extra.revenda,
					tbl_hd_chamado_extra.revenda_nome,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome as posto_nome,
					to_char(tbl_hd_chamado_extra.data_abertura_os,'DD/MM/YYYY') as data_abertura,
					tbl_hd_chamado_extra.receber_info_fabrica,
					tbl_os.sua_os as sua_os,
					tbl_hd_chamado_extra.consumidor_revenda,
					tbl_hd_motivo_ligacao.descricao AS hd_motivo_ligacao

		FROM tbl_hd_chamado
		JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
		LEFT JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
		JOIN tbl_admin  on tbl_hd_chamado.atendente = tbl_admin.admin
		LEFT JOIN tbl_posto on tbl_hd_chamado_extra.posto = tbl_posto.posto
		LEFT JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto  and tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto
		LEFT JOIN tbl_revenda on tbl_revenda.revenda = tbl_hd_chamado_extra.revenda
		LEFT JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
		LEFT JOIN tbl_os on tbl_os.os = tbl_hd_chamado_extra.os and tbl_os.fabrica = $login_fabrica
		LEFT JOIN tbl_hd_motivo_ligacao ON tbl_hd_chamado_extra.hd_motivo_ligacao = tbl_hd_motivo_ligacao.hd_motivo_ligacao AND tbl_hd_motivo_ligacao.fabrica = $login_fabrica
		WHERE tbl_hd_chamado.fabrica = $login_fabrica
		AND tbl_hd_chamado.fabrica_responsavel = $login_fabrica
		AND tbl_hd_chamado.hd_chamado = $callcenter";
		$res = pg_exec($con,$sql);

		if(pg_numrows($res)>0){
			$callcenter                = pg_result($res,0,callcenter);
			$abertura_callcenter       = pg_result($res,0,abertura_callcenter);
			$data_abertura_callcenter  = pg_result($res,0,data);
			$natureza_chamado          = pg_result($res,0,natureza_operacao);
			$consumidor_nome           = pg_result($res,0,nome);
			$cliente                   = pg_result($res,0,cliente);
			$consumidor_cpf            = pg_result($res,0,cpf);
			$consumidor_rg             = pg_result($res,0,rg);
			$consumidor_email          = pg_result($res,0,email);
            $consumidor_fone           = pg_result($res,0,fone);
            $consumidor_fone2         = pg_result($res,0,fone2);
			$consumidor_celular       = pg_result($res,0,celular);
			$consumidor_cep           = pg_result($res,0,cep);
			$consumidor_endereco      = pg_result($res,0,endereco);
			$consumidor_numero        = pg_result($res,0,numero);
			$consumidor_complemento   = pg_result($res,0,complemento);
			$consumidor_bairro        = pg_result($res,0,bairro);
			$consumidor_cidade        = pg_result($res,0,cidade_nome);
			$consumidor_estado        = pg_result($res,0,estado);
			$assunto                  = pg_result($res,0,assunto);
			$sua_os                   = pg_result($res,0,sua_os);
			$os                       = pg_result($res,0,os);
			$data_abertura            = pg_result($res,0,data_abertura);
			$produto                  = pg_result($res,0,produto);
			$produto_referencia       = pg_result($res,0,produto_referencia);
			$produto_nome             = pg_result($res,0,produto_nome);
			$voltagem                 = pg_result($res,0,voltagem);
			$serie                    = pg_result($res,0,serie);
			$data_nf                  = pg_result($res,0,data_nf);
			$nota_fiscal              = pg_result($res,0,nota_fiscal);
			$revenda                  = pg_result($res,0,revenda);
			$revenda_nome             = pg_result($res,0,revenda_nome);
			$posto                    = pg_result($res,0,posto);
			$posto_nome               = pg_result($res,0,posto_nome);
			$defeito_reclamado        = pg_result($res,0,defeito_reclamado);
			$reclamado                = pg_result($res,0,reclamado);
			$status_interacao         = pg_result($res,0,status);
			$atendente                = pg_result($res,0,atendente);
			$receber_informacoes      = pg_result($res,0,receber_info_fabrica);
			$codigo_posto             = pg_result($res,0,codigo_posto);
			$os                       = pg_result($res,0,os);
			$contato_endereco         = pg_result($res,0,contato_endereco);
			$contato_numero           = pg_result($res,0,contato_numero);
			$contato_bairro           = pg_result($res,0,contato_bairro);
			$contato_cidade           = pg_result($res,0,contato_cidade);
			$contato_estado           = pg_result($res,0,contato_estado);
			$contato_cep              = pg_result($res,0,contato_cep);
			$posto_fone               = pg_result($res,0,posto_fone);
			$posto_email              = pg_result($res,0,posto_email);
			$familia                  = pg_result($res,0,familia);
			$consumidor_revenda       = pg_result($res,0,consumidor_revenda);
			$hd_motivo_ligacao        = utf8_decode(pg_result($res,0,hd_motivo_ligacao));

			$tipo_atendimento = array(1 => 'extensao', 2 => 'reclamacao_produto', 3 => 'reclamacao_empresa',
				4 => 'reclamacao_at',5 => 'duvida_produto', 6 => 'sugestao', 7 => 'assistencia', 8 => 'garantia', 9 => 'troca_produto');

			if($natureza_chamado=='reclamacao_produto') $natureza_chamado = "Reclamação do Produto";
			if($natureza_chamado=='duvida_produto')     $natureza_chamado = "Dúvida do Produto";
			if($natureza_chamado=='reclamacao_empresa') $natureza_chamado = "Reclamação da Empresa";
			if($natureza_chamado=='reclamacao_produto') $natureza_chamado = "Reclamação do Produto";
			if($natureza_chamado=='reclamacao_at')      $natureza_chamado = "Reclamação da Assistência Técnica";
			if($natureza_chamado=='duvida_produto')     $natureza_chamado = "Dúvida do Produto";
			if($natureza_chamado=='troca_produto')      $natureza_chamado = "Troca de Produto";
			if($natureza_chamado=='extensao')           $natureza_chamado = "Extensão de Garantia";
			if($natureza_chamado=='sugestao')           $natureza_chamado = "Sugestão";
			if($natureza_chamado=='compra')             $natureza_chamado = "Onde Comprar";

		}

}
$cliente_novo = $_GET['cliente_novo'];
if(strlen($cliente_novo)>0){
	$sql = "select tbl_hd_chamado_extra.cliente          ,
					tbl_hd_chamado_extra.nome            ,
					tbl_hd_chamado_extra.endereco        ,
					tbl_hd_chamado_extra.numero          ,
					tbl_hd_chamado_extra.complemento     ,
					tbl_hd_chamado_extra.bairro          ,
					tbl_hd_chamado_extra.cep             ,
                    tbl_hd_chamado_extra.fone            ,
                    tbl_hd_chamado_extra.fone2           ,
					tbl_hd_chamado_extra.celular         ,
					tbl_hd_chamado_extra.email           ,
					tbl_hd_chamado_extra.cpf             ,
					tbl_hd_chamado_extra.rg              ,
					tbl_cidade.nome as nome_cidade       ,
					tbl_cidade.estado as estado_cidade   ,
					tbl_posto_fabrica.codigo_posto       ,
					tbl_posto.nome as posto_nome         ,
					tbl_posto_fabrica.contato_endereco   ,
					tbl_posto_fabrica.contato_numero     ,
					tbl_posto_fabrica.contato_bairro     ,
					tbl_posto_fabrica.contato_cidade     ,
					tbl_posto_fabrica.contato_cep        ,
					tbl_posto_fabrica.contato_estado     ,
					tbl_posto.fone as posto_fone         ,
					tbl_posto.email as posto_email
			from tbl_hd_chamado_extra
			JOIN tbl_cidade using(cidade)
			LEFT JOIN tbl_posto_fabrica USING(posto)
			LEFT JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			WHERE tbl_hd_chamado_extra.cliente = $cliente_novo
			AND   tbl_posto_fabrica.fabrica = $login_fabrica";
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){
			$consumidor_nome          = pg_result($res,0,nome);
			$cliente                  = pg_result($res,0,cliente);
			$consumidor_cpf           = pg_result($res,0,cpf);
			$consumidor_rg            = pg_result($res,0,rg);
			$consumidor_email         = pg_result($res,0,email);
            $consumidor_fone          = pg_result($res,0,fone);
            $consumidor_fone2         = pg_result($res,0,fone2);
			$consumidor_celular       = pg_result($res,0,celular);
			$consumidor_cep           = pg_result($res,0,cep);
			$consumidor_endereco      = pg_result($res,0,endereco);
			$consumidor_numero        = pg_result($res,0,numero);
			$consumidor_complemento   = pg_result($res,0,complemento);
			$consumidor_bairro        = pg_result($res,0,bairro);
			$consumidor_cidade        = pg_result($res,0,nome_cidade);
			$consumidor_estado        = pg_result($res,0,estado_cidade);
			$codigo_posto             = pg_result($res,0,codigo_posto);
			$posto_nome               = pg_result($res,0,posto_nome);
			$contato_endereco         = pg_result($res,0,contato_endereco);
			$contato_numero           = pg_result($res,0,contato_numero);
			$contato_bairro           = pg_result($res,0,contato_bairro);
			$contato_cidade           = pg_result($res,0,contato_cidade);
			$contato_estado           = pg_result($res,0,contato_estado);
			$contato_cep              = pg_result($res,0,contato_cep);
			$posto_email              = pg_result($res,0,posto_email);
			$posto_fone               = pg_result($res,0,posto_fone);
	}
}

//include "cabecalho.php";

if ($login_fabrica == 30) {

	$img_contrato = "../logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";

	if ($familia == 2680 || $familia == 2681) {//HD 246018
		$img_contrato = "../logos/cabecalho_print_itatiaia.jpg";
	}

}

$sql = "SELECT nome from tbl_fabrica where fabrica = $login_fabrica";
$res = pg_exec($con,$sql);
$nome_da_fabrica = pg_result($res,0,0);

$conteudo = "";
$conteudo = "<table style='text-align:left;font-size:10Px;font-family:Verdana;width:600px;' cellspacing='2'>";
echo "<caption style='border-bottom:1px #000000 solid;font-weight:bold;text-transform:uppercase;text-align:left;'>Callcenter N° $callcenter</caption>";
if ($login_fabrica == 30) {
	$conteudo .=  "<tr>";
	$conteudo .=  '<td align=\'left\' colspan="4"><img src="'.$img_contrato.'" height="40" alt="Logo"></td>';
	$conteudo .=  "</tr>";
}
$conteudo .=  "<tr>";
$conteudo .=  "<th width='35px' align='left'>Data:</th>";
$conteudo .=  "<td align='left'>$data_abertura_callcenter</td>";
$conteudo .=  "<th width='35px' align='left'>Status:</th>";
$conteudo .=  "<td align='left'>$status_interacao</td>";

$conteudo .=  "<th width='35px' align='left'>Atendente:</th>";
$conteudo .=  "<td align='left'>".strtoupper($atendente)."</td>";

$conteudo .=  "</tr>";
$conteudo .=  "</table>";

$conteudo .=  "<br><table style='text-align:left;font-size:10Px;font-family:Verdana;width:600px;' cellspacing='2'>";
$conteudo .=  "<caption style='border-bottom:1px #000000 solid;font-weight:bold;text-transform:uppercase;text-align:left;'>Informa&ccedil;&otilde;es do Consumidor</caption>";
$conteudo .=  "<tr>";
$conteudo .=  "<th width='35px' align='left'>Nome:</th>";
$conteudo .=  "<td align='left' colspan='7'>$consumidor_nome</td>";
$conteudo .=  "</tr>";

	$cpf_cnpj = (strlen($consumidor_cpf) > 11) ? "CNPJ:" : "CPF:";

	$conteudo .=  "<tr>";
	$conteudo .=  "<th align='left'>$cpf_cnpj</th>";
	$conteudo .=  "<td align='left' nowrap>$consumidor_cpf</td>";
	$conteudo .=  "<th width='35px' align='left'>Telefone:</th>";
	$conteudo .=  "<td align='left' nowrap>$consumidor_fone</td>";
	$conteudo .=  "<th width='35px' align='left'>Email:</th>";
	$conteudo .=  "<td align='left' nowrap colspan='3'>$consumidor_email</td>";
	$conteudo .=  "</tr>";

$conteudo .=  "<tr>";
$conteudo .=  "<th width='35px' align='left'>Endere&ccedil;o:</th>";
$conteudo .=  "<td align='left'  colspan='7'>$consumidor_endereco, $consumidor_numero $consumidor_complemento</td>";
$conteudo .=  "</tr>";
$conteudo .=  "<tr>";
$conteudo .=  "<th width='35px' align='left'>Bairro:</th>";
$conteudo .=  "<td align='left'>$consumidor_bairro</td>";
$conteudo .=  "<th width='35px' align='left'>Cidade:</th>";
$conteudo .=  "<td align='left' nowrap>$consumidor_cidade</td>";
$conteudo .=  "<th width='35px' align='left'>Estado:</th>";
$conteudo .=  "<td align='left'>$consumidor_estado</td>";
$conteudo .=  "<th width='35px' align='left'>CEP:</th>";
$conteudo .=  "<td align='left'>$consumidor_cep </td>";
$conteudo .=  "</tr>";

$conteudo .=  "<tr>";
$conteudo .=  "<th width='35px' align='left'>Telefone Comercial:</th>";
$conteudo .=  "<td align='left'>$consumidor_fone2</td>";
$conteudo .=  "<th width='35px' align='left'>Celular:</th>";
$conteudo .=  "<td align='left' colspan='5'>$consumidor_celular</td>";
$conteudo .=  "</tr>";
if(($login_fabrica == 86 || $login_fabrica == 90) AND(!empty($hd_motivo_ligacao) OR !empty($consumidor_revenda))){
	switch ($consumidor_revenda) {
		case 'R':
			$consumidor_revenda = 'Revenda';
			break;
		case 'C':
			$consumidor_revenda = 'Consumidor';
			break;
		case 'P':
			$consumidor_revenda = 'PA';
			break;
		case 'N':
			$consumidor_revenda = 'Consultor';
			break;
		case 'T':
			$consumidor_revenda = 'Representante';
			break;
	}
	$conteudo .=  "<tr>";
	$conteudo .=  "<th width='35px' align='left'>Tipo:</th>";
	$conteudo .=  "<td align='left'>$consumidor_revenda</td>";
	if(!empty($hd_motivo_ligacao)){
		$conteudo .=  "<th width='35px' align='left'>Motivo:</th>";
	$conteudo .=  "<td align='left'>$hd_motivo_ligacao</td>";
	}
	$conteudo .=  "</tr>";
}
$conteudo .=  "</table>";

$conteudo .=  "<br><table style='text-align:left;font-size:10Px;font-family:Verdana;width:600px;' cellspacing='2'>";
$conteudo .=  "<caption style='border-bottom:1px #000000 solid;font-weight:bold;text-transform:uppercase;text-align:left;'>Informa&ccedil;&otilde;es do Produto</caption>";
if ($login_fabrica <> 52 ) {
	$conteudo .=  "<tr align='left'>";
	$conteudo .=  "<th width='70px'align='left'>Linha do Produto:</th>";
	$conteudo .=  "<td align='left' colspan='3'>$produto_descricao</td>";
	$conteudo .=  "</tr>";

	$conteudo .=  "<tr>";
		$conteudo .=  "<th width='35px' align='left'>Produto:</th>";
		$conteudo .=  "<td align='left'>".trim($produto_nome)." - <b>Ref</b>: $produto_referencia</td>";
		if ($login_fabrica ==59){
			$conteudo .=  "</tr><tr>";
			$conteudo .=  "<th width='35px' align='left'>Voltagem:</th>";
			$conteudo .=  "<td align='left'>$voltagem</td>";
		}
		$conteudo .=  "<th width='35px' align='left'>S&eacute;rie:</th>";
		$conteudo .=  "<td align='left'>$serie</td>";
	$conteudo .=  "</tr>";

	$conteudo .=  "<tr>";
		$conteudo .=  "<th width='35px' align='left'>Nota Fiscal:</th>";
		$conteudo .=  "<td align='left'>$nota_fiscal</td>";
		$conteudo .=  "<th width='35px' align='left'>Data NF:</th>";
		$conteudo .=  "<td align='left'>$data_nf</td>";
	$conteudo .=  "</tr>";

	if(strlen($revenda) > 0){
		$sql = "SELECT cnpj FROM tbl_revenda WHERE revenda = $revenda";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res) > 0){
			$revenda = pg_result($res,0,cnpj);
		}
	}

	$conteudo .=  "<tr>";
		$conteudo .=  "<th width='35px' align='left'>Revenda:</th>";
		$conteudo .=  "<td align='left'>$revenda_nome</td>";
		$conteudo .=  "<th width='35px' align='left'>CNPJ da Revenda:</th>";
		$conteudo .=  "<td align='left'>$revenda</td>";
	$conteudo .=  "</tr>";
}
else {
	$conteudo .=  "<tr>";
	$conteudo .=  "<th width='45px' align='left'>Nota Fiscal:</th>";
	$conteudo .=  "<td align='left'>$nota_fiscal</td>";
	$conteudo .=  "<th align='left'>Data NF:</th>";
	$conteudo .=  "<td align='left'>$data_nf</td>";
	$conteudo .=  "</tr>";

	$conteudo .=  "<tr>";
	$conteudo .=  "<td width='35px' align='left'>Série</dh>";
	$conteudo .=  "<td align='left'>Produto</td>";
	$conteudo .=  "<td width='35px' align='left'>Defeito Reclamado</td>";
	$conteudo .=  "</tr>";

	$sql_produto = "SELECT serie,tbl_produto.referencia,tbl_produto.descricao as descricao_produto,tbl_defeito_reclamado.descricao as descricao_defeito from tbl_hd_chamado_item join tbl_produto using(produto) join tbl_defeito_reclamado using(defeito_reclamado) where hd_chamado = $callcenter";
	$res_produto = pg_exec($con,$sql_produto);

	if (pg_num_rows($res_produto)>0) {
		for ($i=0;$i<pg_num_rows($res_produto);$i++) {
			$descricao_produto = pg_result($res_produto,$i,descricao_produto);
			$referencia_produto = pg_result($res_produto,$i,referencia);
			$descricao_defeito = pg_result($res_produto,$i,descricao_defeito);
			$serie= pg_result($res_produto,$i,serie);

			$conteudo .=  "<tr>";
			$conteudo .=  "<td align='left'>$serie</td>";
			$conteudo .=  "<td align='left'>$referencia_produto - $descricao_produto</td>";
			$conteudo .=  "<td align='left'>$descricao_defeito</td>";
			$conteudo .=  "</tr>";
		}
	}
}
$conteudo .=  "</table>";

$conteudo .=  "<br><table  style='text-align:left;font-size:10Px;font-family:Verdana;width:600px;' cellspacing='2'>";
$conteudo .=  "<caption style='border-bottom:1px #000000 solid;font-weight:bold;text-transform:uppercase;text-align:left;'>Posto Autorizado</caption>";
$conteudo .=  "<tr>";
$conteudo .=  "<th width='35px' align='left'>Nome:</th>";
$conteudo .=  "<td align='left' colspan='5'>$posto_nome</td>";
$conteudo .=  "</tr>";

	$conteudo .=  "<tr>";
	$conteudo .=  "<th width='35px' align='left'>Telefone:</th>";
	$conteudo .=  "<td align='left' nowrap>$posto_fone</td>";
	$conteudo .=  "<th width='35px' align='left'>Email:</th>";
	$conteudo .=  "<td align='left' nowrap>$posto_email</td>";
	$conteudo .=  "</tr>";

$conteudo .=  "<tr>";
$conteudo .=  "<th width='35px' align='left'>Endere&ccedil;o:</th>";
$conteudo .=  "<td align='left'  colspan='5'>$contato_endereco, $contato_numero</td>";
$conteudo .=  "</tr>";
$conteudo .=  "<tr>";
$conteudo .=  "<th width='35px' align='left'>Bairro:</th>";
$conteudo .=  "<td align='left'>$contato_bairro</td>";
$conteudo .=  "<th width='35px' align='left'>Cidade:</th>";
$conteudo .=  "<td align='left'>$contato_cidade</td>";
$conteudo .=  "<th width='35px' align='left'>Estado:</th>";
$conteudo .=  "<td align='left'>$contato_estado</td>";
$conteudo .=  "<th width='35px' align='left'>CEP:</th>";
$conteudo .=  "<td align='left'>$contato_cep</td>";

$conteudo .=  "</tr>";

$conteudo .=  "</table>";

$conteudo .=  "<br><table style='text-align:left;font-size:10Px;font-family:Verdana;width:600px;' cellspacing='2'>";
$conteudo .=  "<caption style='border-bottom:1px #000000 solid;font-weight:bold;text-transform:uppercase;text-align:left;'>Reclama&ccedil;&atilde;o</caption>";
if ($login_fabrica == 51 and strlen($posto_nome) > 0) { // HD 63332
	$conteudo .=  "<tr>";
	$conteudo .=  "<th width='35px' align='left'>Código:</th>";
	$conteudo .=  "<td align='left'>$codigo_posto</td>";
	$conteudo .=  "<th width='35px' align='left'>Nome:</th>";
	$conteudo .=  "<td align='left'>$posto_nome</td>";
	$conteudo .=  "</tr>";
}
$conteudo .=  "<tr>";
$conteudo .=  "<th width='35px' align='left'>Tipo:</th>";
$conteudo .=  "<td align='left'>$natureza_chamado</td>";

$conteudo .=  "</tr>";

$conteudo .=  "<tr>";
$conteudo .=  "<td align='left' colspan='4'>$reclamado</td>";
$conteudo .=  "</tr>";

$conteudo .=  "</table>";

$conteudo .=  "<br><table style='text-align:left;font-size:10Px;font-family:Verdana;width:1000px;' cellspacing='2'>";
$conteudo .=  "<caption style='border-bottom:1px #000000 solid;font-weight:bold;text-transform:uppercase;text-align:left;'>Procedimento</caption>";
$conteudo .=  "<tr>";
$conteudo .=  "<td   align='left'>";

$sql = "SELECT
			tbl_hd_chamado_item.hd_chamado_item    ,
			to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY HH24:MI:SS') as data,
			tbl_hd_chamado_item.comentario         ,
			tbl_admin.login    ,
			tbl_hd_chamado_item.interno            ,
			tbl_hd_chamado_item.status_item        ,
			tbl_hd_chamado_item.interno            ,
			tbl_hd_chamado_item.enviar_email
		FROM tbl_hd_chamado_item
		JOIN tbl_admin on tbl_hd_chamado_item.admin = tbl_admin.admin
		JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado
		WHERE tbl_hd_chamado_item.hd_chamado = $callcenter
		AND   tbl_hd_chamado.fabrica_responsavel = $login_fabrica
				order by tbl_hd_chamado_item.data ";
	//	echo $sql;
$res = pg_exec($con,$sql);
if(pg_numrows($res)>0){
	for($x=0;pg_numrows($res)>$x;$x++){
		$data               = pg_result($res,$x,data);
		$comentario         = pg_result($res,$x,comentario);
		$atendente_resposta = pg_result($res,$x,login);
		$status_item        = pg_result($res,$x,status_item);
		$interno            = pg_result($res,$x,interno);
		$enviar_email       = pg_result($res,$x,enviar_email);
		$xx = $xx + 1;

        $conteudo .= "<table width='900px' border='0' align='left' cellpadding='2' cellspacing='1' style=' border:#000000 1px solid; background-color: #FFFFFF;font-size:10px'>
		<tr>
		<td align='left' valign='top'>
			<table style='font-size: 10px; border-bottom: #000000 1px solid;' width='100%'>
			<tr>";
			$conteudo .= "<td align='left' width='100%' nowrap> Resposta: <strong>$xx</strong> Por: <strong>". nl2br($atendente_resposta)."</strong> </td>
			<td align='right' nowrap>$data</td>
			</tr>
			</table>
		</td>
		</tr>";
		if($interno == "t"){
            $conteudo .=	"<tr>
			<td align='center' valign='top' bgcolor='#EFEBCF'>
			<font size='2'>Chamado Interno</font>
			</td>
			</tr>";
		}
		if($status_item == "Cancelado" or $status_item == "Resolvido"){
			$conteudo .= "<tr>
			<td align='center' valign='top' bgcolor='#EFEBCF'><font size='2'>$status_item</font>
			</td>
			</tr>";
		}
		if($enviar_email == "t"){
			$conteudo .= "<tr>
			<td align='center' valign='top' bgcolor='#EFEBCF'><font size='2'>Conteúdo enviado por e-mail para o consumidor</font>
			</td>
			</tr>";
		}
		$conteudo .= "<tr>
		<td align='left' valign='top' bgcolor='#FFFFFF'>". nl2br($comentario)."</td>
		</tr>
		</table><br>";

	}
}
$conteudo .= "</td>";
$conteudo .= "</tr>";
$conteudo .= "</table>";
$mpdf->WriteHTML(utf8_encode($conteudo));
$nome_arquivo = $login_fabrica != 45 ? $atendente."_".$callcenter.".pdf" : "chamado_".$callcenter.".pdf";
$mpdf->Output($nome_arquivo, "D");
exit;
?>
<script language="JavaScript">
//	window.print();
</script>
