<?php
namespace Luyo\Stock\Twse\StockList;

use DOMDocument;
use Luyo\Stock\Crawler;

class OtcCrawler extends Crawler
{
    private $uri = 'http://isin.twse.com.tw/isin/C_public.jsp?strMode=4';
    private $date;

    public function getUrl(): string
    {
        return $this->uri;
    }

    public function run(): array
    {
        $content = trim($this->getContent());
        $html = mb_convert_encoding($content, "utf8", "big5");
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $table = $dom->getElementsByTagName('table')->item(1);
        $stocks = array();
        foreach ($table->childNodes as $tr) {
            if (7 !== $tr->childNodes->length) {
                continue;
            }
            $info_td = $tr->childNodes->item(0);
            $text = $info_td->textContent;
            if (!preg_match('/^(\d{4})　(.+)/', $text, $matches)) {
                continue;
            }
            $code = (string) $matches[1];
            $name = (string) $matches[2];
            $stocks[$code] = $name;
        }
        return $stocks;
    }
}

