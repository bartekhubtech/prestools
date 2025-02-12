<?php 
/* this file is called as an ajax script */
if(!@include 'approve.php') die( "approve.php was not found!");
if(!isset($_GET["action"])) colordie("Nothing to do"); else $action = $_GET["action"];
if(!isset($_GET["id_image"])) colordie("No image specified"); else $id_image = intval($_GET["id_image"]);
if($action == "generate")
{ if(!isset($_GET["imageformat"])) colordie("No format specified"); else $imageformat = preg_replace("/[^0-9\-]/","",$_GET["imageformat"]);
}
$ps_image_quality = get_configuration_value('PS_IMAGE_QUALITY'); 
if( !class_exists("Imagick") )
	  die("Imagick is not installed!");
$filter_array = array(
imagick::FILTER_BESSEL => "BESSEL",
imagick::FILTER_BLACKMAN => "BLACKMAN",
imagick::FILTER_BOX => "BOX",
imagick::FILTER_CATROM => "CATROM",
imagick::FILTER_CUBIC => "CUBIC",
imagick::FILTER_GAUSSIAN => "GAUSSIAN",
imagick::FILTER_HAMMING => "HAMMING",
imagick::FILTER_HANNING => "HANNING",
imagick::FILTER_HERMITE => "HERMITE",
imagick::FILTER_LANCZOS => "LANCZOS",
imagick::FILTER_MITCHELL => "MITCHELL",
imagick::FILTER_POINT => "POINT",
imagick::FILTER_QUADRATIC => "QUADRATIC",
imagick::FILTER_SINC => "SINC",
imagick::FILTER_TRIANGLE => "TRIANGLE");

