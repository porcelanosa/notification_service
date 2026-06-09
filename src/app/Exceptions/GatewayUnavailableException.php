<?php

declare(strict_types = 1);


namespace App\Exceptions;

// шлюз недоступен, делаем retry
class GatewayUnavailableException extends \RuntimeException {}