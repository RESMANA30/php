<?php
// 1. URL target
//$url = 'https://docs.google.com/forms/d/e/1FAIpQLScNAY9KVYKXKz2_6EiV6PQb5bCF3lr7BQvfrzGEEUaP3MFhfA/closedform'; // Neighbor Fun Run
//$url = 'https://docs.google.com/forms/d/e/1FAIpQLSfuWkjoUqZ239xcxuhdlfgCO6LevwaYXjf4tM4S6Mfvp7fvrQ/closedform'; // Epic Run 2025
//$url = 'https://docs.google.com/forms/d/e/1FAIpQLSeYoiVNHSNENTIwbJFEDlCBCGf33DNk2IwJiXICQ0JHF5Y4vg/viewform'; // link konfirm


$url = 'https://docs.google.com/forms/d/e/1FAIpQLSejf6i-ls52Qs0iSHBlF01GWihqFbPtMn6nYT-hxpWGZfhA4w/viewform?usp=header'; // link SAYA
//$xpath_query = '/html/body/div[1]/div[2]/div[1]/div'; xpath gform
date_default_timezone_set('Asia/Jakarta');

// Variabel untuk menyimpan konten teks dari iterasi sebelumnya.
// Didefinisikan di luar loop agar nilainya bertahan di setiap iterasi.
$previous_content = null; 

$xpath_query = '/html/body/div[1]/div[2]';

// --- DEFINISI WARNA ANSI ---
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m"); // Untuk mengembalikan warna ke default
// ---------------------------

/**
 * Mengirim notifikasi menggunakan Termux-API dengan getaran.
 *
 * @param string $title Judul notifikasi.
 * @param string $content Isi notifikasi.
 */
function send_termux_notification($title, $content) {
    $escaped_title = escapeshellarg($title);
    $escaped_content = escapeshellarg($content);
    
    // --vibrate 1: Mengaktifkan getar
    // --alert-once: Agar tidak menumpuk notifikasi
    // & : Menjalankan perintah di background agar PHP tidak terblokir
    $command = "termux-notification --title {$escaped_title} --content {$escaped_content} --vibrate 1 --alert-once &";
    
    // Jalankan perintah
    exec($command);
}

// 2. Tampilan Awal (Diberi Warna Hijau)
echo COLOR_GREEN . "Skrip berjalan dalam loop 10 detik. Tekan Ctrl + C untuk mengakhiri.\n" . COLOR_RESET;
echo COLOR_GREEN . "Mengambil konten dari URL: $url\n\n" . COLOR_RESET; 

// 3. Loop Utama (Berjalan terus-menerus)
while (true) {
    $start_time = microtime(true);
    $start_date = date("Y-m-d H:i:s");

    // Ambil konten HTML dari URL
    $html_content = @file_get_contents($url);

    if ($html_content === false) {
        // Pesan Error diberi Warna Merah
        echo COLOR_RED . "[{$start_date}] Gagal mengambil konten dari URL: $url\n" . COLOR_RESET;
        sleep(10); 
        continue;
    }

    // ... (Logika DOMDocument, XPath, dan Ekstraksi Teks sama)
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

        // 4. Deteksi Perubahan dan Kirim Notifikasi Termux
        if ($previous_content !== null && $current_content !== $previous_content) {
            // Pesan Perubahan diberi Warna Merah/Kuning Terang
            echo "\n" . COLOR_YELLOW . "🔔🔔🔔 PERUBAHAN DITEMUKAN! 🔔🔔🔔\n" . COLOR_RESET;
            echo COLOR_YELLOW . "Konten telah berubah pada {$start_date}\n" . COLOR_RESET;
            
            // Panggil fungsi notifikasi Termux
            send_termux_notification(
                "PERUBAHAN DITEMUKAN!", 
                "Konten pada {$url} telah berubah pada {$start_date}. Skrip dihentikan."
            );
            
            echo "\n";
            
            // HENTIKAN SKRIP
            echo COLOR_RED . "Skrip dihentikan karena perubahan telah ditemukan.\n" . COLOR_RESET;
            exit(0); 
        }
        
        // Perbarui konten sebelumnya
        $previous_content = $current_content;
        
        // --- Tampilkan Hasil (Diberi Warna Biru) ---
        echo "\n" . COLOR_BLUE . "--- Hasil Eksekusi Ditemukan ---\n" . COLOR_RESET;
        echo COLOR_BLUE . "Waktu: {$start_date} | Durasi: {$execution_time} detik";
        
        // Tampilkan status perubahan (Warna Hijau jika Sama, Kuning jika Berubah - *tapi ini tidak akan terjangkau karena skrip sudah dihentikan*)
        if ($previous_content !== null && $current_content !== $previous_content) {
             echo COLOR_YELLOW . " | STATUS: BERUBAH!\n" . COLOR_RESET;
        } else {
             echo COLOR_GREEN . " | STATUS: Sama.\n" . COLOR_RESET;
        }
        
        echo COLOR_BLUE . "---------------------------------\n" . COLOR_RESET;
        echo $current_content; // Konten teks tanpa warna agar mudah dibaca
        echo "\n" . COLOR_BLUE . "---------------------------------\n\n" . COLOR_RESET;

    } else {
        // Pesan Elemen Tidak Ditemukan diberi Warna Kuning
        echo COLOR_YELLOW . "[{$start_date}] Elemen dengan XPath '{$xpath_query}' tidak ditemukan.\n" . COLOR_RESET;
        $previous_content = ''; 
    }

    // Jeda selama 10 detik
    sleep(10);
}
