<?php

namespace common\modules\pay\exceptions;

/**
 * Срабатывает, если у драйвера нет прав для заданной операции
 *
 */
class DriverInvalidPermissionsException extends \ErrorException
{
}