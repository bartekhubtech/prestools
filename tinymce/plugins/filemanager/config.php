<?php 
if(!@include '../../../approve.php') die( "approve.php was not found!");
//if($_SESSION["verify"] != "FileManager4TinyMCE") die('forbidden');

//Specifies where the root of your webpage sits on disk. Usually it is best to
//leave it as DOCUMENT_ROOT

//**********************
//Path configuration
//**********************
// The default configuration uses the following setup
// | - root
// | | - uploads <- Directory where files will be uploaded
// | | - thumbs  <- Directory containing auto-generated thumbnails
// | | - tinymce
// | | | - plugins
// | | | | - filemanager
$ssl_enabled = get_configuration_value('PS_SSL_ENABLED');
$query="select * from ". _DB_PREFIX_."shop_url";
$res=dbquery($query);
$row = mysqli_fetch_array($res);
if($ssl_enabled)
{ $base_url = "https://".$row["domain_ssl"].$row["physical_uri"]; 
}
else 
{ $base_url = "http://".$row["domain"].$row["physical_uri"]; 
}
$root = str_replace("\\","/",realpath($triplepath));
$upload_dir = 'img/cms'; // path from the base_url to the uploads folder
$thumbs_dir = 'img/thumbs'; // path from the base_url to thumbs folder

$MaxSizeUpload=100; //Mb

//**********************
//Image config
//**********************
//set max width pixel or the max height pixel for all images
//If you set dimension limit, automatically the images that exceed this limit are convert to limit, instead
//if the images are lower the dimension is maintained
//if you don't have limit set both to 0
$image_max_width=0;
$image_max_height=0;

//Automatic resizing //
//If you set true $image_resizing the script convert all images uploaded to image_width x image_height resolution
//If you set width or height to 0 the script calcolate automatically the other size
$image_resizing=false;
$image_width=600;
$image_height=0;

//Thumbnail Size//
$thumbnail_width=122;
$thumbnail_height=91;

//******************
//Permissions config
//******************
$delete_file=true;
$create_folder=true;
$delete_folder=true;
$upload_files=true;


//**********************
//Allowed extensions
//**********************
$ext_img = array('jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'svg'); //Images
$ext_file = array('pdf'); //'doc', 'docx', 'pdf', 'xls', 'xlsx', 'txt', 'csv','html','psd','sql','log','fla','xml','ade','adp','ppt','pptx'); //Files
$ext_video = array('mov', 'mpeg', 'mp4', 'avi', 'mpg', 'wma', 'flv', 'webm'); //Videos
$ext_music = array(); //array('mp3', 'm4a', 'ac3', 'aiff', 'mid'); //Music
$ext_misc = array(); //array('zip', 'rar','gzip'); //Archives


$ext=array_merge($ext_img, $ext_file, $ext_misc, $ext_video,$ext_music); //allowed extensions

?>
