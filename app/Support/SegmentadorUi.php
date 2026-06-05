<?php

namespace App\Support;

class SegmentadorUi
{
    public static function devMode(): bool
    {
        return filter_var(config('segmentador.dev_mode'), FILTER_VALIDATE_BOOL);
    }

    public static function isAdmin(): bool
    {
        return (bool) session('segmentador_admin', false);
    }

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            'pendente_validacao' => 'Pendente',
            'pendente' => 'Pendente',
            'rascunho' => 'Pendente',
            'em_analise' => 'Em análise',
            'validada' => 'Aprovado',
            'validado' => 'Aprovado',
            'reprovada' => 'Reprovado',
            'reprovado' => 'Reprovado',
            'inativa' => 'Inativo',
            'erro' => 'Com erro',
        ];
    }

    /** @return array<string, string> */
    public static function statusBadgeClasses(): array
    {
        return [
            'pendente_validacao' => 'badge-warning',
            'pendente' => 'badge-warning',
            'rascunho' => 'badge-warning',
            'em_analise' => 'badge-info',
            'validada' => 'badge-success',
            'validado' => 'badge-success',
            'reprovada' => 'badge-danger',
            'reprovado' => 'badge-danger',
            'inativa' => 'badge-neutral',
            'erro' => 'badge-danger',
        ];
    }

    public static function statusLabel(?string $status): string
    {
        return self::statusLabels()[$status ?? ''] ?? ucfirst(str_replace('_', ' ', (string) $status));
    }

    public static function statusBadgeClass(?string $status): string
    {
        return self::statusBadgeClasses()[$status ?? ''] ?? 'badge-neutral';
    }

    public static function canExportSegment(?string $status): bool
    {
        if (in_array($status, ['reprovada', 'inativa', 'erro'], true)) {
            return false;
        }

        if ($status === 'validada') {
            return true;
        }

        return self::exportWhenPending();
    }

    public static function exportWhenPending(): bool
    {
        return filter_var(config('segmentador.export_when_pending'), FILTER_VALIDATE_BOOL);
    }
}
