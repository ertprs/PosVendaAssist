<?
  Header("Content-type: image/gif");
  if(!isset($s)) $s=11;
  $size = imagettfbbox($s,0,"includes/fonts/TIMES.TTF",$text);
  $dx = abs($size[2]-$size[0]);
  $dy = abs($size[5]-$size[3]);
  $xpad=9;
  $ypad=9;
  $im = imagecreate($dx+$xpad,$dy+$ypad);
  $blue = ImageColorAllocate($im, 0x2c,0x6D,0xAF);
  $black = ImageColorAllocate($im, 0,0,0);
  $white = ImageColorAllocate($im, 255,255,255);
  ImageRectangle($im,0,0,$dx+$xpad-1,$dy+$ypad-1,$black);
  ImageRectangle($im,0,0,$dx+$xpad,$dy+$ypad,$white);
  ImageTTFText($im, $s, 0, (int)($xpad/2)+1, $dy+(int)($ypad/2), $black, "/fonts/TIMES.TTF", $text);
  ImageTTFText($im, $s, 0, (int)($xpad/2), $dy+(int)($ypad/2)-1, $white, "/fonts/TIMES.TTF", $text);
  ImageGif($im);
  ImageDestroy($im);
?> 
