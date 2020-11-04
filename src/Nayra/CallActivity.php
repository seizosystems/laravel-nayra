<?php
declare(strict_types=1);

namespace Viezel\Nayra\Nayra;

use ProcessMaker\Nayra\Bpmn\ActivitySubProcessTrait;
use ProcessMaker\Nayra\Bpmn\Events\ActivityActivatedEvent;
use ProcessMaker\Nayra\Bpmn\Events\ActivityClosedEvent;
use ProcessMaker\Nayra\Bpmn\Events\ActivityCompletedEvent;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\CallableElementInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\CallActivityInterface;

class CallActivity implements CallActivityInterface
{
    use ActivitySubProcessTrait;

    protected function getBpmnEventClasses(): array
    {
        return [
            ActivityInterface::EVENT_ACTIVITY_ACTIVATED => ActivityActivatedEvent::class,
            ActivityInterface::EVENT_ACTIVITY_COMPLETED => ActivityCompletedEvent::class,
            ActivityInterface::EVENT_ACTIVITY_CLOSED => ActivityClosedEvent::class,
        ];
    }

    public function getCalledElement(): CallableElementInterface
    {
        return $this->getProperty(CallActivityInterface::BPMN_PROPERTY_CALLED_ELEMENT);
    }

    public function setCalledElement($callableElement): self
    {
        $this->setProperty(CallActivityInterface::BPMN_PROPERTY_CALLED_ELEMENT, $callableElement);

        return $this;
    }
}
