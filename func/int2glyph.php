<?php 
function int2glyph($int, string $glyph) : string
{
    if ($int == '1') {
        return sprintf('<span class="glyphicon %s" title="Yes" style="color: green;"></span>', $glyph);
    } elseif ($int == '0') {
        return sprintf('<span class="glyphicon %s" title="No" style="color: red;""></span>', $glyph);
    } else {
        return '';
    }
}
