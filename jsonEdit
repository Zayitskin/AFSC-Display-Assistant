<?php

include "utilities.php";

session_start();

 if($_POST["action"] === "editUsers")
 {
    editJSONFile("users.json");
 }
 else if($_POST["action"] === "editUploads")
 {
    editJSONFile("uploads.json");
 }
 
function editJSONFile($file)
{
    if(!isset($_SESSION["username"]) || !isset($_SESSION["userFlag"]))
    {
        sendMessage(false, true, "Issue with current session!", null);
    }
    
    if($_SESSION["userFlag"] !== "admin")
    {
        sendMessage(false, true, "You lack permissions!", null);
    }
    
    if (empty($_POST["jsonData"]))
    {
        sendMessage(false, false, "JSON data is empty!", null);
    }

    $jsonData = $_POST["jsonData"];
   
    if(!@file_put_contents($file, $jsonData))
    {
        sendMessage(false, false, "Could not write to JSON file!", null);
    }
}

/*
    // Example uploading JSON data to the above PHP function with AJAX request using JQuery
    $('document').ready(function()
	    {
	        //Replace "yourButton" with name/id of the button that submits the form.
            $('#yourButton').click(function(event)
            {
                event.preventDefault();
                
                //Replace "yourJSONData" with the JSON data you changed.
                var encodedJSONData = JSON.stringify(yourJSONData); 
            
                $.ajax(
                {
                url : "jsonEdit.php",
                type: "POST",
                data : new FormData(),
                processData: false,
                contentType: false,
                beforeSend: function()
                {
                    //Replace "editWhatever" with either "editUsers" or "editUploads"
                    this.data.append('action', 'editWhatever');
                    this.data.append('jsonData', encodedJSONData);
                },
                success:function(msg)
                {
                    var response = JSON.parse(msg);
                    
                    if(response.succeeded == false)
                    {
                        alert(response.errorMessage);
                        
                        if(response.hasSessionError == true)
                        {
                            window.location.replace("login.html");
                        }
                    }
                    else
                    {
                        window.location.replace("confirmation.html");
                    }
                    },
                    failure:function(msg)
                    {
                        alert(msg);
                    }
                });
            })
        })
*/
 
 ?>