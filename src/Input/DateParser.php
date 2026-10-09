<?php

declare(strict_types=1);

final class DateParser
{
    private const FORMATS = [
        'Y-m-d',
        'd/m/Y',
        'd.m.Y',
        'd-m-Y',
        'Y/m/d',
        'j/n/Y',
        'j.n.Y',
        'j-n-Y',
        'Y/n/j',
        'd M Y',
        'j M Y',
        'd F Y',
        'j F Y',
        'M j, Y',
        'F j, Y',
    ];

    public function parse(string $value): DateTimeImmutable
    {
        $value = trim($value);

        foreach (self::FORMATS as $format) {
            $date = DateTimeImmutable::createFromFormat(
                '!' . $format,
                $value,
            );

            if (
                $date !== false &&
                $date->format($format) === $value
            ) {
                return $date;
            }
        }

        throw new InvalidArgumentException(
            "Invalid or unsupported date: '{$value}'.",
        );
    }
}