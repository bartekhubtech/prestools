<?php
    if(!@include 'approve.php') die( "approve.php was not found!");

	$startId = (int)$_POST['startId'];
	$batchsize = (int)$_POST['batchsize'];
	$zipfile = $_POST['zipfile'];
	$pos = strrpos($zipfile, "/");
	$targetfolder = substr($zipfile, 0, $pos);
	
    $zip = new ZipArchive();
    if(($result = $zip->open($zipfile)) !== true)
	  colordie("Error opening zipfile: ".$ziperrors[$result]."!");
    if (!is_writable("./")) 
	  colordie("No writing rights!");
    $numFiles = $zip->numFiles;
    $lastId = $startId + $batchsize;

    $fileList = array();
	$currentFile='';
    for ($id = $startId; $id < min($numFiles, $lastId); $id++) 
	{   $currentFile = $zip->getNameIndex($id);
        if ($zip->extractTo($targetfolder, $currentFile) === false) {
            die(json_encode([
                'error'    => true,
                'message'  => 'Extraction error - '.$zip->getStatusString(),
                'file'     => $currentFile,
                'numFiles' => $numFiles,
                'lastId'   => $lastId,
            ]));
        }
    }
	
    $zip->close();

    if ($lastId >= $numFiles) {
//        unlink($zipfile);
    }

    die(json_encode([
        'error'    => false,
        'numFiles' => $numFiles,
        'lastId'   => $lastId,
		'example'  => $targetfolder."--".$currentFile,
    ]));
	
	$ziperrors = [
	ZipArchive::ER_EXISTS => "File already exists.",
	ZipArchive::ER_INCONS => "Zip archive inconsistent or corrupted.",
	ZipArchive::ER_INVAL => "Invalid argument.",
	ZipArchive::ER_MEMORY => "Memory allocation error.",
	ZipArchive::ER_NOENT => "Unable to find the file.",
	ZipArchive::ER_NOZIP => "Not a zip file or corrupted.",
	ZipArchive::ER_OPEN => "Can't open file. Check read access.",
	ZipArchive::ER_READ => "Read error.",
	ZipArchive::ER_SEEK => "Seek error.",
    ];
	
	