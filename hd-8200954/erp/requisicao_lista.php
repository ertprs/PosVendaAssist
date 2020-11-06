<?
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include 'autentica_usuario_empresa.php';
include 'menu.php';

//ACESSO RESTRITO AO USUARIO
if (strpos ($login_privilegios,'compra') === false AND strpos ($login_privilegios,'*') === false ) {
		echo "<script>"; 
			echo "window.location.href = 'menu_inicial.php?msg_erro=Você não tem permissão para acessar a tela.'";
		echo "</script>";
	exit;
}

if($_POST["botao"]=="Continuar"){

	$erro = "";

	$cont= $_POST["cont"];
	if(strlen($cont)==0) 
		$erro.= "<br>Cont está vazio!";

	###################  FAZER UPDATE DA TBL_REQUISICAO E TBL_REQUISICAO_ITEM ##################

	if(strlen($erro)==0){

		
		$res  = pg_exec($con, "begin;");

		$sql= "INSERT INTO 
				TBL_REQUISICAO_LISTA (data, empregado, status) 
				VALUES(current_timestamp,  $login_empregado, 'em cotacao');";

		$res= pg_exec($con, $sql);

		//echo "<br>1-sql: $sql";
		//$sql=" SELECT (max(requisicao_lista)+1) as requisicao_lista from tbl_requisicao_lista; ";

		$sql=" SELECT CURRVAL('tbl_requisicao_lista_requisicao_lista_seq') as requisicao_lista;";
		$res= pg_exec($con, $sql);
		
		//echo "<br>2-sql: $sql";

		if(strlen(pg_errormessage($con))>0){
			$erro.= "Erro ao inserir a requisicao_lista";
		}else{
			$requisicao_lista= trim(pg_result($res,0,requisicao_lista));
			if(strlen($requisicao_lista)==0){
				$erro.= "<br>Erro: requisicao_lista vazio!";
			}
		}	
		
		for($i=0;$i< $cont;$i++){

			$requisicao				=trim($_POST["requisicao_$i"]);
			$requisicao_item		=trim($_POST["req_item_$i"]);
			$peca					=trim($_POST["peca_$i"]);
			$quantidade_disponivel	=trim($_POST["qtd_disp_$i"]);
			$quantidade_entregar	=trim($_POST["qtd_entr_$i"]);
			$quantidade_solicitada	=trim($_POST["qtd_solic_$i"]);
			$quantidade_comprar		=trim($_POST["qtd_comprar_$i"]);
			$recusar				=trim($_POST["recusar_$i"]);
			$cotar					=trim($_POST["cotar_$i"]);
			$bloquear				=trim($_POST["bloquear_$i"]);
				
			$status		="";
			$selecao	="";

			if($bloquear == "sim"){
				$status		="bloqueado";
				$selecao	="bloqueado";
			}else{
				if($recusar=="sim"){
					$status		="nao autorizado";
					$selecao	="nao";
				}else{
					if($cotar=="sim"){
						$status		="autorizado";
						$selecao	="sim";
					}
				}
			}

			if(strlen($peca)==0){
				$erro.= "Código de peça está vazio!";
			}
			if(strlen($requisicao_item)==0){
				$erro.= "Código da requisicao_item está vazio!";				
			}
			if(strlen($requisicao)==0){
				$erro.= "Código da requisicao está vazio!";				
			}
			if(strlen($quantidade_comprar)==0){
				$erro.= "Quantidade a comprar está vazio!";				
			}
			if(strlen($quantidade_disponivel)==0)
				$quantidade_disponivel=0;
			if(strlen($quantidade_entregar)==0)
				$quantidade_entregar=0;
			if(strlen($quantidade_solicitada)==0)
				$quantidade_solicitada=0;
			if(strlen($quantidade_comprar)==0)
				$quantidade_comprar=0;

			
			if(strlen($erro)==0 and strlen($selecao)>0) {

				//BLOQUEAR A PEÇA PRA COTAÇÃO
				if (strlen($bloquear)>0){
					//VERIFICAR SE VAI REALMENTE INATIVAR AS PEÇAS??
					$sql= "	UPDATE tbl_peca_item
							SET status = 'inativo'
							WHERE peca = $peca";
					$res= pg_exec($con, $sql);
				}

				$sql= " UPDATE tbl_requisicao_item 
						SET 
							status='$status' 
						WHERE requisicao_item= $requisicao_item;";
				//echo "<br>2-UPD - sql: $sql";
				$res= pg_exec($con, $sql);


				if(strlen(pg_errormessage($con))>0){
					$erro.= "Erro no update tbl_requisicao_item";
				}

				$sql="	SELECT	*
						FROM tbl_requisicao 
						JOIN tbl_requisicao_item using(requisicao)
						WHERE requisicao= $requisicao 
							AND tbl_requisicao_item.status = 'aberto' ;";
				$res= pg_exec($con, $sql);

				if(pg_numrows($res) == 0){
					$sql="	UPDATE tbl_requisicao 
							SET status='finalizado' 
							WHERE requisicao= $requisicao;";
					$res= pg_exec($con, $sql);
					//echo "<br>1-UPD - sql: $sql";
					if(strlen(pg_errormessage($con))>0){
						$erro.= "Erro no update tbl_requisicao_item";
					}				
				}


				$sql= " INSERT INTO tbl_requisicao_lista_item 
						(requisicao_lista, 
						requisicao, 
						peca,
						quantidade_disponivel, 
						quantidade_entregar, 
						quantidade_solicitada, 
						quantidade_comprar, 
						selecao)
					VALUES(
						$requisicao_lista, 
						$requisicao, 
						$peca, 
						$quantidade_disponivel, 
						$quantidade_entregar, 
						$quantidade_solicitada, 
						$quantidade_comprar, 
						'$selecao');";
				//echo "<br>1-INS-sql: $sql";					
				$res= pg_exec($con, $sql);
			
				if(strlen(pg_errormessage($con))>0){
					$erro.= "Erro ao inserir requisicao_lista_item";
				}
			}
		}
		
		//SE ITEM FOR SELECIONADO, ENTAO VAI PARA A COTACAO
		if(strlen($requisicao_lista)>0) {
			$sql="  SELECT 
						tbl_requisicao_lista_item.peca, 
						tbl_estoque.qtde, 
						sum(tbl_requisicao_lista_item.quantidade_solicitada) as qtd, 
						sum(tbl_requisicao_lista_item.quantidade_comprar) as qtd2,
						tbl_estoque_extra.media_7,
						tbl_estoque_extra.media_20,
						tbl_estoque_extra.media_40,
						'a comprar'
					FROM tbl_requisicao_lista
					JOIN tbl_requisicao_lista_item USING(requisicao_lista)
					JOIN tbl_estoque on tbl_estoque.peca =  tbl_requisicao_lista_item.peca
					JOIN tbl_estoque_extra on tbl_estoque_extra.peca = tbl_estoque.peca
					WHERE requisicao_lista = $requisicao_lista and selecao ='sim'
					GROUP BY 
						tbl_requisicao_lista_item.peca, 
						tbl_estoque.qtde, 
						tbl_estoque_extra.media_7,
						tbl_estoque_extra.media_20,
						tbl_estoque_extra.media_40";
			$res= pg_exec($con, $sql);
			if(pg_numrows($res)>0){

				$sql="INSERT INTO TBL_COTACAO 
				   (empresa, data_abertura, status, requisicao_lista) 
				Values($login_empresa, current_date, 'aberta', $requisicao_lista);";
				//echo "<br>1-INS-sql: $sql";
				$res= pg_exec($con, $sql);
			
				if(strlen(pg_errormessage($con))>0){
					$erro.= "Erro ao inserir a COTACAO:".$sql;
				}

				$sql=" SELECT CURRVAL('tbl_cotacao_cotacao_seq') as cotacao;";
				$res= pg_exec($con, $sql);	

				$cotacao= trim(pg_result($res,0,cotacao));
				
				//ECHO "<BR><font color='red'> COTACAO: $cotacao</font>" ;

				$sql= "	INSERT INTO tbl_cotacao_item 
							(
									peca, 
									cotacao, 
									quantidade_disponivel, 
									quantidade_acotar, 
									quantidade_comprar, 
									quantidade_entregar,
									media_7,
									media_20,
									media_40,
									media4,
									status
							) 
							(							
								SELECT 
									tbl_requisicao_lista_item.peca, 
									$cotacao,
									tbl_estoque.qtde, 
									sum(tbl_requisicao_lista_item.quantidade_solicitada) as qtd, 
									sum(tbl_requisicao_lista_item.quantidade_comprar) as qtd2,
									tbl_estoque_extra.quantidade_entregar,
									cast(tbl_estoque_extra.media_7 as numeric(12,2)),
									cast(tbl_estoque_extra.media_20 as numeric(12,2)),
									cast(tbl_estoque_extra.media_40 as numeric(12,2)),
									cast(tbl_estoque_extra.media_7 as numeric(12,2)),
									'a comprar'
								FROM tbl_requisicao_lista
								JOIN tbl_requisicao_lista_item USING(requisicao_lista)
								JOIN tbl_estoque on tbl_estoque.peca =  tbl_requisicao_lista_item.peca
								JOIN tbl_estoque_extra on tbl_estoque_extra.peca = tbl_estoque.peca
								WHERE requisicao_lista = $requisicao_lista and selecao ='sim'
								GROUP BY 
									tbl_requisicao_lista_item.peca, 
									tbl_estoque.qtde, 
									tbl_estoque_extra.media_7,
									tbl_estoque_extra.media_20,
									tbl_estoque_extra.media_40,
									tbl_estoque_extra.quantidade_entregar

							);";
/*
SELECT 
									tbl_requisicao_lista_item.peca, 
									$cotacao,
									tbl_estoque.qtde, 
									sum(tbl_requisicao_lista_item.quantidade_solicitada) as qtd, 
									sum(tbl_requisicao_lista_item.quantidade_comprar) as qtd2,
									tbl_estoque_extra.media_7,
									tbl_estoque_extra.media_20,
									tbl_estoque_extra.media_40,
									'a comprar'
								FROM tbl_requisicao_lista
								JOIN tbl_requisicao_lista_item USING(requisicao_lista)
								JOIN tbl_estoque on tbl_estoque.peca =  tbl_requisicao_lista_item.peca
								JOIN tbl_estoque_extra on tbl_estoque_extra.peca = tbl_estoque.peca
								WHERE requisicao_lista = $requisicao_lista and selecao ='sim'
								GROUP BY 
									tbl_requisicao_lista_item.peca, 
									tbl_estoque.qtde, 
									tbl_estoque_extra.media_7,
									tbl_estoque_extra.media_20,
									tbl_estoque_extra.media_40*/

				//ECHO "<BR><font color='red'> COT_ITEM - SQL: $sql</font>" ;

				$res= pg_exec($con, $sql);
				
				if(strlen(pg_errormessage($con)) > 0){
					$erro.= "Erro ao inserir os itens da cotacao:".$sql;
				}
			}
		}else{
			$erro.= "Erro: requisição lista está vazio.";
		}
	}
	if(strlen($erro)==0){
		$res= pg_exec($con, "commit;");
		echo "<font color='blue'>ok, foi cadastrado com sucesso $erro!</font>";
		echo "<script language='JavaScript'>
			window.location= 'cotacao_mapa.php?cotacao=$cotacao';
		</script>";

	}else{		
		$res= pg_exec($con, "rollback;");
		$host  = $_SERVER['HTTP_HOST'];
		header("Location: http://$host/requisicao_lista.php?msg_erro");
		//echo "<font color='red'>$erro</font>";
	}

}

