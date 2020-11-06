<?php

//**************** MUESTRA LA EXTENSIÓN DE UN FICHERO ****************** 
//Hay que comprobar que se le pasa un nombre de fichero y no un directorio.
function getExtension($fileName) { 	
 	$ext = substr($fileName, strrpos($fileName, '.') + 1);
 	return strtoupper ($ext);
}

function deleteDir($dir) {
   if (substr($dir, strlen($dir)-1, 1) != '/') 
       $dir .= '/';

   if ($handle = opendir($dir))
   {
       while ($obj = readdir($handle))
       {
           if ($obj != '.' && $obj != '..')
           {
               if (is_dir($dir.$obj))
               {
                   if (!deleteDir($dir.$obj)) {
                   		echo "Problema al eliminar un directorio";
                       return false;
                       }
               }
               elseif (is_file($dir.$obj))
               {
                   if (!unlink($dir.$obj)) {
                   	echo "Problema al eliminar el fichero fichero: " . $dir.$obj ;
                       return false;
                       }
               }
           }
       }

       closedir($handle);

       if (!@rmdir($dir))
           return false;
       return true;
   }
   return false;
}



// *************** Funcion para subir fichero al servidor. Si es una imagen JPG la redimensiona ************************
function uploadFile($fileName,$dirpath,$max_width,$max_height) { // (Nombre del fichero, directorio donde subirlo, anchura (sólo jpg), altura (sólo jpg))
	
		$ext = getExtension($fileName['file']['name']);
		echo "extensión : " .$ext . "<br>";
	    if ($ext=="JPG" || $ext=="JPEG"){ // si se trata de fichero jpeg se les cambia el tamaño para que no ocupen demasiado
			$mensaje =  "<div class=\"infoMensaje\">El fichero \"" . $fileName['file']['name'] . "\" ha sido copiado correctamente.</div>";

			$size=GetImageSize($fileName['file']['tmp_name']);

			//Control del tamaño final de la imagen:
			
			$image_ratio = $size[0]/$size[1];
			if ($max_width =="" && $max_height == "") {
			 echo "tenemos ambas medidas";
	 			 $max_width = 400;
	 			 $max_height = 400;
			} else {
				if ($max_width != "" && $max_height == ""){
				 echo "Tenemos solo la altura";
					$max_height = $max_width / $image_ratio;
				}
				if ( $max_height != "" && $max_width == "") {
				 echo "Tenemos solo la anchura";
					$max_width = $max_height * $image_ratio;
				}			  
			}


			$width_ratio  = ($size[0] / $max_width);
			$height_ratio = ($size[1] / $max_height);
	
			if($width_ratio >= $height_ratio)
			{
			   $ratio = $width_ratio;
			}
			else
			{
			   $ratio = $height_ratio;
			}
	
			$new_width    = ($size[0] / $ratio);
			$new_height   = ($size[1] / $ratio);
	
			$src_img = ImageCreateFromJPEG($fileName['file']['tmp_name']);
			$thumb = ImageCreateTrueColor($new_width,$new_height);
			ImageCopyResampled($thumb, $src_img, 0,0,0,0,($new_width),($new_height),$size[0],$size[1]);
			ImageJPEG($thumb,$dirpath.'/'. $fileName['file']['name']);
			chmod($dirpath.'/'.$fileName['file']['name'],  0777);
			ImageDestroy($src_img);
			ImageDestroy($thumb);
			unlink($fileName['file']['tmp_name']);
	
		} else { //Cualquier tipo de fichero	   
			$mensaje = "<div class=\"infoMensaje\">El fichero \"" . $_FILES['file']['name'] . "\" ha sido copiado correctamente.</div>";
			// echo $dirpath.'/'.  $_FILES['file']['name'];
			// echo $_FILES['file']['tmp_name'];
			
		   if(move_uploaded_file($_FILES['file']['tmp_name'],$dirpath.'/'.  $_FILES['file']['name'])) {
		    // echo "Magazine Updated!";
		   }
		   else
		   {
		     //echo "There was a problem when uploding the new file, please contact  about this.";
		     //print_r($_FILES);
		   }

			chmod($dirpath.'/'.$_FILES['file']['name'],  0777);			
		}
		return 1;
	}


 
?>