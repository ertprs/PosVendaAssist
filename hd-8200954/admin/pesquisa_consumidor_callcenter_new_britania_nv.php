<?include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';

$nome        = strtoupper(trim($_REQUEST["nome"]));
$cpf         = strtoupper(trim($_REQUEST['cpf']));
$os          = strtoupper(trim($_REQUEST['os']));
$atendimento = strtoupper(trim($_REQUEST['atendimento']));
$tipo        = strtoupper(trim($_REQUEST['tipo']));
?>
<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa Consumidores..</title>
<meta http-equiv=pragma content=no-cache>

<link rel="stylesheet" type="text/css" href="../css/lupas/lupas.css">
<style>
			body {
				margin: 0;
				font-family: Arial, Verdana, Times, Sans;
				background: #fff;
			}
</style>
<script type="text/javascript" src="js/jquery-1.4.2.js"></script>
<script>
	$(document).ready(function() {
		$("input[name=cpf]").click(function() {
			$("input[name=nome]").val("");
			$("input[name=tipo]").val("cpf");
		});
		$("input[name=nome]").click(function() {
			$("input[name=cpf]").val("");
			$("input[name=tipo]").val("nome");
		});
	});
</script>
</head>

<body>
<div class="lp_header">
	<img src='css/modal/excluir.png' alt='Fechar' class='lp_btn_fechar'  style="cursor: pointer;" onclick="window.parent.Shadowbox.close();" />
</div>
<div class='lp_nova_pesquisa'>
	<form action='<?=$_SERVER["PHP_SELF"]?>' method='POST' name='nova_pesquisa'>
		<input type='hidden' name='os' value='<?=$os?>' />
		<input type='hidden' name='atendimento' value='<?=$atendimento?>' />
		<input type='hidden' name='tipo' value='<?=$tipo?>' />

		<table cellspacing='1' cellpadding='2' style="border: 0;">
			<tr>
				<td>
					<label>Nome</label>
					<input type='text' name='nome' value='<?=$nome?>' style='width: 150px' maxlength='20' />
				</td>
				<td>
					<label>CPF</label>
					<input type='text' name='cpf' value='<?=$cpf?>' style='width: 370px' maxlength='80' />
				</td>
				<td colspan='2' class='btn_acao' valign='bottom'>
					<input type='submit' name='btn_acao' value='Pesquisar' />
				</td>
			</tr>
		</table>
	</form>
</div>

<?

