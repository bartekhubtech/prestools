<?php
if(!@include 'approve.php') die( "approve.php was not found!");

/* this file works together with product-edit to upload new product images */
define('MULTI_FILE_UPLOAD_TEMP_DIR', realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "temp");
if(!isset($default_image_quality)) $default_image_quality = '94';
if(!isset($prestools_settings["skip_initial_jpg_process"])) $prestools_settings["skip_initial_jpg_process"] = 1;

$allowed_file_types = array('.png', '.jpg', '.jpeg', '.gif');
if ($_SERVER["REQUEST_METHOD"] === "GET") { // just render page
    $homepage = file_get_contents("./public/index.html");
    echo $homepage;
} else if ($_SERVER["REQUEST_METHOD"] === "POST") { // handle file upload
    if (!file_exists(MULTI_FILE_UPLOAD_TEMP_DIR)) {
        if (!mkdir(MULTI_FILE_UPLOAD_TEMP_DIR, 0775, true)) {
            http_response_code(400);
            die("Can not create temp directory");
        }
    }

    $filename = $_FILES["userfile"]["name"];
    $file_basename = substr($filename, 0, strripos($filename, '.')); // get file extention
    $file_ext = strtolower(substr($filename, strripos($filename, '.'))); // get file name
    $filesize = $_FILES["userfile"]["size"];
	$tmpname = $_FILES["userfile"]["tmp_name"];

    if (!in_array($file_ext, $allowed_file_types)) {
        http_response_code(400);
        die("Only these file types are allowed for upload: " . implode(', ', $allowed_file_types));
    } elseif (empty($file_basename)) {
        // file selection error
        http_response_code(400);
        die("Please select a file to upload.");
    } elseif ($filesize > $prestools_settings["maxprodimgsize"]) {
        // file size error
        http_response_code(413);
        die("The file you are trying to upload is too large.");
    }
	
	if ( ! function_exists( 'exif_imagetype' ) ) 
	{ function exif_imagetype ( $filename ) 
	  { if ( ( list($width, $height, $type, $attr) = getimagesize( $filename ) ) !== false ) 
            return $type;
        else
			return false;
	  }
    }

	$imgtype = exif_imagetype($tmpname);
	$ps_image_quality = get_configuration_value('PS_IMAGE_QUALITY'); 
	if($imgtype == IMAGETYPE_PNG)
	{ if($ps_image_quality == "jpg")
		update_img($tmpname, "png", "jpg");
	}
	else if($imgtype == IMAGETYPE_JPEG)
	{ if($ps_image_quality == "png_all") 
		update_img($tmpname, "jpg", "png");
	  else if(!$prestools_settings["skip_initial_jpg_process"])
		update_img($tmpname, "jpg", "jpg");
	}
	else if($imgtype == IMAGETYPE_GIF)
	{ if($ps_image_quality == "png_all") 
		update_img($tmpname, "gif", "png");
	  else
		update_img($tmpname, "gif", "jpg");	
	}
	else if($imgtype == IMAGETYPE_BMP)
	{ if($ps_image_quality == "png_all") 
		update_img($tmpname, "bmp", "png");
	  else
		update_img($tmpname, "bmp", "jpg");	
	}
	else if($imgtype == IMAGETYPE_WBMP)
	{ if($ps_image_quality == "png_all") 
		update_img($tmpname, "wbmp", "png");
	  else
		update_img($tmpname, "wbmp", "jpg");	
	}
	
	$random_string = md5(rand());
	$newfilename = md5($file_basename) . "_" . $random_string .".jpg";
	move_uploaded_file($tmpname, MULTI_FILE_UPLOAD_TEMP_DIR . DIRECTORY_SEPARATOR . $newfilename);
	echo $newfilename;
	

} else { // just fallback
    echo "Unsupported method";
}

function update_img($srcname, $importtype, $exporttype)
{ if( class_exists("Imagick")) 
  { $imformats = Imagick::queryformats(); /* if ImageMagick is not correctly installed this returns zero formats */
  }
  if((class_exists("Imagick")) && $imformats && (sizeof($imformats) > 0))
	update_img_imagick($srcname, $importtype, $exporttype);
  else
	update_img_gd($srcname, $importtype, $exporttype);
}

