<?php
declare(strict_types=1);

namespace Viezel\Nayra\Nayra;

use ProcessMaker\Nayra\Contracts\Bpmn\CollectionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ProcessInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ServiceTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Contracts\Engine\JobManagerInterface;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use Viezel\Nayra\Contracts\RequestRepositoryInterface;
use Viezel\Nayra\Jobs\ScriptTaskJob;
use Viezel\Nayra\Jobs\ServiceTaskJob;
use Viezel\Nayra\Models\Request;
use Viezel\Nayra\Repositories\InstanceRepository;

class Manager
{
    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var \ProcessMaker\Nayra\Contracts\EventBusInterface
     */
    private $dispatcher;

    /**
     * @var Engine
     */
    private $engine;

    /**
     * @var BpmnDocument
     */
    private $bpmnRepository;

    /**
     * @var InstanceRepository
     */
    private $instanceRepository;

    /**
     * @var string
     */
    private $bpmn;

    /**
     * @var RequestRepositoryInterface
     */
    private $requestRepository;

    /**
     * @var \Illuminate\Contracts\Foundation\Application|mixed
     */
    private $jobManager;

    public function __construct(RequestRepositoryInterface $requestRepository)
    {
        $this->requestRepository = $requestRepository;
        $this->repository = new Repository;
        $this->dispatcher = app('events');
        $this->jobManager = app(JobManagerInterface::class);
        $this->engine = new Engine($this->repository, $this->dispatcher, $this->jobManager);
        $this->registerEvents();
    }

    private function prepare()
    {
        $this->engine->clearInstances();
        $this->bpmnRepository = new BpmnDocument();
        $this->bpmnRepository->setEngine($this->engine);
        $this->bpmnRepository->setFactory($this->repository);
        $this->bpmnRepository->setSkipElementsNotImplemented(true);
        $this->engine->setRepository($this->repository);
        $this->instanceRepository = $this->repository->createExecutionInstanceRepository();
    }

    /**
     * Call a process
     *
     * @param string $processURL
     * @param array $data
     *
     * @return ExecutionInstanceInterface
     */
    public function callProcess(string $processURL, $data = [])
    {
        $this->prepare();
        $process = $this->loadProcess($processURL);
        $dataStorage = $process->getRepository()->createDataStore();
        $dataStorage->setData($data);
        $instance = $process->call();
        $this->engine->runToNextState();
        $this->saveState();

        return $instance;
    }

    /**
     * Start a process by start event
     *
     * @param string $processURL
     * @param string $eventId
     * @param array $data
     * @return ExecutionInstanceInterface
     */
    public function startProcess($processURL, $eventId, $data = []): ExecutionInstanceInterface
    {
        $this->prepare();
        //Process
        $process = $this->loadProcess($processURL);
        $event = $this->bpmnRepository->getStartEvent($eventId);

        //Create a new data store
        $dataStorage = $process->getRepository()->createDataStore();
        $dataStorage->setData($data);
        $instance = $this->engine->createExecutionInstance(
            $process,
            $dataStorage
        );
        $event->start($instance);

        $this->engine->runToNextState();
        $this->saveState();

        return $instance;
    }

    public function tasks($instanceId): CollectionInterface
    {
        $this->prepare();
        // Load the execution data
        $processData = $this->loadData($this->bpmnRepository, $instanceId);

        // Process and instance
        $instance = $this->engine->loadExecutionInstance($instanceId, $this->bpmnRepository);

        return $instance->getTokens();
    }

    /**
     * @return ExecutionInstanceInterface|null
     */
    public function getInstanceById($instanceId)
    {
        $this->prepare();
        // Load the execution data
        $this->processData = $this->loadData($this->bpmnRepository, $instanceId);

        return $this->engine->loadExecutionInstance($instanceId, $this->bpmnRepository);
    }

    /**
     * Complete a task
     *
     * @param string $instanceId
     * @param string $tokenId
     * @param array $data
     *
     * @return ExecutionInstanceInterface
     */
    public function completeTask($instanceId, $tokenId, $data = [])
    {
        $this->prepare();
        // Load the execution data
        $this->loadData($this->bpmnRepository, $instanceId);

        // Process and instance
        $instance = $this->engine->loadExecutionInstance($instanceId, $this->bpmnRepository);

        // Update data
        foreach ($data as $key => $value) {
            $instance->getDataStore()->putData($key, $value);
        }

        // Complete task
        $token = $instance->getTokens()->findFirst(function ($token) use ($tokenId) {
            return $token->getId() === $tokenId;
        });
        $task = $this->bpmnRepository->getActivity($token->getProperty('element'));
        $task->complete($token);
        $this->engine->runToNextState();
        $this->saveState();

        return $instance;
    }

