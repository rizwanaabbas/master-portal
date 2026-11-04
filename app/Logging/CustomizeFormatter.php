<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\LineFormatter;


class CustomizeFormatter
{
    /**
     * Customize the given logger instance.
     *
     * @param  \Illuminate\Log\Logger  $logger
     * @return void
     */

    public function __invoke(Logger $logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter(new LineFormatter(
                '[%datetime%] %channel%.%level_name%: %message% %context% %extra%' . "\n",
                null, // Datetime format
                true, // allowInlineLineBreaks option, default false
                true  // ignoreEmptyContextAndExtra option, default false
            ));
        }
    }
}
