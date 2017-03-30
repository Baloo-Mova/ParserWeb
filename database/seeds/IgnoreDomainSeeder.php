<?php

use Illuminate\Database\Seeder;
use App\Models\IgnoreDomains;
class IgnoreDomainSeeder extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        IgnoreDomains::insert([
            ['domain' => 'youtube.com'],
            ['domain' => 'images.google'],
            ['domain' => 'msdn.microsoft'],
            ['domain' => 'youtu.be'],
            ['domain' => 'wikipedia.org'],
            ['domain' => 'books.google'],
            ['domain' => '/sgi-bin/'],
            ['domain' => 'dontfollowme'],
            ['domain' => 'microsofttranslator.com'],
            ['domain' => 'docs.disqus.com'],
            ['domain' => 'spampoison'],
            ['domain' => 'flickr.com'],
            ['domain' => 'msdn.com'],
            ['domain' => '.msn.com'],
            ['domain' => 'microsoft.com'],
            ['domain' => 'discoverbing.com'],
            ['domain' => 'wiktionary.org'],
            ['domain' => 'wikimediafoundation.org'],
            ['domain' => 'wikipedia.org'],
            ['domain' => 'wikiquote.org'],
            ['domain' => 'wikibooks.org'],
            ['domain' => 'wikireality'],
            ['domain' => 'yandex.ru/video'],
             ['domain' => 'market.yandex'],
            ['domain' => 'youtube']
        ]);
    }

}
