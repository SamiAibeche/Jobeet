<?php

namespace Ens\JobeetBundle\Utils;


/**
 * Class Jobeet
 * @package Ens\JobeetBundle\Utils
 *
 * Function use in the models "Job" directly m
 */
class Jobeet
{
    static public function slugify($text)
    {
    // replace all non letters or digits by -
        $text = preg_replace('/\W+/', '-', $text);

    // trim and lowercase
        $text = strtolower(trim($text, '-'));

        return $text;
    }
}
 