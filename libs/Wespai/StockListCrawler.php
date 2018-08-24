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

    public function run(): array
    {
        $content = $this->getContent();
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        @$dom->loadHTML($content);
        $table = $dom->getElementById('example');
        $tbody = $table->childNodes[3];
        $tbody->normalize();
        $stock_list = array();
        foreach ($tbody->childNodes as $tr) {
            if (!$tr instanceof DOMElement) {
                continue;
            }
            $code = (string) $tr->childNodes->item(1)->textContent;
            $name = (string) $tr->childNodes->item(3)->textContent;
            $stock_list[$code] = $name;
        }
        return $stock_list;
    }
}


