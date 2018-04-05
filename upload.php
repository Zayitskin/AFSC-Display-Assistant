<?php

include "utilities.php";

session_start();

 if($_POST["action"] === "uploadSlide")
 {
    uploadSlide();
 }
 
 //End of script entry
 
 
function uploadSlide()
{
     if(!isset($_SESSION["username"]) || !isset($_SESSION["userFlag"]))
    {
        sendMessage(false, true, "Issue with current session!", null);
    }

    $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $stringLength = 16;

    /*
     * The new name of the file
     */
    $newName = '';
 
    /*
     * Generate random 16 character alphanumeric name for powerpoint
     */
    $max = strlen($characters) - 1;
    for ($i = 0; $i < $stringLength; $i++)
    {
        $newName .= $characters[mt_rand(0, $max)];
    }

    $target_dir = "slides/";
    $fileType = strtolower(pathinfo($target_dir . $_FILES["fileToUpload"]["name"],PATHINFO_EXTENSION));
    $target_file = $target_dir . $newName . "." . $fileType;

    /*
     * Make sure required data is set
     */
     if (empty($_POST["startDate"])|| empty($_POST["endDate"]) || empty($_FILES["fileToUpload"]))
    {
        sendMessage(false, false, "Not all required fields were set!", null);
    }

    /*
     * Make sure file isn't larger than 100 megabytes, this is an arbitrary max value
	 * upload_max_filesize and post_max_size need to be changed as well in php.ini
	 * Use phpinfo.php page to find out where your php.ini file is located
     */
    if ($_FILES["fileToUpload"]["size"] > 838860800 )
    {
        sendMessage(false, false, "Your file is too large!", null);
     }
    
    /*
     * Make sure file is powerpoint
     */
    if($fileType != "pptx")
    {
        sendMessage(false, false, "Must upload PPTX file!", null);
    }

    /*
     * Check if slides directory and slides.json are writable.
     * 
     * This might not actually work on windows...
     */
     
     if(!is_writable($target_dir) || !is_writable("uploads.json") || !is_writable("temporary"))
     {
        sendMessage(false, false, "Files cannot be written to!", null);
     }
     
    /*
     * Check powerpoint aspect ratio is 16:9 and it only contains one slide.
     */
     $index = 0;
     $tempFilePath = null;
     $tempPPTXPath = null;
     
     for(;$index < 100; $index++)
     {
        $tempFilePath = "temporary/" . "temp" . $index . "/";
        $tempPPTXPath = $tempFilePath . $newName . "." . $fileType;
     
        if(!file_exists($tempFilePath))
        {
            if(mkdir($tempFilePath))
            {
                if(move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $tempPPTXPath))
                {
                    $zip = new ZipArchive;
                    $opened = $zip->open($tempPPTXPath);
    
                    if ($opened === TRUE)
                    {
                        $zip->extractTo($tempFilePath);
                        $zip->close();
                        
                        $fileIterator = new FilesystemIterator($tempFilePath . "ppt/slides/_rels", FilesystemIterator::SKIP_DOTS);
                        $fileCount = iterator_count($fileIterator);
                        
                        if($fileCount > 1)
                        {
                            removeTemporary($index);
                            sendMessage(false, false, "Powerpoint file contains more than one slide. File must contain only one slide!", null);
                        }
						
                        /*
                         *  MAKE SURE TO UNSET THE STUPID THINGS EVEN THO NO ONE TELLS YOU TO DO THAT BECAUSE IT WILL BLOCK THE TEMP FILES FROM BEING DELETED YES I AM MAD
                         */
						unset($fileIterator);
						unset($fileCount);
                        
                        $xmlFile = fopen($tempFilePath . "/ppt/presentation.xml", "r");
                        
                        if($xmlFile === false)
                        {
                            removeTemporary($index);
                            sendMessage(false, false, "Could not read xml file!", null);
                        }
                        
                        $fileContents = fread($xmlFile,filesize($tempFilePath . "/ppt/presentation.xml"));
						fclose($xmlFile);
                    
                        if(!preg_match("/screen16x9/", $fileContents))
                        {
                            /*
                             *   Hot diggidy doodle that regex tho...
                             *   Not all powerpoints files have the 16x9 listed in the xml file.
                             *   As such, we have to get the slide size and calculate the aspect ratio...
                             */
                            if(preg_match("/sldSz\s*cx\s*=\s*\"\s*[0-9]*\s*\"\s*cy\s*=\s*\"\s*[0-9]*\s*\"/", $fileContents, $caughtStrings))
                            {
                                preg_match_all("/[0-9]+/", $caughtStrings[0], $dimensions);
                                
                                if(!(floatval($dimensions[0][0]) / floatval($dimensions[0][1]) == 16 / 9))
                                {
                                    removeTemporary($index);
                                    sendMessage(false, false, "Powerpoint file is not 16x9 aspect ratio!", null);
                                }
                            }
                            else
                            {
                                removeTemporary($index);
                                sendMessage(false, false, "Powerpoint file xml dimension could not be parsed!", null);
                            }
                        }
                    }
                    else
                    {
                        $zip->close();
                        removeTemporary($index);
                        sendMessage(false, false, "Could not unzip the pptx file!", null);
                    }
                }
                else
                {
                    removeTemporary($index);
                    sendMessage(false, false, "There was an error moving the powerpoint file to new temporary directory!", null); 
                }

                break;
            }
            else
            {
                sendMessage(false, false, "Could not create a new temporary directory!", null);
            }
        }
     }
     
     if($index === 100)
     {
        sendMessage(false, false, "Could not create a new temporary directory! Index reached limit!", null);
     }
 
    /*
     * !!!!!!!!!!!
     * Updating the JSON file should not thrown an error
     * If it does, the slides directory now contains a powerpoint with no data in the uploads JSON file.
     */
    if(copy($tempPPTXPath, $target_file))
    {
        removeTemporary($index);
        
        if(updateJSON($_SESSION["username"], $newName . "." . $fileType))
        {
            sendMessage(true, false, null, null);
        }
        else
        {
            if(!unlink($target_file))
            {
                sendMessage(false, false, "Fatal - There was an error saving the uploads JSON file and deleting the new pptx file! The slides directory now contains a dead powerpoint!", null);
            }
        
            sendMessage(false, false, "There was an error saving the uploads JSON file!", null);
        } 
    }
    else
    {
        removeTemporary($index);
        sendMessage(false, false, "There was an error moving the powerpoint file to slides directory!", null); 
    }
}

