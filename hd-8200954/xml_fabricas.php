<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

if (strlen($_GET['fabrica']) > 0) $fabrica = $_GET['fabrica'];

$data = date("d/m/Y");

$sql = "SELECT  trim(tbl_fabrica.fabrica)     AS fabrica,
				trim(tbl_fabrica.nome)        AS nome   ,
				trim(email_gerente)           AS email  ,
				inibe_revenda                           ,
				linha_pedido                            ,
				os_contrato                             ,
				pedido_escolhe_transportadora           ,
				contrato_manutencao                     ,
				os_item_subconjunto                     ,
				os_item_serie                           ,
				os_item_descricao                       ,
				pedir_sua_os                            ,
				pergunta_qtde_os_item                   ,
				pedido_escolhe_condicao                 ,
				vista_explodida_automatica              ,
				os_item_aparencia                       ,
				defeito_constatado_por_familia          ,
				multimarca                              ,
				acrescimo_tabela_base                   ,
				acrescimo_financeiro                    ,
				pedido_via_distribuidor                 ,
				os_defeito                              ,
				pedir_defeito_reclamado_descricao       ,
				pedir_causa_defeito_os_item
		FROM    tbl_fabrica ";
if (strlen($fabrica) > 0) $sql .= "WHERE tbl_fabrica.fabrica in ($fabrica) ";
$sql .= "ORDER BY tbl_fabrica.fabrica;";
$res = pg_exec($con,$sql);


// print o cabeçalho do xml
header("Content-type: application/xml");

// cabeçalho
echo "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>";
?>

