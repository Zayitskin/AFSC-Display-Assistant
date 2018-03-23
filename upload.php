<?php

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
 if (empty($_POST["startTime"])
 || empty($_POST["endDate"]) || empty($_POST["endTime"]) 
 || empty($_FILES["fileToUpload"]))
{
    sendMessage(false, "Error: Not all required fields were set!");
}

/*
 * Make sure file isn't larger than 100 megabytes, this is an arbitrary max value
 */
if ($_FILES["fileToUpload"]["size"] > 838860800 )
{
    sendMessage(false, "Error: Your file is too large!");
 }
    
/*
 * Make sure file is powerpoint
 */
if($fileType != "ppt" && $fileType != "pptx")
{
    sendMessage(false, "Error: Must upload PPTX or PPT file!");
}

    

/*
 * Check if slides directory and slides.json are writable.
 */
 if(!is_writable($target_dir) || !is_writable("uploads.json"))
 {
    sendMessage(false, "Error: Files cannot be written to!");
 }
 
/*
 * !!!!!!!!!!!
 * Username is hardcoded for now.
 * This needs to be replaces with getting the actual user that uploaded the file.
 *
 * !!!!!!!!!!!
 * Uploading the file should not thrown an error.
 * If it does, the JSON file is now corrupted because it contains a slide that was not able to be saved properly.
 * This error should be dealt with later.
 */
if (updateJSON("obama", $newName . "." . $fileType))
{
    if(move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file))
    {
        sendMessage(true, "The file ". basename($_FILES["fileToUpload"]["name"]). " has been uploaded.");
    }
    else
    {
        sendMessage(false, "FatalError: There was an error uploading your file! uploads.json is now potentially corrupted!");
    }   
}
else
{
    sendMessage(false, "Error: There was an error saving the slides JSON file!");
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
            $newDataArray["slides"][0]["startTime"] = $_POST["startTime"];
            $newDataArray["slides"][0]["endDate"] = $_POST["endDate"];
            $newDataArray["slides"][0]["endTime"] = $_POST["endTime"];
            $newDataArray["slides"][0]["value"] = "Undecided";
            $newDataArray["slides"][0]["comments"] =  $_POST["comments"];
            
	        array_push($jsonArray["uploads"], $newDataArray);
        }
        else
        {
            $newDataArray["path"] = $fileName;
            $newDataArray["startDate"] = $_POST["startDate"];
            $newDataArray["startTime"] = $_POST["startTime"];
            $newDataArray["endDate"] = $_POST["endDate"];
            $newDataArray["endTime"] = $_POST["endTime"];
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
        sendMessage(false, $e->getMessage());
        return true;
    }
}

function sendMessage($succeeded, $message)
{
    $messageArray = null;
    $messageArray["succeeded"] = $succeeded;
    $messageArray["message"] = $message;
    $jsonMessage = json_encode($messageArray, JSON_PRETTY_PRINT);
    echo $jsonMessage;
    
    if($succeeded == false)
    {
        die(0);
    }
}

?>