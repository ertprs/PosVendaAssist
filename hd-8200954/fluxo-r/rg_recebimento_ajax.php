<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

if($ajax=='codigo_barra'){
	$sql = "SELECT produto_rg
			FROM   tbl_produto_rg
			WHERE  posto                = $login_posto
			AND    data_digitacao::DATE = CURRENT_DATE
			AND    data_digitacao_termino IS NULL";
	$res1 = @pg_exec($con,$sql);
	
	if(@pg_numrows($res1)>0){

		$produto_rg = @pg_result($res1,0,0);

	}


	$sql = "SELECT produto,referencia,descricao
			FROM   tbl_produto
			JOIN   tbl_linha   USING(linha)
			WHERE  codigo_barra = '$codigo_barra'
			AND    fabrica      = 45";
	$res2 = pg_exec($con,$sql);


	if(pg_numrows($res2)>0){
		$produto    = @pg_result($res2,0,produto);
		$referencia = @pg_result($res2,0,referencia);
		$descricao  = @pg_result($res2,0,descricao);

		$resultado = "<span style='font-size:60px;'>$descricao</span>";

		if ($dh = opendir("imagens_produto/")) {
			$contador=0;
			while (false !== ($filename = readdir($dh))) {
				$Xreferencia = str_replace(" ", "_",$referencia);
				$Xreferencia = explode("-", $referencia);
				$Xreferencia = $Xreferencia[0];
				if (strpos($filename,$Xreferencia) !== false){
					$contador++;
					//$peca_referencia = ntval($peca_referencia);
					$po = strlen($Xreferencia);
					if(substr($filename, 0,$po)==$Xreferencia){
						$file_final = $filename;
						$img = "<img src='imagens_produto/$file_final'/>";

						$tem_foto = "SIM";

					}
				}
			}
		}
		$sql = "SELECT
				(
					SELECT
						count(*) 
					FROM tbl_produto_rg_item  RI
					JOIN tbl_produto          PR USING(produto)
					WHERE RI.produto_rg        = $produto_rg
					AND   PR.troca_obrigatoria IS TRUE
				) AS troca_obrigatoria,
				(
					SELECT count(*)
					FROM tbl_produto_rg_item  RI
					JOIN tbl_produto          PR USING(produto)
					WHERE RI.produto_rg        = $produto_rg
				) AS cb_achado,
				(
					SELECT count(*)
					FROM tbl_produto_rg_item  RI
					WHERE RI.produto_rg        = $produto_rg
					AND   RI.produto           IS NULL
				) AS cb_perdido ";
		$res2 = @pg_exec($con,$sql);
		$troca_obrigatoria = @pg_result($res2,0,0);
		$cb_achado         = @pg_result($res2,0,1);
		$cb_perdido        = @pg_result($res2,0,2);

		$quadro .= "<div style='border: #000000 1px solid;display:inline;height:50px;width:150px;background:#FFFFB3;'>Produtos com Troca Obrigatória:<br> $troca_obrigatoria</div>&nbsp;&nbsp;";
		$quadro .= "<div style='border: #000000 1px solid;display:inline;height:50px;width:150px;;background:#C1FFC1;'>Produtos com código barra encontrados:<br> $cb_achado</div>&nbsp;&nbsp;";
		$quadro .= "<div style='border: #000000 1px solid;display:inline;height:50px;width:150px;;background:#FFD9D9;'>Produtos com código barra NÃO encontrados:<br> $cb_perdido</div><br>";

		if($tem_foto<>'SIM'){
			$img = "<img src='imagens/produto_sem_foto.gif' align='middle'>\n";
		}


	}else $resultado .= "<span style='font-size:70PX;color:#FF0000;text-align:center;'>NÃO ENCONTRADO</SPAN>";

	echo "ok|$resultado|$img|$quadro";
	exit;
}


