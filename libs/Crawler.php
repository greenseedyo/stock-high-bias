<?php
namespace Luyo\Stock;

abstract class Crawler
{
    protected $file_path;
    protected static $cached_contents;

    abstract public function getUrl(): string;

    public function setFilePath($file_path): void
    {
        $this->file_path = $file_path;
    }

    protected function getContentByFile(): string
    {
        return file_get_contents($this->file_path);
    }

    protected function getContentByUrl(): string
    {
        return file_get_contents($this->getUrl());
    }

    protected function getContent(): string
    {
        if (self::$cached_contents) {
            return self::$cached_contents;
        }
        if (isset($this->file_path)) {
            return $this->getContentByFile();
        } else {
            return $this->getContentByUrl();
        }
    }

    abstract public function run();
}
