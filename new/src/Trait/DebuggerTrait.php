<?php
declare(strict_types=1);

namespace App\Trait;

trait DebuggerTrait
{
    private function dd(...$args): void
    {
        echo '<pre>';
        var_dump($args);
        echo '</pre>';
        exit();
    }
}