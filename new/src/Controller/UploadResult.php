<?php
declare(strict_types=1);

namespace App\Controller;

class UploadResult
{
    public function __construct(public bool $success, public string $message)
    {
    }
}