if (strlen($nome) > 2) {

	echo "<div class='lp_pesquisando_por'>Buscando por nome do consumidor: $nome</div>";
	echo "<p>";
	$sql = "SELECT	distinct
					id              ,
					nome            ,
					endereco        ,
					numero          ,
					complemento     ,
					bairro          ,
					cep             ,
					cidade          ,
					fone            ,
					cpf_cnpj        ,
					rg              ,
					email           ,
					nome_cidade     ,
					estado          ,
					tipo            ,
					data_at         ,
					descricao       ,
					sua_os          ,
					status          ,
					hd_chamado      ,
					tipo_registro   ,
					referencia      ,
					serie           ,
					data_nf         ,
					nota_fiscal
				FROM (
						(
						SELECT  tbl_hd_chamado_extra.hd_chamado as id  ,
								tbl_hd_chamado_extra.nome              ,
								tbl_hd_chamado_extra.endereco          ,
								tbl_hd_chamado_extra.numero            ,
								tbl_hd_chamado_extra.complemento       ,
								tbl_hd_chamado_extra.bairro            ,
								tbl_hd_chamado_extra.cep               ,
								tbl_hd_chamado_extra.cidade            ,
								tbl_hd_chamado_extra.fone              ,
								tbl_hd_chamado_extra.cpf as cpf_cnpj   ,
								tbl_hd_chamado_extra.rg                ,
								tbl_hd_chamado_extra.email             ,
								tbl_cidade.nome AS nome_cidade         ,
								tbl_cidade.estado                      ,
								'C' as tipo                            ,
								TO_CHAR(data,'DD/MM/YYYY') AS data_at  ,
								tbl_produto.descricao                  ,
								tbl_os.sua_os                          ,
								status                                 ,
								tbl_hd_chamado.hd_chamado              ,
								tipo_registro                          ,
								tbl_produto.referencia                 ,
								case when tbl_hd_chamado_extra.serie is not null then tbl_hd_chamado_extra.serie else tbl_os.serie end as serie,
								case when tbl_hd_chamado_extra.data_nf is not null then to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') else to_char(tbl_os.data_nf,'DD/MM/YYYY') end as data_nf,
								case when tbl_hd_chamado_extra.nota_fiscal is not null then tbl_hd_chamado_extra.nota_fiscal else tbl_os.nota_fiscal end as nota_fiscal
						FROM tbl_hd_chamado_extra
						JOIN tbl_hd_chamado using (hd_chamado)
						LEFT JOIN tbl_cidade USING (cidade)
						LEFT JOIN tbl_produto USING(produto)
						LEFT JOIN tbl_os USING(os)
						WHERE tbl_hd_chamado_extra.nome ILIKE '%$nome%'
						AND   fabrica_responsavel = $login_fabrica
						)union(
						SELECT tbl_revenda.revenda as id ,
								tbl_revenda.nome         ,
								tbl_revenda.endereco     ,
								tbl_revenda.numero       ,
								tbl_revenda.complemento  ,
								tbl_revenda.bairro       ,
								tbl_revenda.cep          ,
								tbl_revenda.cidade       ,
								tbl_revenda.fone         ,
								tbl_revenda.cnpj  as cpf_cnpj,
								'' as rg                 ,
								tbl_revenda.email        ,
								tbl_cidade.nome AS nome_cidade,
								tbl_cidade.estado        ,
								'R' as tipo,
								''  as data_at,
								''  as descricao,
								''  as sua_os,
								''  as status,
								tbl_revenda.revenda as hd_chamado,
								''  as tipo_registro,
								''  as referencia,
								'' as serie,
								'' as data_nf,
								'' as nota_fiscal
						FROM tbl_revenda
						LEFT JOIN tbl_cidade USING (cidade)
						WHERE tbl_revenda.nome ILIKE '%$nome%'
						)
					) as X";

	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) == 0) {
		echo "<div class='lp_msg_erro'>'$nome' não encontrado</div>";
	}
}
else if (strlen($cpf) > 0 or strlen($atendimento) > 0 or strlen($os) > 0)
{
		if (strlen($cpf) > 0)
		{
			$xcpf = str_replace (".","",$cpf);
			$xcpf = str_replace ("-","",$xcpf);
			$xcpf = str_replace (",","",$xcpf);
			$xcpf = str_replace ("/","",$xcpf);
			$xcpf = str_replace (" ","",$xcpf);

			if (strlen($xcpf) > 0)
			{
				$busca = $xcpf;
				echo "<div class='lp_pesquisando_por'>Buscando por cpf do consumidor: $cpf</div>";
			}
		}

		if (strlen($atendimento) > 0)
		{
			$busca = $atendimento;
		}

		if (strlen($os) > 0)
		{
			$busca = $os;
		}

		$cond_1 = " 1=1 ";

		if($tipo=="CPF")
		{
			//hd 45707
			$cond_1  = " replace(replace(tbl_hd_chamado_extra.cpf, '.',''),'-','') ILIKE '%$busca%'";
			$xcond_1 = " tbl_revenda.cnpj LIKE  '%$busca%' ";
			$titulo  = "CPF/CNPJ";
		}

		if($tipo=="ATENDIMENTO"){
			$cond_1 = " 1 = 2 ";
			$xcond_1 = " 1 = 2 ";
		}

		if($tipo=="OS"){
			$titulo  = "OS";
		}


		if(strlen($busca)>0 AND strlen($atendimento)==0){
		$busca = str_replace (".","",$busca);

			if($tipo =="os") { // HD 48508
				$sql="SELECT DISTINCT os   as id           ,
					consumidor_nome        as nome         ,
					consumidor_endereco    as endereco     ,
					consumidor_numero      as numero       ,
					consumidor_complemento as complemento  ,
					consumidor_bairro      as bairro       ,
					consumidor_cep         as cep          ,
					consumidor_cidade      as cidade       ,
					consumidor_fone        as fone         ,
					consumidor_cpf         as cpf_cnpj     ,
					''                     as rg           ,
					consumidor_email       as email        ,
					consumidor_cidade      as nome_cidade  ,
					consumidor_estado      as estado       ,
					tbl_os.consumidor_revenda     as tipo  ,
					tbl_os.sua_os                          ,
					tbl_produto.descricao                  ,
					tbl_hd_chamado.status                  ,
					tbl_hd_chamado.hd_chamado              ,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY') as data_at,
					tipo_registro                           ,
					tbl_produto.referencia                  ,
					tbl_os.serie                            ,
					tbl_os.nota_fiscal                      ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY') as data_nf
					FROM tbl_os
					LEFT JOIN tbl_hd_chamado_extra USING(os)
					LEFT JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
					JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
					WHERE tbl_os.fabrica = $login_fabrica";

				$busca = strtoupper ($busca);

				$pos = strpos($busca, "-");
				if ($pos === false) {
					if(!ctype_digit($busca)){
						$sql .= " AND tbl_os.sua_os = '$busca' ";
					}else{
						$sql .= " AND (tbl_os.os_numero = '$busca' OR tbl_os.sua_os  = '$busca')";
					}
				}else{
					$conteudo = explode("-", $busca);
					$os_numero    = $conteudo[0];
					$os_sequencia = $conteudo[1];
					if(!ctype_digit($os_sequencia)){
						$sql .= " AND tbl_os.sua_os = '$busca' ";
					}else{
						$sql .= " AND tbl_os.os_numero = '$os_numero' AND tbl_os.os_sequencia = '$os_sequencia' ";
					}
				}
			}else{
				$sql = "SELECT	distinct
					id              ,
					nome            ,
					endereco        ,
					numero          ,
					complemento     ,
					bairro          ,
					cep             ,
					cidade          ,
					fone            ,
					cpf_cnpj        ,
					rg              ,
					email           ,
					nome_cidade     ,
					estado          ,
					tipo            ,
					data_at         ,
					descricao       ,
					sua_os          ,
					status          ,
					hd_chamado      ,
					tipo_registro   ,
					referencia      ,
					serie           ,
					data_nf         ,
					nota_fiscal
				FROM (
						(
						SELECT  tbl_hd_chamado_extra.hd_chamado as id  ,
								tbl_hd_chamado_extra.nome              ,
								tbl_hd_chamado_extra.endereco          ,
								tbl_hd_chamado_extra.numero            ,
								tbl_hd_chamado_extra.complemento       ,
								tbl_hd_chamado_extra.bairro            ,
								tbl_hd_chamado_extra.cep               ,
								tbl_hd_chamado_extra.cidade            ,
								tbl_hd_chamado_extra.fone              ,
								tbl_hd_chamado_extra.cpf as cpf_cnpj   ,
								tbl_hd_chamado_extra.rg                ,
								tbl_hd_chamado_extra.email             ,
								tbl_cidade.nome AS nome_cidade         ,
								tbl_cidade.estado                      ,
								'C' as tipo                            ,
								TO_CHAR(data,'DD/MM/YYYY') AS data_at  ,
								tbl_produto.descricao                  ,
								tbl_os.sua_os                          ,
								status                                 ,
								tbl_hd_chamado.hd_chamado              ,
								tipo_registro                          ,
								tbl_produto.referencia                 ,
								case when tbl_hd_chamado_extra.serie is not null then tbl_hd_chamado_extra.serie else tbl_os.serie end as serie,
								case when tbl_hd_chamado_extra.data_nf is not null then to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') else to_char(tbl_os.data_nf,'DD/MM/YYYY') end as data_nf,
								case when tbl_hd_chamado_extra.nota_fiscal is not null then tbl_hd_chamado_extra.nota_fiscal else tbl_os.nota_fiscal end as nota_fiscal
						FROM tbl_hd_chamado_extra
						JOIN tbl_hd_chamado using (hd_chamado)
						LEFT JOIN tbl_cidade USING (cidade)
						LEFT JOIN tbl_produto USING(produto)
						LEFT JOIN tbl_os USING(os)
						WHERE $cond_1
						AND   fabrica_responsavel= $login_fabrica
						)union(
						SELECT tbl_revenda.revenda as id ,
								tbl_revenda.nome         ,
								tbl_revenda.endereco     ,
								tbl_revenda.numero       ,
								tbl_revenda.complemento  ,
								tbl_revenda.bairro       ,
								tbl_revenda.cep          ,
								tbl_revenda.cidade       ,
								tbl_revenda.fone         ,
								tbl_revenda.cnpj  as cpf_cnpj,
								'' as rg                 ,
								tbl_revenda.email        ,
								tbl_cidade.nome AS nome_cidade,
								tbl_cidade.estado        ,
								'R' as tipo,
								''  as data_at,
								''  as descricao,
								''  as sua_os,
								''  as status,
								tbl_revenda.revenda as hd_chamado,
								''  as tipo_registro,
								''  as referencia,
								'' as serie,
								'' as data_nf,
								'' as nota_fiscal
							FROM tbl_revenda
							LEFT JOIN tbl_cidade USING (cidade)
							WHERE $xcond_1
							)
						) as X
						ORDER BY nome";
			}
		//echo nl2br($sql); exit;
		$res = pg_exec ($con,$sql);
		if (pg_numrows ($res) == 0) {
			echo "<div class='lp_msg_erro'>'$busca' não encontrado</div>";
		}
	}
}
else
{
	echo "<div class='lp_msg_erro'>Informar toda ou parte da informação para realizar a pesquisa!</div>";
}

