<?php 
function priceFormat($float, bool $euro = true) : string
{
    return ($euro ? '&euro; ' : '') . number_format($float, 2);
}
