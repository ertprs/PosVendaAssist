<?php 

/*
Esse grafico faz uma pizza com n variáveis.
1) Os valores deverão ser passados na variável parcela, separados por ; e no máximo 14 valores
   Ex.: parcela=1;2;3;4;5;6;7;8;9
   Obs.: Valores negativos não serão aceitos
2) Caso a variável titulo seja passada, será alinhada e aparecerá acima do gráfico
   Ex.: titulo=TESTE
   Obs.: Máximo de 30 caracteres
3) As descrições das parcelas poderão ser passadas separadas por ; na variável dp
   Ex.: dp=parcela 1;parcela 2; parcela 3
   Obs.: Máximo de 12 caracteres para cada parcela
4) A cor do fundo poderá ser personalizada sendo passada pela variável fundo no formato RGB, separado por ;
   Ex.: fundo=255;255;255

 Versão 2.0.1 - Correção de um Bug com 14 parcelas.
*/
if (!isset($_REQUEST["parcela"])) exit;
if (isset($_REQUEST["dp"])) $dp = explode(";",$_REQUEST["dp"]);

$parcela = explode(";",$_REQUEST["parcela"]);
$n_parc  = count($parcela);
if ($n_parc > 14) $n_parc = 14;
$total = 0;
for ($z=0; $z<$n_parc; $z++) {
 if ($parte[$z]<0) exit;
 $w=$z+1;
 $parte[$w] = $parcela[$z];
 $total += $parcela[$z];
}

$d = Array(); 
$diametro = 200; 
$radius = $diametro/2; 

//Converte para graus 
for ($y=1; $y<=$n_parc; $y++) $d[$y] = ($parte[$y]/$total) * 360;

$im = ImageCreate(450, 300); 
//Cores 
$preto     = ImageColorAllocate($im, 0, 0, 0); 
$branco    = ImageColorAllocate($im, 255, 255, 255); 
$gelo      = ImageColorAllocate($im, 210, 210, 210); 
$azul      = ImageColorAllocate($im, 0, 0, 255); 
$cinza     = ImageColorAllocate($im, 102, 102, 102); 
$verde     = ImageColorAllocate($im, 0, 255, 0); 
$rosa      = ImageColorAllocate($im, 255, 128, 128); 
$amarelo   = ImageColorAllocate($im, 255, 255, 128); 
$vermelho  = ImageColorAllocate($im, 255, 0, 0); 
$lilas     = ImageColorAllocate($im, 128, 128, 192); 
$marrom    = ImageColorAllocate($im, 128, 64, 64); 
$laranja   = ImageColorAllocate($im, 255, 128, 64); 
$vinho     = ImageColorAllocate($im, 64, 0, 64); 
$amarelo2  = ImageColorAllocate($im, 255, 255, 0); 
$vermelho2 = ImageColorAllocate($im, 126, 14, 1); 

$e=0;
if (isset($_REQUEST["fundo"])) {
 $r = explode(";",$_REQUEST["fundo"]);
 for ($z=0; $z<3; $z++) {
  if (is_numeric($r[$z])) {
   if ($r[$z] >= 0) {
    if ($r[$z] <= 255) $e++;
   }
  }
 }
}
if ($e == 3)    $fundo = ImageColorAllocate($im, $r[0], $r[1], $r[2]);
else        $fundo = $branco;
$sombra = $cinza;
$cor[] = $branco;
$cor[] = $gelo;
$cor[] = $azul;
$cor[] = $cinza;
$cor[] = $verde;
$cor[] = $rosa;
$cor[] = $amarelo;
$cor[] = $vermelho;
$cor[] = $lilas;
$cor[] = $marrom;
$cor[] = $laranja;
$cor[] = $vinho;
$cor[] = $amarelo2;
$cor[] = $vermelho2;
$cor[] = $preto;

// preenche o fundo da imagem
ImageFill($im, 0, 0, $fundo); 

// desenha a linha base 
ImageArc($im, 153, 153, $diametro, $diametro, 315, 135, $preto); 
//ImageLine($im, 150, 150, 225, 150, $preto); 

$u_angulo = 0;
for ($z=1; $z<=$n_parc; $z++) { 
 // calcula o arco 
 ImageArc($im, 150, 150, $diametro, $diametro, $u_angulo,($u_angulo+$d[$z]), $preto); 
 $u_angulo = $u_angulo + $d[$z]; 
 $end_x = round(150 + ($radius * cos($u_angulo*pi()/180))); 
 $end_y = round(150 + ($radius * sin($u_angulo*pi()/180))); 
 ImageLine($im, 150, 150, $end_x, $end_y, $preto); 
} 

$a_angulo = 0; 

for ($z=1; $z<=$n_parc; $z++) { 
 $ponteiro = $a_angulo + $d[$z]; 
 $e_angulo = ($a_angulo + $ponteiro) / 2; 
 $a_angulo = $ponteiro; 
 $end_x = round(150 + ($radius * cos($e_angulo*pi()/180))); 
 $end_y = round(150 + ($radius * sin($e_angulo*pi()/180))); 
 $mid_x = round((150+($end_x))/2); 
 $mid_y = round((150+($end_y))/2); 
 ImageFillToBorder($im,$mid_x,$mid_y,$preto,$cor[$z]);
} 

//Legenda. 
$r_x=300; 
$r_y=0; 
$e = 280/($n_parc+1);

for ($z=1; $z<=$n_parc; $z++) {
 $w_x = $r_x;
 $w_y = $r_y + ($z * $e);
 $parte[$z] = round($parte[$z]/$total * 100,1); 
 imagefilledrectangle($im, $w_x,$w_y,$w_x+15,$w_y+15,$sombra); 
 imagerectangle($im,$w_x-3,$w_y-3,$w_x+12,$w_y+12,$preto); 
 imagefilltoborder($im,$w_x-1,$w_y+11,$preto,$cor[$z]); 
 $w = $z - 1;
 if (isset($dp[$w])) $w = "% " . substr($dp[$w],0,12);

 else $w = "%";
 imagestring($im,3,$w_x+20,$w_y,$parte[$z].$w,$preto); 
}

//sombra no circulo 
ImageFillToBorder($im, 150 + 72, 150 + 72, $preto, $sombra); 
ImageArc($im, 153, 153, $diametro, $diametro,315,135,$sombra); 
// Inclui Título
if (isset($_REQUEST["titulo"])) {
 $x = strlen($_REQUEST["titulo"]);
 if ($x > 30) $w = substr($_REQUEST["titulo"],0,30);
 else {
  $w = "";
  for ($z=1; $z<((30-$x)/2); $z++) $w.=" ";
  $w.=$_REQUEST["titulo"];
 }
 ImageString($im, 5, 20, 10, $w, $preto); 
}




// Efetua Saída da imagem
Header("Content-Type: image/png"); 

ImagePNG($im); 

?> 
