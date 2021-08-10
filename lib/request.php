<?php
namespace MyGameCollection\Lib;
 
class Request
{
    private array $queryVars = [];
    private array $postVars = [];
    private array $cookies = [];
    private array $files = [];

    private array $postBody = [];

    public function __construct()
    {
        $this->getQueryVars();
        $this->getPostVars();
        $this->getCookies();
        $this->getFiles();
    }

    private function getQueryVars()
    {
        foreach ($_GET as $key => $value) {
            $this->queryVars[$key] = $value;
        }
    }

    private function getPostVars()
    {
        foreach ($_POST as $key => $value) {
            $this->postVars[$key] = $value;
        }

        $this->postBody = $_POST;
    }

    private function getCookies()
    {
        foreach ($_COOKIE as $key => $value) {
            $this->cookies[$key] = $value;
        }
    }

    private function getFiles()
    {
        foreach ($_FILES as $field => $file) {
            if (is_array($file['tmp_name'])) {
                //multiple files
                $this->files[$field] = [];
                for ($i = 0; $i < count($file['tmp_name']); $i++) {
                    $file = [
                        'name' => $file['name'][$i],
                        'type' => $file['type'][$i],
                        'size' => $file['size'][$i],
                        'tmp_name' => $file['tmp_name'][$i],
                        'error' => $file['error'][$i],
                    ];
                    $this->files[$field][] = $file;
                }
            } else {
                //single file
                $this->files[$field] = [$file];
            }
        }
    }

    public function isGet(): bool
    {
        return filter_input(INPUT_SERVER, 'REQUEST_METHOD') == 'GET';
    }

    public function isPost(): bool
    {
        return filter_input(INPUT_SERVER, 'REQUEST_METHOD') == 'POST';
    }

    public function getInt($var): int
    {
        return (isset($this->queryVars[$var]) ? filter_var($this->queryVars[$var], FILTER_SANITIZE_NUMBER_INT) : 0);
    }

    public function getStr($var): string
    {
        return (isset($this->queryVars[$var]) ? filter_var($this->queryVars[$var], FILTER_SANITIZE_STRING) : '');
    }

    public function postInt($var): int
    {
        return (isset($this->postVars[$var]) ? filter_var($this->postVars[$var], FILTER_SANITIZE_NUMBER_INT) : 0);
    }

    public function postStr($var): string
    {
        return (isset($this->postVars[$var]) ? filter_var($this->postVars[$var], FILTER_SANITIZE_STRING) : '');
    }

    public function cookie($name): string
    {
        return $this->cookies[$name] ?? '';
    }

    public function file($field): array
    {
        return $this->files[$field] ?? [];
    }
}