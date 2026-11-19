<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * ============================================================================
 * SERVICE: PURE PHP ISO/IEC 18004 QR CODE SVG GENERATOR (QrCodeSvg)
 * ============================================================================
 * Menghasilkan gambar vektor QR Code SVG murni tanpa ketergantungan library
 * eksternal atau API pihak ketiga (bekerja 100% offline & siap cetak/print).
 * Mendukung Byte Mode, Error Correction Level M, Versi 1 s/d 10 (hingga 213 byte).
 * ============================================================================
 */
class QrCodeSvg
{
    private const ALIGNMENT_PATTERNS = [
        1  => [],
        2  => [6, 18],
        3  => [6, 22],
        4  => [6, 26],
        5  => [6, 30],
        6  => [6, 34],
        7  => [6, 22, 38],
        8  => [6, 24, 42],
        9  => [6, 26, 46],
        10 => [6, 28, 50],
    ];

    /**
     * Spesifikasi ECC Level M per versi:
     * [totalCodewords, ecPerBlock, [[numBlocks, dataPerBlock], ...], remainderBits]
     */
    private const VERSION_SPECS_M = [
        1  => [26, 10, [[1, 16]], 0],
        2  => [44, 16, [[1, 28]], 7],
        3  => [70, 26, [[1, 44]], 7],
        4  => [100, 18, [[2, 32]], 7],
        5  => [134, 24, [[2, 43]], 7],
        6  => [172, 16, [[4, 27]], 7],
        7  => [196, 18, [[4, 31]], 0],
        8  => [242, 22, [[2, 38], [2, 39]], 0],
        9  => [292, 22, [[3, 36], [2, 37]], 0],
        10 => [346, 26, [[4, 43], [1, 44]], 0],
    ];

    /**
     * Menghasilkan markup <svg>...</svg> yang siap ditampilkan atau dicetak.
     */
    public static function render(string $text, int $size = 220, int $quietZone = 3): string
    {
        $matrix = self::buildMatrix($text);
        $dim = count($matrix);
        $totalModules = $dim + ($quietZone * 2);

        $paths = [];
        for ($r = 0; $r < $dim; $r++) {
            $runStart = null;
            for ($c = 0; $c < $dim; $c++) {
                if ($matrix[$r][$c]) {
                    if ($runStart === null) {
                        $runStart = $c;
                    }
                } elseif ($runStart !== null) {
                    $w = $c - $runStart;
                    $x = $runStart + $quietZone;
                    $y = $r + $quietZone;
                    $paths[] = "M{$x},{$y}h{$w}v1h-{$w}z";
                    $runStart = null;
                }
            }
            if ($runStart !== null) {
                $w = $dim - $runStart;
                $x = $runStart + $quietZone;
                $y = $r + $quietZone;
                $paths[] = "M{$x},{$y}h{$w}v1h-{$w}z";
            }
        }

        $d = implode('', $paths);

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $totalModules . ' ' . $totalModules . '" width="' . $size . '" height="' . $size . '" shape-rendering="crispEdges" role="img" aria-label="QR Code">'
            . '<rect width="' . $totalModules . '" height="' . $totalModules . '" fill="#ffffff"/>'
            . '<path d="' . $d . '" fill="#0F291E"/>'
            . '</svg>';
    }

