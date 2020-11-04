<?php
declare(strict_types=1);

namespace Viezel\Nayra\Nayra;

use DateInterval;
use DatePeriod;
use DateTime;
use Exception;
use ProcessMaker\Nayra\Bpmn\FormalExpressionTrait;
use ProcessMaker\Nayra\Contracts\Bpmn\FormalExpressionInterface;

class FormalExpression implements FormalExpressionInterface
{
    use FormalExpressionTrait;

    /**
     * Get the body of the Expression.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->getProperty(FormalExpressionInterface::BPMN_PROPERTY_BODY);
    }

    /**
     * Get the type that this Expression returns when evaluated.
     *
     * @return string
     */
    public function getEvaluatesToType()
    {
        return $this->getProperty(FormalExpressionInterface::BPMN_PROPERTY_EVALUATES_TO_TYPE_REF);
    }

    /**
     * Get the expression language.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->getProperty(FormalExpressionInterface::BPMN_PROPERTY_LANGUAGE);
    }

    /**
     * Invoke the format expression.
     *
     * @param mixed $data
     *
     * @return string
     */
    public function __invoke($data)
    {
        extract($data);

        return $this->getDateExpression()
            ?: $this->getCycleExpression()
            ?: $this->getDurationExpression()
            ?: eval('return ' . $this->getBody() . ';');
    }

    private function isDateExpression(): bool
    {
        $expression = $this->getProperty(FormalExpressionInterface::BPMN_PROPERTY_BODY);

        try {
            $date = new DateTime($expression);
        } catch (Exception $e) {
            return false;
        }

        return $date !== false;
    }

    private function isCycleExpression(): bool
    {
        $expression = $this->getProperty(FormalExpressionInterface::BPMN_PROPERTY_BODY);

        try {
            $cycle = new DatePeriod($expression);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    private function isDurationExpression(): bool
    {
        $expression = $this->getProperty(FormalExpressionInterface::BPMN_PROPERTY_BODY);

        try {
            $interval = new DateInterval($expression);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
