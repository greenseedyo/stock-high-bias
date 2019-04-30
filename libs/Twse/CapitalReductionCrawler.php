<?php
namespace Luyo\Stock\Twse;

use Datetime;
use Luyo\Stock\Crawler;

class CapitalReductionCrawler extends Crawler
{
    private $uri = 'http://www.twse.com.tw/exchangeReport/TWTAUU';
    private $days = 60;
    private $start_date;
    private $end_date;

    public function __construct()
    {
        $this->end_date = new Datetime('now');
        $this->start_date = new Datetime("-{$this->days}days");
    }

    public function setDays($days): void
    {
        $this->days = (int) $days;
    }

    public function setStartDate(Datetime $start_date): void
    {
        $this->start_date = $start_date;
    }

    public function setEndDate(Datetime $end_date): void
    {
        $this->end_date = $end_date;
    }

    public function getUrl(): string
    {
        $start_date = $this->start_date->format('Ymd');
        $end_date = $this->end_date->format('Ymd');
        $query = http_build_query(array(
            'response' => 'json',
            'strDate' => $start_date,
            'endDate' => $end_date,
            '_' => round(microtime(true) * 1000),
        ));
        $url = "{$this->uri}?{$query}";
        return $url;
    }

    public function run(): array
    {
        $content = $this->getContent();
        $obj = json_decode($content);
        if (!is_array($obj->data) or empty($obj->data)) {
            return array();
        }
        $stock_codes = array();
        foreach ($obj->data as $data) {
            $code = (string) $data[1];
            $stock_code[$code] = $code;
        }
        return $stock_code;
    }
}

