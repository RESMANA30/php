<?php
// Set zona waktu ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// Variabel untuk menyimpan konten teks dari iterasi sebelumnya.
$previous_content = null;

// --- DEFINISI WARNA ANSI ---
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m");
// ---------------------------

/**
 * Mengirim pesan suara menggunakan Termux Text-to-Speech (TTS) berulang kali.
 *
 * @param string $message Pesan yang akan dibacakan.
 * @param int $count Jumlah pengulangan (default 5).
 */
function send_termux_tts_repeatedly($message, $count = 5) {
    $escaped_message = escapeshellarg($message);
    $base_command = "termux-tts-speak -r 1.0 {$escaped_message} &";

    for ($i = 0; $i < $count; $i++) {
        exec($base_command);
        // Jeda 1.5 detik agar suara tidak tumpang tindih
        usleep(1500000);
    }
}

/**
 * Mengirim notifikasi Termux 1 kali.
 *
 * @param string $title Judul notifikasi.
 * @param string $content Isi notifikasi.
 */
function send_termux_notification($title, $content) {
    $escaped_title = escapeshellarg($title);
    $escaped_content = escapeshellarg($content);
    // --vibrate 1 untuk getar
    $command = "termux-notification --title {$escaped_title} --content {$escaped_content} --vibrate 1 --alert-once &";
    exec($command);
}

// ----------------------------------------------------
//          🚀 PENGAMBILAN INPUT DARI PENGGUNA
// ----------------------------------------------------

echo COLOR_GREEN . "--- Skrip Pemantauan Konten Interaktif Dimulai ---\n" . COLOR_RESET;

// 1. Input URL
$url_input = readline(COLOR_BLUE . "1. Masukkan URL situs yang ingin dipantau: " . COLOR_RESET);
$url = trim($url_input);
if (empty($url)) {
    echo COLOR_RED . "URL tidak boleh kosong. Skrip dihentikan.\n" . COLOR_RESET;
    exit(1);
}

// 2. Input XPath
$xpath_query_default = '/html/body/div[1]/div[2]';
$input_xpath_choice = readline(COLOR_BLUE . "2. Gunakan XPath default ('$xpath_query_default')? (y/n, default y): " . COLOR_RESET);

if (strtolower($input_xpath_choice) === 'n') {
    $xpath_query_input = readline(COLOR_BLUE . "    Masukkan XPath kustom Anda: " . COLOR_RESET);
    $xpath_query = trim($xpath_query_input);
    if (empty($xpath_query)) {
        echo COLOR_YELLOW . "    XPath kustom kosong. Menggunakan default: $xpath_query_default\n" . COLOR_RESET;
        $xpath_query = $xpath_query_default;
    }
} else {
    $xpath_query = $xpath_query_default;
}

// 3. Input Loop Delay (Jeda antar loop)
$loop_delay_input = readline(COLOR_BLUE . "3. Masukkan jeda loop (detik, default 10): " . COLOR_RESET);
$loop_delay = (int)trim($loop_delay_input);
// Pastikan jeda minimal 1 detik jika input kosong atau kurang dari 1
$loop_delay = ($loop_delay < 1) ? 10 : $loop_delay;

// 4. Input TTS Count (Jumlah pengulangan TTS)
$tts_count_input = readline(COLOR_BLUE . "4. Masukkan jumlah pengulangan TTS saat perubahan terdeteksi (default 5): " . COLOR_RESET);
$tts_count = (int)trim($tts_count_input);
// Pastikan count minimal 1 jika input kosong atau kurang dari 1
$tts_count = ($tts_count < 1) ? 5 : $tts_count;


// 5. Input Tampilkan Konten
$input_display = readline(COLOR_BLUE . "5. Tampilkan konten yang diekstrak di layar? (y/n, default y): " . COLOR_RESET);
$tampilkan_konten = (empty($input_display) || strtolower($input_display) === 'y');

// ----------------------------------------------------
//             ✅ RINGKASAN & PERINGATAN
// ----------------------------------------------------

