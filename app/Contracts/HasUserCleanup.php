<?php

namespace App\Contracts;

interface HasUserCleanup
{
    public function cleanupOnUserRemoval(): void;
}
