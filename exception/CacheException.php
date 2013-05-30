<?php
class CacheException extends CrossException
{
    function error_handler (exception $e)
    {
        $message = $e->getMessage();
        $line = $e->getLine();
        $trace = $e->getTrace();
        $class = $trace[0]["class"];
        $traceString = $e->getTraceAsString ();
        $notes["n"] = $message.' '.$class.' '.$line.'<br><pre>'.trim(var_export($traceString, true), "'").'</pre>';       
        
        $tpl = CROSSPHP_PATH.'exception/_tpl/error.php';              
        return Response::message( $code, $notes, $tpl );
    }
}