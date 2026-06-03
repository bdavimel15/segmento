<?php

namespace App\Services\Segmentos;

class SegmentoRuleHelper
{
    /**
     * Normaliza regra para suportar grupos (v2) e formato legado (conditions flat).
     *
     * @return array<int, array{logic: string, conditions: array<int, array<string, mixed>>}>
     */
    public static function getGroups(array $regra): array
    {
        if (!empty($regra['groups']) && is_array($regra['groups'])) {
            $groups = [];

            foreach ($regra['groups'] as $group) {
                if (!is_array($group)) {
                    continue;
                }

                $conditions = array_values(array_filter(
                    $group['conditions'] ?? [],
                    fn ($c) => is_array($c) && !empty($c['field']) && !empty($c['operator'])
                ));

                if ($conditions === []) {
                    continue;
                }

                $groups[] = [
                    'logic' => strtoupper((string) ($group['logic'] ?? 'AND')) === 'OR' ? 'OR' : 'AND',
                    'conditions' => $conditions,
                ];
            }

            if ($groups !== []) {
                return $groups;
            }
        }

        $conditions = $regra['conditions'] ?? [];

        if (!is_array($conditions) || $conditions === []) {
            return [];
        }

        $conditions = array_values(array_filter(
            $conditions,
            fn ($c) => is_array($c) && !empty($c['field']) && !empty($c['operator'])
        ));

        if ($conditions === []) {
            return [];
        }

        return [[
            'logic' => strtoupper((string) ($regra['logic'] ?? 'AND')) === 'OR' ? 'OR' : 'AND',
            'conditions' => $conditions,
        ]];
    }

    public static function getTopLogic(array $regra): string
    {
        if (!empty($regra['groups']) && is_array($regra['groups'])) {
            return strtoupper((string) ($regra['logic'] ?? 'AND')) === 'OR' ? 'OR' : 'AND';
        }

        return strtoupper((string) ($regra['logic'] ?? 'AND')) === 'OR' ? 'OR' : 'AND';
    }

    /**
     * @param array<int, array{logic: string, conditions: array<int, array<string, mixed>>}> $groups
     * @return array<int, array<string, mixed>>
     */
    public static function flattenConditions(array $groups): array
    {
        $flat = [];

        foreach ($groups as $group) {
            foreach ($group['conditions'] ?? [] as $condition) {
                $flat[] = $condition;
            }
        }

        return $flat;
    }

    /**
     * Garante groups + conditions no payload salvo (compatibilidade).
     */
    public static function enrichRule(array $regra): array
    {
        $groups = self::getGroups($regra);

        if ($groups === []) {
            return $regra;
        }

        $regra['groups'] = $groups;
        $regra['logic'] = self::getTopLogic($regra);
        $regra['conditions'] = self::flattenConditions($groups);
        $regra['version'] = max(2, (int) ($regra['version'] ?? 1));

        return $regra;
    }
}
