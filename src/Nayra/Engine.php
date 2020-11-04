<?php
declare(strict_types=1);

namespace Viezel\Nayra\Nayra;

use ProcessMaker\Nayra\Contracts\Engine\EngineInterface;
use ProcessMaker\Nayra\Contracts\Engine\JobManagerInterface;
use ProcessMaker\Nayra\Contracts\EventBusInterface;
use ProcessMaker\Nayra\Contracts\RepositoryInterface;
use ProcessMaker\Nayra\Engine\EngineTrait;

class Engine implements EngineInterface
{
    use EngineTrait;

    private RepositoryInterface $repository;
    protected EventBusInterface $dispatcher;

    public function __construct(RepositoryInterface $repository, EventBusInterface $dispatcher, JobManagerInterface $jobManager = null)
    {
        $this->setRepository($repository);
        $this->setDispatcher($dispatcher);
        $this->setJobManager($jobManager);
    }

    public function getDispatcher(): EventBusInterface
    {
        return $this->dispatcher;
    }

    public function setDispatcher(EventBusInterface $dispatcher): self
    {
        $this->dispatcher = $dispatcher;

        return $this;
    }

    public function getRepository(): RepositoryInterface
    {
        return $this->repository;
    }

    public function setRepository(RepositoryInterface $repository): self
    {
        $this->repository = $repository;

        return $this;
    }

    public function clearInstances(): void
    {
        $this->executionInstances = [];
    }
}
