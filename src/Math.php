<?php

declare(strict_types=1);

namespace G\Math;

use DivisionByZeroError;
use InvalidArgumentException;

class Math
{
    /** Smallest representable positive number such that `1.0 + EPSILON != 1.0` */
    public const EPSILON = PHP_FLOAT_EPSILON;

    /** Rounding towards negative infinity. */
    public const FLOOR = 0;

    /** Rounding towards negative infinity. */
    public const NEGATIVE_INFINITY = 0;

    /** Rounding towards positive infinity. */
    public const CEIL = 1;

    /** Rounding towards positive infinity. */
    public const POSITIVE_INFINITY = 1;

    /** Rounding towards zero. */
    public const TOWARDS_ZERO = 2;

    /** Rounding away from zero. */
    public const AWAY_FROM_ZERO = 3;

    /** Rounding to the nearest with ties going towards negative infinity. */
    public const HALF_FLOOR = 4;

    /** Rounding to the nearest with ties going towards negative infinity. */
    public const HALF_NEGATIVE_INFINITY = 4;

    /** Rounding to the nearest with ties going towards positive infinity. */
    public const HALF_CEIL = 5;

    /** Rounding to the nearest with ties going towards positive infinity. */
    public const HALF_POSITIVE_INFINITY = 5;

    /** Rounding to the nearest with ties going towards zero. */
    public const HALF_TOWARDS_ZERO = 6;

    /** Rounding to the nearest with ties going away from zero. */
    public const HALF_AWAY_FROM_ZERO = 7;

    /** Rounding to the nearest with ties going to the nearest even. */
    public const HALF_EVEN = 8;

    /** Rounding to the nearest with ties going to the nearest odd. */
    public const HALF_ODD = 9;

    /** Alignment parallel (0°). */
    public const ALIGNMENT_PARALLEL = 0;

    /** Alignment orthogonal (90°). */
    public const ALIGNMENT_ORTHOGONAL = 90;

    /** Alignment opposite (180°). */
    public const ALIGNMENT_OPPOSITE = 180;

    /** Alignment oblique. */
    public const ALIGNMENT_OBLIQUE = 45;

    /** Full circle in degrees. */
    public const FULL_CIRCLE = 360;

    /** Largest n whose factorial fits in a 64-bit integer. */
    private const MAX_FACTORIAL = 20;

    /** Decimal digits kept by bcmath beyond the operands' own precision. */
    private const BC_GUARD_DIGITS = 12;

    /** Digits ignored at the end of the guard when testing for an exact half. */
    private const BC_HALF_SLACK = 2;

    /** Scale covering the whole range of a float. */
    private const BC_FLOAT_SCALE = 308;

    /**
     * Determines the angular relationship between two axes.
     *
     * @param int|float $num1 First angle in degrees.
     * @param int|float $num2 Second angle in degrees.
     * @param int|float $tolerance Acceptable margin of error.
     * @param bool $fullCircle true for full circle (360° wrap), false for toric (180° wrap).
     *
     * @return int The orientation category.
     */
    public static function angularAlignment(
        int|float $num1,
        int|float $num2,
        int|float $tolerance,
        bool $fullCircle = true
    ): int {
        if ($tolerance < 0) {
            throw new InvalidArgumentException('Tolerance must be a non-negative number.');
        }

        $range = $fullCircle ? 360 : 180;

        $delta = fmod(abs($num1 - $num2), $range);
        $delta = (float) min($delta, $range - $delta);

        return match (true) {
            $delta <= $tolerance => self::ALIGNMENT_PARALLEL,
            abs($delta - self::ALIGNMENT_ORTHOGONAL) <= $tolerance => self::ALIGNMENT_ORTHOGONAL,
            $fullCircle && abs($delta - self::ALIGNMENT_OPPOSITE) <= $tolerance => self::ALIGNMENT_OPPOSITE,
            default => self::ALIGNMENT_OBLIQUE,
        };
    }

