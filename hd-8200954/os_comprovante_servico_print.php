<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if(strlen($_GET['os']) == 0){
	echo "<script>";
	echo "close();";
	echo "</script>";
	exit;
}

$os = trim($_POST['os']);
if(strlen($_GET['os']) > 0){
	$os = trim($_GET['os']);
}

$title = "COMPROVANTE DE SERVIÇO";
if($sistema_lingua=='ES') $title = "COMPROBANTE DE SERVICIO";
?>

<style type="text/css">

.Titulo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;

	color:#000000;
}

.Titulo2 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#000000;
}

.Conteudo {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: normal;
	color:#000000;
}

.Conteudo2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	color:#000000;
}

#info b{

	display: block;
	width: 140px;
	float:left;
	color: #000;
	border-bottom: 1px solid #f1f1f1;
	clear:both;
}

</style>

<p>
<?
# ----------------------------------------- #
# -- VERIFICA SE É POSTO OU DISTRIBUIDOR -- #
# ----------------------------------------- #
$sql = "SELECT  DISTINCT
				tbl_tipo_posto.tipo_posto     ,
				tbl_posto.estado
		FROM    tbl_tipo_posto
		JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
									AND tbl_posto_fabrica.posto      = $login_posto
									AND tbl_posto_fabrica.fabrica    = $login_fabrica
		JOIN    tbl_posto            ON tbl_posto.posto = tbl_posto_fabrica.posto
		WHERE   tbl_tipo_posto.distribuidor IS TRUE
		AND     tbl_posto_fabrica.fabrica = $login_fabrica
		AND     tbl_tipo_posto.fabrica    = $login_fabrica
		AND     tbl_posto_fabrica.posto   = $login_posto ";
$res = pg_exec ($con,$sql);
if (pg_numrows($res) == 0) $tipo_posto = "P"; else $tipo_posto = "D";


