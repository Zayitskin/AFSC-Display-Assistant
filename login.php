<?php

/*
 * userFlag
 * - "admin" or "user"
 *
 * encPassword
 */
 
 if(empty($_POST["theUsername"]) || empty($_POST["encPassword"]))
 {
    sendMessage(false, "Error: Required fields empty!", "none");
 }
 
 $username = $_POST["theUsername"];
 $encPassword = $_POST["encPassword"];
 
 if(!is_readable("users.json"))
 {
    sendMessage(false, "Error: Users files cannot be read!", "none");
 }
 
 $userFlag = checkJSON($username, $encPassword);
 
 if(isset($userFlag))
 {
    if($userFlag === "banned")
    {
        sendMessage(false, "Error: User is banned!", "none");
    }
    else
    {
        sendMessage(true, "none", $userFlag);
    }
 }
 else
 {
    sendMessage(false, "Error: Incorrect username or password!", "none");
 }
 
 
/*
 * Returns userflag or NULL if the user is not the JSON file or did not supply a correct password.
 */
function checkJSON($username, $encPassword)
{
    $myFile = "users.json";
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
         * Checks for user existance and correct password.
         */
	    foreach ($jsonArray["users"] as $value)
	    {
            if($value["user_name"] === $username)
            {
                if($value["encPassword"] === $encPassword)
                {
                    return $value["user_flag"];
                }
            }
        }
        
        return null;
    }
    catch (Throwable $e)
    {
        sendMessage(false, $e->getMessage());
        return true;
    }
}
 
 function sendMessage($succeeded, $message, $userFlag)
 {
    $messageArray = null;
    $messageArray["succeeded"] = $succeeded;
    $messageArray["message"] = $message;
    $messageArray["userFlag"] = $userFlag;
    $jsonMessage = json_encode($messageArray, JSON_PRETTY_PRINT);
    echo $jsonMessage;
    
    if($succeeded == false)
    {
        die(0);
    }
 }
 
?>