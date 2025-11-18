<?php
// 1. URL target
//$url = 'https://docs.google.com/forms/d/e/1FAIpQLScNAY9KVYKXKz2_6EiV6PQb5bCF3lr7BQvfrzGEEUaP3MFhfA/closedform'; // Neighbor Fun Run
//$url = 'https://docs.google.com/forms/d/e/1FAIpQLSfuWkjoUqZ239xcxuhdlfgCO6LevwaYXjf4tM4S6Mfvp7fvrQ/closedform'; // Epic Run 2025
//$url = 'https://docs.google.com/forms/d/e/1FAIpQLSeYoiVNHSNENTIwbJFEDlCBCGf33DNk2IwJiXICQ0JHF5Y4vg/viewform'; // link konfirm


$url = 'https://docs.google.com/forms/d/e/1FAIpQLSejf6i-ls52Qs0iSHBlF01GWihqFbPtMn6nYT-hxpWGZfhA4w/viewform?usp=header'; // link SAYA
//$xpath_query = '/html/body/div[1]/div[2]/div[1]/div'; xpath gform
date_default_timezone_set('Asia/Jakarta');

// Variabel untuk menyimpan konten teks dari iterasi sebelumnya.
$previous_content = null; 
$xpath_query = '/html/body/div[1]/div[2]';

// --- DEFINISI WARNA ANSI ---
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m");
// ---------------------------

/**
 * Mengirim pesan suara menggunakan Termux Text-to-Speech (TTS) sebanyak 5 kali.
 *
 * @param string $message Pesan yang akan dibacakan.
 * @param int $count Jumlah pengulangan (default 5).
 */
function send_termux_tts_repeatedly($message, $count = 5) {
    $escaped_message = escapeshellarg($message);
    
    // Perintah dasar TTS (non-blocking)
    $base_command = "termux-tts-speak -r 1.0 {$escaped_message} &";
    
    for ($i = 0; $i < $count; $i++) {
        // Jalankan perintah TTS
        exec($base_command);
        
        // Jeda 1.5 detik (1,500,000 mikrodetik) agar suara tidak tumpang tindih
        // Anda mungkin perlu menyesuaikan waktu jeda ini tergantung kecepatan bicara TTS Anda.
        usleep(1500000); 
    }
}

## 🚀 Persiapan & Input Pengguna

echo COLOR_GREEN . "Skrip Pemantauan Konten Dimulai.\n" . COLOR_RESET;
echo COLOR_GREEN . "Mengambil konten dari URL: $url\n" . COLOR_RESET; 

// Meminta input dari pengguna (y/n)
$input_display = readline(COLOR_GREEN . "Tampilkan konten yang diekstrak di layar? (y/n, default y): " . COLOR_RESET);
$tampilkan_konten = (empty($input_display) || strtolower($input_display) === 'y');

echo COLOR_BLUE . "\nStatus Tampilan Konten: " . ($tampilkan_konten ? "AKTIF" : "NONAKTIF") . "\n" . COLOR_RESET;
echo COLOR_GREEN . "Skrip berjalan dalam loop 10 detik. Tekan Ctrl + C untuk mengakhiri.\n\n" . COLOR_RESET; 

// 3. Loop Utama (Berjalan terus-menerus)
while (true) {
    $start_time = microtime(true);
    $start_date = date("Y-m-d H:i:s");

    // Ambil konten HTML dari URL
    $html_content = @file_get_contents($url);

    if ($html_content === false) {
        echo COLOR_RED . "[{$start_date}] Gagal mengambil konten dari URL: $url\n" . COLOR_RESET;
        sleep(10); 
        continue;
    }

    // Inisialisasi DOMDocument, XPath, dan Ekstraksi Teks (Logika Sama)
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

        // 4. Deteksi Perubahan dan BUNYIKAN SUARA TTS 5 KALI
        if ($previous_content !== null && $current_content !== $previous_content) {
            echo "\n" . COLOR_YELLOW . "🔔🔔🔔 PERUBAHAN DITEMUKAN! 🔔🔔🔔\n" . COLOR_RESET;
            echo COLOR_YELLOW . "Konten telah berubah pada {$start_date}\n" . COLOR_RESET;
            
            // Panggil fungsi TTS yang berulang 5 kali
            $message = "Perhatian! Perubahan konten telah terdeteksi. Skrip dihentikan.";
            send_termux_tts_repeatedly($message, 5);
            
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

    // Jeda selama 10 detik
    sleep(10);
}