    /**
     * Membangun matriks boolean QR Code untuk teks input.
     *
     * @return array<int, array<int, bool>>
     */
    public static function buildMatrix(string $text): array
    {
        $bytes = array_values(unpack('C*', $text) ?: []);
        $byteLen = count($bytes);
        $version = self::selectVersion($byteLen);

        $spec = self::VERSION_SPECS_M[$version];
        [$totalCodewords, $ecPerBlock, $blockGroups, $remainderBits] = $spec;

        $totalDataCodewords = 0;
        foreach ($blockGroups as [$cnt, $dataLen]) {
            $totalDataCodewords += $cnt * $dataLen;
        }

        // 1. Encode bit stream (Mode Byte 0100)
        $bits = [];
        self::appendBits($bits, 0b0100, 4);
        self::appendBits($bits, $byteLen, $version <= 9 ? 8 : 16);
        foreach ($bytes as $b) {
            self::appendBits($bits, $b, 8);
        }

        $capacityBits = $totalDataCodewords * 8;
        $terminatorLen = min(4, $capacityBits - count($bits));
        self::appendBits($bits, 0, $terminatorLen);

        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        $padBytes = [0xEC, 0x11];
        $padIdx = 0;
        while (count($bits) < $capacityBits) {
            self::appendBits($bits, $padBytes[$padIdx % 2], 8);
            $padIdx++;
        }

        // Konversi bits ke byte array
        $dataCodewords = [];
        for ($i = 0; $i < count($bits); $i += 8) {
            $val = 0;
            for ($j = 0; $j < 8; $j++) {
                $val = ($val << 1) | $bits[$i + $j];
            }
            $dataCodewords[] = $val;
        }

        // 2. Bagi ke blok dan hitung Reed-Solomon ECC
        $dataBlocks = [];
        $ecBlocks = [];
        $offset = 0;
        foreach ($blockGroups as [$cnt, $dataLen]) {
            for ($b = 0; $b < $cnt; $b++) {
                $blockData = array_slice($dataCodewords, $offset, $dataLen);
                $offset += $dataLen;
                $dataBlocks[] = $blockData;
                $ecBlocks[] = self::computeReedSolomon($blockData, $ecPerBlock);
            }
        }

        // 3. Interleave data & ECC codewords
        $finalCodewords = [];
        $maxDataLen = max(array_map('count', $dataBlocks));
        for ($i = 0; $i < $maxDataLen; $i++) {
            foreach ($dataBlocks as $blk) {
                if (isset($blk[$i])) {
                    $finalCodewords[] = $blk[$i];
                }
            }
        }
        for ($i = 0; $i < $ecPerBlock; $i++) {
            foreach ($ecBlocks as $blk) {
                if (isset($blk[$i])) {
                    $finalCodewords[] = $blk[$i];
                }
            }
        }

        $streamBits = [];
        foreach ($finalCodewords as $cw) {
            self::appendBits($streamBits, $cw, 8);
        }
        for ($r = 0; $r < $remainderBits; $r++) {
            $streamBits[] = 0;
        }

        // 4. Siapkan matriks modul & fungsi reserved mask
        $dim = $version * 4 + 17;
        $modules = array_fill(0, $dim, array_fill(0, $dim, false));
        $isFunction = array_fill(0, $dim, array_fill(0, $dim, false));

        self::drawFunctionPatterns($modules, $isFunction, $version, $dim);
        self::placeDataBits($modules, $isFunction, $streamBits, $dim);

        // 5. Pilih mask pattern 0..7 terbaik (penalti terkecil)
        $bestMask = 0;
        $bestPenalty = PHP_INT_MAX;
        $bestMatrix = $modules;

        for ($mask = 0; $mask < 8; $mask++) {
            $candidate = self::applyMask($modules, $isFunction, $mask, $dim);
            self::drawFormatBits($candidate, $mask, $dim);
            $penalty = self::evaluatePenalty($candidate, $dim);
            if ($penalty < $bestPenalty) {
                $bestPenalty = $penalty;
                $bestMask = $mask;
                $bestMatrix = $candidate;
            }
        }

        return $bestMatrix;
    }

    private static function selectVersion(int $byteLen): int
    {
        foreach (self::VERSION_SPECS_M as $ver => [, , $blockGroups]) {
            $capBytes = 0;
            foreach ($blockGroups as [$cnt, $dataLen]) {
                $capBytes += $cnt * $dataLen;
            }
            $headerBits = 4 + ($ver <= 9 ? 8 : 16);
            $maxPayloadBytes = intdiv(($capBytes * 8) - $headerBits, 8);
            if ($byteLen <= $maxPayloadBytes) {
                return $ver;
            }
        }

        throw new InvalidArgumentException("Data QR Code terlalu panjang ({$byteLen} byte, maks 213 byte).");
    }

    private static function appendBits(array &$bits, int $val, int $len): void
    {
        for ($i = $len - 1; $i >= 0; $i--) {
            $bits[] = ($val >> $i) & 1;
        }
    }

    private static function gfTables(): array
    {
        static $exp = null;
        static $log = null;
        if ($exp === null) {
            $exp = array_fill(0, 512, 0);
            $log = array_fill(0, 256, 0);
            $x = 1;
            for ($i = 0; $i < 255; $i++) {
                $exp[$i] = $x;
                $exp[$i + 255] = $x;
                $log[$x] = $i;
                $x <<= 1;
                if ($x & 0x100) {
                    $x ^= 0x11D;
                }
            }
        }
        return [$exp, $log];
    }

    private static function computeReedSolomon(array $data, int $ecLen): array
    {
        [$exp, $log] = self::gfTables();

        // Bangun generator polynomial derajat $ecLen
        $gen = [1];
        for ($i = 0; $i < $ecLen; $i++) {
            $next = array_fill(0, count($gen) + 1, 0);
            for ($j = 0; $j < count($gen); $j++) {
                $next[$j] ^= $gen[$j];
                if ($gen[$j] !== 0) {
                    $next[$j + 1] ^= $exp[($log[$gen[$j]] + $i) % 255];
                }
            }
            $gen = $next;
        }

        $rem = array_merge($data, array_fill(0, $ecLen, 0));
        for ($i = 0; $i < count($data); $i++) {
            $factor = $rem[$i];
            if ($factor !== 0) {
                $logFactor = $log[$factor];
                for ($j = 0; $j <= $ecLen; $j++) {
                    if ($gen[$j] !== 0) {
                        $rem[$i + $j] ^= $exp[($log[$gen[$j]] + $logFactor) % 255];
                    }
                }
            }
        }

        return array_slice($rem, count($data));
    }

