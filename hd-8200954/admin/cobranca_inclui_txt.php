<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';
include 'funcoes.php';

	$layout_menu = "financeiro";
	$title = "Cobrança";
	include 'cabecalho.php';

$nome_arquivo	= "cobranca_upload/teste.txt";
$arquivo		= fopen($nome_arquivo, "r");


	$sql = "delete from tbl_cobranca_nota";
	$res = pg_exec($con,$sql);


// separa arquivo no ;
	while ($linha_arquivo = fgets($arquivo)) {
        $codigo_matriz	= "";
        $linha    	    = explode(";",str_replace("\"","",str_replace("'","",$linha_arquivo)));
        $representante	= str_replace(" ","",$linha[0]);
        $codigo_matriz	= str_replace(" ","",str_replace(".","",$linha[1]));
        $codigo_filial	= str_replace(" ","",$linha[2]);
        $razao_social	= $linha[3];
        $cnpj			= str_replace(" ","",$linha[4]);
        $endereco		= $linha[5];	
        $cidade			= $linha[6];
        $estado			= str_replace(" ","",$linha74549[7]);
        $cep			= str_replace(" ","",$linha[8]);
        $telefone		= str_replace(" ","",$linha[9]);
        $fax			= str_replace(" ","",$linha[10]);
        $filial			= str_replace(" ","",$linha[11]);
        $especie		= str_replace(" ","",$linha[12]);
        $serie			= str_replace(" ","",str_replace("xxxxx","xxx",$linha[13]));
        $nota			= str_replace(" ","",$linha[14]);
        $banco			= str_replace(" ","",$linha[15]);
        $nosso_numero	= str_replace(" ","",$linha[16]);
        $parcela		= str_replace(" ","",$linha[17]);
        $carteira		= str_replace(" ","",$linha[18]);
        $emissao		= $linha[19];
        $vencimento		= $linha[20];
        $valor_original	= str_replace(",",".",str_replace(".","",$linha[21]));
        $valor_saldo	= str_replace(",",".",str_replace(".","",$linha[22]));
        $valor_despesas		= str_replace(",",".",str_replace(".","",$linha[23]));

		$emissao = substr($emissao,6,4) . "/" .substr($emissao,3,2) . "/" . substr($emissao,0,2);
		$vencimento = substr($vencimento,6,4) . "/" .substr($vencimento,3,2) . "/" . substr($vencimento,0,2);

		if ($codigo_matriz<>""){
			// verifica se ja foi cadastrada a empresa
			$sql = "SELECT posto FROM tbl_posto_fabrica
					WHERE codigo_posto='$codigo_matriz' and fabrica=3";
			$res = pg_exec($con,$sql);
					if(pg_numrows($res)> 0){
					$posto = pg_result($res,0,posto);


			//  inclui as notas
								$sql = " INSERT INTO tbl_cobranca_nota (filial,representante,especie,serie,nota,banco,nosso_numero,parcela,carteira,emissao,vencimento,valor_original,valor_saldo,valor_despesas,posto) values ('$filial','$representante','$especie','$serie','$nota','$banco','$nosso_numero','$parcela','$carteira','$emissao','$vencimento','$valor_original','$valor_saldo','$valor_despesas','$posto')";

								$res = pg_exec($con,$sql);

			//verifica se já foi incluido retorno
								$sql = "SELECT posto FROM tbl_cobranca_retorno
										WHERE posto='$posto'";
								$res = pg_exec($con,$sql);
								if(pg_numrows($res)== 0){
								$sql = " INSERT INTO tbl_cobranca_retorno (posto,admin) values ('$posto','$login_admin')";

								$res = pg_exec($con,$sql);
								}

								
					}else{
						// inclui empresa se não foi encontrada
						$erro= "<br>&nbsp;&nbsp;As notas relacionadas abaixo não foram incluídas pois os postos não estão cadastrados, para incluir estas notas cadastre os postos e repita este processo.<br>As outras notas que não estão listadas abaixo forão incluidas com sucesso.<br><br><table border='0' cellspacing='0' cellpadding='0' bgcolor='#D9E2EF' align = 'center'>";
						$erro2 = $erro2 + "<tr><td>$qtd</td><td>".$representante."</td><td>".$codigo_matriz."</td><td>".$codigo_filial."</td><td>".$razao_social."</td><td>".$cnpj."</td><td>".$cidade."</td><td>".$estado."</td><td>".$cep."</td><td>".$telefone."</td><td>".$fax."</td><td>".$filial."</td><td>".$especie."</td><td><b>".$serie."</B></td><td><b>".$nota."</b></td><td>".$banco."</td><td>".$nosso_numero."</td><td>".$parcela."</td><td>".$carteira."</td><td>".$emissao."</td><td>".$vencimento."</td><td>".$valor_original."</td><td>".$valor_saldo."</td><td>".$despesas."</td><td>".$o."</td><td>".$p."</td></tr>";
						$erro3 = "</table>";
					}
		}
 //      echo "<tr><td>$qtd</td><td>".$representante."</td><td>".$codigo_matriz."</td><td>".$codigo_filial."</td><td>".$razao_social."</td><td>".$cnpj."</td><td>".$cidade."</td><td>".$estado."</td><td>".$cep."</td><td>".$telefone."</td><td>".$fax."</td><td>".$filial."</td><td>".$especie."</td><td><b>".$serie."</B></td><td><b>".$nota."</b></td><td>".$banco."</td><td>".$nosso_numero."</td><td>".$parcela."</td><td>".$carteira."</td><td>".$emissao."</td><td>".$vencimento."</td><td>".$valor_original."</td><td>".$valor_saldo."</td><td>".$despesas."</td><td>".$o."</td><td>".$p."</td></tr>";

    }
    fclose($arquivo);
echo $erro.$erro2.$erro3;
echo "<b><br><br>&nbsp;&nbsp;&nbsp;Arquivo incluído com sucesso.<br><br><b>";
include 'rodape.php';
?>
