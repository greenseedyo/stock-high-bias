<?php
namespace Luyo\Stock\Twse\StockList;

use Datetime;
use Luyo\Stock\Crawler;

class ListedCrawler extends Crawler
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
            'type' => 'ALL',
            '_' => round(microtime(true) * 1000),
        ));
        $url = "{$this->uri}?{$query}";
        return $url;
    }

    public function run(): array
    {
        $content = $this->getContent();
        $obj = json_decode($content);
        $stock_list = array();
        foreach ($obj->data5 as $data) {
            $code = (string) $data[0];
            if (4 !== strlen($code)) {
                continue;
            }
            $name = $data[1];
            $stock_list[$code] = $name;
        }
        return $stock_list;
    }
}


