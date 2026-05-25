<div class="fraud-compact">
    {{-- Risk Score Header --}}
    <div class="fc-header text-center mb-2">
        <span class="fc-score text-{{ $analysisData['basic_risk']['badge_class'] }}">
            {{ $analysisData['basic_risk']['score'] }}%
        </span>
        <span class="fc-level text-{{ $analysisData['basic_risk']['badge_class'] }}">
            {{ ucfirst($analysisData['basic_risk']['level']) }} Risk
        </span>
        <div class="text-muted" style="font-size:12px;">{{ $analysisData['phone'] }}</div>
    </div>

    @php
        $riskSource = $analysisData['detailed_risk']['source'] ?? null;
        $isCourierHistory = in_array($riskSource, ['fraudchecker_qc', 'bdcourier'], true);
    @endphp

    {{-- Courier Stats Row --}}
    @if($isCourierHistory)
    <div class="d-flex gap-2 mb-2">
        <div class="fc-stat flex-fill text-center">
            <div class="fc-stat-num">{{ $analysisData['detailed_risk']['total_parcels'] }}</div>
            <div class="fc-stat-label">Total</div>
        </div>
        <div class="fc-stat flex-fill text-center">
            <div class="fc-stat-num text-success">{{ $analysisData['detailed_risk']['total_delivered'] }}</div>
            <div class="fc-stat-label">Delivered ({{ $analysisData['detailed_risk']['delivered_pct'] }}%)</div>
        </div>
        <div class="fc-stat flex-fill text-center">
            <div class="fc-stat-num text-danger">{{ $analysisData['detailed_risk']['total_cancel'] }}</div>
            <div class="fc-stat-label">Cancelled ({{ $analysisData['detailed_risk']['cancel_pct'] }}%)</div>
        </div>
    </div>

    <div class="progress mb-2" style="height:6px;border-radius:3px;">
        <div class="progress-bar bg-success" style="width:{{ $analysisData['detailed_risk']['delivered_pct'] }}%"></div>
        <div class="progress-bar bg-danger" style="width:{{ $analysisData['detailed_risk']['cancel_pct'] }}%"></div>
        @if(($analysisData['detailed_risk']['other_pct'] ?? 0) > 0)
        <div class="progress-bar bg-secondary" style="width:{{ $analysisData['detailed_risk']['other_pct'] }}%"></div>
        @endif
    </div>
    @endif

    {{-- Recommendations --}}
    @if(!empty($recommendations))
    <div class="mb-2">
        @foreach($recommendations as $rec)
        <div class="fc-rec fc-rec-{{ $rec['color'] }}">
            <i class="{{ $rec['icon'] }}"></i>
            <strong>{{ ucfirst($rec['type']) }}:</strong> {{ $rec['message'] }}
        </div>
        @endforeach
    </div>
    @endif

    {{-- Courier Breakdown Table --}}
    @if($isCourierHistory)
    <div class="fc-section-title">
        <i class="fe-activity"></i> Courier History
    </div>
    <table class="table table-sm fc-table mb-2">
        <thead>
            <tr>
                <th>Courier</th>
                <th class="text-end">Total</th>
                <th class="text-end">Delivered</th>
                <th class="text-end">Cancelled</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($analysisData['detailed_risk']['couriers'] ?? []) as $courier)
            <tr>
                <td>
                    @if(!empty($courier['logo']))
                    <img src="{{ $courier['logo'] }}" alt="" style="height:14px;width:auto;" class="me-1">
                    @endif
                    {{ $courier['name'] }}
                </td>
                <td class="text-end">{{ $courier['total_parcels'] }}</td>
                <td class="text-end text-success">{{ $courier['delivered_parcels'] }} <small class="text-muted">({{ $courier['delivered_pct'] }}%)</small></td>
                <td class="text-end text-danger">{{ $courier['cancelled_parcels'] }} <small class="text-muted">({{ $courier['cancelled_pct'] }}%)</small></td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center text-muted">No courier data</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Reports --}}
    @if($riskSource === 'bdcourier' && !empty($analysisData['detailed_risk']['reports']))
    <div class="fc-section-title mt-2"><i class="fe-flag"></i> Reports</div>
    @foreach(($analysisData['detailed_risk']['reports'] ?? []) as $report)
    <div class="fc-report">
        <div class="d-flex align-items-center gap-1">
            @if(!empty($report['courierLogo']))
            <img src="{{ $report['courierLogo'] }}" alt="" style="height:14px;width:auto;">
            @endif
            <strong>{{ $report['courierName'] ?? 'Courier' }}</strong>
            <small class="text-muted ms-auto">{{ $report['created_at'] ?? '' }}</small>
        </div>
        <div style="font-size:12px;margin-top:2px;">
            <strong>{{ $report['name'] ?? 'Report' }}:</strong> {{ $report['details'] ?? '' }}
        </div>
    </div>
    @endforeach
    @endif

    @else
        {{-- Phone Analysis (non-courier) --}}
        @if(!empty($analysisData['detailed_risk']))
            @php $vs = $analysisData['detailed_risk']['verification_status'] ?? 'Unknown'; @endphp
            <div class="fc-section-title"><i class="fe-activity"></i> Phone Analysis</div>
            <table class="table table-sm fc-table mb-2">
                <tr>
                    <td><strong>Verification</strong></td>
                    <td class="text-end">
                        @if($vs === 'Unknown')
                            <span class="badge bg-secondary">{{ $vs }}</span>
                        @elseif($vs)
                            <span class="badge bg-success">Verified</span>
                        @else
                            <span class="badge bg-danger">Not Verified</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td><strong>Carrier</strong></td>
                    <td class="text-end">{{ $analysisData['detailed_risk']['carrier'] ?? 'Unknown' }}</td>
                </tr>
                <tr>
                    <td><strong>Country</strong></td>
                    <td class="text-end">{{ $analysisData['detailed_risk']['country'] ?? 'Unknown' }}</td>
                </tr>
                <tr>
                    <td><strong>Line Type</strong></td>
                    <td class="text-end">{{ $analysisData['detailed_risk']['line_type'] ?? 'Unknown' }}</td>
                </tr>
                <tr>
                    <td><strong>Reputation</strong></td>
                    <td class="text-end">{{ $analysisData['detailed_risk']['reputation_score'] ?? 0 }}/100</td>
                </tr>
                <tr>
                    <td><strong>Blacklist</strong></td>
                    <td class="text-end">
                        @if(!empty($analysisData['detailed_risk']['blacklist_status']))
                            <span class="badge bg-danger">Listed</span>
                        @else
                            <span class="badge bg-success">Clean</span>
                        @endif
                    </td>
                </tr>
            </table>

            @if(!empty($analysisData['detailed_risk']['fraud_indicators']))
            <div class="fc-section-title"><i class="fe-alert-triangle text-warning"></i> Fraud Indicators</div>
            <ul class="fc-indicators">
                @foreach($analysisData['detailed_risk']['fraud_indicators'] as $indicator)
                <li>{{ $indicator }}</li>
                @endforeach
            </ul>
            @endif
        @else
            <div class="text-center text-muted py-3">
                <i class="fe-info" style="font-size:1.5rem;"></i>
                <p style="font-size:12px;margin-top:4px;">
                    Detailed analysis not available.
                    @if(!$analysisData['success'])
                        {{ $analysisData['message'] }}
                    @else
                        API may not be configured.
                    @endif
                </p>
            </div>
        @endif
    @endif

    {{-- Footer --}}
    <div class="text-center text-muted mt-2" style="font-size:11px;">
        <i class="fe-clock"></i> {{ $analysisData['analysis_time'] }}
    </div>
