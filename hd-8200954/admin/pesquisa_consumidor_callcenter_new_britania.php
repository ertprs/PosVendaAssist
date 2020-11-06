<?include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
?>
<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa Consumidores..</title>
<meta http-equiv=pragma content=no-cache>
</head>

<body style="margin: 0px 0px 0px 0px;">
<img src="imagens/pesquisa_consumidor.gif">

<?

if (strlen($_GET["nome"]) > 0) {
	$nome = strtoupper (trim ($_GET["nome"]));

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>nome do consumidor</b>: <i>$nome</i></font>";
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
		echo "<h1>Consumidor '$nome' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}else{
		if(strlen($_POST['cpf'])>0) $cpf = strtoupper ($_POST['cpf']);
		else                        $cpf = strtoupper ($_GET['cpf']);

		if(strlen($_POST['os'])>0) $os = strtoupper ($_POST['os']);
		else                       $os = strtoupper ($_GET['os']);

		$xcpf = str_replace (".","",$cpf);
		$xcpf = str_replace ("-","",$xcpf);
		$xcpf = str_replace (",","",$xcpf);
		$xcpf = str_replace ("/","",$xcpf);
		$xcpf = str_replace (" ","",$xcpf);
		if(strlen($xcpf)>0) $busca = $xcpf;

		if(strlen($_POST['atendimento'])>0) $atendimento = strtoupper ($_POST['atendimento']);
		else                                $atendimento = strtoupper ($_GET['atendimento']);
		if(strlen($atendimento)>0) $busca = $atendimento;

		if(strlen($_POST['os'])>0) $os = trim(strtoupper ($_POST['os']));
		else                       $os = trim(strtoupper ($_GET['os']));
		if(strlen($os)>0) $busca = $os;





		$tipo  = $_GET['tipo'];
		$cond_1 = " 1=1 ";

		if($tipo=="cpf"){
			//hd 45707
			$cond_1  = " replace(replace(tbl_hd_chamado_extra.cpf, '.',''),'-','') ILIKE '%$busca%'";
			$xcond_1 = " tbl_revenda.cnpj LIKE  '%$busca%' ";
			$titulo  = "CPF/CNPJ";
		}

		if($tipo=="atendimento"){
			$cond_1 = " 1 = 2 ";
			$xcond_1 = " 1 = 2 ";
		}

		if($tipo=="os"){
			$titulo  = "OS";
		}

		if(strlen($os)>0) $busca = $os;

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
			echo "<h1>$titulo '$busca' não encontrado</h1>";
			echo "<script language='javascript'>";
			echo "setTimeout('window.close()',2500);";
			echo "</script>";
			exit;
		}
	}
}

