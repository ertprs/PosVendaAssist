<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

$liberar_preco = true ;

include 'funcoes.php';

$layout_menu = "callcenter";
$title = "Consulta valores da tabela de preços";

$downloadTabela = trim($_GET["downloadTabela"]);

if (in_array($login_fabrica,array(11,151)) AND $downloadTabela=="1"){
	$tabela = trim($_GET["tabela"]);

	if (strlen($tabela)>0){
		$sql = "SELECT  tbl_peca.referencia                                                     AS peca_referencia   ,
						tbl_peca.descricao                                                      AS peca_descricao    ,
						tbl_peca.unidade                                                                             ,
						tbl_tabela_item.preco                                                                        ,
						tbl_peca.ipi                                                                                 ,
						(tbl_tabela_item.preco * ((1 + tbl_peca.ipi))/10) AS total             
				FROM    tbl_peca
				JOIN    tbl_tabela_item  ON tbl_tabela_item.peca = tbl_peca.peca
				WHERE   tbl_peca.fabrica       = $login_fabrica 
				AND		tbl_tabela_item.tabela = $tabela
				AND		tbl_peca.ativo         = 't'
				ORDER BY    tbl_peca.descricao ,
							tbl_peca.referencia";
		$res = pg_exec($con,$sql);
	
		$arquivo = "xls/tabela" . $tabela . ".csv";
		$fp = fopen ($arquivo,"w");
		fwrite ($fp,"Referência da peça;Descrição da peça                  ;Unidade;Preço;IPI\n");

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$peca_referencia    = trim(pg_result ($res,$i,peca_referencia));
			$peca_descricao     = trim(pg_result ($res,$i,peca_descricao));
			$unidade            = trim(pg_result ($res,$i,unidade));
			$preco              = trim(pg_result ($res,$i,preco));
			$ipi                = trim(pg_result ($res,$i,ipi));
	
			$linha  = $peca_referencia;
			$linha .= ";";
			$linha .= $peca_descricao;
			$linha .= ";";
			$linha .= $unidade;

			if ($liberar_preco) {
				$linha .= ";";
				$linha .= number_format ($preco,2,",",".");
				$linha .= ";";
				$linha .= $ipi;
			}
			fwrite ($fp,$linha);
			fwrite ($fp,"\n");
		}
		fclose ($fp);
		header("Content-type: text/xml"); 
		header("Content-Length:".filesize($arquivo)); 
		header('Content-Disposition: attachment; filename="TabelaDePrecos-'.$tabela.'.csv"'); 
		header('Expires: 0'); 
		header('Pragma: no-cache'); 
		readfile("$arquivo"); 
		exit;
	}
}else{
	header("Location: preco_consulta.php");
}
?>