if(pg_numrows($res)>0 AND strlen($atendimento)==0){

	echo "<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela'>";
		echo "<TR style='cursor: default;'>";
			echo "<td>Data</td>";
			echo "<td>Produto</td>";
			echo "<td>Atendimento</td>";
			echo "<td>Cliente</td>";
			echo "<td>OS</td>";
			echo "<td>REGISTRO</td>";
			echo "<td>Status</td>";
		echo "</TR>";

	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$cliente     = trim(pg_result($res,$i,id));
		$nome        = str_replace("'","",trim(pg_result($res,$i,nome)));
		$cpf         = trim(pg_result($res,$i,cpf_cnpj));
		$endereco    = str_replace ("'","",trim(pg_result($res,$i,endereco)));
		$numero      = trim(pg_result($res,$i,numero));
		$complemento = trim(pg_result($res,$i,complemento));
		$bairro      = trim(pg_result($res,$i,bairro));
		$cep         = trim(pg_result($res,$i,cep));
		$cidade      = trim(pg_result($res,$i,cidade));
		$fone        = trim(pg_result($res,$i,fone));
		$rg          = trim(pg_result($res,$i,rg));
		$email       = trim(pg_result($res,$i,email));
		$nome_cidade = trim(pg_result($res,$i,nome_cidade));
		$estado      = trim(pg_result($res,$i,estado));
		$tipo        = trim(pg_result($res,$i,tipo));
		$data_at     = trim(pg_result($res,$i,data_at));
		$descricao   = trim(pg_result($res,$i,descricao));
		$sua_os      = trim(pg_result($res,$i,sua_os));
		$status      = trim(pg_result($res,$i,status));
		$hd_chamado  = trim(pg_result($res,$i,hd_chamado));
		$tipo_registro  = trim(pg_result($res,$i,tipo_registro));
		$referencia  = trim(pg_result($res,$i,referencia));
		$serie       = trim(pg_result($res,$i,serie));
		$nota_fiscal = trim(pg_result($res,$i,nota_fiscal));
		$data_nf     = trim(pg_result($res,$i,data_nf));


		if($tipo=="C"){
				$xtipo="Consumidor";
		}else{
			if ($tipo=="R") {
				$xtipo="Revenda";
			} else {
				$xtipo="Assistência";
			}
		}

		$cor = ($i % 2 <> 0) ? '#F7F5F0' : '#F1F4FA';

		echo "<tr style='background-color: $cor; cursor: default;'>";
		echo "<td>";
		echo "$data_at";
		echo "</td>";
		echo "<td>".substr($descricao,0,20)."</td>";
		echo "<td align='center' style='color: #3333ff; cursor: pointer;' onclick='window.parent.retorna_consumidor_hdchamado($hd_chamado); window.parent.Shadowbox.close();'>";
		echo "$hd_chamado</td>";
		echo "<td style='color: #3333ff; cursor: pointer;' onclick=\"window.parent.retorna_consumidor('$cliente', '$nome', '$cpf', '$rg', '$nome_cidade', '$fone', '$endereco', '$numero', '$complemento', '$bairro', '$cep', '$estado', '$tipo', '$email'); window.parent.Shadowbox.close();\">";
		# HD 69909
		echo substr($nome,0,15)."</td>";
		echo "<td style='color: #3333ff; cursor: pointer;' onclick=";
		if ($_GET['forma'] == 'reload') {
			$retorno = $_GET['retorno'];
			echo "\"window.parent.retorna_consumidor_reload($retorno, $cliente); window.parent.Shadowbox.close();\" >" ;
		}else{
			echo "\"window.parent.retorna_consumidor_suaos('$cliente', '$nome', '$cpf', '$rg', '$nome_cidade', '$fone', '$endereco', '$numero', '$complemento', '$bairro', '$cep', '$estado', '$tipo', '$email', '$referencia', '$descricao', '$serie', '$nota_fiscal', '$data_nf'); window.parent.Shadowbox.close();\" >";
		}
		echo "$sua_os</td>";
		echo "<td>$tipo_registro</td>";
		echo "<td>$status</td>";
		echo "</tr>";
	}
	echo "</table>";
}