    /**
     * Calculates the angular distance between two angles in degrees, returning a value between 0 and 180.
     *
     * @param int|float $num1 First angle in degrees
     * @param int|float $num2 Second angle in degrees
     *
     * @return float The angular distance between the two angles in degrees
     *
     * @example Math::angularDistance(10, 350) returns 20
     * @example Math::angularDistance(45, 135) returns 90
     * @example Math::angularDistance(0, 180) returns 180
     * @example Math::angularDistance(270, 90) returns 180
     */
    public static function angularDistance(int|float $num1, int|float $num2): float
    {
        $delta = self::fmod(self::abs($num1 - $num2), self::FULL_CIRCLE);
        return min($delta, self::FULL_CIRCLE - $delta);
    }

    /**
     * Calculates the average of the given numbers.
     *
     * @param int|float ...$nums The numbers to calculate the average of
     *
     * @return float The average of the given numbers
     * @throws InvalidArgumentException If no numbers are provided
     */
    public static function average(int|float ...$nums): float
    {
        if ($nums === []) {
            throw new InvalidArgumentException('At least one number is required.');
        }

        return (float) (array_sum($nums) / count($nums));
    }

    /**
     * Clamps a value between a minimum and maximum bound.
     *
     * @param mixed $min The lower bound
     * @param mixed $preferred The preferred value
     * @param mixed $max The upper bound
     *
     * @return mixed The clamped value
     *
     * @example Math::clamp(0, 5, 10) returns 5
     * @example Math::clamp(0, -1, 10) returns 0
     * @example Math::clamp(0, 15, 10) returns 10
     */
    public static function clamp(mixed $min, mixed $preferred, mixed $max): mixed
    {
        return self::max($min, self::min($preferred, $max));
    }

    /**
     * Finds the closest value to a target in an array of numbers
     *
     * @param int|float $target The target value to find the closest match for
     * @param array<int|float> $values Array of numeric values to search in
     *
     * @return int|float The closest value from the array
     * @throws InvalidArgumentException
     *
     * @example Math::closest(12.5, [0, 2, 5, 10, 20, 25, 28, 30]) returns 10
     * @example Math::closest(12.5, [-5, 520, 42, 10, -53.25, 7.99, 20, -100]) returns 10
     */
    public static function closest(int|float $target, array $values): int|float
    {
        if (empty($values)) {
            throw new InvalidArgumentException('The values array cannot be empty.');
        }

        $values = array_values($values);

        $minDistance = self::abs($values[0] - $target);
        $closestValue = $values[0];

        foreach ($values as $value) {
            if ($value === $target) {
                // Exact match found
                return $value;
            }

            $distance = self::abs($value - $target);

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $closestValue = $value;
            } elseif ($distance === $minDistance && $value > $closestValue) {
                // If distances are equal, choose the larger value
                $closestValue = $value;
            }
        }