    private static function setModule(array &$modules, array &$isFunction, int $r, int $c, bool $dark, int $dim): void
    {
        if ($r >= 0 && $r < $dim && $c >= 0 && $c < $dim) {
            $modules[$r][$c] = $dark;
            $isFunction[$r][$c] = true;
        }
    }

    private static function drawFinderPattern(array &$modules, array &$isFunction, int $row, int $col, int $dim): void
    {
        for ($dr = -1; $dr <= 7; $dr++) {
            for ($dc = -1; $dc <= 7; $dc++) {
                $r = $row + $dr;
                $c = $col + $dc;
                if ($r < 0 || $r >= $dim || $c < 0 || $c >= $dim) {
                    continue;
                }
                $inBorder = ($dr === -1 || $dr === 7 || $dc === -1 || $dc === 7);
                $inOuterRing = ($dr === 0 || $dr === 6 || $dc === 0 || $dc === 6);
                $inCenter = ($dr >= 2 && $dr <= 4 && $dc >= 2 && $dc <= 4);
                $dark = ! $inBorder && ($inOuterRing || $inCenter);
                self::setModule($modules, $isFunction, $r, $c, $dark, $dim);
            }
        }
    }

    private static function drawAlignmentPattern(array &$modules, array &$isFunction, int $centerR, int $centerC, int $dim): void
    {
        for ($dr = -2; $dr <= 2; $dr++) {
            for ($dc = -2; $dc <= 2; $dc++) {
                $dist = max(abs($dr), abs($dc));
                self::setModule($modules, $isFunction, $centerR + $dr, $centerC + $dc, $dist !== 1, $dim);
            }
        }
    }

    private static function drawFunctionPatterns(array &$modules, array &$isFunction, int $version, int $dim): void
    {
        // Timing patterns
        for ($i = 0; $i < $dim; $i++) {
            $dark = ($i % 2 === 0);
            self::setModule($modules, $isFunction, 6, $i, $dark, $dim);
            self::setModule($modules, $isFunction, $i, 6, $dark, $dim);
        }

        // Finder patterns + separators
        self::drawFinderPattern($modules, $isFunction, 0, 0, $dim);
        self::drawFinderPattern($modules, $isFunction, 0, $dim - 7, $dim);
        self::drawFinderPattern($modules, $isFunction, $dim - 7, 0, $dim);

        // Alignment patterns
        $centers = self::ALIGNMENT_PATTERNS[$version] ?? [];
        $cnt = count($centers);
        for ($i = 0; $i < $cnt; $i++) {
            for ($j = 0; $j < $cnt; $j++) {
                // Lewati sudut yang bertindihan dengan finder pattern
                if (($i === 0 && $j === 0) || ($i === 0 && $j === $cnt - 1) || ($i === $cnt - 1 && $j === 0)) {
                    continue;
                }
                self::drawAlignmentPattern($modules, $isFunction, $centers[$i], $centers[$j], $dim);
            }
        }

        // Reserve Format Information areas + Dark Module (lewati row/col 6 karena timing pattern)
        for ($i = 0; $i <= 8; $i++) {
            if ($i !== 6) {
                self::setModule($modules, $isFunction, 8, $i, false, $dim);
                self::setModule($modules, $isFunction, $i, 8, false, $dim);
            }
        }
        for ($i = $dim - 8; $i < $dim; $i++) {
            self::setModule($modules, $isFunction, 8, $i, false, $dim);
            self::setModule($modules, $isFunction, $i, 8, false, $dim);
        }
        self::setModule($modules, $isFunction, $dim - 8, 8, true, $dim); // Dark module wajib

        // Version Information (Versi >= 7)
        if ($version >= 7) {
            $rem = $version;
            for ($i = 0; $i < 12; $i++) {
                $rem = ($rem << 1) ^ ((($rem >> 11) & 1) * 0x1F25);
            }
            $bits = ($version << 12) | $rem;
            for ($i = 0; $i < 18; $i++) {
                $bit = (($bits >> $i) & 1) === 1;
                $a = $dim - 11 + ($i % 3);
                $b = intdiv($i, 3);
                self::setModule($modules, $isFunction, $a, $b, $bit, $dim);
                self::setModule($modules, $isFunction, $b, $a, $bit, $dim);
            }
        }
    }