if($action == "generate")
{ /* get the image format */
  $imageformats = str_replace("-",",",$imageformat);
  $query = 'SELECT name,width,height,id_image_type FROM `'._DB_PREFIX_.'image_type`';
  $query .= ' WHERE products=1 AND id_image_type IN ('.$imageformats.')';
  if(!($res = dbquery($query))) echo "error finding image format!";
  $difx = $dify = 999;
  while($row = mysqli_fetch_array($res))
  { if(abs(300 - intval($row["width"])) < $difx)
    { $difx = abs(300 - intval($row["width"]));
      $targetwidth = $row["width"];
      $targetheight = $row["height"];
      $image_type = $row["name"];
      $id_image_type = $row["id_image_type"];
	}
  }
  $targetratio = $targetwidth / $targetheight;
  
  $dir = $triplepath.'img/p'.getpath($id_image).'/';
  if((!file_exists($dir)) || (!is_dir($dir)))
  { pecho('Image '.$id_image.' not Found. ');
    return;
  }
  
  /* find source image */
  $srcfile = "";
  $files = scandir($dir);
  foreach ($files AS $file)
  { $lfile = strtolower($file);
    if(($lfile==$id_image.".jpg") || ($lfile==$id_image.".png") || ($lfile==$id_image.".gif") || ($lfile==$id_image.".jpeg") || ($lfile==$id_image.".x-png") || ($lfile==$id_image.".pjpeg"))
    { $srcfile = $file;
      break;
    }
  }
  if($srcfile == "")
  { pecho('Image '.$id_image.' not Found. ');
	return;  
  }
  
  $pos = strrpos($srcfile,".");
  $imgroot = substr($srcfile,0,$pos);
  $imgext = substr($srcfile,$pos+1);
  list($width, $height, $imgtype, $attr) = @getimagesize( $dir.$srcfile );
  echo "original width=".$width.", original height=".$height." targetwidth=".$targetwidth.", targetheight=".$targetheight."<br>";
  $sourceratio = $width / $height ;
  
  $blub = '<ul class="imexamples">';
  
  /* First create the GD picture for comparison */
  
  /* image type constants are defined here: http://php.net/manual/en/function.exif-imagetype.php */
  if($imgtype == IMAGETYPE_GIF)
	$src = imagecreatefromgif($dir.'/'.$srcfile);
  else if($imgtype == IMAGETYPE_PNG)
	$src = imagecreatefrompng($dir.'/'.$srcfile);
  else if($imgtype == IMAGETYPE_JPEG)
	$src = imagecreatefromjpeg($dir.'/'.$srcfile);
  else if($imgtype == IMAGETYPE_WEBP)
	$src = imagecreatefromwebp($dir.'/'.$srcfile);
  else if($imgtype == IMG_WBMP)
	$src = imagecreatefromwbmp($dir.'/'.$srcfile);	  
  else if($imgtype == IMAGETYPE_BMP)
	$src = imagecreatefrombmp($dir.'/'.$srcfile);	/* not a GD library function, but defined in functions1.php */
  else /* if we get an unknown format we try it as a jpg */
  { $src = imagecreatefromjpeg($dir.'/'.$srcfile);
	pecho ('<b>Unknown image type '.$imgtype.'</b><br>');
  }
 
  if(!$src)
  { pecho ('<b>Image creation failed for file '.$srcfile.'</b><br>');
    return;
  }

  /* First we create the image for PHP's GD libary */
  if($sourceratio != $targetratio)
  { if($height*$targetratio > $width)
	{  $newwidth = $height*$targetratio;
	   $newheight = $height;
	}
	else
	{ $newheight = $width/$targetratio;
	  $newwidth = $width;
	} 

	$img = imagecreatetruecolor ( $newwidth , $newheight );  /* create black image */
	if(($imgtype == IMAGETYPE_PNG) && ($ps_image_quality != "jpg"))
	{	imagealphablending($img, false);
		imagesavealpha($img, true);
		$transparent = imagecolorallocatealpha($img, 255, 255, 255, 127);
		imagefilledrectangle($img, 0, 0, $newwidth , $newheight, $transparent);
	} 
	else 
	{	$white = imagecolorallocate($img, 255, 255, 255);
		imagefill($img, 0, 0, $white);
	}

	/* in the first step we copy the src on the possibly with whitespace enlarged target */
	imagecopyresampled($img, $src, ($newwidth-$width)/2, ($newheight-$height)/2, 0, 0, $width, $height, $width, $height);
  }
  else
  { $img = $src;
	$newwidth = $width;
	$newheight = $height;
  }

  $img2 = imagecreatetruecolor ( $targetwidth , $targetheight );  /* create black image */
  if(($imgtype == IMAGETYPE_PNG) && ($ps_image_quality != "jpg"))
  {	imagealphablending($img2, false);
	imagesavealpha($img2, true);
	$transparent = imagecolorallocatealpha($img2, 255, 255, 255, 127);
	imagefilledrectangle($img2, 0, 0, $targetwidth , $targetheight, $transparent);
  } 
  else 
  {	$white = imagecolorallocate($img2, 255, 255, 255);
	imagefill($img2, 0, 0, $white);
  }
	
  /* in the second step we resize the image */
  /* we do not enlarge images. If it is too small we put a white border around it */
  if($targetheight < $newheight)
    imagecopyresampled($img2, $img, 0, 0, 0, 0, $targetwidth, $targetheight, $newwidth, $newheight);   
  else
	imagecopyresampled($img2, $img, ($targetwidth-$newwidth)/2, ($targetheight-$newheight)/2, 0, 0, $newwidth, $newheight, $newwidth, $newheight);

  imageinterlace($img2, 1); /* make the image progressive */
  imagejpeg($img2, $dir.'/'.$imgroot."--GD-".$image_type.'.jpg', 94); /* image, filename, quality */
  $blub .= '<li><div><img src="'.$dir.$id_image.'--GD-'.$image_type.'.jpg"><br>GD</div></li>';
  
  /* Now create the imagick pictures */
  $src = new Imagick(realpath($dir.$srcfile));
  $imgbase2 = realpath($dir.$srcfile);
  $pos = strrpos($imgbase2,".");
  $imgbase = substr($imgbase2,0,$pos);
  
  if($sourceratio != $targetratio) /* add whitespace when needed */
  { if($height*$targetratio > $width)
    { $newwidth = $height*$targetratio;
	  $newheight = $height;
	  $offsetx = ($newwidth - $width)/2;
	  $offsety = 0;
    }
    else
	{ $newheight = $width/$targetratio;
	  $newwidth = $width;
	  $offsetx = 0;
	  $offsety = ($newheight - $height)/2;	  
	} 
	$src->extentImage($newwidth, $newheight, -$offsetx, -$offsety);
  }
  
  $mold = clone $src;

  $blub .= '<li><div><img src="'.$dir.$id_image.'-'.$image_type.'.jpg"><br>active</div></li>';
  foreach($filter_array AS $key => $filter)
  { $img = clone $mold;
	$img->setImageCompression(Imagick::COMPRESSION_JPEG);
	$img->setInterlaceScheme(Imagick::INTERLACE_PLANE); /* alternatives: INTERLACE_NONE and INTERLACE_LINE */
	$img->setImageCompressionQuality(99);
//	$img->gaussianBlurImage(0.05,0.05);
	$img->stripImage();		/* Strips an image of all profiles and comments. Stripping Exif information may effect the rotation */
	// if you're doing a lot of picture resizing, it might be beneficial to use scaleImage instead of resizeImage, as it seems to be much much more efficient.
	// for resizing see http://www.imagemagick.org/Usage/resize/#thumbnail
//	$img->thumbnailImage($targetwidth, $targetheight, Imagick::FILTER_LANCZOS, 1); /* for times of filters see the comments of http://php.net/manual/en/imagick.resizeimage.php */
	$img->resizeImage($targetwidth,$targetheight,$key, 1);
	//Output the final Image using Imagick
	$img->writeImage($imgbase."--".$filter."-".$image_type.'.jpg');
	$blub .= '<li><div><img src="'.$dir.$id_image.'--'.$filter."-".$image_type.'.jpg"><br>'.$filter.'</div></li>';
	$img->destroy();
  }
  $blub .= "</ul>";
  echo $blub;
}	
else if($action == "clear")
{ $dir = $triplepath.'img/p'.getpath($id_image).'/';
  if((!file_exists($dir)) || (!is_dir($dir)))
  { pecho('Image '.$id_image.' not Found. ');
	return;  
  }
  
  /* delete old images */
  $files = scandir($dir);
  $files_to_erase = array();
  $len = 2+strlen($id_image);
  foreach ($files AS $file)
  { $lfile = strtolower($file);
    if(($file=="..") || ($file==".") || ($lfile=="index.php"))
	  continue;
    if(is_dir($dir.$file))
  	  continue;
    if(substr($lfile,0,$len) == $id_image."--")
      $files_to_erase[] = $file;
  }

  foreach($files_to_erase AS $file)
  { if(!unlink($dir.'/'.$file))
	  pecho (" error deleting ".$file);
    else 
	  pecho (" erasing ".$file);
  }
}
else if($action == "showsizes")
{ $dir = $triplepath.'img/p'.getpath($id_image).'/';
  $query = 'SELECT name,width,height,id_image_type FROM `'._DB_PREFIX_.'image_type`';
  $query .= ' WHERE products=1 ORDER BY height';
  if(!($res = dbquery($query))) echo "error finding image format!";
  $blub = '<ul class="imexamples">';
  while($row = mysqli_fetch_array($res))
  { $blub .= '<li><div><img border=1 src="'.$dir.$id_image.'-'.$row['name'].'.jpg"><br>'.$row['name'].'(';
    $blub .= $row['width'].'x'.$row['height'].')</div></li>';
  }
  $blub .= '</ul>';
  echo $blub;
}

function pecho($txt)
{ echo $txt;
  echo '<script>parent.dynamo2("'.$txt.'");</script>';
}
