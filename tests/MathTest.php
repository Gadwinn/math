<?php

declare(strict_types=1);

namespace G\Math\Tests;

use DivisionByZeroError;
use PHPUnit\Framework\TestCase;
use G\Math\Math;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

class MathTest extends TestCase
{
    public function console($var)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        fwrite(STDERR, "\n-- {$trace['file']}:{$trace['line']}\n" . print_r($var, true) . "\n");
    }

    #[DataProvider('getAverageValues')]
    public function testAverage($expected, $nums)
    {
        $this->assertEquals($expected, Math::average(...$nums));
    }
    public static function getAverageValues()
    {
        return [
            [1, [1]],
            [1, [1, 1]],
            [1, [0, 2]],
            [0, [-2, 2]],
            [5, [0, 0, 5, 10, 10]],
        ];
    }

    #[DataProvider('getAngularDistanceValues')]
    public function testAngularDistance($expected, $num1, $num2)
    {
        $this->assertEquals($expected, Math::angularDistance($num1, $num2));
    }
    public static function getAngularDistanceValues()
    {
        return [
            [0.0, 0, 0],
            [20.0, 10, 350],
            [90.0, 45, 135],
            [180.0, 0, 180],
            [180.0, 270, 90],
            [10.0, 5, 355],
            [30.0, 15, 345],
            [70.0, 10, 80],
            [70.0, 80, 10],
            [0.0, 360, 0],
            [0.0, 720, 0],
            [90.0, -45, 45],
            [180.0, -90, 90],
            [45.0, 10.5, 55.5],
            [179.5, 0.25, 179.75],
        ];
    }

    #[DataProvider('getAngularAlignmentValues')]
    public function testAngularAlignment(int $expected, int|float $num1, int|float $num2, int|float $tolerance, bool $fullCircle = true): void
    {
        $this->assertSame($expected, Math::angularAlignment($num1, $num2, $tolerance, $fullCircle));
    }

    public static function getAngularAlignmentValues(): array
    {
        return [
            // fullCircle = true (default, 360° wrap)
            [Math::ALIGNMENT_PARALLEL, 0, 0, 0],
            [Math::ALIGNMENT_PARALLEL, 5, 355, 20],
            [Math::ALIGNMENT_PARALLEL, -10, 350, 0],
            [Math::ALIGNMENT_PARALLEL, 720, 0, 0],

            [Math::ALIGNMENT_ORTHOGONAL, 0, 90, 0],
            [Math::ALIGNMENT_ORTHOGONAL, 10, 80, 20],
            [Math::ALIGNMENT_ORTHOGONAL, -45, 45, 0],

            [Math::ALIGNMENT_OPPOSITE, 0, 180, 0],
            [Math::ALIGNMENT_OPPOSITE, 10, 170, 20],
            [Math::ALIGNMENT_OPPOSITE, 270, 90, 0],

            [Math::ALIGNMENT_OBLIQUE, 0, 45, 10],
            [Math::ALIGNMENT_OBLIQUE, 15, 60, 5],
            [Math::ALIGNMENT_OBLIQUE, 0, 100, 5],

            // fullCircle = false (toric / 180° wrap)
            [Math::ALIGNMENT_PARALLEL, 0, 0, 0, false],
            [Math::ALIGNMENT_PARALLEL, 0, 180, 0, false],    // 180° = same axis in toric mode
            [Math::ALIGNMENT_PARALLEL, 10, 190, 0, false],   // same
            [Math::ALIGNMENT_PARALLEL, 5, 185, 20, false],   // within tolerance
            [Math::ALIGNMENT_ORTHOGONAL, 0, 90, 0, false],
            [Math::ALIGNMENT_ORTHOGONAL, 10, 80, 20, false],
            [Math::ALIGNMENT_OBLIQUE, 0, 45, 10, false],     // OPPOSITE not evaluated → oblique
        ];
    }

    public function testAngularAlignmentThrowsOnNegativeTolerance(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tolerance must be a non-negative number.');

        Math::angularAlignment(0, 90, -1);
    }

    #[DataProvider('getRoundValues')]
    public function testRound($expected, $num, $multiple = 1, $mode = Math::HALF_AWAY_FROM_ZERO)
    {
        $this->assertEquals($expected, Math::round($num, $multiple, $mode));
    }

    /**
     * The float fallback used without bcmath, called directly so it is covered even
     * when the extension is loaded (as in the CI).
     */
    #[DataProvider('getRoundValues')]
    public function testRoundFloatFallback($expected, $num, $multiple = 1, $mode = Math::HALF_AWAY_FROM_ZERO)
    {
        $roundFloat = new \ReflectionMethod(Math::class, 'roundFloat');

        $this->assertEqualsWithDelta($expected, $roundFloat->invoke(null, $num, $multiple, $mode), 1e-9);
    }

    public function testRoundFloatFallbackInvalidMode(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new \ReflectionMethod(Math::class, 'roundFloat'))->invoke(null, 1.5, 1, 999);
    }
    public static function getRoundValues()
    {
        return [
            [10, 10],
            [10, 9.5],
            [10, 10.4],
            [10.5, 10, 1.5],
            [12, 11, 2],
            [12, 10.5, 2, Math::CEIL],
            [12, 10.5, 2, Math::AWAY_FROM_ZERO],
            [10, 11.5, 2, Math::FLOOR],
            [10, 11.5, 2, Math::TOWARDS_ZERO],
            [10, 10.5, 1, Math::HALF_TOWARDS_ZERO],
            [10, 9.5, 1, Math::HALF_AWAY_FROM_ZERO],
            [3.1415927, pi(), 1e-7],
            [1, 0.5000000000000001],
            // negatif
            [-10, -10],
            [-10, -9.5],
            [-10, -10.4],
            [-10.5, -10, 1.5],
            [-12, -11, 2],
            [-12, -10.5, 2, Math::FLOOR],
            [-10, -10.5, 2, Math::TOWARDS_ZERO],
            [-10, -11.5, 2, Math::CEIL],
            [-12, -11.5, 2, Math::AWAY_FROM_ZERO],
            [-10, -10.5, 1, Math::HALF_TOWARDS_ZERO],
            [-10, -9.5, 1, Math::HALF_AWAY_FROM_ZERO],
            [-3.1415927, -pi(), 1e-7],
            [-1, -0.5000000000000001],
            // wiki
            [18.68, 18.6837, 0.01],
            [3.5, 3.48, 0.1],
            [-14.38, -14.375, 0.01],
            [2, 2.5, 2],
            [8, 7.5, 2],
            [8.12, 8.125, 0.02],
            [-1.4, -1.35, 0.2],
            [2, 2.8, 1, Math::FLOOR],
            [3, 2.8],
            [6, 5.2, 1, Math::CEIL],
            [5, 5.2],
            // php.net #1
            [3, 3.4],
            [4, 3.5],
            [4, 3.6],
            [4, 3.6, 1],
            [5.05, 5.045, 0.01],
            [5.06, 5.055, 0.01],
            [300, 345, 100],
            [0, 345, 1000],
            [700, 678, 100],
            [1000, 678, 1000],
            // php.net #2
            [135.79, 135.79, 0.001],
            [135.79, 135.79, 0.01],
            [135.8, 135.79, 0.1],
            [136, 135.79, 1],
            [140, 135.79, 10],
            [100, 135.79, 100],
            [0, 135.79, 1000],
            // php.net #3
            [10, 9.5, 1, Math::HALF_AWAY_FROM_ZERO],
            [9, 9.5, 1, Math::HALF_TOWARDS_ZERO],
            [10, 9.5, 1, Math::HALF_EVEN],
            [9, 9.5, 1, Math::HALF_ODD],
            [9, 8.5, 1, Math::HALF_AWAY_FROM_ZERO],
            [8, 8.5, 1, Math::HALF_TOWARDS_ZERO],
            [8, 8.5, 1, Math::HALF_EVEN],
            [9, 8.5, 1, Math::HALF_ODD],
            // php.net #4
            [1.6, 1.55, 0.1, Math::HALF_AWAY_FROM_ZERO],
            [-1.6, -1.55, 0.1, Math::HALF_AWAY_FROM_ZERO],
            [1.5, 1.55, 0.1, Math::HALF_TOWARDS_ZERO],
            [-1.5, -1.55, 0.1, Math::HALF_TOWARDS_ZERO],
            [1.6, 1.55, 0.1, Math::HALF_EVEN],
            [-1.6, -1.55, 0.1, Math::HALF_EVEN],
            [1.5, 1.55, 0.1, Math::HALF_ODD],
            [-1.5, -1.55, 0.1, Math::HALF_ODD],
            // WIKI
            // FLOOR
            [1, 1.8, 1, Math::FLOOR],
            [1, 1.5, 1, Math::FLOOR],
            [1, 1.2, 1, Math::FLOOR],
            [0, 0.8, 1, Math::FLOOR],
            [0, 0.5, 1, Math::FLOOR],
            [0, 0.2, 1, Math::FLOOR],
            [-1, -0.2, 1, Math::FLOOR],
            [-1, -0.5, 1, Math::FLOOR],
            [-1, -0.8, 1, Math::FLOOR],
            [-2, -1.2, 1, Math::FLOOR],
            [-2, -1.5, 1, Math::FLOOR],
            [-2, -1.8, 1, Math::FLOOR],
            // CEIL
            [2, 1.8, 1, Math::CEIL],
            [2, 1.5, 1, Math::CEIL],
            [2, 1.2, 1, Math::CEIL],
            [1, 0.8, 1, Math::CEIL],
            [1, 0.5, 1, Math::CEIL],
            [1, 0.2, 1, Math::CEIL],
            [0, -0.2, 1, Math::CEIL],
            [0, -0.5, 1, Math::CEIL],
            [0, -0.8, 1, Math::CEIL],
            [-1, -1.2, 1, Math::CEIL],
            [-1, -1.5, 1, Math::CEIL],
            [-1, -1.8, 1, Math::CEIL],
            // TOWARDS_ZERO
            [1, 1.8, 1, Math::TOWARDS_ZERO],
            [1, 1.5, 1, Math::TOWARDS_ZERO],
            [1, 1.2, 1, Math::TOWARDS_ZERO],
            [0, 0.8, 1, Math::TOWARDS_ZERO],
            [0, 0.5, 1, Math::TOWARDS_ZERO],
            [0, 0.2, 1, Math::TOWARDS_ZERO],
            [0, -0.2, 1, Math::TOWARDS_ZERO],
            [0, -0.5, 1, Math::TOWARDS_ZERO],
            [0, -0.8, 1, Math::TOWARDS_ZERO],
            [-1, -1.2, 1, Math::TOWARDS_ZERO],
            [-1, -1.5, 1, Math::TOWARDS_ZERO],
            [-1, -1.8, 1, Math::TOWARDS_ZERO],
            // AWAY_FROM_ZERO
            [2, 1.8, 1, Math::AWAY_FROM_ZERO],
            [2, 1.5, 1, Math::AWAY_FROM_ZERO],
            [2, 1.2, 1, Math::AWAY_FROM_ZERO],
            [1, 0.8, 1, Math::AWAY_FROM_ZERO],
            [1, 0.5, 1, Math::AWAY_FROM_ZERO],
            [1, 0.2, 1, Math::AWAY_FROM_ZERO],
            [-1, -0.2, 1, Math::AWAY_FROM_ZERO],
            [-1, -0.5, 1, Math::AWAY_FROM_ZERO],
            [-1, -0.8, 1, Math::AWAY_FROM_ZERO],
            [-2, -1.2, 1, Math::AWAY_FROM_ZERO],
            [-2, -1.5, 1, Math::AWAY_FROM_ZERO],
            [-2, -1.8, 1, Math::AWAY_FROM_ZERO],
            // HALF_FLOOR
            [2, 1.8, 1, Math::HALF_FLOOR],
            [1, 1.5, 1, Math::HALF_FLOOR],
            [1, 1.2, 1, Math::HALF_FLOOR],
            [1, 0.8, 1, Math::HALF_FLOOR],
            [0, 0.5, 1, Math::HALF_FLOOR],
            [0, 0.2, 1, Math::HALF_FLOOR],
            [0, -0.2, 1, Math::HALF_FLOOR],
            [-1, -0.5, 1, Math::HALF_FLOOR],
            [-1, -0.8, 1, Math::HALF_FLOOR],
            [-1, -1.2, 1, Math::HALF_FLOOR],
            [-2, -1.5, 1, Math::HALF_FLOOR],
            [-2, -1.8, 1, Math::HALF_FLOOR],
            // HALF_CEIL
            [2, 1.8, 1, Math::HALF_CEIL],
            [2, 1.5, 1, Math::HALF_CEIL],
            [1, 1.2, 1, Math::HALF_CEIL],
            [1, 0.8, 1, Math::HALF_CEIL],
            [1, 0.5, 1, Math::HALF_CEIL],
            [0, 0.2, 1, Math::HALF_CEIL],
            [0, -0.2, 1, Math::HALF_CEIL],
            [0, -0.5, 1, Math::HALF_CEIL],
            [-1, -0.8, 1, Math::HALF_CEIL],
            [-1, -1.2, 1, Math::HALF_CEIL],
            [-1, -1.5, 1, Math::HALF_CEIL],
            [-2, -1.8, 1, Math::HALF_CEIL],
            // HALF_TOWARDS_ZERO
            [2, 1.8, 1, Math::HALF_TOWARDS_ZERO],
            [1, 1.5, 1, Math::HALF_TOWARDS_ZERO],
            [1, 1.2, 1, Math::HALF_TOWARDS_ZERO],
            [1, 0.8, 1, Math::HALF_TOWARDS_ZERO],
            [0, 0.5, 1, Math::HALF_TOWARDS_ZERO],
            [0, 0.2, 1, Math::HALF_TOWARDS_ZERO],
            [0, -0.2, 1, Math::HALF_TOWARDS_ZERO],
            [0, -0.5, 1, Math::HALF_TOWARDS_ZERO],
            [-1, -0.8, 1, Math::HALF_TOWARDS_ZERO],
            [-1, -1.2, 1, Math::HALF_TOWARDS_ZERO],
            [-1, -1.5, 1, Math::HALF_TOWARDS_ZERO],
            [-2, -1.8, 1, Math::HALF_TOWARDS_ZERO],
            // HALF_AWAY_FROM_ZERO
            [2, 1.8, 1, Math::HALF_AWAY_FROM_ZERO],
            [2, 1.5, 1, Math::HALF_AWAY_FROM_ZERO],
            [1, 1.2, 1, Math::HALF_AWAY_FROM_ZERO],
            [1, 0.8, 1, Math::HALF_AWAY_FROM_ZERO],
            [1, 0.5, 1, Math::HALF_AWAY_FROM_ZERO],
            [0, 0.2, 1, Math::HALF_AWAY_FROM_ZERO],
            [0, -0.2, 1, Math::HALF_AWAY_FROM_ZERO],
            [-1, -0.5, 1, Math::HALF_AWAY_FROM_ZERO],
            [-1, -0.8, 1, Math::HALF_AWAY_FROM_ZERO],
            [-1, -1.2, 1, Math::HALF_AWAY_FROM_ZERO],
            [-2, -1.5, 1, Math::HALF_AWAY_FROM_ZERO],
            [-2, -1.8, 1, Math::HALF_AWAY_FROM_ZERO],
            // HALF_EVEN
            [2, 1.8, 1, Math::HALF_EVEN],
            [2, 1.5, 1, Math::HALF_EVEN],
            [1, 1.2, 1, Math::HALF_EVEN],
            [1, 0.8, 1, Math::HALF_EVEN],
            [0, 0.5, 1, Math::HALF_EVEN],
            [0, 0.2, 1, Math::HALF_EVEN],
            [0, -0.2, 1, Math::HALF_EVEN],
            [0, -0.5, 1, Math::HALF_EVEN],
            [-1, -0.8, 1, Math::HALF_EVEN],
            [-1, -1.2, 1, Math::HALF_EVEN],
            [-2, -1.5, 1, Math::HALF_EVEN],
            [-2, -1.8, 1, Math::HALF_EVEN],
            // HALF_ODD
            [2, 1.8, 1, Math::HALF_ODD],
            [1, 1.5, 1, Math::HALF_ODD],
            [1, 1.2, 1, Math::HALF_ODD],
            [1, 0.8, 1, Math::HALF_ODD],
            [1, 0.5, 1, Math::HALF_ODD],
            [0, 0.2, 1, Math::HALF_ODD],
            [0, -0.2, 1, Math::HALF_ODD],
            [-1, -0.5, 1, Math::HALF_ODD],
            [-1, -0.8, 1, Math::HALF_ODD],
            [-1, -1.2, 1, Math::HALF_ODD],
            [-1, -1.5, 1, Math::HALF_ODD],
            [-2, -1.8, 1, Math::HALF_ODD],
            // Multiple de 3
            [3, 1.8, 3],
            [3, 1.5, 3],
            [0, 1.2, 3],
            [0, 0.8, 3],
            [0, -0.8, 3],
            [0, -1.2, 3],
            [-3, -1.5, 3],
            [-3, -1.8, 3],
            // Multiple de 0.2
            [1.8, 1.8, 0.2],
            [1.6, 1.5, 0.2],
            [1.2, 1.2, 0.2],
            [0.8, 0.8, 0.2],
            [0.6, 0.5, 0.2],
            [0.2, 0.2, 0.2],
            [-0.2, -0.2, 0.2],
            [-0.6, -0.5, 0.2],
            [-0.8, -0.8, 0.2],
            [-1.2, -1.2, 0.2],
            [-1.6, -1.5, 0.2],
            [-1.8, -1.8, 0.2],
            // Approximation errors
            [0.4, 0.3, 0.2, Math::HALF_AWAY_FROM_ZERO],
            [10.6, 10.6, 0.2, Math::TOWARDS_ZERO],
            [0.3, (0.2 * 10 + 0.1 * 10) / 10, 0.01, Math::AWAY_FROM_ZERO], // Correction of 0.2 + 0.1
        ];
    }

    public function testRoundDivisionByZeroError()
    {
        $this->expectException(DivisionByZeroError::class);
        Math::round(1, 0);
    }

    public function testRoundInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);
        Math::round(1, 1, PHP_INT_MIN);
    }

    #[DataProvider('getMedianValues')]
    public function testMedian($expected, $nums)
    {
        $this->assertEquals($expected, Math::median(...$nums));
    }
    public static function getMedianValues()
    {
        return [
            [1, [1]],
            [1, [0, 2]],
            [2, [-2, 2, 4]],
            [5, [0, 5, 5, 10, 10]],
            [1.5, [0, 1, 2, 50]],
            [40, [50, 90, 0, 40, 20]],
            [5, [1, 6, 4, 7, 9, 0]],
        ];
    }

    #[DataProvider('getModeValues')]
    public function testMode($expected, $nums)
    {
        $this->assertEquals($expected, Math::mode(...$nums));
    }
    public static function getModeValues()
    {
        return [
            [[], [1, 2, 3, 4, 5]],
            [[8], [7, 6, 8, 2, 8, 4]],
            [[21], [18, 21, 35, 21, 50, 22, 28, 21, 36, 22, 35]],
            [[50], [48, 50, 44, 48, 50, 48, 56, 54, 50, 62, 50]],
            [[9], [9, 2, 6, 4, 6, 2, 9, 3, 9, 4, 7, 8]],
            [[21, 28], [19, 28, 21, 20, 28, 21, 20, 35, 21, 28]],
        ];
    }

    #[DataProvider('getStandardDeviationValues')]
    public function testStandardDeviation($expected, $nums)
    {
        $this->assertEquals($expected, round(Math::standardDeviation(...$nums), 5));
    }
    public static function getStandardDeviationValues()
    {
        return [
            [1.85472, [2, 3, 5, 6, 7]],
            [1.87083, [6, 2, 3, 1]],
            [2.28035, [1, 4, 7, 2, 6]],
            [3.72022, [2, 7, 3, 12, 9]],
        ];
    }

    #[DataProvider('getSignValues')]
    public function testSign($expected, $num)
    {
        $this->assertEquals($expected, Math::sign($num));
    }
    public static function getSignValues()
    {
        return [
            [1, 1],
            [-1, -1],
            [0, 0],
            [-0, -0],
            [1, 11.5],
            [-1, -11.5],
        ];
    }

    #[DataProvider('getFmodValues')]
    public function testFmod($expected, $num, $mod)
    {
        $this->assertEquals($expected, Math::fmod($num, $mod));
    }
    public static function getFmodValues()
    {
        return [
            [0, 5, 5],
            [0, 5, 1],
            [1, 5, 2],
            [2, 5, 3],
            [1, 5, 4],
            [0.1, 1.5, 1.4],
            [1.5, 5.5, 2],
            [0.5, 5.5, 1],
            [0.25, 5.25, 1],
            [0.75, 5.75, 1],
        ];
    }

    #[DataProvider('getIsPrimeValues')]
    public function testIsPrime($expected, $num)
    {
        $this->assertEquals($expected, Math::isPrime($num));
    }

    public static function getIsPrimeValues()
    {
        return [
            // TRUE
            [true, 2],
            [true, 3],
            [true, 5],
            [true, 7],
            [true, 11],
            [true, 13],
            [true, 65537],
            [true, 123456789133471],
            // FALSE
            [false, -1],
            [false, 0],
            [false, 1],
            [false, 4],
            [false, 9],
            [false, 100],
            [false, 1000],
            [false, 123456789133470],
        ];
    }

    #[DataProvider('getClampValues')]
    public function testClamp($expected, $min, $preferred, $max)
    {
        $this->assertEquals($expected, Math::clamp($min, $preferred, $max));
    }
    public static function getClampValues()
    {
        return [
            // Within range
            [5, 0, 5, 10],
            [5.5, 0.0, 5.5, 10.0],

            // Below min → returns min
            [0, 0, -1, 10],
            [-5, -5, -10, 0],

            // Above max → returns max
            [10, 0, 15, 10],
            [100, 0, 200, 100],

            // Equal to bounds
            [0, 0, 0, 10],
            [10, 0, 10, 10],

            // Floats
            [1.5, 1.0, 1.5, 2.0],
            [1.0, 1.0, 0.5, 2.0],
            [2.0, 1.0, 2.5, 2.0],

            // Negative range
            [-3, -5, -3, 0],
            [-5, -5, -7, 0],
            [0, -5, 2, 0],

            // Same min/max
            [5, 5, 3, 5],
            [5, 5, 5, 5],
            [5, 5, 8, 5],
        ];
    }

    #[DataProvider('getClosestValues')]
    public function testClosest($expected, $target, $values)
    {
        $this->assertEquals($expected, Math::closest($target, $values));
    }

    public static function getClosestValues()
    {
        return [
            // Basic cases
            [10, 12.5, [0, 2, 5, 10, 20, 25, 28, 30]],
            [10, 12.5, [30, 28, 25, 20, 10, 5, 2, 0]],
            [5, 4.9, [0, 2, 5, 10, 20, 25, 28, 30]],
            [2, 1.5, [0, 2, 5, 10, 20, 25, 28, 30]],
            [0, 0.1, [0, 2, 5, 10, 20, 25, 28, 30]],

            // Out of bounds
            [30, 100, [0, 2, 5, 10, 20, 25, 28, 30]],
            [0, -100, [0, 2, 5, 10, 20, 25, 28, 30]],

            // Exact matches
            [5, 5, [0, 2, 5, 10, 20, 25, 28, 30]],
            [20, 20, [0, 2, 5, 10, 20, 25, 28, 30]],

            // Single value array
            [42, 10, [42]],
            [42, 50, [42]],

            // Negative values
            [-5, -4.8, [-10, -5, 0, 5, 10]],
            [-10, -12, [-10, -5, 0, 5, 10]],
            [0, 0.2, [-10, -5, 0, 5, 10]],
            [5, 6, [-10, -5, 0, 5, 10]],

            // Float values
            [1.5, 1.4, [1.0, 1.5, 2.0, 2.5]],
            [2.5, 2.7, [1.0, 1.5, 2.0, 2.5]],

            // Equal distances (should return larger value)
            [10, 7.5, [5, 10]],
            [10, 7.5, [10, 5]],
            [3, 2, [1, 3]],

            // Large numbers
            [1000000, 999999, [500000, 1000000, 1500000]],
            [1500000, 1400000, [500000, 1000000, 1500000]],

            // Very small differences
            [1.002, 1.0015, [1.000, 1.001, 1.002]],
            [1.002, 1.0025, [1.000, 1.001, 1.002]],

            // Messy values
            [3, 2.8, [2, 5, -4, 3, -1]],
            [10, 12.5, [-5, 520, 42, 10, -53.25, 7.99, 20, -100]],
        ];
    }

    public function testClosestEmptyArrayException()
    {
        $this->expectException(InvalidArgumentException::class);
        Math::closest(5, []);
    }

    #[DataProvider('getFactorialValues')]
    public function testFactorial($expected, $input)
    {
        $this->assertEquals($expected, Math::factorial($input));
    }
    public static function getFactorialValues(): array
    {
        return [
            [1, 0],
            [1, 1],
            [2, 2],
            [6, 3],
            [24, 4],
            [120, 5],
            [720, 6],
            [5040, 7],
            [40320, 8],
            [362880, 9],
            [3628800, 10],
        ];
    }

    public function testFactorialError()
    {
        $this->expectException(InvalidArgumentException::class);
        Math::factorial(-1);
    }

    #[DataProvider('getProductValues')]
    public function testProduct($expected, $input)
    {
        $this->assertEquals($expected, Math::product(...$input));
    }
    public static function getProductValues()
    {
        return [
            [1, []],
            [1, [1]],
            [2, [1, 2]],
            [6, range(1, 3)],
            [24, range(1, 4)],
            [120, range(1, 5)],
            [720, range(1, 6)],
            [5040, range(1, 7)],
            [40320, range(1, 8)],
            [362880, range(1, 9)],
            [3628800, range(1, 10)],
        ];
    }

    #[DataProvider('getCountDecimalsValues')]
    public function testCountDecimals($expected, $num)
    {
        $this->assertEquals($expected, Math::countDecimals($num));
    }
    public static function getCountDecimalsValues()
    {
        return [
            [0, 1],
            [0, 100],
            [0, 100.0],
            [1, 1.1],
            [1, 1.10],
            [2, 1.11],
            [3, 1.111],
            [4, 1.1111],
            [5, 1.11111],
            [6, 1.111111],
            [7, 1.1111111],
            [8, 1.11111111],
            // Negative numbers
            [3, -1.123],
            [4, -1.1234],
            // Scientific notation
            [0, 1e3],
            [3, 1e-3],
            [0, 1.234e3],
            [8, 1.23456e-3],
        ];
    }

    /* ------------- */
    /* ------------- */
    /* --- ALIAS --- */
    /* ------------- */
    /* ------------- */

    public function testAlias()
    {
        $num = $multiple = $num1 = $num2 = 1;
        $x = $y = 0.5;
        $values = [1, 2, 3];
        $min = $max = 1;
        $base = $from_base = $to_base = 10;
        $exponent = 2;
        $binary_string = '1010';
        $hex_string = 'A';
        $octal_string = '12';
        $seed = 12345;
        $mode = MT_RAND_MT19937;

        $this->assertEquals(Math::abs($num), abs($num));
        $this->assertEquals(Math::acos($num), acos($num));
        $this->assertEquals(Math::acosh($num), acosh($num));
        $this->assertEquals(Math::asin($num), asin($num));
        $this->assertEquals(Math::asinh($num), asinh($num));
        $this->assertEquals(Math::atan2($y, $x), atan2($y, $x));
        $this->assertEquals(Math::atan($num), atan($num));
        $this->assertEquals(Math::atanh($num), atanh($num));
        $this->assertEquals(Math::awayFromZero($num, $multiple), Math::round($num, $multiple, Math::AWAY_FROM_ZERO));
        $this->assertEquals(Math::baseConvert((string) $num, $from_base, $to_base), base_convert((string) $num, $from_base, $to_base));
        $this->assertEquals(Math::bindec($binary_string), bindec($binary_string));
        $this->assertEquals(Math::ceil($num, $multiple), Math::round($num, $multiple, Math::CEIL));
        $this->assertEquals(Math::cos($num), cos($num));
        $this->assertEquals(Math::cosh($num), cosh($num));
        $this->assertEquals(Math::decbin($num), decbin($num));
        $this->assertEquals(Math::dechex($num), dechex($num));
        $this->assertEquals(Math::decoct($num), decoct($num));
        $this->assertEquals(Math::deg2rad($num), deg2rad($num));
        $this->assertEquals(Math::exp($num), exp($num));
        $this->assertEquals(Math::expm1($num), expm1($num));
        $this->assertEquals(Math::fdiv($num1, $num2), fdiv($num1, $num2));
        $this->assertEquals(Math::floor($num, $multiple), Math::round($num, $multiple, Math::FLOOR));
        $this->assertEquals(Math::getrandmax(), getrandmax());
        $this->assertEquals(Math::halfAwayFromZero($num, $multiple), Math::round($num, $multiple, Math::HALF_AWAY_FROM_ZERO));
        $this->assertEquals(Math::halfCeil($num, $multiple), Math::round($num, $multiple, Math::HALF_CEIL));
        $this->assertEquals(Math::halfEven($num, $multiple), Math::round($num, $multiple, Math::HALF_EVEN));
        $this->assertEquals(Math::halfFloor($num, $multiple), Math::round($num, $multiple, Math::HALF_FLOOR));
        $this->assertEquals(Math::halfOdd($num, $multiple), Math::round($num, $multiple, Math::HALF_ODD));
        $this->assertEquals(Math::halfTowardsZero($num, $multiple), Math::round($num, $multiple, Math::HALF_TOWARDS_ZERO));
        $this->assertEquals(Math::hexdec($hex_string), hexdec($hex_string));
        $this->assertEquals(Math::hypot($x, $y), hypot($x, $y));
        $this->assertEquals(Math::intdiv($num1, $num2), intdiv($num1, $num2));
        $this->assertEquals(Math::isFinite($num), is_finite($num));
        $this->assertEquals(Math::isInfinite($num), is_infinite($num));
        $this->assertEquals(Math::isNan($num), is_nan($num));
        $this->assertEquals(Math::log10($num), log10($num));
        $this->assertEquals(Math::log1p($num), log1p($num));
        $this->assertEquals(Math::log($num, $base), log($num, $base));
        $this->assertEquals(Math::max(...$values), max(...$values));
        $this->assertEquals(Math::min(...$values), min(...$values));
        $this->assertEquals(Math::mtGetrandmax(), mt_getrandmax());
        $this->assertEquals(Math::mtSrand($seed, $mode), mt_srand($seed, $mode));
        $this->assertEquals(Math::mtRand($min, $max), mt_rand($min, $max));
        $this->assertEquals(Math::octdec($octal_string), octdec($octal_string));
        $this->assertEquals(Math::pi(), pi());
        $this->assertEquals(Math::pow($num, $exponent), pow($num, $exponent));
        $this->assertEquals(Math::rad2deg($num), rad2deg($num));
        $this->assertEquals(Math::srand($seed, $mode), srand($seed, $mode));
        $this->assertEquals(Math::rand($min, $max), rand($min, $max));
        $this->assertEquals(Math::sin($num), sin($num));
        $this->assertEquals(Math::sinh($num), sinh($num));
        $this->assertEquals(Math::sqrt($num), sqrt($num));
        $this->assertEquals(Math::tan($num), tan($num));
        $this->assertEquals(Math::tanh($num), tanh($num));
        $this->assertEquals(Math::towardsZero($num, $multiple), Math::round($num, $multiple, Math::TOWARDS_ZERO));
    }

    // -------------------------------------------------------------------------
    // Edge cases
    // -------------------------------------------------------------------------

    public function testMedianRequiresAtLeastOneNumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Math::median();
    }

    public function testClosestAcceptsAnyArrayKeys(): void
    {
        $this->assertSame(4, Math::closest(3, ['a' => 1, 'b' => 4]));
        $this->assertSame(10, Math::closest(12.5, [5 => 0, 9 => 10, 12 => 20]));
    }

    public function testFactorialRefusesAResultThatDoesNotFitInAnInteger(): void
    {
        $this->assertSame(2432902008176640000, Math::factorial(20));

        $this->expectException(\InvalidArgumentException::class);

        Math::factorial(21);
    }

    public function testFmodHandlesScientificNotationZeroAndNonFiniteValues(): void
    {
        $this->assertSame(0.0, Math::fmod(1.0, 1.0E-7));
        $this->assertEqualsWithDelta(0.0, Math::fmod(0.3, 0.1), 1e-12);
        $this->assertSame(1.5, Math::fmod(7.5, 2.0));
        $this->assertNan(Math::fmod(1.0, 0.0));
        $this->assertNan(Math::fmod(INF, 2.0));
        $this->assertSame(1.0, Math::fmod(1.0, INF));
    }

    public function testModeWorksWithFloatsAndASingleValue(): void
    {
        $this->assertSame([1.5], Math::mode(1.5, 1.5, 2.5));
        $this->assertSame([5], Math::mode(5, 5, 5));
        $this->assertSame([], Math::mode());
        $this->assertSame([], Math::mode(1.5, 2.5));
        $this->assertSame([1, 2.5], Math::mode(2.5, 1, 2.5, 1, 7));
    }

    public function testCountDecimalsUnderstandsScientificNotation(): void
    {
        $this->assertSame(7, Math::countDecimals(1.0E-7));
        $this->assertSame(8, Math::countDecimals(1.5E-7));
        $this->assertSame(0, Math::countDecimals(1.0E+25));
        $this->assertSame(2, Math::countDecimals(1.25));
        $this->assertSame(0, Math::countDecimals(3.0));
        $this->assertSame(0, Math::countDecimals(INF));
    }

    public function testRoundReturnsNonFiniteNumbersAsTheyAre(): void
    {
        $this->assertSame(INF, Math::round(INF));
        $this->assertSame(-INF, Math::round(-INF, 0.5));
        $this->assertNan(Math::round(NAN));
    }

    public function testRoundWithVerySmallMultiple(): void
    {
        $this->assertEqualsWithDelta(0.0000012, Math::round(0.00000123, 0.0000001), 1e-15);
    }
}
