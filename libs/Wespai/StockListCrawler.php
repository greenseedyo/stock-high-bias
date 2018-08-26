<?php
namespace Luyo\Stock\Wespai;

use DOMDocument;
use DOMElement;
use Luyo\Stock\Crawler;

class StockListCrawler extends Crawler
{
    private $uri = 'https://stock.wespai.com/p/16647';
    private $date;

    public function getUrl(): string
    {
        return $this->uri;
    }

    protected function getContentByUrl(): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getUrl());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_PORT, 443);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36');
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    public function run(): array
    {
        $html = $this->getContent();
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $table = $dom->getElementById('example');
        $tbody = $table->childNodes[1];
        $tbody->normalize();
        $stock_list = array();
        foreach ($tbody->childNodes as $tr) {
            if (!$tr instanceof DOMElement) {
                continue;
            }
            $code = (string) $tr->childNodes->item(0)->textContent;
            $name = (string) $tr->childNodes->item(2)->textContent;
            $stock_list[$code] = $name;
        }
        return $stock_list;
    }
}


