<?php

namespace App\Console\Commands\Senders;

use Illuminate\Console\Command;
use App\Helpers\SimpleHtmlDom;
use GuzzleHttp;

class OkSender extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:ok';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ok sender process';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $uuid = "";

        $crawler = new SimpleHtmlDom(null, true, true, 'UTF-8', true, '\r\n', ' ');

        $client = new GuzzleHttp\Client([
            'verify' => false,
            'cookies' => true,
            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:50.0) Gecko/20100101 Firefox/50.0'
            ]
        ]);

        $data = $client->request('POST', 'https://www.ok.ru/https', [
            'form_params' => [
                "st.redirect" => "",
                "st.asr" => "",
                "st.posted" => "set",
                "st.originalaction" => "https://www.ok.ru/dk?cmd=AnonymLogin&st.cmd=anonymLogin",
                "st.fJS" => "on",
                "st.st.screenSize" => "1920 x 1080",
                "st.st.browserSize" => "947",
                "st.st.flashVer" => "23.0.0",
                "st.email" => "380679744662",
                "st.password" => "azerty99",
                "st.iscode" => "false"
            ],
            "proxy" => "127.0.0.1:8888"
        ]);

        $cookies_number = count($client->getConfig("cookies")); // Считаем, сколько получили кукисов

        $html_doc = $data->getBody()->getContents();

        if($cookies_number > 2){ // Куков больше 2, возможно залогинились

            $crawler->clear();
            $crawler->load($html_doc);

            if(count($crawler->find('Мы отправили')) > 0){ // Вывелось сообщение безопасности, значит не залогинились
                echo "bad"; // Аккаунт плохой - удаляем
            }else{
                echo "ok";
                $gwt = substr($html_doc, strripos($html_doc, "gwtHash:") + 9, 8);
                $tkn = substr($html_doc, strripos($html_doc, "OK.tkn.set('") + 12, 32);

            }
        }else{  // Точно не залогинись
            echo "bad"; // Аккаунт плохой - удаляем
        }

        $data2 = $client->request('GET', 'https://www.ok.ru/messages/578730788514', [
            "proxy" => "127.0.0.1:8888"
        ]);

        $html_doc2 = $data2->getBody()->getContents();
        $crawler->clear();
        $crawler->load($html_doc2);

        $uuid = substr($html_doc2, (strripos($html_doc2, "data-uuid=") + 11), 36);

        echo $uuid;

        $data = $client->request('POST', 'https://www.ok.ru', [
            "proxy" => "127.0.0.1:8888"
        ]);

        $tkn = $data->getHeader("TKN");


        $data1 = $client->request('POST', 'https://www.ok.ru/dk', [
            'headers' => [
                'TKN' => $tkn[0]
            ],
            'form_params' => [
                "cmd" => "MessagesTypingStatus",
                "st.convId" => "PRIVATE_578730788514",
                "st.ts" => "0",
                "gwt.requested" => $gwt
            ],
            "proxy" => "127.0.0.1:8888",
        ]);

         $tkn2 = $data->getHeader("TKN");

        echo $tkn2[0];

        $data = $client->request('POST', 'https://www.ok.ru/dk?cmd=MessagesController&st.convId=PRIVATE_578730788514&st.cmd=userMain&st.openPanel=messages', [
            'headers' => [
                'TKN' => $tkn2[0]
            ],
            'form_params' => [
                "st.txt" => urlencode("1ttfpt"),
                "st.uuid" => $uuid,
                "st.posted" => $gwt
            ],
            "proxy" => "127.0.0.1:8888"
        ]);





    }

    function randomBytes($length)
    {
        if (function_exists('random_bytes')) {
            return random_bytes($length);
        }
        if (!is_int($length) || $length < 1) {
            throw new \Exception('Invalid first parameter ($length)');
        }
        // The recent LibreSSL RNGs are faster and likely better than /dev/urandom.
        // Parse OPENSSL_VERSION_TEXT because OPENSSL_VERSION_NUMBER is no use for LibreSSL.
        // https://bugs.php.net/bug.php?id=71143
        static $libreSSL;
        if ($libreSSL === null) {
            $libreSSL = defined('OPENSSL_VERSION_TEXT')
                && preg_match('{^LibreSSL (\d\d?)\.(\d\d?)\.(\d\d?)$}', OPENSSL_VERSION_TEXT, $matches)
                && (10000 * $matches[1]) + (100 * $matches[2]) + $matches[3] >= 20105;
        }
        // Since 5.4.0, openssl_random_pseudo_bytes() reads from CryptGenRandom on Windows instead
        // of using OpenSSL library. Don't use OpenSSL on other platforms.
        if ($libreSSL === true
            || (DIRECTORY_SEPARATOR !== '/'
                && PHP_VERSION_ID >= 50400
                && substr_compare(PHP_OS, 'win', 0, 3, true) === 0
                && function_exists('openssl_random_pseudo_bytes'))
        ) {
            $key = openssl_random_pseudo_bytes($length, $cryptoStrong);
            if ($cryptoStrong === false) {
                throw new Exception(
                    'openssl_random_pseudo_bytes() set $crypto_strong false. Your PHP setup is insecure.'
                );
            }
            if ($key !== false && mb_strlen($key, '8bit') === $length) {
                return $key;
            }
        }
        // mcrypt_create_iv() does not use libmcrypt. Since PHP 5.3.7 it directly reads
        // CrypGenRandom on Windows. Elsewhere it directly reads /dev/urandom.
        if (PHP_VERSION_ID >= 50307 && function_exists('mcrypt_create_iv')) {
            $key = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
            if (mb_strlen($key, '8bit') === $length) {
                return $key;
            }
        }
        // If not on Windows, try a random device.
        if (DIRECTORY_SEPARATOR === '/') {
            // urandom is a symlink to random on FreeBSD.
            $device = PHP_OS === 'FreeBSD' ? '/dev/random' : '/dev/urandom';
            // Check random device for speacial character device protection mode. Use lstat()
            // instead of stat() in case an attacker arranges a symlink to a fake device.
            $lstat = @lstat($device);
            if ($lstat !== false && ($lstat['mode'] & 0170000) === 020000) {
                $key = @file_get_contents($device, false, null, 0, $length);
                if ($key !== false && mb_strlen($key, '8bit') === $length) {
                    return $key;
                }
            }
        }
        throw new \Exception('Unable to generate a random key');
    }

    /**
     * Generates a random UUID using the secure RNG.
     *
     * Returns Version 4 UUID format: xxxxxxxx-xxxx-4xxx-Yxxx-xxxxxxxxxxxx where x is
     * any random hex digit and Y is a random choice from 8, 9, a, or b.
     *
     * @return string the UUID
     */
    function randomUuid()
    {
        $bytes = $this->randomBytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $id = str_split(bin2hex($bytes), 4);
        return "{$id[0]}{$id[1]}-{$id[2]}-{$id[3]}-{$id[4]}-{$id[5]}{$id[6]}{$id[7]}";
    }
}