    private static function placeDataBits(array &$modules, array $isFunction, array $streamBits, int $dim): void
    {
        $bitIdx = 0;
        $totalBits = count($streamBits);
        $upward = true;

        for ($right = $dim - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }
            for ($vert = 0; $vert < $dim; $vert++) {
                $r = $upward ? ($dim - 1 - $vert) : $vert;
                for ($j = 0; $j < 2; $j++) {
                    $c = $right - $j;
                    if (! $isFunction[$r][$c]) {
                        $modules[$r][$c] = ($bitIdx < $totalBits) ? ($streamBits[$bitIdx] === 1) : false;
                        $bitIdx++;
                    }
                }
            }
            $upward = ! $upward;
        }
    }

    private static function applyMask(array $modules, array $isFunction, int $mask, int $dim): array
    {
        $out = $modules;
        for ($r = 0; $r < $dim; $r++) {
            for ($c = 0; $c < $dim; $c++) {
                if ($isFunction[$r][$c]) {
                    continue;
                }
                $invert = match ($mask) {
                    0 => ($r + $c) % 2 === 0,
                    1 => $r % 2 === 0,
                    2 => $c % 3 === 0,
                    3 => ($r + $c) % 3 === 0,
                    4 => (intdiv($r, 2) + intdiv($c, 3)) % 2 === 0,
                    5 => (($r * $c) % 2 + ($r * $c) % 3) === 0,
                    6 => ((($r * $c) % 2 + ($r * $c) % 3) % 2) === 0,
                    7 => ((($r + $c) % 2 + ($r * $c) % 3) % 2) === 0,
                    default => false,
                };
                if ($invert) {
                    $out[$r][$c] = ! $out[$r][$c];
                }
            }
        }
        return $out;
    }

    private static function drawFormatBits(array &$modules, int $mask, int $dim): void
    {
        // ECC Level M = 0b00
        $data = (0b00 << 3) | $mask;
        $rem = $data;
        for ($i = 0; $i < 10; $i++) {
            $rem = ($rem << 1) ^ ((($rem >> 9) & 1) * 0x537);
        }
        $bits = (($data << 10) | $rem) ^ 0x5412;

        // Salinan pertama (sekitar top-left)
        for ($i = 0; $i <= 5; $i++) {
            $modules[8][$i] = (($bits >> $i) & 1) === 1;
        }
        $modules[8][7] = (($bits >> 6) & 1) === 1;
        $modules[8][8] = (($bits >> 7) & 1) === 1;
        $modules[7][8] = (($bits >> 8) & 1) === 1;
        for ($i = 9; $i < 15; $i++) {
            $modules[14 - $i][8] = (($bits >> $i) & 1) === 1;
        }

        // Salinan kedua (bottom-left & top-right)
        for ($i = 0; $i < 8; $i++) {
            $modules[$dim - 1 - $i][8] = (($bits >> $i) & 1) === 1;
        }
        for ($i = 8; $i < 15; $i++) {
            $modules[8][$dim - 15 + $i] = (($bits >> $i) & 1) === 1;
        }
        $modules[$dim - 8][8] = true;
    }

    private static function evaluatePenalty(array $modules, int $dim): int
    {
        $penalty = 0;

        // 1. Baris & Kolom 5+ modul warna sama berturutan
        for ($r = 0; $r < $dim; $r++) {
            $runColor = false;
            $runLen = 0;
            for ($c = 0; $c < $dim; $c++) {
                if ($modules[$r][$c] === $runColor) {
                    $runLen++;
                    if ($runLen === 5) {
                        $penalty += 3;
                    } elseif ($runLen > 5) {
                        $penalty += 1;
                    }
                } else {
                    $runColor = $modules[$r][$c];
                    $runLen = 1;
                }
            }
        }
        for ($c = 0; $c < $dim; $c++) {
            $runColor = false;
            $runLen = 0;
            for ($r = 0; $r < $dim; $r++) {
                if ($modules[$r][$c] === $runColor) {
                    $runLen++;
                    if ($runLen === 5) {
                        $penalty += 3;
                    } elseif ($runLen > 5) {
                        $penalty += 1;
                    }
                } else {
                    $runColor = $modules[$r][$c];
                    $runLen = 1;
                }
            }
        }

        // 2. Blok 2x2 warna sama
        for ($r = 0; $r < $dim - 1; $r++) {
            for ($c = 0; $c < $dim - 1; $c++) {
                $col = $modules[$r][$c];
                if ($col === $modules[$r][$c + 1] && $col === $modules[$r + 1][$c] && $col === $modules[$r + 1][$c + 1]) {
                    $penalty += 3;
                }
            }
        }

        return $penalty;
    }
}
