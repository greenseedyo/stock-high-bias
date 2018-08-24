<?php
namespace Luyo\Stock;

abstract class Crawler
{
    protected $file_path;

    abstract public function getUrl(): string;

    public function setFilePath($file_path): void
    {
        $this->file_path = $file_path;
    }

    protected function getContent(): string
    {
        if (isset($this->file_path)) {
            return file_get_contents($this->file_path);
        } else {
            return file_get_contents($this->getUrl());
        }
    }

    abstract public function run();
}
