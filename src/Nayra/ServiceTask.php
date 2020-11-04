<?php
declare(strict_types=1);

namespace Viezel\Nayra\Nayra;

use Illuminate\Support\Facades\Log;
use ProcessMaker\Nayra\Bpmn\Models\ServiceTask as BaseServiceTask;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;

class ServiceTask extends BaseServiceTask
{
    public function run(TokenInterface $token): self
    {
        if ($this->executeService($token, $this->getImplementation())) {
            $this->complete($token);
        } else {
            $token->setStatus(ActivityInterface::TOKEN_STATE_FAILING);
        }

        return $this;
    }

    private function executeService(TokenInterface $token, $implementation): bool
    {
        try {
            if (is_string($implementation) && strpos($implementation, '@')) {
                [$class, $method] = explode('@', $implementation);

                return app($class)->$method($token);
            }

            return call_user_func($implementation);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }

        return false;
    }
}
