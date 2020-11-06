<? 
include 'menu.php';
if ($logado==""){header("Location: index.php"); }
include 'banco.php';

$nome_arquivo	= "teste.txt";
$arquivo		= fopen($nome_arquivo, "r");

// separa arquivo no ;
	while ($linha_arquivo = fgets($arquivo)) {
        $linha    	    = explode(";",str_replace("\"","",str_replace("'","",$linha_arquivo)));
        $representante	= $linha[0];
        $codigo_matriz	= $linha[1];
        $codigo_filial	= $linha[2];
        $razao_social	= $linha[3];
        $cnpj			= $linha[4];
        $endereco		= $linha[5];	
        $cidade			= $linha[6];
        $estado			= $linha[7];
        $cep			= $linha[8];
        $telefone		= $linha[9];
        $fax			= $linha[10];
        $filial			= $linha[11];
        $especie		= $linha[12];
        $serie			= $linha[13];
        $nota			= $linha[14];
        $banco			= $linha[15];
        $nosso_numero	= $linha[16];
        $parcela		= $linha[17];
        $carteira		= $linha[18];
        $emissao		= $linha[19];
        $vencimento		= $linha[20];
        $valor_original	= str_replace(",",".",$linha[21]);
        $valor_saldo	= str_replace(",",".",$linha[22]);
        $despesas		= $linha[23];

		 
$emissao = substr($emissao,6,4) . "/" .substr($emissao,3,2) . "/" . substr($emissao,0,2);
$vencimento = substr($vencimento,6,4) . "/" .substr($vencimento,3,2) . "/" . substr($vencimento,0,2);

// verifica se ja foi cadastrada a empresa
$sql = "SELECT id_empresa FROM tbl_cobranca_empresa
		WHERE cnpj='$cnpj' and codigo_matriz='$codigo_matriz' and codigo_filial='$codigo_filial'";
$res = pg_exec($con,$sql);
		if(pg_numrows($res)> 0){
		$id_empresa = pg_result($res,0,id_empresa);


// se já existe a empresa verifica se ja existe a nota
				if ($id_empresa<>"") {
					$sql = "SELECT nota FROM tbl_cobranca_nota
							WHERE nota='$nota' and serie='$serie' and parcela='$parcela'";
					$res = pg_exec($con,$sql);
						if(pg_numrows($res)> 0){
						$nota = pg_result($res,0,nota);
						}
						else{
// se existe empresa e não existe nota inclui a nota
									$sql = " INSERT INTO tbl_cobranca_nota (filial,especie,serie,nota,banco,nosso_numero,parcela,carteira,emissao,vencimento,valor_original,valor_saldo,despesas,id_empresa) values ('$filial','$especie','$serie','$nota','banco','nosso_numero','$parcela','$carteira','$emissao','$vencimento','$valor_original','$valor_saldo','$despesas','$id_empresa')";

									$res = pg_exec($con,$sql);

						}
				}

		}
		else{
// inclui empresa se não foi encontrada
					$sql = " INSERT INTO tbl_cobranca_empresa (cnpj,codigo_filial,representante,codigo_matriz,razao_social,endereco,cidade,estado,cep,telefone,fax) values ('$cnpj','$codigo_filial','$representante','$codigo_matriz','$razao_social','$endereco','$cidade','$estado','$cep','$telefone','$fax')";

						$res = pg_exec($con,$sql);



										$sql = "SELECT id_empresa FROM tbl_cobranca_empresa
										WHERE cnpj='$cnpj' and codigo_matriz='$codigo_matriz' and codigo_filial='$codigo_filial'";
										$res = pg_exec($con,$sql);
											if(pg_numrows($res)> 0){
											$id_empresa = pg_result($res,0,id_empresa);
											}
											else{
											$id_empresa = "";
											}
												if ($id_empresa<>"") {
										$sql = "SELECT nota FROM tbl_cobranca_nota
												WHERE nota='$nota' and serie='$serie'";
										$res = pg_exec($con,$sql);
														if(pg_numrows($res)> 0){
														$nota = pg_result($res,0,id_empresa);
														}
														else{
// inclui a nota apos incluir a empresa
																	$sql = " INSERT INTO tbl_cobranca_nota (filial,especie,serie,nota,banco,nosso_numero,parcela,carteira,emissao,vencimento,valor_original,valor_saldo,despesas,id_empresa) values ('$filial','$especie','$serie','$nota','banco','nosso_numero','$parcela','$carteira','$emissao','$vencimento','$valor_original','$valor_saldo','$despesas','$id_empresa')";

																	$res = pg_exec($con,$sql);

														}
												}

		}

 //      echo "<tr><td>$qtd</td><td>".$representante."</td><td>".$codigo_matriz."</td><td>".$codigo_filial."</td><td>".$razao_social."</td><td>".$cnpj."</td><td>".$cidade."</td><td>".$estado."</td><td>".$cep."</td><td>".$telefone."</td><td>".$fax."</td><td>".$filial."</td><td>".$especie."</td><td><b>".$serie."</B></td><td><b>".$nota."</b></td><td>".$banco."</td><td>".$nosso_numero."</td><td>".$parcela."</td><td>".$carteira."</td><td>".$emissao."</td><td>".$vencimento."</td><td>".$valor_original."</td><td>".$valor_saldo."</td><td>".$despesas."</td><td>".$o."</td><td>".$p."</td></tr>";

    }
    fclose($arquivo);
echo "<b><br><br>&nbsp;&nbsp;&nbsp;Arquivo incluído com sucesso.<br><br><b>";
include 'rodape.php';
?>
