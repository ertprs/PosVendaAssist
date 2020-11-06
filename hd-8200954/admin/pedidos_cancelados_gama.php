<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
//include 'autentica_admin.php';

if (!$con) {
echo "Sem conexão com o banco de dados!";
exit;
}
?>
<html>
<head>
<style type='text/css'>
<!--
body {
    font: normal normal 12px Verdana;
}
div {
    width: 60%;
    margin:1em 20%;
    border: 1px solid grey;
    border-radius: 6px;
    -moz-border-radius: 6px;
    -webkit-border-radius: 6px;
    padding: 5px;
}
div p:first:first-line {
    font-weight: bold
}
div a {
    text-decoration: none;
    font-weight: bold;
    color: #10539a;
}
div a:hover {
    text-decoration: underline;
    color: #68789c;
}
//-->
</style>
</head>
<body>
<h2>Comunicados para os postos com pedidos antigos cancelados</h2>
<?
/*
    Column    |           Type           | Modifiers
--------------+--------------------------+-----------
 fabrica      | integer                  |
 distribuidor | integer                  |
 posto        | integer                  |
 pedido       | integer                  |
 data         | timestamp with time zone |
 pedido_item  | integer                  |
 peca         | integer                  |
 qtde         | double precision         |
 preco        | double precision         |
*/

/*
         Column          |            Type             |                          Modifiers
-------------------------+-----------------------------+--------------------------------------------------------------
 comunicado              | integer                     | not null default nextval(('seq_comunicado'::text)::regclass)
 mensagem                | text                        |
 data                    | timestamp without time zone | default ('now'::text)::timestamp(6) with time zone
 tipo                    | character varying(50)       |
 fabrica                 | integer                     |
 produto                 | integer                     |
 extensao                | character(3)                |
 remetente_nome          | character varying(50)       |
 remetente_email         | character varying(50)       |
 tipo_posto              | integer                     |
 linha                   | integer                     |
 estado                  | character(2)                |
 envio                   | timestamp without time zone |
 pedido_em_garantia      | boolean                     |
 pedido_faturado         | boolean                     |
 obrigatorio_os_produto  | boolean                     |
 destinatario_especifico | text                        |
 destinatario            | integer                     |
 suframa                 | boolean                     |
 obrigatorio_site        | boolean                     | default false
 descricao               | character varying(50)       |
 familia                 | integer                     |
 posto                   | integer                     |
 ativo                   | boolean                     |
 pais                    | character(2)                |
 digita_os               | boolean                     | not null default true
 reembolso_peca_estoque  | boolean                     | not null default false
 peca                    | integer                     |
 video                   | text                        |
 
 Exemplo:
 comunicado              | 192661
mensagem                | O pedido das peças referente a OS 001122002303 foi <b>cancelado</b> pela fábrica. <br><br>Justificativa: teste.
data                    | 2009-07-28 13:13:59.612424
tipo                    | Pedido de Peças
fabrica                 | 3
produto                 |
extensao                |
remetente_nome          |
remetente_email         |
tipo_posto              |
linha                   |
estado                  |
envio                   |
pedido_em_garantia      |
pedido_faturado         |
obrigatorio_os_produto  | f
destinatario_especifico |
destinatario            |
suframa                 |
obrigatorio_site        | t
descricao               | Pedido de Peças CANCELADO
familia                 |
posto                   | 6359
ativo                   | t
pais                    |
digita_os               | t
reembolso_peca_estoque  | f
peca                    |
video                   |
*/
$sql = "SELECT DISTINCT posto FROM tbl_pedido_faturado_cancelado WHERE fabrica=51 ORDER BY posto";
$res = pg_query($con,$sql);
$num = pg_num_rows($res);

for ($i; $i < $num; $i++) {
	$postos[] = pg_fetch_result($res, $i, posto);
}

foreach ($postos as $posto) {
    $num_pedidos_posto  = 0;
    $pedidos_do_posto   = "";
    $sql    = "SELECT DISTINCT tbl_pedido_faturado_cancelado.posto, tbl_posto_fabrica.codigo_posto,
                      pedido, TO_CHAR(data::date,'DD/MM/YYYY') AS data
                    FROM tbl_pedido_faturado_cancelado
                    JOIN tbl_posto_fabrica USING (posto,fabrica)
                WHERE tbl_pedido_faturado_cancelado.fabrica = 51
                  AND tbl_pedido_faturado_cancelado.posto = $posto
                ORDER BY posto, pedido";
    $res    = pg_query($con, $sql);
    $num    = pg_num_rows($res);
// echo $num."<br>\n";
    $i=0;
    while ($i < $num) {
        $pedido_posto = pg_fetch_row($res,$i++);
        if ($pedido_posto[0]==$posto) {
            list ($ignorar, $codigo_posto, $pedido, $data) = $pedido_posto;
            $pedidos_do_posto .= "<li>".
                                 "<a href=\'/assist/pedido_finalizado.php?pedido=$pedido\' target=\'_blank\'>".
                                 "$pedido</a>".
                                 ", do $data</li>";
        }

        if($num) {
            if ($num==1) {
                $mensagem  = "<p>Prezado Assistente T&eacute;cnico,<br>";
                $mensagem .= "Informamos que as pe&ccedil;as pendentes do seu pedido ";
                $mensagem .= substr($pedidos_do_posto,4,-6).", foram canceladas.";
                $mensagem .= "Caso ainda necessitem das pe&ccedil;as favor inserir um novo pedido.</p>\n";
            } else {
                $mensagem  = "<p>Prezado Assistente T&eacute;cnico,<br>";
                $mensagem .= "Informamos que as pe&ccedil;as pendentes dos pedidos abaixo foram canceladas. ";
                $mensagem .= "Caso ainda necessitem das pe&ccedil;as favor inserir um novo pedido.</p>\n";
                $mensagem .= "<p>Rela&ccedil;&atilde;o de pedidos:</p>\n";
                $mensagem .= "<ul style=\"font: normal normal 12px Courier New, Courier;\">\n$pedidos_do_posto\n</ul>\n";
            }
            $mensagem .= "<p><i><b>Obs.:</b> clicar no n&uacute;mero do pedido para consultar quais pe&ccedil;as foram canceladas.</i></p>";
            unset($ignorar, $codigo_posto, $pedido, $data);
        }
    }
//  Insere os comunicados para os postos
    echo "<DIV>".$mensagem."</DIV>";
    echo "<p>Inserindo comunicado para o posto $posto...";    
    $sql = "<b>INSERT INTO</b> <i>tbl_comunicado</i> (mensagem, tipo, descricao, posto, fabrica, obrigatorio_site, ativo) <b>VALUES</b> ('<pre>".
            htmlentities($mensagem).
            "</pre>', 'Pedido de peças', 'Pedidos Cancelados', $posto, 51, true, true)";
    echo $sql.";<br><p>&nbsp</p>";
//     $sql = "INSERT INTO tbl_comunicado (mensagem, tipo, descricao, posto, fabrica, obrigatorio_site, ativo) VALUES (".
//             "'$mensagem', 'Pedido de peças', 'Pedidos Cancelados', $posto, 51, true, true)";
//     $res = pg_query($con,$sql);
//     if (!$res) echo "</p><p style='font-size: 15px;color:white;background-color:red;'>Erro ao gravar o comunicado para o posto $posto!!";
//     if ($res)  echo "SQL executado.</p>";
}
?>
</body>
</html>