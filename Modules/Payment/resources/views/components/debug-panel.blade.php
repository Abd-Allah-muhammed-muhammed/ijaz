@props(['payment'])

@if(app()->environment('local') && config('app.debug'))
    @php
        $debug = \Modules\Payment\DTOs\PaymentDebugData::fromPayment($payment);

        $driverColors = [
            'rajhi' => '#16a34a',
            'paytabs' => '#2563eb',
            'testing' => '#6b7280',
        ];
        $driverColor = $driverColors[$debug->driver] ?? '#6b7280';
    @endphp

    <div style="margin-top: 2rem; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;">
        <div style="background: #fef3c7; color: #92400e; padding: 0.5rem 1rem; font-size: 0.8rem; font-weight: 600;">
            ⚠ Debug mode — visible to developers only. Never shown in production.
        </div>

        <div style="padding: 1rem; background: #fff;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                <span style="background: {{ $driverColor }}; color: white; padding: 2px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                    {{ $debug->driver }}
                </span>
                <span style="font-size: 0.85rem; color: #374151;">
                    Status: <strong>{{ $debug->status }}</strong>
                </span>
            </div>

            <details style="margin-bottom: 0.5rem;" open>
                <summary style="cursor: pointer; font-weight: 600; padding: 0.5rem 0; font-size: 0.9rem;">Meta</summary>
                <pre style="background:#0f172a; color:#e2e8f0; padding:1rem; border-radius:8px; overflow-x:auto; font-size:0.8rem; line-height:1.5;">{{ json_encode($debug->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
            </details>

            @if($debug->request)
                <details style="margin-bottom: 0.5rem;">
                    <summary style="cursor: pointer; font-weight: 600; padding: 0.5rem 0; font-size: 0.9rem;">Request</summary>
                    <pre style="background:#0f172a; color:#e2e8f0; padding:1rem; border-radius:8px; overflow-x:auto; font-size:0.8rem; line-height:1.5;">{{ json_encode($debug->request, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                </details>
            @endif

            @if($debug->response)
                <details>
                    <summary style="cursor: pointer; font-weight: 600; padding: 0.5rem 0; font-size: 0.9rem;">Response</summary>
                    <pre style="background:#0f172a; color:#e2e8f0; padding:1rem; border-radius:8px; overflow-x:auto; font-size:0.8rem; line-height:1.5;">{{ json_encode($debug->response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                </details>
            @endif
        </div>
    </div>
@endif