        return $closestValue;
    }

    /**
     * Counts the meaningful decimal places of a number, as PHP prints it.
     *
     * @example Math::countDecimals(1.25) returns 2
     * @example Math::countDecimals(1.0E-7) returns 7
     */
    public static function countDecimals(float $num): int
    {
        if ($num == 0.0 || !is_finite($num)) {
            return 0;
        }

        // Very small or very large floats are printed in scientific notation ("1.0E-7")
        [$mantissa, $exponent] = array_pad(explode('e', strtolower((string) $num)), 2, '0');
        $mantissaDecimals = str_contains($mantissa, '.')
            ? strlen(rtrim(explode('.', $mantissa)[1], '0'))
            : 0;

        return max(0, $mantissaDecimals - (int) $exponent);
    }

    /**
     * @param int $num
     * @return int
     * @throws InvalidArgumentException If the number is negative or its factorial does not fit in an integer
     */
    public static function factorial(int $num): int
    {
        if ($num < 0) {
            throw new InvalidArgumentException('The number must be a non-negative integer.');
        }

        if ($num > self::MAX_FACTORIAL) {
            throw new InvalidArgumentException(
                sprintf('The factorial of %d does not fit in an integer (maximum: %d).', $num, self::MAX_FACTORIAL)
            );
        }

        return (int) self::product(...range(1, max($num, 1)));
    }

    /**
     * Returns the floating point remainder (modulo) of the division of the arguments
     *
     * @param float $num1 The dividend
     * @param float $num2 The divisor
     *
     * @return float The floating point remainder of x/y
     *
     * @link https://php.net/manual/function.bcmod.php
     * @link https://php.net/manual/function.gmp-mod.php
     * @link https://php.net/manual/function.fmod.php
     */
    public static function fmod(float $num1, float $num2): float
    {
        // Same result as the native function for the cases exact arithmetic cannot express
        if ($num2 == 0.0 || !is_finite($num1) || !is_finite($num2)) {
            return fmod($num1, $num2);
        }

        if (extension_loaded('bcmath')) {
            return (float) bcmod(self::toDecimalString($num1), self::toDecimalString($num2), self::BC_FLOAT_SCALE);
        }

        if (
            extension_loaded('gmp') &&
            floor(abs($num1)) === abs($num1) &&
            floor(abs($num2)) === abs($num2)
        ) {
            // GMP is integer-only; skip if either operand has a decimal part
            return (float) gmp_strval(gmp_mod((string) (int) $num1, (string) (int) $num2));
        }

        return fmod($num1, $num2);
    }

    /**
     * Checks if a number is prime
     *
     * @param int $num The number to check
     *
     * @return bool True if the number is prime, false otherwise
     */
    public static function isPrime(int $num): bool
    {
        if ($num < intval(2)) {
            return false;
        }

        $max = floor(sqrt($num));
        for ($i = intval(2); $i <= $max; $i++) {
            if ($num % $i === 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int|float ...$nums
     * @return float
     * @throws InvalidArgumentException If no numbers are provided
     */
    public static function median(int|float ...$nums): float
    {
        if ($nums === []) {
            throw new InvalidArgumentException('At least one number is required.');
        }

        $nums = array_values($nums);
        sort($nums);
        $n = count($nums) - 1;
        $middle = (int) ($n / intval(2));
        $low = $nums[$middle];
        if ($n % intval(2) === 0) {
            return $low;
        }
        return self::average($low, $nums[$middle + 1]);
    }

    /**
     * Returns the most frequent value(s), sorted. There is no mode (empty array)
     * when several distinct values all appear the same number of times.
     *
     * @param int|float ...$nums
     * @return array<int|float>
     */
    public static function mode(int|float ...$nums): array
    {
        $counts = [];
        $originals = [];
        foreach ($nums as $num) {
            $key = (string) $num; // floats cannot be array keys
            $counts[$key] = ($counts[$key] ?? 0) + 1;
            $originals[$key] ??= $num;
        }

        if ($counts === [] || (count($counts) > 1 && count(array_unique($counts)) === 1)) {
            return [];
        }

        $modes = [];
        foreach (array_keys($counts, max($counts), true) as $key) {
            $modes[] = $originals[$key];
        }
        sort($modes);

        return $modes;
    }

    /**
     * @param int|float ...$params
     * @return int|float
     */
    public static function product(int|float ...$params): int|float
    {
        return array_product($params);
    }

    /**
     * Rounds a number to the multiple
     *
     * @param int|float $num Number to round
     * @param int|float $multiple Multiple to round to
     * @param int $mode Rounding mode
     *
     * @return float Rounded number
     *
     * @throws InvalidArgumentException
     *
     * @example - Math::round(1.1) = 1
     * @example - Math::round(-1.5) = -2
     * @example - Math::round(1.1, 2) = 2
     * @example - Math::round(2.7, 0.25, Math::NEGATIVE_INFINITY) = 2.5
     * @example - Math::round(-2.7, 0.25, Math::NEGATIVE_INFINITY) = -2.75
     * @example - Math::round(2.5, 1, Math::HALF_EVEN) = 2
     */
    public static function round(
        int|float $num,
        int|float $multiple = 1,
        int $mode = self::HALF_AWAY_FROM_ZERO
    ): float {
        if ($multiple == 0) {
            throw new DivisionByZeroError('Division by zero');
        }

        // Nothing to round: infinity and NaN are returned as they are
        if (!is_finite($num) || !is_finite($multiple)) {
            return (float) $num;
        }

        return extension_loaded('bcmath')
            ? self::roundBc($num, $multiple, $mode)
            : self::roundFloat($num, $multiple, $mode);
    }

    /**
     * Float-based fallback for `round()`, used when the bcmath extension is not loaded.
     *
     * Ties ("is this exactly a half?") are detected with a fixed epsilon tolerance, which
     * cannot distinguish genuine binary floating-point drift from an intentional near-half
     * value at every magnitude. Prefer `roundBc()` whenever bcmath is available.
     */
    private static function roundFloat(int|float $num, int|float $multiple, int $mode): float
    {
        $scale = floatval(10) ** min(self::countDecimals((float) $num), self::countDecimals((float) $multiple));
        $multiple = self::abs($multiple) * $scale;
        $num = $num * $scale / $multiple;
        $signNum = self::sign($num);
        $absNum = self::abs($num);
        $isHalf = self::abs(self::fmod($absNum, 1) - floatval(0.5)) < self::EPSILON;
        $rounded = 0;

        switch ($mode) {
            case self::FLOOR: // also NEGATIVE_INFINITY
                $rounded = floor($num);
                break;
            case self::CEIL: // also POSITIVE_INFINITY
                $rounded = ceil($num);
                break;
            case self::TOWARDS_ZERO:
                $rounded = floor($absNum);
                break;
            case self::AWAY_FROM_ZERO:
                $rounded = ceil($absNum);
                break;
            case self::HALF_FLOOR: // also HALF_NEGATIVE_INFINITY
                $rounded = $isHalf ? $num - floatval(0.5) : round($num);
                break;
            case self::HALF_CEIL: // also HALF_POSITIVE_INFINITY
                $rounded = $isHalf ? $num + floatval(0.5) : round($num);
                break;
            case self::HALF_TOWARDS_ZERO:
                $rounded = $isHalf ? $absNum - floatval(0.5) : round($absNum);
                break;
            case self::HALF_EVEN:
                $rounded = ($isHalf ?
                    (self::fmod(floor($num), 2) === 0.0 ?
                        floor($num) :
                        ceil($num)) :
                    round($num));
                break;
            case self::HALF_ODD:
                $rounded = ($isHalf ?
                    (self::fmod(floor($num), 2) !== 0.0 ?
                        floor($num) :
                        ceil($num)) :
                    round($num));
                break;
            case self::HALF_AWAY_FROM_ZERO:
                $rounded = $isHalf ? $absNum + floatval(0.5) : round($absNum);
                break;
            default:
                throw new InvalidArgumentException('Invalid rounding mode');
        }

        $finalRoundedResult = self::abs($rounded) * $multiple / $scale;
        return $finalRoundedResult * $signNum;
    }

    /**
     * bcmath-based implementation of `round()`. All arithmetic happens in exact decimal
     * string space (never a binary float) until the final cast, so "is this exactly a
     * half?" is an exact comparison instead of an epsilon guess. This avoids both false
     * negatives (a genuine tie missed because of division drift) and false positives
     * (drift misread as a tie) that `roundFloat()` is structurally prone to.
     */
    private static function roundBc(int|float $num, int|float $multiple, int $mode): float
    {
        $decNum = self::countDecimals((float) $num);
        $decMult = self::countDecimals((float) $multiple);
        $guard = max($decNum, $decMult) + self::BC_GUARD_DIGITS;

        $numStr = number_format((float) $num, $decNum, '.', '');
        $multipleStr = number_format(self::abs((float) $multiple), $decMult, '.', '');

        // units = num / multiple, exact decimal division (truncated toward zero at $guard digits)
        $units = bcdiv($numStr, $multipleStr, $guard);
        $intPart = bcadd($units, '0', 0);
        $floorUnits = bccomp(bcsub($units, $intPart, $guard), '0', $guard) < 0
            ? bcsub($intPart, '1', 0)
            : $intPart;
        $frac = bcsub($units, $floorUnits, $guard); // always in [0, 1)
        $ceilUnits = bccomp($frac, '0', $guard) > 0 ? bcadd($floorUnits, '1', 0) : $floorUnits;

        $isPositive = bccomp($numStr, '0', $decNum) >= 0;
        $isHalf = bccomp($frac, '0.5', $guard - self::BC_HALF_SLACK) === 0;
        $nearest = bccomp($frac, '0.5', $guard) > 0 ? $ceilUnits : $floorUnits;
        $floorIsEven = bccomp(bcmod($floorUnits, '2'), '0', 0) === 0;

        $rounded = match ($mode) {
            self::FLOOR => $floorUnits, // also NEGATIVE_INFINITY
            self::CEIL => $ceilUnits, // also POSITIVE_INFINITY
            self::TOWARDS_ZERO => $intPart,
            self::AWAY_FROM_ZERO => $isPositive ? $ceilUnits : $floorUnits,
            self::HALF_FLOOR => $isHalf ? $floorUnits : $nearest, // also HALF_NEGATIVE_INFINITY
            self::HALF_CEIL => $isHalf ? $ceilUnits : $nearest, // also HALF_POSITIVE_INFINITY
            self::HALF_TOWARDS_ZERO => $isHalf ? ($isPositive ? $floorUnits : $ceilUnits) : $nearest,
            self::HALF_AWAY_FROM_ZERO => $isHalf ? ($isPositive ? $ceilUnits : $floorUnits) : $nearest,
            self::HALF_EVEN => $isHalf ? ($floorIsEven ? $floorUnits : $ceilUnits) : $nearest,
            self::HALF_ODD => $isHalf ? ($floorIsEven ? $ceilUnits : $floorUnits) : $nearest,
            default => throw new InvalidArgumentException('Invalid rounding mode'),
        };

        return (float) bcmul($rounded, $multipleStr, $decMult);
    }

    /**
     * Plain decimal notation of a finite float: bcmath rejects the scientific
     * notation PHP uses for very small or very large numbers ("1.0E-7").
     *
     * @return numeric-string
     */
    private static function toDecimalString(float $num): string
    {
        return number_format($num, self::countDecimals($num), '.', '');
    }

    /**
     * Calculates the standard deviation of a set of numbers.
     *
     * @param int|float ...$nums Variable number of numeric values
     *
     * @return float The standard deviation of the provided numbers
     * @throws InvalidArgumentException
     */
    public static function standardDeviation(int|float ...$nums): float
    {
        $average = self::average(...$nums);
        $variance = 0.0;
        foreach ($nums as $num) {
            $variance += ($num - $average) ** intval(2);
        }
        return self::sqrt($variance / count($nums));
    }

    /**
     * Returns 1 or -1, indicating the sign of the number passed as argument. If the input is 0 or -0,
     * it will be returned as-is.
     *
     * @param int|float $num The numeric value to process
     *
     * @return int 1 if the number is positive, -1 if it is negative, else 0.
     */
    public static function sign(int|float $num): int
    {
        return $num > 0 ? 1 : ($num < 0 ? -1 : 0);
    }

    /* ------------- */
    /* ------------- */
    /* --- ALIAS --- */
    /* ------------- */
    /* ------------- */

    /**
     * Alias of function `abs`
     *
     * @link https://php.net/manual/function.abs.php
     */
    public static function abs(int|float $num): int|float
    {
        return abs($num);
    }

    /**
     * Alias of function `acos`
     *
     * @link https://php.net/manual/function.acos.php
     */
    public static function acos(float $num): float
    {
        return acos($num);
    }

    /**
     * Alias of function `acosh`
     *
     * @link https://php.net/manual/function.acosh.php
     */
    public static function acosh(float $num): float
    {
        return acosh($num);
    }

    /**
     * Alias of function `asin`
     *
     * @link https://php.net/manual/function.asin.php
     */
    public static function asin(float $num): float
    {
        return asin($num);
    }

    /**
     * Alias of function `asinh`
     *
     * @link https://php.net/manual/function.asinh.php
     */
    public static function asinh(float $num): float
    {
        return asinh($num);
    }

    /**
     * Alias of function `atan2`
     *
     * @link https://php.net/manual/function.atan2.php
     */
    public static function atan2(float $y, float $x): float
    {
        return atan2($y, $x);
    }

    /**
     * Alias of function `atan`
     *
     * @link https://php.net/manual/function.atan.php
     */
    public static function atan(float $num): float
    {
        return atan($num);
    }

    /**
     * Alias of function `atanh`
     *
     * @link https://php.net/manual/function.atanh.php
     */
    public static function atanh(float $num): float
    {
        return atanh($num);
    }

    /**
     * Alias of method `Math::round(..., mode: Math::AWAY_FROM_ZERO)`
     *
     * @param int|float $num
     * @param int|float $multiple
     * @return float
     */
    public static function awayFromZero(int|float $num, int|float $multiple = 1): float
    {
        return self::round($num, $multiple, self::AWAY_FROM_ZERO);
    }

    /**
     * Alias of function `base_convert`
     *
     * @link https://php.net/manual/function.base-convert.php
     */
    public static function baseConvert(string $num, int $from_base, int $to_base): string
    {
        return base_convert($num, $from_base, $to_base);
    }

    /**
     * Alias of function `bindec`
     *
     * @link https://php.net/manual/function.bindec.php
     */
    public static function bindec(string $binary_string): int|float
    {
        return bindec($binary_string);
    }

    /**
     * Alias of method `Math::round(..., mode: Math::CEIL)`
     *
     * @param int|float $num
     * @param int|float $multiple
     * @return float
     */
    public static function ceil(int|float $num, int|float $multiple = 1): float
    {
        return self::round($num, $multiple, self::CEIL);
    }

    /**
     * Alias of function `cos`
     *
     * @link https://php.net/manual/function.cos.php
     */
    public static function cos(float $num): float
    {
        return cos($num);
    }

    /**
     * Alias of function `cosh`
     *
     * @link https://php.net/manual/function.cosh.php
     */
    public static function cosh(float $num): float
    {
        return cosh($num);
    }

    /**
     * Alias of function `decbin`
     *
     * @link https://php.net/manual/function.decbin.php
     */
    public static function decbin(int $num): string
    {
        return decbin($num);
    }

    /**
     * Alias of function `dechex`
     *
     * @link https://php.net/manual/function.dechex.php
     */
    public static function dechex(int $num): string
    {
        return dechex($num);
    }

    /**
     * Alias of function `decoct`
     *
     * @link https://php.net/manual/function.decoct.php
     */
    public static function decoct(int $num): string
    {
        return decoct($num);
    }

    /**
     * Alias of function `deg2rad`
     *
     * @link https://php.net/manual/function.deg2rad.php
     */
    public static function deg2rad(float $num): float
    {
        return deg2rad($num);
    }

    /**
     * Alias of function `exp`
     *
     * @link https://php.net/manual/function.exp.php
     */
    public static function exp(float $num): float
    {
        return exp($num);
    }

    /**
     * Alias of function `expm1`
     *
     * @link https://php.net/manual/function.expm1.php
     */
    public static function expm1(float $num): float
    {
        return expm1($num);
    }

    /**
     * Alias of function `fdiv`
     *
     * @link https://php.net/manual/function.fdiv.php
     */
    public static function fdiv(float $num1, float $num2): float
    {
        return fdiv($num1, $num2);
    }

    /**
     * Alias of method `Math::round(..., mode: Math::FLOOR)`
     *
     * @param int|float $num
     * @param int|float $multiple
     * @return float
     */
    public static function floor(int|float $num, int|float $multiple = 1): float
    {
        return self::round($num, $multiple, self::FLOOR);
    }

    /**
     * Alias of function `getrandmax`
     *
     * @link https://php.net/manual/function.getrandmax.php
     */
    public static function getrandmax(): int
    {
        return getrandmax();
    }

    /**
     * Alias of method `Math::round(..., mode: Math::HALF_AWAY_FROM_ZERO)`
     *
     * @param int|float $num
     * @param int|float $multiple
     * @return float
     */
    public static function halfAwayFromZero(int|float $num, int|float $multiple = 1): float
    {
        return self::round($num, $multiple, self::HALF_AWAY_FROM_ZERO);
    }

    /**
     * Alias of method `Math::round(..., mode: Math::HALF_CEIL)`
     *
     * @param int|float $num
     * @param int|float $multiple
     * @return float
     */
    public static function halfCeil(int|float $num, int|float $multiple = 1): float
    {
        return self::round($num, $multiple, self::HALF_CEIL);
    }

    /**
     * Alias of method `Math::round(..., mode: Math::HALF_EVEN)`
     *
     * @param int|float $num
     * @param int|float $multiple
     * @return float
     */
    public static function halfEven(int|float $num, int|float $multiple = 1): float
    {
        return self::round($num, $multiple, self::HALF_EVEN);
    }

    /**
     * Alias of method `Math::round(..., mode: Math::HALF_FLOOR)`
     *
     * @param int|float $num
     * @param int|float $multiple
     * @return float
     */
    public static function halfFloor(int|float $num, int|float $multiple = 1): float
    {
        return self::round($num, $multiple, self::HALF_FLOOR);
    }

    /**
     * Alias of method `Math::round(..., mode: Math::HALF_ODD)`
     *
     * @param int|float $num
     * @param int|float $multiple
     * @return float
     */
    public static function halfOdd(int|float $num, int|float $multiple = 1): float
    {
        return self::round($num, $multiple, self::HALF_ODD);
    }

    /**
     * Alias of method `Math::round(..., mode: Math::HALF_TOWARDS_ZERO)`
     *
     * @param int|float $num
     * @param int|float $multiple
     * @return float
     */
    public static function halfTowardsZero(int|float $num, int|float $multiple = 1): float
    {
        return self::round($num, $multiple, self::HALF_TOWARDS_ZERO);
    }

    /**
     * Alias of function `hexdec`
     *
     * @link https://php.net/manual/function.hexdec.php
     */
    public static function hexdec(string $hex_string): int|float
    {
        return hexdec($hex_string);
    }

    /**
     * Alias of function `hypot`
     *
     * @link https://php.net/manual/function.hypot.php
     */
    public static function hypot(float $x, float $y): float
    {
        return hypot($x, $y);
    }

    /**
     * Alias of function `intdiv`
     *
     * @link https://php.net/manual/function.intdiv.php
     */
    public static function intdiv(int $num1, int $num2): int
    {
        return intdiv($num1, $num2);
    }

    /**
     * Alias of function `is_finite`
     *
     * @link https://php.net/manual/function.is-finite.php
     */
    public static function isFinite(float $num): bool
    {
        return is_finite($num);
    }

    /**
     * Alias of function `is_infinite`
     *
     * @link https://php.net/manual/function.is-infinite.php
     */
    public static function isInfinite(float $num): bool
    {
        return is_infinite($num);
    }

    /**
     * Alias of function `is_nan`
     *
     * @link https://php.net/manual/function.is-nan.php
     */
    public static function isNan(float $num): bool
    {
        return is_nan($num);
    }

    /**
     * Alias of function `log10`
     *
     * @link https://php.net/manual/function.log10.php
     */
    public static function log10(float $num): float
    {
        return log10($num);
    }

    /**
     * Alias of function `log1p`
     *
     * @link https://php.net/manual/function.log1p.php
     */
    public static function log1p(float $num): float
    {
        return log1p($num);
    }

    /**
     * Alias of function `log`
     *
     * @link https://php.net/manual/function.log.php
     */
    public static function log(float $num, float $base = M_E): float
    {
        return log($num, $base);
    }

    /**
     * Alias of function `max`
     *
     * @link https://php.net/manual/function.max.php
     */
    public static function max(mixed ...$values): mixed
    {
        return max(...$values);
    }

    /**
     * Alias of function `min`
     *
     * @link https://php.net/manual/function.min.php
     */
    public static function min(mixed ...$values): mixed
    {
        return min(...$values);
    }

    /**
     * Alias of function `mt_getrandmax`
     *
     * @link https://php.net/manual/function.mt-getrandmax.php
     */
    public static function mtGetrandmax(): int
    {
        return mt_getrandmax();
    }

    /**
     * Alias of function `mt_rand`
     *
     * @link https://php.net/manual/function.mt-rand.php
     */
    public static function mtRand(int $min, int $max): int
    {
        return mt_rand($min, $max);
    }

    /**
     * Alias of function `mt_srand`
     *
     * @link https://php.net/manual/function.mt-srand.php
     */
    public static function mtSrand(int|null $seed = null, int $mode = MT_RAND_MT19937): void
    {
        mt_srand($seed, $mode);
    }

    /**
     * Alias of function `octdec`
     *
     * @link https://php.net/manual/function.octdec.php
     */
    public static function octdec(string $octal_string): int|float
    {
        return octdec($octal_string);
    }

    /**
     * Alias of function `pi`
     *
     * @link https://php.net/manual/function.pi.php
     */
    public static function pi(): float
    {
        return pi();
    }

    /**
     * Alias of function `pow`
     *
     * @link https://php.net/manual/function.pow.php
     */
    public static function pow(mixed $num, mixed $exponent): object|int|float
    {
        return pow($num, $exponent); // @phpstan-ignore argument.type, argument.type (FALSE POSITIVE)
    }

    /**
     * Alias of function `rad2deg`
     *
     * @link https://php.net/manual/function.rad2deg.php
     */
    public static function rad2deg(float $num): float
    {
        return rad2deg($num);
    }

    /**
     * Alias of function `rand`
     *
     * @link https://php.net/manual/function.rand.php
     */
    public static function rand(int $min, int $max): int
    {
        return rand($min, $max);
    }

    /**
     * Alias of function `sin`
     *
     * @link https://php.net/manual/function.sin.php
     */
    public static function sin(float $num): float
    {
        return sin($num);
    }

    /**
     * Alias of function `sinh`
     *
     * @link https://php.net/manual/function.sinh.php
     */
    public static function sinh(float $num): float
    {
        return sinh($num);
    }

    /**
     * Alias of function `sqrt`
     *
     * @link https://php.net/manual/function.sqrt.php
     */
    public static function sqrt(float $num): float
    {
        return sqrt($num);
    }

    /**
     * Alias of function `srand`
     *
     * @link https://php.net/manual/function.srand.php
     */
    public static function srand(int|null $seed = null, int $mode = MT_RAND_MT19937): void
    {
        srand($seed, $mode);
    }

    /**
     * Alias of function `tan`
     *
     * @link https://php.net/manual/function.tan.php
     */
    public static function tan(float $num): float
    {
        return tan($num);
    }

    /**
     * Alias of function `tanh`
     *
     * @link https://php.net/manual/function.tanh.php
     */
    public static function tanh(float $num): float
    {
        return tanh($num);
    }

    /**
     * Alias of method `Math::round(..., mode: Math::TOWARDS_ZERO)`
     *
     * @param int|float $num
     * @param int|float $multiple
     * @return float
     */
    public static function towardsZero(int|float $num, int|float $multiple = 1): float
    {
        return self::round($num, $multiple, self::TOWARDS_ZERO);
    }
}
