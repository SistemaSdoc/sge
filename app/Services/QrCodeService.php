<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeService
{
    public static function generate(string $text): string
    {
        $result = (new Builder(
            writer: new PngWriter(),
            data: $text,
            size: 300,
            margin: 10,
        ))->build();

        return base64_encode($result->getString());
    }
}