function update_img_imagick($srcname, $importtype, $exporttype)
{ global $default_image_quality;
  $img = new Imagick(realpath($srcname));
  if(!$img) die('Image creation failed for file '.$srcname);
  
  if (($importtype == "jpg") && function_exists('exif_read_data') && function_exists('mb_strtolower'))
  {	$exif = @exif_read_data($srcname);
	if ($exif && isset($exif['Orientation']))
	{	switch ($exif['Orientation'])  
		{	case 3: $rotate = 180; break; 
			case 6: $rotate = 90; break;  /* note that gd rotate values are different from Imagick values */
			case 8:	$rotate = 270;  break;
			default: $rotate = 0;
		}
		if ($rotate) 
		{ $img->rotateImage(new ImagickPixel(), $rotate);
		}
	}			
  }
  
  $compressiontype = $img->getImageCompression();
  if($exporttype == "png")
  { $srcname = "png:".$srcname;
  }
  else
  { $img->setImageCompression(Imagick::COMPRESSION_JPEG);
	$img->setInterlaceScheme(Imagick::INTERLACE_PLANE); /* alternatives: INTERLACE_NONE and INTERLACE_LINE */
	$srcname = "jpg:".$srcname;
  }
  
  /* prevent that transparent background becomes black */
  if(($importtype == 'png') && ($exporttype == "jpg"))
  { list($width, $height, $imgtype, $attr) = @getimagesize($srcname);
$img->setImageBackgroundColor('white');
$img->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
$img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
  }
  
  $img->stripImage();		/* Strips an image of all profiles and comments. Stripping Exif information may effect the rotation */
  if(!$img->writeImage($srcname))
	  pecho("Error creating ".$srcname); /* image, filename, quality */
  $img->destroy();
}

function update_img_gd($srcname, $importtype, $exporttype)
{ global $default_image_quality;
  
  /* image type constants are defined here: http://php.net/manual/en/function.exif-imagetype.php */
  if($importtype == 'gif')
	$img = imagecreatefromgif($srcname);
  else if($importtype == 'png')
	$img = imagecreatefrompng($srcname);
  else if($importtype == 'jpg')
	$img = imagecreatefromjpeg($srcname);
  else if($importtype == 'wbmp')
	$img = imagecreatefromwbmp($srcname);
  else if($importtype == 'bmp')
	$img = imagecreatefrombmp($srcname);	/* not a GD library function, but defined below */
  else /* if we get an unknown format we try it as a jpg */
  { echo 'Unknown image type '.$importtype;
    $img = imagecreatefromjpeg($srcname);
	die('<br>');
  }

  if(!$img) die('GD image creation failed for file '.$srcname);
  
  if (($importtype == IMAGETYPE_JPEG) && function_exists('exif_read_data') && function_exists('mb_strtolower'))
  {	$exif = @exif_read_data($srcname);
	if ($exif && isset($exif['Orientation']))
	{	switch ($exif['Orientation'])
		{	case 3: $rotate = 180; break;
			case 6: $rotate = -90; break;
			case 8:	$rotate = 90;  break;
			default: $rotate = 0;
		}
		if ($rotate) 
		{ $img = imagerotate($img, $rotate, 0);
		}
	}			
  }
  /* prevent that transparent background becomes black */
  if(($importtype == 'png') && ($exporttype == "jpg"))
  { list($width, $height, $imgtype, $attr) = @getimagesize($srcname);
	$bg = imagecreatetruecolor($width, $height);
	$white = imagecolorallocate($bg, 255, 255, 255);
	imagefill($bg, 0, 0, $white);
	imagecopyresampled($bg, $img, 0, 0, 0, 0, $width, $height, $width, $height);
    $img = $bg;
  }

  if($exporttype == "png")
  { if(!imagepng($img, $srcname, 7))
	  die("Error creating png ".$srcname); /* image, filename, quality */
  }
  else
  { imageinterlace($img, 1); /* make the image progressive */
	if(!imagejpeg($img, $srcname, $default_image_quality))
	  die("Error creating jpg ".$srcname); /* image, filename, quality */
  }
}