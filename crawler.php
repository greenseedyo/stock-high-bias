<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
date_default_timezone_set("Asia/Taipei");
$loader = require __DIR__ . '/vendor/autoload.php';

use Luyo\Stock\Nlog\HighBiasCrawler;
use Luyo\Stock\Twse\CapitalReductionCrawler;
use Luyo\Stock\Twse\IndexCrawler;
use Luyo\Stock\Wespai\StockListCrawler;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class PickCommand
{
    private $date;
    private $latest_result_file;
    private $history_dir;
    private $bias_file_path;
    private $capital_reduction_file_path;
    private $stock_list_file_path;

    public function __construct(PickConfig $config)
    {
        $data_dir = $config->data_dir;
        $this->date = $config->date ?: date('Ymd');
        if (!is_dir($data_dir)) {
            mkdir($data_dir, 0777, true);
        }
        $this->latest_result_file = "{$data_dir}/latest_result.json";
        $this->history_dir = "{$data_dir}/history";
        if (!file_exists($this->latest_result_file)) {
            touch($this->latest_result_file);
        }
        if (!file_exists($this->history_dir)) {
            mkdir($this->history_dir, 0777, true);
        }

        $this->bias_file_path = $config->bais_file_path ?: null;
        $this->capital_reduction_file_path = $config->capital_reduction_file_path ?: null;
        $this->stock_list_file_path = $config->stock_list_file_path ?: null;
    }

    private function getHighBiasStocks()
    {
        $crawler = new HighBiasCrawler;
        if ($this->bias_file_path) {
            $crawler->setFilePath($this->bias_file_path);
        }
        $stock_codes = $crawler->run();
        return $stock_codes;
    }

    private function getCapitalReductionStocks()
    {
        $crawler = new CapitalReductionCrawler;
        if ($this->capital_reduction_file_path) {
            $crawler->setFilePath($this->capital_reduction_file_path);
        }
        $stock_codes = $crawler->run();
        return $stock_codes;
    }

    private function getStockList()
    {
        $crawler = new StockListCrawler;
        if ($this->stock_list_file_path) {
            $crawler->setFilePath($this->stock_list_file_path);
        }
        $stock_list = $crawler->run();
        return $stock_list;
    }

    public function checkDate()
    {
        $index_crawler = new IndexCrawler;
        $index_info_obj = $index_crawler->run();
        $latest_trade_day = $index_info_obj->date;
        if ($this->date !== $latest_trade_day) {
            return false;
        }
        return true;
    }

    public function exec()
    {
        echo "getting stock list...\n";
        $stock_list = $this->getStockList();
        echo "getting capital reduction stocks...\n";
        $capital_reduction_codes = $this->getCapitalReductionStocks();
        echo "getting high bias stocks...\n";
        $high_bias_stocks = $this->getHighBiasStocks();
        echo "processing...\n";
        $filtered_high_bias_stocks = array_filter($high_bias_stocks, function($value, $key) use ($stock_list, $capital_reduction_codes) {
            if (!$stock_list[$key]) {
                return false;
            }
            if ($capital_reduction_codes[$key]) {
                return false;
            }
            return true;
        }, ARRAY_FILTER_USE_BOTH);
        $i = 0;
        $latest_result = $this->getLatestResult();
        foreach ($filtered_high_bias_stocks as $code => $bias) {
            $i ++;
            $name = $stock_list[$code];
            $line = "{$i}. {$code} {$name}: {$bias}";
            if ($previous_bias = $latest_result[$code]) {
                $diff = $bias - $previous_bias;
                $line .= " ({$diff}%)";
            } else {
                $line .= ' (new)';
            }
            $result[] = $line;
        }
        print_r($result);
        $content = implode("\n", $result);
        $this->sendMessage($content);
        $this->saveToLatestResult($filtered_high_bias_stocks);
        $this->saveToHistory($filtered_high_bias_stocks);
    }

    private function getLatestResult(): array
    {
        $latest_result = json_decode(file_get_contents($this->latest_result_file), 1);
        if (0 === strlen($latest_result)) {
            echo "{$this->latest_result_file}: no contents, ignore.\n";
            return array();
        }
        if (!is_array($latest_result)) {
            echo "{$this->latest_result_file}: file contents are not in json format, ignore.\n";
            return array();
        }
        return $latest_result;
    }

    private function saveToLatestResult(array $stock_codes): void
    {
        $contents = json_encode($stock_codes);
        file_put_contents($this->latest_result_file, $contents);
    }

    private function saveToHistory(array $stock_codes): void
    {
        $file = "{$this->history_dir}/{$this->date}.json";
        $contents = json_encode($stock_codes);
        file_put_contents($file, $contents);
    }

    private function sendMessage($content): void
    {
        $subject = sprintf("%s 高乖離率", date('Y-m-d'));
        $mail = new PHPMailer(true);
        $mail->SMTPDebug = 2;
        $mail->CharSet = 'UTF-8';
        $mail->isMail();
        $mail->setFrom('yo@gettoeat.com', 'Yo');
        $mail->addAddress('turtleegg@gmail.com');
        $mail->Subject = $subject;
        $mail->Body = $content;
        $mail->send();
    }
}


class PickConfig
{
    public $data_dir = "data";
    public $date;
    public $bias_file_path;
}


$config = new PickConfig;
//$config->date = '20180906';
//$config->bias_file_path = __DIR__ . "tests/nlog_bias.html";
//$config->capital_reduction_file_path = __DIR__ . "/tests/capital_reduction.json";
//$config->stock_list_file_path = __DIR__ . "/tests/stock_list.html";

$command = new PickCommand($config);
if (!$command->checkDate()) {
    die('今天非交易日');
}
$command->exec();
