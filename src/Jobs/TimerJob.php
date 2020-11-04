<?php
declare(strict_types=1);

namespace Viezel\Nayra\Jobs;

use DOMXPath;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProcessMaker\Nayra\Contracts\Bpmn\FlowElementInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TimerEventDefinitionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Storage\BpmnDocument;
use Viezel\Nayra\Facades\Nayra;

class TimerJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $elementId;
    public $eventDefinitionPath;
    public $instanceId;
    public $next;
    public $tokenId;

    public function __construct(
        TimerEventDefinitionInterface $eventDefinition,
        FlowElementInterface $element,
        TokenInterface $token = null
    ) {
        $this->elementId = $element->getId();
        $this->eventDefinitionPath = $eventDefinition->getBpmnElement()->getNodePath();
        $this->instanceId = $token->getInstance()->getId();
        $this->tokenId = $token->getId();
    }

    public function handle()
    {
        $instance = Nayra::getInstanceById($this->instanceId);
        $eventDefinition = $this->getEventDefinition($instance->getOwnerDocument());
        Nayra::executeEvent($this->instanceId, $this->tokenId, $eventDefinition);
    }

    private function getEventDefinition(BpmnDocument $dom)
    {
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query($this->eventDefinitionPath);

        return $nodes ? $nodes->item(0)->getBpmnElementInstance() : null;
    }
}
