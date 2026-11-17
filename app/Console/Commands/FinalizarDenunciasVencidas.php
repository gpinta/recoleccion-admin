<?php

namespace App\Console\Commands;

use App\Enums\DenunciaEstado;
use App\Models\Denuncia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FinalizarDenunciasVencidas extends Command
{
    protected $signature = 'denuncias:finalizar-vencidas';

    protected $description =
        'Finaliza automáticamente las denuncias resueltas cuyo plazo de espera ya venció.';

    public function handle(): int
    {
        $finalizadas = 0;

        Denuncia::query()
            ->where(
                'estado',
                DenunciaEstado::RESUELTO->value
            )
            ->whereNotNull(
                'estado_cambiado_at'
            )
            ->where(
                'estado_cambiado_at',
                '<=',
                now()->subHours(
                    Denuncia::HORAS_PARA_FINALIZAR
                )
            )
            ->select('id')
            ->orderBy('id')
            ->chunkById(
                100,
                function ($denuncias) use (
                    &$finalizadas
                ): void {
                    foreach ($denuncias as $denuncia) {
                        $finalizada =
                            DB::transaction(
                                function () use (
                                    $denuncia
                                ): bool {
                                    /*
                                     * Volvemos a consultar y bloqueamos la fila
                                     * para evitar competir con una modificación
                                     * administrativa realizada al mismo tiempo.
                                     */
                                    $denunciaBloqueada =
                                        Denuncia::query()
                                            ->whereKey(
                                                $denuncia->id
                                            )
                                            ->lockForUpdate()
                                            ->first();

                                    if (!$denunciaBloqueada) {
                                        return false;
                                    }

                                    /*
                                     * Revalidamos dentro de la transacción.
                                     * Esto evita finalizar una denuncia que
                                     * dejó de estar en Resuelto entre la consulta
                                     * inicial y el bloqueo.
                                     */
                                    if (
                                        $denunciaBloqueada
                                            ->estadoEnum()
                                        !==
                                        DenunciaEstado::RESUELTO
                                    ) {
                                        return false;
                                    }

                                    if (
                                        !$denunciaBloqueada
                                            ->puedeFinalizar()
                                    ) {
                                        return false;
                                    }

                                    $denunciaBloqueada
                                        ->cambiarEstado(
                                            DenunciaEstado::FINALIZADO
                                        );

                                    return true;
                                }
                            );

                        if ($finalizada) {
                            $finalizadas++;
                        }
                    }
                }
            );

        $this->info(
            sprintf(
                'Denuncias finalizadas automáticamente: %d',
                $finalizadas
            )
        );

        return self::SUCCESS;
    }
}
