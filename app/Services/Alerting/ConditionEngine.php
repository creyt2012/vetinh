<?php

namespace App\Services\Alerting;

use Illuminate\Support\Facades\Log;

class ConditionEngine
{
    /**
     * Evaluate a metric against a collection of DB rules.
     */
    public function evaluateModelRules(array $currentState, ?array $previousState, $rules): array
    {
        foreach ($rules as $rule) {
            $triggered = false;

            // Handle trend operators
            if (in_array($rule->operator, ['trend_up', 'trend_down'])) {
                if ($previousState && isset($previousState[$rule->parameter]) && isset($currentState[$rule->parameter])) {
                    $diff = $currentState[$rule->parameter] - $previousState[$rule->parameter];
                    if ($rule->operator === 'trend_up' && $diff > $rule->threshold)
                        $triggered = true;
                    if ($rule->operator === 'trend_down' && $diff < -$rule->threshold)
                        $triggered = true;
                }
            } else {
                // Absolute comparison
                if (isset($currentState[$rule->parameter])) {
                    $triggered = $this->compare($currentState[$rule->parameter], $rule->operator, $rule->threshold);
                }
            }

            if ($triggered) {
                return [
                    'triggered' => true,
                    'level' => $rule->severity,
                    'reason' => $rule->name,
                    'rule_id' => $rule->id,
                    'channels' => $rule->channels
                ];
            }
        }

        return ['triggered' => false];
    }

    public function evaluate(array $currentState, ?array $previousState, array $rules): array
    {
        // Existing logic for nested rules (backward compatibility)
        foreach ($rules as $rule) {
            if ($this->evaluateRule($currentState, $previousState, $rule)) {
                return [
                    'triggered' => true,
                    'level' => $rule['level'] ?? 'WARNING',
                    'reason' => $rule['description'] ?? 'Threshold exceeded'
                ];
            }
        }

        return ['triggered' => false];
    }

    protected function evaluateRule(array $current, ?array $previous, array $rule): bool
    {
        $logic = $rule['logic'] ?? 'AND';
        $results = [];

        foreach ($rule['conditions'] as $condition) {
            $results[] = $this->evaluateCondition($current, $previous, $condition);
        }

        if ($logic === 'AND') {
            return !empty($results) && !in_array(false, $results, true);
        }

        if ($logic === 'OR') {
            return in_array(true, $results, true);
        }

        return false;
    }

    protected function evaluateCondition(array $current, ?array $previous, array $condition): bool
    {
        $param = $condition['parameter'];
        $operator = $condition['operator'];
        $value = $condition['value'];
        $type = $condition['type'] ?? 'absolute'; // absolute, trend

        if (!isset($current[$param])) {
            return false;
        }

        if ($type === 'trend') {
            if (!$previous || !isset($previous[$param])) {
                return false;
            }
            $diff = $current[$param] - $previous[$param];
            return $this->compare($diff, $operator, $value);
        }

        return $this->compare($current[$param], $operator, $value);
    }

    protected function compare($actual, $operator, $expected): bool
    {
        switch ($operator) {
            case '>':
                return $actual > $expected;
            case '<':
                return $actual < $expected;
            case '>=':
                return $actual >= $expected;
            case '<=':
                return $actual <= $expected;
            case '==':
                return $actual == $expected;
            default:
                return false;
        }
    }

    /**
     * Default enterprise rules for immediate safety.
     */
    public function getDefaultRules(): array
    {
        return [
            [
                'description' => 'Extreme Storm Condition: High Wind + Heavy Rain',
                'level' => 'CRITICAL',
                'logic' => 'AND',
                'conditions' => [
                    ['parameter' => 'wind_speed', 'operator' => '>', 'value' => 80],
                    ['parameter' => 'rain_intensity', 'operator' => '>', 'value' => 30]
                ]
            ],
            [
                'description' => 'Flash Flood Risk: Rapid Pressure Drop',
                'level' => 'WARNING',
                'logic' => 'AND',
                'conditions' => [
                    ['parameter' => 'pressure', 'operator' => '<', 'value' => -5, 'type' => 'trend']
                ]
            ],
            [
                'description' => 'Extreme Heat Alert',
                'level' => 'CRITICAL',
                'logic' => 'AND',
                'conditions' => [
                    ['parameter' => 'temperature', 'operator' => '>', 'value' => 45]
                ]
            ]
        ];
    }
}
