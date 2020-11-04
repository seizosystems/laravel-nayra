<?php
declare(strict_types=1);

namespace Viezel\Nayra\Jobs;

use DateTime;
use DOMXPath;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Nayra\Bpmn\Models\DatePeriod;
use ProcessMaker\Nayra\Contracts\Bpmn\EventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\FlowElementInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TimerEventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\JobManagerInterface;
use Viezel\Nayra\Facades\Nayra;

class CycleTimerJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $cycle;
    public $elementId;
    public $eventDefinitionPath;
    public $instanceId;
    public $next;
    public $tokenId;

    public function __construct(
        $cycle,
        TimerEventDefinitionInterface $eventDefinition,
        FlowElementInterface $element,
        TokenInterface $token = null,
        DateTime $next
    ) {
        $this->cycle = json_encode($cycle);
        $this->elementId = $element->getId();
        $this->eventDefinitionPath = $eventDefinition->getBpmnElement()->getNodePath();
        $this->instanceId = $token->getInstance()->getId();
        $this->next = $next->format(DateTime::ATOM);
        $this->tokenId = $token->getId();
    }

    public function handle()
    {
        $instance = Nayra::getInstanceById($this->instanceId);
        $token = $instance->getTokens()->findFirst(function ($token) {
            return $token->getId() === $this->tokenId;
        });
        if ($token->getStatus() === EventInterface::TOKEN_STATE_ACTIVE) {
            Nayra::executeEvent($this->instanceId, $this->tokenId);
            $element = $instance->getOwnerDocument()->getElementInstanceById($this->elementId);
            $eventDefinition = $this->getEventDefinition($instance->getOwnerDocument());
            $manager = app(JobManagerInterface::class);
            $next = $manager->getNextDateTimeCycle($this->getCycle(), $this->getNext());
            CycleTimerJob::dispatch($this->getCycle(), $eventDefinition, $element, $token, $next)
                ->delay($next);
        }
    }

    private function getCycle(): DatePeriod
    {
        return $this->loadTimerFromJson($this->cycle);
    }

    /**
     * @return DatePeriod
     */
    private function getEventDefinition($dom)
    {
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query($this->eventDefinitionPath);

        return $nodes ? $nodes->item(0)->getBpmnElementInstance() : null;
    }

    private function getNext(): DateTime
    {
        return new DateTime($this->next);
    }

    private function loadTimerFromJson($timer): DatePeriod
    {
        $start = $timer->start ? $this->loadTimerFromJson($timer->start) : null;
        $interval = $this->loadTimerFromJson($timer->interval);
        $end = $timer->end ? $this->loadTimerFromJson($timer->end) : null;
        $recurrences = $timer->recurrences;

        return new DatePeriod($start, $interval, [$end, $recurrences - 1]);
    }
}
