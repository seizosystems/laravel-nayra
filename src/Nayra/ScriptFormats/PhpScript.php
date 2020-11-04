<?php
declare(strict_types=1);

namespace Viezel\Nayra\Nayra\ScriptFormats;

use Viezel\Nayra\Nayra\ScriptTask;

class PhpScript extends BaseScriptExecutor
{
    public function runFile(ScriptTask $scriptTask)
    {
        $self = $this;
        $closure = function (ScriptTask $scriptTask) use ($self) {
            return require $self->filename;
        };

        return $closure->call($scriptTask, $scriptTask);
    }
}
