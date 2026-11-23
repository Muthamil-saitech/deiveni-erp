@forelse($logs as $log)
    <tr class="audit-row" 
        data-id="{{ $log->event_id }}" 
        data-db-id="{{ $log->id }}"
        data-module="{{ $log->module }}" 
        data-action="{{ $log->action }}" 
        data-user="{{ $log->user_name }}" 
        data-severity="{{ $log->severity }}" 
        data-site="{{ $log->site_name ?: 'Head Office' }}">
        
        <td>
            <div class="fw-semibold text-dark">{{ $log->created_at ? $log->created_at->format('d M Y, h:i A') : 'N/A' }}</div>
            <small class="text-muted"><i class="bi bi-clock me-1"></i>{{ $log->created_at ? $log->created_at->diffForHumans() : '' }}</small>
        </td>

        <td>
            <div class="d-flex align-items-center">
                <div class="rounded-circle {{ $log->avatar_bg }} d-flex align-items-center justify-content-center fw-bold me-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                    {{ $log->user_initials }}
                </div>
                <div>
                    <div class="fw-bold text-dark">{{ $log->user_name ?: 'System' }}</div>
                    <small class="text-muted d-block" style="font-size: 0.72rem;">
                        {{ ucwords(str_replace('_', ' ', (string) ($log->user_role ?: 'User'))) }} &bull; {{ $log->ip_address ?: '127.0.0.1' }}
                    </small>
                </div>
            </div>
        </td>

        <td>
            @php
                $moduleConfig = match($log->module) {
                    'site_cash' => ['class' => 'bg-success-subtle text-success border border-success-subtle', 'icon' => 'bi bi-cash-stack', 'label' => 'Site Cash'],
                    'daily_wages' => ['class' => 'bg-primary-subtle text-primary border border-primary-subtle', 'icon' => 'bi bi-journal-check', 'label' => 'Daily Wages'],
                    'purchases' => ['class' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'icon' => 'bi bi-cart-check', 'label' => 'Purchases'],
                    'ledger' => ['class' => 'bg-info-subtle text-info border border-info-subtle', 'icon' => 'bi bi-book-half', 'label' => 'Daily Ledger'],
                    'sites' => ['class' => 'bg-primary-subtle text-primary border', 'icon' => 'bi bi-building-gear', 'label' => 'Project Sites'],
                    'warehouse' => ['class' => 'bg-secondary-subtle text-secondary border border-secondary-subtle', 'icon' => 'bi bi-shop-window', 'label' => 'Warehouse Store'],
                    'masters' => ['class' => 'bg-info-subtle text-info-emphasis border border-info-subtle', 'icon' => 'bi bi-layers', 'label' => 'Masters'],
                    'users' => ['class' => 'bg-dark-subtle text-dark border', 'icon' => 'bi bi-shield-lock', 'label' => 'Users & RBAC'],
                    'auth' => ['class' => 'bg-secondary-subtle text-dark border', 'icon' => 'bi bi-shield-check', 'label' => 'Auth Service'],
                    default => ['class' => 'bg-light text-dark border', 'icon' => 'bi bi-activity', 'label' => ucfirst((string) $log->module)],
                };
            @endphp
            <span class="badge {{ $moduleConfig['class'] }} mb-1">
                <i class="{{ $moduleConfig['icon'] }} me-1"></i>{{ $moduleConfig['label'] }}
            </span>
            <div class="font-monospace small text-muted">{{ $log->target_id ?: ('#'.class_basename($log->target_type ?: 'ID').'-'.$log->id) }}</div>
        </td>

        <td>
            <span class="badge {{ $log->action_badge_class }} fw-semibold px-2 py-1">
                <i class="{{ $log->action_icon }} me-1"></i>{{ $log->action }}
            </span>
            <div class="small text-muted mt-1">
                <span class="severity-dot severity-dot-{{ $log->severity }}"></span>{{ ucfirst($log->severity) }}
            </div>
        </td>

        <td>
            <div class="fw-semibold text-dark">{{ $log->title }}</div>
            @if(!empty($log->title_ta))
                <small class="ta-text text-muted d-block">{{ $log->title_ta }}</small>
            @endif

            @if(!empty($log->old_values) && !empty($log->new_values))
                <div class="diff-box p-1.5 mt-1 d-inline-block">
                    @foreach(array_slice($log->new_values, 0, 2, true) as $fKey => $fVal)
                        @php
                            $oldFVal = $log->old_values[$fKey] ?? null;
                            $oldValStr = is_array($oldFVal) ? json_encode($oldFVal) : ($oldFVal !== null ? (string)$oldFVal : 'None');
                            $newValStr = is_array($fVal) ? json_encode($fVal) : (string)$fVal;
                        @endphp
                        <span class="text-muted me-1">{{ $fKey }}:</span>
                        <span class="diff-old">{{ $oldValStr }}</span> &rarr;
                        <span class="diff-new">{{ $newValStr }}</span>
                    @endforeach
                </div>
            @elseif($log->amount !== null && (float)$log->amount > 0)
                <span class="badge bg-light text-secondary border font-monospace mt-1" style="font-size:0.75rem;">
                    Tracked Value: ₹{{ number_format($log->amount, 2) }}
                </span>
            @endif
        </td>

        <td>
            <span class="badge bg-primary-subtle text-primary">
                <i class="bi bi-geo-alt-fill me-1"></i>{{ $log->site_name ?: 'Head Office' }}
            </span>
        </td>

        <td class="text-end">
            <button class="btn btn-sm btn-outline-primary view-audit-btn" data-id="{{ $log->id }}" title="View Full Audit Snapshot">
                <i class="bi bi-eye-fill"></i> View
            </button>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="p-5 text-center">
            <i class="bi bi-journal-x text-muted fs-1 mb-2 d-block"></i>
            <h6 class="fw-bold text-secondary mb-1">No Audit Records Found</h6>
            <p class="text-muted small mb-0">No activities match your current search and filter criteria.</p>
        </td>
    </tr>
@endforelse

