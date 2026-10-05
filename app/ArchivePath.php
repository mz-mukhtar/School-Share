<?php

namespace App;

class ArchivePath
{
    public static function validSegment(string $name): bool
    {
        return $name !== '' && $name !== '.' && $name !== '..'
            && preg_match('/[\x00-\x1f\x7f\\\\\/:]/u', $name) === 0
            && ! str_ends_with($name, '.') && ! str_ends_with($name, ' ')
            && ! preg_match('/^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(\.|$)/i', $name);
    }

    public static function validate(string $name): void
    {
        abort_unless(self::validSegment($name), 422, 'A file or folder has an unsafe archive name. Rename it before exporting.');
    }
}
