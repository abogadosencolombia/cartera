<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CalculateLateFees extends Command
{
    protected $signature = 'app:calculate-late-fees';
    protected $description = 'Calcula y acumula los intereses de mora para cuotas y cargos vencidos.';

    public function handle()
    {
        $this->info('Iniciando cálculo y acumulación de intereses de mora...');
        $today = Carbon::today();

        $tasaVigente = DB::table('intereses_tasas')
                            ->where('fecha_vigencia', '<=', $today)
                            ->orderByDesc('fecha_vigencia')
                            ->first();

        if (!$tasaVigente) {
            $this->error('No se encontró una tasa de interés vigente. Abortando.');
            return self::FAILURE;
        }

        $tasa_ea_decimal = $tasaVigente->tasa_ea / 100;
        $tasa_diaria = pow(1 + $tasa_ea_decimal, 1/365) - 1;
        
        $this->info("Tasa de interés vigente: {$tasaVigente->tasa_ea}% EA. Tasa diaria calculada: " . number_format($tasa_diaria * 100, 6) . "%");

        $this->procesarCuotasVencidas($today, $tasa_diaria);
        $this->procesarCargosVencidos($today, $tasa_diaria);

        $this->info('Cálculo de intereses finalizado.');
        return self::SUCCESS;
    }

    private function procesarCuotasVencidas(Carbon $today, float $tasa_diaria): void
    {
        $this->line("\n[Procesando Cuotas Vencidas]");
        $cuotasVencidas = DB::table('contrato_cuotas as cc')
            ->join('contratos as c', 'cc.contrato_id', '=', 'c.id')
            ->whereIn('c.estado', ['ACTIVO', 'PAGOS_PENDIENTES', 'EN_MORA']) // Añadimos EN_MORA si lo usas
            ->where('cc.estado', 'PENDIENTE')
            ->where('cc.fecha_vencimiento', '<', $today)
            ->select('cc.id', 'cc.valor', 'cc.fecha_vencimiento')
            ->get();

        if ($cuotasVencidas->isEmpty()) {
            $this->info("No se encontraron cuotas vencidas.");
            return;
        }

        foreach ($cuotasVencidas as $cuota) {
            $diasVencidos = Carbon::parse($cuota->fecha_vencimiento)->diffInDays($today);
            $intereses_totales = round(($cuota->valor * $tasa_diaria) * $diasVencidos, 2);

            DB::table('contrato_cuotas')
                ->where('id', $cuota->id)
                ->update(['intereses_mora_acumulados' => $intereses_totales]);
            
            $this->line(" -> Cuota #{$cuota->id}: {$diasVencidos} días vencidos. Mora total actualizada a $" . number_format($intereses_totales, 2));
        }
    }

    /**
     * Busca cargos vencidos según su fecha de inicio de intereses y actualiza su mora.
     */
    private function procesarCargosVencidos(Carbon $today, float $tasa_diaria): void
    {
        $this->line("\n[Procesando Cargos Vencidos]");
        
        // --- LÓGICA MEJORADA ---
        // Buscamos cargos pendientes cuya fecha de inicio de intereses ya haya pasado.
        $cargosVencidos = DB::table('contrato_cargos as cc')
            ->join('contratos as c', 'cc.contrato_id', '=', 'c.id')
            ->whereIn('c.estado', ['ACTIVO', 'PAGOS_PENDIENTES', 'EN_MORA'])
            ->where('cc.estado', 'PENDIENTE')
            ->whereNotNull('cc.fecha_inicio_intereses') // Solo los que tienen fecha de inicio
            ->where('cc.fecha_inicio_intereses', '<', $today) // Y esa fecha ya pasó
            ->select('cc.id', 'cc.monto', 'cc.fecha_inicio_intereses')
            ->get();
            
        if ($cargosVencidos->isEmpty()) {
            $this->info("No se encontraron cargos con mora activa.");
            return;
        }

        foreach ($cargosVencidos as $cargo) {
            // Se calculan los días de mora desde la fecha de inicio establecida
            $diasDeMora = Carbon::parse($cargo->fecha_inicio_intereses)->diffInDays($today);

            if ($diasDeMora <= 0) continue;

            $intereses_totales = round(($cargo->monto * $tasa_diaria) * $diasDeMora, 2);

            DB::table('contrato_cargos')
                ->where('id', $cargo->id)
                ->update(['intereses_mora_acumulados' => $intereses_totales]);
            
            $this->line(" -> Cargo #{$cargo->id}: {$diasDeMora} días de mora. Mora total actualizada a $" . number_format($intereses_totales, 2));
        }
    }
}