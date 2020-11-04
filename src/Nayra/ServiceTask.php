<?php
declare(strict_types=1);

namespace Viezel\Nayra\Nayra;

use Illuminate\Support\Facades\Log;
use ProcessMaker\Nayra\Bpmn\Models\ServiceTask as BaseServiceTask;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;

class ServiceTask extends BaseServiceTask
{
    /**
     * Runs the Service Task
     *
     * @param TokenInterface $token
     *
     * @return \ProcessMaker\Nayra\Bpmn\Models\ServiceTask
     */
    public function run(TokenInterface $token)
    {
        //if the script runs correctly complete te activity, otherwise set the token to failed state
        if ($this->executeService($token, $this->getImplementation())) {
            $this->complete($token);
        } else {
            $token->setStatus(ActivityInterface::TOKEN_STATE_FAILING);
        }

        return $this;
    }
    
    /**
     * Service Task runner for testing purposes
     *
     * @param TokenInterface $token
     * @param mixed $implementation
     *
     * @return bool
     */
    private function executeService(TokenInterface $token, $implementation)
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