    /**
     *
     * @param string $instanceId
     *
     * @return ExecutionInstanceInterface|null
     */
    public function cancelProcess($instanceId)
    {
        $this->prepare();

        $processData = $this->loadData($this->bpmnRepository, $instanceId);
        $processData->status = 'CANCELED';
        $processData->save();

        return $this->engine->loadExecutionInstance($instanceId, $this->bpmnRepository);
    }

    /**
     * Execute a script task
     *
     * @param string $instanceId
     * @param string $tokenId
     *
     * @return ExecutionInstanceInterface
     */
    public function executeScript($instanceId, $tokenId)
    {
        $this->prepare();
        // Load the execution data
        $model = $this->loadData($this->bpmnRepository, $instanceId);

        // Process and instance
        $instance = $this->engine->loadExecutionInstance($instanceId, $this->bpmnRepository);

        // Complete task
        $token = $instance->getTokens()->findFirst(function ($token) use ($tokenId) {
            return $token->getId() === $tokenId;
        });
        $task = $this->bpmnRepository->getScriptTask($token->getProperty('element'));
        $task->runScript($token);
        $this->engine->runToNextState();
        $this->saveState();

        return $instance;
    }

    /**
     * Execute an event
     *
     * @param string $instanceId
     * @param string $tokenId
     * @param mixed $eventDefinition
     *
     * @return ExecutionInstanceInterface
     */
    public function executeEvent($instanceId, $tokenId, $eventDefinition)
    {
        $this->prepare();
        // Load the execution data
        $model = $this->loadData($this->bpmnRepository, $instanceId);

        // Process and instance
        $instance = $this->engine->loadExecutionInstance($instanceId, $this->bpmnRepository);

        // Execute event
        $token = $instance->getTokens()->findFirst(function ($token) use ($tokenId) {
            return $token->getId() === $tokenId;
        });

        $token->getOwnerElement()->execute($eventDefinition, $instance);
        $this->engine->runToNextState();
        $this->saveState();

        // Return the instance id
        return $instance;
    }

    private function loadProcess(string $filename): ProcessInterface
    {
        $this->bpmnRepository->load($filename);
        $this->bpmn = $filename;

        return $this->bpmnRepository->getElementsByTagName('process')
            ->item(0)
            ->getBpmnElementInstance();
    }

    /**
     *
     * @param BpmnDocument $repository
     * @param $instanceId
     *
     * @return ?Request
     */
    private function loadData(BpmnDocument $repository, $instanceId)
    {
        $processData = $this->requestRepository->find($instanceId);
        $this->loadProcess($processData->bpmn);

        return $processData;
    }

    private function registerEvents(): void
    {
        $this->dispatcher->listen(
            ScriptTaskInterface::EVENT_SCRIPT_TASK_ACTIVATED,
            function (ScriptTaskInterface $scriptTask, TokenInterface $token) {
                $this->saveProcessInstance($token->getInstance());
                ScriptTaskJob::dispatch($token);
            }
        );

        $this->dispatcher->listen(
            ServiceTaskInterface::EVENT_SERVICE_TASK_ACTIVATED,
            function (ServiceTaskInterface $serviceTask, TokenInterface $token) {
                $this->saveProcessInstance($token->getInstance());
                ServiceTaskJob::dispatch($token);
            }
        );
    }

    private function saveState(): void
    {
        $processes = $this->bpmnRepository->getElementsByTagNameNS(BpmnDocument::BPMN_MODEL, 'process');
        foreach ($processes as $node) {
            $process = $node->getBpmnElementInstance();
            foreach ($process->getInstances() as $instance) {
                $this->saveProcessInstance($instance);
            }
        }
    }

    public function saveProcessInstance(ExecutionInstanceInterface $instance): self
    {
        $this->instanceRepository->saveProcessInstance($instance, $this->bpmn);

        return $this;
    }
}
