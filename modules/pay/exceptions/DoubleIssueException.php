<?php

namespace common\modules\pay\exceptions;

/**
 * Срабатывает, если по заявке уже есть выдача
 *
 */
class DoubleIssueException extends \ErrorException
{
}