if(strlen($_GET["requisicao_sistema"])>0) {

	$sql= "SELECT 
			tbl_peca.peca
			FROM tbl_peca
			JOIN tbl_peca_item on tbl_peca.peca = tbl_peca_item.peca
			JOIN tbl_estoque on tbl_estoque.peca = tbl_peca.peca
			JOIN tbl_estoque_extra on tbl_estoque_extra.peca = tbl_estoque.peca
			WHERE ( ( tbl_estoque.qtde + tbl_estoque_extra.quantidade_entregar) < (tbl_estoque_extra.media_7 * 7) )
				AND ( ( tbl_estoque_extra.media_7 *7) > 1 ) 
				AND (   tbl_peca_item.status <> 'inativo' OR tbl_peca_item.status IS NULL )";
	$res= pg_exec($con, $sql);
//	echo "sql: $sql";
	if(pg_numrows($res)>0){

		$res= pg_exec($con, "begin;");

		//USUARIO 7 É ADMIN
		$sql="	DELETE FROM 
					tbl_requisicao_item 
				WHERE requisicao_item in(
										SELECT requisicao_item
										FROM tbl_requisicao
										JOIN tbl_requisicao_item using(requisicao)
										WHERE tbl_requisicao.empregado = 7 and tbl_requisicao.status= 'aberto'
										)";
		//echo "sql: $sql";
		$res= pg_exec($con, $sql);

		$sql="	DELETE FROM tbl_requisicao
				WHERE 
					empregado = 7 AND tbl_requisicao.status= 'aberto'";
		//echo "sql: $sql";
		$res= pg_exec($con, $sql);

		$sql= "/* INSERE NA TBL_REQUISICAO, E RESGATA A CHAVE PRIMARIA */
				INSERT INTO tbl_requisicao(data, empregado, status, empresa)					
				VALUES(current_date , 7, 'aberto', $login_empresa);

				SELECT CURRVAL ('tbl_requisicao_requisicao_seq') as requisicao ;";

		$res= pg_exec($con, $sql);	
		$requisicao = trim(pg_result($res,0,requisicao));

		//echo "<br>sql: $sql";

		$sql="/* INSERE OS PRODUTOS A SEREM COTADOS*/
				INSERT INTO tbl_requisicao_item
					(requisicao,
					peca,
					quantidade, 
					quantidade_solicitada,
					status)
					(
						SELECT 
							$requisicao, 
							tbl_peca.peca, 
							( ( tbl_estoque_extra.media_7 * 7) - ( tbl_estoque.qtde+ tbl_estoque_extra.quantidade_entregar)  ) as qc, 
							( ( tbl_estoque_extra.media_7 * 7) - ( tbl_estoque.qtde+ tbl_estoque_extra.quantidade_entregar)  ) as qcomp, 
							'aberto' 
						FROM tbl_peca 
						JOIN tbl_peca_item on tbl_peca.peca = tbl_peca_item.peca 
						JOIN tbl_estoque on tbl_estoque.peca = tbl_peca.peca 
						JOIN tbl_estoque_extra on tbl_estoque_extra.peca = tbl_estoque.peca 
						WHERE ( ( ( tbl_estoque_extra.media_7 * 7) - ( tbl_estoque.qtde+ tbl_estoque_extra.quantidade_entregar)  )>0) 
						AND ( tbl_peca_item.status <> 'inativo' OR tbl_peca_item.status IS NULL ) 
					);";

//SQL ANTIGO
/*
					SELECT $requisicao,
						tbl_peca.peca,
						case WHEN (tbl_estoque.qtde + tbl_estoque_extra.quantidade_entregar > 0)
							THEN ((tbl_estoque_extra.media_7 * 7)- (tbl_estoque.qtde + tbl_estoque_extra.quantidade_entregar))
							ELSE ((tbl_estoque_extra.media_7 * 7) + (tbl_estoque.qtde+ tbl_estoque_extra.quantidade_entregar))
						END  as qc,	
						case WHEN (tbl_estoque.qtde+ tbl_estoque_extra.quantidade_entregar > 0)
							THEN ((tbl_estoque_extra.media_7 * 7)- (tbl_estoque.qtde+ tbl_estoque_extra.quantidade_entregar))
							ELSE ((tbl_estoque_extra.media_7 * 7) + (tbl_estoque.qtde+ tbl_estoque_extra.quantidade_entregar))
						END  as qcomp,
						'aberto'
					FROM tbl_peca
					JOIN tbl_peca_item on tbl_peca.peca = tbl_peca_item.peca
					JOIN tbl_estoque on tbl_estoque.peca = tbl_peca.peca
					JOIN tbl_estoque_extra on tbl_estoque_extra.peca = tbl_estoque.peca
					WHERE ( ( tbl_estoque.qtde+ tbl_estoque_extra.quantidade_entregar) <	 (tbl_estoque_extra.media_7 * 7) )
						AND ( ( tbl_estoque_extra.media_7 * 7) > 0 ) 
						AND (   tbl_peca_item.status <> 'inativo' OR tbl_peca_item.status IS NULL )
*/					

		//echo "sql: $sql";
		$res= pg_exec($con, $sql);

		if(strlen(pg_errormessage($con))>0){
			$res= pg_exec($con, "ROLLBACK;");
			echo "<font color='red'>erro na geração da requisicao:". pg_errormessage($res)."</font>";
		}else{
			$res= pg_exec($con, "commit;");
			echo "<font color='blue'>Cadastro com sucesso</font>";
			echo "<br><a href='requisicao.php?requisicao=$requisicao'><font color='#0000ff'>$requisicao</font></a>";
		}
	}else{
		$msg_erro ="Não existe produto a ser cotado!";
	}
}

