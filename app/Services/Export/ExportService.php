<?php

namespace App\Services\Export;

class ExportService
{
    public function safeCsvValue(mixed $value): string
    {
        $value = (string) $value;

        if ($value !== '' && preg_match('/^[=+\-@]/', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }
}
