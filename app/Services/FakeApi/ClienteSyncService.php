<?php

namespace App\Services\FakeApi;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ClienteSyncService
{
    public function sync(?int $initiatedBy = null): array
    {
        $started = now();
        $runId = DB::table('fakeapi_sync_runs')->insertGetId([
            'resource' => 'users', 'status' => 'running', 'requests' => 0,
            'received' => 0, 'skipped' => 0, 'initiated_by' => $initiatedBy,
            'started_at' => $started, 'created_at' => $started, 'updated_at' => $started,
        ]);
        $requests = 0;
        $received = 0;
        $skipped = 0;
        $page = 1;
        $limit = 100;
        $key = trim((string) config('services.fakeapi.key'));
        $url = rtrim((string) config('services.fakeapi.base_url'), '/') . '/users';

        try {
            do {
                $client = Http::acceptJson()->timeout(20)->connectTimeout(8)
                    ->retry(3, 1000);
                if ($key !== '') {
                    $client = $client->withToken($key);
                }

                $requests++;
                $response = $client->get($url, ['page' => $page, 'per_page' => $limit]);
                $response->throw();
                $body = $response->json();
                if (!is_array($body) || !isset($body['data']) || !is_array($body['data'])) {
                    throw new RuntimeException('La API devolvió una respuesta inesperada en la página ' . $page . '.');
                }

                $timestamp = now()->toDateTimeString();
                $rows = [];
                foreach ($body['data'] as $user) {
                    $received++;
                    if (!is_array($user) || !isset($user['id']) || (!is_int($user['id']) && !is_string($user['id'])) || !ctype_digit((string) $user['id']) || (int) $user['id'] < 1) {
                        $skipped++;
                        continue;
                    }
                    $age = $user['age'] ?? null;
                    $age = filter_var($age, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 65535]]);
                    $address = $user['address'] ?? null;
                    $rows[(int) $user['id']] = [
                        'external_id' => (int) $user['id'],
                        'name' => $this->text($user['name'] ?? null),
                        'first_name' => $this->text($user['first_name'] ?? null),
                        'last_name' => $this->text($user['last_name'] ?? null),
                        'username' => $this->text($user['username'] ?? null),
                        'email' => $this->text($user['email'] ?? null),
                        'phone' => $this->text($user['phone'] ?? null),
                        'dob' => $this->text($user['dob'] ?? null),
                        'avatar' => $this->text($user['avatar'] ?? null, 2048),
                        'gender' => $this->text($user['gender'] ?? null),
                        'age' => $age === false ? null : $age,
                        'locale' => $this->text($user['locale'] ?? null),
                        'address' => $address === null ? null : $this->json($address),
                        'company' => $this->text($user['company']['name'] ?? $user['company'] ?? null),
                        'job_title' => $this->text($user['job_title'] ?? null),
                        'source_created_at' => $this->text($user['created_at'] ?? null),
                        'raw_data' => $this->json($user),
                        'synced_at' => $timestamp,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }

                if ($rows !== []) {
                    DB::table('clientes')->upsert(array_values($rows), ['external_id'], [
                        'name', 'first_name', 'last_name', 'username', 'email', 'phone', 'dob', 'avatar',
                        'gender', 'age', 'locale', 'address', 'company', 'job_title',
                        'source_created_at', 'raw_data', 'synced_at', 'updated_at',
                    ]);
                }
                $meta = $body['meta'] ?? [];
                $hasMore = $meta['has_more'] ?? ($page < (int) ($meta['total_pages'] ?? $page));
                $page++;
                if ($page > 5000) {
                    throw new RuntimeException('Se alcanzó el límite de páginas de seguridad.');
                }
            } while ($hasMore === true || $hasMore === 1);

            DB::table('fakeapi_sync_runs')->where('id', $runId)->update([
                'status' => 'success', 'requests' => $requests, 'received' => $received,
                'skipped' => $skipped, 'finished_at' => now(), 'updated_at' => now(),
            ]);

            return ['requests' => $requests, 'received' => $received, 'skipped' => $skipped, 'total' => DB::table('clientes')->count()];
        } catch (Throwable $exception) {
            DB::table('fakeapi_sync_runs')->where('id', $runId)->update([
                'status' => 'failed', 'requests' => $requests, 'received' => $received,
                'skipped' => $skipped, 'error' => substr($exception->getMessage(), 0, 2000),
                'finished_at' => now(), 'updated_at' => now(),
            ]);
            Log::error('Falló la sincronización de clientes de FakeAPI', ['exception' => $exception->getMessage()]);
            throw $exception;
        }
    }

    private function text(mixed $value, int $max = 255): ?string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }
        $value = trim((string) $value);
        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    private function json(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
    }
}