</div>

<style>
.fraud-compact {
    font-size: 13px;
    line-height: 1.4;
}
.fc-header {
    padding: 8px 0 4px;
}
.fc-score {
    font-size: 28px;
    font-weight: 800;
    display: block;
    line-height: 1;
}
.fc-level {
    font-size: 14px;
    font-weight: 600;
}
.fc-stat {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 6px 4px;
}
.fc-stat-num {
    font-size: 18px;
    font-weight: 700;
    line-height: 1.2;
}
.fc-stat-label {
    font-size: 11px;
    color: #6c757d;
}
.fc-rec {
    padding: 6px 10px;
    border-radius: 5px;
    font-size: 12px;
    margin-bottom: 4px;
}
.fc-rec-warning { background: #fff3cd; color: #856404; }
.fc-rec-danger  { background: #f8d7da; color: #721c24; }
.fc-rec-info    { background: #d1ecf1; color: #0c5460; }
.fc-rec-success { background: #d4edda; color: #155724; }
.fc-section-title {
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 4px;
    color: #495057;
}
.fc-table {
    margin-bottom: 0 !important;
}
.fc-table td, .fc-table th {
    padding: 4px 8px !important;
    font-size: 12px;
    vertical-align: middle;
    border-color: #f0f0f0;
}
.fc-table thead th {
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    font-weight: 600;
}
.fc-report {
    background: #f8f9fa;
    border-radius: 5px;
    padding: 6px 8px;
    margin-bottom: 4px;
    font-size: 12px;
}
.fc-indicators {
    list-style: none;
    padding: 0;
    margin: 0;
}
.fc-indicators li {
    font-size: 12px;
    padding: 2px 0;
}
.fc-indicators li::before {
    content: "⚠ ";
}
.fraud-compact .badge {
    font-size: 11px;
    padding: 3px 6px;
}
</style>
