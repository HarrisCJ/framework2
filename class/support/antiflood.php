<?php
/**
 * A class that tries to detect when there have been too
 * many class from a given IP address
 *
 * @author Lindsay Marshall <lindsay.marshall@ncl.ac.uk>
 * @copyright 2020 Newcastle University
 *
 */
    namespace Support;

    use Support\Context as Context;
/**
 * Handles AntiFlood calls
 */
    class AntiFlood
    {
        const TABLE     = 'fwflood';
        const KEEPTIME  = 60*60;
        const DIVERSION = 'https://google.com';
/**
 * Check if an IP is flooding
 *
 * @param int    $limit    Number of seconds allowed between calls
 * @param bool   $divert   If TRUE Then don't return but divert somewhere else
 *
 * @return bool
 */
        public static function flooding(int $limit, bool $divert = TRUE) : bool
        {
            $now = time();
            \R::exec('delete from '.self::TABLE.' where ('.$now.' - calltime) > '.self::KEEPTIME);
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
            $f = \R::findOne(self::TABLE, 'ip=?', [$ip]);
            if (is_object($f))
            {
                $res =  ($limit - $f->calltime) < $limit;
            }
            else
            {
// Not in table so log it.
                $f = \R::dispense(self::TABLE);
                $f->ip = $ip;
                $res = FALSE;
            }
            $f->calltime = $now; // update the stored time
            \R::store($f);
            if ($divert && $res)
            { // we are flooding so divert the caller
                Context::getinstance()->web()->relocate(self::DIVERSION);
            }
            return $res;
        }
    }
?>