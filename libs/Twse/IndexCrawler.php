<?php
namespace Luyo\Stock\Twse;

use Datetime;
use Luyo\Stock\Crawler;

class IndexCrawler extends Crawler
{
    private $uri = 'http://www.tse.com.tw/exchangeReport/MI_INDEX';
    private $date;

    public function setDate(Datetime $date): void
    {
        $this->date = $date;
    }

    public function getUrl(): string
    {
        if (isset($this->date)) {
            $date = $this->date->format('Ymd');
        } else {
            $date = '';
        }
        $query = http_build_query(array(
            'response' => 'json',
            'date' => $date,
            'type' => '',
            '_' => round(microtime(true) * 1000),
        ));
        $url = "{$this->uri}?{$query}";
        return $url;
    }

    public function run(): \stdClass
    {
        $content = $this->getContent();
        $obj = json_decode($content);
        return $obj;
    }
}

