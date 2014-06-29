<?php

namespace Core;

class Sys
{

    /**
     * Редирект
     *
     * @param $url
     * @param int $statusCode
     */
    public static function redirect($url, $statusCode = 302)
    {
        header('Location: ' . $url, true, $statusCode);
    }


    /**
     * Преобразование дат из ISO в нужный формат
     */
    public static function date($date, $format)
    {
        $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $date);
        return $dt ? $dt->format($format) : null;
    }


    /**
     * Преобразование сумм
     */
    public static function number($n)
    {
        return $n;
    }

}