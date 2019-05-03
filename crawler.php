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
    private $debug_mode;

    public function __construct(PickConfig $config)
    {
        $data_dir = $config->data_dir;
        $this->date = $config->date ?: date('Ymd');
        $this->debug_mode = $config->debug_mode ?: false;
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

    private function getHighBiasStocks($bias_threshold)
    {
        $crawler = new HighBiasCrawler;
        if ($this->bias_file_path) {
            $crawler->setFilePath($this->bias_file_path);
        }
        $crawler->setThreshold($bias_threshold);
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

    public function checkHasRun()
    {
        if ($this->debug_mode) {
            return false;
        }
        $file_name = $this->getHistoryFileName();
        if (file_exists($file_name)) {
            return true;
        }
        return false;
    }

    public function exec()
    {
        if ($this->checkHasRun()) {
            echo "history file exists, skipped.\n";
            return;
        }
        echo "getting stock list...\n";
        $stock_list = $this->getStockList();
        echo "getting capital reduction stocks...\n";
        $capital_reduction_codes = $this->getCapitalReductionStocks();
        echo "getting high bias stocks...\n";
        $high_bias_stocks_20 = $this->getHighBiasStocks(20);
        $high_bias_stocks_30 = $this->getHighBiasStocks(30);
        echo "processing...\n";

        $filtered_high_bias_stocks_20 = $this->getFilteredHighBiasStocks($stock_list, $capital_reduction_codes, $high_bias_stocks_20);
        $filtered_high_bias_stocks_30 = $this->getFilteredHighBiasStocks($stock_list, $capital_reduction_codes, $high_bias_stocks_30);
        $result = array();
        $latest_result = $this->getLatestResult();
        foreach ($filtered_high_bias_stocks_30 as $code => $bias) {
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
        $content = implode("\n", $result);
        $this->sendMessage($content);
        $this->saveToLatestResult($filtered_high_bias_stocks_20);
        $this->saveToHistory($filtered_high_bias_stocks_20);
    }

    private function getFilteredHighBiasStocks($stock_list, $capital_reduction_codes, $high_bias_stocks): array
    {
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
        return $filtered_high_bias_stocks;
    }

    private function getLatestResult(): array
    {
        $file_contents = file_get_contents($this->latest_result_file);
        if (0 === strlen($file_contents)) {
            echo "{$this->latest_result_file}: no contents, ignore.\n";
            return array();
        }
        $latest_result = json_decode($file_contents, 1);
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

    private function getHistoryFileName()
    {
        $file_name = "{$this->history_dir}/{$this->date}.csv";
        return $file_name;
    }

    private function saveToHistory(array $stock_codes): void
    {
        $file_name = $this->getHistoryFileName();
        if (file_exists($file_name)) {
            unlink($file_name);
        }
        $file = fopen($file_name, "w");
        foreach ($stock_codes as $code => $bias) {
            fputcsv($file, array($code, $bias));
        }
        fclose($file);
    }

    private function sendMessage($content): void
    {
        $subject = sprintf("%s 高乖離率", date('Y-m-d'));
        $mail = new PHPMailer(true);
        $mail->SMTPDebug = 2;
        $mail->CharSet = 'UTF-8';
        $mail->isMail();
        $mail->setFrom('yo@gettoeat.com', 'Yo');
        $mail->addAddress('luyotw@gmail.com');
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


$options = getopt('d:x:');
$config = new PickConfig;
$config->date = $options['d'];
$config->debug_mode = $options['x'];
//$config->bias_file_path = __DIR__ . "tests/nlog_bias.html";
//$config->capital_reduction_file_path = __DIR__ . "/tests/capital_reduction.json";
//$config->stock_list_file_path = __DIR__ . "/tests/stock_list.html";

$command = new PickCommand($config);
if (!$command->checkDate()) {
    die('今天非交易日');
}
$command->exec();
