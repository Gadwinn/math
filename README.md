# Math

A static utility class wrapping and extending PHP's native math functions. Provides advanced rounding (10 modes, arbitrary multiples), statistical helpers, angular geometry, and a unified API surface over `ext-bcmath` / `ext-gmp` / native fallbacks.

## Tech Stack

- **PHP** `>= 8.3`
- **Extensions** (optional, auto-detected): `ext-bcmath` (preferred), `ext-gmp`
- **No production dependencies**

## Installation

```bash
composer require gadwinn/math
```

## Usage

```php
use G\Math\Math;
```

All methods are static. No instantiation required.

---

### Rounding

`round()` supports **10 rounding modes** and rounds to an **arbitrary multiple** (not just decimal places).

```php
// Signature
Math::round(int|float $num, int|float $multiple = 1, int $mode = Math::HALF_AWAY_FROM_ZERO): float
```

```php
Math::round(18.6837, 0.01);                         // 18.68
Math::round(678, 100);                              // 700
Math::round(2.7, 0.25, Math::FLOOR);                // 2.5
Math::round(-2.7, 0.25, Math::NEGATIVE_INFINITY);   // -2.75
Math::round(2.5, 1, Math::HALF_EVEN);               // 2  (banker's rounding)
Math::round(3.48, 0.1);                             // 3.5
Math::round(0.3, 0.01, Math::AWAY_FROM_ZERO);       // 0.3  (corrects float drift)
```

#### Rounding mode constants

| Constant | Alias | Behavior |
|---|---|---|
| `Math::FLOOR` | `Math::NEGATIVE_INFINITY` | Towards −∞ |
| `Math::CEIL` | `Math::POSITIVE_INFINITY` | Towards +∞ |
| `Math::TOWARDS_ZERO` | | Truncate |
| `Math::AWAY_FROM_ZERO` | | Away from zero |
| `Math::HALF_FLOOR` | `Math::HALF_NEGATIVE_INFINITY` | Half → −∞ |
| `Math::HALF_CEIL` | `Math::HALF_POSITIVE_INFINITY` | Half → +∞ |
| `Math::HALF_TOWARDS_ZERO` | | Half → zero |
| `Math::HALF_AWAY_FROM_ZERO` | | Half away from zero *(default)* |
| `Math::HALF_EVEN` | | Banker's rounding |
| `Math::HALF_ODD` | | Half → nearest odd |

#### Rounding shorthand aliases

Each mode has a dedicated method accepting the same `($num, $multiple = 1)` signature:

```php
Math::floor(11.7, 2);          // 10.0
Math::ceil(10.5, 2);           // 12.0
Math::halfEven(2.5);           // 2.0
Math::halfOdd(2.5);            // 3.0
Math::towardsZero(-1.8);       // -1.0
Math::awayFromZero(-1.2);      // -2.0
Math::halfTowardsZero(9.5);    // 9.0
Math::halfAwayFromZero(9.5);   // 10.0
Math::halfCeil(-1.5);          // -1.0
Math::halfFloor(1.5);          // 1.0
```

> `round($n, 0)` throws `\DivisionByZeroError`.

---

### Statistics

```php
Math::average(2, 4, 6, 8);                      // 5.0
Math::median(1, 6, 4, 7, 9, 0);                 // 5.0
Math::mode(7, 6, 8, 2, 8, 4);                   // [8]
Math::mode(19, 28, 21, 20, 28, 21);             // [21, 28]
Math::mode(1, 2, 3, 4, 5);                      // []  (no mode)
Math::standardDeviation(2, 3, 5, 6, 7);         // 1.854...
Math::product(1, 2, 3, 4, 5);                   // 120
Math::factorial(10);                            // 3628800  (up to 20; beyond, the result does not fit in an integer and an exception is thrown)
```

---

### Angular geometry