if ($acao == "gravar" AND $ajax == "sim") {

//	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$rg           = strtoupper(trim($_POST['rg']));
	$codigo_barra = strtoupper(trim($_POST['codigo_barra']));

	if(strlen($codigo_barra)==0) $msg_erro .= "Código de Barra não pode estar vazio<br>";
	if(strlen($codigo_barra)==0) $msg_erro .= "RG não pode estar vazio<br>";

	if(strlen($msg_erro)==0){
		//Verificar se o recebimento foi finalizado
		$sql = "SELECT produto_rg
				FROM   tbl_produto_rg
				WHERE  posto                = $login_posto
				AND    data_digitacao::DATE = CURRENT_DATE
				AND    data_digitacao_termino IS NULL";
		$res1 = @pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
		//se o recebimento não foi finalizado pega o ID do Lote
		if(@pg_numrows($res1)>0){
			$produto_rg = @pg_result($res1,0,0);
		}else{
			//se o recebimento está finalizado cria uma nova ID para o Lote
			$sql =	"INSERT INTO tbl_produto_rg (
						posto             ,
						revenda           ,
						fabrica
					) VALUES (
						$login_posto,
						307,
						45
					)";
			$res = @pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);

			if (strlen($msg_erro) == 0 AND strlen($pedido) == 0) {
				$res = @pg_exec($con,"SELECT CURRVAL ('seq_produto_rg')");
				$produto_rg = pg_result($res,0,0);
				$msg_erro .= pg_errormessage($con);
			}
		}
	}

	if (strlen($msg_erro) == 0) {

		//Verifica se o Produto do RG tem OS em aberto.
		$sql = "SELECT  RI.fabrica
				FROM tbl_produto_rg      RG
				JOIN tbl_produto_rg_item RI USING(produto_rg)
				WHERE RG.posto = $login_posto
				AND   RI.rg    = '$rg'
				AND   RI.data_devolucao IS NULL";
		$res1 = @pg_exec($con,$sql);
		$msg_erro .= pg_errormessage($con);
		if(@pg_numrows($res1)>0){
			$fabrica   = pg_result($res1,0,fabrica);
			$msg_erro .= "RG já foi dado entrada neste lote. Este produto está com o mesmo RG de outro produto<br>";

			//Verifica se a OS está na tbl_os
			$sql = "SELECT os,sua_os FROM tbl_os 
					WHERE fabrica    = 45
					AND   posto      = $login_posto
					AND   rg_produto = '$rg'";
			$res2 = @pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if(pg_numrows($res2)>0) {
				$os     = pg_result($res2,0,os);
				$sua_os = pg_result($res2,0,sua_os);
				$msg_erro = "Produto já cadastrado na OS <a href='../login_unico.php?id=$fabrica&os=$os'>$sua_os</a>";
			}
		}

		//Verifica se o código de Barra tem um único produto correspondente
		if(strlen($codigo_barra)>0){
			$sql = "SELECT produto,referencia,descricao,troca_obrigatoria
					FROM   tbl_produto
					JOIN   tbl_linha   USING(linha)
					WHERE  codigo_barra = '$codigo_barra'
					AND    fabrica      = 45";
			$res2 = @pg_exec($con,$sql);
			if(pg_numrows($res2)==1){
				$produto           = @pg_result($res2,0,produto);
				$troca_obrigatoria = @pg_result($res2,0,troca_obrigatoria);
			}else{
				$produto = "null";
			}
			if(strlen($troca_obrigatoria)==0) $troca_obrigatoria = "f";
			
		}

		if(strlen($msg_erro)==0){
			//Insere o produto 
			$sql =	"INSERT INTO tbl_produto_rg_item (
						produto_rg  ,
						rg          ,
						codigo_barra,
						produto     ,
						fabrica     ,
						posto       ,
						devolucao  
					) VALUES (
						$produto_rg    ,
						'$rg'          ,
						'$codigo_barra',
						$produto       ,
						45             ,
						$login_posto   ,
						'$troca_obrigatoria'
					)";
			$res = @pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
		}
	}


	if (strlen($msg_erro) == 0) {
		//$res = pg_exec($con,"COMMIT TRANSACTION");
		echo "ok|Gravado com Sucesso<br>$resultado|$produto_rg|";
		if($produto=="null") echo "produto";
	}else{
		//$res = pg_exec($con,"ROLLBACK TRANSACTION");
		echo "1|<font color='#FF0000' size=5>$msg_erro</font>";
	}
	exit;
}













