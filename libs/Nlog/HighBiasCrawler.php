<?php
namespace Luyo\Stock\Nlog;

use DOMDocument;
use Luyo\Stock\Crawler;

class HighBiasCrawler extends Crawler
{
    private $uri = 'http://stock.nlog.cc/SS/apdr60';
    private $threshold = 20;
    private static $result;

    public function setThreshold($threshold): void
    {
        $this->threshold = (float) $threshold;
    }

    public function getUrl(): string
    {
        return $this->uri;
    }

    public function run(): array
    {
        if (self::$result) {
            return self::$result;
        }
        $html = $this->getContent();
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $table = $dom->getElementById('x');
        $result = array();
        foreach ($table->childNodes as $tr) {
            if (!$attrs = $tr->attributes) {
                continue;
            }
            if (0 === $attrs->length) {
                continue;
            }
            if (!$class = $attrs->getNamedItem('class')) {
                continue;
            }
            if ('lo' != $class->nodeValue and 'le' != $class->nodeValue) {
                continue;
            }
            $bias = (float) $tr->childNodes[7]->nodeValue;
            if ($bias < $this->threshold) {
                continue;
            }
            $code = (string) trim($tr->childNodes[2]->nodeValue);
            $result[$code] = $bias;
        }
        self::$result = $result;
        return $result;
    }
}

