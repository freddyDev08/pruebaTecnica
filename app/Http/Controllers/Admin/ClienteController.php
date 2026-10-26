<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\FakeApi\ClienteSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->allows('clients.view'), 403);

        return view('admin.clientes.index', [
            'total' => Cliente::count(),
            'ultimaSync' => DB::table('fakeapi_sync_runs')->where('resource', 'users')
                ->where('status', 'success')->orderByDesc('id')->first(),
            'puedeSincronizar' => $request->user()->allows('sync.run'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        abort_unless($request->user()->allows('clients.view'), 403);

        return response()->json([
            'data' => Cliente::orderBy('external_id')->get([
                'external_id', 'name', 'first_name', 'last_name', 'username',
                'email', 'phone', 'dob', 'avatar', 'gender', 'age', 'locale',
                'address', 'company', 'job_title', 'source_created_at',
                'synced_at', 'raw_data',
            ]),
        ]);
    }

    public function sync(Request $request, ClienteSyncService $service): JsonResponse
    {
        abort_unless($request->user()->allows('sync.run'), 403);

        try {
            $result = $service->sync($request->user()->id);
            return response()->json(['message' => 'Clientes sincronizados correctamente.', 'result' => $result]);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'No fue posible completar la sincronización. Consulta el registro del servidor para más detalles.'], 502);
        }
    }
}
