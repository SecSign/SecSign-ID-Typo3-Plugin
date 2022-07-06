<?php

namespace SecSign\Secsign\Utils;


class SecSignUtils
{
   static function checkDuplicateID($secsignid)
    {
        $foundValue=\SecSign\Secsign\Accessor\DBAccessor::getValueForSecSignID('username', $secsignid);
        if(\SecSign\Secsign\Accessor\SettingsAccessor::DEBUG) { error_log("found value for ".$secsignid.":".$foundValue); }
        if($foundValue)
        {
            return true;
        }else{
            return false;
        }
    }
    
    static function mergeAllowedMethods($first,$second)
    {
        foreach ($second as $valueSecond)
        {
            if(!in_array($valueSecond, $first))
            {
                $first[]=$valueSecond;
            }
        }
        return $first;
    }
}