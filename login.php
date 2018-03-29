<?php

 include "utilities.php";
 
 session_start();

 if($_POST["action"] == "login")
 {
    loginUser();
 }
 
 //End of script entry
 
 
 function loginUser()
 {
    if(empty($_POST["theUsername"]) || empty($_POST["encPassword"]))
    {
        sendMessage(false, false, "Required fields empty!", null);
    }
 
    $username = $_POST["theUsername"];
    $encPassword = $_POST["encPassword"];
 
    if(!is_readable("users.json"))
    {
        sendMessage(false, false, "Users files cannot be read!", null);
    }
 
    $userFlag = checkJSON($username, $encPassword);
 
    if(isset($userFlag))
    {
        if($userFlag === "banned")
        {
            sendMessage(false, false, "User is banned!", null);
        }
        else
        {
            $_SESSION["username"] = $username;
            $_SESSION["userFlag"] = $userFlag;
        
            $dataArray = null;
            $dataArray["userFlag"] = $userFlag;
            sendMessage(true, false, null, $dataArray);
        }
    }
    else
    {
        sendMessage(false, false, "Incorrect username or password!", null);
    }
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
 
?>