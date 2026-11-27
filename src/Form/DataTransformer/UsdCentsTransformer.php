<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @implements DataTransformerInterface<int|null, string>
 */
final class UsdCentsTransformer implements DataTransformerInterface
{
    #[\Override]
    public function transform(mixed $value): string
    {
        if (null === $value) {
            return '';
        }

        if ($value < 0) {
            throw new TransformationFailedException('The stored price must be a non-negative integer.');
        }

        $dollars = intdiv($value, 100);
        $cents = $value % 100;

        return sprintf('%d.%02d', $dollars, $cents);
    }

    #[\Override]
    public function reverseTransform(mixed $value): int
    {
        if (!is_string($value)) {
            throw new TransformationFailedException('The price must be a string.');
        }

        $value = trim($value);

        if (!preg_match('/\A\d+(?:\.\d{1,2})?\z/', $value)) {
            throw new TransformationFailedException('Enter a valid USD amount with no more than two decimal places.');
        }

        [$dollars, $fraction] = array_pad(
            explode('.', $value, 2),
            2,
            ''
        );

        $dollars = ltrim($dollars, '0');

        if ('' === $dollars) {
            $dollars = '0';
        }

        $cents = (int) str_pad($fraction, 2, '0');

        $maxDollars = (string) intdiv(PHP_INT_MAX, 100);
        $maxCents = PHP_INT_MAX % 100;

        if (
            strlen($dollars) > strlen($maxDollars) || (
                strlen($dollars) === strlen($maxDollars) && strcmp($dollars, $maxDollars) > 0
            )
        ) {
            throw new TransformationFailedException('The price is too large.');
        }

        $dollarValue = (int) $dollars;

        if (
            $dollarValue === (int) $maxDollars && $cents > $maxCents
        ) {
            throw new TransformationFailedException('The price is too large.');
        }

        return ($dollarValue * 100) + $cents;
    }
}
