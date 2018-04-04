<?php

function deleteTree($dir)
{
   $files = array_diff(scandir($dir), array('.','..'));
   
    foreach ($files as $file)
    {
	  if(is_dir("$dir/$file"))
	  {
		  deleteTree("$dir/$file");
	  }
	  else
	  {
		  if(!@unlink("$dir/$file"))
		  {
			  return false;
		  }
	  }
    }
    
    return @rmdir($dir);
  }

function sendMessage($succeeded, $hasSessionError, $errorMessage, $dataArray)
 {
    $jsonMessageArray = null;
    $jsonMessageArray["succeeded"] = $succeeded;
    $jsonMessageArray["hasSessionError"] = $hasSessionError;
    
    if(isset($errorMessage))
    {
        $jsonMessageArray["errorMessage"] = "ServerError: " . $errorMessage;
    }
    
    if(isset($dataArray))
    {
        $jsonMessageArray["dataArray"] = $dataArray;
    }
    
    $jsonMessage = json_encode($jsonMessageArray, JSON_PRETTY_PRINT);
    echo $jsonMessage;
    
    if($succeeded == false)
    {
        die(0);
    }
 }
?>