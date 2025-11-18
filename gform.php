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

/**
 * Mengirim notifikasi menggunakan Termux-API.
 * Termux-API harus terinstal: pkg install termux-api
 *
 * @param string $title Judul notifikasi.
 * @param string $content Isi notifikasi.
 */
function send_termux_notification($title, $content) {
    // Escaping konten untuk shell command
    $escaped_title = escapeshellarg($title);
    $escaped_content = escapeshellarg($content);
    
    // Perintah termux-notification
    $command = "termux-notification --title {$escaped_title} --content {$escaped_content} --vibrate 1";
    
    // Jalankan perintah
    exec($command);
}

// 2. Tampilan Awal
echo "Skrip berjalan dalam loop 10 detik. Tekan Ctrl + C untuk mengakhiri.\n";
echo "Mengambil konten dari URL: $url\n\n"; 

// 3. Loop Utama (Berjalan terus-menerus)
while (true) {
    $start_time = microtime(true);
    $start_date = date("Y-m-d H:i:s");

    // Ambil konten HTML dari URL
    $html_content = @file_get_contents($url);

    if ($html_content === false) {
        echo "[{$start_date}] Gagal mengambil konten dari URL: $url\n";
        sleep(10); // Tetap tidur sebelum mencoba lagi
        continue;
    }

    // Inisialisasi DOMDocument dan tangani error parsing
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html_content);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    
    // Kueri XPath untuk elemen target
    $elements = $xpath->query($xpath_query);

    if ($elements->length > 0) {
        $element = $elements->item(0);
        $separated_text = '';

        // --- Logika Ekstraksi Teks Rekursif dengan Baris Baru ---
        $text_nodes = $xpath->query('.//text()', $element); 

        foreach ($text_nodes as $text_node) {
            $text = trim($text_node->textContent);
            
            if ($text !== '') {
                $separated_text .= $text . "\n"; 
            }
        }
        
        $current_content = trim($separated_text);
        
        // --- Akhir Logika Ekstraksi ---

        $end_time = microtime(true);
        $execution_time = round($end_time - $start_time, 4);

        // 4. Deteksi Perubahan dan Kirim Notifikasi Termux
        if ($previous_content !== null && $current_content !== $previous_content) {
            echo "\n🔔🔔🔔 PERUBAHAN DITEMUKAN! 🔔🔔🔔\n";
            echo "Konten telah berubah pada {$start_date}\n";
            
            // Panggil fungsi notifikasi Termux
            send_termux_notification(
                "PERUBAHAN DITEMUKAN!", 
                "Konten pada {$url} telah berubah pada {$start_date}. Skrip dihentikan."
            );
            
            echo "\n";
            
            // !!! PERUBAHAN INTI: HENTIKAN SKRIP !!!
            echo "Skrip dihentikan karena perubahan telah ditemukan.\n";
            exit(0); 
            // ------------------------------------
        }
        
        // Perbarui konten sebelumnya untuk iterasi berikutnya
        $previous_content = $current_content;
        
        // --- Tampilkan Hasil ---
        echo "\n--- Hasil Eksekusi Ditemukan ---\n";
        echo "Waktu: {$start_date} | Durasi: {$execution_time} detik";
        
        // Tampilkan status perubahan
        if ($previous_content !== null && $current_content !== $previous_content) {
             echo " | STATUS: BERUBAH!\n";
        } else {
             echo " | STATUS: Sama.\n";
        }
        
        echo "---------------------------------\n";
        echo $current_content;
        echo "\n---------------------------------\n\n";

    } else {
        echo "[{$start_date}] Elemen dengan XPath '{$xpath_query}' tidak ditemukan.\n";
        $previous_content = ''; 
    }

    // Jeda selama 10 detik
    sleep(10);
}