/*
 * Remove the temporary files created for pptx file checks.
 */
function removeTemporary($tempIndex)
{
    $tempFilePath = "temporary/" . "temp" . $tempIndex;
    
    if(!deleteTree($tempFilePath))
    {
        sendMessage(false, false, "Fatal - There was an error deleting the temporary directory! The temporary directory now contains a dead temp folder!", null);
    }
}

/*
 * Returns true if slides JSON file was succesfully updated.
 */
function updateJSON($username, $fileName)
{
    $myFile = "uploads.json";
    $arr_data = array(); // create empty array

    try
    {
	    /*
         * Get contents from existing json file
         */
	    $jsonContents = file_get_contents($myFile);

	    /*
         * Converts json contents into array
         */
	    $jsonArray = json_decode($jsonContents, true);
	    
	    /*
         * Checks if the user is already in json file
         */
        $userIsContained = false;
        $userIndex = 0;
         
	    foreach ($jsonArray["uploads"] as $value)
	    {
            if($value["user"] === $username)
            {
                $userIsContained = true;
                break;
            }
            
            $userIndex++;
        }
        
        $newDataArray = null;
        
        /*
         * Add user to uploads list
         */
        if(!$userIsContained)
        {
            $newDataArray["user"] = $username;
            
            $newDataArray["slides"][0]["path"] = $fileName;
            $newDataArray["slides"][0]["startDate"] = $_POST["startDate"];
            $newDataArray["slides"][0]["endDate"] = $_POST["endDate"];
            $newDataArray["slides"][0]["value"] = "Undecided";
            $newDataArray["slides"][0]["comments"] =  $_POST["comments"];
            
	        array_push($jsonArray["uploads"], $newDataArray);
        }
        else
        {
            $newDataArray["path"] = $fileName;
            $newDataArray["startDate"] = $_POST["startDate"];
            $newDataArray["endDate"] = $_POST["endDate"];
            $newDataArray["value"] = "Undecided";
            $newDataArray["comments"] =  $_POST["comments"];
        
            array_push($jsonArray["uploads"][$userIndex]["slides"], $newDataArray);
        }
        

        /*
         * Convert updated array to json
         */
	    $updatedJSONContents = json_encode($jsonArray, JSON_PRETTY_PRINT);
	   
	    /*
         * Write json contents back into file
         */
	    if(@file_put_contents($myFile, $updatedJSONContents))
	    {
	        return true;
	    }
	    else 
	    {
	        return false;
	    }

    }
    catch (Throwable $e)
    {
        sendMessage(false, false, $e->getMessage(), null);
        return true;
    }
}

?>
