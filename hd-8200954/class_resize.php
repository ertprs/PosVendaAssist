<?
/******** class_resize.php ***********
Creator		: Bruno VIBERT
E-mail		: bvibert@mytracer.com
Date		: 01/20/2003
Version    : First and last
Descripton : Copy uploaded image keeping aspect ratio
*********************************/

class resize
{

	var $iOrig = array();		// uploaded image
	var $iNew = object;			// image created object
	
	// Contructor resize( ARRAY postimage [, INT mawWidth, INT maxHeight])
	// Resize the uploaded image and sets width and/or height to the maximum
	// value, keeping the aspect ratio
	// ie resise( var, 100, 50 ) an image that size is 200x50 will return an image of 100x25
	function resize( $postImage, $maxWidth = 10000, $maxHeight = 10000 )
	{
		global $_FILES;
		$this -> iOrig = $_FILES[ $postImage ];
		$this -> type = $this -> imageType( );
		
		
		$picInfos = getimagesize( $this -> iOrig[ 'tmp_name' ] );
		
		$width = $picInfos[0];
		$height = $picInfos[1];
//		echo "$height - $width<br>";
//		echo "$maxHeight - $maxWidth<br>";
		if( $width > $maxWidth & $height <= $maxHeight )
		{
			$ratio = $maxWidth / $width;
		}
		elseif( $height > $maxHeight & $width <= $maxWidth )
		{
			$ratio = $maxHeight / $height;
		}
		elseif( $width > $maxWidth & $height > $maxHeight )
		{
			$ratio1 = $maxWidth / $width;
			$ratio2 = $maxHeight / $height;
			$ratio = ($ratio1 < $ratio2)? $ratio1:$ratio2;
		}
		else
		{
			$ratio = 1;
		}

		$nWidth = floor($width*$ratio);
		$nHeight = floor($height*$ratio);
		
		if( $this -> type == 'jpg' ){
			 $origPic = imagecreatefromjpeg( $this -> iOrig[ 'tmp_name' ] );
		}elseif( $this -> type == 'png' ){
	 		$origPic = imagecreatefrompng( $this -> iOrig[ 'tmp_name' ] );
	 		imagealphablending($origPic,FALSE);
	 		imagesavealpha($origPic,TRUE);
		}
		
		$this -> iNew = ImageCreateTrueColor($nWidth,$nHeight);
		ImageCopyResampled($this -> iNew, $origPic, 0, 0, 0, 0, $nWidth, $nHeight, $width, $height);	
		
	}
	
	// function imageType(); return JPG/PNG (so cool !)
	function imageType( )
	{
		if( preg_match( "/jpeg/", $this -> iOrig[ 'type' ]) ) // JPG
			 return "jpg";
		elseif( preg_match( "/png/", $this -> iOrig[ 'type' ] ) ) // PNG
	 		return "png";
	}

	// function saveTo( STRING name [, STRING path ] )
	// save the new image in the specified path, with the specified name
	function saveTo( $name = '', $path = "./" )
	{
		if( empty( $name ) )
			echo "name!";
		elseif( !is_dir( $path ) )
			echo "$path is not a directory!";
		else
		{
			
			if( $this -> type == 'jpg' )
				imagejpeg( $this -> iNew, $path.$name );
			elseif( $this -> type == 'png' )
//                 header('Content-type: image/png');
				imagepng( $this -> iNew, $path.$name );
		
		}
	}
	

}

?>