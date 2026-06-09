<?php

declare(strict_types = 1);

namespace App\Exceptions;

// невалидный номер/email, retry не имеет смысла
use RuntimeException;

class InvalidRecipientException extends RuntimeException {}