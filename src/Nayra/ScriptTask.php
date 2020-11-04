<?php
declare(strict_types=1);

namespace Viezel\Nayra\Nayra;

use Exception;
use Illuminate\Support\Facades\Log;
use ProcessMaker\Nayra\Bpmn\Models\ScriptTask as ScriptTaskBase;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use Viezel\Nayra\Nayra\ScriptFormats\BaseScriptExecutor;
use Viezel\Nayra\Nayra\ScriptFormats\PhpScript;

class ScriptTask extends ScriptTaskBase
{
    const scriptFormats = [
        'application/x-php' => PhpScript::class,
    ];

    public function runScript(TokenInterface $token)
    {
        if ($this->executeScript($token, $this->getScript(), $this->getScriptFormat())) {
            $this->complete($token);
        } else {
            $token->setStatus(ActivityInterface::TOKEN_STATE_FAILING);
        }
    }

    private function executeScript(TokenInterface $token, string $script, string $format): bool
    {
        try {
            $response = $this->runCode($script, $format);
            if (is_array($response)) {
                foreach ($response as $key => $value) {
                    $token->getInstance()->getDataStore()->putData($key, $value);
                }
            }

            return true;
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return false;
        }
    }

    private function runCode(string $script, string $format)
    {
        return $this->scriptFactory($format)->run($this, $script);
    }

    private function scriptFactory(string $format): BaseScriptExecutor
    {
        $class = self::scriptFormats[$format];

        return new $class;
    }
}