$msg_erro.= $_GET["msg_erro"];

$sql= "SELECT 
			tbl_peca.peca, 
			tbl_peca.referencia, 
			tbl_peca.descricao, 
			tbl_estoque.qtde as qd, 
			tbl_requisicao.requisicao,
			tbl_requisicao.data,
			tbl_requisicao_item.requisicao_item,  
			tbl_requisicao_item.status,  
			tbl_requisicao_item.quantidade as qc,  
			tbl_pessoa.nome as nome_usuario,
			qtd_cotacao.orcando,
			tbl_estoque_extra.quantidade_entregar,
			tbl_peca_item.familia,
			tbl_peca_item.linha,
			tbl_peca_item.marca
		FROM tbl_requisicao
		JOIN tbl_requisicao_item on tbl_requisicao_item.requisicao  = tbl_requisicao.requisicao
		JOIN tbl_peca			 on tbl_peca.peca					= tbl_requisicao_item.peca
		JOIN tbl_peca_item       on tbl_peca.peca                   = tbl_peca_item.peca
		left JOIN tbl_estoque		 on tbl_estoque.peca				= tbl_peca.peca
		left JOIN tbl_estoque_extra	 on tbl_estoque.peca				= tbl_estoque_extra.peca
		JOIN tbl_empregado		 on tbl_empregado.empregado			= tbl_requisicao.empregado
		JOIN tbl_pessoa			 on tbl_pessoa.pessoa				= tbl_empregado.pessoa
		LEFT JOIN (	
				SELECT peca, sum(quantidade_acotar)as orcando
				FROM tbl_cotacao 
				JOIN tbl_cotacao_item USING(cotacao) 
				WHERE tbl_cotacao.status='aberta' 
					AND tbl_cotacao_item.status='a comprar'
				GROUP BY peca
			) qtd_cotacao on qtd_cotacao.peca = tbl_requisicao_item.peca
		WHERE tbl_requisicao.status ='aberto' AND tbl_requisicao_item.status ='aberto' 
		order by tbl_peca.descricao";

