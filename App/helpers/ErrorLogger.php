<?php
class Helpers_ErrorLogger
{
    
    public static function log($msg)
    {
        
        $errorLogFile = APP_PATH ."/extras/logs/errors.txt";
        
        // file must be writable
        if ( is_writable($errorLogFile) ) 
        {
            $content = "";
            $content .= "\nError Log Date Time : " . date("d-m-Y H:i:s") . "\n";
            $content .= "\n". stripslashes($msg) ."\n";
            $content .= "\n=============================================\n";
            
            // open file in append mode so it will always write content at the end
            if ( !$handle = fopen($errorLogFile, 'a') ) 
            {
                echo "Cannot open file ($errorLogFile)";
                exit;
            }
            
            if ( fwrite($handle, $content) === FALSE ) 
            {
                echo "Cannot write to file ($errorLogFile)";
                exit;
            }
            
            fclose($handle);
            
        }
        else
        {
            trigger_error("Error log file is not writable", E_USER_NOTICE);
        }
        
    }
    
}
?>