echo COLOR_YELLOW . "\n--- Ringkasan Konfigurasi ---\n" . COLOR_RESET;
echo COLOR_BLUE . "URL yang Dipantau: " . $url . "\n" . COLOR_RESET;
echo COLOR_BLUE . "XPath yang Digunakan: " . $xpath_query . "\n" . COLOR_RESET;
echo COLOR_BLUE . "Jeda Loop: " . $loop_delay . " detik\n" . COLOR_RESET;
echo COLOR_BLUE . "Pengulangan TTS: " . $tts_count . " kali\n" . COLOR_RESET;
echo COLOR_BLUE . "Tampilan Konten: " . ($tampilkan_konten ? "AKTIF" : "NONAKTIF") . "\n" . COLOR_RESET;
echo COLOR_GREEN . "\nSkrip berjalan dalam loop {$loop_delay} detik. Tekan Ctrl + C untuk mengakhiri.\n\n" . COLOR_RESET;

// ----------------------------------------------------
//             6. Loop Utama (Berjalan terus-menerus)
// ----------------------------------------------------

while (true) {
    $start_time = microtime(true);
    $start_date = date("Y-m-d H:i:s");

    // Ambil konten HTML dari URL
    $html_content = @file_get_contents($url);

    if ($html_content === false) {
        echo COLOR_RED . "[{$start_date}] Gagal mengambil konten dari URL: $url\n" . COLOR_RESET;
        sleep($loop_delay);
        continue;
    }

    // Inisialisasi DOMDocument, XPath, dan Ekstraksi Teks
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html_content);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $elements = $xpath->query($xpath_query);

    if ($elements->length > 0) {
        $element = $elements->item(0);
        $separated_text = '';
        $text_nodes = $xpath->query('.//text()', $element);

        foreach ($text_nodes as $text_node) {
            $text = trim($text_node->textContent);
            if ($text !== '') {
                $separated_text .= $text . "\n";
            }
        }

        $current_content = trim($separated_text);

        $end_time = microtime(true);
        $execution_time = round($end_time - $start_time, 4);

        // 7. Deteksi Perubahan dan BUNYIKAN PERINGATAN
        if ($previous_content !== null && $current_content !== $previous_content) {
            echo "\n" . COLOR_YELLOW . "🔔🔔🔔 PERUBAHAN DITEMUKAN! 🔔🔔🔔\n" . COLOR_RESET;
            echo COLOR_YELLOW . "Konten telah berubah pada {$start_date}\n" . COLOR_RESET;

            $message = "Perhatian! Perubahan konten telah terdeteksi. Skrip dihentikan.";

            // 1. Notifikasi Visual (1 kali)
            send_termux_notification("PERUBAHAN DITEMUKAN!", "Konten telah berubah pada {$start_date}. Skrip dihentikan.");

            // 2. Peringatan Suara (TTS dengan jumlah yang diinput)
            send_termux_tts_repeatedly($message, $tts_count);


            echo "\n";

            // HENTIKAN SKRIP
            echo COLOR_RED . "Skrip dihentikan karena perubahan telah ditemukan.\n" . COLOR_RESET;
            exit(0);
        }

        // Perbarui konten sebelumnya
        $previous_content = $current_content;

        ## 📊 Tampilkan Hasil
        echo "\n" . COLOR_BLUE . "--- Hasil Eksekusi Ditemukan ---\n" . COLOR_RESET;
        echo COLOR_BLUE . "Waktu: {$start_date} | Durasi: {$execution_time} detik";

        // Tampilkan status perubahan
        if ($previous_content !== null && $current_content !== $previous_content) {
             echo COLOR_YELLOW . " | STATUS: BERUBAH!\n" . COLOR_RESET;
        } else {
             echo COLOR_GREEN . " | STATUS: Sama.\n" . COLOR_RESET;
        }

        // --- KONTROL TAMPILAN KONTEN ---
        if ($tampilkan_konten) {
            echo COLOR_BLUE . "---------------------------------\n" . COLOR_RESET;
            echo $current_content;
            echo "\n" . COLOR_BLUE . "---------------------------------\n\n" . COLOR_RESET;
        } else {
            echo COLOR_BLUE . "---------------------------------\n" . COLOR_RESET;
            echo COLOR_BLUE . "(Tampilan konten dinonaktifkan)\n" . COLOR_RESET;
            echo COLOR_BLUE . "---------------------------------\n\n" . COLOR_RESET;
        }

    } else {
        echo COLOR_YELLOW . "[{$start_date}] Elemen dengan XPath '{$xpath_query}' tidak ditemukan.\n" . COLOR_RESET;
        $previous_content = '';
    }

    // Jeda selama durasi yang diinput
    sleep($loop_delay);
}
