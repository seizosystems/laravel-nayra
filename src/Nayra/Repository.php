<?php
declare(strict_types=1);

namespace Viezel\Nayra\Nayra;

use ProcessMaker\Nayra\Contracts\Bpmn\CallActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\FormalExpressionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ServiceTaskInterface;
use ProcessMaker\Nayra\Contracts\Repositories\ExecutionInstanceRepositoryInterface;
use ProcessMaker\Nayra\Contracts\Repositories\TokenRepositoryInterface;
use ProcessMaker\Nayra\Contracts\RepositoryInterface;
use ProcessMaker\Nayra\RepositoryTrait;

class Repository implements RepositoryInterface
{
    use RepositoryTrait;

    public function createFormalExpression(): FormalExpressionInterface
    {
        return new FormalExpression();
    }

    public function createCallActivity(): CallActivityInterface
    {
        return new CallActivity();
    }

    public function createScriptTask(): ScriptTaskInterface
    {
        return new ScriptTask();
    }

    public function createServiceTask(): ServiceTaskInterface
    {
        return new ServiceTask();
    }

    public function createExecutionInstanceRepository(): ExecutionInstanceRepositoryInterface
    {
        return app(ExecutionInstanceRepositoryInterface::class);
    }

    public function getTokenRepository(): TokenRepositoryInterface
    {
        return app(TokenRepositoryInterface::class);
    }
}