$sql = "SELECT  tbl_os.sua_os                                                  ,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura  ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
					tbl_produto.referencia                                         ,
					tbl_produto.descricao                                          ,
					tbl_defeito_reclamado.descricao AS defeito_cliente             ,
					tbl_os.cliente                                                 ,
					tbl_os.os AS id_os 											   ,
					tbl_os.revenda                                                 ,
					tbl_os.serie                                                   ,
					tbl_os.codigo_fabricacao                                       ,
					tbl_os.consumidor_cpf                                         ,
					tbl_os.consumidor_nome                                         ,
					tbl_os.consumidor_fone                                         ,
					tbl_os.consumidor_endereco                                     ,
					tbl_os.consumidor_numero                                       ,
					tbl_os.consumidor_complemento                                  ,
					tbl_os.consumidor_bairro                                       ,
					tbl_os.consumidor_cep                                          ,
					tbl_os.consumidor_cidade                                       ,
					tbl_os.consumidor_estado                                       ,
					tbl_os.revenda_cnpj                                            ,
					tbl_os.revenda_nome                                            ,
					tbl_os.nota_fiscal                                             ,
					to_char(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf        ,
					tbl_os.defeito_reclamado                                       ,
					tbl_os.defeito_reclamado_descricao                             ,
					tbl_os.acessorios                                              ,
					tbl_os.aparencia_produto                                       ,
					tbl_os.obs                                                     ,
					tbl_posto.nome                                                 ,
					tbl_posto.endereco                                             ,
					tbl_posto.numero                                               ,
					tbl_posto.cep                                                  ,
					tbl_posto.cidade                                               ,
					tbl_posto.estado                                               ,
					tbl_posto.fone                                                 ,
					tbl_posto.cnpj                                                 ,
					tbl_posto.ie                                                   ,
					tbl_posto.email                                                ,
					tbl_os.consumidor_revenda                                      ,
					tbl_os.tipo_atendimento                                           ,
					tbl_os.tecnico_nome                                               ,
					tbl_tipo_atendimento.descricao                 AS nome_atendimento,
					tbl_os.qtde_produtos                                             ,
					tbl_os.excluida
					/*tbl_tipo_atendimento_idioma.descricao as nome_atendimento_idioma*/
			FROM    tbl_os
			JOIN    tbl_produto USING (produto)
			JOIN    tbl_posto   USING (posto)
			LEFT JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
			LEFT JOIN tbl_defeito_reclamado USING (defeito_reclamado)
			/*LEFT JOIN tbl_tipo_atendimento_idioma ON tbl_tipo_atendimento.tipo_atendimento = tbl_tipo_atendimento_idioma.tipo_atendimento*/
	WHERE	tbl_os.os = $os
	AND	tbl_os.posto = $login_posto ";

$sql .= " ORDER BY    lpad(substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')),20,'0')               ASC,
		     replace(lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')),20,'0'),'-','') ASC";
$res = pg_exec ($con,$sql);
$totalRegistros = pg_numrows($res);

$img_contrato = "logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";

if ($totalRegistros == 0){
	echo "<script>";
	echo "close();";
	echo "</script>";
	exit;
}elseif ($totalRegistros > 0){
	$ja_baixado = false ;
	$sua_os = pg_result ($res,0,sua_os) ;

	echo "<TABLE width='650' align='center' border='1' cellspacing='0' cellpadding='1' style='border-collapse: collapse' bordercolor='#000000'>\n";
	ECHO "<TR CLASS='TITULO'>";
	echo "<TD ><IMG SRC=' $img_contrato' HEIGHT='40' ALT='ORDEM DE SERVIÇO'></TD>";
	echo "<TD>$title</TD>";
	echo "<TD>";
	if ($login_fabrica == 1) echo $posto_codigo.$sua_os;
	else                     echo $sua_os;
	ECHO "</TD>";
	ECHO "</TR>";
	echo "</TABLE><br>\n";


//--=== DADOS DO POSTO============================================================================================--\\
	$sql2 = "SELECT  tbl_posto_fabrica.codigo_posto                          ,
					tbl_posto.posto                                         ,
					tbl_posto.nome                                          ,
					tbl_posto.endereco                                      ,
					tbl_posto.cidade                                        ,
					tbl_posto.estado                                        ,
					tbl_posto.cep                                           ,
					tbl_posto.fone                                          ,
					tbl_posto.fax                                           ,
					tbl_posto.contato                                       ,
					tbl_posto.email                                         ,
					tbl_posto.cnpj                                          ,
					tbl_posto.ie                                            ,
					tbl_posto_fabrica.banco                                 ,
					tbl_posto_fabrica.agencia                               ,
					tbl_posto_fabrica.conta
			FROM    tbl_posto
			JOIN    tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
						  AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN    tbl_os            ON tbl_os.posto               = tbl_posto.posto
			WHERE   tbl_os.os = $os;";
	$res2 = pg_exec ($con,$sql2);

	if (pg_numrows($res2) > 0) {
		$posto_nome                  = pg_result ($res,0,nome)                       ;
		$posto_endereco              = strtoupper(pg_result ($res,0,endereco))       ;
		$posto_numero                = pg_result ($res,0,numero)                     ;
		$posto_cep                   = pg_result ($res,0,cep)                        ;
		$posto_cidade                = pg_result ($res,0,cidade)                     ;
		$posto_estado                = pg_result ($res,0,estado)                     ;
		$posto_fone                  = pg_result ($res,0,fone)                       ;
		$posto_cnpj                  = pg_result ($res,0,cnpj)                       ;
		$posto_ie                    = pg_result ($res,0,ie)                         ;
		$posto_email                 = pg_result ($res,0,email)                      ;


	echo "<TABLE width='650' align='center' border='1' cellspacing='0' cellpadding='0' style='border-collapse: collapse' bordercolor='#000000'>\n";
	echo "<TR class='Conteudo2'>\n";
	echo "<TD>";

	echo "<table border='0'>";
	echo "<tr>";
	echo "<td class='Titulo2'>";
	if($sistema_lingua=='ES')echo "SERVICIO";
	else                     echo "POSTO";
	echo "</td>";
	echo "<td class='Conteudo2' width='200'>$posto_nome</td>";
	echo "</tr>";
	echo "<td class='Titulo2'>";
	if($sistema_lingua=='ES')echo "DIRECCION";
	else                     echo "ENDEREÇO";
	echo "</td>";
	echo "<td class='Conteudo2'>$posto_endereco,$posto_numero</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='Titulo2'>";
	if($sistema_lingua=='ES')echo "CIUDAD";
	else                     echo "CIDADE";
	echo "</td>";
	echo "<td class='Conteudo2'>$posto_cidade - $posto_estado</td>";
	echo "<td class='Titulo2'>";
	if($sistema_lingua=='ES')echo "APARTADO POSTAL";
	else                     echo "CEP";
	echo "</td>";
	echo "<td class='Conteudo2'>$posto_cep</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='Titulo2'>";
	if($sistema_lingua=='ES')echo "TELÉFONO";
	else                     echo "TELEFONE";
	echo "</td>";
	echo "<td class='Conteudo2'>$posto_fone</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='Titulo2'>";
	if($sistema_lingua=='ES')echo "IDENTIFICACÍON 1";
	else                     echo "CNPJ";
	echo "</td>";
	echo "<td class='Conteudo2'>$posto_cnpj</td>";
	echo "<td class='Titulo2'>";
	if($sistema_lingua=='ES')echo "IDENTIFICACÍON 2";
	else                     echo "IE/RG";
	echo "</td>";
	echo "<td class='Conteudo2'>$posto_ie</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='Titulo2'>EMAIL</td>";
	echo "<td class='Conteudo2'>$posto_email</td>";
	echo "</tr>";
	echo "</table>";

	echo "</TD>";
	echo "</TR>";
	echo "</TABLE>";

	}
//--=== DADOS DO POSTO============================================================================================--\\

	echo "<br>";
	echo "<TABLE width='650' align='center' border='1' cellspacing='0' cellpadding='1' style='border-collapse: collapse' bordercolor='#000000'>\n";

	echo "<TR class='Titulo2'>\n";
	echo "<TD colspan='6'><i>";
	if($sistema_lingua=='ES')echo "Informaciones en la orden de servicio";
	else                     echo "Informações sobre a Ordem de Serviço";
	echo "</i></TD>\n";
	echo "</TR>";

	echo "<TR class='Titulo2'>\n";
	echo "<TD>OS</TD>\n";
	echo "<TD>";
	if($sistema_lingua=='ES')echo "REFERENCIA";
	else                     echo "REFERÊNCIA";
	echo "</TD>\n";
	echo "<TD>";
	if($sistema_lingua=='ES')echo "PRODUCTO";
	else                     echo "PRODUTO";
	echo "</TD>\n";
	echo "<TD>";
	if($sistema_lingua=='ES')echo "ABERTURA";
	else                     echo "ABERTURA";
	echo "</TD>\n";
	echo "<TD>";
	if($sistema_lingua=='ES')echo "CIERRE";
	else                     echo "FECHAMENTO";
	echo "</TD>\n";
	echo "<TD>";
	if($sistema_lingua=='ES')echo "SERIE";
	else                     echo "SÉRIE";
	echo "</TD>\n";

	echo "	</TR>\n";


	$total             = 0;
	$total_mao_de_obra = 0;
	$total_pecas       = 0;


	for ($i = 0 ; $i < $totalRegistros; $i++){

		$sua_os                      = pg_result ($res,0,sua_os)                     ;
		$data_abertura               = pg_result ($res,0,data_abertura)              ;
		$data_fechamento             = pg_result ($res,0,data_fechamento)            ;
		$referencia                  = pg_result ($res,0,referencia)                 ;
		$descricao                   = pg_result ($res,0,descricao)                  ;
		$serie                       = pg_result ($res,0,serie)                      ;
		$codigo_fabricacao           = pg_result ($res,0,codigo_fabricacao)          ;
		$cliente                     = pg_result ($res,0,cliente)                    ;
		$revenda                     = pg_result ($res,0,revenda)                    ;
		$consumidor_cpf              = pg_result ($res,0,consumidor_cpf)             ;
		$consumidor_nome             = pg_result ($res,0,consumidor_nome)            ;
		$consumidor_endereco         = pg_result ($res,0,consumidor_endereco)        ;
		$consumidor_numero           = pg_result ($res,0,consumidor_numero)          ;
		$consumidor_complemento      = pg_result ($res,0,consumidor_complemento)     ;
		$consumidor_bairro           = pg_result ($res,0,consumidor_bairro)          ;
		$consumidor_cidade           = pg_result ($res,0,consumidor_cidade)          ;
		$consumidor_estado           = pg_result ($res,0,consumidor_estado)          ;
		$consumidor_cep              = pg_result ($res,0,consumidor_cep)             ;
		$consumidor_fone             = pg_result ($res,0,consumidor_fone)            ;
		$revenda_cnpj                = pg_result ($res,0,revenda_cnpj)               ;
		$revenda_nome                = pg_result ($res,0,revenda_nome)               ;
		$nota_fiscal                 = pg_result ($res,0,nota_fiscal)                ;
		$data_nf                     = pg_result ($res,0,data_nf)                    ;
		$defeito_reclamado           = pg_result ($res,0,defeito_reclamado)          ;
		$aparencia_produto           = pg_result ($res,0,aparencia_produto)          ;
		$acessorios                  = pg_result ($res,0,acessorios)                 ;
		$defeito_cliente             = pg_result ($res,0,defeito_cliente)            ;
		$defeito_reclamado_descricao = pg_result ($res,0,defeito_reclamado_descricao);
		$posto_nome                  = pg_result ($res,0,nome)                       ;
		$posto_endereco              = pg_result ($res,0,endereco)                   ;
		$posto_numero                = pg_result ($res,0,numero)                     ;
		$posto_cep                   = pg_result ($res,0,cep)                        ;
		$posto_cidade                = pg_result ($res,0,cidade)                     ;
		$posto_estado                = pg_result ($res,0,estado)                     ;
		$posto_fone                  = pg_result ($res,0,fone)                       ;
		$posto_cnpj                  = pg_result ($res,0,cnpj)                       ;
		$posto_ie                    = pg_result ($res,0,ie)                         ;
		$consumidor_revenda          = pg_result ($res,0,consumidor_revenda)         ;
		$obs                         = pg_result ($res,0,obs)                        ;
		$qtde_produtos               = pg_result ($res,0,qtde_produtos)              ;
		$excluida                    = pg_result ($res,0,excluida)                   ;
		$tipo_atendimento            = trim(pg_result($res,0,tipo_atendimento))      ;
		$tecnico_nome                = trim(pg_result($res,0,tecnico_nome))          ;
		$nome_atendimento            = trim(pg_result($res,0,nome_atendimento))      ;
		$id_os 						 = trim(pg_result($res,0,id_os))      			 ;


		$sql_linguagem = "SELECT tbl_tipo_atendimento_idioma.descricao AS nome_atendimento_idioma
							FROM    tbl_os
							JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
							JOIN tbl_tipo_atendimento_idioma ON tbl_tipo_atendimento.tipo_atendimento = tbl_tipo_atendimento_idioma.tipo_atendimento
							WHERE tbl_os.os = $id_os";
		$res_linguagem = pg_query($con, $sql_linguagem);
		if(pg_num_rows($res_linguagem) > 0){
			$nome_atendimento_idioma     = trim(pg_result($res_linguagem,0,nome_atendimento_idioma))      ;
		}

		$sql_idioma = " SELECT tbl_produto_idioma.* FROM tbl_produto_idioma
				JOIN    tbl_produto USING (produto)
				WHERE referencia     = '$referencia'
				AND upper(idioma) = '$sistema_lingua'";
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
		$descricao  = trim(@pg_result($res_idioma,0,descricao));
		}

		$sql_idioma = "SELECT * FROM tbl_defeito_reclamado_idioma
				WHERE defeito_reclamado = $defeito_reclamado
				AND upper(idioma)        = '$sistema_lingua'";
		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
		$defeito_cliente  = trim(@pg_result($res_idioma,0,descricao));
		}

		echo "<TR class='Conteudo2'>\n";
		echo "<TD>";
		if($login_fabrica == 1) echo $posto_codigo;
		echo "$sua_os</TD>\n";
		echo "<TD>$referencia</TD>\n";
		echo "<TD>$descricao</TD>\n";
		echo "<TD>$data_abertura</td>\n";
		echo "<TD>$data_fechamento</td>\n";
		echo "<TD>$serie</td>\n";
		echo "	</TR>\n";
		echo "<TR class='Conteudo2'>\n";
		//HD 6027 Paulo colocar tipo de atendimento
		echo "<TD colspan='6'>";
		if($sistema_lingua=='ES')echo "<b>TIPO DE ATENCIÓN:</b>". " " .$nome_atendimento_idioma;
		else                     echo "<b>TIPO DE ATENDIMENTO:</b>". " " .$nome_atendimento;
		echo "</TD>";
		echo "	</TR>\n";
	}

	echo "</TABLE>\n";


	echo "<br><TABLE width='650' align='center' border='1' cellspacing='0' cellpadding='1' style='border-collapse: collapse' bordercolor='#000000'>";
	echo "<TR class='Titulo2'>";
	echo "<TD>";
	if($sistema_lingua=='ES')echo "NOMBRE DEL CLIENTE";
	else                     echo "NOME DO CONSUMIDOR";
	echo "</TD>";
	echo "<TD>";
	if($sistema_lingua=='ES')echo "CIUDAD";
	else                     echo "CIDADE";
	echo "</TD>";
	echo "<TD>";
	if($sistema_lingua=='ES')echo "PROVINCIA";
	else                     echo "ESTADO</TD>";
	echo "<TD>";
	if($sistema_lingua=='ES')echo "TELEFONO";
	else                     echo "FONE";
	echo "</TD>";
	echo "</TR>";
	echo "<TR class='Conteudo2' align='center'>";
	echo "<TD>$consumidor_nome</TD>";
	echo "<TD>$consumidor_cidade</TD>";
	echo "<TD>$consumidor_estado</TD>";
	echo "<TD>$consumidor_fone</TD>";
	echo "</TR>";
	echo "</TABLE>";

	echo "<TABLE width='650' align='center' border='1' cellspacing='0' cellpadding='1' style='border-collapse: collapse ; border-top:none;' bordercolor='#000000'>";
	echo "<TR class='Titulo2'>";
	echo "<TD>";
	if($sistema_lingua=='ES')echo "DIRECCIÓN";
	else                     echo "ENDEREÇO";
	echo "</TD>";
	echo "<TD>";
	if($sistema_lingua=='ES')echo "NUMERO";
	else                     echo "NÚMERO";
	echo "</TD>";
	echo "<TD>";
	if($sistema_lingua=='ES')echo "COMPLEMIENTO";
	else                     echo "COMPLEMENTO";
	echo "</TD>";
	echo "<TD>";
	if($sistema_lingua=='ES')echo "BARRIO";
	else                     echo "BAIRRO";
	echo "</TD>";
	echo "</TR>";
	echo "<TR class='Conteudo2' align='center'>";
	echo "<TD>$consumidor_endereco</TD>";
	echo "<TD>$consumidor_numero </TD>";
	echo "<TD>$consumidor_complemento</TD>";
	echo "<TD>$consumidor_bairro</TD>";
	echo "</TR>";
	echo "</TABLE>";

	echo "<TABLE width='650' align='center' border='1' cellspacing='0' cellpadding='1' style='border-collapse: collapse; border-top:none;' bordercolor='#000000'>";
	echo "<TR class='Titulo2'>";
	echo "<TD>";
	if($sistema_lingua=='ES')echo "APARTADO POSTAL";
	else                     echo "CEP";
	echo "</TD>";
	echo "<TD>";
	if($sistema_lingua=='ES')echo "ID CLIENTE";
	else                     echo "CPF";
	echo "</TD>";
	echo "</TR>";
	echo "<TR class='Conteudo2' align='center'>";
	echo "<TD> $consumidor_cep </TD>";
	echo "<TD> $consumidor_cpf </TD>";
	echo "</TR>";
	echo "</TABLE>";

	echo "<TABLE width='650' align='center' border='1' cellspacing='0' cellpadding='1' style='border-collapse: collapse; border-top:none;' bordercolor='#000000'>";
	echo "<TR class='Titulo2'>";
	echo "<TD>";
	if($sistema_lingua=='ES')echo "REFERENCIA";
	else                     echo "DEFEITO APRESENTADO PELO CLIENTE";
	echo "</TD>";
	echo "</TR>";
	echo "<TR class='Conteudo2'>";
	echo "<TD>$defeito_reclamado_descricao <br> $defeito_cliente</TD>";
	echo "</TR>";
	echo "</TABLE>";


//--=== PEÇAS DA OS ============================================================--\\
	if($login_fabrica==20){
	echo "<tr>";
	echo "<td>";




	$sql = "SELECT  tbl_os.sua_os,
			tbl_produto.referencia AS ref_equipamento ,
			tbl_produto.descricao  AS nome_equipamento,
			tbl_peca.peca                             ,
			tbl_produto.produto    AS produto_id      ,
			tbl_peca.referencia    AS ref_peca        ,
			tbl_peca.descricao     AS nome_peca       ,
			tbl_os_item.preco                         ,
			tbl_os_item.qtde
		FROM    tbl_os_item
		JOIN    tbl_os_produto        ON tbl_os_item.os_produto                  = tbl_os_produto.os_produto
		JOIN    tbl_os                ON tbl_os_produto.os                       = tbl_os.os
		JOIN    tbl_produto           ON tbl_os.produto                          = tbl_produto.produto
		JOIN    tbl_peca              ON tbl_os_item.peca                        = tbl_peca.peca
		JOIN    tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
		WHERE   tbl_os.os = $os
		AND     tbl_os.fabrica       = $login_fabrica
		ORDER BY substr(tbl_os.sua_os,0,strpos(tbl_os.sua_os,'-')) ASC,
				lpad(substr(tbl_os.sua_os,strpos(tbl_os.sua_os,'-')+1,length(tbl_os.sua_os)),5,'0') ASC,
				tbl_os.sua_os;";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {

		echo "<br><table border='1' cellpadding='2' cellspacing='0' width='650' align='center' style='border-collapse: collapse' bordercolor='#000000'>\n";

		echo "<tr class='Titulo2'>\n";
		echo "<td colspan='3'><i>";
		if($sistema_lingua=='ES')echo "Repuesto de la OS";
		else                     echo "Peças da OS";
		echo "</i></td>\n";
		echo "</tr>";

		echo "<tr class='Titulo2'>\n";
		echo "<td>";
		if($sistema_lingua=='ES')echo "REFERENCIA";
		else                     echo "REFERENCIA";
		echo "</td>\n";
		echo "<td>";
		if($sistema_lingua=='ES')echo "DESCRIPCIÓN";
		else                     echo "DESCRIÇÂO";
		echo "</td>\n";
		echo "<td>";
		if($sistema_lingua=='ES')echo "CANT.";
		else                     echo "QTDE";
		echo "</td>\n";

		echo "</tr>\n";

		for ($x = 0; $x < pg_numrows($res); $x++) {

			$qtde         = 1;
			$peca         = trim(pg_result($res,$x,peca))                         ;
			$ref_peca     = trim(pg_result($res,$x,ref_peca))                     ;
			$nome_peca    = trim(pg_result($res,$x,nome_peca))                    ;
			$qtde         =trim(pg_result($res,$x,qtde))                          ;

			$sql_idioma = "SELECT * FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma) = '$sistema_lingua'";

			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) {
				$nome_peca  = trim(@pg_result($res_idioma,0,descricao));
			}

			echo "<tr class='Conteudo2'>\n";

			echo "<td>$ref_peca</td>";
			echo "<td>$nome_peca</td>\n";
			echo "<td>$qtde</td>\n";

			echo "</tr>\n";
		}
		echo "</table>\n";
	}

	echo "</td>";
	echo "</tr>";
	}

}





echo "</TABLE>\n";

?>

<BR>

<br>

<p>

<script>
	window.print();
</script>