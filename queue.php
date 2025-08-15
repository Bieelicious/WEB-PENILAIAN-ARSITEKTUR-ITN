<?php

$artisanPath = __DIR__ . '/artisan';

if (!file_exists($artisanPath)) {
    die("File 'artisan' tidak ditemukan di path: $artisanPath\n");
}

$command = "php $artisanPath queue:work";

$descriptorspec = [
    1 => ['pipe', 'w'], // stdout
    2 => ['pipe', 'w'], // stderr
];

$process = proc_open($command, $descriptorspec, $pipes);

if (!is_resource($process)) {
    die("Gagal menjalankan command.\n");
}

stream_set_blocking($pipes[1], false);
stream_set_blocking($pipes[2], false);

echo "Menjalankan command: $command\n\n";

while (true) {
    $stdout = fgets($pipes[1]);
    $stderr = fgets($pipes[2]);

    if ($stdout !== false) {
        echo $stdout;
    }

    if ($stderr !== false) {
        echo "[ERR] " . $stderr;
    }

    $status = proc_get_status($process);
    if (!$status['running']) {
        break;
    }

    usleep(100000); // delay 0.1 detik untuk mengurangi beban CPU
}

fclose($pipes[1]);
fclose($pipes[2]);

$exitCode = proc_close($process);

echo "\nCommand selesai dengan kode keluar: $exitCode\n";
?>
