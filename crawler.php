<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
$loader = require __DIR__ . '/vendor/autoload.php';

use Luyo\Stock\Nlog\HighBiasCrawler;
use Luyo\Stock\Twse\CapitalReductionCrawler;
use Luyo\Stock\Wespai\StockListCrawler;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class PickCommand
{
    public function setBiasFilePath($file_path)
    {
        $this->bias_file_path = $file_path;
    }

    public function setCapitalReductionFilePath($file_path)
    {
        $this->capital_reduction_file_path = $file_path;
    }

    public function setStockListFilePath($file_path)
    {
        $this->stock_list_file_path = $file_path;
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
        foreach ($filtered_high_bias_stocks as $code => $bias) {
            $i ++;
            $name = $stock_list[$code];
            $result[] = "({$i}) {$code} {$name}: {$bias}";
        }
        print_r($result);
        $content = implode("\n", $result);
        $this->sendMessage($content);
    }

    private function sendMessage($content)
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

$command = new PickCommand;
//$command->setBiasFilePath(__DIR__ . "/tests/nlog_bias.html");
//$command->setCapitalReductionFilePath(__DIR__ . "/tests/capital_reduction.json");
//$command->setStockListFilePath(__DIR__ . "/tests/stock_list.html");
$command->exec();