if(pg_numrows($res)>0 AND strlen($atendimento)==0){
	echo "<script language='JavaScript'>";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";

	echo "<table width='100%' border='0' cellspacing='2'>\n";
		echo "<TR bgcolor='#CCCCCC'>";
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

		if($cor=='#FFECF5') $cor = '#EEEEEE';
		else                $cor = '#FFECF5';

		echo "<tr bgcolor='$cor' style='font-size: 11px;'>\n";
		echo "<td>\n";
		echo "$data_at\n";
		echo "</td>\n";
		echo "<td>".substr($descricao,0,20)."</td>";
		echo "<td align='center'>";
		echo "<a href=\"javascript: opener.document.location = 'callcenter_interativo_new_britania.php?callcenter=$hd_chamado' ; this.close() ;\" > " ;
		echo "$hd_chamado</a></td>";
		echo "<td>";
		# HD 69909
		echo "<a href=\"javascript: cliente.value='$cliente' ; nome.value='$nome' ; cpf.value = '$cpf' ; rg.value='$rg'; cidade.value='$nome_cidade' ; fone.value='$fone' ; endereco.value='$endereco' ; numero.value='$numero' ; complemento.value='$complemento' ; bairro.value='$bairro' ; cep.value='$cep' ; estado.value='$estado' ; tipo.value='$tipo'; email.value = '$email'; ";
		if ($_GET["proximo"] == "t") echo "janela.focus(); ";
		echo "this.close(); \">\n";
		echo substr($nome,0,15)."</a></td>";
		echo "<td>";
		if ($_GET['forma'] == 'reload') {
			$retorno = $_GET['retorno'];
			echo "<a href=\"javascript: opener.document.location = '$retorno?cliente=$cliente' ; this.close() ;\" > " ;
		}else{
			echo "<a href=\"javascript: cliente.value='$cliente' ; nome.value='$nome' ; cpf.value = '$cpf' ; rg.value='$rg'; cidade.value='$nome_cidade' ; fone.value='$fone' ; endereco.value='$endereco' ; numero.value='$numero' ; complemento.value='$complemento' ; bairro.value='$bairro' ; cep.value='$cep' ; estado.value='$estado' ; tipo.value='$tipo'; email.value = '$email'; referencia.value='$referencia'; descricao.value='$descricao'; serie.value='$serie'; nota_fiscal.value='$nota_fiscal'; data_nf.value='$data_nf'; ";
			if ($_GET["proximo"] == "t") echo "janela.focus(); ";
			echo "this.close(); \">\n";
		}
		echo "$sua_os</td>";
		echo "<td>$tipo_registro</td>";
		echo "<td>$status</td>";
		echo "</tr>";
	}
	echo "</table>\n";
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
			echo "<h1>ATENDIMENTO '$atendimento' não encontrado</h1>";
			echo "<script language='javascript'>";
			echo "setTimeout('window.close()',2500);";
			echo "</script>";
			exit;
		}
		if(pg_numrows($at)>0){
			echo "<table width='100%' border='0' cellspacing='2'>\n";
				echo "<TR bgcolor='#CCCCCC'>";
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

				if($cor=='#FFECF5') $cor = '#EEEEEE';
				else                $cor = '#FFECF5';

				echo "<tr bgcolor=$cor style='font-size: 11px;'>";
				echo "<td>$data</td>";
				echo "<td align=''>".substr($descricao,0,20)."</td>";
				echo "<td align='center'>";
				echo "<a href=\"javascript: opener.document.location = 'callcenter_interativo_new_britania.php?callcenter=$hd_chamado' ; this.close() ;\" > " ;
				echo "$hd_chamado</td>";
				echo "<td>";
				echo "<a href=\"javascript: cliente.value='$cliente' ; nome.value='$nome' ; cpf.value = '$cpf' ; rg.value='$rg'; cidade.value='$nome_cidade' ; fone.value='$fone' ; endereco.value='$endereco' ; numero.value='$numero' ; complemento.value='$complemento' ; bairro.value='$bairro' ; cep.value='$cep' ; estado.value='$estado' ; tipo.value='$tipo'; email.value = '$email'; ";
				if ($_GET["proximo"] == "t") echo "janela.focus(); ";
				echo "this.close(); \">\n";
				echo substr($nome,0,15)."</a></td>";
				echo "<td>";
				echo "<a href=\"javascript: cliente.value='$cliente' ; nome.value='$nome' ; cpf.value = '$cpf' ; rg.value='$rg'; cidade.value='$nome_cidade' ; fone.value='$fone' ; endereco.value='$endereco' ; numero.value='$numero' ; complemento.value='$complemento' ; bairro.value='$bairro' ; cep.value='$cep' ; estado.value='$estado' ; tipo.value='$tipo'; email.value = '$email'; referencia.value='$referencia'; descricao.value='$descricao'; serie.value='$serie'; nota_fiscal.value='$nota_fiscal'; data_nf.value='$data_nf'; ";
				if ($_GET["proximo"] == "t") echo "janela.focus(); ";
				echo "this.close(); \">\n";
				echo "$sua_os</a></td>";
				echo "<td>$tipo_registro</td>";
				echo "<td>$status</td>";
				echo "</tr>";

			}
			echo "</table>";

		}
	}else{
		echo "<h1>ATENDIMENTO '$atendimento' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
	}
}

?>


</body>
</html>