if ($acao == "mostrar" AND $ajax == "sim") {

	$sql = "SELECT produto_rg
			FROM   tbl_produto_rg
			WHERE  posto                = $login_posto
			AND    data_digitacao::DATE = CURRENT_DATE
			AND    data_digitacao_termino IS NULL";
	$res1 = @pg_exec($con,$sql);

	if(@pg_numrows($res1)>0){

		$produto_rg = @pg_result($res1,0,0);
		$sql = "SELECT  RI.produto_rg_item                                           ,
					RI.codigo_barra                                              ,
					RI.rg                                                        ,
					RI.produto                                                   ,
					RI.serie                                                     ,
					RI.defeito_reclamado                                         ,
					RI.fabrica                                                   ,
					PR.referencia                           AS produto_referencia,
					PR.descricao                            AS produto_descricao ,
					TO_CHAR(RI.data_devolucao,'dd/mm/YYYY') AS data              ,
					RI.devolucao
			FROM tbl_produto_rg_item  RI 
			LEFT JOIN tbl_produto          PR USING(produto) 
			WHERE produto_rg = $produto_rg
			ORDER BY produto_rg_item DESC ";
		$res = @pg_exec($con,$sql);

		if(strlen($produto_rg)>0){
			$sql = "SELECT produto_rg_item
					FROM   tbl_produto_rg      RG
					JOIN   tbl_produto_rg_item RI USING(produto_rg)
					WHERE  RG.produto_rg = $produto_rg
					AND    produto IS NULL";
			$res2 = @pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			if(@pg_numrows($res2)>0) {
				$bloqueio = "style='display:none'";
			}
		}

		$resultado .= "<input type='hidden' name='produto_rg' id='produto_rg' value='$produto_rg'>\n";
		$resultado .= "<input type='hidden' name='ja_estou_editando' id='ja_estou_editando' value='0'>\n";
		$resultado .= "<div id='explodir_os' $bloqueio ><input type='button' name='btn_acao' id='btn_acao' value='Finalizar Recebimento' onclick=\"window.location='rg_recebimento.php?explodir=$produto_rg'\"></div>";

		$resultado .= "<table class='TabelaRevenda'  cellspacing='3' cellpadding='3' width='98%'>\n";
		$resultado .= "<thead>";
		$resultado .= "<tr bgcolor='#596D9B' style='color:#FFFFFF;'>";
		$resultado .= "<td><b>Código Barra</b></td>";
		$resultado .= "<td><b>P</b></td>";
		$resultado .= "<td><b>Produto - Descrição</b></td>";
		$resultado .= "<td><b>Ação</b></td>";
		$resultado .= "</tr>";
		$resultado .= "</thead>";
		$resultado .= "<tbody>";

		for($i=0;$i<@pg_numrows($res);$i++) {
			$produto_rg_item    = pg_result($res,$i,produto_rg_item);
			$codigo_barra       = pg_result($res,$i,codigo_barra);
			$rg                 = pg_result($res,$i,rg);
			$data               = pg_result($res,$i,data);
			$produto            = pg_result($res,$i,produto);
			$produto_referencia = pg_result($res,$i,produto_referencia);
			$produto_descricao  = pg_result($res,$i,produto_descricao);
			$defeito_reclamado  = pg_result($res,$i,defeito_reclamado);
			$serie              = pg_result($res,$i,serie);
			$fabrica            = pg_result($res,$i,fabrica);
			$devolucao          = pg_result($res,$i,devolucao);

			if($cor<>'#FFFFFF') $cor = '#FFFFFF';
			else                $cor = '';

			if($devolucao=='t') $cor = "#FFFFA8";

			$resultado .= "<tr bgcolor='$cor' >\n";
			$resultado .= "<td><input type='hidden' name='produto_rg_item_$i' value='$produto_rg_item'>$codigo_barra</td>\n";
			$resultado .= "<td>$rg</td>\n";
			$resultado .= "<td>\n";

			$resultado .= "<div id='linha_descricao_$i' style='display:block;width:350' onmouseover=\"this.style.cursor = 'pointer' \" onclick=\"if (ja_estou_editando.value == '0') {linha_campos_$i.style.display='block' ; linha_descricao_$i.style.display='none' ;  ja_estou_editando.value = '1' ; executar('produto_$i','id_produto_$i');produto_$i.focus(); } \">";

			if(strlen($produto_referencia)>0)$resultado .=  "$produto_referencia - $produto_descricao";
			else                             $resultado .=  "&nbsp;";
			$resultado .= "</div>\n";
			if(strlen($produto_referencia)>0) $produto_completo = "$produto_referencia - $produto_descricao";
			else                              $produto_completo = " ";

			$resultado .= "<div id='linha_campos_$i' style='display:none;' >";
			$resultado .= "<input type='hidden' name='id_produto_$i' id='id_produto_$i' value='$produto'>";
			$resultado .= "<input type='text' size='35' name='produto_$i' id='produto_$i' rel='produto' value='$produto_completo' onkeypress=\"if (event.keyCode == 13) { linha_campos_$i.style.display='none' ; linha_descricao_$i.style.display='block' ; ja_estou_editando.value = '0' ; linha_descricao_$i.innerHTML = produto_$i.value;gravar_produto(id_produto_$i.value,produto_rg_item_$i.value,'mostra_gravar_$i',produto_rg.value,'explodir_os');} \" >";
			$resultado .= "</div>\n";
			$resultado .= "<div id='mostra_gravar_$i' name='mostra_gravar_$i' style='display:inline'></div>\n";

			$resultado .= "</td>\n";
			$resultado .= "<td><a href='rg_recebimento.php?excluir=$produto_rg_item'><img src='imagens/icone_deletar.png'></a></td>\n";
			$resultado .= "</tr>\n";
		}
		$z = $i ;
		$resultado .= "<tr>";
		$resultado .=  "<td colspan='5'>Total de Produto: $z</td>";
		$resultado .= "</tr>";
		
		$resultado .= "</tbody>";
		$resultado .= "</table>";
		
		$x = $_GET["x"];
		//if($x == 1) $resultado .= "habilitar<script language='javascript'>ativar();  </script>";




		$sql = "SELECT
				(
					SELECT
						count(*) 
					FROM tbl_produto_rg_item  RI
					JOIN tbl_produto          PR USING(produto)
					WHERE RI.produto_rg        = $produto_rg
					AND   PR.troca_obrigatoria IS TRUE
				) AS troca_obrigatoria,
				(
					SELECT count(*)
					FROM tbl_produto_rg_item  RI
					JOIN tbl_produto          PR USING(produto)
					WHERE RI.produto_rg        = $produto_rg
				) AS cb_achado,
				(
					SELECT count(*)
					FROM tbl_produto_rg_item  RI
					WHERE RI.produto_rg        = $produto_rg
					AND   RI.produto           IS NULL
				) AS cb_perdido ";
		$res2 = @pg_exec($con,$sql);
		$troca_obrigatoria = @pg_result($res2,0,0);
		$cb_achado         = @pg_result($res2,0,1);
		$cb_perdido        = @pg_result($res2,0,2);

		$quadro .= "<div style='border: #000000 1px solid;display:inline;height:50px;width:150px;background:#FFFFB3;'>Produtos com Troca Obrigatória:<br> $troca_obrigatoria</div>&nbsp;&nbsp;";
		$quadro .= "<div style='border: #000000 1px solid;display:inline;height:50px;width:150px;;background:#C1FFC1;'>Produtos com código barra encontrados:<br> $cb_achado</div>&nbsp;&nbsp;";
		$quadro .= "<div style='border: #000000 1px solid;display:inline;height:50px;width:150px;;background:#FFD9D9;'>Produtos com código barra NÃO encontrados:<br> $cb_perdido</div><br>";

	}
	echo "ok|$resultado|$quadro";
	exit;
}

?>
