<?php

declare(strict_types=1);

namespace PhilipRehberger\EnvValidator;

final class PendingFileValidation extends PendingValidation
{
    /**
     * @param  array<string>  $required
     * @param  array<string, string>  $env
     */
    public function __construct(array $required, array $env)
    {
        parent::__construct($required);
        $this->envSource = $env;
    }
}
