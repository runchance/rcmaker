<?php
namespace support\exception;
use RC\Exception\ExceptionHandler;
class Handler extends ExceptionHandler{
	public $dontReport = [
        BusinessException::class,
    ];

    public function report(\Throwable $exception)
    {
        parent::report($exception);
    }

    public function render(\Throwable $exception,$request) : array
    {
        return parent::render($exception,$request);
    }
}
?>