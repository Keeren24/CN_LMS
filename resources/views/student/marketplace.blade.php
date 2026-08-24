@extends('layouts/layoutMaster')

@section('title', 'Marketplace')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm overflow-hidden position-relative"
             style="background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%);">
            <div class="card-body py-3 px-4 d-flex align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md flex-shrink-0">
                        <span class="avatar-initial rounded-circle" style="background:rgba(255,255,255,.3);">
                            <i class="ti ti-coin text-white ti-md"></i>
                        </span>
                    </div>
                    <div>
                        <div class="text-white opacity-75 small mb-0">Your Ninja Coins</div>
                        <div class="text-white fw-bold" style="font-size:1.6rem;line-height:1.1;" id="walletBalanceDisplay">
                            {{ number_format($walletBalance) }}
                        </div>
                    </div>
                </div>
                <div class="text-white opacity-75 small">
                    <i class="ti ti-shopping-bag me-1"></i>Redeem rewards below
                </div>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-pills mb-4">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#browsePane" type="button">
            <i class="ti ti-shopping-bag me-1"></i>Marketplace
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#historyPane" type="button">
            <i class="ti ti-history me-1"></i>Redemption History
        </button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="browsePane">
        <div class="row g-4">
            @forelse ($items as $item)
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div style="height:140px;background:#f5f5f9;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                            @if ($item->image_path)
                                <img src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->name }}" style="width:100%;height:100%;object-fit:cover;">
                            @else
                                <i class="ti ti-gift ti-48px opacity-25"></i>
                            @endif
                        </div>
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-label-info mb-2 align-self-start">{{ $item->categoryLabel() }}</span>
                            <div class="fw-semibold">{{ $item->name }}</div>
                            @if ($item->description)
                                <div class="text-muted small mb-2">{{ $item->description }}</div>
                            @endif
                            <div class="mt-auto">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="fw-bold"><i class="ti ti-coin text-warning me-1"></i>{{ number_format($item->cost) }}</span>
                                    <span class="text-muted small">
                                        {{ $item->stock === null ? 'Unlimited' : $item->stock . ' left' }}
                                    </span>
                                </div>
                                <button
                                    class="btn btn-primary w-100 btn-redeem"
                                    data-id="{{ $item->id }}"
                                    data-name="{{ $item->name }}"
                                    data-cost="{{ $item->cost }}"
                                    @disabled(! $item->isInStock() || $walletBalance < $item->cost)
                                >
                                    @if (! $item->isInStock())
                                        Out of Stock
                                    @elseif ($walletBalance < $item->cost)
                                        Not Enough Coins
                                    @else
                                        Redeem
                                    @endif
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center text-muted py-5">
                            <i class="ti ti-shopping-bag-off ti-48px d-block mb-2 opacity-25"></i>Nothing in the Marketplace yet — check back soon!
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <div class="tab-pane fade" id="historyPane">
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-end">Cost</th>
                            <th>Status</th>
                            <th>Redeemed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($history as $h)
                            <tr>
                                <td>{{ $h->item_name }}</td>
                                <td class="text-end">{{ number_format($h->cost_paid) }}</td>
                                <td>
                                    @php
                                        $statusColor = match ($h->status) { 'fulfilled' => 'success', 'cancelled' => 'danger', default => 'warning' };
                                    @endphp
                                    <span class="badge bg-label-{{ $statusColor }}">{{ ucfirst($h->status) }}</span>
                                </td>
                                <td>{{ $h->redeemed_at->format('d M Y, h:ia') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">
                                    <i class="ti ti-history-off ti-48px d-block mb-2 opacity-25"></i>No redemptions yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</div>
@endsection

@section('page-script')
<script>
(function () {
    document.querySelectorAll('.btn-redeem').forEach(function (btn) {
        btn.addEventListener('click', function () {
            Swal.fire({
                title: `Redeem "${btn.dataset.name}"?`,
                text: `This will cost ${btn.dataset.cost} Ninja Coins.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Redeem',
            }).then(function (result) {
                if (!result.isConfirmed) return;

                fetch(`{{ url('/marketplace') }}/${btn.dataset.id}/redeem`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                })
                .then(r => r.json().then(data => ({ ok: r.ok, data })))
                .then(({ ok, data }) => {
                    if (ok && data.success) {
                        Swal.fire('Redeemed!', data.message, 'success').then(() => window.location.reload());
                    } else {
                        Swal.fire('Could not redeem', data.message || 'Something went wrong.', 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'Something went wrong.', 'error'));
            });
        });
    });
})();
</script>
@endsection
