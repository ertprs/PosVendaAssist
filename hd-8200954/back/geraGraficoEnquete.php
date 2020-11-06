<html>

<head>
  <title></title>
</head>

<body>

<?php
###########CRÉDITOS###########
# Script Original Criado Por #
#       Neander Araújo       #
#   neander@eumesmo.com.br   #
# http://www.eumesmo.com.br/ #
##############################
function rate_blue($id,$per){
echo"
<!-- rate -->
<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" title=\"$id $per%\" style=\"background-color: white\">
<tr>
<td style=\"width: 1px; height: 9px; background-color: #FFF0E6\"></td>";
$i = 1;

while ($i >= 1 and $i <= 10 and $i <= $per):
    echo"<td style=\"width: 1px; height: 9px; background-color: #CCFFFF\"></td>";
//      echo $i;
    $i++;
    endwhile;
while ($i > 10 and $i <=20 and $i<= $per):
    echo"<td style=\"width: 1px; height: 9px; background-color: #99CCFF\"></td>";
    $i++;
endwhile;
while ($i > 20 and $i <= 30 and $i<= $per):
    echo"<td style=\"width: 1px; height: 9px; background-color: #5AACFE\"></td>";
    $i++;
endwhile;
while ($i > 30 and $i <= 40 and $i<= $per):
    echo"<td style=\"width: 1px; height: 9px; background-color: #3A9CFE\"></td>";
    $i++;
endwhile;
while ($i > 40 and $i <= 50 and $i<= $per):
    echo"<td style=\"width: 1px; height: 9px; background-color: #0066FF\"></td>";
    $i++;
endwhile;
while ($i > 50 and $i <= 60 and $i<= $per):
    echo"<td style=\"width: 1px; height: 9px; background-color: #0033FF\"></td>";
    $i++;
endwhile;
while ($i > 60 and $i <= 70 and $i<= $per):
    echo"<td style=\"width: 1px; height: 9px; background-color: #0000FF\"></td>";
    $i++;
endwhile;
while ($i > 70 and $i <= 80 and $i<= $per):
    echo"<td style=\"width: 1px; height: 9px; background-color: #0000CC\"></td>";
    $i++;
endwhile;
while ($i > 80 and $i <= 90 and $i<= $per):
    echo"<td style=\"width: 1px; height: 9px; background-color: #000099\"></td>";
    $i++;
endwhile;
while ($i > 90 and $i <= 100 and $i<= $per):
    echo"<td style=\"width: 1px; height: 9px; background-color: #000066\"></td>";
    $i++;
endwhile;
echo"
</table>
<!-- rate end -->";
}
function rate_red($id,$per){
echo"
<!-- rate -->
<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\" title=\"$id $per%\" style=\"background-color: white\">
<tr>
<td style=\"width: 1px; height: 9px; background-color: #FFF0E6\"></td>";
$i = 1;

while ($i >= 1 and $i <= 10 and $i <= $per):
    echo"<td style=\"width: 1px; height: 9px; background-color: #FFE0CC\"></td>";
//      echo $i;
    $i++;
    endwhile;
while ($i > 10 and $i <=20 and $i<= $per):
    echo"<td style=\"width: 1px; height: 9px; background-color: #FEBBB1\"></td>";
    $i++;
endwhile;
while ($i > 20 and $i <= 30 and $i<= $per):
    echo"<td style=\"width: 1px; height: 9px; background-color: #FD8B7B\"></td>";
    $i++;
endwhile;
while ($i > 30 and $i <= 40 and $i<= $per):
    echo"<td style=\"width: 1px; height: 9px; background-color: #FC624B\"></td>";
    $i++;
endwhile;
while ($i > 40 and $i <= 50 and $i<= $per):
    echo"<td style=\"width: 1px; height: 9px; background-color: #FB462D\"></td>";
    $i++;
endwhile;
while ($i > 50 and $i <= 60 and $i<= $per):
    echo"<td style=\"width: 1px; height: 9px; background-color: #F72304\"></td>";
    $i++;
endwhile;
while ($i > 60 and $i <= 70 and $i<= $per):
    echo"<td style=\"width: 1px; height: 9px; background-color: #D01E04\"></td>";
    $i++;
endwhile;
while ($i > 70 and $i <= 80 and $i<= $per):
    echo"<td style=\"width: 1px; height: 9px; background-color: #921503\"></td>";
    $i++;
endwhile;
while ($i > 80 and $i <= 90 and $i<= $per):
    echo"<td style=\"width: 1px; height: 9px; background-color: #620E02\"></td>";
    $i++;
endwhile;
while ($i > 90 and $i <= 100 and $i<= $per):
    echo"<td style=\"width: 1px; height: 9px; background-color: #210501\"></td>";
    $i++;
endwhile;
echo"
</table>
<!-- rate end -->";
}
/*------- EXEMPLO --------
Obs:
1º- Esta função é recomendada para ser usada apartir de valores percentuais;
2º- Existem 2 funçoes, uma para cor degradê vermelha e outra para degradê azul.
3º- Para cada linha rate_red(xx,xx) ele desenha o gráfico, logo se você quer
mostrar esse gráfico para representar o percentual de vários ítens a linha
deverá ser escrita para cada item

O uso:
O uso desta função é simples, você tem de passar 2 valores a ela, um valor tipo string e o
principal tipo number, inteiro ou decimal, mas que obrigatoriamente deverá ser o resultado
percentual para o item em questão, esses valores vao aparecer seguidos do simbolo "%"
quando se passar o mouse em cima do gráfico.
Nos casos de uso enquetes você tem de achar um percentual para cada opção, isso se faz obtendo o
valor individual de cada alternativa, e o valor total da somatória das mesmas.
A fórmula de percentual é basicamente assim: percentual = (valor1 / somatotal * 100)

Exemplo prático:
Supondo que tenhamos a seguinte enquete:

1)Você acessa a internet de onde?
  opt1- Do trabalho:           10 voto(s)
  opt2- De casa:               5 voto(s)
  opt3- De um café:            0 voto(s)
  opt4- Da casa de amigos:     0 voto(s)

Após somar todos os votos para se obter o valor total de votos
devemos achar o percentual de cada um em relaçao a esse total,
abaixo tem um exemplo dessa formula:
number_format ---> para se obter um número formatado em 2 casas decimais, exe: 12,54%
$Per_optx ---> retorna o valor percentual de cada item
*/