if(strlen($atendimento)>0){

	if(is_numeric($atendimento)){
		$sql = "SELECT tbl_hd_chamado.hd_chamado,
						status                  ,
						TO_CHAR(data,'DD/MM/YYYY') AS data_at,
						tbl_produto.descricao,
						tbl_hd_chamado_extra.nome,
						tbl_hd_chamado_extra.os,
						tbl_hd_chamado_extra.endereco     ,
						tbl_hd_chamado_extra.numero       ,
						tbl_hd_chamado_extra.complemento  ,
						tbl_hd_chamado_extra.bairro       ,
						tbl_hd_chamado_extra.cep          ,
						tbl_hd_chamado_extra.cidade       ,
						tbl_hd_chamado_extra.fone         ,
						tbl_hd_chamado_extra.cpf as cpf_cnpj ,
						tbl_hd_chamado_extra.rg           ,
						tbl_hd_chamado_extra.email        ,
						tbl_cidade.nome AS nome_cidade    ,
						tbl_cidade.estado                 ,
						tbl_os.sua_os                     ,
						tipo_registro                     ,
						tbl_produto.referencia            ,
						case when tbl_hd_chamado_extra.serie is not null then tbl_hd_chamado_extra.serie else tbl_os.serie end as serie,
						case when tbl_hd_chamado_extra.data_nf is not null then to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') else to_char(tbl_os.data_nf,'DD/MM/YYYY') end as data_nf,
						case when tbl_hd_chamado_extra.nota_fiscal is not null then tbl_hd_chamado_extra.nota_fiscal else tbl_os.nota_fiscal end as nota_fiscal
				FROM tbl_hd_chamado
				JOIN tbl_hd_chamado_extra USING(hd_chamado)
				LEFT JOIN tbl_os USING (os)
				LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_hd_chamado_extra.produto
				LEFT JOIN tbl_cidade ON tbl_cidade.cidade=tbl_hd_chamado_extra.cidade
				WHERE hd_chamado           = $atendimento
				AND   fabrica_responsavel = $login_fabrica
				ORDER BY data DESC";
		$at = pg_exec($con,$sql);

		if (pg_numrows ($at) == 0) {
			echo "<div class='lp_msg_erro'>'$atendimento' não encontrado</div>";
		}
		if(pg_numrows($at)>0){
			echo "<table width='100%' border='0' cellspacing='1' cellspading='0' class='lp_tabela'>";
				echo "<TR style='cursor: default;'>";
					echo "<td>Data</td>";
					echo "<td>Produto</td>";
					echo "<td>Atendimento</td>";
					echo "<td>Cliente</td>";
					echo "<td>OS</td>";
					echo "<td>REGISTRO</td>";
					echo "<td>Status</td>";
				echo "</TR>";
			for($j=0;$j<pg_numrows($at);$j++){
				$hd_chamado      = pg_result($at,$j,hd_chamado);
				$status          = pg_result($at,$j,status);
				$data            = pg_result($at,$j,data_at);
				$descricao       = pg_result($at,$j,descricao);
				$nome = pg_result($at,$j,nome);
				$os              = pg_result($at,$j,os);
				$cpf         = trim(pg_result($at,$j,cpf_cnpj));
				$endereco    = str_replace ("'","",trim(pg_result($at,$j,endereco)));
				$numero      = trim(pg_result($at,$j,numero));
				$complemento = trim(pg_result($at,$j,complemento));
				$bairro      = trim(pg_result($at,$j,bairro));
				$cep         = trim(pg_result($at,$j,cep));
				$cidade      = trim(pg_result($at,$j,cidade));
				$fone        = trim(pg_result($at,$j,fone));
				$rg          = trim(pg_result($at,$j,rg));
				$email       = trim(pg_result($at,$j,email));
				$nome_cidade = trim(pg_result($at,$j,nome_cidade));
				$estado      = trim(pg_result($at,$j,estado));
				$sua_os      = trim(pg_result($at,$j,sua_os));
				$tipo_registro = trim(pg_result($at,$j,tipo_registro));
				$referencia  = trim(pg_result($at,$j,referencia));
				$serie       = trim(pg_result($at,$j,serie));
				$nota_fiscal = trim(pg_result($at,$j,nota_fiscal));
				$data_nf     = trim(pg_result($at,$j,data_nf));

				$cor = ($i % 2 <> 0) ? '#F7F5F0' : '#F1F4FA';

				echo "<tr style='background-color: $cor; cursor: default;'>";
				echo "<td>$data</td>";
				echo "<td align=''>".substr($descricao,0,20)."</td>";
				echo "<td align='center' style='color: #3333ff; cursor: pointer;' onclick='window.parent.retorna_consumidor_hdchamado($hd_chamado); window.parent.Shadowbox.close();'>";
				echo "$hd_chamado</td>";
				echo "<td style='color: #3333ff; cursor: pointer;' onclick=\"window.parent.retorna_consumidor('$cliente', '$nome', '$cpf', '$rg', '$nome_cidade', '$fone', '$endereco', '$numero', '$complemento', '$bairro', '$cep', '$estado', '$tipo', '$email'); window.parent.Shadowbox.close();\">";
				echo substr($nome,0,15)."</td>";
				echo "<td style='color: #3333ff; cursor: pointer;' onclick=\"window.parent.retorna_consumidor_suaos('$cliente', '$nome', '$cpf', '$rg', '$nome_cidade', '$fone', '$endereco', '$numero', '$complemento', '$bairro', '$cep', '$estado', '$tipo', '$email', '$referencia', '$descricao', '$serie', '$nota_fiscal', '$data_nf'); window.parent.Shadowbox.close();\">";
				echo "$sua_os</td>";
				echo "<td>$tipo_registro</td>";
				echo "<td>$status</td>";
				echo "</tr>";

			}
			echo "</table>";

		}
	}else{
		echo "<div class='lp_msg_erro'>'$atendimento' não encontrado</div>";
	}
}

?>


</body>
</html>