<DATAPACKET Version="2.0">
	<METADATA>
		<FIELDS>
			<FIELD attrname="fabrica"                           fieldtype="integer"             />
			<FIELD attrname="nome"                              fieldtype="string"   WIDTH="50" />
			<FIELD attrname="email_gerente"                     fieldtype="string"   WIDTH="50" />
			<FIELD attrname="inibe_revenda"                     fieldtype="boolean"             />
			<FIELD attrname="linha_pedido"                      fieldtype="boolean"             />
			<FIELD attrname="os_contrato"                       fieldtype="boolean"             />
			<FIELD attrname="pedido_escolhe_transportadora"     fieldtype="boolean"             />
			<FIELD attrname="contrato_manutencao"               fieldtype="boolean"             />
			<FIELD attrname="os_item_subconjunto"               fieldtype="boolean"             />
			<FIELD attrname="os_item_serie"                     fieldtype="boolean"             />
			<FIELD attrname="os_item_descricao"                 fieldtype="boolean"             />
			<FIELD attrname="pedir_sua_os"                      fieldtype="boolean"             />
			<FIELD attrname="pergunta_qtde_os_item"             fieldtype="boolean"             />
			<FIELD attrname="pedido_escolhe_condicao"           fieldtype="boolean"             />
			<FIELD attrname="vista_explodida_automatica"        fieldtype="boolean"             />
			<FIELD attrname="os_item_aparencia"                 fieldtype="boolean"             />
			<FIELD attrname="defeito_constatado_por_familia"    fieldtype="boolean"             />
			<FIELD attrname="multimarca"                        fieldtype="boolean"             />
			<FIELD attrname="acrescimo_tabela_base"             fieldtype="boolean"             />
			<FIELD attrname="acrescimo_financeiro"              fieldtype="boolean"             />
			<FIELD attrname="pedido_via_distribuidor"           fieldtype="boolean"             />
			<FIELD attrname="os_defeito"                        fieldtype="boolean"             />
			<FIELD attrname="pedir_defeito_reclamado_descricao" fieldtype="boolean"             />
			<FIELD attrname="pedir_causa_defeito_os_item"       fieldtype="boolean"             />
		</FIELDS>
	</METADATA>
	<ROWDATA>
		<?
		for ($i=0; $i < pg_numrows($res); $i++) {
			$fabrica                           = pg_result($res,$i,fabrica);
			$nome                              = pg_result($res,$i,nome);
			$email_gerente                     = pg_result($res,$i,email);
			$inibe_revenda                     = pg_result($res,$i,inibe_revenda);
			$linha_pedido                      = pg_result($res,$i,linha_pedido);
			$os_contrato                       = pg_result($res,$i,os_contrato);
			$pedido_escolhe_transportadora     = pg_result($res,$i,pedido_escolhe_transportadora);
			$contrato_manutencao               = pg_result($res,$i,contrato_manutencao);
			$os_item_subconjunto               = pg_result($res,$i,os_item_subconjunto);
			$os_item_serie                     = pg_result($res,$i,os_item_serie);
			$os_item_descricao                 = pg_result($res,$i,os_item_descricao);
			$pedir_sua_os                      = pg_result($res,$i,pedir_sua_os);
			$pergunta_qtde_os_item             = pg_result($res,$i,pergunta_qtde_os_item);
			$pedido_escolhe_condicao           = pg_result($res,$i,pedido_escolhe_condicao);
			$vista_explodida_automatica        = pg_result($res,$i,vista_explodida_automatica);
			$os_item_aparencia                 = pg_result($res,$i,os_item_aparencia);
			$defeito_constatado_por_familia    = pg_result($res,$i,defeito_constatado_por_familia);
			$multimarca                        = pg_result($res,$i,multimarca);
			$acrescimo_tabela_base             = pg_result($res,$i,acrescimo_tabela_base);
			$acrescimo_financeiro              = pg_result($res,$i,acrescimo_financeiro);
			$pedido_via_distribuidor           = pg_result($res,$i,pedido_via_distribuidor);
			$os_defeito                        = pg_result($res,$i,os_defeito);
			$pedir_defeito_reclamado_descricao = pg_result($res,$i,pedir_defeito_reclamado_descricao);
			$pedir_causa_defeito_os_item       = pg_result($res,$i,pedir_causa_defeito_os_item);
			
			if (strlen($inibe_revenda) == 0)                     $inibe_revenda                     = "f";
			if (strlen($linha_pedido) == 0)                      $linha_pedido                      = "f";
			if (strlen($os_contrato) == 0)                       $os_contrato                       = "f";
			if (strlen($pedido_escolhe_transportadora) == 0)     $pedido_escolhe_transportadora     = "f";
			if (strlen($contrato_manutencao) == 0)               $contrato_manutencao               = "f";
			if (strlen($os_item_subconjunto) == 0)               $os_item_subconjunto               = "f";
			if (strlen($os_item_serie) == 0)                     $os_item_serie                     = "f";
			if (strlen($os_item_descricao) == 0)                 $os_item_descricao                 = "f";
			if (strlen($pedir_sua_os) == 0)                      $pedir_sua_os                      = "t";
			if (strlen($pergunta_qtde_os_item) == 0)             $pergunta_qtde_os_item             = "f";
			if (strlen($pedido_escolhe_condicao) == 0)           $pedido_escolhe_condicao           = "t";
			if (strlen($vista_explodida_automatica) == 0)        $vista_explodida_automatica        = "f";
			if (strlen($os_item_aparencia) == 0)                 $os_item_aparencia                 = "f";
			if (strlen($defeito_constatado_por_familia) == 0)    $defeito_constatado_por_familia    = "f";
			if (strlen($multimarca) == 0)                        $multimarca                        = "f";
			if (strlen($acrescimo_tabela_base) == 0)             $acrescimo_tabela_base             = "f";
			if (strlen($acrescimo_financeiro) == 0)              $acrescimo_financeiro              = "f";
			if (strlen($pedido_via_distribuidor) == 0)           $pedido_via_distribuidor           = "f";
			if (strlen($os_defeito) == 0)                        $os_defeito                        = "f";
			if (strlen($pedir_defeito_reclamado_descricao) == 0) $pedir_defeito_reclamado_descricao = "f";
			if (strlen($pedir_causa_defeito_os_item) == 0)       $pedir_causa_defeito_os_item       = "f";
		?>
			<ROW
			RowState="<?=$i+1;?>"
			fabrica="<?=$fabrica;?>"
			nome="<?=$nome;?>"
			email_gerente="<?=$email_gerente;?>"
			inibe_revenda="<?=$inibe_revenda;?>"
			linha_pedido="<?=$linha_pedido;?>"
			os_contrato="<?=$os_contrato;?>"
			pedido_escolhe_transportadora="<?=$pedido_escolhe_transportadora;?>"
			contrato_manutencao="<?=$contrato_manutencao;?>"
			os_item_subconjunto="<?=$os_item_subconjunto;?>"
			os_item_serie="<?=$os_item_serie;?>"
			os_item_descricao="<?=$os_item_descricao;?>"
			pedir_sua_os="<?=$pedir_sua_os;?>"
			pergunta_qtde_os_item="<?=$pergunta_qtde_os_item;?>"
			pedido_escolhe_condicao="<?=$pedido_escolhe_condicao;?>"
			vista_explodida_automatica="<?=$vista_explodida_automatica;?>"
			defeito_constatado_por_familia="<?=$defeito_constatado_por_familia;?>"
			multimarca="<?=$multimarca;?>"
			acrescimo_tabela_base="<?=$acrescimo_tabela_base;?>"
			acrescimo_financeiro="<?=$acrescimo_financeiro;?>"
			pedido_via_distribuidor="<?=$pedido_via_distribuidor;?>"
			os_defeito="<?=$os_defeito;?>"
			pedir_defeito_reclamado_descricao="<?=$pedir_defeito_reclamado_descricao;?>"
			pedir_causa_defeito_os_item="<?=$pedir_causa_defeito_os_item;?>"
			/>
		<? } ?>
	</ROWDATA>
</DATAPACKET>