//-- Variáveis para a função:
$v1=101;
$v2=350;
$v3=20;
$v4=69;
$vTotal=540;
$Per_opt1 = number_format($v1 / $vTotal * 100,2);
$Per_opt2 = number_format($v2 / $vTotal * 100,2);
$Per_opt3 = number_format($v3 / $vTotal * 100,2);
$Per_opt4 = number_format($v4 / $vTotal * 100,2);

//-- Outras variáveis sem importancia:
$titulo="Você acessa a internet de onde?";
$opt1="Do trabalho:";
$opt2="De casa:";
$opt3="De um café:";
$opt4="Da casa de amigos:";

echo "<font face=\"Verdana\"><h3>Resultado da Enquete:</h3><br>
<h4>$titulo</h4><br>
$opt1 $v1 Voto(s) - $Per_opt1 %<br>";
rate_red(opt1,$Per_opt1);
echo"$opt2 $v2 Voto(s) - $Per_opt2 %<br>";
rate_red(opt2,$Per_opt2);
echo"$opt3 $v3 Voto(s) - $Per_opt3 %<br>";
rate_red(opt3,$Per_opt3);
echo"$opt4 $v4 Voto(s) - $Per_opt4 %<br>";
rate_red(opt4,$Per_opt4);

?>
</body>

</html>