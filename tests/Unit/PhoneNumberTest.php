<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    #[DataProvider('formattable')]
    public function test_formats_ten_digit_numbers(string $input): void
    {
        $this->assertSame('(123) 123-9090', PhoneNumber::format($input));
    }

    public static function formattable(): array
    {
        return [
            'digits only' => ['1231239090'],
            'dashes' => ['123-123-9090'],
            'already formatted' => ['(123) 123-9090'],
            'no hyphen' => ['(123) 123 9090'],
            'country code' => ['+1 123 123 9090'],
            'dots' => ['123.123.9090'],
        ];
    }

    #[DataProvider('unformattable')]
    public function test_rejects_everything_else(?string $input): void
    {
        $this->assertNull(PhoneNumber::format($input));
    }

    public static function unformattable(): array
    {
        return [
            'nine digits' => ['62-320-4940'],
            'eleven digits without a leading 1' => ['21231239090'],
            'empty' => [''],
            'null' => [null],
            'letters' => ['call me'],
        ];
    }

    public function test_format_or_keep_leaves_unreadable_values_alone(): void
    {
        $this->assertSame('62-320-4940', PhoneNumber::formatOrKeep('62-320-4940'));
        $this->assertSame('(123) 123-9090', PhoneNumber::formatOrKeep('1231239090'));
    }
}