```php
// Shortest arc between two angles in degrees [0, 180]
Math::angularDistance(10, 350);    // 20.0
Math::angularDistance(270, 90);    // 180.0
Math::angularDistance(720, 0);     // 0.0   (normalises multiples of 360)

// Classify the relationship between two axes
// $tolerance: acceptable margin of error in degrees
// $fullCircle: true = 360° wrap (vectors), false = 180° wrap (lines/orientations)
Math::angularAlignment(0, 90, tolerance: 0, fullCircle: true);     // Math::ALIGNMENT_ORTHOGONAL (90)
Math::angularAlignment(5, 355, tolerance: 20);                     // Math::ALIGNMENT_PARALLEL   (0)
Math::angularAlignment(10, 170, tolerance: 20);                    // Math::ALIGNMENT_OPPOSITE   (180)
Math::angularAlignment(0, 180, tolerance: 0, fullCircle: false);   // Math::ALIGNMENT_PARALLEL   (0)
Math::angularAlignment(0, 45, tolerance: 10);                      // Math::ALIGNMENT_OBLIQUE    (45)
```

#### Alignment constants

| Constant | Value | Meaning |
|---|---|---|
| `Math::ALIGNMENT_PARALLEL` | `0` | 0° — same direction |
| `Math::ALIGNMENT_OBLIQUE` | `45` | Oblique |
| `Math::ALIGNMENT_ORTHOGONAL` | `90` | 90° — perpendicular |
| `Math::ALIGNMENT_OPPOSITE` | `180` | 180° — opposite |

---

### Utility

```php
Math::clamp(0, 5, 10);       // 5    (within range → preferred)
Math::clamp(0, -1, 10);      // 0    (below min → min)
Math::clamp(0, 15, 10);      // 10   (above max → max)
Math::clamp(1.0, 1.5, 2.0);  // 1.5  (floats)

Math::closest(12.5, [0, 2, 5, 10, 20, 25]);    // 10   (ties → larger value)
Math::closest(7.5, [5, 10]);                    // 10

Math::sign(-42);       // -1
Math::sign(0);         //  0
Math::sign(3.14);      //  1

Math::isPrime(65537);              // true
Math::isPrime(100);                // false

Math::countDecimals(3.1415927);    // 7
Math::countDecimals(100.0);        // 0

Math::fmod(5.5, 2.0);             // 1.5  (bcmath → gmp → native fallback)
```

---

### PHP math function aliases

`Math` wraps every native PHP math function under a unified static API (camelCase naming):

```php
Math::abs(-5);                          // 5
Math::sqrt(144.0);                      // 12.0
Math::pow(2, 10);                       // 1024
Math::log(M_E);                         // 1.0
Math::log10(1000.0);                    // 3.0
Math::hypot(3.0, 4.0);                  // 5.0
Math::intdiv(7, 2);                     // 3
Math::fdiv(1.0, 0.0);                   // INF  (no exception)
Math::pi();                             // 3.141592...
Math::deg2rad(180.0);                   // π
Math::rad2deg(M_PI);                    // 180.0
Math::sin(M_PI / 2);                    // 1.0
Math::cos(0.0);                         // 1.0
Math::isNan(sqrt(-1.0));                // true
Math::isFinite(INF);                    // false
Math::isInfinite(INF);                  // true
Math::decbin(255);                      // "11111111"
Math::bindec('11111111');               // 255
Math::dechex(255);                      // "ff"
Math::hexdec('ff');                     // 255
Math::baseConvert('ff', 16, 2);         // "11111111"
Math::rand(1, 100);                     // random int in [1, 100]
Math::mtRand(1, 100);                   // Mersenne Twister random int
```

Full alias list: `abs`, `acos`, `acosh`, `asin`, `asinh`, `atan`, `atan2`, `atanh`, `baseConvert`, `bindec`, `cos`, `cosh`, `decbin`, `dechex`, `decoct`, `deg2rad`, `exp`, `expm1`, `fdiv`, `floor`*, `ceil`*, `getrandmax`, `hexdec`, `hypot`, `intdiv`, `isFinite`, `isInfinite`, `isNan`, `log`, `log10`, `log1p`, `max`, `min`, `mtGetrandmax`, `mtRand`, `mtSrand`, `octdec`, `pi`, `pow`, `rad2deg`, `rand`, `sin`, `sinh`, `sqrt`, `srand`, `tan`, `tanh`.

> Methods marked `*` are enhanced aliases of `Math::round()` and accept a `$multiple` parameter.

## License

MIT, see [LICENSE](LICENSE).
