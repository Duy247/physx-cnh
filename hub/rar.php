<?php
declare(strict_types=1);

/** Encode a RAR 5 unsigned variable-length integer (including sizes above 4 GiB). */
function rarInteger(int $value): string
{
    $encoded = '';
    do {
        $byte = $value & 0x7f;
        $value >>= 7;
        $encoded .= chr($byte | ($value > 0 ? 0x80 : 0));
    } while ($value > 0);
    return $encoded;
}

function rarHeader(string $body): string
{
    $header = rarInteger(strlen($body)) . $body;
    return pack('V', crc32($header)) . $header;
}

/**
 * Wrap one file in a RAR 5 archive using the store method (no compression).
 * Streams the payload so the Hub's 5 GiB limit does not become a memory limit.
 * The destination must be a temporary path, never a published download.
 */
function createRarArchive(string $source, string $destination, string $fileName): int
{
    $size = filesize($source);
    $checksum = hash_file('crc32b', $source);
    if ($size === false || $checksum === false) {
        throw new RuntimeException('The downloaded file could not be read for archiving.');
    }

    $input = fopen($source, 'rb');
    if ($input === false) {
        throw new RuntimeException('The downloaded file could not be opened for archiving.');
    }
    $output = fopen($destination, 'xb');
    if ($output === false) {
        fclose($input);
        throw new RuntimeException('The temporary RAR archive could not be created.');
    }

    try {
        // Main header: type 1, no header flags, no archive flags.
        $prefix = "Rar!\x1a\x07\x01\x00" . rarHeader("\x01\x00\x00");
        // File header: data present, CRC32 present, Unix regular-file attributes,
        // store method, Unix host OS, and a UTF-8 filename.
        $prefix .= rarHeader(
            "\x02\x02" . rarInteger($size) . "\x04" . rarInteger($size)
            . rarInteger(0100644) . pack('V', (int) hexdec($checksum))
            . "\x00\x01" . rarInteger(strlen($fileName)) . $fileName
        );
        $suffix = rarHeader("\x05\x00\x00");

        if (fwrite($output, $prefix) !== strlen($prefix)
            || stream_copy_to_stream($input, $output) !== $size
            || fwrite($output, $suffix) !== strlen($suffix)
            || !fflush($output)
        ) {
            throw new RuntimeException('The RAR archive could not be written completely.');
        }
        return strlen($prefix) + $size + strlen($suffix);
    } finally {
        fclose($input);
        fclose($output);
    }
}
