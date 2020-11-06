<?php
session_start();
include "config.php";
header("Content-type: image/png");

class createimage{
	echo "funcao";
   //Matriz para criar o texto para imagem
   var $str="0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
   var $width = 160;//Largura da imagem
   var $height = 60;//Altura da imagem
   //Arquivos com Fontes TrueType
   var $fonts = array("cour.ttf","verdana.ttf","impact.ttf");
   //Diretório da Fontes				  
   var	$path ;
   //Cores no formato hexadecimal			  
   var $hexcolors = array("#FFFF00","#000000","#FF0000","#FF00FF","#808080","#008000",
                          "#00FF00","#000080","#800000",
			  "#008080","#800080","#0000FF","#C0C0C0","#808000","#00FFFF");

   var $image;

   //Gera uma semente para ser utilizada pela função srand
   function make_seed() {
      list($usec, $sec) = explode(' ', microtime());
      return (float) $sec + ((float) $usec * 100000);
   }

   //Converte hexadecimal para rgb 
   function hex2rgb($hex) {
      $hex = str_replace('#','',$hex);
      $rgb = array('r' => hexdec(substr($hex,0,2)),
                   'g' => hexdec(substr($hex,2,2)),
                   'b' => hexdec(substr($hex,4,2)));
       return $rgb;
   } 
   
   //Aloca uma cor para imagem
   function color($value){
       $rgb = $this->hex2rgb($value);
       return ImageColorAllocate($this->image, $rgb['r'], $rgb['g'], $rgb['b']);
   }
   
   //Aloca uma cor aleatória para imagem 
   function randcolor(){
      srand($this->make_seed());
      shuffle($this->hexcolors);
      return $this->color($this->hexcolors[0]);   
   }
   
   //Cria uma linha em  posição e cor aleatória 
   function randline(){
      srand($this->make_seed());
      shuffle($this->hexcolors);
      $i=rand(0, $this->width);
      $k=rand(0, $this->width);
      imagesetthickness ($this->image, 2);
      imageline($this->image,$i,0,$k,$this->height,$this->randcolor());   
   }
   
   //Cria um quadrado 10X10 em posição e cor aleatória
   function randsquare(){
      imagesetthickness ($this->image, 1);
      srand($this->make_seed());
      $x=rand(0, ($this->width-15));
      $y=rand(0, ($this->height-15));
      imageFilledRectangle( $this->image, $x, $y, $x+10, $y+10, $this->color('#EFEFEF'));
	  imagerectangle ( $this->image, $x, $y, $x+10, $y+10, $this->randcolor());
   }
   
   //Cria uma imagem com texto aleatório e retorno o texto
   function output(){
      $defstr="";
      $this->image = ImageCreate($this->width,$this->height);
      $background = $this->color('#EFEFEF');  
      imageFilledRectangle($this->image, 0,0,$this->width , $this->height, $background);
	  srand($this->make_seed());
	  for($i=0;$i < 4;$i++){
         $this->str=str_shuffle($this->str);
		 shuffle($this->hexcolors);
		 shuffle($this->fonts);
		 $char=$this->str[0];
		 $defstr.=$char;
         imagettftext($this->image, 35, 0,($i*40+5), rand(40,($this->height-10)), $this->randcolor(), $this->path.$this->fonts[0],$char);
	  }
	  for($k=0;$k < 3;$k++){
	     $this->randline(); 
		 $this->randsquare();
	  }
      ImagePng($this->image);
      ImageDestroy($this->image);
	  return $defstr;
   }
}
$img = new createimage;
$img->path=$path."fonts/";
$_SESSION["valor"] = $img->output();
?> 
