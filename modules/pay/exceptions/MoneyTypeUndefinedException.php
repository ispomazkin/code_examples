<?php

namespace common\modules\pay\exceptions;

/**
 * Исключение предназначено для прокидывания при выдаче,
 * если не задан тип вывода денежных средств [[MoneyType]]
 *
 */
class MoneyTypeUndefinedException extends \ErrorException
{
}