//echo "sql: $sql";

$res= pg_exec($con, $sql);
echo "<br> <font color='red'> $msg_erro</font>";
?>
<script type="text/javascript">
	$(function() {
		$('#container-Principal').tabs( {fxAutoHeight: true} );
	});
</script>

<style type="text/css">
.tabela{
	font-family: Verdana;
	font-size: 12px;
	
}

.Label{
	font-family: Verdana;
	font-size: 10px;
}

.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	border: 0px;
}
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color: #000000;
	border: 0px;
}

.titulo {
	font-family: Arial;
	font-size: 10pt;
	color: #000000;
	background: #ced7e7;
}

.Caixa{
	FONT: 8pt Arial ;
	BORDER-RIGHT:     #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}

.Botao{
	FONT: 10pt Arial ;
	BORDER-RIGHT:     #000000 1px solid;
	BACKGROUND-COLOR: #C0C0C0;
	padding:3px;
}

</style>

<script language="javascript">

function check_cotar(linha){
	var recusar		= document.getElementById('recusar_'+linha);
	var cotar = document.getElementById('cotar_'+linha);
	var bloquear= document.getElementById('bloquear_'+linha);
	if (cotar.checked){
		recusar.checked= false;
		bloquear.checked= false;
	}
}

function check_recusar(linha){
	
	var recusar		= document.getElementById('recusar_'+linha);
	var cotar = document.getElementById('cotar_'+linha);
	var bloquear= document.getElementById('bloquear_'+linha);
	if (recusar.checked){
		cotar.checked= false;
		bloquear.checked= false;
	}
}
function check_bloquear(linha){
	var recusar	= document.getElementById('recusar_'+linha);
	var cotar	= document.getElementById('cotar_'+linha);
	var bloquear= document.getElementById('bloquear_'+linha);
	if (bloquear.checked){
		cotar.checked= false;
		recusar.checked= true;
	}
}
</script>

<table style=' border:#485989 1px solid; background-color: #e6eef7' align='center' width='890' border='0' class='tabela'>
		<tr height='20' bgcolor='#7392BF'>
			<td class='Titulo_Tabela' align='center' colspan='6'>Gerar cotação de compra</td>
		</tr>
		<tr height='10'>
			<td  align='center' colspan='6'></td>
		</tr>
		<tr>
			<td class='Label'>
				<div id="container-Principal">
					<ul>
						<li><a href="#tab1Procurar"><span><img src='imagens/lupa.png' align=absmiddle> Busca</span></a></li>
						<li><a href="#tab2Gerar"><span><img src='imagens/document-txt-blue-new.png' align=absmiddle> Gerar cotação</span></a></li>
					</ul>
					<div id="tab1Procurar">
						<table align='left' width='890' border='0' class='tabela'>
						<form method='POST' name="Pesquisar" action="requisicao_mostra_listas.php">
							<tr>
								<td class='Label' align='center'><BR>Usuário:
									<?		$sql2 = "SELECT 
													DISTINCT (tbl_requisicao_lista.empregado),
													pessoa_empregado.nome
													from tbl_requisicao_lista
													JOIN tbl_cotacao using(requisicao_lista)
													JOIN tbl_requisicao_lista_item USING(requisicao_lista)
													JOIN tbl_empregado on tbl_empregado.empregado = tbl_requisicao_lista.empregado
													JOIN tbl_pessoa as pessoa_empregado on pessoa_empregado.pessoa = tbl_empregado.pessoa";

											$res2 = pg_exec($con, $sql2);

											echo "<SELECT NAME='busca' class='Caixa'>";
											echo "<OPTION></OPTION>";
												for ( $i = 0 ; $i < pg_numrows ($res2) ; $i++ ) {
													$nome = trim(pg_result($res2,$i,nome));
													echo "<OPTION VALUE=$nome>$nome</OPTION>";
												}
											echo "</SELECT>";
										?>
								</td>
							</tr>
							<tr>
								<td class='Label' align='center'>
								Pesquisar cotação por Status: 
								<input type='radio' name='tipo' value='ab' <?if($tipo=='ab') echo "CHECKED";?> CHECKED> Aberta
								<input type='radio' name='tipo' value='fi' <?if($tipo=='fi') echo "CHECKED";?>> Finalizada
								</td>
							</tr>
							<tr>
								<td align='center'>
									<BR><input name="btnG" value="Pesquisar" type="submit" class='Botao'>
								</td>
							</tr>
						</form>
						</table>
					</div>
					<div id="tab2Gerar">
						<table width='700' align='center' border='1' cellpadding="2" cellspacing="0" style='border-collapse: collapse' bordercolor='#D2E4FC' class='tabela'>
						<FORM ACTION='requisicao_lista.php' METHOD='POST'>
							<tr >
								<td class='menu_top' nowrap colspan='13' align='right' >
									<a href='requisicao_lista.php?requisicao_sistema=gerar'><font color='#0000ff'>GERAR REQUISIÇÃO DO SISTEMA</font></a>
								</td>
							</tr>
							<tr bgcolor='#596D9B'>
								<td class='menu_top' nowrap colspan='13' align='center' ></td>
							</tr>
							<tr bgcolor='#596D9B'>
								<td class='titulo' nowrap colspan='4' align='center' >Produto</td>
								<td class='titulo' nowrap colspan='1' align='center' >Usuário</td>
								<td class='titulo' nowrap colspan='5' align='center' >Quantidade</td>
								<td class='titulo' nowrap colspan='3' align='center' >Ações</td>
							</tr>
							<tr bgcolor='#596D9B'>
								<td class='titulo' nowrap align='center'>#</td>
								<td class='titulo' nowrap align='center'>Requisição</td>
								<td class='titulo' nowrap align='center'>Código</td>
								<td class='titulo' width='200' nowrap nowrap align='center'>Nome do produto</td>
								<td class='titulo' nowrap align='center'>Nome</td>
								<td class='titulo' nowrap align='center'>Disponível</td>
								<td class='titulo' nowrap align='center'>Entregar</td>
								<td class='titulo' nowrap align='center'>Orçando</td>
								<td class='titulo' nowrap align='center'>Solicitada</td>
								<td class='titulo' nowrap align='center'>Comprar</td>
								<td class='titulo' nowrap align='center'>
									<acronym title='Esta opção inativa a peça para as próximas compras!'>Bloquear</acronym>	
								</td>
								<td class='titulo' nowrap align='center'>Recusar</td>
								<td class='titulo' nowrap align='center'>Selec.</td>
							</tr>
						<? 
							//IMPRESSAO DAS PEÇAS
						  if(pg_numrows($res)>0){
							for($i=0; $i<pg_numrows($res); $i++){

								if ($cor== "#eeeeff")	$cor= "#fafafa";
								else					$cor= "#eeeeff";

								$checked = "checked";
								$requisicao		= pg_result($res,$i,requisicao);
								$peca			= pg_result($res,$i,peca);
								$descricao		= pg_result($res,$i,descricao);		
								$nome_usuario	= pg_result($res,$i,nome_usuario);
								$qtd_disponivel = pg_result($res,$i,qd);
								$referencia		= pg_result($res,$i,referencia);	
								$orcando		= pg_result($res,$i,orcando);	
								$qtd_ent		= pg_result($res,$i,quantidade_entregar);	
								$qtd_solicitada = trim(pg_result($res,$i,qc));// usado no hidden
								$requisicao_item= trim(pg_result($res,$i,requisicao_item));
								$marca          = trim(pg_result($res,$i,marca));	
								$linha          = trim(pg_result($res,$i,linha));	
								$familia        = trim(pg_result($res,$i,familia));	
								// samuel 23/10 tirou o disabled pq posso comprar qq coisa mesmo sem ter o cadastro completo. A obrigatoriedade é no momento do recebimento de mercadoria, e o comprador é quem vai escolher o q comprar
								//if($linha == "447" OR $familia == "767" or $marca==""){$desativado = "DISABLED";}
								//$check= $checked;

								if(strlen($qtd_disponivel)==0)
									$qtd_disponivel = 0;
								if(strlen($qtd_ent)==0)
									$qtd_ent = 0;

								echo "<tr bgcolor='$cor' style='font-size: 10px'>";
								echo "<td align='center' nowrap>".($i+1)."</td>";
								echo "<td align='center' nowrap>";
								echo "<a href='requisicao.php?requisicao=$requisicao'><font color='#0000ff'>$requisicao</font></a>";
								echo "</td>";
								echo "<td align='left' nowrap>$referencia</td>";
								echo "<td align='left' nowrap>";
								if($linha == "447" OR $familia == "767" or $marca==""){echo "<a href='cadastro_produto.php?btn_acao=alterar&peca=$peca'>$descricao</a>";}
								else{
									echo "$descricao";
								}
								echo "</td>";
								echo "<td align='center' nowrap>$nome_usuario</td>";
								echo "<td align='center' nowrap >$qtd_disponivel</td>";
								echo "<td align='center' nowrap >$qtd_ent</td>";
								echo "<td align='center' nowrap >$orcando</td>";
								echo "<td align='center' nowrap >$qtd_solicitada</td>";

								echo "<td align='center' nowrap >
									<input type='text' name='qtd_comprar_$i' value='$qtd_solicitada' size=2 maxlength=10>
								</td>";
								echo "<td align='center' nowrap >";
								if($linha == "447" OR $familia == "767" or $marca==""){
									echo "<input title='Esta opção inativa a peça para as próximas compras!' type='checkbox' name='bloquear_$i' value='sim' $desativado onclick='javascript:check_bloquear(\"$i\")'>";
									echo "</td>";
									echo "<td align='center' nowrap >";
									echo "<input type='checkbox' name='recusar_$i'$desativado value='sim'  onclick='javascript:check_recusar(\"$i\")'>";
								} else {
									echo "<input title='Esta opção inativa a peça para as próximas compras!' type='checkbox' name='bloquear_$i' value='sim' onclick='javascript:check_bloquear(\"$i\")'>";
									echo "</td>";
									echo "<td align='center' nowrap >";
									echo "<input type='checkbox' name='recusar_$i' value='sim'  onclick='javascript:check_recusar(\"$i\")'>";
								}
								echo "</td>";
								echo "<td align='center' nowrap >";
								echo "<input type='hidden' name='qtd_disp_$i' value='$qtd_disponivel'>";
								echo "<input type='hidden' name='qtd_entr_$i' value='$qtd_entregar'>";
								echo "<input type='hidden' name='qtd_solic_$i' value='$qtd_solicitada'>";
								echo "<input type='hidden' name='requisicao_$i' value='$requisicao'>";
								echo "<input type='hidden' name='req_item_$i' value='$requisicao_item'>";
								echo "<input type='hidden' name='peca_$i' value='$peca'>";
								if($linha == "447" OR $familia == "767" or $marca==""){
									echo "<input type='checkbox' name='cotar_$i' $desativado value='sim' $check onclick='javascript:check_cotar(\"$i\")'>";
								} else{
									echo "<input type='checkbox' name='cotar_$i' value='sim' onclick='javascript:check_cotar(\"$i\")'>";
								}
								echo "</td>";
								echo "</tr>";
							}
						  }else{
								echo "<tr ><td colspan='13' align='center'> <font color='#0000ff'><b>Sem Requisição Cadastrada!</font></b></td></tr>"; 
						  }
						?>
							<tr style='font-size: 10px'>
								<td align='right' colspan='13' nowrap>
									<input type='hidden' name='cont' value='<?echo "$i";?>'>
									<input type='submit' name='botao' value="Continuar" class='Botao'>
								</td>
							</tr>
						</form>
						</table>
					</div>
		</td>
	</tr>
</table>